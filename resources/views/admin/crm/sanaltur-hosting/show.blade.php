@extends('admin._layout')
@section('title', ($row->domain ?? '—') . ' — Sanal Tur Hosting Detay')
@section('content')
@php
    $domain = $row->domain ?? '—';
    $baslangic = $row->baslangic_tarih ?? null;
    $bitis = $row->bitis_tarih ?? null;
    $tutar = $row->tutar ?? 0;
    $statusRaw = $row->durum ?? '';
    $statusMap = [
        '1' => ['label' => 'Aktif', 'class' => 'badge-success', 'icon' => '🟢'],
        1   => ['label' => 'Aktif', 'class' => 'badge-success', 'icon' => '🟢'],
        '0' => ['label' => 'Pasif', 'class' => 'badge-danger',  'icon' => '🔴'],
        0   => ['label' => 'Pasif', 'class' => 'badge-danger',  'icon' => '🔴'],
    ];
    $status = $statusMap[$statusRaw] ?? ['label' => $statusRaw ?: 'Bilinmiyor', 'class' => 'badge-neutral', 'icon' => '❓'];
    $kalanGun = null;
    if ($bitis) {
        try { $kalanGun = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($bitis)->startOfDay(), false); } catch (\Throwable $e) {}
    }
    $yas = null;
    if ($baslangic) {
        try { $yas = (int) \Carbon\Carbon::parse($baslangic)->startOfDay()->diffInDays(now()->startOfDay()); } catch (\Throwable $e) {}
    }
    $musteriAdi = $row->uye_firma ?: trim(($row->uye_ad ?? '').' '.($row->uye_soyad ?? '')) ?: ($row->uye_email ?? '—');
    $hizmetler = [];
    if (!empty($row->hizmetler)) {
        $dec = is_array($row->hizmetler) ? $row->hizmetler : json_decode($row->hizmetler, true);
        if (is_array($dec)) $hizmetler = $dec;
    }
@endphp
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.sanaltur-hosting.index') }}">Sanal Tur Hosting Takip</a>
    <span class="sep">/</span>
    <span class="current">{{ $domain }}</span>
</div>
<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, #3b82f6, #1e40af); font-size:30px">
        🎦
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $domain }}</h1>
        <div class="profile-meta">
            @if(!empty($row->uyeid))
                <span class="meta-item">
                    <i data-lucide="user"></i>
                    <a href="{{ url('/admin/uyeler?search='.urlencode($row->uye_email ?? '')) }}" style="color:inherit;text-decoration:none;font-weight:600">
                        {{ $musteriAdi }}
                    </a>
                </span>
            @endif
            @if(!empty($row->uye_email))
                <span class="meta-item">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $row->uye_email }}" style="color:inherit;text-decoration:none">{{ $row->uye_email }}</a>
                </span>
            @endif
            @if($baslangic)
                <span class="meta-item">
                    <i data-lucide="calendar-check"></i>
                    Kayıt: {{ \Carbon\Carbon::parse($baslangic)->format('d.m.Y') }}
                </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <span class="badge {{ $status['class'] }}" style="font-size:12px;padding:5px 10px">
                {{ $status['icon'] }} {{ $status['label'] }}
            </span>
            @if($kalanGun !== null)
                @if($kalanGun < 0)
                    <span class="badge badge-danger" style="font-size:12px;padding:5px 10px">⚠️ {{ abs($kalanGun) }} gün geçti</span>
                @elseif($kalanGun <= 7)
                    <span class="badge badge-danger" style="font-size:12px;padding:5px 10px">🔥 {{ $kalanGun }} gün kaldı</span>
                @elseif($kalanGun <= 30)
                    <span class="badge badge-warning" style="font-size:12px;padding:5px 10px">⏰ {{ $kalanGun }} gün kaldı</span>
                @endif
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="https://{{ $domain }}" target="_blank" class="btn btn-secondary btn-sm" title="Domain'i yeni sekmede aç">
            <i data-lucide="external-link"></i>
            <span>Siteyi Aç</span>
        </a>
        <a href="{{ route('admin.crm.sanaltur-hosting.edit', ['id' => $row->id]) }}" class="btn btn-primary btn-sm">
            <i data-lucide="edit-2"></i>
            <span>Düzenle</span>
        </a>
        <form action="{{ route('admin.crm.sanaltur-hosting.destroy', ['id' => $row->id]) }}" method="POST" onsubmit="return confirm('Bu kaydı silmek istediğine emin misin?');" style="margin:0">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger)">
                <i data-lucide="trash-2"></i>
            </button>
        </form>
    </div>
