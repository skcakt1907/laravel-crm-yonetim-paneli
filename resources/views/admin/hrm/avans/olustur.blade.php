@extends('admin._layout')

@section('title', 'Avans Talebi Oluştur')

@push('head')
<style>
    .io-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:16px}
    .io-back:hover{color:#8a8820}
    .io-head{display:flex;align-items:center;gap:10px;margin-bottom:20px}
    .io-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .io-bakiye{display:flex;gap:10px;margin-bottom:20px;max-width:560px}
    .io-bk{flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;text-align:center}
    .io-bk-v{font-size:22px;font-weight:800}
    .io-bk-l{font-size:11.5px;color:#94a3b8;font-weight:600;margin-top:3px}
    .io-bk.ony .io-bk-v{color:#10b981}
    .io-bk.bek .io-bk-v{color:#f59e0b}
    .io-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:26px;max-width:560px}
    .io-grup{margin-bottom:18px;display:flex;flex-direction:column;gap:7px}
    .io-label{font-weight:700;font-size:13.5px;color:#334155}
    .io-label .req{color:#ef4444}
    .io-input,.io-textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:11px;padding:11px 14px;font-size:14px;font-family:inherit;color:#1a1d24;box-sizing:border-box}
    .io-input:focus,.io-textarea:focus{outline:none;border-color:#b8b62e;box-shadow:0 0 0 3px rgba(184,182,46,.12)}
    .io-textarea{resize:vertical;min-height:80px}
    .io-submit{display:inline-flex;align-items:center;gap:8px;background:#b8b62e;color:#1a1d24;font-weight:700;padding:13px 28px;border-radius:12px;border:none;cursor:pointer;font-size:15px;margin-top:6px}
    .io-submit:hover{background:#a4a229}
    .io-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px}
</style>
@endpush

@section('content')
<a href="{{ route('admin.hrm.avans.index') }}" class="io-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Avans taleplerine dön</a>
<div class="io-head"><h1><i data-lucide="banknote"></i> Avans Talebi Oluştur</h1></div>

@if($errors->any())<div class="io-err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('error'))<div class="io-err">{{ session('error') }}</div>@endif

<div class="io-bakiye">
    <div class="io-bk ony"><div class="io-bk-v">₺{{ number_format($ozet['onaylanan'], 2, ',', '.') }}</div><div class="io-bk-l">BU YIL ONAYLANAN</div></div>
    <div class="io-bk bek"><div class="io-bk-v">₺{{ number_format($ozet['bekleyen'], 2, ',', '.') }}</div><div class="io-bk-l">BEKLEYEN TALEP</div></div>
</div>

<form action="{{ route('admin.hrm.avans.store') }}" method="POST" class="io-card">
    @csrf
    <div class="io-grup">
        <label class="io-label">Avans Tutarı (₺) <span class="req">*</span></label>
        <input type="number" name="tutar" class="io-input" step="0.01" min="1" value="{{ old('tutar') }}" placeholder="örn: 5000" required>
    </div>

    <div class="io-grup">
        <label class="io-label">Açıklama</label>
        <textarea name="aciklama" class="io-textarea" placeholder="Avans sebebi (isteğe bağlı)...">{{ old('aciklama') }}</textarea>
    </div>

    <button type="submit" class="io-submit"><i data-lucide="send" style="width:18px;height:18px"></i> Talebi Gönder</button>
</form>
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush