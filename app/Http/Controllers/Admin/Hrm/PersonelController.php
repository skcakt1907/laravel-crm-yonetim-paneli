<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Support\HrmYetki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HRM — Personel yönetimi.
 * Personel = yoneticiler tablosu (rol 1=Patron, 2=Çalışan, 5=Muhasebe vb. — müşteri/bayi hariç).
 * Özlük bilgileri personel_ozluk, dosyalar personel_dosyalar tablosunda.
 */
class PersonelController extends Controller
{
    /** Personel sayılan roller (müşteri=4, bayi=3 hariç) */
    private const PERSONEL_ROLLERI = [1, 2, 5, 19, 20, 21];

    /**
     * Personel listesi (yoneticiler + özlük özeti).
     */
    public function index(Request $request)
    {
        $arama = trim((string) $request->get('q'));

        $query = DB::table('yoneticiler as y')
            ->leftJoin('roller as r', 'r.id', '=', 'y.rol')
            ->select('y.id', 'y.kullaniciadi', 'y.adi', 'y.email', 'y.rol', 'y.durum', 'r.ad as rol_adi', 'r.renk as rol_renk');

        // Özlük tablosu varsa işe başlama / departman ekle
        if (Schema::hasTable('personel_ozluk')) {
            $query->leftJoin('personel_ozluk as po', 'po.yonetici_id', '=', 'y.id')
                  ->addSelect('po.ise_baslama_tarihi', 'po.departman', 'po.pozisyon', 'po.yillik_izin_hakki');
        }

        if (Schema::hasColumn('yoneticiler', 'rol')) {
            $query->whereIn('y.rol', self::PERSONEL_ROLLERI);
        }

        if ($arama !== '') {
            $query->where(function ($w) use ($arama) {
                $w->where('y.adi', 'like', "%{$arama}%")
                  ->orWhere('y.kullaniciadi', 'like', "%{$arama}%")
                  ->orWhere('y.email', 'like', "%{$arama}%");
            });
        }

        $personeller = $query->orderBy('y.adi')->paginate(30)->withQueryString();

        return view('admin.hrm.personel.index', [
            'personeller' => $personeller,
            'arama' => $arama,
            'onayYetkisi' => HrmYetki::izinOnaylayabilir(),
        ]);
    }

    /**
     * Personel İK kartı (detay).
     */
    public function goster($id)
    {
        $personel = DB::table('yoneticiler as y')
            ->leftJoin('roller as r', 'r.id', '=', 'y.rol')
            ->where('y.id', $id)
            ->select('y.*', 'r.ad as rol_adi', 'r.renk as rol_renk')
            ->first();

        if (!$personel) {
            return redirect()->route('admin.hrm.personel.index')->with('error', 'Personel bulunamadı.');
        }

        $ozluk = Schema::hasTable('personel_ozluk')
            ? DB::table('personel_ozluk')->where('yonetici_id', $id)->first()
            : null;

        $dosyalar = Schema::hasTable('personel_dosyalar')
            ? DB::table('personel_dosyalar')->where('yonetici_id', $id)->orderByDesc('id')->get()
            : collect();

        // İzin geçmişi + bakiye
        $izinler = collect();
        $izinBakiye = $this->izinBakiyesi($id, $ozluk);
        if (Schema::hasTable('izin_talepleri')) {
            $izinler = DB::table('izin_talepleri')->where('yonetici_id', $id)->orderByDesc('id')->limit(10)->get();
        }

        return view('admin.hrm.personel.goster', compact('personel', 'ozluk', 'dosyalar', 'izinler', 'izinBakiye'));
    }

    /**
     * Bir personelin yıllık izin bakiyesi.
     */
    private function izinBakiyesi($yoneticiId, $ozluk): array
    {
        $hak = $ozluk->yillik_izin_hakki ?? 14;
        $kullanilan = 0;

        if (Schema::hasTable('izin_talepleri')) {
            $kullanilan = (int) DB::table('izin_talepleri')
                ->where('yonetici_id', $yoneticiId)
                ->where('izin_tipi', 'yillik')
                ->where('durum', 'onaylandi')
                ->sum('gun_sayisi');
        }

        return [
            'hak' => (int) $hak,
            'kullanilan' => $kullanilan,
            'kalan' => max(0, (int) $hak - $kullanilan),
        ];
    }

