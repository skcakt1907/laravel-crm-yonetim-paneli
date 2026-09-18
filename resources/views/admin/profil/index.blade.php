@extends('admin._layout')

@section('title', 'Profilim')

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     PROFİL SAYFASI — GitHub Settings / Vercel Account tarzı
     Tab Navigation + Section'lı kompakt tasarım
═══════════════════════════════════════════════════════════ --}}

<div class="page-header">
    <div>
        <div class="page-title">Hesap Ayarları</div>
        <div class="page-subtitle">Profil bilgilerinizi ve güvenlik tercihlerinizi yönetin</div>
    </div>
</div>

{{-- Profil Kart (özet) --}}
<div class="section" style="display:flex;align-items:center;gap:20px;padding:24px">
    <div class="user-avatar" style="width:72px;height:72px;font-size:28px;flex-shrink:0;box-shadow:var(--shadow-md);overflow:hidden">
        @if(!empty($yonetici->profil_foto ?? null) && is_file(public_path($yonetici->profil_foto)))
            <img src="{{ asset($yonetici->profil_foto) }}?v={{ time() }}" alt="" style="width:100%;height:100%;object-fit:cover">
        @else
            {{ strtoupper(mb_substr($yonetici->adi ?? $yonetici->kullaniciadi ?? 'A', 0, 1, 'UTF-8')) }}
        @endif
    </div>
    <div style="flex:1;min-width:0">
        <div style="font-size:18px;font-weight:700;color:var(--text);margin-bottom:4px">
            {{ $yonetici->adi ?? $yonetici->kullaniciadi }}
        </div>
        <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:13px;color:var(--text-secondary)">
            <span><i data-lucide="at-sign" style="width:13px;height:13px;display:inline;vertical-align:-2px;margin-right:3px"></i>{{ $yonetici->kullaniciadi ?? '—' }}</span>
            <span><i data-lucide="mail" style="width:13px;height:13px;display:inline;vertical-align:-2px;margin-right:3px"></i>{{ $yonetici->email ?? $yonetici->eposta ?? '—' }}</span>
            @if(!empty($yonetici->telefon))
            <span><i data-lucide="phone" style="width:13px;height:13px;display:inline;vertical-align:-2px;margin-right:3px"></i>{{ $yonetici->telefon }}</span>
            @endif
        </div>
        <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap">
            <span class="badge" style="background:var(--brand-soft);color:var(--brand);border:1px solid var(--brand-medium);padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600">
                <i data-lucide="shield" style="width:12px;height:12px;display:inline;vertical-align:-2px;margin-right:3px"></i>
                @if(($yonetici->rol ?? 0) == 1) Patron
                @elseif(($yonetici->rol ?? 0) == 2) Çalışan
                @elseif(($yonetici->rol ?? 0) == 3) Bayi
                @else Kullanıcı @endif
            </span>
            <span class="badge" style="background:var(--success-soft);color:var(--success);border:1px solid rgba(16,185,129,.25);padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600">
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--success);margin-right:4px;vertical-align:1px"></span>
                Aktif
            </span>
            <span class="badge" style="background:var(--bg-subtle);color:var(--text-secondary);border:1px solid var(--border);padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600">
                <i data-lucide="hash" style="width:12px;height:12px;display:inline;vertical-align:-2px;margin-right:3px"></i>
                ID #{{ $yonetici->id ?? '—' }}
            </span>
        </div>
    </div>
    <div class="text-right" style="font-size:12px;color:var(--text-muted);text-align:right;flex-shrink:0">
        <div>Üyelik</div>
        <div style="font-weight:600;color:var(--text-secondary);margin-top:2px">
            @if(!empty($yonetici->created_at))
                @php try { echo \Carbon\Carbon::parse($yonetici->created_at)->format('d M Y'); } catch(\Throwable $e) { echo '—'; } @endphp
            @else — @endif
        </div>
    </div>
</div>

