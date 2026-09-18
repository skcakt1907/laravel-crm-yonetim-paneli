{{-- Tab: Krediler (Taksitli Ödeme Planı) --}}
@php
    // Hem email hem crm_musteri_id ile uye_id'yi bul (otomatik üye sonrası dahil)
    $musteriUyeId = null;
    if (!empty($customer->email)) {
        $musteriUyeId = \Illuminate\Support\Facades\DB::table('uyeler')
            ->where('email', $customer->email)
            ->value('id');
    }

    $krediler = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('musteri_krediler')) {
        $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('musteri_krediler');

        $query = \Illuminate\Support\Facades\DB::table('musteri_krediler');
        $whereAdded = false;

        // crm_musteri_id kolonu varsa onunla da ara (en güvenli)
        if (in_array('crm_musteri_id', $kolonlar)) {
            $query->where('crm_musteri_id', $customer->id);
            $whereAdded = true;
        }

        // uye_id veya uyeid kolonu — hangisi varsa
        if ($musteriUyeId) {
            $uyeIdKol = in_array('uye_id', $kolonlar) ? 'uye_id'
                      : (in_array('uyeid', $kolonlar) ? 'uyeid' : null);

            if ($uyeIdKol) {
                if ($whereAdded) {
                    $query->orWhere($uyeIdKol, $musteriUyeId);
                } else {
                    $query->where($uyeIdKol, $musteriUyeId);
                    $whereAdded = true;
                }
            }
        }

        if ($whereAdded) {
            $krediler = $query->orderByDesc('id')->get();
        }
    }

    $toplamAna    = $krediler->sum('ana_para');
    $toplamKalan  = $krediler->sum('kalan_borc');
    $toplamOdenen = $krediler->sum('odenen_tutar');

    // DN Bank kullanılabilir coin bakiyesi
    $dnbankCoin = 0;
    if ($musteriUyeId && \Illuminate\Support\Facades\Schema::hasColumn('uyeler', 'dnbank_bakiye')) {
        $dnbankCoin = (float) (\Illuminate\Support\Facades\DB::table('uyeler')->where('id', $musteriUyeId)->value('dnbank_bakiye') ?? 0);
    }
@endphp

<div class="mini-stat-grid">
    <div class="mini-stat" style="border-left:4px solid #1a2332">
        <div class="lbl">🏦 Kullanılabilir Coin</div>
        <div class="val">₺{{ number_format((float) $dnbankCoin, 2, ',', '.') }}</div>
        <div class="sub">Müşterinin harcayabileceği DN Bank coin</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Açılan Toplam Kredi</div>
        <div class="val">₺{{ number_format((float) $toplamAna, 2, ',', '.') }}</div>
        <div class="sub">{{ $krediler->count() }} kredi</div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Ödenen</div>
        <div class="val" style="color:var(--success)">₺{{ number_format((float) $toplamOdenen, 2, ',', '.') }}</div>
    </div>
    <div class="mini-stat danger">
        <div class="lbl">Kalan Borç</div>
        <div class="val" style="color:var(--danger)">₺{{ number_format((float) $toplamKalan, 2, ',', '.') }}</div>
    </div>
</div>

