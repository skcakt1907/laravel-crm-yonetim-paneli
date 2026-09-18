@extends('admin._layout')

@section('title', 'Durum Panosu')

@push('head')
<style>
    .dp-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .dp-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .dp-head p{margin:4px 0 0;font-size:13px;color: var(--text-muted)}

    /* ── Kendi durumum ── */
    .dp-ben{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:18px;margin-bottom:20px}
    .dp-ben h2{font-size:14px;font-weight:800;margin:0 0 14px;color: var(--text)}
    .dp-secim{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
    .dp-tip{display:inline-flex;align-items:center;gap:7px;padding:9px 15px;border-radius: 22px;border: 1.5px solid var(--border);
            background: var(--surface);cursor:pointer;font-size:13.5px;font-weight:600;color: var(--text-secondary);transition:.15s}
    .dp-tip:hover{border-color: var(--brand)}
    .dp-tip input{display:none}
    .dp-tip.secili{color: var(--text);font-weight:700}
    .dp-tip .em{font-size:16px;line-height:1}
    .dp-alt{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .dp-alt input[type=text]{flex:1;min-width:220px}

    /* ── Personel listesi ── */
    .dp-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(270px,1fr))}
    .dp-kisi{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:14px;display:flex;gap:12px;align-items:flex-start}
    .dp-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0;background: var(--bg-subtle)}
    .dp-avatar-bos{width:42px;height:42px;border-radius:50%;flex-shrink:0;background: var(--bg-subtle);
                   display:flex;align-items:center;justify-content:center;font-weight:800;color: var(--text-muted);font-size:15px}
    .dp-bilgi{min-width:0;flex:1}
    .dp-ad{font-weight:700;font-size:14px;color: var(--text);display:flex;align-items:center;gap:6px}
    /* Otomatik durum noktasi: "bilgisayari acik mi" -- elle secilen durumdan AYRI */
    .dp-nokta{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .dp-durum{display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:4px 11px;border-radius: 20px;
              font-size:12.5px;font-weight:700}
    .dp-not{font-size:12px;color: var(--text-muted);margin-top:5px;word-break:break-word}
    .dp-sure{font-size:11px;color: var(--text-muted);margin-top:4px}
    .dp-bos{color: var(--text-muted);font-size:12.5px;margin-top:6px;font-style:italic}

    /* ── Yonetici kutulari ── */
    .dp-yonetim{margin-top:24px}
    .dp-kutu{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;margin-bottom:14px;overflow:hidden}
    .dp-kutu-bas{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 18px;cursor:pointer;
                 border-bottom: 1px solid transparent}
    .dp-kutu-bas h2{font-size:14px;font-weight:800;margin:0;color: var(--text)}
    .dp-kutu-bas small{font-size:12px;color: var(--text-muted);font-weight:500}
    .dp-kutu-ic{padding:0 18px 18px;border-top: 1px solid var(--border)}
    .dp-tablo{width:100%;border-collapse:collapse;margin-top:14px}
    .dp-tablo th{text-align:left;font-size:11.5px;font-weight:700;color: var(--text-muted);text-transform:uppercase;
                 letter-spacing:.4px;padding:10px 8px;border-bottom: 1px solid var(--border)}
    .dp-tablo td{padding:10px 8px;border-bottom: 1px solid var(--border);font-size:13px;color: var(--text-secondary)}
    .dp-tablo tr:last-child td{border-bottom:none}
    .dp-mini{padding:6px 10px;font-size:12.5px;border-radius:9px;border:1px solid var(--border);background: var(--surface);color: var(--text)}
    .dp-alici{display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid var(--border);border-radius:10px;
              cursor:pointer;font-size:13px}
    .dp-alici-liste{display:flex;flex-wrap:wrap;gap:8px}
    .dp-ipucu{font-size:12px;color: var(--text-muted);display:flex;align-items:center;gap:6px}
</style>
@endpush

@section('content')
<div class="dp-head">
    <div>
        <h1>Durum Panosu</h1>
        <p>Kim ne yapıyor. Herkes kendi durumunu değiştirir, değişince 3 yöneticiye bildirim gider.</p>
    </div>
    @if(Route::has('admin.hrm.durum.gecmis'))
        <a href="{{ route('admin.hrm.durum.gecmis') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="history"></i> <span>Geçmiş</span>
        </a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">{{ $errors->first() }}</div>
@endif

{{-- ══ KENDİ DURUMUM ══════════════════════════════════════════════ --}}
@php
    $ben = $personeller->firstWhere('id', $aktifId);
    $benimTip = null;
    if ($ben && $ben->durum_adi) {
        $benimTip = $tipler->firstWhere('ad', $ben->durum_adi)?->id;
    }
@endphp

<form method="POST" action="{{ route('admin.hrm.durum.degistir') }}" class="dp-ben">
    @csrf
    <h2>Şu an ne yapıyorsun?</h2>

    <div class="dp-secim">
        @foreach($tipler as $tip)
            <label class="dp-tip {{ $benimTip == $tip->id ? 'secili' : '' }}"
                   data-renk="{{ $tip->renk }}"
                   style="{{ $benimTip == $tip->id ? 'border-color:'.$tip->renk.';background:'.$tip->renk.'1a' : '' }}">
                <input type="radio" name="durum_tipi_id" value="{{ $tip->id }}"
                       @checked($benimTip == $tip->id)>
                <span class="em">{{ $tip->emoji }}</span>
                <span>{{ $tip->ad }}</span>
            </label>
        @endforeach
    </div>

    <div class="dp-alt">
        <input type="text" name="not" class="form-input" maxlength="200"
               placeholder="İsteğe bağlı not — örn. Vidal Dent çekimi"
               value="{{ $ben->durum_notu ?? '' }}">
        <button type="submit" class="btn btn-primary">Durumu güncelle</button>
    </div>
</form>

{{-- ANLIK GERİ BİLDİRİM
     Radyo düğmesi gizli (display:none) ve seçili görünümü sunucu
     basıyordu; tıklayınca ekranda HİÇBİR ŞEY değişmiyordu. Kullanıcı
     haklı olarak "buton çalışmıyor" sanıyor, oysa seçim yapılmış
     oluyor, yalnızca görünmüyordu.
     Renk her durumun kendi rengi -- sunucunun bastığıyla aynı. --}}
<script>
(function () {
    var kutu = document.querySelector('.dp-ben .dp-secim');
    if (!kutu) return;

    kutu.addEventListener('change', function (e) {
        if (!e.target.matches('input[name="durum_tipi_id"]')) return;

        kutu.querySelectorAll('.dp-tip').forEach(function (et) {
            et.classList.remove('secili');
            et.style.borderColor = '';
            et.style.background = '';
        });

        var secilen = e.target.closest('.dp-tip');
        if (!secilen) return;

        var renk = secilen.dataset.renk || '';
        secilen.classList.add('secili');
        if (renk) {
            secilen.style.borderColor = renk;
            secilen.style.background = renk + '1a';
        }
    });
})();
</script>

{{-- ══ HERKES ═════════════════════════════════════════════════════ --}}
<div class="dp-grid">
    @foreach($personeller as $kisi)
        @php
            // Otomatik durum (tarayici sinyali) -- elle secilen durumdan AYRI bir bilgi
            $oto = \App\Http\Controllers\Admin\PresenceController::durum($kisi->son_gorulme, $kisi->son_etkinlik);
            $otoBilgi = \App\Http\Controllers\Admin\PresenceController::durumBilgi($oto);
        @endphp
        <div class="dp-kisi">
            @if($kisi->profil_foto)
                <img src="{{ asset('tema/uploads/profil/'.$kisi->profil_foto) }}" alt="" class="dp-avatar">
            @else
                <div class="dp-avatar-bos">{{ mb_strtoupper(mb_substr($kisi->adi ?: $kisi->kullaniciadi, 0, 1)) }}</div>
            @endif

            <div class="dp-bilgi">
                <div class="dp-ad">
                    <span class="dp-nokta" style="background:{{ $otoBilgi['renk'] }}"
                          title="{{ $otoBilgi['etiket'] }}"></span>
                    {{ $kisi->adi ?: $kisi->kullaniciadi }}
                </div>

                @if($kisi->durum_adi)
                    <span class="dp-durum"
                          style="background:{{ $kisi->durum_renk }}1a;color:{{ $kisi->durum_renk }}">
                        {{ $kisi->durum_emoji }} {{ $kisi->durum_adi }}
                    </span>
                    @if($kisi->durum_notu)
                        <div class="dp-not">{{ $kisi->durum_notu }}</div>
                    @endif
                    <div class="dp-sure">{{ \Carbon\Carbon::parse($kisi->durum_baslangic)->diffForHumans() }}</div>
                @else
                    <div class="dp-bos">Durum girilmemiş</div>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- ══ YÖNETİCİ AYARLARI ══════════════════════════════════════════ --}}