{{-- Tab Nav --}}
<div class="tab-nav">
    <a href="#profil-tab" class="tab-nav-link active" data-tab="profil-tab" onclick="showTab(event,'profil-tab')">
        <i data-lucide="user"></i>
        <span>Profil</span>
    </a>
    <a href="#guvenlik-tab" class="tab-nav-link" data-tab="guvenlik-tab" onclick="showTab(event,'guvenlik-tab')">
        <i data-lucide="lock"></i>
        <span>Güvenlik</span>
    </a>
    <a href="#hesap-tab" class="tab-nav-link" data-tab="hesap-tab" onclick="showTab(event,'hesap-tab')">
        <i data-lucide="info"></i>
        <span>Hesap Bilgileri</span>
    </a>
</div>

{{-- ════════════════ TAB 1: PROFİL ════════════════ --}}
<div id="profil-tab" class="tab-content">
    <div class="section">
        <div class="section-title">
            <i data-lucide="user-cog"></i>
            <span>Kişisel Bilgiler</span>
        </div>

        <form action="{{ route('admin.profil.guncelle') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @php $fotoVar = !empty($yonetici->profil_foto ?? null) && is_file(public_path($yonetici->profil_foto)); @endphp
            <div class="form-group" style="margin-bottom:18px">
                <label class="form-label">Profil Fotoğrafı</label>
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    @if($fotoVar)
                        <img src="{{ asset($yonetici->profil_foto) }}?v={{ time() }}" alt=""
                             style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
                    @endif
                    <input type="file" name="profil_foto" accept=".jpg,.jpeg,.png,.webp" class="form-input" style="max-width:340px">
                    @if($fotoVar)
                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--danger);cursor:pointer">
                            <input type="checkbox" name="foto_kaldir" value="1" style="width:auto;margin:0">
                            <span>Fotoğrafı kaldır</span>
                        </label>
                    @endif
                </div>
                <div class="form-help">JPG, PNG veya WEBP — en fazla 2MB. Kare görseller en iyi sonucu verir.</div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Görünen Ad <span class="required">*</span></label>
                    <input type="text" name="adi" class="form-input"
                           value="{{ old('adi', $yonetici->adi ?? '') }}"
                           placeholder="Ad Soyad" required>
                    <div class="form-help">Diğer kullanıcılara ve müşterilere bu isim gösterilir</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-input" value="{{ $yonetici->kullaniciadi ?? '' }}"
                           disabled style="opacity:.6;cursor:not-allowed">
                    <div class="form-help">
                        <i data-lucide="lock" style="width:11px;height:11px;display:inline;vertical-align:-1px"></i>
                        Değiştirilemez — sistem girişi için kullanılır
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">E-posta Adresi <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input"
                           value="{{ old('email', $yonetici->email ?? $yonetici->eposta ?? '') }}"
                           placeholder="ornek@isortagim.com" required>
                    <div class="form-help">Bildirimler ve şifre sıfırlama bu adrese gönderilir</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="telefon" class="form-input"
                           value="{{ old('telefon', $yonetici->telefon ?? '') }}"
                           placeholder="05XX XXX XX XX">
                    <div class="form-help">Acil durumlarda iletişim için (opsiyonel)</div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:20px;margin-top:20px;border-top:1px solid var(--border)">
                <button type="reset" class="btn btn-ghost">
                    <i data-lucide="rotate-ccw"></i>
                    <span>Sıfırla</span>
                </button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="check"></i>
                    <span>Değişiklikleri Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════ TAB 2: GÜVENLİK ════════════════ --}}
