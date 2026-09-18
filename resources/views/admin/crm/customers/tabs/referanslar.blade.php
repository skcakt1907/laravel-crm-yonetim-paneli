{{-- Tab: Referanslar --}}
@php
    $refler = collect();
    try {
        $refler = \Illuminate\Support\Facades\DB::table('referanslar')->orderByDesc('id')->limit(50)->get();
    } catch (\Throwable $e) {}

    // Bonus dropdown'i icin musteri listesi (mevcut musteri haric)
    $bonusMusteriler = collect();
    try {
        $bonusMusteriler = \Illuminate\Support\Facades\DB::table('crm_customers')
            ->where('id', '!=', $customer->id)
            ->orderBy('adi')
            ->get(['id', 'adi']);
    } catch (\Throwable $e) {}
@endphp

<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button type="button" class="btn btn-primary btn-sm" onclick="crmToggleForm('referansEkleForm')">
        <i data-lucide="plus"></i>
        <span>Yeni Referans Ekle</span>
    </button>
</div>

<div class="section" id="referansEkleForm" style="{{ $errors->any() ? '' : 'display:none' }}">
    <div class="section-title">
        <i data-lucide="award"></i>
        <span>Yeni Referans Ekle</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.referans.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Firma / Ad <span class="required">*</span></label>
                <input type="text" name="adi" required class="form-input" placeholder="Örn: ABC Şirketi">
            </div>
            <div class="form-group">
                <label class="form-label">Durum</label>
                <select name="durum" class="form-select">
                    <option value="1">✓ Yayında</option>
                    <option value="0">⏸ Pasif</option>
                </select>
            </div>
            <div class="form-group full">
                <label class="form-label">Kısa Açıklama</label>
                <input type="text" name="kisa" class="form-input" placeholder="Bir cümlelik özet">
            </div>
            <div class="form-group full">
                <label class="form-label">Detaylı Açıklama</label>
                <textarea name="aciklama" rows="4" class="form-textarea" placeholder="Referans detayları, müşteri yorumu..."></textarea>
            </div>
            <div class="form-group full">
                <label class="form-label">Logo / Görsel</label>
                <input type="file" name="resim_dosya" accept="image/*" class="form-input">
                <div class="form-help">PNG, JPG veya WEBP — <code>uploads/referans/</code> klasörüne yüklenir.</div>
            </div>
        </div>

        {{-- ── REFERANS BONUSU (%10 bakiye) ── --}}
        <div style="margin-top:14px;padding:14px 16px;background:rgba(184,182,46,.08);border:1px solid var(--brand-medium, #d6d488);border-radius:10px">
            <label class="form-label" style="display:flex;align-items:center;gap:6px;font-weight:600">
                🎁 Referans Bonusu (opsiyonel)
            </label>
            <div class="form-help" style="margin-bottom:10px">
                Bu referansı getiren <strong>{{ $customer->adi ?? 'bu müşteriye' }}</strong>, aşağıda seçilen yeni müşterinin
                <strong>ilk kesilmiş faturasının %10'u</strong> kadar bakiye ödülü alır. Tek seferlik uygulanır.
            </div>
            <select name="bonus_musteri_id" class="form-select">
                <option value="">— Bonus uygulama (boş bırak) —</option>
                @foreach($bonusMusteriler as $bm)
                    <option value="{{ $bm->id }}">{{ \Illuminate\Support\Str::limit($bm->adi, 50) }}</option>
                @endforeach
            </select>
            <div class="form-help" style="margin-top:6px">Referans getirilen (yeni) müşteriyi seç — ödül {{ $customer->adi ?? 'bu müşteriye' }} kişisine yüklenir.</div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button class="btn btn-primary btn-sm">
                <i data-lucide="save"></i>
                <span>Referans Ekle</span>
            </button>
        </div>
    </form>
</div>

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <h4 style="font-size:14px;font-weight:600">
            ⭐ Referanslar
            <span class="badge badge-brand" style="margin-left:6px">{{ $refler->count() }}</span>
        </h4>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Firma</th>
                    <th>Özet</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                @forelse($refler as $r)
                <tr>
                    <td style="font-family:monospace;font-size:12px">#{{ $r->id }}</td>
                    <td><strong>{{ $r->adi ?? '—' }}</strong></td>
                    <td style="color:var(--text-secondary)">{{ \Illuminate\Support\Str::limit($r->kisa ?? '', 60) }}</td>
                    <td>
                        @if(($r->durum ?? 0) == 1)
                            <span class="badge badge-success">✓ Yayında</span>
                        @else
                            <span class="badge badge-warning">⏸ Pasif</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $r->tarih ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <div class="empty-state">
                        <i data-lucide="award" class="empty-state-icon"></i>
                        <h4>Referans yok</h4>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>