@extends('admin._layout')

@section('title', 'Üye Detay')

@push('head')
<style>
    .timeline {
        position: relative;
        padding-left: 28px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: var(--border);
    }
    .timeline-item {
        position: relative;
        padding: 12px 0 12px 16px;
        border-bottom: 1px dashed var(--border);
    }
    .timeline-item:last-child { border-bottom: none; }
    .timeline-dot {
        position: absolute;
        left: -22px;
        top: 16px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid var(--card-bg);
        box-shadow: 0 0 0 1px var(--border);
    }
    .timeline-head {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 4px;
        align-items: baseline;
        flex-wrap: wrap;
    }
    .timeline-title {
        font-weight: 600;
        font-size: 13px;
    }
    .timeline-date {
        font-size: 11px;
        color: var(--text-muted);
        font-family: 'JetBrains Mono', monospace;
    }
    .timeline-detail {
        font-size: 12px;
        color: var(--text-muted);
    }
    .timeline-tutar {
        font-weight: 700;
        color: var(--brand-dark);
        font-size: 13px;
    }
    .quick-action-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .quick-action-list .btn {
        justify-content: flex-start;
        width: 100%;
    }
    .pwd-toggle-wrap {
        position: relative;
    }
    .pwd-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        padding: 4px;
    }
</style>
@endpush

@section('content')

@php
    $ad = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''));
    if (empty($ad)) $ad = $uye->adi_soyadi ?? 'İsimsiz Üye';
    $bas = strtoupper(mb_substr($uye->ad ?? $ad, 0, 1));
    $email = $uye->email ?? $uye->eposta ?? null;
    $tel = $uye->telefon ?? $uye->gsm ?? null;
    $firma = $uye->firmaadi ?? $uye->firma ?? null;
    $sehir = $uye->il ?? $uye->sehir ?? null;
    $durumVal = (int)($uye->durum ?? 1);
    $utipi = (int)($uye->utipi ?? 0);

    $kayitTarihi = '—';
    if (!empty($uye->tarih)) {
        try {
            $kayitTarihi = is_numeric($uye->tarih)
                ? \Carbon\Carbon::createFromTimestamp((int)$uye->tarih)->format('d.m.Y H:i')
                : \Carbon\Carbon::parse($uye->tarih)->format('d.m.Y H:i');
        } catch (\Throwable $e) {}
    }

    $sonGuncelleme = '—';
    if (!empty($uye->ktarih)) {
        try {
            $sonGuncelleme = is_numeric($uye->ktarih)
                ? \Carbon\Carbon::createFromTimestamp((int)$uye->ktarih)->format('d.m.Y H:i')
                : \Carbon\Carbon::parse($uye->ktarih)->format('d.m.Y H:i');
        } catch (\Throwable $e) {}
    }

    $toplamFatura = collect($faturalar ?? [])->sum(function($f){
        return (float)($f->toplam ?? $f->tutar ?? 0);
    });
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.uyeler.index') }}">Üyeler</a>
    <span class="sep">/</span>
    <span class="current">{{ $ad }}</span>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- PROFILE HEADER --}}