<div id="guvenlik-tab" class="tab-content" style="display:none">
    <div class="section">
        <div class="section-title">
            <i data-lucide="key-round"></i>
            <span>Şifre Değiştir</span>
        </div>

        <div style="background:var(--info-soft);border:1px solid rgba(59,130,246,.2);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
            <i data-lucide="info" style="width:16px;height:16px;color:var(--info);flex-shrink:0;margin-top:2px"></i>
            <div style="font-size:13px;color:var(--text-secondary);line-height:1.5">
                Güvenliğiniz için en az <strong>6 karakter</strong> uzunluğunda, <strong>büyük/küçük harf</strong>, <strong>rakam</strong> ve <strong>özel karakter</strong> içeren bir şifre seçin.
            </div>
        </div>

        <form action="{{ route('admin.profil.sifre.degistir') }}" method="POST">
            @csrf

            <div class="form-group" style="max-width:520px">
                <label class="form-label">Mevcut Şifre <span class="required">*</span></label>
                <div style="position:relative">
                    <input type="password" name="eski_sifre" id="eski_sifre" class="form-input" required
                           placeholder="••••••••" style="padding-right:42px">
                    <button type="button" onclick="togglePass('eski_sifre',this)"
                            style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:transparent;border:none;cursor:pointer;color:var(--text-muted);width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center"
                            onmouseover="this.style.background='var(--bg-subtle)';this.style.color='var(--text)'"
                            onmouseout="this.style.background='transparent';this.style.color='var(--text-muted)'">
                        <i data-lucide="eye" style="width:16px;height:16px"></i>
                    </button>
                </div>
            </div>

            <div class="form-grid" style="max-width:720px">
                <div class="form-group">
                    <label class="form-label">Yeni Şifre <span class="required">*</span></label>
                    <div style="position:relative">
                        <input type="password" name="yeni_sifre" id="yeni_sifre" class="form-input" required minlength="6"
                               placeholder="En az 6 karakter" style="padding-right:42px">
                        <button type="button" onclick="togglePass('yeni_sifre',this)"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:transparent;border:none;cursor:pointer;color:var(--text-muted);width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                        </button>
                    </div>
                    <div id="sifreGucBar" style="height:4px;background:var(--bg-subtle);border-radius:2px;margin-top:8px;overflow:hidden">
                        <div id="sifreGucFill" style="height:100%;width:0%;background:var(--danger);transition:all .3s;border-radius:2px"></div>
                    </div>
                    <div id="sifreGuc" class="form-help" style="margin-top:6px">Güçlü bir şifre seçin</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Yeni Şifre (Tekrar) <span class="required">*</span></label>
                    <div style="position:relative">
                        <input type="password" name="yeni_sifre_tekrar" id="yeni_sifre_tekrar" class="form-input" required minlength="6"
                               placeholder="Aynısını tekrar yazın" style="padding-right:42px">
                        <button type="button" onclick="togglePass('yeni_sifre_tekrar',this)"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:transparent;border:none;cursor:pointer;color:var(--text-muted);width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                        </button>
                    </div>
                    <div id="sifreEslesme" class="form-help" style="margin-top:6px">Yeni şifrenizi tekrar yazın</div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:20px;margin-top:8px;border-top:1px solid var(--border)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="shield-check"></i>
                    <span>Şifreyi Değiştir</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════ TAB 3: HESAP BİLGİLERİ ════════════════ --}}
<div id="hesap-tab" class="tab-content" style="display:none">
    <div class="section">
        <div class="section-title">
            <i data-lucide="badge-info"></i>
            <span>Hesap Detayları</span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
            <div class="info-row">
                <div class="info-label">
                    <i data-lucide="shield" style="width:14px;height:14px"></i>
                    Rol
                </div>
                <div class="info-value">
                    @if(($yonetici->rol ?? 0) == 1) Patron — Tam yetki
                    @elseif(($yonetici->rol ?? 0) == 2) Çalışan — Sınırlı yetki
                    @elseif(($yonetici->rol ?? 0) == 3) Bayi — Bayi paneli
                    @else Kullanıcı @endif
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">
                    <i data-lucide="hash" style="width:14px;height:14px"></i>
                    Üye Numarası
                </div>
                <div class="info-value" style="font-family:monospace">#{{ $yonetici->id ?? '—' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">
                    <i data-lucide="at-sign" style="width:14px;height:14px"></i>
                    Kullanıcı Adı
                </div>
                <div class="info-value" style="font-family:monospace">{{ $yonetici->kullaniciadi ?? '—' }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">
                    <i data-lucide="calendar-days" style="width:14px;height:14px"></i>
                    Üyelik Tarihi
                </div>
                <div class="info-value">
                    @if(!empty($yonetici->created_at))
                        @php try { echo \Carbon\Carbon::parse($yonetici->created_at)->format('d.m.Y H:i'); } catch(\Throwable $e) { echo '—'; } @endphp
                    @else — @endif
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">
                    <i data-lucide="globe" style="width:14px;height:14px"></i>
                    Son IP Adresi
                </div>
                <div class="info-value" style="font-family:monospace">{{ request()->ip() }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">
                    <i data-lucide="activity" style="width:14px;height:14px"></i>
                    Hesap Durumu
                </div>
                <div class="info-value">
                    <span style="display:inline-flex;align-items:center;gap:6px;color:var(--success);font-weight:600">
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 0 3px var(--success-soft)"></span>
                        Aktif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Info rows (GitHub Settings tarzı) */
.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 4px;
    border-bottom: 1px solid var(--border);
}
.info-row:nth-last-child(-n+2) { border-bottom: none; }
.info-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
}
.info-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    text-align: right;
}

