<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Maatwebsite\Excel\Facades\Excel; // Paket kurulunca aktif olacak
// use App\Exports\UyelerExport;
// use App\Exports\FaturalarExport;
// use App\Exports\PaketlerExport;
// use App\Imports\UyelerImport;
// use App\Imports\PaketlerImport;

class ImportExportController extends Controller
{
    /**
     * Import/Export ana sayfa
     */
    public function index()
    {
        return view('admin.import-export.index');
    }
    
    /**
     * CSV Şablonu İndir
     */
    public function uyelerTemplate()
    {
        $filename = 'uyeler_sablon.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // UTF-8 BOM ekle (Excel için)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Başlıklar - Export ile aynı
        fputcsv($handle, [
            'Ad', 'Soyad', 'Email', 'Telefon', 'Şifre', 'Üye Tipi', 'TC Kimlik No', 'Doğum Tarihi',
            'Firma Adı', 'Vergi No', 'Vergi Dairesi', 'Cinsiyet', 'İl', 'İlçe', 'Posta Kodu', 'Adres',
            'Durum', 'Bakiye', 'Kayıt Tarihi'
        ], ';'); // Excel için noktalı virgül
        
        // Örnek satır
        fputcsv($handle, [
            'Ahmet', 'Yılmaz', 'ahmet@example.com', '05551234567', '123456', '0', '12345678901', '1990-01-01',
            '', '', '', 'Erkek', 'İstanbul', 'Kadıköy', '34000', 'Örnek Mahalle Örnek Sokak No:1',
            '1', '0', ''
        ], ';');
        
        fclose($handle);
        exit;
    }
    
    /**
     * Üyeleri Excel'e aktar
     */
    public function uyelerExport()
    {
        // return Excel::download(new UyelerExport, 'uyeler_' . date('Y-m-d') . '.xlsx');
        
        // Geçici çözüm (Excel paketi kurulana kadar)
        $uyeler = DB::table('uyeler')->get();
        
        $filename = 'uyeler_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Başlıklar - UyeController'daki tüm alanlarla uyumlu
        fputcsv($handle, [
            'Ad', 'Soyad', 'Email', 'Telefon', 'Şifre', 'Üye Tipi', 'TC Kimlik No', 'Doğum Tarihi',
            'Firma Adı', 'Vergi No', 'Vergi Dairesi', 'Cinsiyet', 'İl', 'İlçe', 'Posta Kodu', 'Adres',
            'Durum', 'Bakiye', 'Kayıt Tarihi'
        ]);
        
        // Veriler
        foreach ($uyeler as $uye) {
            fputcsv($handle, [
                $uye->ad ?? '',
                $uye->soyad ?? '',
                $uye->email ?? '',
                $uye->telefon ?? '',
                '', // Şifre export edilmez (güvenlik)
                $uye->utipi ?? 0,
                $uye->tc ?? $uye->tc_no ?? '',
                $uye->dtarih ?? '',
                $uye->firmaadi ?? '',
                $uye->vergino ?? '',
                $uye->vergidairesi ?? '',
                $uye->cinsiyet ?? 'Erkek',
                $uye->il ?? $uye->sehir ?? '',
                $uye->ilce ?? '',
                $uye->pkodu ?? '',
                $uye->adres ?? '',
                $uye->durum ?? 1,
                $uye->bakiye ?? 0,
                $uye->tarih ?? $uye->ktarih ?? '',
            ]);
        }
        
        fclose($handle);
        exit;
    }
    
