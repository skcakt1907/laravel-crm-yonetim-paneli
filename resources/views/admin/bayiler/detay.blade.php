@extends('admin._layout')

@section('title', 'Bayi Detayı')

@push('head')
<style>
    .quick-action-list {
        display: flex; flex-direction: column; gap: 8px;
    }
    .quick-action-list form { margin: 0; }
    .quick-action-list .btn { width: 100%; justify-content: center; }

    .kredi-stats { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
    .kredi-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 0; font-size: 13px;
        border-bottom: 1px dashed var(--border);
    }
    .kredi-row:last-child { border-bottom: none; }
    .kredi-row .lbl { color: var(--text-muted); }
    .kredi-row .val { font-weight: 700; }

    .progress-bar {
        width: 100%; height: 8px;
        background: var(--bg-subtle);
        border-radius: 99px; overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%; border-radius: 99px;
        transition: width 0.5s ease;
    }
    .progress-fill.low { background: var(--success); }
    .progress-fill.mid { background: var(--warning); }
    .progress-fill.high { background: var(--danger); }

    .talep-card {
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.15);
        border-radius: var(--radius-md);
        padding: 12px;
    }
    .talep-card + .talep-card { margin-top: 8px; }

    .code-chip {
        display: inline-block;
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 13px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')

@php
    $bayiAd = trim(($bayi->ad ?? '') . ' ' . ($bayi->soyad ?? '')) ?: 'Bayi';
    $harf = mb_strtoupper(mb_substr($bayi->ad ?? 'B', 0, 1, 'UTF-8'), 'UTF-8');
    $onay = (int)($bayi->onay_durumu ?? 0);
    $aktif = (int)($bayi->durum ?? 1);

    $krediLimiti = (float)($bayi->kredi_limiti ?? 0);
    $krediKullanim = (float)($bayi->kredi_kullanim ?? 0);
    $krediKalan = max(0, $krediLimiti - $krediKullanim);
    $krediYuzde = $krediLimiti > 0 ? min(100, round(($krediKullanim / $krediLimiti) * 100)) : 0;
    $progressClass = $krediYuzde >= 80 ? 'high' : ($krediYuzde >= 50 ? 'mid' : 'low');
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.bayiler.index') }}">Bayiler</a>
    <span class="sep">/</span>
    <span class="current">{{ $bayiAd }}</span>
</div>

{{-- PROFILE HEADER --}}
<div class="profile-header">
    <div class="profile-avatar"
         style="background:linear-gradient(135deg,#b8b62e,#8a8a1f);font-size:30px;font-weight:700;color:#000">
        {{ $harf }}
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $bayiAd }}</h1>
        <div class="profile-meta">
            @if(!empty($bayi->email))
                <span class="meta-item">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $bayi->email }}" style="color:inherit;text-decoration:none">{{ $bayi->email }}</a>
                </span>
            @endif
            @if(!empty($bayi->telefon))
                <span class="meta-item">
                    <i data-lucide="phone"></i>
                    {{ $bayi->telefon }}
                </span>
            @endif
            @if(!empty($bayi->il))
                <span class="meta-item">
                    <i data-lucide="map-pin"></i>
                    {{ $bayi->il }}{{ !empty($bayi->ilce) ? ' / '.$bayi->ilce : '' }}
                </span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <span class="code-chip">{{ $bayi->bayi_kodu ?? '—' }}</span>

            @if($onay === 1)
                <span class="badge badge-success">
                    <i data-lucide="check" style="width:11px;height:11px"></i>
                    Onaylı
                </span>
            @elseif($onay === 2)
                <span class="badge badge-danger">
                    <i data-lucide="x" style="width:11px;height:11px"></i>
                    Reddedildi
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
            @else
                <span class="badge badge-brand">
                    <i data-lucide="zap" style="width:11px;height:11px"></i>
                    Aktif
                </span>
            @endif

            <span class="badge badge-neutral">
                <i data-lucide="hash" style="width:11px;height:11px"></i>
                {{ $bayi->id }}
            </span>
        </div>
    </div>
    <div class="profile-actions" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start">
        <a href="{{ route('admin.bayiler.duzenle', $bayi->id) }}" class="btn btn-primary btn-sm">
            <i data-lucide="edit-3"></i>
            <span>Düzenle</span>
        </a>
        <a href="{{ route('admin.bayiler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

