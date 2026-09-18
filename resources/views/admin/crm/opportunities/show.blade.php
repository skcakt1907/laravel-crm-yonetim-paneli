@extends('admin._layout')

@section('title', $opportunity->baslik)

@section('content')

@php
    $durumMap = [
        'acik'       => ['label' => 'Açık',       'class' => 'badge-warning', 'icon' => '🔓'],
        'beklemede'  => ['label' => 'Beklemede',  'class' => 'badge-neutral', 'icon' => '⏳'],
        'kazanildi'  => ['label' => 'Kazanıldı',  'class' => 'badge-success', 'icon' => '🏆'],
        'kaybedildi' => ['label' => 'Kaybedildi', 'class' => 'badge-danger',  'icon' => '❌'],
    ];
    $oncelikMap = [
        1 => ['label' => 'Düşük',   'class' => 'badge-success', 'icon' => '🟢'],
        2 => ['label' => 'Normal',  'class' => 'badge-brand',   'icon' => '🟡'],
        3 => ['label' => 'Yüksek',  'class' => 'badge-warning', 'icon' => '🟠'],
        4 => ['label' => 'Acil',    'class' => 'badge-danger',  'icon' => '🔴'],
        5 => ['label' => 'Kritik',  'class' => 'badge-danger',  'icon' => '⚫'],
    ];
    $paraSimgesi = ['TRY'=>'₺', 'USD'=>'$', 'EUR'=>'€', 'AED'=>'د.إ'][$opportunity->para_birimi] ?? '';

    $durum = $durumMap[$opportunity->durum] ?? $durumMap['acik'];
    $oncelik = $oncelikMap[(int)($opportunity->oncelik ?? 2)] ?? $oncelikMap[2];

    $kapanisGun = null;
    if (!empty($opportunity->beklenen_kapanis)) {
        try {
            $kapanisGun = \Carbon\Carbon::parse($opportunity->beklenen_kapanis)->diffInDays(now(), false) * -1;
        } catch (\Throwable $e) {}
    }

    $yas = $opportunity->created_at ? $opportunity->created_at->diffInDays(now()) : 0;
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.firsatlar.index') }}">Fırsatlar</a>
    <span class="sep">/</span>
    <span class="current">{{ Str::limit($opportunity->baslik, 40) }}</span>
</div>

{{-- Profile Header --}}
<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, var(--brand), #8a8a1f); font-size:28px">
        📈
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $opportunity->baslik }}</h1>
        <div class="profile-meta">
            @if($opportunity->musteri)
                <span class="meta-item">
                    <i data-lucide="user"></i>
                    <a href="{{ route('admin.crm.musteriler.show', $opportunity->musteri->id) }}" style="color:inherit;text-decoration:none;font-weight:600">
                        {{ $opportunity->musteri->adi }}@if(!empty($opportunity->musteri->unvan)) — {{ $opportunity->musteri->unvan }}@endif
                    </a>
                </span>
            @endif
            @if($opportunity->sorumlu)
                <span class="meta-item">
                    <i data-lucide="user-check"></i>
                    {{ $opportunity->sorumlu->adi ?: $opportunity->sorumlu->kullaniciadi }}
                </span>
            @endif
            <span class="meta-item">
                <i data-lucide="calendar"></i>
                {{ $opportunity->created_at?->format('d.m.Y') ?? '—' }} ({{ $yas }} gün önce)
            </span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <span class="badge {{ $durum['class'] }}" style="font-size:12px;padding:5px 10px">
                {{ $durum['icon'] }} {{ $durum['label'] }}
            </span>
            <span class="badge {{ $oncelik['class'] }}" style="font-size:12px;padding:5px 10px">
                {{ $oncelik['icon'] }} {{ $oncelik['label'] }}
            </span>
            @if($opportunity->pipeline)
                <span class="badge badge-neutral" style="font-size:12px;padding:5px 10px">
                    <i data-lucide="git-branch" style="width:11px;height:11px"></i>
                    {{ $opportunity->pipeline->adi ?? $opportunity->pipeline->ad ?? 'Pipeline' }}
                </span>
            @endif
            @if($opportunity->stage)
                <span class="badge badge-brand" style="font-size:12px;padding:5px 10px">
                    {{ $opportunity->stage->adi ?? $opportunity->stage->ad ?? 'Aşama' }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.crm.firsatlar.edit', $opportunity->id) }}" class="btn btn-primary btn-sm">
            <i data-lucide="edit-2"></i>
            <span>Düzenle</span>
        </a>
        <form action="{{ route('admin.crm.firsatlar.destroy', $opportunity->id) }}" method="POST" onsubmit="return confirm('Bu fırsatı silmek istediğine emin misin?');" style="margin:0">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger)">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>
        </form>
    </div>
