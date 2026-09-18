<?php

namespace App\Helpers;

/**
 * Spreadsheet Şablon Kütüphanesi - RESMİ LUCKYSHEET FORMATI
 * 
 * https://dream-num.github.io/LuckysheetDocs/guide/sheet.html
 * 
 * celldata formatı: [{ r, c, v: { v, m, ...style } }]
 * config: {} (asla null/string olamaz)
 * load: 0 (asla 1 olamaz initialize sırasında)
 */
class SpreadsheetTemplates
{
    public static function list()
    {
        return [
            'bos' => [
                'ad' => 'Boş Tablo',
                'aciklama' => 'Boş bir Excel sayfası ile başla',
                'ikon' => '📄',
                'renk' => '#9ca3af',
            ],
            'tek_sayfa_hepsi' => [
                'ad' => 'TEK SAYFA — Hepsi Yan Yana',
                'aciklama' => '4 tablo aynı sayfada yan yana (Sözleşmeler, Tahsil, Gider+Maaş, Kasa)',
                'ikon' => '🌟',
                'renk' => '#b8b62e',
            ],
            'sozlesmeler' => [
                'ad' => 'Sözleşmeler Tablosu',
                'aciklama' => 'Müşteri sözleşmelerini periyot ve tutar ile takip et',
                'ikon' => '📅',
                'renk' => '#1e3a8a',
            ],
            'tahsil_edilen' => [
                'ad' => 'Tahsil Edilen Aylık Gelir',
                'aciklama' => 'Aylık tahsil edilen gelirleri kayıt et',
                'ikon' => '💸',
                'renk' => '#059669',
            ],
            'gider_maas' => [
                'ad' => 'Aylık Gider + Maaş Tablosu',
                'aciklama' => 'Sabit giderler ve personel maaşları',
                'ikon' => '💰',
                'renk' => '#d97706',
            ],
            'kasa_hareketleri' => [
                'ad' => 'Günlük Kasa Hareketleri',
                'aciklama' => 'Banka ve nakit kasa hareketleri',
                'ikon' => '🏦',
                'renk' => '#0891b2',
            ],
        ];
    }
    
    public static function getTemplate(string $type): array
    {
        switch ($type) {
            case 'sozlesmeler':       return [self::sozlesmeler()];
            case 'tahsil_edilen':     return [self::tahsilEdilen()];
            case 'gider_maas':        return [self::giderMaas()];
            case 'kasa_hareketleri':  return [self::kasaHareketleri()];
            case 'tek_sayfa_hepsi':   return [self::tekSayfaHepsi()];
            default:                  return [self::bos()];
        }
    }
    
    /**
     * Hücre değeri - Luckysheet "v" formatı
     * Resmi örnek:
     * { ct: {fa: "General", t: "g"}, m:"value1", v:"value1" }
     */
    protected static function cellValue($value, $extra = []) {
        $cell = [
            'v' => $value,
            'm' => is_null($value) ? '' : (string)$value,
            'ct' => ['fa' => 'General', 't' => 'g'],
        ];
        return array_merge($cell, $extra);
    }
    
    /**
     * Formül hücresi
     */
    protected static function cellFormula($formula, $extra = []) {
        $cell = [
            'f' => $formula,
            'v' => 0,
            'm' => '0',
            'ct' => ['fa' => 'General', 't' => 'n'],
        ];
        return array_merge($cell, $extra);
    }
    
    /**
     * celldata array elemanı: { r, c, v: cellValue }
     */
    protected static function makeCell($r, $c, $v) {
        return ['r' => $r, 'c' => $c, 'v' => $v];
    }
    
    /**
     * Sheet objesi - resmi FORMAT
     */
    protected static function makeSheet($name, $color, $celldata, $config = []) {
        // config asla null/string olamaz
        if (!is_array($config)) $config = [];
        
        return [
            'name' => $name,
            'color' => $color,
            'index' => 0,
            'order' => 0,
            'status' => 1,
            'row' => 100,
            'column' => 26,
            'load' => 0,           // KRITIK: initialize için 0
            'config' => $config,   // KRITIK: object
            'celldata' => $celldata,
            'zoomRatio' => 1,
            'showGridLines' => 1,
            'defaultRowHeight' => 24,
            'defaultColWidth' => 100,
        ];
    }
    
