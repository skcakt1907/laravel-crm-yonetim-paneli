@extends('layouts.master')

@section('title', 'Randevu Değerlendirme')

@section('content')
<div style="min-height:72vh;display:flex;align-items:center;justify-content:center;padding:150px 16px 70px">
    <div style="max-width:560px;width:100%;border:1px solid #e5e7eb;border-radius:18px;padding:36px 32px;background:#fff;text-align:center;box-shadow:0 4px 18px rgba(0,0,0,.05)">

        @if($dolduruldu || session('tesekkur'))
            <div style="font-size:52px">🙏</div>
            <h1 style="font-size:24px;font-weight:800;margin:12px 0 8px">{{ __('messages.thanks_excl') }}</h1>
            <p style="color:#6b7280;font-size:15px;margin:0">
                {{ __('messages.feedback_received') }}
            </p>
            @if(!empty($d->puan))
                <div style="font-size:30px;margin-top:14px;letter-spacing:4px">
                    @for($i = 1; $i <= 5; $i++){{ $i <= $d->puan ? '⭐' : '☆' }}@endfor
                </div>
            @endif
        @else
            <div style="font-size:52px">⭐</div>
            <h1 style="font-size:24px;font-weight:800;margin:12px 0 6px">{{ __('messages.how_was_meeting') }}</h1>
            <p style="color:#6b7280;font-size:14.5px;margin:0 0 6px">
                {{ \Carbon\Carbon::parse($d->baslangic)->format('d.m.Y H:i') }} tarihli randevunuz
                @if(!empty($d->calisan_ad)) — <strong>{{ $d->calisan_ad }}</strong> @endif
                @if(!empty($d->lokasyon_ad)) ({{ $d->lokasyon_ad }}) @endif
            </p>

            <form method="POST" action="{{ route('randevu.degerlendirme.kaydet', $token) }}" style="margin-top:22px">
                @csrf

                {{-- Yıldızlar --}}
                <div id="rdvStars" style="display:flex;justify-content:center;gap:6px;font-size:40px;cursor:pointer;user-select:none">
                    @for($i = 1; $i <= 5; $i++)
                        <span data-puan="{{ $i }}" onclick="rdvPuanSec({{ $i }})" style="filter:grayscale(1);opacity:.45;transition:.15s">⭐</span>
                    @endfor
                </div>
                <input type="hidden" name="puan" id="rdvPuan" value="">
                <div id="rdvPuanText" style="color:#9ca3af;font-size:13px;margin-top:6px">{{ __('messages.tap_star_hint') }}</div>
                @error('puan')<div style="color:#dc2626;font-size:13px;margin-top:4px">{{ __('messages.select_rating_1_5') }}</div>@enderror

                <textarea name="yorum" rows="4" maxlength="2000" placeholder="{{ __('messages.additional_comments_placeholder') }}"
                          style="width:100%;margin-top:18px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:inherit;resize:vertical">{{ old('yorum') }}</textarea>

                <button type="submit"
                        style="margin-top:16px;background:#b8b62e;color:#1f2937;font-weight:800;font-size:15px;border:0;border-radius:12px;padding:13px 34px;cursor:pointer">
                    {{ __('messages.submit') }}
                </button>
            </form>

            <script>
            const rdvText = {1:'😞 Hiç memnun kalmadım',2:'🙁 Memnun kalmadım',3:'😐 İdare eder',4:'🙂 Memnun kaldım',5:'🤩 Çok memnun kaldım'};
            function rdvPuanSec(p){
                document.getElementById('rdvPuan').value = p;
                document.getElementById('rdvPuanText').textContent = rdvText[p];
                document.querySelectorAll('#rdvStars span').forEach(s => {
                    const aktif = parseInt(s.dataset.puan) <= p;
                    s.style.filter = aktif ? 'none' : 'grayscale(1)';
                    s.style.opacity = aktif ? '1' : '.45';
                });
            }
            </script>
        @endif

    </div>
</div>
@endsection