</div>
@if($kalanGun !== null && $kalanGun <= 30 && $kalanGun > -90)
    <div class="alert {{ $kalanGun < 0 ? 'alert-danger' : ($kalanGun <= 7 ? 'alert-danger' : 'alert-warning') }}" style="margin-top:16px;display:flex;align-items:center;gap:14px;padding:14px 18px">
        <div style="font-size:28px;line-height:1">
            @if($kalanGun < 0) ⚠️ @elseif($kalanGun <= 7) 🔥 @else ⏰ @endif
        </div>
        <div style="flex:1">
            <div style="font-weight:700;margin-bottom:2px">
                @if($kalanGun < 0)
                    Süre {{ abs($kalanGun) }} Gün Önce Doldu!
                @elseif($kalanGun == 0)
                    Bugün Bitiyor!
                @elseif($kalanGun <= 7)
                    {{ $kalanGun }} Gün İçinde Bitecek!
                @else
                    {{ $kalanGun }} Gün İçinde Bitecek
                @endif
            </div>
            <div style="font-size:12.5px;opacity:0.85">
                Bitiş tarihi: <strong>{{ $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : '—' }}</strong>
                — Müşteriyi bilgilendir ve yenileme teklifi gönder.
            </div>
        </div>
        @if(!empty($row->uye_email))
            <a href="mailto:{{ $row->uye_email }}?subject=Sanal Tur Hosting Yenileme: {{ $domain }}" class="btn btn-primary btn-sm">
                <i data-lucide="mail"></i>
                <span>Mail Gönder</span>
            </a>
        @endif
    </div>
