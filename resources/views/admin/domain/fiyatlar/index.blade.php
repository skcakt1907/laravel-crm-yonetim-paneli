@extends('admin._layout')

@section('title', 'Alan Adı Fiyatları')

@push('head')
<style>
    .uzanti-chip {
        display: inline-block;
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 13px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
    }

    .fiyat-cell { font-weight: 700; color: var(--brand-dark); }
    .fiyat-transfer { font-weight: 600; color: var(--text); }

    .drag-handle:active { cursor: grabbing; }
    .sortable-ghost { opacity: .4; background: var(--brand-soft); }
    #fiyatTbody tr { transition: background .15s ease; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Alan Adı Fiyatları</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="dollar-sign"></i>
            Alan Adı Fiyatları
            <span class="badge badge-brand">{{ $fiyatlar->total() }}</span>
        </h1>
        <div class="page-subtitle">
            Domain uzantıları ve kayıt/yenileme/transfer fiyatlarınızı yönetin · <span style="color:var(--brand-dark)">satırları sürükleyerek sıralayın</span>
            @if(!empty($usingLegacyTable))
                · <span style="color:var(--warning)">Legacy tablo (alanadi) kullanılıyor</span>
            @endif
        </div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.satislar.domain'))
            <a href="{{ route('admin.satislar.domain') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="bar-chart-3"></i>
                <span>Domain Satışları</span>
            </a>
        @endif
        @if(empty($tableMissing) && empty($usingLegacyTable))
            <a href="{{ route('admin.domain.fiyatlar.ekle') }}" class="btn btn-primary">
                <i data-lucide="plus"></i>
                <span>Yeni Uzantı</span>
            </a>
        @endif
    </div>
</div>

{{-- TABLO YOK UYARISI --}}
@if(!empty($tableMissing))
    <div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px">
        <i data-lucide="alert-triangle" style="width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
        <div>
            <strong>Domain fiyat tablosu bulunamadı.</strong>
            <div style="margin-top:6px;font-size:13px">
                <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">domain_fiyatlar</code>
                veya
                <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">alanadi</code>
                tablosunun oluşturulması gerekir.
            </div>
        </div>
    </div>
@endif

{{-- LEGACY UYARI --}}
@if(!empty($usingLegacyTable))
    <div class="alert alert-info" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;background:rgba(59,130,246,0.06);border-color:rgba(59,130,246,0.3)">
        <i data-lucide="info" style="width:20px;height:20px;flex-shrink:0;margin-top:2px;color:#3b82f6"></i>
        <div>
            <strong>Eski tablo yapısı (`alanadi`) kullanılıyor.</strong>
            <div style="margin-top:6px;font-size:13px">
                Mevcut kayıtlar düzenlenebilir ancak <strong>yeni kayıt eklenemez</strong>.
                Yeni `domain_fiyatlar` tablosuna geçiş için sistem yöneticinizle iletişime geçin.
            </div>
        </div>
    </div>
@endif

{{-- STAT KARTLARI --}}
@php
    $toplam = $fiyatlar->total();
    $aktif = 0; $ortKayit = 0; $maxKayit = 0;
    try {
        if (\Schema::hasTable('domain_fiyatlar')) {
            $aktif = \DB::table('domain_fiyatlar')->where('durum', 1)->count();
            $row = \DB::table('domain_fiyatlar')->selectRaw('AVG(kayit_fiyat) as ort, MAX(kayit_fiyat) as mx')->first();
            $ortKayit = (float)($row->ort ?? 0);
            $maxKayit = (float)($row->mx ?? 0);
        } elseif (\Schema::hasTable('alanadi')) {
            // Legacy: tüm aktif sayılır
            $aktif = $toplam;
        }
    } catch (\Throwable $e) {}
@endphp

