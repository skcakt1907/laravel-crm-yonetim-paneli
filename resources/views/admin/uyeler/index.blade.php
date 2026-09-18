@extends('admin._layout')

@section('title', 'Üyeler')

@push('head')
<style>
    .uye-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .uye-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .uye-name {
        font-weight: 600;
        font-size: 14px;
    }
    .uye-id {
        font-size: 11px;
        color: var(--text-muted);
        font-family: 'JetBrains Mono', monospace;
    }
    .filter-bar {
        display: grid;
        grid-template-columns: 1fr 200px auto auto;
        gap: 10px;
        align-items: end;
    }
    @media (max-width: 768px) {
        .filter-bar { grid-template-columns: 1fr; }
    }
    .quick-cell {
        font-size: 13px;
    }
    .quick-cell-sub {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* === Uyeler tablosu sutun hizalama duzeltmesi === */
    .uye-tablo { width: 100%; border-collapse: collapse; table-layout: auto; }
    .uye-tablo th, .uye-tablo td {
        padding: 12px 14px;
        vertical-align: middle;
        text-align: left;
        white-space: nowrap;
    }
    .uye-tablo th { font-size: 12px; color: var(--text-muted); font-weight: 600; }
    /* Iletisim ve Firma uzun olabilir, taşmasın */
    .uye-tablo td .quick-cell,
    .uye-tablo td .uye-name { white-space: normal; word-break: break-word; }
    /* Islem sutunu en sagda ve dar */
    .uye-tablo th.col-islem, .uye-tablo td.col-islem { text-align: right; width: 140px; }
    .uye-tablo th.col-uye, .uye-tablo td.col-uye { min-width: 220px; }
    /* Avatar bas harfi net gorunsun */
    .uye-avatar {
        line-height: 1;
        text-transform: uppercase;
    }
    .uye-tablo .table-actions { display: inline-flex; gap: 6px; justify-content: flex-end; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Üyeler</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="users"></i>
            Üyeler
            <span class="badge badge-brand">{{ $uyeler->total() }}</span>
        </h1>
        <div class="page-subtitle">Tüm kayıtlı müşterileri yönet, ara ve düzenle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.uyeler.export') }}" class="btn btn-secondary">
            <i data-lucide="download"></i>
            <span>Tümünü İndir</span>
        </a>
        <a href="{{ route('admin.uyeler.export-page') }}?page={{ $uyeler->currentPage() }}" class="btn btn-secondary">
            <i data-lucide="file-down"></i>
            <span>Sayfayı İndir</span>
        </a>
        <a href="{{ route('admin.uyeler.ekle') }}" class="btn btn-primary">
            <i data-lucide="user-plus"></i>
            <span>Yeni Üye</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $aktifSayi = \DB::table('uyeler')->where('durum', 1)->count();
    $pasifSayi = \DB::table('uyeler')->where('durum', 0)->count();
    $buAySayi = 0;
    try {
        $buAySayi = \DB::table('uyeler')
            ->whereYear('tarih', now()->year)
            ->whereMonth('tarih', now()->month)
            ->count();
    } catch (\Throwable $e) {}
    $kurumsalSayi = \DB::table('uyeler')->where('utipi', 1)->count();
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,.12);color:var(--brand-dark)">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Üye</div>
            <div class="stat-card-value">{{ $uyeler->total() }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.12);color:#10b981">
            <i data-lucide="user-check"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value">{{ $aktifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,.12);color:#ef4444">
            <i data-lucide="user-x"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value">{{ $pasifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">
            <i data-lucide="building-2"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Kurumsal</div>
            <div class="stat-card-value">{{ $kurumsalSayi }}</div>
        </div>
    </div>
</div>

{{-- FİLTRE BARI --}}
<form method="GET" action="{{ route('admin.uyeler.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div class="filter-bar">
        <div>
            <label class="form-label" style="font-size:12px">Arama</label>
            <input type="text" name="q" value="{{ $arama }}" class="form-input"
                   placeholder="Ad, soyad, e-posta, telefon, firma...">
        </div>
        <div>
            <label class="form-label" style="font-size:12px">Durum</label>
            <select name="durum" class="form-select">
                <option value="">Tümü</option>
                <option value="1" @if($durum === '1' || $durum === 1) selected @endif>Aktif</option>
                <option value="0" @if($durum === '0' || $durum === 0) selected @endif>Pasif</option>
                <option value="2" @if($durum === '2' || $durum === 2) selected @endif>Engelli</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search"></i>
            <span>Filtrele</span>
        </button>
        @if($arama || $durum !== null && $durum !== '')
        <a href="{{ route('admin.uyeler.index') }}" class="btn btn-ghost">
            <i data-lucide="x"></i>
            <span>Temizle</span>
        </a>
        @endif
    </div>
</form>

{{-- TABLO --}}
@if($uyeler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="user-x" class="empty-state-icon"></i>
            <h4>Üye bulunamadı</h4>
            <p>@if($arama || ($durum !== null && $durum !== ''))Filtre kriterlerinize uygun üye yok. Filtreyi temizleyip tekrar deneyin.@else Henüz hiç üye kaydı yok. İlk üyeyi eklemek için yukarıdaki butonu kullanın.@endif</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table uye-tablo">
                <thead>
                    <tr>
                        <th class="col-uye">Üye</th>
                        <th>İletişim</th>
                        <th>Firma</th>
                        <th>Tip</th>
                        <th>Durum</th>
                        <th>Kayıt</th>
                        <th class="col-islem">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($uyeler as $u)
                    @php
                        $ad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
                        if (empty($ad)) $ad = $u->adi_soyadi ?? '—';
                        $bas = strtoupper(mb_substr($u->ad ?? $ad, 0, 1));
                        $email = $u->email ?? $u->eposta ?? null;
                        $tel = $u->telefon ?? $u->gsm ?? null;
                        $firma = $u->firmaadi ?? $u->firma ?? null;
                        $utipi = (int)($u->utipi ?? 0);
                        $durumVal = (int)($u->durum ?? 1);
                        $tarihFmt = '—';
                        if (!empty($u->tarih)) {
                            try {
                                $tarihFmt = is_numeric($u->tarih)
                                    ? \Carbon\Carbon::createFromTimestamp((int)$u->tarih)->format('d.m.Y')
                                    : \Carbon\Carbon::parse($u->tarih)->format('d.m.Y');
                            } catch (\Throwable $e) {}
                        }
                    @endphp
                    <tr>
                        <td class="col-uye">
                            <div class="uye-cell">
                                <div class="uye-avatar">{{ $bas ?: 'U' }}</div>
                                <div>
                                    <div class="uye-name">{{ $ad }}</div>
                                    <div class="uye-id">#{{ $u->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="quick-cell">{{ $email ?: '—' }}</div>
                            @if($tel)
                                <div class="quick-cell-sub">{{ $tel }}</div>
                            @endif
                        </td>
                        <td>
                            @if($firma)
                                <div class="quick-cell">{{ $firma }}</div>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td>
                            @if($utipi === 1)
                                <span class="badge badge-brand">Kurumsal</span>
                            @else
                                <span class="badge badge-neutral">Bireysel</span>
                            @endif
                        </td>
                        <td>
                            @if($durumVal === 1)
                                <span class="badge badge-success">Aktif</span>
                            @elseif($durumVal === 2)
                                <span class="badge badge-warning">Engelli</span>
                            @else
                                <span class="badge badge-danger">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:12px;color:var(--text-muted)">{{ $tarihFmt }}</span>
                        </td>
                        <td class="col-islem">
                            <div class="table-actions" style="justify-content:flex-end">
                                <a href="{{ route('admin.uyeler.detay', $u->id) }}"
                                   class="table-action" title="Detay">
                                    <i data-lucide="eye"></i>
                                </a>
                                <form action="{{ route('admin.uyeler.durum', [$u->id, $durumVal === 1 ? 'pasif' : 'aktif']) }}"
                                      method="POST" style="display:inline">
                                    @csrf
                                    <button type="submit" class="table-action"
                                            title="{{ $durumVal === 1 ? 'Pasif yap' : 'Aktif yap' }}">
                                        <i data-lucide="{{ $durumVal === 1 ? 'pause-circle' : 'play-circle' }}"></i>
                                    </button>
                                </form>
                                <button type="button" class="table-action danger"
                                        onclick="document.getElementById('sil-{{ $u->id }}').submit()"
                                        title="Sil">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                            <form id="sil-{{ $u->id }}"
                                  action="{{ route('admin.uyeler.sil', $u->id) }}"
                                  method="POST" style="display:none"
                                  onsubmit="return confirm('Üye silinsin mi? Bu işlem geri alınamaz!')">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div style="font-size:13px;color:var(--text-muted)">
            <strong>{{ $uyeler->firstItem() }}</strong> - <strong>{{ $uyeler->lastItem() }}</strong> arası,
            toplam <strong>{{ $uyeler->total() }}</strong> üye
        </div>
        <div>
            {{ $uyeler->links() }}
        </div>
    </div>
@endif

@endsection