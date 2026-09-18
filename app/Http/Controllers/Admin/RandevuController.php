<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RandevuSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Randevu Yönetimi modülü.
 * Randevular = çalışan-kolonlu günlük takvim (custom) + Ay/Hafta/Liste (FullCalendar).
 */
class RandevuController extends Controller
{
    /* ─────────── RANDEVULAR (TAKVİM) ─────────── */

    public function randevular(Request $request)
    {
        $tarih = $request->query('tarih');
        try { $tarih = $tarih ? Carbon::parse($tarih) : Carbon::today(); }
        catch (\Throwable $e) { $tarih = Carbon::today(); }

        $calisanlar = DB::table('randevu_calisanlar')
            ->where('durum', 1)
            ->orderBy('siralama')->orderBy('id')
            ->get();

        $hizmetler = DB::table('randevu_hizmetler')
            ->where('durum', 1)
            ->orderBy('ad')->get();

        // Randevu müşterileri CRM'den seçilir (tek müşteri rehberi)
        $crmMusteriler = DB::table('crm_customers')
            ->select('id', 'adi', 'telefon', 'gsm')
            ->orderBy('adi')
            ->get();

        return view('admin.randevu.randevular', [
            'baslik'        => 'Randevular',
            'tarih'         => $tarih,
            'calisanlar'    => $calisanlar,
            'hizmetler'     => $hizmetler,
            'crmMusteriler' => $crmMusteriler,
        ]);
    }

    /** FullCalendar + günlük ızgara için JSON randevu beslemesi */
    public function events(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        $q = DB::table('randevular as r')
            ->leftJoin('randevu_calisanlar as c', 'c.id', '=', 'r.calisan_id')
            ->leftJoin('randevu_hizmetler as h', 'h.id', '=', 'r.hizmet_id')
            ->select('r.*', 'c.ad as calisan_ad', 'c.renk as calisan_renk', 'h.ad as hizmet_ad', 'h.renk as hizmet_renk');

        if ($start) { try { $q->where('r.baslangic', '>=', Carbon::parse($start)->startOfDay()); } catch (\Throwable $e) {} }
        if ($end)   { try { $q->where('r.baslangic', '<=', Carbon::parse($end)->endOfDay());     } catch (\Throwable $e) {} }

        $rows = $q->orderBy('r.baslangic')->get();

        $events = $rows->map(function ($r) {
            $renk = $r->hizmet_renk ?: ($r->calisan_renk ?: '#3b82f6');
            if ($r->durum === 'iptal') $renk = '#9ca3af';
            return [
                'id'    => $r->id,
                'title' => $r->musteri_ad . ($r->hizmet_ad ? ' · ' . $r->hizmet_ad : ''),
                'start' => Carbon::parse($r->baslangic)->toIso8601String(),
                'end'   => Carbon::parse($r->bitis)->toIso8601String(),
                'backgroundColor' => $renk,
                'borderColor'     => $renk,
                'extendedProps'   => [
                    'calisan_id'  => $r->calisan_id,
                    'calisan_ad'  => $r->calisan_ad,
                    'hizmet_id'   => $r->hizmet_id,
                    'hizmet_ad'   => $r->hizmet_ad,
                    'musteri_ad'  => $r->musteri_ad,
                    'musteri_tel' => $r->musteri_tel,
                    'durum'       => $r->durum,
                    'notlar'      => $r->notlar,
                    'ozel_not'    => $r->ozel_not ?? null,
                    'crm_musteri_id' => $r->crm_musteri_id ?? null,
                    'olusturan'   => $r->olusturan_adi ?? null,
                ],
            ];
        });

        return response()->json($events);
    }

