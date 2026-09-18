@extends('layouts.bayi')

@section('title', __('messages.my_support_requests'))

@section('panel_content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🎫 <span class="gradient-text">{{ __('messages.my_support_requests') }}</span></h1>
    <a href="{{ route('admin.bayi.destek.ticket.olustur') }}" class="btn-y">➕ {{ __('messages.create_new_ticket') }}</a>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.subject') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.category') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.priority') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.status') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.date') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($tickets as $ticket)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">#{{ $ticket->id }}</td>
                <td class="px-6 py-4 font-semibold">{{ $ticket->konu }}</td>
                <td class="px-6 py-4 text-center"><span class="badge badge-secondary">{{ $ticket->kategori }}</span></td>
                <td class="px-6 py-4 text-center">
                    @if($ticket->oncelik == 'acil')<span class="badge badge-danger">🚨 {{ __('messages.urgent') }}</span>
                    @elseif($ticket->oncelik == 'yuksek')<span class="badge badge-warning">⚠️ {{ __('messages.high') }}</span>
                    @elseif($ticket->oncelik == 'normal')<span class="badge badge-yellow">{{ __('messages.normal') }}</span>
                    @else<span class="badge badge-secondary">{{ __('messages.low') }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    @if($ticket->durum == 'acik')<span class="badge badge-success">🟢 {{ __('messages.open') }}</span>
                    @elseif($ticket->durum == 'cevaplandi')<span class="badge badge-yellow">💬 {{ __('messages.answered') }}</span>
                    @elseif($ticket->durum == 'cozuldu')<span class="badge badge-success">✅ {{ __('messages.resolved') }}</span>
                    @else<span class="badge badge-secondary">🔒 {{ __('messages.closed') }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d.m.Y H:i') }}</td>
                <td class="px-6 py-4 text-center">
                    <a href="{{ route('admin.bayi.destek.ticket.detay', $ticket->id) }}" class="btn-o" style="padding:6px 12px;font-size:12px">👁️ {{ __('messages.view') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">🎫</div>{{ __('messages.no_support_requests_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
