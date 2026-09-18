<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\EmailNotificationService;

class AdminAuthController extends Controller
{
    public function giris(\Illuminate\Http\Request $request)
    {
        // ?fresh=1 ile zorla temiz session başlat
        if ($request->boolean('fresh')) {
            Auth::guard('uye')->logout();
            Auth::guard('web')->logout();
            session()->flush();
            session()->regenerate();
            return redirect()->route('admin.giris');
        }

        $adminLogged = session()->has('admin_logged_in') && session('admin_logged_in');
        $adminId = session('admin_id');
        $uyeLogged = Auth::guard('uye')->check();

        // Session'da admin oturumu var ama: admin_id geçersizse, ya da
        // aynı anda uye guard da aktifse (bridge kalıntısı) -> session'u temizle
        if ($adminLogged) {
            $adminVar = $adminId && DB::table('yoneticiler')->where('id', $adminId)->where('durum', 1)->exists();
            if (!$adminVar || $uyeLogged) {
                Auth::guard('uye')->logout();
                Auth::guard('web')->logout();
                session()->flush();
                session()->regenerate();
                return view('admin.giris');
            }

            // Gerçekten geçerli admin oturumu varsa role'e göre yönlendir
            $rol = (int) session('admin_rol', 2);
            $route = $rol === 3 ? 'admin.bayi.dashboard' : 'admin.dashboard';
            return redirect()->route($route)->with('info', 'Zaten giriş yapmışsınız.');
        }

        // Uye logged in ama admin değil — admin login form gösterirken uye guard'ını kapat
        if ($uyeLogged) {
            Auth::guard('uye')->logout();
            session()->forget(['uye_logged_in']);
            session()->regenerateToken();
        }

        return view('admin.giris');
    }
    
