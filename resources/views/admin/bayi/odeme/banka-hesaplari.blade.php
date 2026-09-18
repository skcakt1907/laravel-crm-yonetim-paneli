@extends('layouts.bayi')

@section('title', __('messages.my_bank_accounts'))

@section('panel_content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🏦 <span class="gradient-text">{{ __('messages.my_bank_accounts') }}</span></h1>
    <button type="button" onclick="document.getElementById('yeniHesapModal').classList.remove('hidden')" class="btn-y">➕ {{ __('messages.add_new_account') }}</button>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    @forelse($hesaplar as $hesap)
    <div class="glass rounded-2xl p-6 relative {{ $hesap->varsayilan ? 'ring-2 ring-yellow-500' : '' }}">
        @if($hesap->varsayilan)<span class="absolute top-3 right-3 badge badge-yellow">⭐ {{ __('messages.default') }}</span>@endif
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center text-2xl">🏦</div>
            <div>
                <div class="font-display text-lg font-bold">{{ $hesap->banka_adi }}</div>
                <div class="text-xs text-white/60">{{ $hesap->hesap_sahibi }}</div>
            </div>
        </div>
        <div class="space-y-2 text-sm mb-4">
            <div><span class="text-white/60">IBAN:</span> <code class="text-yellow-400 font-mono text-xs ml-2">{{ $hesap->iban }}</code></div>
            @if($hesap->sube_kodu)<div><span class="text-white/60">{{ __('messages.branch_code') }}:</span> <strong class="ml-2">{{ $hesap->sube_kodu }}</strong></div>@endif
            @if($hesap->hesap_no)<div><span class="text-white/60">{{ __('messages.account_number') }}:</span> <strong class="ml-2">{{ $hesap->hesap_no }}</strong></div>@endif
        </div>
        <form action="{{ route('admin.bayi.banka.hesap.sil', $hesap->id) }}" method="POST" onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-o" style="padding:6px 12px;font-size:12px;border-color:rgba(239,68,68,.4);color:#fca5a5">🗑️ {{ __('messages.delete') }}</button>
        </form>
    </div>
    @empty
    <div class="lg:col-span-2 glass rounded-2xl p-16 text-center">
        <div class="text-6xl mb-3">🏦</div>
        <p class="text-white/60 mb-4">{{ __('messages.no_bank_accounts_yet') }}</p>
        <button type="button" onclick="document.getElementById('yeniHesapModal').classList.remove('hidden')" class="btn-y">➕ {{ __('messages.add_new_account') }}</button>
    </div>
    @endforelse
</div>

<!-- Modal -->
<div id="yeniHesapModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
    <div class="glass rounded-2xl p-6 w-full max-w-md mx-4 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-xl font-bold">🏦 {{ __('messages.new_bank_account') }}</h3>
            <button type="button" onclick="document.getElementById('yeniHesapModal').classList.add('hidden')" class="text-2xl text-white/60 hover:text-white">×</button>
        </div>
        <form action="{{ route('admin.bayi.banka.hesap.ekle') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="field-label">{{ __('messages.bank_name') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="banka_adi" required>
            </div>
            <div>
                <label class="field-label">{{ __('messages.account_holder') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="hesap_sahibi" required>
            </div>
            <div>
                <label class="field-label">IBAN <span class="text-rose-300">*</span></label>
                <input type="text" name="iban" maxlength="26" required>
                <small class="text-xs text-white/50 block mt-1">{{ __('messages.iban_26_chars') }}</small>
            </div>
            <div>
                <label class="field-label">{{ __('messages.branch_code') }}</label>
                <input type="text" name="sube_kodu">
            </div>
            <div>
                <label class="field-label">{{ __('messages.account_number') }}</label>
                <input type="text" name="hesap_no">
            </div>
            <div class="flex items-center justify-between p-3 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
                <div class="text-sm">⭐ {{ __('messages.set_as_default') }}</div>
                <label class="toggle"><input type="checkbox" name="varsayilan"><span class="slider"></span></label>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('yeniHesapModal').classList.add('hidden')" class="btn-o">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn-y">💾 {{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
