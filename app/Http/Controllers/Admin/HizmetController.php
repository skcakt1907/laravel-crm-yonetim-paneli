<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HizmetController extends Controller
{
    /**
     * Hizmet listesi — artık müşteriye özel hizmet kayıtlarını gösteriyor.
     * Eski "katalog" amaçlı `hizmetler` tablosu yerine `musteri_hizmetler` tablosundan çekiyor.
     */
    public function index(Request $request)
    {
        $arama = trim((string) $request->get('q', ''));

        $query = DB::table('musteri_hizmetler')
            ->leftJoin('uyeler', 'musteri_hizmetler.uyeid', '=', 'uyeler.id')
            ->select(
                'musteri_hizmetler.*',
                'uyeler.ad as musteri_ad',
                'uyeler.soyad as musteri_soyad',
                'uyeler.email as musteri_email'
            );

        if ($arama !== '') {
            $query->where(function ($q) use ($arama) {
                $q->where('musteri_hizmetler.baslik', 'like', "%{$arama}%")
                  ->orWhere('musteri_hizmetler.icerik', 'like', "%{$arama}%")
                  ->orWhere('uyeler.ad', 'like', "%{$arama}%")
                  ->orWhere('uyeler.soyad', 'like', "%{$arama}%")
                  ->orWhere('uyeler.email', 'like', "%{$arama}%");
            });
        }

        $hizmetler = $query
            ->orderByDesc('musteri_hizmetler.id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.hizmetler.index', compact('hizmetler', 'arama'));
    }

    /**
     * Yeni hizmet formu — müşteri listesini autocomplete için gönderiyor.
     */
    public function ekle()
    {
        $uyeler = DB::table('uyeler')
            ->where('durum', 1)
            ->select('id', 'ad', 'soyad', 'email')
            ->orderBy('ad')
            ->limit(2000)
            ->get();

        return view('admin.hizmetler.ekle', compact('uyeler'));
    }

    /**
     * Hizmeti müşteriye ata - musteri_hizmetler tablosuna kayıt at.
     */
    public function eklePost(Request $request)
    {
        $validated = $request->validate([
            'uyeid'         => 'required|integer|exists:uyeler,id',
            'baslik'        => 'required|string|max:255',
            'icerik'        => 'nullable|string',
            'tutar'         => 'nullable|string|max:50',
            'durum'         => 'nullable|string|max:50',
            'dosya'         => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ], [
            'uyeid.required' => 'Lütfen bir müşteri seçin.',
            'uyeid.exists'   => 'Seçilen müşteri sistemde bulunamadı.',
            'baslik.required' => 'Hizmet adı boş olamaz.',
        ]);

        // Dosya yükleme (varsa)
        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $file = $request->file('dosya');
            $uploadPath = public_path('tema/uploads/hizmet');
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
            $fileName  = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $dosyaYolu = 'tema/uploads/hizmet/' . $fileName;
        }

        $insertData = [
            'uyeid'  => (int) $validated['uyeid'],
            'baslik' => $validated['baslik'],
            'icerik' => $validated['icerik'] ?? '',
            'tutar'  => $validated['tutar'] ?? '0',
            'durum'  => $validated['durum'] ?? 'aktif',
            'tarih'  => date('Y-m-d H:i:s'),
            'dil'    => 'tr',
        ];

        if ($dosyaYolu) {
            $insertData['dosya'] = $dosyaYolu;
        }

        $id = DB::table('musteri_hizmetler')->insertGetId($insertData);

        // Müşteriye bildirim maili — ortak lime+logo tasarım (sessiz başarısızlık)
        try {
            $mesaj = "<p style='margin:0 0 14px'>Hesabınıza yeni bir hizmet atandı:</p>"
                   . "<div style='background-color:#f7f8f3;border-left:4px solid #b8b62e;border-radius:8px;padding:14px 18px;margin:0 0 4px'>"
                   . "<strong style='color:#6f7320'>" . e($insertData['baslik']) . "</strong>"
                   . (!empty($insertData['icerik']) ? "<div style='margin-top:8px;color:#475569;font-size:14px;line-height:1.6'>" . nl2br(e(\Illuminate\Support\Str::limit($insertData['icerik'], 300))) . "</div>" : "")
                   . "</div>";
            \App\Services\CustomerNotifier::musteriyeMail(
                $insertData['uyeid'],
                '📋 Size yeni bir hizmet atandı: ' . $insertData['baslik'],
                $mesaj,
                url('/hizmetlerim'),
                'Hizmetlerim Sayfasını Aç',
                'hizmet_atandi'
            );
        } catch (\Throwable $e) {
            \Log::warning('Hizmet atama mail hatasi', ['err' => $e->getMessage()]);
        }

        return redirect()->route('admin.hizmetler.index')
            ->with('success', 'Hizmet başarıyla müşteriye atandı.');
    }

    /**
     * Düzenleme formu - musteri_hizmetler kaydını yüklüyor.
     */
    public function duzenle($id)
    {
        $hizmet = DB::table('musteri_hizmetler')->where('id', $id)->first();
        if (!$hizmet) {
            return redirect()->route('admin.hizmetler.index')->with('error', 'Hizmet bulunamadı.');
        }

        $uyeler = DB::table('uyeler')
            ->where('durum', 1)
            ->select('id', 'ad', 'soyad', 'email')
            ->orderBy('ad')
            ->limit(2000)
            ->get();

        return view('admin.hizmetler.duzenle', compact('hizmet', 'uyeler'));
    }

    public function duzenlePost(Request $request, $id)
    {
        $validated = $request->validate([
            'uyeid'  => 'required|integer|exists:uyeler,id',
            'baslik' => 'required|string|max:255',
            'icerik' => 'nullable|string',
            'tutar'  => 'nullable|string|max:50',
            'durum'  => 'nullable|string|max:50',
            'dosya'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $mevcut = DB::table('musteri_hizmetler')->where('id', $id)->first();
        if (!$mevcut) {
            return redirect()->route('admin.hizmetler.index')->with('error', 'Hizmet bulunamadı.');
        }

        $updateData = [
            'uyeid'  => (int) $validated['uyeid'],
            'baslik' => $validated['baslik'],
            'icerik' => $validated['icerik'] ?? '',
            'tutar'  => $validated['tutar'] ?? '0',
            'durum'  => $validated['durum'] ?? 'aktif',
        ];

        // Yeni dosya yüklendiyse - eskisini sil, yenisini kaydet
        if ($request->hasFile('dosya')) {
            if (!empty($mevcut->dosya)) {
                $eskiPath = public_path($mevcut->dosya);
                if (file_exists($eskiPath)) {
                    @unlink($eskiPath);
                }
            }
            $file = $request->file('dosya');
            $uploadPath = public_path('tema/uploads/hizmet');
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $updateData['dosya'] = 'tema/uploads/hizmet/' . $fileName;
        }

        DB::table('musteri_hizmetler')->where('id', $id)->update($updateData);

        return redirect()->route('admin.hizmetler.index')->with('success', 'Hizmet başarıyla güncellendi.');
    }

    public function sil($id)
    {
        $hizmet = DB::table('musteri_hizmetler')->where('id', $id)->first();
        if (!$hizmet) {
            return redirect()->route('admin.hizmetler.index')->with('error', 'Hizmet bulunamadı.');
        }

        // Dosya varsa onu da sil
        if (!empty($hizmet->dosya)) {
            $abs = public_path($hizmet->dosya);
            if (file_exists($abs)) {
                @unlink($abs);
            }
        }

        DB::table('musteri_hizmetler')->where('id', $id)->delete();

        return redirect()->route('admin.hizmetler.index')->with('success', 'Hizmet başarıyla silindi.');
    }
}