<div class="section">
    <div class="section-title">
        <i data-lucide="landmark"></i>
        <span>Yeni DN Bank Kredisi (Coin yükle + Taksitli geri ödeme)</span>
    </div>

    <div class="alert" style="background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start">
        <i data-lucide="info"></i>
        <div style="font-size:13px">
            Kredi açıldığında girilen tutar kadar <strong>DN Bank coin</strong> müşterinin hesabına yüklenir
            (alışverişlerde kullanır). Aynı tutar, aşağıda belirlediğiniz vadeye göre <strong>taksitli geri ödeme</strong>
            planı olarak borçlandırılır. 1 coin = 1 ₺.
        </div>
    </div>

    @if(empty($customer->email))
    <div class="alert alert-danger" style="margin-bottom:16px">
        <i data-lucide="alert-circle"></i>
        <div>
            <strong>Müşteri e-postası tanımlı değil.</strong>
            Kredi açmak için önce müşteriye e-posta adresi ekleyin.
            <a href="{{ route('admin.crm.musteriler.edit', $customer->id) }}" style="color:var(--danger);font-weight:600;text-decoration:underline">Müşteriyi düzenle</a>
        </div>
    </div>
    @elseif(!$musteriUyeId)
    <div class="alert alert-info" style="margin-bottom:16px">
        <i data-lucide="info"></i>
        <div>
            <strong>Bilgi:</strong> Müşteri henüz üye olarak kayıtlı değil.
            Plan oluştururken otomatik olarak <strong>üye kaydı da açılacak</strong>.
            (E-posta: {{ $customer->email }})
        </div>
    </div>
    @endif

    <form action="{{ route('admin.crm.musteriler.kredi.ekle', $customer->id) }}" method="POST">
        @csrf
        <input type="hidden" name="faiz_orani" value="0">

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Coin / Kredi Tutarı (₺) <span class="required">*</span></label>
                <input type="number" name="ana_para" step="0.01" min="1" required class="form-input" placeholder="12000.00" value="{{ old('ana_para') }}" oninput="hesaplaTaksit()" id="krAnaPara">
                <div class="form-help">Hesaba yüklenecek coin = bu tutar (1 coin = 1 ₺)</div>
            </div>
            <div class="form-group">
                <label class="form-label">Vade (ay) <span class="required">*</span></label>
                <input type="number" name="vade_ay" min="1" max="60" required class="form-input" placeholder="12" value="{{ old('vade_ay', '12') }}" oninput="hesaplaTaksit()" id="krVade">
                <div class="form-help">Kaç ay (faizsiz)</div>
            </div>
            <div class="form-group">
                <label class="form-label">Başlangıç</label>
                <input type="date" name="baslangic_tarihi" value="{{ old('baslangic_tarihi', date('Y-m-d')) }}" class="form-input">
                <div class="form-help">İlk taksit: bu tarih + 1 ay</div>
            </div>
            <div class="form-group full">
                <label class="form-label">Açıklama / Ne için</label>
                <input type="text" name="aciklama" class="form-input" placeholder="Örn: Kurumsal web sitesi + SEO paketi" value="{{ old('aciklama') }}">
            </div>
        </div>

        <div id="krOnizleme" class="alert alert-info" style="display:none;margin-top:12px">
            <i data-lucide="info"></i>
            <div><strong>Önizleme:</strong> <span id="krOnizlemeText"></span></div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn btn-primary btn-sm" {{ empty($customer->email) ? 'disabled' : '' }}>
                <i data-lucide="save"></i>
                <span>DN Bank Kredisi Aç (Coin Yükle)</span>
            </button>
        </div>
    </form>
</div>

@php $bugun = now()->toDateString(); @endphp