    /** Randevu oluştur / güncelle */
    public function kaydet(Request $request)
    {
        $data = $request->validate([
            'id'          => 'nullable|integer',
            'calisan_id'  => 'required|integer',
            'hizmet_id'   => 'nullable|integer',
            'crm_musteri_id' => 'nullable|integer',
            'musteri_ad'  => 'required|string|max:190',
            'musteri_tel' => 'nullable|string|max:40',
            'tarih'       => 'required|date',
            'saat'        => 'required|string',
            'sure_dk'     => 'nullable|integer|min:5|max:600',
            'fiyat'       => 'nullable|numeric',
            'durum'       => 'nullable|string|max:20',
            'notlar'      => 'nullable|string',
            'ozel_not'    => 'nullable|string',
        ]);

        // Hizmet seçiliyse varsayılan süre/fiyat
        $hizmet = !empty($data['hizmet_id'])
            ? DB::table('randevu_hizmetler')->find($data['hizmet_id'])
            : null;

        $sure  = $data['sure_dk'] ?? ($hizmet->sure_dk ?? 30);
        $fiyat = $data['fiyat']   ?? ($hizmet->fiyat ?? 0);

        try {
            $bas = Carbon::parse($data['tarih'] . ' ' . $data['saat']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'msg' => 'Geçersiz tarih/saat'], 422);
        }
        $bit = (clone $bas)->addMinutes((int) $sure);

