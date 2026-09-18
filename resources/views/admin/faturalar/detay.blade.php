@extends('admin._layout')

@section('title', 'Fatura #' . ($fatura->fatura_no ?? $fatura->id))

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.faturalar.index') }}">Faturalar</a>
    <span class="sep">/</span>
    <span class="current">#{{ $fatura->fatura_no ?? $fatura->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            🧾 Fatura #{{ $fatura->fatura_no ?? $fatura->id }}
            @if(($fatura->durum ?? 0) == 1)
                <span class="badge badge-success" style="vertical-align:middle;margin-left:8px">✓ Ödendi</span>
            @elseif(($fatura->durum ?? 0) == 2)
                <span class="badge badge-danger" style="vertical-align:middle;margin-left:8px">✗ İptal</span>
            @else
                <span class="badge badge-warning" style="vertical-align:middle;margin-left:8px">⏳ Bekliyor</span>
            @endif
        </h1>
        <div class="page-subtitle">Müşteri: <strong>{{ $fatura->ad ?? '' }} {{ $fatura->soyad ?? '' }}</strong></div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.faturalar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.duzenle'))
        <a href="{{ route('admin.faturalar.duzenle', $fatura->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="edit-2"></i>
            <span>Düzenle</span>
        </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.goster'))
        <a href="{{ route('admin.faturalar.goster', $fatura->id) }}" target="_blank" class="btn btn-secondary btn-sm">
            <i data-lucide="printer"></i>
            <span>Yazdır / Görüntüle</span>
        </a>
        @endif
        @if(($fatura->durum ?? 0) == 0 && \Illuminate\Support\Facades\Route::has('admin.faturalar.hatirlat'))
        <form action="{{ route('admin.faturalar.hatirlat', $fatura->id) }}" method="POST" style="display:inline"
              onsubmit="return confirm('Müşteriye ödeme bildirimi (e-posta + SMS) gönderilsin mi?');">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe">
                <i data-lucide="bell-ring"></i>
                <span>Ödeme Bildirimi Gönder</span>
            </button>
        </form>
        @endif
        @if(($fatura->durum ?? 0) == 0 && \Illuminate\Support\Facades\Route::has('admin.faturalar.durum'))
        <form action="{{ route('admin.faturalar.durum', [$fatura->id, 1]) }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i data-lucide="check-circle"></i>
                <span>Ödendi İşaretle</span>
            </button>
        </form>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.sil'))
        <form action="{{ route('admin.faturalar.sil', $fatura->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Fatura silinsin mi?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>
        </form>
        @endif
    </div>
</div>

