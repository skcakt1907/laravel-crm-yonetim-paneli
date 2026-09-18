{{--
    Paylaşılan form partial — create + edit ortak
    Beklenenler:
        - $opportunity (null veya Opportunity modeli)
        - $customers, $pipelines, $yoneticiler, $paraBirimleri
--}}
@php
    $isEdit = isset($opportunity) && $opportunity && $opportunity->id;
    $currentMusteriId = old('musteri_id', $opportunity->musteri_id ?? null);
    $currentPipelineId = old('pipeline_id', $opportunity->pipeline_id ?? null);
    $currentStageId = old('stage_id', $opportunity->stage_id ?? null);
    $currentParaBirimi = old('para_birimi', $opportunity->para_birimi ?? 'TRY');
    $currentDurum = old('durum', $opportunity->durum ?? 'acik');
    $currentOncelik = old('oncelik', $opportunity->oncelik ?? 2);
    $currentSorumluId = old('sorumlu_id', $opportunity->sorumlu_id ?? null);

    $oncelikList = [
        1 => '🟢 Düşük',
        2 => '🟡 Normal',
        3 => '🟠 Yüksek',
        4 => '🔴 Acil',
        5 => '⚫ Kritik',
    ];

    $durumList = [
        'acik' => '🔓 Açık',
        'beklemede' => '⏳ Beklemede',
        'kazanildi' => '🏆 Kazanıldı',
        'kaybedildi' => '❌ Kaybedildi',
    ];
@endphp

