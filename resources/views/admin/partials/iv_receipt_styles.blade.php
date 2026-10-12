{{-- Shared receipt preview styles (student / fee / inquiry) --}}
<style>
    .iv-receipt {
        --ivr-teal: #0f5c4d;
        --ivr-teal-dark: #0a453a;
        --ivr-gold: #d9a441;
        --ivr-cream: #f7faf8;
        --ivr-line: rgba(15, 92, 77, 0.12);
        position: relative;
        border: 1px solid var(--ivr-line);
        border-radius: 16px;
        background:
            radial-gradient(circle at top right, rgba(217, 164, 65, 0.08), transparent 28%),
            linear-gradient(180deg, #ffffff 0%, #fbfcfb 100%);
        padding: 0;
        overflow: hidden;
        box-shadow: 0 14px 36px rgba(15, 40, 35, 0.08);
    }
    .iv-receipt__banner {
        background: linear-gradient(135deg, var(--ivr-teal-dark) 0%, var(--ivr-teal) 55%, #1a7a66 100%);
        color: #fff;
        padding: 1.15rem 1.25rem 0.95rem;
        position: relative;
    }
    .iv-receipt__banner::after {
        content: "";
        position: absolute;
        inset: auto 0 0 0;
        height: 18px;
        background: linear-gradient(180deg, transparent, rgba(0,0,0,0.08));
        pointer-events: none;
    }
    .iv-receipt__banner-inner {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        position: relative;
        z-index: 1;
    }
    .iv-receipt__brand {
        display: flex;
        gap: 0.9rem;
        align-items: flex-start;
        min-width: 0;
        flex: 1;
    }
    .iv-receipt__logo-wrap {
        flex-shrink: 0;
        width: 68px;
        height: 68px;
        border-radius: 14px;
        background: #fff;
        border: 2px solid rgba(217, 164, 65, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }
    .iv-receipt__logo {
        max-height: 56px;
        max-width: 56px;
        object-fit: contain;
    }
    .iv-receipt__logo-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--ivr-teal);
        background: linear-gradient(135deg, #fff, #eef6f3);
    }
    .iv-receipt__name {
        font-size: 1.28rem;
        font-weight: 800;
        letter-spacing: 0.01em;
        line-height: 1.2;
        color: #fff;
    }
    .iv-receipt__tag {
        display: inline-block;
        margin-top: 0.25rem;
        font-size: 0.74rem;
        font-weight: 700;
        color: #1f2a28;
        background: var(--ivr-gold);
        border-radius: 999px;
        padding: 0.15rem 0.55rem;
    }
    .iv-receipt__contact {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.88);
        margin-top: 0.35rem;
        line-height: 1.45;
    }
    .iv-receipt__contact--muted {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0.85rem;
        opacity: 0.92;
    }
    .iv-receipt__contact--muted span {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .iv-receipt__contact--muted i { font-size: 0.9rem; color: var(--ivr-gold); }
    .iv-receipt__meta-box {
        min-width: 210px;
        border-radius: 12px;
        padding: 0.75rem 0.85rem;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.22);
        backdrop-filter: blur(6px);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);
    }
    .iv-receipt__doc-title {
        text-align: center;
        font-weight: 800;
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #1f2a28;
        background: var(--ivr-gold);
        border-radius: 8px;
        padding: 0.4rem 0.55rem;
        margin-bottom: 0.6rem;
    }
    .iv-receipt__meta-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.8rem;
        margin-bottom: 0.28rem;
        color: rgba(255,255,255,0.85);
    }
    .iv-receipt__meta-row strong {
        color: #fff;
        font-weight: 750;
        text-align: right;
    }
    .iv-receipt__gold-bar {
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--ivr-gold), transparent);
        margin-top: 0.85rem;
    }
    .iv-receipt__body {
        padding: 1rem 1.2rem 1.15rem;
    }
    .iv-receipt__section-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0.2rem 0 0.55rem;
        color: var(--ivr-teal-dark);
        font-size: 0.82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .iv-receipt__section-title::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--ivr-gold);
        box-shadow: 0 0 0 3px rgba(217, 164, 65, 0.2);
        flex-shrink: 0;
    }
    .iv-receipt__section-title + .iv-receipt__grid,
    .iv-receipt__section-title + .iv-receipt__table,
    .iv-receipt__section-title + .iv-receipt__terms,
    .iv-receipt__section-title + .iv-receipt__amount-card {
        margin-top: 0;
    }
    .iv-receipt__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
        margin-bottom: 0.95rem;
    }
    .iv-receipt__grid > div {
        padding: 0.7rem 0.8rem;
        border-radius: 10px;
        background: var(--ivr-cream);
        border: 1px solid var(--ivr-line);
        transition: none;
    }
    .iv-receipt__grid span {
        display: block;
        font-size: 0.66rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7a76;
        margin-bottom: 0.18rem;
        font-weight: 700;
    }
    .iv-receipt__grid strong {
        color: var(--ivr-teal-dark);
        font-size: 0.92rem;
        font-weight: 750;
        word-break: break-word;
    }
    .iv-receipt__table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0.85rem;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--ivr-line);
    }
    .iv-receipt__table th {
        background: var(--ivr-teal);
        color: #fff;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.65rem 0.75rem;
        border: none;
        font-weight: 750;
    }
    .iv-receipt__table td {
        padding: 0.7rem 0.75rem;
        border-top: 1px solid var(--ivr-line);
        font-size: 0.88rem;
        background: #fff;
        color: #243230;
    }
    .iv-receipt__table tbody tr:nth-child(even) td { background: var(--ivr-cream); }
    .iv-receipt__table .text-end { text-align: right; }
    .iv-receipt__amount-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin: 0.35rem 0 0.95rem;
        padding: 1rem 1.15rem;
        border-radius: 14px;
        background: linear-gradient(135deg, #0a453a 0%, #0f5c4d 55%, #1a7a66 100%);
        border: 2px solid var(--ivr-gold);
        box-shadow: 0 10px 24px rgba(15, 92, 77, 0.18);
        color: #fff;
    }
    .iv-receipt__amount-card .lbl {
        font-size: 0.72rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(255,255,255,0.78);
    }
    .iv-receipt__amount-card .small,
    .iv-receipt__amount-card .text-muted {
        color: rgba(255,255,255,0.72) !important;
        font-size: 0.8rem;
    }
    .iv-receipt__amount-card .val {
        font-size: 1.55rem;
        font-weight: 800;
        color: var(--ivr-gold);
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .iv-receipt__terms {
        margin: 0 0 0.95rem;
        padding: 0.75rem 0.85rem 0.75rem 1.5rem;
        border-radius: 12px;
        background: #fffdf8;
        border: 1px solid rgba(217, 164, 65, 0.28);
        color: #4a5754;
        font-size: 0.82rem;
        line-height: 1.5;
    }
    .iv-receipt__terms li { margin-bottom: 0.28rem; }
    .iv-receipt__terms li::marker { color: var(--ivr-gold); font-weight: 800; }
    .iv-receipt__note {
        margin: 0 0 0.85rem;
        padding: 0.7rem 0.85rem;
        border-radius: 10px;
        background: rgba(15, 92, 77, 0.05);
        border-left: 3px solid var(--ivr-teal);
        font-size: 0.84rem;
        color: #4a5754;
    }
    .iv-receipt__signs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1.15rem;
        text-align: center;
    }
    .iv-receipt__sign {
        padding: 0.85rem 0.75rem 0.7rem;
        border-radius: 12px;
        background: #fff;
        border: 1px dashed rgba(15, 92, 77, 0.22);
    }
    .iv-receipt__sign img {
        max-height: 64px;
        max-width: 140px;
        object-fit: contain;
        margin-bottom: 0.4rem;
    }
    .iv-receipt__sign-placeholder { height: 48px; margin-bottom: 0.4rem; }
    .iv-receipt__sign span {
        display: block;
        font-size: 0.74rem;
        font-weight: 700;
        color: var(--ivr-teal-dark);
        letter-spacing: 0.03em;
        text-transform: uppercase;
        border-top: 1px solid var(--ivr-line);
        padding-top: 0.45rem;
        margin-top: 0.15rem;
    }
    .iv-receipt__foot {
        margin-top: 1rem;
        padding: 0.7rem 1rem;
        text-align: center;
        font-size: 0.72rem;
        color: #6b7a76;
        background: linear-gradient(90deg, rgba(15,92,77,0.04), rgba(217,164,65,0.08), rgba(15,92,77,0.04));
        border-top: 1px solid var(--ivr-line);
    }
    @media (max-width: 767.98px) {
        .iv-receipt__grid { grid-template-columns: 1fr; }
        .iv-receipt__signs { grid-template-columns: 1fr; }
        .iv-receipt__meta-box { width: 100%; }
        .iv-receipt__amount-card { flex-direction: column; align-items: flex-start; }
    }
    @media print {
        body * { visibility: hidden !important; }
        #ivReceiptPrintArea, #ivReceiptPrintArea *,
        #stuReceiptPrintArea, #stuReceiptPrintArea * { visibility: visible !important; }
        #ivReceiptPrintArea, #stuReceiptPrintArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            border: none !important;
        }
        .modal-header, .modal-footer, .btn-close { display: none !important; }
    }
</style>
