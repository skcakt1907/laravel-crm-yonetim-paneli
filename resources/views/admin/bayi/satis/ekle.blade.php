@extends('layouts.bayi')

@section('title', __('messages.add_new_sale'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); $sembol = \App\Helpers\CurrencyHelper::getSymbol($currency); @endphp

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-white/60 mb-2">
        <a href="{{ route('admin.bayi.satislar') }}" class="hover:text-yellow-400">Satışlarım</a> <span>›</span> <span>Yeni Satış</span>
    </div>
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🛒 <span class="gradient-text">{{ __('messages.add_new_sale') }}</span></h1>
</div>

<form action="{{ route('admin.bayi.satis.ekle.post') }}" method="POST">
    @csrf
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6">
                <div class="section-title">📋 {{ __('messages.sale_information') }}</div>
                <div class="space-y-5">
                    <div>
                        <label class="field-label">👤 {{ __('messages.customer_name') }} <span class="text-rose-300">*</span></label>
                        <input type="text" name="musteri_adi" required>
                    </div>
                    <div>
                        <label class="field-label">📧 {{ __('messages.customer_email') }} <span class="text-rose-300">*</span></label>
                        <input type="email" name="musteri_email" required>
                    </div>
                    <div>
                        <label class="field-label">📞 {{ __('messages.customer_phone') }}</label>
                        <input type="text" name="musteri_telefon">
                    </div>
                    <div>
                        <label class="field-label">📦 {{ __('messages.select_package') }} <span class="text-rose-300">*</span></label>
                        <div class="paket-search-wrap" style="position:relative">
                            <input type="text" id="paketSearch" autocomplete="off"
                                   placeholder="Paket ara... (isim veya fiyat yazın)"
                                   style="padding-right:36px">
                            <span class="paket-search-icon">🔍</span>
                            <input type="hidden" name="paket_id" id="paketSelect" required>
                            <div id="paketDropdown" class="paket-dropdown"></div>
                        </div>
                        <small class="paket-secili-info" id="paketSecili" style="display:none">
                            ✅ Seçili: <strong id="paketSeciliAd"></strong>
                            <a href="#" id="paketTemizle">temizle</a>
                        </small>
                        @php
                            $paketlerJson = $paketler->map(function ($p) use ($currency) {
                                $fiyat = (float)($p->fiyat ?? $p->tutar ?? 0);
                                return [
                                    'id' => $p->id,
                                    'adi' => $p->adi ?? '-',
                                    'fiyat' => $fiyat,
                                    'fiyat_label' => \App\Helpers\CurrencyHelper::format($fiyat, $currency),
                                ];
                            });
                        @endphp
                        <script type="application/json" id="paketlerData">{!! $paketlerJson->toJson() !!}</script>
                    </div>
                    <div>
                        <label class="field-label">💰 {{ __('messages.sale_amount') }} <span class="text-rose-300">*</span></label>
                        <input type="number" name="satis_tutari" id="satisTutari" step="0.01" min="0" required>
                        <small class="text-xs text-white/50 block mt-1">{{ __('messages.sale_amount_help') }}</small>
                    </div>
                    <div>
                        <label class="field-label">% {{ __('messages.commission_rate_percent') }}</label>
                        <input type="text" value="10%" readonly>
                        <small class="text-xs text-white/50 block mt-1">{{ __('messages.your_earnings') }}: <strong id="komisyonTutar" class="text-yellow-400">{{ $sembol }}0.00</strong></small>
                    </div>
                    <div>
                        <label class="field-label">📝 {{ __('messages.description') }}</label>
                        <textarea name="aciklama" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="glass rounded-2xl p-6" style="border-color:rgba(59,130,246,.4)">
                <div class="section-title">💡 {{ __('messages.info') }}</div>
                <p class="text-sm text-white/80 mb-3">{{ __('messages.manual_sale_info') }}</p>
                <ul class="text-sm space-y-2 text-white/70">
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.fill_customer_info') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.select_correct_package') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.enter_sale_amount') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.commission_auto_calculated') }}</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="sticky bottom-4 glass rounded-2xl p-4 mt-6 flex items-center justify-between">
        <a href="{{ route('admin.bayi.satislar') }}" class="btn-o">❌ {{ __('messages.cancel') }}</a>
        <button type="submit" class="btn-y">💾 {{ __('messages.save_sale') }}</button>
    </div>
</form>

