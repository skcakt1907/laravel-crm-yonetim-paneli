<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MADDE 7: Duyurular modülü.
 * Yöneticiden yöneticiye (admin -> admin) duyuru. Müşterilere DEĞİL (onlar için toplu mail var).
 * Tüm yöneticiler duyuru oluşturabilir. Hedef: tüm yöneticiler VEYA seçili yöneticiler.
 * Her duyuru: uygulama-içi bildirim (admin_bildirimler) + e-posta (NotificationMailer) gönderir.
 */
class DuyuruController extends Controller
{
    /** Duyuru hedefi olan yönetici rolleri: tüm gerçek yönetici rolleri (müşteri=4, bayi=3 hariç) */
    private const YONETICI_ROLLERI = [1, 2, 5, 19, 20, 21];

    /**
     * Hedef alınabilecek yöneticileri getirir (aktif, gerçek yönetici rolleri).
     */
    private function hedefYoneticiler()
    {
        $query = DB::table('yoneticiler')->where('durum', 1);

        if (Schema::hasColumn('yoneticiler', 'rol')) {
            $query->whereIn('rol', self::YONETICI_ROLLERI);
        }

        return $query->orderBy('adi')->get();
    }

    /**
     * Geçmiş duyurular listesi.
     */
    public function index()
    {
        $duyurular = collect();

        if (Schema::hasTable('duyurular')) {
            $duyurular = DB::table('duyurular')
                ->orderByDesc('id')
                ->paginate(20);
        }

        return view('admin.duyurular.index', compact('duyurular'));
    }

    /**
     * Yeni duyuru oluşturma formu.
     */
    public function olustur()
    {
        $yoneticiler = $this->hedefYoneticiler();
        return view('admin.duyurular.olustur', compact('yoneticiler'));
    }

