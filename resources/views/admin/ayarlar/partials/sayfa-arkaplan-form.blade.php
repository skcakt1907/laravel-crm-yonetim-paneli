@php
    use Illuminate\Support\Facades\DB;
    $turu = $ayarlar->{"{$sayfaAdi}_arkaplan_turu"} ?? 'resim';
    $arkaplan = DB::table('arka_plan')->where('id', 1)->first();
    $resimKolonu = match($sayfaAdi) {
        'anasayfa' => 'anasayfa',
        'paketler' => 'paketler',
        'hosting' => 'hosting',
        'blog' => 'blog',
        'iletisim' => 'iletisim',
        'hizmet' => 'hizmetler',
        'domain' => 'alanadi',
        'sayfa' => 'sayfalar',
        'referanslar' => 'referanslar',
        'firsatlar' => 'firsatlar',
        default => 'paketler'
    };
    $klasor = match($sayfaAdi) {
        'anasayfa' => 'anasayfa',
        'paketler' => 'paketler',
        'hosting' => 'hosting',
        'blog' => 'blog',
        'iletisim' => 'iletisim',
        'hizmet' => 'hizmetler',
        'domain' => 'alanadi',
        'sayfa' => 'sayfalar',
        'referanslar' => 'referanslar',
        'firsatlar' => 'firsatlar',
        default => 'paketler'
    };
    $resim = $arkaplan->{$resimKolonu} ?? 'bg.jpg';
@endphp

<div class="form-group">
    <label>Arkaplan Türü</label>
    <select class="form-control sayfa-arkaplan-turu" name="{{ $sayfaAdi }}_arkaplan_turu" data-sayfa="{{ $sayfaAdi }}">
        <option value="resim" {{ $turu == 'resim' ? 'selected' : '' }}>Resim</option>
        <option value="renk" {{ $turu == 'renk' ? 'selected' : '' }}>Düz Renk</option>
        <option value="gradient" {{ $turu == 'gradient' ? 'selected' : '' }}>Gradient</option>
    </select>
</div>

<div class="form-group d-none" id="{{ $sayfaAdi }}RenkDiv">
    <label>Arkaplan Rengi</label>
    <input type="color" class="form-control" name="{{ $sayfaAdi }}_arkaplan_renk" value="{{ $ayarlar->{"{$sayfaAdi}_arkaplan_renk"} ?? '#141e30' }}" style="height: 50px;">
</div>

<div class="form-group d-none" id="{{ $sayfaAdi }}GradientDiv">
    <label>Gradient Başlangıç</label>
    <input type="color" class="form-control mb-2" name="{{ $sayfaAdi }}_gradient_baslangic" value="{{ $ayarlar->{"{$sayfaAdi}_gradient_baslangic"} ?? '#141e30' }}" style="height: 40px;">
    <label>Gradient Bitiş</label>
    <input type="color" class="form-control" name="{{ $sayfaAdi }}_gradient_bitis" value="{{ $ayarlar->{"{$sayfaAdi}_gradient_bitis"} ?? '#243b55' }}" style="height: 40px;">
</div>

<div class="form-group" id="{{ $sayfaAdi }}ResimDiv">
    <label>Arkaplan Resmi</label>
    @if($resim && file_exists(public_path('tema/uploads/arkaplan/' . $klasor . '/' . $resim)))
        <div class="mb-2">
            <img src="{{ asset('tema/uploads/arkaplan/' . $klasor . '/' . $resim) }}" class="img-thumbnail" style="max-height: 150px; width: auto;">
            <p class="text-muted small mt-1">Mevcut: {{ $resim }}</p>
        </div>
    @endif
    <input type="file" class="form-control" name="{{ $sayfaAdi }}_arkaplan_resim" accept="image/*">
    <small class="form-text text-muted">Önerilen boyut: 1920x500px veya daha büyük</small>
</div>
