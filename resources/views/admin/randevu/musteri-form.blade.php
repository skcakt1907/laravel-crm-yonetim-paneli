@extends('admin._layout')

@section('title', $baslik)

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.randevu.musteriler') }}">Müşteriler</a>
    <span class="sep">/</span>
    <span class="current">{{ $baslik }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $musteri ? '✏️ Müşteri Düzenleme' : '➕ Yeni Müşteri' }}</h1>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <i data-lucide="alert-circle"></i>
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="section" style="max-width:680px">
    <form method="POST" action="{{ route('admin.randevu.musteri-kaydet') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $musteri->id ?? '' }}">

        <div class="form-group">
            <label class="form-label">Müşteri Adı *</label>
            <input type="text" name="ad" class="form-input" required
                   value="{{ old('ad', $musteri->ad ?? '') }}" placeholder="Ad Soyad">
        </div>

        <div class="form-group">
            <label class="form-label">Telefon</label>
            <input type="text" name="telefon" class="form-input"
                   value="{{ old('telefon', $musteri->telefon ?? '') }}" placeholder="05xx...">
        </div>

        <div class="form-group">
            <label class="form-label">Not</label>
            <textarea name="not" class="form-input" rows="3" placeholder="Müşteri hakkında not">{{ old('not', $musteri->not ?? '') }}</textarea>
        </div>

        <div style="display:flex;gap:10px;margin-top:8px">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <span>Kaydet</span>
            </button>
            <a href="{{ route('admin.randevu.musteriler') }}" class="btn btn-ghost">
                <i data-lucide="arrow-left"></i> <span>Müşterilere Dön</span>
            </a>
        </div>
    </form>
</div>

@endsection
