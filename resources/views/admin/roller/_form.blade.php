{{-- Ortak Rol Form Alanları --}}
{{-- Kullanım: @include('admin.roller._form', ['rol' => $rol ?? null, 'kategoriler' => $kategoriler, 'mevcutYetkiler' => $mevcutYetkiler]) --}}

@php
    $rolVar = isset($rol) && $rol;
    $korumali = $rolVar && $rol->korumali;
    $renkler = [
        'primary'   => ['ad' => 'Lime',     'hex' => '#b8b62e'],
        'success'   => ['ad' => 'Yeşil',    'hex' => '#10b981'],
        'danger'    => ['ad' => 'Kırmızı',  'hex' => '#ef4444'],
        'warning'   => ['ad' => 'Sarı',     'hex' => '#f59e0b'],
        'info'      => ['ad' => 'Mavi',     'hex' => '#3b82f6'],
        'dark'      => ['ad' => 'Koyu',     'hex' => '#1f2937'],
        'secondary' => ['ad' => 'Gri',      'hex' => '#6b7280'],
    ];
    $onerilenIkonlar = ['👑','👤','🤝','👥','💼','🛡️','⚙️','🔧','📊','📝','💰','🎯','🚀','⭐','🔑','📞','🎨','🏆','🧑‍💻','🧰'];
@endphp