@push('styles')
<style>
.paket-search-wrap .paket-search-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#aaa;font-size:16px;pointer-events:none}
.paket-dropdown{display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #d8d8d8;border-radius:10px;max-height:280px;overflow-y:auto;z-index:1000;box-shadow:0 8px 24px rgba(0,0,0,.08)}
.paket-dropdown.open{display:block}
.paket-dropdown-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid #f3f3f3;display:flex;justify-content:space-between;align-items:center;gap:10px;color:#222}
.paket-dropdown-item:last-child{border-bottom:none}
.paket-dropdown-item:hover,.paket-dropdown-item.active{background:#fef3c7;color:#222}
.paket-dropdown-item .paket-ad{font-weight:600;font-size:14px}
.paket-dropdown-item .paket-fiyat{font-size:13px;color:#d97706;font-weight:600;white-space:nowrap}
.paket-dropdown-empty{padding:14px;color:#999;text-align:center;font-size:13px}
.paket-secili-info{display:block;margin-top:6px;font-size:13px;color:#059669}
.paket-secili-info a{color:#dc3545 !important;margin-left:8px;text-decoration:underline;font-size:12px}
mark{background:#fef08a;color:#222;padding:0 2px;border-radius:3px}
</style>
@endpush

@push('scripts')
<script src="{{ asset('yonetim/js/bayi-satis-form.js') }}"></script>
<script>
(function(){
    const data = JSON.parse(document.getElementById('paketlerData').textContent || '[]');
    const search = document.getElementById('paketSearch');
    const hidden = document.getElementById('paketSelect');
    const dropdown = document.getElementById('paketDropdown');
    const seciliInfo = document.getElementById('paketSecili');
    const seciliAd = document.getElementById('paketSeciliAd');
    const temizle = document.getElementById('paketTemizle');
    const satisTutari = document.getElementById('satisTutari');

    let activeIdx = -1;
    let filtered = [];

    function highlight(text, q){
        if(!q) return text;
        const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(re, '<mark>$1</mark>');
    }

    function render(q){
        const ql = (q||'').toLowerCase().trim();
        filtered = data.filter(p =>
            p.adi.toLowerCase().includes(ql) ||
            String(p.fiyat).includes(ql) ||
            p.fiyat_label.toLowerCase().includes(ql)
        );
        if(filtered.length === 0){
            dropdown.innerHTML = '<div class="paket-dropdown-empty">Sonuç bulunamadı</div>';
        } else {
            dropdown.innerHTML = filtered.map((p,i) =>
                `<div class="paket-dropdown-item ${i===activeIdx?'active':''}" data-id="${p.id}" data-fiyat="${p.fiyat}" data-ad="${p.adi.replace(/"/g,'&quot;')}">
                    <span class="paket-ad">${highlight(p.adi, q)}</span>
                    <span class="paket-fiyat">${p.fiyat_label}</span>
                </div>`
            ).join('');
        }
        dropdown.classList.add('open');
        dropdown.style.display = 'block';
    }

    function select(item){
        hidden.value = item.dataset.id;
        const fiyat = parseFloat(item.dataset.fiyat) || 0;
        search.value = item.dataset.ad;
        seciliAd.textContent = item.dataset.ad + ' — ' + filtered.find(f=>f.id==item.dataset.id).fiyat_label;
        seciliInfo.style.display = 'block';
        if(satisTutari && !satisTutari.value){ satisTutari.value = fiyat.toFixed(2); satisTutari.dispatchEvent(new Event('input',{bubbles:true})); }
        close();
    }

    function close(){
        dropdown.classList.remove('open');
        dropdown.style.display = 'none';
        activeIdx = -1;
    }

    search.addEventListener('focus', () => render(search.value));
    search.addEventListener('input', () => { activeIdx = -1; render(search.value); hidden.value=''; seciliInfo.style.display='none'; });
    search.addEventListener('keydown', (e) => {
        if(!dropdown.classList.contains('open')) return;
        if(e.key === 'ArrowDown'){ e.preventDefault(); activeIdx = Math.min(activeIdx+1, filtered.length-1); render(search.value); }
        else if(e.key === 'ArrowUp'){ e.preventDefault(); activeIdx = Math.max(activeIdx-1, 0); render(search.value); }
        else if(e.key === 'Enter'){
            e.preventDefault();
            if(activeIdx >= 0 && filtered[activeIdx]){
                const items = dropdown.querySelectorAll('.paket-dropdown-item');
                if(items[activeIdx]) select(items[activeIdx]);
            }
        } else if(e.key === 'Escape'){ close(); }
    });

    dropdown.addEventListener('click', (e) => {
        const item = e.target.closest('.paket-dropdown-item');
        if(item) select(item);
    });

    document.addEventListener('click', (e) => {
        if(!e.target.closest('.paket-search-wrap')) close();
    });

    temizle.addEventListener('click', (e) => {
        e.preventDefault();
        hidden.value = '';
        search.value = '';
        seciliInfo.style.display = 'none';
        search.focus();
    });
})();
</script>
@endpush
@endsection
