@extends('admin._layout')

@section('title', 'Kupon Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.kuponlar.index') }}">Kuponlar</a>
    <span class="sep">/</span>
    <span class="current">{{ $kupon->kod ?? 'Düzenle' }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ {{ $kupon->kod ?? '—' }}</h1>
        <div class="page-subtitle">Kupon bilgilerini güncelle</div>
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

<form action="{{ route('admin.kuponlar.duzenlePost', $kupon->id) }}" method="POST">
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
                    <input type="text" name="kod" value="{{ old('kod', $kupon->kod) }}" required maxlength="50" class="form-input" style="font-family:monospace;text-transform:uppercase;font-weight:700">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">İndirim Miktarı <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="indirim" value="{{ old('indirim', $kupon->indirim) }}" required class="form-input" id="indirim-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">İndirim Türü <span class="required">*</span></label>
                        @php $currentTip = old('tip', $kupon->tip ?? 'yuzde'); @endphp
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid {{ $currentTip == 'yuzde' ? 'var(--brand)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentTip == 'yuzde' ? 'var(--brand-soft)' : 'transparent' }};justify-content:center">
                                <input type="radio" name="tip" value="yuzde" {{ $currentTip == 'yuzde' ? 'checked' : '' }} style="width:auto;margin:0" onchange="updateTipUI()">
                                <span style="font-weight:600">📊 Yüzde</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid {{ $currentTip == 'tutar' ? 'var(--brand)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentTip == 'tutar' ? 'var(--brand-soft)' : 'transparent' }};justify-content:center">
                                <input type="radio" name="tip" value="tutar" {{ $currentTip == 'tutar' ? 'checked' : '' }} style="width:auto;margin:0" onchange="updateTipUI()">
                                <span style="font-weight:600">💰 Tutar (₺)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Tarihler + önizleme --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="calendar"></i>
                    <span>Geçerlilik Süresi</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" name="baslangic_tarih" value="{{ old('baslangic_tarih', isset($kupon->baslangic_tarih) && $kupon->baslangic_tarih ? \Carbon\Carbon::parse($kupon->baslangic_tarih)->format('Y-m-d') : '') }}" class="form-input">
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" name="bitis_tarih" value="{{ old('bitis_tarih', isset($kupon->bitis_tarih) && $kupon->bitis_tarih ? \Carbon\Carbon::parse($kupon->bitis_tarih)->format('Y-m-d') : '') }}" class="form-input">
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="eye"></i>
                    <span>Önizleme</span>
                </div>
                <div style="text-align:center;padding:16px;background:var(--brand-soft);border:2px dashed var(--brand);border-radius:var(--radius-lg)">
                    <div style="font-size:11px;color:var(--brand-dark);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">İndirim Kuponu</div>
                    <div id="preview-kod" style="font-family:monospace;font-size:20px;font-weight:700;color:var(--text);margin-bottom:8px">{{ $kupon->kod }}</div>
                    <div id="preview-indirim" style="font-size:24px;font-weight:800;color:var(--brand-dark)">
                        @if(($kupon->tip ?? 'yuzde') === 'yuzde')% {{ rtrim(rtrim(number_format((float)$kupon->indirim, 2, '.', ''), '0'), '.') }}
                        @else₺{{ number_format((float)$kupon->indirim, 2, ',', '.') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <button type="button" onclick="document.getElementById('sil-form').submit()" class="btn btn-danger">
            <i data-lucide="trash-2"></i>
            <span>Kuponu Sil</span>
        </button>

        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.kuponlar.index') }}" class="btn btn-secondary">İptal</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Güncelle</span>
            </button>
        </div>
    </div>
</form>

{{-- Sil formu - ANA FORMUN DIŞINDA (HTML iç içe form'a izin vermez) --}}
<form id="sil-form" action="{{ route('admin.kuponlar.sil', $kupon->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($kupon->kod) }} kuponu silinsin mi?');" style="display:none">
    @csrf @method('DELETE')
</form>

<script>
function updateTipUI() {
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
</script>

@endsection