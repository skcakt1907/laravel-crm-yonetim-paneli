<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Sozlesme;
use App\Models\CRM\SozlesmeKategori;
use App\Models\CRM\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SozlesmeController extends Controller
{
    /** Sözleşme listesi (kategori filtresi + arama) */
    public function index(Request $request)
    {
        $kategoriler = SozlesmeKategori::orderBy('sira')->orderBy('ad')->get();

        $q = Sozlesme::query()
            ->leftJoin('crm_sozlesme_kategorileri as k', 'k.id', '=', 'crm_sozlesmeler.kategori_id')
            ->leftJoin('crm_customers as c', 'c.id', '=', 'crm_sozlesmeler.musteri_id')
            ->leftJoin('crm_sozlesmeler as ana', 'ana.id', '=', 'crm_sozlesmeler.ana_sozlesme_id')
            ->select(
                'crm_sozlesmeler.*',
                'k.ad as kategori_ad', 'k.renk as kategori_renk',
                'c.adi as musteri_ad',
                // Ana/ek bağı: bağlı olduğu ana sözleşmenin başlığı + kendi ek sayısı
                'ana.baslik as ana_baslik',
                DB::raw('(SELECT COUNT(*) FROM crm_sozlesmeler e WHERE e.ana_sozlesme_id = crm_sozlesmeler.id) as ek_sayisi')
            );

        if ($request->filled('kategori')) {
            $q->where('crm_sozlesmeler.kategori_id', (int) $request->kategori);
        }
        if ($request->filled('durum')) {
            $q->where('crm_sozlesmeler.durum', $request->durum);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('crm_sozlesmeler.baslik', 'like', "%{$s}%")
                  ->orWhere('crm_sozlesmeler.sozlesme_no', 'like', "%{$s}%")
                  ->orWhere('crm_sozlesmeler.taraf_adi', 'like', "%{$s}%")
                  ->orWhere('c.adi', 'like', "%{$s}%");
            });
        }

        $sozlesmeler = $q->orderByDesc('crm_sozlesmeler.id')->paginate(20)->withQueryString();

        $toplam = Sozlesme::count();

        return view('admin.crm.sozlesmeler.index', compact('sozlesmeler', 'kategoriler', 'toplam'));
    }

    /** Yeni sözleşme formu */
    public function create(Request $request)
    {
        $kategoriler = SozlesmeKategori::where('durum', 1)->orderBy('sira')->orderBy('ad')->get();
        $musteriler  = Customer::orderBy('adi')->get(['id', 'adi']);
        $sozlesme    = null;
        // Müşteri kartından "Yeni Sözleşme" ile gelindiğinde ön-seçili müşteri
        $onSeciliMusteri = $request->integer('musteri_id') ?: null;
        $anaSecenekler   = $this->anaSozlesmeSecenekleri();
        $ekler           = collect();

        return view('admin.crm.sozlesmeler.form', compact('kategoriler', 'musteriler', 'sozlesme', 'onSeciliMusteri', 'anaSecenekler', 'ekler'));
    }

    /** Kaydet */
    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['sozlesme_no']  = 'SZL-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        $data['olusturan_id'] = session('admin_id');

        $sozlesme = Sozlesme::create($data);

        // Mail + bildirim (hata sözleşme kaydını ASLA bozmasın)
        try {
            $this->sozlesmeMailGonder($sozlesme);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Sözleşme maili gönderilemedi', [
                'sozlesme_id' => $sozlesme->id, 'err' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.crm.sozlesmeler.index')
            ->with('success', 'Sözleşme oluşturuldu: ' . $sozlesme->sozlesme_no);
    }

    /**
     * Sözleşme oluşunca: seçili müşteriye marka şablonlu mail + panel zili,
     * ekibe admin bildirimi. Müşteri 3 katmanlı eşleşir (crm_customers.uye_id /
     * e-posta / aynı ID). Mail gövdesine sözleşme metni gömülür.
     */
    protected function sozlesmeMailGonder(Sozlesme $sozlesme): void
    {
        // --- Seçili CRM müşterisi + bağlı üye ---
        $musteri = null;
        $uye     = null;
        if (!empty($sozlesme->musteri_id)) {
            $musteri = \Illuminate\Support\Facades\DB::table('crm_customers')->where('id', $sozlesme->musteri_id)->first();
        }
        if ($musteri && \Illuminate\Support\Facades\Schema::hasTable('uyeler')) {
            // (1) crm_customers.uye_id
            if (!empty($musteri->uye_id) && \Illuminate\Support\Facades\Schema::hasColumn('crm_customers', 'uye_id')) {
                $uye = \Illuminate\Support\Facades\DB::table('uyeler')->where('id', $musteri->uye_id)->first();
            }
            // (2) e-posta
            if (!$uye && !empty($musteri->email)) {
                $uye = \Illuminate\Support\Facades\DB::table('uyeler')->where('email', $musteri->email)->first();
            }
            // (3) aynı ID (e-posta da uyuşuyorsa)
            if (!$uye) {
                $ayni = \Illuminate\Support\Facades\DB::table('uyeler')->where('id', $musteri->id)->first();
                if ($ayni && (empty($musteri->email) || strcasecmp(trim($ayni->email ?? ''), trim($musteri->email ?? '')) === 0)) {
                    $uye = $ayni;
                }
            }
        }

        // --- Mail gövdesi (özet tablo + sözleşme metni) ---
        $kategoriAd = $sozlesme->kategori->ad ?? null;
        $tutarStr   = is_null($sozlesme->tutar) ? '—' : ('₺' . number_format((float) $sozlesme->tutar, 2, ',', '.'));
        $tarihStr   = $sozlesme->tarih ? \Carbon\Carbon::parse($sozlesme->tarih)->format('d.m.Y') : '—';
        $durumMap   = ['taslak' => 'Taslak', 'aktif' => 'Aktif', 'imzalandi' => 'İmzalandı', 'iptal' => 'İptal'];
        $durumStr   = $durumMap[$sozlesme->durum] ?? ($sozlesme->durum ?? '—');
        $tarafAdi   = $sozlesme->taraf_adi ?: ($musteri->adi ?? '');

        $satir = function ($etiket, $deger) {
            return "<tr><td style='padding:11px 16px;background:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270;width:38%'>"
                 . e($etiket)
                 . "</td><td style='padding:11px 16px;border-bottom:1px solid #eceee6;font-size:14px;font-weight:600'>"
                 . $deger . "</td></tr>";
        };

        $mesaj  = "<p style='margin:0 0 14px'>Sayın " . e($tarafAdi ?: 'Yetkili') . ", aşağıdaki sözleşme tarafınız için oluşturulmuştur:</p>";
        $mesaj .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #eceee6;border-radius:10px;overflow:hidden;margin-bottom:18px'>";
        $mesaj .= $satir('Sözleşme No', e($sozlesme->sozlesme_no));
        $mesaj .= $satir('Başlık', e($sozlesme->baslik));
        if ($kategoriAd) { $mesaj .= $satir('Kategori', e($kategoriAd)); }
        $mesaj .= $satir('Tutar', e($tutarStr));
        $mesaj .= $satir('Durum', e($durumStr));
        $mesaj .= $satir('Tarih', e($tarihStr));
        $mesaj .= "</table>";

        if (!empty($sozlesme->icerik)) {
            $guvenliIcerik = strip_tags(
                $sozlesme->icerik,
                '<p><br><b><strong><i><em><u><ul><ol><li><h1><h2><h3><h4><table><thead><tbody><tr><td><th><span><div><a>'
            );
            $mesaj .= "<div style='border-top:1px solid #eceee6;padding-top:16px'>"
                    . "<div style='font-size:12px;color:#7a8270;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em'>Sözleşme Metni</div>"
                    . "<div style='font-size:14px;line-height:1.7;color:#2b2f25'>" . $guvenliIcerik . "</div></div>";
        }

        // --- Müşteriye marka şablonlu mail + panel zili ---
        if ($uye && class_exists('\App\Services\CustomerNotifier')) {
            \App\Services\CustomerNotifier::musteriyeMail(
                $uye->id,
                '📄 Sözleşme: ' . $sozlesme->baslik,
                $mesaj,
                url('/hesabim'),
                'Hesabıma Git',
                'sozlesme_olusturuldu'
            );

            if (function_exists('uye_bildirim_gonder')) {
                uye_bildirim_gonder(
                    $uye->id,
                    '📄 Yeni Sözleşme: ' . $sozlesme->sozlesme_no,
                    'Size yeni bir sözleşme tanımlandı: ' . $sozlesme->baslik,
                    'info', url('/hesabim'), 'mdi-file-document'
                );
            }
        } elseif ($musteri && !empty($musteri->email) && class_exists('\App\Services\EmailNotificationService')) {
            // Üye kaydı yoksa doğrudan CRM e-postasına gönder
            \App\Services\EmailNotificationService::send(
                $musteri->email,
                '📄 Sözleşme: ' . $sozlesme->baslik,
                $mesaj
            );
        }

        // --- Ekibe (admin) bildirimi ---
        if (function_exists('admin_bildirim_gonder')) {
            $kimFor = $tarafAdi ?: ($musteri->adi ?? 'Taraf');
            admin_bildirim_gonder(
                '📄 Yeni Sözleşme: ' . $sozlesme->sozlesme_no,
                $kimFor . ' için sözleşme oluşturuldu: ' . $sozlesme->baslik . ' (' . $tutarStr . ')',
                'sozlesme',
                'crm',
                (int) $sozlesme->id
            );
        }
    }

    /** Düzenleme formu */
    public function edit(int $id)
    {
        $sozlesme    = Sozlesme::findOrFail($id);
        $kategoriler = SozlesmeKategori::where('durum', 1)->orderBy('sira')->orderBy('ad')->get();
        $musteriler  = Customer::orderBy('adi')->get(['id', 'adi']);

        $onSeciliMusteri = null;
        $anaSecenekler   = $this->anaSozlesmeSecenekleri($id);
        $ekler           = $sozlesme->ekSozlesmeler()->get();

        return view('admin.crm.sozlesmeler.form', compact('kategoriler', 'musteriler', 'sozlesme', 'onSeciliMusteri', 'anaSecenekler', 'ekler'));
    }

    /** Güncelle */
    public function update(Request $request, int $id)
    {
        $sozlesme = Sozlesme::findOrFail($id);
        $sozlesme->update($this->validateData($request, $id));

        return redirect()
            ->route('admin.crm.sozlesmeler.index')
            ->with('success', 'Sözleşme güncellendi: ' . $sozlesme->sozlesme_no);
    }

    /** Sil */
    public function destroy(int $id)
    {
        // Silmeden ÖNCE kaydı İşlem Geçmişi'ne al — yanlışlıkla silinirse geri gelsin
        $sozlesme = Sozlesme::find($id);
        if ($sozlesme) {
            \App\Services\IslemGecmisi::silmeKaydet(
                'crm_sozlesmeler',
                $id,
                'Sözleşme silindi: ' . mb_substr((string) ($sozlesme->sozlesme_no ?: $sozlesme->baslik), 0, 80)
            );
        }

        // Ana sözleşme siliniyorsa ekleri SİLİNMEZ; bağları çözülür ki yetim kalmasınlar.
        $ekAdedi = Sozlesme::where('ana_sozlesme_id', $id)->count();
        if ($ekAdedi > 0) {
            Sozlesme::where('ana_sozlesme_id', $id)->update(['ana_sozlesme_id' => null]);
        }

        Sozlesme::where('id', $id)->delete();

        $mesaj = 'Sözleşme silindi. İşlem Geçmişi\'nden geri alınabilir.';
        if ($ekAdedi > 0) {
            $mesaj .= " Bu sözleşmeye bağlı {$ekAdedi} ek sözleşme SİLİNMEDİ, bağları kaldırıldı"
                    . ' — artık bağımsız sözleşme olarak listede duruyorlar.';
        }

        return back()->with('success', $mesaj);
    }

    /** Yazdırılabilir / çıktı sayfası */
    public function yazdir(int $id)
    {
        $sozlesme = Sozlesme::with(['kategori', 'musteri'])->findOrFail($id);
        $ayarlar  = class_exists('\App\Models\Ayar') ? \App\Models\Ayar::first() : \Illuminate\Support\Facades\DB::table('ayarlar')->first();

        return view('admin.crm.sozlesmeler.yazdir', compact('sozlesme', 'ayarlar'));
    }

    /** Word (.docx) — ekrandaki yazdır görünümüne yakın: çizgili başlık, kutulu taraflar; düzenlenebilir */
    public function word(int $id)
    {
        $sozlesme = Sozlesme::with(['kategori', 'musteri'])->findOrFail($id);
        $ayarlar  = class_exists('\App\Models\Ayar') ? \App\Models\Ayar::first() : \Illuminate\Support\Facades\DB::table('ayarlar')->first();

        $firma = ($ayarlar->fatura_firma_adi ?? null)
            ?: ($ayarlar->firma_adi ?? 'DN GRUP MEDYA VE TEKNOLOJİ ANONİM ŞİRKETİ');

        $musteriAd = $sozlesme->musteri->adi ?? $sozlesme->taraf_adi ?? '—';
        $tarih = $sozlesme->tarih
            ? \Carbon\Carbon::parse($sozlesme->tarih)->format('d.m.Y')
            : \Carbon\Carbon::parse($sozlesme->created_at)->format('d.m.Y');
        $tutar = is_null($sozlesme->tutar) ? null : ('₺' . number_format((float) $sozlesme->tutar, 2, ',', '.'));

        // Dosya adı (ASCII güvenli)
        $ad = $sozlesme->sozlesme_no . '-' . ($sozlesme->baslik ?? 'sozlesme');
        $ad = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $this->trToAscii($ad));
        $ad = trim($ad, '_') ?: 'sozlesme';

        // ZipArchive yoksa eski HTML yöntemine güvenli düşüş
        if (!class_exists('\ZipArchive')) {
            $html = view('admin.crm.sozlesmeler.word', compact('sozlesme', 'ayarlar'))->render();
            return response($html, 200, [
                'Content-Type'        => 'application/msword; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $ad . '.doc"',
            ]);
        }

        // --- Gövde ---
        $body = '';

        // Üst başlık bloğu: solda firma (büyük kalın), sağda meta — altında kalın çizgi
        $metaLines = ['Sözleşme No: ' . $sozlesme->sozlesme_no, 'Tarih: ' . $tarih];
        if ($tutar) { $metaLines[] = 'Tutar: ' . $tutar; }
        $body .= $this->docxHeaderBlock($firma, $metaLines);
        $body .= $this->docxPara('', false, 22, null, 120);

        // Ortalanmış başlık + kategori
        $body .= $this->docxPara($sozlesme->baslik ?? 'Sözleşme', true, 28, 'center', 40);
        if (!empty($sozlesme->kategori->ad)) {
            $body .= $this->docxPara($sozlesme->kategori->ad, false, 16, 'center', 200, '888888');
        } else {
            $body .= $this->docxPara('', false, 22, null, 120);
        }

        // HİZMET VEREN / MÜŞTERİ kutuları (yan yana)
        $body .= $this->docxTwoBox('HİZMET VEREN', $firma, 'MÜŞTERİ / KARŞI TARAF', $musteriAd);
        $body .= $this->docxPara('', false, 22, null, 200);

        // Sözleşme metni
        if (!empty($sozlesme->icerik)) {
            $body .= $this->htmlToDocxParas($sozlesme->icerik);
        } else {
            $body .= $this->docxPara('(Sözleşme metni girilmemiş)');
        }

        // İmza alanı (yan yana kutusuz, üstte çizgi hissi için boşluk)
        $body .= $this->docxPara('', false, 22, null, 400);
        $body .= $this->docxImzaBlok($firma, $musteriAd);

        // --- .docx paketi ---
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . $body
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
            . '</w:body></w:document>';

        $tmp = tempnam(sys_get_temp_dir(), 'szl') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        return response()->download($tmp, $ad . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /** Tek bir run (metin parçası) */
    private function docxRun(string $text, bool $bold = false, int $size = 22, ?string $color = null): string
    {
        $rpr = '<w:rPr>';
        if ($bold) { $rpr .= '<w:b/>'; }
        $rpr .= '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>';
        if ($color) { $rpr .= '<w:color w:val="' . $color . '"/>'; }
        $rpr .= '</w:rPr>';
        return '<w:r>' . $rpr . '<w:t xml:space="preserve">' . $this->xmlEsc($text) . '</w:t></w:r>';
    }

    /** Paragraf (hizalama/boşluk/renk) */
    private function docxPara(string $text, bool $bold = false, int $size = 22, ?string $align = null, int $after = 120, ?string $color = null): string
    {
        $ppr = '<w:pPr>';
        if ($align) { $ppr .= '<w:jc w:val="' . $align . '"/>'; }
        $ppr .= '<w:spacing w:after="' . $after . '"/></w:pPr>';
        return '<w:p>' . $ppr . $this->docxRun($text, $bold, $size, $color) . '</w:p>';
    }

    /** Üst başlık bloğu: sol firma + sağ meta, altında kalın çizgi (2 sütun tablo) */
    private function docxHeaderBlock(string $firma, array $metaLines): string
    {
        $solPara = '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr>' . $this->docxRun($firma, true, 32) . '</w:p>';

        $sagParas = '';
        foreach ($metaLines as $m) {
            $sagParas .= '<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:after="20"/></w:pPr>'
                       . $this->docxRun($m, false, 16) . '</w:p>';
        }

        $solCell = '<w:tc><w:tcPr><w:tcW w:w="6500" w:type="dxa"/>'
                 . '<w:tcMar><w:top w:w="60" w:type="dxa"/><w:bottom w:w="120" w:type="dxa"/></w:tcMar></w:tcPr>'
                 . $solPara . '</w:tc>';
        $sagCell = '<w:tc><w:tcPr><w:tcW w:w="3500" w:type="dxa"/>'
                 . '<w:tcMar><w:top w:w="60" w:type="dxa"/><w:bottom w:w="120" w:type="dxa"/></w:tcMar></w:tcPr>'
                 . $sagParas . '</w:tc>';

        return '<w:tbl><w:tblPr><w:tblW w:w="10000" w:type="dxa"/><w:tblLayout w:type="fixed"/>'
             . '<w:tblBorders><w:bottom w:val="single" w:sz="12" w:color="222222"/></w:tblBorders></w:tblPr>'
             . '<w:tblGrid><w:gridCol w:w="6500"/><w:gridCol w:w="3500"/></w:tblGrid>'
             . '<w:tr>' . $solCell . $sagCell . '</w:tr></w:tbl>';
    }

    /** Yan yana iki kenarlıklı kutu (HİZMET VEREN / MÜŞTERİ) */
    private function docxTwoBox(string $solLabel, string $solVal, string $sagLabel, string $sagVal): string
    {
        $kutu = function ($label, $val) {
            $labelP = '<w:p><w:pPr><w:spacing w:after="40"/></w:pPr>' . $this->docxRun($label, false, 15, '8A8F7E') . '</w:p>';
            $valP   = '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr>' . $this->docxRun($val, true, 22) . '</w:p>';
            return '<w:tc><w:tcPr><w:tcW w:w="4900" w:type="dxa"/>'
                 . '<w:tcBorders>'
                 . '<w:top w:val="single" w:sz="4" w:color="D9DCD2"/>'
                 . '<w:left w:val="single" w:sz="4" w:color="D9DCD2"/>'
                 . '<w:bottom w:val="single" w:sz="4" w:color="D9DCD2"/>'
                 . '<w:right w:val="single" w:sz="4" w:color="D9DCD2"/></w:tcBorders>'
                 . '<w:tcMar><w:top w:w="120" w:type="dxa"/><w:left w:w="140" w:type="dxa"/><w:bottom w:w="120" w:type="dxa"/><w:right w:w="140" w:type="dxa"/></w:tcMar>'
                 . '</w:tcPr>' . $labelP . $valP . '</w:tc>';
        };

        $ara = '<w:tc><w:tcPr><w:tcW w:w="200" w:type="dxa"/></w:tcPr><w:p/></w:tc>';

        return '<w:tbl><w:tblPr><w:tblW w:w="10000" w:type="dxa"/><w:tblLayout w:type="fixed"/></w:tblPr>'
             . '<w:tblGrid><w:gridCol w:w="4900"/><w:gridCol w:w="200"/><w:gridCol w:w="4900"/></w:tblGrid>'
             . '<w:tr>' . $kutu($solLabel, $solVal) . $ara . $kutu($sagLabel, $sagVal) . '</w:tr></w:tbl>';
    }

    /** İmza bloğu: yan yana iki sütun, üstte çizgi */
    private function docxImzaBlok(string $sol, string $sag): string
    {
        $hucre = function ($metin) {
            $p = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="600" w:after="0"/>'
               . '<w:pBdr><w:top w:val="single" w:sz="4" w:color="333333" w:space="4"/></w:pBdr></w:pPr>'
               . $this->docxRun($metin, false, 20) . '</w:p>';
            return '<w:tc><w:tcPr><w:tcW w:w="4900" w:type="dxa"/></w:tcPr>' . $p . '</w:tc>';
        };
        $ara = '<w:tc><w:tcPr><w:tcW w:w="200" w:type="dxa"/></w:tcPr><w:p/></w:tc>';

        return '<w:tbl><w:tblPr><w:tblW w:w="10000" w:type="dxa"/><w:tblLayout w:type="fixed"/></w:tblPr>'
             . '<w:tblGrid><w:gridCol w:w="4900"/><w:gridCol w:w="200"/><w:gridCol w:w="4900"/></w:tblGrid>'
             . '<w:tr>' . $hucre($sol) . $ara . $hucre($sag) . '</w:tr></w:tbl>';
    }

    /** Sözleşme metnindeki HTML'i Word paragraflarına çevirir (başlık/paragraf/madde) */
    private function htmlToDocxParas(string $html): string
    {
        $html = preg_replace('#<\s*br\s*/?>#i', "\n", $html);
        $html = preg_replace('#</\s*(p|div|li|tr|h[1-6])\s*>#i', "\n", $html);
        $html = preg_replace('#<\s*(h[1-4])[^>]*>#i', "\x01", $html);
        $html = preg_replace('#<\s*li[^>]*>#i', "• ", $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $out = '';
        foreach (preg_split('/\n/', $text) as $satir) {
            $satir = trim($satir);
            if ($satir === '') { continue; }
            $baslikMi = false;
            if (strpos($satir, "\x01") !== false) {
                $baslikMi = true;
                $satir = trim(str_replace("\x01", '', $satir));
                if ($satir === '') { continue; }
            }
            $out .= $this->docxPara($satir, $baslikMi, $baslikMi ? 24 : 22, null, $baslikMi ? 80 : 120);
        }
        return $out;
    }

    /** XML için güvenli kaçış */
    private function xmlEsc(string $s): string
    {
        return str_replace(
            ['&', '<', '>', '"', "'"],
            ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
            $s
        );
    }

    private function trToAscii(string $s): string
    {
        return strtr($s, [
            'ç'=>'c','Ç'=>'C','ğ'=>'g','Ğ'=>'G','ı'=>'i','İ'=>'I',
            'ö'=>'o','Ö'=>'O','ş'=>'s','Ş'=>'S','ü'=>'u','Ü'=>'U',
        ]);
    }

    /** Kategori ekle */
    public function kategoriEkle(Request $request)
    {
        $request->validate([
            'ad'   => 'required|string|max:150',
            'renk' => 'nullable|string|max:20',
        ]);

        SozlesmeKategori::create([
            'ad'    => $request->ad,
            'renk'  => $request->renk ?: '#6366f1',
            'sira'  => (int) (SozlesmeKategori::max('sira') ?? 0) + 1,
            'durum' => 1,
        ]);

        return back()->with('success', 'Kategori eklendi.');
    }

    /** Kategori sil (kullanımdaki sözleşmelerin kategorisi boşa düşer) */
    public function kategoriSil(int $id)
    {
        DB::table('crm_sozlesmeler')->where('kategori_id', $id)->update(['kategori_id' => null]);
        SozlesmeKategori::where('id', $id)->delete();

        return back()->with('success', 'Kategori silindi.');
    }

    protected function validateData(Request $request, ?int $duzenlenenId = null): array
    {
        $validated = $request->validate([
            'baslik'      => 'required|string|max:255',
            'kategori_id' => 'nullable|integer|exists:crm_sozlesme_kategorileri,id',
            'musteri_id'  => 'nullable|integer|exists:crm_customers,id',
            'taraf_adi'   => 'nullable|string|max:255',
            'tutar'       => 'nullable|numeric|min:0',
            'durum'       => 'required|in:taslak,aktif,imzalandi,iptal',
            'tarih'       => 'nullable|date',
            'baslangic_tarihi' => 'nullable|date',
            'bitis_tarihi'     => 'nullable|date|after_or_equal:baslangic_tarihi',
            'icerik'      => 'nullable|string',
            'ana_sozlesme_id'  => 'nullable|integer|exists:crm_sozlesmeler,id',
        ], [], [
            'baslangic_tarihi' => 'başlangıç tarihi',
            'bitis_tarihi'     => 'bitiş tarihi',
            'ana_sozlesme_id'  => 'ana sözleşme',
        ]);

        // Bitiş tarihi girildiyse/değiştiyse hatırlatma damgası sıfırlanır,
        // böylece yeni tarihe göre bildirim tekrar gönderilebilir.
        $validated['bitis_bildirim_at'] = null;

        $validated['ana_sozlesme_id'] = $this->anaSozlesmeDogrula(
            $validated['ana_sozlesme_id'] ?? null,
            $duzenlenenId
        );

        return $validated;
    }

    /**
     * Ana sözleşme seçimini doğrular. Bozuk bağ kurulmasını engeller:
     *   1) Sözleşme kendi kendinin eki olamaz
     *   2) Zaten EK olan bir sözleşme ANA seçilemez (tek seviye kuralı)
     *   3) Bu sözleşmenin kendi ekleri varsa, o ek ANA olarak seçilemez (döngü)
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function anaSozlesmeDogrula(?int $anaId, ?int $duzenlenenId): ?int
    {
        if (empty($anaId)) return null;

        $hata = function (string $mesaj) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ana_sozlesme_id' => $mesaj]);
        };

        if ($duzenlenenId && (int) $anaId === (int) $duzenlenenId) {
            $hata('Bir sözleşme kendisinin eki olamaz.');
        }

        $ana = Sozlesme::find($anaId);
        if (!$ana) $hata('Seçilen ana sözleşme bulunamadı.');

        if (!empty($ana->ana_sozlesme_id)) {
            $hata('Seçtiğin sözleşme zaten başka bir sözleşmenin eki. Ek sözleşmeye ek bağlanamaz — ana sözleşmeyi seç.');
        }

        if ($duzenlenenId && (int) $ana->ana_sozlesme_id === (int) $duzenlenenId) {
            $hata('Bu sözleşme seçtiğin sözleşmenin anası; ikisi birbirine bağlanamaz.');
        }

        // Bu sözleşmenin kendi ekleri varsa, artık ek olamaz (ana konumunda)
        if ($duzenlenenId && Sozlesme::where('ana_sozlesme_id', $duzenlenenId)->exists()) {
            $hata('Bu sözleşmenin kendisine bağlı ek sözleşmeleri var; ana sözleşme olduğu için başka bir sözleşmeye ek yapılamaz.');
        }

        return (int) $anaId;
    }

    /** Forma "ana sözleşme" seçeneklerini hazırlar (ek olanlar ve düzenlenen hariç). */
    protected function anaSozlesmeSecenekleri(?int $haricId = null): \Illuminate\Support\Collection
    {
        return Sozlesme::query()
            // NOT: crm_customers join'i var — kolonlar tablo adıyla nitelenmeli,
            // yoksa 'id' ve 'ana_sozlesme_id' belirsiz kalıp SQL hatası veriyor.
            ->whereNull('crm_sozlesmeler.ana_sozlesme_id')
            ->when($haricId, fn ($q) => $q->where('crm_sozlesmeler.id', '<>', $haricId))
            ->leftJoin('crm_customers as mc', 'mc.id', '=', 'crm_sozlesmeler.musteri_id')
            ->orderByDesc('crm_sozlesmeler.tarih')
            ->orderByDesc('crm_sozlesmeler.id')
            ->limit(500)
            ->get([
                'crm_sozlesmeler.id',
                'crm_sozlesmeler.baslik',
                'crm_sozlesmeler.sozlesme_no',
                'crm_sozlesmeler.tarih',
                'crm_sozlesmeler.musteri_id',
                'mc.adi as musteri_adi',
            ]);
    }
}