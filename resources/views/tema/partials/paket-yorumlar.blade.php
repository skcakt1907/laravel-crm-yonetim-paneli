{{-- Paket değerlendirmeleri: puan özeti + onaylı yorumlar + yorum formu.
     Beklenen: $paket, $yorumlar, $puanOzet['ortalama'|'adet'|'dagilim'], $yorumYaptiMi --}}
@php
    $_ort   = $puanOzet['ortalama'] ?? null;
    $_adet  = (int) ($puanOzet['adet'] ?? 0);
    $_dag   = $puanOzet['dagilim'] ?? [];
    $_etiket = $_ort >= 4.5 ? __('messages.rating_excellent')
             : ($_ort >= 4 ? __('messages.rating_verygood')
             : ($_ort >= 3 ? __('messages.rating_good')
             : ($_ort ? __('messages.rating_average') : '')));

    // Yıldız seçicinin JS etiketleri (dile göre)
    $_puanAdlari = json_encode([
        1 => __('messages.rating_poor'),
        2 => __('messages.rating_fair'),
        3 => __('messages.rating_good'),
        4 => __('messages.rating_verygood'),
        5 => __('messages.rating_excellent'),
    ], JSON_UNESCAPED_UNICODE);
@endphp

<div class="container" id="degerlendirmeler" style="margin-top:10px; margin-bottom:44px;">
    <h2 style="font-size:24px; font-weight:800; color:#1a1a1a; margin:0 0 6px;">{{ __('messages.reviews') }}</h2>
    <p style="color:#999; font-size:14px; margin:0 0 22px;">
        {{ $_adet > 0 ? __('messages.reviews_sub') : __('messages.no_reviews_for_package') }}
    </p>

    {{-- Bildirimler --}}
    @if(session('yorum_ok'))
        <div style="display:flex;align-items:center;gap:10px;background:#f2f7ee;border:1px solid #cfe3c0;color:#3f6b28;border-radius:12px;padding:13px 16px;margin-bottom:18px;font-size:14px;">
            <i class="mdi mdi-check-circle" style="font-size:19px;"></i> {{ session('yorum_ok') }}
        </div>
    @endif
    @if(session('yorum_hata'))
        <div style="display:flex;align-items:center;gap:10px;background:#fdf3f2;border:1px solid #f0cfcb;color:#a3402f;border-radius:12px;padding:13px 16px;margin-bottom:18px;font-size:14px;">
            <i class="mdi mdi-alert-circle" style="font-size:19px;"></i> {{ session('yorum_hata') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background:#fdf3f2;border:1px solid #f0cfcb;color:#a3402f;border-radius:12px;padding:13px 16px;margin-bottom:18px;font-size:14px;">
            @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="row">
        {{-- Sol: puan özeti --}}
        <div class="col-lg-4 col-md-12 mb-4">
            <div style="background:#fbfbf7;border:1px solid #ececec;border-radius:16px;padding:24px;">
                @if($_ort)
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                        <div style="font-size:44px;font-weight:800;color:#1a1a1a;line-height:1;letter-spacing:-.03em;">
                            {{ number_format($_ort, 1, ',', '') }}
                        </div>
                        <div>
                            <div style="color:#e0b400;font-size:16px;letter-spacing:1px;">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="mdi mdi-star{{ $i <= round($_ort) ? '' : '-outline' }}"></i>
                                @endfor
                            </div>
                            <div style="font-size:13px;color:#666;margin-top:3px;">
                                <b style="color:#1a1a1a;">{{ $_etiket }}</b> · {{ __('messages.n_reviews_count', ['adet' => $_adet]) }}
                            </div>
                        </div>
                    </div>

                    {{-- Yıldız dağılımı --}}
                    @for($s = 5; $s >= 1; $s--)
                        @php
                            $_n = (int) ($_dag[$s] ?? 0);
                            $_yuzde = $_adet ? round($_n / $_adet * 100) : 0;
                        @endphp
                        <div style="display:flex;align-items:center;gap:9px;margin-bottom:6px;">
                            <span style="font-size:12px;color:#888;width:26px;white-space:nowrap;">{{ $s }} <i class="mdi mdi-star" style="font-size:11px;color:#e0b400;"></i></span>
                            <div style="flex:1;height:7px;background:#ececec;border-radius:99px;overflow:hidden;">
                                <div style="width:{{ $_yuzde }}%;height:100%;background:#b8b62e;border-radius:99px;"></div>
                            </div>
                            <span style="font-size:12px;color:#999;width:20px;text-align:right;font-variant-numeric:tabular-nums;">{{ $_n }}</span>
                        </div>
                    @endfor
                @else
                    <div style="text-align:center;padding:14px 0;">
                        <i class="mdi mdi-star-outline" style="font-size:40px;color:#dcdcd2;"></i>
                        <div style="font-size:14px;color:#999;margin-top:8px;">{{ __('messages.no_rating_yet') }}</div>
                        <div style="font-size:12.5px;color:#bbb;margin-top:3px;">{{ __('messages.be_first_reviewer') }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sağ: yorumlar + form --}}
        <div class="col-lg-8 col-md-12">
            {{-- Onaylı yorumlar --}}
            @forelse($yorumlar as $y)
                @php
                    $_harf = mb_strtoupper(mb_substr($y->adi ?: 'Ü', 0, 1, 'UTF-8'), 'UTF-8');
                    $_zaman = null;
                    try { $_zaman = \Carbon\Carbon::parse($y->tarih)->diffForHumans(); } catch (\Throwable $e) {}
                @endphp
                <div style="background:#fff;border:1px solid #ececec;border-radius:14px;padding:17px 19px;margin-bottom:12px;">
                    <div style="display:flex;align-items:center;gap:11px;margin-bottom:9px;">
                        <div style="width:38px;height:38px;border-radius:50%;background:#b8b62e;color:#1a1a0e;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0;">{{ $_harf }}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:14px;color:#1a1a1a;">{{ $y->adi }}</div>
                            @if($_zaman)<div style="font-size:11.5px;color:#aaa;">{{ $_zaman }}</div>@endif
                        </div>
                        @if($y->puan)
                            <div style="color:#e0b400;font-size:13px;letter-spacing:.5px;flex-shrink:0;">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="mdi mdi-star{{ $i <= $y->puan ? '' : '-outline' }}"></i>
                                @endfor
                            </div>
                        @endif
                    </div>
                    <p style="margin:0;font-size:14px;line-height:1.65;color:#555;">{{ $y->yorum }}</p>
                </div>
            @empty
                @if($_adet === 0)
                    <div style="background:#fff;border:1px dashed #dcdcd2;border-radius:14px;padding:26px;text-align:center;color:#aaa;font-size:14px;margin-bottom:12px;">
                        {{ __('messages.be_first_comment') }}
                    </div>
                @endif
            @endforelse

            {{-- Form --}}
            @auth('uye')
                @if($yorumYaptiMi)
                    <div style="background:#fbfbf7;border:1px solid #ececec;border-radius:14px;padding:17px 19px;font-size:14px;color:#666;display:flex;align-items:center;gap:9px;">
                        <i class="mdi mdi-check-circle" style="color:#b8b62e;font-size:18px;"></i>
                        {{ __('messages.already_reviewed_note') }}
                    </div>
                @else
                    <div style="background:#fff;border:1px solid #ececec;border-radius:14px;padding:20px 22px;margin-top:16px;">
                        <div style="font-weight:800;font-size:15px;color:#1a1a1a;margin-bottom:14px;">{{ __('messages.review_this_package') }}</div>
                        <form action="{{ route('yorum.ekle') }}" method="POST">
                            @csrf
                            <input type="hidden" name="icerik_id" value="{{ $paket->id }}">

                            {{-- Yıldız seçici --}}
                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-size:13px;color:#666;margin-bottom:7px;">{{ __('messages.your_rating') }}</label>
                                <div id="puanSecici" style="display:inline-flex;gap:4px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" class="puan-yildiz" data-puan="{{ $i }}" aria-label="{{ $i }} yıldız"
                                                style="background:none;border:none;padding:0 2px;cursor:pointer;font-size:28px;line-height:1;color:#dcdcd2;transition:color .12s;">
                                            <i class="mdi mdi-star"></i>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="puan" id="puanDeger" value="{{ old('puan') }}">
                                <span id="puanEtiket" style="font-size:13px;color:#999;margin-left:10px;"></span>
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-size:13px;color:#666;margin-bottom:7px;">{{ __('messages.your_review') }}</label>
                                <textarea name="yorum" rows="4" maxlength="1000" required
                                          placeholder="{{ __('messages.review_placeholder') }}"
                                          style="width:100%;padding:13px 15px;font-size:14px;line-height:1.6;border:1px solid #e0e0d8;border-radius:11px;background:#fbfbf7;color:#1a1a1a;outline:none;resize:vertical;font-family:inherit;"
                                          onfocus="this.style.borderColor='#b8b62e';this.style.background='#fff';"
                                          onblur="this.style.borderColor='#e0e0d8';this.style.background='#fbfbf7';">{{ old('yorum') }}</textarea>
                            </div>

                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <span style="font-size:12px;color:#aaa;">{{ __('messages.review_moderation_note') }}</span>
                                <button type="submit" style="padding:12px 28px;font-size:14px;font-weight:700;background:#b8b62e;color:#1a1a0e;border:none;border-radius:11px;cursor:pointer;">
                                    <i class="mdi mdi-send"></i> {{ __('messages.send') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <script>
                    (function () {
                        var secici = document.getElementById('puanSecici');
                        if (!secici) return;
                        var deger  = document.getElementById('puanDeger');
                        var etiket = document.getElementById('puanEtiket');
                        var adlar  = {!! $_puanAdlari !!};
                        var yildiz = secici.querySelectorAll('.puan-yildiz');

                        function boya(n) {
                            yildiz.forEach(function (b, i) {
                                b.style.color = (i < n) ? '#e0b400' : '#dcdcd2';
                            });
                            etiket.textContent = n ? adlar[n] : '';
                        }
                        yildiz.forEach(function (b) {
                            var p = parseInt(b.dataset.puan, 10);
                            b.addEventListener('mouseenter', function () { boya(p); });
                            b.addEventListener('click', function () { deger.value = p; boya(p); });
                        });
                        secici.addEventListener('mouseleave', function () { boya(parseInt(deger.value, 10) || 0); });
                        boya(parseInt(deger.value, 10) || 0);   // old() ile geri gelen puan
                    })();
                    </script>
                @endif
            @else
                <div style="background:#fbfbf7;border:1px solid #ececec;border-radius:14px;padding:20px;text-align:center;margin-top:16px;">
                    <div style="font-size:14px;color:#666;margin-bottom:12px;">{{ __('messages.login_to_review') }}</div>
                    <a href="{{ localized_route('giris') }}"
                       style="display:inline-flex;align-items:center;gap:7px;padding:11px 24px;border-radius:11px;background:#b8b62e;color:#1a1a0e;font-weight:700;font-size:14px;text-decoration:none;">
                        <i class="mdi mdi-login"></i> {{ __('messages.login') }}
                    </a>
                </div>
            @endauth
        </div>
    </div>
</div>
