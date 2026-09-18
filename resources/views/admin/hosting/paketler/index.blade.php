@extends('admin._layout')

@section('title', 'Hosting Paketleri')

@push('head')
<style>
    .hp-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 18px;
        transition: all 0.2s ease;
        display: flex; flex-direction: column;
        height: 100%;
    }
    .hp-card:hover {
        border-color: var(--brand-medium);
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .hp-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px dashed var(--border);
    }

    .hp-card-name {
        font-weight: 700;
        font-size: 16px;
        line-height: 1.3;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hp-card-price {
        font-weight: 700;
        color: var(--brand-dark);
        font-size: 22px;
        margin: 10px 0;
        text-align: center;
        padding: 10px;
        background: var(--brand-soft);
        border-radius: var(--radius-md);
    }
    .hp-card-price .yil {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .hp-spec-list { margin: 8px 0 14px; flex: 1; }
    .hp-spec {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        font-size: 13px;
        border-bottom: 1px dashed var(--border);
    }
    .hp-spec:last-child { border-bottom: none; }
    .hp-spec .ic {
        width: 22px; height: 22px;
        display: inline-flex;
        align-items: center; justify-content: center;
        border-radius: 6px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        flex-shrink: 0;
    }
    .hp-spec .lbl { color: var(--text-muted); flex: 1; }
    .hp-spec .val { font-weight: 600; color: var(--text); }

    .hp-actions { display: flex; gap: 6px; margin-top: auto; }
    .hp-actions .btn { flex: 1; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Hosting Paketleri</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="server"></i>
            Hosting Paketleri
            <span class="badge badge-brand">{{ $paketler->total() }}</span>
        </h1>
        <div class="page-subtitle">Sunucu paketlerinizi tanımlayın ve fiyatlandırın</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.satislar.hosting'))
            <a href="{{ route('admin.satislar.hosting') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="bar-chart-3"></i>
                <span>Hosting Satışları</span>
            </a>
        @endif
        @if(empty($tableMissing))
            <a href="{{ route('admin.hosting.paketler.ekle') }}" class="btn btn-primary">
                <i data-lucide="plus"></i>
                <span>Yeni Paket</span>
            </a>
        @endif
    </div>
</div>

{{-- TABLO YOK UYARISI --}}
@if(!empty($tableMissing))
    <div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px">
        <i data-lucide="alert-triangle" style="width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
        <div>
            <strong>Hosting paket tablosu bulunamadı.</strong>
            <div style="margin-top:6px;font-size:13px">
                Lütfen <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">hosting_paketler</code>
                veya
                <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">hostingler</code>
                tablosunu oluşturun. Detay için sistem yöneticinizle iletişime geçin.
            </div>
        </div>
    </div>
@endif

{{-- STAT KARTLARI --}}
@php
    $toplamHp = $paketler->total();
    $aktifHp = 0; $pasifHp = 0; $ortFiyat = 0; $toplamFiyat = 0;

    if (!empty($tableName)) {
        try {
            $aktifHp = \DB::table($tableName)->where('durum', 1)->count();
            $pasifHp = \DB::table($tableName)->where('durum', 0)->count();

            $cols = \Schema::getColumnListing($tableName);
            $fiyatCol = in_array('fiyat', $cols) ? 'fiyat' : (in_array('tutar', $cols) ? 'tutar' : null);
            if ($fiyatCol) {
                $row = \DB::table($tableName)
                    ->selectRaw("COALESCE(SUM($fiyatCol), 0) as toplam, COALESCE(AVG($fiyatCol), 0) as ort")
                    ->first();
                $toplamFiyat = (float)($row->toplam ?? 0);
                $ortFiyat = (float)($row->ort ?? 0);
            }
        } catch (\Throwable $e) {}
    }
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="server"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Paket</div>
            <div class="stat-card-value">{{ $toplamHp }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifHp }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Ortalama Fiyat</div>
            <div class="stat-card-value" style="color:var(--brand-dark);font-size:22px">
                ₺{{ number_format($ortFiyat, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="package"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Değer</div>
            <div class="stat-card-value" style="font-size:22px">
                ₺{{ number_format($toplamFiyat, 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>

{{-- PAKETLER --}}
@if($paketler->isEmpty())
    @if(empty($tableMissing))
        <div class="section">
            <div class="empty-state">
                <i data-lucide="server" class="empty-state-icon"></i>
                <h4>Henüz hosting paketi yok</h4>
                <p>"Yeni Paket" butonu ile ilk hosting paketinizi tanımlayın.</p>
                <div style="margin-top:16px">
                    <a href="{{ route('admin.hosting.paketler.ekle') }}" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        <span>Yeni Paket Ekle</span>
                    </a>
                </div>
            </div>
        </div>
    @endif
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($paketler as $p)
            @php
                $aktif = (int)($p->durum ?? 0) === 1;
                $diskAlani = $p->disk_alani ?? $p->whm_alan ?? '—';
                $trafik = $p->trafik ?? $p->whm_atrafik ?? '—';
                $veritabani = $p->veritabani ?? $p->whm_max_veritabani ?? '—';
                $email = $p->email ?? $p->whm_max_email ?? '—';
                $fiyat = (float)($p->fiyat ?? $p->tutar ?? 0);
            @endphp

            <div class="hp-card">
                <div class="hp-card-header">
                    <div class="hp-card-name">
                        <i data-lucide="server" style="width:18px;height:18px;color:var(--brand-dark)"></i>
                        {{ $p->adi ?? '—' }}
                    </div>
                    @if($aktif)
                        <span class="badge badge-success">
                            <i data-lucide="check" style="width:11px;height:11px"></i>
                        </span>
                    @else
                        <span class="badge badge-neutral">
                            <i data-lucide="pause" style="width:11px;height:11px"></i>
                        </span>
                    @endif
                </div>

                <div class="hp-card-price">
                    ₺{{ number_format($fiyat, 0, ',', '.') }}
                    <div class="yil">/ yıllık</div>
                </div>

                <div class="hp-spec-list">
                    <div class="hp-spec">
                        <span class="ic"><i data-lucide="hard-drive" style="width:13px;height:13px"></i></span>
                        <span class="lbl">Disk</span>
                        <span class="val">{{ $diskAlani }}</span>
                    </div>
                    <div class="hp-spec">
                        <span class="ic"><i data-lucide="activity" style="width:13px;height:13px"></i></span>
                        <span class="lbl">Trafik</span>
                        <span class="val">{{ $trafik }}</span>
                    </div>
                    <div class="hp-spec">
                        <span class="ic"><i data-lucide="database" style="width:13px;height:13px"></i></span>
                        <span class="lbl">Veritabanı</span>
                        <span class="val">{{ $veritabani }}</span>
                    </div>
                    <div class="hp-spec">
                        <span class="ic"><i data-lucide="mail" style="width:13px;height:13px"></i></span>
                        <span class="lbl">E-posta</span>
                        <span class="val">{{ $email }}</span>
                    </div>
                </div>

                <div class="hp-actions">
                    <a href="{{ route('admin.hosting.paketler.duzenle', $p->id) }}" class="btn btn-primary btn-sm">
                        <i data-lucide="edit-3"></i>
                        <span>Düzenle</span>
                    </a>
                    <button type="button" class="btn btn-ghost btn-sm"
                            style="color:var(--danger);flex:0 0 auto;padding:7px 10px"
                            onclick="silHostingPaket({{ $p->id }})">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($paketler->hasPages())
        <div style="margin-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md)">
            <div style="font-size:13px;color:var(--text-muted)">
                {{ $paketler->firstItem() ?? 0 }} – {{ $paketler->lastItem() ?? 0 }} / Toplam
                <strong style="color:var(--text)">{{ $paketler->total() }}</strong>
            </div>
            {{ $paketler->withQueryString()->links() }}
        </div>
    @endif

    {{-- SİL FORMLARI --}}
    @foreach($paketler as $p)
        <form id="del-hp-{{ $p->id }}"
              action="{{ route('admin.hosting.paketler.sil', $p->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silHostingPaket(id) {
    if (confirm('Bu hosting paketini silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
        document.getElementById('del-hp-' + id).submit();
    }
}
</script>

@endsection