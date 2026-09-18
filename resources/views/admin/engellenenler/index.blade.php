@extends('admin._layout')

@section('title', '🚫 Engellenen Kullanıcılar')

@push('head')
<style>
    .eng-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:20px; }
    .eng-stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg);
        padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .eng-stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center;
        justify-content:center; font-size:20px; }
    .eng-stat-num { font-size:22px; font-weight:800; color:var(--text); line-height:1.1; }
    .eng-stat-lab { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; }

    .eng-table { width:100%; border-collapse:collapse; background:var(--surface);
        border-radius:var(--radius-lg); overflow:hidden; }
    .eng-table thead tr { background:var(--bg-subtle); }
    .eng-table th { text-align:left; padding:12px 14px; font-size:11px; text-transform:uppercase;
        letter-spacing:.05em; color:var(--text-muted); font-weight:700; }
    .eng-table tbody tr { border-top:1px solid var(--border); transition:background .15s; }
    .eng-table tbody tr:hover { background:var(--bg-subtle); }
    .eng-table td { padding:12px 14px; font-size:13px; color:var(--text); vertical-align:middle; }

    .eng-avatar { width:36px; height:36px; border-radius:50%; background:rgba(239,68,68,.12);
        color:#dc2626; display:inline-flex; align-items:center; justify-content:center;
        font-weight:700; font-size:13px; }
    .eng-info-cell { display:flex; align-items:center; gap:10px; }
    .eng-info-name { font-weight:600; }
    .eng-info-meta { font-size:11px; color:var(--text-muted); margin-top:2px; }

    .eng-action-btn { padding:5px 10px; border:none; cursor:pointer; border-radius:6px;
        font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .eng-action-btn.unblock { background:rgba(16,185,129,.12); color:#10b981; }
    .eng-action-btn.unblock:hover { background:rgba(16,185,129,.22); }
    .eng-action-btn.delete { background:rgba(239,68,68,.12); color:#dc2626; }
    .eng-action-btn.delete:hover { background:rgba(239,68,68,.22); }

    .eng-bulk-bar { background:var(--brand-soft); border:1px solid var(--brand);
        border-radius:var(--radius); padding:10px 14px; margin-bottom:12px;
        display:none; align-items:center; gap:12px; }
    .eng-bulk-bar.show { display:flex; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.musteriler.index') }}">Müşteriler</a>
    <span class="sep">/</span>
    <span class="current">Engellenenler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🚫 Engellenen Kullanıcılar</h1>
        <div class="page-subtitle">Pasif duruma alınmış kullanıcıların yönetimi (uyeler.durum = 0)</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.musteriler.index') }}" class="btn btn-secondary">
            <i data-lucide="users"></i>
            <span>Tüm Müşteriler</span>
        </a>
    </div>
</div>

{{-- İstatistik --}}
<div class="eng-stat-grid">
    <div class="eng-stat-card">
        <div class="eng-stat-icon" style="background:rgba(239,68,68,.12);color:#dc2626">🚫</div>
        <div>
            <div class="eng-stat-num">{{ number_format($istatistik['toplam']) }}</div>
            <div class="eng-stat-lab">Toplam Engellenen</div>
        </div>
    </div>
    <div class="eng-stat-card">
        <div class="eng-stat-icon" style="background:rgba(251,146,60,.12);color:#f97316">📅</div>
        <div>
            <div class="eng-stat-num">{{ number_format($istatistik['bu_ay']) }}</div>
            <div class="eng-stat-lab">Bu Ay Engellenen</div>
        </div>
    </div>
    <div class="eng-stat-card">
        <div class="eng-stat-icon" style="background:rgba(16,185,129,.12);color:#10b981">✓</div>
        <div>
            <div class="eng-stat-num">{{ number_format($istatistik['aktif_toplam']) }}</div>
            <div class="eng-stat-lab">Aktif Kullanıcı</div>
        </div>
    </div>
</div>

{{-- Arama --}}
<form method="GET" action="{{ route('admin.engellenenler.index') }}" class="section" style="padding:14px;margin-bottom:14px">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" name="q" value="{{ $q }}" placeholder="Ad, soyad, e-posta, telefon, firma..."
               class="form-input" style="flex:1;min-width:240px">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search"></i><span>Ara</span>
        </button>
        @if($q)
            <a href="{{ route('admin.engellenenler.index') }}" class="btn btn-ghost btn-sm">Temizle</a>
        @endif
    </div>
</form>

{{-- Toplu işlem bar (seçim varsa açılır) --}}
<div id="engBulkBar" class="eng-bulk-bar">
    <strong>
        <span id="engSelectedCount">0</span> kullanıcı seçildi
    </strong>
    <button type="button" onclick="engBulkAction('aktive')" class="btn btn-success btn-sm">
        <i data-lucide="check-circle"></i><span>Engeli Kaldır</span>
    </button>
    <button type="button" onclick="engBulkAction('sil')" class="btn btn-secondary btn-sm" style="color:var(--danger)">
        <i data-lucide="trash-2"></i><span>Tamamen Sil</span>
    </button>
    <button type="button" onclick="engClearSelection()" class="btn btn-ghost btn-sm" style="margin-left:auto">
        İptal
    </button>
</div>

{{-- Liste --}}
@if($engellenenler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="user-x" class="empty-state-icon"></i>
            <h4>Engellenen kullanıcı yok</h4>
            <p>Henüz hiç engellenmiş kullanıcı bulunmuyor.</p>
        </div>
    </div>
@else
    <div class="section" style="padding:0;overflow-x:auto">
        <table class="eng-table">
            <thead>
                <tr>
                    <th style="width:30px;text-align:center">
                        <input type="checkbox" id="engSelectAll" onchange="engToggleAll(this)">
                    </th>
                    <th style="width:50px">#</th>
                    <th>Kullanıcı</th>
                    <th>İletişim</th>
                    <th>Firma</th>
                    <th>Kayıt</th>
                    <th>Engel Tarihi</th>
                    <th style="text-align:right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @foreach($engellenenler as $u)
                    @php
                        $kt = null; $ut = null;
                        try { $kt = $u->ktarih ? \Carbon\Carbon::parse($u->ktarih)->format('d.m.Y') : null; } catch(\Throwable $e) {}
                        try { $ut = $u->updated_at ? \Carbon\Carbon::parse($u->updated_at)->format('d.m.Y H:i') : null; } catch(\Throwable $e) {}
                        $tam = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
                        $bas = mb_strtoupper(mb_substr($tam ?: ($u->email ?? '?'), 0, 1, 'UTF-8'), 'UTF-8');
                    @endphp
                    <tr>
                        <td style="text-align:center">
                            <input type="checkbox" class="eng-check" value="{{ $u->id }}" onchange="engUpdateBulkBar()">
                        </td>
                        <td style="color:var(--text-muted);font-size:11px">#{{ $u->id }}</td>
                        <td>
                            <div class="eng-info-cell">
                                <div class="eng-avatar">{{ $bas }}</div>
                                <div>
                                    <div class="eng-info-name">{{ $tam ?: '—' }}</div>
                                    @if($u->tc)
                                        <div class="eng-info-meta">TC: {{ $u->tc }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:12px">{{ $u->email ?? '—' }}</div>
                            <div class="eng-info-meta">{{ $u->telefon ?? '—' }}</div>
                        </td>
                        <td>{{ $u->firmaadi ?? '—' }}</td>
                        <td style="color:var(--text-muted);font-size:11px">{{ $kt ?? '—' }}</td>
                        <td style="color:var(--text-muted);font-size:11px">{{ $ut ?? '—' }}</td>
                        <td style="text-align:right;white-space:nowrap">
                            <button type="button" class="eng-action-btn unblock"
                                    onclick="engAktiveEt({{ $u->id }}, '{{ addslashes($tam ?: $u->email) }}')"
                                    title="Engeli kaldır">
                                <i data-lucide="check-circle" style="width:12px;height:12px"></i>
                                <span>Aç</span>
                            </button>
                            <button type="button" class="eng-action-btn delete"
                                    onclick="engSil({{ $u->id }}, '{{ addslashes($tam ?: $u->email) }}')"
                                    title="Tamamen sil">
                                <i data-lucide="trash-2" style="width:12px;height:12px"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px">
        {{ $engellenenler->links() }}
    </div>
@endif


{{-- Gizli formlar (iç içe form yasak) --}}
@foreach($engellenenler as $u)
    <form id="eng-aktive-{{ $u->id }}" method="POST" style="display:none"
          action="{{ route('admin.engellenenler.aktive', $u->id) }}">
        @csrf
    </form>
    <form id="eng-sil-{{ $u->id }}" method="POST" style="display:none"
          action="{{ route('admin.engellenenler.sil', $u->id) }}">
        @csrf @method('DELETE')
    </form>
@endforeach

<form id="engBulkAktiveForm" method="POST" style="display:none"
      action="{{ route('admin.engellenenler.toplu-aktive') }}">
    @csrf
    <div id="engBulkAktiveIds"></div>
</form>

<form id="engBulkSilForm" method="POST" style="display:none"
      action="{{ route('admin.engellenenler.toplu-sil') }}">
    @csrf @method('DELETE')
    <div id="engBulkSilIds"></div>
</form>


<script>
function engAktiveEt(id, name) {
    if (!confirm('"' + name + '" kullanıcısının engelini kaldırmak istediğine emin misin?')) return;
    document.getElementById('eng-aktive-' + id).submit();
}

function engSil(id, name) {
    if (!confirm('"' + name + '" kullanıcısını TAMAMEN silmek istediğine emin misin?\n\nBu işlem geri alınamaz!\nKullanıcı uyeler tablosundan tamamen kaldırılır.')) return;
    document.getElementById('eng-sil-' + id).submit();
}

function engToggleAll(master) {
    document.querySelectorAll('.eng-check').forEach(c => c.checked = master.checked);
    engUpdateBulkBar();
}

function engClearSelection() {
    document.querySelectorAll('.eng-check').forEach(c => c.checked = false);
    document.getElementById('engSelectAll').checked = false;
    engUpdateBulkBar();
}

function engUpdateBulkBar() {
    const checked = document.querySelectorAll('.eng-check:checked');
    const bar = document.getElementById('engBulkBar');
    const count = document.getElementById('engSelectedCount');
    count.innerText = checked.length;
    if (checked.length > 0) {
        bar.classList.add('show');
    } else {
        bar.classList.remove('show');
    }
}

function engBulkAction(action) {
    const checked = Array.from(document.querySelectorAll('.eng-check:checked')).map(c => c.value);
    if (checked.length === 0) return;

    const msg = action === 'aktive'
        ? checked.length + ' kullanıcının engelini kaldırmak istediğine emin misin?'
        : checked.length + ' kullanıcıyı TAMAMEN silmek istediğine emin misin?\n\nBu işlem geri alınamaz!';

    if (!confirm(msg)) return;

    const formId = action === 'aktive' ? 'engBulkAktiveForm' : 'engBulkSilForm';
    const idsDiv = action === 'aktive' ? 'engBulkAktiveIds' : 'engBulkSilIds';

    const div = document.getElementById(idsDiv);
    div.innerHTML = '';
    checked.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        div.appendChild(input);
    });

    document.getElementById(formId).submit();
}
</script>

@endsection