<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Models\CRM\SosyalMedyaPlanKalem;
use App\Models\Yonetici;
use App\Services\SosyalMedyaTakip;
use App\Support\SosyalMedyaYetki;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * SOSYAL MEDYA TAKİP — uzman ekranı
 *
 * Günlük paylaşım işaretlemesi. Hesaplama mantığı burada DEĞİL,
 * SosyalMedyaTakip servisinde — aynı mantığı gün sonu raporu ve
 * aylık rapor da kullanıyor.
 *
 * YETKİ: Panelin kendi rol sistemi (bkz. SosyalMedyaYetki). Erişim
 * "Roller" ekranından verilir; burada sabit rol listesi TUTULMAZ.
 */
class SosyalMedyaTakipController extends Controller
{
    public function __construct(private SosyalMedyaTakip $takip)
    {
    }

    /** Günlük liste — varsayılan bugün, en fazla 3 gün geriye */
    public function index(Request $request)
    {
        $this->yetkiKontrol();

        $tarih = $request->filled('tarih')
            ? Carbon::parse($request->string('tarih'))->startOfDay()
            : Carbon::today();

        // Gelecek istenirse bugüne çek — ileriye işaretleme yok
        if ($tarih->gt(Carbon::today())) {
            $tarih = Carbon::today();
        }

        $ben = $this->girisYapan();

        // Patron hepsini görür; çalışan varsayılan olarak kendi hesaplarını.
        // "Hepsi" seçilirse çalışan da tümünü görebilir (küçük ekip, ve
        // birbirinin yerine işaretleme ihtiyacı oluyor).
        $sadeceBenim = $request->boolean('benim', false);
        $sorumluId   = $sadeceBenim ? ($ben->id ?? 0) : null;

        $hesaplar = $this->takip->gununHesaplari($tarih, $sorumluId);

        $satirlar = $hesaplar->map(function ($hesap) use ($tarih) {
            $durum = $this->takip->gunDurumu($hesap->id, $tarih);

            return [
                'hesap' => $hesap,
                'marka' => $hesap->kayit->baslik ?? $hesap->kayit->firma ?? '—',
            ] + $durum;
        })->sortBy('marka')->values();

        return view('admin.sosyal-medya.index', [
            'tarih'            => $tarih,
            'satirlar'         => $satirlar,
            'sadeceBenim'      => $sadeceBenim,
            'isaretlenebilir'  => $this->takip->isaretlenebilirMi($tarih),
            'geriyeGun'        => SosyalMedyaTakip::GERIYE_GUN,
            'bugueErtelenenler'=> $this->takip->bugueErtelenenler($tarih),
        ]);
    }

