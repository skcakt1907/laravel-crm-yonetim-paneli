@extends('admin._layout')

@section('title', 'Veri Araçları')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Veri Araçları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Veri Araçları</h1>
        <div class="page-subtitle">Bakım komutlarını buradan çalıştır — sunucuya bağlanmaya gerek yok</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if(session('cikti'))
<div class="table-wrap" style="margin-bottom:18px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0">
        <div class="card-title"><i data-lucide="terminal"></i> {{ session('ciktiBaslik') }}</div>
    </div>
    <pre style="margin:0;padding:16px 18px;background:var(--bg-subtle);color:var(--text);
                font-family:ui-monospace,Consolas,monospace;font-size:12px;line-height:1.55;
                white-space:pre;overflow-x:auto;max-height:520px">{{ session('cikti') }}</pre>
</div>
@endif

<div class="alert alert-warning">
    <i data-lucide="info"></i>
    <div>
        <strong>Önce kuru çalıştır.</strong> Kuru çalışmada hiçbir şey değişmez, sadece
        ne olacağı listelenir. Listeyi okuduktan sonra yazma onayı verirsin.
        Yazma işlemlerinden önce veritabanı yedeği almak iyi olur.
    </div>
</div>

@foreach($araclar as $anahtar => $arac)
<div class="section" style="margin-bottom:14px">
    <div class="section-title">
        <i data-lucide="{{ $arac['ikon'] }}"></i>
        <span>{{ $arac['ad'] }}</span>
    </div>

    <div style="color:var(--text-secondary);font-size:13px;margin-bottom:14px">
        {{ $arac['aciklama'] }}
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        {{-- Kuru çalışma --}}
        @if(empty($arac['kurusuz']))
        <form method="POST" action="{{ route('admin.bakim.veri-araclari.calistir') }}">
            @csrf
            <input type="hidden" name="arac" value="{{ $anahtar }}">
            <button type="submit" class="btn btn-secondary">
                <i data-lucide="eye"></i> <span>Kuru Çalıştır (güvenli)</span>
            </button>
        </form>
        @endif

        {{-- Yazma --}}
        <form method="POST" action="{{ route('admin.bakim.veri-araclari.calistir') }}"
              style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"
              onsubmit="return confirm('{{ $arac['ad'] }} — veriyi DEĞİŞTİRECEK. Devam edilsin mi?')">
            @csrf
            <input type="hidden" name="arac" value="{{ $anahtar }}">
            <input type="hidden" name="uygula" value="1">
            <input type="text" name="onay" class="form-input" style="width:150px"
                   placeholder="ANLADIM yaz" autocomplete="off">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="play"></i> <span>Uygula</span>
            </button>
        </form>
    </div>

    @if(!empty($arac['kurusuz']))
        <div class="form-help" style="color:var(--danger)">
            ⚠️ Bu aracın kuru çalışması yok — çalıştırırsan e-posta gönderilir.
        </div>
    @endif
</div>
@endforeach

<div class="form-help" style="margin-top:16px">
    Her çalıştırma kayıt altına alınır (kim, hangi araç, kuru mu). Buradan yalnızca
    yukarıdaki sabit komutlar çalışır; serbest komut girişi yoktur.
</div>

@endsection
