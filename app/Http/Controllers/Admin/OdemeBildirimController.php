<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OdemeBildirimController extends Controller
{
    /**
     * Public: Ödeme bildirim formunu göster
     */
    public function formGoster()
    {
        $ayar = \App\Models\Ayar::first();
        return view('tema.odeme-bildirim-formu', compact('ayar'));
    }

    /**
     * Public: Ödeme bildirim formunu işle (DB'ye kaydet + firma mailine gönder)
     */
    public function formGonder(Request $request)
    {
        $request->validate([
            'isim'    => 'required|string|max:255',
            'tutar'   => 'required|string|max:100',
            'tarih'   => 'required|string|max:100',
            'banka'   => 'required|string|max:255',
            'notunuz' => 'nullable|string|max:2000',
        ], [], [
            'isim'  => 'Adınız Soyadınız',
            'tutar' => 'Yatırılan Tutar',
            'tarih' => 'Yatırma Tarihi',
            'banka' => 'Yatırılan Banka',
        ]);

        try {
            // 1) Veritabanına kaydet (mevcut odeme_bildirimleri tablosu)
            DB::table('odeme_bildirimleri')->insert([
                'isim'    => $request->isim,
                'tutar'   => $request->tutar,
                'tarih'   => $request->tarih,
                'banka'   => $request->banka,
                'notunuz' => $request->notunuz ?? '',
                'ip'      => $request->ip(),
                'ktarih'  => date('Y-m-d H:i:s'),
                'durum'   => 0,
            ]);

            // 2) Firma mailine bildirim gönder (fail-safe)
            try {
                $ayar = \App\Models\Ayar::first();
                $aliciMail = $ayar->firma_email ?? config('mail.from.address');
                if ($aliciMail) {
                    $govde = "Yeni Ödeme Bildirimi\n\n"
                        . "Ad Soyad: {$request->isim}\n"
                        . "Tutar: {$request->tutar}\n"
                        . "Tarih: {$request->tarih}\n"
                        . "Banka: {$request->banka}\n"
                        . "Not: " . ($request->notunuz ?? '-') . "\n"
                        . "IP: " . $request->ip() . "\n"
                        . "Kayıt Zamanı: " . date('d.m.Y H:i:s');

                    Mail::raw($govde, function ($m) use ($aliciMail) {
                        $m->to($aliciMail)->subject('Yeni Ödeme Bildirimi');
                    });
                }
            } catch (\Throwable $e) {
                Log::warning('Ödeme bildirimi mail gönderim hatası', ['err' => $e->getMessage()]);
            }

            return redirect()->back()->with('success', 'Ödeme bildiriminiz başarıyla alındı. En kısa sürede kontrol edilecektir.');
        } catch (\Exception $e) {
            Log::error('Ödeme bildirimi kayıt hatası', ['err' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Bildiriminiz gönderilirken bir hata oluştu. Lütfen tekrar deneyin.');
        }
    }

    public function index()
    {
        $bildirimler = DB::table('odeme_bildirimleri')->orderBy('id', 'desc')->paginate(20);
        return view('admin.odeme-bildirim.index', compact('bildirimler'));
    }
    
    public function detay($id)
    {
        $bildirim = DB::table('odeme_bildirimleri')->where('id', $id)->first();
        
        if (!$bildirim) {
            return redirect()->route('admin.odeme-bildirim.index')->with('error', 'Bildirim bulunamadı.');
        }
        
        return view('admin.odeme-bildirim.detay', compact('bildirim'));
    }
    
    public function durumDegistir($id, $durum)
    {
        DB::table('odeme_bildirimleri')->where('id', $id)->update([
            'durum' => $durum,
            'kontrol_tarihi' => date('Y-m-d H:i:s'),
        ]);
        
        $durumlar = [0 => 'Bekliyor', 1 => 'Onaylandı', 2 => 'Reddedildi'];
        return redirect()->back()->with('success', 'Ödeme bildirimi "' . $durumlar[$durum] . '" olarak işaretlendi.');
    }
    
    public function sil($id)
    {
        DB::table('odeme_bildirimleri')->where('id', $id)->delete();
        return redirect()->route('admin.odeme-bildirim.index')->with('success', 'Ödeme bildirimi başarıyla silindi.');
    }
}