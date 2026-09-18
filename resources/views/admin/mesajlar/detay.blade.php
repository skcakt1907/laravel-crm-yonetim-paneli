@extends('admin._layout')

@section('title', 'Mesaj #' . $mesaj->id)

@section('content')

@php
    $gonderen = $mesaj->gonderen ?? $mesaj->ad ?? $mesaj->isim ?? '—';
    $konu = $mesaj->konu ?? $mesaj->baslik ?? 'Konusuz';
    $icerik = $mesaj->icerik ?? $mesaj->mesaj ?? '';
    $email = $mesaj->email ?? null;
    $telefon = $mesaj->telefon ?? $mesaj->tel ?? null;
    $isOkunmamis = (int)($mesaj->durum ?? 0) === 0;

    // Güvenli tarih parse
    $tarihFmt = null;
    $tarihHumans = null;
    $tarihVal = $mesaj->tarih ?? $mesaj->created_at ?? null;
    if (!empty($tarihVal)) {
        try {
            $tc = \Carbon\Carbon::parse($tarihVal);
            $tarihFmt = $tc->format('d.m.Y H:i');
            $tarihHumans = $tc->diffForHumans();
        } catch (\Throwable $e) {}
    }
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.mesajlar.index') }}">Mesajlar</a>
    <span class="sep">/</span>
    <span class="current">#{{ $mesaj->id }}</span>
</div>

{{-- Profile Header --}}
<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, #3b82f6, #1e40af); font-size:28px;color:#fff">
        {{ strtoupper(mb_substr($gonderen, 0, 1, 'UTF-8')) }}
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $gonderen }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <i data-lucide="hash"></i> Mesaj #{{ $mesaj->id }}
            </span>
            @if($email)
                <span class="meta-item">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $email }}" style="color:inherit;text-decoration:none">{{ $email }}</a>
                </span>
            @endif
            @if($telefon)
                <span class="meta-item">
                    <i data-lucide="phone"></i>
                    <a href="tel:{{ $telefon }}" style="color:inherit;text-decoration:none">{{ $telefon }}</a>
                </span>
            @endif
            @if($tarihFmt)
                <span class="meta-item">
                    <i data-lucide="calendar"></i> {{ $tarihFmt }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.mesajlar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
        @if($email)
            <a href="mailto:{{ $email }}?subject=RE: {{ urlencode($konu) }}" class="btn btn-primary btn-sm">
                <i data-lucide="reply"></i>
                <span>Mail ile Cevapla</span>
            </a>
        @endif
    </div>
</div>

{{-- Mini Stats --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ $isOkunmamis ? 'warning' : 'success' }}">
        <div class="mini-stat-icon">{{ $isOkunmamis ? '📬' : '✅' }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value" style="font-size:17px">{{ $isOkunmamis ? 'Okunmamış' : 'Okundu' }}</div>
            <div class="mini-stat-sub">
                @if($isOkunmamis)
                    Henüz görüntülenmedi
                @else
                    Şimdi okundu olarak işaretlendi
                @endif
            </div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Tarih</div>
            <div class="mini-stat-value" style="font-size:15px">{{ $tarihFmt ?? '—' }}</div>
            <div class="mini-stat-sub">{{ $tarihHumans ?? '' }}</div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">📝</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">İçerik Boyutu</div>
            <div class="mini-stat-value">{{ mb_strlen($icerik) }}</div>
            <div class="mini-stat-sub">karakter</div>
        </div>
    </div>

    <div class="mini-stat {{ ($email || $telefon) ? 'success' : 'warning' }}">
        <div class="mini-stat-icon">📞</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">İletişim</div>
            <div class="mini-stat-value" style="font-size:15px">
                {{ ($email || $telefon) ? 'Mevcut' : 'Eksik' }}
            </div>
            <div class="mini-stat-sub">
                @if($email && $telefon)
                    Mail + Tel
                @elseif($email)
                    Sadece mail
                @elseif($telefon)
                    Sadece tel
                @else
                    Bilgi yok
                @endif
            </div>
        </div>
    </div>
</div>

<div class="form-grid" style="margin-top:20px">
    {{-- SOL: Mesaj içeriği --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="message-square"></i>
                <span>Konu</span>
            </div>
            <div style="font-size:18px;font-weight:700;color:var(--text);padding:12px 14px;background:var(--bg-subtle);border-radius:var(--radius-md)">
                {{ $konu }}
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Mesaj İçeriği</span>
            </div>
            @if(!empty($icerik))
                <div style="white-space:pre-wrap;color:var(--text);line-height:1.8;font-size:14px;padding:16px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md);word-break:break-word">{{ $icerik }}</div>
            @else
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="file-x" class="empty-state-icon"></i>
                    <p>Mesaj içeriği boş</p>
                </div>
            @endif
        </div>
    </div>

    {{-- SAĞ: Gönderen bilgileri + işlemler --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Gönderen</span>
            </div>

            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);margin-bottom:12px">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;flex-shrink:0">
                    {{ strtoupper(mb_substr($gonderen, 0, 1, 'UTF-8')) }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:14px;color:var(--text)">{{ $gonderen }}</div>
                    @if($email)
                        <div style="font-size:11.5px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $email }}</div>
                    @endif
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Ad / İsim</span>
                    <span class="val">{{ $gonderen }}</span>
                </div>
                @if($email)
                    <div class="info-item">
                        <span class="lbl">E-posta</span>
                        <span class="val">
                            <a href="mailto:{{ $email }}" style="color:var(--brand);text-decoration:none">{{ $email }}</a>
                        </span>
                    </div>
                @endif
                @if($telefon)
                    <div class="info-item">
                        <span class="lbl">Telefon</span>
                        <span class="val">
                            <a href="tel:{{ $telefon }}" style="color:var(--brand);text-decoration:none">{{ $telefon }}</a>
                        </span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="lbl">Tarih</span>
                    <span class="val">{{ $tarihFmt ?? '—' }}</span>
                </div>
            </div>

            @if($email || $telefon)
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    @if($email)
                        <a href="mailto:{{ $email }}?subject=RE: {{ urlencode($konu) }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                            <i data-lucide="mail"></i>
                            <span>Mail At</span>
                        </a>
                    @endif
                    @if($telefon)
                        <a href="tel:{{ $telefon }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                            <i data-lucide="phone"></i>
                            <span>Ara</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="settings"></i>
                <span>İşlemler</span>
            </div>

            <form action="{{ route('admin.mesajlar.sil', $mesaj->id) }}" method="POST" onsubmit="return confirm('Bu mesajı silmek istediğine emin misin?\nBu işlem geri alınamaz!');" style="margin:0">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="width:100%;justify-content:center">
                    <i data-lucide="trash-2"></i>
                    <span>Mesajı Sil</span>
                </button>
            </form>
        </div>
    </div>
</div>

@endsection