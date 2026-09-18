<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Services\DomainKarRaporu;
use App\Services\IslemGecmisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DOMAIN TOPLU TUTAR GİRİŞİ.
 *
 * `satilanlar` tablosundaki domain kayıtlarının 221'inde `tutar` boş/0. Bu yüzden
 * kâr raporu ciroyu liste fiyatından TAHMİN ediyor. Kayıtları tek tek açıp
 * düzenlemek yerine burada hepsi tek listede, yanlarında uzantıya göre önerilen
 * fiyatla birlikte gelir; istenen satırlar doldurulup tek seferde kaydedilir.
 *
 * Sadece DOLU gönderilen satırlar yazılır — boş bırakılan kayda dokunulmaz.
 * Tüm parti İşlem Geçmişi'ne yazılır, yanlış girilirse "Geri Al" ile döner.
 */
class TopluTutarController extends Controller
{
    /** Domain sayılan kayıt tipleri (0 = domain, 1/2/3 = hizmet/hosting) */
    private const TIPLER = ['0', '1', '2', '3'];

    public function index(Request $request)
    {
        $uzantilar = DomainKarRaporu::uzantilar();

        $uzanti = strtolower(ltrim(trim((string) $request->query('uzanti')), '.'));
        if ($uzanti !== '' && !array_key_exists($uzanti, $uzantilar)) $uzanti = '';

        $ara  = trim((string) $request->query('ara'));
        $sira = $request->query('sira') === 'domain' ? 'domain' : 'tarih';

        $sorgu = DB::table('satilanlar as s')
            ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
            ->leftJoin('crm_customers as c', 'c.id', '=', 's.crm_musteri_id')
            ->whereIn('s.tipi', self::TIPLER)
            ->whereNotNull('s.domain')->where('s.domain', '<>', '')
            ->where(function ($q) {
                $q->whereNull('s.tutar')->orWhere('s.tutar', '')->orWhere('s.tutar', '0')->orWhere('s.tutar', 0);
            });

        if ($ara !== '') {
            $sorgu->where(function ($q) use ($ara) {
                $q->where('s.domain', 'like', "%{$ara}%")
                  ->orWhere('c.adi', 'like', "%{$ara}%")
                  ->orWhere('u.ad', 'like', "%{$ara}%")
                  ->orWhere('u.soyad', 'like', "%{$ara}%");
            });
        }

        $kayitlar = $sorgu->select(
                's.id', 's.domain', 's.tarih', 's.bitis_tarih', 's.tipi', 's.maliyet',
                DB::raw('COALESCE(c.adi, CONCAT(COALESCE(u.ad,\'\'), \' \', COALESCE(u.soyad,\'\'))) as musteri')
            )
            ->orderBy($sira === 'domain' ? 's.domain' : 's.tarih', $sira === 'domain' ? 'asc' : 'desc')
            ->limit(1000)->get();

        // Uzantı + öneri fiyatını her satıra iliştir, sonra uzantı filtresini uygula
        $kayitlar = $kayitlar->map(function ($k) use ($uzantilar) {
            $u = DomainKarRaporu::uzantiBul($k->domain, $uzantilar);
            $k->uzanti = $u;
            $k->oneri  = $u !== null ? (float) ($uzantilar[$u]['yenileme'] ?? 0) : 0.0;

            return $k;
        });

        if ($uzanti !== '') {
            $kayitlar = $kayitlar->filter(fn ($k) => $k->uzanti === $uzanti)->values();
        }

        // Uzantı dağılımı (filtre kutusundaki sayılar) — filtreden BAĞIMSIZ olsun diye ayrı sayılır
        $dagilim = [];
        foreach ($kayitlar as $k) {
            $ad = $k->uzanti ?? 'bilinmiyor';
            $dagilim[$ad] = ($dagilim[$ad] ?? 0) + 1;
        }
        arsort($dagilim);

        return view('admin.crm.toplu-tutar.index', [
            'kayitlar'    => $kayitlar,
            'uzantilar'   => $uzantilar,
            'dagilim'     => $dagilim,
            'uzanti'      => $uzanti,
            'ara'         => $ara,
            'sira'        => $sira,
            'dolduruldu'  => $this->doluAdet(),
            'oneriToplam' => $kayitlar->sum('oneri'),
        ]);
    }

    public function kaydet(Request $request)
    {
        $gelen = $request->input('tutar', []);
        if (!is_array($gelen) || !$gelen) {
            return back()->with('error', 'Hiçbir tutar girilmedi.');
        }

        // 1) Girilen değerleri temizle — sadece geçerli ve 0'dan büyük olanlar
        $temiz = [];
        foreach ($gelen as $id => $deger) {
            $d = str_replace([' ', '.'], '', trim((string) $deger));
            $d = str_replace(',', '.', $d);
            if ($d === '' || !is_numeric($d) || (float) $d <= 0) continue;
            $temiz[(int) $id] = (float) $d;
        }
        if (!$temiz) {
            return back()->with('error', 'Geçerli tutar bulunamadı. Sayı girmelisin (örn. 1500).');
        }

        // 2) Sadece gerçekten boş olan domain kayıtları güncellenebilir
        $hedefler = DB::table('satilanlar')->whereIn('id', array_keys($temiz))
            ->whereIn('tipi', self::TIPLER)
            ->whereNotNull('domain')->where('domain', '<>', '')
            ->where(function ($q) {
                $q->whereNull('tutar')->orWhere('tutar', '')->orWhere('tutar', '0')->orWhere('tutar', 0);
            })
            ->get(['id', 'domain', 'tutar'])->keyBy('id');

        if ($hedefler->isEmpty()) {
            return back()->with('error', 'Güncellenecek uygun kayıt bulunamadı (tutarları bu arada dolmuş olabilir).');
        }

        // 3) Yaz + İşlem Geçmişi'ne parti olarak kaydet (tek "Geri Al" ile hepsi döner)
        $etkilenen = [];
        $yazilan   = 0;
        foreach ($hedefler as $id => $kayit) {
            $yeni = $temiz[$id];
            DB::table('satilanlar')->where('id', $id)->update(['tutar' => $yeni]);
            $etkilenen[] = [
                'tablo' => 'satilanlar', 'id' => (int) $id, 'islem' => 'guncelle',
                'eski'  => ['tutar' => $kayit->tutar],
                'yeni'  => ['tutar' => $yeni],
            ];
            $yazilan++;
        }

        IslemGecmisi::kaydet(
            'guncelle',
            "Toplu tutar girişi — {$yazilan} domain kaydının satış tutarı dolduruldu",
            $etkilenen
        );

        $atlanan = count($temiz) - $yazilan;
        $mesaj = "{$yazilan} kaydın tutarı kaydedildi. Yanlışsa İşlem Geçmişi'nden geri alabilirsin.";
        if ($atlanan > 0) $mesaj .= " ({$atlanan} kayıt atlandı — tutarı zaten doluydu.)";

        return redirect()->route('admin.crm.domains.toplu-tutar')->with('success', $mesaj);
    }

    /** Tutarı girilmiş domain kaydı sayısı — ekrandaki ilerleme göstergesi için */
    private function doluAdet(): int
    {
        return DB::table('satilanlar')->whereIn('tipi', self::TIPLER)
            ->whereNotNull('domain')->where('domain', '<>', '')
            ->where('tutar', '>', 0)->count();
    }
}