    /**
     * Faturaları Excel'e aktar
     */
    public function faturalarExport()
    {
        // return Excel::download(new FaturalarExport, 'faturalar_' . date('Y-m-d') . '.xlsx');
        
        // Tüm fatura alanları + müşteri alanları
        $faturalar = DB::table('faturalar')
            ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select(
                'faturalar.id',
                'faturalar.uyeid',
                'faturalar.baslik',
                'faturalar.bitis_tarih',
                'faturalar.tutar',
                'faturalar.odenen_tarih',
                'faturalar.durum',
                'faturalar.hizmet',
                'faturalar.aciklama',
                'faturalar.tarih',
                'faturalar.odeme_yontemi',
                'faturalar.spno',
                'faturalar.mail',
                // müşteri bilgileri (mevcut kolonlar)
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.email',
                'uyeler.telefon',
                'uyeler.firmaadi',
                'uyeler.vergino',
                'uyeler.vergidairesi',
                'uyeler.cinsiyet',
                'uyeler.dtarih',
                'uyeler.tc',
                'uyeler.durum as uye_durum',
                'uyeler.bakiye',
                'uyeler.bayi'
            )
            ->orderBy('faturalar.id', 'desc')
            ->get();
        
        return $this->buildStyledXlsx(
            'Tüm Faturalar',
            [
                ['Fatura ID',8],['Üye ID',8],['Fatura Başlık',32],['Bitiş Tarihi',13],['Tutar (₺)',14],
                ['Ödenen Tarih',14],['Durum',10],['Hizmet',18],['Açıklama',40],['Kayıt Tarihi',14],
                ['Ödeme Yöntemi',16],['SP No',12],['Mail',20],
                ['Müşteri Ad',16],['Müşteri Soyad',16],['Email',26],['Telefon',16],['Firma',24],
                ['Vergi No',14],['Vergi Dairesi',18],['Cinsiyet',10],['Doğum',12],['TC',14],['Müşteri Durum',10],['Bakiye',12],['Bayi',8],
            ],
            $faturalar->map(function ($f) {
                return [
                    $f->id, $f->uyeid, $f->baslik, $f->bitis_tarih,
                    (float) ($f->tutar ?? 0),
                    $f->odenen_tarih,
                    (int) ($f->durum ?? 0) === 1 ? 'Ödendi' : 'Bekliyor',
                    $f->hizmet, $f->aciklama, $f->tarih, $f->odeme_yontemi, $f->spno, $f->mail,
                    $f->ad, $f->soyad, $f->email, $f->telefon, $f->firmaadi, $f->vergino, $f->vergidairesi,
                    $f->cinsiyet, $f->dtarih, $f->tc, $f->uye_durum, (float) ($f->bakiye ?? 0),
                    ($f->bayi ?? 0) ? 'Bayi' : 'Normal',
                ];
            })->all(),
            ['G' => '#,##0.00 "₺"', 'Y' => '#,##0.00 "₺"'],
            'faturalar'
        );
    }

