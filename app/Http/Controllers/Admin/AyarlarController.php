<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AyarlarController extends Controller
{
    public function index()
    {
        $ayarlar = DB::table('ayarlar')->first();
        
        if (!$ayarlar) {
            $ayarlar = (object)[];
        }
        
        return view('admin.ayarlar.index', compact('ayarlar'));
    }
    
    public function guncelle(Request $request)
    {
        $validated = $request->validate([
            'site_baslik' => 'nullable|string|max:255',
            'site_url' => 'nullable|url|max:255',
            'site_desc' => 'nullable|string',
            'site_keyw' => 'nullable|string',
            'firma_adi' => 'nullable|string|max:255',
            'copyright' => 'nullable|string|max:255',
            'firma_telefon' => 'nullable|string|max:50',
            'firma_fax' => 'nullable|string|max:50',
            'firma_email' => 'nullable|email|max:255',
            'firma_adres' => 'nullable|string',
            'whatsapp' => 'nullable|string|max:50',
            'google_maps' => 'nullable|string',
            'facebook' => 'nullable|string|max:500',
            'twitter' => 'nullable|string|max:500',
            'instagram' => 'nullable|string|max:500',
            'linkedin' => 'nullable|string|max:500',
            'youtube' => 'nullable|string|max:500',
            'google_analytics' => 'nullable|string',
            'dogrulama_kodu' => 'nullable|string',
            'canli_destek' => 'nullable|string',
            'site_dil' => 'nullable|string|max:10',
            'site_tema' => 'nullable|string|max:50',
            'kdv' => 'nullable|numeric|min:0|max:100',
            'demo' => 'nullable|boolean',
            'rcaptha' => 'nullable|string|max:255',
        ]);

        // Önce mevcut kayıtları çek (silme işlemleri için)
        $ayarlarMevcut = DB::table('ayarlar')->first();

        $logoDir = public_path('tema/uploads/logo');

        // Logo silme
        $deleteFirmaLogo = $request->boolean('delete_firma_logo');
        if ($deleteFirmaLogo && $ayarlarMevcut && !empty($ayarlarMevcut->firma_logo)) {
            $path = $logoDir . DIRECTORY_SEPARATOR . $ayarlarMevcut->firma_logo;
            if (is_file($path)) {
                @unlink($path);
            }
            $validated['firma_logo'] = null;
        }

        // Footer logo silme
        $deleteFirmaFooterlogo = $request->boolean('delete_firma_footerlogo');
        if ($deleteFirmaFooterlogo && $ayarlarMevcut && !empty($ayarlarMevcut->firma_footerlogo)) {
            $path = $logoDir . DIRECTORY_SEPARATOR . $ayarlarMevcut->firma_footerlogo;
            if (is_file($path)) {
                @unlink($path);
            }
            $validated['firma_footerlogo'] = null;
        }

        // Favicon silme
        $deleteFavicon = $request->boolean('delete_favicon');
        if ($deleteFavicon && $ayarlarMevcut && !empty($ayarlarMevcut->favicon)) {
            $path = $logoDir . DIRECTORY_SEPARATOR . $ayarlarMevcut->favicon;
            if (is_file($path)) {
                @unlink($path);
            }
            $validated['favicon'] = null;
        }
        
        // Logo yükleme
        if ($request->hasFile('firma_logo')) {
            $logo = $request->file('firma_logo');
            $logoName = ImageHelper::saveAsWebp($logo, public_path('tema/uploads/logo'), time() . '_logo');
            $validated['firma_logo'] = $logoName;
        }
        
        // Footer logo yükleme
        if ($request->hasFile('firma_footerlogo')) {
            $footerLogo = $request->file('firma_footerlogo');
            $footerLogoName = ImageHelper::saveAsWebp($footerLogo, public_path('tema/uploads/logo'), time() . '_footer');
            $validated['firma_footerlogo'] = $footerLogoName;
        }
        
        // Favicon yükleme
        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');
            $faviconName = time() . '_favicon.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('tema/uploads/logo'), $faviconName);
            $validated['favicon'] = $faviconName;
        }
        
        $validated['updated_at'] = now();
        
        $ayarlar = DB::table('ayarlar')->first();
        
        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update($validated);
        } else {
            $validated['created_at'] = now();
            DB::table('ayarlar')->insert($validated);
        }
        
        return redirect()->route('admin.ayarlar.index')->with('success', 'Ayarlar başarıyla güncellendi.');
    }
    
    public function bakimModu(Request $request)
    {
        $bakim = $request->input('bakim', 0);
        
        $ayarlar = DB::table('ayarlar')->first();
        
        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update(['bakim_modu' => $bakim]);
        }
        
        return redirect()->route('admin.ayarlar.bakim')->with('success', 'Bakım modu ' . ($bakim ? 'aktif' : 'pasif') . ' edildi.');
    }
    
    public function api()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.api', compact('ayarlar'));
    }
    
    public function apiPost(Request $request)
    {
        try {
            $validated = $request->validate([
                'paytr_merchant_id' => 'nullable|string|max:255',
                'paytr_merchant_key' => 'nullable|string|max:255',
                'paytr_merchant_salt' => 'nullable|string|max:255',
                'iyzico_api_key' => 'nullable|string|max:255',
                'iyzico_secret_key' => 'nullable|string|max:255',
                'iyzico_base_url' => 'nullable|url|max:500',
            ]);
            
            $validated['updated_at'] = now();
            
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                DB::table('ayarlar')->where('id', $ayarlar->id)->update($validated);
            } else {
                $validated['created_at'] = now();
                DB::table('ayarlar')->insert($validated);
            }
            
            return redirect()->route('admin.ayarlar.api')->with('success', 'API ayarları güncellendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.api')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('API ayarları güncelleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.ayarlar.api')
                ->with('error', 'API ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function iletisim()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.iletisim', compact('ayarlar'));
    }
    
    public function iletisimPost(Request $request)
    {
        $request->validate([
            'firma_adi' => 'nullable|string|max:255',
            'adres' => 'nullable|string',
            'telefon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'maps' => 'nullable|string',
        ]);

        // Form alanlari -> DB kolon adlari mapping
        $data = [
            'firma_adi'     => $request->firma_adi,
            'firma_adres'   => $request->adres,
            'firma_telefon' => $request->telefon,
            'firma_email'   => $request->email,
            'google_maps'   => $request->maps,
            'updated_at'    => now(),
        ];

        $ayarlar = DB::table('ayarlar')->first();

        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('ayarlar')->insert($data);
        }

        return redirect()->route('admin.ayarlar.iletisim')->with('success', 'İletişim ayarları başarıyla güncellendi.');
    }
    
    public function sosyalMedya()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.sosyal-medya', compact('ayarlar'));
    }
    
    public function sosyalMedyaPost(Request $request)
    {
        try {
            $validated = $request->validate([
                'facebook' => 'nullable|url|max:500',
                'twitter' => 'nullable|url|max:500',
                'instagram' => 'nullable|url|max:500',
                'linkedin' => 'nullable|url|max:500',
                'youtube' => 'nullable|url|max:500',
                'tiktok' => 'nullable|url|max:500',
                'pinterest' => 'nullable|url|max:500',
            ]);
            
            $validated['updated_at'] = now();
            
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                DB::table('ayarlar')->where('id', $ayarlar->id)->update($validated);
            } else {
                $validated['created_at'] = now();
                DB::table('ayarlar')->insert($validated);
            }
            
            return redirect()->route('admin.ayarlar.sosyal')->with('success', 'Sosyal medya ayarları güncellendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.sosyal')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Sosyal medya ayarları güncelleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.ayarlar.sosyal')
                ->with('error', 'Sosyal medya ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function modul()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.modul', compact('ayarlar'));
    }
    
    public function modulPost(Request $request)
    {
        try {
            // Modül ayarları için boolean değerler
            $data = [];
            $booleanFields = ['blog_aktif', 'referans_aktif', 'iletisim_aktif', 'ebulten_aktif', 'kampanya_aktif'];
            
            foreach ($booleanFields as $field) {
                if ($request->has($field)) {
                    $data[$field] = $request->input($field) ? 1 : 0;
                }
            }
            
            if (empty($data)) {
                return redirect()->route('admin.ayarlar.modul')
                    ->with('error', 'Güncellenecek modül ayarı bulunamadı.');
            }
            
            $data['updated_at'] = now();
            
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                DB::table('ayarlar')->where('id', $ayarlar->id)->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('ayarlar')->insert($data);
            }
            
            return redirect()->route('admin.ayarlar.modul')->with('success', 'Modül ayarları güncellendi.');
        } catch (\Exception $e) {
            \Log::error('Modül ayarları güncelleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.ayarlar.modul')
                ->with('error', 'Modül ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function limit()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.limit', compact('ayarlar'));
    }
    
    public function limitPost(Request $request)
    {
        try {
            $validated = $request->validate([
                'max_upload_size' => 'nullable|integer|min:1|max:100',
                'max_file_count' => 'nullable|integer|min:1|max:50',
                'session_timeout' => 'nullable|integer|min:5|max:1440',
            ]);
            
            $validated['updated_at'] = now();
            
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                DB::table('ayarlar')->where('id', $ayarlar->id)->update($validated);
            } else {
                $validated['created_at'] = now();
                DB::table('ayarlar')->insert($validated);
            }
            
            return redirect()->route('admin.ayarlar.limit')->with('success', 'Limit ayarları güncellendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.limit')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Limit ayarları güncelleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.ayarlar.limit')
                ->with('error', 'Limit ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function bakim()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.bakim', compact('ayarlar'));
    }
    
    public function bakimPost(Request $request)
    {
        try {
            $validated = $request->validate([
                'bakim_modu' => 'nullable|boolean',
                'bakim_baslik' => 'nullable|string|max:255',
                'bakim_mesaj' => 'nullable|string',
            ]);
            
            $validated['updated_at'] = now();
            
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                DB::table('ayarlar')->where('id', $ayarlar->id)->update($validated);
            } else {
                $validated['created_at'] = now();
                DB::table('ayarlar')->insert($validated);
            }
            
            return redirect()->route('admin.ayarlar.bakim')->with('success', 'Bakım modu ayarları güncellendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.bakim')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Bakım modu ayarları güncelleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.ayarlar.bakim')
                ->with('error', 'Bakım modu ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function mail()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.mail', compact('ayarlar'));
    }
    
    public function mailPost(Request $request)
    {
        // Test mail gönderimi
        if ($request->has('test_mail') && $request->test_email) {
            try {
                $ayarlar = DB::table('ayarlar')->first();
                
                // FIX: DB öncelikli oku (panel kaydedince DB güncelleniyor, .env eski olabilir)
                $mailHost = ($ayarlar->mail_host ?? null) ?: env('MAIL_HOST');
                $mailPort = ($ayarlar->mail_port ?? null) ?: env('MAIL_PORT', 587);
                $mailUsername = ($ayarlar->mail_username ?? null) ?: env('MAIL_USERNAME');
                $mailPassword = ($ayarlar->mail_password ?? null) ?: env('MAIL_PASSWORD');
                $mailEncryption = ($ayarlar->mail_encryption ?? null) ?: env('MAIL_ENCRYPTION', 'tls');
                $mailFromAddress = ($ayarlar->mail_from_address ?? null) ?: env('MAIL_FROM_ADDRESS', $mailUsername);
                $mailFromName = ($ayarlar->mail_from_name ?? null) ?: env('MAIL_FROM_NAME', config('app.name', 'DN İş Ortağım'));
                
                if (!$mailHost || !$mailUsername) {
                    return redirect()->route('admin.ayarlar.mail')->with('error', 'SMTP ayarları eksik! Lütfen sunucu ve kullanıcı adı girin.');
                }
                
                if (!$mailPassword) {
                    return redirect()->route('admin.ayarlar.mail')->with('error', 'SMTP şifresi eksik!');
                }
                
                // Dinamik SMTP yapılandırması
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.transport' => 'smtp',
                    'mail.mailers.smtp.host' => $mailHost,
                    'mail.mailers.smtp.port' => $mailPort,
                    'mail.mailers.smtp.username' => $mailUsername,
                    'mail.mailers.smtp.password' => $mailPassword,
                    'mail.mailers.smtp.encryption' => $mailEncryption,
                    'mail.from.address' => $mailFromAddress,
                    'mail.from.name' => $mailFromName,
                ]);

                // FIX: Mail manager cache'lenmiş olabilir, yeni transport ile yeniden başlat
                try {
                    app()->forgetInstance('mail.manager');
                    app()->forgetInstance('mailer');
                    \Illuminate\Support\Facades\Mail::clearResolvedInstances();
                } catch (\Throwable $e) {}
                
                $testEmail = $request->test_email;
                $firmaAdi = $mailFromName;
                $logoUrl  = mail_logo_url();
                $yil      = date('Y');
                $zaman    = date('d.m.Y H:i:s');

                $htmlContent = '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:32px 36px 24px;text-align:center;border-bottom:1px solid #f0f1ec">
      <img src="' . $logoUrl . '" alt="' . e($firmaAdi) . '" width="148" style="display:block;margin:0 auto;max-width:148px;height:auto;border:0">
    </td></tr>
    <tr><td style="padding:30px 36px;font-size:15px;line-height:1.7;color:#3a4133">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px"><tr>
        <td align="center" style="background-color:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:20px">
          <div style="font-size:34px;line-height:1">✅</div>
          <div style="font-size:18px;font-weight:800;color:#15803d;margin-top:8px">Test E-postası Başarılı!</div>
        </td>
      </tr></table>
      <p style="margin:0 0 18px">Bu e-posta, SMTP ayarlarınızın doğru çalıştığını doğrulamak için gönderilmiştir.</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #eceee6;border-radius:10px;overflow:hidden;margin:0 0 18px">
        <tr><td style="padding:12px 16px;background-color:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270;width:42%">Gönderim Zamanı</td><td style="padding:12px 16px;border-bottom:1px solid #eceee6;font-size:14px;font-weight:600">' . $zaman . '</td></tr>
        <tr><td style="padding:12px 16px;background-color:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270">SMTP Sunucu</td><td style="padding:12px 16px;border-bottom:1px solid #eceee6;font-size:14px;font-weight:600">' . e($mailHost) . '</td></tr>
        <tr><td style="padding:12px 16px;background-color:#f7f8f3;font-size:13px;color:#7a8270">Port</td><td style="padding:12px 16px;font-size:14px;font-weight:600">' . e($mailPort) . '</td></tr>
      </table>
      <p style="margin:0;color:#15803d;font-weight:700">Mail ayarlarınız doğru yapılandırılmış! 🎉</p>
    </td></tr>
    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0;font-size:11px;color:#b3b8a8">© ' . $yil . ' ' . e($firmaAdi) . '</p>
    </td></tr>
  </table>
</td></tr>
</table></body></html>';
                
                // Mail gönderimini dene
                \Illuminate\Support\Facades\Mail::html($htmlContent, function ($message) use ($testEmail, $firmaAdi, $mailFromAddress) {
                    $message->to($testEmail)
                            ->subject('Test E-postası - ' . $firmaAdi)
                            ->from($mailFromAddress, $firmaAdi);
                });
                
                \Log::info('Test mail gönderildi', [
                    'to' => $testEmail,
                    'from' => $mailFromAddress,
                    'host' => $mailHost,
                    'port' => $mailPort
                ]);
                
                return redirect()->route('admin.ayarlar.mail')->with('success', 'Test e-postası başarıyla gönderildi! Lütfen ' . $testEmail . ' adresini kontrol edin. (Spam klasörünü de kontrol edin)');
                
            } catch (\Swift_TransportException $e) {
                \Log::error('SMTP bağlantı hatası', [
                    'error' => $e->getMessage(),
                    'host' => $mailHost ?? 'N/A',
                    'port' => $mailPort ?? 'N/A',
                    'username' => $mailUsername ?? 'N/A'
                ]);
                return redirect()->route('admin.ayarlar.mail')->with('error', 'SMTP bağlantı hatası: ' . $e->getMessage() . ' - Lütfen sunucu, port ve şifre bilgilerini kontrol edin.');
            } catch (\Exception $e) {
                \Log::error('Test mail hatası', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'host' => $mailHost ?? 'N/A',
                    'port' => $mailPort ?? 'N/A'
                ]);
                return redirect()->route('admin.ayarlar.mail')->with('error', 'Mail gönderilemedi: ' . $e->getMessage());
            }
        }
        
        $data = $request->except(['_token', 'test_mail', 'test_email']);
        $data['updated_at'] = now();
        
        $ayarlar = DB::table('ayarlar')->first();
        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update($data);
        }

        // FIX: .env dosyasını da güncelle (Laravel Mail::send() bunu okur)
        try {
            $envUpdates = [];
            if (!empty($data['mail_host']))         $envUpdates['MAIL_HOST']         = $data['mail_host'];
            if (!empty($data['mail_port']))         $envUpdates['MAIL_PORT']         = $data['mail_port'];
            if (!empty($data['mail_username']))     $envUpdates['MAIL_USERNAME']     = $data['mail_username'];
            if (!empty($data['mail_password']))     $envUpdates['MAIL_PASSWORD']     = $data['mail_password'];
            if (!empty($data['mail_encryption']))   $envUpdates['MAIL_ENCRYPTION']   = $data['mail_encryption'];
            if (!empty($data['mail_from_address'])) $envUpdates['MAIL_FROM_ADDRESS'] = $data['mail_from_address'];
            if (!empty($data['mail_from_name']))    $envUpdates['MAIL_FROM_NAME']    = $data['mail_from_name'];
            $envUpdates['MAIL_MAILER'] = 'smtp';  // emin olalım

            if (!empty($envUpdates)) {
                $this->updateEnvFile($envUpdates);

                // Runtime config'i de güncelle (anlık etki)
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host'       => $data['mail_host']       ?? config('mail.mailers.smtp.host'),
                    'mail.mailers.smtp.port'       => $data['mail_port']       ?? config('mail.mailers.smtp.port'),
                    'mail.mailers.smtp.username'   => $data['mail_username']   ?? config('mail.mailers.smtp.username'),
                    'mail.mailers.smtp.password'   => $data['mail_password']   ?? config('mail.mailers.smtp.password'),
                    'mail.mailers.smtp.encryption' => $data['mail_encryption'] ?? config('mail.mailers.smtp.encryption'),
                    'mail.from.address' => $data['mail_from_address'] ?? config('mail.from.address'),
                    'mail.from.name'    => $data['mail_from_name']    ?? config('mail.from.name'),
                ]);

                // Config cache varsa temizle (önemli - değişiklik aktif olsun)
                try {
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                } catch (\Throwable $e) {
                    \Log::warning('config:clear çalıştırılamadı', ['err' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('.env güncelleme hatası', ['err' => $e->getMessage()]);
            return redirect()->route('admin.ayarlar.mail')
                ->with('error', 'Ayarlar DB\'ye kaydedildi ama .env güncellenemedi: ' . $e->getMessage());
        }
        
        return redirect()->route('admin.ayarlar.mail')->with('success', 'Mail ayarları güncellendi. (.env dosyası ve config cache yenilendi)');
    }


    /**
     * .env dosyasını güvenli şekilde güncelle
     * - Atomic write: önce backup, sonra yaz, hata olursa geri al
     * - Özel karakter içeren değerleri çift tırnak içine alır
     */
    protected function updateEnvFile(array $values): bool
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            throw new \Exception('.env dosyası bulunamadı: ' . $envPath);
        }
        if (!is_writable($envPath)) {
            throw new \Exception('.env dosyası yazılabilir değil. Sunucu izinlerini kontrol edin (644 veya 664).');
        }

        $content = file_get_contents($envPath);
        $backup = $content;

        foreach ($values as $key => $value) {
            // Değeri güvenli formata çevir
            $escapedValue = $this->envEscapeValue((string)$value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            if (preg_match($pattern, $content)) {
                // Var olan satırı güncelle
                $content = preg_replace($pattern, $key . '=' . $escapedValue, $content);
            } else {
                // Yoksa sona ekle
                $content .= PHP_EOL . $key . '=' . $escapedValue;
            }
        }

        // Atomik yazma: önce temp dosyaya, sonra rename
        $tempPath = $envPath . '.tmp';
        if (file_put_contents($tempPath, $content, LOCK_EX) === false) {
            throw new \Exception('.env temp dosyası yazılamadı');
        }
        if (!rename($tempPath, $envPath)) {
            // Hata durumunda backup'tan geri yükle
            file_put_contents($envPath, $backup, LOCK_EX);
            @unlink($tempPath);
            throw new \Exception('.env dosyası güncellenemedi (rename hatası)');
        }

        return true;
    }


    /**
     * .env değerini güvenli formata çevir
     * - Boşluk, =, ", $, ' karakterleri için çift tırnak ekle
     * - İçindeki çift tırnakları kaçır
     */
    protected function envEscapeValue(string $value): string
    {
        // Boş değer
        if ($value === '') return '';

        // Özel karakter var mı?
        $needsQuotes = preg_match('/[\s"\'#=$]/', $value);

        if ($needsQuotes) {
            // İçerdeki çift tırnakları kaçır
            $value = str_replace('"', '\"', $value);
            return '"' . $value . '"';
        }

        return $value;
    }
    
    public function sms()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.sms', compact('ayarlar'));
    }
    
    public function smsPost(Request $request)
    {
        $ayarlar = DB::table('ayarlar')->first();

        // Yeni form isimleri öncelikli, eski isimler fallback
        $kullaniciAdi = $request->input('sms_kullanici_adi', $request->input('sms_kullanici_kodu', $request->input('KULLANICIADI', '')));
        $sifre        = $request->input('sms_sifre', $request->input('SIFRE', ''));
        $baslik       = $request->input('sms_baslik', $request->input('ORGINATOR', ''));
        $postUrl      = $request->input('sms_post_url', $request->input('postUrl', 'https://api.netgsm.com.tr/sms/send/get'));
        $testTel      = $request->input('sms_test_telefon', $request->input('m_kime', ''));
        $apiKey       = $request->input('sms_api_key', '');

        $smsData = [
            'sms_post_url'      => $postUrl,
            'sms_kullanici_adi' => $kullaniciAdi,
            'sms_baslik'        => $baslik,
            'sms_test_telefon'  => $testTel,
            'sms_aktif'         => $request->has('sms_aktif') || (int)($ayarlar->sms_aktif ?? 0) === 1 ? 1 : 1,
        ];

        // Şifre boş gönderildiyse mevcut değeri koru (UI'da boş bıraktıysa silmesin)
        if ($sifre !== '' && $sifre !== null) {
            $smsData['sms_sifre'] = $sifre;
        }

        // sms_api_key kolonu varsa kaydet
        if (Schema::hasColumn('ayarlar', 'sms_api_key') && $apiKey !== '') {
            $smsData['sms_api_key'] = $apiKey;
        }

        if (Schema::hasColumn('ayarlar', 'updated_at')) {
            $smsData['updated_at'] = now();
        }

        if (Schema::hasColumn('ayarlar', 'defaultsms')) {
            $smsData['defaultsms'] = 'netgsm';
        }

        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update($smsData);
        } else {
            if (Schema::hasColumn('ayarlar', 'created_at')) {
                $smsData['created_at'] = now();
            }
            DB::table('ayarlar')->insert($smsData);
        }

        return redirect()->route('admin.ayarlar.sms')->with('success', 'SMS ayarları başarıyla güncellendi.');
    }
    
    /**
     * Test mail gönderme
     */
    public function mailTest(Request $request)
    {
        try {
            $validated = $request->validate([
                'test_email' => 'required|email|max:255',
            ], [
                'test_email.required' => 'E-posta adresi gereklidir.',
                'test_email.email' => 'Geçerli bir e-posta adresi giriniz.',
            ]);

            $to = $validated['test_email'];
            $subject = 'Mail Ayarları Test - ' . now()->format('d.m.Y H:i');
            $body = "Bu bir test e-postasıdır.\n\nMail ayarlarınız doğru yapılandırılmıştır.\n\n" . now()->format('d.m.Y H:i:s');

            try {
                \Mail::raw($body, function ($m) use ($to, $subject) {
                    $m->to($to)->subject($subject);
                });
                return redirect()->route('admin.ayarlar.mail')
                    ->with('success', 'Test e-postası ' . $to . ' adresine gönderildi.');
            } catch (\Exception $mailEx) {
                \Log::error('Mail test gönderme hatası', ['error' => $mailEx->getMessage()]);
                return redirect()->route('admin.ayarlar.mail')
                    ->with('error', 'Mail gönderilemedi: ' . $mailEx->getMessage());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.mail')
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function smsTest(Request $request)
    {
        try {
            $validated = $request->validate([
                'test_telefon' => 'required|string|max:20',
            ], [
                'test_telefon.required' => 'Telefon numarası gereklidir.',
                'test_telefon.max' => 'Telefon numarası en fazla 20 karakter olabilir.',
            ]);
            
            $smsService = new \App\Services\SmsService();
            $testMesaj = "DENEME";
            
            $result = $smsService->send($validated['test_telefon'], $testMesaj);
            
            // $result'ın array olduğundan emin ol
            if (!is_array($result)) {
                \Log::error('SMS Service beklenmeyen dönüş tipi', [
                    'result_type' => gettype($result),
                    'result_value' => $result,
                ]);
                return redirect()->route('admin.ayarlar.sms')
                    ->with('error', 'SMS servisi beklenmeyen bir yanıt döndürdü. Lütfen log dosyasını kontrol edin.')
                    ->withInput();
            }
            
            // Güvenli erişim
            $success = isset($result['success']) && $result['success'] === true;
            $resultMessage = $result['message'] ?? 'Bilinmeyen hata';
            
            if ($success) {
                // data'nın array olduğundan emin ol
                $data = isset($result['data']) && is_array($result['data']) ? $result['data'] : [];
                $responseCode = isset($data['code']) ? $data['code'] : '';
                $message = 'Test SMS başarıyla gönderildi! Telefonunuzu kontrol edin.';
                if ($responseCode) {
                    $message .= ' (Kod: ' . $responseCode . ')';
                }
                return redirect()->route('admin.ayarlar.sms')->with('success', $message);
            } else {
                return redirect()->route('admin.ayarlar.sms')
                    ->with('error', 'Test SMS gönderilemedi: ' . $resultMessage)
                    ->withInput();
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.sms')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('SMS Test Hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.ayarlar.sms')
                ->with('error', 'SMS gönderilirken hata oluştu: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function sanalPos()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.sanal-pos', compact('ayarlar'));
    }
    
    public function sanalPosPost(Request $request)
    {
        try {
            $validated = $request->validate([
                'paytr_merchant_id' => 'nullable|string|max:255',
                'paytr_merchant_key' => 'nullable|string|max:255',
                'paytr_merchant_salt' => 'nullable|string|max:255',
                'paytr_aktif' => 'nullable|boolean',
                'paytr_test_mode' => 'nullable|boolean',
                'iyzico_apikey' => 'nullable|string|max:255',
                'iyzico_secret' => 'nullable|string|max:255',
                'iyzico_base' => 'nullable|string|max:500',
                'iyzico_aktif' => 'nullable|boolean',
                'havale_aktif' => 'nullable|boolean',
                'bakiye_odeme_aktif' => 'nullable|boolean',
                'defaultpayment' => 'nullable|string|max:50',
                'kdv' => 'nullable|numeric|min:0|max:100',
                'para_birimi' => 'nullable|string|max:10',
                'min_bakiye_yukleme' => 'nullable|numeric|min:0',
            ]);

            // Boolean alanları normalize et (checkbox göndermeyebilir)
            foreach (['paytr_aktif','paytr_test_mode','iyzico_aktif','havale_aktif','bakiye_odeme_aktif'] as $b) {
                $validated[$b] = $request->has($b) ? 1 : 0;
            }

            $validated['updated_at'] = now();

            // Sadece var olan kolonları update et (yeni kolonlar yoksa görmezden gel)
            $existingCols = Schema::getColumnListing('ayarlar');
            $filtered = array_intersect_key($validated, array_flip($existingCols));

            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                DB::table('ayarlar')->where('id', $ayarlar->id)->update($filtered);
            } else {
                $filtered['created_at'] = now();
                DB::table('ayarlar')->insert($filtered);
            }

            return redirect()->route('admin.ayarlar.sanal')->with('success', 'Sanal pos ayarları güncellendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.ayarlar.sanal')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Sanal pos ayarları güncelleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.ayarlar.sanal')
                ->with('error', 'Sanal pos ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function arkaplan()
    {
        $ayarlar = DB::table('ayarlar')->first();
        if (!$ayarlar) $ayarlar = (object)[];
        return view('admin.ayarlar.arkaplan', compact('ayarlar'));
    }
    
    public function arkaplanPost(Request $request)
    {
        $sayfalar = ['anasayfa', 'paketler', 'hosting', 'blog', 'iletisim', 'hizmet', 'domain', 'sayfa', 'referanslar', 'firsatlar'];
        
        $data = $request->except(['_token']);
        $data['updated_at'] = now();
        
        // Her sayfa için arkaplan resmi yükleme
        foreach ($sayfalar as $sayfaAdi) {
            $inputName = "{$sayfaAdi}_arkaplan_resim";
            if ($request->hasFile($inputName)) {
                $resim = $request->file($inputName);
                $resimAdi = time() . '_' . $sayfaAdi . '.' . $resim->getClientOriginalExtension();
                
                // Klasör adını belirle
                $klasor = match($sayfaAdi) {
                    'anasayfa' => 'anasayfa',
                    'paketler' => 'paketler',
                    'hosting' => 'hosting',
                    'blog' => 'blog',
                    'iletisim' => 'iletisim',
                    'hizmet' => 'hizmetler',
                    'domain' => 'alanadi',
                    'sayfa' => 'sayfalar',
                    'referanslar' => 'referanslar',
                    'firsatlar' => 'firsatlar',
                    default => 'paketler'
                };
                
                // Klasör yoksa oluştur
                $hedefKlasor = public_path("tema/uploads/arkaplan/{$klasor}");
                if (!file_exists($hedefKlasor)) {
                    mkdir($hedefKlasor, 0755, true);
                }
                
                $resim->move($hedefKlasor, $resimAdi);
                
                // Eski resmi sil
                $arkaplan = DB::table('arka_plan')->where('id', 1)->first();
                if ($arkaplan) {
                    $resimKolonu = match($sayfaAdi) {
                        'anasayfa' => 'anasayfa',
                        'paketler' => 'paketler',
                        'hosting' => 'hosting',
                        'blog' => 'blog',
                        'iletisim' => 'iletisim',
                        'hizmet' => 'hizmetler',
                        'domain' => 'alanadi',
                        'sayfa' => 'sayfalar',
                        'referanslar' => 'referanslar',
                        'firsatlar' => 'firsatlar',
                        default => 'paketler'
                    };
                    
                    if (!empty($arkaplan->{$resimKolonu}) && file_exists($hedefKlasor . '/' . $arkaplan->{$resimKolonu})) {
                        @unlink($hedefKlasor . '/' . $arkaplan->{$resimKolonu});
                    }
                    
                    // arka_plan tablosuna kaydet
                    if (Schema::hasTable('arka_plan')) {
                        DB::table('arka_plan')->where('id', 1)->update([$resimKolonu => $resimAdi]);
                    }
                }
                
                // data'dan çıkar (dosya adı değil, dosya objesi)
                unset($data[$inputName]);
            }
        }
        
        // Sadece 'ayarlar' tablosunda gerçekten var olan kolonları yaz
        // (form'da _arkaplan_turu/_renk/_gradient_* gibi kolonu olmayan alanlar var → 1054 hatası)
        $mevcutKolonlar = Schema::getColumnListing('ayarlar');
        $data = array_intersect_key($data, array_flip($mevcutKolonlar));

        $ayarlar = DB::table('ayarlar')->first();
        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('ayarlar')->insert($data);
        }

        return redirect()->route('admin.ayarlar.arkaplan')->with('success', 'Arkaplan ayarları güncellendi.');
    }
    
    /**
     * Sayfa Bazlı Bakım Modu
     */
    public function sayfaBakim()
    {
        if (!Schema::hasTable('sayfa_bakim')) {
            // Tabloyu oluştur
            \Artisan::call('migrate', ['--force' => true]);
        }
        
        $sayfalar = DB::table('sayfa_bakim')->orderBy('sayfa_adi')->get();
        return view('admin.ayarlar.sayfa-bakim', compact('sayfalar'));
    }
    
    public function sayfaBakimStore(Request $request)
    {
        $request->validate([
            'sayfa_adi' => 'required|string|max:100',
            'url_pattern' => 'required|string|max:255',
        ]);
        
        DB::table('sayfa_bakim')->insert([
            'sayfa_adi' => $request->sayfa_adi,
            'route_name' => $request->route_name,
            'url_pattern' => $request->url_pattern,
            'baslik' => $request->baslik ?? 'Sayfa Bakımda',
            'mesaj' => $request->mesaj ?? 'Bu sayfa şu anda bakımdadır.',
            'baslangic_tarihi' => $request->baslangic_tarihi,
            'bitis_tarihi' => $request->bitis_tarihi,
            'aktif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.ayarlar.sayfa-bakim')->with('success', 'Sayfa başarıyla eklendi.');
    }
    
    public function sayfaBakimUpdate(Request $request, $id)
    {
        $request->validate([
            'sayfa_adi' => 'required|string|max:100',
        ]);
        
        DB::table('sayfa_bakim')->where('id', $id)->update([
            'sayfa_adi' => $request->sayfa_adi,
            'route_name' => $request->route_name,
            'url_pattern' => $request->url_pattern,
            'baslik' => $request->baslik,
            'mesaj' => $request->mesaj,
            'baslangic_tarihi' => $request->baslangic_tarihi ?: null,
            'bitis_tarihi' => $request->bitis_tarihi ?: null,
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.ayarlar.sayfa-bakim')->with('success', 'Sayfa güncellendi.');
    }
    
    public function sayfaBakimToggle($id)
    {
        $sayfa = DB::table('sayfa_bakim')->where('id', $id)->first();
        
        if ($sayfa) {
            DB::table('sayfa_bakim')->where('id', $id)->update([
                'aktif' => !$sayfa->aktif,
                'updated_at' => now(),
            ]);
            
            $durum = !$sayfa->aktif ? 'bakıma alındı' : 'yayına alındı';
            return redirect()->route('admin.ayarlar.sayfa-bakim')->with('success', "{$sayfa->sayfa_adi} sayfası {$durum}.");
        }
        
        return redirect()->route('admin.ayarlar.sayfa-bakim')->with('error', 'Sayfa bulunamadı.');
    }
    
    public function sayfaBakimDelete($id)
    {
        DB::table('sayfa_bakim')->where('id', $id)->delete();
        return redirect()->route('admin.ayarlar.sayfa-bakim')->with('success', 'Sayfa silindi.');
    }
    
    /**
     * Fatura Ayarları
     */
    public function fatura()
    {
        $ayarlar = DB::table('ayarlar')->first();
        
        if (!$ayarlar) {
            $ayarlar = (object)[];
        }
        
        return view('admin.ayarlar.fatura', compact('ayarlar'));
    }
    
    public function faturaPost(Request $request)
    {
        $validated = $request->validate([
            'fatura_firma_adi' => 'nullable|string|max:255',
            'fatura_vergi_no' => 'nullable|string|max:50',
            'fatura_vergi_dairesi' => 'nullable|string|max:255',
            'fatura_adres' => 'nullable|string',
            'fatura_telefon' => 'nullable|string|max:50',
            'fatura_email' => 'nullable|email|max:255',
            'fatura_web' => 'nullable|url|max:255',
            'fatura_otomatik_kes' => 'nullable|boolean',
        ]);
        
        $ayarlar = DB::table('ayarlar')->first();
        
        if ($ayarlar) {
            DB::table('ayarlar')->where('id', $ayarlar->id)->update([
                'fatura_firma_adi' => $validated['fatura_firma_adi'] ?? null,
                'fatura_vergi_no' => $validated['fatura_vergi_no'] ?? null,
                'fatura_vergi_dairesi' => $validated['fatura_vergi_dairesi'] ?? null,
                'fatura_adres' => $validated['fatura_adres'] ?? null,
                'fatura_telefon' => $validated['fatura_telefon'] ?? null,
                'fatura_email' => $validated['fatura_email'] ?? null,
                'fatura_web' => $validated['fatura_web'] ?? null,
                'fatura_otomatik_kes' => $request->has('fatura_otomatik_kes') ? 1 : 0,
                'updated_at' => now(),
            ]);
        } else {
            $validated['created_at'] = now();
            $validated['updated_at'] = now();
            $validated['fatura_otomatik_kes'] = $request->has('fatura_otomatik_kes') ? 1 : 0;
            DB::table('ayarlar')->insert($validated);
        }
        
        return redirect()->route('admin.ayarlar.fatura')->with('success', 'Fatura ayarları başarıyla güncellendi.');
    }
}