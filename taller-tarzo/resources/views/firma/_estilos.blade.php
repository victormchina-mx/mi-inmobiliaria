<style>
    :root { --bg:#f2f4f5; --paper:#fff; --fg:#1a2124; --muted:#5a666c; --line:#d6dde0; --accent:#0d7a4a; --danger:#b42318; }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); color: var(--fg); font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
    main { max-width: 640px; margin: 0 auto; padding: 20px 16px 48px; display: grid; gap: 16px; }
    header h1 { margin: 0; font-size: 22px; }
    header p { margin: 4px 0 0; color: var(--muted); }
    .card { background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 16px; display: grid; gap: 10px; }
    .card h2 { margin: 0; font-size: 16px; }
    dl { margin: 0; display: grid; grid-template-columns: max-content 1fr; gap: 4px 12px; }
    dt { color: var(--muted); }
    dd { margin: 0; font-weight: 500; }
    .chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .chip { background: #edf0f2; border-radius: 999px; padding: 2px 10px; font-size: 14px; }
    .chip.falta { background: #fde8e6; color: var(--danger); }
    .danos { display: grid; grid-template-columns: 120px 1fr; gap: 12px; align-items: start; }
    .danos ul { margin: 0; padding-left: 18px; }
    .fotos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .fotos img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 6px; }
    .legal { font-size: 14px; color: var(--muted); max-height: 220px; overflow-y: auto; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; }
    .legal p, .legal ol { margin: 0 0 8px; }
    label.check { display: flex; gap: 10px; align-items: flex-start; }
    label.check input { width: 22px; height: 22px; margin-top: 1px; flex: none; }
    input[type=text] { width: 100%; font: inherit; padding: 12px; border: 1px solid var(--line); border-radius: 8px; }
    .lienzo { position: relative; border: 2px dashed var(--line); border-radius: 10px; background: #fff; touch-action: none; }
    .lienzo canvas { display: block; width: 100%; height: 200px; border-radius: 10px; }
    .lienzo span { position: absolute; left: 12px; bottom: 8px; color: var(--muted); font-size: 13px; pointer-events: none; }
    .acciones { display: flex; gap: 10px; flex-wrap: wrap; }
    button { font: inherit; font-weight: 600; border: 0; border-radius: 10px; padding: 14px 18px; cursor: pointer; }
    .primario { background: var(--accent); color: #fff; flex: 1; }
    .secundario { background: #edf0f2; color: var(--fg); }
    .error { background: #fde8e6; color: var(--danger); border-radius: 8px; padding: 10px 12px; }
    .ok { background: #e2f1e9; color: var(--accent); border-radius: 8px; padding: 10px 12px; }
    button:focus-visible, input:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
</style>
