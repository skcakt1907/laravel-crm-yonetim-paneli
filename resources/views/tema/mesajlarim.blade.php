@extends('layouts.panel')

@section('page_title', 'Mesajlarım')

@section('panel_content')
<div class="main-content" style="text-align:center;padding:48px 20px">
    <div style="width:84px;height:84px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:38px;color:#1a2332">
        <i class="fas fa-comments"></i>
    </div>
    <h3 style="margin:0 0 8px;color:#111827;font-weight:700">{{ __('messages.chat_with_management') }}</h3>
    <p style="color:#6b7280;max-width:480px;margin:0 auto 22px;line-height:1.6">
        Yöneticilerimizle birebir, anlık olarak yazışabilirsiniz. Aşağıdaki butona tıklayın
        veya sağ alttaki <strong>mavi mesaj balonunu</strong> {{ __('messages.use_verb') }}
    </p>
    <button type="button" onclick="if(window.dmwToggle)dmwToggle()"
            style="background:#1a2332;color:#fff;border:none;border-radius:10px;padding:12px 26px;font-weight:600;font-size:15px;cursor:pointer;box-shadow:0 4px 12px rgba(26,35,50,.25)">
        <i class="fas fa-comment-dots"></i> {{ __('messages.open_chat') }}
    </button>
</div>

@push('scripts')
<script>
// Sayfa açılınca mesaj widget'ını otomatik aç
document.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){ if(window.dmwToggle) dmwToggle(); }, 350);
});
</script>
@endpush
@endsection
