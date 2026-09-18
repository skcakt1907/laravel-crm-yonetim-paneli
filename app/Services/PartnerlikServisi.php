<?php

namespace App\Services;

use App\Models\Bayi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * DN OFİS PARTNERLİĞİ — tahsilat ve teslimat akışı.
 *
 * Partner 10.000 $ giriş bedeli öder → partnerlik aktifleşir → paket teslim edilir.
 * Paket kalemleri PAKET sabitinde tanımlıdır; partner oluşturulunca otomatik açılır.
 */
class PartnerlikServisi
{
    /** Partnere verilen paket (10.000 $ karşılığı) */
    public const PAKET = [
        'CRM Sistemi',
        'Dahili Telefon Sistemi',
        'Ofis2 Tasarımı',
        'Mimari Destek',
        'Teknoloji Altyapı Desteği',
        'Ofis İçi Telefon (1 adet)',
        'MacBook Pro (1 adet)',
        'Kurumsal Kimlik Kitleri',
        'Tabela',
        'Eğitim Danışmanlığı',
    ];

    /** Partner için teslimat kalemlerini açar (varsa dokunmaz). */
    public static function teslimatlariHazirla(int $bayiId): int
    {
        if (!Schema::hasTable('partner_teslimatlar')) return 0;

        $eklenen = 0;
        foreach (self::PAKET as $i => $kalem) {
            try {
                $varMi = DB::table('partner_teslimatlar')
                    ->where('bayi_id', $bayiId)->where('kalem', $kalem)->exists();
                if ($varMi) continue;

                DB::table('partner_teslimatlar')->insert([
                    'bayi_id' => $bayiId, 'kalem' => $kalem, 'durum' => 'bekliyor',
                    'sira' => $i, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $eklenen++;
            } catch (\Throwable $e) {
                Log::warning('Partner teslimat kalemi eklenemedi', ['kalem' => $kalem, 'hata' => $e->getMessage()]);
            }
        }

        return $eklenen;
    }

    /**
     * Partnerlik bedelini tahsil edildi olarak işaretler ve partnerliği aktifleştirir.
     * @return array{0: bool, 1: string}
     */
    public static function tahsilatKaydet(int $bayiId, ?float $tutar = null, ?string $aciklama = null): array
    {
        $bayi = Bayi::find($bayiId);
        if (!$bayi) return [false, 'Bayi bulunamadı.'];

        if (!$bayi->partnerMi()) {
            return [false, 'Bu bayi ofis partneri değil. Önce bayi tipini "DN Ofis Partnerliği" yapın.'];
        }

        if (!empty($bayi->partnerlik_odendi_at)) {
            return [false, 'Partnerlik bedeli zaten tahsil edilmiş olarak işaretli.'];
        }

        $bedel = $tutar ?? (float) ($bayi->partnerlik_bedeli ?: Bayi::varsayilanBedel('partner'));

        try {
            DB::table('bayiler')->where('id', $bayiId)->update([
                'partnerlik_bedeli'    => $bedel,
                'partnerlik_odendi_at' => now(),
                'durum'                => 1,   // partnerlik aktif
                'onay_durumu'          => 1,
                'updated_at'           => now(),
            ]);

            $eklenen = self::teslimatlariHazirla($bayiId);

            Log::info('Partnerlik bedeli tahsil edildi', [
                'bayi' => $bayiId, 'tutar' => $bedel, 'aciklama' => $aciklama,
            ]);

            try { self::hosgeldinMaili($bayi, $bedel); } catch (\Throwable $e) {}

            return [true, number_format($bedel, 2, ',', '.') . ' $ tahsilat kaydedildi, partnerlik aktifleşti. '
                . ($eklenen ? $eklenen . ' teslimat kalemi açıldı.' : 'Teslimat listesi hazır.')];
        } catch (\Throwable $e) {
            Log::error('Partnerlik tahsilatı kaydedilemedi', ['bayi' => $bayiId, 'hata' => $e->getMessage()]);
            return [false, 'Tahsilat kaydedilemedi: ' . $e->getMessage()];
        }
    }

    /** Bir teslimat kaleminin durumunu günceller. */
    public static function teslimatGuncelle(int $teslimatId, string $durum, array $ek = []): array
    {
        if (!in_array($durum, ['bekliyor', 'hazirlaniyor', 'teslim_edildi', 'iptal'], true)) {
            return [false, 'Geçersiz durum.'];
        }

        $veri = [
            'durum'      => $durum,
            'seri_no'    => $ek['seri_no']  ?? null,
            'aciklama'   => $ek['aciklama'] ?? null,
            'updated_at' => now(),
        ];

        if ($durum === 'teslim_edildi') {
            $veri['teslim_tarihi']  = $ek['teslim_tarihi'] ?? now()->toDateString();
            $veri['teslim_eden_id'] = session('admin_id');
        } else {
            $veri['teslim_tarihi'] = null;
        }

        try {
            DB::table('partner_teslimatlar')->where('id', $teslimatId)->update($veri);
            return [true, 'Teslimat durumu güncellendi.'];
        } catch (\Throwable $e) {
            return [false, 'Güncellenemedi: ' . $e->getMessage()];
        }
    }

    /** Partnerin teslimat özeti: [kalemler, teslim_edilen, toplam, yuzde] */
    public static function ozet(int $bayiId): array
    {
        if (!Schema::hasTable('partner_teslimatlar')) {
            return ['kalemler' => collect(), 'teslim' => 0, 'toplam' => 0, 'yuzde' => 0];
        }

        $kalemler = DB::table('partner_teslimatlar')
            ->where('bayi_id', $bayiId)->orderBy('sira')->orderBy('id')->get();

        $toplam = $kalemler->where('durum', '!=', 'iptal')->count();
        $teslim = $kalemler->where('durum', 'teslim_edildi')->count();

        return [
            'kalemler' => $kalemler,
            'teslim'   => $teslim,
            'toplam'   => $toplam,
            'yuzde'    => $toplam > 0 ? (int) round($teslim / $toplam * 100) : 0,
        ];
    }

    /* ───────────── mail ───────────── */

    private static function hosgeldinMaili(Bayi $bayi, float $bedel): void
    {
        if (!$bayi->uye_id) return;
        $mail = DB::table('uyeler')->where('id', $bayi->uye_id)->value('email');
        if (!$mail) return;

        $kalemler = '';
        foreach (self::PAKET as $k) {
            $kalemler .= '<li style="padding:4px 0;color:#2b2b1f;font-size:14px">' . htmlspecialchars($k) . '</li>';
        }

        $govde = '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:Arial,Helvetica,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 16px"><tr><td align="center">
  <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
    <tr><td style="background:#1a2332;padding:26px 36px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" width="150" alt="DN Kreatif" style="display:block;margin:0 auto 12px">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">DN Ofis Partnerliği 🏢</div>
    </td></tr>
    <tr><td style="height:5px;background:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 10px">
      <h2 style="margin:0 0 10px;font-size:22px;color:#1a1a0e">Aramıza hoş geldiniz!</h2>
      <p style="margin:0 0 16px;color:#6b6f63;font-size:14px;line-height:1.7">
        ' . htmlspecialchars($bayi->firma_adi ?: 'Değerli iş ortağımız') . ', partnerlik bedeliniz
        (' . number_format($bedel, 2, ',', '.') . ' $) tahsil edilmiştir. Partnerliğiniz aktif —
        artık satışlarınızdan <strong>%30 komisyon</strong> kazanıyorsunuz.
      </p>
      <div style="font-size:13px;font-weight:800;color:#8a8718;text-transform:uppercase;margin:18px 0 6px">Paketinize dahil olanlar</div>
      <ul style="margin:0;padding-left:20px">' . $kalemler . '</ul>
      <p style="margin:18px 0 0;color:#6b6f63;font-size:13px;line-height:1.6">
        Teslimatlar hazırlandıkça ekibimiz sizinle iletişime geçecektir.
      </p>
    </td></tr>
    <tr><td style="background:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';

        EmailNotificationService::send($mail, '🏢 DN Ofis Partnerliğiniz aktif — hoş geldiniz!', $govde);
    }
}
