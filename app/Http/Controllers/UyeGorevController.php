<?php

namespace App\Http\Controllers;

use App\Models\CRM\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UyeGorevController extends Controller
{
    /**
     * Giriş yapan üyenin (CRM eşleşmeli) görevlerini listeler.
     */
    public function index()
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect('/');
        }

        $musteriIds = $this->customerIdsForUye($uye);

        $gorevler = collect();
        if (!empty($musteriIds) && Schema::hasTable((new Task)->getTable())) {
            $gorevler = Task::whereIn('musteri_id', $musteriIds)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('tema.gorevlerim', compact('gorevler'));
    }

    /**
     * Üye ↔ CRM müşteri eşleşmesi (3 katman: aynı ID, uye_id kolonu, e-posta).
     */
    private function customerIdsForUye($uye): array
    {
        $ids = [(int) $uye->id];

        if (Schema::hasTable('crm_customers')) {
            if (Schema::hasColumn('crm_customers', 'uye_id')) {
                $ids = array_merge($ids, DB::table('crm_customers')
                    ->where('uye_id', $uye->id)->pluck('id')->map(fn ($i) => (int) $i)->all());
            }
            if (!empty($uye->email)) {
                $ids = array_merge($ids, DB::table('crm_customers')
                    ->where('email', $uye->email)->pluck('id')->map(fn ($i) => (int) $i)->all());
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}