/* Tab content fade-in */
.tab-content {
    animation: fadeIn .25s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 720px) {
    .info-row { padding: 12px 0 }
}
</style>

<script>
// Tab switching
function showTab(e, tabId) {
    if (e) e.preventDefault();
    document.querySelectorAll('.tab-nav-link').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    var link = document.querySelector('[data-tab="'+tabId+'"]');
    var content = document.getElementById(tabId);
    if (link) link.classList.add('active');
    if (content) content.style.display = '';
    try { history.replaceState(null, '', '#'+tabId); } catch(_) {}
    if (window.lucide) window.lucide.createIcons();
}

// İlk yüklemede URL hash kontrol
(function(){
    var hash = window.location.hash.replace('#','');
    if (hash && document.getElementById(hash)) showTab(null, hash);
})();

// Şifre göster/gizle
function togglePass(id, btn) {
    var inp = document.getElementById(id);
    if (!inp) return;
    var icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        if (icon) icon.setAttribute('data-lucide', 'eye-off');
    } else {
        inp.type = 'password';
        if (icon) icon.setAttribute('data-lucide', 'eye');
    }
    if (window.lucide) window.lucide.createIcons();
}

// Şifre güç + eşleşme
(function(){
    var sif = document.getElementById('yeni_sifre');
    var tek = document.getElementById('yeni_sifre_tekrar');
    var guc = document.getElementById('sifreGuc');
    var bar = document.getElementById('sifreGucFill');
    var esl = document.getElementById('sifreEslesme');

    function gucKontrol() {
        if (!sif || !bar || !guc) return;
        var v = sif.value, skor = 0;
        if (v.length >= 6) skor++;
        if (v.length >= 10) skor++;
        if (/[A-Z]/.test(v)) skor++;
        if (/[0-9]/.test(v)) skor++;
        if (/[^A-Za-z0-9]/.test(v)) skor++;

        var renkler = ['#ef4444','#f97316','#f59e0b','#84cc16','#10b981','#10b981'];
        var yuzde = [0, 20, 40, 60, 80, 100];
        var metinler = [
            'Güçlü bir şifre seçin',
            '🔴 Çok zayıf — Çok kolay tahmin edilir',
            '🟠 Zayıf — Daha karmaşık yapın',
            '🟡 Orta — İyileştirilebilir',
            '🟢 Güçlü — Tamam',
            '✅ Çok güçlü — Mükemmel'
        ];

        if (!v) {
            bar.style.width = '0%';
            guc.textContent = metinler[0];
            guc.style.color = '';
            return;
        }
        bar.style.width = yuzde[skor] + '%';
        bar.style.background = renkler[skor];
        guc.textContent = metinler[skor];
        guc.style.color = renkler[skor];
    }

    function eslesmeKontrol() {
        if (!sif || !tek || !esl) return;
        if (!tek.value) {
            esl.textContent = 'Yeni şifrenizi tekrar yazın';
            esl.style.color = '';
            return;
        }
        if (sif.value === tek.value) {
            esl.innerHTML = '<i data-lucide="check" style="width:11px;height:11px;display:inline;vertical-align:-1px"></i> Şifreler eşleşiyor';
            esl.style.color = 'var(--success)';
        } else {
            esl.innerHTML = '<i data-lucide="x" style="width:11px;height:11px;display:inline;vertical-align:-1px"></i> Şifreler eşleşmiyor';
            esl.style.color = 'var(--danger)';
        }
        if (window.lucide) window.lucide.createIcons();
    }

    if (sif) sif.addEventListener('input', function(){ gucKontrol(); eslesmeKontrol(); });
    if (tek) tek.addEventListener('input', eslesmeKontrol);
})();
</script>

@endsection