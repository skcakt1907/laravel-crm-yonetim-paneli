<?php

namespace App\Console\Commands;

use App\Services\VoiceSmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Süresine 7 gün (veya daha az) kalan domainlerin MÜŞTERİLERİNİ NetGSM TTS ile
 * otomatik arar ve "domain yenileme ödemeniz yaklaştı" mesajını sesli iletir.
 * Aynı müşteri aynı gün tekrar aranmaz (domain_yenileme_log, kaynak='sesli:...').
 */
class DomainSesliAramaCommand extends Command
{
    protected $signature = 'domain:sesli-ara
        {--dry : Sadece raporla, arama yapma}
        {--gun=7 : Kaç gün ve altı kalanlar aransın}';

    protected $description = '7 gün içinde bitecek domainlerin müşterilerini NetGSM TTS ile sesli arar';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $gun = max(1, (int) $this->option('gun') ?: 7);

        $bugun    = Carbon::today();
        $sonTarih = $bugun->copy()->addDays($gun);

        // uyeler telefon sütununu tespit et
        $telSut = null;
        $uCols = Schema::getColumnListing('uyeler');
        foreach (['gsm', 'telefon', 'tel', 'cep'] as $aday) {
            if (in_array($aday, $uCols, true)) { $telSut = $aday; break; }
        }
        if (!$telSut) {
            $this->error('uyeler tablosunda telefon sütunu bulunamadı.');
            return 1;
        }

        $hepsi = collect();

        // 1) Online domainler
        if (Schema::hasTable('domain_orders')) {
            $hepsi = $hepsi->concat(
                DB::table('domain_orders as d')
                    ->leftJoin('uyeler as u', 'u.id', '=', 'd.user_id')
                    ->whereNotNull('d.expires_at')
                    ->whereDate('d.expires_at', '>=', $bugun->toDateString())
                    ->whereDate('d.expires_at', '<=', $sonTarih->toDateString())
                    ->whereIn('d.status', ['active', 'registered'])
                    ->whereNotNull("u.$telSut")->where("u.$telSut", '!=', '')
                    ->select('d.id', 'd.domain', DB::raw('d.expires_at as bitis'),
                        'u.ad', 'u.soyad', DB::raw("u.$telSut as telefon"), DB::raw("'order' as kaynak"))
                    ->get()
            );
        }

        // 2) Manuel domainler (satilanlar tipi=3)
        if (Schema::hasTable('satilanlar')) {
            $cols = Schema::getColumnListing('satilanlar');
            $bitisSut = in_array('bitis_tarih', $cols, true) ? 'bitis_tarih'
                      : (in_array('bitis_tarihi', $cols, true) ? 'bitis_tarihi' : null);
            if ($bitisSut) {
                $hepsi = $hepsi->concat(
                    DB::table('satilanlar as s')
                        ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                        ->where('s.tipi', '3')->where('s.durum', 1)
                        ->whereNotNull("s.$bitisSut")
                        ->whereDate("s.$bitisSut", '>=', $bugun->toDateString())
                        ->whereDate("s.$bitisSut", '<=', $sonTarih->toDateString())
                        ->whereNotNull("u.$telSut")->where("u.$telSut", '!=', '')
                        ->select('s.id', 's.domain', DB::raw("s.$bitisSut as bitis"),
                            'u.ad', 'u.soyad', DB::raw("u.$telSut as telefon"), DB::raw("'manuel' as kaynak"))
                        ->get()
                );
            }
        }

        $logVar = Schema::hasTable('domain_yenileme_log');
        $aranan = 0; $atlanan = 0; $hata = 0;

        $this->info("{$gun} gün içinde bitecek (telefonlu) domain: {$hepsi->count()}");

        foreach ($hepsi as $d) {
            $kalan = (int) $bugun->diffInDays(Carbon::parse($d->bitis)->startOfDay(), false);
            $logKaynak = 'sesli:' . ($d->kaynak ?? 'order');

            // Aynı gün zaten arandı mı?
            if ($logVar) {
                $zaten = DB::table('domain_yenileme_log')
                    ->where('domain_order_id', $d->id)->where('kaynak', $logKaynak)
                    ->where('gonderim_tarihi', $bugun->toDateString())->exists();
                if ($zaten) { $atlanan++; continue; }
            }

            $ad = trim(($d->ad ?? '') . ' ' . ($d->soyad ?? '')) ?: 'Değerli müşterimiz';
            $metin = "Sayın {$ad}, {$d->domain} alan adınızın süresi {$kalan} gün sonra doluyor. "
                   . "Kesintisiz hizmet için yenileme işleminizi tamamlamak üzere lütfen bizimle iletişime geçiniz. "
                   . "İş Ortağım, iyi günler diler.";

            if ($dry) {
                $this->line("  [DRY] ☎ {$d->telefon} | {$d->domain} | {$kalan}g | {$ad}");
                $aranan++;
                continue;
            }

            $ok = VoiceSmsService::tts($d->telefon, $metin);
            if ($logVar) {
                DB::table('domain_yenileme_log')->insert([
                    'domain_order_id' => $d->id, 'kaynak' => $logKaynak,
                    'email' => $d->telefon, 'kalan_gun' => $kalan,
                    'gonderim_tarihi' => $bugun->toDateString(),
                    'durum' => $ok ? 'ok' : 'fail',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if ($ok) { $aranan++; $this->info("  ☎ arandı: {$d->telefon} ({$d->domain})"); }
            else     { $hata++;   $this->error("  ✗ hata: {$d->telefon} ({$d->domain})"); }
        }

        $this->info("\n===== ÖZET =====");
        $this->info("☎ Arandı : {$aranan}");
        $this->info("↻ Atlandı : {$atlanan} (bugün zaten arandı)");
        $this->info("✗ Hata    : {$hata}");
        $this->info($dry ? '[DRY-RUN — gerçek arama yapılmadı]' : '[GERÇEK]');

        return 0;
    }
}