@if($yonetici)
<div class="dp-yonetim">

    {{-- Durum listesi --}}
    <div class="dp-kutu">
        <div class="dp-kutu-bas" onclick="dpAc('dpTipler')">
            <h2>Durum listesi</h2>
            <small>Panelde seçilebilen durumlar — ekle, düzenle, kaldır</small>
        </div>
        <div class="dp-kutu-ic" id="dpTipler" hidden>
            <table class="dp-tablo">
                <thead>
                    <tr>
                        <th style="width:70px">Emoji</th>
                        <th>Ad</th>
                        <th style="width:110px">Renk</th>
                        <th style="width:80px">Sıra</th>
                        <th style="width:90px">Mail</th>
                        <th style="width:90px">Listede</th>
                        <th style="width:150px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tiplerHepsi as $tip)
                        <tr>
                            <form method="POST" action="{{ route('admin.hrm.durum.tip.guncelle', $tip->id) }}">
                                @csrf
                                <td><input type="text" name="emoji" value="{{ $tip->emoji }}" class="dp-mini" style="width:52px" maxlength="16"></td>
                                <td><input type="text" name="ad" value="{{ $tip->ad }}" class="dp-mini" style="width:100%" maxlength="60" required></td>
                                <td><input type="color" name="renk" value="{{ $tip->renk }}" class="dp-mini" style="width:52px;padding:2px"></td>
                                <td><input type="number" name="sira" value="{{ $tip->sira }}" class="dp-mini" style="width:60px" min="0" max="999"></td>
                                <td><input type="checkbox" name="mail_gonder" value="1" @checked($tip->mail_gonder)></td>
                                <td><input type="checkbox" name="aktif" value="1" @checked($tip->aktif)></td>
                                <td>
                                    <button type="submit" class="dp-mini" style="cursor:pointer">Kaydet</button>
                            </form>
                            <form method="POST" action="{{ route('admin.hrm.durum.tip.sil', $tip->id) }}"
                                  style="display:inline" onsubmit="return confirm('Bu durumu kaldırmak istediğine emin misin?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="dp-mini" style="cursor:pointer;color:var(--danger)">Kaldır</button>
                            </form>
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <form method="POST" action="{{ route('admin.hrm.durum.tip.ekle') }}"
                  style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                @csrf
                <input type="text" name="emoji" class="dp-mini" style="width:60px" placeholder="🎬" maxlength="16">
                <input type="text" name="ad" class="dp-mini" style="min-width:180px" placeholder="Durum adı" maxlength="60" required>
                <input type="color" name="renk" class="dp-mini" style="width:52px;padding:2px" value="#6b7280">
                <label class="dp-ipucu" style="cursor:pointer">
                    <input type="checkbox" name="mail_gonder" value="1" checked> mail gönder
                </label>
                <button type="submit" class="btn btn-primary btn-sm">Ekle</button>
            </form>

            <p class="dp-ipucu" style="margin-top:12px">
                <i data-lucide="info" style="width:14px;height:14px"></i>
                Geçmişte kullanılmış bir durum silinmez, listeden kaldırılır — eski kayıtların izi bozulmasın diye.
            </p>
        </div>
    </div>

    {{-- Bildirim alıcıları --}}
    <div class="dp-kutu">
        <div class="dp-kutu-bas" onclick="dpAc('dpAlicilar')">
            <h2>Bildirim alıcıları</h2>
            <small>Durum her değiştiğinde bu kişilere mail gider</small>
        </div>
        <div class="dp-kutu-ic" id="dpAlicilar" hidden>
            <form method="POST" action="{{ route('admin.hrm.durum.alicilar') }}" style="margin-top:14px">
                @csrf
                <div class="dp-alici-liste">
                    @foreach($personeller as $kisi)
                        <label class="dp-alici">
                            <input type="checkbox" name="alicilar[]" value="{{ $kisi->id }}"
                                   @checked(in_array($kisi->id, $alicilar ?? []))>
                            <span>{{ $kisi->adi ?: $kisi->kullaniciadi }}</span>
                        </label>
                    @endforeach
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-top:14px">
                    <span class="dp-ipucu">
                        <i data-lucide="info" style="width:14px;height:14px"></i>
                        Hiç kimse seçilmezse varsayılan liste kullanılır: Nurseli İnan, Nesimi Ateş, Dilan Ateş.
                    </span>
                    <button type="submit" class="btn btn-primary btn-sm">Alıcıları kaydet</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endif
@endsection

@push('scripts')
<script>
/* Kutular kapali basliyor: sayfanin asil isi durum panosu, ayarlar
   gunde bir kez aciliyor. */
function dpAc(id) {
    var el = document.getElementById(id);
    if (el) { el.hidden = !el.hidden; }
}
</script>
@endpush
