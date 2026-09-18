@extends('admin._layout')

@section('title', 'Ek Hizmetler')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Ek Hizmetler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🧩 Ek Hizmetler</h1>
        <div class="page-subtitle">Paket/ilan detayında "Yanında Satın Alınabilecekler" bölümünde gösterilir. Bunlar paketler listesinde ayrı ilan olarak görünmez.</div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0 0 0 18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Yeni ekle formu --}}
<div class="section" style="margin-bottom:18px">
    <div class="section-title"><i data-lucide="plus-circle"></i> <span>Yeni Ek Hizmet</span></div>
    <form method="POST" action="{{ route('admin.ek-hizmetler.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:2fr 1fr 90px auto;gap:12px;align-items:end">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Hizmet Adı *</label>
                <input type="text" name="ad" required class="form-input" placeholder="Örn: Web Master Hizmeti" value="{{ old('ad') }}">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Fiyat (₺) *</label>
                <input type="number" step="0.01" min="0" name="fiyat" required class="form-input" placeholder="25000" value="{{ old('fiyat') }}">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">İkon</label>
                <input type="text" name="ikon" maxlength="60" class="form-input" placeholder="🧑‍💻" value="{{ old('ikon') }}">
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> <span>Ekle</span></button>
        </div>
        <div class="form-group" style="margin-top:12px;margin-bottom:0">
            <label class="form-label">Açıklama (opsiyonel)</label>
            <input type="text" name="aciklama" maxlength="1000" class="form-input" placeholder="Kısa açıklama" value="{{ old('aciklama') }}">
        </div>

        {{-- Kapsam: bu hizmet hangi kategorilerdeki paket detayında görünsün --}}
        <div class="form-group" style="margin-top:12px;margin-bottom:0">
            <label class="form-label">
                Hangi Kategorilerde Görünsün?
                <span style="font-weight:400;color:var(--text-muted);font-size:11px">— hiçbiri seçilmezse tüm paketlerde çıkar</span>
            </label>
            <div style="display:flex;flex-wrap:wrap;gap:6px;max-height:132px;overflow-y:auto;padding:10px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg-subtle)">
                @forelse($kategoriler as $k)
                    <label style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border:1px solid var(--border);border-radius:999px;background:var(--bg);cursor:pointer;font-size:12px">
                        <input type="checkbox" name="kategoriler[]" value="{{ $k->id }}"
                               @checked(in_array((string) $k->id, (array) old('kategoriler', []))) style="margin:0">
                        {{ $k->adi }}
                    </label>
                @empty
                    <span style="color:var(--text-muted);font-size:12px">Kategori bulunamadı.</span>
                @endforelse
            </div>
        </div>
    </form>
</div>

{{-- Liste (her hizmet = düzenlenebilir kart) --}}
<div class="section">
    <div class="section-title">
        <i data-lucide="list"></i> <span>Ek Hizmetler</span>
        <span class="badge badge-brand" style="margin-left:6px">{{ $hizmetler->count() }}</span>
    </div>

    @forelse($hizmetler as $h)
    <div style="background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;margin-bottom:10px">
        <form method="POST" action="{{ route('admin.ek-hizmetler.update', $h->id) }}" id="ek-form-{{ $h->id }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:64px 2fr 130px 90px auto;gap:10px;align-items:end">
                <div>
                    <label class="form-label" style="font-size:10px">İkon</label>
                    <input type="text" name="ikon" value="{{ $h->ikon }}" class="form-input" style="text-align:center" placeholder="🧩">
                </div>
                <div>
                    <label class="form-label" style="font-size:10px">Hizmet Adı</label>
                    <input type="text" name="ad" value="{{ $h->ad }}" required class="form-input" style="font-weight:600">
                </div>
                <div>
                    <label class="form-label" style="font-size:10px">Fiyat (₺)</label>
                    <input type="number" step="0.01" min="0" name="fiyat" value="{{ $h->fiyat }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size:10px">Sıra</label>
                    <input type="number" name="sira" value="{{ $h->sira }}" class="form-input">
                </div>
                <div>
                    <span class="badge {{ $h->durum ? 'badge-success' : 'badge-secondary' }}" style="display:block;text-align:center;padding:6px 8px">{{ $h->durum ? 'Aktif' : 'Pasif' }}</span>
                </div>
            </div>
            <div style="margin-top:10px">
                <label class="form-label" style="font-size:10px">Açıklama</label>
                <input type="text" name="aciklama" value="{{ $h->aciklama }}" class="form-input" placeholder="Kısa açıklama">
            </div>

            {{-- Kapsam --}}
            @php $_secili = array_filter(array_map('trim', explode(',', (string) $h->kategoriler))); @endphp
            <div style="margin-top:10px">
                <label class="form-label" style="font-size:10px">
                    Görüneceği Kategoriler
                    @if(empty($_secili))
                        <span class="badge badge-warning" style="margin-left:4px;font-size:9px">Tüm paketlerde çıkıyor</span>
                    @endif
                </label>
                <div style="display:flex;flex-wrap:wrap;gap:6px;max-height:110px;overflow-y:auto;padding:8px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg)">
                    @foreach($kategoriler as $k)
                        <label style="display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border:1px solid var(--border);border-radius:999px;background:var(--bg-subtle);cursor:pointer;font-size:11.5px">
                            <input type="checkbox" name="kategoriler[]" value="{{ $k->id }}"
                                   @checked(in_array((string) $k->id, $_secili)) style="margin:0">
                            {{ $k->adi }}
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
        <div style="display:flex;gap:6px;justify-content:flex-end;margin-top:12px;padding-top:12px;border-top:1px dashed var(--border)">
            <button type="submit" form="ek-form-{{ $h->id }}" class="btn btn-primary btn-sm"><i data-lucide="save"></i> <span>Kaydet</span></button>
            <form method="POST" action="{{ route('admin.ek-hizmetler.toggle', $h->id) }}" style="display:inline">@csrf
                <button class="btn btn-secondary btn-sm" title="{{ $h->durum ? 'Pasife al' : 'Aktife al' }}">
                    <i data-lucide="{{ $h->durum ? 'eye-off' : 'eye' }}"></i> <span>{{ $h->durum ? 'Pasife Al' : 'Aktife Al' }}</span>
                </button>
            </form>
            <form method="POST" action="{{ route('admin.ek-hizmetler.destroy', $h->id) }}" style="display:inline" onsubmit="return confirm('Bu ek hizmet silinsin mi?')">@csrf @method('DELETE')
                <button class="btn btn-secondary btn-sm" style="color:var(--danger)" title="Sil"><i data-lucide="trash-2"></i> <span>Sil</span></button>
            </form>
        </div>
    </div>
    @empty
    <div class="empty-state" style="padding:32px;text-align:center;color:var(--text-muted)">
        <p>Henüz ek hizmet yok. Yukarıdan ekleyin (ör. <strong>Web Master — 25.000₺</strong>).</p>
    </div>
    @endforelse
</div>

@endsection
