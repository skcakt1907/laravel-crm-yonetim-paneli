@extends('admin._layout')

@section('title', 'Ödeme Bildirimi #' . $bildirim->id)

@section('content')

@php
    $durum = (int) ($bildirim->durum ?? 0);
    $durumMap = [
        0 => ['label' => 'Bekliyor',    'class' => 'badge-warning', 'icon' => '⏳', 'mini' => 'warning'],
        1 => ['label' => 'Onaylandı',   'class' => 'badge-success', 'icon' => '✅', 'mini' => 'success'],
        2 => ['label' => 'Reddedildi',  'class' => 'badge-danger',  'icon' => '❌', 'mini' => 'danger'],
    ];
    $d = $durumMap[$durum] ?? $durumMap[0];
    $musteriAdi = $bildirim->ad ?? $bildirim->isim ?? '—';

    // Güvenli tarih parse - bozuk veri olabilir
    $tarihFmt = null;
    $tarihFmtFull = null;
    $tarihHumans = null;
    if (!empty($bildirim->tarih)) {
        try {
            $tc = \Carbon\Carbon::parse($bildirim->tarih);
            $tarihFmt = $tc->format('d.m.Y');
            $tarihFmtFull = $tc->format('d.m.Y H:i');
            $tarihHumans = $tc->diffForHumans();
        } catch (\Throwable $e) {}
    }

    $kontrolFmt = null;
    if (!empty($bildirim->kontrol_tarihi)) {
        try {
            $kontrolFmt = \Carbon\Carbon::parse($bildirim->kontrol_tarihi)->format('d.m.Y H:i');
        } catch (\Throwable $e) {}
    }
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.odeme-bildirim.index') }}">Ödeme Bildirimleri</a>
    <span class="sep">/</span>
    <span class="current">#{{ $bildirim->id }}</span>
</div>

