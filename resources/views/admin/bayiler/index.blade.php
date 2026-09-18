@extends('admin._layout')

@section('title', 'Bayiler')

@push('head')
<style>
    .bayi-avatar {
        width: 38px; height: 38px; border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #000; font-weight: 700; font-size: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bayi-cell { display: flex; align-items: center; gap: 12px; }
    .bayi-cell .name { font-weight: 600; font-size: 14px; line-height: 1.3; }
    .bayi-cell .sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .code-chip {
        display: inline-block;
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 12px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        padding: 4px 9px;
        border-radius: 6px;
        font-weight: 600;
    }

    .money-pos { color: var(--success); font-weight: 700; }
    .money-brand { color: var(--brand-dark); font-weight: 700; }

    .filter-form {
        padding: 14px;
        margin-bottom: 16px;
    }
    .filter-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 10px;
        align-items: end;
    }
    @media(max-width:768px) {
        .filter-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Bayiler</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="store"></i>
            Bayiler
            <span class="badge badge-brand">{{ $bayiler->total() }}</span>
        </h1>
        <div class="page-subtitle">Tüm yetkili bayileri yönet</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.bayiler.ayarlar') }}" class="btn btn-secondary">
            <i data-lucide="settings"></i>
            <span>Bayi Ayarları</span>
        </a>
        <a href="{{ route('admin.bayiler.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Bayi</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI (inline - admin-theme.css uyumlu) --}}
@php
    // statCards array'inden değerleri çıkar (geriye uyumluluk)
    $st = [];
    if (!empty($statCards) && is_iterable($statCards)) {
        foreach($statCards as $c) {
            $key = mb_strtolower($c['label'] ?? '');
            if (str_contains($key, 'toplam')) $st['toplam'] = $c['value'] ?? 0;
            elseif (str_contains($key, 'aktif')) $st['aktif'] = $c['value'] ?? 0;
            elseif (str_contains($key, 'pasif')) $st['pasif'] = $c['value'] ?? 0;
            elseif (str_contains($key, 'ay'))   $st['buay'] = $c['value'] ?? 0;
        }
    }
    // Fallback: doğrudan DB'den say (statCards gelmezse)
    $totalBayi  = $st['toplam'] ?? \DB::table('bayiler')->count();
    $aktifBayi  = $st['aktif']  ?? \DB::table('bayiler')->where('durum', 1)->count();
    $pasifBayi  = $st['pasif']  ?? \DB::table('bayiler')->where('durum', 0)->count();
    $buAyBayi   = $st['buay']   ?? 0;
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="store"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Bayi</div>
            <div class="stat-card-value">{{ $totalBayi }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifBayi }}</div>
        </div>
    </div>

    <div class="stat-card" style="@if($pasifBayi > 0)border-color:rgba(239,68,68,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="pause-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value" style="@if($pasifBayi > 0)color:var(--danger)@endif">{{ $pasifBayi }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay Yeni</div>
            <div class="stat-card-value">{{ $buAyBayi }}</div>
        </div>
    </div>
</div>

{{-- FİLTRE BARI --}}
<form method="GET" class="section filter-form">
    <div class="filter-grid">
        <div>
            <label class="form-label">Ara</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   class="form-input" placeholder="Ad, soyad, e-posta veya bayi kodu...">
        </div>
        <div>
            <label class="form-label">Onay Durumu</label>
            <select name="onay" class="form-select">
                <option value="">Tümü</option>
                <option value="1" {{ request('onay') === '1' ? 'selected' : '' }}>Onaylı</option>
                <option value="0" {{ request('onay') === '0' ? 'selected' : '' }}>Beklemede</option>
                <option value="2" {{ request('onay') === '2' ? 'selected' : '' }}>Reddedildi</option>
            </select>
        </div>
        <div>
            <label class="form-label">Aktiflik</label>
            <select name="durum" class="form-select">
                <option value="">Tümü</option>
                <option value="1" {{ request('durum') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('durum') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="search"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['q','onay','durum']))
                <a href="{{ route('admin.bayiler.index') }}" class="btn btn-ghost btn-sm" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- TABLO veya EMPTY STATE --}}
