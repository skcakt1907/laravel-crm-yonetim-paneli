@extends('admin._layout')

@section('title', 'Yeni Dil')

@push('head')
<style>
    .ekle-wrap {
        max-width: 720px;
        margin: 0 auto;
    }

    .preview-card {
        background: linear-gradient(135deg, var(--brand-soft), transparent);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-lg);
        padding: 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }
    .preview-flag {
        font-size: 56px;
        width: 80px; height: 80px;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface);
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
    }
    .preview-info { flex: 1; min-width: 0; }
    .preview-name {
        font-size: 18px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 4px;
        word-break: break-word;
    }
    .preview-code {
        display: inline-block;
        font-family: monospace;
        background: var(--brand);
        color: #000;
        padding: 3px 10px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
    }

    .emoji-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .emoji-suggestion {
        font-size: 22px;
        padding: 4px 8px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s;
        line-height: 1;
    }
    .emoji-suggestion:hover {
        background: var(--brand-soft);
        border-color: var(--brand);
        transform: scale(1.1);
    }

    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        z-index: 10;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.diller.index') }}">Diller</a>
    <span class="sep">/</span>
    <span class="current">Yeni Dil</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="languages"></i>
            Yeni Dil Ekle
        </h1>
        <div class="page-subtitle">Site içeriğine yeni bir dil desteği ekleyin</div>
    </div>
</div>

<div class="ekle-wrap">
    <form action="{{ route('admin.diller.ekle.post') }}" method="POST">
        @csrf

        {{-- CANLI ÖNİZLEME --}}
        <div class="preview-card">
            <div class="preview-flag" id="prevFlag">🌐</div>
            <div class="preview-info">
                <div class="preview-name" id="prevName">Yeni Dil</div>
                <span class="preview-code" id="prevCode">XX</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Dil Bilgileri</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Dil Adı <span class="required">*</span></label>
                    <input type="text" name="ad" id="ad" value="{{ old('ad') }}"
                           required class="form-input"
                           placeholder="Örn: Türkçe, English, العربية"
                           oninput="updatePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label">Kod <span class="required">*</span></label>
                    <input type="text" name="kod" id="kod" value="{{ old('kod') }}"
                           required maxlength="5"
                           class="form-input"
                           placeholder="tr, en, ar..."
                           style="text-transform:lowercase;font-family:monospace"
                           oninput="this.value=this.value.toLowerCase();updatePreview()">
                    <small class="form-help">ISO 639-1 kodu (2 harf)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Bayrak Emoji</label>
                    <input type="text" name="bayrak" id="bayrak" value="{{ old('bayrak') }}"
                           maxlength="10"
                           class="form-input"
                           placeholder="🇹🇷"
                           style="font-size:18px"
                           oninput="updatePreview()">
                    <div class="emoji-suggestions">
                        <span class="emoji-suggestion" onclick="setEmoji('🇹🇷', 'tr', 'Türkçe')">🇹🇷</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇬🇧', 'en', 'English')">🇬🇧</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇸🇦', 'ar', 'العربية')">🇸🇦</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇩🇪', 'de', 'Deutsch')">🇩🇪</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇫🇷', 'fr', 'Français')">🇫🇷</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇪🇸', 'es', 'Español')">🇪🇸</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇮🇹', 'it', 'Italiano')">🇮🇹</span>
                        <span class="emoji-suggestion" onclick="setEmoji('🇷🇺', 'ru', 'Русский')">🇷🇺</span>
                    </div>
                    <small class="form-help">Boş bırakırsanız koddan otomatik üretilir</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Sıralama</label>
                    <input type="number" min="0" name="sira"
                           value="{{ old('sira', 0) }}"
                           class="form-input"
                           placeholder="0">
                    <small class="form-help">Düşük sayı önce gösterilir</small>
                </div>
            </div>
        </div>

        <div class="sticky-save">
            <div style="font-size:13px;color:var(--text-muted)">
                <span class="required">*</span> zorunlu alan
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('admin.diller.index') }}" class="btn btn-secondary">
                    <i data-lucide="x"></i>
                    <span>İptal</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Dili Kaydet</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const bayrakMap = {
    'tr': '🇹🇷', 'en': '🇬🇧', 'ar': '🇸🇦',
    'de': '🇩🇪', 'fr': '🇫🇷', 'es': '🇪🇸',
    'it': '🇮🇹', 'ru': '🇷🇺', 'nl': '🇳🇱',
    'jp': '🇯🇵', 'cn': '🇨🇳', 'zh': '🇨🇳',
    'pt': '🇵🇹', 'pl': '🇵🇱', 'gr': '🇬🇷',
};

function updatePreview() {
    const ad = document.getElementById('ad').value || 'Yeni Dil';
    const kod = (document.getElementById('kod').value || 'XX').toUpperCase();
    let bayrak = document.getElementById('bayrak').value;

    if (!bayrak) {
        bayrak = bayrakMap[kod.toLowerCase()] || '🌐';
    }

    document.getElementById('prevName').textContent = ad;
    document.getElementById('prevCode').textContent = kod;
    document.getElementById('prevFlag').textContent = bayrak;
}

function setEmoji(emoji, kod, ad) {
    document.getElementById('bayrak').value = emoji;
    if (!document.getElementById('kod').value) {
        document.getElementById('kod').value = kod;
    }
    if (!document.getElementById('ad').value) {
        document.getElementById('ad').value = ad;
    }
    updatePreview();
}

// Sayfa yüklendiğinde de güncelle
updatePreview();
</script>

@endsection