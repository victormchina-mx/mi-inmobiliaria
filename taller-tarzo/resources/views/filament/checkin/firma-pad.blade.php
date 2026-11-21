{{-- Recuadro de firma en la tableta. Guarda un PNG (data URI) en el estado del campo. --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            ctx: null, dibujando: false, ultimo: null, trazos: 0,
            init() {
                this.ajustar();
                window.addEventListener('resize', () => this.ajustar());
            },
            ajustar() {
                const c = this.$refs.lienzo, r = c.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
                if (! r.width) return;
                c.width = r.width * dpr; c.height = r.height * dpr;
                this.ctx = c.getContext('2d');
                this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                this.ctx.lineWidth = 2.4; this.ctx.lineCap = 'round'; this.ctx.lineJoin = 'round'; this.ctx.strokeStyle = '#111';
                this.trazos = 0; this.state = null;
            },
            punto(e) { const r = this.$refs.lienzo.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; },
            bajar(e) { this.dibujando = true; this.ultimo = this.punto(e); this.$refs.lienzo.setPointerCapture(e.pointerId); },
            mover(e) {
                if (! this.dibujando) return;
                const p = this.punto(e);
                this.ctx.beginPath(); this.ctx.moveTo(this.ultimo.x, this.ultimo.y); this.ctx.lineTo(p.x, p.y); this.ctx.stroke();
                this.ultimo = p; this.trazos++;
            },
            subir() {
                if (! this.dibujando) return;
                this.dibujando = false;
                if (this.trazos >= 15) this.state = this.$refs.lienzo.toDataURL('image/png');
            },
        }"
        style="display:grid; gap:8px;"
    >
        <div style="position:relative; border:2px dashed var(--gray-300); border-radius:10px; background:#fff; touch-action:none;">
            <canvas x-ref="lienzo" style="display:block; width:100%; height:200px; border-radius:10px;"
                x-on:pointerdown="bajar($event)" x-on:pointermove="mover($event)"
                x-on:pointerup="subir()" x-on:pointercancel="subir()" x-on:pointerleave="subir()"
                aria-label="Recuadro para que el cliente firme con el dedo"></canvas>
            <span x-show="trazos === 0" style="position:absolute; left:12px; bottom:8px; color:var(--gray-400); font-size:13px; pointer-events:none;">El cliente firma aquí con el dedo</span>
        </div>
        <div style="display:flex; gap:12px; align-items:center;">
            <x-filament::button color="gray" size="sm" x-on:click="ajustar()">Borrar firma</x-filament::button>
            <span x-show="state" style="color:var(--success-600); font-size:13px;">Firma capturada. Se guardará al pulsar "Guardar inventario".</span>
        </div>
    </div>
</x-dynamic-component>
