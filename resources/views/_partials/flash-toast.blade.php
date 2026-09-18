{{-- ══════════════════════════════════════════════════════════════
     SİTE GENELİ TEK POPUP (TOAST) TEMASI
     Başarı / hata / uyarı / bilgi — master, panel(→master), admin ve bayi
     layout'larının HEPSİ bunu @include eder. Tek tasarım, tek yer.
     session('success'|'error'|'warning'|'info') + $errors + ?hosgeldin.
     ══════════════════════════════════════════════════════════════ --}}
<style>
    .global-notifications { position: fixed; top: 24px; right: 24px; z-index: 99999; max-width: 420px; width: calc(100% - 48px); display: flex; flex-direction: column; gap: 12px; pointer-events: none; }
    .global-notifications .gn-toast { pointer-events: auto; }
    .gn-toast { position: relative; display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px 14px 14px; border-radius: 14px; background: #fff; border: 1px solid rgba(0,0,0,.06); box-shadow: 0 12px 32px rgba(17, 24, 39, 0.12); overflow: hidden; animation: gnSlideInRight .35s cubic-bezier(.22,.61,.36,1); font-family:'Poppins',system-ui,sans-serif; }
    .gn-toast::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; border-radius: 14px 0 0 14px; }
    .gn-toast.success::before { background: linear-gradient(180deg, #10b981, #059669); }
    .gn-toast.error::before   { background: linear-gradient(180deg, #ef4444, #dc2626); }
    .gn-toast.warning::before { background: linear-gradient(180deg, #f59e0b, #d97706); }
    .gn-toast.info::before    { background: linear-gradient(180deg, #3b82f6, #1d4ed8); }
    .gn-icon { width: 42px; height: 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 22px; flex-shrink: 0; }
    .gn-toast.success .gn-icon { background: linear-gradient(135deg, #10b981, #059669); }
    .gn-toast.error   .gn-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .gn-toast.warning .gn-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .gn-toast.info    .gn-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .gn-body { flex: 1; min-width: 0; padding-right: 18px; }
    .gn-title { font-weight: 700; font-size: 14px; color: #111827; margin: 0 0 2px; }
    .gn-message { font-size: 13px; color: #4b5563; line-height: 1.45; word-wrap: break-word; }
    .gn-close { position: absolute; top: 8px; right: 10px; background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 18px; line-height: 1; padding: 4px 6px; border-radius: 6px; transition: background .15s, color .15s; }
    .gn-close:hover { background: #f3f4f6; color: #111827; }
    .gn-progress { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: rgba(0,0,0,.06); }
    .gn-progress > span { display: block; height: 100%; width: 100%; transform-origin: left center; animation: gnProgress 5s linear forwards; }
    .gn-toast.success .gn-progress > span { background: #10b981; }
    .gn-toast.error   .gn-progress > span { background: #ef4444; }
    .gn-toast.warning .gn-progress > span { background: #f59e0b; }
    .gn-toast.info    .gn-progress > span { background: #3b82f6; }
    @keyframes gnProgress { from { transform: scaleX(1); } to { transform: scaleX(0); } }
    @keyframes gnSlideInRight { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes gnSlideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(110%); opacity: 0; } }
    @media (max-width: 576px) { .global-notifications { top: 12px; right: 12px; left: 12px; width: auto; max-width: 100%; } }
</style>
<div class="global-notifications" id="globalNotifications">
    @if(request('hosgeldin'))
        <div class="gn-toast success" role="alert">
            <div class="gn-icon"><i class="mdi mdi-check-circle-outline"></i></div>
            <div class="gn-body"><div class="gn-title">{{ __('messages.success') }}</div><div class="gn-message">{{ __('messages.welcome_excl') }}</div></div>
            <button type="button" class="gn-close" onclick="this.parentElement.remove()">&times;</button>
            <div class="gn-progress"><span></span></div>
        </div>
    @endif
    @if(session('error'))
        <div class="gn-toast error" role="alert">
            <div class="gn-icon"><i class="mdi mdi-alert-circle-outline"></i></div>
            <div class="gn-body"><div class="gn-title">{{ __('messages.something_went_wrong') }}</div><div class="gn-message">{{ session('error') }}</div></div>
            <button type="button" class="gn-close" onclick="this.parentElement.remove()">&times;</button>
            <div class="gn-progress"><span></span></div>
        </div>
    @endif
    @if(!session('error') && $errors->any())
        <div class="gn-toast error" role="alert">
            <div class="gn-icon"><i class="mdi mdi-alert-circle-outline"></i></div>
            <div class="gn-body"><div class="gn-title">Formda hatalar var</div><div class="gn-message">{{ $errors->first() }}</div></div>
            <button type="button" class="gn-close" onclick="this.parentElement.remove()">&times;</button>
            <div class="gn-progress"><span></span></div>
        </div>
    @endif
    @if(session('success'))
        <div class="gn-toast success" role="alert">
            <div class="gn-icon"><i class="mdi mdi-check-circle-outline"></i></div>
            <div class="gn-body"><div class="gn-title">{{ __('messages.success') }}</div><div class="gn-message">{{ session('success') }}</div></div>
            <button type="button" class="gn-close" onclick="this.parentElement.remove()">&times;</button>
            <div class="gn-progress"><span></span></div>
        </div>
    @endif
    @if(session('warning'))
        <div class="gn-toast warning" role="alert">
            <div class="gn-icon"><i class="mdi mdi-alert-outline"></i></div>
            <div class="gn-body"><div class="gn-title">Dikkat</div><div class="gn-message">{{ session('warning') }}</div></div>
            <button type="button" class="gn-close" onclick="this.parentElement.remove()">&times;</button>
            <div class="gn-progress"><span></span></div>
        </div>
    @endif
    @if(session('info'))
        <div class="gn-toast info" role="alert">
            <div class="gn-icon"><i class="mdi mdi-information-outline"></i></div>
            <div class="gn-body"><div class="gn-title">{{ __('messages.info_title') }}</div><div class="gn-message">{{ session('info') }}</div></div>
            <button type="button" class="gn-close" onclick="this.parentElement.remove()">&times;</button>
            <div class="gn-progress"><span></span></div>
        </div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#globalNotifications .gn-toast').forEach(function (t) {
            setTimeout(function () {
                t.style.animation = 'gnSlideOutRight .35s cubic-bezier(.22,.61,.36,1) forwards';
                setTimeout(function () { t.remove(); }, 350);
            }, 5000);
        });
    });
</script>