    /** Paylaşım ekle — "Yapıldı" */
    public function paylasimEkle(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'hesap_id' => 'required|integer|exists:sosyal_medya_hesaplari,id',
            'tarih'    => 'required|date',
            'kalem_id' => 'nullable|integer|exists:sosyal_medya_plan_kalemleri,id',
            'link'     => 'nullable|url|max:500',
            'not'      => 'nullable|string|max:1000',
        ], [
            'link.url' => 'Paylaşım linki geçerli bir adres olmalı (https://... ile başlamalı).',
        ]);

        $tarih = Carbon::parse($veri['tarih'])->startOfDay();

        if (! $this->takip->isaretlenebilirMi($tarih)) {
            return back()->with('error',
                'Bu tarih işaretlenemez. En fazla ' . SosyalMedyaTakip::GERIYE_GUN . ' gün geriye işaretleme yapılabilir.');
        }

        // Gün notu (erteleme/iptal) varken paylaşım eklenirse çelişki olur;
        // önce notu kaldırmasını isteriz.
        $notVar = SosyalMedyaGunNotu::where('hesap_id', $veri['hesap_id'])
            ->whereDate('tarih', $tarih->toDateString())->exists();

        if ($notVar) {
            return back()->with('error',
                'Bu gün "ertelendi/iptal" olarak işaretli. Paylaşım eklemek için önce o kaydı kaldırın.');
        }

        // Checklist kaleminin iki kez işaretlenmesini engelle: aynı kaleme
        // ikinci paylaşım eklenirse hedef 3 iken 4 "yapıldı" görünürdü.
        if (! empty($veri['kalem_id'])
            && SosyalMedyaPaylasim::where('kalem_id', $veri['kalem_id'])->exists()) {
            return back()->with('error', 'Bu paylaşım zaten işaretlenmiş.');
        }

        SosyalMedyaPaylasim::create([
            'hesap_id'       => $veri['hesap_id'],
            'tarih'          => $tarih->toDateString(),
            'kalem_id'       => $veri['kalem_id'] ?? null,
            'link'           => $veri['link'] ?? null,
            'not'            => $veri['not'] ?? null,
            'isaretleyen_id' => session('admin_id'),
            'isaretlendi_at' => now(),   // GERÇEK an — geriye işaretlemede tarihten farklı olur
        ]);

        return back()->with('success', 'Paylaşım işaretlendi.');
    }

    /** Yanlış eklenen paylaşımı sil */
    public function paylasimSil(int $id)
    {
        $this->yetkiKontrol();

        $p = SosyalMedyaPaylasim::findOrFail($id);

        if (! $this->takip->isaretlenebilirMi(Carbon::parse($p->tarih))) {
            return back()->with('error', 'Bu tarihteki kayıt artık değiştirilemez.');
        }

        $p->delete();

        return back()->with('success', 'Paylaşım kaydı silindi.');
    }

    /**
     * Platformu O GÜNÜN listesinden çıkarır.
     *
     * Haftalık şablona DOKUNMAZ — yalnızca bu tarihe hedefi 0 olan bir
     * istisna yazar, böylece gelecek haftalar etkilenmez. O güne girilmiş
     * isimli paylaşımlar da (kalemler) silinir, çünkü platform o gün
     * listede olmayacaksa planı da anlamsız kalır.
     *
     * YAPILMIŞ İŞ VARSA ENGELLENİR: tamamlanmış bir paylaşımı ekrandan
     * gizlemek, yapılan işi kayıtlarda görünmez kılardı. Önce işaretin
     * kaldırılması istenir.
     */
    public function gundenKaldir(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'hesap_id' => 'required|integer|exists:sosyal_medya_hesaplari,id',
            'tarih'    => 'required|date',
        ]);

        $tarih = Carbon::parse($veri['tarih'])->startOfDay();

        if (! $this->takip->isaretlenebilirMi($tarih)) {
            return back()->with('error', 'Bu tarih artık değiştirilemez.');
        }

        $isaretliSayi = SosyalMedyaPaylasim::where('hesap_id', $veri['hesap_id'])
            ->whereDate('tarih', $tarih->toDateString())->count();

        if ($isaretliSayi > 0) {
            return back()->with('error',
                'Bu gün için işaretlenmiş paylaşım var. Önce işaretleri kaldırın, sonra listeden çıkarın.');
        }

        // O güne ait plan kalemleri ve erteleme/iptal notu anlamını yitirir
        SosyalMedyaPlanKalem::where('hesap_id', $veri['hesap_id'])
            ->whereDate('tarih', $tarih->toDateString())->delete();

        SosyalMedyaGunNotu::where('hesap_id', $veri['hesap_id'])
            ->whereDate('tarih', $tarih->toDateString())->delete();

        // Hedefi 0 yap -> gununHesaplari() bu platformu o gün listelemez
        SosyalMedyaPlanIstisna::updateOrCreate(
            ['hesap_id' => $veri['hesap_id'], 'tarih' => $tarih->toDateString()],
            [
                'hedef_adet'   => 0,
                'sebep'        => 'Listeden çıkarıldı',
                'olusturan_id' => session('admin_id'),
            ]
        );

        return back()->with('success', 'Platform bu günün listesinden çıkarıldı. Haftalık plan değişmedi.');
    }

    /** Erteleme veya iptal */
    public function gunNotu(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'hesap_id'        => 'required|integer|exists:sosyal_medya_hesaplari,id',
            'tarih'           => 'required|date',
            'tip'             => 'required|in:ertelendi,iptal',
            'ertelendi_tarih' => 'nullable|date|after:tarih',
            'sebep'           => 'required|string|max:500',
        ], [
            'sebep.required'        => 'Sebep yazmanız gerekiyor.',
            'ertelendi_tarih.after' => 'Erteleme tarihi, paylaşım tarihinden sonra olmalı.',
        ]);

        if ($veri['tip'] === SosyalMedyaGunNotu::TIP_ERTELENDI && empty($veri['ertelendi_tarih'])) {
            return back()->with('error', 'Erteleme için yeni tarih girmelisiniz.');
        }

        $tarih = Carbon::parse($veri['tarih'])->startOfDay();

        if (! $this->takip->isaretlenebilirMi($tarih)) {
            return back()->with('error', 'Bu tarih artık değiştirilemez.');
        }

        // (hesap_id, tarih) benzersiz — updateOrCreate ile çakışma olmaz
        SosyalMedyaGunNotu::updateOrCreate(
            ['hesap_id' => $veri['hesap_id'], 'tarih' => $tarih->toDateString()],
            [
                'tip'             => $veri['tip'],
                'ertelendi_tarih' => $veri['tip'] === SosyalMedyaGunNotu::TIP_ERTELENDI
                                        ? $veri['ertelendi_tarih'] : null,
                'sebep'           => $veri['sebep'],
                'isaretleyen_id'  => session('admin_id'),
                'isaretlendi_at'  => now(),
            ]
        );

        return back()->with('success',
            $veri['tip'] === SosyalMedyaGunNotu::TIP_ERTELENDI ? 'Ertelendi olarak işaretlendi.' : 'İptal olarak işaretlendi.');
    }

    /** Erteleme/iptal kaydını geri al */
    public function gunNotuSil(int $id)
    {
        $this->yetkiKontrol();

        $n = SosyalMedyaGunNotu::findOrFail($id);

        if (! $this->takip->isaretlenebilirMi(Carbon::parse($n->tarih))) {
            return back()->with('error', 'Bu tarihteki kayıt artık değiştirilemez.');
        }

        $n->delete();

        return back()->with('success', 'Kayıt kaldırıldı.');
    }

    // ─────────────────────────────────────────────────────────────

    private function girisYapan(): ?Yonetici
    {
        return session('admin_id') ? Yonetici::find(session('admin_id')) : null;
    }

    /** Yetki panelin rol sisteminden okunur — sabit rol listesi yok */
    private function yetkiKontrol(): void
    {
        SosyalMedyaYetki::zorunlu();
    }
}
