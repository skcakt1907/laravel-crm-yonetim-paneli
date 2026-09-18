@extends('admin._layout')

@section('title', 'Kredi Talebi — ' . ($kredi->kredi_no ?: '#'.$kredi->id))

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.dnbank-krediler.index') }}">DN Bank Kredi Talepleri</a>
    <span class="sep">/</span>
    <span class="current">{{ $kredi->kredi_no ?: '#'.$kredi->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="landmark"></i> {{ $kredi->kredi_no ?: 'Kredi #'.$kredi->id }}</h1>
        <div class="page-subtitle">{{ $kredi->musteri_adi ?: '—' }} · {{ \Illuminate\Support\Carbon::parse($kredi->created_at)->format('d.m.Y H:i') }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('admin.dnbank-krediler.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> <span>Listeye Dön</span></a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('info'))
    <div class="alert alert-info"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>
@endif

@php
    $renk = match($kredi->onay_durumu) {
        'onaylandi' => 'badge-success',
        'reddedildi' => 'badge-danger',
        default => 'badge-warning',
    };
    $metin = match($kredi->onay_durumu) {
        'onaylandi' => 'Onaylandı — coin yüklendi',
        'reddedildi' => 'Reddedildi',
        default => 'Onay bekliyor',
    };
@endphp

<div class="section" style="margin-bottom:18px">
    <span class="badge {{ $renk }}" style="font-size:13px;padding:6px 12px">{{ $metin }}</span>
</div>

@if($kredi->onay_durumu === 'bekliyor')
<div class="section" style="margin-bottom:18px;display:flex;gap:10px">
    <form method="POST" action="{{ route('admin.dnbank-krediler.onayla', $kredi->id) }}"
          onsubmit="return confirm('₺{{ number_format((float) $kredi->ana_para, 2, ',', '.') }} tutarındaki kredi onaylansın mı? Coin hemen {{ $kredi->musteri_adi }} hesabına yüklenecek.')">
        @csrf
        <button type="submit" class="btn btn-success"><i data-lucide="check"></i> <span>Onayla ve Coin Yükle</span></button>
    </form>
    <form method="POST" action="{{ route('admin.dnbank-krediler.reddet', $kredi->id) }}"
          onsubmit="return confirm('Bu kredi talebi reddedilsin mi?')">
        @csrf
        <button type="submit" class="btn" style="color:var(--danger);border:1px solid var(--danger)"><i data-lucide="x"></i> <span>Reddet</span></button>
    </form>
</div>
@endif

<div class="mini-stat-grid" style="margin-bottom:18px">
    <div class="mini-stat">
        <div class="lbl">Ana Para</div>
        <div class="val">₺{{ number_format((float) $kredi->ana_para, 2, ',', '.') }}</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Vade</div>
        <div class="val">{{ $kredi->vade_ay }} ay</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Aylık Taksit</div>
        <div class="val">₺{{ number_format((float) $kredi->aylik_taksit, 2, ',', '.') }}</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Kalan Borç</div>
        <div class="val" style="color:{{ (float) $kredi->kalan_borc > 0 ? 'var(--danger)' : 'var(--success)' }}">
            ₺{{ number_format((float) $kredi->kalan_borc, 2, ',', '.') }}
        </div>
    </div>
</div>

<div class="section" style="margin-bottom:18px">
    <div class="card-title" style="margin-bottom:14px">👤 Müşteri Bilgileri</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px">
        <div><span style="color:var(--text-muted)">Ad Soyad:</span> {{ $kredi->musteri_adi ?: '—' }}</div>
        <div><span style="color:var(--text-muted)">E-posta:</span> {{ $kredi->musteri_email ?: '—' }}</div>
        <div><span style="color:var(--text-muted)">Telefon:</span> {{ $kredi->musteri_telefon ?: '—' }}</div>
        <div><span style="color:var(--text-muted)">CRM Kaydı:</span> {{ $kredi->crm_musteri_adi ?: '—' }}</div>
    </div>
    @if($kredi->aciklama)
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
        <span style="color:var(--text-muted);font-size:13px">Açıklama:</span>
        <div style="font-size:13px;margin-top:4px">{{ $kredi->aciklama }}</div>
    </div>
    @endif
</div>

@if($taksitler->isNotEmpty())
<div class="table-wrap">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0"><div class="card-title">📅 Taksit Planı</div></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>#</th><th>Vade Tarihi</th><th class="text-right">Tutar</th><th>Durum</th></tr></thead>
            <tbody>
                @foreach($taksitler as $t)
                <tr>
                    <td>{{ $t->sira }}</td>
                    <td>{{ isset($t->vade_tarihi) ? \Illuminate\Support\Carbon::parse($t->vade_tarihi)->format('d.m.Y') : '—' }}</td>
                    <td class="text-right">₺{{ number_format((float) ($t->tutar ?? 0), 2, ',', '.') }}</td>
                    <td>{{ $t->durum ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
