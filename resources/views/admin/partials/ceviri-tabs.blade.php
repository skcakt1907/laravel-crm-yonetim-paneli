{{--
    Çeviri Tabs Partial — TR/EN/AR  (admin-theme.css uyumlu, Alpine/Tailwind GEREKTİRMEZ)

    Parametreler:
      $tablo    : string (zorunlu) — örn: 'yazilimlar', 'hostingler'
      $kayit_id : int|null         — düzenleme için id; yeni eklemede null
      $fields   : array            — ['adi' => ['label'=>'Paket Adı', 'type'=>'text|textarea|wysiwyg', 'rows'=>3, 'required'=>true, 'placeholder'=>'...'], ...]
      $tr_values: array (ops.)      — Türkçe alanların ön-dolu değerleri

    name="ceviriler[en][adi]" formatında POST eder. TR alanları doğrudan name="adi" gider.
    Controller: \App\Models\Ceviri::sync($tablo, $request->input('ceviriler', []), $id);
--}}
@php
    $diller = [
        'tr' => ['ad' => 'Türkçe',   'kod' => 'TR'],
        'en' => ['ad' => 'English',  'kod' => 'EN'],
        'ar' => ['ad' => 'العربية',  'kod' => 'AR'],
    ];
    $tr_values = $tr_values ?? [];
    $mevcut = [];
    if (!empty($kayit_id ?? null)) {
        $mevcut = \App\Models\Ceviri::getAllForForm($tablo, (int) $kayit_id);
    }
    // Aynı sayfada birden fazla partial çağrılırsa çakışmasın diye benzersiz id
    $cvId = 'cv_' . \Illuminate\Support\Str::random(6);
@endphp

<div class="section ceviri-tabs" id="{{ $cvId }}">
    <div class="section-title" style="justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="display:inline-flex;align-items:center;gap:8px">
            <i data-lucide="languages"></i>
            İçerik (TR / EN / AR)
        </span>
        <span style="font-size:11.5px;color:var(--text-muted);font-weight:500">
            EN/AR boş bırakılırsa Türkçe metin kullanılır.
        </span>
    </div>

    {{-- Tab nav --}}
    <div class="tab-nav" role="tablist">
        @foreach($diller as $kod => $dil)
            <button type="button"
                    class="tab-nav-link cv-tab {{ $kod === 'tr' ? 'active' : '' }}"
                    data-cv-tab="{{ $kod }}"
                    role="tab"
                    aria-selected="{{ $kod === 'tr' ? 'true' : 'false' }}">
                <span style="font-size:10px;font-weight:700;letter-spacing:.04em;opacity:.7">{{ $dil['kod'] }}</span>
                <span>{{ $dil['ad'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Tab panels --}}
    @foreach($diller as $kod => $dil)
        <div class="cv-panel" data-cv-panel="{{ $kod }}"
             style="display:{{ $kod === 'tr' ? 'block' : 'none' }}"
             @if($kod === 'ar') dir="rtl" @endif>
            <div class="form-grid">
                @foreach($fields as $alan => $cfg)
                    @php
                        $tip         = $cfg['type'] ?? 'text';
                        $label       = $cfg['label'] ?? $alan;
                        $rows        = $cfg['rows'] ?? 3;
                        $placeholder = $cfg['placeholder'] ?? '';
                        $isText      = ($tip === 'text');
                        $isTr        = ($kod === 'tr');
                        $fieldName   = $isTr ? $alan : "ceviriler[$kod][$alan]";
                        $isRequired  = $isTr && ($cfg['required'] ?? false);
                        $value       = $isTr
                            ? old($alan, $tr_values[$alan] ?? '')
                            : old("ceviriler.$kod.$alan", $mevcut[$kod][$alan] ?? '');
                    @endphp
                    <div class="form-group {{ $isText ? '' : 'full' }}">
                        <label class="form-label">
                            {{ $label }}
                            @unless($isTr)<span style="opacity:.55;font-weight:500"> ({{ $dil['kod'] }})</span>@endunless
                            @if($isRequired)<span class="required">*</span>@endif
                        </label>

                        @if($tip === 'text')
                            <input type="text" name="{{ $fieldName }}" value="{{ $value }}"
                                   placeholder="{{ $placeholder }}" class="form-input"
                                   @if($isRequired) required @endif>
                        @elseif($tip === 'wysiwyg')
                            <textarea name="{{ $fieldName }}" rows="{{ max($rows, 6) }}"
                                      placeholder="{{ $placeholder }}"
                                      class="form-textarea rich-full">{{ $value }}</textarea>
                        @else
                            <textarea name="{{ $fieldName }}" rows="{{ $rows }}"
                                      placeholder="{{ $placeholder }}"
                                      class="form-textarea">{{ $value }}</textarea>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
(function () {
    const root = document.getElementById('{{ $cvId }}');
    if (!root) return;
    const tabs   = root.querySelectorAll('.cv-tab');
    const panels = root.querySelectorAll('.cv-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const lang = tab.getAttribute('data-cv-tab');

            tabs.forEach(function (t) {
                const on = t === tab;
                t.classList.toggle('active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            panels.forEach(function (p) {
                p.style.display = (p.getAttribute('data-cv-panel') === lang) ? 'block' : 'none';
            });

            // TinyMCE alanları gizli sekmede yanlış boyutlanmasın diye tetikle
            if (window.tinymce) {
                window.dispatchEvent(new Event('resize'));
            }
        });
    });
})();
</script>