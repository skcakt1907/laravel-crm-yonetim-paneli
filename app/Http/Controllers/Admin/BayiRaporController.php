<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BayiRaporController extends Controller
{
    /**
     * Raporlar Ana Sayfa
     */
    public function index()
    {
        $bayiId = session('admin_id');
        $bayi = DB::table('bayiler')->where('id', $bayiId)->first();
        
        if (!$bayi) {
            $bayi = DB::table('bayiler')->first();
        }
        
        // Rapor türleri - çeviri destekli
        $raporlar = [
            'satis' => __('messages.sales_report'),
            'kazanc' => __('messages.earnings_report'),
            'musteri' => __('messages.customer_report'),
            'odeme' => __('messages.payment_report'),
        ];
        
        return view('admin.bayi.raporlar.index', compact('bayi', 'raporlar'));
    }
    
    /**
     * PDF İndir
     */
    public function pdfIndir($tip)
    {
        $bayiId = session('admin_id');
        $bayi = DB::table('bayiler')->where('id', $bayiId)->first();
        
        if (!$bayi) {
            $bayi = DB::table('bayiler')->first();
        }
        
        $data = $this->getRaporData($tip, $bayi);
        
        // PDF içeriği oluştur
        $html = view('admin.bayi.raporlar.pdf', [
            'bayi' => $bayi,
            'tip' => $tip,
            'data' => $data,
            'tarih' => now()->format('d.m.Y H:i')
        ])->render();
        
        // Basit HTML to PDF (DomPDF yoksa HTML döndür)
        $filename = $tip . '_raporu_' . date('Y-m-d') . '.html';
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    /**
     * Excel İndir
     */
    public function excelIndir($tip)
    {
        $bayiId = session('admin_id');
        $bayi = DB::table('bayiler')->where('id', $bayiId)->first();
        
        if (!$bayi) {
            $bayi = DB::table('bayiler')->first();
        }
        
        $data = $this->getRaporData($tip, $bayi);
        
        // CSV formatında rapor döndür
        
        $filename = $tip . '_raporu_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($data, $tip) {
            $file = fopen('php://output', 'w');
            
            // Başlıklar
            if ($tip == 'satis') {
                fputcsv($file, ['ID', 'Müşteri', 'Paket', 'Tutar', 'Komisyon', 'Tarih']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->musteri_adi ?? '-',
                        $row->paket_adi ?? '-',
                        $row->satis_tutari,
                        $row->komisyon_tutari,
                        $row->created_at
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Rapor Datası Al
     */
    private function getRaporData($tip, $bayi)
    {
        switch ($tip) {
            case 'satis':
                return DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
                
            case 'kazanc':
                return DB::table('bayi_satislar')
                    ->select(
                        DB::raw('DATE_FORMAT(created_at, "%Y-%m") as ay'),
                        DB::raw('COUNT(*) as adet'),
                        DB::raw('SUM(satis_tutari) as toplam_satis'),
                        DB::raw('SUM(komisyon_tutari) as toplam_komisyon')
                    )
                    ->where('bayi_id', $bayi->id)
                    ->groupBy('ay')
                    ->orderBy('ay', 'desc')
                    ->get();
                
            case 'musteri':
                return DB::table('bayi_satislar')
                    ->join('faturalar', 'bayi_satislar.fatura_id', '=', 'faturalar.id')
                    ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
                    ->where('bayi_satislar.bayi_id', $bayi->id)
                    ->select('uyeler.*', DB::raw('COUNT(bayi_satislar.id) as toplam_satis'))
                    ->groupBy('uyeler.id')
                    ->get();
                
            case 'odeme':
                return DB::table('bayi_odeme_talepleri')
                    ->where('bayi_id', $bayi->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
                
            default:
                return [];
        }
    }
}