    public function girisPost(Request $request)
    {
        try {
            \Log::info('Admin giriş denemesi', [
                'kullanici_adi' => $request->kullanici_adi,
                'ip' => $request->ip()
            ]);
            
            $request->validate([
                'kullanici_adi' => 'required|string',
                'sifre' => 'required|string',
            ], [
                'kullanici_adi.required' => 'Kullanıcı adı gereklidir.',
                'sifre.required' => 'Şifre gereklidir.',
            ]);
            
            // Hem kullanıcı adı hem de email ile giriş yapılabilir
            $admin = DB::table('yoneticiler')
                ->where(function($query) use ($request) {
                    $query->where('kullaniciadi', $request->kullanici_adi)
                          ->orWhere('email', $request->kullanici_adi)
                          ->orWhere('eposta', $request->kullanici_adi);
                })
                ->where('durum', 1)
                ->first();
            
            if (!$admin) {
                \Log::warning('Admin bulunamadı', ['kullanici_adi' => $request->kullanici_adi]);
                \App\Helpers\GirisLogHelper::kaydet('yonetici', 'basarisiz', $request->kullanici_adi, null, null, 'Kullanıcı bulunamadı veya hesap pasif');
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Kullanıcı adı veya şifre hatalı!');
            }
            
            $sifreDogrumu = false;
            
            // Eski MD5 formatı - başarılı girişte bcrypt'e migrate et
            if (strlen($admin->sifre) === 32 && $admin->sifre === md5($request->sifre)) {
                $sifreDogrumu = true;
                DB::table('yoneticiler')->where('id', $admin->id)->update(['sifre' => Hash::make($request->sifre)]);
                \Log::info('Admin şifresi MD5\'ten bcrypt\'e migrate edildi', ['admin_id' => $admin->id]);
            } else {
                // Yeni Bcrypt formatı (veya diğer Laravel destekli hash'ler)
                try {
                    if (Hash::check($request->sifre, $admin->sifre)) {
                        $sifreDogrumu = true;
                    }
                } catch (\Throwable $hashException) {
                    // Eski / desteklenmeyen hash formatı ise patlamasın, sadece logla
                    \Log::warning('Admin Hash::check hatası (muhtemelen eski hash formatı)', [
                        'admin_id' => $admin->id,
                        'error' => $hashException->getMessage(),
                    ]);
                }
            }
            
            if (!$sifreDogrumu) {
                \Log::warning('Şifre hatalı', [
                    'admin_id' => $admin->id,
                    'kullanici_adi' => $request->kullanici_adi
                ]);
                \App\Helpers\GirisLogHelper::kaydet('yonetici', 'basarisiz', $request->kullanici_adi, $admin->adi ?? $admin->kullaniciadi, $admin->id, 'Şifre hatalı');
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Kullanıcı adı veya şifre hatalı!');
            }
            
            \Log::info('Giriş başarılı', ['admin_id' => $admin->id, 'rol' => $admin->rol]);
            \App\Helpers\GirisLogHelper::kaydet('yonetici', 'basarili', $admin->email ?? $admin->eposta ?? $request->kullanici_adi, $admin->adi ?? $admin->kullaniciadi, $admin->id, null);
        
        session()->regenerate(); // Session fixation koruması

        // Session'ı kaydet - tüm değerleri tek seferde
        $sessionData = [
            'admin_logged_in' => true,
            'admin_id' => $admin->id,
            'admin_kullanici_adi' => $admin->kullaniciadi,
            'admin_adi' => $admin->adi ?? $admin->kullaniciadi,
            'admin_yetki' => $admin->yetki ?? 1,
            'admin_rol' => $admin->rol ?? 2, // 1=Patron, 2=Çalışan, 3=Bayi
        ];
        
        foreach ($sessionData as $key => $value) {
            session()->put($key, $value);
        }

        // "Beni hatırla" işaretliyse oturum süresini uzat (30 gün), değilse normal
        if ($request->filled('beni_hatirla')) {
            $dakika = 60 * 24 * 30; // 30 gün
            config(['session.lifetime' => $dakika]);
            // Oturum çerezini uzun ömürlü olarak yeniden kuyruğa al
            $cookieName = config('session.cookie');
            if ($cookieName && session()->getId()) {
                Cookie::queue(
                    $cookieName,
                    session()->getId(),
                    $dakika,
                    config('session.path', '/'),
                    config('session.domain'),
                    config('session.secure', false),
                    config('session.http_only', true),
                    false,
                    config('session.same_site', 'lax')
                );
            }
            session()->put('admin_remember', true);
        }
        
        // Veritabanını güncelle
        DB::table('yoneticiler')
            ->where('id', $admin->id)
            ->update([
                'son_giris' => now('Europe/Istanbul'),
                'son_ip' => $request->ip(),
            ]);
        
        // Session'ı kesinlikle kaydet
        session()->save();
        
        // Session'ın kaydedildiğini doğrula
        if (!session()->has('admin_id')) {
            \Log::error('Session kaydedilemedi!', ['admin_id' => $admin->id]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Oturum oluşturulamadı. Lütfen tekrar deneyin.');
        }
        
        // Bayi rolü için bayi dashboard'a yönlendir
        $adminRol = session('admin_rol', $admin->rol ?? 2);
        
        \Log::info('Yönlendirme yapılıyor', [
            'admin_id' => $admin->id,
            'rol' => $adminRol,
            'session_rol' => session('admin_rol')
        ]);
        
        if ($adminRol == 3) {
            $redirectUrl = route('admin.bayi.dashboard');
            \Log::info('Bayi dashboard\'a yönlendiriliyor', ['url' => $redirectUrl]);
            return redirect($redirectUrl)->with('success', 'Hoş geldiniz!');
        }
        
        $redirectUrl = route('admin.dashboard');
        \Log::info('Admin dashboard\'a yönlendiriliyor', ['url' => $redirectUrl]);
        return redirect($redirectUrl)->with('success', 'Hoş geldiniz!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Lütfen formu doldurun.');
        } catch (\Exception $e) {
            \Log::error('Admin giriş hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Giriş yapılırken bir hata oluştu. Lütfen tekrar deneyin.');
        }
    }
    
    /**
     * Admin şifre sıfırlama formunu göster
     */
    public function sifreSifirlama()
    {
        return view('admin.sifre-sifirlama');
    }

    /**
     * Admin şifre sıfırlama: e-postaya doğrulama kodu gönder
     */
    public function sifreSifirlamaKodGonder(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email'    => 'Geçerli bir e-posta adresi girin.',
        ]);

