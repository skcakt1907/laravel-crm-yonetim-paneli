@php $d = $alacak ?? null; @endphp

<div class="form-grid">
    <div class="form-group full">
        <label class="form-label">Alacak Başlığı <span class="required">*</span></label>
        <input type="text" name="baslik" class="form-input" required maxlength="191"
               value="{{ old('baslik', $d->baslik ?? '') }}" placeholder="Örn: Aylık sosyal medya yönetimi">
        @error('baslik')<div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label">Müşteri</label>
        <select name="musteri_id" class="form-select">
            <option value="">— Seçiniz (opsiyonel) —</option>
            @foreach($musteriler as $m)
                <option value="{{ $m->id }}" {{ (string) old('musteri_id', $d->musteri_id ?? '') === (string) $m->id ? 'selected' : '' }}>
                    {{ \Illuminate\Support\Str::limit($m->adi, 55) }}
                </option>
            @endforeach
        </select>
        <div class="form-help">Listede yoksa yandaki alana elle yazabilirsiniz.</div>
    </div>

    <div class="form-group">
        <label class="form-label">Müşteri Adı (elle)</label>
        <input type="text" name="musteri_adi" class="form-input" maxlength="191"
               value="{{ old('musteri_adi', $d->musteri_adi ?? '') }}" placeholder="Kayıtlı değilse">
    </div>

    <div class="form-group">
        <label class="form-label">Tutar <span class="required">*</span></label>
        <input type="number" step="0.01" min="0" name="tutar" class="form-input" required
               value="{{ old('tutar', $d->tutar ?? '') }}" placeholder="0,00">
        @error('tutar')<div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label">Para Birimi</label>
        <select name="para_birimi" class="form-select">
            @foreach(['TL', 'USD', 'EUR'] as $pb)
                <option value="{{ $pb }}" {{ old('para_birimi', $d->para_birimi ?? 'TL') === $pb ? 'selected' : '' }}>{{ $pb }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Periyot <span class="required">*</span></label>
        <select name="periyot_ay" class="form-select" required>
            @foreach([1 => 'Her ay', 3 => '3 ayda 1', 6 => '6 ayda 1', 12 => 'Yılda 1'] as $ay => $et)
                <option value="{{ $ay }}" {{ (int) old('periyot_ay', $d->periyot_ay ?? 1) === $ay ? 'selected' : '' }}>{{ $et }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Son Tahsil Tarihi <span class="required">*</span></label>
        <input type="date" name="son_tahsil_tarihi" class="form-input" required
               value="{{ old('son_tahsil_tarihi', isset($d->son_tahsil_tarihi) && $d->son_tahsil_tarihi ? \Carbon\Carbon::parse($d->son_tahsil_tarihi)->format('Y-m-d') : '') }}">
        @error('son_tahsil_tarihi')<div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label">Kategori</label>
        <select name="kategori_id" class="form-select">
            <option value="">— Seçiniz —</option>
            @foreach($kategoriler as $k)
                <option value="{{ $k->id }}" {{ (string) old('kategori_id', $d->kategori_id ?? '') === (string) $k->id ? 'selected' : '' }}>{{ $k->ad }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Durum <span class="required">*</span></label>
        <select name="durum" class="form-select" required>
            <option value="bekliyor" {{ old('durum', $d->durum ?? 'bekliyor') === 'bekliyor' ? 'selected' : '' }}>Bekliyor</option>
            <option value="tahsil_edildi" {{ old('durum', $d->durum ?? '') === 'tahsil_edildi' ? 'selected' : '' }}>Tahsil edildi</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Tahsil Yöntemi</label>
        <input type="text" name="tahsil_yontemi" class="form-input" maxlength="50"
               value="{{ old('tahsil_yontemi', $d->tahsil_yontemi ?? '') }}" placeholder="Havale, nakit, kredi kartı...">
    </div>

    <div class="form-group full">
        <label class="form-label">Açıklama</label>
        <textarea name="aciklama" class="form-textarea" rows="3">{{ old('aciklama', $d->aciklama ?? '') }}</textarea>
    </div>

    <div class="form-group full">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text)">
            <input type="checkbox" name="aktif" value="1" {{ old('aktif', $d->aktif ?? 1) ? 'checked' : '' }}>
            <span>Aktif — pasif kayıtlar için hatırlatma gönderilmez</span>
        </label>
    </div>
</div>
