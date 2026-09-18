<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin tarafı: müşteri DM — HAVUZ (shared inbox).
 * TÜM yöneticiler tüm müşteri mesajlarını görür/cevaplar.
 * Konuşma = uye_id. Admin mesajında yonetici_id = cevaplayan admin.
 * musteri_dm_atama: bir müşteriyle hangi adminin "ilgilendiği" (üstlendiği).
 */
class MusteriDmController extends Controller
{
    private function benId(): int
    {
        return (int) session('admin_id');
    }

    private function fotoUrl(?string $yol): ?string
    {
        if (!$yol) return null;
        if (preg_match('#^https?://#', $yol)) return $yol;
        return url(ltrim($yol, '/'));
    }

    private function uyeAd($u): string
    {
        $ad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
        if ($ad !== '') return $ad;
        return $u->firmaadi ?: ($u->kullanici_adi ?: ($u->email ?: ('Üye #' . $u->id)));
    }

    /** Bir müşterinin atanan (ilgilenen) yöneticisi [id, ad] ya da null. */
    private function atama(int $uyeId): ?array
    {
        $a = DB::table('musteri_dm_atama')->where('uye_id', $uyeId)->first();
        if (!$a) return null;
        $y = DB::table('yoneticiler')->where('id', $a->yonetici_id)->first(['adi', 'kullaniciadi']);
        return [
            'id'  => (int) $a->yonetici_id,
            'ad'  => $y ? ($y->adi ?: ($y->kullaniciadi ?: 'Yönetici')) : 'Yönetici',
            'ben' => ((int) $a->yonetici_id === $this->benId()),
        ];
    }

    /** Havuzdaki tüm müşteriler (en az 1 mesajı olan), son mesaj + okunmamış + atama. */
    private function kisiler(): \Illuminate\Support\Collection
    {
        $uyeIdler = DB::table('musteri_dm_mesajlar')->distinct()->pluck('uye_id');
        if ($uyeIdler->isEmpty()) return collect();

        $uyeler = DB::table('uyeler')->whereIn('id', $uyeIdler)
            ->get(['id', 'ad', 'soyad', 'firmaadi', 'kullanici_adi', 'email', 'profil_foto']);

        foreach ($uyeler as $k) {
            $sonMesaj = DB::table('musteri_dm_mesajlar')->where('uye_id', $k->id)
                ->orderByDesc('id')->first(['mesaj', 'created_at', 'gonderen']);
            $k->son_mesaj      = $sonMesaj->mesaj ?? null;
            $k->son_mesaj_ben  = $sonMesaj ? ($sonMesaj->gonderen === 'admin') : false;
            $k->son_mesaj_at   = $sonMesaj->created_at ?? null;
            $k->son_mesaj_zaman = ($sonMesaj && $sonMesaj->created_at)
                ? \Carbon\Carbon::parse($sonMesaj->created_at)->locale('tr')->diffForHumans() : null;
            $k->okunmamis = DB::table('musteri_dm_mesajlar')->where('uye_id', $k->id)
                ->where('gonderen', 'uye')->where('okundu', 0)->count();
            $k->adgosterim = $this->uyeAd($k);
            $k->foto       = $this->fotoUrl($k->profil_foto ?? null);
            $k->durum      = 'cevrimdisi';
            $k->atama      = $this->atama($k->id);
        }

        return $uyeler->sortByDesc(fn ($k) => $k->son_mesaj_at ?? '0')->values();
    }

    public function kisilerJson()
    {
        $liste = $this->kisiler()->map(function ($k) {
            return [
                'id' => (int) $k->id, 'ad' => $k->adgosterim, 'foto' => $k->foto, 'durum' => $k->durum,
                'son_mesaj' => $k->son_mesaj, 'son_mesaj_ben' => (bool) $k->son_mesaj_ben,
                'zaman' => $k->son_mesaj_zaman, 'okunmamis' => (int) $k->okunmamis,
                'atanan' => $k->atama['ad'] ?? null, 'atanan_ben' => $k->atama['ben'] ?? false,
            ];
        })->values();
        return response()->json(['success' => true, 'kisiler' => $liste]);
    }