        $email = trim($request->email);

        // Admini bul (email veya eposta kolonu)
        $admin = DB::table('yoneticiler')
            ->where(function ($q) use ($email) {
                $q->where('email', $email)->orWhere('eposta', $email);
            })
            ->where('durum', 1)
            ->first();

        if (!$admin) {
            return back()->with('error', 'Bu e-posta ile kayıtlı yönetici bulunamadı.');
        }

        // 6 haneli kod
        $kod = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Eski admin kodlarını geçersiz yap
        DB::table('dogrulama_kodlari')
            ->where('tip', 'admin_sifre_sifirlama')
            ->where('uye_id', $admin->id)
            ->where('kullanildi', false)
            ->update(['kullanildi' => true]);

        // Yeni kod kaydet (tip ile üye kodlarından ayrılır)
        DB::table('dogrulama_kodlari')->insert([
            'tip'               => 'admin_sifre_sifirlama',
            'email'             => $email,
            'telefon'           => null,
            'kod'               => $kod,
            'uye_id'            => $admin->id,
            'kullanildi'        => false,
            'gecerlilik_suresi' => now()->addMinutes(10),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // E-posta gönder (mevcut servis)
        try {
            EmailNotificationService::sendVerificationCode($email, $kod, 'sifre_sifirlama');
        } catch (\Throwable $e) {
            \Log::error('Admin şifre sıfırlama mail hatası', ['err' => $e->getMessage()]);
            return back()->with('error', 'Doğrulama kodu gönderilemedi. Lütfen tekrar deneyin.');
        }

        return redirect()->route('admin.sifre.sifirlama.kod.dogrula')
            ->with('success', 'Doğrulama kodu e-posta adresinize gönderildi.')
            ->with('admin_reset_email', $email);
    }

    /**
     * Admin şifre sıfırlama: kod doğrulama formunu göster
     */
    public function sifreSifirlamaKodDogrula()
    {
        if (!session('admin_reset_email')) {
            return redirect()->route('admin.sifre.sifirlama')
                ->with('error', 'Oturum süresi doldu. Lütfen tekrar deneyin.');
        }
        // Email'i bir sonraki istek için sakla
        session()->keep(['admin_reset_email']);
        return view('admin.sifre-sifirlama-kod-dogrula');
    }

    /**
     * Admin şifre sıfırlama: kodu TEKRAR gönder (session'daki e-postaya).
     * Kullanici e-postayi tekrar girmeden, dogrulama ekraninda kalir.
     */
    public function sifreSifirlamaTekrarGonder()
    {
        $email = session('admin_reset_email');
        if (!$email) {
            return redirect()->route('admin.sifre.sifirlama')
                ->with('error', 'Oturum süresi doldu. Lütfen e-posta adresinizi tekrar girin.');
        }

        $admin = DB::table('yoneticiler')
            ->where(function ($q) use ($email) {
                $q->where('email', $email)->orWhere('eposta', $email);
            })
            ->where('durum', 1)
            ->first();

        if (!$admin) {
            session()->forget('admin_reset_email');
            return redirect()->route('admin.sifre.sifirlama')
                ->with('error', 'Kayıt bulunamadı. Lütfen e-posta adresinizi tekrar girin.');
        }

        $kod = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('dogrulama_kodlari')
            ->where('tip', 'admin_sifre_sifirlama')
            ->where('uye_id', $admin->id)
            ->where('kullanildi', false)
            ->update(['kullanildi' => true]);

        DB::table('dogrulama_kodlari')->insert([
            'tip'               => 'admin_sifre_sifirlama',
            'email'             => $email,
            'telefon'           => null,
            'kod'               => $kod,
            'uye_id'            => $admin->id,
            'kullanildi'        => false,
            'gecerlilik_suresi' => now()->addMinutes(10),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        try {
            EmailNotificationService::sendVerificationCode($email, $kod, 'sifre_sifirlama');
        } catch (\Throwable $e) {
            \Log::error('Admin şifre sıfırlama (tekrar) mail hatası', ['err' => $e->getMessage()]);
            session()->keep(['admin_reset_email']);
            return redirect()->route('admin.sifre.sifirlama.kod.dogrula')
                ->with('error', 'Kod gönderilemedi. Lütfen birkaç saniye sonra tekrar deneyin.');
        }

        session()->keep(['admin_reset_email']);
        return redirect()->route('admin.sifre.sifirlama.kod.dogrula')
            ->with('success', 'Yeni doğrulama kodu e-posta adresinize gönderildi.')
            ->with('admin_reset_email', $email);
    }

    /**
     * Admin şifre sıfırlama: kodu doğrula ve yeni şifreyi kaydet
     */
    public function sifreSifirlamaKodDogrulaPost(Request $request)
    {
        $request->validate([
            'kod'          => 'required|string|size:6',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'kod.required'          => 'Doğrulama kodu gereklidir.',
            'kod.size'              => 'Doğrulama kodu 6 haneli olmalıdır.',
            'new_password.required' => 'Yeni şifre gereklidir.',
            'new_password.min'      => 'Şifre en az 6 karakter olmalıdır.',
            'new_password.confirmed'=> 'Şifreler eşleşmiyor.',
        ]);

        $email = session('admin_reset_email');
        if (!$email) {
            return redirect()->route('admin.sifre.sifirlama')
                ->with('error', 'Oturum süresi doldu. Lütfen tekrar deneyin.');
        }

        $admin = DB::table('yoneticiler')
            ->where(function ($q) use ($email) {
                $q->where('email', $email)->orWhere('eposta', $email);
            })
            ->where('durum', 1)
            ->first();

        if (!$admin) {
            return redirect()->route('admin.sifre.sifirlama')->with('error', 'Yönetici bulunamadı.');
        }

        // Kod kontrolü
        $dogrulama = DB::table('dogrulama_kodlari')
            ->where('tip', 'admin_sifre_sifirlama')
            ->where('uye_id', $admin->id)
            ->where('kod', $request->kod)
            ->where('kullanildi', false)
            ->where('gecerlilik_suresi', '>', now())
            ->first();

        if (!$dogrulama) {
            session()->keep(['admin_reset_email']);
            return back()->withErrors(['kod' => 'Doğrulama kodu geçersiz veya süresi dolmuş.']);
        }

        // Kodu kullanıldı işaretle
        DB::table('dogrulama_kodlari')->where('id', $dogrulama->id)->update(['kullanildi' => true]);

        // Şifreyi güncelle (bcrypt)
        DB::table('yoneticiler')->where('id', $admin->id)->update([
            'sifre' => Hash::make($request->new_password),
        ]);

        session()->forget(['admin_reset_email']);

        return redirect()->route('admin.giris')
            ->with('success', 'Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.');
    }

    public function cikis()
    {
        $adminRol = (int) session('admin_rol', 0);

        // Admin + üye tarafındaki oturumları birlikte temizle
        Auth::guard('uye')->logout();
        Auth::guard('web')->logout();

        session()->forget([
            'admin_logged_in',
            'admin_id',
            'admin_kullanici_adi',
            'admin_adi',
            'admin_yetki',
            'admin_rol',
            'uye_logged_in',
        ]);

        session()->flush();
        session()->invalidate();
        session()->regenerateToken();

        Cookie::queue(Cookie::forget('remember_uye'));
        Cookie::queue(Cookie::forget('remember_web'));

        if ($adminRol === 3) {
            return redirect()->route('anasayfa')->with('success', 'Başarıyla çıkış yaptınız.');
        }

        return redirect()->route('admin.giris')->with('success', 'Başarıyla çıkış yaptınız.');
    }
}