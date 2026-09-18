@extends('admin._layout')

@section('title', ($customer->adi ?? 'Müşteri') . ' — Müşteri Detayı')

@php
    $aktifTab = request('tab', 'bilgiler');
    $_ad = $customer->adi ?? $customer->ad ?? '—';
    $dbilgi   = \App\Models\CRM\Customer::durumBilgi($customer->durum ?? null);
    $dsonraki = \App\Models\CRM\Customer::durumBilgi(\App\Models\CRM\Customer::durumSonraki($customer->durum ?? null));

    $tabs = [
        'bilgiler'    => ['📋', 'Bilgiler'],
        'notlar'      => ['📝', 'Notlar'],
        'faturalar'   => ['🧾', 'Faturalar'],
        'teklifler'   => ['💼', 'Teklifler'],
        'sozlesmeler' => ['📄', 'Sözleşmeler'],
        'krediler'    => ['🏦', 'DN Bank'],
        'hizmetler'   => ['⚒️', 'Hizmetler'],
        'e-faturalar' => ['📑', 'E-Faturalar'],
        'destek'      => ['🎫', 'Destek'],
        'raporlar'    => ['📊', 'Raporlar'],
        'referanslar' => ['⭐', 'Referanslar'],
        'bildirimler' => ['📧', 'Bildirimler'],
        'randevular'  => ['📅', 'Randevular'],
        'gorevler'    => ['✅', 'Görevler'],
    ];

    $altSekme = null;
    if (in_array($aktifTab, ['hostingler', 'alan-adlari'], true)) {
        $altSekme = $aktifTab;
        $aktifTab = 'hizmetler';
    }
    if (!array_key_exists($aktifTab, $tabs)) {
        $aktifTab = 'bilgiler';
    }

    // Profil fotoğrafı: önce crm_customers.profil_foto, yoksa bağlı üyenin
    // (ID veya E-POSTA eşleşen) fotosu — id her zaman aynı olmayabilir.
    $_fotoUrl = null;
    $_cFoto = $customer->profil_foto ?? null;
    if (empty($_cFoto)) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('uyeler', 'profil_foto')) {
                $_cFoto = \Illuminate\Support\Facades\DB::table('uyeler')
                    ->where(function ($q) use ($customer) {
                        $q->where('id', $customer->id);
                        if (!empty($customer->email)) { $q->orWhere('email', $customer->email); }
                    })
                    ->whereNotNull('profil_foto')
                    ->value('profil_foto');
            }
        } catch (\Throwable $e) {}
    }
    if ($_cFoto && is_file(public_path($_cFoto))) {
        $_fotoUrl = asset($_cFoto);
    }
@endphp

@section('content')

