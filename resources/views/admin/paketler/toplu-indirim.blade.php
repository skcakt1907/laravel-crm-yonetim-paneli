@extends('admin._layout')

@section('title', 'Kategoriye Toplu İndirim')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.paketler.index') }}">Paketler</a>
    <span class="sep">/</span>
    <span class="current">Toplu İndirim</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Kategoriye Toplu İndirim</h1>
        <div class="page-subtitle">Bir kategorideki tüm paketlere tek seferde indirim uygula · orijinal fiyat saklanır, geri alınabilir</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.paketler.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> <span>Paketlere Dön</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if($kolonYok)
    <div class="alert alert-warning">
        <i data-lucide="database"></i>
        <div>
            <strong>Kurulum eksik.</strong> <code>yazilimlar.eski_tutar</code> kolonu yok.
            Migration çalıştırılmalı:
            <code>php artisan migrate --force --path=database/migrations/2026_07_30_160000_add_eski_tutar_to_yazilimlar.php</code>
        </div>
    </div>
@endif

{{-- ═══ 1) Kategori + indirim seçimi ═══ --}}
<form method="GET" class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">
    <div class="form-group" style="flex:2;min-width:260px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Kategori</label>
        <select name="kategori_id" class="form-select">
            <option value="">— Kategori seç —</option>
            @foreach($kategoriler as $k)
                <option value="{{ $k->id }}" {{ (int) $kategoriId === (int) $k->id ? 'selected' : '' }}>
                    {{ $k->adi }} ({{ $k->paket_sayisi }} paket{{ $k->indirimli > 0 ? ' · ' . $k->indirimli . ' indirimli' : '' }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group" style="flex:1;min-width:150px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">İndirim Tipi</label>
        <select name="tip" class="form-select">
            <option value="yuzde" {{ $tip === 'yuzde' ? 'selected' : '' }}>Yüzde (%)</option>
            <option value="tutar" {{ $tip === 'tutar' ? 'selected' : '' }}>Sabit tutar (₺)</option>
        </select>
    </div>

    <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Değer</label>
        <input type="number" step="0.01" min="0" name="deger" class="form-input"
               value="{{ $deger > 0 ? $deger : '' }}" placeholder="Örn: 20">
    </div>

    <button type="submit" class="btn btn-primary"><i data-lucide="calculator"></i> <span>Hesapla</span></button>
</form>

@if($kategoriId && $satirlar->isEmpty() && $atlanan->isEmpty())
    <div class="alert alert-warning">
        <i data-lucide="info"></i>
        <div>Bu kategoride paket bulunamadı ya da indirim değeri girilmedi.</div>
    </div>
@endif

{{-- ═══ 2) Önizleme ═══ --}}
@if($satirlar->isNotEmpty())
    @php
        $toplamEski = $satirlar->sum('hesap_eski');
        $toplamYeni = $satirlar->sum('hesap_yeni');
        $toplamFark = $toplamEski - $toplamYeni;
    @endphp

    <div class="mini-stat-grid" style="margin-bottom:16px">
        <div class="mini-stat info">
            <div class="lbl">Etkilenecek Paket</div>
            <div class="val">{{ $satirlar->count() }}</div>
            <div class="sub">{{ $atlanan->count() }} paket atlanacak</div>
        </div>
        <div class="mini-stat">
            <div class="lbl">Şu Anki Toplam</div>
            <div class="val">{{ number_format($toplamEski, 2, ',', '.') }} ₺</div>
            <div class="sub">İndirimsiz liste fiyatı</div>
        </div>
        <div class="mini-stat success">
            <div class="lbl">İndirimli Toplam</div>
            <div class="val" style="color:var(--success)">{{ number_format($toplamYeni, 2, ',', '.') }} ₺</div>
            <div class="sub">Uygulandıktan sonra</div>
        </div>
        <div class="mini-stat warning">
            <div class="lbl">Toplam İndirim</div>
            <div class="val" style="color:var(--warning)">{{ number_format($toplamFark, 2, ',', '.') }} ₺</div>
            <div class="sub">{{ $tip === 'yuzde' ? '%' . rtrim(rtrim(number_format($deger, 2, ',', '.'), '0'), ',') : number_format($deger, 2, ',', '.') . ' ₺' }} indirim</div>
        </div>
    </div>

    <div class="table-wrap" style="margin-bottom:16px">
        <div class="card-header" style="padding:16px 18px;margin-bottom:0">
            <div class="card-title">Önizleme — henüz kaydedilmedi</div>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th style="width:110px">Durum</th>
                        <th style="width:140px" class="text-right">Şu Anki</th>
                        <th style="width:140px" class="text-right">Yeni Fiyat</th>
                        <th style="width:130px" class="text-right">Fark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satirlar as $p)
                    <tr>
                        <td>
                            <strong>{{ $p->adi }}</strong>
                            @if($p->eski_tutar !== null && $p->eski_tutar !== '')
                                <span class="badge badge-warning" style="font-size:9.5px;margin-left:6px">zaten indirimli</span>
                            @endif
                        </td>
                        <td>
                            @if((int) $p->durum === 1)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-neutral">Pasif</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($p->hesap_eski, 2, ',', '.') }} ₺</td>
                        <td class="text-right"><strong style="color:var(--success)">{{ number_format($p->hesap_yeni, 2, ',', '.') }} ₺</strong></td>
                        <td class="text-right" style="color:var(--warning)">−{{ number_format($p->hesap_fark, 2, ',', '.') }} ₺</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($atlanan->isNotEmpty())
        <div class="alert alert-warning">
            <i data-lucide="alert-triangle"></i>
            <div>
                <strong>{{ $atlanan->count() }} paket atlanacak</strong> — fiyatı sayı değil:
                {{ $atlanan->pluck('adi')->map(fn($a) => '"' . \Illuminate\Support\Str::limit($a, 40) . '"')->implode(', ') }}
            </div>
        </div>
    @endif

    {{-- ═══ 3) Uygula ═══ --}}
    <div class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between">
        <div class="form-help" style="margin-top:0">
            Orijinal fiyatlar saklanır; istediğin zaman "İndirimi Kaldır" ile geri alabilirsin.
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <form method="POST" action="{{ route('admin.paketler.toplu-indirim.kaldir') }}"
                  onsubmit="return confirm('Bu kategorideki indirimler geri alınacak, fiyatlar eski hâline dönecek. Onaylıyor musun?')">
                @csrf
                <input type="hidden" name="kategori_id" value="{{ $kategoriId }}">
                <button type="submit" class="btn btn-secondary"><i data-lucide="rotate-ccw"></i> <span>İndirimi Kaldır</span></button>
            </form>

            <form method="POST" action="{{ route('admin.paketler.toplu-indirim.uygula') }}"
                  onsubmit="return confirm('{{ $satirlar->count() }} paketin fiyatı değiştirilecek. Onaylıyor musun?')">
                @csrf
                <input type="hidden" name="kategori_id" value="{{ $kategoriId }}">
                <input type="hidden" name="tip" value="{{ $tip }}">
                <input type="hidden" name="deger" value="{{ $deger }}">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="check"></i> <span>İndirimi Uygula ({{ $satirlar->count() }} paket)</span>
                </button>
            </form>
        </div>
    </div>
@endif

@endsection