        // Müşteriyi telefona göre bul/oluştur (basit kayıt)
        $musteriId = null;
        if (!empty($data['musteri_tel'])) {
            $mevcut = DB::table('randevu_musteriler')->where('telefon', $data['musteri_tel'])->first();
            if ($mevcut) {
                $musteriId = $mevcut->id;
            } else {
                $musteriId = DB::table('randevu_musteriler')->insertGetId([
                    'ad' => $data['musteri_ad'], 'telefon' => $data['musteri_tel'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $kayit = [
            'calisan_id'  => $data['calisan_id'],
            'hizmet_id'   => $data['hizmet_id'] ?? null,
            'musteri_id'  => $musteriId,
            'crm_musteri_id' => $data['crm_musteri_id'] ?? null,
            'musteri_ad'  => $data['musteri_ad'],
            'musteri_tel' => $data['musteri_tel'] ?? null,
            'baslangic'   => $bas->format('Y-m-d H:i:s'),
            'bitis'       => $bit->format('Y-m-d H:i:s'),
            'fiyat'       => $fiyat,
            'durum'       => $data['durum'] ?? 'beklemede',
            'notlar'      => $data['notlar'] ?? null,
            'ozel_not'    => $data['ozel_not'] ?? null,
            'updated_at'  => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('randevular')->where('id', $data['id'])->update($kayit);
            $id = $data['id'];
        } else {
            $kayit['created_at'] = now();
            // Oluşturan bilgisi (ilk kayıtta yazılır, sonradan değişmez).
            // Bildirimler bu kişiye gider; kolon yoksa (migration çalışmamışsa) atlanır.
            if (Schema::hasColumn('randevular', 'olusturan_id')) {
                $kayit['olusturan_id']  = session('admin_id');
            }
            if (Schema::hasColumn('randevular', 'olusturan_adi')) {
                $kayit['olusturan_adi'] = session('admin_adi') ?: session('admin_kullanici_adi');
            }
            $id = DB::table('randevular')->insertGetId($kayit);
        }

        // Kayıt SMS'i: not yazıldıysa veya DN Kreatif konumu seçildiyse müşteriye anında gönder.
        // Özel not ASLA gönderilmez. Mükerrer engeli için sms_kayit_at damgalanır.
        $this->kayitSmsGonder($id, $data, $hizmet);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    /**
     * Randevu kaydedildiğinde müşteriye anlık SMS:
     *  - "Not" alanı doluysa → not metni gönderilir (özel not hariç).
     *  - Seçilen lokasyonun kendi "konum_link" değeri varsa → o konum linki eklenir.
     *    (ör. Marmaris ofisi seçilince Marmaris'in linki, İstanbul seçilince İstanbul'un linki.)
     * Bir randevuya yalnızca bir kez gönderilir (sms_kayit_at).
     */
    private function kayitSmsGonder(int $id, array $data, ?object $hizmet): void
    {
        try {
            $rdv = DB::table('randevular')->find($id);
            if (!$rdv) return;

            // Daha önce kayıt SMS'i gönderildiyse tekrar gönderme
            if (!empty($rdv->sms_kayit_at)) return;

            $tel = $data['musteri_tel'] ?? ($rdv->musteri_tel ?? null);
            if (!$tel) return;

            $parcalar = [];

            // 1) Not (müşteriye gider) — özel not değil
            $not = trim((string) ($data['notlar'] ?? ''));
            if ($not !== '') {
                $parcalar[] = Str::limit($not, 300, '');
            }

            // 2) Seçilen lokasyonun konum linki (her ofisin kendi linki)
            $konumLink = isset($hizmet->konum_link) ? trim((string) $hizmet->konum_link) : '';
            if ($konumLink !== '') {
                $konumAdi = $hizmet->ad ?? null;
                $parcalar[] = 'Konum' . ($konumAdi ? ' (' . Str::limit($konumAdi, 40, '') . ')' : '') . ': ' . $konumLink;
            }

            if (empty($parcalar)) return;

            $ad = $data['musteri_ad'] ?? ($rdv->musteri_ad ?? 'Musterimiz');
            $mesaj = 'Sayin ' . Str::limit($ad, 40, '') . ', ' . implode(' ', $parcalar);

            $ok = RandevuSms::gonder($tel, $mesaj);
            DB::table('randevular')->where('id', $id)->update(['sms_kayit_at' => now()]);
            Log::info('Randevu kayit SMS', ['randevu' => $id, 'ok' => $ok]);
        } catch (\Throwable $e) {
            Log::warning('Randevu kayit SMS hatasi', ['randevu' => $id, 'e' => $e->getMessage()]);
        }
    }

    /** Sürükle-bırak: başlangıç/çalışan güncelle */
    public function tasi(Request $request, $id)
    {
        $data = $request->validate([
            'baslangic'  => 'required|date',
            'calisan_id' => 'nullable|integer',
        ]);
        $rdv = DB::table('randevular')->find($id);
        if (!$rdv) return response()->json(['ok' => false], 404);

        $bas = Carbon::parse($data['baslangic']);
        $sureDk = Carbon::parse($rdv->baslangic)->diffInMinutes(Carbon::parse($rdv->bitis));
        $upd = [
            'baslangic' => $bas->format('Y-m-d H:i:s'),
            'bitis'     => (clone $bas)->addMinutes($sureDk)->format('Y-m-d H:i:s'),
            'updated_at'=> now(),
        ];
        if (!empty($data['calisan_id'])) $upd['calisan_id'] = $data['calisan_id'];

        DB::table('randevular')->where('id', $id)->update($upd);
        return response()->json(['ok' => true]);
    }

    public function sil($id)
    {
        DB::table('randevular')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    /* ─────────── DİĞER SAYFALAR (içerik sonra) ─────────── */

    /* ─────────── HİZMETLER (CRUD) ─────────── */

    public function hizmetler()
    {
        $hizmetler = DB::table('randevu_hizmetler')->orderBy('ad')->get();
        return view('admin.randevu.hizmetler', ['baslik' => 'Hizmetler', 'hizmetler' => $hizmetler]);
    }

    /** Hizmet ekle/düzenle formu (tam sayfa) */
    public function hizmetForm($id = null)
    {
        $hizmet = $id ? DB::table('randevu_hizmetler')->find($id) : null;
        return view('admin.randevu.hizmet-form', [
            'baslik' => $hizmet ? 'Hizmet Düzenleme' : 'Yeni Hizmet',
            'hizmet' => $hizmet,
        ]);
    }

    public function hizmetKaydet(Request $request)
    {
        $data = $request->validate([
            'id'                => 'nullable|integer',
            'ad'                => 'required|string|max:190',
            'aciklama'          => 'nullable|string',
            'konum_link'        => 'nullable|string|max:500',
            'sure_dk'           => 'required|integer|min:5|max:600',
            'tekrarlama_suresi' => 'nullable|integer|min:0',
            'fiyat'             => 'nullable|numeric',
            'renk'              => 'nullable|string|max:20',
            'durum'             => 'nullable',
        ]);

        $kayit = [
            'ad'                => $data['ad'],
            'aciklama'          => $data['aciklama'] ?? null,
            'konum_link'        => $data['konum_link'] ?? null,
            'sure_dk'           => $data['sure_dk'],
            'tekrarlama_suresi' => $data['tekrarlama_suresi'] ?? 0,
            'fiyat'             => $data['fiyat'] ?? 0,
            'renk'              => $data['renk'] ?: '#3b82f6',
            'durum'             => $request->boolean('durum') ? 1 : 0,
            'updated_at'        => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('randevu_hizmetler')->where('id', $data['id'])->update($kayit);
            $msg = 'Hizmet güncellendi.';
        } else {
            $kayit['created_at'] = now();
            DB::table('randevu_hizmetler')->insert($kayit);
            $msg = 'Hizmet eklendi.';
        }

        return redirect()->route('admin.randevu.hizmetler')->with('success', $msg);
    }

    public function hizmetSil($id)
    {
        DB::table('randevu_hizmetler')->where('id', $id)->delete();
        return redirect()->route('admin.randevu.hizmetler')->with('success', 'Hizmet silindi.');
    }

    /* ─────────── MÜŞTERİLER (CRUD) ─────────── */

    public function musteriler()
    {
        $musteriler = DB::table('randevu_musteriler')->orderByDesc('id')->get();

        $sayilar = DB::table('randevular')
            ->whereNotNull('musteri_id')
            ->select('musteri_id', DB::raw('COUNT(*) as adet'), DB::raw('MAX(baslangic) as son'))
            ->groupBy('musteri_id')
            ->get()->keyBy('musteri_id');

        return view('admin.randevu.musteriler', [
            'baslik'     => 'Müşteriler',
            'musteriler' => $musteriler,
            'sayilar'    => $sayilar,
        ]);
    }

    public function musteriForm($id = null)
    {
        $musteri = $id ? DB::table('randevu_musteriler')->find($id) : null;
        return view('admin.randevu.musteri-form', [
            'baslik'  => $musteri ? 'Müşteri Düzenleme' : 'Yeni Müşteri',
            'musteri' => $musteri,
        ]);
    }

    public function musteriKaydet(Request $request)
    {
        $data = $request->validate([
            'id'      => 'nullable|integer',
            'ad'      => 'required|string|max:190',
            'telefon' => 'nullable|string|max:40',
            'not'     => 'nullable|string',
        ]);

        $kayit = [
            'ad'         => $data['ad'],
            'telefon'    => $data['telefon'] ?? null,
            'not'        => $data['not'] ?? null,
            'updated_at' => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('randevu_musteriler')->where('id', $data['id'])->update($kayit);
            $msg = 'Müşteri güncellendi.';
        } else {
            $kayit['created_at'] = now();
            DB::table('randevu_musteriler')->insert($kayit);
            $msg = 'Müşteri eklendi.';
        }

        return redirect()->route('admin.randevu.musteriler')->with('success', $msg);
    }

    public function musteriSil($id)
    {
        DB::table('randevu_musteriler')->where('id', $id)->delete();
        return redirect()->route('admin.randevu.musteriler')->with('success', 'Müşteri silindi.');
    }

    /* ─────────── ÇALIŞANLAR (CRUD) ─────────── */

    public function calisanlar()
    {
        $calisanlar = DB::table('randevu_calisanlar')->orderBy('siralama')->orderBy('id')->get();

        // Bu ayki randevu sayıları
        $sayilar = DB::table('randevular')
            ->whereYear('baslangic', now()->year)
            ->whereMonth('baslangic', now()->month)
            ->select('calisan_id', DB::raw('COUNT(*) as adet'))
            ->groupBy('calisan_id')
            ->pluck('adet', 'calisan_id');

        return view('admin.randevu.calisanlar', [
            'baslik'     => 'Çalışanlar',
            'calisanlar' => $calisanlar,
            'sayilar'    => $sayilar,
        ]);
    }

    public function calisanForm($id = null)
    {
        $calisan = $id ? DB::table('randevu_calisanlar')->find($id) : null;

        // Çalışanlar panel yöneticilerinden seçilir
        try {
            $yoneticiler = DB::table('yoneticiler')
                ->select('id', 'adi', 'email', 'eposta')
                ->orderBy('adi')
                ->get();
        } catch (\Throwable $e) {
            $yoneticiler = collect();
        }

        return view('admin.randevu.calisan-form', [
            'baslik'      => $calisan ? 'Çalışan Düzenleme' : 'Yeni Çalışan',
            'calisan'     => $calisan,
            'yoneticiler' => $yoneticiler,
        ]);
    }

    public function calisanKaydet(Request $request)
    {
        $data = $request->validate([
            'id'              => 'nullable|integer',
            'yonetici_id'     => 'nullable|integer',
            'ad'              => 'required|string|max:190',
            'email'           => 'nullable|email|max:190',
            'telefon'         => 'nullable|string|max:40',
            'mesai_baslangic' => 'nullable|string|max:5',
            'mesai_bitis'     => 'nullable|string|max:5',
            'renk'            => 'nullable|string|max:20',
            'siralama'        => 'nullable|integer',
            'durum'           => 'nullable',
            'foto'            => 'nullable|image|max:4096',
        ]);

        $kayit = [
            'yonetici_id'     => $data['yonetici_id'] ?? null,
            'ad'              => $data['ad'],
            'email'           => $data['email'] ?? null,
            'telefon'         => $data['telefon'] ?? null,
            'mesai_baslangic' => $data['mesai_baslangic'] ?? null,
            'mesai_bitis'     => $data['mesai_bitis'] ?? null,
            'renk'            => $data['renk'] ?: '#f59e0b',
            'siralama'        => $data['siralama'] ?? 0,
            'durum'           => $request->boolean('durum') ? 1 : 0,
            'updated_at'      => now(),
        ];

        // Avatar yükleme
        if ($request->hasFile('foto')) {
            $dir = public_path('uploads/randevu-calisanlar');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $file = $request->file('foto');
            $name = 'calisan_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);
            $kayit['foto'] = 'uploads/randevu-calisanlar/' . $name;
        }

        if (!empty($data['id'])) {
            DB::table('randevu_calisanlar')->where('id', $data['id'])->update($kayit);
            $msg = 'Çalışan güncellendi.';
        } else {
            $kayit['created_at'] = now();
            DB::table('randevu_calisanlar')->insert($kayit);
            $msg = 'Çalışan eklendi.';
        }

        return redirect()->route('admin.randevu.calisanlar')->with('success', $msg);
    }

    public function calisanSil($id)
    {
        DB::table('randevu_calisanlar')->where('id', $id)->delete();
        return redirect()->route('admin.randevu.calisanlar')->with('success', 'Çalışan silindi.');
    }

    /* ─────────── AYIN ELEMANI ─────────── */

    /**
     * Ayın Elemanı — kompozit performans puanı.
     * Toplam = İş Puanı (randevu + tamamlanan görev[zorluk/süreye göre] + kanban)
     *        + Erken Teslim Bonusu (görev zamanında/erken bitti mi)
     *        + İyi Dilek Puanı (patronların verdiği).
     */
    public function ayinElemani(Request $request)
    {
        $ay = $request->query('ay');
        try { $d = $ay ? Carbon::createFromFormat('Y-m', $ay) : Carbon::now(); }
        catch (\Throwable $e) { $d = Carbon::now(); }

        $ayKod = $d->format('Y-m');
        $bas = $d->copy()->startOfMonth();
        $bit = $d->copy()->endOfMonth();

        // Puanlama ağırlıkları — DB'den (patron düzenleyebilir), yoksa varsayılan.
        $W = $this->puanAyarlari();

        $calisanlar = DB::table('randevu_calisanlar')
            ->where('durum', 1)->orderBy('siralama')->orderBy('id')->get();

        // İyi dilek puanları (seçili ay)
        $iyiDilek = DB::table('iyi_dilek_puanlari')->where('ay', $ayKod)
            ->select('yonetici_id', DB::raw('SUM(puan) as toplam'))
            ->groupBy('yonetici_id')->pluck('toplam', 'yonetici_id');

        $gunFark = fn ($a, $b) => (int) floor((strtotime((string) $b) - strtotime((string) $a)) / 86400);

        $satir = [];
        foreach ($calisanlar as $c) {
            $yid = $c->yonetici_id;

            // Randevular
            $rdv = DB::table('randevular')->where('calisan_id', $c->id)
                ->where('durum', '!=', 'iptal')->whereBetween('baslangic', [$bas, $bit]);
            $rdvAdet = (clone $rdv)->count();
            $ciro    = (float) (clone $rdv)->sum('fiyat');

            $gorevAdet = 0; $erkenAdet = 0; $erkenGun = 0; $gorevPuan = 0; $kanbanAdet = 0;
            // Görevin ulaştığı her aşama puan kazandırır (her ilerlemede puan)
            $asamaSayi = ['beklemede' => 0, 'devam' => 0, 'musteri_bekleniyor' => 0, 'tamamlandi' => 0];

            if ($yid) {
                // Bu ay TAMAMLANAN görevler (tamamlandi_at'a göre) +
                // bu ay üzerinde ÇALIŞILAN/ilerletilen görevler (updated_at'a göre).
                $gorevler = DB::table('crm_tasks')->where('atanan_id', $yid)
                    ->where(function ($q) use ($bas, $bit) {
                        $q->where(function ($w) use ($bas, $bit) {
                            $w->where('durum', 'tamamlandi')->whereNotNull('tamamlandi_at')
                              ->whereBetween('tamamlandi_at', [$bas, $bit]);
                        })->orWhere(function ($w) use ($bas, $bit) {
                            $w->where('durum', '!=', 'tamamlandi')
                              ->whereBetween('updated_at', [$bas, $bit]);
                        });
                    })
                    ->get(['durum', 'son_tarih', 'tamamlandi_at', 'created_at', 'updated_at']);

                foreach ($gorevler as $g) {
                    $gorevAdet++;
                    // Aşamayı normalize et (bilinmeyen statü = beklemede)
                    $durum = (string) ($g->durum ?: 'beklemede');
                    if (!array_key_exists($durum, $asamaSayi)) $durum = 'beklemede';
                    $asamaSayi[$durum]++;

                    // Ulaşılan aşamanın puanı
                    $gorevPuan += (float) ($W['gorev_' . $durum] ?? 0);

                    // Tamamlanan görevlerde ayrıca efor (zorluk/süre) + erken teslim bonusu
                    if ($durum === 'tamamlandi') {
                        // Efor = planlı süre (oluşturma → son teslim) gün; uzun/zor iş = daha çok puan
                        $efor = $g->son_tarih ? max(1, $gunFark($g->created_at, $g->son_tarih)) : 1;
                        $gorevPuan += min($W['gorev_efor_max'], $efor * $W['gorev_efor_gun']);
                        /*
                         * ERKEN TESLİM BONUSU — TAVANLI (17.08.2026 düzeltmesi)
                         *
                         * SORUN: Eskiden $fark'a hiçbir sınır yoktu. Görevin son
                         * tarihine yanlış YIL yazılınca (örn. 2026 yerine 2027)
                         * görev "364 gün erken" sayılıp 728 bonus puan veriyordu;
                         * o kişi tek bir yazım hatasıyla ayın elemanı oluyordu.
                         * Canlı veride birebir bu yaşandı (bir görevde 2027, bir
                         * başkasında yıl "0026" yazılmıştı).
                         *
                         * ÇÖZÜM: tek görevden gelen erken gün sayısı 'erken_gun_max'
                         * ile sınırlanır (varsayılan 30). Gerçek bir erken teslim
                         * zaten bu aralıkta olur; daha büyük değerler veri hatasıdır.
                         * Not: efor puanında zaten böyle bir tavan vardı, buraya
                         * konmayı unutulmuştu.
                         */
                        if ($g->son_tarih) {
                            $fark = $gunFark($g->tamamlandi_at, $g->son_tarih); // pozitif = erken
                            if ($fark >= 0) {
                                $erkenAdet++;
                                $erkenGun += min($fark, (int) ($W['erken_gun_max'] ?? 30));
                            }
                        }
                    }
                }

                try {
                    $kanbanAdet = DB::table('kanban_cards')->where('atanan_id', $yid)
                        ->where('durum', 'tamamlandi')->whereBetween('updated_at', [$bas, $bit])->count();
                } catch (\Throwable $e) {}
            }

            $isPuani    = $rdvAdet * $W['randevu'] + $gorevPuan + $kanbanAdet * $W['kanban'];
            $erkenBonus = $erkenAdet * $W['erken_taban'] + $erkenGun * $W['erken_gun'];
            $dilek      = (int) round(((int) ($iyiDilek[$yid] ?? 0)) * ($W['iyi_dilek_carpan'] ?? 1));
            $toplam     = $isPuani + $erkenBonus + $dilek;

            $satir[] = (object) [
                'calisan_id' => $c->id, 'yonetici_id' => $yid, 'ad' => $c->ad, 'foto' => $c->foto, 'renk' => $c->renk,
                'randevu' => $rdvAdet, 'ciro' => $ciro, 'gorev' => $gorevAdet, 'asama' => $asamaSayi,
                'erken' => $erkenAdet, 'erken_gun' => $erkenGun, 'kanban' => $kanbanAdet,
                'is_puani' => round($isPuani), 'erken_bonus' => round($erkenBonus), 'iyi_dilek' => $dilek, 'toplam' => round($toplam),
            ];
        }

        usort($satir, fn ($a, $b) => [$b->toplam, $b->erken, $b->gorev] <=> [$a->toplam, $a->erken, $a->gorev]);
        $siralama = collect($satir);

        // Patron (rol=1) iyi dilek puanı verebilir
        $patronMu = (int) session('admin_rol') === 1;
        $dilekGecmis = DB::table('iyi_dilek_puanlari')->where('ay', $ayKod)->orderByDesc('id')->limit(50)->get();
        $calisanAd = $calisanlar->whereNotNull('yonetici_id')->pluck('ad', 'yonetici_id');

        return view('admin.randevu.ayin-elemani', [
            'baslik'      => 'Ayın Elemanı',
            'd'           => $d,
            'ay'          => $ayKod,
            'siralama'    => $siralama,
            'agirliklar'  => $W,
            'puanMeta'    => $this->puanMeta(),
            'patronMu'    => $patronMu,
            'dilekGecmis' => $dilekGecmis,
            'calisanAd'   => $calisanAd,
            'calisanlar'  => $calisanlar->whereNotNull('yonetici_id')->values(),
        ]);
    }

    /** İyi Dilek Puanı ver (yalnızca patron / rol=1) */
    public function iyiDilekVer(Request $request)
    {
        if ((int) session('admin_rol') !== 1) {
            return back()->with('error', 'İyi dilek puanı yalnızca patron tarafından verilebilir.');
        }

        $data = $request->validate([
            'yonetici_id' => 'required|integer',
            'ay'          => 'required|string|max:7',
            'puan'        => 'required|integer|min:1|max:100',
            'aciklama'    => 'nullable|string|max:500',
        ]);

        DB::table('iyi_dilek_puanlari')->insert([
            'yonetici_id' => $data['yonetici_id'],
            'veren_id'    => session('admin_id'),
            'veren_adi'   => session('admin_adi') ?: session('admin_kullanici_adi'),
            'ay'          => $data['ay'],
            'puan'        => $data['puan'],
            'aciklama'    => $data['aciklama'] ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'İyi dilek puanı verildi.');
    }

    /** İyi Dilek Puanı sil (yalnızca patron / rol=1) */
    public function iyiDilekSil($id)
    {
        if ((int) session('admin_rol') !== 1) {
            return back()->with('error', 'Yetkiniz yok.');
        }
        DB::table('iyi_dilek_puanlari')->where('id', $id)->delete();
        return back()->with('success', 'İyi dilek puanı silindi.');
    }

    /* ─────────── PUANLAMA AYARLARI ─────────── */

    /** Varsayılan puanlama ağırlıkları */
    private function puanVarsayilan(): array
    {
        return [
            'randevu'                  => 5,   // her randevu
            'gorev_beklemede'          => 2,   // görev oluştu / beklemede
            'gorev_devam'              => 5,   // üzerinde çalışılıyor
            'gorev_musteri_bekleniyor' => 8,   // teslim edildi, müşteri bekleniyor
            'gorev_tamamlandi'         => 12,  // tamamlandı (taban)
            'gorev_efor_gun'           => 3,   // tamamlanan görevin planlı süresi (gün) başına
            'gorev_efor_max'           => 40,  // görev efor puanı tavanı (uzun/zor iş)
            'kanban'                   => 8,   // tamamlanan kanban kartı
            'erken_taban'              => 5,   // zamanında/erken teslim başına
            'erken_gun'                => 2,   // erken teslim edilen gün başına
            'erken_gun_max'            => 30,  // TEK görevde sayılacak en fazla erken gün (bkz. NOT)
            'iyi_dilek_carpan'         => 1,   // iyi dilek puanı çarpanı
        ];
    }

    /** Form etiketleri + gruplar (puan ayar paneli için) */
    private function puanMeta(): array
    {
        return [
            'randevu'                  => ['etiket' => 'Randevu (her biri)',            'grup' => 'Randevu'],
            'gorev_beklemede'          => ['etiket' => 'Görev — Beklemede',            'grup' => 'Görev Aşamaları'],
            'gorev_devam'              => ['etiket' => 'Görev — Devam ediyor',         'grup' => 'Görev Aşamaları'],
            'gorev_musteri_bekleniyor' => ['etiket' => 'Görev — Müşteri Bekleniyor',   'grup' => 'Görev Aşamaları'],
            'gorev_tamamlandi'         => ['etiket' => 'Görev — Tamamlandı (taban)',   'grup' => 'Görev Aşamaları'],
            'gorev_efor_gun'           => ['etiket' => 'Görev efor — gün başına',      'grup' => 'Zorluk / Efor'],
            'gorev_efor_max'           => ['etiket' => 'Görev efor — tavan',           'grup' => 'Zorluk / Efor'],
            'kanban'                   => ['etiket' => 'Kanban kartı (tamamlanan)',    'grup' => 'Diğer'],
            'erken_taban'              => ['etiket' => 'Erken/zamanında teslim',       'grup' => 'Erken Teslim'],
            'erken_gun'                => ['etiket' => 'Erken teslim — gün başına',    'grup' => 'Erken Teslim'],
            'erken_gun_max'            => ['etiket' => 'Erken teslim — gün tavanı',    'grup' => 'Erken Teslim'],
            'iyi_dilek_carpan'         => ['etiket' => 'İyi Dilek puanı çarpanı',      'grup' => 'Diğer'],
        ];
    }

    /** Etkin ağırlıklar: DB'de kayıtlı değer varsa onu, yoksa varsayılanı kullan */
    private function puanAyarlari(): array
    {
        $W = $this->puanVarsayilan();
        try {
            if (Schema::hasTable('performans_puan_ayarlari')) {
                $kayitli = DB::table('performans_puan_ayarlari')->pluck('deger', 'anahtar');
                foreach ($kayitli as $anahtar => $deger) {
                    if (array_key_exists($anahtar, $W)) {
                        $W[$anahtar] = (float) $deger;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Puan ayarları okunamadı', ['e' => $e->getMessage()]);
        }
        return $W;
    }

    /** Ayar tablosu yoksa oluştur (migration'a bağımlı kalmadan kendini onarır) */
    private function ayarTablosuGarantile(): void
    {
        if (Schema::hasTable('performans_puan_ayarlari')) return;
        Schema::create('performans_puan_ayarlari', function ($table) {
            $table->id();
            $table->string('anahtar', 60)->unique();
            $table->decimal('deger', 8, 2)->default(0);
            $table->string('guncelleyen_adi', 120)->nullable();
            $table->timestamps();
        });
    }

    /** Puanlama ağırlıklarını kaydet (yalnızca patron / rol=1) */
    public function puanAyarKaydet(Request $request)
    {
        if ((int) session('admin_rol') !== 1) {
            return back()->with('error', 'Puanlama ayarlarını yalnızca patron değiştirebilir.');
        }

        $meta   = $this->puanMeta();
        $girilen = (array) $request->input('puan', []);
        $kimAdi  = session('admin_adi') ?: session('admin_kullanici_adi');

        try {
            $this->ayarTablosuGarantile();

            foreach ($meta as $anahtar => $bilgi) {
                if (!array_key_exists($anahtar, $girilen)) continue;
                $deger = (float) str_replace(',', '.', (string) $girilen[$anahtar]);
                if ($deger < 0)    $deger = 0;
                if ($deger > 9999) $deger = 9999;

                DB::table('performans_puan_ayarlari')->updateOrInsert(
                    ['anahtar' => $anahtar],
                    ['deger' => $deger, 'guncelleyen_adi' => $kimAdi, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Puan ayarları kaydedilemedi', ['e' => $e->getMessage()]);
            return back()->with('error', 'Ayarlar kaydedilemedi: ' . $e->getMessage());
        }

        return back()->with('success', 'Puanlama ayarları güncellendi.');
    }

    /** Puanlama ağırlıklarını varsayılana döndür (yalnızca patron / rol=1) */
    public function puanAyarSifirla()
    {
        if ((int) session('admin_rol') !== 1) {
            return back()->with('error', 'Yetkiniz yok.');
        }
        try {
            if (Schema::hasTable('performans_puan_ayarlari')) {
                DB::table('performans_puan_ayarlari')->delete();
            }
        } catch (\Throwable $e) {}
        return back()->with('success', 'Puanlama ayarları varsayılana döndürüldü.');
    }
}