@extends('admin._layout')

@section('title', 'Banka Hesapları')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Banka Hesapları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🏦 Banka Hesapları
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $bankalar->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşterilerin havale yapacağı banka hesaplarını yönet</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.banka.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Hesap</span>
        </a>
    </div>
</div>

@php
    $totalBanka = \DB::table('banka_hesaplari')->count();
    $aktifBanka = \DB::table('banka_hesaplari')->where('durum', 1)->count();
    $pasifBanka = $totalBanka - $aktifBanka;
    $farkliBankaSayisi = \DB::table('banka_hesaplari')->select('banka')->distinct()->count();
@endphp

{{-- 4 Stat Card --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="landmark"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Hesap</div>
            <div class="stat-card-value">{{ $totalBanka }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifBanka }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(148,163,184,0.18);color:#64748b">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value">{{ $pasifBanka }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:var(--brand)">
            <i data-lucide="building-2"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Farklı Banka</div>
            <div class="stat-card-value">{{ $farkliBankaSayisi }}</div>
        </div>
    </div>
</div>

@if($bankalar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="landmark" class="empty-state-icon"></i>
            <h4>Henüz banka hesabı eklenmemiş</h4>
            <p>Müşterilerin havale yapabilmesi için en az bir banka hesabı ekle.</p>
            <a href="{{ route('admin.banka.ekle') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>İlk Hesabı Ekle</span>
            </a>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Banka</th>
                        <th>Hesap Sahibi</th>
                        <th>IBAN</th>
                        <th>Hesap No</th>
                        <th>Şube</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankalar as $b)
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $b->id }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                        {{ strtoupper(mb_substr($b->banka ?? 'B', 0, 1, 'UTF-8')) }}
                                    </div>
                                    <div style="font-weight:600;color:var(--text)">{{ $b->banka ?? '—' }}</div>
                                </div>
                            </td>
                            <td style="font-size:13px">{{ $b->hesap ?? '—' }}</td>
                            <td style="font-family:monospace;font-size:11.5px;color:var(--text-secondary)">
                                @if(!empty($b->iban))
                                    <span title="{{ $b->iban }}">{{ $b->iban }}</span>
                                    <button onclick="copyToClipboard('{{ $b->iban }}', this)" class="icon-btn" style="margin-left:4px;width:20px;height:20px;padding:0;background:transparent;border:none;cursor:pointer;color:var(--text-muted)" title="Kopyala">
                                        <i data-lucide="copy" style="width:11px;height:11px"></i>
                                    </button>
                                @else
                                    <span style="color:var(--text-muted);font-family:inherit">—</span>
                                @endif
                            </td>
                            <td style="font-family:monospace;font-size:12px">{{ $b->hnumara ?? '—' }}</td>
                            <td style="font-size:12.5px;color:var(--text-secondary)">{{ $b->sube ?? '—' }}</td>
                            <td>
                                @if($b->durum == 1)
                                    <span class="badge badge-success">🟢 Aktif</span>
                                @else
                                    <span class="badge badge-danger">🔴 Pasif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.banka.duzenle', $b->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.banka.sil', $b->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($b->banka ?? 'Bu hesap') }} silinsin mi?');" style="margin:0;display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($bankalar->hasPages())
        <div style="margin-top:16px">
            {{ $bankalar->links() }}
        </div>
    @endif
@endif

<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const ico = btn.querySelector('i');
        if (ico) ico.setAttribute('data-lucide', 'check');
        btn.style.color = 'var(--success)';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => {
            if (ico) ico.setAttribute('data-lucide', 'copy');
            btn.style.color = 'var(--text-muted)';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 1500);
    });
}
</script>

@endsection