    // Stiller
    protected static function STYLE_BLUE() { return ['bg' => '#1e3a8a', 'fc' => '#ffffff', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 11]; }
    protected static function STYLE_GREEN() { return ['bg' => '#065f46', 'fc' => '#ffffff', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 11]; }
    protected static function STYLE_YELLOW() { return ['bg' => '#fcd34d', 'fc' => '#000000', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 12]; }
    protected static function STYLE_TEAL() { return ['bg' => '#0891b2', 'fc' => '#ffffff', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 11]; }
    protected static function STYLE_SUB() { return ['bg' => '#dbeafe', 'fc' => '#1e3a8a', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 10]; }
    protected static function STYLE_RED() { return ['fc' => '#dc2626', 'bl' => 1, 'ht' => 2, 'fs' => 12]; }
    
    /**
     * Stilli hücre ekle
     */
    protected static function H($r, $c, $text, $style) {
        return self::makeCell($r, $c, self::cellValue($text, $style));
    }
    
    /**
     * Stilli formül hücresi
     */
    protected static function F($r, $c, $formula, $style = []) {
        $extra = array_merge($style, ['ct' => ['fa' => '#,##0.00', 't' => 'n']]);
        return self::makeCell($r, $c, self::cellFormula($formula, $extra));
    }
    
    // ============================================================
    
    protected static function bos(): array {
        return self::makeSheet('Sayfa1', '', [], []);
    }
    
    // ============================================================
    // SÖZLEŞMELER
    // ============================================================
    
    protected static function sozlesmeler(): array {
        $cd = [];
        $blue = self::STYLE_BLUE();
        $red = self::STYLE_RED();
        
        // Başlıklar
        $cd[] = self::H(0, 0, 'TARİH', $blue);
        $cd[] = self::H(0, 1, 'AÇIKLAMA', $blue);
        $cd[] = self::H(0, 2, 'HİZMET', $blue);
        $cd[] = self::H(0, 3, 'TUTAR', $blue);
        $cd[] = self::H(0, 4, 'sonraki ay', $blue);
        
        $veri = [
            ['1\'inden 1\'ine', 'KAMURAN AKBAY MİMARLIK', 'SEO HİZMETİ'],
            ['5\'inden 5\'ine', 'BOOM STREET FOOD', 'SOSYAL MEDYA YÖNETİMİ'],
            ['5\'inden 5\'ine', 'NETA RESTAURANT', 'SOSYAL MEDYA YÖNETİMİ'],
            ['6\'sından 6\'sına', 'MURAT TOBACCO', 'GENEL HİZMET YÖNETİMİ'],
            ['7\'sinden 7\'sine', 'HCA REAL ESTATE', 'DİJİTAL PAZARLAMA'],
            ['13\'ünden 13\'üne', 'ÖZÜM NAKLİYAT', 'GOOGLE ADS DANIŞMANLIĞI'],
            ['20\'sinden 20\'sine', '', ''],
        ];
        foreach ($veri as $i => $row) {
            $r = $i + 1;
            $cd[] = self::H($r, 0, $row[0], ['fc' => '#1e3a8a', 'ht' => 0, 'fs' => 10]);
            $cd[] = self::H($r, 1, $row[1], ['fc' => '#065f46', 'bl' => 1, 'ht' => 0, 'fs' => 10]);
            $cd[] = self::H($r, 2, $row[2], ['fc' => '#1e3a8a', 'ht' => 0, 'fs' => 10]);
        }
        
        // TOPLAM
        $cd[] = self::H(8, 2, 'TOPLAM TUTAR', $red);
        $cd[] = self::F(8, 3, '=SUM(D2:D8)', $red);
        
        return self::makeSheet('Sözleşmeler', '#1e3a8a', $cd, [
            'columnlen' => (object)['0' => 140, '1' => 260, '2' => 220, '3' => 140, '4' => 100],
            'rowlen' => (object)['0' => 32],
        ]);
    }
    
    // ============================================================
    // TAHSİL EDİLEN
    // ============================================================
    
    protected static function tahsilEdilen(): array {
        $cd = [];
        $green = self::STYLE_GREEN();
        $red = self::STYLE_RED();
        
        $cd[] = self::H(0, 0, 'TARİH', $green);
        $cd[] = self::H(0, 1, 'AÇIKLAMA', $green);
        $cd[] = self::H(0, 2, 'VERİLEN HİZMET', $green);
        $cd[] = self::H(0, 3, 'TUTAR', $green);
        
        $cd[] = self::H(21, 2, 'TOPLAM GELİR', $red);
        $cd[] = self::F(21, 3, '=SUM(D2:D21)', $red);
        
        return self::makeSheet('Tahsil Edilen Gelir', '#065f46', $cd, [
            'columnlen' => (object)['0' => 130, '1' => 240, '2' => 220, '3' => 140],
            'rowlen' => (object)['0' => 32],
        ]);
    }
    
