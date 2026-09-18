@extends('layouts.panel')

@section('page_title', 'Bildirimlerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-bell"></i>
            Bildirimlerim
        </h5>
        @if($bildirimler->total() > 0)
        <div class="pull-right">
            <form action="{{ route('bildirimler.hepsini.oku') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-check"></i> Hepsini Oku
                </button>
            </form>
            <form action="{{ route('bildirimler.hepsini.sil') }}" method="POST" class="d-inline" onsubmit="return confirm('Tüm bildirimler silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fa fa-trash"></i> {{ __('messages.delete_all') }}
                </button>
            </form>
        </div>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th class="text-left">Bildirim</th>
                <th class="text-center" style="width:160px;">{{ __('messages.table_date') }}</th>
                <th class="text-center" style="width:200px;">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($bildirimler as $bildirim)
            @php
                $tipler = [
                    'info'    => ['alert-info','info-circle'],
                    'success' => ['alert-success','check-circle'],
                    'warning' => ['alert-warning','exclamation-triangle'],
                    'danger'  => ['alert-danger','exclamation-circle'],
                ];
                $t = $tipler[$bildirim->tip] ?? $tipler['info'];
            @endphp
            <tr class="{{ $bildirim->okundu ? 'text-muted' : '' }}">
                <td>
                    <strong>
                        <i class="fa fa-{{ $t[1] }} text-{{ $bildirim->tip ?? 'info' }}"></i>
                        {{ $bildirim->baslik }}
                        @if(!$bildirim->okundu)<span class="badge badge-warning ml-1">{{ __('messages.new_badge') }}</span>@endif
                    </strong>
                    <p class="t-detail mt-1 mb-0">{{ $bildirim->mesaj }}</p>
                </td>
                <td class="text-center align-middle">
                    <small>{{ $bildirim->created_at->diffForHumans() }}</small>
                </td>
                <td class="text-center align-middle">
                    @if($bildirim->link)
                        <a href="{{ $bildirim->link }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-arrow-right"></i> Detay
                        </a>
                    @endif
                    @if(!$bildirim->okundu)
                        <form action="{{ route('bildirim.okundu', $bildirim->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-success btn-sm" title="{{ __('messages.mark_read_title') }}">
                                <i class="fa fa-check"></i>
                            </button>
                        </form>
                    @endif
                    @if($bildirim->uye_id !== null)
                        <form action="{{ route('bildirim.sil', $bildirim->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" title="{{ __('messages.delete') }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center p-4">
                <i class="fa fa-bell-slash" style="font-size:48px;color:#ccc"></i>
                <p class="mt-3 mb-0">{{ __('messages.no_notifications') }}</p>
            </td></tr>
        @endforelse
        </tbody>
    </table>

    @if($bildirimler->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $bildirimler->links() }}</div>
    @endif
</div>
@endsection