<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, #f59e0b, #d97706); font-size:28px">
        💸
    </div>
    <div class="profile-info">
        <h1 class="profile-name">Ödeme Bildirimi #{{ $bildirim->id }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <i data-lucide="user"></i>
                {{ $musteriAdi }}
            </span>
            <span class="meta-item">
                <i data-lucide="banknote"></i>
                <strong style="color:var(--brand)">₺{{ number_format((float)($bildirim->tutar ?? 0), 2, ',', '.') }}</strong>
            </span>
            @if(!empty($bildirim->tarih))
                <span class="meta-item">
                    <i data-lucide="calendar"></i>
                    {{ $tarihFmtFull ?? '—' }}
                </span>
            @endif
        </div>
        <div style="margin-top:10px">
            <span class="badge {{ $d['class'] }}" style="font-size:13px;padding:6px 12px">
                {{ $d['icon'] }} {{ $d['label'] }}
            </span>
            @if(!empty($bildirim->kontrol_tarihi))
                <span style="font-size:11.5px;color:var(--text-muted);margin-left:8px">
                    Kontrol: {{ $kontrolFmt ?? '—' }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.odeme-bildirim.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

{{-- Mini stats --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ $d['mini'] }}">
        <div class="mini-stat-icon">{{ $d['icon'] }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value" style="font-size:17px">{{ $d['label'] }}</div>
            <div class="mini-stat-sub">
                @if($durum === 0)
                    İnceleme bekliyor
                @elseif($durum === 1)
                    Onaylandı
                @else
                    Reddedildi
                @endif
            </div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">💰</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Tutar</div>
            <div class="mini-stat-value">₺{{ number_format((float)($bildirim->tutar ?? 0), 2, ',', '.') }}</div>
            <div class="mini-stat-sub">Beyan edilen</div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Bildirim Tarihi</div>
            <div class="mini-stat-value" style="font-size:16px">
                @if(!empty($bildirim->tarih))
                    {{ $tarihFmt ?? '—' }}
                @else
                    —
                @endif
            </div>
            <div class="mini-stat-sub">
                @if(!empty($bildirim->tarih))
                    {{ $tarihHumans ?? '' }}
                @endif
            </div>
        </div>
    </div>

    <div class="mini-stat {{ !empty($bildirim->dekont) ? 'success' : 'warning' }}">
        <div class="mini-stat-icon">📎</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Dekont</div>
            <div class="mini-stat-value" style="font-size:16px">
                {{ !empty($bildirim->dekont) ? 'Var' : 'Yok' }}
            </div>
            <div class="mini-stat-sub">
                {{ !empty($bildirim->dekont) ? 'Görüntülenebilir' : 'Müşteri dekont eklemedi' }}
            </div>
        </div>
    </div>
</div>

{{-- Durum değiştirme paneli --}}
<div class="section" style="margin-top:20px">
    <div class="section-title">
        <i data-lucide="flag"></i>
        <span>Durum Değiştirme</span>
    </div>

    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
        Bu ödemeyi onayla, reddet veya tekrar inceleme bekleyene çevir. Onaylama, müşterinin hesabına işlenmesini sağlar.
    </p>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @if($durum !== 1)
            <form action="{{ route('admin.odeme-bildirim.durum', ['id' => $bildirim->id, 'durum' => 1]) }}" method="POST" style="margin:0" onsubmit="return confirm('Bu ödemeyi ONAYLA?\n\nMüşterinin hesabına yansıyacak.');">
                @csrf
                <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i data-lucide="check-circle"></i>
                    <span>Onayla</span>
                </button>
            </form>
        @endif

        @if($durum !== 0)
            <form action="{{ route('admin.odeme-bildirim.durum', ['id' => $bildirim->id, 'durum' => 0]) }}" method="POST" style="margin:0" onsubmit="return confirm('Beklemeye al?');">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="clock"></i>
                    <span>Beklemeye Al</span>
                </button>
            </form>
        @endif

        @if($durum !== 2)
            <form action="{{ route('admin.odeme-bildirim.durum', ['id' => $bildirim->id, 'durum' => 2]) }}" method="POST" style="margin:0" onsubmit="return confirm('Bu ödemeyi REDDET?');">
                @csrf
                <button type="submit" class="btn btn-secondary" style="color:var(--warning)">
                    <i data-lucide="x-circle"></i>
                    <span>Reddet</span>
                </button>
            </form>
        @endif

        <form action="{{ route('admin.odeme-bildirim.sil', $bildirim->id) }}" method="POST" onsubmit="return confirm('Bu bildirimi sil? Bu işlem geri alınamaz.');" style="margin:0;margin-left:auto">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>
        </form>
    </div>
</div>

<div class="form-grid" style="margin-top:16px">
    {{-- SOL: Detaylar --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Bildirim Detayları</span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Bildirim ID</span>
                    <span class="val">#{{ $bildirim->id }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Müşteri / Ad</span>
                    <span class="val">{{ $musteriAdi }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Tutar</span>
                    <span class="val" style="color:var(--brand);font-weight:700;font-size:15px">
                        ₺{{ number_format((float)($bildirim->tutar ?? 0), 2, ',', '.') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Bildirim Tarihi</span>
                    <span class="val">
                        @if(!empty($bildirim->tarih))
                            {{ $tarihFmtFull ?? '—' }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                @if(!empty($bildirim->kontrol_tarihi))
                    <div class="info-item">
                        <span class="lbl">Son Kontrol</span>
                        <span class="val">{{ $kontrolFmt ?? '—' }}</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="lbl">Durum</span>
                    <span class="val">
                        <span class="badge {{ $d['class'] }}">{{ $d['icon'] }} {{ $d['label'] }}</span>
                    </span>
                </div>
            </div>
        </div>

        @if(!empty($bildirim->aciklama))
            <div class="section">
                <div class="section-title">
                    <i data-lucide="message-square"></i>
                    <span>Müşteri Açıklaması</span>
                </div>
                <div style="white-space:pre-wrap;color:var(--text);line-height:1.7;font-size:14px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md)">
                    {{ $bildirim->aciklama }}
                </div>
            </div>
        @endif
    </div>

    {{-- SAĞ: Dekont --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="paperclip"></i>
                <span>Dekont</span>
            </div>

            @if(!empty($bildirim->dekont))
                @php
                    $dekontUrl = asset('tema/uploads/dekont/'.$bildirim->dekont);
                    $ext = strtolower(pathinfo($bildirim->dekont, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                @endphp

                @if($isImage)
                    <a href="{{ $dekontUrl }}" target="_blank" style="display:block">
                        <img src="{{ $dekontUrl }}" alt="Dekont" style="width:100%;border-radius:var(--radius-md);border:1px solid var(--border);display:block">
                    </a>
                @else
                    <div style="padding:24px;text-align:center;background:var(--bg-subtle);border-radius:var(--radius-md)">
                        <div style="font-size:48px;margin-bottom:12px">📄</div>
                        <div style="font-weight:600;color:var(--text);margin-bottom:4px">{{ $bildirim->dekont }}</div>
                        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase">{{ $ext }} dosyası</div>
                    </div>
                @endif

                <a href="{{ $dekontUrl }}" target="_blank" class="btn btn-secondary btn-sm" style="margin-top:10px;width:100%;justify-content:center">
                    <i data-lucide="external-link"></i>
                    <span>Yeni Sekmede Aç</span>
                </a>
            @else
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="file-x" class="empty-state-icon"></i>
                    <p>Müşteri dekont eklemedi</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection