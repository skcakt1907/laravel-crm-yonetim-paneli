@extends('admin._layout')

@section('title', 'Yeni Ticket')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.tickets.index') }}">Ticketlar</a>
    <span class="sep">/</span>
    <span class="current">Yeni</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Ticket</h1>
        <div class="page-subtitle">İç ekipte birine görev/sorun ticketı ata</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary btn-sm">
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

<form action="{{ route('admin.tickets.olustur.post') }}" method="POST">
    @csrf

    <div class="form-grid">
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="ticket"></i>
                    <span>Ticket Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Başlık <span class="required">*</span></label>
                    <input type="text" name="baslik" value="{{ old('baslik') }}" required maxlength="255" class="form-input" placeholder="Kısa konu başlığı">
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Mesaj / Detay <span class="required">*</span></label>
                    <textarea name="mesaj" rows="8" required class="form-textarea" placeholder="Ticket'ın detayları, ne yapılması gerekiyor...">{{ old('mesaj') }}</textarea>
                </div>
            </div>
        </div>

        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="user-check"></i>
                    <span>Atama</span>
                </div>

                {{-- Çoklu atama (01.09.2026): bir ticket birden fazla kişiye
                     atanabilir. Alan adı atanan_ids[]; sunucu tekil atanan_id'yi
                     de hâlâ kabul ediyor, eski formlar bozulmasın diye. --}}
                <div class="form-group">
                    <label class="form-label">Kimlere Atanacak?</label>
                    <div class="atanan-liste">
                        @foreach($calisanlar ?? [] as $c)
                            @php
                                $rolEt  = [1 => 'Patron', 2 => 'Çalışan', 3 => 'Bayi'][$c->rol] ?? '';
                                $secili = in_array((string) $c->id, array_map('strval', (array) old('atanan_ids', [])), true);
                            @endphp
                            <label class="atanan-secim">
                                <input type="checkbox" name="atanan_ids[]" value="{{ $c->id }}" @checked($secili)>
                                <span>{{ $c->adi ?: $c->kullaniciadi }}</span>
                                @if($rolEt)<em>{{ $rolEt }}</em>@endif
                            </label>
                        @endforeach
                    </div>
                    @error('atanan_ids.*')
                        <small class="form-help" style="color:#dc2626">{{ $message }}</small>
                    @enderror
                    <small class="form-help">
                        Birden fazla kişi seçebilirsin. Hiç seçmezsen ileride atayabilirsin.
                    </small>
                </div>

                <style>
                    .atanan-liste{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:6px}
                    .atanan-secim{display:inline-flex;align-items:center;gap:7px;cursor:pointer;
                        border:1.5px solid var(--border,#e2e5ea);border-radius:8px;
                        padding:7px 12px;font-size:13px;transition:.15s;user-select:none}
                    .atanan-secim:hover{border-color:var(--brand,#8a8a1f)}
                    .atanan-secim input{margin:0;cursor:pointer}
                    .atanan-secim em{font-style:normal;font-size:11px;color:var(--text-muted,#9aa0a6)}
                    .atanan-secim:has(input:checked){border-color:var(--brand,#8a8a1f);
                        background:rgba(138,138,31,.08);font-weight:600}
                </style>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Öncelik <span class="required">*</span></label>
                    <select name="oncelik" required class="form-select">
                        <option value="dusuk" {{ old('oncelik') == 'dusuk' ? 'selected' : '' }}>🟢 Düşük</option>
                        <option value="normal" {{ old('oncelik', 'normal') == 'normal' ? 'selected' : '' }}>⚪ Normal</option>
                        <option value="yuksek" {{ old('oncelik') == 'yuksek' ? 'selected' : '' }}>🟡 Yüksek</option>
                        <option value="acil" {{ old('oncelik') == 'acil' ? 'selected' : '' }}>🔴 Acil</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Ticket Oluştur</span>
        </button>
    </div>
</form>

@endsection