{{-- Breadcrumb --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.musteriler.index') }}">Müşteriler</a>
    <span class="sep">/</span>
    <span class="current">{{ $_ad }} {{ $customer->soyad ?? '' }}</span>
</div>

{{-- NOT: Session alert'leri admin._layout zaten gösteriyor.
     Burada tekrar göstermiyoruz, çift mesaj olmasın. --}}

{{--
    GEÇİCİ ŞİFRE GÖSTERİMİ (04.08.2026)

    "Tek Kullanımlık Şifre Gönder" butonuna basılınca üretilen şifre, kaybolan
    bir bildirimde değil, burada — elle kapatılana kadar açık kalan bir kutuda
    gösterilir. Şifre veritabanında bcrypt ile tutulduğu için (doğru davranış)
    bu kutu kapatıldıktan sonra AYNI ŞİFRE bir daha hiçbir yerden görüntülenemez;
    gerekirse "Tek Kullanımlık Şifre Gönder" tekrar basılıp yenisi üretilir.
--}}
@if(session('gecici_sifre_goster'))
    @php $gs = session('gecici_sifre_goster'); @endphp
    <div id="geciciSifreKutusu" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;
         display:flex;align-items:center;justify-content:center;padding:16px">
        <div style="background:var(--surface,#fff);border-radius:16px;max-width:440px;width:100%;
             padding:26px;box-shadow:0 20px 60px rgba(0,0,0,.3)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                <i data-lucide="key-round" style="color:var(--brand)"></i>
                <h3 style="margin:0;font-size:17px;font-weight:800">Geçici Şifre Oluşturuldu</h3>
            </div>
            <p style="color:var(--text-muted);font-size:13px;margin:0 0 16px">
                {{ $gs['ad'] ? $gs['ad'] . ' için ' : '' }}yeni giriş şifresi üretildi.
                {{ $gs['mail'] ? 'Ayrıca e-posta ile de gönderildi.' : 'E-posta gönderilemedi — aşağıdakini elle iletin.' }}
            </p>

            <div style="background:var(--bg-subtle);border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:6px">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px">E-posta</div>
                <div style="font-size:13.5px;font-weight:600">{{ $gs['email'] }}</div>
            </div>
            <div style="background:var(--bg-subtle);border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:16px">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px">Şifre</div>
                <div style="display:flex;align-items:center;gap:10px">
                    <div id="geciciSifreDeger" style="font-family:monospace;font-size:19px;font-weight:700;letter-spacing:.5px;user-select:all">{{ $gs['sifre'] }}</div>
                    <button type="button" onclick="
                        navigator.clipboard.writeText(document.getElementById('geciciSifreDeger').textContent.trim());
                        this.textContent='Kopyalandı ✓';
                        setTimeout(() => this.textContent='Kopyala', 1800);
                    " class="btn btn-secondary btn-sm" style="margin-left:auto;flex-shrink:0">Kopyala</button>
                </div>
            </div>

            <div style="background:var(--warning-soft);border-left:3px solid var(--warning);border-radius:8px;padding:10px 12px;margin-bottom:18px">
                <p style="margin:0;font-size:12px;color:var(--text)">
                    <strong>Not al veya kopyala.</strong> Bu kutuyu kapattıktan sonra şifre bir daha hiçbir yerden görüntülenemez.
                </p>
            </div>

            <button type="button" onclick="document.getElementById('geciciSifreKutusu').remove()"
                    class="btn btn-primary" style="width:100%">Kaydettim, Kapat</button>
        </div>
    </div>
@endif

{{-- Sadece validation hataları (errors bag) — layout bunu göstermiyor olabilir --}}
@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <strong style="display:block;margin-bottom:6px">
        <i data-lucide="x-circle" style="display:inline-block;vertical-align:-3px;margin-right:6px"></i>
        Form Hataları:
    </strong>
    <ul style="margin:0 0 0 24px;font-size:13px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- Profile Header --}}
<div class="profile-header">
    <div class="profile-avatar" style="overflow:hidden">@if($_fotoUrl)<img src="{{ $_fotoUrl }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ strtoupper(mb_substr($_ad ?: 'M', 0, 1)) }}@endif</div>

    <div class="profile-info">
        <h2>
            {{ $_ad }} {{ $customer->soyad ?? '' }}
            <span class="badge {{ $dbilgi['class'] }}" style="vertical-align:middle;margin-left:8px">{{ $dbilgi['ikon'] }} {{ $dbilgi['label'] }}</span>
        </h2>
        <div class="meta">
            ID #{{ $customer->id }}
            @if(!empty($customer->bayi_kodu)) · Bayi: <strong>{{ $customer->bayi_kodu }}</strong> @endif
        </div>
        <div class="meta-row">
            @if(!empty($customer->email))<span><i data-lucide="mail" style="width:13px;height:13px"></i> {{ $customer->email }}</span>@endif
            @if(!empty($customer->telefon))<span><i data-lucide="phone" style="width:13px;height:13px"></i> {{ $customer->telefon }}</span>@endif
            @if(!empty($customer->il))<span><i data-lucide="map-pin" style="width:13px;height:13px"></i> {{ $customer->il }} / {{ $customer->ilce ?? '—' }}</span>@endif
        </div>
    </div>

    <div class="profile-actions">
        <a href="{{ route('admin.crm.musteriler.edit', $customer->id) }}" class="btn btn-primary btn-sm">
            <i data-lucide="edit-2"></i>
            <span>Düzenle</span>
        </a>
        @if(!empty($customer->email))
        <a href="mailto:{{ $customer->email }}" class="btn btn-secondary btn-sm">
            <i data-lucide="mail"></i>
            <span>Mail</span>
        </a>
        @endif
        @if(!empty($customer->telefon))
        <a href="tel:{{ $customer->telefon }}" class="btn btn-secondary btn-sm">
            <i data-lucide="phone"></i>
            <span>Ara</span>
        </a>
        @endif
        {{-- Tüm tablarda görünen tek buton: SMS veya Mail gönder (rapor/teklif de iliştirilebilir) --}}
        @if(!empty($customer->email) || !empty($customer->telefon))
        <button type="button" class="btn btn-primary btn-sm" onclick="crmSendOpen({})" title="SMS veya e-posta gönder">
            <i data-lucide="send"></i>
            <span>SMS / Mail</span>
        </button>
        @endif
        {{-- Lead sıcaklığı: 3 ayrı buton, istediğine doğrudan tıkla --}}
          <div class="lead-temp-buttons" style="display:inline-flex;gap:4px;border:1px solid
  var(--border,#e5e7eb);border-radius:10px;padding:3px;vertical-align:middle">
      @php $durumListesi = \App\Models\CRM\Customer::DURUMLAR; @endphp
      @foreach($durumListesi as $dk => $dv)
          @php $aktifSic = ($dbilgi['key'] === $dk); @endphp
          <form method="POST" action="{{ route('admin.crm.musteriler.durum', $customer->id) }}" style="display:inline;margin:0">
              @csrf @method('PATCH')
              <input type="hidden" name="durum" value="{{ $dk }}">
              <button type="submit" class="btn btn-sm {{ $aktifSic ? 'btn-primary' : 'btn-secondary' }}"
                      title="{{ $dv['label'] }} yap"
                      style="{{ $aktifSic ? '' : 'opacity:.65' }}">
                  <span>{{ $dv['ikon'] }} {{ $dv['label'] }}</span>
              </button>
          </form>
      @endforeach
  </div>

        {{-- ENGELLENENLER SİSTEMİ (uyeler tablosu) — Pasif/Aktif'ten ayrı --}}
        @if(Route::has('admin.engellenenler.engelle') && Route::has('admin.engellenenler.aktive'))
            @php
                // uyeler tablosundaki gerçek durum kontrolü (durum=0 → engellenmiş)
                $uyeKaydi = null;
                if (!empty($customer->id)) {
                    try {
                        $uyeKaydi = \Illuminate\Support\Facades\DB::table('uyeler')
                            ->where('id', $customer->id)
                            ->first(['id', 'durum']);
                    } catch (\Throwable $e) {}
                }
                $engellenmis = $uyeKaydi && (int)($uyeKaydi->durum ?? 1) === 0;
            @endphp

            @if($engellenmis)
                <form method="POST" action="{{ route('admin.engellenenler.aktive', $customer->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Bu kullanıcının engelini kaldırmak istediğinden emin misin?')"
                            title="uyeler.durum = 1 yap (sisteme tekrar girebilir)">
                        <i data-lucide="unlock"></i>
                        <span>Engeli Kaldır</span>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.engellenenler.engelle', $customer->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Bu kullanıcıyı ENGELLEMEK istediğinden emin misin?\n\nKullanıcı sisteme giriş yapamayacak (uyeler.durum = 0).')"
                            title="uyeler.durum = 0 yap (kullanıcı sisteme giremez)">
                        <i data-lucide="ban"></i>
                        <span>Engelle</span>
                    </button>
                </form>
            @endif
        @endif
    </div>
</div>

{{-- Stat strip (bakiye, harcama özet) --}}
<div class="mini-stat-grid">
    <div class="mini-stat">
        <div class="lbl">Bakiye</div>
        <div class="val">₺{{ number_format($customer->bakiye ?? 0, 2, ',', '.') }}</div>
        <div class="sub">Mevcut bakiye</div>
    </div>
    @php
        $_dnbankCoin = 0;
        if (!empty($customer->email) && \Illuminate\Support\Facades\Schema::hasColumn('uyeler', 'dnbank_bakiye')) {
            $_dnbankCoin = (float) (\Illuminate\Support\Facades\DB::table('uyeler')->where('email', $customer->email)->value('dnbank_bakiye') ?? 0);
        }
    @endphp
    <div class="mini-stat">
        <div class="lbl">🏦 DN Bank Coin</div>
        <div class="val">₺{{ number_format($_dnbankCoin, 2, ',', '.') }}</div>
        <div class="sub">Kullanılabilir coin</div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Toplam Harcama</div>
        <div class="val">₺{{ number_format($customer->toplam_harcama ?? 0, 2, ',', '.') }}</div>
        <div class="sub">Tüm zamanlar</div>
    </div>
    <div class="mini-stat info">
        <div class="lbl">Fatura Sayısı</div>
        <div class="val">{{ count($faturalar ?? $customer->faturalar ?? []) }}</div>
        <div class="sub">Aktif faturalar</div>
    </div>
    <div class="mini-stat warning">
        <div class="lbl">Üyelik</div>
        @php
            $uyelikGun = 0;
            $kt = $customer->created_at ?? null;
            if ($kt) {
                try { $uyelikGun = (int) abs(\Carbon\Carbon::parse($kt)->diffInDays(now())); } catch (\Throwable $e) {}
            }
        @endphp
        <div class="val">{{ number_format($uyelikGun) }} <span style="font-size:13px;font-weight:500;color:var(--text-muted)">gün</span></div>
        <div class="sub">{{ $kt ? \Carbon\Carbon::parse($kt)->format('d.m.Y') : '—' }}'den beri</div>
    </div>
</div>

{{-- Tab Nav (orijinal tasarım) — aktif sekmeye tekrar tıklayınca içerik açılır/kapanır --}}
<nav class="tab-nav">
    @foreach($tabs as $key => $info)
        <a href="{{ route('admin.crm.musteriler.show', ['id' => $customer->id, 'tab' => $key]) }}"
           class="tab-nav-link {{ $aktifTab == $key ? 'active' : '' }}"
           @if($aktifTab === $key) onclick="return crmTabToggle(event)" @endif>
            <span>{{ $info[0] }}</span>
            <span>{{ $info[1] }}</span>
        </a>
    @endforeach
</nav>

{{-- Tab Content --}}
<div id="crmTabContent">
@switch($aktifTab)
    @case('bilgiler')   @include('admin.crm.customers.tabs.bilgiler')   @break
    @case('notlar')     @include('admin.crm.customers.tabs.notlar')     @break
    @case('faturalar')  @include('admin.crm.customers.tabs.faturalar')  @break
    @case('teklifler')  @include('admin.crm.customers.tabs.teklifler')  @break
    @case('sozlesmeler') @include('admin.crm.customers.tabs.sozlesmeler') @break
    @case('krediler')   @include('admin.crm.customers.tabs.krediler')   @break
    @case('hizmetler')  @include('admin.crm.customers.tabs.hizmetler')  @break
    @case('e-faturalar') @include('admin.crm.customers.tabs.e-faturalar') @break
    @case('destek')     @include('admin.crm.customers.tabs.destek')     @break
    @case('raporlar')   @include('admin.crm.customers.tabs.raporlar')   @break
    @case('referanslar') @include('admin.crm.customers.tabs.referanslar') @break
    @case('bildirimler') @include('admin.crm.customers.tabs.bildirimler') @break
    @case('randevular')  @include('admin.crm.customers.tabs.randevular')  @break
    @case('gorevler')    @include('admin.crm.customers.tabs.gorevler')    @break
    @default            @include('admin.crm.customers.tabs.bilgiler')
@endswitch
</div>

{{-- Aktif sekmeye tekrar tıklayınca içeriği aç/kapat (dropdown mantığı) --}}
<script>
function crmTabToggle(e){
    const body = document.getElementById('crmTabContent');
    if(!body) return true;
    e.preventDefault();
    const willShow = (body.style.display === 'none');
    body.style.display = willShow ? '' : 'none';
    const link = e.currentTarget;
    if(link && link.classList) link.classList.toggle('tab-collapsed', !willShow);
    return false;
}
</script>

{{-- ═══ HIZLI GÖNDERİM MODALI (SMS / Mail) — profil başlığından açılır, tüm tablarda erişilebilir ═══ --}}
<div id="crmSendModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px">
  <form method="POST" action="{{ route('admin.crm.musteriler.hizli-gonder', $customer->id) }}"
        style="background:var(--card,#fff);color:var(--text,#111);width:100%;max-width:520px;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;max-height:92vh;overflow-y:auto"
        onsubmit="return crmSendSubmit(this)">
    @csrf
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border,#eee)">
      <strong style="font-size:16px"><i data-lucide="send" style="width:16px;height:16px;vertical-align:-2px"></i> Hızlı Gönderim</strong>
      <button type="button" onclick="crmSendClose()" style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-muted,#888);line-height:1">&times;</button>
    </div>
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;gap:8px">
        <button type="button" id="crmKanalMail" onclick="crmSendKanal('mail')" class="btn btn-sm btn-primary" style="flex:1;justify-content:center">📧 E-Posta</button>
        <button type="button" id="crmKanalSms" onclick="crmSendKanal('sms')" class="btn btn-sm btn-secondary" style="flex:1;justify-content:center">💬 SMS</button>
      </div>
      <input type="hidden" name="kanal" id="crmKanal" value="mail">
      <div style="font-size:13px;color:var(--text-muted,#777)">Alıcı: <strong id="crmAlici" style="color:var(--text,#111)"></strong></div>
      <div>
        <label style="font-size:12px;font-weight:600;color:var(--text-muted,#777);text-transform:uppercase;letter-spacing:.04em">Şablon</label>
        <select id="crmSablon" onchange="crmSablonSec(this.value)" class="form-control" style="width:100%;margin-top:4px">
          <option value="">— Şablon seç (opsiyonel) —</option>
        </select>
      </div>
      <div id="crmKonuWrap">
        <label style="font-size:12px;font-weight:600;color:var(--text-muted,#777);text-transform:uppercase;letter-spacing:.04em">Konu</label>
        <input type="text" name="konu" id="crmKonu" maxlength="200" class="form-control" style="width:100%;margin-top:4px" placeholder="E-posta konusu">
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;color:var(--text-muted,#777);text-transform:uppercase;letter-spacing:.04em">Mesaj <span id="crmSayac" style="float:right;font-weight:400;text-transform:none;color:var(--text-muted,#999)"></span></label>
        <textarea name="mesaj" id="crmMesaj" rows="5" maxlength="2000" required class="form-control" style="width:100%;margin-top:4px" oninput="crmSayacGuncelle()" placeholder="Mesajınız...  ({ad} yazarsanız müşteri adıyla değişir)"></textarea>
      </div>
      <div id="crmEkWrap" style="display:none;font-size:13px;background:var(--bg-subtle,#f6f6f6);border-radius:8px;padding:10px 12px">
        📎 Ekli belge: <strong id="crmEkBaslikTxt"></strong>
        <button type="button" onclick="crmEkKaldir()" style="background:none;border:none;color:var(--danger,#dc2626);cursor:pointer;float:right">kaldır</button>
      </div>
      <input type="hidden" name="ek_link" id="crmEkLink">
      <input type="hidden" name="ek_baslik" id="crmEkBaslik">
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--border,#eee)">
      <button type="button" onclick="crmSendClose()" class="btn btn-secondary btn-sm">İptal</button>
      <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="send"></i> <span>Gönder</span></button>
    </div>
  </form>
</div>

<script>
const CRM_MUSTERI = { email: @json($customer->email ?? ''), telefon: @json($customer->telefon ?? ''), ad: @json($customer->adi ?? $customer->ad ?? '') };
// Şablonlar — admin panelindeki "Mail Şablonları" (mail_templates) tablosundan gelir (madde 2)
@php
    $crmSablonlar = \Illuminate\Support\Facades\Schema::hasTable('mail_templates')
        ? \Illuminate\Support\Facades\DB::table('mail_templates')->where('aktif', 1)->orderBy('name')->get()
            ->map(fn ($t) => ['ad' => $t->name, 'konu' => $t->subject, 'metin' => $t->body])->values()
        : collect();
@endphp
const CRM_SABLONLAR = @json($crmSablonlar);
function crmSendFillSablon(){ const s=document.getElementById('crmSablon'); s.length=1; CRM_SABLONLAR.forEach((t,i)=>{ const o=document.createElement('option'); o.value=i; o.textContent=t.ad; s.appendChild(o); }); }
function crmSablonSec(i){ if(i==='')return; const t=CRM_SABLONLAR[i]; if(!t)return; document.getElementById('crmMesaj').value=t.metin||''; if(t.konu)document.getElementById('crmKonu').value=t.konu; crmSayacGuncelle(); }
function crmSendKanal(k){
  document.getElementById('crmKanal').value=k; const mail=(k==='mail');
  document.getElementById('crmKanalMail').className='btn btn-sm '+(mail?'btn-primary':'btn-secondary');
  document.getElementById('crmKanalSms').className='btn btn-sm '+(!mail?'btn-primary':'btn-secondary');
  document.getElementById('crmKonuWrap').style.display=mail?'':'none';
  document.getElementById('crmAlici').textContent=mail?(CRM_MUSTERI.email||'— e-posta yok —'):(CRM_MUSTERI.telefon||'— telefon yok —');
  crmSayacGuncelle();
}
function crmSayacGuncelle(){ const sms=document.getElementById('crmKanal').value==='sms'; const len=document.getElementById('crmMesaj').value.length; document.getElementById('crmSayac').textContent=sms?(len+' karakter · '+(Math.ceil(len/160)||1)+' SMS'):''; }
function crmSendOpen(opts){
  opts=opts||{}; crmSendFillSablon();
  const kanal=opts.kanal||(CRM_MUSTERI.email?'mail':'sms');
  document.getElementById('crmMesaj').value=opts.mesaj||'';
  document.getElementById('crmKonu').value=opts.konu||'';
  document.getElementById('crmSablon').value='';
  if(opts.ek_link){ document.getElementById('crmEkLink').value=opts.ek_link; document.getElementById('crmEkBaslik').value=opts.ek_baslik||'Belge'; document.getElementById('crmEkBaslikTxt').textContent=opts.ek_baslik||'Belge'; document.getElementById('crmEkWrap').style.display=''; } else { crmEkKaldir(); }
  crmSendKanal(kanal);
  document.getElementById('crmSendModal').style.display='flex';
  if(window.lucide) lucide.createIcons();
}
function crmEkKaldir(){ document.getElementById('crmEkLink').value=''; document.getElementById('crmEkBaslik').value=''; document.getElementById('crmEkWrap').style.display='none'; }
function crmSendClose(){ document.getElementById('crmSendModal').style.display='none'; }
function crmSendSubmit(){
  const kanal=document.getElementById('crmKanal').value;
  if(kanal==='mail' && !CRM_MUSTERI.email){ alert('Müşterinin e-posta adresi yok.'); return false; }
  if(kanal==='sms' && !CRM_MUSTERI.telefon){ alert('Müşterinin telefon numarası yok.'); return false; }
  if(!document.getElementById('crmMesaj').value.trim()){ alert('Mesaj boş olamaz.'); return false; }
  return true;
}
window.crmSendOpen=crmSendOpen;
document.getElementById('crmSendModal').addEventListener('click', function(e){ if(e.target===this) crmSendClose(); });
</script>

@endsection