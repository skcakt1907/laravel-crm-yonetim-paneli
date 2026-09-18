@extends('admin._layout')

@section('title', 'İletişim Mesajları')

@push('head')
<style>
    .msg-row.unread { background: rgba(184,182,46,0.06); }
    .msg-row.unread td:first-child { border-left: 3px solid var(--brand); }
    .msg-row td:first-child { border-left: 3px solid transparent; }
    .msg-checkbox { width: 18px; height: 18px; accent-color: var(--brand); cursor: pointer; }
    .bulk-bar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px; background: var(--brand-soft);
        border-bottom: 1px solid rgba(184,182,46,0.2);
        flex-wrap: wrap; gap: 10px;
    }
    .bulk-bar label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; cursor:pointer; }
    .sender-cell .sender-name { font-weight: 600; font-size: 14px; }
    .sender-cell .sender-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .contact-cell { font-size: 13px; }
    .contact-cell .phone { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .subject-cell { max-width: 280px; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">İletişim Mesajları</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="mail"></i>
            İletişim Mesajları
            <span class="badge badge-brand">{{ $mesajlar->total() ?? count($mesajlar ?? []) }}</span>
        </h1>
        <div class="page-subtitle">Web sitesi iletişim formundan gelen mesajları yönetin</div>
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $totalCount = (method_exists($mesajlar ?? null, 'total')) ? $mesajlar->total() : count($mesajlar ?? []);
    $unreadCount = $okunmamisSayisi ?? 0;
    $readCount = max(0, $totalCount - $unreadCount);

    // Bu ay - gerçek DB sorgusu (controller'da yok ama tablo varsa hesaplanabilir)
    $buAyCount = 0;
    try {
        if (\Schema::hasTable('iletisim')) {
            // Önce 'tarih', sonra 'created_at' kolonunu dene
            if (\Schema::hasColumn('iletisim', 'tarih')) {
                $buAyCount = \DB::table('iletisim')
                    ->whereMonth('tarih', now()->month)
                    ->whereYear('tarih', now()->year)
                    ->count();
            } elseif (\Schema::hasColumn('iletisim', 'created_at')) {
                $buAyCount = \DB::table('iletisim')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
            }
        }
    } catch (\Throwable $e) {}
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="inbox"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Mesaj</div>
            <div class="stat-card-value">{{ $totalCount }}</div>
        </div>
    </div>

    <div class="stat-card" style="@if($unreadCount > 0)border-color:rgba(245,158,11,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="mail-warning"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Okunmamış</div>
            <div class="stat-card-value" style="@if($unreadCount > 0)color:var(--warning)@endif">{{ $unreadCount }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Yeni mesaj</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Okundu</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $readCount }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay</div>
            <div class="stat-card-value">{{ $buAyCount }}</div>
        </div>
    </div>
</div>

{{-- UYARI BANNER (okunmamış varsa) --}}
@if($unreadCount > 0)
<div class="alert alert-warning" style="margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <i data-lucide="bell" style="width:24px;height:24px;flex-shrink:0"></i>
    <div>
        <strong>Okunmamış mesajlar var!</strong>
        <span style="opacity:.8">{{ $unreadCount }} adet okunmamış iletişim mesajınız bulunmaktadır.</span>
    </div>
</div>
@endif

{{-- TABLO ya da EMPTY STATE --}}
@if(empty($mesajlar) || (is_object($mesajlar) && $mesajlar->isEmpty()))
    <div class="section">
        <div class="empty-state">
            <i data-lucide="inbox" class="empty-state-icon"></i>
            <h4>Henüz mesaj yok</h4>
            <p>Web sitesinin iletişim formundan gelen mesajlar burada listelenecek.</p>
        </div>
    </div>
@else

{{-- TOPLU SİL FORMU --}}
<form id="topluForm" action="{{ route('admin.iletisim.toplu-sil') }}" method="POST"
      onsubmit="return confirmTopluSil();">
    @csrf
    <input type="hidden" name="ids" id="selectedIds">

    <div class="table-wrap">
        {{-- BULK BAR --}}
        <div class="bulk-bar">
            <label>
                <input type="checkbox" id="selectAll" class="msg-checkbox"
                       onchange="toggleAll(this);">
                <span>Tümünü Seç</span>
                <span class="badge badge-neutral" id="selectedBadge" style="display:none">
                    <span id="selectedCount">0</span> seçili
                </span>
            </label>
            <button type="submit" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                <i data-lucide="trash-2"></i>
                <span>Seçilileri Sil</span>
            </button>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:44px"></th>
                        <th>Gönderen</th>
                        <th>İletişim</th>
                        <th>Konu</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th style="text-align:right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mesajlar as $m)
                    @php
                        $isUnread = ($m->durum ?? 0) == 0;
                        $tarihFmt = null;
                        $rawTarih = $m->tarih ?? $m->created_at ?? null;
                        if (!empty($rawTarih)) {
                            try {
                                $tarihFmt = \Carbon\Carbon::parse($rawTarih)->format('d.m.Y H:i');
                            } catch (\Throwable $e) {}
                        }
                        $gonderen = $m->ad ?? $m->isim ?? '—';
                    @endphp
                    <tr class="msg-row {{ $isUnread ? 'unread' : '' }}">
                        <td>
                            <input type="checkbox" class="message-checkbox msg-checkbox"
                                   value="{{ $m->id }}"
                                   onchange="updateSelectedCount();">
                        </td>
                        <td class="sender-cell">
                            <div class="sender-name">{{ $gonderen }}</div>
                            @if($isUnread)
                                <div class="sender-meta" style="color:var(--brand-dark);font-weight:600">
                                    <i data-lucide="circle" style="width:8px;height:8px;fill:currentColor"></i>
                                    Yeni
                                </div>
                            @endif
                        </td>
                        <td class="contact-cell">
                            <div><i data-lucide="mail" style="width:13px;height:13px;display:inline"></i> {{ $m->email ?? '—' }}</div>
                            @if(!empty($m->telefon))
                                <div class="phone"><i data-lucide="phone" style="width:13px;height:13px;display:inline"></i> {{ $m->telefon }}</div>
                            @endif
                        </td>
                        <td class="subject-cell">
                            {{ \Illuminate\Support\Str::limit($m->konu ?? '—', 50) }}
                        </td>
                        <td>
                            @if($isUnread)
                                <span class="badge badge-warning">Yeni</span>
                            @else
                                <span class="badge badge-success">Okundu</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:var(--text-muted)">
                            {{ $tarihFmt ?? '—' }}
                        </td>
                        <td style="text-align:right">
                            <div class="table-actions">
                                <a href="{{ route('admin.iletisim.detay', $m->id) }}"
                                   class="table-action" title="Görüntüle">
                                    <i data-lucide="eye"></i>
                                </a>
                                <button type="button" class="table-action"
                                        title="Durumu Değiştir"
                                        onclick="document.getElementById('durum-form-{{ $m->id }}').submit();">
                                    <i data-lucide="{{ $isUnread ? 'check' : 'mail' }}"></i>
                                </button>
                                <button type="button" class="table-action"
                                        title="Sil" style="color:var(--danger)"
                                        onclick="silMesaj({{ $m->id }});">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($mesajlar, 'hasPages') && $mesajlar->hasPages())
            <div style="padding:16px;border-top:1px solid var(--border)">
                {{ $mesajlar->withQueryString()->links() }}
            </div>
        @endif
    </div>
</form>

{{-- SİL FORMLARI (ANA FORMUN DIŞINDA — HTML İÇ İÇE FORM YASAK) --}}
@foreach($mesajlar as $m)
    <form id="del-form-{{ $m->id }}"
          action="{{ route('admin.iletisim.sil', $m->id) }}"
          method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
    <form id="durum-form-{{ $m->id }}"
          action="{{ route('admin.iletisim.durum', $m->id) }}"
          method="POST" style="display:none">
        @csrf
    </form>
@endforeach

@endif

<script>
function toggleAll(checkbox) {
    document.querySelectorAll('.message-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.message-checkbox:checked');
    const count = checked.length;
    const badge = document.getElementById('selectedBadge');
    const countEl = document.getElementById('selectedCount');
    const btn = document.getElementById('bulkDeleteBtn');

    countEl.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
    btn.disabled = count === 0;

    document.getElementById('selectedIds').value =
        JSON.stringify(Array.from(checked).map(cb => cb.value));

    // selectAll checkbox state senkron
    const all = document.querySelectorAll('.message-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (all.length > 0) {
        selectAll.checked = count === all.length;
        selectAll.indeterminate = count > 0 && count < all.length;
    }
}

function confirmTopluSil() {
    const count = document.querySelectorAll('.message-checkbox:checked').length;
    if (count === 0) {
        alert('Lütfen silmek için en az bir mesaj seçin.');
        return false;
    }
    return confirm(count + ' adet mesajı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');
}

function silMesaj(id) {
    if (confirm('Bu mesajı silmek istediğinizden emin misiniz?')) {
        document.getElementById('del-form-' + id).submit();
    }
}
</script>

@endsection