    /**
     * Şık xlsx builder — başlık banner + dondurulmuş header + para format + zebra + auto-filter
     */
    private function buildStyledXlsx(string $title, array $headers, array $rows, array $numberFormats, string $slug)
    {
        $sp = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $sp->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        $colCount = count($headers);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // 1. satır banner
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->setCellValue('A1', 'İŞ ORTAĞIM — ' . mb_strtoupper($title) . ' (' . date('d.m.Y H:i') . ')');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FACC15']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // 2. satır header
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '2', is_array($h) ? $h[0] : $h);
            if (is_array($h)) {
                $sheet->getColumnDimension($col)->setWidth($h[1]);
            }
        }
        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '92400E']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Veri
        $row = 3;
        foreach ($rows as $r) {
            $sheet->fromArray($r, null, 'A' . $row);
            // Zebra
            if ($row % 2 === 1) {
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FAFAFA');
            }
            $row++;
        }

        // Border
        if ($row > 3) {
            $sheet->getStyle('A3:' . $lastCol . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'EEEEEE']]],
                'alignment' => ['vertical' => 'center'],
                'font' => ['size' => 10],
            ]);
        }

        // Para format kolonları
        foreach ($numberFormats as $col => $fmt) {
            $sheet->getStyle($col . '3:' . $col . max($row - 1, 3))->getNumberFormat()->setFormatCode($fmt);
        }

        $sheet->freezePane('A3');
        if ($row > 3) $sheet->setAutoFilter('A2:' . $lastCol . ($row - 1));

        $filename = $slug . '_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sp);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    
    /**
     * Paketleri Excel'e aktar
     */
    public function paketlerExport()
    {
        // return Excel::download(new PaketlerExport, 'paketler_' . date('Y-m-d') . '.xlsx');
        
        $paketler = DB::table('yazilimlar')->get();
        
        $filename = 'paketler_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        fputcsv($handle, ['ID', 'Başlık', 'Fiyat', 'Kategori ID', 'Durum', 'Anasayfa']);
        
        foreach ($paketler as $paket) {
            fputcsv($handle, [
                $paket->id,
                $paket->baslik ?? $paket->adi,
                $paket->fiyat ?? $paket->tutar,
                $paket->kid ?? $paket->kategori,
                $paket->durum ? 'Aktif' : 'Pasif',
                $paket->anasayfa ? 'Evet' : 'Hayır',
            ]);
        }
        
        fclose($handle);
        exit;
    }
    
    /**
     * Üyeleri Excel'den içe aktar
     */
    public function uyelerImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        
        // Excel::import(new UyelerImport, $request->file('file'));
        
        // CSV import
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Başlık satırını oku
        $headers = fgetcsv($handle);
        
        // Başlık kontrolü - eski formatı destekle
        $isOldFormat = false;
        if ($headers && count($headers) < 10) {
            // Eski format: adi, soyadi, email, telefon, sehir, ulke
            $isOldFormat = true;
        }
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle)) !== false) {
            try {
                if ($isOldFormat) {
                    // Eski format desteği
                    if (empty($data[0]) || empty($data[2])) {
                        continue; // Ad veya email boşsa atla
                    }
                    
                    $email = $data[2];
                    $ad = $data[0];
                    $soyad = $data[1] ?? '';
                    $telefon = $data[3] ?? '';
                    $sehir = $data[4] ?? '';
                    $ulke = $data[5] ?? '';
                } else {
                    // Yeni format: Export şablonu ile aynı
                    // Ad, Soyad, Email, Telefon, Şifre, Üye Tipi, TC Kimlik No, Doğum Tarihi,
                    // Firma Adı, Vergi No, Vergi Dairesi, Cinsiyet, İl, İlçe, Posta Kodu, Adres,
                    // Durum, Bakiye, Kayıt Tarihi
                    if (empty($data[0]) || empty($data[2])) {
                        continue; // Ad veya email boşsa atla
                    }
                    
                    $ad = $data[0] ?? '';
                    $soyad = $data[1] ?? '';
                    $email = $data[2] ?? '';
                    $telefon = $data[3] ?? '';
                    $sifre = $data[4] ?? '123456'; // Şifre boşsa varsayılan
                    $utipi = isset($data[5]) ? (int)$data[5] : 0;
                    $tc = $data[6] ?? '';
                    $dtarih = $data[7] ?? '';
                    $firmaadi = $data[8] ?? '';
                    $vergino = $data[9] ?? '';
                    $vergidairesi = $data[10] ?? '';
                    $cinsiyet = $data[11] ?? 'Erkek';
                    $il = $data[12] ?? '';
                    $ilce = $data[13] ?? '';
                    $pkodu = $data[14] ?? '';
                    $adres = $data[15] ?? '';
                    $durum = isset($data[16]) ? (int)$data[16] : 1;
                    $bakiye = isset($data[17]) ? (float)$data[17] : 0;
                    $sehir = ''; // Yeni formatta yok
                    $ulke = ''; // Yeni formatta yok
                }
                
                // Email kontrolü
                $mevcutUye = DB::table('uyeler')->where('email', $email)->first();
                if ($mevcutUye) {
                    $errors[] = $email . ' - Zaten kayıtlı';
                    continue;
                }
                
                // Şifre hash'leme
                if (!empty($sifre) && $sifre != '123456') {
                    $hashedPassword = \Hash::make($sifre);
                } else {
                    // Eski sistem uyumluluğu için plaintext veya hash
                    $hashedPassword = \Hash::make('123456');
                }
                
                $insertData = [
                    'ad' => $ad,
                    'soyad' => $soyad,
                    'email' => $email,
                    'telefon' => $telefon,
                    'sifre' => $hashedPassword,
                    'durum' => isset($durum) ? $durum : 1,
                    'utipi' => isset($utipi) ? $utipi : 0,
                    'bakiye' => isset($bakiye) ? $bakiye : 0,
                    'bayi' => 0,
                ];
                
                // Yeni format için ek alanlar
                if (!$isOldFormat) {
                    $insertData['tc'] = $tc;
                    $insertData['dtarih'] = $dtarih;
                    $insertData['firmaadi'] = $firmaadi;
                    $insertData['vergino'] = $vergino;
                    $insertData['vergidairesi'] = $vergidairesi;
                    $insertData['cinsiyet'] = $cinsiyet;
                    $insertData['il'] = $il;
                    $insertData['ilce'] = $ilce;
                    $insertData['pkodu'] = $pkodu;
                    $insertData['adres'] = $adres;
                } else {
                    // Eski format için şehir ve ülke
                    $insertData['il'] = $sehir;
                    $insertData['sehir'] = $sehir;
                    $insertData['ulke'] = $ulke;
                }
                
                // Tarih alanları
                $insertData['tarih'] = date('Y-m-d H:i:s');
                $insertData['ktarih'] = date('Y-m-d H:i:s');
                
                DB::table('uyeler')->insert($insertData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = ($data[2] ?? $data[0] ?? 'Bilinmeyen') . ' - Hata: ' . $e->getMessage();
                continue;
            }
        }
        
        fclose($handle);
        
        $mesaj = $imported . ' üye başarıyla içe aktarıldı.';
        if (!empty($errors)) {
            $mesaj .= ' Hata sayısı: ' . count($errors);
        }
        
        return redirect()->back()->with('success', $mesaj);
    }
    
    /**
     * Paketleri Excel'den içe aktar
     */
    public function paketlerImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        
        // Excel::import(new PaketlerImport, $request->file('file'));
        
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Başlık satırını oku
        $headers = fgetcsv($handle);
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle)) !== false) {
            try {
                // CSV'den gelen: adi, kategori, fiyat, aciklama, durum
                if (empty($data[0])) {
                    continue; // Ad boşsa atla
                }
                
                // Kategori ID'sini bul (kategori adından)
                $kategoriAdi = $data[1] ?? 'Genel';
                $kategori = DB::table('web_kategori')->where('adi', $kategoriAdi)->first();
                $kategoriId = $kategori->id ?? 1;
                
                DB::table('yazilimlar')->insert([
                    'adi' => $data[0], // adi
                    'kategori' => $kategoriId, // kategori ID
                    'tutar' => $data[2] ?? 0, // fiyat
                    'kisa' => $data[3] ?? '', // aciklama
                    'aciklama' => $data[3] ?? '', // aciklama
                    'durum' => $data[4] ?? 1, // durum
                    'seo' => \Str::slug($data[0]), // SEO URL
                    'sira' => 99,
                    'anasayfa' => 0,
                    'tarih' => date('Y-m-d H:i:s'),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = ($data[0] ?? 'Bilinmeyen') . ' - Hata: ' . $e->getMessage();
                continue;
            }
        }
        
        fclose($handle);
        
        $mesaj = $imported . ' paket başarıyla içe aktarıldı.';
        if (!empty($errors)) {
            $mesaj .= ' Hata sayısı: ' . count($errors);
        }
        
        return redirect()->back()->with('success', $mesaj);
    }
    
    /**
     * Toplu silme
     */
    public function topluSil(Request $request)
    {
        $request->validate([
            'tablo' => 'required|in:uyeler,faturalar,yazilimlar,blog,referanslar',
            'ids' => 'required|array',
        ]);
        
        $silinen = DB::table($request->tablo)
            ->whereIn('id', $request->ids)
            ->delete();
        
        return redirect()->back()->with('success', $silinen . ' kayıt silindi.');
    }
}


