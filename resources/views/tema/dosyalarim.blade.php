@extends('layouts.panel')

@section('page_title', 'Dosyalarım')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-folder-open"></i>
            {{ __('messages.my_files') }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <a href="{{ route('dosyalarim') }}">{{ __('messages.my_files') }}</a>
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">{{ __('messages.file_name') }}</th>
                <th scope="col" class="text-center" style="width:200px;">{{ __('messages.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @if(isset($dosyalar) && (is_countable($dosyalar) ? count($dosyalar) : 0) > 0)
            @foreach($dosyalar as $item)
            <tr>
                <td class="align-middle">{{ $item->file_name ?? $item->ad ?? '-' }}</td>
                <td class="text-center align-middle">
                    @if(isset($item->file_name))
                    <a download href="{{ asset('tema/webajans/uploads/dosyalar2/'.$item->file_name) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-download"></i> {{ __('messages.download') }}
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        @else
            <tr><td colspan="2" class="text-center p-4">
                <i class="fas fa-folder-open" style="font-size:48px;color:#ccc"></i>
                <p class="mt-3 mb-0">{{ __('messages.no_files') }}</p>
            </td></tr>
        @endif
        </tbody>
    </table>

    <div class="alert alert-warning alert-dismissible fade show mt-4" role="alert">
        <button type="button" class="close mt-0" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        {{ __('messages.files_download_hint') }}
    </div>
</div>
@endsection