    /** Yeni sohbet için müşteri ara. */
    public function ara(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = DB::table('uyeler')->select('id', 'ad', 'soyad', 'firmaadi', 'kullanici_adi', 'email', 'profil_foto');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('ad', 'like', "%$q%")->orWhere('soyad', 'like', "%$q%")
                  ->orWhere('firmaadi', 'like', "%$q%")->orWhere('kullanici_adi', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%");
            });
        }
        $liste = $query->orderByDesc('id')->limit(20)->get()->map(function ($u) {
            return ['id' => (int) $u->id, 'ad' => $this->uyeAd($u), 'foto' => $this->fotoUrl($u->profil_foto ?? null),
                'durum' => 'cevrimdisi', 'son_mesaj' => null, 'son_mesaj_ben' => false, 'zaman' => null, 'okunmamis' => 0];
        })->values();
        return response()->json(['success' => true, 'kisiler' => $liste]);
    }

    public function index()
    {
        $kisiler = $this->kisiler();
        $aktif = null;
        return view('admin.musteri-dm.index', compact('kisiler', 'aktif'));
    }

    public function konusma($id)
    {
        $ben = $this->benId();
        $id = (int) $id;

        $u = DB::table('uyeler')->where('id', $id)->first(['id', 'ad', 'soyad', 'firmaadi', 'kullanici_adi', 'email', 'profil_foto']);
        if (!$u) return redirect()->route('admin.musteri-dm.index')->with('error', 'Müşteri bulunamadı.');

        $aktif = (object) [
            'id' => $u->id, 'adgosterim' => $this->uyeAd($u), 'foto' => $this->fotoUrl($u->profil_foto ?? null),
            'durum' => 'cevrimdisi', 'email' => $u->email, 'atama' => $this->atama($id),
        ];

        // Müşteriden gelenleri okundu işaretle (ortak — herkes için)
        DB::table('musteri_dm_mesajlar')->where('uye_id', $id)
            ->where('gonderen', 'uye')->where('okundu', 0)
            ->update(['okundu' => 1, 'okundu_at' => now()]);

        $kisiler = $this->kisiler();
        return view('admin.musteri-dm.index', compact('kisiler', 'aktif'));
    }

    public function mesajlar(Request $request, $id)
    {
        $ben = $this->benId();
        $id = (int) $id;

        DB::table('musteri_dm_mesajlar')->where('uye_id', $id)
            ->where('gonderen', 'uye')->where('okundu', 0)
            ->update(['okundu' => 1, 'okundu_at' => now()]);

        $after = (int) $request->get('after', 0);
        $mesajlar = $this->thread($id, $after);

        $okunanSonId = (int) DB::table('musteri_dm_mesajlar')->where('uye_id', $id)
            ->where('gonderen', 'admin')->where('okundu', 1)->max('id');

        return response()->json([
            'success' => true, 'mesajlar' => $mesajlar, 'okunan_son_id' => $okunanSonId,
            'atama' => $this->atama($id),
        ]);
    }

    public function gonder(Request $request)
    {
        $ben = $this->benId();
        $validated = $request->validate([
            'alici_id' => 'required|integer|exists:uyeler,id',
            'mesaj'    => 'required_without:dosya|nullable|string|max:5000',
            'dosya'    => 'nullable|file|max:102400',
            'yanit_id' => 'nullable|integer',
        ]);
        $uyeId = (int) $validated['alici_id'];

        $yanitId = null;
        if (!empty($validated['yanit_id'])) {
            $yanitId = DB::table('musteri_dm_mesajlar')->where('id', (int) $validated['yanit_id'])
                ->where('uye_id', $uyeId)->value('id');
        }

        $oncedenOkunmamis = DB::table('musteri_dm_mesajlar')->where('uye_id', $uyeId)
            ->where('gonderen', 'admin')->where('okundu', 0)->exists();

        $mesajMetni = trim((string) ($validated['mesaj'] ?? ''));

        $dosyaYol = null; $dosyaAd = null; $dosyaTip = null;
        if ($request->hasFile('dosya')) {
            $f = $request->file('dosya');
            $dosyaAd = $f->getClientOriginalName(); $dosyaTip = $f->getMimeType();
            $ext = $f->getClientOriginalExtension() ?: 'bin';
            $ad = bin2hex(random_bytes(8)) . '.' . $ext;
            $f->move(public_path('uploads/dm'), $ad);
            $dosyaYol = 'uploads/dm/' . $ad;
        }

        $id = DB::table('musteri_dm_mesajlar')->insertGetId([
            'uye_id' => $uyeId, 'yonetici_id' => $ben, 'gonderen' => 'admin',
            'mesaj' => $mesajMetni, 'yanit_id' => $yanitId,
            'dosya' => $dosyaYol, 'dosya_ad' => $dosyaAd, 'dosya_tip' => $dosyaTip,
            'okundu' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Cevap yazınca: kimse üstlenmemişse otomatik bu admin üstlenir
        if (!DB::table('musteri_dm_atama')->where('uye_id', $uyeId)->exists()) {
            DB::table('musteri_dm_atama')->insert(['uye_id' => $uyeId, 'yonetici_id' => $ben, 'updated_at' => now()]);
        }

        $row = DB::table('musteri_dm_mesajlar')->where('id', $id)->first();

        if (!$oncedenOkunmamis) {
            $mailMetni = $mesajMetni !== '' ? $mesajMetni : ('📎 ' . ($dosyaAd ?: 'Dosya') . ' gönderdi');
            $this->mailBildir($uyeId, $ben, $mailMetni);
        }

        return response()->json(['success' => true, 'mesaj' => $this->mesajFormat($row, $ben)]);
    }

    public function mesajSil($mid)
    {
        $ben = $this->benId();
        $row = DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->first();
        if (!$row) return response()->json(['success' => false, 'message' => 'Mesaj bulunamadı.'], 404);
        if ($row->gonderen !== 'admin' || (int) $row->yonetici_id !== $ben) {
            return response()->json(['success' => false, 'message' => 'Sadece kendi mesajınızı silebilirsiniz.'], 403);
        }
        DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->delete();
        DB::table('musteri_dm_mesajlar')->where('yanit_id', (int) $mid)->update(['yanit_id' => null]);
        return response()->json(['success' => true, 'id' => (int) $mid]);
    }

    public function mesajDuzenle(Request $request, $mid)
    {
        $ben = $this->benId();
        $validated = $request->validate(['mesaj' => 'required|string|max:5000']);
        $row = DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->first();
        if (!$row) return response()->json(['success' => false, 'message' => 'Mesaj bulunamadı.'], 404);
        if ($row->gonderen !== 'admin' || (int) $row->yonetici_id !== $ben) {
            return response()->json(['success' => false, 'message' => 'Sadece kendi mesajınızı düzenleyebilirsiniz.'], 403);
        }
        DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->update([
            'mesaj' => trim($validated['mesaj']), 'duzenlendi' => 1, 'updated_at' => now(),
        ]);
        $yeni = DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->first();
        return response()->json(['success' => true, 'mesaj' => $this->mesajFormat($yeni, $ben)]);
    }

    /** Bu müşteriyi üstlen (ilgilen). */
    public function ustlen($id)
    {
        $id = (int) $id;
        DB::table('musteri_dm_atama')->updateOrInsert(
            ['uye_id' => $id],
            ['yonetici_id' => $this->benId(), 'updated_at' => now()]
        );
        return response()->json(['success' => true, 'atama' => $this->atama($id)]);
    }

    /** Üstlenmeyi bırak. */
    public function birak($id)
    {
        DB::table('musteri_dm_atama')->where('uye_id', (int) $id)->delete();
        return response()->json(['success' => true, 'atama' => null]);
    }

    /** Tüm müşterilerden okunmamış (ortak rozet). */
    public function okunmamis()
    {
        $sayi = DB::table('musteri_dm_mesajlar')->where('gonderen', 'uye')->where('okundu', 0)->count();
        return response()->json(['count' => $sayi]);
    }

    private function mailBildir(int $uyeId, int $yoneticiId, string $mesaj): void
    {
        $uye = DB::table('uyeler')->where('id', $uyeId)->first(['ad', 'soyad', 'email']);
        if (!$uye || empty($uye->email)) return;
        $yon = DB::table('yoneticiler')->where('id', $yoneticiId)->first(['adi', 'kullaniciadi']);
        $gonderenAd = $yon ? ($yon->adi ?: $yon->kullaniciadi) : 'Yönetim';
        $aliciAd = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: 'Müşterimiz';
        $kisaMesaj = \Illuminate\Support\Str::limit($mesaj, 300);
        $panelUrl = route('hesabim');
        dispatch(function () use ($uye, $aliciAd, $gonderenAd, $kisaMesaj, $panelUrl) {
            try {
                $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;border:1px solid #eee;border-radius:12px;overflow:hidden">'
                    . '<div style="background:#4f46e5;color:#fff;padding:16px 20px;font-weight:700;font-size:16px">💬 Yeni Mesajınız Var</div>'
                    . '<div style="padding:20px;color:#222;font-size:14px;line-height:1.6">'
                    . 'Merhaba <strong>' . e($aliciAd) . '</strong>,<br><br>'
                    . '<strong>' . e($gonderenAd) . '</strong> size bir mesaj gönderdi:<br>'
                    . '<div style="background:#f6f6f2;border-left:3px solid #4f46e5;padding:12px 14px;border-radius:6px;margin:12px 0;color:#333">' . nl2br(e($kisaMesaj)) . '</div>'
                    . '<a href="' . e($panelUrl) . '" style="display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;font-weight:700;padding:10px 18px;border-radius:8px;margin-top:8px">Panele Git</a>'
                    . '</div>'
                    . '<div style="padding:12px 20px;background:#fafafa;color:#999;font-size:12px;border-top:1px solid #eee">DN Kreatif İş Ortağım — bu otomatik bir bildirimdir.</div>'
                    . '</div>';
                \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($uye, $gonderenAd) {
                    $m->to($uye->email)->subject($gonderenAd . ' size mesaj gönderdi');
                });
            } catch (\Throwable $e) {
                \Log::warning('Müşteri DM (admin) mail gönderilemedi: ' . $e->getMessage());
            }
        })->afterResponse();
    }

    private function thread(int $uyeId, int $after = 0): array
    {
        $q = DB::table('musteri_dm_mesajlar')->where('uye_id', $uyeId);
        if ($after > 0) $q->where('id', '>', $after);
        return $q->orderBy('id')->get()->map(fn ($row) => $this->mesajFormat($row, $this->benId()))->all();
    }

    /** $ben = bakan yönetici id. */
    private function mesajFormat($row, int $ben): array
    {
        $dosya = $row->dosya ?? null;
        $uzanti = $dosya ? strtolower(pathinfo($dosya, PATHINFO_EXTENSION)) : '';
        $sesUzant = ['webm', 'weba', 'ogg', 'oga', 'mp3', 'm4a', 'wav', 'aac'];
        $sesMi = (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'audio/') === 0) || in_array($uzanti, $sesUzant, true);

        $adminMi = ($row->gonderen === 'admin');
        $benim   = $adminMi && ((int) $row->yonetici_id === $ben);

        // Ekip içi atıf: başka adminin mesajıysa adını göster (kendiminkinde gösterme)
        $gonderenAd = null;
        if ($adminMi && !$benim && !empty($row->yonetici_id)) {
            $y = DB::table('yoneticiler')->where('id', $row->yonetici_id)->first(['adi', 'kullaniciadi']);
            if ($y) $gonderenAd = $y->adi ?: ($y->kullaniciadi ?: 'Yönetici');
        }

        $yanit = null;
        if (!empty($row->yanit_id)) {
            $y = DB::table('musteri_dm_mesajlar')->where('id', (int) $row->yanit_id)->first();
            if ($y) {
                $kim = ($y->gonderen === 'admin') ? 'Yönetim' : 'Müşteri';
                $onizleme = trim((string) $y->mesaj) !== '' ? \Illuminate\Support\Str::limit($y->mesaj, 80) : ('📎 ' . ($y->dosya_ad ?: 'Dosya'));
                $yanit = ['id' => (int) $y->id, 'kim' => $kim, 'mesaj' => $onizleme];
            }
        }

        return [
            'id' => (int) $row->id, 'mesaj' => $row->mesaj,
            'ben' => $adminMi,          // sağ tarafta (ekip/giden)
            'benim' => $benim,          // düzenle/sil yetkisi
            'gonderen_ad' => $gonderenAd,
            'okundu' => (int) ($row->okundu ?? 0),
            'dosya' => $dosya ? url($dosya) : null, 'dosya_ad' => $row->dosya_ad ?? null,
            'resim' => (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'image/') === 0),
            'video' => (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'video/') === 0) && !$sesMi,
            'ses' => $sesMi,
            'saat' => $row->created_at ? date('H:i', strtotime($row->created_at)) : '',
            'tarih' => $row->created_at ? date('d.m.Y', strtotime($row->created_at)) : '',
            'duzenlendi' => (int) ($row->duzenlendi ?? 0), 'iletildi' => (int) ($row->iletildi ?? 0),
            'yanit' => $yanit,
        ];
    }
}
