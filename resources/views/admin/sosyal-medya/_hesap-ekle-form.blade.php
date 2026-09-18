{{-- ── YENİ MARKA / PLATFORM EKLE ──────────────────────────────
     Şifre Kasası'na gitmeden kısayol. Aynı tabloya yazar
     (bolum=sosyal_medya); kasadaki şifre/kullanıcı adı alanları
     burada yok çünkü bu ekran onlarla ilgilenmiyor.

     Hem Plan hem Takip ekranında kullanılıyor — kopyalamak yerine
     ortak parçaya çıkarıldı; aynı formu ikinci kez yazmak, biri
     düzeltilip diğeri unutulmasına yol açardı.

     Varsayılan GİZLİ: sayfa açılınca önce asıl içerik (ızgara/liste)
     görünsün, ekleme formu isteyince açılsın. Bu formla ilgili bir
     doğrulama hatası dönmüşse (marka_adi/platform boş bırakılmışsa)
     otomatik açık kalır, yoksa kullanıcı hatayı hiç göremezdi.

     Beklenen değişkenler: $markalar, $platformlar, $uzmanlar --}}
<div class="card" id="smEkleKutu" @unless($errors->has('marka_adi') || $errors->has('platform')) hidden @endunless>
    <div class="card-header">
        <h2 class="card-title">Yeni marka / platform ekle</h2>
        <span class="card-desc">Var olan markaya yeni platform ekleyebilir ya da yepyeni bir marka açabilirsin</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.hesap.ekle') }}" class="sm-ekle-form">
            @csrf
            <div class="sm-ekle-alan">
                <label>Marka</label>
                <select name="marka_id" id="smMarkaSecim" class="form-select">
                    <option value="">+ Yeni marka…</option>
                    @foreach($markalar as $m)
                        <option value="{{ $m->id }}">{{ $m->baslik }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm-ekle-alan" id="smYeniMarkaAlani">
                <label>Yeni marka adı</label>
                <input type="text" name="marka_adi" maxlength="191"
                       placeholder="Örn. ZZ Demo Marka" class="form-input">
            </div>

            <div class="sm-ekle-alan">
                <label>Platform</label>
                <select name="platform" class="form-select" required>
                    <option value="">Seçin…</option>
                    @foreach($platformlar as $ikon => $ad)
                        <option value="{{ $ad }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm-ekle-alan">
                <label>Kullanıcı adı <span class="sm-ekle-opsiyonel">(opsiyonel)</span></label>
                <input type="text" name="kullanici_adi" maxlength="255"
                       placeholder="@marka" class="form-input">
            </div>

            <div class="sm-ekle-alan genis">
                <label>Profil linki <span class="sm-ekle-opsiyonel">(opsiyonel)</span></label>
                <input type="text" name="link" maxlength="255"
                       placeholder="https://instagram.com/marka" class="form-input">
            </div>

            <div class="sm-ekle-alan">
                <label>Sorumlu uzman <span class="sm-ekle-opsiyonel">(opsiyonel)</span></label>
                <select name="sorumlu_id" class="form-select">
                    <option value="">— atanmamış —</option>
                    @foreach($uzmanlar as $u)
                        <option value="{{ $u->id }}">{{ $u->adi ?: $u->kullaniciadi }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> <span>Ekle</span>
            </button>
        </form>
    </div>
</div>

<script>
/* Ekle butonu formu ac/kapa yapar. Buton her tiklamada metnini de
   degistirir ki "ac" mi "kapat" mi oldugu belirsiz kalmasin. */
(function () {
    var buton = document.getElementById('smEkleAc');
    var kutu  = document.getElementById('smEkleKutu');
    if (!buton || !kutu) return;

    function etiketGuncelle() {
        buton.querySelector('span').textContent = kutu.hidden ? 'Marka / Platform Ekle' : 'Formu Kapat';
    }
    buton.addEventListener('click', function () {
        kutu.hidden = !kutu.hidden;
        etiketGuncelle();
        if (!kutu.hidden) kutu.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    etiketGuncelle();
})();

/* Var olan marka seçilince "yeni marka adı" kutusu gizlenir — ikisi
   birlikte gönderilirse marka_id kazanır, kafa karışmasın diye önden gizle. */
(function () {
    var secim = document.getElementById('smMarkaSecim');
    var yeniAlan = document.getElementById('smYeniMarkaAlani');
    if (!secim || !yeniAlan) return;
    function guncelle() { yeniAlan.hidden = secim.value !== ''; }
    secim.addEventListener('change', guncelle);
    guncelle();
})();
</script>
