<?php
/**
 * Location picker modal — opens on dispatch('open-location-picker').
 * Persists chosen city+locality via POST /location/set, then reloads the page
 * so server-rendered "Near you" sections + meetup filters reflect the change.
 */
?>
<div x-data="kbLocationPicker()"
     x-cloak
     @open-location-picker.window="open = true; loadLocalities()"
     x-show="open"
     class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4">

    <!-- Backdrop -->
    <div x-show="open" x-transition.opacity @click="open = false"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    <!-- Sheet -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 scale-100"
         class="relative bg-white rounded-t-3xl sm:rounded-3xl shadow-soft-lg w-full sm:max-w-lg max-h-[85vh] overflow-y-auto">

        <div class="sticky top-0 bg-white border-b border-slate-100 px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="h-display text-xl font-black">Where are you?</h2>
                <p class="text-xs text-slate-500">We'll show classes &amp; meetups near you first.</p>
            </div>
            <button @click="open = false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>

        <div class="p-5 space-y-4">
            <!-- Detect / clear -->
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="detect()"
                        class="px-3 py-2 rounded-full bg-brand-100 hover:bg-brand-200 text-brand-700 text-xs font-bold transition">
                    📍 Detect my location
                </button>
                <?php if (! empty($location_label)): ?>
                    <button type="button" @click="clearLocation()"
                            class="px-3 py-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                        🗑️ Clear "<?= esc($location_label) ?>"
                    </button>
                <?php endif; ?>
            </div>

            <!-- City list -->
            <div>
                <div class="eyebrow text-slate-500 mb-2">Pick your city</div>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach (($all_cities ?? []) as $c): ?>
                        <button type="button" @click="selectCity('<?= esc($c['city'], 'attr') ?>')"
                                class="text-left px-3 py-2.5 rounded-lg border-2 border-slate-200 hover:border-brand-400 hover:bg-brand-50 transition"
                                :class="city === '<?= esc($c['city'], 'attr') ?>' ? 'border-brand-500 bg-brand-50' : ''">
                            <div class="font-bold text-sm text-slate-900"><?= esc($c['city']) ?></div>
                            <div class="text-[10px] text-slate-500"><?= (int) $c['localities'] ?> localities · <?= (int) $c['n'] ?> events</div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Locality dropdown (once city picked) -->
            <div x-show="city" x-cloak class="space-y-2">
                <div class="eyebrow text-slate-500">Pick locality in <span x-text="city" class="text-brand-600"></span> (optional)</div>
                <select x-model="locality" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-sm focus:border-brand-400 focus:outline-none">
                    <option value="">All localities</option>
                    <template x-for="loc in localities">
                        <option :value="loc.locality" x-text="loc.locality + ' (' + loc.n + ')'"></option>
                    </template>
                </select>
                <input type="text" x-model="pincode" placeholder="Pincode (optional)"
                       maxlength="6" pattern="\d{6}"
                       class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-sm focus:border-brand-400 focus:outline-none">
            </div>

            <button type="button" @click="save()" :disabled="! city || busy"
                    class="w-full h-12 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold uppercase tracking-wider shadow-cta hover:shadow-cta-lg disabled:opacity-50 transition">
                <span x-show="!busy">Use this location</span>
                <span x-show="busy" x-cloak>Saving…</span>
            </button>
        </div>
    </div>
</div>

<script>
function kbLocationPicker() {
    return {
        open: false,
        city: '<?= esc($location['city'] ?? '', 'js') ?>',
        locality: '<?= esc($location['locality'] ?? '', 'js') ?>',
        pincode: '<?= esc($location['pincode'] ?? '', 'js') ?>',
        localities: [],
        busy: false,
        async loadLocalities() {
            if (! this.city) return;
            try {
                const r = await fetch('<?= base_url('feed/products.json') ?>', { method:'HEAD' });  // warm
            } catch (e) {}
            // Localities are static per-city in our seed — fetch fresh:
            const r = await fetch('<?= base_url('feed/products.json') ?>?_=1', { method:'GET' });  // placeholder; actual list:
            // For speed, derive localities from the all_cities prop on page render:
            // (We re-call the city listing inline; simpler to inline-render localities per city via PHP)
        },
        selectCity(c) { this.city = c; this.locality = ''; this.pincode = ''; this.fetchLocalities(); },
        async fetchLocalities() {
            // Pull localities for the chosen city from cached endpoint
            // We use a tiny JSON shim — locationCityLocalities is rendered below
            this.localities = (window.kbLocalities && window.kbLocalities[this.city]) || [];
        },
        async detect() {
            if (! navigator.geolocation) { alert('Browser does not support geolocation'); return; }
            navigator.geolocation.getCurrentPosition(async (pos) => {
                // Reverse-geocode via free API — fallback to city pick if it fails
                try {
                    const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&zoom=14`);
                    const j = await r.json();
                    const city = j.address?.city || j.address?.town || j.address?.state_district || '';
                    const locality = j.address?.suburb || j.address?.neighbourhood || '';
                    const pin  = j.address?.postcode || '';
                    if (city) { this.city = city; this.locality = locality; this.pincode = pin; this.fetchLocalities(); }
                } catch (e) {}
            }, () => alert('Could not detect — please pick from the list.'));
        },
        async save() {
            this.busy = true;
            const fd = new FormData();
            fd.append('city', this.city); fd.append('locality', this.locality || ''); fd.append('pincode', this.pincode || '');
            try {
                await fetch('<?= base_url('location/set') ?>', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                location.reload();
            } catch (e) { this.busy = false; alert('Could not save — try again.'); }
        },
        async clearLocation() {
            await fetch('<?= base_url('location/clear') ?>', { method: 'POST', headers: { 'Accept': 'application/json' } });
            location.reload();
        },
    }
}
// Inline localities per city so the picker can show them without an extra round-trip:
window.kbLocalities = <?= json_encode(array_reduce(($all_cities ?? []), function ($acc, $c) {
    $locs = \App\Libraries\LocationService::localitiesIn($c['city']);
    $acc[$c['city']] = array_values(array_map(fn ($l) => ['locality' => $l['locality'], 'n' => (int) $l['n']], $locs));
    return $acc;
}, [])) ?>;
</script>
