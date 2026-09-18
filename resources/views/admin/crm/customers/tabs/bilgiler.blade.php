{{-- Tab: Bilgiler --}}

<div class="form-grid" style="grid-template-columns: 2fr 1fr; align-items: start">

    {{-- SOL: Detay --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Kişisel Bilgiler</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Ad / Unvan</span>
                    <span class="val">{{ $customer->adi ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Email</span>
                    <span class="val">{{ $customer->email ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Telefon</span>
                    <span class="val">{{ $customer->telefon ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">GSM</span>
                    <span class="val">{{ $customer->gsm ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">TC Kimlik</span>
                    <span class="val">{{ $customer->tc_kimlik ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Doğum Tarihi</span>
                    <span class="val">{{ $customer->dogum_tarihi ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Web Sitesi</span>
                    <span class="val">
                        @if(!empty($customer->web_sitesi))
                            <a href="{{ $customer->web_sitesi }}" target="_blank" style="color:var(--brand)">{{ $customer->web_sitesi }}</a>
                        @else — @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Sektör</span>
                    <span class="val">{{ $customer->sektor ?? '—' }}</span>
                </div>
            </div>
        </div>

        @if(!empty($customer->unvan) || !empty($customer->vergi_no))
        <div class="section">
            <div class="section-title">
                <i data-lucide="briefcase"></i>
                <span>Firma Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Unvan</span>
                    <span class="val">{{ $customer->unvan ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Firma Tipi</span>
                    <span class="val">{{ $customer->firma_tipi ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vergi No</span>
                    <span class="val">{{ $customer->vergi_no ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Vergi Dairesi</span>
                    <span class="val">{{ $customer->vergi_dairesi ?? '—' }}</span>
                </div>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="section-title">
                <i data-lucide="map-pin"></i>
                <span>Adres</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">İl / İlçe</span>
                    <span class="val">{{ $customer->il ?? '—' }} / {{ $customer->ilce ?? '—' }}</span>
                </div>
                <div class="info-item" style="grid-column:1/-1">
                    <span class="lbl">Açık Adres</span>
                    <span class="val">{{ $customer->adres ?? '—' }}</span>
                </div>
            </div>
        </div>

        @if(!empty($customer->etiketler))
        <div class="section">
            <div class="section-title">
                <i data-lucide="tag"></i>
                <span>Etiketler</span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                @foreach((array)$customer->etiketler as $et)
                    <span class="badge badge-brand">{{ $et }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($customer->not_icerik))
        <div class="section">
            <div class="section-title">
                <i data-lucide="sticky-note"></i>
                <span>Dahili Not</span>
            </div>
            <p style="white-space:pre-wrap;color:var(--text-secondary);font-size:13px;line-height:1.6">{{ $customer->not_icerik }}</p>
        </div>
        @endif
    </div>

    {{-- SAĞ: Yan panel --}}
    <div>
        <div class="section" style="text-align:center">
            <div class="section-title" style="justify-content:center">
                <i data-lucide="wallet"></i>
                <span>Bakiye</span>
            </div>
            <div style="font-size:34px;font-weight:700;color:{{ ($customer->bakiye ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' }};letter-spacing:-0.02em">
                ₺{{ number_format($customer->bakiye ?? 0, 2, ',', '.') }}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Mevcut bakiye</div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı Aksiyonlar</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <a href="{{ route('admin.crm.musteriler.edit', $customer->id) }}" class="btn btn-primary" style="width:100%">
                    <i data-lucide="edit-2"></i>
                    <span>Düzenle</span>
                </a>
                @if(!empty($customer->email))
                <a href="mailto:{{ $customer->email }}" class="btn btn-secondary" style="width:100%">
                    <i data-lucide="mail"></i>
                    <span>Mail Gönder</span>
                </a>
                @endif
                @if(!empty($customer->telefon))
                <a href="tel:{{ $customer->telefon }}" class="btn btn-secondary" style="width:100%">
                    <i data-lucide="phone"></i>
                    <span>Ara</span>
                </a>
                @endif
                @if(!empty($customer->email) && Route::has('admin.crm.musteriler.gecici-sifre'))
                <form method="POST" action="{{ route('admin.crm.musteriler.gecici-sifre', $customer->id) }}"
                      onsubmit="return confirm('{{ $customer->email }} adresine 8 karakterlik tek kullanımlık şifre gönderilsin mi?')"
                      style="margin:0">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width:100%">
                        <i data-lucide="key-round"></i>
                        <span>Tek Kullanımlık Şifre Gönder</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

</div>