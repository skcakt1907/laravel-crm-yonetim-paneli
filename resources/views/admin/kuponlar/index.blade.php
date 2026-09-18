@extends('admin._layout')

@section('title', 'Kuponlar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Kuponlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🎫 Kuponlar
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $kuponlar->total() }}</span>
        </h1>
        <div class="page-subtitle">İndirim kuponları oluştur ve yönet</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.kuponlar.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Kupon</span>
        </a>
    </div>
</div>

@php
    $totalKupon = \DB::table('kuponlar')->count();
    $aktifKupon = \DB::table('kuponlar')
        ->where(function($q) {
            $q->whereNull('bit_tarih')
              ->orWhere('bit_tarih', '>=', now()->toDateString());
        })
        ->count();
    $expiredKupon = \DB::table('kuponlar')
        ->whereNotNull('bit_tarih')
        ->where('bit_tarih', '<', now()->toDateString())
        ->count();
    $yuzdeKupon = \DB::table('kuponlar')->where('tur', 1)->count();
    $tutarKupon = \DB::table('kuponlar')->where('tur', 2)->count();
@endphp

{{-- 4 Stat Card --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="ticket"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Kupon</div>
            <div class="stat-card-value">{{ $totalKupon }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif Kupon</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifKupon }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="clock-x"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Süresi Geçen</div>
            <div class="stat-card-value">{{ $expiredKupon }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="percent"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Yüzde / Tutar</div>
            <div class="stat-card-value" style="font-size:18px">{{ $yuzdeKupon }} / {{ $tutarKupon }}</div>
        </div>
    </div>
</div>

@if($kuponlar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="ticket" class="empty-state-icon"></i>
            <h4>Henüz kupon yok</h4>
            <p>İndirim kuponları oluştur, müşterilerine sun.</p>
            <a href="{{ route('admin.kuponlar.ekle') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>İlk Kuponu Ekle</span>
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
                        <th>Kupon Kodu</th>
                        <th>İndirim</th>
                        <th>Tür</th>
                        <th>Başlangıç</th>
                        <th>Bitiş</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kuponlar as $k)
                        @php
                            $tur = (int) ($k->tur ?? 1);
                            $isYuzde = $tur === 1;
                            $kalanGun = null;
                            $isExpired = false;
                            $isUpcoming = false;
                            if (!empty($k->bit_tarih)) {
                                try {
                                    $bitDate = \Carbon\Carbon::parse($k->bit_tarih)->startOfDay();
                                    $today = \Carbon\Carbon::now()->startOfDay();
                                    $kalanGun = $today->diffInDays($bitDate, false);
                                    $isExpired = $kalanGun < 0;
                                } catch (\Throwable $e) {}
                            }
                            if (!empty($k->bas_tarih)) {
                                try {
                                    $basDate = \Carbon\Carbon::parse($k->bas_tarih)->startOfDay();
                                    $isUpcoming = $basDate->isFuture();
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr @if($isExpired)style="opacity:0.55"@endif>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $k->id }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <code style="background:var(--brand-soft);color:var(--brand-dark);font-weight:700;font-size:13px;padding:4px 10px;border-radius:var(--radius-md);font-family:monospace">{{ $k->kod }}</code>
                                    <button onclick="copyToClipboard('{{ $k->kod }}', this)" class="icon-btn" style="width:24px;height:24px;padding:0;background:transparent;border:none;cursor:pointer;color:var(--text-muted)" title="Kopyala">
                                        <i data-lucide="copy" style="width:12px;height:12px"></i>
                                    </button>
                                </div>
                            </td>
                            <td style="font-weight:700;color:var(--brand);font-size:15px">
                                @if($isYuzde)
                                    %{{ rtrim(rtrim(number_format((float)$k->miktar, 2, '.', ''), '0'), '.') }}
                                @else
                                    ₺{{ number_format((float)$k->miktar, 2, ',', '.') }}
                                @endif
                            </td>
                            <td>
                                @if($isYuzde)
                                    <span class="badge badge-warning">📊 Yüzde</span>
                                @else
                                    <span class="badge badge-success">💰 Tutar</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary)">
                                @if(!empty($k->bas_tarih))
                                    {{ \Carbon\Carbon::parse($k->bas_tarih)->format('d.m.Y') }}
                                    @if($isUpcoming)
                                        <div style="font-size:10px;color:var(--info)">Henüz başlamadı</div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px">
                                @if(!empty($k->bit_tarih))
                                    <span style="color:{{ $isExpired ? 'var(--danger)' : 'var(--text-secondary)' }};font-weight:{{ $isExpired ? '700' : '500' }}">
                                        {{ \Carbon\Carbon::parse($k->bit_tarih)->format('d.m.Y') }}
                                    </span>
                                    @if($isExpired)
                                        <div style="font-size:10px;color:var(--danger)">⚠️ {{ abs((int)$kalanGun) }}g geçti</div>
                                    @elseif($kalanGun !== null && $kalanGun <= 7)
                                        <div style="font-size:10px;color:var(--warning);font-weight:600">⏰ {{ (int)$kalanGun }}g kaldı</div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">Süresiz</span>
                                @endif
                            </td>
                            <td>
                                @if($isExpired)
                                    <span class="badge badge-danger">❌ Süresi Doldu</span>
                                @elseif($isUpcoming)
                                    <span class="badge badge-warning">⏳ Beklemede</span>
                                @else
                                    <span class="badge badge-success">🟢 Aktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.kuponlar.duzenle', $k->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.kuponlar.sil', $k->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($k->kod) }} kuponu silinsin mi?');" style="margin:0;display:inline">
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

    @if($kuponlar->hasPages())
        <div style="margin-top:16px">
            {{ $kuponlar->links() }}
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