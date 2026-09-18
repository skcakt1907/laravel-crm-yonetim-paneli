<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Uye;
use App\Models\Ayar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cookie;
use App\Services\SmsService;
use App\Services\EmailNotificationService;
use App\Services\NotificationMailer;

class AuthController extends Controller
{
    private function tryBayiLogin(Request $request, string $loginInput)
    {
        $bayiYonetici = DB::table('yoneticiler')
            ->where(function ($query) use ($loginInput) {
                $query->where('kullaniciadi', $loginInput)
                    ->orWhere('email', $loginInput)
                    ->orWhere('eposta', $loginInput);
            })
            ->where('durum', 1)
            ->where('rol', 3)
            ->first();

        if (!$bayiYonetici) {
            return null;
        }

        $sifreDogru = false;
        if ($bayiYonetici->sifre === md5($request->password)) {
            $sifreDogru = true;
        } else {
            try {
                $sifreDogru = Hash::check($request->password, $bayiYonetici->sifre);
            } catch (\Throwable $e) {
                $sifreDogru = false;
            }
        }

        if (!$sifreDogru) {
            return null;
        }

        // Bayi admin girişinde eski üye oturum kalıntılarını temizle.
        Auth::guard('uye')->logout();
        session()->forget(['uye_logged_in', 'onceki_son_giris']);

        $aktifBayi = DB::table('bayiler')
            ->where('yonetici_id', $bayiYonetici->id)
            ->where('durum', 1)
            ->first();

        // Fallback: yonetici email -> uye -> bayi.uye_id
        if (!$aktifBayi) {
            $yoneticiEmail = $bayiYonetici->email ?? $bayiYonetici->eposta ?? null;
            if ($yoneticiEmail) {
                $uyeKaydi = DB::table('uyeler')
                    ->where('email', $yoneticiEmail)
                    ->first();

                if ($uyeKaydi) {
                    $aktifBayi = DB::table('bayiler')
                        ->where('uye_id', $uyeKaydi->id)
                        ->where('durum', 1)
                        ->first();
                }
            }
        }

        if (!$aktifBayi) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Bayi hesabı aktif değil veya bayi kaydı bulunamadı.');
        }

        session()->regenerate(); // Session fixation koruması
        session()->put('admin_logged_in', true);
        session()->put('admin_id', $bayiYonetici->id);
        session()->put('admin_kullanici_adi', $bayiYonetici->kullaniciadi);
        session()->put('admin_adi', $bayiYonetici->adi ?? $bayiYonetici->kullaniciadi);
        session()->put('admin_yetki', $bayiYonetici->yetki ?? 1);
        session()->put('admin_rol', 3);
        session()->save();

        DB::table('yoneticiler')
            ->where('id', $bayiYonetici->id)
            ->update([
                'son_giris' => now('Europe/Istanbul'),
                'son_ip' => $request->ip(),
            ]);