{{-- 4 MİNİ STAT --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat info">
        <div class="mini-stat-icon">
            <i data-lucide="shopping-cart"></i>
        </div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Toplam Satış</div>
            <div class="mini-stat-value">{{ $stats['toplam_satis'] ?? 0 }}</div>
            <div class="mini-stat-sub">Adet</div>
        </div>
    </div>

    <div class="mini-stat success">
        <div class="mini-stat-icon">
            <i data-lucide="wallet"></i>
        </div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Toplam Kazanç</div>
            <div class="mini-stat-value">₺{{ number_format($stats['toplam_kazanc'] ?? 0, 2, ',', '.') }}</div>
            <div class="mini-stat-sub">Tüm zamanlar</div>
        </div>
    </div>

    <div class="mini-stat warning">
        <div class="mini-stat-icon">
            <i data-lucide="piggy-bank"></i>
        </div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Çekilebilir</div>
            <div class="mini-stat-value">₺{{ number_format($stats['cekilebilir_bakiye'] ?? 0, 2, ',', '.') }}</div>
            <div class="mini-stat-sub">Hazır bakiye</div>
        </div>
    </div>

    <div class="mini-stat danger">
        <div class="mini-stat-icon">
            <i data-lucide="banknote"></i>
        </div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Çekilen</div>
            <div class="mini-stat-value">₺{{ number_format($stats['cekilen_toplam'] ?? 0, 2, ',', '.') }}</div>
            <div class="mini-stat-sub">Ödenen toplam</div>
        </div>
    </div>
</div>

{{-- 2 KOLON LAYOUT --}}
<div class="form-grid" style="margin-top:20px;grid-template-columns:2fr 1fr">

    {{-- SOL: Bilgi kartları + satışlar --}}
    <div>
        {{-- BAYİ BİLGİLERİ --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Bayi Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">E-posta</span>
                    <span class="val">
                        @if(!empty($bayi->email))
                            <a href="mailto:{{ $bayi->email }}" style="color:var(--brand-dark);text-decoration:none">{{ $bayi->email }}</a>
                        @else — @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Telefon</span>
                    <span class="val">{{ $bayi->telefon ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Komisyon Oranı</span>
                    <span class="val" style="color:var(--brand-dark)">
                        %{{ number_format($bayi->komisyon_orani ?? 0, 2) }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Müşteri İndirim</span>
                    <span class="val">%{{ number_format($bayi->musteri_indirim_orani ?? 0, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Peşin Komisyon</span>
                    <span class="val">%{{ number_format($bayi->pesin_komisyon_orani ?? 0, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vadeli Komisyon</span>
                    <span class="val">%{{ number_format($bayi->vadeli_komisyon_orani ?? 0, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vergi No</span>
                    <span class="val">{{ $bayi->vergi_no ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vergi Dairesi</span>
                    <span class="val">{{ $bayi->vergi_dairesi ?? '—' }}</span>
                </div>
                <div class="info-item full">
                    <span class="lbl">Adres</span>
                    <span class="val">{{ $bayi->adres ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- BANKA BİLGİLERİ --}}
        @if(!empty($bayi->banka_adi) || !empty($bayi->iban))
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="landmark"></i>
                    <span>Banka Bilgileri</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="lbl">Banka</span>
                        <span class="val">{{ $bayi->banka_adi ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="lbl">Hesap Sahibi</span>
                        <span class="val">{{ $bayi->hesap_sahibi ?? '—' }}</span>
                    </div>
                    <div class="info-item full">
                        <span class="lbl">IBAN</span>
                        <span class="val" style="font-family:'SF Mono','Monaco',monospace;letter-spacing:0.5px">
                            {{ $bayi->iban ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- KOMİSYON GÜNCELLEME (HIZLI) --}}
        <div class="section" style="margin-top:16px">
            <div class="section-title">
                <i data-lucide="percent"></i>
                <span>Komisyon Oranını Hızlı Güncelle</span>
            </div>
            <form action="{{ route('admin.bayiler.komisyon', $bayi->id) }}" method="POST">
                @csrf
                <div style="display:flex;gap:10px;align-items:flex-end">
                    <div class="form-group" style="flex:1;margin:0">
                        <label class="form-label">Yeni Komisyon (%)</label>
                        <input type="number" step="0.01" name="komisyon_orani"
                               value="{{ $bayi->komisyon_orani ?? 0 }}"
                               min="0" max="100" required class="form-input">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Güncelle</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- SON SATIŞLAR --}}
        @if(isset($satislar) && count($satislar) > 0)
            <div class="section" style="margin-top:16px;padding:0">
                <div style="padding:16px 16px 0">
                    <div class="section-title" style="border-bottom:none;padding-bottom:0;margin-bottom:0">
                        <i data-lucide="shopping-cart"></i>
                        <span>Son Satışlar</span>
                        <span class="badge badge-neutral" style="margin-left:auto;font-size:11px">
                            {{ count($satislar) }} kayıt
                        </span>
                    </div>
                </div>
                <div class="table-wrap" style="border:none;margin-top:12px">
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Sipariş</th>
                                    <th>Tutar</th>
                                    <th>Komisyon</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($satislar->toArray(), 0, 10) as $s)
                                    @php
                                        $sTarih = null;
                                        $rawT = $s->created_at ?? $s->tarih ?? null;
                                        if (!empty($rawT)) {
                                            try { $sTarih = \Carbon\Carbon::parse($rawT)->format('d.m.Y H:i'); }
                                            catch (\Throwable $e) {}
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span style="font-family:monospace;font-size:12px;color:var(--text-muted)">
                                                #{{ $s->id }}
                                            </span>
                                        </td>
                                        <td style="font-weight:700">
                                            ₺{{ number_format($s->tutar ?? $s->satis_tutari ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td style="color:var(--brand-dark);font-weight:700">
                                            ₺{{ number_format($s->komisyon_tutari ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td style="font-size:12px;color:var(--text-muted)">
                                            {{ $sTarih ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- SAĞ: Aksiyonlar + Kredi + Ödeme talepleri --}}
    <div>
        {{-- HIZLI AKSİYONLAR --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı Aksiyonlar</span>
            </div>
            <div class="quick-action-list">
                <a href="{{ route('admin.bayiler.duzenle', $bayi->id) }}" class="btn btn-primary btn-sm">
                    <i data-lucide="edit-3"></i>
                    <span>Düzenle</span>
                </a>

                @if($onay !== 1)
                    <form action="{{ route('admin.bayiler.onay', [$bayi->id, 'onayli']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--success)">
                            <i data-lucide="check"></i>
                            <span>Onayla</span>
                        </button>
                    </form>
                @endif

                @if($onay !== 2)
                    <form action="{{ route('admin.bayiler.onay', [$bayi->id, 'reddedildi']) }}" method="POST"
                          onsubmit="return confirm('Bayiyi reddedilmiş olarak işaretle?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger)">
                            <i data-lucide="x"></i>
                            <span>Reddet</span>
                        </button>
                    </form>
                @endif

                @if($onay !== 0)
                    <form action="{{ route('admin.bayiler.onay', [$bayi->id, 'beklemede']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <i data-lucide="clock"></i>
                            <span>Beklemeye Al</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.bayiler.sil', $bayi->id) }}" method="POST"
                      onsubmit="return confirm('Bu bayiyi silmek istediğinize emin misiniz?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i data-lucide="trash-2"></i>
                        <span>Sil</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- KREDİ LİMİTİ --}}
        <div class="section" style="margin-top:14px">
            <div class="section-title">
                <i data-lucide="credit-card"></i>
                <span>Kredi Limiti</span>
            </div>

            <div class="kredi-stats">
                <div class="kredi-row">
                    <span class="lbl">Limit</span>
                    <span class="val" style="color:var(--brand-dark)">
                        ₺{{ number_format($krediLimiti, 2, ',', '.') }}
                    </span>
                </div>
                <div class="kredi-row">
                    <span class="lbl">Kullanılan</span>
                    <span class="val" style="color:var(--danger)">
                        ₺{{ number_format($krediKullanim, 2, ',', '.') }}
                    </span>
                </div>
                <div class="kredi-row">
                    <span class="lbl">Kalan</span>
                    <span class="val" style="color:var(--success)">
                        ₺{{ number_format($krediKalan, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            @if($krediLimiti > 0)
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:4px">
                        <span>Kullanım</span>
                        <span>%{{ $krediYuzde }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill {{ $progressClass }}" style="width:{{ $krediYuzde }}%"></div>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.bayiler.kredi', $bayi->id) }}" method="POST"
                  style="display:flex;flex-direction:column;gap:8px">
                @csrf
                <input type="number" name="kredi_limiti"
                       value="{{ $krediLimiti }}"
                       min="0" step="100" class="form-input"
                       placeholder="Kredi limiti (₺)">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="save"></i>
                    <span>Limiti Güncelle</span>
                </button>
            </form>

            @if($krediKullanim > 0)
                <form action="{{ route('admin.bayiler.kredi.sifirla', $bayi->id) }}" method="POST"
                      style="margin-top:8px"
                      onsubmit="return confirm('Kredi kullanımı sıfırlansın mı?');">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--warning);width:100%">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Kullanımı Sıfırla</span>
                    </button>
                </form>
            @endif
        </div>

        {{-- ÖDEME TALEPLERİ --}}
        @if(isset($odemeTalepleri) && count($odemeTalepleri) > 0)
            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="hand-coins"></i>
                    <span>Ödeme Talepleri</span>
                    <span class="badge badge-neutral" style="margin-left:auto;font-size:11px">
                        {{ count($odemeTalepleri) }}
                    </span>
                </div>
                @foreach($odemeTalepleri->take(5) as $t)
                    @php
                        $tTarih = null;
                        $rawTT = $t->created_at ?? $t->tarih ?? null;
                        if (!empty($rawTT)) {
                            try { $tTarih = \Carbon\Carbon::parse($rawTT)->format('d.m.Y H:i'); }
                            catch (\Throwable $e) {}
                        }
                    @endphp
                    <div class="talep-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                            <span style="font-weight:700;color:var(--brand-dark);font-size:14px">
                                ₺{{ number_format($t->tutar ?? 0, 2, ',', '.') }}
                            </span>
                            @if(($t->durum ?? '') === 'beklemede')
                                <span class="badge badge-warning">
                                    <i data-lucide="clock" style="width:11px;height:11px"></i>
                                    Beklemede
                                </span>
                            @elseif(($t->durum ?? '') === 'onaylandi')
                                <span class="badge badge-success">
                                    <i data-lucide="check" style="width:11px;height:11px"></i>
                                    Onaylandı
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    <i data-lucide="x" style="width:11px;height:11px"></i>
                                    Reddedildi
                                </span>
                            @endif
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px">
                            {{ $tTarih ?? '—' }}
                        </div>

                        @if(($t->durum ?? '') === 'beklemede')
                            <div style="display:flex;gap:6px">
                                <form action="{{ route('admin.bayiler.odeme.onayla', $t->id) }}" method="POST" style="flex:1">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%">
                                        <i data-lucide="check" style="width:13px;height:13px"></i>
                                        <span>Onayla</span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.bayiler.odeme.reddet', $t->id) }}" method="POST" style="flex:1"
                                      onsubmit="return confirm('Ödeme talebi reddedilsin mi?');">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;color:var(--danger)">
                                        <i data-lucide="x" style="width:13px;height:13px"></i>
                                        <span>Reddet</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection