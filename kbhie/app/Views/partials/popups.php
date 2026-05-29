<?php
// Server-side selection of active popups for the current request.
$popups = [];
try {
    $db = \Config\Database::connect();
    $now = date('Y-m-d H:i:s');
    $popups = $db->table('popups')
        ->where('is_active', 1)
        ->groupStart()
            ->where('starts_at IS NULL')
            ->orWhere('starts_at <=', $now)
        ->groupEnd()
        ->groupStart()
            ->where('ends_at IS NULL')
            ->orWhere('ends_at >=', $now)
        ->groupEnd()
        ->get()->getResultArray();
} catch (\Throwable $e) {
    $popups = [];
}
if (empty($popups)) return;
?>

<div id="kb-popups"
     x-data="kbPopups(<?= esc(json_encode(array_map(function ($p) {
         return [
             'id'      => (int) $p['id'],
             'trigger' => $p['trigger'],
             'value'   => (int) $p['trigger_value'],
             'freq'    => (int) $p['frequency_days'],
             'title'   => $p['title'],
             'subtitle'=> $p['subtitle'],
             'cta'     => $p['cta_text'],
             'fields'  => json_decode($p['capture_fields'] ?? '[]', true) ?: ['email'],
             'reward'  => $p['reward_message'],
         ];
     }, $popups)), 'attr') ?>)"
     x-init="init()">

    <template x-for="p in active" :key="p.id">
        <div x-show="open === p.id" x-cloak x-transition.opacity
             class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
             @click.self="dismiss(p)">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 lg:p-8 relative">
                <button @click="dismiss(p)" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500" aria-label="Close">&times;</button>
                <h3 class="text-2xl font-black" x-text="p.title"></h3>
                <p class="mt-1 text-sm text-slate-600" x-text="p.subtitle"></p>

                <form @submit.prevent="submit(p, $event)" class="mt-5 space-y-2">
                    <template x-for="f in p.fields" :key="f">
                        <input :name="f" :type="f === 'email' ? 'email' : (f === 'phone' ? 'tel' : 'text')"
                               :placeholder="f.charAt(0).toUpperCase() + f.slice(1)"
                               required class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">
                    </template>
                    <button class="w-full px-4 py-3 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold" x-text="p.cta"></button>
                </form>
                <p class="mt-3 text-[11px] text-center text-slate-400">By submitting you agree to receive offers from Krafty Khoobie. Unsubscribe anytime.</p>
                <p x-show="message" x-text="message" class="mt-3 text-center text-sm text-emerald-600"></p>
            </div>
        </div>
    </template>
</div>

<script>
function kbPopups(list) {
    return {
        active: list,
        open: null,
        message: '',
        init() {
            const csrf = '<?= csrf_hash() ?>';
            const csrfName = '<?= csrf_token() ?>';
            this.csrf = { name: csrfName, value: csrf };

            for (const p of this.active) {
                if (this.wasShown(p)) continue;
                this.schedule(p);
            }
        },
        wasShown(p) {
            const key = 'kb_popup_' + p.id;
            const last = localStorage.getItem(key);
            if (!last) return false;
            const days = (Date.now() - parseInt(last, 10)) / 86400000;
            return days < (p.freq || 1);
        },
        schedule(p) {
            if (p.trigger === 'time_delay') {
                setTimeout(() => this.show(p), Math.max(2, p.value) * 1000);
            } else if (p.trigger === 'scroll_percent') {
                const onScroll = () => {
                    const pct = (window.scrollY + window.innerHeight) / document.body.scrollHeight * 100;
                    if (pct >= p.value) { window.removeEventListener('scroll', onScroll); this.show(p); }
                };
                window.addEventListener('scroll', onScroll, { passive: true });
            } else if (p.trigger === 'exit_intent') {
                let fired = false;
                document.addEventListener('mouseleave', (e) => {
                    if (fired || e.clientY > 0) return;
                    fired = true; this.show(p);
                });
            }
        },
        show(p) {
            if (this.open) return;
            this.open = p.id;
            localStorage.setItem('kb_popup_' + p.id, Date.now().toString());
            if (window.kbTrack) window.kbTrack('PopupShown', { popup_id: p.id });
        },
        dismiss(p) {
            this.open = null;
            if (window.kbTrack) window.kbTrack('PopupDismissed', { popup_id: p.id });
        },
        async submit(p, ev) {
            const fd = new FormData(ev.target);
            fd.append(this.csrf.name, this.csrf.value);
            fd.append('popup_id', p.id);
            const r = await fetch('<?= base_url('lead/capture') ?>', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' },
            });
            const j = await r.json();
            if (j.ok) {
                this.message = j.message || 'Thank you!';
                if (window.kbTrack) window.kbTrack('Lead', { popup_id: p.id });
                setTimeout(() => { this.open = null; }, 2500);
            } else {
                this.message = j.error || 'Something went wrong.';
            }
        }
    }
}
</script>