</div>

{{-- Mini Stat Grid --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ in_array($opportunity->durum, ['kazanildi']) ? 'success' : (in_array($opportunity->durum, ['kaybedildi']) ? 'danger' : 'info') }}">
        <div class="mini-stat-icon">{{ $paraSimgesi }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Tutar</div>
            <div class="mini-stat-value">{{ $paraSimgesi }}{{ number_format($opportunity->tutar ?? 0, 2, ',', '.') }}</div>
            <div class="mini-stat-sub">{{ $opportunity->para_birimi ?? 'TRY' }}</div>
        </div>
    </div>

    <div class="mini-stat {{ $durum['class'] === 'badge-success' ? 'success' : ($durum['class'] === 'badge-danger' ? 'danger' : 'warning') }}">
        <div class="mini-stat-icon">{{ $durum['icon'] }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value">{{ $durum['label'] }}</div>
            <div class="mini-stat-sub">Mevcut konum</div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">📊</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Aşama</div>
            <div class="mini-stat-value" style="font-size:15px">
                {{ $opportunity->stage->adi ?? $opportunity->stage->ad ?? '—' }}
            </div>
            <div class="mini-stat-sub">
                {{ $opportunity->pipeline->adi ?? $opportunity->pipeline->ad ?? 'Pipeline yok' }}
            </div>
        </div>
    </div>

    <div class="mini-stat {{ $kapanisGun !== null && $kapanisGun < 0 ? 'danger' : ($kapanisGun !== null && $kapanisGun <= 7 ? 'warning' : 'info') }}">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Beklenen Kapanış</div>
            <div class="mini-stat-value" style="font-size:15px">
                @if(!empty($opportunity->beklenen_kapanis))
                    {{ \Carbon\Carbon::parse($opportunity->beklenen_kapanis)->format('d.m.Y') }}
                @else
                    Belirtilmedi
                @endif
            </div>
            <div class="mini-stat-sub">
                @if($kapanisGun !== null)
                    @if($kapanisGun < 0)
                        ⚠️ {{ abs($kapanisGun) }} gün geçti
                    @elseif($kapanisGun == 0)
                        Bugün
                    @else
                        {{ $kapanisGun }} gün kaldı
                    @endif
                @else
                    Tarih yok
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Detay kartları --}}
<div class="form-grid" style="margin-top:20px">
    {{-- SOL: Açıklama --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Açıklama</span>
            </div>
            @if(!empty($opportunity->aciklama))
                <div style="white-space:pre-wrap;color:var(--text);line-height:1.7;font-size:14px">{{ $opportunity->aciklama }}</div>
            @else
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="file-text" class="empty-state-icon"></i>
                    <p>Bu fırsat için henüz açıklama eklenmemiş.</p>
                    <a href="{{ route('admin.crm.firsatlar.edit', $opportunity->id) }}" class="btn btn-secondary btn-sm" style="margin-top:8px">
                        <i data-lucide="plus"></i>
                        <span>Açıklama Ekle</span>
                    </a>
                </div>
            @endif
        </div>

        {{-- Hızlı durum değiştirme --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="flag"></i>
                <span>Hızlı Durum Değiştirme</span>
            </div>
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
                Fırsatı tek tıkla sonlandır. Bu durum, fırsatın aşaması değişmez, sadece "durum" alanını günceller.
            </p>

            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @foreach($durumMap as $key => $info)
                    <form action="{{ route('admin.crm.firsatlar.update', $opportunity->id) }}" method="POST" style="margin:0">
                        @csrf @method('PUT')
                        {{-- Mevcut alanları gizli olarak gönder --}}
                        <input type="hidden" name="baslik" value="{{ $opportunity->baslik }}">
                        <input type="hidden" name="musteri_id" value="{{ $opportunity->musteri_id }}">
                        <input type="hidden" name="pipeline_id" value="{{ $opportunity->pipeline_id }}">
                        <input type="hidden" name="stage_id" value="{{ $opportunity->stage_id }}">
                        <input type="hidden" name="tutar" value="{{ $opportunity->tutar }}">
                        <input type="hidden" name="para_birimi" value="{{ $opportunity->para_birimi }}">
                        <input type="hidden" name="sorumlu_id" value="{{ $opportunity->sorumlu_id }}">
                        <input type="hidden" name="oncelik" value="{{ $opportunity->oncelik }}">
                        <input type="hidden" name="beklenen_kapanis" value="{{ $opportunity->beklenen_kapanis }}">
                        <input type="hidden" name="aciklama" value="{{ $opportunity->aciklama }}">
                        <input type="hidden" name="durum" value="{{ $key }}">

                        <button type="submit"
                                class="btn btn-sm {{ $opportunity->durum === $key ? 'btn-primary' : 'btn-secondary' }}"
                                {{ $opportunity->durum === $key ? 'disabled' : '' }}
                                title="{{ $opportunity->durum === $key ? 'Zaten bu durumda' : $info['label'].' olarak işaretle' }}">
                            <span>{{ $info['icon'] }}</span>
                            <span>{{ $info['label'] }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    {{-- SAĞ: Detay bilgileri --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Detaylar</span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Fırsat ID</span>
                    <span class="val">#{{ $opportunity->id }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Müşteri</span>
                    <span class="val">
                        @if($opportunity->musteri)
                            <a href="{{ route('admin.crm.musteriler.show', $opportunity->musteri->id) }}" style="color:var(--brand);text-decoration:none">
                                {{ $opportunity->musteri->adi }}
                            </a>
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Sorumlu</span>
                    <span class="val">{{ $opportunity->sorumlu->adi ?? $opportunity->sorumlu->kullaniciadi ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Pipeline</span>
                    <span class="val">{{ $opportunity->pipeline->adi ?? $opportunity->pipeline->ad ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Aşama</span>
                    <span class="val">{{ $opportunity->stage->adi ?? $opportunity->stage->ad ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Tutar</span>
                    <span class="val" style="color:var(--brand);font-weight:700">
                        {{ $paraSimgesi }}{{ number_format($opportunity->tutar ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Para Birimi</span>
                    <span class="val">{{ $opportunity->para_birimi ?? 'TRY' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Durum</span>
                    <span class="val">
                        <span class="badge {{ $durum['class'] }}">{{ $durum['icon'] }} {{ $durum['label'] }}</span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Öncelik</span>
                    <span class="val">
                        <span class="badge {{ $oncelik['class'] }}">{{ $oncelik['icon'] }} {{ $oncelik['label'] }}</span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Beklenen Kapanış</span>
                    <span class="val">
                        @if(!empty($opportunity->beklenen_kapanis))
                            {{ \Carbon\Carbon::parse($opportunity->beklenen_kapanis)->format('d.m.Y') }}
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Oluşturulma</span>
                    <span class="val">{{ $opportunity->created_at?->format('d.m.Y H:i') ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Son Güncelleme</span>
                    <span class="val">{{ $opportunity->updated_at?->diffForHumans() ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Müşteri Kartı --}}
        @if($opportunity->musteri)
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Müşteri Bilgisi</span>
            </div>

            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);margin-bottom:12px">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--brand),#8a8a1f);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;flex-shrink:0">
                    {{ strtoupper(mb_substr($opportunity->musteri->adi ?? 'M', 0, 1, 'UTF-8')) }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:14px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        {{ $opportunity->musteri->adi }}
                    </div>
                    @if(!empty($opportunity->musteri->unvan))
                        <div style="font-size:11.5px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $opportunity->musteri->unvan }}
                        </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.crm.musteriler.show', $opportunity->musteri->id) }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
                <i data-lucide="external-link"></i>
                <span>Müşteri Profilini Aç</span>
            </a>
        </div>
        @endif
    </div>
</div>

@endsection