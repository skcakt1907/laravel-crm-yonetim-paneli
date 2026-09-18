@extends('layouts.bayi')

@section('title', 'Öner-Kazan')
@section('page_title', 'Öner-Kazan')

@section('panel_content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">🤝 Öner-Kazan</h1>
    <p class="text-white/60 text-sm mt-1">
        Bir iş ortağı önerin; o satış yaptıkça <strong class="text-yellow-400">komisyonunun %{{ (int) $pay }}'si</strong>
        kadar siz de kazanın. Süresiz — her satışında kazanmaya devam edersiniz.
    </p>
</div>

@if(session('success'))
    <div class="glass rounded-xl p-4 mb-5" style="border-color:rgba(34,197,94,.5)">
        <div class="text-emerald-300 text-sm">✓ {{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="glass rounded-xl p-4 mb-5" style="border-color:rgba(244,63,94,.5)">
        <div class="text-rose-300 text-sm">✗ {{ session('error') }}</div>
    </div>
@endif
@if($errors->any())
    <div class="glass rounded-xl p-4 mb-5" style="border-color:rgba(244,63,94,.5)">
        <ul class="text-rose-300 text-sm list-disc pl-5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- Özet kutuları --}}
<div class="grid gap-4 mb-6" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
    <div class="glass rounded-2xl p-5">
        <div class="text-white/60 text-xs uppercase tracking-wide mb-1">Toplam Öner-Kazan Geliri</div>
        <div class="text-2xl font-bold text-yellow-400">{{ number_format($toplamKazanc, 2, ',', '.') }} <span class="text-sm">DN Coin</span></div>
    </div>
    <div class="glass rounded-2xl p-5">
        <div class="text-white/60 text-xs uppercase tracking-wide mb-1">Önerdiğiniz İş Ortağı</div>
        <div class="text-2xl font-bold">{{ $onerilenler->count() }}</div>
    </div>
    <div class="glass rounded-2xl p-5">
        <div class="text-white/60 text-xs uppercase tracking-wide mb-1">Pay Oranınız</div>
        <div class="text-2xl font-bold text-emerald-400">%{{ (int) $pay }}</div>
        <div class="text-white/40 text-xs mt-1">önerdiğiniz bayinin komisyonundan</div>
    </div>
</div>

@if($bizimOneren)
<div class="glass rounded-xl p-4 mb-6" style="border-color:rgba(184,182,46,.4)">
    <div class="text-sm text-white/80">
        Sizi <strong>{{ $bizimOneren->firma_adi ?: $bizimOneren->bayi_kodu }}</strong> önerdi.
        Bu, sizin kazancınızı <strong>etkilemez</strong> — payı biz karşılıyoruz.
    </div>
</div>
@endif

<div class="grid gap-6" style="grid-template-columns:minmax(0,1fr) minmax(0,340px)">

    {{-- SOL: önerilen bayiler + kazançlar --}}
    <div>
        <div class="glass rounded-2xl p-6 mb-6">
            <div class="section-title mb-4">👥 Önerdiğiniz İş Ortakları</div>

            @if($onerilenler->isEmpty())
                <p class="text-white/50 text-sm">
                    Henüz kimseyi önermediniz. Sağdaki formdan davet gönderebilirsiniz.
                </p>
            @else
                <div style="overflow-x:auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-white/50 text-xs uppercase">
                                <th class="text-left py-2">İş Ortağı</th>
                                <th class="text-left py-2">Tip</th>
                                <th class="text-left py-2">Durum</th>
                                <th class="text-right py-2">Kazandırdığı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($onerilenler as $o)
                            <tr style="border-top:1px solid rgba(255,255,255,.08)">
                                <td class="py-3">
                                    <div class="font-semibold">{{ $o->firma_adi ?: ($o->kisi_adi ?: 'Bayi #' . $o->bayi_id) }}</div>
                                    <div class="text-white/40 text-xs">{{ $o->bayi_kodu ?? '—' }}</div>
                                </td>
                                <td class="py-3 text-white/70">
                                    {{ ($o->bayi_tipi ?? 'internet') === 'partner' ? 'Ofis Partneri' : 'İnternet Bayisi' }}
                                </td>
                                <td class="py-3">
                                    @if($o->durum === 'aktif')
                                        <span class="text-emerald-400">● Aktif</span>
                                    @elseif($o->durum === 'beklemede')
                                        <span class="text-yellow-400">● Bekliyor</span>
                                    @else
                                        <span class="text-white/40">● İptal</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right font-semibold text-yellow-400">
                                    {{ number_format((float) $o->toplam_kazanc, 2, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="section-title mb-4">💰 Son Kazançlar</div>
            @if($kazanclar->isEmpty())
                <p class="text-white/50 text-sm">Önerdiğiniz iş ortakları satış yaptıkça kazançlarınız burada görünür.</p>
            @else
                <div style="overflow-x:auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-white/50 text-xs uppercase">
                                <th class="text-left py-2">Tarih</th>
                                <th class="text-left py-2">Açıklama</th>
                                <th class="text-right py-2">Kazanç</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kazanclar as $k)
                            <tr style="border-top:1px solid rgba(255,255,255,.08)">
                                <td class="py-3 text-white/60">
                                    {{ $k->created_at ? \Illuminate\Support\Carbon::parse($k->created_at)->format('d.m.Y') : '—' }}
                                </td>
                                <td class="py-3 text-white/80">{{ $k->aciklama ?: 'Öner-Kazan payı' }}</td>
                                <td class="py-3 text-right font-semibold text-yellow-400">
                                    +{{ number_format((float) $k->tutar, 2, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- SAĞ: davet formu --}}
    <div>
        <div class="glass rounded-2xl p-6 mb-4" style="border-color:rgba(184,182,46,.45)">
            <div class="section-title mb-4">✉️ İş Ortağı Öner</div>

            <form method="POST" action="{{ route('admin.bayi.oner.kazan.davet') }}">
                @csrf
                <div class="mb-3">
                    <label class="field-label">Ad Soyad <span class="text-rose-300">*</span></label>
                    <input type="text" name="ad_soyad" value="{{ old('ad_soyad') }}" required maxlength="190">
                </div>
                <div class="mb-3">
                    <label class="field-label">E-posta <span class="text-rose-300">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="190">
                </div>
                <div class="mb-4">
                    <label class="field-label">Mesajınız</label>
                    <textarea name="not" rows="3" maxlength="500" placeholder="İsteğe bağlı kısa bir not">{{ old('not') }}</textarea>
                </div>
                <button type="submit" class="w-full py-3 rounded-xl font-bold"
                        style="background:#b8b62e;color:#1a1a0e">Daveti Gönder</button>
            </form>
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="section-title mb-3">💡 Nasıl İşliyor?</div>
            <ol class="text-sm text-white/70 space-y-3 pl-5" style="list-style:decimal">
                <li>Tanıdığınız kişiye davet gönderirsiniz.</li>
                <li>Kişi başvurusunu yapar, ekibimiz onaylar.</li>
                <li>Öneri bağınız kurulur — biz sizi eşleştiririz.</li>
                <li>O her satış yaptığında, komisyonunun <strong class="text-yellow-400">%{{ (int) $pay }}'si</strong> hesabınıza DN Coin olarak yatar.</li>
                <li>Bu <strong>süresizdir</strong>; onun kazancı da azalmaz.</li>
            </ol>
        </div>
    </div>
</div>
@endsection
