@extends('admin._layout')

@section('title', 'Günlük Raporlar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Günlük Raporlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="bar-chart-3"></i> Günlük Raporlar</h1>
        <div class="page-subtitle">Tüm hareketler · {{ $veri['tarih']->format('d.m.Y') }}</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Tarih seçimi + elle gönder --}}
<div class="section" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <form method="GET" style="display:flex;gap:8px;align-items:flex-end">
        <div class="form-group" style="margin-bottom:0;min-width:170px">
            <label class="form-label" style="font-size:11px">Tarih</label>
            <input type="date" name="tarih" value="{{ $veri['tarih']->toDateString() }}" class="form-input">
        </div>
        <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> <span>Göster</span></button>
    </form>

    <div style="flex:1"></div>

    <form method="POST" action="{{ route('admin.raporlar.gunluk.gonder') }}"
          onsubmit="return confirm('{{ $veri['tarih']->format('d.m.Y') }} raporu muhasebeye mail olarak gönderilsin mi?')">
        @csrf
        <input type="hidden" name="tarih" value="{{ $veri['tarih']->toDateString() }}">
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="mail"></i>
            <span>Raporu Şimdi Gönder ({{ count($alicilar) }} kişi)</span>
        </button>
    </form>
</div>

<div class="form-help" style="margin-bottom:16px">
    Bu rapor <strong>her gün 23:30</strong>'da otomatik olarak muhasebeye e-posta ile gönderilir.
    Yukarıdaki butonla dilediğiniz an elle de gönderebilirsiniz.
</div>

{{-- Özet kutuları --}}
<div class="mini-stat-grid" style="margin-bottom:18px">
    <div class="mini-stat success">
        <div class="lbl">Gelir</div>
        <div class="val" style="color:var(--success)">{{ number_format($veri['gelir_toplam'], 2, ',', '.') }} ₺</div>
        <div class="sub">{{ $veri['gelir']->count() }} tahsilat</div>
    </div>
    <div class="mini-stat danger">
        <div class="lbl">Gider</div>
        <div class="val" style="color:var(--danger)">{{ number_format($veri['gider_toplam'], 2, ',', '.') }} ₺</div>
        <div class="sub">{{ $veri['gider']->count() }} kalem</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Net</div>
        <div class="val" style="color:{{ $veri['net'] >= 0 ? 'var(--success)' : 'var(--danger)' }}">
            {{ number_format($veri['net'], 2, ',', '.') }} ₺
        </div>
        <div class="sub">Gelir − Gider</div>
    </div>
</div>

{{-- Tahsilatlar --}}
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0">
        <div class="card-title">💰 Tahsilatlar</div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th>Fatura No</th><th>Müşteri</th><th>Başlık</th><th>Ödeme Yöntemi</th><th class="text-right">Tutar</th></tr>
            </thead>
            <tbody>
                @forelse($veri['gelir'] as $g)
                <tr>
                    <td style="font-family:monospace;font-size:12px">{{ $g->fatura_no ?: '#'.$g->id }}</td>
                    <td>{{ $g->musteri ?: '—' }}</td>
                    <td style="color:var(--text-secondary)">{{ \Illuminate\Support\Str::limit($g->baslik ?? '—', 40) }}</td>
                    <td style="font-size:12px">{{ $g->odeme_yontemi ?: '—' }}</td>
                    <td class="text-right" style="color:var(--success);font-weight:700">
                        +{{ number_format((float) $g->tutar, 2, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state" style="padding:28px 0"><p>Bu gün tahsilat yok.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Giderler --}}
<div class="table-wrap">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0">
        <div class="card-title">💸 Giderler</div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th>Başlık</th><th>Kategori</th><th>Ödeme Yöntemi</th><th class="text-right">Tutar</th></tr>
            </thead>
            <tbody>
                @forelse($veri['gider'] as $g)
                <tr>
                    <td><strong>{{ $g->baslik ?: '—' }}</strong></td>
                    <td style="font-size:12px">{{ $g->kategori ?: '—' }}</td>
                    <td style="font-size:12px">{{ $g->odeme_yontemi ?: '—' }}</td>
                    <td class="text-right" style="color:var(--danger);font-weight:700">
                        -{{ number_format((float) $g->tutar, 2, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state" style="padding:28px 0"><p>Bu gün gider yok.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
