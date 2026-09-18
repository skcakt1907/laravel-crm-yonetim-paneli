<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmTeklifController extends Controller
{
    /**
     * Müşterinin tüm tekliflerini listele.
     */
    public function index()
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect()->route('uye.giris')->with('error', 'Giriş yapmalısınız.');
        }

        $teklifler = $this->getTekliflerForUye($uye);

        $ayarlar = DB::table('ayarlar')->first();

        return view('tema.tekliflerim', compact('teklifler', 'ayarlar'));
    }

    /**
     * Tek bir teklifin detayı (kabul/red/satın al butonlarıyla).
     * Müşteri panel içinden açılır.
     */
    public function detay($id)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect()->route('uye.giris')->with('error', 'Giriş yapmalısınız.');
        }

        $talep = DB::table('crm_musteri_teklifleri')->where('id', $id)->first();
        if (!$talep) {
            return redirect()->route('uye.crm.tekliflerim')->with('error', 'Teklif bulunamadı.');
        }

        // Güvenlik: Bu teklif gerçekten bu üyenin mi?
        if (!$this->teklifBuUyeyeMi($talep, $uye)) {
            return redirect()->route('uye.crm.tekliflerim')->with('error', 'Bu teklife erişim yetkiniz yok.');
        }

        $customer = DB::table('crm_customers')->where('id', $talep->customer_id)->first();
        $ayarlar  = DB::table('ayarlar')->first();

        return view('tema.teklif-detay', compact('talep', 'customer', 'ayarlar'));
    }

    /**
     * Panel içinden teklifi kabul et (token gerekmez, oturum yeterli).
     */
    public function kabul(Request $request, $id)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) abort(401);

        $talep = DB::table('crm_musteri_teklifleri')->where('id', $id)->first();
        abort_if(!$talep, 404);

        if (!$this->teklifBuUyeyeMi($talep, $uye)) {
            return redirect()->route('uye.crm.tekliflerim')->with('error', 'Bu teklife erişim yetkiniz yok.');
        }

        if (!in_array($talep->durum, ['gonderildi', 'bekliyor'])) {
            return back()->with('error', 'Bu teklif için zaten bir işlem yapılmış.');
        }

        DB::table('crm_musteri_teklifleri')->where('id', $id)->update([
            'durum'      => 'kabul_edildi',
            'updated_at' => now(),
        ]);

        return redirect()->route('uye.teklif.detay', $id)
            ->with('success', '✅ Teklifi kabul ettiniz. Şimdi ödeme adımına geçebilirsiniz.');
    }

    /**
     * Panel içinden teklifi reddet (sebep opsiyonel).
     */
    public function red(Request $request, $id)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) abort(401);

        $talep = DB::table('crm_musteri_teklifleri')->where('id', $id)->first();
        abort_if(!$talep, 404);

        if (!$this->teklifBuUyeyeMi($talep, $uye)) {
            return redirect()->route('uye.crm.tekliflerim')->with('error', 'Bu teklife erişim yetkiniz yok.');
        }

        if (in_array($talep->durum, ['odendi', 'onaylandi', 'reddedildi', 'iptal'])) {
            return back()->with('error', 'Bu teklif için zaten bir işlem yapılmış.');
        }

        $sebep = trim((string) $request->input('red_sebep', ''));

        $updateData = [
            'durum'      => 'reddedildi',
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('crm_musteri_teklifleri', 'red_sebep')) {
            $updateData['red_sebep'] = $sebep ?: null;
        } elseif (Schema::hasColumn('crm_musteri_teklifleri', 'red_aciklama')) {
            $updateData['red_aciklama'] = $sebep ?: null;
        }

        DB::table('crm_musteri_teklifleri')->where('id', $id)->update($updateData);

        return redirect()->route('uye.crm.tekliflerim')
            ->with('success', 'Teklifi reddettiniz. Geri bildiriminiz için teşekkürler.');
    }

    /**
     * Panel içinden "Ödedim" bildirimi (havale/EFT veya manuel ödeme sonrası).
     * Durum: kabul_edildi/gonderildi/bekliyor → odendi
     */
    public function odedim(Request $request, $id)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) abort(401);

        $talep = DB::table('crm_musteri_teklifleri')->where('id', $id)->first();
        abort_if(!$talep, 404);

        if (!$this->teklifBuUyeyeMi($talep, $uye)) {
            return redirect()->route('uye.crm.tekliflerim')->with('error', 'Bu teklife erişim yetkiniz yok.');
        }

        if (!in_array($talep->durum, ['gonderildi', 'bekliyor', 'kabul_edildi'])) {
            return back()->with('error', 'Bu teklif için zaten bir işlem yapılmış.');
        }

        DB::table('crm_musteri_teklifleri')->where('id', $id)->update([
            'durum'      => 'odendi',
            'paid_at'    => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('uye.teklif.detay', $id)
            ->with('success', '✓ Ödeme bildiriminiz alındı. Onay sonrası size haber verilecek.');
    }

    /**
     * Yardımcı: Bu üyenin teklifleri (crm_customers eşleştirmesi)
     * Schema-aware: önce uye_id, yoksa email ile eşleştir.
     */
    private function getTekliflerForUye($uye)
    {
        // Önce bu üyenin crm_customers içindeki id'lerini bul
        $customerIds = $this->getCustomerIdsForUye($uye);

        if (empty($customerIds)) {
            return collect();
        }

        return DB::table('crm_musteri_teklifleri')
            ->whereIn('customer_id', $customerIds)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Yardımcı: Bu uyeye karşılık gelen crm_customers.id'leri döner.
     */
    private function getCustomerIdsForUye($uye): array
    {
        $ids = [];

        // 1) crm_customers.uye_id varsa öncelik (kesin eşleşme)
        if (Schema::hasColumn('crm_customers', 'uye_id')) {
            $ids = DB::table('crm_customers')
                ->where('uye_id', $uye->id)
                ->pluck('id')
                ->toArray();
        }

        // 2) Email ile eşleştir (her durumda denenecek, çoklu kayıt olabilir)
        if (!empty($uye->email)) {
            $emailIds = DB::table('crm_customers')
                ->where('email', $uye->email)
                ->pluck('id')
                ->toArray();
            $ids = array_unique(array_merge($ids, $emailIds));
        }

        return array_values($ids);
    }

    /**
     * Yardımcı: Bu teklif bu üyeye mi ait?
     */
    private function teklifBuUyeyeMi($talep, $uye): bool
    {
        $customerIds = $this->getCustomerIdsForUye($uye);
        return in_array((int) $talep->customer_id, array_map('intval', $customerIds), true);
    }
}