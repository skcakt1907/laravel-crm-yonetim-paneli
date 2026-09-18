@extends('layouts.bayi')

@section('title', __('messages.notification_settings'))

@section('panel_content')
<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🔔 <span class="gradient-text">{{ __('messages.notification_settings') }}</span></h1>
</div>

<form action="{{ route('admin.bayi.bildirim.ayarlari.guncelle') }}" method="POST">
    @csrf
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6">
                <div class="section-title">📧 {{ __('messages.email_sms_notifications') }}</div>
                <p class="text-sm text-white/60 mb-5">{{ __('messages.choose_notification_preferences') }}</p>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-4 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
                        <div>
                            <div class="font-semibold">🛒 {{ __('messages.new_sale_notifications') }}</div>
                            <div class="text-xs text-white/60">{{ __('messages.receive_email_new_sale') }}</div>
                        </div>
                        <label class="toggle"><input type="checkbox" name="yeni_satis_email" {{ ($ayarlar->yeni_satis_email ?? 1) ? 'checked' : '' }}><span class="slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
                        <div>
                            <div class="font-semibold">💰 {{ __('messages.payment_approval_notifications') }}</div>
                            <div class="text-xs text-white/60">{{ __('messages.receive_email_payment_approved') }}</div>
                        </div>
                        <label class="toggle"><input type="checkbox" name="odeme_onay_email" {{ ($ayarlar->odeme_onay_email ?? 1) ? 'checked' : '' }}><span class="slider"></span></label>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
                        <div>
                            <div class="font-semibold">📢 {{ __('messages.system_announcements') }}</div>
                            <div class="text-xs text-white/60">{{ __('messages.receive_email_system_updates') }}</div>
                        </div>
                        <label class="toggle"><input type="checkbox" name="sistem_duyuru_email" {{ ($ayarlar->sistem_duyuru_email ?? 1) ? 'checked' : '' }}><span class="slider"></span></label>
                    </div>
                </div>
            </div>
            <div class="glass rounded-2xl p-4 mt-4 flex items-center justify-end">
                <button type="submit" class="btn-y">💾 {{ __('messages.save_settings') }}</button>
            </div>
        </div>
        <div>
            <div class="glass rounded-2xl p-6" style="border-color:rgba(59,130,246,.4)">
                <div class="section-title">💡 {{ __('messages.tip') }}</div>
                <p class="text-sm text-white/80 mb-4">{{ __('messages.customize_notifications') }}</p>
                <ul class="text-sm space-y-2 text-white/70">
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.stay_informed_sales') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.stay_informed_payments') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.stay_informed_announcements') }}</span></li>
                </ul>
            </div>
        </div>
    </div>
</form>
@endsection