    // ============================================================
    // GİDER + MAAŞ
    // ============================================================
    
    protected static function giderMaas(): array {
        $cd = [];
        $yellow = self::STYLE_YELLOW();
        $sub = self::STYLE_SUB();
        $red = self::STYLE_RED();
        
        // GİDER ana başlık (A1:B1 merge)
        $cd[] = self::makeCell(0, 0, self::cellValue('AYLIK GİDER TABLOSU', array_merge($yellow, [
            'mc' => ['r' => 0, 'c' => 0, 'rs' => 1, 'cs' => 2]
        ])));
        $cd[] = self::makeCell(0, 1, ['mc' => ['r' => 0, 'c' => 0]]);
        
        $giderler = [
            'AYLIK VERGİ GİDERİ', 'AYLIK MUHASEBE ÖDEMESİ', 'EV VE OFİS KİRALARI',
            'YAKIT GİDERİ', 'KREŞ GİDERİ', 'KREDİ GİDERLERİ',
            'ELEKTRİK GİDERİ', 'SU GİDERİ', 'İNTERNET GİDERİ',
            'MUTFAK GİDERİ', 'FREELANCER WEB', 'FREELANCER PRODÜKSİYON',
        ];
        foreach ($giderler as $i => $kat) {
            $cd[] = self::H($i + 1, 0, $kat, $sub);
        }
        $cd[] = self::H(13, 0, 'GİDER TOPLAMI', $red);
        $cd[] = self::F(13, 1, '=SUM(B2:B13)', $red);
        
        // MAAŞ ana başlık (D1:F1 merge - 3 sütun)
        $cd[] = self::makeCell(0, 3, self::cellValue('AYLIK MAAŞ TABLOSU', array_merge($yellow, [
            'mc' => ['r' => 0, 'c' => 3, 'rs' => 1, 'cs' => 3]
        ])));
        $cd[] = self::makeCell(0, 4, ['mc' => ['r' => 0, 'c' => 3]]);
        $cd[] = self::makeCell(0, 5, ['mc' => ['r' => 0, 'c' => 3]]);
        
        $cd[] = self::H(1, 3, 'PERSONEL ADI', $sub);
        $cd[] = self::H(1, 4, 'NET MAAŞ', $sub);
        $cd[] = self::H(1, 5, 'BRÜT MAAŞ', $sub);
        
        $personeller = [
            'Seda BAYKAL', 'Nurseli İNAN', 'Dilan ATEŞ', 'Nesimi ATEŞ',
            'Serap KAYA', 'Deniz TAŞTAN', 'ÇINAR ÇETİN', 'AYKUT YAR',
            'Damla Ceren GÖKSEL',
        ];
        foreach ($personeller as $i => $kisi) {
            $cd[] = self::H($i + 2, 3, $kisi, ['fc' => '#1e3a8a', 'ht' => 0, 'fs' => 10]);
        }
        $cd[] = self::H(11, 3, 'MAAŞ TOPLAMI', $red);
        $cd[] = self::F(11, 4, '=SUM(E3:E11)', $red);
        $cd[] = self::F(11, 5, '=SUM(F3:F11)', $red);
        
        return self::makeSheet('Aylık Gider + Maaş', '#fcd34d', $cd, [
            'columnlen' => (object)['0' => 220, '1' => 130, '2' => 20, '3' => 180, '4' => 130, '5' => 130],
            'rowlen' => (object)['0' => 36],
            'merge' => (object)[
                '0_0' => ['r' => 0, 'c' => 0, 'rs' => 1, 'cs' => 2],
                '0_3' => ['r' => 0, 'c' => 3, 'rs' => 1, 'cs' => 3],
            ],
        ]);
    }
    
    // ============================================================
    // GÜNLÜK KASA
    // ============================================================
    
