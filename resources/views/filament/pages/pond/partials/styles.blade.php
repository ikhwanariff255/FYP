<style>
    /* Gaya berskop untuk halaman My Pond — guna pemboleh ubah warna Filament supaya mod gelap automatik */
    .tg { --tg-card: #fff; --tg-ring: rgba(var(--gray-950), .06); --tg-muted: rgb(var(--gray-500)); --tg-text: rgb(var(--gray-950)); --tg-soft: rgb(var(--gray-50)); --tg-line: rgba(var(--gray-950), .08);
          display: flex; flex-direction: column; gap: 1.25rem; color: var(--tg-text); }
    .dark .tg { --tg-card: rgb(var(--gray-900)); --tg-ring: rgba(255,255,255,.1); --tg-muted: rgb(var(--gray-400)); --tg-text: #fff; --tg-soft: rgba(255,255,255,.04); --tg-line: rgba(255,255,255,.08); }
    .tg [x-cloak] { display: none !important; }
    .tg-card { background: var(--tg-card); border-radius: .75rem; box-shadow: 0 0 0 1px var(--tg-ring), 0 1px 2px rgba(0,0,0,.04); padding: 1.25rem; min-width: 0; }
    .tg-grid { display: grid; gap: 1.25rem; grid-template-columns: 1fr; }
    @media (min-width: 640px)  { .tg-grid-2, .tg-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .tg-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .tg-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .tg-main { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
    @media (min-width: 1280px) { .tg-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .tg-h2 { font-size: 1rem; font-weight: 700; margin: 0; }
    .tg-sub { font-size: .75rem; color: var(--tg-muted); margin-top: .15rem; }
    .tg-label { font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--tg-muted); }
    .tg-big { font-size: 1.75rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .tg-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .tg-wrap { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .tg-small { font-size: .75rem; color: var(--tg-muted); }
    .tg-tiny { font-size: .68rem; color: var(--tg-muted); }
    .tg-num { font-variant-numeric: tabular-nums; }
    .tg-bar { height: .4rem; border-radius: 999px; background: var(--tg-line); overflow: hidden; margin-top: .6rem; }
    .tg-bar > div { height: 100%; border-radius: 999px; }
    .tg-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; font-size: .68rem; font-weight: 700; border-radius: .4rem; white-space: nowrap; }
    .tg-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .8rem; font-size: .75rem; font-weight: 700; border-radius: .5rem; border: 0; cursor: pointer; background: var(--tg-soft); color: var(--tg-text); box-shadow: 0 0 0 1px var(--tg-ring); }
    .tg-btn:hover { filter: brightness(.96); }
    .tg-btn-primary { background: rgb(var(--primary-600)); color: #fff; box-shadow: none; }
    .tg-select { font-size: .85rem; font-weight: 600; border-radius: .5rem; padding: .4rem 2rem .4rem .7rem; background-color: var(--tg-card); color: var(--tg-text); border: 1px solid var(--tg-ring); }
    .tg-soft { background: var(--tg-soft); border-radius: .6rem; padding: .75rem; box-shadow: 0 0 0 1px var(--tg-ring); }

    /* Nada warna */
    .t-success { --c: var(--success-500); --ct: var(--success-600); }
    .t-warning { --c: var(--warning-500); --ct: var(--warning-600); }
    .t-danger  { --c: var(--danger-500);  --ct: var(--danger-600); }
    .t-info    { --c: var(--info-500);    --ct: var(--info-600); }
    .t-primary { --c: var(--primary-500); --ct: var(--primary-600); }
    .t-gray    { --c: var(--gray-400);    --ct: var(--gray-500); }
    .dark .t-success { --ct: var(--success-400); } .dark .t-warning { --ct: var(--warning-400); } .dark .t-danger { --ct: var(--danger-400); }
    .dark .t-info { --ct: var(--info-400); } .dark .t-primary { --ct: var(--primary-400); } .dark .t-gray { --ct: var(--gray-400); }
    .tone-text { color: rgb(var(--ct)); }
    .tone-bg { background: rgba(var(--c), .1); color: rgb(var(--ct)); box-shadow: 0 0 0 1px rgba(var(--c), .25); }
    .tone-fill { background: rgb(var(--c)); }
    .tone-edge { border-left: 3px solid rgb(var(--c)); }

    /* Hero */
    .tg-hero { position: relative; overflow: hidden; border-radius: .75rem; padding: 1.5rem; color: #fff;
               background: radial-gradient(120% 140% at 100% 0%, rgba(var(--primary-500), .35) 0%, transparent 55%), rgb(var(--gray-900)); box-shadow: 0 0 0 1px rgba(255,255,255,.08); }
    .dark .tg-hero { background: radial-gradient(120% 140% at 100% 0%, rgba(var(--primary-500), .25) 0%, transparent 55%), rgb(var(--gray-950)); }
    .tg-hero .tg-sub, .tg-hero .tg-label { color: rgb(var(--gray-400)); }
    .tg-hero-row { display: flex; flex-direction: column; gap: 1.25rem; }
    @media (min-width: 1024px) { .tg-hero-row { flex-direction: row; align-items: center; justify-content: space-between; } }
    .tg-hero .tg-select { background-color: rgba(255,255,255,.06); color: #fff; border-color: rgba(255,255,255,.15); }
    .tg-hero .tg-select option { color: #000; }
    .tg-dot { width: .5rem; height: .5rem; border-radius: 999px; display: inline-block; }
    .tg-pulse { animation: tgPulse 1.6s ease-in-out infinite; }
    @keyframes tgPulse { 0%,100% { opacity: 1 } 50% { opacity: .35 } }

    /* Jalur kesihatan 24 jam */
    .tg-strip { display: grid; grid-template-columns: repeat(24, minmax(0, 1fr)); gap: 3px; margin-top: .75rem; }
    .tg-strip > div { height: 2.25rem; border-radius: .3rem; }

    /* Senarai risiko */
    .tg-risk { display: flex; flex-direction: column; gap: .6rem; padding: .9rem 1rem; border-radius: .6rem; background: var(--tg-soft); box-shadow: 0 0 0 1px var(--tg-ring); }
    .tg-factor { display: flex; justify-content: space-between; gap: .5rem; font-size: .75rem; padding: .35rem 0; border-bottom: 1px dashed var(--tg-line); }
    .tg-factor:last-child { border-bottom: 0; }

    /* Modal */
    .tg-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(3, 7, 18, .55); backdrop-filter: blur(3px); }
    .tg-modal-box { width: 100%; max-height: 90vh; overflow-y: auto; background: var(--tg-card); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 0 0 1px var(--tg-ring), 0 20px 50px rgba(0,0,0,.3); }
    .tg-steps { margin: .5rem 0 0; padding-left: 1.2rem; font-size: .85rem; line-height: 1.55; }
    .tg-steps li { margin-bottom: .35rem; }
    .tg-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .tg-table th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--tg-muted); padding: .5rem; border-bottom: 1px solid var(--tg-line); }
    .tg-table td { padding: .55rem .5rem; border-bottom: 1px solid var(--tg-line); }
    .tg-table tr.is-now td { background: rgba(var(--primary-500), .1); font-weight: 700; }

    /* Carta */
    .tg-chart text { font-size: 10px; fill: var(--tg-muted); }
    .tg-legend { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .72rem; color: var(--tg-muted); }
    .tg-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .tg-legend i { display: inline-block; width: 1.1rem; height: 0; border-top: 2px solid; }

    /* Navigasi antara halaman My Pond */
    .tg-subnav { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: 2px; }
    .tg-subnav a { display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; padding: .4rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 700; text-decoration: none;
                   color: var(--tg-muted); background: var(--tg-card); box-shadow: 0 0 0 1px var(--tg-ring); }
    .tg-subnav a:hover { color: var(--tg-text); }
    .tg-subnav a.is-active { background: rgb(var(--primary-600)); color: #fff; box-shadow: none; }
    .tg-subnav .tg-count { background: rgb(var(--danger-500)); color: #fff; border-radius: 999px; padding: 0 .4rem; font-size: .65rem; }

    /* Jubin ringkasan yang boleh diklik */
    a.tg-tile { display: flex; flex-direction: column; gap: .35rem; text-decoration: none; color: inherit; transition: transform .15s ease, box-shadow .15s ease; }
    a.tg-tile:hover { transform: translateY(-2px); box-shadow: 0 0 0 1px rgba(var(--primary-500), .35), 0 6px 16px rgba(0,0,0,.06); }
    .tg-more { font-size: .72rem; font-weight: 700; color: rgb(var(--primary-600)); margin-top: auto; }
    .dark .tg-more { color: rgb(var(--primary-400)); }

    /* Bahagian boleh lipat */
    .tg details > summary { cursor: pointer; list-style: none; }
    .tg details > summary::-webkit-details-marker { display: none; }
    .tg details > summary .tg-chev { transition: transform .2s ease; display: inline-block; }
    .tg details[open] > summary .tg-chev { transform: rotate(90deg); }
</style>