<div class="profile-header">
    <div class="profile-avatar"
         style="background:linear-gradient(135deg, var(--brand), var(--brand-dark));font-size:28px">
        {{ $bas ?: '👤' }}
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $ad }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <i data-lucide="hash"></i> #{{ $uye->id }}
            </span>
            @if($email)
            <span class="meta-item">
                <i data-lucide="mail"></i> {{ $email }}
            </span>
            @endif
            @if($tel)
            <span class="meta-item">
                <i data-lucide="phone"></i> {{ $tel }}
            </span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            @if($durumVal === 1)
                <span class="badge badge-success">Aktif</span>
            @elseif($durumVal === 2)
                <span class="badge badge-warning">Engelli</span>
            @else
                <span class="badge badge-danger">Pasif</span>
            @endif
            <span class="badge {{ $utipi === 1 ? 'badge-brand' : 'badge-neutral' }}">
                {{ $utipi === 1 ? 'Kurumsal' : 'Bireysel' }}
            </span>
            @if($firma)
                <span class="badge badge-neutral">
                    <i data-lucide="building-2" style="width:12px;height:12px"></i>
                    {{ $firma }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions">
        @if($email)
        <a href="mailto:{{ $email }}" class="btn btn-secondary btn-sm">
            <i data-lucide="mail"></i>
            <span>Mail</span>
        </a>
        @endif
        @if($tel)
        <a href="tel:{{ $tel }}" class="btn btn-secondary btn-sm">
            <i data-lucide="phone"></i>
            <span>Ara</span>
        </a>
        @endif
    </div>
</div>

{{-- 4 MİNİ STAT --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat success">
        <div class="mini-stat-icon"><i data-lucide="wallet"></i></div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Bakiye</div>
            <div class="mini-stat-value">₺{{ number_format((float)($uye->bakiye ?? 0), 2, ',', '.') }}</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon"><i data-lucide="shopping-cart"></i></div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Satın Alma</div>
            <div class="mini-stat-value">{{ count($satinalmalar ?? []) }}</div>
        </div>
    </div>
    <div class="mini-stat warning">
        <div class="mini-stat-icon"><i data-lucide="receipt"></i></div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Fatura</div>
            <div class="mini-stat-value">{{ count($faturalar ?? []) }}</div>
            <div class="mini-stat-sub">₺{{ number_format($toplamFatura, 2, ',', '.') }}</div>
        </div>
    </div>
    <div class="mini-stat danger">
        <div class="mini-stat-icon"><i data-lucide="life-buoy"></i></div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Destek</div>
            <div class="mini-stat-value">{{ count($destekler ?? []) }}</div>
            <div class="mini-stat-sub">{{ $destekYanitSayisi ?? 0 }} yanıt</div>
        </div>
    </div>
</div>

<div class="form-grid" style="margin-top:20px">
    {{-- SOL: Bilgiler + Timeline --}}
    <div>
        {{-- KİŞİSEL BİLGİLER --}}
        <div class="section" style="margin-bottom:16px">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Kişisel Bilgiler</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Ad Soyad</span>
                    <span class="val">{{ $ad }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">E-posta</span>
                    <span class="val">{{ $email ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Telefon</span>
                    <span class="val">{{ $tel ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">TC Kimlik</span>
                    <span class="val">{{ $uye->tc ?? $uye->tc_no ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Doğum Tarihi</span>
                    <span class="val">{{ $uye->dtarih ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Cinsiyet</span>
                    <span class="val">{{ $uye->cinsiyet ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- FİRMA BİLGİLERİ --}}
        @if($firma || !empty($uye->vergino))
        <div class="section" style="margin-bottom:16px">
            <div class="section-title">
                <i data-lucide="building-2"></i>
                <span>Firma Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Firma Adı</span>
                    <span class="val">{{ $firma ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vergi No</span>
                    <span class="val">{{ $uye->vergino ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vergi Dairesi</span>
                    <span class="val">{{ $uye->vergidairesi ?? '—' }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- ADRES --}}
        @if($sehir || !empty($uye->adres))
        <div class="section" style="margin-bottom:16px">
            <div class="section-title">
                <i data-lucide="map-pin"></i>
                <span>Adres Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">İl</span>
                    <span class="val">{{ $sehir ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">İlçe</span>
                    <span class="val">{{ $uye->ilce ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Posta Kodu</span>
                    <span class="val">{{ $uye->pkodu ?? '—' }}</span>
                </div>
                @if(!empty($uye->adres))
                <div class="info-item" style="grid-column:1/-1">
                    <span class="lbl">Açık Adres</span>
                    <span class="val">{{ $uye->adres }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- AKTİVİTE TIMELINE --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="activity"></i>
                <span>Aktivite Geçmişi</span>
                <span class="badge badge-neutral" style="margin-left:8px">{{ count($loglar ?? []) }}</span>
            </div>
            @if(empty($loglar) || count($loglar) === 0)
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="inbox" class="empty-state-icon"></i>
                    <h4>Henüz aktivite yok</h4>
                    <p>Bu üyenin fatura, satın alma veya destek kaydı bulunmuyor.</p>
                </div>
            @else
                <div class="timeline">
                    @foreach($loglar as $log)
                        @php
                            $logTarihFmt = '—';
                            if (!empty($log->tarih)) {
                                try {
                                    $logTarihFmt = is_numeric($log->tarih)
                                        ? \Carbon\Carbon::createFromTimestamp((int)$log->tarih)->format('d.m.Y H:i')
                                        : \Carbon\Carbon::parse($log->tarih)->format('d.m.Y H:i');
                                } catch (\Throwable $e) {}
                            }
                            $renk = $log->renk ?? '#6b7280';
                            $tipIkon = [
                                'fatura' => 'receipt',
                                'satis'  => 'shopping-cart',
                                'destek' => 'life-buoy',
                            ][$log->tip ?? ''] ?? 'circle';
                        @endphp
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background:{{ $renk }}"></div>
                            <div class="timeline-head">
                                <span class="timeline-title">
                                    <i data-lucide="{{ $tipIkon }}" style="width:14px;height:14px;display:inline-block;vertical-align:-2px;color:{{ $renk }}"></i>
                                    {{ $log->baslik ?? '—' }}
                                </span>
                                <span class="timeline-date">{{ $logTarihFmt }}</span>
                            </div>
                            <div class="timeline-detail">{{ $log->detay ?? '' }}</div>
                            @if(!empty($log->tutar))
                                <div class="timeline-tutar">₺{{ number_format((float)$log->tutar, 2, ',', '.') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- SAĞ: Aksiyonlar + Şifre + Özet --}}
    <div>
        {{-- HIZLI AKSİYONLAR --}}
        <div class="section" style="margin-bottom:16px">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı Aksiyonlar</span>
            </div>
            <div class="quick-action-list">
                <form action="{{ route('admin.uyeler.durum', [$uye->id, $durumVal === 1 ? 'pasif' : 'aktif']) }}"
                      method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:flex-start">
                        <i data-lucide="{{ $durumVal === 1 ? 'pause-circle' : 'play-circle' }}"></i>
                        <span>{{ $durumVal === 1 ? 'Pasif Yap' : 'Aktif Yap' }}</span>
                    </button>
                </form>
                @if($email)
                <a href="mailto:{{ $email }}" class="btn btn-secondary btn-sm">
                    <i data-lucide="mail"></i>
                    <span>Mail Gönder</span>
                </a>
                @endif
                @if($tel)
                <a href="tel:{{ $tel }}" class="btn btn-secondary btn-sm">
                    <i data-lucide="phone"></i>
                    <span>Ara</span>
                </a>
                @endif
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="document.getElementById('uye-sil-form').submit()"
                        style="justify-content:flex-start">
                    <i data-lucide="trash-2"></i>
                    <span>Üyeyi Sil</span>
                </button>
            </div>
            <form id="uye-sil-form" action="{{ route('admin.uyeler.sil', $uye->id) }}" method="POST"
                  style="display:none"
                  onsubmit="return confirm('Üye kalıcı olarak silinsin mi? Bu işlem geri alınamaz!')">
                @csrf
                @method('DELETE')
            </form>
        </div>

        {{-- ŞİFRE GÜNCELLE --}}
        <div class="section" style="margin-bottom:16px">
            <div class="section-title">
                <i data-lucide="key"></i>
                <span>Şifre Güncelle</span>
            </div>
            <form action="{{ route('admin.uyeler.sifre', $uye->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Yeni Şifre</label>
                    <div class="pwd-toggle-wrap">
                        <input type="password" name="yeni_sifre" required minlength="6" id="yeni_sifre"
                               class="form-input" placeholder="En az 6 karakter"
                               style="padding-right:40px">
                        <button type="button" class="pwd-toggle-btn" onclick="pwdToggle('yeni_sifre', this)">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                    <small class="form-help">Müşteriye bildirmeyi unutmayın</small>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%">
                    <i data-lucide="check"></i>
                    <span>Güncelle</span>
                </button>
            </form>
        </div>

        {{-- ÖZET --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="clipboard-list"></i>
                <span>Özet</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Kayıt Tarihi</span>
                    <span class="val">{{ $kayitTarihi }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Son Güncelleme</span>
                    <span class="val">{{ $sonGuncelleme }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Üye Tipi</span>
                    <span class="val">{{ $utipi === 1 ? 'Kurumsal' : 'Bireysel' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Bakiye</span>
                    <span class="val">₺{{ number_format((float)($uye->bakiye ?? 0), 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function pwdToggle(inputId, btn){
    var inp = document.getElementById(inputId);
    if(!inp) return;
    if(inp.type === 'password'){
        inp.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i data-lucide="eye"></i>';
    }
    if(window.lucide) window.lucide.createIcons();
}
</script>

@endsection