        return redirect()->route('admin.bayi.dashboard')->with('success', 'Hoş geldiniz!');
    }

    public function giris()
    {
        // Session'ı yenile (CSRF token'ın düzgün oluşması için)
        session()->save();
        
        try {
            $ayar = Ayar::first();
            if (!$ayar) {
                $ayar = new \App\Models\Ayar();
            }
        } catch (\Exception $e) {
            $ayar = new \App\Models\Ayar();
        }
        
        // 419 önleme: tarayıcı bu sayfayı cache'lemesin (her seferinde fresh CSRF token)
        return response()
            ->view('tema.giris', compact('ayar'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    
    public function girisPost(Request $request)
    {
        try {
            // Güvenlik: normal kullanıcı girişinde admin session taşınmasını engelle.
            session()->forget([
                'admin_logged_in',
                'admin_id',
                'admin_kullanici_adi',
                'admin_adi',
                'admin_yetki',
                'admin_rol',
            ]);

            $hasKullaniciAdiColumn = false;
            try {
                $hasKullaniciAdiColumn = \DB::getSchemaBuilder()->hasColumn('uyeler', 'kullanici_adi');
            } catch (\Exception $e) {
                $hasKullaniciAdiColumn = false;
            }

            // Validation
            $validated = $request->validate([
                'email' => 'required|string|max:255',
                'password' => 'required|string|min:6',
            ], [
                'email.required' => __('messages.email_or_username_required'),
                'password.required' => __('messages.password_required'),
            ]);
            
            // Manuel giriş kontrolü (çünkü field isimleri farklı)
            $loginInput = trim((string) $request->email);
            
            if (empty($loginInput)) {
                return redirect('/giris?hata=' . urlencode('E-posta veya kullanıcı adı boş olamaz.'))
                    ->withInput($request->only('email'));
            }
            
            if (empty($request->password)) {
                return redirect('/giris?hata=' . urlencode('Şifre boş olamaz.'))
                    ->withInput($request->only('email'));
            }
        
        // Giriş tipini kontrol et (email mi kullanıcı adı mı?)
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        
        // Tablo kontrolü
        $uye = null;
        try {
            if (\DB::getSchemaBuilder()->hasTable('uyeler')) {
                $query = Uye::where('email', $loginInput);
                if ($hasKullaniciAdiColumn) {
                    $query->orWhere('kullanici_adi', $loginInput);
                }
                $uye = $query->first();
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }
        
        if (!$uye) {
            // Üye yok — ama girilen bilgi bir YÖNETİCİ hesabına ait olabilir.
            // Tek giriş ekranı: admin bilgileriyle de buradan girilebilsin.
            if ($this->adminHesabiVarMi($loginInput)) {
                return $this->adminGirisineDevret($request, $loginInput);
            }

            // Kullanıcı bulunamadı - daha spesifik mesaj göster
            if ($isEmail) {
                $errorMessage = 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı. Lütfen e-posta adresinizi kontrol edin.';
            } else {
                $errorMessage = 'Bu kullanıcı adı ile kayıtlı kullanıcı bulunamadı. Lütfen kullanıcı adınızı kontrol edin.';
            }

            \App\Helpers\GirisLogHelper::kaydet('uye', 'basarisiz', $loginInput, null, null, 'Kullanıcı bulunamadı');
            return redirect('/giris?hata=' . urlencode($errorMessage))
                ->withInput($request->only('email'));
        }
        
        // Hesap pasif
        if ($uye->durum != 1) {
            \App\Helpers\GirisLogHelper::kaydet('uye', 'bloke', $loginInput, $uye->ad ?? $uye->kullanici_adi ?? null, $uye->id, 'Hesap pasif/engelli');
            return redirect('/giris?hata=' . urlencode('Hesabınız pasif durumda. Lütfen destek ile iletişime geçin.'))
                ->withInput($request->only('email'));
        }
        
        // Şifre kontrolü - eski ve yeni formatları destekle
        $sifreDogrulandi = false;
        
        // Yeni Bcrypt formatı kontrolü
        try {
            if (Hash::check($request->password, $uye->sifre)) {
                $sifreDogrulandi = true;
            }
        } catch (\Exception $e) {
            // Bcrypt hatası varsa eski formatı kontrol et
        }

        // Eski format kontrolü - giriş başarılıysa bcrypt'e migrate et
        if (!$sifreDogrulandi) {
            // MD5 kontrolü
            if (strlen($uye->sifre) === 32 && md5($request->password) === $uye->sifre) {
                $sifreDogrulandi = true;
                $uye->update(['sifre' => Hash::make($request->password)]);
                \Log::info('Kullanıcı şifresi MD5\'ten bcrypt\'e migrate edildi', ['uye_id' => $uye->id]);
            }
            // SHA1 kontrolü
            elseif (strlen($uye->sifre) === 40 && sha1($request->password) === $uye->sifre) {
                $sifreDogrulandi = true;
                $uye->update(['sifre' => Hash::make($request->password)]);
                \Log::info('Kullanıcı şifresi SHA1\'den bcrypt\'e migrate edildi', ['uye_id' => $uye->id]);
            }
            // NOT: Düz metin şifre kontrolü güvenlik riski nedeniyle kaldırıldı
        }
        
        if (!$sifreDogrulandi) {
            // Aynı e-posta hem üye hem yönetici olarak kayıtlı olabilir
            // (ör. patron kendi mailiyle müşteri hesabı da açmışsa). Üye şifresi
            // tutmadıysa, aynı bilgilerle yönetici girişi de denensin.
            if ($this->adminHesabiVarMi($loginInput)) {
                return $this->adminGirisineDevret($request, $loginInput);
            }

            \App\Helpers\GirisLogHelper::kaydet('uye', 'basarisiz', $loginInput, $uye->ad ?? $uye->kullanici_adi ?? null, $uye->id, 'Şifre hatalı');
            return redirect('/giris?hata=' . urlencode('Şifre hatalı! Lütfen şifrenizi kontrol edin.'))
                ->withInput($request->only('email'));
        }
        
        // Önceki son giriş bilgisini al (güncellemeden önce)
        $onceki_son_giris = $uye->son_giris;
        
        // Giriş başarılı
        Auth::guard('uye')->login($uye, $request->filled('remember'));
        \App\Helpers\GirisLogHelper::kaydet('uye', 'basarili', $uye->email ?? $loginInput, $uye->ad ?? $uye->kullanici_adi ?? null, $uye->id, null);
        session()->regenerate(); // Session fixation koruması
        session()->put('uye_logged_in', true);

        session()->save();
        
        // Son giriş bilgilerini güncelle (bir sonraki giriş için)
        $uye->update([
            'son_giris' => now('Europe/Istanbul')->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
        ]);

        // Yeni giriş bildirimi maili (fail-safe)
        NotificationMailer::loginNotice($uye, $request->ip());

        // Önceki son giriş bilgisini session'a kaydet (göstermek için)
        session()->put('onceki_son_giris', $onceki_son_giris);
        
        // Session'ı tekrar kaydet (redirect'ten önce)
        session()->save();
        
        // Redirect parametresi varsa oraya yönlendir
        $redirect = $request->input('redirect');
        if ($redirect) {
            $ayrac = (strpos($redirect, '?') !== false) ? '&' : '?';
            return redirect($redirect . $ayrac . 'hosgeldin=1')->with('success', 'Hoş geldiniz!');
        }
        
        // Bayi ise direkt hesabim'e yönlendir (basari mesaji query ile de tasinir)
        $redirectUrl = route('hesabim') . '?hosgeldin=1';
        
        // Session'ı tekrar kaydet (redirect'ten önce)
        session()->save();
        
        return redirect($redirectUrl)->with('success', 'Hoş geldiniz!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation hatası
            return redirect('/giris?hata=' . urlencode('Lütfen formu doldurun.'))
                ->withInput($request->only('email'))
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            // Genel hata
            \Log::error('Giriş hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except('password')
            ]);
            
            return redirect('/giris?hata=' . urlencode('Giriş yapılırken bir hata oluştu. Lütfen tekrar deneyin.'))
                ->withInput($request->only('email'));
        }
    }

    /* ═══════════ TEK GİRİŞ EKRANI: /giris'ten yönetici girişi ═══════════ */

    /**
     * Girilen kullanıcı adı/e-posta AKTİF bir yönetici hesabına ait mi?
     *
     * Sadece VARLIK kontrolü yapar, şifreye bakmaz. Şifre doğrulaması
     * AdminAuthController@girisPost'a bırakılır — böylece hash migrasyonu,
     * loglama ve oturum kurulumu tek yerde kalır (kopyalanmaz).
     *
     * Şifre burada kontrol edilmediği için, eşleşme yoksa yönetici tarafına
     * hiç gidilmez; yani /giris'te yapılan her hatalı denemenin giriş
     * loglarına "yonetici basarisiz" olarak düşmesi de önlenmiş olur.
     */
    protected function adminHesabiVarMi(string $loginInput): bool
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('yoneticiler')) {
                return false;
            }

            return DB::table('yoneticiler')
                ->where(function ($q) use ($loginInput) {
                    $q->where('kullaniciadi', $loginInput)
                      ->orWhere('email', $loginInput)
                      ->orWhere('eposta', $loginInput);
                })
                ->where('durum', 1)
                ->exists();
        } catch (\Throwable $e) {
            // Tablo/kolon yoksa müşteri girişi bozulmasın
            return false;
        }
    }

    /**
     * Yönetici girişini AdminAuthController'a devret.
     *
     * Yeni bir Request UYDURULMUYOR; mevcut request'e sadece admin formunun
     * beklediği alan adları ekleniyor. Böylece gerçek IP, oturum ve
     * CSRF doğrulaması aynen korunur. Rol'e göre yönlendirmeyi (patron/çalışan
     * -> dashboard, bayi -> bayi paneli) da AdminAuthController yapar.
     */
    protected function adminGirisineDevret(Request $request, string $loginInput)
    {
        $request->merge([
            'kullanici_adi' => $loginInput,
            'sifre'         => (string) $request->input('password'),
            'beni_hatirla'  => $request->filled('remember') ? 1 : null,
        ]);

        $cevap = app(\App\Http\Controllers\Admin\AdminAuthController::class)->girisPost($request);

        // Başarılıysa (admin oturumu kurulduysa) yönlendirmeyi olduğu gibi geçir:
        // rol'e göre dashboard / bayi paneli ayrımını AdminAuthController yapmıştı.
        if (session('admin_logged_in')) {
            return $cevap;
        }

        // Başarısız: AdminAuthController hatayı flash ile döndürüyor, ama /giris
        // ekranında flash mesajlar yönlendirme zincirinde kayboluyor (bkz.
        // tema/giris.blade.php'deki not — üye girişi de bu yüzden ?hata=
        // kullanıyor). Aynı yönteme çeviriyoruz ki uyarı gerçekten görünsün.
        $mesaj = session('error') ?: 'Kullanıcı adı veya şifre hatalı!';
        session()->forget('error');

        return redirect('/giris?hata=' . urlencode($mesaj))
            ->withInput($request->only('email'));
    }

    public function kayit(Request $request)
    {
        try {
            $ayar = Ayar::first();
            if (!$ayar) {
                $ayar = new \App\Models\Ayar();
            }
        } catch (\Exception $e) {
            $ayar = new \App\Models\Ayar();
        }
        
        $ref = $request->get('ref');
        
        // Referans kodunu session'a kaydet
        if ($ref) {
            session(['referans_kodu' => $ref]);
        }
        
        // 419 önleme: kayıt sayfası da cache'lenmesin
        return response()
            ->view('tema.kayit', compact('ayar', 'ref'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    
    public function kayitPost(Request $request)
    {
        $hasKullaniciAdiColumn = false;
        try {
            $hasKullaniciAdiColumn = \DB::getSchemaBuilder()->hasColumn('uyeler', 'kullanici_adi');
        } catch (\Exception $e) {
            $hasKullaniciAdiColumn = false;
        }

        $rules = [
            'adi' => 'required|string|max:255',
            'soyadi' => 'required|string|max:255',
            'email' => 'required|email|unique:uyeler,email',
            'password' => 'required|min:6|confirmed',
            'telefon' => 'nullable|string',
            'tc' => 'nullable|string|size:11',
            'nereden_duydunuz' => 'nullable|string|max:100',
            // 3 onay zorunlu (accepted = checkbox işaretli olmalı)
            'hizmet_sozlesme'   => 'accepted',
            'gizlilik_sozlesme' => 'accepted',
            'kvkk_onay'         => 'accepted',
        ];

        // Kullanıcı adı artık kayıt formunda yok; email'den otomatik üretilir.
        // Doğum tarihi (dtarih) opsiyonel.
        $rules['dtarih'] = 'nullable|date';

        $validated = $request->validate($rules, [
            'hizmet_sozlesme.accepted'   => 'Üyelik sözleşmesini kabul etmelisiniz.',
            'gizlilik_sozlesme.accepted' => 'Gizlilik politikasını kabul etmelisiniz.',
            'kvkk_onay.accepted'         => 'Hizmet ve Kullanım Sözleşmesini kabul etmelisiniz.',
        ]);
        
        // TC No kontrolü
        if ($request->tc && !tc_no_kontrol($request->tc)) {
            return back()->withErrors(['tc' => 'Geçersiz TC Kimlik Numarası'])->withInput();
        }
        
        // ── SPAM ÖNLEMİ: hesabı DOĞRULANANA KADAR OLUŞTURMA ──
        // Kayıt bilgilerini session'da beklet, e-postaya 6 haneli kod gönder.
        // Hesap ancak kod ekranında doğrulanınca oluşur (kayitDogrula).
        $payload = [
            'ad'               => $validated['adi'],
            'soyad'            => $validated['soyadi'],
            'email'            => $validated['email'],
            'sifre'            => Hash::make($validated['password']), // session'da düz şifre TUTULMAZ
            'telefon'          => $validated['telefon'] ?? null,
            'tc'               => $validated['tc'] ?? null,
            'dtarih'           => $validated['dtarih'] ?? null,
            'nereden_duydunuz' => $validated['nereden_duydunuz'] ?? null,
        ];

        $kod = (string) random_int(100000, 999999);

        /*
         * KANAL SEÇİMİ (18.08.2026): telefon numarası girildiyse doğrulama
         * kodu artık SMS ile gidiyor (kullanıcı isteği — "mail yerine sms
         * olsun"). Telefon boşsa (alan opsiyonel) e-postaya düşer.
         * Kullanılan kanal session'a yazılır ki "Tekrar Gönder" ve
         * doğrulama ekranı AYNI kanalı kullansın.
         */
        $kanal = 'email';
        $smsGitti = false;
        if (!empty($payload['telefon'])) {
            try {
                $sonuc = (new SmsService())->dogrulamaKoduGonder($payload['telefon'], $kod);
                $smsGitti = (bool) ($sonuc['success'] ?? false);
            } catch (\Throwable $e) {
                \Log::warning('Kayıt doğrulama SMS gönderilemedi: ' . $e->getMessage());
            }
        }
        if ($smsGitti) {
            $kanal = 'sms';
        }

        session(['pending_kayit' => [
            'payload'    => $payload,
            'kod'        => $kod,
            'gecerlilik' => now()->addMinutes(15)->timestamp,
            'email'      => $validated['email'],
            'kanal'      => $kanal,
            'ref'        => session('referans_kodu') ?? $request->get('ref'),
            'deneme'     => 0,
        ]]);
        session()->save();

        if ($kanal === 'sms') {
            return redirect()->route('kayit.dogrula')
                ->with('success', 'Telefon numaranıza 6 haneli bir doğrulama kodu SMS olarak gönderdik. Lütfen kodu girin.');
        }

        try {
            NotificationMailer::dogrulamaKodu($validated['email'], $validated['adi'], $kod);
        } catch (\Throwable $e) {
            \Log::error('Doğrulama kodu maili gönderilemedi: ' . $e->getMessage());
        }

        return redirect()->route('kayit.dogrula')
            ->with('success', 'E-posta adresinize 6 haneli bir doğrulama kodu gönderdik. Lütfen kodu girin.');
    }

    /**
     * Kod doğrulama ekranı (kayıt sonrası).
     */
    public function kayitDogrulaGoster(Request $request)
    {
        $pending = session('pending_kayit');
        if (!$pending) {
            return redirect()->route('kayit')->with('error', 'Doğrulama oturumu bulunamadı. Lütfen tekrar kayıt olun.');
        }

        $smsMi = ($pending['kanal'] ?? 'email') === 'sms';

        return view('tema.kayit-dogrula', [
            'kanal'        => $smsMi ? 'sms' : 'email',
            'emailMaskeli' => $smsMi
                ? self::telefonMaskele($pending['payload']['telefon'] ?? '')
                : self::emailMaskele($pending['email'] ?? ''),
        ]);
    }

    /**
     * Kodu doğrula → doğruysa hesabı OLUŞTUR + otomatik giriş.
     */
    public function kayitDogrula(Request $request)
    {
        $request->validate(['kod' => 'required|digits:6']);

        $pending = session('pending_kayit');
        if (!$pending) {
            return redirect()->route('kayit')->with('error', 'Doğrulama oturumu bulunamadı. Lütfen tekrar kayıt olun.');
        }
        if ((int) ($pending['gecerlilik'] ?? 0) < now()->timestamp) {
            return back()->withErrors(['kod' => 'Kodun süresi doldu. "Tekrar Gönder" ile yeni kod alın.']);
        }
        $deneme = (int) ($pending['deneme'] ?? 0);
        if ($deneme >= 5) {
            session()->forget('pending_kayit');
            return redirect()->route('kayit')->with('error', 'Çok fazla hatalı deneme. Lütfen tekrar kayıt olun.');
        }
        if (!hash_equals((string) $pending['kod'], (string) $request->input('kod'))) {
            $pending['deneme'] = $deneme + 1;
            session(['pending_kayit' => $pending]);
            return back()->withErrors(['kod' => 'Kod hatalı. Kalan deneme hakkı: ' . (5 - $pending['deneme'])]);
        }

        return $this->kayitiOlustur($pending['payload'], $pending['ref'] ?? null, $request);
    }

    /**
     * Doğrulama kodunu tekrar gönder.
     */
    public function kayitKodTekrar(Request $request)
    {
        $pending = session('pending_kayit');
        if (!$pending) {
            return redirect()->route('kayit')->with('error', 'Doğrulama oturumu bulunamadı. Lütfen tekrar kayıt olun.');
        }
        $kod = (string) random_int(100000, 999999);
        $pending['kod'] = $kod;
        $pending['gecerlilik'] = now()->addMinutes(15)->timestamp;
        $pending['deneme'] = 0;
        session(['pending_kayit' => $pending]);
        session()->save();

        // İlk gönderimde hangi kanal kullanıldıysa (SMS/e-posta) tekrar gönderim de aynısını kullanır.
        if (($pending['kanal'] ?? 'email') === 'sms' && !empty($pending['payload']['telefon'])) {
            try {
                (new SmsService())->dogrulamaKoduGonder($pending['payload']['telefon'], $kod);
            } catch (\Throwable $e) {
                \Log::warning('Doğrulama kodu SMS tekrar gönderilemedi: ' . $e->getMessage());
            }
            return back()->with('success', 'Yeni doğrulama kodu telefon numaranıza SMS olarak gönderildi.');
        }

        try {
            NotificationMailer::dogrulamaKodu($pending['email'], $pending['payload']['ad'] ?? null, $kod);
        } catch (\Throwable $e) {
            \Log::error('Doğrulama kodu tekrar gönderilemedi: ' . $e->getMessage());
        }
        return back()->with('success', 'Yeni doğrulama kodu e-posta adresinize gönderildi.');
    }

    /**
     * Doğrulama başarılı → hesabı oluştur + yan etkiler + otomatik giriş.
     */
    protected function kayitiOlustur(array $payload, ?string $ref, Request $request)
    {
        // Doğrulama beklenirken aynı e-posta başkasınca alınmış olabilir
        if (Uye::where('email', $payload['email'])->exists()) {
            session()->forget('pending_kayit');
            return redirect()->route('giris')->with('error', 'Bu e-posta zaten kayıtlı. Lütfen giriş yapın.');
        }

        $hasKullaniciAdiColumn = false;
        try { $hasKullaniciAdiColumn = \DB::getSchemaBuilder()->hasColumn('uyeler', 'kullanici_adi'); } catch (\Exception $e) {}

        $createData = [
            'ad'                => $payload['ad'],
            'soyad'             => $payload['soyad'],
            'email'             => $payload['email'],
            'sifre'             => $payload['sifre'], // zaten hash'li
            'telefon'           => $payload['telefon'] ?? null,
            'tc'                => $payload['tc'] ?? null,
            'durum'             => 1,
            'bakiye'            => 0,
            'tarih'             => date('Y-m-d H:i:s'),
            'ip'                => $request->ip(),
            'utipi'             => 0,
            'dtarih'            => $payload['dtarih'] ?? null,
            'nereden_duydunuz'  => $payload['nereden_duydunuz'] ?? null,
            'hizmet_sozlesme'   => 1,
            'gizlilik_sozlesme' => 1,
            'kvkk_onay'         => 1,
            'email_verified_at' => now(), // kod ile doğrulandı
        ];

        if ($hasKullaniciAdiColumn) {
            $taban = strtolower(preg_replace('/[^a-z0-9]/i', '', explode('@', $payload['email'])[0]));
            if ($taban === '') { $taban = 'uye'; }
            $aday = $taban; $sayac = 0;
            while (\DB::table('uyeler')->where('kullanici_adi', $aday)->exists()) {
                $sayac++;
                $aday = $taban . $sayac;
                if ($sayac > 9999) { $aday = $taban . '_' . uniqid(); break; }
            }
            $createData['kullanici_adi'] = $aday;
        }

        $uye = Uye::create($createData);

        try {
            $uyeAd = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ($uye->email ?? 'Yeni üye');
            admin_bildirim_gonder('👤 Yeni Üye Kaydı: ' . $uyeAd, $uyeAd . ' siteye üye oldu. (' . ($uye->email ?? '') . ')', 'uye', 'uyeler', (int) $uye->id);
        } catch (\Throwable $e) {
            \Log::warning('Yeni üye admin bildirimi: ' . $e->getMessage());
        }

        self::crmMusteriyeSenkronla($uye);
        NotificationMailer::welcome($uye);

        if ($ref && \Illuminate\Support\Facades\Schema::hasTable('referans_kayitlari')) {
            $bayi = \Illuminate\Support\Facades\DB::table('bayiler')->where('bayi_kodu', $ref)->first();
            if ($bayi) {
                \Illuminate\Support\Facades\DB::table('referans_kayitlari')->insert([
                    'bayi_kodu' => $ref, 'bayi_id' => $bayi->id, 'uye_id' => $uye->id,
                    'kazanc' => 0, 'durum' => 1, 'kayit_tarihi' => now(), 'created_at' => now(),
                ]);
            }
        }
        session()->forget('referans_kodu');

        try {
            Auth::guard('uye')->login($uye);
            session()->regenerate();
            session()->put('uye_logged_in', true);
            session()->forget('pending_kayit');
            session()->save();
            $uye->update(['son_giris' => now('Europe/Istanbul')->format('Y-m-d H:i:s'), 'ip' => $request->ip()]);
            session()->save();
            return redirect(route('hesabim') . '?hosgeldin=1')->with('success', 'Hesabınız doğrulandı ve oluşturuldu, hoş geldiniz!');
        } catch (\Throwable $e) {
            \Log::warning('Kayıt sonrası otomatik giriş yapılamadı: ' . $e->getMessage());
            session()->forget('pending_kayit');
            return redirect()->route('giris')->with('success', 'Hesabınız oluşturuldu! Lütfen giriş yapın.');
        }
    }

    /**
     * E-postayı maskele: ab***@site.com
     */
    protected static function emailMaskele(string $email): string
    {
        if (!str_contains($email, '@')) return $email;
        [$u, $d] = explode('@', $email, 2);
        $u = mb_strlen($u) <= 2 ? $u : mb_substr($u, 0, 2) . str_repeat('*', min(5, mb_strlen($u) - 2));
        return $u . '@' . $d;
    }

    /** "05321234567" -> "0532 *** 45 67" gibi ortası gizli gösterim. */
    protected static function telefonMaskele(string $telefon): string
    {
        $t = preg_replace('/\D/', '', $telefon);
        if (strlen($t) < 7) return $telefon;
        $bas = substr($t, 0, 4);
        $son = substr($t, -2);
        return $bas . ' *** ** ' . $son;
    }

    /**
     * uyeler -> crm_customers tek yönlü senkron.
     * Email crm_customers'ta varsa dokunmaz (duplicate önleme), yoksa oluşturur.
     * Hem siteden kayıt (kayitPost) hem admin üye ekleme tarafında kullanılabilir.
     */
    public static function crmMusteriyeSenkronla($uye): void
    {
        try {
            $email = is_object($uye) ? ($uye->email ?? null) : ($uye['email'] ?? null);
            if (empty($email) || !\Illuminate\Support\Facades\Schema::hasTable('crm_customers')) {
                return;
            }

            // Zaten varsa hiçbir şey yapma (duplicate önleme)
            $mevcut = DB::table('crm_customers')->where('email', $email)->first();
            if ($mevcut) {
                return;
            }

            $ad = is_object($uye) ? ($uye->ad ?? '') : ($uye['ad'] ?? '');
            $soyad = is_object($uye) ? ($uye->soyad ?? '') : ($uye['soyad'] ?? '');
            $adiSoyadi = trim($ad . ' ' . $soyad) ?: $email;
            $telefon = is_object($uye) ? ($uye->telefon ?? null) : ($uye['telefon'] ?? null);

            $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('crm_customers');
            $data = [];
            if (in_array('adi', $kolonlar))     $data['adi'] = $adiSoyadi;
            if (in_array('email', $kolonlar))   $data['email'] = $email;
            if (in_array('telefon', $kolonlar)) $data['telefon'] = $telefon;
            if (in_array('durum', $kolonlar))   $data['durum'] = 'aktif';
            if (in_array('kaynak', $kolonlar))  $data['kaynak'] = 'Site Kaydı';
            if (in_array('created_at', $kolonlar)) $data['created_at'] = now();
            if (in_array('updated_at', $kolonlar)) $data['updated_at'] = now();

            if (!empty($data)) {
                DB::table('crm_customers')->insert($data);
            }
        } catch (\Throwable $e) {
            \Log::warning('uyeler->crm_customers senkron hatası', ['err' => $e->getMessage()]);
        }
    }

    public function cikis(Request $request)
    {
        $uye = Auth::guard('uye')->user();
        if ($uye && \Illuminate\Support\Facades\Schema::hasColumn('uyeler', 'remember_token')) {
            DB::table('uyeler')->where('id', $uye->id)->update(['remember_token' => null]);
        }

        // Üye ve varsayılan guard oturumlarını kapat.
        Auth::guard('uye')->logout();
        Auth::guard('web')->logout();

        // "Beni hatırla" çerezlerini de temizle (varsa otomatik tekrar login olmasın).
        Cookie::queue(Cookie::forget(Auth::guard('uye')->getRecallerName()));
        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));
        Cookie::queue(Cookie::forget('remember_uye'));
        Cookie::queue(Cookie::forget('remember_web'));
        Cookie::queue(Cookie::forget('remember_admin'));

        // Admin session kalıntılarını da temizle.
        $request->session()->forget([
            'uye_logged_in',
            'admin_logged_in',
            'admin_id',
            'admin_kullanici_adi',
            'admin_adi',
            'admin_yetki',
            'admin_rol',
        ]);

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('anasayfa')->with('success', 'Başarıyla çıkış yaptınız.');
    }
    
    public function hesabim()
    {
        try {
            $ayarlar = Ayar::first();
            $uye = Auth::guard('uye')->user();
            
            // İstatistikler - Try-catch ile güvenli sorgular
            $stats = [];
            
            // DİKKAT: doğru tablo 'satilanlar' (çoğul). Eskiden boş olan 'satilan'
            // tablosu sorgulanıyordu; bu yüzden tüm müşterilerde sayaçlar 0 görünüyordu.
            // tipi → 1: hosting · 2: web paketi · 3: domain
            try {
                $stats['yazilimlar'] = \DB::table('satilanlar')->where('uyeid', $uye->id)->where('tipi', 2)->count();
            } catch (\Exception $e) {
                $stats['yazilimlar'] = 0;
            }

            try {
                $stats['alanadlari'] = \DB::table('satilanlar')->where('uyeid', $uye->id)->where('tipi', 3)->count();
            } catch (\Exception $e) {
                $stats['alanadlari'] = 0;
            }

            // Birleşik "Hizmetlerim": web paketi + hosting + domain (satın alınan her şey)
            try {
                $stats['hizmetlerim'] = \DB::table('satilanlar')
                    ->where('uyeid', $uye->id)->whereIn('tipi', [1, 2, 3])->count();
            } catch (\Exception $e) {
                $stats['hizmetlerim'] = 0;
            }

            try {
                $stats['odenmemis_fatura'] = \DB::table('faturalar')->where('uyeid', $uye->id)->where('durum', 0)->count();
            } catch (\Exception $e) {
                $stats['odenmemis_fatura'] = 0;
            }

            try {
                $stats['odenmis_fatura'] = \DB::table('faturalar')->where('uyeid', $uye->id)->where('durum', 1)->count();
            } catch (\Exception $e) {
                $stats['odenmis_fatura'] = 0;
            }
            
            try {
                $stats['toplam_destek'] = \DB::table('destek')->where('uyeid', $uye->id)->where('ustid', 0)->count();
            } catch (\Exception $e) {
                $stats['toplam_destek'] = 0;
            }
            
            $stats['bakiye'] = $uye->bakiye ?? 0;
            
            try {
                $stats['fatura_tutar'] = \DB::table('faturalar')->where('uyeid', $uye->id)->where('durum', 0)->sum('tutar') ?? 0;
            } catch (\Exception $e) {
                $stats['fatura_tutar'] = 0;
            }
            
            try {
                if (\DB::getSchemaBuilder()->hasTable('paket_teklifleri')) {
                    $stats['teklifler'] = \DB::table('paket_teklifleri')->where('uye_id', $uye->id)->count();
                } else {
                    $stats['teklifler'] = 0;
                }
            } catch (\Exception $e) {
                $stats['teklifler'] = 0;
            }
            
            // Son destek talepleri
            try {
                $destekler = \DB::table('destek')
                    ->where('uyeid', $uye->id)
                    ->where('ustid', 0)
                    ->orderBy('son_cevap', 'desc')
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {
                $destekler = collect([]);
            }
            
            // Son faturalar
            try {
                $faturalar = \DB::table('faturalar')
                    ->where('uyeid', $uye->id)
                    ->orderBy('tarih', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($fatura) {
                        // Eğer fatura_no yoksa ID'den oluştur
                        if (!isset($fatura->fatura_no) || empty($fatura->fatura_no)) {
                            $fatura->fatura_no = 'FAT-' . str_pad($fatura->id, 6, '0', STR_PAD_LEFT);
                        }
                        return $fatura;
                    });
            } catch (\Exception $e) {
                $faturalar = collect([]);
            }
            
            return view('tema.hesabim', compact('ayarlar', 'uye', 'stats', 'destekler', 'faturalar'));
            
        } catch (\Exception $e) {
            // Hata varsa basit view döndür
            dd('HATA: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        }
    }
    
    public function bilgilerim()
    {
        $ayarlar = Ayar::first();
        $uye = Auth::guard('uye')->user();
        return view('tema.bilgilerim', compact('ayarlar', 'uye'));
    }
    
    public function bilgilerimPost(Request $request)
    {
        $uye = Auth::guard('uye')->user();
        $tab = $request->input('tab', 'ozet');
        
        // Şifre değişikliği (ad/soyad validation'ı patlamasın diye önce ele al)
        if ($request->filled('new_password')) {
            $request->validate([
                'verification_type' => 'required|in:email,telefon',
                'verification_code' => 'required|string|size:6',
                'new_password' => 'required|min:6|confirmed',
            ]);
            
            $verificationType = $request->verification_type;
            $verificationCode = $request->verification_code;
            
            $dogrulama = DB::table('dogrulama_kodlari')
                ->where('tip', 'sifre_degistir')
                ->where('uye_id', $uye->id)
                ->where('kod', $verificationCode)
                ->where('kullanildi', false)
                ->where(function($query) use ($verificationType, $uye) {
                    if ($verificationType == 'email') {
                        $query->where('email', $uye->email);
                    } else {
                        $telefon = preg_replace('/[^0-9]/', '', $uye->telefon ?? '');
                        if (substr($telefon, 0, 1) == '0') {
                            $telefon = substr($telefon, 1);
                        }
                        $query->where('telefon', $telefon)
                              ->orWhere('telefon', '0' . $telefon);
                    }
                })
                ->where('gecerlilik_suresi', '>', now())
                ->first();
            
            if (!$dogrulama) {
                return back()->withErrors(['verification_code' => 'Doğrulama kodu geçersiz veya süresi dolmuş.'])->withInput();
            }
            
            DB::table('dogrulama_kodlari')
                ->where('id', $dogrulama->id)
                ->update(['kullanildi' => true]);

            $uye->update(['sifre' => Hash::make($request->new_password)]);
            NotificationMailer::passwordChanged($uye, $request->ip());

            return redirect()->route('bilgilerim', ['tab' => 'sifre'])->with('success', 'Şifreniz başarıyla değiştirildi.');
        }
        
        // FATURA BİLGİLERİ
        if ($tab === 'fatura') {
            $validated = $request->validate([
                'fatura_unvan' => 'nullable|string|max:255',
                'fatura_tc' => 'nullable|string|max:50',
                'fatura_adres' => 'nullable|string|max:500',
                'fatura_sehir' => 'nullable|string|max:255',
                'fatura_ulke' => 'nullable|string|max:255',
            ]);
            
            $update = [];
            if (Schema::hasColumn('uyeler', 'fatura_unvan')) $update['fatura_unvan'] = $validated['fatura_unvan'] ?? null;
            if (Schema::hasColumn('uyeler', 'fatura_tc')) $update['fatura_tc'] = $validated['fatura_tc'] ?? null;
            if (Schema::hasColumn('uyeler', 'fatura_adres')) $update['fatura_adres'] = $validated['fatura_adres'] ?? null;
            if (Schema::hasColumn('uyeler', 'fatura_sehir')) $update['fatura_sehir'] = $validated['fatura_sehir'] ?? null;
            if (Schema::hasColumn('uyeler', 'fatura_ulke')) $update['fatura_ulke'] = $validated['fatura_ulke'] ?? null;
            
            if (!empty($update)) {
                $uye->update($update);
            }
            
            return redirect()->route('bilgilerim', ['tab' => 'fatura'])->with('success', 'Fatura bilgileriniz güncellendi.');
        }
        
        // TER CİHLER
        if ($tab === 'tercihler') {
            $validated = $request->validate([
                'email_bildirimleri' => 'nullable|boolean',
                'sms_bildirimleri' => 'nullable|boolean',
            ]);
            
            $update = [];
            $emailVal = $request->has('email_bildirimleri') ? 1 : 0;
            $smsVal = $request->has('sms_bildirimleri') ? 1 : 0;
            
            // Kolon adı uyumu için iki ihtimali de kontrol ediyoruz
            if (Schema::hasColumn('uyeler', 'email_bildirim')) $update['email_bildirim'] = $emailVal;
            if (Schema::hasColumn('uyeler', 'sms_bildirim')) $update['sms_bildirim'] = $smsVal;
            if (Schema::hasColumn('uyeler', 'email_bildirimleri')) $update['email_bildirimleri'] = $emailVal;
            if (Schema::hasColumn('uyeler', 'sms_bildirimleri')) $update['sms_bildirimleri'] = $smsVal;
            
            if (!empty($update)) {
                $uye->update($update);
            }
            
            return redirect()->route('bilgilerim', ['tab' => 'tercihler'])->with('success', 'Bildirim tercihleri güncellendi.');
        }
        
        // ÖZET (Ülke / Şehir / Adres dahil)
        // Kullanici adi kolonu var mi? (eski tablolarda olmayabilir)
        $hasKullaniciAdiColumn = false;
        try {
            $hasKullaniciAdiColumn = Schema::hasColumn('uyeler', 'kullanici_adi');
        } catch (\Exception $e) {
            $hasKullaniciAdiColumn = false;
        }

        $ozetRules = [
            'ad' => 'required|string|max:255',
            'soyad' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:uyeler,email,' . $uye->id,
            'telefon' => 'required|string|max:50',
            'tc' => 'nullable|required_if:utipi,0|digits:11',
            'firmaadi' => 'nullable|string|max:255',
            'vergino' => 'nullable|string|max:255',
            'vergidairesi' => 'nullable|string|max:255',
            'utipi' => 'nullable|integer|in:0,1',

            'ulke' => 'nullable|string|max:255',
            'sehir' => 'nullable|string|max:255',
            'adres' => 'nullable|string|max:500',
        ];

        // Kullanici adi: kolon varsa duzenlenebilir. Kendi kaydi haric benzersiz olmali.
        if ($hasKullaniciAdiColumn) {
            $ozetRules['kullanici_adi'] = 'required|string|min:3|max:50|alpha_dash|unique:uyeler,kullanici_adi,' . $uye->id;
        }

        $validated = $request->validate($ozetRules, [
            'telefon.required'         => 'Telefon numarası zorunludur.',
            'tc.required_if'           => 'Bireysel hesap için TC Kimlik No zorunludur.',
            'tc.digits'                => 'TC Kimlik No 11 haneli olmalıdır.',
            'kullanici_adi.required'   => 'Kullanıcı adı boş olamaz.',
            'kullanici_adi.min'        => 'Kullanıcı adı en az 3 karakter olmalı.',
            'kullanici_adi.max'        => 'Kullanıcı adı en fazla 50 karakter olabilir.',
            'kullanici_adi.alpha_dash' => 'Kullanıcı adı yalnızca harf, rakam, tire ve alt çizgi içerebilir.',
            'kullanici_adi.unique'     => 'Bu kullanıcı adı başkası tarafından alınmış. Lütfen farklı bir kullanıcı adı seçin.',
        ]);

        $update = [
            'ad' => $validated['ad'],
            'soyad' => $validated['soyad'],
            'email' => $validated['email'],
            'telefon' => $validated['telefon'] ?? null,
            'firmaadi' => $validated['firmaadi'] ?? null,
            'vergino' => $validated['vergino'] ?? null,
            'vergidairesi' => $validated['vergidairesi'] ?? null,
            'utipi' => $validated['utipi'] ?? 0,
        ];

        // Opsiyonel kolonlar - tabloda varsa update'e dahil et
        if ($hasKullaniciAdiColumn && array_key_exists('kullanici_adi', $validated)) {
            $update['kullanici_adi'] = $validated['kullanici_adi'];
        }
        if (Schema::hasColumn('uyeler', 'tc')) $update['tc'] = $validated['tc'] ?? null;
        if (Schema::hasColumn('uyeler', 'ulke')) $update['ulke'] = $validated['ulke'] ?? null;
        if (Schema::hasColumn('uyeler', 'sehir')) $update['sehir'] = $validated['sehir'] ?? null;
        if (Schema::hasColumn('uyeler', 'adres')) $update['adres'] = $validated['adres'] ?? null;

        // ── PROFİL FOTOĞRAFI (kolon varsa) ──
        // NOT: Uye modelinin $fillable listesinde profil_foto olmayabilir;
        // sessizce atlanmasin diye dogrudan DB::table ile guncellenir.
        if (Schema::hasColumn('uyeler', 'profil_foto')) {
            $eskiFoto = $uye->profil_foto ?? null;

            if ($request->hasFile('profil_foto')) {
                $request->validate([
                    'profil_foto' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
                ], [
                    'profil_foto.image' => 'Profil fotoğrafı bir resim dosyası olmalı.',
                    'profil_foto.mimes' => 'Sadece JPG, PNG veya WEBP yükleyebilirsiniz.',
                    'profil_foto.max'   => 'Fotoğraf en fazla 2MB olabilir.',
                ]);

                $klasor = public_path('tema/uploads/profil');
                if (!is_dir($klasor)) { @mkdir($klasor, 0755, true); }

                $uzanti = strtolower($request->file('profil_foto')->getClientOriginalExtension() ?: 'jpg');
                $dosyaAdi = 'uye_' . $uye->id . '_' . time() . '.' . $uzanti;
                $request->file('profil_foto')->move($klasor, $dosyaAdi);

                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }

                DB::table('uyeler')->where('id', $uye->id)
                    ->update(['profil_foto' => 'tema/uploads/profil/' . $dosyaAdi]);
            } elseif ($request->boolean('foto_kaldir')) {
                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }
                DB::table('uyeler')->where('id', $uye->id)->update(['profil_foto' => null]);
            }
        }

        $uye->update($update);
        
        return redirect()->route('bilgilerim', ['tab' => $tab, 'lang' => app()->getLocale()])->with('success', 'Bilgileriniz güncellendi.');
    }
    
    /**
     * Şifre sıfırlama sayfası
     */
    public function sifreSifirlama()
    {
        $ayar = Ayar::first();
        return view('tema.sifre-sifirlama', compact('ayar'));
    }
    
    /**
     * Şifre sıfırlama kodu gönder
     */
    public function sifreSifirlamaKodGonder(Request $request)
    {
        $request->validate([
            'email_or_phone' => 'required|string',
        ]);
        
        $input = trim($request->email_or_phone);
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
        
        // Kullanıcıyı bul
        $uye = null;
        if ($isEmail) {
            $uye = Uye::where('email', $input)->first();
        } else {
            // Telefon numarasını temizle
            $telefon = preg_replace('/[^0-9]/', '', $input);
            if (substr($telefon, 0, 1) == '0') {
                $telefon = substr($telefon, 1);
            }
            $uye = Uye::where('telefon', $telefon)
                ->orWhere('telefon', '0' . $telefon)
                ->first();
        }
        
        if (!$uye) {
            return back()->with('error', 'Bu e-posta veya telefon numarası ile kayıtlı kullanıcı bulunamadı.');
        }
        
        // 6 haneli kod oluştur
        $kod = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Eski kodları geçersiz yap
        DB::table('dogrulama_kodlari')
            ->where('tip', 'sifre_sifirlama')
            ->where('uye_id', $uye->id)
            ->where('kullanildi', false)
            ->update(['kullanildi' => true]);
        
        // Telefon numarasını temizle
        $telefonKayit = null;
        if (!$isEmail) {
            $telefonKayit = preg_replace('/[^0-9]/', '', $uye->telefon ?? $input);
            if (substr($telefonKayit, 0, 1) == '0') {
                $telefonKayit = substr($telefonKayit, 1);
            }
        }
        
        // Yeni kod kaydet
        DB::table('dogrulama_kodlari')->insert([
            'tip' => 'sifre_sifirlama',
            'email' => $isEmail ? $uye->email : null,
            'telefon' => $telefonKayit,
            'kod' => $kod,
            'uye_id' => $uye->id,
            'kullanildi' => false,
            'gecerlilik_suresi' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Kod gönder
        if ($isEmail) {
            EmailNotificationService::sendVerificationCode($uye->email, $kod, 'sifre_sifirlama');
            // KALICI session (flash degil) -> sayfa yenilense/gecikse de "Oturum suresi doldu" olmaz.
            session()->put('sifre_sifirlama_hedef', $input);
            session()->put('sifre_sifirlama_tip', 'email');
            session()->save();
            return redirect()->route('sifre.sifirlama.kod.dogrula')
                ->with('success', 'Doğrulama kodu e-posta adresinize gönderildi.');
        } else {
            $smsService = new SmsService();
            $result = $smsService->dogrulamaKoduGonder($uye->telefon ?? $input, $kod);
            
            if ($result['success']) {
                session()->put('sifre_sifirlama_hedef', $input);
                session()->put('sifre_sifirlama_tip', 'telefon');
                session()->save();
                return redirect()->route('sifre.sifirlama.kod.dogrula')
                    ->with('success', 'Doğrulama kodu telefon numaranıza SMS olarak gönderildi.');
            } else {
                // Daha açıklayıcı hata mesajı
                $errorMsg = 'SMS gönderilemedi: ' . $result['message'];
                if (isset($result['data']['code'])) {
                    $errorMsg .= ' (Hata Kodu: ' . $result['data']['code'] . ')';
                }
                return back()->with('error', $errorMsg)->withInput();
            }
        }
    }
    
    /**
     * "Kod gelmedi mi? Tekrar gönder" — session'daki hedefe YENİ kod gönderir,
     * AYNI doğrulama sayfasında kalır (ana sayfaya atmaz).
     */
    public function sifreSifirlamaTekrarGonder()
    {
        $hedef = session('sifre_sifirlama_hedef');
        $tip   = session('sifre_sifirlama_tip') ?? 'email';

        if (!$hedef) {
            return redirect()->route('sifre.sifirlama')
                ->with('error', 'Önce e-posta veya telefon girip kod talep edin.');
        }

        $isEmail = ($tip === 'email');

        // Kullanıcıyı bul (kodGonder ile aynı mantık)
        $uye = null;
        if ($isEmail) {
            $uye = Uye::where('email', $hedef)->first();
        } else {
            $tel = preg_replace('/[^0-9]/', '', $hedef);
            if (substr($tel, 0, 1) == '0') { $tel = substr($tel, 1); }
            $uye = Uye::where('telefon', $tel)->orWhere('telefon', '0' . $tel)->first();
        }

        if (!$uye) {
            return redirect()->route('sifre.sifirlama')
                ->with('error', 'Kullanıcı bulunamadı. Lütfen tekrar deneyin.');
        }

        // 6 haneli yeni kod
        $kod = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Eski kodları geçersiz yap
        DB::table('dogrulama_kodlari')
            ->where('tip', 'sifre_sifirlama')
            ->where('uye_id', $uye->id)
            ->where('kullanildi', false)
            ->update(['kullanildi' => true]);

        $telefonKayit = null;
        if (!$isEmail) {
            $telefonKayit = preg_replace('/[^0-9]/', '', $uye->telefon ?? $hedef);
            if (substr($telefonKayit, 0, 1) == '0') { $telefonKayit = substr($telefonKayit, 1); }
        }

        DB::table('dogrulama_kodlari')->insert([
            'tip' => 'sifre_sifirlama',
            'email' => $isEmail ? $uye->email : null,
            'telefon' => $telefonKayit,
            'kod' => $kod,
            'uye_id' => $uye->id,
            'kullanildi' => false,
            'gecerlilik_suresi' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($isEmail) {
            EmailNotificationService::sendVerificationCode($uye->email, $kod, 'sifre_sifirlama');
        } else {
            try {
                (new SmsService())->dogrulamaKoduGonder($uye->telefon ?? $hedef, $kod);
            } catch (\Throwable $e) {}
        }

        // AYNI doğrulama sayfasına dön (ana sayfaya atma)
        return redirect()->route('sifre.sifirlama.kod.dogrula')
            ->with('success', 'Yeni doğrulama kodu gönderildi. Lütfen e-postanızı/telefonunuzu kontrol edin.');
    }

    /**
     * Şifre sıfırlama kodu doğrulama sayfası
     */
    public function sifreSifirlamaKodDogrula()
    {
        $ayar = Ayar::first();
        // Kalici session anahtarlari (yeni) + eski flash anahtarlari (geriye donuk uyum).
        $emailOrPhone = session('sifre_sifirlama_hedef') ?? session('email_or_phone');
        $verificationType = session('sifre_sifirlama_tip') ?? session('verification_type') ?? 'email';
        
        if (!$emailOrPhone) {
            return redirect()->route('sifre.sifirlama')
                ->with('error', 'Once e-posta veya telefon girip kod talep edin.');
        }
        
        // Sayfa cache'ten gelmesin (geri tusu / yenilemede tutarlilik).
        return response()
            ->view('tema.sifre-sifirlama-kod-dogrula', compact('ayar', 'emailOrPhone', 'verificationType'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    
    /**
     * Şifre sıfırlama kodu doğrula ve yeni şifre belirle
     */
    public function sifreSifirlamaKodDogrulaPost(Request $request)
    {
        $request->validate([
            'kod' => 'required|string|size:6',
            'new_password' => 'required|min:6|confirmed',
        ]);
        
        $emailOrPhone = session('sifre_sifirlama_hedef') ?? session('email_or_phone');
        $verificationType = session('sifre_sifirlama_tip') ?? session('verification_type') ?? 'email';
        
        if (!$emailOrPhone) {
            return redirect()->route('sifre.sifirlama')->with('error', 'Oturum süresi doldu. Lütfen tekrar deneyin.');
        }
        
        $isEmail = $verificationType == 'email';
        
        // Kullanıcıyı bul
        $uye = null;
        if ($isEmail) {
            $uye = Uye::where('email', $emailOrPhone)->first();
        } else {
            $telefon = preg_replace('/[^0-9]/', '', $emailOrPhone);
            if (substr($telefon, 0, 1) == '0') {
                $telefon = substr($telefon, 1);
            }
            $uye = Uye::where('telefon', $telefon)
                ->orWhere('telefon', '0' . $telefon)
                ->first();
        }
        
        if (!$uye) {
            return redirect()->route('sifre.sifirlama')->with('error', 'Kullanıcı bulunamadı.');
        }
        
        // ═══ GECICI TESHIS LOGU (sonra kaldirilacak) ═══
        \Log::info('SIFRE-SIFIRLAMA-DOGRULA TESHIS', [
            'girilen_kod'     => $request->kod,
            'girilen_kod_tip' => gettype($request->kod),
            'session_hedef'   => $emailOrPhone,
            'session_tip'     => $verificationType,
            'bulunan_uye_id'  => $uye->id,
            'bulunan_email'   => $uye->email,
            'db_eslesme'      => DB::table('dogrulama_kodlari')
                ->where('tip','sifre_sifirlama')->where('uye_id',$uye->id)
                ->where('kod',$request->kod)->get()->toArray(),
        ]);
        // ═══════════════════════════════════════════════

        // Doğrulama kodu kontrolü
        $dogrulama = DB::table('dogrulama_kodlari')
            ->where('tip', 'sifre_sifirlama')
            ->where('uye_id', $uye->id)
            ->where('kod', $request->kod)
            ->where('kullanildi', false)
            ->where(function($query) use ($isEmail, $emailOrPhone, $uye) {
                if ($isEmail) {
                    $query->where('email', $uye->email);
                } else {
                    $telefon = preg_replace('/[^0-9]/', '', $uye->telefon ?? $emailOrPhone);
                    if (substr($telefon, 0, 1) == '0') {
                        $telefon = substr($telefon, 1);
                    }
                    $query->where('telefon', $telefon)
                          ->orWhere('telefon', '0' . $telefon);
                }
            })
            ->where('gecerlilik_suresi', '>', now())
            ->first();
        
        if (!$dogrulama) {
            return back()->withErrors(['kod' => 'Doğrulama kodu geçersiz veya süresi dolmuş.']);
        }
        
        // Kodu kullanıldı olarak işaretle
        DB::table('dogrulama_kodlari')
            ->where('id', $dogrulama->id)
            ->update(['kullanildi' => true]);
        
        // Şifreyi güncelle
        $uye->update(['sifre' => Hash::make($request->new_password)]);
        NotificationMailer::passwordChanged($uye, $request->ip());

        // Session'ı temizle (hem yeni kalici hem eski flash anahtarlar)
        session()->forget(['sifre_sifirlama_hedef', 'sifre_sifirlama_tip', 'email_or_phone', 'verification_type']);

        // Basari mesaji query ile tasinir (flash redirect zincirinde kaybolsa bile gorunur)
        return redirect('/giris?basari=' . urlencode('Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.'))
            ->with('success', 'Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.');
    }
    
    /**
     * Şifre değiştirme için doğrulama kodu gönder
     */
    public function sifreDegistirKodGonder(Request $request)
    {
        $request->validate([
            'verification_type' => 'required|in:email,telefon',
        ]);
        
        $uye = Auth::guard('uye')->user();
        $verificationType = $request->verification_type;
        
        // 6 haneli kod oluştur
        $kod = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Eski kodları geçersiz yap
        DB::table('dogrulama_kodlari')
            ->where('tip', 'sifre_degistir')
            ->where('uye_id', $uye->id)
            ->where('kullanildi', false)
            ->update(['kullanildi' => true]);
        
        // Telefon numarasını temizle ve kaydet
        $telefonKayit = null;
        if ($verificationType == 'telefon' && $uye->telefon) {
            $telefonKayit = preg_replace('/[^0-9]/', '', $uye->telefon);
            if (substr($telefonKayit, 0, 1) == '0') {
                $telefonKayit = substr($telefonKayit, 1);
            }
        }
        
        // Yeni kod kaydet
        DB::table('dogrulama_kodlari')->insert([
            'tip' => 'sifre_degistir',
            'email' => $verificationType == 'email' ? $uye->email : null,
            'telefon' => $telefonKayit,
            'kod' => $kod,
            'uye_id' => $uye->id,
            'kullanildi' => false,
            'gecerlilik_suresi' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Kod gönder
        if ($verificationType == 'email') {
            EmailNotificationService::sendVerificationCode($uye->email, $kod, 'sifre_degistir');
            return response()->json(['success' => true, 'message' => 'Doğrulama kodu e-posta adresinize gönderildi.']);
        } else {
            if (!$uye->telefon) {
                return response()->json(['success' => false, 'message' => 'Telefon numaranız kayıtlı değil.']);
            }
            
            $smsService = new SmsService();
            $result = $smsService->dogrulamaKoduGonder($uye->telefon, $kod);
            
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'Doğrulama kodu telefon numaranıza SMS olarak gönderildi.']);
            } else {
                // Daha açıklayıcı hata mesajı
                $errorMsg = 'SMS gönderilemedi: ' . $result['message'];
                if (isset($result['data']['code'])) {
                    $errorMsg .= ' (Hata Kodu: ' . $result['data']['code'] . ')';
                }
                return response()->json([
                    'success' => false, 
                    'message' => $errorMsg,
                    'error_code' => $result['data']['code'] ?? null
                ]);
            }
        }
    }
    
    public function tekliflerim()
    {
        $ayarlar = Ayar::first();
        $uye     = Auth::guard('uye')->user();

        $teklifler = collect([]);

        // 1) paket_teklifleri (PaketTeklifController sistemi — uye_id ile)
        try {
            if (\DB::getSchemaBuilder()->hasTable('paket_teklifleri')) {
                $paketTeklifler = \DB::table('paket_teklifleri')
                    ->where('uye_id', $uye->id)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($teklif) {
                        $paketler = collect([]);
                        try {
                            if (\DB::getSchemaBuilder()->hasTable('paket_teklif_paketleri')) {
                                $paketler = \DB::table('paket_teklif_paketleri')
                                    ->join('yazilimlar', 'paket_teklif_paketleri.paket_id', '=', 'yazilimlar.id')
                                    ->where('paket_teklif_paketleri.teklif_id', $teklif->id)
                                    ->select('yazilimlar.adi', 'paket_teklif_paketleri.birim_fiyat_tl', 'paket_teklif_paketleri.adet', 'paket_teklif_paketleri.satir_toplam_tl')
                                    ->get();
                            }
                        } catch (\Exception $e) {}

                        $durum = match ($teklif->durum ?? 'beklemede') {
                            'onaylandi', 'odendi' => 'onaylandi',
                            'reddedildi', 'iptal' => 'reddedildi',
                            default               => 'beklemede',
                        };

                        return (object) [
                            'id'                         => 'pt_' . $teklif->id,
                            'baslik'                     => $teklif->baslik ?? 'Paket Teklifi',
                            'aciklama'                   => $teklif->aciklama ?? null,
                            'durum'                      => $durum,
                            'toplam_tl'                  => $teklif->toplam_tl,
                            'para_birimi'                => $teklif->para_birimi ?? null,
                            'toplam_para_birimi_tutar'   => $teklif->toplam_para_birimi_tutar ?? null,
                            'token'                      => $teklif->token ?? null,
                            'odeme_yontemi'              => null,
                            'created_at'                 => $teklif->created_at,
                            'paketler'                   => $paketler,
                        ];
                    });

                $teklifler = $teklifler->merge($paketTeklifler);
            }
        } catch (\Exception $e) {}

        // 2) crm_musteri_teklifleri (CRM sistemi — email eşleştirme ile)
        try {
            if (\DB::getSchemaBuilder()->hasTable('crm_musteri_teklifleri')) {
                $crmMusteri = \DB::table('crm_customers')
                    ->where('email', $uye->email)
                    ->first();

                if ($crmMusteri) {
                    $crmTeklifler = \DB::table('crm_musteri_teklifleri')
                        ->where('customer_id', $crmMusteri->id)
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($teklif) {
                            $durum = match ($teklif->durum ?? 'gonderildi') {
                                'onaylandi', 'odendi', 'paid' => 'onaylandi',
                                'reddedildi', 'iptal'         => 'reddedildi',
                                default                       => 'beklemede',
                            };

                            return (object) [
                                'id'            => 'crm_' . $teklif->id,
                                'baslik'        => $teklif->paket_adi,
                                'aciklama'      => $teklif->mesaj ?? null,
                                'durum'         => $durum,
                                'toplam_tl'     => $teklif->tutar,
                                'para_birimi'   => null,
                                'token'         => $teklif->token ?? null,
                                'odeme_yontemi' => $teklif->odeme_yontemi ?? null,
                                'created_at'    => $teklif->created_at,
                                'paketler'      => collect([]),
                            ];
                        });

                    $teklifler = $teklifler->merge($crmTeklifler);
                }
            }
        } catch (\Exception $e) {}

        // Tarihe göre sırala
        $teklifler = $teklifler->sortByDesc('created_at')->values();

        return view('tema.tekliflerim', compact('ayarlar', 'uye', 'teklifler'));
    }

    /**
     * E-posta doğrulama linki tıklanınca
     */
    public function emailVerify($id, $hash)
    {
        $uye = Uye::find($id);
        if (!$uye) {
            return redirect()->route('giris')->with('error', 'Kullanıcı bulunamadı.');
        }
        if (!hash_equals(sha1($uye->email), (string) $hash)) {
            return redirect()->route('giris')->with('error', 'Doğrulama linki geçersiz.');
        }
        if (!empty($uye->email_verified_at)) {
            return redirect()->route('giris')->with('success', 'E-postanız zaten doğrulanmış. Giriş yapabilirsiniz.');
        }
        $uye->update(['email_verified_at' => now()]);
        NotificationMailer::emailVerified($uye);
        return redirect()->route('giris')->with('success', '✅ E-postanız doğrulandı. Şimdi giriş yapabilirsiniz.');
    }

    /**
     * Doğrulama mailini yeniden gönder
     */
    public function emailVerifyResend(Request $request)
    {
        $email = trim((string) $request->input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Geçerli bir e-posta adresi girin.');
        }
        $uye = Uye::where('email', $email)->first();
        if (!$uye) {
            // Email enumeration korumak için yine başarı mesajı dön
            return back()->with('success', 'Eğer bu adres kayıtlıysa doğrulama maili gönderildi.');
        }
        if (!empty($uye->email_verified_at)) {
            return back()->with('success', 'Bu hesap zaten doğrulanmış.');
        }
        NotificationMailer::emailVerify($uye);
        return back()->with('success', 'Doğrulama maili gönderildi. Lütfen e-postanızı kontrol edin.');
    }

    /**
     * Müşteri teklifi onaylar
     * ID prefix'i:
     *   'pt_'  → paket_teklifleri tablosu
     *   'crm_' → crm_musteri_teklifleri tablosu
     */
    public function teklifOnayla(Request $request, $prefixedId)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect()->route('giris')->with('error', 'Giriş yapmalısınız.');
        }

        // Prefix ayıkla
        if (strpos($prefixedId, 'pt_') === 0) {
            $id = (int) substr($prefixedId, 3);
            $tablo = 'paket_teklifleri';
            $uyeKol = 'uye_id';
        } elseif (strpos($prefixedId, 'crm_') === 0) {
            $id = (int) substr($prefixedId, 4);
            $tablo = 'crm_musteri_teklifleri';
            $uyeKol = null; // CRM'de email ile eşleşiyor
        } else {
            return redirect()->route('tekliflerim')->with('error', 'Geçersiz teklif ID.');
        }

        try {
            // Teklif var mı + sahip mi kontrol
            $query = \DB::table($tablo)->where('id', $id);
            if ($uyeKol) {
                $query->where($uyeKol, $uye->id);
            } else {
                // CRM teklifleri customer_id üzerinden eşleşiyor
                $crmMusteri = \DB::table('crm_customers')->where('email', $uye->email)->first();
                if (!$crmMusteri) {
                    return redirect()->route('tekliflerim')->with('error', 'CRM müşteri kaydı bulunamadı.');
                }
                $query->where('customer_id', $crmMusteri->id);
            }

            $teklif = $query->first();
            if (!$teklif) {
                return redirect()->route('tekliflerim')->with('error', 'Teklif bulunamadı veya size ait değil.');
            }

            // Zaten onaylanmış mı / reddedilmiş mi?
            $mevcutDurum = $teklif->durum ?? 'beklemede';
            if (in_array($mevcutDurum, ['onaylandi', 'odendi', 'paid', 'reddedildi', 'iptal'], true)) {
                return redirect()->route('tekliflerim')->with('info', 'Bu teklif zaten işlem görmüş (' . $mevcutDurum . ').');
            }

            // Durum güncelle
            \DB::table($tablo)->where('id', $id)->update([
                'durum'      => 'onaylandi',
                'updated_at' => now(),
            ]);

            // Admine panel bildirimi — teklif onaylandı
            try {
                $musteriAd = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ($uye->email ?? 'Müşteri');
                $teklifBaslik = $teklif->baslik ?? ('#' . $id);
                admin_bildirim_gonder(
                    '✅ Teklif Onaylandı: ' . $teklifBaslik,
                    $musteriAd . ' bir teklifi onayladı.',
                    'teklif',
                    $tablo,
                    (int) $id
                );
            } catch (\Throwable $e) {
                \Log::warning('Teklif onay admin bildirimi: ' . $e->getMessage());
            }

            return redirect()->route('tekliflerim')->with('success', 'Teklifi onayladınız. Detaylar için sizinle iletişime geçilecektir.');

        } catch (\Throwable $e) {
            \Log::warning('Teklif onaylama hatasi', ['id' => $prefixedId, 'err' => $e->getMessage()]);
            return redirect()->route('tekliflerim')->with('error', 'Teklif onaylanamadı: ' . $e->getMessage());
        }
    }

    /**
     * Müşteri teklifi reddeder (opsiyonel sebep ile)
     */
    public function teklifReddet(Request $request, $prefixedId)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect()->route('giris')->with('error', 'Giriş yapmalısınız.');
        }

        $request->validate([
            'red_sebebi' => 'nullable|string|max:500',
        ]);

        if (strpos($prefixedId, 'pt_') === 0) {
            $id = (int) substr($prefixedId, 3);
            $tablo = 'paket_teklifleri';
            $uyeKol = 'uye_id';
        } elseif (strpos($prefixedId, 'crm_') === 0) {
            $id = (int) substr($prefixedId, 4);
            $tablo = 'crm_musteri_teklifleri';
            $uyeKol = null;
        } else {
            return redirect()->route('tekliflerim')->with('error', 'Geçersiz teklif ID.');
        }

        try {
            $query = \DB::table($tablo)->where('id', $id);
            if ($uyeKol) {
                $query->where($uyeKol, $uye->id);
            } else {
                $crmMusteri = \DB::table('crm_customers')->where('email', $uye->email)->first();
                if (!$crmMusteri) {
                    return redirect()->route('tekliflerim')->with('error', 'CRM müşteri kaydı bulunamadı.');
                }
                $query->where('customer_id', $crmMusteri->id);
            }

            $teklif = $query->first();
            if (!$teklif) {
                return redirect()->route('tekliflerim')->with('error', 'Teklif bulunamadı veya size ait değil.');
            }

            $mevcutDurum = $teklif->durum ?? 'beklemede';
            if (in_array($mevcutDurum, ['onaylandi', 'odendi', 'paid', 'reddedildi', 'iptal'], true)) {
                return redirect()->route('tekliflerim')->with('info', 'Bu teklif zaten işlem görmüş.');
            }

            $updateData = [
                'durum'      => 'reddedildi',
                'updated_at' => now(),
            ];

            // Schema-aware red_sebebi kolonu (varsa)
            try {
                $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing($tablo);
                if (in_array('red_sebebi', $kolonlar, true) && $request->filled('red_sebebi')) {
                    $updateData['red_sebebi'] = $request->red_sebebi;
                } elseif (in_array('aciklama', $kolonlar, true) && $request->filled('red_sebebi')) {
                    // Red sebebini açıklama sonuna ekle
                    $mevcutAciklama = $teklif->aciklama ?? '';
                    $updateData['aciklama'] = trim($mevcutAciklama . "\n\n[RED SEBEBİ - " . now()->format('d.m.Y H:i') . "] " . $request->red_sebebi);
                }
            } catch (\Throwable $e) {}

            \DB::table($tablo)->where('id', $id)->update($updateData);

            // Admine panel bildirimi — teklif reddedildi
            try {
                $musteriAd = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ($uye->email ?? 'Müşteri');
                $teklifBaslik = $teklif->baslik ?? ('#' . $id);
                $sebep = $request->filled('red_sebebi') ? (' Sebep: ' . \Illuminate\Support\Str::limit($request->red_sebebi, 120)) : '';
                admin_bildirim_gonder(
                    '❌ Teklif Reddedildi: ' . $teklifBaslik,
                    $musteriAd . ' bir teklifi reddetti.' . $sebep,
                    'teklif',
                    $tablo,
                    (int) $id
                );
            } catch (\Throwable $e) {
                \Log::warning('Teklif red admin bildirimi: ' . $e->getMessage());
            }

            return redirect()->route('tekliflerim')->with('success', 'Teklifi reddettiniz.');

        } catch (\Throwable $e) {
            \Log::warning('Teklif red hatasi', ['id' => $prefixedId, 'err' => $e->getMessage()]);
            return redirect()->route('tekliflerim')->with('error', 'Teklif reddedilemedi: ' . $e->getMessage());
        }
    }
}