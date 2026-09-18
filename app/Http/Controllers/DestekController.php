<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Destek;
use App\Models\Ayar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DestekController extends Controller
{
    public function index()
    {
        $ayarlar = Ayar::first();
        $destekler = Destek::where('uyeid', Auth::guard('uye')->id())
            ->where('ustid', 0)
            ->orderBy('son_cevap', 'desc')
            ->paginate(20);
        
        return view('tema.destek-taleplerim', compact('ayarlar', 'destekler'));
    }
    
    public function detay($id)
    {
        $ayarlar = Ayar::first();
        $destek = Destek::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->firstOrFail();
        
        // Alt mesajları getir
        $cevaplar = Destek::where('ustid', $id)
            ->orderBy('tarih', 'asc')
            ->get();
        
        return view('tema.destek-detay', compact('ayarlar', 'destek', 'cevaplar'));
    }
    
    public function olustur()
    {
        $ayarlar = Ayar::first();
        return view('tema.destek-talebi-olustur', compact('ayarlar'));
    }
    
    public function olusturPost(Request $request)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'hizmet' => 'nullable|string|max:255',
            'mesaj' => 'required|string',
            'oncelik' => 'nullable|integer|in:0,1,2',
        ]);

        $destek = Destek::create([
            'uyeid' => Auth::guard('uye')->id(),
            'baslik' => $validated['baslik'],
            'hizmet' => $validated['hizmet'] ?? 'Genel',
            'mesaj' => $validated['mesaj'],
            'oncelik' => $validated['oncelik'] ?? 0,
            'durum' => 0, // Beklemede
            'ustid' => 0,
            'tarih' => now(),
            'son_cevap' => now(),
        ]);

        // Admine bildirim (mail + uygulama içi)
        try {
            $musteri = \Illuminate\Support\Facades\DB::table('uyeler')->where('id', Auth::guard('uye')->id())->first();
            if ($musteri) {
                \App\Services\DestekMailHelper::yeniTalepMusteridenAdmine($destek, $musteri);
            }
            // Panel zil bildirimi
            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri');
            admin_bildirim_gonder(
                '🎫 Yeni Destek Talebi: ' . $destek->baslik,
                $musteriAd . ' yeni bir destek talebi açtı (#' . $destek->id . ')',
                'destek',
                'destek',
                (int) $destek->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Destek yeni talep admin bildirimi: ' . $e->getMessage());
        }

        return redirect()->route('destek.taleplerim')
            ->with('success', 'Destek talebiniz oluşturuldu. (#' . $destek->id . ')');
    }
    
    public function cevapla(Request $request, $id)
    {
        $destek = Destek::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'mesaj' => 'required|string',
            'dosya' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,rar|max:10240',
        ]);

        // Dosya yükleme (varsa) - eski sistemle uyumlu klasör: public/tema/uploads/destek
        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $file = $request->file('dosya');

            $uploadPath = public_path('tema/uploads/destek');
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }

            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $dosyaYolu = 'tema/uploads/destek/' . $fileName;
        }

        // Cevap oluştur
        // NOT: durum kodlama → 0=Beklemede (admin cevaplamalı), 1=Cevaplandı (admin cevapladı), 2=Kapalı
        // Müşteri mesaj atınca durum=0 (admin'in görmesi gereken, beklemede)
        $cevapData = [
            'uyeid'     => Auth::guard('uye')->id(),
            'baslik'    => 'RE: ' . $destek->baslik,
            'hizmet'    => $destek->hizmet,
            'mesaj'     => $validated['mesaj'],
            'oncelik'   => $destek->oncelik,
            'dosya'     => $dosyaYolu,
            'durum'     => 0, // Alt mesaj, müşteri tarafından — beklemede
            'ustid'     => $id,
            'tarih'     => now(),
            'son_cevap' => now(),
        ];

        // Schema-aware: gonderen_tip kolonu varsa 'musteri' yaz (admin controller'da 'admin' yazıyor)
        $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('destek');
        if (in_array('gonderen_tip', $kolonlar, true)) {
            $cevapData['gonderen_tip'] = 'musteri';
        }

        $cevap = Destek::create($cevapData);

        // Ana talebi güncelle: durum=0 (beklemede), son_cevap=now()
        // ÖNCEKİ HATA: burada durum=2 (kapalı) yazılıyordu, talep otomatik kapanıyordu
        $destek->update([
            'son_cevap' => now(),
            'durum'     => 0, // Müşteri mesaj attı → admin görmesi gereken, beklemede
        ]);

        // Admine bildirim (mail + uygulama içi)
        try {
            $musteri = \Illuminate\Support\Facades\DB::table('uyeler')->where('id', Auth::guard('uye')->id())->first();
            if ($musteri) {
                \App\Services\DestekMailHelper::cevapMusteridenAdmine($destek, $cevap, $musteri);
            }
            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri');
            admin_bildirim_gonder(
                '💬 Destek Yanıtı: ' . $destek->baslik,
                $musteriAd . ' destek talebine yanıt yazdı (#' . $destek->id . ')',
                'destek',
                'destek',
                (int) $destek->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Destek cevap admin bildirimi: ' . $e->getMessage());
        }

        return redirect()->route('destek.detay', $id)
            ->with('success', 'Yanıtınız gönderildi.');
    }
    
    
    /**
     * Müşteri talebini kapatır (silmez, sadece durum değiştirir)
     *
     * Güvenlik:
     * - Sadece kendi talebini kapatabilir
     * - Zaten kapalı bir talebi tekrar kapatamaz
     */
    public function kapat(Request $request, $id)
    {
        $uyeId = session('uye_id') ?? \Illuminate\Support\Facades\Auth::id();
        if (!$uyeId) {
            return redirect()->route('uye.giris')->with('error', 'Giriş yapmanız gerekiyor.');
        }
 
        $talep = \Illuminate\Support\Facades\DB::table('destek')
            ->where('id', $id)
            ->where('uyeid', $uyeId)
            ->whereIn('ustid', [0, null])  // sadece ana talep
            ->first();
 
        if (!$talep) {
            return redirect()->route('destek.taleplerim')
                ->with('error', 'Talep bulunamadı veya size ait değil.');
        }
 
        // Zaten kapalı mı?
        if ((int) ($talep->durum ?? 0) === 2) {
            return redirect()->route('destek.detay', $id)
                ->with('info', 'Bu talep zaten kapalı durumda.');
        }
 
        \Illuminate\Support\Facades\DB::table('destek')
            ->where('id', $id)
            ->update([
                'durum'     => 2,  // 2 = İptal/Kapalı (0=Açık, 1=Cevaplandı, 2=Kapalı)
                'son_cevap' => now(),
            ]);
 
        return redirect()->route('destek.detay', $id)
            ->with('success', 'Destek talebi kapatıldı.');
    }

    /**
     * AJAX POLLING: Bu talebin verilen ID'den sonraki yeni mesajlarını döner.
     * Güvenlik: Sadece kendi talebinin mesajlarını çekebilir.
     */
    public function yeniMesajlar(Request $request, $id)
    {
        $uyeId = Auth::guard('uye')->id();
        if (!$uyeId) {
            return response()->json(['ok' => false, 'msg' => 'Oturum yok'], 401);
        }

        // Sadece kendi talebi
        $talep = \Illuminate\Support\Facades\DB::table('destek')
            ->where('id', $id)
            ->where('uyeid', $uyeId)
            ->first();
        if (!$talep) {
            return response()->json(['ok' => false, 'msg' => 'Talep bulunamadı'], 404);
        }

        $afterId = (int) $request->input('after_id', 0);

        $kullaniciAdi = trim((Auth::guard('uye')->user()->ad ?? '') . ' ' . (Auth::guard('uye')->user()->soyad ?? ''));
        $firmaAdi = config('app.name', 'Destek Ekibi');

        $cevaplar = \Illuminate\Support\Facades\DB::table('destek')
            ->where('ustid', $id)
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->get();

        $mesajlar = [];
        foreach ($cevaplar as $c) {
            // Çoklu fallback: gönderen tespiti
            $isAdmin = false;
            if (isset($c->gonderen_tip) && $c->gonderen_tip === 'admin') {
                $isAdmin = true;
            } elseif (!empty($c->admin_id) && (int)$c->admin_id > 0) {
                $isAdmin = true;
            } elseif (!empty($c->uyeid) && (int)$c->uyeid > 0) {
                $isAdmin = false;
            }

            $tarihFmt = '';
            if (!empty($c->tarih)) {
                try { $tarihFmt = \Carbon\Carbon::parse($c->tarih)->format('d.m.Y H:i'); }
                catch (\Throwable $e) { $tarihFmt = (string) $c->tarih; }
            }

            $mesajlar[] = [
                'id'      => (int) $c->id,
                'isAdmin' => $isAdmin,
                'ad'      => $isAdmin ? ($firmaAdi . ' Destek') : ($kullaniciAdi ?: 'Siz'),
                'mesaj'   => (string) ($c->mesaj ?? ''),
                'tarih'   => $tarihFmt,
                'dosya'   => $c->dosya ?? null,
            ];
        }

        return response()->json([
            'ok'       => true,
            'mesajlar' => $mesajlar,
            'durum'    => (int) ($talep->durum ?? 0),
            'kapali'   => ((int) ($talep->durum ?? 0)) === 2,
        ]);
    }

    /**
     * AJAX: Yeni mesaj gönder. (POST)
     * Form'un AJAX versiyonu - JSON döner.
     */
    public function cevaplaAjax(Request $request, $id)
    {
        $uyeId = Auth::guard('uye')->id();
        if (!$uyeId) {
            return response()->json(['ok' => false, 'msg' => 'Oturum yok'], 401);
        }

        $destek = Destek::where('uyeid', $uyeId)->where('id', $id)->first();
        if (!$destek) {
            return response()->json(['ok' => false, 'msg' => 'Talep bulunamadı'], 404);
        }
        if ((int) $destek->durum === 2) {
            return response()->json(['ok' => false, 'msg' => 'Bu talep kapatılmış'], 422);
        }

        $request->validate([
            'mesaj' => 'required|string',
            'dosya' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,rar|max:10240',
        ]);

        // Dosya yükleme
        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $file = $request->file('dosya');
            $uploadPath = public_path('tema/uploads/destek');
            if (!is_dir($uploadPath)) @mkdir($uploadPath, 0755, true);
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $dosyaYolu = 'tema/uploads/destek/' . $fileName;
        }

        $cevapData = [
            'uyeid'     => $uyeId,
            'baslik'    => 'RE: ' . $destek->baslik,
            'hizmet'    => $destek->hizmet,
            'mesaj'     => $request->mesaj,
            'oncelik'   => $destek->oncelik,
            'dosya'     => $dosyaYolu,
            'durum'     => 0,
            'ustid'     => $id,
            'tarih'     => now(),
            'son_cevap' => now(),
        ];

        // Schema-aware: gonderen_tip yaz
        $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('destek');
        if (in_array('gonderen_tip', $kolonlar, true)) {
            $cevapData['gonderen_tip'] = 'musteri';
        }

        $cevap = Destek::create($cevapData);

        $destek->update([
            'son_cevap' => now(),
            'durum'     => 0,
        ]);

        // Admine bildirim (mail + uygulama içi)
        try {
            $musteri = \Illuminate\Support\Facades\DB::table('uyeler')->where('id', $uyeId)->first();
            if ($musteri) {
                \App\Services\DestekMailHelper::cevapMusteridenAdmine($destek, $cevap, $musteri);
            }
            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri');
            admin_bildirim_gonder(
                '💬 Destek Yanıtı: ' . $destek->baslik,
                $musteriAd . ' destek talebine yanıt yazdı (#' . $destek->id . ')',
                'destek',
                'destek',
                (int) $destek->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Destek cevap (ajax) admin bildirimi: ' . $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'id' => (int) $cevap->id,
            'msg' => 'Mesaj gönderildi.',
        ]);
    }
}