    /**
     * Duyuruyu kaydet + bildirim + mail gönder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'icerik' => 'required|string',
            'hedef_tip' => 'required|in:tum,secili',
            'alicilar' => 'nullable|array',
            'alicilar.*' => 'integer',
            'mail_gonder' => 'nullable',
        ], [
            'baslik.required' => 'Başlık zorunludur.',
            'icerik.required' => 'İçerik zorunludur.',
            'hedef_tip.required' => 'Hedef seçiniz.',
        ]);

        if (!Schema::hasTable('duyurular') || !Schema::hasTable('duyuru_alicilari')) {
            return back()->withInput()->with('error', 'Duyuru tabloları bulunamadı. Lütfen SQL kurulumunu çalıştırın.');
        }

        $hedefTip = $validated['hedef_tip'];
        $mailGonder = $request->boolean('mail_gonder');

        // Hedef yöneticileri belirle
        $tumYoneticiler = $this->hedefYoneticiler();

        if ($hedefTip === 'secili') {
            $secili = $validated['alicilar'] ?? [];
            if (empty($secili)) {
                return back()->withInput()->with('error', 'En az bir yönetici seçmelisiniz.');
            }
            $hedefler = $tumYoneticiler->whereIn('id', $secili)->values();
        } else {
            $hedefler = $tumYoneticiler;
        }

        if ($hedefler->isEmpty()) {
            return back()->withInput()->with('error', 'Gönderilecek yönetici bulunamadı.');
        }

        $olusturanId = session('admin_id');
        $olusturanAdi = session('admin_adi') ?? session('admin_kullaniciadi') ?? null;

        DB::beginTransaction();
        try {
            // 1) Duyuruyu kaydet
            $duyuruId = DB::table('duyurular')->insertGetId([
                'baslik' => $validated['baslik'],
                'icerik' => $validated['icerik'],
                'hedef_tip' => $hedefTip,
                'mail_gonderildi' => $mailGonder ? 1 : 0,
                'olusturan_id' => $olusturanId,
                'olusturan_adi' => $olusturanAdi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) Her alıcı için: alıcı kaydı + uygulama-içi bildirim
            $alabHasYoneticiId = Schema::hasColumn('admin_bildirimler', 'yonetici_id');
            $now = now();

            foreach ($hedefler as $y) {
                // duyuru_alicilari
                DB::table('duyuru_alicilari')->insert([
                    'duyuru_id' => $duyuruId,
                    'yonetici_id' => $y->id,
                    'okundu' => 0,
                    'created_at' => $now,
                ]);

                // admin_bildirimler (uygulama-içi)
                $bildirim = [
                    'tip' => 'duyuru',
                    'baslik' => 'Duyuru: ' . $validated['baslik'],
                    'mesaj' => mb_substr(strip_tags($validated['icerik']), 0, 250),
                    'ilgili_id' => $duyuruId,
                    'ilgili_tablo' => 'duyurular',
                    'okundu' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($alabHasYoneticiId) {
                    $bildirim['yonetici_id'] = $y->id;
                }
                DB::table('admin_bildirimler')->insert($bildirim);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Duyuru kaydı hatası', ['err' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Duyuru kaydedilirken hata oluştu: ' . $e->getMessage());
        }

        // 3) E-posta gönder (transaction dışında, fail-safe)
        $mailSayisi = 0;
        if ($mailGonder) {
            foreach ($hedefler as $y) {
                $email = $y->email ?? $y->eposta ?? null;
                if (empty($email)) {
                    continue;
                }
                $ad = $y->adi ?? $email;
                $body = "<p>Merhaba <strong>{$ad}</strong>,</p>"
                    . "<p>Yeni bir duyuru yayınlandı:</p>"
                    . "<div style='background:#f8fafc;border-left:4px solid #b8b62e;padding:16px;border-radius:8px;margin:16px 0'>"
                    . "<strong style='font-size:16px'>" . e($validated['baslik']) . "</strong>"
                    . "<div style='margin-top:10px;color:#334155;line-height:1.6'>" . nl2br(e($validated['icerik'])) . "</div>"
                    . "</div>"
                    . ($olusturanAdi ? "<p style='font-size:13px;color:#64748b'>Gönderen: {$olusturanAdi}</p>" : '');

                $gonderildi = NotificationMailer::send($email, 'Duyuru: ' . $validated['baslik'], $body, $ad);
                if ($gonderildi) {
                    $mailSayisi++;
                }
            }
        }

        $mesaj = $hedefler->count() . ' yöneticiye duyuru gönderildi.';
        if ($mailGonder) {
            $mesaj .= " ({$mailSayisi} e-posta iletildi.)";
        }

        return redirect()->route('admin.duyurular.index')->with('success', $mesaj);
    }

    /**
     * Duyuru detayı.
     */
    public function goster($id)
    {
        $duyuru = DB::table('duyurular')->where('id', $id)->first();
        if (!$duyuru) {
            return redirect()->route('admin.duyurular.index')->with('error', 'Duyuru bulunamadı.');
        }

        // Alıcılar + okundu durumu
        $alicilar = DB::table('duyuru_alicilari as da')
            ->leftJoin('yoneticiler as y', 'y.id', '=', 'da.yonetici_id')
            ->where('da.duyuru_id', $id)
            ->select('da.*', 'y.adi as yonetici_adi', 'y.email as yonetici_email')
            ->get();

        return view('admin.duyurular.goster', compact('duyuru', 'alicilar'));
    }

    /**
     * Duyuru sil (ilişkili alıcı + bildirim kayıtlarıyla birlikte).
     */
    public function sil($id)
    {
        if (!Schema::hasTable('duyurular')) {
            return back();
        }

        DB::beginTransaction();
        try {
            DB::table('duyuru_alicilari')->where('duyuru_id', $id)->delete();
            DB::table('admin_bildirimler')
                ->where('ilgili_tablo', 'duyurular')
                ->where('ilgili_id', $id)
                ->delete();
            DB::table('duyurular')->where('id', $id)->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Silme hatası: ' . $e->getMessage());
        }

        return redirect()->route('admin.duyurular.index')->with('success', 'Duyuru silindi.');
    }
}