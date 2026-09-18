{{-- Paylaşılan müşteri formu (create + edit) --}}
@php
    $isEdit = !empty($customer);
    $action = $isEdit ? route('admin.crm.musteriler.update', $customer->id) : route('admin.crm.musteriler.store');
    $etiketler = old('etiketler', $isEdit ? (array)($customer->etiketler ?? []) : []);
@endphp

@if(session('uye_sifre_bilgi'))
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.35);color:#059669;padding:14px 18px;border-radius:12px;margin-bottom:18px;font-size:14px;display:flex;align-items:flex-start;gap:10px">
    <i data-lucide="user-check" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>
    <div><strong>Giriş hesabı da oluşturuldu.</strong><br>{{ session('uye_sifre_bilgi') }}</div>
</div>
@endif

<style>
/* Müşteri formu: masaüstü 2 sütun, mobilde tek sütun (inline stil mobilde sıkışmasın) */
.musteri-form-grid { grid-template-columns: 2fr 1fr; align-items: start; }
@media (max-width: 900px) { .musteri-form-grid { grid-template-columns: 1fr; } }
</style>
<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
@csrf
@if($isEdit) @method('PUT') @endif

<div class="form-grid musteri-form-grid">

    {{-- SOL: Ana bilgiler --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Kişisel Bilgiler</span>
            </div>

            <div class="form-grid">
                <div class="form-group" style="grid-column:1 / -1">
                    @php
                        $mFoto = $isEdit ? ($customer->profil_foto ?? null) : null;
                        if ($isEdit && empty($mFoto)) {
                            try {
                                if (\Illuminate\Support\Facades\Schema::hasColumn('uyeler', 'profil_foto')) {
                                    $mFoto = \Illuminate\Support\Facades\DB::table('uyeler')
                                        ->where(function ($q) use ($customer) {
                                            $q->where('id', $customer->id);
                                            if (!empty($customer->email)) { $q->orWhere('email', $customer->email); }
                                        })
                                        ->whereNotNull('profil_foto')
                                        ->value('profil_foto');
                                }
                            } catch (\Throwable $e) {}
                        }
                        $mFotoVar = $mFoto && is_file(public_path($mFoto));
                    @endphp
                    <label class="form-label">Profil Fotoğrafı</label>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                        @if($mFotoVar)
                            <img src="{{ asset($mFoto) }}?v={{ time() }}" alt=""
                                 style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
                        @endif
                        <input type="file" name="profil_foto" accept=".jpg,.jpeg,.png,.webp" class="form-input" style="max-width:320px">
                        @if($mFotoVar)
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--danger);cursor:pointer">
                                <input type="checkbox" name="foto_kaldir" value="1" style="width:auto;margin:0">
                                <span>Fotoğrafı kaldır</span>
                            </label>
                        @endif
                    </div>
                    <div class="form-help">JPG, PNG veya WEBP — en fazla 2MB. Müşteri kendi panelinden yüklediyse otomatik görünür.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Ad / Unvan <span class="required">*</span></label>
                    <input type="text" name="adi" value="{{ old('adi', $customer->adi ?? '') }}" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Firma / Unvan</label>
                    <input type="text" name="unvan" value="{{ old('unvan', $customer->unvan ?? '') }}" class="form-input" placeholder="Şirket adı">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-input" placeholder="ornek@firma.com">
                    <div class="form-help" style="color:#b45309;display:flex;align-items:center;gap:5px;margin-top:5px">
                        <i data-lucide="info" style="width:13px;height:13px"></i>
                        Email girilmezse müşteri giriş yapamaz ve üye olmaz. Email girilirse otomatik üye oluşturulur.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="telefon" value="{{ old('telefon', $customer->telefon ?? '') }}" class="form-input" placeholder="0212 ...">
                </div>
                <div class="form-group">
                    <label class="form-label">Doğum Tarihi</label>
                    <input type="date" name="dtarih" value="{{ old('dtarih', $customer->dtarih ?? '') }}" class="form-input">
                    <div class="form-help">Üye olarak da eklenirse profiline işlenir.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Vergi No / TCKN</label>
                    <input type="text" name="vergi_no" value="{{ old('vergi_no', $customer->vergi_no ?? '') }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Vergi Dairesi</label>
                    <input type="text" name="vergi_dairesi" value="{{ old('vergi_dairesi', $customer->vergi_dairesi ?? '') }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Web Sitesi</label>
                    <input type="text" name="web_sitesi" value="{{ old('web_sitesi', $customer->web_sitesi ?? '') }}" class="form-input" placeholder="https://...">
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="map-pin"></i>
                <span>Adres</span>
            </div>

            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label">Ülke / İl</label>
                    @php
                        $secIl      = old('il', $customer->il ?? '');
                        $secIlce    = old('ilce', $customer->ilce ?? '');
                        $secMahalle = old('mahalle', $customer->mahalle ?? '');
                        $trIller = ['Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara','Antalya','Ardahan','Artvin','Aydın','Balıkesir','Bartın','Batman','Bayburt','Bilecik','Bingöl','Bitlis','Bolu','Burdur','Bursa','Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır','Düzce','Edirne','Elazığ','Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun','Gümüşhane','Hakkari','Hatay','Iğdır','Isparta','İstanbul','İzmir','Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri','Kırıkkale','Kırklareli','Kırşehir','Kilis','Kocaeli','Konya','Kütahya','Malatya','Manisa','Mardin','Mersin','Muğla','Muş','Nevşehir','Niğde','Ordu','Osmaniye','Rize','Sakarya','Samsun','Siirt','Sinop','Sivas','Şanlıurfa','Şırnak','Tekirdağ','Tokat','Trabzon','Tunceli','Uşak','Van','Yalova','Yozgat','Zonguldak'];
                        // Mevcut il değeri listede yoksa "Diğer ülke" modunda başla
                        $yurtdisi = $secIl !== '' && !in_array($secIl, $trIller, true);
                    @endphp
                    <div style="display:grid;grid-template-columns:170px 1fr;gap:10px">
                        <select id="crmUlkeSec" class="form-select">
                            <option value="tr" {{ !$yurtdisi ? 'selected' : '' }}>🇹🇷 Türkiye</option>
                            <option value="diger" {{ $yurtdisi ? 'selected' : '' }}>🌍 Diğer Ülke</option>
                        </select>
                        <div>
                            <select name="il" id="crmIlSelect" class="form-select" style="{{ $yurtdisi ? 'display:none' : '' }}" {{ $yurtdisi ? 'disabled' : '' }}>
                                <option value="">— İl seç —</option>
                                @foreach($trIller as $tIl)
                                    <option value="{{ $tIl }}" {{ $secIl === $tIl ? 'selected' : '' }}>{{ $tIl }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="il" id="crmIlText" class="form-input"
                                   value="{{ $yurtdisi ? $secIl : '' }}"
                                   placeholder="Ülke / Şehir (örn: Almanya / Berlin)"
                                   style="{{ $yurtdisi ? '' : 'display:none' }}" {{ $yurtdisi ? '' : 'disabled' }}>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">İlçe</label>
                    <select name="ilce" id="crmIlceSelect" class="form-select"
                            style="{{ $yurtdisi ? 'display:none' : '' }}" {{ $yurtdisi ? 'disabled' : '' }}>
                        <option value="">— Önce il seçin —</option>
                    </select>
                    <input type="text" name="ilce" id="crmIlceText" class="form-input"
                           value="{{ $secIlce }}" placeholder="İlçe"
                           style="{{ $yurtdisi ? '' : 'display:none' }}" {{ $yurtdisi ? '' : 'disabled' }}>
                </div>
                <div class="form-group">
                    <label class="form-label">Mahalle</label>
                    <input type="text" name="mahalle" value="{{ $secMahalle }}" class="form-input" placeholder="Mahalle">
                </div>
                <div class="form-group full">
                    <label class="form-label">Açık Adres</label>
                    <textarea name="adres" rows="3" class="form-textarea" placeholder="Mahalle, sokak, no...">{{ old('adres', $customer->adres ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="sticky-note"></i>
                <span>Dahili Not</span>
            </div>
            <div class="form-group">
                <textarea name="not_icerik" rows="4" class="form-textarea" placeholder="Müşteri hakkında dahili notlar (sadece admin görür)">{{ old('not_icerik', $customer->not_icerik ?? '') }}</textarea>
                <div class="form-help">Bu not müşteri detay sayfasında görünür, sadece adminler okur.</div>
            </div>
        </div>
    </div>

    {{-- SAĞ: Yan panel --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="settings"></i>
                <span>Hesap Ayarları</span>
            </div>

            <div class="form-group">
                <label class="form-label">Yeni Giriş Şifresi</label>
                <input type="password" name="sifre" class="form-input" autocomplete="new-password"
                       value="{{ old('sifre') }}" placeholder="Değiştirmek için yeni şifre (boş bırak = değişmez)">
                <div class="form-help">🔑 Doldurulursa müşterinin giriş şifresi bu şifreyle değişir (en az 6 karakter). Boş bırakırsan mevcut şifre korunur. E-posta varsa hesabı yoksa otomatik oluşturulur.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Durum (Lead Sıcaklığı)</label>
                @php $curDurum = old('durum', \App\Models\CRM\Customer::durumBilgi($customer->durum ?? null)['key']); @endphp
                <select name="durum" class="form-select">
                    @foreach(\App\Models\CRM\Customer::DURUMLAR as $dk => $dv)
                        <option value="{{ $dk }}" {{ $curDurum == $dk ? 'selected' : '' }}>{{ $dv['ikon'] }} {{ $dv['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                @php
                    $dcKategoriler = \Illuminate\Support\Facades\Schema::hasColumn('crm_customers','kategori')
                        ? \Illuminate\Support\Facades\DB::table('crm_customers')->whereNotNull('kategori')->where('kategori','!=','')->distinct()->orderBy('kategori')->pluck('kategori')
                        : collect();
                @endphp
                <input type="text" name="kategori" list="dcKategoriListe" class="form-input"
                       value="{{ old('kategori', $customer->kategori ?? '') }}" placeholder="Seç ya da yeni kategori yaz">
                <datalist id="dcKategoriListe">
                    @foreach($dcKategoriler as $kat)<option value="{{ $kat }}">@endforeach
                </datalist>
                <div class="form-help">Listeden seç ya da yeni kategori yazıp kaydet (otomatik eklenir).</div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
                    <input type="checkbox" name="data_center" value="1" style="width:auto;margin:0"
                           {{ old('data_center', $customer->data_center ?? 0) ? 'checked' : '' }}>
                    <span>Data Center'a da ekle</span>
                </label>
                <div class="form-help">İşaretlenirse kayıt hem CRM listesinde hem Data Center'da görünür.</div>
            </div>

            {{-- DATA CENTER LİSTELERİ — çoklu seçim (Çoka Çok) --}}
            <div class="form-group">
                <label class="form-label">Data Center Listeleri</label>
                @php $seciliLst = old('listeler', $seciliListeler ?? []); @endphp
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @forelse(($listeler ?? collect()) as $liste)
                        <label style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid var(--aline,#e2e8f0);border-radius:20px;cursor:pointer;font-size:13px">
                            <input type="checkbox" name="listeler[]" value="{{ $liste->id }}" style="width:auto;margin:0" {{ in_array($liste->id, $seciliLst) ? 'checked' : '' }}>
                            <span style="width:9px;height:9px;border-radius:50%;background:{{ $liste->renk ?: '#c8a44d' }}"></span>
                            {{ $liste->ad }}
                        </label>
                    @empty
                        <span class="form-help">Henüz liste yok. <a href="{{ route('admin.data-center.listeler') }}" target="_blank">Liste oluştur →</a></span>
                    @endforelse
                </div>
                <div class="form-help">Müşteri aynı anda birden çok listede olabilir (örn. hem "Spor Yapanlar" hem "Gurme").</div>
            </div>

            @if(!empty($yoneticiler) && count($yoneticiler) > 0)
            <div class="form-group">
                <label class="form-label">Sorumlu Yönetici</label>
                <select name="sorumlu_id" class="form-select">
                    <option value="">— Seç —</option>
                    @foreach($yoneticiler as $y)
                        <option value="{{ $y->id }}" {{ old('sorumlu_id', $customer->sorumlu_id ?? '') == $y->id ? 'selected' : '' }}>
                            {{ $y->adi ?? $y->kullaniciadi }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Bakiye (₺)</label>
                <input type="number" step="0.01" name="bakiye" value="{{ old('bakiye', $customer->bakiye ?? 0) }}" class="form-input">
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="tag"></i>
                <span>Etiketler</span>
            </div>
            <div id="etiketWrap" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">
                @foreach($etiketler as $tag)
                    <span class="etiket-pill" style="padding:4px 10px;font-size:11px">
                        <input type="hidden" name="etiketler[]" value="{{ $tag }}">
                        {{ $tag }}
                        <button type="button" onclick="this.closest('.etiket-pill').remove()" style="background:none;border:none;color:inherit;cursor:pointer;padding:0 0 0 4px;font-size:14px">×</button>
                    </span>
                @endforeach
            </div>
            <input type="text" id="etiketInput" class="form-input" placeholder="Etiket yaz + Enter">
            <div class="form-help">Müşteri segmentasyonu için (örn: vip, kurumsal, kobi)</div>
        </div>

        <div class="section">
            <button type="submit" class="btn btn-primary" style="width:100%">
                <i data-lucide="save"></i>
                <span>{{ $isEdit ? 'Kaydet' : 'Müşteri Oluştur' }}</span>
            </button>
            @if($isEdit)
            <a href="{{ route('admin.crm.musteriler.show', $customer->id) }}" class="btn btn-secondary" style="width:100%;margin-top:8px">
                İptal
            </a>
            @endif
        </div>
    </div>

</div>

</form>

<style>
    .etiket-pill {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        background: var(--brand-soft);
        color: var(--brand);
        border: 1px solid var(--brand-medium);
    }
</style>

<script>
(function(){
    // Ülke / İl / İlçe yönetimi: Türkiye → listeden seçim, Diğer → serbest yazım
    const TR_ILCELER = {
        "Adana": ["Aladağ", "Ceyhan", "Çukurova", "Feke", "İmamoğlu", "Karaisalı", "Karataş", "Kozan", "Pozantı", "Saimbeyli", "Sarıçam", "Seyhan", "Tufanbeyli", "Yumurtalık", "Yüreğir"],
        "Adıyaman": ["Besni", "Çelikhan", "Gerger", "Gölbaşı", "Kahta", "Merkez", "Samsat", "Sincik", "Tut"],
        "Afyonkarahisar": ["Başmakçı", "Bayat", "Bolvadin", "Çay", "Çobanlar", "Dazkırı", "Dinar", "Emirdağ", "Evciler", "Hocalar", "İhsaniye", "İscehisar", "Kızılören", "Merkez", "Sandıklı", "Sinanpaşa", "Sultandağı", "Şuhut"],
        "Ağrı": ["Diyadin", "Doğubayazıt", "Eleşkirt", "Hamur", "Merkez", "Patnos", "Taşlıçay", "Tutak"],
        "Aksaray": ["Ağaçören", "Eskil", "Gülağaç", "Güzelyurt", "Merkez", "Ortaköy", "Sarıyahşi", "Sultanhanı"],
        "Amasya": ["Göynücek", "Gümüşhacıköy", "Hamamözü", "Merkez", "Merzifon", "Suluova", "Taşova"],
        "Ankara": ["Akyurt", "Altındağ", "Ayaş", "Bala", "Beypazarı", "Çamlıdere", "Çankaya", "Çubuk", "Elmadağ", "Etimesgut", "Evren", "Gölbaşı", "Güdül", "Haymana", "Kahramankazan", "Kalecik", "Keçiören", "Kızılcahamam", "Mamak", "Nallıhan", "Polatlı", "Pursaklar", "Sincan", "Şereflikoçhisar", "Yenimahalle"],
        "Antalya": ["Akseki", "Aksu", "Alanya", "Demre", "Döşemealtı", "Elmalı", "Finike", "Gazipaşa", "Gündoğmuş", "İbradı", "Kaş", "Kemer", "Kepez", "Konyaaltı", "Korkuteli", "Kumluca", "Manavgat", "Muratpaşa", "Serik"],
        "Ardahan": ["Çıldır", "Damal", "Göle", "Hanak", "Merkez", "Posof"],
        "Artvin": ["Ardanuç", "Arhavi", "Borçka", "Hopa", "Kemalpaşa", "Merkez", "Murgul", "Şavşat", "Yusufeli"],
        "Aydın": ["Bozdoğan", "Buharkent", "Çine", "Didim", "Efeler", "Germencik", "İncirliova", "Karacasu", "Karpuzlu", "Koçarlı", "Köşk", "Kuşadası", "Kuyucak", "Nazilli", "Söke", "Sultanhisar", "Yenipazar"],
        "Balıkesir": ["Altıeylül", "Ayvalık", "Balya", "Bandırma", "Bigadiç", "Burhaniye", "Dursunbey", "Edremit", "Erdek", "Gömeç", "Gönen", "Havran", "İvrindi", "Karesi", "Kepsut", "Manyas", "Marmara", "Savaştepe", "Sındırgı", "Susurluk"],
        "Bartın": ["Amasra", "Kurucaşile", "Merkez", "Ulus"],
        "Batman": ["Beşiri", "Gercüş", "Hasankeyf", "Kozluk", "Merkez", "Sason"],
        "Bayburt": ["Aydıntepe", "Demirözü", "Merkez"],
        "Bilecik": ["Bozüyük", "Gölpazarı", "İnhisar", "Merkez", "Osmaneli", "Pazaryeri", "Söğüt", "Yenipazar"],
        "Bingöl": ["Adaklı", "Genç", "Karlıova", "Kiğı", "Merkez", "Solhan", "Yayladere", "Yedisu"],
        "Bitlis": ["Adilcevaz", "Ahlat", "Güroymak", "Hizan", "Merkez", "Mutki", "Tatvan"],
        "Bolu": ["Dörtdivan", "Gerede", "Göynük", "Kıbrıscık", "Mengen", "Merkez", "Mudurnu", "Seben", "Yeniçağa"],
        "Burdur": ["Ağlasun", "Altınyayla", "Bucak", "Çavdır", "Çeltikçi", "Gölhisar", "Karamanlı", "Kemer", "Merkez", "Tefenni", "Yeşilova"],
        "Bursa": ["Büyükorhan", "Gemlik", "Gürsu", "Harmancık", "İnegöl", "İznik", "Karacabey", "Keles", "Kestel", "Mudanya", "Mustafakemalpaşa", "Nilüfer", "Orhaneli", "Orhangazi", "Osmangazi", "Yenişehir", "Yıldırım"],
        "Çanakkale": ["Ayvacık", "Bayramiç", "Biga", "Bozcaada", "Çan", "Eceabat", "Ezine", "Gelibolu", "Gökçeada", "Lapseki", "Merkez", "Yenice"],
        "Çankırı": ["Atkaracalar", "Bayramören", "Çerkeş", "Eldivan", "Ilgaz", "Kızılırmak", "Korgun", "Kurşunlu", "Merkez", "Orta", "Şabanözü", "Yapraklı"],
        "Çorum": ["Alaca", "Bayat", "Boğazkale", "Dodurga", "İskilip", "Kargı", "Laçin", "Mecitözü", "Merkez", "Oğuzlar", "Ortaköy", "Osmancık", "Sungurlu", "Uğurludağ"],
        "Denizli": ["Acıpayam", "Babadağ", "Baklan", "Bekilli", "Beyağaç", "Bozkurt", "Buldan", "Çal", "Çameli", "Çardak", "Çivril", "Güney", "Honaz", "Kale", "Merkezefendi", "Pamukkale", "Sarayköy", "Serinhisar", "Tavas"],
        "Diyarbakır": ["Bağlar", "Bismil", "Çermik", "Çınar", "Çüngüş", "Dicle", "Eğil", "Ergani", "Hani", "Hazro", "Kayapınar", "Kocaköy", "Kulp", "Lice", "Silvan", "Sur", "Yenişehir"],
        "Düzce": ["Akçakoca", "Cumayeri", "Çilimli", "Gölyaka", "Gümüşova", "Kaynaşlı", "Merkez", "Yığılca"],
        "Edirne": ["Enez", "Havsa", "İpsala", "Keşan", "Lalapaşa", "Meriç", "Merkez", "Süloğlu", "Uzunköprü"],
        "Elazığ": ["Ağın", "Alacakaya", "Arıcak", "Baskil", "Karakoçan", "Keban", "Kovancılar", "Maden", "Merkez", "Palu", "Sivrice"],
        "Erzincan": ["Çayırlı", "İliç", "Kemah", "Kemaliye", "Merkez", "Otlukbeli", "Refahiye", "Tercan", "Üzümlü"],
        "Erzurum": ["Aşkale", "Aziziye", "Çat", "Hınıs", "Horasan", "İspir", "Karaçoban", "Karayazı", "Köprüköy", "Narman", "Oltu", "Olur", "Palandöken", "Pasinler", "Pazaryolu", "Şenkaya", "Tekman", "Tortum", "Uzundere", "Yakutiye"],
        "Eskişehir": ["Alpu", "Beylikova", "Çifteler", "Günyüzü", "Han", "İnönü", "Mahmudiye", "Mihalgazi", "Mihalıççık", "Odunpazarı", "Sarıcakaya", "Seyitgazi", "Sivrihisar", "Tepebaşı"],
        "Gaziantep": ["Araban", "İslahiye", "Karkamış", "Nizip", "Nurdağı", "Oğuzeli", "Şahinbey", "Şehitkamil", "Yavuzeli"],
        "Giresun": ["Alucra", "Bulancak", "Çamoluk", "Çanakçı", "Dereli", "Doğankent", "Espiye", "Eynesil", "Görele", "Güce", "Keşap", "Merkez", "Piraziz", "Şebinkarahisar", "Tirebolu", "Yağlıdere"],
        "Gümüşhane": ["Kelkit", "Köse", "Kürtün", "Merkez", "Şiran", "Torul"],
        "Hakkari": ["Çukurca", "Derecik", "Merkez", "Şemdinli", "Yüksekova"],
        "Hatay": ["Altınözü", "Antakya", "Arsuz", "Belen", "Defne", "Dörtyol", "Erzin", "Hassa", "İskenderun", "Kırıkhan", "Kumlu", "Payas", "Reyhanlı", "Samandağ", "Yayladağı"],
        "Iğdır": ["Aralık", "Karakoyunlu", "Merkez", "Tuzluca"],
        "Isparta": ["Aksu", "Atabey", "Eğirdir", "Gelendost", "Gönen", "Keçiborlu", "Merkez", "Senirkent", "Sütçüler", "Şarkikaraağaç", "Uluborlu", "Yalvaç", "Yenişarbademli"],
        "İstanbul": ["Adalar", "Arnavutköy", "Ataşehir", "Avcılar", "Bağcılar", "Bahçelievler", "Bakırköy", "Başakşehir", "Bayrampaşa", "Beşiktaş", "Beykoz", "Beylikdüzü", "Beyoğlu", "Büyükçekmece", "Çatalca", "Çekmeköy", "Esenler", "Esenyurt", "Eyüpsultan", "Fatih", "Gaziosmanpaşa", "Güngören", "Kadıköy", "Kağıthane", "Kartal", "Küçükçekmece", "Maltepe", "Pendik", "Sancaktepe", "Sarıyer", "Silivri", "Sultanbeyli", "Sultangazi", "Şile", "Şişli", "Tuzla", "Ümraniye", "Üsküdar", "Zeytinburnu"],
        "İzmir": ["Aliağa", "Balçova", "Bayındır", "Bayraklı", "Bergama", "Beydağ", "Bornova", "Buca", "Çeşme", "Çiğli", "Dikili", "Foça", "Gaziemir", "Güzelbahçe", "Karabağlar", "Karaburun", "Karşıyaka", "Kemalpaşa", "Kınık", "Kiraz", "Konak", "Menderes", "Menemen", "Narlıdere", "Ödemiş", "Seferihisar", "Selçuk", "Tire", "Torbalı", "Urla"],
        "Kahramanmaraş": ["Afşin", "Andırın", "Çağlayancerit", "Dulkadiroğlu", "Ekinözü", "Elbistan", "Göksun", "Nurhak", "Onikişubat", "Pazarcık", "Türkoğlu"],
        "Karabük": ["Eflani", "Eskipazar", "Merkez", "Ovacık", "Safranbolu", "Yenice"],
        "Karaman": ["Ayrancı", "Başyayla", "Ermenek", "Kazımkarabekir", "Merkez", "Sarıveliler"],
        "Kars": ["Akyaka", "Arpaçay", "Digor", "Kağızman", "Merkez", "Sarıkamış", "Selim", "Susuz"],
        "Kastamonu": ["Abana", "Ağlı", "Araç", "Azdavay", "Bozkurt", "Cide", "Çatalzeytin", "Daday", "Devrekani", "Doğanyurt", "Hanönü", "İhsangazi", "İnebolu", "Küre", "Merkez", "Pınarbaşı", "Seydiler", "Şenpazar", "Taşköprü", "Tosya"],
        "Kayseri": ["Akkışla", "Bünyan", "Develi", "Felahiye", "Hacılar", "İncesu", "Kocasinan", "Melikgazi", "Özvatan", "Pınarbaşı", "Sarıoğlan", "Sarız", "Talas", "Tomarza", "Yahyalı", "Yeşilhisar"],
        "Kırıkkale": ["Bahşili", "Balışeyh", "Çelebi", "Delice", "Karakeçili", "Keskin", "Merkez", "Sulakyurt", "Yahşihan"],
        "Kırklareli": ["Babaeski", "Demirköy", "Kofçaz", "Lüleburgaz", "Merkez", "Pehlivanköy", "Pınarhisar", "Vize"],
        "Kırşehir": ["Akçakent", "Akpınar", "Boztepe", "Çiçekdağı", "Kaman", "Merkez", "Mucur"],
        "Kilis": ["Elbeyli", "Merkez", "Musabeyli", "Polateli"],
        "Kocaeli": ["Başiskele", "Çayırova", "Darıca", "Derince", "Dilovası", "Gebze", "Gölcük", "İzmit", "Kandıra", "Karamürsel", "Kartepe", "Körfez"],
        "Konya": ["Ahırlı", "Akören", "Akşehir", "Altınekin", "Beyşehir", "Bozkır", "Cihanbeyli", "Çeltik", "Çumra", "Derbent", "Derebucak", "Doğanhisar", "Emirgazi", "Ereğli", "Güneysınır", "Hadim", "Halkapınar", "Hüyük", "Ilgın", "Kadınhanı", "Karapınar", "Karatay", "Kulu", "Meram", "Sarayönü", "Selçuklu", "Seydişehir", "Taşkent", "Tuzlukçu", "Yalıhüyük", "Yunak"],
        "Kütahya": ["Altıntaş", "Aslanapa", "Çavdarhisar", "Domaniç", "Dumlupınar", "Emet", "Gediz", "Hisarcık", "Merkez", "Pazarlar", "Simav", "Şaphane", "Tavşanlı"],
        "Malatya": ["Akçadağ", "Arapgir", "Arguvan", "Battalgazi", "Darende", "Doğanşehir", "Doğanyol", "Hekimhan", "Kale", "Kuluncak", "Pütürge", "Yazıhan", "Yeşilyurt"],
        "Manisa": ["Ahmetli", "Akhisar", "Alaşehir", "Demirci", "Gölmarmara", "Gördes", "Kırkağaç", "Köprübaşı", "Kula", "Salihli", "Sarıgöl", "Saruhanlı", "Selendi", "Soma", "Şehzadeler", "Turgutlu", "Yunusemre"],
        "Mardin": ["Artuklu", "Dargeçit", "Derik", "Kızıltepe", "Mazıdağı", "Midyat", "Nusaybin", "Ömerli", "Savur", "Yeşilli"],
        "Mersin": ["Akdeniz", "Anamur", "Aydıncık", "Bozyazı", "Çamlıyayla", "Erdemli", "Gülnar", "Mezitli", "Mut", "Silifke", "Tarsus", "Toroslar", "Yenişehir"],
        "Muğla": ["Bodrum", "Dalaman", "Datça", "Fethiye", "Kavaklıdere", "Köyceğiz", "Marmaris", "Menteşe", "Milas", "Ortaca", "Seydikemer", "Ula", "Yatağan"],
        "Muş": ["Bulanık", "Hasköy", "Korkut", "Malazgirt", "Merkez", "Varto"],
        "Nevşehir": ["Acıgöl", "Avanos", "Derinkuyu", "Gülşehir", "Hacıbektaş", "Kozaklı", "Merkez", "Ürgüp"],
        "Niğde": ["Altunhisar", "Bor", "Çamardı", "Çiftlik", "Merkez", "Ulukışla"],
        "Ordu": ["Akkuş", "Altınordu", "Aybastı", "Çamaş", "Çatalpınar", "Çaybaşı", "Fatsa", "Gölköy", "Gülyalı", "Gürgentepe", "İkizce", "Kabadüz", "Kabataş", "Korgan", "Kumru", "Mesudiye", "Perşembe", "Ulubey", "Ünye"],
        "Osmaniye": ["Bahçe", "Düziçi", "Hasanbeyli", "Kadirli", "Merkez", "Sumbas", "Toprakkale"],
        "Rize": ["Ardeşen", "Çamlıhemşin", "Çayeli", "Derepazarı", "Fındıklı", "Güneysu", "Hemşin", "İkizdere", "İyidere", "Kalkandere", "Merkez", "Pazar"],
        "Sakarya": ["Adapazarı", "Akyazı", "Arifiye", "Erenler", "Ferizli", "Geyve", "Hendek", "Karapürçek", "Karasu", "Kaynarca", "Kocaali", "Pamukova", "Sapanca", "Serdivan", "Söğütlü", "Taraklı"],
        "Samsun": ["Alaçam", "Asarcık", "Atakum", "Ayvacık", "Bafra", "Canik", "Çarşamba", "Havza", "İlkadım", "Kavak", "Ladik", "Ondokuzmayıs", "Salıpazarı", "Tekkeköy", "Terme", "Vezirköprü", "Yakakent"],
        "Siirt": ["Baykan", "Eruh", "Kurtalan", "Merkez", "Pervari", "Şirvan", "Tillo"],
        "Sinop": ["Ayancık", "Boyabat", "Dikmen", "Durağan", "Erfelek", "Gerze", "Merkez", "Saraydüzü", "Türkeli"],
        "Sivas": ["Akıncılar", "Altınyayla", "Divriği", "Doğanşar", "Gemerek", "Gölova", "Gürün", "Hafik", "İmranlı", "Kangal", "Koyulhisar", "Merkez", "Suşehri", "Şarkışla", "Ulaş", "Yıldızeli", "Zara"],
        "Şanlıurfa": ["Akçakale", "Birecik", "Bozova", "Ceylanpınar", "Eyyübiye", "Halfeti", "Haliliye", "Harran", "Hilvan", "Karaköprü", "Siverek", "Suruç", "Viranşehir"],
        "Şırnak": ["Beytüşşebap", "Cizre", "Güçlükonak", "İdil", "Merkez", "Silopi", "Uludere"],
        "Tekirdağ": ["Çerkezköy", "Çorlu", "Ergene", "Hayrabolu", "Kapaklı", "Malkara", "Marmaraereğlisi", "Muratlı", "Saray", "Süleymanpaşa", "Şarköy"],
        "Tokat": ["Almus", "Artova", "Başçiftlik", "Erbaa", "Merkez", "Niksar", "Pazar", "Reşadiye", "Sulusaray", "Turhal", "Yeşilyurt", "Zile"],
        "Trabzon": ["Akçaabat", "Araklı", "Arsin", "Beşikdüzü", "Çarşıbaşı", "Çaykara", "Dernekpazarı", "Düzköy", "Hayrat", "Köprübaşı", "Maçka", "Of", "Ortahisar", "Sürmene", "Şalpazarı", "Tonya", "Vakfıkebir", "Yomra"],
        "Tunceli": ["Çemişgezek", "Hozat", "Mazgirt", "Merkez", "Nazımiye", "Ovacık", "Pertek", "Pülümür"],
        "Uşak": ["Banaz", "Eşme", "Karahallı", "Merkez", "Sivaslı", "Ulubey"],
        "Van": ["Bahçesaray", "Başkale", "Çaldıran", "Çatak", "Edremit", "Erciş", "Gevaş", "Gürpınar", "İpekyolu", "Muradiye", "Özalp", "Saray", "Tuşba"],
        "Yalova": ["Altınova", "Armutlu", "Çınarcık", "Çiftlikköy", "Merkez", "Termal"],
        "Yozgat": ["Akdağmadeni", "Aydıncık", "Boğazlıyan", "Çandır", "Çayıralan", "Çekerek", "Kadışehri", "Merkez", "Saraykent", "Sarıkaya", "Sorgun", "Şefaatli", "Yenifakılı", "Yerköy"],
        "Zonguldak": ["Alaplı", "Çaycuma", "Devrek", "Ereğli", "Gökçebey", "Kilimli", "Kozlu", "Merkez"]
    };
    const ulkeSec    = document.getElementById('crmUlkeSec');
    const ilSelect   = document.getElementById('crmIlSelect');
    const ilText     = document.getElementById('crmIlText');
    const ilceSelect = document.getElementById('crmIlceSelect');
    const ilceText   = document.getElementById('crmIlceText');
    if (!ulkeSec || !ilSelect || !ilText || !ilceSelect || !ilceText) return;

    const IL_SECILI   = @json($secIl);
    const ILCE_SECILI = @json($secIlce);
    const YURTDISI    = @json($yurtdisi);

    function ilceModu(m){
        const sel = (m === 'select');
        ilceSelect.style.display = sel ? '' : 'none';
        ilceSelect.disabled = !sel;
        ilceText.style.display = sel ? 'none' : '';
        ilceText.disabled = sel;
    }

    function ilceDoldur(il, secili){
        const list = TR_ILCELER[il] || [];
        let html = '<option value="">' + (list.length ? '— İlçe seç —' : '— Önce il seçin —') + '</option>';
        list.forEach(function(i){ html += '<option value="' + i + '">' + i + '</option>'; });
        if (list.length) html += '<option value="__elle__">✏️ Listede yok — elle yaz</option>';
        ilceSelect.innerHTML = html;
        if (secili && list.indexOf(secili) !== -1) {
            ilceSelect.value = secili;
            ilceModu('select');
        } else if (secili) {
            ilceText.value = secili;
            ilceModu('text');
        } else {
            ilceModu('select');
        }
    }

    ulkeSec.addEventListener('change', function(){
        const tr = this.value === 'tr';
        ilSelect.style.display = tr ? '' : 'none';
        ilSelect.disabled = !tr;
        ilText.style.display = tr ? 'none' : '';
        ilText.disabled = tr;
        if (tr) { ilceDoldur(ilSelect.value, ''); }
        else { ilceText.value = ''; ilceModu('text'); }
    });

    ilSelect.addEventListener('change', function(){
        ilceDoldur(this.value, '');
    });

    ilceSelect.addEventListener('change', function(){
        if (this.value === '__elle__') {
            ilceText.value = '';
            ilceModu('text');
            ilceText.focus();
        }
    });

    // Sayfa açılışı: kayıtlı il/ilçeyi yerine koy
    if (!YURTDISI) { ilceDoldur(IL_SECILI, ILCE_SECILI); }
})();
(function(){
    const input = document.getElementById('etiketInput');
    const wrap = document.getElementById('etiketWrap');
    if (!input) return;

    function addTag(value) {
        value = value.trim();
        if (!value) return;
        // Aynı etiket varsa ekleme
        const existing = Array.from(wrap.querySelectorAll('input[name="etiketler[]"]')).map(i => i.value);
        if (existing.includes(value)) return;

        const span = document.createElement('span');
        span.className = 'etiket-pill';
        span.style.cssText = 'padding:4px 10px;font-size:11px';
        const safe = value.replace(/"/g, '&quot;').replace(/</g, '&lt;');
        span.innerHTML = `
            <input type="hidden" name="etiketler[]" value="${safe}">
            ${safe}
            <button type="button" style="background:none;border:none;color:inherit;cursor:pointer;padding:0 0 0 4px;font-size:14px">×</button>
        `;
        span.querySelector('button').addEventListener('click', () => span.remove());
        wrap.appendChild(span);
        input.value = '';
    }

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(input.value);
        }
    });
})();
</script>