{{-- Üst özet stat'ları --}}
<div class="mini-stat-grid" style="grid-template-columns:repeat(3,1fr)">
    @php
        $fDovizli = !empty($fatura->para_birimi ?? null) && ($fatura->para_birimi ?? 'TL') !== 'TL' && !empty($fatura->doviz_tutar ?? null);
    @endphp
    <div class="mini-stat">
        <div class="lbl">Toplam Tutar</div>
        @if($fDovizli)
            <div class="val">{{ number_format($fatura->doviz_tutar, 2, ',', '.') }} {{ $fatura->para_birimi }}</div>
            <div class="sub">TCMB kuru {{ number_format($fatura->kur ?? 0, 4, ',', '.') }} ≈ <strong>₺{{ number_format($fatura->tutar ?? 0, 2, ',', '.') }}</strong></div>
        @else
            <div class="val">₺{{ number_format($fatura->tutar ?? 0, 2, ',', '.') }}</div>
            <div class="sub">Fatura toplamı</div>
        @endif
    </div>
    <div class="mini-stat info">
        <div class="lbl">Kesim Tarihi</div>
        <div class="val" style="font-size:18px">
            {{ !empty($fatura->tarih) ? \Carbon\Carbon::parse($fatura->tarih)->format('d.m.Y') : '—' }}
        </div>
        <div class="sub">Düzenleme tarihi</div>
    </div>
    <div class="mini-stat {{ (!empty($fatura->bitis_tarih) && strtotime($fatura->bitis_tarih) < time() && ($fatura->durum ?? 0) == 0) ? 'danger' : 'warning' }}">
        <div class="lbl">Son Ödeme Tarihi</div>
        <div class="val" style="font-size:18px">
            {{ !empty($fatura->bitis_tarih) ? \Carbon\Carbon::parse($fatura->bitis_tarih)->format('d.m.Y') : '—' }}
        </div>
        <div class="sub">
            @if(!empty($fatura->bitis_tarih) && ($fatura->durum ?? 0) == 0)
                @php $kalan = (int) ((strtotime($fatura->bitis_tarih) - time()) / 86400); @endphp
                @if($kalan < 0)<span style="color:var(--danger)">{{ abs($kalan) }} gün gecikme</span>
                @elseif($kalan == 0)Bugün son
                @else{{ $kalan }} gün kaldı @endif
            @else
                Ödeme son tarihi
            @endif
        </div>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 2fr 1fr; align-items: start">

    {{-- SOL: Fatura detayı --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Fatura Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Başlık</span>
                    <span class="val">{{ $fatura->baslik ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Hizmet</span>
                    <span class="val">{{ $fatura->hizmet ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Ödeme Yöntemi</span>
                    <span class="val">
                        @php $oy = $fatura->odeme_yontemi ?? '—'; @endphp
                        @if($oy === 'online')<span class="badge badge-info">💳 Online</span>
                        @elseif($oy === 'havale')<span class="badge badge-neutral">🏦 Havale/EFT</span>
                        @elseif($oy === 'elden')<span class="badge badge-warning">💵 Elden</span>
                        @else {{ $oy }}@endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Ödeme Tarihi</span>
                    <span class="val">
                        {{ !empty($fatura->odenen_tarih) ? \Carbon\Carbon::parse($fatura->odenen_tarih)->format('d.m.Y H:i') : '—' }}
                    </span>
                </div>
                @if(!empty($fatura->aciklama))
                <div class="info-item" style="grid-column:1/-1">
                    <span class="lbl">Açıklama</span>
                    <span class="val" style="white-space:pre-wrap;line-height:1.6">{{ $fatura->aciklama }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Durum değiştirme paneli --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.durum'))
        <div class="section">
            <div class="section-title">
                <i data-lucide="repeat"></i>
                <span>Durum Değiştir</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if(($fatura->durum ?? 0) != 0)
                <form action="{{ route('admin.faturalar.durum', [$fatura->id, 0]) }}" method="POST" onsubmit="return confirm('Durumu Bekleyen olarak değiştir?');">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <i data-lucide="clock"></i>
                        <span>Bekleyen Yap</span>
                    </button>
                </form>
                @endif

                @if(($fatura->durum ?? 0) != 1)
                <form action="{{ route('admin.faturalar.durum', [$fatura->id, 1]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i data-lucide="check-circle"></i>
                        <span>Ödendi Yap</span>
                    </button>
                </form>
                @endif

                @if(($fatura->durum ?? 0) != 2)
                <form action="{{ route('admin.faturalar.durum', [$fatura->id, 2]) }}" method="POST" onsubmit="return confirm('Fatura iptal edilsin mi?');">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i data-lucide="x-circle"></i>
                        <span>İptal Et</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- SAĞ: Müşteri bilgileri --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Müşteri Bilgileri</span>
            </div>

            <div style="text-align:center;margin-bottom:14px">
                <div class="profile-avatar" style="margin:0 auto 8px;width:56px;height:56px;font-size:22px">
                    {{ strtoupper(mb_substr(($fatura->ad ?? 'M'), 0, 1)) }}
                </div>
                <strong style="font-size:15px">{{ $fatura->ad ?? '' }} {{ $fatura->soyad ?? '' }}</strong>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px">
                @if(!empty($fatura->email))
                <a href="mailto:{{ $fatura->email }}" style="color:var(--text-secondary);text-decoration:none;display:flex;align-items:center;gap:8px">
                    <i data-lucide="mail" style="width:14px;height:14px"></i>
                    <span>{{ $fatura->email }}</span>
                </a>
                @endif
                @if(!empty($fatura->telefon))
                <a href="tel:{{ $fatura->telefon }}" style="color:var(--text-secondary);text-decoration:none;display:flex;align-items:center;gap:8px">
                    <i data-lucide="phone" style="width:14px;height:14px"></i>
                    <span>{{ $fatura->telefon }}</span>
                </a>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection