@extends('admin._layout')

@section('title', 'Pipelines')

@push('head')
<style>
    .pipeline-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        margin-bottom: 16px;
        transition: border-color 0.15s;
    }
    .pipeline-card:hover { border-color: var(--brand-medium); }
    .pipeline-card.is-default {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px var(--brand-soft);
    }

    .pipeline-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: var(--bg-subtle);
        border-bottom: 1px solid var(--border);
    }
    .pipeline-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--brand), #8a8a1f);
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .pipeline-title {
        flex: 1;
        min-width: 0;
    }
    .pipeline-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pipeline-desc {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .pipeline-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }

    /* Stages flow */
    .stages-flow {
        padding: 16px 20px;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        align-items: stretch;
        min-height: 80px;
    }

    .stage-card {
        flex: 1;
        min-width: 160px;
        max-width: 220px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 10px 12px;
        position: relative;
        transition: all 0.15s;
    }
    .stage-card:hover { border-color: var(--brand-medium); }

    .stage-card.colored {
        border-left-width: 3px;
    }

    .stage-name {
        font-weight: 600;
        font-size: 13px;
        color: var(--text);
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }

    .stage-prob {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 999px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        flex-shrink: 0;
    }
    body.theme-dark .stage-prob { color: var(--brand); }

    .stage-actions {
        position: absolute;
        top: 6px;
        right: 6px;
        display: flex;
        gap: 2px;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .stage-card:hover .stage-actions { opacity: 1; }

    .stage-arrow {
        display: flex;
        align-items: center;
        color: var(--text-muted);
        font-size: 18px;
        flex-shrink: 0;
        padding: 0 2px;
    }

    .add-stage-card {
        flex-shrink: 0;
        min-width: 140px;
        background: transparent;
        border: 2px dashed var(--border);
        border-radius: var(--radius-md);
        padding: 10px 12px;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.15s;
        text-align: center;
    }
    .add-stage-card:hover {
        border-color: var(--brand);
        color: var(--brand);
        background: var(--brand-soft);
    }

    .icon-btn-sm {
        width: 24px;
        height: 24px;
        border-radius: 5px;
        border: none;
        background: var(--surface);
        color: var(--text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        padding: 0;
    }
    .icon-btn-sm:hover { background: var(--brand-soft); color: var(--brand); }
    .icon-btn-sm.danger:hover { background: rgba(239,68,68,0.1); color: var(--danger); }

    /* Inline form */
    .inline-form-row {
        background: var(--bg-subtle);
        padding: 12px 20px;
        border-bottom: 1px solid var(--border);
    }
    .inline-form-row.hidden { display: none; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Pipelines</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🔄 Satış Pipeline'ları
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ count($pipelines) }}</span>
        </h1>
        <div class="page-subtitle">Satış süreçlerini ve aşamalarını tanımla. Fırsatlar bu aşamalar arasında ilerler.</div>
    </div>
    <div class="page-actions">
        <button type="button" onclick="toggleNewPipelineForm()" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Pipeline</span>
        </button>
    </div>
</div>

{{-- 4 Stat Card --}}
@php
    $totalStages = $pipelines->sum(fn($p) => count($p->stages ?? []));
    $defaultPipeline = $pipelines->firstWhere('varsayilan', true);
    $totalFirsatlar = 0;
    try {
        $totalFirsatlar = \App\Models\CRM\Opportunity::count();
    } catch (\Throwable $e) {}
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="git-branch"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Pipeline</div>
            <div class="stat-card-value">{{ count($pipelines) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="layers"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Aşama</div>
            <div class="stat-card-value">{{ $totalStages }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:var(--brand)">
            <i data-lucide="star"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Varsayılan</div>
            <div class="stat-card-value" style="font-size:16px">
                {{ $defaultPipeline->adi ?? 'Yok' }}
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Fırsat</div>
            <div class="stat-card-value">{{ $totalFirsatlar }}</div>
        </div>
    </div>
</div>

{{-- YENİ PIPELINE FORM (gizli, butonla açılır) --}}
<div id="newPipelineForm" class="section" style="display:none;border:2px dashed var(--brand);background:var(--brand-soft);margin-bottom:16px">
    <div class="section-title">
        <i data-lucide="git-branch-plus"></i>
        <span>Yeni Pipeline Ekle</span>
    </div>
    <form action="{{ route('admin.crm.pipelines.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Pipeline Adı <span class="required">*</span></label>
                <input type="text" name="adi" required maxlength="150" class="form-input" placeholder="Örn: Web Tasarım Süreci" autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <input type="text" name="aciklama" maxlength="255" class="form-input" placeholder="Bu pipeline ne için kullanılacak?">
            </div>
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                <input type="checkbox" name="varsayilan" value="1" style="width:auto;margin:0">
                <span><strong>⭐ Varsayılan pipeline yap</strong> — yeni fırsatlar otomatik bu pipeline'a atanır</span>
            </label>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="toggleNewPipelineForm()" class="btn btn-secondary btn-sm">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="save"></i>
                <span>Pipeline Oluştur</span>
            </button>
        </div>
    </form>
</div>

{{-- PIPELINE LİSTESİ --}}
@if($pipelines->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="git-branch" class="empty-state-icon"></i>
            <h4>Henüz pipeline yok</h4>
            <p>İlk satış pipeline'ını oluştur. Sonra içine aşamalar ekleyerek fırsatlarını yönetebilirsin.</p>
            <button type="button" onclick="toggleNewPipelineForm()" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>İlk Pipeline'ı Oluştur</span>
            </button>
        </div>
    </div>
@else
    @foreach($pipelines as $p)
        @php
            $isDefault = $p->varsayilan;
            $stages = $p->stages ?? collect();
            // sira'ya göre sırala
            $stages = $stages->sortBy('sira')->values();
        @endphp

        <div class="pipeline-card {{ $isDefault ? 'is-default' : '' }}">
            {{-- HEADER --}}
            <div class="pipeline-header">
                <div class="pipeline-icon">🔄</div>
                <div class="pipeline-title">
                    <div class="pipeline-name">
                        {{ $p->adi }}
                        @if($isDefault)
                            <span class="badge badge-brand" style="font-size:10px;padding:2px 7px">⭐ Varsayılan</span>
                        @endif
                        <span class="badge badge-neutral" style="font-size:10px;padding:2px 7px">{{ $stages->count() }} aşama</span>
                    </div>
                    @if(!empty($p->aciklama))
                        <div class="pipeline-desc">{{ $p->aciklama }}</div>
                    @endif
                </div>
                <div class="pipeline-actions">
                    <button type="button" onclick="togglePipelineEdit({{ $p->id }})" class="btn btn-secondary btn-sm" title="Düzenle">
                        <i data-lucide="edit-2"></i>
                    </button>
                    <button type="button" onclick="toggleNewStageForm({{ $p->id }})" class="btn btn-primary btn-sm" title="Aşama Ekle">
                        <i data-lucide="plus"></i>
                        <span>Aşama Ekle</span>
                    </button>
                    @if(!$isDefault)
                        <form action="{{ route('admin.crm.pipelines.destroy', $p->id) }}" method="POST"
                              onsubmit="return confirm('{{ addslashes($p->adi) }} pipeline silinsin mi?\nTüm aşamalar da silinecek!\n\nNot: Pipeline içinde fırsat varsa silinemez.');"
                              style="margin:0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger)" title="Sil">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- DÜZENLE FORM (gizli) --}}
            <div id="editPipelineForm-{{ $p->id }}" class="inline-form-row hidden">
                <form action="{{ route('admin.crm.pipelines.update', $p->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-grid">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:11px">Pipeline Adı *</label>
                            <input type="text" name="adi" value="{{ $p->adi }}" required maxlength="150" class="form-input" style="height:36px;font-size:13px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:11px">Açıklama</label>
                            <input type="text" name="aciklama" value="{{ $p->aciklama }}" maxlength="255" class="form-input" style="height:36px;font-size:13px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:11px">Sıra</label>
                            <input type="number" name="sira" value="{{ $p->sira }}" min="0" class="form-input" style="height:36px;font-size:13px">
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;flex-wrap:wrap;gap:10px">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px">
                            <input type="checkbox" name="varsayilan" value="1" {{ $isDefault ? 'checked' : '' }} style="width:auto;margin:0">
                            <span>⭐ Varsayılan</span>
                        </label>
                        <div style="display:flex;gap:6px">
                            <button type="button" onclick="togglePipelineEdit({{ $p->id }})" class="btn btn-secondary btn-sm">İptal</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i data-lucide="save"></i>
                                <span>Kaydet</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- YENİ AŞAMA FORM (gizli) --}}
            <div id="newStageForm-{{ $p->id }}" class="inline-form-row hidden">
                <form action="{{ route('admin.crm.pipelines.stages.store', $p->id) }}" method="POST">
                    @csrf
                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;align-items:end">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:11px">Aşama Adı *</label>
                            <input type="text" name="adi" required maxlength="150" class="form-input" style="height:36px;font-size:13px" placeholder="Örn: İlk Görüşme">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:11px">Olasılık (%)</label>
                            <input type="number" name="olasilik" min="0" max="100" value="0" class="form-input" style="height:36px;font-size:13px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:11px">Renk</label>
                            <input type="color" name="renk" value="#b8b62e" class="form-input" style="height:36px;padding:2px">
                        </div>
                        <div style="display:flex;gap:6px">
                            <button type="button" onclick="toggleNewStageForm({{ $p->id }})" class="btn btn-secondary btn-sm" style="height:36px">
                                <i data-lucide="x"></i>
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm" style="height:36px">
                                <i data-lucide="check"></i>
                                <span>Ekle</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- STAGES FLOW --}}
            <div class="stages-flow">
                @if($stages->isEmpty())
                    <div style="flex:1;padding:24px;text-align:center;color:var(--text-muted);font-size:13px">
                        Henüz aşama yok. Üstteki <strong>"Aşama Ekle"</strong> butonu ile başla.
                    </div>
                @else
                    @foreach($stages as $index => $s)
                        <div class="stage-card {{ !empty($s->renk) ? 'colored' : '' }}" style="@if(!empty($s->renk))border-left-color:{{ $s->renk }}@endif">
                            <div class="stage-actions">
                                <button type="button" onclick="toggleStageEdit({{ $p->id }}, {{ $s->id }})" class="icon-btn-sm" title="Düzenle">
                                    <i data-lucide="edit-2" style="width:11px;height:11px"></i>
                                </button>
                                <form action="{{ route('admin.crm.pipelines.stages.destroy', ['pipeline' => $p->id, 'stage' => $s->id]) }}" method="POST"
                                      onsubmit="return confirm('{{ addslashes($s->adi) }} aşaması silinsin mi?\n\nNot: Aşamada fırsat varsa silinemez.');"
                                      style="margin:0;display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn-sm danger" title="Sil">
                                        <i data-lucide="trash-2" style="width:11px;height:11px"></i>
                                    </button>
                                </form>
                            </div>

                            <div id="stageDisplay-{{ $s->id }}">
                                <div class="stage-name">
                                    <span>{{ $s->adi }}</span>
                                    <span class="stage-prob">%{{ $s->olasilik ?? 0 }}</span>
                                </div>
                                <div style="font-size:10.5px;color:var(--text-muted)">
                                    Sıra: {{ $s->sira ?? 0 }}
                                </div>
                            </div>

                            {{-- Inline edit form (her stage için, gizli) --}}
                            <div id="stageEdit-{{ $s->id }}" style="display:none;margin-top:4px">
                                <form action="{{ route('admin.crm.pipelines.stages.update', ['pipeline' => $p->id, 'stage' => $s->id]) }}" method="POST">
                                    @csrf @method('PUT')
                                    <input type="text" name="adi" value="{{ $s->adi }}" required maxlength="150" class="form-input" style="height:30px;font-size:12px;margin-bottom:4px" placeholder="Aşama adı">
                                    <div style="display:flex;gap:4px;margin-bottom:4px">
                                        <input type="number" name="olasilik" value="{{ $s->olasilik ?? 0 }}" min="0" max="100" class="form-input" style="height:30px;font-size:11px;flex:1" placeholder="%">
                                        <input type="number" name="sira" value="{{ $s->sira ?? 0 }}" min="0" class="form-input" style="height:30px;font-size:11px;width:50px" placeholder="Sıra">
                                    </div>
                                    <div style="display:flex;gap:4px;margin-bottom:4px">
                                        <input type="color" name="renk" value="{{ $s->renk ?: '#b8b62e' }}" class="form-input" style="height:28px;padding:1px;flex:1">
                                    </div>
                                    <div style="display:flex;gap:4px">
                                        <button type="button" onclick="toggleStageEdit({{ $p->id }}, {{ $s->id }})" class="btn btn-ghost btn-sm" style="height:28px;font-size:11px;flex:1">İptal</button>
                                        <button type="submit" class="btn btn-primary btn-sm" style="height:28px;font-size:11px;flex:1">
                                            <i data-lucide="check" style="width:11px;height:11px"></i>
                                            <span>Kaydet</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if(!$loop->last)
                            <div class="stage-arrow">→</div>
                        @endif
                    @endforeach
                @endif

                {{-- Hızlı aşama ekleme kartı --}}
                <button type="button" onclick="toggleNewStageForm({{ $p->id }})" class="add-stage-card" title="Yeni aşama ekle">
                    <span style="font-size:20px">＋</span>
                    <span>Aşama Ekle</span>
                </button>
            </div>
        </div>
    @endforeach
