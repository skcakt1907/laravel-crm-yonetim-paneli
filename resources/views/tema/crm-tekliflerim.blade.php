@extends('layouts.panel')

@section('page_title', 'Özel Tekliflerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">

    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-file-invoice-dollar"></i>
            {{ __('messages.my_special_offers') }}
        </h5>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @php
        $bekleyenSayisi = $teklifler->whereIn('durum', ['gonderildi','bekliyor'])->count();
        $kabulSayisi    = $teklifler->where('durum', 'kabul_edildi')->count();
        $odenenSayisi   = $teklifler->whereIn('durum', ['odendi','onaylandi'])->count();
    @endphp

    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">{{ __('messages.total') }}</div>
                <div style="font-size:24px;font-weight:700;color:#1f2937;">{{ $teklifler->count() }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div style="background:#fff;border:1px solid #fde68a;border-radius:12px;padding:16px;">
                <div style="font-size:12px;color:#92400e;margin-bottom:6px;">Bekleyen</div>
                <div style="font-size:24px;font-weight:700;color:#b45309;">{{ $bekleyenSayisi }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div style="background:#fff;border:1px solid #a7f3d0;border-radius:12px;padding:16px;">
                <div style="font-size:12px;color:#065f46;margin-bottom:6px;">Kabul Edilen</div>
                <div style="font-size:24px;font-weight:700;color:#059669;">{{ $kabulSayisi }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div style="background:#fff;border:1px solid #c7d2fe;border-radius:12px;padding:16px;">
                <div style="font-size:12px;color:#3730a3;margin-bottom:6px;">{{ __('messages.paid_approved') }}</div>
                <div style="font-size:24px;font-weight:700;color:#1a2332;">{{ $odenenSayisi }}</div>
            </div>
        </div>
    </div>

    @if($teklifler->count() > 0)
        <table id="datatable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th scope="col" class="text-left">{{ __('messages.offer') }}</th>
                    <th scope="col" class="text-center" style="width:140px;">{{ __('messages.table_amount') }}</th>
                    <th scope="col" class="text-center" style="width:130px;">Ödeme</th>
                    <th scope="col" class="text-center" style="width:140px;">{{ __('messages.table_status') }}</th>
                    <th scope="col" class="text-center" style="width:130px;">{{ __('messages.table_date') }}</th>
                    <th scope="col" class="text-center" style="width:110px;">{{ __('messages.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teklifler as $t)
                <tr>
                    <th scope="row" class="align-middle text-left">
                        <a href="{{ route('uye.crm.teklif.detay', $t->id) }}" class="link">{{ $t->paket_adi ?? '—' }}</a>
                        @if(!empty($t->tip))
                            <p class="t-detail">
                                @if($t->tip === 'bayi')
                                    ⭐ Bayilik Teklifi
                                @else
                                    🎁 Özel Teklif
                                @endif
                            </p>
                        @endif
                    </th>
                    <td class="text-center align-middle">
                        <strong>₺{{ number_format((float)($t->tutar ?? 0), 2, ',', '.') }}</strong>
                    </td>
                    <td class="text-center align-middle">
                        @if(($t->odeme_yontemi ?? '') === 'online')
                            <span class="badge badge-info">💳 Online</span>
                        @elseif(($t->odeme_yontemi ?? '') === 'havale')
                            <span class="badge badge-warning">🏦 Havale</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @php $d = $t->durum ?? 'bekliyor'; @endphp
                        @if(in_array($d, ['gonderildi','bekliyor']))
                            <label class="alert alert-warning alert-sm mt-3">{{ __('messages.awaiting_response') }}</label>
                        @elseif($d === 'kabul_edildi')
                            <label class="alert alert-info alert-sm mt-3">✓ Kabul Edildi</label>
                        @elseif($d === 'odendi')
                            <label class="alert alert-success alert-sm mt-3" style="background:#fef3c7;color:#92400e;border-color:#fde68a">{{ __('messages.paid_pending_approval') }}</label>
                        @elseif(in_array($d, ['onaylandi','paid']))
                            <label class="alert alert-success alert-sm mt-3">{{ __('messages.approved_check') }}</label>
                        @elseif(in_array($d, ['reddedildi','iptal']))
                            <label class="alert alert-danger alert-sm mt-3">✕ Reddedildi</label>
                        @else
                            <label class="alert alert-secondary alert-sm mt-3">{{ $d }}</label>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        <small>{{ \Carbon\Carbon::parse($t->sent_at ?? $t->created_at ?? now())->format('d.m.Y') }}</small>
                    </td>
                    <td class="text-center align-middle">
                        <a href="{{ route('uye.crm.teklif.detay', $t->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-eye"></i> {{ __('messages.view_details') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center p-5">
            <i class="fa fa-file-invoice" style="font-size:64px;color:#ccc"></i>
            <h5 class="mt-3">{{ __('messages.no_offers_sent') }}</h5>
            <p class="text-muted">{{ __('messages.new_offers_note') }}</p>
        </div>
    @endif
</div>
@endsection