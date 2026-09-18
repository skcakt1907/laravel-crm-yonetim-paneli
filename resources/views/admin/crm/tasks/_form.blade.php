{{--
    Paylaşılan görev formu — create + edit ortak
    Beklenenler:
        - $task (null veya Task modeli) — edit modunda var
        - $customers, $yoneticiler
--}}
@php
    $isEdit = isset($task) && $task && $task->id;
    $currentMusteriId = old('musteri_id', $task->musteri_id ?? request('musteri_id'));
    $currentAtananId = old('atanan_id', $task->atanan_id ?? null);

    // Coklu atama: secili kisilerin id listesi.
    // old() > kayitli uyeler > (tablo yoksa) tekli atanan
    $currentAtananIds = old('atanan_ids');
    if ($currentAtananIds === null) {
        $currentAtananIds = ($isEdit && \Illuminate\Support\Facades\Schema::hasTable('crm_task_members'))
            ? $task->atananlar()->pluck('yoneticiler.id')->all()
            : [];
    }
    $currentAtananIds = collect($currentAtananIds)->map(fn ($x) => (int) $x)->filter()->unique()->values()->all();
    if (!$currentAtananIds && $currentAtananId) {
        $currentAtananIds = [(int) $currentAtananId];
    }
    $currentDepartman = old('departman', $task->departman ?? 'sirket_genel');

    $currentOncelik = old('oncelik', $task->oncelik ?? 'normal');
    $oncelikList = [
        'dusuk'  => '🟢 Düşük',
        'normal' => '🟡 Normal',
        'yuksek' => '🔴 Yüksek',
    ];

    $currentDurum = old('durum', $task->durum ?? 'beklemede');
    $durumList = [
        'beklemede'  => '📝 Beklemede',
        'devam'      => '⏳ Devam',
        'musteri_bekleniyor' => '📞 Müşteri Bekleniyor',
        'tamamlandi' => '✅ Tamamlandı',
    ];

    $departmanList = \App\Models\CRM\Task::departmanlar();
@endphp