    protected static function kasaHareketleri(): array {
        $cd = [];
        $teal = self::STYLE_TEAL();
        
        $cd[] = self::H(0, 0, 'TARİH', $teal);
        $cd[] = self::H(0, 1, 'KASA', $teal);
        $cd[] = self::H(0, 2, 'AÇIKLAMA', $teal);
        $cd[] = self::H(0, 3, 'GELEN ÖDEME', $teal);
        $cd[] = self::H(0, 4, 'GİDEN ÖDEME', $teal);
        $cd[] = self::H(0, 5, 'KALAN BAKİYE', $teal);
        
        $ornekler = [
            ['01.01.2026', 'qnb finansbank', 'otobüs bileti', null, 1000],
            ['01.01.2026', 'nakit kasa', 'nesimi ateş virman', 1500, null],
            ['01.01.2026', '', 'yemek', null, 200],
            ['02.01.2026', '', '', 500, null],
            ['02.01.2026', '', '', null, 800],
        ];
        
        foreach ($ornekler as $i => $row) {
            $r = $i + 1;
            $cd[] = self::H($r, 0, $row[0], ['ht' => 0, 'fs' => 10]);
            $cd[] = self::H($r, 1, $row[1], ['ht' => 1, 'fs' => 10]);
            $cd[] = self::H($r, 2, $row[2], ['ht' => 1, 'fs' => 10]);
            if ($row[3] !== null) {
                $cd[] = self::H($r, 3, $row[3], ['ht' => 2, 'fs' => 10, 'ct' => ['fa' => '#,##0.00', 't' => 'n']]);
            }
            if ($row[4] !== null) {
                $cd[] = self::H($r, 4, $row[4], ['ht' => 2, 'fs' => 10, 'ct' => ['fa' => '#,##0.00', 't' => 'n']]);
            }
            
            if ($i === 0) {
                $f = '=IF(D' . ($r + 1) . '="",0,D' . ($r + 1) . ')-IF(E' . ($r + 1) . '="",0,E' . ($r + 1) . ')';
            } else {
                $f = '=F' . ($r) . '+IF(D' . ($r + 1) . '="",0,D' . ($r + 1) . ')-IF(E' . ($r + 1) . '="",0,E' . ($r + 1) . ')';
            }
            $cd[] = self::F($r, 5, $f, ['bl' => 1, 'ht' => 2, 'fs' => 10]);
        }
        
        return self::makeSheet('Günlük Kasa', '#0891b2', $cd, [
            'columnlen' => (object)['0' => 100, '1' => 160, '2' => 240, '3' => 130, '4' => 130, '5' => 150],
            'rowlen' => (object)['0' => 32],
        ]);
    }
    
    // ============================================================
    // TEK SAYFA - HEPSİ YAN YANA (ana şablon)
    // ============================================================
    