@if($bayiler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="store" class="empty-state-icon"></i>
            <h4>Henüz bayi yok</h4>
            <p>"Yeni Bayi" butonu ile ilk bayinizi ekleyebilirsiniz.</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.bayiler.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Bayi Ekle</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bayi</th>
                        <th>İletişim</th>
                        <th>Bayi Kodu</th>
                        <th>İl / İlçe</th>
                        <th>Komisyon</th>
                        <th>Kazanç</th>
                        <th>Bakiye</th>
                        <th>Onay</th>
                        <th style="text-align:right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bayiler as $b)
                        @php
                            $hasUye = !empty($b->ad) || !empty($b->email);
                            $ad = trim(($b->ad ?? '') . ' ' . ($b->soyad ?? '')) ?: ('Bayi #' . $b->id);
                            $harf = $hasUye
                                ? mb_strtoupper(mb_substr($b->ad ?? 'B', 0, 1, 'UTF-8'), 'UTF-8')
                                : '?';
                            $onay = (int)($b->onay_durumu ?? 0);
                            $aktif = (int)($b->durum ?? 1);
                        @endphp
                        <tr @if(!$hasUye) style="background:rgba(239,68,68,0.04)" @endif>
                            <td>
                                <div class="bayi-cell">
                                    <div class="bayi-avatar" @if(!$hasUye) style="background:linear-gradient(135deg,#94a3b8,#64748b)" @endif>{{ $harf }}</div>
                                    <div>
                                        <div class="name">{{ $ad }}</div>
                                        <div class="sub">
                                            ID: #{{ $b->id }}
                                            @if(!$hasUye)
                                                · <span style="color:var(--danger);font-weight:600">⚠ Üye bulunamadı</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($hasUye)
                                    <div style="font-size:13px">
                                        <i data-lucide="mail" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                                        {{ $b->email ?? '—' }}
                                    </div>
                                    @if(!empty($b->telefon))
                                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
                                            <i data-lucide="phone" style="width:12px;height:12px;display:inline;vertical-align:middle"></i>
                                            {{ $b->telefon }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">
                                        <i data-lucide="user-x" style="width:12px;height:12px;display:inline"></i>
                                        uye_id: {{ $b->uye_id ?? '—' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="code-chip">{{ $b->bayi_kodu ?? '—' }}</span>
                            </td>
                            <td style="font-size:12.5px">
                                @if(!empty($b->il) || !empty($b->ilce))
                                    <div>{{ $b->il ?? '—' }}</div>
                                    @if(!empty($b->ilce))
                                        <div style="color:var(--text-muted);margin-top:2px">{{ $b->ilce }}</div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="money-brand">%{{ number_format($b->komisyon_orani ?? 0, 2) }}</span>
                            </td>
                            <td>
                                <span class="money-pos">₺{{ number_format($b->toplam_kazanc ?? 0, 2, ',', '.') }}</span>
                            </td>
                            <td>
                                <div style="font-weight:700;font-size:13px">
                                    ₺{{ number_format($b->cekilebilir_bakiye ?? 0, 2, ',', '.') }}
                                </div>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                    Çekilen: ₺{{ number_format($b->cekilen_toplam ?? 0, 2, ',', '.') }}
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start">
                                    @if($onay === 1)
                                        <span class="badge badge-success">
                                            <i data-lucide="check" style="width:11px;height:11px"></i>
                                            Onaylı
                                        </span>
                                    @elseif($onay === 2)
                                        <span class="badge badge-danger">
                                            <i data-lucide="x" style="width:11px;height:11px"></i>
                                            Red
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i data-lucide="clock" style="width:11px;height:11px"></i>
                                            Beklemede
                                        </span>
                                    @endif

                                    @if($aktif === 0)
                                        <span class="badge badge-neutral">
                                            <i data-lucide="pause" style="width:11px;height:11px"></i>
                                            Pasif
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.bayiler.detay', $b->id) }}"
                                       class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <a href="{{ route('admin.bayiler.duzenle', $b->id) }}"
                                       class="table-action" title="Düzenle">
                                        <i data-lucide="edit-3"></i>
                                    </a>
                                    <button type="button" class="table-action"
                                            title="Sil" style="color:var(--danger)"
                                            onclick="silBayi({{ $b->id }});">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($bayiler->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:13px;color:var(--text-muted)">
                    {{ $bayiler->firstItem() ?? 0 }} – {{ $bayiler->lastItem() ?? 0 }} / Toplam
                    <strong style="color:var(--text)">{{ $bayiler->total() }}</strong>
                </div>
                {{ $bayiler->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- SİL FORMLARI (ANA SAYFADA YOK, AMA HTML İÇ İÇE FORM KURALI GEREĞİ AYRI) --}}
    @foreach($bayiler as $b)
        <form id="del-bayi-{{ $b->id }}"
              action="{{ route('admin.bayiler.sil', $b->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silBayi(id) {
    if (confirm('Bu bayi silinsin mi? Bu işlem geri alınamaz.')) {
        document.getElementById('del-bayi-' + id).submit();
    }
}
</script>

@endsection