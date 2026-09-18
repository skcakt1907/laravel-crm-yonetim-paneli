<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfilController extends Controller
{
    public function index()
    {
        $adminId = session('admin_id');
        
        if (!$adminId) {
            return redirect()->route('admin.giris')
                ->with('error', 'Oturum bulunamadı!');
        }
        
        $yonetici = DB::table('yoneticiler')
            ->where('id', $adminId)
            ->first();
        
        if (!$yonetici) {
            return redirect()->route('admin.giris')
                ->with('error', 'Kullanıcı bulunamadı!');
        }
        
        return view('admin.profil.index', compact('yonetici'));
    }
    
    public function guncelle(Request $request)
    {
        $adminId = session('admin_id');
        
        if (!$adminId) {
            return redirect()->route('admin.giris')
                ->with('error', 'Oturum bulunamadı!');
        }
        
        \Log::info('Profil güncelleme isteği', [
            'admin_id' => $adminId,
            'request_data' => $request->all()
        ]);
        
        try {
            $request->validate([
                'kullaniciadi' => 'nullable|string|max:100',
                'adi' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'telefon' => 'nullable|string|max:20',
            ], [
                'kullaniciadi.string' => 'Kullanıcı adı geçerli bir metin olmalıdır.',
                'kullaniciadi.max' => 'Kullanıcı adı en fazla 100 karakter olabilir.',
                'adi.string' => 'Ad alanı geçerli bir metin olmalıdır.',
                'email.email' => 'Geçerli bir e-posta adresi giriniz.',
                'telefon.string' => 'Telefon alanı geçerli bir metin olmalıdır.',
            ]);
            
            \Log::info('Validation başarılı', ['admin_id' => $adminId]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation hatası', [
                'admin_id' => $adminId,
                'errors' => $e->errors()
            ]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
        
        $data = [];
        
        // Kullanıcı adı kontrolü - başka bir kullanıcıda kullanılıyor mu?
        if ($request->filled('kullaniciadi')) {
            \Log::info('Kullanıcı adı kontrolü yapılıyor', [
                'admin_id' => $adminId,
                'kullaniciadi' => $request->kullaniciadi
            ]);
            $kullaniciAdiKontrol = DB::table('yoneticiler')
                ->where('kullaniciadi', $request->kullaniciadi)
                ->where('id', '!=', $adminId)
                ->first();
            
            if ($kullaniciAdiKontrol) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Bu kullanıcı adı başka bir kullanıcı tarafından kullanılıyor!');
            }
            
            $data['kullaniciadi'] = $request->kullaniciadi;
            \Log::info('Kullanıcı adı data\'ya eklendi', ['kullaniciadi' => $request->kullaniciadi]);
        }
        
        if ($request->filled('adi')) {
            $data['adi'] = $request->adi;
            \Log::info('Ad data\'ya eklendi', ['adi' => $request->adi]);
        }
        
        if ($request->filled('email')) {
            // Mevcut kullanıcının email'ini al
            $mevcutYonetici = DB::table('yoneticiler')
                ->where('id', $adminId)
                ->first();
            
            $mevcutEmail = $mevcutYonetici->email ?? null;
            $yeniEmail = $request->email;
            
            // Email değişmişse kontrol et
            if ($yeniEmail !== $mevcutEmail) {
                \Log::info('Email değişti, kontrol yapılıyor', [
                    'admin_id' => $adminId,
                    'eski_email' => $mevcutEmail,
                    'yeni_email' => $yeniEmail
                ]);
                
                // Email kontrolü - başka bir kullanıcıda kullanılıyor mu?
                $emailKontrol = DB::table('yoneticiler')
                    ->where('email', $yeniEmail)
                    ->where('id', '!=', $adminId)
                    ->first();
                
                if ($emailKontrol) {
                    \Log::warning('Email başka kullanıcıda kullanılıyor', [
                        'admin_id' => $adminId,
                        'email' => $yeniEmail
                    ]);
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor!');
                }
                
                $data['email'] = $yeniEmail;
                \Log::info('Email data\'ya eklendi', ['email' => $yeniEmail]);
            } else {
                \Log::info('Email değişmedi, kontrol atlandı', [
                    'admin_id' => $adminId,
                    'email' => $mevcutEmail
                ]);
            }
        }
        
        if ($request->filled('telefon')) {
            // Mevcut kullanıcının telefonunu al
            if (!isset($mevcutYonetici)) {
                $mevcutYonetici = DB::table('yoneticiler')
                    ->where('id', $adminId)
                    ->first();
            }
            
            $mevcutTelefon = $mevcutYonetici->telefon ?? null;
            $yeniTelefon = $request->telefon;
            
            // Telefon değişmişse ekle
            if ($yeniTelefon !== $mevcutTelefon) {
                $data['telefon'] = $yeniTelefon;
                \Log::info('Telefon data\'ya eklendi', [
                    'eski_telefon' => $mevcutTelefon,
                    'yeni_telefon' => $yeniTelefon
                ]);
            } else {
                \Log::info('Telefon değişmedi, eklenmedi', ['telefon' => $mevcutTelefon]);
            }
        }
        
        // ── PROFİL FOTOĞRAFI (kolon varsa) ──
        if (Schema::hasColumn('yoneticiler', 'profil_foto')) {
            if (!isset($mevcutYonetici)) {
                $mevcutYonetici = DB::table('yoneticiler')->where('id', $adminId)->first();
            }
            $eskiFoto = $mevcutYonetici->profil_foto ?? null;

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
                $dosyaAdi = 'yonetici_' . $adminId . '_' . time() . '.' . $uzanti;
                $request->file('profil_foto')->move($klasor, $dosyaAdi);

                // Eski fotoyu sil (sadece profil klasöründekini)
                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }

                $data['profil_foto'] = 'tema/uploads/profil/' . $dosyaAdi;
                \Log::info('Profil fotoğrafı yüklendi', ['admin_id' => $adminId, 'dosya' => $dosyaAdi]);
            } elseif ($request->boolean('foto_kaldir')) {
                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }
                $data['profil_foto'] = null;
                \Log::info('Profil fotoğrafı kaldırıldı', ['admin_id' => $adminId]);
            }
        }

        \Log::info('Data hazırlandı', [
            'admin_id' => $adminId,
            'data' => $data,
            'data_count' => count($data)
        ]);
        
        if (empty($data)) {
            \Log::warning('Profil güncelleme - boş data', ['admin_id' => $adminId]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lütfen en az bir alan doldurun!');
        }
        
        try {
            $data['updated_at'] = now();
            
            \Log::info('Profil güncelleme - güncellenecek data', [
                'admin_id' => $adminId,
                'data' => $data
            ]);
            
            $updated = DB::table('yoneticiler')
                ->where('id', $adminId)
                ->update($data);
            
            if ($updated === false) {
                \Log::error('Profil güncelleme - veritabanı hatası', [
                    'admin_id' => $adminId,
                    'data' => $data
                ]);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Profil güncellenirken bir hata oluştu!');
            }
            
            // Session'ı güncelle - veritabanından güncel bilgileri al
            $guncelYonetici = DB::table('yoneticiler')
                ->where('id', $adminId)
                ->first();
            
            if ($guncelYonetici) {
                session([
                    'admin_adi' => $guncelYonetici->adi ?? $guncelYonetici->kullaniciadi,
                    'admin_kullanici_adi' => $guncelYonetici->kullaniciadi
                ]);
                session()->save(); // Session'ı kaydet
                
                \Log::info('Profil güncellendi ve session güncellendi', [
                    'admin_id' => $adminId,
                    'yeni_adi' => $guncelYonetici->adi,
                    'yeni_kullaniciadi' => $guncelYonetici->kullaniciadi
                ]);
            } else {
                \Log::warning('Profil güncellendi ama kullanıcı bulunamadı', ['admin_id' => $adminId]);
            }
            
            return redirect(route('admin.profil') . '#success')
                ->with('success', 'Profil bilgileri başarıyla güncellendi!');
                
        } catch (\Exception $e) {
            \Log::error('Profil güncelleme hatası', [
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Profil güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function sifreDegistir(Request $request)
    {
        $adminId = session('admin_id');
        
        if (!$adminId) {
            return redirect()->route('admin.giris')
                ->with('error', 'Oturum bulunamadı!');
        }
        
        $request->validate([
            'eski_sifre' => 'required|string',
            'yeni_sifre' => 'required|string|min:6',
            'yeni_sifre_tekrar' => 'required|string|same:yeni_sifre',
        ], [
            'eski_sifre.required' => 'Mevcut şifre gereklidir.',
            'yeni_sifre.required' => 'Yeni şifre gereklidir.',
            'yeni_sifre.min' => 'Yeni şifre en az 6 karakter olmalıdır.',
            'yeni_sifre_tekrar.required' => 'Yeni şifre tekrarı gereklidir.',
            'yeni_sifre_tekrar.same' => 'Yeni şifreler eşleşmiyor.',
        ]);
        
        $yonetici = DB::table('yoneticiler')
            ->where('id', $adminId)
            ->first();
        
        if (!$yonetici) {
            return redirect()->route('admin.giris')
                ->with('error', 'Kullanıcı bulunamadı!');
        }
        
        // Mevcut şifre kontrolü
        $sifreDogrumu = false;
        
        if ($yonetici->sifre === md5($request->eski_sifre)) {
            $sifreDogrumu = true;
        } elseif (Hash::check($request->eski_sifre, $yonetici->sifre)) {
            $sifreDogrumu = true;
        }
        
        if (!$sifreDogrumu) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Mevcut şifre hatalı!');
        }
        
        try {
            // Yeni şifreyi bcrypt ile kaydet (md5 güvensiz, kaldırıldı)
            $updated = DB::table('yoneticiler')
                ->where('id', $adminId)
                ->update([
                    'sifre' => Hash::make($request->yeni_sifre),
                    'updated_at' => now()
                ]);
            
            if ($updated) {
                \Log::info('Şifre başarıyla değiştirildi', [
                    'admin_id' => $adminId,
                    'updated_at' => now()
                ]);

                // 🔒 Admin yöneticisi olduğu için musteri_bildirimleri'ne değil, kendi mailına bildirim
                // (admin email'i varsa, şifre değişimi bildirimi)
                try {
                    $yoneticiEmail = $yonetici->email ?? null;
                    if ($yoneticiEmail && filter_var($yoneticiEmail, FILTER_VALIDATE_EMAIL)) {
                        $firmaAdi = DB::table('ayarlar')->value('firma_adi') ?: 'DN İş Ortağım';
                        $html = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f4f4f0;padding:24px"><table style="max-width:600px;margin:auto;background:#fff;border-radius:14px;overflow:hidden">'
                            . '<tr><td style="padding:24px 28px;background:linear-gradient(135deg,#b8b62e 0%,#8a8a1f 100%);color:#fff"><h1 style="margin:0;font-size:20px">'.e($firmaAdi).' - Admin Panel</h1></td></tr>'
                            . '<tr><td style="padding:28px;line-height:1.7">'
                            . '<h3 style="color:#dc2626;margin-top:0">🔒 Yönetici Şifreniz Değiştirildi</h3>'
                            . '<p>Sayın <strong>'.e($yonetici->adi ?? $yonetici->kullaniciadi).'</strong>,</p>'
                            . '<p>Admin panel hesabınızın şifresi az önce değiştirildi.</p>'
                            . '<table style="width:100%;background:#f9fafb;padding:14px;border-radius:8px;border-left:3px solid #b8b62e;margin:12px 0">'
                            . '<tr><td style="padding:6px 0;color:#6b7280;width:35%">Tarih:</td><td style="font-weight:600">'.date('d.m.Y H:i').'</td></tr>'
                            . '<tr><td style="padding:6px 0;color:#6b7280">IP:</td><td style="font-weight:600">'.e(request()->ip() ?: '—').'</td></tr>'
                            . '</table>'
                            . '<p style="color:#dc2626;margin-top:16px"><strong>Bu işlemi siz yapmadıysanız:</strong> Derhal sistem yöneticisi ile iletişime geçin.</p>'
                            . '</td></tr></table></body></html>';

                        if (class_exists(\App\Services\EmailNotificationService::class)) {
                            \App\Services\EmailNotificationService::send($yoneticiEmail, '🔒 Admin Şifre Değişikliği', $html, true);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Admin şifre değişim maili gönderilemedi', ['err' => $e->getMessage()]);
                }
                
                return redirect(route('admin.profil') . '#success')
                    ->with('success', 'Şifre başarıyla değiştirildi!');
            } else {
                \Log::warning('Şifre güncellenemedi', ['admin_id' => $adminId]);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Şifre güncellenirken bir hata oluştu!');
            }
        } catch (\Exception $e) {
            \Log::error('Şifre değiştirme hatası', [
                'admin_id' => $adminId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Şifre değiştirilirken bir hata oluştu: ' . $e->getMessage());
        }
    }
}