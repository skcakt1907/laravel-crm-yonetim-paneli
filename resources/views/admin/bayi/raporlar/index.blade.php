@extends('layouts.bayi')

@section('title', __('messages.my_reports'))

@section('panel_content')
<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">📊 <span class="gradient-text">{{ __('messages.my_reports') }}</span></h1>
</div>

<div class="grid md:grid-cols-2 gap-4 mb-8">
    @foreach($raporlar as $key => $baslik)
    @php
        $desc = match($key) {
            'satis' => __('messages.all_sales_detail_report'),
            'kazanc' => __('messages.monthly_earnings_commission_report'),
            'musteri' => __('messages.customer_list_sales_stats'),
            default => __('messages.payment_history_requests'),
        };
        $emoji = match($key) {
            'satis' => '🛒', 'kazanc' => '💰', 'musteri' => '👥', default => '💳'
        };
    @endphp
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="text-3xl">{{ $emoji }}</div>
            <h3 class="font-display text-lg font-bold flex-1">{{ $baslik }}</h3>
        </div>
        <p class="text-sm text-white/60 mb-4">{{ $desc }}</p>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.bayi.rapor.pdf', $key) }}" target="_blank" class="btn-o" style="padding:8px 14px;font-size:13px;border-color:rgba(239,68,68,.4);color:#fca5a5">📄 {{ __('messages.download_pdf') }}</a>
            <a href="{{ route('admin.bayi.rapor.excel', $key) }}" class="btn-o" style="padding:8px 14px;font-size:13px;border-color:rgba(16,185,129,.4);color:#6ee7b7">📗 {{ __('messages.download_excel') }}</a>
        </div>
    </div>
    @endforeach
</div>

<div class="glass rounded-2xl p-6">
    <div class="section-title">🔍 {{ __('messages.create_custom_report') }}</div>
    <form action="{{ route('admin.bayi.rapor.pdf', 'ozel') }}" method="GET" target="_blank">
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="field-label">{{ __('messages.report_type') }}</label>
                <select name="tip">
                    <option value="satis" style="background:#1a1a1a">{{ __('messages.sales_report') }}</option>
                    <option value="kazanc" style="background:#1a1a1a">{{ __('messages.earnings_report') }}</option>
                    <option value="musteri" style="background:#1a1a1a">{{ __('messages.customer_report') }}</option>
                    <option value="odeme" style="background:#1a1a1a">{{ __('messages.payment_report') }}</option>
                </select>
            </div>
            <div>
                <label class="field-label">{{ __('messages.start_date') }}</label>
                <input type="date" name="baslangic">
            </div>
            <div>
                <label class="field-label">{{ __('messages.end_date') }}</label>
                <input type="date" name="bitis">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-y w-full">📥 {{ __('messages.generate_report') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
