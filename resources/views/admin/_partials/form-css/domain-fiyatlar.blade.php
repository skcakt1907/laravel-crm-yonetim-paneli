{{-- ORTAK FORM CSS — domain/fiyatlar/{ekle,duzenle} (11.08.2026'da ikisinden cikarildi;
     iki dosyadaki bloklar byte-byte aynıydı, tek kaynağa indirildi) --}}
<style>
    .toggle-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; gap: 16px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
    }
    .toggle-card .desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .toggle-card .lbl-strong { font-weight: 600; font-size: 14px; }

    .ios-toggle { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
    .ios-toggle input { opacity: 0; width: 0; height: 0; }
    .ios-toggle .knob {
        position: absolute; cursor: pointer; inset: 0;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 26px;
        transition: 0.25s;
    }
    .ios-toggle .knob:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background: #fff;
        border-radius: 50%; transition: 0.25s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .ios-toggle input:checked + .knob {
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        border-color: var(--brand);
    }
    .ios-toggle input:checked + .knob:before { transform: translateX(22px); }

    .fiyat-input { position: relative; }
    .fiyat-input::before {
        content: "₺";
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        color: var(--brand-dark);
        font-weight: 700;
        z-index: 1;
        pointer-events: none;
    }
    .fiyat-input input { padding-left: 32px !important; }
</style>
