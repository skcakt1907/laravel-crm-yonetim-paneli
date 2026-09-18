<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class EBultenController extends Controller
{
    public function index()
    {
        $aboneler = DB::table('ebulten_aboneler')->orderBy('id', 'desc')->paginate(20);
        return view('admin.ebulten.index', compact('aboneler'));
    }

    public function sil($id)
    {
        DB::table('ebulten_aboneler')->where('id', $id)->delete();
        return redirect()->route('admin.ebulten.index')->with('success', 'Abone başarıyla silindi.');
    }

    public function topluMail()
    {
        $aboneler = DB::table('ebulten_aboneler')->where('durum', 1)->get();
        $uyeSayisi = Schema::hasTable('uyeler') ? DB::table('uyeler')->whereNotNull('email')->count() : 0;
        $bayiSayisi = Schema::hasTable('bayiler') ? DB::table('bayiler')->where('durum', 1)->count() : 0;
        return view('admin.ebulten.toplu-mail', compact('aboneler', 'uyeSayisi', 'bayiSayisi'));
    }

    public function topluMailGonder(Request $request)
    {
        $request->validate([
            'konu' => 'required|string|max:255',
            'icerik' => 'required|string',
            'hedef' => 'required|in:aboneler,uyeler,bayiler,hepsi,secili',
            'secili_emails' => 'nullable|array',
        ]);

        $alicilar = collect();

        if (in_array($request->hedef, ['aboneler', 'hepsi'])) {
            $a = DB::table('ebulten_aboneler')->where('durum', 1)->pluck('email');
            $alicilar = $alicilar->merge($a);
        }

        if (in_array($request->hedef, ['uyeler', 'hepsi']) && Schema::hasTable('uyeler')) {
            $u = DB::table('uyeler')->whereNotNull('email')->pluck('email');
            $alicilar = $alicilar->merge($u);
        }

        if (in_array($request->hedef, ['bayiler', 'hepsi']) && Schema::hasTable('bayiler')) {
            // bayiler tablosunda email kolonu varsa al, yoksa uye_id üzerinden
            $bayiCols = Schema::getColumnListing('bayiler');
            if (in_array('email', $bayiCols)) {
                $b = DB::table('bayiler')->where('durum', 1)->whereNotNull('email')->pluck('email');
            } else {
                $b = DB::table('bayiler')
                    ->join('uyeler', 'bayiler.uye_id', '=', 'uyeler.id')
                    ->where('bayiler.durum', 1)->pluck('uyeler.email');
            }
            $alicilar = $alicilar->merge($b);
        }

        if ($request->hedef === 'secili' && is_array($request->secili_emails)) {
            $alicilar = collect($request->secili_emails);
        }

        $alicilar = $alicilar->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values();

        if ($alicilar->isEmpty()) {
            return back()->with('error', 'Gönderilecek geçerli alıcı yok!')->withInput();
        }

        $basarili = 0;
        $hatali = 0;
        $konu = $request->konu;
        $icerik = $request->icerik;

        foreach ($alicilar as $email) {
            try {
                Mail::html($icerik, function ($msg) use ($email, $konu) {
                    $msg->to($email)->subject($konu);
                });
                $basarili++;
            } catch (\Throwable $e) {
                $hatali++;
                \Log::warning('Toplu mail hatası: '.$email.' - '.$e->getMessage());
            }
        }

        // Log kaydı (varsa)
        if (Schema::hasTable('toplu_mail_loglar')) {
            DB::table('toplu_mail_loglar')->insert([
                'konu' => $konu,
                'icerik' => $icerik,
                'alici_sayisi' => $alicilar->count(),
                'basarili' => $basarili,
                'hatali' => $hatali,
                'gonderen_id' => session('admin_id'),
                'created_at' => now(),
            ]);
        }

        return back()->with('success', "✅ $basarili kişiye gönderildi" . ($hatali > 0 ? " ($hatali hata)" : ""));
    }
}