    protected static function tekSayfaHepsi(): array {
        $cd = [];
        $merge = [];
        
        $blue = self::STYLE_BLUE();
        $green = self::STYLE_GREEN();
        $yellow = self::STYLE_YELLOW();
        $teal = self::STYLE_TEAL();
        $sub = self::STYLE_SUB();
        $red = self::STYLE_RED();
        
        // ==================================================
        // SATIR 0 - Ana başlıklar (merge ile)
        // Luckysheet kuralı:
        //  - Ana hücre: mc: { r, c, rs, cs }
        //  - Kapsanan hücreler: mc: { r, c } (sadece koordinat)
        //  - config.merge: { "r_c": {r,c,rs,cs} }
        // ==================================================
        
        // SÖZLEŞMELER (A1:E1 - 5 sütun, A0)
        $cd[] = self::makeCell(0, 0, self::cellValue('📅 SÖZLEŞMELER', array_merge($blue, [
            'mc' => ['r' => 0, 'c' => 0, 'rs' => 1, 'cs' => 5]
        ])));
        // Kapsanan: B0, C0, D0, E0
        for ($c = 1; $c <= 4; $c++) {
            $cd[] = self::makeCell(0, $c, ['mc' => ['r' => 0, 'c' => 0]]);
        }
        $merge['0_0'] = ['r' => 0, 'c' => 0, 'rs' => 1, 'cs' => 5];
        
        // TAHSİL (G1:J1 - 4 sütun, G0 başlangıç)
        $cd[] = self::makeCell(0, 6, self::cellValue('💸 TAHSİL EDİLEN GELİR', array_merge($green, [
            'mc' => ['r' => 0, 'c' => 6, 'rs' => 1, 'cs' => 4]
        ])));
        for ($c = 7; $c <= 9; $c++) {
            $cd[] = self::makeCell(0, $c, ['mc' => ['r' => 0, 'c' => 6]]);
        }
        $merge['0_6'] = ['r' => 0, 'c' => 6, 'rs' => 1, 'cs' => 4];
        
        // GİDER (L1:M1 - 2 sütun)
        $cd[] = self::makeCell(0, 11, self::cellValue('💰 AYLIK GİDER', array_merge($yellow, [
            'mc' => ['r' => 0, 'c' => 11, 'rs' => 1, 'cs' => 2]
        ])));
        $cd[] = self::makeCell(0, 12, ['mc' => ['r' => 0, 'c' => 11]]);
        $merge['0_11'] = ['r' => 0, 'c' => 11, 'rs' => 1, 'cs' => 2];
        
        // MAAŞ (O1:Q1 - 3 sütun)
        $cd[] = self::makeCell(0, 14, self::cellValue('👥 AYLIK MAAŞ', array_merge($yellow, [
            'mc' => ['r' => 0, 'c' => 14, 'rs' => 1, 'cs' => 3]
        ])));
        for ($c = 15; $c <= 16; $c++) {
            $cd[] = self::makeCell(0, $c, ['mc' => ['r' => 0, 'c' => 14]]);
        }
        $merge['0_14'] = ['r' => 0, 'c' => 14, 'rs' => 1, 'cs' => 3];
        
        // KASA (S1:X1 - 6 sütun)
        $cd[] = self::makeCell(0, 18, self::cellValue('🏦 GÜNLÜK KASA', array_merge($teal, [
            'mc' => ['r' => 0, 'c' => 18, 'rs' => 1, 'cs' => 6]
        ])));
        for ($c = 19; $c <= 23; $c++) {
            $cd[] = self::makeCell(0, $c, ['mc' => ['r' => 0, 'c' => 18]]);
        }
        $merge['0_18'] = ['r' => 0, 'c' => 18, 'rs' => 1, 'cs' => 6];
        
        // Satır 1 - Sütun başlıkları
        $sozHeaders = ['TARİH', 'AÇIKLAMA', 'HİZMET', 'TUTAR', 'sonraki ay'];
        foreach ($sozHeaders as $i => $h) $cd[] = self::H(1, $i, $h, $blue);
        
        $tahsilHeaders = ['TARİH', 'AÇIKLAMA', 'VERİLEN HİZMET', 'TUTAR'];
        foreach ($tahsilHeaders as $i => $h) $cd[] = self::H(1, 6 + $i, $h, $green);
        
        $cd[] = self::H(1, 11, 'KATEGORİ', $sub);
        $cd[] = self::H(1, 12, 'TUTAR', $sub);
        
        $cd[] = self::H(1, 14, 'PERSONEL', $sub);
        $cd[] = self::H(1, 15, 'NET MAAŞ', $sub);
        $cd[] = self::H(1, 16, 'BRÜT MAAŞ', $sub);
        
        $kasaHeaders = ['TARİH', 'KASA', 'AÇIKLAMA', 'GELEN', 'GİDEN', 'BAKİYE'];
        foreach ($kasaHeaders as $i => $h) $cd[] = self::H(1, 18 + $i, $h, $teal);
        
        // Sözleşmeler verileri
        $sozVeri = [
            ['1\'inden 1\'ine', 'KAMURAN AKBAY MİMARLIK', 'SEO HİZMETİ'],
            ['5\'inden 5\'ine', 'BOOM STREET FOOD', 'SOSYAL MEDYA YÖNETİMİ'],
            ['5\'inden 5\'ine', 'NETA RESTAURANT', 'SOSYAL MEDYA YÖNETİMİ'],
            ['6\'sından 6\'sına', 'MURAT TOBACCO', 'GENEL HİZMET YÖNETİMİ'],
            ['7\'sinden 7\'sine', 'HCA REAL ESTATE', 'DİJİTAL PAZARLAMA'],
            ['13\'ünden 13\'üne', 'ÖZÜM NAKLİYAT', 'GOOGLE ADS DANIŞMANLIĞI'],
            ['20\'sinden 20\'sine', '', ''],
        ];
        foreach ($sozVeri as $i => $row) {
            $r = $i + 2;
            $cd[] = self::H($r, 0, $row[0], ['fc' => '#1e3a8a', 'ht' => 0, 'fs' => 10]);
            $cd[] = self::H($r, 1, $row[1], ['fc' => '#065f46', 'bl' => 1, 'ht' => 0, 'fs' => 10]);
            $cd[] = self::H($r, 2, $row[2], ['fc' => '#1e3a8a', 'ht' => 0, 'fs' => 10]);
        }
        $cd[] = self::H(9, 2, 'TOPLAM', $red);
        $cd[] = self::F(9, 3, '=SUM(D3:D9)', $red);
        
        // Gider kategorileri
        $giderler = [
            'AYLIK VERGİ', 'MUHASEBE', 'EV/OFİS KİRA', 'YAKIT', 'KREŞ',
            'KREDİ', 'ELEKTRİK', 'SU', 'İNTERNET', 'MUTFAK',
            'FREELANCER WEB', 'FREELANCER PROD.',
        ];
        foreach ($giderler as $i => $kat) {
            $cd[] = self::H($i + 2, 11, $kat, $sub);
        }
        $cd[] = self::H(14, 11, 'GİDER TOPLAMI', $red);
        $cd[] = self::F(14, 12, '=SUM(M3:M14)', $red);
        
        // Maaş personelleri
        $personeller = [
            'Seda BAYKAL', 'Nurseli İNAN', 'Dilan ATEŞ', 'Nesimi ATEŞ',
            'Serap KAYA', 'Deniz TAŞTAN', 'ÇINAR ÇETİN', 'AYKUT YAR',
            'Damla Ceren GÖKSEL',
        ];
        foreach ($personeller as $i => $kisi) {
            $cd[] = self::H($i + 2, 14, $kisi, ['fc' => '#1e3a8a', 'ht' => 0, 'fs' => 10]);
        }
        $cd[] = self::H(11, 14, 'MAAŞ TOPLAMI', $red);
        $cd[] = self::F(11, 15, '=SUM(P3:P11)', $red);
        $cd[] = self::F(11, 16, '=SUM(Q3:Q11)', $red);
        
        // Kasa örnekler
        $kasa = [
            ['01.01.2026', 'qnb finansbank', 'otobüs bileti', null, 1000],
            ['01.01.2026', 'nakit kasa', 'nesimi ateş virman', 1500, null],
            ['01.01.2026', '', 'yemek', null, 200],
            ['02.01.2026', '', '', 500, null],
            ['02.01.2026', '', '', null, 800],
        ];
        foreach ($kasa as $i => $row) {
            $r = $i + 2;
            $cd[] = self::H($r, 18, $row[0], ['ht' => 0, 'fs' => 10]);
            $cd[] = self::H($r, 19, $row[1], ['ht' => 1, 'fs' => 10]);
            $cd[] = self::H($r, 20, $row[2], ['ht' => 1, 'fs' => 10]);
            if ($row[3] !== null) {
                $cd[] = self::H($r, 21, $row[3], ['ht' => 2, 'fs' => 10, 'ct' => ['fa' => '#,##0.00', 't' => 'n']]);
            }
            if ($row[4] !== null) {
                $cd[] = self::H($r, 22, $row[4], ['ht' => 2, 'fs' => 10, 'ct' => ['fa' => '#,##0.00', 't' => 'n']]);
            }
            if ($i === 0) {
                $f = '=IF(V' . ($r + 1) . '="",0,V' . ($r + 1) . ')-IF(W' . ($r + 1) . '="",0,W' . ($r + 1) . ')';
            } else {
                $f = '=X' . ($r) . '+IF(V' . ($r + 1) . '="",0,V' . ($r + 1) . ')-IF(W' . ($r + 1) . '="",0,W' . ($r + 1) . ')';
            }
            $cd[] = self::F($r, 23, $f, ['bl' => 1, 'ht' => 2, 'fs' => 10]);
        }
        
        return self::makeSheet('Hepsi Yan Yana', '#b8b62e', $cd, [
            'columnlen' => (object)[
                '0' => 120, '1' => 200, '2' => 180, '3' => 110, '4' => 85,
                '5' => 25,
                '6' => 110, '7' => 180, '8' => 180, '9' => 110,
                '10' => 25,
                '11' => 160, '12' => 110,
                '13' => 25,
                '14' => 160, '15' => 110, '16' => 110,
                '17' => 25,
                '18' => 95, '19' => 130, '20' => 180, '21' => 110, '22' => 110, '23' => 120,
            ],
            'rowlen' => (object)['0' => 38, '1' => 28],
            'merge' => (object)$merge,
        ]);
    }
}