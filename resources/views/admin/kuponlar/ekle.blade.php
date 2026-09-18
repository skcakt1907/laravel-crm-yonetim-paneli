@extends('admin._layout')

@section('title', 'Yeni Kupon')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.kuponlar.index') }}">Kuponlar</a>
    <span class="sep">/</span>
    <span class="current">Yeni Kupon</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Kupon</h1>
        <div class="page-subtitle">İndirim kuponu oluştur — yüzde veya sabit tutar</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.kuponlar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">
        <strong>Form Hataları:</strong>
        <ul style="margin:6px 0 0 18px;font-size:13px">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.kuponlar.eklePost') }}" method="POST">
    @csrf

    <div class="form-grid">
        {{-- SOL: Kupon bilgileri --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="ticket"></i>
                    <span>Kupon Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Kupon Kodu <span class="required">*</span></label>
                    <input type="text" name="kod" value="{{ old('kod') }}" required maxlength="50" class="form-input" placeholder="Örn: YILBASI2026" style="font-family:monospace;text-transform:uppercase;font-weight:700">
                    <small class="form-help">Müşterinin kullanacağı kod — otomatik büyük harfe çevrilir</small>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">İndirim Miktarı <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="indirim" value="{{ old('indirim') }}" required class="form-input" placeholder="0.00" id="indirim-input">
                        <small class="form-help" id="indirim-help">% veya ₺ — sağdaki türe göre</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">İndirim Türü <span class="required">*</span></label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            @php $currentTip = old('tip', 'yuzde'); @endphp
                            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid {{ $currentTip == 'yuzde' ? 'var(--brand)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentTip == 'yuzde' ? 'var(--brand-soft)' : 'transparent' }};justify-content:center">
                                <input type="radio" name="tip" value="yuzde" {{ $currentTip == 'yuzde' ? 'checked' : '' }} style="width:auto;margin:0" onchange="updateTipUI('yuzde')">
                                <span style="font-weight:600">📊 Yüzde</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid {{ $currentTip == 'tutar' ? 'var(--brand)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentTip == 'tutar' ? 'var(--brand-soft)' : 'transparent' }};justify-content:center">
                                <input type="radio" name="tip" value="tutar" {{ $currentTip == 'tutar' ? 'checked' : '' }} style="width:auto;margin:0" onchange="updateTipUI('tutar')">
                                <span style="font-weight:600">💰 Tutar (₺)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Tarih ve durum --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="calendar"></i>
                    <span>Geçerlilik Süresi</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" name="baslangic_tarih" value="{{ old('baslangic_tarih') }}" class="form-input">
                    <small class="form-help">Boş bırakırsan hemen geçerli olur</small>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" name="bitis_tarih" value="{{ old('bitis_tarih') }}" class="form-input">
                    <small class="form-help">Boş bırakırsan süresiz geçerli olur</small>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Önizleme</span>
                </div>
                <div style="text-align:center;padding:16px;background:var(--brand-soft);border:2px dashed var(--brand);border-radius:var(--radius-lg)">
                    <div style="font-size:11px;color:var(--brand-dark);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">İndirim Kuponu</div>
                    <div id="preview-kod" style="font-family:monospace;font-size:20px;font-weight:700;color:var(--text);margin-bottom:8px">KOD</div>
                    <div id="preview-indirim" style="font-size:24px;font-weight:800;color:var(--brand-dark)">% 0</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.kuponlar.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Kupon Oluştur</span>
        </button>
    </div>
</form>

<script>
function updateTipUI(tip) {
    document.querySelectorAll('input[name="tip"]').forEach(r => {
        const label = r.closest('label');
        if (r.checked) {
            label.style.borderColor = 'var(--brand)';
            label.style.background = 'var(--brand-soft)';
        } else {
            label.style.borderColor = 'var(--border)';
            label.style.background = 'transparent';
        }
    });
    updatePreview();
}

function updatePreview() {
    const kod = document.querySelector('input[name="kod"]').value.toUpperCase() || 'KOD';
    const indirim = parseFloat(document.getElementById('indirim-input').value) || 0;
    const tip = document.querySelector('input[name="tip"]:checked')?.value || 'yuzde';

    document.getElementById('preview-kod').textContent = kod;
    if (tip === 'yuzde') {
        document.getElementById('preview-indirim').textContent = '% ' + indirim.toFixed(0).replace(/\.0+$/, '');
    } else {
        document.getElementById('preview-indirim').textContent = '₺' + indirim.toFixed(2);
    }
}

document.querySelector('input[name="kod"]').addEventListener('input', updatePreview);
document.getElementById('indirim-input').addEventListener('input', updatePreview);
updatePreview();
</script>

@endsection