@endif
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ $kalanGun !== null && $kalanGun < 0 ? 'danger' : ($kalanGun !== null && $kalanGun <= 30 ? 'warning' : 'info') }}">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Kalan Süre</div>
            <div class="mini-stat-value" style="font-size:18px">
                @if($kalanGun === null) —
                @elseif($kalanGun < 0) {{ abs($kalanGun) }} gün geçti
                @elseif($kalanGun == 0) Bugün biter
                @else {{ $kalanGun }} gün @endif
            </div>
            <div class="mini-stat-sub">{{ $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : 'Tarih yok' }}</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon">💰</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Tutar</div>
            <div class="mini-stat-value">₺{{ number_format((float) $tutar, 2, ',', '.') }}</div>
            <div class="mini-stat-sub">Toplam</div>
        </div>
    </div>
    <div class="mini-stat {{ $status['class'] === 'badge-success' ? 'success' : ($status['class'] === 'badge-danger' ? 'danger' : 'warning') }}">
        <div class="mini-stat-icon">{{ $status['icon'] }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value" style="font-size:16px">{{ $status['label'] }}</div>
            <div class="mini-stat-sub">Sanal tur hosting</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon">⏱️</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Kayıt Yaşı</div>
            <div class="mini-stat-value" style="font-size:18px">
                @if($yas === null) —
                @elseif($yas < 30) {{ $yas }} gün
                @elseif($yas < 365) {{ floor($yas/30) }} ay
                @else {{ number_format($yas/365, 1, ',', '.') }} yıl @endif
            </div>
            <div class="mini-stat-sub">{{ $baslangic ? \Carbon\Carbon::parse($baslangic)->format('d.m.Y') : 'Başlangıç yok' }}</div>
        </div>
    </div>
</div>
<div class="form-grid" style="margin-top:20px">
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Kayıt Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Domain / Hosting</span>
                    <span class="val" style="font-weight:700;color:var(--brand)">🎦 {{ $domain }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Durum</span>
                    <span class="val"><span class="badge {{ $status['class'] }}">{{ $status['icon'] }} {{ $status['label'] }}</span></span>
                </div>
                <div class="info-item">
                    <span class="lbl">Kayıt ID</span>
                    <span class="val">#{{ $row->id }}</span>
                </div>
                @if(!empty($row->saglayici))
                    <div class="info-item">
                        <span class="lbl">Sağlayıcı</span>
                        <span class="val">{{ $row->saglayici }}</span>
                    </div>
                @endif
                @if(!empty($hizmetler))
                    <div class="info-item">
                        <span class="lbl">Hizmetler</span>
                        <span class="val">{{ implode(', ', $hizmetler) }}</span>
                    </div>
                @endif
                @if(!empty($row->vds))
                    <div class="info-item">
                        <span class="lbl">VDS</span>
                        <span class="val">{{ $row->vds }}</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="lbl">Başlangıç Tarihi</span>
                    <span class="val">{{ $baslangic ? \Carbon\Carbon::parse($baslangic)->format('d.m.Y') : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Bitiş Tarihi</span>
                    <span class="val" style="{{ $kalanGun !== null && $kalanGun < 30 ? 'color:var(--danger);font-weight:700' : '' }}">
                        {{ $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : '—' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Tutar</span>
                    <span class="val" style="color:var(--brand);font-weight:700">₺{{ number_format((float) $tutar, 2, ',', '.') }}</span>
                </div>
                @if(!empty($row->created_at))
                    <div class="info-item">
                        <span class="lbl">Sisteme Eklendi</span>
                        <span class="val">{{ \Carbon\Carbon::parse($row->created_at)->format('d.m.Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>
        @if(!empty($row->mesaj))
            <div class="section">
                <div class="section-title">
                    <i data-lucide="message-square"></i>
                    <span>Notlar</span>
                </div>
                <div style="white-space:pre-wrap;color:var(--text);line-height:1.7;font-size:14px">{{ $row->mesaj }}</div>
            </div>
        @endif
    </div>
    <div>
        @if(!empty($row->uyeid))
            <div class="section">
                <div class="section-title">
                    <i data-lucide="user"></i>
                    <span>Müşteri Bilgisi</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);margin-bottom:12px">
                    <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--brand),#8a8a1f);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;flex-shrink:0">
                        {{ strtoupper(mb_substr($musteriAdi, 0, 1, 'UTF-8')) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:14px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $musteriAdi }}</div>
                        @if(!empty($row->uye_email))
                            <div style="font-size:11.5px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row->uye_email }}</div>
                        @endif
                    </div>
                </div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <a href="{{ url('/admin/uyeler?search='.urlencode($row->uye_email ?? '')) }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                        <i data-lucide="external-link"></i>
                        <span>Üye Profili</span>
                    </a>
                    @if(!empty($row->uye_email))
                        <a href="mailto:{{ $row->uye_email }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                            <i data-lucide="mail"></i>
                            <span>Mail At</span>
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="section">
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="user-x" class="empty-state-icon"></i>
                    <p>Bu kayıt bir üye ile eşleşmemiş.</p>
                </div>
            </div>
        @endif
        <div class="section">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı İşlemler</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
                <a href="https://{{ $domain }}" target="_blank" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                    <i data-lucide="globe"></i>
                    <span>Domain'i Tarayıcıda Aç</span>
                </a>
                <a href="https://who.is/whois/{{ $domain }}" target="_blank" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                    <i data-lucide="search"></i>
                    <span>WHOIS Sorgula</span>
                </a>
                <a href="{{ route('admin.crm.sanaltur-hosting.edit', ['id' => $row->id]) }}" class="btn btn-primary btn-sm" style="justify-content:flex-start;margin-top:6px">
                    <i data-lucide="edit-2"></i>
                    <span>Bu Kaydı Düzenle</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
