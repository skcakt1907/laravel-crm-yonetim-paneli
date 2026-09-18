<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Customer;
use App\Models\CRM\Opportunity;
use App\Models\CRM\Pipeline;
use App\Models\CRM\Stage;
use App\Models\Yonetici;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::query()
            ->with(['musteri', 'pipeline', 'stage', 'sorumlu']);

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'like', '%' . $search . '%')
                    ->orWhere('aciklama', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('musteri_id')) {
            $query->where('musteri_id', $request->integer('musteri_id'));
        }

        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->integer('pipeline_id'));
        }

        if ($request->filled('stage_id')) {
            $query->where('stage_id', $request->integer('stage_id'));
        }

        if ($request->filled('sorumlu_id')) {
            $query->where('sorumlu_id', $request->integer('sorumlu_id'));
        }

        if ($request->filled('durum')) {
            $query->where('durum', $request->string('durum'));
        }

        $opportunities = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $pipelines = Pipeline::with('stages')->orderBy('sira')->get();
        $customers = Customer::orderBy('adi')->get(['id', 'adi', 'unvan']);
        $yoneticiler = Yonetici::orderBy('adi')->get(['id', 'adi', 'kullaniciadi', 'rol']);

        $durumlar = [
            'acik' => 'Açık',
            'beklemede' => 'Beklemede',
            'kazanildi' => 'Kazanıldı',
            'kaybedildi' => 'Kaybedildi',
        ];

        $paraBirimleri = ['TRY', 'USD', 'EUR', 'AED'];

        $statCards = [
            ['label'=>'TOPLAM FIRSAT','value'=>Opportunity::count(),'icon'=>'📈','color'=>'#3b82f6','text'=>'#60a5fa'],
            ['label'=>'AÇIK','value'=>Opportunity::where('durum','acik')->count(),'icon'=>'🔓','color'=>'#b8b62e','text'=>'#d4d066'],
            ['label'=>'KAZANILAN','value'=>Opportunity::where('durum','kazanildi')->count(),'icon'=>'🏆','color'=>'#22c55e','text'=>'#4ade80'],
            ['label'=>'POTANSİYEL','value'=>'₺'.number_format((float) Opportunity::whereIn('durum',['acik','beklemede'])->sum('tutar'),0,',','.'),'icon'=>'💰','color'=>'#f59e0b','text'=>'#fdba74'],
        ];

        return view('admin.crm.opportunities.index', compact(
            'opportunities',
            'pipelines',
            'customers',
            'yoneticiler',
            'durumlar',
            'paraBirimleri',
            'statCards'
        ));
    }

    public function create()
    {
        $customers = Customer::orderBy('adi')->get(['id', 'adi', 'unvan']);
        $pipelines = Pipeline::with('stages')->orderBy('sira')->get();
        $yoneticiler = Yonetici::orderBy('adi')->get(['id', 'adi', 'kullaniciadi', 'rol']);
        $paraBirimleri = ['TRY', 'USD', 'EUR', 'AED'];

        return view('admin.crm.opportunities.create', compact('customers', 'pipelines', 'yoneticiler', 'paraBirimleri'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'musteri_id' => 'required|integer|exists:crm_customers,id',
            'pipeline_id' => 'nullable|integer|exists:crm_pipelines,id',
            'stage_id' => 'nullable|integer|exists:crm_stages,id',
            'baslik' => 'required|string|max:180',
            'tutar' => 'nullable|numeric|min:0',
            'para_birimi' => ['required', 'string', 'max:10', Rule::in(['TRY', 'USD', 'EUR', 'AED'])],
            'durum' => ['required', 'string', 'max:50', Rule::in(['acik', 'beklemede', 'kazanildi', 'kaybedildi'])],
            'sorumlu_id' => 'nullable|integer|exists:yoneticiler,id',
            'beklenen_kapanis' => 'nullable|date',
            'oncelik' => 'nullable|integer|min:1|max:5',
            'aciklama' => 'nullable|string',
        ]);

        $stageId = $validated['stage_id'] ?? null;
        if ($stageId) {
            $stage = Stage::findOrFail($stageId);
            $validated['pipeline_id'] = $stage->pipeline_id;
        }

        $opportunity = Opportunity::create($validated);

        // 📧 Müşteriye teklif mailı (admin checkbox işaretlemişse)
        if ($request->has('mail_gonder') && $request->mail_gonder == 1) {
            try {
                \App\Services\CustomerNotifier::teklifGonderildi(
                    $validated['musteri_id'],
                    $opportunity->id
                );
            } catch (\Throwable $e) {
                \Log::warning('Teklif maili gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.crm.firsatlar.index')
            ->with('success', 'Fırsat kaydı oluşturuldu.');
    }

    public function show(int $id)
    {
        $opportunity = Opportunity::with(['musteri', 'pipeline', 'stage', 'sorumlu'])->findOrFail($id);
        return view('admin.crm.opportunities.show', compact('opportunity'));
    }

    public function edit(int $id)
    {
        $opportunity = Opportunity::with(['musteri', 'pipeline', 'stage'])->findOrFail($id);
        $customers = Customer::orderBy('adi')->get(['id', 'adi', 'unvan']);
        $pipelines = Pipeline::with('stages')->orderBy('sira')->get();
        $yoneticiler = Yonetici::orderBy('adi')->get(['id', 'adi', 'kullaniciadi', 'rol']);
        $paraBirimleri = ['TRY', 'USD', 'EUR', 'AED'];

        return view('admin.crm.opportunities.edit', compact('opportunity', 'customers', 'pipelines', 'yoneticiler', 'paraBirimleri'));
    }

    public function update(Request $request, int $id)
    {
        $opportunity = Opportunity::findOrFail($id);

        $validated = $request->validate([
            'musteri_id' => 'required|integer|exists:crm_customers,id',
            'pipeline_id' => 'nullable|integer|exists:crm_pipelines,id',
            'stage_id' => 'nullable|integer|exists:crm_stages,id',
            'baslik' => 'required|string|max:180',
            'tutar' => 'nullable|numeric|min:0',
            'para_birimi' => ['required', 'string', 'max:10', Rule::in(['TRY', 'USD', 'EUR', 'AED'])],
            'durum' => ['required', 'string', 'max:50', Rule::in(['acik', 'beklemede', 'kazanildi', 'kaybedildi'])],
            'sorumlu_id' => 'nullable|integer|exists:yoneticiler,id',
            'beklenen_kapanis' => 'nullable|date',
            'oncelik' => 'nullable|integer|min:1|max:5',
            'aciklama' => 'nullable|string',
        ]);

        $stageId = $validated['stage_id'] ?? null;
        if ($stageId) {
            $stage = Stage::findOrFail($stageId);
            $validated['pipeline_id'] = $stage->pipeline_id;
        }

        $opportunity->update($validated);

        return redirect()->route('admin.crm.firsatlar.index')
            ->with('success', 'Fırsat bilgileri güncellendi.');
    }

    public function destroy(int $id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $opportunity->tasks()->delete();
        $opportunity->notes()->delete();
        $opportunity->delete();

        return redirect()->route('admin.crm.firsatlar.index')
            ->with('success', 'Fırsat kaydı silindi.');
    }

    public function updateStage(Request $request, int $id)
    {
        $opportunity = Opportunity::findOrFail($id);

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:crm_stages,id',
        ]);

        $stage = Stage::with('pipeline')->findOrFail($validated['stage_id']);

        $opportunity->update([
            'stage_id' => $stage->id,
            'pipeline_id' => $stage->pipeline_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fırsat aşaması güncellendi.',
            'stage' => $stage,
        ]);
    }
}