@extends('layouts.bayi')

@section('title', __('messages.sub_resellers'))

@section('panel_content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🏪 <span class="gradient-text">{{ __('messages.sub_resellers') }}</span></h1>
    <button type="button" onclick="document.getElementById('altBayiModal').classList.remove('hidden')" class="btn-y">➕ {{ __('messages.add_new_sub_reseller') }}</button>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-yellow-500/10">
        <h2 class="font-display text-xl font-bold flex items-center gap-2">👥 {{ __('messages.my_sub_resellers') }}</h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.reseller_code') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.full_name') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.email') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.phone') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.registration_date') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.status') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($altBayilerWithUye ?? $altBayiler ?? [] as $altBayi)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">{{ $altBayi->id ?? '-' }}</td>
                <td class="px-6 py-4"><span class="badge badge-yellow">{{ $altBayi->bayi_kodu ?? '-' }}</span></td>
                <td class="px-6 py-4 font-semibold">{{ isset($altBayi->uye) ? (($altBayi->uye->ad ?? '').' '.($altBayi->uye->soyad ?? '')) : '-' }}</td>
                <td class="px-6 py-4 text-white/80">{{ $altBayi->uye->email ?? '-' }}</td>
                <td class="px-6 py-4 text-white/80">{{ $altBayi->uye->telefon ?? '-' }}</td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ isset($altBayi->created_at) ? date('d.m.Y', strtotime($altBayi->created_at)) : '-' }}</td>
                <td class="px-6 py-4 text-center">
                    @if(isset($altBayi->onay_durumu))
                        @if($altBayi->onay_durumu == 1)<span class="badge badge-success">✅ {{ __('messages.approved') }}</span>
                        @else<span class="badge badge-warning">⏳ {{ __('messages.pending') }}</span>@endif
                    @elseif(isset($altBayi->durum))
                        @if($altBayi->durum == 1)<span class="badge badge-success">✅ {{ __('messages.active') }}</span>
                        @else<span class="badge badge-secondary">⚫ {{ __('messages.inactive') }}</span>@endif
                    @else<span class="badge badge-secondary">-</span>@endif
                </td>
                <td class="px-6 py-4 text-center">
                    <a href="{{ route('admin.bayiler.detay', $altBayi->id) }}" class="btn-o" style="padding:6px 12px;font-size:12px">👁️ {{ __('messages.detail') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">🏪</div>{{ __('messages.no_sub_resellers_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="altBayiModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
    <div class="glass rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-xl font-bold">➕ {{ __('messages.add_new_sub_reseller') }}</h3>
            <button type="button" onclick="document.getElementById('altBayiModal').classList.add('hidden')" class="text-2xl text-white/60 hover:text-white">×</button>
        </div>
        <form action="{{ route('admin.bayi.alt.bayi.ekle') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="field-label">{{ __('messages.first_name') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="adi" required>
            </div>
            <div>
                <label class="field-label">{{ __('messages.email') }} <span class="text-rose-300">*</span></label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label class="field-label">{{ __('messages.phone') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="telefon" required>
            </div>
            <div class="glass rounded-xl p-3 text-xs text-white/70 flex items-start gap-2" style="border-color:rgba(59,130,246,.4)">
                <span>ℹ️</span><span>{{ __('messages.sub_reseller_will_be_created') }}</span>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('altBayiModal').classList.add('hidden')" class="btn-o">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn-y">{{ __('messages.create_application') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
