@extends('layouts.panel')

@section('page_title', 'Hosting Yönetimi')

@section('panel_content')
@php
    $isPast = $hosting->bitis_tarihi ? \Carbon\Carbon::parse($hosting->bitis_tarihi)->isPast() : false;
    $diff = $hosting->bitis_tarihi ? \Carbon\Carbon::parse($hosting->bitis_tarihi)->diffInDays(now()) : null;
@endphp

<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-server"></i>
            {{ $hosting->domain ?? $hosting->paket_adi ?? 'Hosting' }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <a href="{{ route('hostinglerim') }}">Hostinglerim</a> /
            {{ $hosting->domain ?? 'Detay' }}
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="alert {{ $hosting->durum == 1 ? 'alert-success' : 'alert-danger' }} text-center">
                <strong>{{ __('messages.status_label') }}</strong>
                @if($hosting->durum == 1) Aktif
                @elseif($hosting->durum == 2) Askıda
                @else Pasif
                @endif
            </div>
        </div>
        @if($hosting->bitis_tarihi)
        <div class="col-md-4">
            <div class="alert {{ $isPast ? 'alert-danger' : ($diff <= 30 ? 'alert-warning' : 'alert-info') }} text-center">
                <strong>{{ __('messages.end_label') }}</strong> {{ \Carbon\Carbon::parse($hosting->bitis_tarihi)->format('d.m.Y') }}
                @if(!$isPast && $diff <= 30)<br><small><strong>{{ $diff }} gün kaldı</strong></small>
                @elseif($isPast)<br><small><strong>{{ __('messages.expired') }}</strong></small>
                @endif
            </div>
        </div>
        @endif
        @if($hosting->baslangic_tarihi)
        <div class="col-md-4">
            <div class="alert alert-secondary text-center">
                <strong>{{ __('messages.start_label') }}</strong> {{ \Carbon\Carbon::parse($hosting->baslangic_tarihi)->format('d.m.Y') }}
            </div>
        </div>
        @endif
    </div>

    <div class="panel panel-default mb-3">
        <div class="panel-heading"><strong><i class="fa fa-info-circle"></i> Hosting Bilgileri</strong></div>
        <div class="panel-body">
            <table class="table table-bordered">
                <tbody>
                    @if($hosting->domain)<tr><th style="width:200px;">{{ __('messages.domain') }}</th><td>{{ $hosting->domain }}</td></tr>@endif
                    <tr><th>{{ __('messages.package') }}</th><td>{{ $hosting->paket_adi ?? 'Hosting' }}</td></tr>
                    @if($hosting->ip_adresi ?? null)<tr><th>IP Adresi</th><td><code>{{ $hosting->ip_adresi }}</code></td></tr>@endif
                    @if($hosting->sunucu ?? null)<tr><th>Sunucu</th><td>{{ $hosting->sunucu }}</td></tr>@endif
                    @if($hosting->disk_alani ?? null)<tr><th>{{ __('messages.disk_space') }}</th><td>{{ $hosting->disk_alani }}</td></tr>@endif
                    @if($hosting->bant_genisligi ?? null)<tr><th>{{ __('messages.bandwidth') }}</th><td>{{ $hosting->bant_genisligi }}</td></tr>@endif
                </tbody>
            </table>
        </div>
    </div>

    @if($hosting->bitis_tarihi && ($isPast || $diff <= 30))
    <div class="alert alert-danger text-center mb-3">
        <h5><i class="fa fa-exclamation-triangle"></i>
            @if($isPast) Hostinginizin süresi dolmuş
            @else Süresinin dolmasına {{ $diff }} gün kaldı
            @endif
        </h5>
        <a href="{{ route('hosting.yenile', $hosting->id) }}" class="btn btn-success mt-2">
            <i class="fa fa-sync"></i> {{ __('messages.renew_now') }}
        </a>
    </div>
    @endif

    <div class="text-center">
        <a href="{{ route('hostinglerim') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> {{ __('messages.go_back') }}
        </a>
        <a href="{{ route('destek.talebi.olustur') }}" class="btn btn-outline-primary">
            <i class="fa fa-life-ring"></i> {{ __('messages.create_support_ticket') }}
        </a>
    </div>
</div>
@endsection
