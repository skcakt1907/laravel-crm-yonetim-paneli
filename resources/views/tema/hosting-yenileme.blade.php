@extends('layouts.panel')

@section('page_title', 'Hosting Yenileme')

@section('panel_content')
@php
    $yillik_fiyat = $hosting->fiyat ?? $hosting->tutar ?? 0;
    $aylik_fiyat = $yillik_fiyat / 12;
@endphp

<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-sync"></i>
            Hosting Yenileme
        </h5>
        <div class="pull-right">
            <a href="{{ route('hostinglerim') }}">Hostinglerim</a> /
            <a href="{{ route('uye.hosting.detay', $hosting->id) }}">{{ $hosting->domain ?? 'Detay' }}</a> /
            Yenile
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    <div class="panel panel-default mb-3">
        <div class="panel-heading"><strong><i class="fa fa-info-circle"></i> {{ __('messages.renewal_summary') }}</strong></div>
        <div class="panel-body">
            <table class="table table-bordered mb-0">
                <tbody>
                    @if($hosting->domain)<tr><th style="width:200px;">{{ __('messages.domain') }}</th><td>{{ $hosting->domain }}</td></tr>@endif
                    <tr><th>{{ __('messages.package') }}</th><td>{{ $hosting->paket_adi ?? 'Hosting' }}</td></tr>
                    @if($hosting->bitis_tarihi)
                    <tr><th>{{ __('messages.current_expiry_date') }}</th><td class="text-danger"><strong>{{ \Carbon\Carbon::parse($hosting->bitis_tarihi)->format('d.m.Y') }}</strong></td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <form action="{{ route('sepet.ekle') }}" method="POST">
        @csrf
        {{-- ZORUNLU SEPET ALANLARI (29 Mayıs 2026 fix — SepetController::ekle validation gerekiyor) --}}
        <input type="hidden" name="urun_id" value="{{ $hosting->id }}">
        <input type="hidden" name="urun_tipi" value="hosting_yenileme">
        <input type="hidden" name="urun_adi" value="{{ ($hosting->paket_adi ?? $hosting->hosting_baslik ?? 'Hosting') . ' — Yenileme' . ($hosting->domain ? ' (' . $hosting->domain . ')' : '') }}">
        <input type="hidden" name="fiyat" id="fiyat_input" value="{{ $yillik_fiyat }}">
        <input type="hidden" name="aciklama" id="aciklama_input" value="Hosting yenileme: {{ $hosting->domain ?? 'Hosting' }} - 1 Yıllık">
        <input type="hidden" name="hosting_id" value="{{ $hosting->id }}">

        <div class="panel panel-default mb-3">
            <div class="panel-heading"><strong><i class="fa fa-calendar"></i> {{ __('messages.select_renewal_period') }}</strong></div>
            <div class="panel-body">
                <div class="row">
                    @php
                        $opts = [
                            ['12','1 Yıllık', $yillik_fiyat, null, ''],
                            ['24','2 Yıllık', $yillik_fiyat*2*0.9, $yillik_fiyat*2, '%10 İndirim'],
                            ['36','3 Yıllık', $yillik_fiyat*3*0.8, $yillik_fiyat*3, '%20 İndirim'],
                        ];
                    @endphp
                    @foreach($opts as $i => $opt)
                    @php [$sure,$label,$fiyat,$eskifiyat,$rozet] = $opt; @endphp
                    <div class="col-md-4 mb-3">
                        <label class="d-block" style="cursor:pointer">
                            <input type="radio" name="sure" value="{{ $sure }}" class="d-none sure-radio" {{ $i === 0 ? 'checked' : '' }}
                                   data-fiyat="{{ $fiyat }}" data-label="{{ $label }}" onchange="fiyatGuncelle(this)">
                            <div class="panel panel-default text-center sure-secim mb-0">
                                <div class="panel-body">
                                    @if($rozet)<span class="badge badge-warning mb-2">{{ $rozet }}</span>@endif
                                    <h6>{{ $label }}</h6>
                                    <h3 class="mb-1"><strong>{{ number_format($fiyat, 2, ',', '.') }} ₺</strong></h3>
                                    @if($eskifiyat)
                                        <small><del>{{ number_format($eskifiyat, 2, ',', '.') }} ₺</del></small>
                                    @else
                                        <small class="text-muted">~{{ number_format($aylik_fiyat, 2, ',', '.') }} ₺/ay</small>
                                    @endif
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('uye.hosting.detay', $hosting->id) }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('messages.go_back') }}
            </a>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fa fa-shopping-cart"></i> {{ __('messages.add_to_cart_and_renew') }}
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
.sure-radio:checked + .sure-secim{border:2px solid #28a745!important;background:#f0fff4!important}
.sure-secim{border:2px solid transparent;transition:.2s}
.sure-secim:hover{border-color:#ced4da}
</style>
@endpush

@push('scripts')
<script>
function fiyatGuncelle(radio) {
    var fiyat = radio.getAttribute('data-fiyat');
    var label = radio.getAttribute('data-label');
    var domain = "{{ $hosting->domain ?? 'Hosting' }}";

    var fiyatInput = document.getElementById('fiyat_input');
    var aciklamaInput = document.getElementById('aciklama_input');

    if (fiyatInput) fiyatInput.value = fiyat;
    if (aciklamaInput) aciklamaInput.value = 'Hosting yenileme: ' + domain + ' - ' + label;
}
</script>
@endpush
@endsection