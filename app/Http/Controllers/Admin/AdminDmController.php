<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\PresenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDmController extends Controller
{
    /** Geçerli oturum yöneticisinin id'si. */
    private function benId(): int
    {
        return (int) session('admin_id');
    }

    /** Profil foto yolunu tam URL'ye çevir (yoksa null). */
    private function fotoUrl(?string $yol): ?string
    {
        if (!$yol) return null;
        if (preg_match('#^https?://#', $yol)) return $yol;
        return url(ltrim($yol, '/'));
    }

    /**
     * DM yapılabilecek yöneticiler (adminler arası).
     * Aktif yöneticiler; kendisi ve bayi (rol 3) hariç. Stajyer (rol 4) dahildir.
     */
    private function kisiler(): \Illuminate\Support\Collection
    {
        $ben = $this->benId();

        $liste = DB::table('yoneticiler')
            ->where('durum', 1)
            ->where('id', '!=', $ben)
            ->whereNotIn('rol', [3])
            ->orderBy('adi')
            ->get(['id', 'adi', 'kullaniciadi', 'rol', 'profil_foto', 'son_gorulme', 'son_etkinlik']);

        // Her kişi için son mesaj + okunmamış sayısı
        foreach ($liste as $k) {
            $sonMesaj = DB::table('admin_dm_mesajlar')
                ->where(function ($q) use ($ben, $k) {
                    $q->where('gonderen_id', $ben)->where('alici_id', $k->id);
                })
                ->orWhere(function ($q) use ($ben, $k) {
                    $q->where('gonderen_id', $k->id)->where('alici_id', $ben);
                })
                ->orderByDesc('id')
                ->first(['mesaj', 'created_at', 'gonderen_id']);

            $k->son_mesaj      = $sonMesaj->mesaj ?? null;
            $k->son_mesaj_ben  = $sonMesaj ? ((int) $sonMesaj->gonderen_id === $ben) : false;
            $k->son_mesaj_at   = $sonMesaj->created_at ?? null;
            $k->son_mesaj_zaman = ($sonMesaj && $sonMesaj->created_at)
                ? \Carbon\Carbon::parse($sonMesaj->created_at)->locale('tr')->diffForHumans()
                : null;
            $k->okunmamis      = DB::table('admin_dm_mesajlar')
                ->where('gonderen_id', $k->id)
                ->where('alici_id', $ben)
                ->where('okundu', 0)
                ->count();
            $k->adgosterim     = $k->adi ?: $k->kullaniciadi;
            $k->foto           = $this->fotoUrl($k->profil_foto ?? null);
            $k->durum          = PresenceController::durum($k->son_gorulme ?? null, $k->son_etkinlik ?? null);
        }

        // Son mesajı olanlar üste, sonra isim
        return $liste->sortByDesc(fn ($k) => $k->son_mesaj_at ?? '0')->values();
    }

    /** AJAX: kişi listesi (sağ-alt widget için). */
    public function kisilerJson()
    {
        $liste = $this->kisiler()->map(function ($k) {
            return [
                'id'            => (int) $k->id,
                'ad'            => $k->adgosterim,
                'foto'          => $k->foto,
                'durum'         => $k->durum,
                'son_mesaj'     => $k->son_mesaj,
                'son_mesaj_ben' => (bool) $k->son_mesaj_ben,
                'zaman'         => $k->son_mesaj_zaman,
                'okunmamis'     => (int) $k->okunmamis,
            ];
        })->values();

        return response()->json(['success' => true, 'kisiler' => $liste]);
    }

    /** DM ana ekranı (sol kişi listesi + sağ boş durum). */
    public function index()
    {
        $kisiler = $this->kisiler();
        $aktif = null;
        $mesajlar = collect();
        return view('admin.dm.index', compact('kisiler', 'aktif', 'mesajlar'));
    }

    /** Belirli bir yöneticiyle konuşma ekranı. */
    public function konusma($id)
    {
        $ben = $this->benId();
        $id = (int) $id;

        $aktif = DB::table('yoneticiler')->where('id', $id)->first(['id', 'adi', 'kullaniciadi', 'rol', 'profil_foto', 'son_gorulme', 'son_etkinlik']);
        if (!$aktif) {
            return redirect()->route('admin.dm.index')->with('error', 'Kişi bulunamadı.');
        }
        $aktif->adgosterim = $aktif->adi ?: $aktif->kullaniciadi;
        $aktif->foto = $this->fotoUrl($aktif->profil_foto ?? null);
        $aktif->durum = PresenceController::durum($aktif->son_gorulme ?? null, $aktif->son_etkinlik ?? null);

        // Bu kişiden bana gelenleri okundu işaretle
        DB::table('admin_dm_mesajlar')
            ->where('gonderen_id', $id)
            ->where('alici_id', $ben)
            ->where('okundu', 0)
            ->update(['okundu' => 1, 'okundu_at' => now()]);

        $mesajlar = $this->ikiliMesajlar($ben, $id);
        $kisiler  = $this->kisiler();

        return view('admin.dm.index', compact('kisiler', 'aktif', 'mesajlar'));
    }

    /** AJAX: konuşmadaki mesajları getir (polling). Okundu işaretler. */
    public function mesajlar(Request $request, $id)
    {
        $ben = $this->benId();
        $id = (int) $id;

        DB::table('admin_dm_mesajlar')
            ->where('gonderen_id', $id)
            ->where('alici_id', $ben)
            ->where('okundu', 0)
            ->update(['okundu' => 1, 'okundu_at' => now()]);

        $after = (int) $request->get('after', 0);
        $mesajlar = $this->ikiliMesajlar($ben, $id, $after);

        // Karşı tarafın okuduğu en son MESAJIM (mavi tik için)
        $okunanSonId = (int) DB::table('admin_dm_mesajlar')
            ->where('gonderen_id', $ben)
            ->where('alici_id', $id)
            ->where('okundu', 1)
            ->max('id');

        return response()->json([
            'success'       => true,
            'mesajlar'      => $mesajlar,
            'okunan_son_id' => $okunanSonId,
        ]);
    }

    /** AJAX: mesaj gönder. */
    public function gonder(Request $request)
    {
        $ben = $this->benId();

        $validated = $request->validate([
            'alici_id' => 'required|integer|exists:yoneticiler,id',
            'mesaj'    => 'required_without:dosya|nullable|string|max:5000',
            'dosya'    => 'nullable|file|max:102400', // 100 MB (video dahil)
            'yanit_id' => 'nullable|integer',
        ]);

        $aliciId = (int) $validated['alici_id'];
        if ($aliciId === $ben) {
            return response()->json(['success' => false, 'message' => 'Kendinize mesaj gönderemezsiniz.'], 422);
        }

        // Yanıtlanan mesaj — sadece bu iki kişi arasındaki bir mesaj olabilir
        $yanitId = null;
        if (!empty($validated['yanit_id'])) {
            $yanitId = DB::table('admin_dm_mesajlar')
                ->where('id', (int) $validated['yanit_id'])
                ->where(function ($w) use ($ben, $aliciId) {
                    $w->where(function ($q) use ($ben, $aliciId) {
                        $q->where('gonderen_id', $ben)->where('alici_id', $aliciId);
                    })->orWhere(function ($q) use ($ben, $aliciId) {
                        $q->where('gonderen_id', $aliciId)->where('alici_id', $ben);
                    });
                })
                ->value('id');
        }

        // Spam önleme: alıcının bu kişiden okunmamış mesajı zaten varsa tekrar mail atma.
        $oncedenOkunmamis = DB::table('admin_dm_mesajlar')
            ->where('gonderen_id', $ben)
            ->where('alici_id', $aliciId)
            ->where('okundu', 0)
            ->exists();

        $mesajMetni = trim((string) ($validated['mesaj'] ?? ''));

        // Dosya yükleme — doğrudan public/uploads/dm (symlink'e gerek yok, Windows'ta sağlam)
        $dosyaYol = null; $dosyaAd = null; $dosyaTip = null;
        if ($request->hasFile('dosya')) {
            $f = $request->file('dosya');
            $dosyaAd  = $f->getClientOriginalName();
            $dosyaTip = $f->getMimeType();
            $ext = $f->getClientOriginalExtension() ?: 'bin';
            $ad = bin2hex(random_bytes(8)) . '.' . $ext;
            $f->move(public_path('uploads/dm'), $ad);
            $dosyaYol = 'uploads/dm/' . $ad;
        }

        $id = DB::table('admin_dm_mesajlar')->insertGetId([
            'gonderen_id' => $ben,
            'alici_id'    => $aliciId,
            'mesaj'       => $mesajMetni,
            'yanit_id'    => $yanitId,
            'dosya'       => $dosyaYol,
            'dosya_ad'    => $dosyaAd,
            'dosya_tip'   => $dosyaTip,
            'okundu'      => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $row = DB::table('admin_dm_mesajlar')->where('id', $id)->first();

        // Alıcıya e-posta bildirimi (yanıttan sonra, lag olmadan)
        if (!$oncedenOkunmamis) {
            $mailMetni = $mesajMetni !== '' ? $mesajMetni : ('📎 ' . ($dosyaAd ?: 'Dosya') . ' gönderdi');
            $this->mailBildir($aliciId, $ben, $mailMetni);
        }

        return response()->json([
            'success' => true,
            'mesaj'   => $this->mesajFormat($row, $ben),
        ]);
    }

    /** AJAX: kendi mesajını sil. */
    public function mesajSil($mid)
    {
        $ben = $this->benId();
        $row = DB::table('admin_dm_mesajlar')->where('id', (int) $mid)->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Mesaj bulunamadı.'], 404);
        }
        if ((int) $row->gonderen_id !== $ben) {
            return response()->json(['success' => false, 'message' => 'Sadece kendi mesajınızı silebilirsiniz.'], 403);
        }

        DB::table('admin_dm_mesajlar')->where('id', (int) $mid)->delete();
        // Bu mesaja yapılan yanıt referanslarını temizle (kırık alıntı kalmasın)
        DB::table('admin_dm_mesajlar')->where('yanit_id', (int) $mid)->update(['yanit_id' => null]);

        return response()->json(['success' => true, 'id' => (int) $mid]);
    }

    /** AJAX: kendi mesajını düzenle. */
    public function mesajDuzenle(Request $request, $mid)
    {
        $ben = $this->benId();
        $validated = $request->validate(['mesaj' => 'required|string|max:5000']);

        $row = DB::table('admin_dm_mesajlar')->where('id', (int) $mid)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Mesaj bulunamadı.'], 404);
        }
        if ((int) $row->gonderen_id !== $ben) {
            return response()->json(['success' => false, 'message' => 'Sadece kendi mesajınızı düzenleyebilirsiniz.'], 403);
        }

        DB::table('admin_dm_mesajlar')->where('id', (int) $mid)->update([
            'mesaj'      => trim($validated['mesaj']),
            'duzenlendi' => 1,
            'updated_at' => now(),
        ]);

        $yeni = DB::table('admin_dm_mesajlar')->where('id', (int) $mid)->first();
        return response()->json(['success' => true, 'mesaj' => $this->mesajFormat($yeni, $ben)]);
    }

    /** AJAX: bir mesajı başka bir yöneticiye ilet. */
    public function ilet(Request $request)
    {
        $ben = $this->benId();
        $validated = $request->validate([
            'kaynak_id' => 'required|integer',
            'alici_id'  => 'required|integer|exists:yoneticiler,id',
        ]);

        $aliciId = (int) $validated['alici_id'];
        if ($aliciId === $ben) {
            return response()->json(['success' => false, 'message' => 'Kendinize iletemezsiniz.'], 422);
        }

        // Kaynak mesaj — sadece benim dahil olduğum bir konuşmadan iletilebilir
        $kaynak = DB::table('admin_dm_mesajlar')
            ->where('id', (int) $validated['kaynak_id'])
            ->where(function ($w) use ($ben) {
                $w->where('gonderen_id', $ben)->orWhere('alici_id', $ben);
            })
            ->first();

        if (!$kaynak) {
            return response()->json(['success' => false, 'message' => 'İletilecek mesaj bulunamadı.'], 404);
        }

        $id = DB::table('admin_dm_mesajlar')->insertGetId([
            'gonderen_id' => $ben,
            'alici_id'    => $aliciId,
            'mesaj'       => $kaynak->mesaj,
            'iletildi'    => 1,
            'dosya'       => $kaynak->dosya,
            'dosya_ad'    => $kaynak->dosya_ad,
            'dosya_tip'   => $kaynak->dosya_tip,
            'okundu'      => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $oncedenOkunmamis = DB::table('admin_dm_mesajlar')
            ->where('gonderen_id', $ben)->where('alici_id', $aliciId)
            ->where('okundu', 0)->where('id', '!=', $id)->exists();
        if (!$oncedenOkunmamis) {
            $metin = trim((string) $kaynak->mesaj) !== '' ? $kaynak->mesaj : ('📎 ' . ($kaynak->dosya_ad ?: 'Dosya'));
            $this->mailBildir($aliciId, $ben, $metin);
        }

        $row = DB::table('admin_dm_mesajlar')->where('id', $id)->first();
        return response()->json(['success' => true, 'mesaj' => $this->mesajFormat($row, $ben)]);
    }

    /** Alıcıya "yeni mesaj" e-postası gönder (HTTP yanıtından sonra çalışır). */
    private function mailBildir(int $aliciId, int $gonderenId, string $mesaj): void
    {
        $alici = DB::table('yoneticiler')->where('id', $aliciId)->first(['adi', 'email', 'eposta']);
        if (!$alici) return;
        $email = $alici->email ?: ($alici->eposta ?? null);
        if (!$email) return;

        $gonderen = DB::table('yoneticiler')->where('id', $gonderenId)->first(['adi', 'kullaniciadi']);
        $gonderenAd = $gonderen ? ($gonderen->adi ?: $gonderen->kullaniciadi) : 'Bir yönetici';
        $aliciAd = $alici->adi ?: 'Yönetici';
        $kisaMesaj = \Illuminate\Support\Str::limit($mesaj, 300);
        $panelUrl = route('admin.dm.index');

        dispatch(function () use ($email, $aliciAd, $gonderenAd, $kisaMesaj, $panelUrl) {
            try {
                $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;border:1px solid #eee;border-radius:12px;overflow:hidden">'
                    . '<div style="background:#b8b62e;color:#1a1a1a;padding:16px 20px;font-weight:700;font-size:16px">💬 Yeni Mesajınız Var</div>'
                    . '<div style="padding:20px;color:#222;font-size:14px;line-height:1.6">'
                    . 'Merhaba <strong>' . e($aliciAd) . '</strong>,<br><br>'
                    . '<strong>' . e($gonderenAd) . '</strong> size bir mesaj gönderdi:<br>'
                    . '<div style="background:#f6f6f2;border-left:3px solid #b8b62e;padding:12px 14px;border-radius:6px;margin:12px 0;color:#333">' . nl2br(e($kisaMesaj)) . '</div>'
                    . '<a href="' . e($panelUrl) . '" style="display:inline-block;background:#b8b62e;color:#1a1a1a;text-decoration:none;font-weight:700;padding:10px 18px;border-radius:8px;margin-top:8px">Mesajı Görüntüle</a>'
                    . '</div>'
                    . '<div style="padding:12px 20px;background:#fafafa;color:#999;font-size:12px;border-top:1px solid #eee">DN Kreatif İş Ortağım — bu otomatik bir bildirimdir.</div>'
                    . '</div>';

                \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($email, $gonderenAd) {
                    $m->to($email)->subject($gonderenAd . ' size mesaj gönderdi');
                });
            } catch (\Throwable $e) {
                \Log::warning('DM mail bildirimi gönderilemedi: ' . $e->getMessage());
            }
        });
    }

    /** AJAX: bana gelen toplam okunmamış sayısı (sidebar rozeti). */
    public function okunmamis()
    {
        $sayi = DB::table('admin_dm_mesajlar')
            ->where('alici_id', $this->benId())
            ->where('okundu', 0)
            ->count();

        return response()->json(['count' => $sayi]);
    }

    /** İki yönetici arasındaki mesajlar (opsiyonel after id'den sonrakiler). */
    private function ikiliMesajlar(int $ben, int $id, int $after = 0): array
    {
        // ÖNEMLİ: konuşma koşulunu kendi parantezine al, yoksa `id > after`
        // filtresi sadece OR'un ikinci dalına uygulanır (operatör önceliği) ve
        // kişinin kendi mesajları her poll'da tekrar döner.
        $q = DB::table('admin_dm_mesajlar')
            ->where(function ($w) use ($ben, $id) {
                $w->where(function ($q) use ($ben, $id) {
                    $q->where('gonderen_id', $ben)->where('alici_id', $id);
                })->orWhere(function ($q) use ($ben, $id) {
                    $q->where('gonderen_id', $id)->where('alici_id', $ben);
                });
            });

        if ($after > 0) {
            $q->where('id', '>', $after);
        }

        return $q->orderBy('id')->get()
            ->map(fn ($row) => $this->mesajFormat($row, $ben))
            ->all();
    }

    /** Tek mesajı JSON formatına çevir. */
    private function mesajFormat($row, int $ben): array
    {
        $dosya = $row->dosya ?? null;

        // Ses tespiti: MIME audio/* OLABİLİR ama .webm bazen video/webm algılanır.
        // Bu uygulamada ses dosyaları webm/ogg/m4a/mp3... olduğundan uzantıdan da bak.
        $uzanti  = $dosya ? strtolower(pathinfo($dosya, PATHINFO_EXTENSION)) : '';
        $sesUzant = ['webm', 'weba', 'ogg', 'oga', 'mp3', 'm4a', 'wav', 'aac'];
        $sesMi = (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'audio/') === 0)
                 || in_array($uzanti, $sesUzant, true);

        // Yanıtlanan mesajın küçük önizlemesi
        $yanit = null;
        if (!empty($row->yanit_id)) {
            $y = DB::table('admin_dm_mesajlar')->where('id', (int) $row->yanit_id)->first();
            if ($y) {
                $kim = ((int) $y->gonderen_id === $ben) ? 'Sen' : null;
                if ($kim === null) {
                    $g = DB::table('yoneticiler')->where('id', $y->gonderen_id)->first(['adi', 'kullaniciadi']);
                    $kim = $g ? ($g->adi ?: $g->kullaniciadi) : 'Yönetici';
                }
                $onizleme = trim((string) $y->mesaj) !== ''
                    ? \Illuminate\Support\Str::limit($y->mesaj, 80)
                    : ('📎 ' . ($y->dosya_ad ?: 'Dosya'));
                $yanit = ['id' => (int) $y->id, 'kim' => $kim, 'mesaj' => $onizleme];
            }
        }

        return [
            'id'         => (int) $row->id,
            'mesaj'      => $row->mesaj,
            'ben'        => ((int) $row->gonderen_id === $ben),
            'okundu'     => (int) ($row->okundu ?? 0),
            'dosya'      => $dosya ? url($dosya) : null,
            'dosya_ad'   => $row->dosya_ad ?? null,
            'resim'      => (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'image/') === 0),
            'video'      => (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'video/') === 0) && !$sesMi,
            'ses'        => $sesMi,
            'saat'       => $row->created_at ? date('H:i', strtotime($row->created_at)) : '',
            'tarih'      => $row->created_at ? date('d.m.Y', strtotime($row->created_at)) : '',
            'duzenlendi' => (int) ($row->duzenlendi ?? 0),
            'iletildi'   => (int) ($row->iletildi ?? 0),
            'yanit'      => $yanit,
        ];
    }
}