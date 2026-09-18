<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Üye panelinde giriş yapmış bayi kullanıcı admin/bayi paneline geçmek istediğinde
        // admin session'ı otomatik kur. Eksik bayiler/yoneticiler kayıtları varsa oto-oluştur.
        if ((!session()->has('admin_logged_in') || !session('admin_logged_in')) && Auth::guard('uye')->check()) {
            $uyeId = Auth::guard('uye')->id();
            $uye = DB::table('uyeler')->where('id', $uyeId)->first();

            // Sadece bayi olarak işaretlenmiş üyeler için bridge çalışsın
            if ($uye && ($uye->bayi ?? 0) > 0) {
                $uyeColName = Schema::hasColumn('bayiler', 'uye_id') ? 'uye_id'
                    : (Schema::hasColumn('bayiler', 'uyeid') ? 'uyeid' : 'uye_id');

                $bayi = DB::table('bayiler')->where($uyeColName, $uyeId)->first();

                // 1) bayiler kaydı yoksa oluştur
                if (!$bayi) {
                    $bayiInsert = [$uyeColName => $uyeId, 'durum' => 1];
                    if (Schema::hasColumn('bayiler', 'onay_durumu')) $bayiInsert['onay_durumu'] = 1;
                    if (Schema::hasColumn('bayiler', 'bayi_kodu'))   $bayiInsert['bayi_kodu']   = 'BAY' . str_pad((string)$uyeId, 5, '0', STR_PAD_LEFT);
                    if (Schema::hasColumn('bayiler', 'komisyon_orani')) $bayiInsert['komisyon_orani'] = 10.00;
                    if (Schema::hasColumn('bayiler', 'created_at'))  $bayiInsert['created_at']  = now();
                    if (Schema::hasColumn('bayiler', 'updated_at'))  $bayiInsert['updated_at']  = now();

                    try {
                        DB::table('bayiler')->insert($bayiInsert);
                        $bayi = DB::table('bayiler')->where($uyeColName, $uyeId)->first();
                    } catch (\Throwable $e) {
                        \Log::warning('AdminAuth: bayiler kaydi olusturulamadi', ['uye_id' => $uyeId, 'err' => $e->getMessage()]);
                    }
                }

                // bayiler.durum 1 değilse aktif et
                if ($bayi && (int)($bayi->durum ?? 0) !== 1) {
                    DB::table('bayiler')->where('id', $bayi->id)->update(['durum' => 1]);
                    $bayi->durum = 1;
                }

                $yonetici = null;
                $yoneticiIdCol = Schema::hasColumn('bayiler', 'yonetici_id') ? 'yonetici_id'
                    : (Schema::hasColumn('bayiler', 'yoneticiid') ? 'yoneticiid' : null);
                $yoneticiId = $yoneticiIdCol && $bayi ? ($bayi->{$yoneticiIdCol} ?? null) : null;

                if (!empty($yoneticiId)) {
                    $yonetici = DB::table('yoneticiler')->where('id', $yoneticiId)->where('rol', 3)->where('durum', 1)->first();
                }

                // 2) yonetici kaydını email ile bul
                if (!$yonetici && !empty($uye->email)) {
                    $yonetici = DB::table('yoneticiler')
                        ->where(function ($q) use ($uye) {
                            $q->where('email', $uye->email)
                                ->orWhere('eposta', $uye->email)
                                ->orWhere('kullaniciadi', $uye->email);
                        })
                        ->where('rol', 3)
                        ->where('durum', 1)
                        ->first();
                }

                // 3) hala yoksa yoneticiler kaydı oluştur
                if (!$yonetici && !empty($uye->email)) {
                    $ycols = Schema::getColumnListing('yoneticiler');
                    $yInsert = ['rol' => 3, 'durum' => 1];
                    if (in_array('email', $ycols))        $yInsert['email']        = $uye->email;
                    if (in_array('eposta', $ycols))       $yInsert['eposta']       = $uye->email;
                    if (in_array('kullaniciadi', $ycols)) $yInsert['kullaniciadi'] = $uye->email;
                    if (in_array('adi', $ycols))          $yInsert['adi']          = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: $uye->email;
                    if (in_array('sifre', $ycols))        $yInsert['sifre']        = $uye->sifre ?? bcrypt(Str::random(16));
                    if (in_array('yetki', $ycols))        $yInsert['yetki']        = 1;
                    if (in_array('created_at', $ycols))   $yInsert['created_at']   = now();
                    if (in_array('updated_at', $ycols))   $yInsert['updated_at']   = now();

                    try {
                        $newId = DB::table('yoneticiler')->insertGetId($yInsert);
                        $yonetici = DB::table('yoneticiler')->where('id', $newId)->first();
                    } catch (\Throwable $e) {
                        \Log::warning('AdminAuth: yonetici kaydi olusturulamadi', ['uye_id' => $uyeId, 'err' => $e->getMessage()]);
                    }
                }

                // 4) bayiler.yonetici_id boşsa şimdi doldur
                if ($yonetici && $bayi && $yoneticiIdCol && empty($yoneticiId)) {
                    DB::table('bayiler')->where('id', $bayi->id)->update([$yoneticiIdCol => $yonetici->id]);
                }

                if ($yonetici) {
                    session()->put('admin_logged_in', true);
                    session()->put('admin_id', $yonetici->id);
                    session()->put('admin_kullanici_adi', $yonetici->kullaniciadi ?? $uye->email);
                    session()->put('admin_adi', $yonetici->adi ?? ($yonetici->kullaniciadi ?? $uye->email));
                    session()->put('admin_yetki', $yonetici->yetki ?? 1);
                    session()->put('admin_rol', 3);
                    session()->save();
                }
            }
        }

        // Session kontrolü
        if (!session()->has('admin_logged_in') || !session('admin_logged_in')) {
            return redirect()->route('admin.giris')->with('error', 'Lütfen giriş yapın!');
        }
        
        if (!session()->has('admin_id')) {
            session()->flush();
            return redirect()->route('admin.giris')->with('error', 'Oturum sonlanmış, lütfen tekrar giriş yapın!');
        }
        
        // NOT: session()->save() burada çağrılmamalı. Laravel'in StartSession middleware'i
        // request sonunda otomatik kaydeder. Burada erken save çağrısı flash data'yı
        // (success/error mesajlarını) view render edilmeden önce siliyordu.
        
        // Sayfa bazlı yetkilendirme kontrolü (çalışan, bayi ve muhasebe için)
        $adminRol = session('admin_rol', 2);
        if (in_array($adminRol, [2, 3, 5])) {
            $routeName = $request->route()?->getName();

            // Dashboard route'larında sonsuz redirect'i engelle: her rolde ana dashboard erişimi açık olsun.
            if ($routeName === 'admin.dashboard') {
                return $next($request);
            }

            // Takvim herkese açık: herkesin kendi takvimi olur (görünürlük kontrolü
            // TakvimController içinde rol bazlı yapılır).
            if ($routeName && Str::startsWith($routeName, 'admin.takvim.')) {
                return $next($request);
            }

            // Geo referans uçları (il/ilçe/mahalle) herkese açık — form dropdown'ları için
            if ($routeName && Str::startsWith($routeName, 'admin.geo.')) {
                return $next($request);
            }

            // Randevu Yönetimi modülü tüm yöneticilere açık
            if ($routeName && Str::startsWith($routeName, 'admin.randevu.')) {
                return $next($request);
            }
            if ($adminRol == 3 && in_array($routeName, ['admin.bayi.dashboard', 'bayi.dashboard'], true)) {
                return $next($request);
            }
            
            if ($routeName && Schema::hasTable('yonetici_yetkileri')) {
                // Bu yönetici için bu sayfa için yetki kaydı var mı?
                $yetkiKaydi = DB::table('yonetici_yetkileri')
                    ->where('yonetici_id', session('admin_id'))
                    ->where('sayfa_route', $routeName)
                    ->first();
                
                // Eğer yetki kaydı varsa ve gorebilir=0 ise, izin verme
                if ($yetkiKaydi) {
                    if ($yetkiKaydi->gorebilir != 1) {
                        \Log::warning('Yetkisiz erişim denemesi - REDDEDİLDİ', [
                            'yonetici_id' => session('admin_id'),
                            'route' => $routeName,
                            'gorebilir' => $yetkiKaydi->gorebilir
                        ]);
                        // Bayi ise bayi dashboard'a, değilse admin dashboard'a yönlendir
                        $redirectRoute = ($adminRol == 3) ? 'bayi.dashboard' : 'admin.dashboard';
                        return redirect()->route($redirectRoute)
                            ->with('error', 'Bu sayfaya erişim yetkiniz yok!');
                    }
                }
                // Yetki kaydı YOKSA bu katman karar vermez; karar rol bazlı yetki
                // sistemine (RolKontrol middleware + rol_yetkileri) bırakılır.
                // ESKİ DAVRANIŞ: kayıt yoksa varsayılan RED idi — yeni eklenen her
                // sayfada herkesin "yetkiniz yok" almasına ve log şişmesine yol açıyordu.
                // Kişiye özel bilinçli engeller (gorebilir=0 kayıtları) yukarıda
                // aynen çalışmaya devam eder.
            }
        }
        
        return $next($request);
    }
}