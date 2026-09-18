<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Müşteri (üye) paneli tarafı DM — HAVUZ (shared inbox) modeli.
 * Müşterinin TEK bir konuşması var: "Yönetim" ile. Admin seçmez.
 * Konuşma = uye_id. Müşteri mesajlarında yonetici_id = NULL.
 * Admin mesajlarında yonetici_id = cevaplayan adminin id'si (gösterim için).
 */
class MusteriDmController extends Controller
{
    /** Geçerli oturum müşterisinin (üye) id'si. */
    private function benId(): int
    {
        return (int) (auth()->guard('uye')->id() ?? 0);
    }

    /** Tam sayfa görünüm. */
    public function index()
    {
        return view('tema.mesajlarim');
    }

    /** AJAX: konuşmadaki mesajları getir (polling). Admin mesajlarını okundu işaretler. */
    public function konusma(Request $request)
    {
        $ben = $this->benId();

        DB::table('musteri_dm_mesajlar')
            ->where('uye_id', $ben)
            ->where('gonderen', 'admin')
            ->where('okundu', 0)
            ->update(['okundu' => 1, 'okundu_at' => now()]);

        $after = (int) $request->get('after', 0);
        $mesajlar = $this->thread($ben, $after);

        // Yönetimin okuduğu en son MESAJIM (mavi tik)
        $okunanSonId = (int) DB::table('musteri_dm_mesajlar')
            ->where('uye_id', $ben)
            ->where('gonderen', 'uye')
            ->where('okundu', 1)
            ->max('id');

        return response()->json([
            'success'       => true,
            'mesajlar'      => $mesajlar,
            'okunan_son_id' => $okunanSonId,
        ]);
    }

    /** AJAX: mesaj gönder (havuza). */
    public function gonder(Request $request)
    {
        $ben = $this->benId();

        $validated = $request->validate([
            'mesaj'    => 'required_without:dosya|nullable|string|max:5000',
            'dosya'    => 'nullable|file|max:102400',
            'yanit_id' => 'nullable|integer',
        ]);

        // Yanıtlanan mesaj — sadece bu müşterinin konuşmasından olabilir
        $yanitId = null;
        if (!empty($validated['yanit_id'])) {
            $yanitId = DB::table('musteri_dm_mesajlar')
                ->where('id', (int) $validated['yanit_id'])
                ->where('uye_id', $ben)
                ->value('id');
        }

        // Spam önleme: bu müşteriden okunmamış mesaj zaten varsa tekrar mail atma.
        $oncedenOkunmamis = DB::table('musteri_dm_mesajlar')
            ->where('uye_id', $ben)
            ->where('gonderen', 'uye')
            ->where('okundu', 0)
            ->exists();

        $mesajMetni = trim((string) ($validated['mesaj'] ?? ''));

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

        $id = DB::table('musteri_dm_mesajlar')->insertGetId([
            'uye_id'      => $ben,
            'yonetici_id' => null,          // havuz: belirli admin yok
            'gonderen'    => 'uye',
            'mesaj'       => $mesajMetni,
            'yanit_id'    => $yanitId,
            'dosya'       => $dosyaYol,
            'dosya_ad'    => $dosyaAd,
            'dosya_tip'   => $dosyaTip,
            'okundu'      => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $row = DB::table('musteri_dm_mesajlar')->where('id', $id)->first();

        if (!$oncedenOkunmamis) {
            $mailMetni = $mesajMetni !== '' ? $mesajMetni : ('📎 ' . ($dosyaAd ?: 'Dosya') . ' gönderdi');
            $this->mailBildir($ben, $mailMetni);
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
        $row = DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Mesaj bulunamadı.'], 404);
        }
        if ((int) $row->uye_id !== $ben || $row->gonderen !== 'uye') {
            return response()->json(['success' => false, 'message' => 'Sadece kendi mesajınızı silebilirsiniz.'], 403);
        }

        DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->delete();
        DB::table('musteri_dm_mesajlar')->where('yanit_id', (int) $mid)->update(['yanit_id' => null]);

        return response()->json(['success' => true, 'id' => (int) $mid]);
    }

    /** AJAX: kendi mesajını düzenle. */
    public function mesajDuzenle(Request $request, $mid)
    {
        $ben = $this->benId();
        $validated = $request->validate(['mesaj' => 'required|string|max:5000']);

        $row = DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Mesaj bulunamadı.'], 404);
        }
        if ((int) $row->uye_id !== $ben || $row->gonderen !== 'uye') {
            return response()->json(['success' => false, 'message' => 'Sadece kendi mesajınızı düzenleyebilirsiniz.'], 403);
        }

        DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->update([
            'mesaj'      => trim($validated['mesaj']),
            'duzenlendi' => 1,
            'updated_at' => now(),
        ]);

        $yeni = DB::table('musteri_dm_mesajlar')->where('id', (int) $mid)->first();
        return response()->json(['success' => true, 'mesaj' => $this->mesajFormat($yeni, $ben)]);
    }

    /** AJAX: okunmamış (Yönetim'den gelen) mesaj sayısı. */
    public function okunmamis()
    {
        $sayi = DB::table('musteri_dm_mesajlar')
            ->where('uye_id', $this->benId())
            ->where('gonderen', 'admin')
            ->where('okundu', 0)
            ->count();

        return response()->json(['count' => $sayi]);
    }

    /** Yeni müşteri mesajında ilgili yöneticilere e-posta. */
    private function mailBildir(int $uyeId, string $mesaj): void
    {
        // Atanmış admin varsa sadece ona; yoksa tüm aktif yöneticilere (bayi hariç).
        $atama = DB::table('musteri_dm_atama')->where('uye_id', $uyeId)->value('yonetici_id');
        $q = DB::table('yoneticiler')->where('durum', 1)->whereNotIn('rol', [3]);
        if ($atama) $q->where('id', $atama);
        $aliciIds = $q->pluck('id');
        if ($aliciIds->isEmpty()) return;

        $alicilar = DB::table('yoneticiler')->whereIn('id', $aliciIds)
            ->get(['adi', 'kullaniciadi', 'email', 'eposta']);

        $uye = DB::table('uyeler')->where('id', $uyeId)->first(['ad', 'soyad', 'firmaadi']);
        $gonderenAd = $uye ? (trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ($uye->firmaadi ?: 'Bir müşteri')) : 'Bir müşteri';
        $kisaMesaj = \Illuminate\Support\Str::limit($mesaj, 300);
        $panelUrl = route('admin.musteri-dm.index');

        dispatch(function () use ($alicilar, $gonderenAd, $kisaMesaj, $panelUrl) {
            foreach ($alicilar as $alici) {
                $email = ($alici->email ?? null) ?: ($alici->eposta ?? null);
                if (!$email) continue;
                $aliciAd = $alici->adi ?: ($alici->kullaniciadi ?: 'Yönetici');
                try {
                    $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;border:1px solid #eee;border-radius:12px;overflow:hidden">'
                        . '<div style="background:#4f46e5;color:#fff;padding:16px 20px;font-weight:700;font-size:16px">💬 Müşteriden Yeni Mesaj</div>'
                        . '<div style="padding:20px;color:#222;font-size:14px;line-height:1.6">'
                        . 'Merhaba <strong>' . e($aliciAd) . '</strong>,<br><br>'
                        . '<strong>' . e($gonderenAd) . '</strong> isimli müşteri yönetime bir mesaj gönderdi:<br>'
                        . '<div style="background:#f6f6f2;border-left:3px solid #4f46e5;padding:12px 14px;border-radius:6px;margin:12px 0;color:#333">' . nl2br(e($kisaMesaj)) . '</div>'
                        . '<a href="' . e($panelUrl) . '" style="display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;font-weight:700;padding:10px 18px;border-radius:8px;margin-top:8px">Mesajı Görüntüle</a>'
                        . '</div>'
                        . '<div style="padding:12px 20px;background:#fafafa;color:#999;font-size:12px;border-top:1px solid #eee">DN Kreatif İş Ortağım — bu otomatik bir bildirimdir.</div>'
                        . '</div>';
                    \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($email, $gonderenAd) {
                        $m->to($email)->subject($gonderenAd . ' yönetime mesaj gönderdi');
                    });
                } catch (\Throwable $e) {
                    \Log::warning('Müşteri DM (havuz) mail gönderilemedi: ' . $e->getMessage());
                }
            }
        })->afterResponse();
    }