    /**
     * Özlük bilgilerini kaydet/güncelle (upsert).
     */
    public function ozlukKaydet(Request $request, $id)
    {
        $validated = $request->validate([
            'ise_baslama_tarihi' => 'nullable|date',
            'departman' => 'nullable|string|max:100',
            'pozisyon' => 'nullable|string|max:100',
            'tc' => 'nullable|string|max:11',
            'dogum_tarihi' => 'nullable|date',
            'telefon' => 'nullable|string|max:30',
            'adres' => 'nullable|string',
            'acil_durum_kisi' => 'nullable|string|max:150',
            'acil_durum_tel' => 'nullable|string|max:30',
            'yillik_izin_hakki' => 'nullable|integer|min:0|max:365',
            'notlar' => 'nullable|string',
        ]);

        if (!Schema::hasTable('personel_ozluk')) {
            return back()->with('error', 'Özlük tablosu bulunamadı. Lütfen SQL kurulumunu çalıştırın.');
        }

        $data = $validated;
        $data['yillik_izin_hakki'] = $validated['yillik_izin_hakki'] ?? 14;
        $data['updated_at'] = now();

        $mevcut = DB::table('personel_ozluk')->where('yonetici_id', $id)->first();
        if ($mevcut) {
            DB::table('personel_ozluk')->where('yonetici_id', $id)->update($data);
        } else {
            $data['yonetici_id'] = $id;
            $data['created_at'] = now();
            DB::table('personel_ozluk')->insert($data);
        }

        return redirect()->route('admin.hrm.personel.goster', $id)->with('success', 'Özlük bilgileri kaydedildi.');
    }

    /**
     * Özlük dosyası yükle (public/uploads/personel/).
     */
    public function dosyaYukle(Request $request, $id)
    {
        $request->validate([
            'dosya' => 'required|file|max:10240', // 10MB
            'tip' => 'nullable|string|max:50',
        ], [
            'dosya.required' => 'Dosya seçiniz.',
            'dosya.max' => 'Dosya en fazla 10MB olabilir.',
        ]);

        if (!Schema::hasTable('personel_dosyalar')) {
            return back()->with('error', 'Dosya tablosu bulunamadı.');
        }

        $file = $request->file('dosya');
        $orijinalAd = $file->getClientOriginalName();
        $uzanti = $file->getClientOriginalExtension();
        $guvenliAd = 'personel_' . $id . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $uzanti;

        // public/uploads/personel/ altına taşı
        $hedefDizin = public_path('uploads/personel');
        if (!is_dir($hedefDizin)) {
            @mkdir($hedefDizin, 0755, true);
        }

        try {
            $file->move($hedefDizin, $guvenliAd);
        } catch (\Throwable $e) {
            return back()->with('error', 'Dosya yüklenemedi: ' . $e->getMessage());
        }

        DB::table('personel_dosyalar')->insert([
            'yonetici_id' => $id,
            'dosya_adi' => $orijinalAd,
            'dosya_yolu' => 'uploads/personel/' . $guvenliAd,
            'tip' => $request->get('tip', 'diger'),
            'boyut' => $file ? null : null, // move sonrası boyut alınamaz; orijinalden alalım
            'yukleyen_id' => session('admin_id'),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Dosya yüklendi.');
    }

    /**
     * Özlük dosyası sil (fiziksel + kayıt).
     */
    public function dosyaSil($id, $dosyaId)
    {
        $dosya = DB::table('personel_dosyalar')->where('id', $dosyaId)->where('yonetici_id', $id)->first();
        if (!$dosya) {
            return back()->with('error', 'Dosya bulunamadı.');
        }

        $tamYol = public_path($dosya->dosya_yolu);
        if (is_file($tamYol)) {
            @unlink($tamYol);
        }
        DB::table('personel_dosyalar')->where('id', $dosyaId)->delete();

        return back()->with('success', 'Dosya silindi.');
    }
}
