{{-- ORTAK FORM CSS — slider/{ekle,duzenle} (11.08.2026'da ikisinden cikarildi;
     iki dosyadaki bloklar byte-byte aynıydı, tek kaynağa indirildi) --}}
<style>
    .toggle-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; gap: 16px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
        margin-bottom: 10px;
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

    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        z-index: 10;
    }

    .image-upload {
        position: relative;
        border: 2px dashed var(--border);
        border-radius: var(--radius-md);
        padding: 30px 20px;
        text-align: center;
        background: var(--bg-subtle);
        transition: all 0.2s;
        cursor: pointer;
    }
    .image-upload:hover { border-color: var(--brand); background: var(--brand-soft); }
    .image-upload input[type="file"] {
        position: absolute; inset: 0;
        opacity: 0; cursor: pointer;
    }
    .image-upload .preview {
        max-width: 100%; max-height: 240px;
        border-radius: var(--radius-md);
        margin: 10px auto 0;
        display: block; object-fit: cover;
    }
</style>
