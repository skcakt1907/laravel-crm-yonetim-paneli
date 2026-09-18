@extends('admin._layout')

@section('title', 'Bayi Ayarları')

@push('head')
<style>
    .toggle-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; gap: 16px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
    }
    .toggle-card .desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .toggle-card .lbl-strong { font-weight: 600; font-size: 14px; }

    .ios-toggle { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
    .ios-toggle input { opacity: 0; width: 0; height: 0; }
    .ios-toggle .knob {
        position: absolute; cursor: pointer; inset: 0;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 26px;
        transition: 0.25s;
    }
    .ios-toggle .knob:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background: #fff;
        border-radius: 50%; transition: 0.25s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .ios-toggle input:checked + .knob {
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        border-color: var(--brand);
    }
    .ios-toggle input:checked + .knob:before { transform: translateX(22px); }

    .bayi-list-row {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .bayi-list-row:last-child { border-bottom: none; }
    .bayi-list-row:hover { background: var(--bg-subtle); }

    .danger-zone {
        border: 1px solid rgba(239,68,68,0.3);
        background: rgba(239,68,68,0.04);
        border-radius: var(--radius-lg);
        padding: 16px;
        margin-top: 16px;
    }
    .danger-zone .section-title { color: var(--danger); border-bottom-color: rgba(239,68,68,0.2); }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.bayiler.index') }}">Bayiler</a>
    <span class="sep">/</span>
    <span class="current">Ayarlar</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="settings"></i>
            Bayi Sistemi Ayarları
        </h1>
        <div class="page-subtitle">Bayilik sisteminin genel kuralları ve varsayılan değerleri</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.bayiler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Bayilere Dön</span>
        </a>
    </div>
</div>

{{-- TAB NAV --}}
<div class="section" style="margin-bottom:20px">
    <div class="tab-nav">
        <a href="javascript:void(0)" class="tab-nav-link active" data-tab="genel" onclick="setAyarTab('genel')">
            <i data-lucide="sliders" style="width:16px;height:16px"></i>
            Genel Ayarlar
        </a>
        <a href="javascript:void(0)" class="tab-nav-link" data-tab="toplu" onclick="setAyarTab('toplu')">
            <i data-lucide="users" style="width:16px;height:16px"></i>
            Toplu Güncelleme
        </a>
        <a href="javascript:void(0)" class="tab-nav-link" data-tab="liste" onclick="setAyarTab('liste')">
            <i data-lucide="list" style="width:16px;height:16px"></i>
            Tüm Bayiler ({{ count($bayiler ?? []) }})
        </a>
    </div>
</div>

{{-- TAB: GENEL AYARLAR --}}
<div id="tab-genel" class="tab-content">
    <form action="{{ route('admin.bayiler.ayarlar.guncelle') }}" method="POST">
        @csrf

        <div class="form-grid">
            <div>
                {{-- VARSAYILAN ORANLAR --}}
                <div class="section">
                    <div class="section-title">
                        <i data-lucide="percent"></i>
                        <span>Varsayılan Oranlar</span>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Varsayılan Komisyon (%) <span class="required">*</span></label>
                            <input type="number" step="0.01" name="varsayilan_komisyon_orani"
                                   value="{{ old('varsayilan_komisyon_orani', $ayarlar->varsayilan_komisyon_orani ?? 10) }}"
                                   min="0" max="100" required class="form-input">
                            <small class="form-help">Yeni eklenen bayiler için varsayılan</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Varsayılan Müşteri İndirimi (%) <span class="required">*</span></label>
                            <input type="number" step="0.01" name="varsayilan_musteri_indirim_orani"
                                   value="{{ old('varsayilan_musteri_indirim_orani', $ayarlar->varsayilan_musteri_indirim_orani ?? 5) }}"
                                   min="0" max="100" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Min. Sepet Tutarı (₺) <span class="required">*</span></label>
                            <input type="number" step="0.01" name="min_sepet_tutari"
                                   value="{{ old('min_sepet_tutari', $ayarlar->min_sepet_tutari ?? 100) }}"
                                   min="0" required class="form-input">
                            <small class="form-help">Bu tutarın altındaki sepetlerde bayi komisyon kazanmaz</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Maks. Müşteri İndirimi (₺) <span class="required">*</span></label>
                            <input type="number" step="0.01" name="max_musteri_indirim"
                                   value="{{ old('max_musteri_indirim', $ayarlar->max_musteri_indirim ?? 500) }}"
                                   min="0" required class="form-input">
                            <small class="form-help">Bayinin verebileceği maks. indirim tutarı</small>
                        </div>
                    </div>
                </div>

                {{-- KULLANIM ŞARTLARI --}}
                <div class="section" style="margin-top:16px">
                    <div class="section-title">
                        <i data-lucide="file-text"></i>
                        <span>Kullanım Şartları</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bayi Kullanım Şartları</label>
                        <textarea name="kullanim_sartlari" rows="8" class="form-textarea"
                                  placeholder="Bayilerin kabul edeceği kullanım şartları metnini buraya yazın...">{{ old('kullanim_sartlari', $ayarlar->kullanim_sartlari ?? '') }}</textarea>
                        <small class="form-help">Bu metin bayi başvuru formunda gösterilir (maks. 5000 karakter)</small>
                    </div>
                </div>
            </div>

            {{-- SAĞ KOLON --}}
            <div>
                <div class="section">
                    <div class="section-title">
                        <i data-lucide="shield-check"></i>
                        <span>Sistem Kontrolü</span>
                    </div>

                    <div class="toggle-card" style="margin-bottom:12px">
                        <div>
                            <div class="lbl-strong">Bayi Sistemi Aktif</div>
                            <div class="desc">Bayilik özelliği kullanılabilir</div>
                        </div>
                        <label class="ios-toggle">
                            <input type="checkbox" name="bayi_sistemi_aktif" value="1"
                                   {{ old('bayi_sistemi_aktif', $ayarlar->bayi_sistemi_aktif ?? 1) ? 'checked' : '' }}>
                            <span class="knob"></span>
                        </label>
                    </div>

                    <div class="toggle-card">
                        <div>
                            <div class="lbl-strong">Otomatik Onay</div>
                            <div class="desc">Yeni bayiler otomatik onaylansın</div>
                        </div>
                        <label class="ios-toggle">
                            <input type="checkbox" name="yeni_bayi_otomatik_onay" value="1"
                                   {{ old('yeni_bayi_otomatik_onay', $ayarlar->yeni_bayi_otomatik_onay ?? 0) ? 'checked' : '' }}>
                            <span class="knob"></span>
                        </label>
                    </div>
                </div>

                <div class="section" style="margin-top:14px">
                    <div class="section-title">
                        <i data-lucide="info"></i>
                        <span>Bilgi</span>
                    </div>
                    <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.7">
                        <p style="margin:0 0 8px">
                            <strong>Otomatik Onay kapalıyken:</strong> Yeni bayi başvuruları "Beklemede" statüsünde kalır ve manuel onay gerekir.
                        </p>
                        <p style="margin:0">
                            <strong>Sistem pasifken:</strong> Bayilik fonksiyonları çalışmaz, mevcut bayiler giriş yapamaz.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:24px">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Ayarları Kaydet</span>
            </button>
        </div>
    </form>
</div>

{{-- TAB: TOPLU GÜNCELLEME --}}
<div id="tab-toplu" class="tab-content" style="display:none">
    <div class="alert alert-warning" style="margin-bottom:16px">
        <i data-lucide="alert-triangle" style="width:18px;height:18px"></i>
        <div>
            <strong>Dikkat:</strong> Bu işlem
            <strong style="color:var(--text)">{{ count($bayiler ?? []) }} bayinin</strong>
            oranlarını aynı değere ayarlar. Geri alınamaz.
        </div>
    </div>

    <form action="{{ route('admin.bayiler.toplu.guncelle') }}" method="POST"
          onsubmit="return confirm('Tüm bayilerin oranlarını güncellemek istediğinize emin misiniz?');">
        @csrf

        <div class="section">
            <div class="section-title">
                <i data-lucide="users"></i>
                <span>Tüm Bayilere Uygula</span>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Yeni Komisyon Oranı (%)</label>
                    <input type="number" step="0.01" name="komisyon_orani"
                           min="0" max="100" class="form-input"
                           placeholder="Boş bırakılırsa değişmez">
                    <small class="form-help">Tüm bayilerin komisyon oranını bu değere ayarlar</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Yeni Müşteri İndirim (%)</label>
                    <input type="number" step="0.01" name="musteri_indirim_orani"
                           min="0" max="100" class="form-input"
                           placeholder="Boş bırakılırsa değişmez">
                    <small class="form-help">Tüm bayilerin müşteri indirim oranını bu değere ayarlar</small>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-danger">
                    <i data-lucide="users"></i>
                    <span>Tüm Bayilere Uygula</span>
                </button>
            </div>
        </div>
    </form>
</div>

{{-- TAB: TÜM BAYİLER LİSTESİ (özet) --}}
<div id="tab-liste" class="tab-content" style="display:none">
    <div class="section" style="padding:0;overflow:hidden">
        @if(!empty($bayiler) && count($bayiler) > 0)
            @foreach($bayiler as $b)
                @php
                    $ad = trim(($b->ad ?? '') . ' ' . ($b->soyad ?? '')) ?: '—';
                    $onay = (int)($b->onay_durumu ?? 0);
                @endphp
                <div class="bayi-list-row">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600">{{ $ad }}</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                            {{ $b->email ?? '—' }}
                            @if(!empty($b->bayi_kodu))
                                · <span style="font-family:monospace;color:var(--brand-dark)">{{ $b->bayi_kodu }}</span>
                            @endif
                        </div>
                    </div>
                    <div style="text-align:right;font-size:12px;color:var(--text-muted)">
                        Komisyon: <strong style="color:var(--brand-dark)">%{{ number_format($b->komisyon_orani ?? 0, 2) }}</strong>
                    </div>
                    <div>
                        @if($onay === 1)
                            <span class="badge badge-success">Onaylı</span>
                        @elseif($onay === 2)
                            <span class="badge badge-danger">Red</span>
                        @else
                            <span class="badge badge-warning">Beklemede</span>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('admin.bayiler.detay', $b->id) }}"
                           class="table-action" title="Detay">
                            <i data-lucide="eye"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i data-lucide="users" class="empty-state-icon"></i>
                <h4>Henüz bayi yok</h4>
                <p>Sistemde kayıtlı bayi bulunmuyor.</p>
            </div>
        @endif
    </div>
</div>

<script>
function setAyarTab(tab) {
    document.querySelectorAll('.tab-nav-link[data-tab]').forEach(el => {
        if (el.dataset.tab === tab) el.classList.add('active');
        else el.classList.remove('active');
    });

    ['genel', 'toplu', 'liste'].forEach(t => {
        document.getElementById('tab-' + t).style.display = (t === tab) ? '' : 'none';
    });
}
</script>

@endsection