<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\SosyalMedyaKayit;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OzelKayitController extends Controller
{
    /**
     * Toplu dışa aktarma yalnızca TAM YETKİLİ yöneticilere açıktır.
     * (Tek tıkla bütün müşterilerin giriş bilgileri indirilebildiği için.)
     */
    private function disaAktarabilirMi(): bool
    {
        try {
            $rolId = session('admin_rol');
            if (!$rolId) return false;

            if (\Illuminate\Support\Facades\Schema::hasTable('roller')) {
                return (bool) DB::table('roller')->where('id', $rolId)->value('tam_yetki');
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /** Kasaya erişimi kayda geçirir (kim, ne zaman, ne yaptı). */
    private function erisimKaydet(string $aksiyon, string $aciklama): void
    {
        try {
            \Illuminate\Support\Facades\Log::channel('single')->warning('[KASA ERISIM] ' . $aksiyon, [
                'yonetici_id' => session('admin_id'),
                'yonetici'    => session('admin_adi'),
                'rol'         => session('admin_rol'),
                'ip'          => request()->ip(),
                'aciklama'    => $aciklama,
                'zaman'       => now()->format('d.m.Y H:i:s'),
            ]);
        } catch (\Throwable $e) {}
    }

    /** Liste (arama + hesap sayısı + CRM müşteri adı) */
    public function index(Request $request)
    {
        $q = SosyalMedyaKayit::query()
            ->leftJoin('crm_customers as c', 'c.id', '=', 'sosyal_medya_kayitlari.crm_musteri_id')
            ->select(
                'sosyal_medya_kayitlari.*',
                'c.adi as crm_ad',
                DB::raw('(SELECT COUNT(*) FROM sosyal_medya_hesaplari h WHERE h.kayit_id = sosyal_medya_kayitlari.id) as hesap_sayisi'),
                // Bölüm kırılımı (4. öncelik: kasa Sosyal Medya / Web Sitesi diye ayrıldı)
                DB::raw("(SELECT COUNT(*) FROM sosyal_medya_hesaplari h WHERE h.kayit_id = sosyal_medya_kayitlari.id AND h.bolum = 'sosyal_medya') as sosyal_sayisi"),
                DB::raw("(SELECT COUNT(*) FROM sosyal_medya_hesaplari h WHERE h.kayit_id = sosyal_medya_kayitlari.id AND h.bolum = 'web_sitesi') as web_sayisi")
            );

        // Bölüme göre filtre — sadece o bölümde hesabı olan kayıtlar
        if (in_array($request->query('bolum'), ['sosyal_medya', 'web_sitesi'], true)) {
            $bolum = $request->query('bolum');
            $q->whereExists(function ($alt) use ($bolum) {
                $alt->selectRaw('1')->from('sosyal_medya_hesaplari as hh')
                    ->whereColumn('hh.kayit_id', 'sosyal_medya_kayitlari.id')
                    ->where('hh.bolum', $bolum);
            });
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('sosyal_medya_kayitlari.baslik', 'like', "%{$s}%")
                  ->orWhere('sosyal_medya_kayitlari.firma', 'like', "%{$s}%")
                  ->orWhere('sosyal_medya_kayitlari.email', 'like', "%{$s}%")
                  ->orWhere('sosyal_medya_kayitlari.telefon', 'like', "%{$s}%")
                  ->orWhere('c.adi', 'like', "%{$s}%");
            });
        }

        $kayitlar = $q->orderByDesc('sosyal_medya_kayitlari.id')->paginate(20)->withQueryString();
        $toplam   = SosyalMedyaKayit::count();

        return view('admin.crm.ozel-kayitlar.index', compact('kayitlar', 'toplam'));
    }

    /** Yeni kayıt formu */
    public function create(Request $request)
    {
        $kayit       = null;
        // Durum da alınır: PASİF müşteriler de seçilebilir, listede belirtilir.
        $musteriler  = Customer::orderBy('adi')->get(['id', 'adi', 'durum']);
        $onSeciliMusteri = $request->integer('musteri_id') ?: null;

        return view('admin.crm.ozel-kayitlar.form', compact('kayit', 'musteriler', 'onSeciliMusteri'));
    }

    /** Kaydet */
    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['olusturan_id'] = session('admin_id');

        $kayit = SosyalMedyaKayit::create($data);

        return redirect()
            ->route('admin.crm.ozel-kayitlar.show', $kayit->id)
            ->with('success', 'Kayıt oluşturuldu. Şimdi hesap / şifre ekleyebilirsiniz.');
    }

    /** Detay + hesaplar */
    public function show(int $id)
    {
        $kayit = SosyalMedyaKayit::with(['hesaplar', 'musteri'])->findOrFail($id);

        return view('admin.crm.ozel-kayitlar.show', compact('kayit'));
    }

    /** Düzenleme formu */
    public function edit(int $id)
    {
        $kayit       = SosyalMedyaKayit::findOrFail($id);
        // Durum da alınır: PASİF müşteriler de seçilebilir, listede belirtilir.
        $musteriler  = Customer::orderBy('adi')->get(['id', 'adi', 'durum']);
        $onSeciliMusteri = null;

        return view('admin.crm.ozel-kayitlar.form', compact('kayit', 'musteriler', 'onSeciliMusteri'));
    }

    /** Güncelle */
    public function update(Request $request, int $id)
    {
        $kayit = SosyalMedyaKayit::findOrFail($id);
        $kayit->update($this->validateData($request));

        return redirect()
            ->route('admin.crm.ozel-kayitlar.show', $kayit->id)
            ->with('success', 'Kayıt güncellendi.');
    }

    /**
     * Aktif/Pasif değiştir.
     * Pasif kayıt silinmez — müşteriyle çalışılmasa da giriş bilgileri arşivde kalır.
     */
    public function durumDegistir(int $id)
    {
        $kayit = SosyalMedyaKayit::findOrFail($id);
        $kayit->durum = !$kayit->durum;
        $kayit->save();

        return back()->with('success', $kayit->baslik . ' → '
            . ($kayit->durum ? 'Aktif' : 'Pasif') . ' yapıldı.');
    }

    /** Sil (hesaplar da silinir) */
    public function destroy(int $id)
    {
        $kayit = SosyalMedyaKayit::findOrFail($id);
        SosyalMedyaHesap::where('kayit_id', $kayit->id)->delete();
        $kayit->delete();

        return redirect()
            ->route('admin.crm.ozel-kayitlar.index')
            ->with('success', 'Kayıt ve tüm hesapları silindi.');
    }

    /** Hesap ekle */
    public function hesapEkle(Request $request, int $id)
    {
        $kayit = SosyalMedyaKayit::findOrFail($id);

        $data = $request->validate([
            'bolum'         => 'nullable|in:sosyal_medya,web_sitesi',
            'platform'      => 'required|string|max:100',
            'kullanici_adi' => 'nullable|string|max:255',
            'sifre'         => 'nullable|string|max:255',
            'email'         => 'nullable|string|max:255',
            'link'          => 'nullable|string|max:255',
            'aciklama'      => 'nullable|string',
        ]);

        $data['bolum']    = $data['bolum'] ?? SosyalMedyaHesap::BOLUM_SOSYAL;
        $data['kayit_id'] = $kayit->id;
        $data['sira']     = (int) (SosyalMedyaHesap::where('kayit_id', $kayit->id)->max('sira') ?? 0) + 1;

        SosyalMedyaHesap::create($data);

        return back()->with('success', 'Hesap eklendi.');
    }

    /** Hesap güncelle */
    public function hesapGuncelle(Request $request, int $id, int $hesapId)
    {
        $hesap = SosyalMedyaHesap::where('kayit_id', $id)->where('id', $hesapId)->firstOrFail();

        $data = $request->validate([
            'bolum'         => 'nullable|in:sosyal_medya,web_sitesi',
            'platform'      => 'required|string|max:100',
            'kullanici_adi' => 'nullable|string|max:255',
            'sifre'         => 'nullable|string|max:255',
            'email'         => 'nullable|string|max:255',
            'link'          => 'nullable|string|max:255',
            'aciklama'      => 'nullable|string',
        ]);

        // Bölüm gönderilmediyse hesabın mevcut bölümü korunur
        if (empty($data['bolum'])) unset($data['bolum']);

        $hesap->update($data);

        return back()->with('success', 'Hesap güncellendi.');
    }

    /** Hesap sil */
    public function hesapSil(int $id, int $hesapId)
    {
        $hesap = SosyalMedyaHesap::where('kayit_id', $id)->where('id', $hesapId)->firstOrFail();
        $hesap->delete();

        return back()->with('success', 'Hesap silindi.');
    }

    /**
     * Excel'e aktar (tüm kayıtlar + hesapları, her hesap ayrı satır).
     *
     * DİKKAT — EN BÜYÜK SIZINTI YOLU BURASI: çıktı bütün müşterilerin giriş
     * bilgilerini içerir. Bu yüzden yalnızca TAM YETKİLİ yöneticiler indirebilir
     * ve her indirme kim/ne zaman/hangi IP olarak kayda geçer.
     */
    public function export()
    {
        if (!$this->disaAktarabilirMi()) {
            $this->erisimKaydet('disa_aktarma_REDDEDILDI', 'Yetkisiz dışa aktarma denemesi');
            return redirect()->route('admin.crm.ozel-kayitlar.index')
                ->with('error', 'Bu listeyi dışa aktarma yetkiniz yok. Tam yetkili bir yönetici ile iletişime geçin.');
        }

        $this->erisimKaydet('disa_aktarma', 'Tüm kayıtlar Excel olarak indirildi');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sosyal Medya');

        $basliklar = ['Başlık', 'Firma', 'CRM Müşteri', 'E-posta', 'Telefon', 'Platform', 'Kullanıcı Adı', 'Şifre', 'Link', 'Açıklama'];
        $sheet->fromArray($basliklar, null, 'A1');

        $satir = 2;
        $kayitlar = SosyalMedyaKayit::with(['hesaplar', 'musteri'])->orderBy('baslik')->get();
        foreach ($kayitlar as $k) {
            $crmAd = $k->musteri->adi ?? '';
            if ($k->hesaplar->count() === 0) {
                $sheet->fromArray([$k->baslik, $k->firma, $crmAd, $k->email, $k->telefon, '', '', '', '', ''], null, 'A' . $satir);
                $satir++;
                continue;
            }
            foreach ($k->hesaplar as $h) {
                $sheet->fromArray([
                    $k->baslik, $k->firma, $crmAd, $k->email, $k->telefon,
                    $h->platform, $h->kullanici_adi, $h->sifre, $h->link, $h->aciklama,
                ], null, 'A' . $satir);
                $satir++;
            }
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->indirXlsx($spreadsheet, 'kayit-listesi');
    }

    /** Boş şablon indir */
    public function exportTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sablon');

        $basliklar = ['Başlık', 'Firma', 'E-posta', 'Telefon', 'Platform', 'Kullanıcı Adı', 'Şifre', 'Link', 'Açıklama'];
        $sheet->fromArray($basliklar, null, 'A1');
        // Örnek satır
        $sheet->fromArray(
            ['Ahmet Yılmaz / Kafe Libre', 'Kafe Libre', 'ornek@gmail.com', '05551234567', 'Instagram', '@kafelibre', 'sifre123', 'https://instagram.com/kafelibre', 'Ana hesap'],
            null, 'A2'
        );

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->indirXlsx($spreadsheet, 'kayit-sablonu');
    }

    /** Excel'den içe aktar (aynı başlık tek kayıtta toplanır, hesaplar eklenir) */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($request->file('file')->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return back()->with('error', 'Dosya okunamadı: ' . $e->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('error', 'Dosyada veri bulunamadı.');
        }

        // Başlık satırını atla (ilk satır)
        array_shift($rows);

        $eklenenKayit = 0;
        $eklenenHesap = 0;
        $kayitCache = []; // baslik(normalize) => kayit_id

        foreach ($rows as $r) {
            // Beklenen sütun sırası: Başlık, Firma, E-posta, Telefon, Platform, Kullanıcı Adı, Şifre, Link, Açıklama
            $baslik   = trim((string) ($r[0] ?? ''));
            $firma    = trim((string) ($r[1] ?? ''));
            $email    = trim((string) ($r[2] ?? ''));
            $telefon  = trim((string) ($r[3] ?? ''));
            $platform = trim((string) ($r[4] ?? ''));
            $kadi     = trim((string) ($r[5] ?? ''));
            $sifre    = trim((string) ($r[6] ?? ''));
            $link     = trim((string) ($r[7] ?? ''));
            $aciklama = trim((string) ($r[8] ?? ''));

            if ($baslik === '') { continue; }

            $anahtar = mb_strtolower($baslik, 'UTF-8');

            // Kayıt bul/oluştur
            if (isset($kayitCache[$anahtar])) {
                $kayitId = $kayitCache[$anahtar];
            } else {
                $kayit = SosyalMedyaKayit::where('baslik', $baslik)->first();
                if (!$kayit) {
                    $kayit = SosyalMedyaKayit::create([
                        'baslik'       => $baslik,
                        'firma'        => $firma ?: null,
                        'email'        => $email ?: null,
                        'telefon'      => $telefon ?: null,
                        'olusturan_id' => session('admin_id'),
                    ]);
                    $eklenenKayit++;
                }
                $kayitId = $kayit->id;
                $kayitCache[$anahtar] = $kayitId;
            }

            // Hesap satırı varsa ekle
            if ($platform !== '') {
                SosyalMedyaHesap::create([
                    'kayit_id'      => $kayitId,
                    'platform'      => $platform,
                    'kullanici_adi' => $kadi ?: null,
                    'sifre'         => $sifre ?: null,
                    'email'         => $email ?: null,
                    'link'          => $link ?: null,
                    'aciklama'      => $aciklama ?: null,
                    'sira'          => (int) (SosyalMedyaHesap::where('kayit_id', $kayitId)->max('sira') ?? 0) + 1,
                ]);
                $eklenenHesap++;
            }
        }

        return back()->with('success', "İçe aktarma tamam: {$eklenenKayit} yeni kayıt, {$eklenenHesap} hesap eklendi.");
    }

    /** PhpSpreadsheet nesnesini xlsx olarak indirten yardımcı */
    private function indirXlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $ad)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'sm') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, $ad . '-' . date('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Form doğrulama (kayıt ekle/güncelle) */
    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'crm_musteri_id' => 'nullable|integer|exists:crm_customers,id',
            'baslik'         => 'required|string|max:255',
            'firma'          => 'nullable|string|max:255',
            'email'          => 'nullable|string|max:255',
            'telefon'        => 'nullable|string|max:50',
            'genel_not'      => 'nullable|string',
        ]);

        // Boş string -> null normalize
        foreach (['crm_musteri_id', 'firma', 'email', 'telefon', 'genel_not', 'durum'] as $k) {
            if (array_key_exists($k, $validated) && $validated[$k] === '') {
                $validated[$k] = null;
            }
        }

        return $validated;
    }
}