<form action="{{ $isEdit ? route('admin.crm.firsatlar.update', $opportunity->id) : route('admin.crm.firsatlar.store') }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="form-grid">
        {{-- SOL: Temel Bilgiler --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="trending-up"></i>
                    <span>Fırsat Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Başlık <span class="required">*</span></label>
                    <input type="text" name="baslik" value="{{ old('baslik', $opportunity->baslik ?? '') }}" required maxlength="180" class="form-input" placeholder="Örn: Kurumsal Web Tasarım Projesi">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Müşteri <span class="required">*</span></label>
                        <select name="musteri_id" required class="form-select">
                            <option value="">— Müşteri seç —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $currentMusteriId == $c->id ? 'selected' : '' }}>
                                    {{ $c->adi }}{{ !empty($c->unvan) ? ' — '.$c->unvan : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sorumlu</label>
                        <select name="sorumlu_id" class="form-select">
                            <option value="">— Seçilmedi —</option>
                            @foreach($yoneticiler as $y)
                                <option value="{{ $y->id }}" {{ $currentSorumluId == $y->id ? 'selected' : '' }}>
                                    {{ $y->adi ?: $y->kullaniciadi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="aciklama" rows="5" class="form-textarea" placeholder="Fırsat hakkında detaylar, müşterinin ihtiyaçları, görüşme notları...">{{ old('aciklama', $opportunity->aciklama ?? '') }}</textarea>
                </div>
            </div>

            {{-- Pipeline & Stage --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="git-branch"></i>
                    <span>Satış Aşaması</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Pipeline</label>
                        <select name="pipeline_id" id="pipeline_id" class="form-select">
                            <option value="">— Pipeline seç —</option>
                            @foreach($pipelines as $p)
                                <option value="{{ $p->id }}" {{ $currentPipelineId == $p->id ? 'selected' : '' }}>
                                    {{ $p->adi ?? $p->ad ?? 'Pipeline #'.$p->id }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-help">İşin hangi süreçte ilerlediğini takip etmek için</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Aşama (Stage)</label>
                        <select name="stage_id" id="stage_id" class="form-select">
                            <option value="">— Önce pipeline seç —</option>
                            @foreach($pipelines as $p)
                                @foreach($p->stages ?? [] as $s)
                                    <option value="{{ $s->id }}"
                                            data-pipeline="{{ $s->pipeline_id }}"
                                            {{ $currentStageId == $s->id ? 'selected' : '' }}>
                                        {{ $s->adi ?? $s->ad ?? 'Stage #'.$s->id }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Mali, durum, son tarih --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="dollar-sign"></i>
                    <span>Mali Bilgiler</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Tutar</label>
                    <input type="number" step="0.01" min="0" name="tutar" value="{{ old('tutar', $opportunity->tutar ?? '') }}" class="form-input" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Para Birimi</label>
                    <select name="para_birimi" required class="form-select">
                        @foreach($paraBirimleri as $pb)
                            <option value="{{ $pb }}" {{ $currentParaBirimi == $pb ? 'selected' : '' }}>{{ $pb }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="flag"></i>
                    <span>Durum & Öncelik</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Durum <span class="required">*</span></label>
                    <select name="durum" required class="form-select">
                        @foreach($durumList as $key => $label)
                            <option value="{{ $key }}" {{ $currentDurum == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Öncelik</label>
                    <select name="oncelik" class="form-select">
                        @foreach($oncelikList as $key => $label)
                            <option value="{{ $key }}" {{ $currentOncelik == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Beklenen Kapanış</label>
                    <input type="date" name="beklenen_kapanis" value="{{ old('beklenen_kapanis', isset($opportunity->beklenen_kapanis) ? \Carbon\Carbon::parse($opportunity->beklenen_kapanis)->format('Y-m-d') : '') }}" class="form-input">
                    <small class="form-help">Bu fırsatın ne zaman kapanmasını bekliyorsun?</small>
                </div>
                
                <div class="form-group" style="margin-top:16px;padding:14px;background:var(--bg-subtle);border-radius:var(--radius)">
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0">
        <input type="checkbox" name="mail_gonder" value="1">
        <span><strong>📧 Teklifi müşteriye mail olarak gönder</strong></span>
    </label>
</div>
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.crm.firsatlar.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>

        <div style="display:flex;gap:10px">
            @if($isEdit)
                <button type="button" onclick="firsatSil()" class="btn btn-danger">
                    <i data-lucide="trash-2"></i>
                    <span>Sil</span>
                </button>
            @endif

            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>{{ $isEdit ? 'Güncelle' : 'Fırsat Oluştur' }}</span>
            </button>
        </div>
    </div>
</form>

{{-- ═══════════════════════════════════════════════════════════════
    İÇ İÇE FORM YASAK olduğu için sil form'u BURADA, ana form DIŞINDA.
═══════════════════════════════════════════════════════════════ --}}
@if($isEdit)
    <form id="firsat-sil-form-{{ $opportunity->id }}"
          action="{{ route('admin.crm.firsatlar.destroy', $opportunity->id) }}"
          method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endif

<script>
// Pipeline değişince stage'leri filtrele
(function() {
    const pipelineSelect = document.getElementById('pipeline_id');
    const stageSelect = document.getElementById('stage_id');
    if (!pipelineSelect || !stageSelect) return;

    function filterStages() {
        const pid = pipelineSelect.value;
        let visibleCount = 0;
        let firstVisible = null;

        Array.from(stageSelect.options).forEach(opt => {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            const dp = opt.dataset.pipeline;
            const match = !pid || dp === pid;
            opt.hidden = !match;
            if (match) {
                visibleCount++;
                if (!firstVisible) firstVisible = opt;
            }
        });

        const selectedOpt = stageSelect.options[stageSelect.selectedIndex];
        if (selectedOpt && selectedOpt.hidden) {
            stageSelect.value = '';
        }

        const emptyOpt = stageSelect.querySelector('option[value=""]');
        if (emptyOpt) {
            emptyOpt.textContent = pid ? '— Aşama seç —' : '— Önce pipeline seç —';
        }
    }

    pipelineSelect.addEventListener('change', filterStages);
    filterStages();
})();

@if($isEdit)
// Fırsat silme — ana form DIŞINDAKİ gizli form'u submit eder
function firsatSil() {
    if (!confirm('{{ addslashes($opportunity->baslik ?? "Bu fırsat") }} kaydını silmek istediğine emin misin?\nİlgili tüm görev ve notlar da silinecek!')) return;
    document.getElementById('firsat-sil-form-{{ $opportunity->id }}').submit();
}
@endif
</script>