    /** Müşterinin tüm konuşması. */
    private function thread(int $ben, int $after = 0): array
    {
        $q = DB::table('musteri_dm_mesajlar')->where('uye_id', $ben);
        if ($after > 0) $q->where('id', '>', $after);

        return $q->orderBy('id')->get()
            ->map(fn ($row) => $this->mesajFormat($row, $ben))
            ->all();
    }

    /** Tek mesajı JSON formatına çevir. */
    private function mesajFormat($row, int $ben): array
    {
        $dosya = $row->dosya ?? null;
        $uzanti  = $dosya ? strtolower(pathinfo($dosya, PATHINFO_EXTENSION)) : '';
        $sesUzant = ['webm', 'weba', 'ogg', 'oga', 'mp3', 'm4a', 'wav', 'aac'];
        $sesMi = (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'audio/') === 0)
                 || in_array($uzanti, $sesUzant, true);

        // Admin mesajıysa cevaplayan adminin adı (müşteriye "Yönetim · Ahmet" gösterilir)
        $gonderenAd = null;
        if ($row->gonderen === 'admin') {
            $gonderenAd = 'Yönetim';
            if (!empty($row->yonetici_id)) {
                $y = DB::table('yoneticiler')->where('id', $row->yonetici_id)->first(['adi', 'kullaniciadi']);
                if ($y) $gonderenAd = $y->adi ?: ($y->kullaniciadi ?: 'Yönetim');
            }
        }

        $yanit = null;
        if (!empty($row->yanit_id)) {
            $y = DB::table('musteri_dm_mesajlar')->where('id', (int) $row->yanit_id)->first();
            if ($y) {
                $kim = ($y->gonderen === 'uye') ? 'Sen' : 'Yönetim';
                $onizleme = trim((string) $y->mesaj) !== ''
                    ? \Illuminate\Support\Str::limit($y->mesaj, 80)
                    : ('📎 ' . ($y->dosya_ad ?: 'Dosya'));
                $yanit = ['id' => (int) $y->id, 'kim' => $kim, 'mesaj' => $onizleme];
            }
        }

        return [
            'id'          => (int) $row->id,
            'mesaj'       => $row->mesaj,
            'ben'         => ($row->gonderen === 'uye'),
            'gonderen_ad' => $gonderenAd,
            'okundu'      => (int) ($row->okundu ?? 0),
            'dosya'       => $dosya ? url($dosya) : null,
            'dosya_ad'    => $row->dosya_ad ?? null,
            'resim'       => (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'image/') === 0),
            'video'       => (!empty($row->dosya_tip) && strpos($row->dosya_tip, 'video/') === 0) && !$sesMi,
            'ses'         => $sesMi,
            'saat'        => $row->created_at ? date('H:i', strtotime($row->created_at)) : '',
            'tarih'       => $row->created_at ? date('d.m.Y', strtotime($row->created_at)) : '',
            'duzenlendi'  => (int) ($row->duzenlendi ?? 0),
            'iletildi'    => (int) ($row->iletildi ?? 0),
            'yanit'       => $yanit,
        ];
    }
}