<style>
.icon-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 6px;
    margin-top: 8px;
}
.icon-picker button {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--card-bg);
    font-size: 18px;
    cursor: pointer;
    transition: all .12s ease;
}
.icon-picker button:hover {
    border-color: var(--brand);
    background: var(--brand-soft);
    transform: scale(1.1);
}
.renk-picker {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.renk-opt {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all .12s ease;
}
.renk-opt.active {
    border-color: var(--text);
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
.rol-preview {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 15px;
    margin-top: 12px;
}
.permission-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
    overflow: hidden;
}
.permission-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--bg);
    border-bottom: 1px solid var(--border);
}
.permission-card-title {
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.permission-card-body {
    padding: 14px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 8px;
}
.perm-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .12s ease;
}
.perm-item:hover {
    background: var(--brand-soft);
    border-color: var(--brand-soft);
}
.perm-item input[type=checkbox] {
    width: 16px;
    height: 16px;
    margin-top: 2px;
    flex-shrink: 0;
}
.perm-item-text {
    flex: 1;
    min-width: 0;
}
.perm-item-name {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.3;
}
.perm-item-route {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    color: var(--text-muted);
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.perm-toolbar {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.kategori-toggle {
    display: flex;
    gap: 4px;
}
.kategori-toggle button {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all .12s ease;
}
.kategori-toggle button:hover {
    border-color: var(--brand);
    color: var(--brand-dark);
}
</style>

<div class="form-grid">
    {{-- SOL: Temel Bilgiler --}}
    <div>
        <div class="section" style="margin-bottom:16px">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Rol Bilgileri</span>
            </div>

            <div class="form-group">
                <label class="form-label">Rol Adı <span class="required">*</span></label>
                <input type="text" name="ad" value="{{ old('ad', $rolVar ? $rol->ad : '') }}" required
                       class="form-input" {{ $korumali ? 'readonly' : '' }} maxlength="100"
                       placeholder="Örn: Muhasebe Yöneticisi">
                @if($korumali)
                    <small class="form-help" style="color:#ef4444">
                        <i data-lucide="lock" style="width:12px;height:12px;display:inline-block;vertical-align:-2px"></i>
                        Korumalı rolün adı değiştirilemez
                    </small>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <input type="text" name="aciklama"
                       value="{{ old('aciklama', $rolVar ? $rol->aciklama : '') }}"
                       class="form-input" maxlength="255"
                       placeholder="Rolün ne işe yaradığını kısaca yaz">
            </div>

            <div class="form-group">
                <label class="form-label">İkon (Emoji)</label>
                <input type="text" id="ikonInput" name="ikon"
                       value="{{ old('ikon', $rolVar ? ($rol->ikon ?? '🛡️') : '🛡️') }}"
                       maxlength="4" class="form-input"
                       style="font-size:20px;text-align:center;width:80px">
                <div class="icon-picker">
                    @foreach($onerilenIkonlar as $i)
                        <button type="button" onclick="setIkon('{{ $i }}')">{{ $i }}</button>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Badge Rengi</label>
                <select name="renk" id="renkInput" class="form-select"
                        onchange="updatePreview()">
                    @foreach($renkler as $k => $v)
                        <option value="{{ $k }}"
                                {{ old('renk', $rolVar ? ($rol->renk ?? 'primary') : 'primary') === $k ? 'selected' : '' }}>
                            {{ $v['ad'] }}
                        </option>
                    @endforeach
                </select>
                <div class="renk-picker" id="renkPicker">
                    @foreach($renkler as $k => $v)
                        <div class="renk-opt" data-renk="{{ $k }}" onclick="setRenk('{{ $k }}')"
                             style="background:{{ $v['hex'] }}" title="{{ $v['ad'] }}"></div>
                    @endforeach
                </div>
            </div>

            <div style="background:var(--bg);border:1px dashed var(--border);border-radius:var(--radius-md);padding:14px;text-align:center">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">Önizleme</div>
                <div id="rolPreview" class="rol-preview">
                    <span id="rolPreviewIkon">{{ old('ikon', $rolVar ? ($rol->ikon ?? '🛡️') : '🛡️') }}</span>
                    <span id="rolPreviewAd">{{ old('ad', $rolVar ? $rol->ad : 'Rol Adı') }}</span>
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;margin-bottom:0">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="durum" value="1"
                           {{ old('durum', $rolVar ? $rol->durum : 1) ? 'checked' : '' }}
                           style="width:18px;height:18px">
                    <div>
                        <div style="font-weight:600;font-size:14px">Rol Aktif</div>
                        <div style="font-size:12px;color:var(--text-muted)">Pasif yapılan rol yöneticilere atanamaz</div>
                    </div>
                </label>
            </div>

            @if(!$korumali)
            <div class="form-group" style="margin-top:14px;margin-bottom:0;padding:12px;border:1px dashed var(--brand);border-radius:var(--radius-md);background:var(--brand-soft)">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="tam_yetki" value="1"
                           {{ old('tam_yetki', $rolVar ? ($rol->tam_yetki ?? 0) : 0) ? 'checked' : '' }}
                           style="width:18px;height:18px">
                    <div>
                        <div style="font-weight:700;font-size:14px;color:var(--brand-dark)">🛡️ Tüm Yetkiler (Neredeyse-Patron)</div>
                        <div style="font-size:12px;color:var(--text-muted)">Açık olursa bu rol <strong>Roller ve Yöneticiler hariç</strong> her sayfaya girer. Aşağıdaki yetki seçimi dikkate alınmaz.</div>
                    </div>
                </label>
            </div>
            @endif
        </div>
    </div>

    {{-- SAĞ: Yetki Matrisi --}}
    <div>
        @if($korumali)
            <div class="section" style="background:rgba(239,68,68,.05);border-color:rgba(239,68,68,.2)">
                <div style="display:flex;gap:14px;align-items:flex-start">
                    <div style="font-size:32px">🛡️</div>
                    <div>
                        <strong style="color:#ef4444;font-size:15px">Korumalı Rol — Patron</strong>
                        <p style="font-size:13px;color:var(--text-muted);margin:8px 0 0;line-height:1.6">
                            Bu rol <strong>tüm sayfalara</strong> otomatik olarak erişebilir.
                            Yetki seçimi yapılamaz ve rol silinemez.
                            Sadece açıklama, ikon, renk ve durum güncellenebilir.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="section">
                <div class="section-title">
                    <i data-lucide="shield-check"></i>
                    <span>Sayfa Yetkileri</span>
                </div>
                <p style="font-size:13px;color:var(--text-muted);margin:0 0 14px">
                    Bu rolün <strong>görebileceği</strong> sayfaları seç.
                    Seçilmeyen sayfalara erişim engellenir.
                </p>

                <div class="perm-toolbar">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="tumYetkiler(true)">
                        <i data-lucide="check-square"></i>
                        <span>Tümünü Seç</span>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="tumYetkiler(false)">
                        <i data-lucide="square"></i>
                        <span>Tümünü Kaldır</span>
                    </button>
                    <span style="margin-left:auto;font-size:12px;color:var(--text-muted);align-self:center">
                        <strong id="selectedCount">0</strong> / {{ collect($kategoriler)->flatten(1)->count() }} seçili
                    </span>
                </div>

                @foreach($kategoriler as $kategori => $sayfalar)
                    <div class="permission-card">
                        <div class="permission-card-head">
                            <div class="permission-card-title">
                                <i data-lucide="folder"></i>
                                <span>{{ $kategori }}</span>
                                <span class="badge badge-neutral">{{ count($sayfalar) }}</span>
                            </div>
                            <div class="kategori-toggle">
                                <button type="button" onclick="kategoriSec(this, true)">Hepsi</button>
                                <button type="button" onclick="kategoriSec(this, false)">Hiçbiri</button>
                            </div>
                        </div>
                        <div class="permission-card-body">
                            @foreach($sayfalar as $sayfa)
                                @php
                                    $cbName = 'yetki_' . str_replace('.', '__', $sayfa['route']);
                                    $checked = old($cbName,
                                        isset($mevcutYetkiler[$sayfa['route']]) && $mevcutYetkiler[$sayfa['route']] ? '1' : null
                                    ) == '1';
                                @endphp
                                <label class="perm-item">
                                    <input type="checkbox" class="yetki-cb" name="{{ $cbName }}" value="1"
                                           {{ $checked ? 'checked' : '' }}
                                           onchange="updateSelectedCount()">
                                    <div class="perm-item-text">
                                        <div class="perm-item-name">{{ $sayfa['adi'] }}</div>
                                        <div class="perm-item-route" title="{{ $sayfa['route'] }}">{{ $sayfa['route'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
function setIkon(emoji){
    var inp = document.getElementById('ikonInput');
    inp.value = emoji;
    document.getElementById('rolPreviewIkon').textContent = emoji;
}

function setRenk(renk){
    var sel = document.getElementById('renkInput');
    sel.value = renk;
    updatePreview();
}

function updatePreview(){
    var renk = document.getElementById('renkInput').value;
    var renkHex = {
        'primary':'#b8b62e','success':'#10b981','danger':'#ef4444',
        'warning':'#f59e0b','info':'#3b82f6','dark':'#1f2937','secondary':'#6b7280'
    }[renk] || '#b8b62e';

    document.querySelectorAll('.renk-opt').forEach(function(el){
        el.classList.toggle('active', el.dataset.renk === renk);
    });

    var preview = document.getElementById('rolPreview');
    if(preview){
        preview.style.background = renkHex + '22';
        preview.style.color = renkHex;
        preview.style.border = '1px solid ' + renkHex + '44';
    }

    var adInput = document.querySelector('input[name=ad]');
    if(adInput){
        document.getElementById('rolPreviewAd').textContent = adInput.value || 'Rol Adı';
    }
}

function tumYetkiler(state){
    document.querySelectorAll('.yetki-cb').forEach(function(cb){ cb.checked = state; });
    updateSelectedCount();
}

function kategoriSec(btn, state){
    var card = btn.closest('.permission-card');
    card.querySelectorAll('.yetki-cb').forEach(function(cb){ cb.checked = state; });
    updateSelectedCount();
}

function updateSelectedCount(){
    var el = document.getElementById('selectedCount');
    if(el){
        el.textContent = document.querySelectorAll('.yetki-cb:checked').length;
    }
}

// Init
document.addEventListener('DOMContentLoaded', function(){
    updatePreview();
    updateSelectedCount();
    var adInput = document.querySelector('input[name=ad]');
    if(adInput){
        adInput.addEventListener('input', function(){
            document.getElementById('rolPreviewAd').textContent = this.value || 'Rol Adı';
        });
    }
    var ikonInput = document.getElementById('ikonInput');
    if(ikonInput){
        ikonInput.addEventListener('input', function(){
            document.getElementById('rolPreviewIkon').textContent = this.value || '🛡️';
        });
    }
});
</script>