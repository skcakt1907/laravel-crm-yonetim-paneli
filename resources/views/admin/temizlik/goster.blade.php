@extends('admin._layout')

@section('title', 'Temizlik Kontrolü')

@section('content')

@php
    $toplam  = $detaylar->count();
    $yapilan = $detaylar->where('yapildi', 1)->count();
    $yuzde   = $toplam > 0 ? round($yapilan / $toplam * 100) : 0;
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.temizlik.index') }}">Temizlik Kontrol</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Carbon::parse($kontrol->kontrol_tarihi)->format('d.m.Y') }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="spray-can"></i> {{ $kontrol->baslik ?: 'Temizlik Kontrolü' }}</h1>
        <div class="page-subtitle">
            Kontrol tarihi: {{ \Illuminate\Support\Carbon::parse($kontrol->kontrol_tarihi)->format('d.m.Y') }}
            · Oluşturan: {{ $kontrol->olusturan_adi ?: '—' }}
            · Oluşturma: {{ \Illuminate\Support\Carbon::parse($kontrol->created_at)->format('d.m.Y H:i') }}
        </div>
    </div>
    <a href="{{ route('admin.temizlik.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> <span>Listeye Dön</span>
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

{{-- İlerleme --}}
<div class="section" style="margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <div style="height:11px;background:var(--bg-subtle);border-radius:6px;overflow:hidden">
                <div id="ilerlemeCubuk" style="height:100%;width:{{ $yuzde }}%;
                     background:{{ $yapilan === $toplam && $toplam > 0 ? 'var(--success)' : 'var(--brand)' }};
                     transition:width .3s"></div>
            </div>
        </div>
        <div style="font-size:15px;font-weight:800">
            <span id="yapilanSayi">{{ $yapilan }}</span> / {{ $toplam }}
        </div>
        <div id="tamamDurum" style="font-size:13px;font-weight:700;color:var(--success);
             display:{{ $yapilan === $toplam && $toplam > 0 ? 'block' : 'none' }}">
            ✓ Tamamlandı
        </div>
    </div>
</div>

{{-- Kontrol listesi --}}
<div class="table-wrap" style="margin-bottom:16px">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:56px" class="text-center">Yapıldı</th>
                    <th>Madde</th>
                    <th style="width:180px">Yapılma Tarihi</th>
                    <th style="width:150px">Yapan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detaylar as $d)
                <tr id="satir-{{ $d->id }}" class="{{ $d->yapildi ? 'yapildi-satir' : '' }}">
                    <td class="text-center">
                        <input type="checkbox" class="tk-kutu"
                               data-id="{{ $d->id }}"
                               {{ $d->yapildi ? 'checked' : '' }}
                               style="width:20px;height:20px;cursor:pointer;accent-color:var(--brand)">
                    </td>
                    <td>
                        <strong class="tk-baslik">{{ $d->madde_baslik }}</strong>
                    </td>
                    <td class="tk-tarih" style="font-size:12.5px;color:var(--text-secondary)">
                        {{ $d->yapilma_tarihi ? \Illuminate\Support\Carbon::parse($d->yapilma_tarihi)->format('d.m.Y H:i') : '—' }}
                    </td>
                    <td class="tk-yapan" style="font-size:12.5px;color:var(--text-secondary)">
                        {{ $d->yapan_adi ?: '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Not --}}
<div class="section">
    <form method="POST" action="{{ route('admin.temizlik.not', $kontrol->id) }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Kontrol Notu</label>
            <textarea name="not" rows="3" class="form-textarea"
                      placeholder="Varsa eksikler, dikkat edilecekler...">{{ $kontrol->not }}</textarea>
        </div>
        <div style="display:flex;justify-content:flex-end">
            <button type="submit" class="btn btn-secondary"><i data-lucide="save"></i> <span>Notu Kaydet</span></button>
        </div>
    </form>
</div>

<style>
    .yapildi-satir .tk-baslik { text-decoration: line-through; color: var(--text-muted); }
    .yapildi-satir { background: rgba(16,185,129,.05); }
</style>

<script>
(function () {
    const TOPLAM = {{ $toplam }};
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const URL_KALIP = "{{ url('admin/temizlik/'.$kontrol->id.'/isaretle') }}/__ID__";

    document.querySelectorAll('.tk-kutu').forEach(function (kutu) {
        kutu.addEventListener('change', function () {
            const id = this.dataset.id;
            const satir = document.getElementById('satir-' + id);
            this.disabled = true;

            fetch(URL_KALIP.replace('__ID__', id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success) { this.checked = !this.checked; return; }

                satir.classList.toggle('yapildi-satir', d.yapildi);
                satir.querySelector('.tk-tarih').textContent = d.tarih || '—';
                satir.querySelector('.tk-yapan').textContent = d.yapan || '—';

                const yapilan = TOPLAM - d.kalan;
                document.getElementById('yapilanSayi').textContent = yapilan;
                const cubuk = document.getElementById('ilerlemeCubuk');
                cubuk.style.width = (TOPLAM ? Math.round(yapilan / TOPLAM * 100) : 0) + '%';
                cubuk.style.background = d.tamamlandi ? 'var(--success)' : 'var(--brand)';
                document.getElementById('tamamDurum').style.display = d.tamamlandi ? 'block' : 'none';
            })
            .catch(() => { this.checked = !this.checked; })
            .finally(() => { this.disabled = false; });
        });
    });
})();
</script>

@endsection
