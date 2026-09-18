<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

/**
 * Data Center — toplu müşteri içe aktarma.
 * İlk satır başlık olmalı. Tanınan başlıklar (büyük/küçük + Türkçe esnek):
 *   Ad / Adı / Ad Firma / Firma, Email / E-posta, Telefon / Tel,
 *   Sektör, İl / Şehir, İlçe, Ünvan, Kategori, Adres
 * Excel (.xlsx) ve ; ayraçlı CSV (Türkçe Excel varsayılanı) desteklenir.
 */
class DataCenterMusteriImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public int $eklenen = 0;
    public int $atlanan = 0;
    /** @var int[] İçe aktarılanların ekleneceği liste id'leri */
    public array $listeIds = [];

    public function __construct(array $listeIds = [])
    {
        $this->listeIds = array_values(array_filter(array_map('intval', $listeIds)));
    }

    public function getCsvSettings(): array
    {
        // Türkçe Excel CSV'yi ; ile kaydeder; dışa aktar şablonumuz da ; kullanır.
        return ['delimiter' => ';', 'input_encoding' => 'UTF-8'];
    }

    public function collection(Collection $rows): void
    {
        $listePivotVar = \Illuminate\Support\Facades\Schema::hasTable('crm_customer_liste');

        foreach ($rows as $row) {
            $ad = $this->al($row, ['ad', 'adi', 'ad_firma', 'firma', 'ad_soyad', 'isim']);
            if ($ad === '') { $this->atlanan++; continue; }

            $kayit = [
                'adi'      => mb_substr($ad, 0, 190),
                'email'    => $this->al($row, ['email', 'eposta', 'e_posta', 'mail']) ?: null,
                'telefon'  => $this->al($row, ['telefon', 'tel', 'cep', 'gsm']) ?: null,
                'sektor'   => $this->al($row, ['sektor', 'sektör']) ?: null,
                'il'       => $this->al($row, ['il', 'sehir', 'şehir']) ?: null,
                'ilce'     => $this->al($row, ['ilce', 'ilçe']) ?: null,
                'unvan'    => $this->al($row, ['unvan', 'ünvan', 'firma_unvani']) ?: null,
                'kategori' => $this->al($row, ['kategori']) ?: null,
                'adres'    => $this->al($row, ['adres', 'address']) ?: null,
                'durum'    => 'pasif',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $id = DB::table('crm_customers')->insertGetId($kayit);

            if ($id && $listePivotVar && !empty($this->listeIds)) {
                foreach ($this->listeIds as $lid) {
                    DB::table('crm_customer_liste')->updateOrInsert(
                        ['customer_id' => $id, 'liste_id' => $lid],
                        ['kaynak' => 'import', 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }

            $this->eklenen++;
        }
    }

    /** Satırdan ilk dolu eşleşen başlığı oku (boşlukları temizler). */
    private function al($row, array $anahtarlar): string
    {
        foreach ($anahtarlar as $k) {
            if (isset($row[$k]) && trim((string) $row[$k]) !== '') {
                return trim((string) $row[$k]);
            }
        }
        return '';
    }
}