<div class="stat-grid" style="margin-bottom:20px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="globe"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Uzantı</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Ortalama Kayıt Fiyatı</div>
            <div class="stat-card-value" style="color:var(--brand-dark);font-size:22px">
                ₺{{ number_format($ortKayit, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="award"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">En Yüksek Fiyat</div>
            <div class="stat-card-value" style="color:var(--success);font-size:22px">
                ₺{{ number_format($maxKayit, 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if($fiyatlar->isEmpty())
    @if(empty($tableMissing))
        <div class="section">
            <div class="empty-state">
                <i data-lucide="globe" class="empty-state-icon"></i>
                <h4>Henüz fiyat kaydı yok</h4>
                <p>İlk domain uzantısı fiyatını ekleyerek başlayın.</p>
                @if(empty($usingLegacyTable))
                    <div style="margin-top:16px">
                        <a href="{{ route('admin.domain.fiyatlar.ekle') }}" class="btn btn-primary">
                            <i data-lucide="plus"></i>
                            <span>Yeni Uzantı</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table" id="fiyatTablo">
                <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th style="width:140px">Uzantı</th>
                        <th>Kayıt Fiyatı</th>
                        <th>Yenileme Fiyatı</th>
                        <th>Transfer Fiyatı</th>
                        <th style="width:120px">Durum</th>
                        <th style="text-align:right;width:120px">İşlem</th>
                    </tr>
                </thead>
                <tbody id="fiyatTbody">
                    @foreach($fiyatlar as $f)
                        @php
                            $durum = (int)($f->durum ?? 1);
                            $isLegacy = !empty($f->legacy_id);
                        @endphp
                        <tr data-id="{{ $f->id }}">
                            <td style="text-align:center;cursor:grab" class="drag-handle" title="Sürükleyerek sırala">
                                <i data-lucide="grip-vertical" style="width:16px;height:16px;color:var(--text-muted)"></i>
                            </td>
                            <td>
                                <span class="uzanti-chip">
                                    @if(!str_starts_with($f->uzanti ?? '', '.'))
                                        .{{ $f->uzanti }}
                                    @else
                                        {{ $f->uzanti }}
                                    @endif
                                </span>
                                @if($isLegacy)
                                    <div style="font-size:10px;color:var(--text-muted);margin-top:4px">
                                        <i data-lucide="archive" style="width:10px;height:10px;display:inline;vertical-align:middle"></i>
                                        Legacy
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="fiyat-cell">
                                    ₺{{ number_format((float)($f->kayit_fiyat ?? 0), 2, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                <span class="fiyat-cell">
                                    ₺{{ number_format((float)($f->yenileme_fiyat ?? 0), 2, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if((float)($f->transfer_fiyat ?? 0) > 0)
                                    <span class="fiyat-transfer">
                                        ₺{{ number_format((float)$f->transfer_fiyat, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                @if($durum === 1)
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width:11px;height:11px"></i>
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge badge-neutral">
                                        <i data-lucide="pause" style="width:11px;height:11px"></i>
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.domain.fiyatlar.duzenle', $f->id) }}"
                                       class="table-action" title="Düzenle">
                                        <i data-lucide="edit-3"></i>
                                    </a>
                                    @if(!$isLegacy)
                                        <button type="button" class="table-action"
                                                title="Sil" style="color:var(--danger)"
                                                onclick="silFiyat({{ $f->id }}, '{{ addslashes($f->uzanti ?? '') }}');">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($fiyatlar->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:13px;color:var(--text-muted)">
                    {{ $fiyatlar->firstItem() ?? 0 }} – {{ $fiyatlar->lastItem() ?? 0 }} / Toplam
                    <strong style="color:var(--text)">{{ $fiyatlar->total() }}</strong>
                </div>
                {{ $fiyatlar->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- SİL FORMLARI (sadece non-legacy için) --}}
    @foreach($fiyatlar as $f)
        @if(empty($f->legacy_id))
            <form id="del-fiyat-{{ $f->id }}"
                  action="{{ route('admin.domain.fiyatlar.sil', $f->id) }}"
                  method="POST" style="display:none">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
@endif

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
function silFiyat(id, uzanti) {
    if (confirm('Bu fiyatı silmek istediğinize emin misiniz?\n\nUzantı: ' + uzanti)) {
        document.getElementById('del-fiyat-' + id).submit();
    }
}

// Sürükle-bırak sıralama
(function () {
    var tbody = document.getElementById('fiyatTbody');
    if (!tbody || typeof Sortable === 'undefined') return;

    var siraUrl = '{{ route('admin.domain.fiyatlar.sira') }}';
    var csrf = '{{ csrf_token() }}';

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function (tr) {
                return tr.getAttribute('data-id');
            });
            fetch(siraUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ sira: ids })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    if (window.toastSuccess) { toastSuccess('Sıralama kaydedildi'); }
                } else {
                    alert('Sıralama kaydedilemedi: ' + (d.message || ''));
                }
            })
            .catch(function (e) { alert('Sıralama kaydedilemedi: ' + e.message); });
        }
    });
})();
</script>
@endsection