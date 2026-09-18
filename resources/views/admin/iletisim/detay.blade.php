@extends('admin._layout')

@section('title', 'Mesaj Detayı')

@push('head')
<style>
    .message-body {
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-lg);
        padding: 20px;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-size: 14px;
        line-height: 1.7;
        min-height: 120px;
    }
    body.theme-dark .message-body {
        background: rgba(184,182,46,0.05);
    }
    .info-item .val a {
        color: var(--brand-dark);
        text-decoration: none;
        font-weight: 500;
    }
    .info-item .val a:hover { text-decoration: underline; }
</style>
@endpush

@section('content')

@php
    $gonderen = $mesaj->ad ?? $mesaj->isim ?? '—';
    $isUnread = ($mesaj->durum ?? 0) == 0;

    $tarihFmt = null;
    $rawTarih = $mesaj->tarih ?? $mesaj->created_at ?? null;
    if (!empty($rawTarih)) {
        try {
            $tarihFmt = \Carbon\Carbon::parse($rawTarih)->format('d.m.Y H:i');
        } catch (\Throwable $e) {}
    }

    // Avatar için ilk harf
    $avatarHarf = mb_strtoupper(mb_substr(trim($gonderen) ?: '?', 0, 1, 'UTF-8'), 'UTF-8');
    if ($avatarHarf === '—' || $avatarHarf === '') $avatarHarf = '?';

    // Mesajın karakter uzunluğu
    $mesajUzunluk = mb_strlen($mesaj->mesaj ?? '', 'UTF-8');
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.iletisim.index') }}">İletişim Mesajları</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($mesaj->konu ?? 'Mesaj Detayı', 40) }}</span>
</div>

{{-- PROFILE HEADER --}}
<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg,#b8b62e,#8a8a1f);font-size:32px;font-weight:700;color:#000">
        {{ $avatarHarf }}
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $gonderen }}</h1>
        <div class="profile-meta">
            @if(!empty($mesaj->email))
                <span class="meta-item">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $mesaj->email }}" style="color:inherit;text-decoration:none">{{ $mesaj->email }}</a>
                </span>
            @endif
            @if(!empty($mesaj->telefon))
                <span class="meta-item">
                    <i data-lucide="phone"></i>
                    <a href="tel:{{ $mesaj->telefon }}" style="color:inherit;text-decoration:none">{{ $mesaj->telefon }}</a>
                </span>
            @endif
            @if($tarihFmt)
                <span class="meta-item">
                    <i data-lucide="calendar"></i>
                    {{ $tarihFmt }}
                </span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            @if($isUnread)
                <span class="badge badge-warning">
                    <i data-lucide="circle" style="width:10px;height:10px;fill:currentColor"></i>
                    Yeni
                </span>
            @else
                <span class="badge badge-success">
                    <i data-lucide="check"></i>
                    Okundu
                </span>
            @endif
            <span class="badge badge-neutral">
                <i data-lucide="hash"></i>
                #{{ $mesaj->id }}
            </span>
        </div>
    </div>
    <div class="profile-actions" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start">
        <a href="{{ route('admin.iletisim.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

{{-- 2 KOLON LAYOUT --}}
<div class="form-grid" style="margin-top:20px;grid-template-columns:2fr 1fr">

    {{-- SOL: Mesaj İçeriği --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Konu</span>
            </div>
            <div style="font-size:16px;font-weight:600;line-height:1.5;padding:8px 0">
                {{ $mesaj->konu ?? '—' }}
            </div>
        </div>

        <div class="section" style="margin-top:16px">
            <div class="section-title">
                <i data-lucide="message-square"></i>
                <span>Mesaj İçeriği</span>
                <span class="badge badge-neutral" style="margin-left:auto;font-size:11px">
                    {{ $mesajUzunluk }} karakter
                </span>
            </div>
            <div class="message-body">{{ $mesaj->mesaj ?? '—' }}</div>
        </div>

        {{-- AKSIYON BUTONLARI --}}
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;flex-wrap:wrap">
            <button type="button" class="btn btn-secondary"
                    onclick="document.getElementById('durum-form').submit();">
                <i data-lucide="{{ $isUnread ? 'check' : 'mail' }}"></i>
                <span>{{ $isUnread ? 'Okundu olarak işaretle' : 'Okunmadı olarak işaretle' }}</span>
            </button>

            <button type="button" class="btn btn-danger"
                    onclick="if(confirm('Bu mesajı silmek istediğinizden emin misiniz?')){ document.getElementById('sil-form').submit(); }">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>

            @if(!empty($mesaj->email))
                <a href="mailto:{{ $mesaj->email }}?subject=Re: {{ urlencode($mesaj->konu ?? '') }}"
                   class="btn btn-primary">
                    <i data-lucide="reply"></i>
                    <span>E-posta ile Yanıtla</span>
                </a>
            @endif
        </div>
    </div>

    {{-- SAĞ: Gönderen Bilgileri --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Gönderen Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Ad Soyad</span>
                    <span class="val">{{ $gonderen }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">E-posta</span>
                    <span class="val">
                        @if(!empty($mesaj->email))
                            <a href="mailto:{{ $mesaj->email }}">{{ $mesaj->email }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Telefon</span>
                    <span class="val">
                        @if(!empty($mesaj->telefon))
                            <a href="tel:{{ $mesaj->telefon }}">{{ $mesaj->telefon }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Mesaj Tarihi</span>
                    <span class="val">{{ $tarihFmt ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Mesaj ID</span>
                    <span class="val">#{{ $mesaj->id }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Durum</span>
                    <span class="val">
                        @if($isUnread)
                            <span class="badge badge-warning">Yeni / Okunmamış</span>
                        @else
                            <span class="badge badge-success">Okundu</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- HIZLI EYLEMLER --}}
        <div class="section" style="margin-top:16px">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı Eylemler</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                @if(!empty($mesaj->email))
                    <a href="mailto:{{ $mesaj->email }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                        <i data-lucide="mail"></i>
                        <span>E-posta Gönder</span>
                    </a>
                @endif
                @if(!empty($mesaj->telefon))
                    <a href="tel:{{ $mesaj->telefon }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                        <i data-lucide="phone"></i>
                        <span>Telefon Et</span>
                    </a>
                    @php
                        $waNum = preg_replace('/[^0-9]/', '', $mesaj->telefon);
                        if (strlen($waNum) === 10) $waNum = '90' . $waNum;
                        if (strlen($waNum) === 11 && substr($waNum, 0, 1) === '0') $waNum = '90' . substr($waNum, 1);
                    @endphp
                    @if($waNum)
                        <a href="https://wa.me/{{ $waNum }}" target="_blank"
                           class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                            <i data-lucide="message-circle"></i>
                            <span>WhatsApp</span>
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

{{-- FORM'LAR (HTML İÇ İÇE FORM YASAK — DIŞARDA) --}}
<form id="sil-form"
      action="{{ route('admin.iletisim.sil', $mesaj->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<form id="durum-form"
      action="{{ route('admin.iletisim.durum', $mesaj->id) }}"
      method="POST" style="display:none">
    @csrf
</form>

@endsection