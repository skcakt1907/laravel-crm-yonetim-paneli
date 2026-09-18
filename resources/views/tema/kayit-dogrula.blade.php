@extends('layouts.master')

@section('title', 'Hesap Doğrulama')

@section('content')
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/bg.jpg') }}); background-size: cover; background-position: center;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading">Hesap Doğrulama</h1>
                    <h3 class="subheading">Kaydınızı tamamlamak için son bir adım</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding:48px 0">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px 28px;box-shadow:0 20px 50px -20px rgba(0,0,0,.18)">

                <div style="text-align:center;margin-bottom:22px">
                    <div style="font-size:44px;line-height:1">{{ ($kanal ?? 'email') === 'sms' ? '📱' : '✉️' }}</div>
                    <p style="margin:12px 0 4px;color:#334155;font-size:15px">
                        @if(($kanal ?? 'email') === 'sms')
                            <strong>{{ $emailMaskeli }}</strong> numarasına <strong>6 haneli</strong> bir doğrulama kodu SMS olarak gönderdik.
                        @else
                            <strong>{{ $emailMaskeli }}</strong> adresine <strong>6 haneli</strong> bir doğrulama kodu gönderdik.
                        @endif
                    </p>
                    <p style="color:#64748b;font-size:13px">
                        Kod 15 dakika geçerlidir.
                        @if(($kanal ?? 'email') !== 'sms') Gelen kutunuzu (ve spam klasörünü) kontrol edin. @endif
                    </p>
                </div>

                {{-- Mesajlar --}}
                @if(session('success'))
                    <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:11px 14px;border-radius:10px;font-size:13.5px;margin-bottom:14px">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:11px 14px;border-radius:10px;font-size:13.5px;margin-bottom:14px">{{ session('error') }}</div>
                @endif
                @error('kod')
                    <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:11px 14px;border-radius:10px;font-size:13.5px;margin-bottom:14px">{{ $message }}</div>
                @enderror

                {{-- Kod formu --}}
                <form method="POST" action="{{ route('kayit.dogrula.post') }}">
                    @csrf
                    <input type="text" name="kod" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                           autofocus required placeholder="______"
                           style="width:100%;text-align:center;font-size:30px;font-weight:800;letter-spacing:14px;padding:14px;border:2px solid #e5e7eb;border-radius:12px;outline:none;color:#111827"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    <button type="submit"
                            style="width:100%;margin-top:16px;background:linear-gradient(135deg,#facc15,#f59e0b);color:#111827;border:none;border-radius:12px;padding:14px;font-weight:800;font-size:16px;cursor:pointer">
                        Doğrula ve Kaydı Tamamla
                    </button>
                </form>

                {{-- Tekrar gönder --}}
                <form method="POST" action="{{ route('kayit.dogrula.tekrar') }}" style="margin-top:14px;text-align:center">
                    @csrf
                    <span style="color:#64748b;font-size:13px">Kod gelmedi mi?</span>
                    <button type="submit" style="background:none;border:none;color:#f59e0b;font-weight:700;font-size:13px;cursor:pointer;text-decoration:underline">Tekrar Gönder</button>
                </form>

                <div style="text-align:center;margin-top:16px;font-size:12.5px">
                    <a href="{{ route('kayit') }}" style="color:#94a3b8;text-decoration:none">← Bilgileri değiştir / yeniden kayıt</a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
