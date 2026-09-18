<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fatura;
use App\Models\Ayar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\PaytrService;

class FaturaController extends Controller
{
    public function index()
    {
        $ayarlar = Ayar::first();
        $faturalar = Fatura::where('uyeid', Auth::guard('uye')->id())
            ->orderBy('tarih', 'desc')
            ->paginate(20);
        
        return view('tema.faturalarim', compact('ayarlar', 'faturalar'));
    }
    
    public function detay($id)
    {
        $ayarlar = Ayar::first();
        $fatura = Fatura::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->firstOrFail();
        
        return view('tema.fatura-detay', compact('ayarlar', 'fatura'));
    }
    
    public function ode($id)
    {
        $ayarlar = Ayar::first();
        $fatura = Fatura::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->where('durum', 0)
            ->firstOrFail();

        // Ödeme sayfasına yönlendir (PayTR/Iyzico)
        return view('tema.fatura-odeme', compact('ayarlar', 'fatura'));
    }

    /**
     * Ödeme yöntemini işle (kredi kartı → PayTR sanalpos, bakiye → düş & öde)
     * POST /fatura/{id}/ode
     */
    public function odemeBaslat($id, Request $request)
    {
        $userId = Auth::guard('uye')->id();
        $fatura = Fatura::where('uyeid', $userId)
            ->where('id', $id)
            ->where('durum', 0)
            ->firstOrFail();

        $yontem = $request->input('odeme_yontemi', 'kredi_karti');

        // ═══ BAKİYE İLE ÖDEME ═══
        if ($yontem === 'bakiye') {
            $uye = DB::table('uyeler')->where('id', $userId)->first();
            $bakiye = (float)($uye->bakiye ?? 0);
            $tutar = (float) $fatura->tutar;

            if ($bakiye < $tutar) {
                return redirect()->route('fatura.ode', $id)
                    ->with('error', 'Bakiyeniz yetersiz. Mevcut: ' . number_format($bakiye, 2, ',', '.') . ' ₺');
            }

            DB::beginTransaction();
            try {
                $yeniBakiye = $bakiye - $tutar;
                DB::table('uyeler')->where('id', $userId)->update(['bakiye' => $yeniBakiye]);

                $update = ['durum' => 1];
                // TAHSILAT TARIHI: raporlar 'odenen_tarih'e bakar.
                // 'odeme_tarihi' diye bir kolon yok (hasColumn hep false),
                // bu yuzden bakiyeyle odenen faturalar gunluk hareketlerde
                // hic gorunmuyordu.
                if (Schema::hasColumn('faturalar', 'odenen_tarih')) $update['odenen_tarih'] = now()->toDateString();
                if (Schema::hasColumn('faturalar', 'odeme_tarihi')) $update['odeme_tarihi'] = now();
                if (Schema::hasColumn('faturalar', 'odeme_yontemi')) $update['odeme_yontemi'] = 'bakiye';
                DB::table('faturalar')->where('id', $id)->update($update);

                if (Schema::hasTable('bakiye_gecmisi')) {
                    DB::table('bakiye_gecmisi')->insert([
                        'uye_id' => $userId,
                        'tip' => 'harcama',
                        'tutar' => $tutar,
                        'bakiye_once' => $bakiye,
                        'bakiye_sonra' => $yeniBakiye,
                        'aciklama' => 'Fatura ödeme: ' . $fatura->fatura_no,
                        'tarih' => now(),
                    ]);
                }

                DB::commit();
                return redirect()->route('faturalarim')
                    ->with('success', 'Fatura bakiyenizden ödendi. Kalan bakiye: ' . number_format($yeniBakiye, 2, ',', '.') . ' ₺');
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Bakiye ile fatura ödeme hatası', ['fatura_id' => $id, 'err' => $e->getMessage()]);
                return redirect()->route('fatura.ode', $id)->with('error', 'Ödeme sırasında hata: ' . $e->getMessage());
            }
        }

        // ═══ KREDİ KARTI → PayTR iframe (sanalpos) ═══
        try {
            $paytr = new PaytrService();
            $result = $paytr->createPayment(
                $fatura->id,
                route('bakiye.yukleme.sonuc', ['status' => 'success']),
                route('bakiye.yukleme.sonuc', ['status' => 'cancel'])
            );

            if (($result['status'] ?? null) === 'success' && !empty($result['iframe_url'])) {
                return view('tema.paytr-iframe', ['iframe_url' => $result['iframe_url']]);
            }
            return redirect()->route('fatura.ode', $id)
                ->with('error', 'Sanal POS başlatılamadı: ' . ($result['message'] ?? 'Bilinmeyen hata'));
        } catch (\Throwable $e) {
            Log::error('Fatura PayTR hatası', ['fatura_id' => $id, 'err' => $e->getMessage()]);
            return redirect()->route('fatura.ode', $id)->with('error', 'Ödeme başlatılırken hata: ' . $e->getMessage());
        }
    }
    
    public function indir($id)
    {
        $fatura = Fatura::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->firstOrFail();
        
        // PDF olarak indir
        // Bu kısım PDF kütüphanesi ile tamamlanacak
        
        return redirect()->back()->with('info', 'Fatura indirme özelliği yakında eklenecek.');
    }
}