@endif

{{-- Bilgi paneli --}}
<div class="section" style="margin-top:24px;background:var(--brand-soft);border:1px solid var(--brand-medium)">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <i data-lucide="info" style="color:var(--brand);width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
        <div style="flex:1;font-size:13px;color:var(--text-secondary);line-height:1.6">
            <strong style="color:var(--text)">Pipeline & Aşama Nasıl Çalışır?</strong>
            <ul style="margin:6px 0 0 18px;padding:0">
                <li><strong>Pipeline:</strong> Bir satış sürecinin tamamı (örn: "Web Tasarım", "Hosting Satış")</li>
                <li><strong>Aşama (Stage):</strong> Süreçteki adımlar (örn: "Lead → Görüşme → Teklif → Sözleşme → Kazanıldı")</li>
                <li><strong>Olasılık:</strong> Bu aşamadaki fırsatın kapanma yüzdesi (0-100)</li>
                <li><strong>Varsayılan:</strong> Yeni fırsatlar bu pipeline'a otomatik atanır (sadece 1 tane olabilir)</li>
                <li><strong>Sıra:</strong> Aşamaların gösterim sırası (küçük → büyük)</li>
            </ul>
        </div>
    </div>
</div>

<script>
function toggleNewPipelineForm() {
    const form = document.getElementById('newPipelineForm');
    const isOpen = form.style.display === 'block';
    form.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        form.querySelector('input[name="adi"]')?.focus();
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function togglePipelineEdit(id) {
    const form = document.getElementById('editPipelineForm-' + id);
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        form.querySelector('input[name="adi"]')?.focus();
    }
}

function toggleNewStageForm(pipelineId) {
    // Diğer açık formları kapat (UX için)
    document.querySelectorAll('[id^="newStageForm-"]').forEach(f => {
        if (f.id !== 'newStageForm-' + pipelineId) f.classList.add('hidden');
    });

    const form = document.getElementById('newStageForm-' + pipelineId);
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        form.querySelector('input[name="adi"]')?.focus();
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function toggleStageEdit(pipelineId, stageId) {
    const display = document.getElementById('stageDisplay-' + stageId);
    const edit = document.getElementById('stageEdit-' + stageId);
    const isEditing = edit.style.display === 'block';
    display.style.display = isEditing ? 'block' : 'none';
    edit.style.display = isEditing ? 'none' : 'block';
    if (!isEditing) {
        edit.querySelector('input[name="adi"]')?.focus();
    }
}
</script>

@endsection