<form action="{{ $isEdit ? route('admin.crm.gorevler.update', $task->id) : route('admin.crm.gorevler.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="form-grid">
        {{-- SOL: Görev bilgileri --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="check-square"></i>
                    <span>Görev Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Konu <span class="required">*</span></label>
                    <input type="text" name="konu" value="{{ old('konu', $task->konu ?? '') }}" required maxlength="180" class="form-input" placeholder="Örn: Müşteri ile fiyat görüşmesi">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Departman</label>
                        <select name="departman" class="form-select">
                            @foreach($departmanList as $key => $label)
                                <option value="{{ $key }}" {{ $currentDepartman == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sorumlular (Atananlar)</label>
                        <div class="ara-secim" data-ara-secim data-coklu>
                            {{-- Secilenler JS ile atanan_ids[] gizli input'lari olarak buraya yazilir --}}
                            <div class="ara-chipler"></div>
                            <input type="text" class="form-input ara-input" placeholder="Yönetici ara veya tıkla..." autocomplete="off">
                            <div class="ara-liste">
                                @foreach($yoneticiler as $y)
                                    <div class="ara-item {{ in_array((int) $y->id, $currentAtananIds, true) ? 'selected' : '' }}"
                                         data-id="{{ $y->id }}" data-ad="{{ $y->adi ?: $y->kullaniciadi }}">{{ $y->adi ?: $y->kullaniciadi }}</div>
                                @endforeach
                                <div class="ara-bos" style="display:none">Sonuç bulunamadı</div>
                            </div>
                        </div>
                        <small class="form-help">Birden fazla kişi seçebilirsiniz. İlk seçilen kişi <strong>birincil sorumlu</strong> olur. Boş bırakılırsa görev atanmamış olur.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="aciklama" rows="5" class="form-textarea" placeholder="Görev detayları, hatırlatmalar, müşteriyle paylaşılacak notlar...">{{ old('aciklama', $task->aciklama ?? '') }}</textarea>
                </div>
            </div>

            {{-- DOSYALAR --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="paperclip"></i>
                    <span>Dosya Ekle</span>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Yeni Dosyalar (çoklu seçim)</label>
                    <input type="file" name="dosyalar[]" multiple class="form-input">
                    <small class="form-help">Sözleşme, PDF, görsel, doküman vb. — Ctrl tuşu ile birden çok seçebilirsin</small>
                </div>

                @if($isEdit)
                    @php
                        $mevcutDosyalar = \Illuminate\Support\Facades\DB::table('crm_task_files')
                            ->where('task_id', $task->id)
                            ->orderByDesc('id')
                            ->get();
                    @endphp
                    @if($mevcutDosyalar->count())
                        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                            <label class="form-label" style="margin-bottom:10px">Mevcut Dosyalar ({{ $mevcutDosyalar->count() }})</label>
                            <div style="display:flex;flex-direction:column;gap:6px">
                                @foreach($mevcutDosyalar as $df)
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md)">
                                        <div style="flex:1;min-width:0">
                                            <a href="{{ asset($df->file_path) }}" target="_blank" style="font-weight:600;color:var(--brand);text-decoration:none;display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                                📄 {{ $df->original_name }}
                                            </a>
                                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                                {{ number_format($df->size/1024, 1) }} KB
                                                @if($df->mime) · {{ $df->mime }} @endif
                                                · {{ \Carbon\Carbon::parse($df->created_at)->diffForHumans() }}
                                            </div>
                                        </div>
                                        <button type="button" onclick="dosyaSil({{ $df->id }})"
                                                class="btn btn-ghost btn-sm" style="color:var(--danger);padding:4px 8px" title="Sil">
                                            <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- SAĞ: Durum + İlişkiler --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="flag"></i>
                    <span>Öncelik & Tarih</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Durum</label>
                    <select name="durum" class="form-select">
                        @foreach($durumList as $key => $label)
                            <option value="{{ $key }}" {{ $currentDurum == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="form-help">Listeden de durum rozetine tıklayıp hızlıca değiştirebilirsin</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Öncelik</label>
                    <select name="oncelik" class="form-select">
                        @foreach($oncelikList as $key => $label)
                            <option value="{{ $key }}" {{ $currentOncelik == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="form-help">Kanban'a taşındığında kartın önceliği olur</small>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Son Tarih</label>
                    <input type="date" name="son_tarih"
                           value="{{ old('son_tarih', isset($task->son_tarih) && $task->son_tarih ? \Carbon\Carbon::parse($task->son_tarih)->format('Y-m-d') : '') }}"
                           class="form-input">
                    <small class="form-help">Görevin tamamlanması gereken son tarih</small>
                </div>

                @if($isEdit && !empty($task->tamamlandi_at))
                    <div style="margin-top:12px;padding:10px 12px;background:var(--success-soft);border:1px solid rgba(16,185,129,.2);border-radius:var(--radius-md);font-size:12px;color:var(--success)">
                        ✅ Tamamlandı: {{ \Carbon\Carbon::parse($task->tamamlandi_at)->format('d.m.Y H:i') }}
                    </div>
                @endif
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="link"></i>
                    <span>İlişkiler</span>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Müşteri</label>
                    <div class="ara-secim" data-ara-secim>
                        <input type="hidden" name="musteri_id" value="{{ $currentMusteriId }}">
                        <div class="ara-secili" style="display:none">
                            <span class="ara-secili-ad"></span>
                            <button type="button" class="ara-temizle" title="Seçimi temizle">✕</button>
                        </div>
                        <input type="text" class="form-input ara-input" placeholder="Müşteri ara veya tıkla..." autocomplete="off">
                        <div class="ara-liste">
                            @foreach($customers as $c)
                                <div class="ara-item {{ $currentMusteriId == $c->id ? 'selected' : '' }}"
                                     data-id="{{ $c->id }}" data-ad="{{ $c->adi }}">{{ $c->adi }}</div>
                            @endforeach
                            <div class="ara-bos" style="display:none">Sonuç bulunamadı</div>
                        </div>
                    </div>
                    <small class="form-help">Görev bir müşteriyle ilgili mi? Yazarak arayabilirsin.</small>
                </div>

                @if($isEdit)
                    @php
                        $olusturanAd = null;
                        if (!empty($task->olusturan_id)) {
                            $olusturanAd = optional(\App\Models\Yonetici::find($task->olusturan_id));
                            $olusturanAd = $olusturanAd ? ($olusturanAd->adi ?: $olusturanAd->kullaniciadi) : null;
                        }
                        $olusturanAd = $olusturanAd ?: ($task->olusturan_adi ?? null);
                    @endphp
                    <div class="form-group" style="margin-top:14px;margin-bottom:0;padding-top:14px;border-top:1px solid var(--border)">
                        <label class="form-label">Oluşturan</label>
                        <div style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md);font-size:13px;color:var(--text-secondary)">
                            <i data-lucide="user" style="width:14px;height:14px"></i>
                            <span>{{ $olusturanAd ?: 'Bilinmiyor' }}</span>
                            @if(!empty($task->created_at))
                                <span style="margin-left:auto;font-size:11px;color:var(--text-muted)">{{ \Carbon\Carbon::parse($task->created_at)->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        <small class="form-help">Görevi oluşturan kişi (otomatik kaydedilir, değiştirilemez)</small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.crm.gorevler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>

        <div style="display:flex;gap:10px">
            @if($isEdit)
                <button type="button" onclick="gorevSil()" class="btn btn-danger">
                    <i data-lucide="trash-2"></i>
                    <span>Sil</span>
                </button>
            @endif

            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>{{ $isEdit ? 'Güncelle' : 'Görev Oluştur' }}</span>
            </button>
        </div>
    </div>
</form>

{{-- ═══════════════════════════════════════════════════════════════
    İÇ İÇE FORM YASAK olduğu için sil form'ları BURADA, ana form DIŞINDA.
    JS ile tetiklenirler.
═══════════════════════════════════════════════════════════════ --}}
@if($isEdit)
    {{-- Görev silme formu --}}
    <form id="gorev-sil-form-{{ $task->id }}"
          action="{{ route('admin.crm.gorevler.destroy', $task->id) }}"
          method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Dosya silme formları (her dosya için ayrı) --}}
    @if(isset($mevcutDosyalar) && $mevcutDosyalar->count())
        @foreach($mevcutDosyalar as $df)
            <form id="dosya-sil-form-{{ $df->id }}"
                  action="{{ route('admin.crm.gorevler.dosya.sil', ['id' => $task->id, 'fileId' => $df->id]) }}"
                  method="POST" style="display:none">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
@endif

<script>
@if($isEdit)
// Görev silme — ana form DIŞINDAKİ gizli form'u submit eder
function gorevSil() {
    if (!confirm('{{ addslashes($task->konu ?? "Bu görev") }} silinsin mi?\nİlgili dosyalar da silinecek!')) return;
    document.getElementById('gorev-sil-form-{{ $task->id }}').submit();
}

// Dosya silme — gizli form'u submit eder
function dosyaSil(fileId) {
    if (!confirm('Dosya silinsin mi?')) return;
    const f = document.getElementById('dosya-sil-form-' + fileId);
    if (f) f.submit();
}
@endif
</script>

{{-- ═══ ARAMA KUTULU DROPDOWN (Sorumlu + Müşteri) ═══ --}}
<style>
    .ara-secim { position: relative; }
    .ara-liste {
        position: absolute; left: 0; right: 0; z-index: 60; display: none;
        max-height: 260px; overflow-y: auto; margin-top: 6px;
        background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb);
        border-radius: var(--radius-md, 10px); box-shadow: 0 8px 24px rgba(0,0,0,.12);
    }
    .ara-liste.open { display: block; }
    .ara-item { padding: 9px 12px; font-size: 14px; cursor: pointer; }
    .ara-item:hover { background: rgba(184,182,46,.12); }
    .ara-item.selected { background: rgba(184,182,46,.18); font-weight: 700; }
    .ara-secili {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        padding: 9px 12px; font-size: 14px; font-weight: 600;
        border: 1px solid var(--brand, #b8b62e); border-radius: var(--radius-md, 10px);
        background: rgba(184,182,46,.10);
    }
    .ara-temizle { border: 0; background: none; cursor: pointer; color: var(--text-muted, #9aa0a6); font-size: 15px; line-height: 1; padding: 2px 4px; }
    .ara-temizle:hover { color: var(--danger, #dc2626); }
    .ara-bos { padding: 10px 12px; font-size: 13px; color: var(--text-muted, #9aa0a6); }

    /* Coklu atama rozetleri */
    .ara-chipler { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
    .ara-chipler:empty { display: none; }
    .ara-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 8px 4px 10px; border-radius: 999px;
        background: rgba(184,182,46,.16); border: 1px solid rgba(184,182,46,.45);
        font-size: 13px; line-height: 1.3; color: var(--text, #1f2419);
    }
    .ara-chip.birincil { background: rgba(184,182,46,.34); font-weight: 700; }
    .ara-chip-sil {
        border: 0; background: none; cursor: pointer; padding: 0 2px;
        color: var(--text-muted, #6b7280); font-size: 14px; line-height: 1;
    }
    .ara-chip-sil:hover { color: var(--danger, #dc2626); }
    .ara-item.selected::after { content: ' ✓'; font-weight: 700; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-ara-secim]').forEach(function (kutu) {

        // ── COKLU KIP ──────────────────────────────────────────────
        // Ayni widget'in cok secimli hali: secilenler rozet olur ve
        // atanan_ids[] gizli input'lari olarak forma yazilir.
        // Listedeki ILK kisi birincil sorumludur (crm_tasks.atanan_id).
        if (kutu.hasAttribute('data-coklu')) {
            var cArama  = kutu.querySelector('.ara-input');
            var cListe  = kutu.querySelector('.ara-liste');
            var cChip   = kutu.querySelector('.ara-chipler');
            var cItems  = Array.prototype.slice.call(kutu.querySelectorAll('.ara-item'));
            var cBos    = kutu.querySelector('.ara-bos');
            var secili  = cItems.filter(function (i) { return i.classList.contains('selected'); })
                                .map(function (i) { return i.getAttribute('data-id'); });

            function cAc()    { cListe.classList.add('open'); }
            function cKapat() { cListe.classList.remove('open'); }

            function cFiltrele() {
                var q = (cArama.value || '').toLocaleLowerCase('tr');
                var gorunen = 0;
                cItems.forEach(function (it) {
                    var hay = (it.getAttribute('data-ad') || it.textContent).toLocaleLowerCase('tr');
                    var goster = q === '' || hay.indexOf(q) !== -1;
                    it.style.display = goster ? '' : 'none';
                    if (goster) gorunen++;
                });
                if (cBos) cBos.style.display = gorunen === 0 ? '' : 'none';
            }

            function cCiz() {
                cChip.innerHTML = '';
                secili.forEach(function (id, sira) {
                    var it = cItems.filter(function (i) { return i.getAttribute('data-id') === id; })[0];
                    if (!it) return;

                    var chip = document.createElement('span');
                    chip.className = 'ara-chip' + (sira === 0 ? ' birincil' : '');
                    chip.title = sira === 0 ? 'Birincil sorumlu' : 'Atanan';
                    chip.textContent = (sira === 0 ? '★ ' : '') + it.getAttribute('data-ad');

                    var sil = document.createElement('button');
                    sil.type = 'button';
                    sil.className = 'ara-chip-sil';
                    sil.textContent = '✕';
                    sil.title = 'Çıkar';
                    sil.addEventListener('click', function () {
                        secili = secili.filter(function (x) { return x !== id; });
                        it.classList.remove('selected');
                        cCiz();
                    });
                    chip.appendChild(sil);
                    cChip.appendChild(chip);

                    var gizliInput = document.createElement('input');
                    gizliInput.type  = 'hidden';
                    gizliInput.name  = 'atanan_ids[]';
                    gizliInput.value = id;
                    cChip.appendChild(gizliInput);
                });
            }

            cItems.forEach(function (it) {
                it.addEventListener('click', function () {
                    var id = it.getAttribute('data-id');
                    if (secili.indexOf(id) !== -1) {
                        secili = secili.filter(function (x) { return x !== id; });
                        it.classList.remove('selected');
                    } else {
                        secili.push(id);
                        it.classList.add('selected');
                    }
                    cCiz();
                    cArama.value = '';
                    cFiltrele();
                    cArama.focus();
                });
            });

            cArama.addEventListener('focus', function () { cFiltrele(); cAc(); });
            cArama.addEventListener('click', function () { cAc(); });
            cArama.addEventListener('input', function () { cFiltrele(); cAc(); });
            document.addEventListener('click', function (e) { if (!kutu.contains(e.target)) cKapat(); });

            cCiz();
            return;   // tekli kip kodu calismasin
        }

        var gizli      = kutu.querySelector('input[type="hidden"]');
        var arama      = kutu.querySelector('.ara-input');
        var liste      = kutu.querySelector('.ara-liste');
        var seciliBox  = kutu.querySelector('.ara-secili');
        var seciliAd   = kutu.querySelector('.ara-secili-ad');
        var temizleBtn = kutu.querySelector('.ara-temizle');
        var items      = Array.prototype.slice.call(kutu.querySelectorAll('.ara-item'));
        var bos        = kutu.querySelector('.ara-bos');

        function ac()    { liste.classList.add('open'); }
        function kapat() { liste.classList.remove('open'); }

        function filtrele() {
            var q = (arama.value || '').toLocaleLowerCase('tr');
            var gorunen = 0;
            items.forEach(function (it) {
                var hay = (it.getAttribute('data-ad') || it.textContent).toLocaleLowerCase('tr');
                var goster = q === '' || hay.indexOf(q) !== -1;
                it.style.display = goster ? '' : 'none';
                if (goster) gorunen++;
            });
            if (bos) bos.style.display = gorunen === 0 ? '' : 'none';
        }

        function sec(it) {
            gizli.value = it.getAttribute('data-id');
            seciliAd.textContent = it.getAttribute('data-ad');
            seciliBox.style.display = '';
            arama.style.display = 'none';
            items.forEach(function (i) { i.classList.remove('selected'); });
            it.classList.add('selected');
            kapat();
        }

        function temizle() {
            gizli.value = '';
            seciliBox.style.display = 'none';
            arama.style.display = '';
            arama.value = '';
            items.forEach(function (i) { i.classList.remove('selected'); });
            filtrele();
            arama.focus();
            ac();
        }

        arama.addEventListener('focus', function () { filtrele(); ac(); });
        arama.addEventListener('click', function () { ac(); });
        arama.addEventListener('input', function () { filtrele(); ac(); });
        items.forEach(function (it) { it.addEventListener('click', function () { sec(it); }); });
        temizleBtn.addEventListener('click', temizle);
        document.addEventListener('click', function (e) { if (!kutu.contains(e.target)) kapat(); });

        // Edit / old() değeri varsa açılışta seçili göster
        if (gizli.value) {
            var onceki = null;
            items.forEach(function (i) { if (i.classList.contains('selected')) onceki = i; });
            if (onceki) sec(onceki);
        }
    });
});
</script>