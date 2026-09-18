<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Pipeline;
use App\Models\CRM\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineController extends Controller
{
    public function index()
    {
        $pipelines = Pipeline::with('stages')->orderBy('sira')->get();

        return view('admin.crm.pipelines.index', compact('pipelines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:150',
            'aciklama' => 'nullable|string|max:255',
            'varsayilan' => 'nullable|boolean',
        ]);

        $payload = [
            'adi' => $validated['adi'],
            'aciklama' => $validated['aciklama'] ?? null,
            'varsayilan' => $request->boolean('varsayilan'),
            'sira' => Pipeline::max('sira') + 1,
        ];

        DB::transaction(function () use ($payload) {
            if ($payload['varsayilan']) {
                Pipeline::query()->update(['varsayilan' => false]);
            }

            Pipeline::create($payload);
        });

        return redirect()->route('admin.crm.pipelines.index')
            ->with('success', 'Pipeline başarıyla oluşturuldu.');
    }

    public function update(Request $request, int $id)
    {
        $pipeline = Pipeline::findOrFail($id);

        $validated = $request->validate([
            'adi' => 'required|string|max:150',
            'aciklama' => 'nullable|string|max:255',
            'varsayilan' => 'nullable|boolean',
            'sira' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($pipeline, $validated, $request) {
            $pipeline->adi = $validated['adi'];
            $pipeline->aciklama = $validated['aciklama'] ?? null;

            if ($request->filled('sira')) {
                $pipeline->sira = (int) $validated['sira'];
            }

            if ($request->boolean('varsayilan')) {
                Pipeline::query()
                    ->where('id', '<>', $pipeline->id)
                    ->update(['varsayilan' => false]);

                $pipeline->varsayilan = true;
            }

            $pipeline->save();
        });

        return redirect()->route('admin.crm.pipelines.index')
            ->with('success', 'Pipeline güncellendi.');
    }

    public function destroy(int $id)
    {
        $pipeline = Pipeline::withCount('opportunities')->findOrFail($id);

        if ($pipeline->varsayilan) {
            return redirect()->route('admin.crm.pipelines.index')
                ->with('error', 'Varsayılan pipeline silinemez.');
        }

        if ($pipeline->opportunities_count > 0) {
            return redirect()->route('admin.crm.pipelines.index')
                ->with('error', 'Pipeline içerisinde fırsat olduğu için silinemez.');
        }

        $pipeline->stages()->delete();
        $pipeline->delete();

        return redirect()->route('admin.crm.pipelines.index')
            ->with('success', 'Pipeline silindi.');
    }

    public function storeStage(Request $request, int $pipelineId)
    {
        $pipeline = Pipeline::findOrFail($pipelineId);

        $validated = $request->validate([
            'adi' => 'required|string|max:150',
            'olasilik' => 'nullable|integer|min:0|max:100',
            'renk' => 'nullable|string|max:20',
        ]);

        $pipeline->stages()->create([
            'adi' => $validated['adi'],
            'olasilik' => $validated['olasilik'] ?? 0,
            'renk' => $validated['renk'] ?? null,
            'sira' => $pipeline->stages()->max('sira') + 1,
        ]);

        return redirect()->route('admin.crm.pipelines.index')
            ->with('success', 'Pipeline aşaması eklendi.');
    }

    public function updateStage(Request $request, int $pipelineId, int $stageId)
    {
        $pipeline = Pipeline::findOrFail($pipelineId);
        $stage = $pipeline->stages()->where('id', $stageId)->firstOrFail();

        $validated = $request->validate([
            'adi' => 'required|string|max:150',
            'olasilik' => 'nullable|integer|min:0|max:100',
            'renk' => 'nullable|string|max:20',
            'sira' => 'nullable|integer|min:0',
        ]);

        $stage->adi = $validated['adi'];
        $stage->olasilik = $validated['olasilik'] ?? 0;
        $stage->renk = $validated['renk'] ?? null;

        if ($request->filled('sira')) {
            $stage->sira = (int) $validated['sira'];
        }

        $stage->save();

        return redirect()->route('admin.crm.pipelines.index')
            ->with('success', 'Aşama güncellendi.');
    }

    public function destroyStage(int $pipelineId, int $stageId)
    {
        $pipeline = Pipeline::findOrFail($pipelineId);
        $stage = $pipeline->stages()->where('id', $stageId)->firstOrFail();

        if ($stage->opportunities()->exists()) {
            return redirect()->route('admin.crm.pipelines.index')
                ->with('error', 'Bu aşamada fırsatlar bulunduğu için silinemez.');
        }

        $stage->delete();

        return redirect()->route('admin.crm.pipelines.index')
            ->with('success', 'Aşama silindi.');
    }
}

