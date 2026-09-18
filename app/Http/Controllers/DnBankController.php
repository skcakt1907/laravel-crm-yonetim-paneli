<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\DnBankService;

/**
 * Müşteri (üye) DN Bank paneli: harcanabilir coin, geri ödeme (taksit) planları,
 * coin hareket geçmişi.
 */
class DnBankController extends Controller
{
    public function index()
    {
        $userId = (int) Auth::guard('uye')->id();
        $uye    = Auth::guard('uye')->user();

        $coin = DnBankService::bakiye($userId);

        // Geri ödeme planları (krediler) — uye_id VEYA musteri_id ile (şemaya dayanıklı)
        $krediler = collect();
        if (Schema::hasTable('musteri_krediler')) {
            $kolon = Schema::getColumnListing('musteri_krediler');
            $krediler = DB::table('musteri_krediler')
                ->where(function ($q) use ($userId, $uye, $kolon) {
                    if (in_array('uye_id', $kolon))  { $q->orWhere('uye_id', $userId); }
                    if (in_array('uyeid', $kolon))   { $q->orWhere('uyeid', $userId); }
                    // crm_musteri_id ile de dene (CRM müşteri eşleşmesi)
                    if (in_array('crm_musteri_id', $kolon) || in_array('musteri_id', $kolon)) {
                        $crmId = null;
                        if (!empty($uye->email)) {
                            $crmId = DB::table('crm_customers')->where('email', $uye->email)->value('id');
                        }
                        if ($crmId) {
                            if (in_array('crm_musteri_id', $kolon)) { $q->orWhere('crm_musteri_id', $crmId); }
                            if (in_array('musteri_id', $kolon))     { $q->orWhere('musteri_id', $crmId); }
                        }
                    }
                })
                ->orderByDesc('id')
                ->get();
        }

        // Taksitler (kredi_id'ye göre gruplu)
        $taksitler = collect();
        $krediIdler = $krediler->pluck('id')->all();
        if (!empty($krediIdler) && Schema::hasTable('musteri_kredi_taksitleri')) {
            $taksitler = DB::table('musteri_kredi_taksitleri')
                ->whereIn('kredi_id', $krediIdler)
                ->orderBy('sira')
                ->get()
                ->groupBy('kredi_id');
        }

        $toplamKredi  = (float) $krediler->sum('ana_para');
        $toplamBorc   = (float) $krediler->sum('kalan_borc');
        $toplamOdenen = (float) $krediler->sum('odenen_tutar');

        // Coin hareketleri
        $hareketler = collect();
        if (Schema::hasTable('dnbank_hareketleri')) {
            $hareketler = DB::table('dnbank_hareketleri')
                ->where('uye_id', $userId)
                ->orderByDesc('id')
                ->limit(60)
                ->get();
        }

        return view('tema.dnbank', compact(
            'uye', 'coin', 'krediler', 'taksitler',
            'toplamKredi', 'toplamBorc', 'toplamOdenen', 'hareketler'
        ));
    }
    /** Müşteri: krediyi onaylar (coin yüklenir) */
    public function krediOnayla($krediId)
    {
        $userId = (int) Auth::guard('uye')->id();
        $kredi  = DB::table('musteri_krediler')->where('id', $krediId)->first();

        if (!$kredi || (int) ($kredi->uye_id ?? 0) !== $userId) {
            return back()->with('error', 'Kredi bulunamadı.');
        }
        if (Schema::hasColumn('musteri_krediler', 'onay_durumu') && $kredi->onay_durumu === 'onaylandi') {
            return back()->with('info', 'Bu kredi zaten onaylanmış.');
        }

        DnBankService::krediOnayla((int) $krediId, null);

        return back()->with('success', 'Kredi onaylandı, coin hesabınıza yüklendi.');
    }

    /** Müşteri: krediyi reddeder */
    public function krediReddet($krediId)
    {
        $userId = (int) Auth::guard('uye')->id();
        $kredi  = DB::table('musteri_krediler')->where('id', $krediId)->first();

        if (!$kredi || (int) ($kredi->uye_id ?? 0) !== $userId) {
            return back()->with('error', 'Kredi bulunamadı.');
        }

        DnBankService::krediReddet((int) $krediId, null);

        return back()->with('success', 'Kredi talebi reddedildi.');
    }

    /** Müşteri: yeni kredi talebi oluşturur (admin onayına gider) */
    public function krediTalepEt(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ana_para' => 'required|numeric|min:1',
            'vade_ay'  => 'required|integer|min:1|max:120',
            'aciklama' => 'nullable|string|max:500',
        ]);

        $userId = (int) Auth::guard('uye')->id();
        $uye    = Auth::guard('uye')->user();

        // CRM müşteri eşleşmesi (varsa)
        $crmId = null;
        if (!empty($uye->email)) {
            $crmId = DB::table('crm_customers')->where('email', $uye->email)->value('id');
        }

        $krediId = DnBankService::krediTalepEt(
            $userId,
            $crmId ? (int) $crmId : null,
            (float) $request->ana_para,
            (int) $request->vade_ay,
            $request->aciklama
        );

        // Admin'e bildirim
        if (function_exists('admin_bildirim_gonder')) {
            try {
                admin_bildirim_gonder(
                    '🏦 Yeni Kredi Talebi',
                    ($uye->ad ?? 'Müşteri') . ' ₺' . number_format((float) $request->ana_para, 2, ',', '.') . ' kredi talep etti.',
                    'kredi_talep', 'dnbank', (int) $krediId
                );
            } catch (\Throwable $e) {}
        }

        return back()->with('success', 'Kredi talebiniz alındı. Onaylandığında coin hesabınıza yüklenecek.');
    }
}