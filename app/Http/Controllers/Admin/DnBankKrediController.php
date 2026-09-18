<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DnBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DN BANK — KREDİ TALEPLERİ (admin onay ekranı).
 *
 * Müşteriler "Kredilerim" panelinden kredi talep ediyor (DnBankController@krediTalepEt,
 * musteri_krediler.onay_durumu=bekliyor). Coin, admin onaylayana kadar YÜKLENMEZ
 * (DnBankService::krediOnayla). Bu ekran o onay adımı için — daha önce hiç yoktu,
 * bildirimdeki "🏦 Yeni Kredi Talebi" linki hep boşa gidiyordu (07.08.2026).
 */
class DnBankKrediController extends Controller
{
    public function index(Request $request)
    {
        $durum = $request->query('durum', 'bekliyor');

        $query = DB::table('musteri_krediler as k')
            ->leftJoin('uyeler as u', 'u.id', '=', 'k.uye_id')
            ->select(
                'k.*',
                DB::raw("TRIM(CONCAT(COALESCE(u.ad,''),' ',COALESCE(u.soyad,''))) as musteri_adi"),
                'u.email as musteri_email'
            );

        if ($durum !== 'tumu') {
            $query->where('k.onay_durumu', $durum);
        }

        $krediler = $query->orderByDesc('k.id')->paginate(25)->withQueryString();

        $sayimBase = fn () => DB::table('musteri_krediler');
        $bekleyenSayisi   = $sayimBase()->where('onay_durumu', 'bekliyor')->count();
        $onaylananSayisi  = $sayimBase()->where('onay_durumu', 'onaylandi')->count();
        $reddedilenSayisi = $sayimBase()->where('onay_durumu', 'reddedildi')->count();

        return view('admin.dnbank-krediler.index', compact(
            'krediler', 'durum', 'bekleyenSayisi', 'onaylananSayisi', 'reddedilenSayisi'
        ));
    }

    public function goster(int $id)
    {
        $kredi = DB::table('musteri_krediler as k')
            ->leftJoin('uyeler as u', 'u.id', '=', 'k.uye_id')
            ->leftJoin('crm_customers as c', 'c.id', '=', 'k.musteri_id')
            ->select(
                'k.*',
                DB::raw("TRIM(CONCAT(COALESCE(u.ad,''),' ',COALESCE(u.soyad,''))) as musteri_adi"),
                'u.email as musteri_email', 'u.telefon as musteri_telefon',
                'c.adi as crm_musteri_adi'
            )
            ->where('k.id', $id)
            ->first();

        abort_if(!$kredi, 404);

        $taksitler = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('musteri_kredi_taksitleri')) {
            $taksitler = DB::table('musteri_kredi_taksitleri')
                ->where('kredi_id', $id)->orderBy('sira')->get();
        }

        return view('admin.dnbank-krediler.goster', compact('kredi', 'taksitler'));
    }

    /** Admin: krediyi onaylar (coin yüklenir) */
    public function onayla(int $id)
    {
        $yuklendi = DnBankService::krediOnayla($id, session('admin_id'));

        return back()->with(
            $yuklendi ? 'success' : 'info',
            $yuklendi ? 'Kredi onaylandı, coin müşterinin hesabına yüklendi.' : 'Bu kredi zaten onaylanmış/yüklenmiş.'
        );
    }

    /** Admin: krediyi reddeder (coin yüklenmez) */
    public function reddet(int $id)
    {
        DnBankService::krediReddet($id, session('admin_id'));

        return back()->with('success', 'Kredi talebi reddedildi.');
    }
}
