@extends('layouts.bayi')

@section('title', __('messages.security_settings'))

@section('panel_content')
<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🔐 <span class="gradient-text">{{ __('messages.security_settings') }}</span></h1>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <form action="{{ route('admin.bayi.sifre.degistir') }}" method="POST">
        @csrf
        <div class="glass rounded-2xl p-6">
            <div class="section-title">🔑 {{ __('messages.change_password') }}</div>
            <div class="space-y-4">
                <div>
                    <label class="field-label">{{ __('messages.current_password') }} <span class="text-rose-300">*</span></label>
                    <input type="password" name="mevcut_sifre" required>
                </div>
                <div>
                    <label class="field-label">{{ __('messages.new_password') }} <span class="text-rose-300">*</span></label>
                    <input type="password" name="yeni_sifre" required minlength="6">
                </div>
                <div>
                    <label class="field-label">{{ __('messages.new_password_confirm') }} <span class="text-rose-300">*</span></label>
                    <input type="password" name="yeni_sifre_confirmation" required minlength="6">
                </div>
                <button type="submit" class="btn-y w-full">🔒 {{ __('messages.update_password') }}</button>
            </div>
        </div>
    </form>

    <div class="glass rounded-2xl p-6">
        <div class="section-title">🛡️ {{ __('messages.two_factor_auth') }}</div>
        <p class="text-sm text-white/70 mb-4">{{ __('messages.extra_security_layer') }}</p>
        <form action="{{ route('admin.bayi.2fa.aktif') }}" method="POST">
            @csrf
            <div class="flex items-center justify-between p-4 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
                <div>
                    <div class="font-semibold">🔐 {{ __('messages.enable_2fa') }}</div>
                    @if($ikiFactorAktif)
                        <div class="text-xs text-emerald-300 mt-1">✅ {{ __('messages.2fa_active') }}</div>
                    @endif
                </div>
                <label class="toggle"><input type="checkbox" name="durum" {{ $ikiFactorAktif ? 'checked' : '' }} onchange="this.form.submit()"><span class="slider"></span></label>
            </div>
        </form>
        <div class="mt-4 text-xs text-white/60 flex items-start gap-2">
            <span>ℹ️</span><span>{{ __('messages.2fa_phone_confirm') }}</span>
        </div>
    </div>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-yellow-500/10">
        <h2 class="font-display text-xl font-bold flex items-center gap-2">📜 {{ __('messages.session_history') }}</h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.ip_address') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.browser') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.device') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.login_date') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.logout_date') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($oturumlar ?? [] as $oturum)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 font-mono text-xs">{{ $oturum->ip_adresi }}</td>
                <td class="px-6 py-4">{{ $oturum->tarayici ?? '-' }}</td>
                <td class="px-6 py-4">{{ $oturum->cihaz ?? '-' }}</td>
                <td class="px-6 py-4 text-xs text-white/70">{{ \Carbon\Carbon::parse($oturum->giris_tarihi)->format('d.m.Y H:i') }}</td>
                <td class="px-6 py-4 text-xs">
                    @if($oturum->cikis_tarihi)
                        <span class="text-white/70">{{ \Carbon\Carbon::parse($oturum->cikis_tarihi)->format('d.m.Y H:i') }}</span>
                    @else
                        <span class="badge badge-success">🟢 {{ __('messages.active') }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-white/50">{{ __('messages.no_session_history') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
