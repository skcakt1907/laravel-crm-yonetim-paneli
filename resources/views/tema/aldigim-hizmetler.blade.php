@extends('layouts.panel')

@section('page_title', 'Hizmetlerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-cubes"></i>
            Hizmetlerim
        </h5>
        <a href="{{ Route::has('paketler') ? route('paketler') : url('/paketler') }}" class="btn btn-sm btn-outline-primary pull-right">
            <i class="fa fa-plus"></i> Yeni Hizmet Al
        </a>
    </div>

    {{-- Tip özeti --}}
    <div class="hzm-ozet">
        <span class="hzm-rozet hzm-paket"><i class="fa fa-cube"></i> Web Paketi <b>{{ $ozet['paket'] ?? 0 }}</b></span>
        <span class="hzm-rozet hzm-hosting"><i class="fa fa-server"></i> Hosting <b>{{ $ozet['hosting'] ?? 0 }}</b></span>
        <span class="hzm-rozet hzm-domain"><i class="fa fa-globe"></i> Alan Adı <b>{{ $ozet['domain'] ?? 0 }}</b></span>
    </div>

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">Hizmet</th>
                <th scope="col" class="text-center" style="width:120px;">Tür</th>
                <th scope="col" class="text-left">Alan Adı</th>
                <th scope="col" class="text-center" style="width:110px;">Başlangıç</th>
                <th scope="col" class="text-center" style="width:110px;">Bitiş</th>
                <th scope="col" class="text-center" style="width:110px;">Durum</th>
                <th scope="col" class="text-center" style="width:110px;">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kayitlar as $k)
                @php
                    $_tip = (int) ($k->tipi ?? 0);
                    $_tipAd = [1 => 'Hosting', 2 => 'Web Paketi', 3 => 'Alan Adı'][$_tip] ?? 'Hizmet';
                    $_tipSinif = [1 => 'hzm-hosting', 2 => 'hzm-paket', 3 => 'hzm-domain'][$_tip] ?? '';
                    $_ad = $k->paket_adi ?: ($k->hosting_baslik ?: ($k->adi ?: ($_tip === 3 ? ($k->domain ?: 'Alan Adı') : $_tipAd)));
                    $_bas = $k->baslangic_tarih ?? null;
                    $_bit = $k->bitis_tarih ?? null;
                @endphp
                <tr>
                    <th scope="row" class="align-middle text-left">
                        <span class="link">{{ $_ad }}</span>
                        @if(!empty($k->tarih))
                            <p class="t-detail">Satın alma: {{ \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') }}</p>
                        @endif
                    </th>
                    <td class="text-center align-middle">
                        <span class="hzm-rozet {{ $_tipSinif }}">{{ $_tipAd }}</span>
                    </td>
                    <td class="align-middle">{{ $k->domain ?: '-' }}</td>
                    <td class="text-center align-middle">{{ $_bas ? \Carbon\Carbon::parse($_bas)->format('d.m.Y') : '-' }}</td>
                    <td class="text-center align-middle">{{ $_bit ? \Carbon\Carbon::parse($_bit)->format('d.m.Y') : '-' }}</td>
                    <td class="text-center align-middle">
                        @if((int) ($k->durum ?? 0) === 1)
                            <label class="alert alert-success alert-sm mt-3">Aktif</label>
                        @else
                            <label class="alert alert-secondary alert-sm mt-3">Pasif</label>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        <strong>{{ number_format((float) ($k->tutar ?? 0), 2, ',', '.') }} ₺</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center p-4">Henüz kayıtlı bir hizmetiniz bulunmuyor.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(method_exists($kayitlar, 'links'))
        <div class="mt-3">{{ $kayitlar->links() }}</div>
    @endif
</div>

@push('styles')
<style>
    .hzm-ozet{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 18px}
    .hzm-rozet{
        display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;
        padding:5px 12px;border-radius:999px;background:#eef0e8;color:#43483a;white-space:nowrap;
    }
    .hzm-rozet b{font-weight:800}
    .hzm-rozet i{font-size:11.5px;opacity:.85}
    .hzm-paket{background:#e8eefc;color:#25457f}
    .hzm-hosting{background:#e6f5ec;color:#1f6b3a}
    .hzm-domain{background:#fdf3dd;color:#8a6200}
</style>
@endpush
@endsection