@forelse($krediler as $k)
    @php
        $taksitler = \Illuminate\Support\Facades\DB::table('musteri_kredi_taksitleri')
            ->where('kredi_id', $k->id)->orderBy('sira')->get();
        $renkler = ['aktif'=>'var(--brand)','kapandi'=>'var(--success)','gecikmede'=>'var(--danger)','iptal'=>'var(--text-muted)'];
        $durumRenk = $renkler[$k->durum] ?? 'var(--brand)';
    @endphp

    <div class="section" style="padding:0;border-left:4px solid {{ $durumRenk }}">
        <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="font-size:11px;color:var(--text-muted)">{{ $k->kredi_no }} · Açıldı: {{ \Carbon\Carbon::parse($k->created_at)->format('d.m.Y') }}</div>
                <h3 style="font-size:20px;font-weight:700;margin-top:4px">
                    ₺{{ number_format((float) $k->ana_para, 2, ',', '.') }}
                    @if($k->durum == 'aktif')<span class="badge badge-brand" style="margin-left:6px">⏳ Aktif</span>
                    @elseif($k->durum == 'kapandi')<span class="badge badge-success" style="margin-left:6px">✓ Kapandı</span>
                    @elseif($k->durum == 'gecikmede')<span class="badge badge-danger" style="margin-left:6px">⚠️ Gecikme</span>
                    @else<span class="badge badge-neutral" style="margin-left:6px">⏸ İptal</span>@endif
                    @if(isset($k->onay_durumu) && $k->onay_durumu === 'bekliyor')
                        <span class="badge badge-warning" style="margin-left:6px">⏳ Onay Bekliyor ({{ ($k->talep_eden ?? 'admin') === 'musteri' ? 'müşteri talebi' : 'müşteri onayında' }})</span>
                    @elseif(isset($k->onay_durumu) && $k->onay_durumu === 'reddedildi')
                        <span class="badge badge-danger" style="margin-left:6px">✗ Reddedildi</span>
                    @endif
                </h3>
                <div style="font-size:12px;color:var(--text-secondary);margin-top:4px">
                    {{ $k->vade_ay }} ay {{ ($k->faiz_orani ?? 0) == 0 ? '(faizsiz)' : '· %' . number_format((float) $k->faiz_orani, 2) . ' faiz' }} ·
                    Aylık: <strong style="color:var(--brand)">₺{{ number_format((float) $k->aylik_taksit, 2, ',', '.') }}</strong>
                </div>
                @if(!empty($k->aciklama))<div style="font-size:12px;color:var(--text-muted);margin-top:2px">{{ $k->aciklama }}</div>@endif
            </div>
            <div style="text-align:right">
                <div style="font-size:11px;color:var(--text-muted)">Kalan Borç</div>
                <div style="font-size:24px;font-weight:700;color:{{ $k->kalan_borc > 0 ? 'var(--danger)' : 'var(--success)' }}">
                    ₺{{ number_format((float) $k->kalan_borc, 2, ',', '.') }}
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    Ödenen: ₺{{ number_format((float) $k->odenen_tutar, 2, ',', '.') }} / ₺{{ number_format((float) $k->toplam_geri_odeme, 2, ',', '.') }}
                </div>
                @if(isset($k->onay_durumu) && $k->onay_durumu === 'bekliyor' && Route::has('admin.crm.musteriler.kredi.onayla'))
                <form action="{{ route('admin.crm.musteriler.kredi.onayla', ['id' => $customer->id, 'krediId' => $k->id]) }}" method="POST" style="display:inline-block;margin-top:6px" onsubmit="return confirm('Krediyi onayla ve coin yukle?');">
                    @csrf
                    <button class="btn btn-success btn-sm" title="Onayla ve coin yükle"><i data-lucide="check"></i> <span>Onayla</span></button>
                </form>
                @endif
                <form action="{{ route('admin.crm.musteriler.kredi.sil', ['id' => $customer->id, 'krediId' => $k->id]) }}" method="POST" style="display:inline-block;margin-top:6px" onsubmit="return confirm('Kredi ve tüm taksitler silinsin mi?');">
                    @csrf @method('DELETE')
                    <button class="table-action" style="color:var(--danger)" title="Sil">
                        <i data-lucide="trash-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vade</th>
                        <th>Tutar</th>
                        <th>Ödenen</th>
                        <th>Durum</th>
                        <th class="text-right">Ödeme</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taksitler as $t)
                        @php $gecikme = $t->durum != 'odendi' && $t->vade_tarihi < $bugun; @endphp
                        <tr class="taksit-row {{ $gecikme ? 'gecikme' : ($t->durum == 'kismi' ? 'kismi' : ($t->durum == 'odendi' ? 'odendi' : '')) }}">
                            <td style="font-family:monospace;font-size:12px">{{ $t->sira }}/{{ $k->vade_ay }}</td>
                            <td style="font-size:12px">
                                {{ \Carbon\Carbon::parse($t->vade_tarihi)->format('d.m.Y') }}
                                @if($gecikme)<span class="badge badge-danger" style="font-size:10px">⚠️ Geç</span>@endif
                            </td>
                            <td><strong>₺{{ number_format((float) $t->taksit_tutari, 2, ',', '.') }}</strong></td>
                            <td style="color:var(--success)">₺{{ number_format((float) $t->odenen_tutar, 2, ',', '.') }}</td>
                            <td>
                                @if($t->durum == 'odendi')<span class="badge badge-success">✓ Ödendi</span>
                                @elseif($t->durum == 'kismi')<span class="badge badge-warning">◐ Kısmi</span>
                                @elseif($gecikme)<span class="badge badge-danger">⚠️ Gecikti</span>
                                @else<span class="badge badge-neutral">⏳ Bekliyor</span>@endif
                            </td>
                            <td class="text-right">
                                @if($t->durum != 'odendi')
                                    <form action="{{ route('admin.crm.musteriler.kredi.taksit.ode', ['id' => $customer->id, 'krediId' => $k->id, 'taksitId' => $t->id]) }}" method="POST" style="display:inline-flex;align-items:center;gap:4px">
                                        @csrf
                                        <input type="number" name="tutar" step="0.01" min="0.01" value="{{ number_format((float) $t->taksit_tutari - (float) $t->odenen_tutar, 2, '.', '') }}" class="form-input" style="width:100px;padding:4px 8px;font-size:12px" required>
                                        <button class="table-action" title="Ödeme kaydet">
                                            <i data-lucide="dollar-sign"></i>
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:11px;color:var(--text-muted)">{{ \Carbon\Carbon::parse($t->odeme_tarihi)->format('d.m.Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
<div class="section">
    <div class="empty-state">
        <i data-lucide="credit-card" class="empty-state-icon"></i>
        <h4>Henüz kredi açılmamış</h4>
        <p>Yukarıdan ilk taksitli ödeme planını oluştur.</p>
    </div>
</div>
@endforelse

<script>
function hesaplaTaksit() {
    const P = parseFloat(document.getElementById('krAnaPara').value || 0);
    const n = parseInt(document.getElementById('krVade').value || 0);
    const box = document.getElementById('krOnizleme');
    const txt = document.getElementById('krOnizlemeText');
    if (P <= 0 || n <= 0) { box.style.display = 'none'; return; }
    const m = P / n;
    const fmt = v => v.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    txt.innerHTML = `Aylık taksit: <strong>₺${fmt(m)}</strong> × ${n} ay = Toplam <strong>₺${fmt(P)}</strong> <span style="color:var(--success)">(faizsiz)</span>`;
    box.style.display = '';
}
document.addEventListener('DOMContentLoaded', hesaplaTaksit);
</script>