@extends('admin._layout')

@section('title', 'Aylık Bildirimli Alacaklar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Aylık Bildirimli Alacaklar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="hand-coins"></i> Aylık Bildirimli Alacaklar</h1>
        <div class="page-subtitle">Tahsil edeceğimiz periyodik tutarlar · tarih yaklaşınca otomatik hatırlatma</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.aylik-alacaklar.olustur') }}" class="btn btn-primary">
            <i data-lucide="plus"></i> <span>Yeni Alacak</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if($tabloYok)
    <div class="alert alert-warning">
        <i data-lucide="database"></i>
        <div><strong>Tablo bulunamadı.</strong> <code>aylik_alacaklar</code> tablosu için migration çalıştırılmalı.</div>
    </div>
@else

{{-- Özet --}}
<div class="mini-stat-grid">
    <div class="mini-stat">
        <div class="lbl">Toplam Alacak</div>
        <div class="val">{{ number_format($ozet['toplam'], 2, ',', '.') }} ₺</div>
        <div class="sub">Tüm kayıtlar</div>
    </div>
    <div class="mini-stat warning">
        <div class="lbl">Bekleyen</div>
        <div class="val" style="color:var(--warning)">{{ number_format($ozet['bekleyen'], 2, ',', '.') }} ₺</div>
        <div class="sub">Tahsil edilmeyi bekliyor</div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Tahsil Edilen</div>
        <div class="val" style="color:var(--success)">{{ number_format($ozet['tahsil'], 2, ',', '.') }} ₺</div>
        <div class="sub">Tamamlanan</div>
    </div>
    <div class="mini-stat info">
        <div class="lbl">7 Gün İçinde</div>
        <div class="val" style="color:var(--info)">{{ $ozet['yaklasan'] }}</div>
        <div class="sub">kayıt hatırlatmaya girecek</div>
    </div>
</div>

{{-- Filtre --}}
<form method="GET" class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:16px;margin-bottom:16px">
    <div class="form-group" style="flex:2;min-width:230px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Ara</label>
        <input type="text" name="q" value="{{ $filtre['arama'] }}" class="form-input" placeholder="Başlık, müşteri veya açıklama">
    </div>
    <div class="form-group" style="flex:1;min-width:170px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Durum</label>
        <select name="durum" class="form-select">
            <option value="">Tümü</option>
            <option value="bekliyor" @selected($filtre['durum']==='bekliyor')>Bekliyor</option>
            <option value="tahsil_edildi" @selected($filtre['durum']==='tahsil_edildi')>Tahsil edildi</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i data-lucide="filter"></i> <span>Filtrele</span></button>
    @if($filtre['arama'] || $filtre['durum'])
        <a href="{{ route('admin.aylik-alacaklar.index') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> <span>Temizle</span>
        </a>
    @endif
</form>

{{-- Liste --}}
<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Alacak</th>
                    <th style="width:150px">Müşteri</th>
                    <th style="width:120px">Kategori</th>
                    <th style="width:90px">Periyot</th>
                    <th style="width:115px">Tahsil Tarihi</th>
                    <th style="width:135px">Durum</th>
                    <th style="width:130px" class="text-right">Tutar</th>
                    <th style="width:130px" class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alacaklar as $a)
                <tr @if(!$a->aktif) style="opacity:.55" @endif>
                    <td>
                        <strong>{{ $a->baslik }}</strong>
                        @if($a->aciklama)<div style="font-size:11.5px;color:var(--text-muted)">{{ \Illuminate\Support\Str::limit($a->aciklama, 50) }}</div>@endif
                        @if(!$a->aktif)<span class="badge badge-neutral" style="font-size:9.5px">pasif</span>@endif
                    </td>
                    <td style="font-size:12.5px">{{ $a->musteri ?: '—' }}</td>
                    <td style="font-size:12px">{{ $a->kategori_adi ?: ($a->kategori_diger ?: '—') }}</td>
                    <td style="font-size:12px">{{ $a->periyot_ay }} ayda 1</td>
                    <td style="font-size:12px">
                        {{ $a->son_tahsil_tarihi ? \Carbon\Carbon::parse($a->son_tahsil_tarihi)->format('d.m.Y') : '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $a->aciliyet['sinif'] ?? 'badge-neutral' }}">
                            {{ $a->aciliyet['metin'] }}
                        </span>
                    </td>
                    <td class="text-right"><strong>{{ number_format((float) $a->tutar, 2, ',', '.') }} {{ $a->para_birimi ?: '₺' }}</strong></td>
                    <td class="text-right">
                        <div class="table-actions" style="justify-content:flex-end">
                            <form method="POST" action="{{ route('admin.aylik-alacaklar.durum', $a->id) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="durum" value="{{ $a->durum === 'bekliyor' ? 'tahsil_edildi' : 'bekliyor' }}">
                                <button class="table-action" style="color:{{ $a->durum === 'bekliyor' ? 'var(--success)' : 'var(--text-muted)' }}"
                                        title="{{ $a->durum === 'bekliyor' ? 'Tahsil edildi işaretle' : 'Bekliyor işaretle' }}">
                                    <i data-lucide="{{ $a->durum === 'bekliyor' ? 'check' : 'rotate-ccw' }}"></i>
                                </button>
                            </form>
                            <a href="{{ route('admin.aylik-alacaklar.duzenle', $a->id) }}" class="table-action" style="color:var(--brand-dark)" title="Düzenle"><i data-lucide="pencil"></i></a>
                            <form method="POST" action="{{ route('admin.aylik-alacaklar.sil', $a->id) }}" style="display:inline"
                                  onsubmit="return confirm('Bu alacak kaydı silinsin mi?')">
                                @csrf @method('DELETE')
                                <button class="table-action" style="color:var(--danger)" title="Sil"><i data-lucide="trash-2"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8">
                    <div class="empty-state" style="padding:36px 0">
                        <i data-lucide="hand-coins" class="empty-state-icon"></i>
                        <h4>Henüz alacak kaydı yok</h4>
                        <p>"Yeni Alacak" ile periyodik tahsilatlarınızı ekleyin; tarih yaklaşınca hatırlatma gelir.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="form-help" style="margin-top:14px">
    Tahsil tarihine <strong>1 hafta / 3 gün / 1 gün</strong> kalanlar ve gecikenler için her gün 09:15'te
    yönetime hatırlatma e-postası gider. Tahsil edildi işaretlenip tarihi geçen kayıt, periyoduna göre
    otomatik olarak bir sonraki döneme taşınır.
</div>
@endif

@endsection
