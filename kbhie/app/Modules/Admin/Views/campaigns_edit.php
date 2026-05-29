<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('admin/campaigns/save') ?>" class="max-w-5xl grid lg:grid-cols-[1fr_320px] gap-4" x-data="campaignAi()">
    <?= csrf_field() ?>
    <?php if ($row): ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><?php endif; ?>

    <div class="space-y-4">
        <a href="<?= base_url('admin/campaigns') ?>" class="text-sm text-slate-500 hover:underline">← All campaigns</a>

        <!-- AI drafter -->
        <div class="bg-violet-50 border-2 border-dashed border-violet-300 rounded-2xl p-5 space-y-3">
            <h2 class="font-bold text-violet-900">✨ AI Campaign Drafter</h2>
            <select x-model="aiChannel" class="w-full px-3 py-2 rounded-lg border-2 border-violet-200">
                <option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="sms">SMS</option>
            </select>
            <select x-model="aiAudience" class="w-full px-3 py-2 rounded-lg border-2 border-violet-200">
                <option value="active_customers">All active customers</option>
                <option value="recent_buyers">Recent buyers (60 days)</option>
                <option value="abandoned_cart">Abandoned cart parents</option>
                <option value="unverified">Unverified accounts</option>
            </select>
            <textarea x-model="aiGoal" rows="2" placeholder="Goal — e.g. announce weekend Bharatanatyam classes in Mumbai with WELCOME10 code" class="w-full px-3 py-2 rounded-lg border-2 border-violet-200 text-sm"></textarea>
            <button type="button" @click="draft()" :disabled="busy"
                    class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm disabled:opacity-50">
                <span x-show="!busy">✨ Generate draft</span><span x-show="busy" x-cloak>Writing…</span>
            </button>
        </div>

        <!-- Editor -->
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Campaign</h2>
            <input name="name" required x-model="name" placeholder="Internal name *" value="<?= esc($row['name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
            <div class="grid sm:grid-cols-2 gap-3">
                <select name="channel" x-model="channel" class="px-3 py-2 rounded-lg border-2 border-slate-200">
                    <?php foreach (['email','whatsapp','sms'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($row['channel'] ?? 'email')===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="audience" class="px-3 py-2 rounded-lg border-2 border-slate-200">
                    <?php foreach (['all','active_customers','recent_buyers','by_city','by_tier','unverified','abandoned_cart'] as $a): ?>
                        <option value="<?= $a ?>" <?= ($row['audience'] ?? 'all')===$a?'selected':'' ?>><?= esc(ucwords(str_replace('_',' ', $a))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input name="audience_arg" placeholder="Audience arg (e.g. 'Mumbai' for by_city)" value="<?= esc($row['audience_arg'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
            <input name="subject" x-model="subject" placeholder="Subject line *" required value="<?= esc($row['subject'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 font-bold">
            <textarea name="body_html" x-model="body" rows="14" placeholder="Body (HTML for email, plain for WhatsApp/SMS)" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 font-mono text-sm"><?= esc($row['body_html'] ?? '') ?></textarea>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Schedule</h2>
            <select name="status" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                <?php foreach (['draft','scheduled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($row['status'] ?? 'draft')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="scheduled_at" type="datetime-local" value="<?= esc(substr($row['scheduled_at'] ?? '', 0, 16)) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-sm">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="ai_generated" value="1" <?= !empty($row['ai_generated'])?'checked':'' ?>>
                Mark as AI-generated
            </label>
            <button type="submit" class="w-full btn-primary">💾 Save</button>
        </div>

        <?php if ($row && $row['status'] !== 'sent'): ?>
            <form method="post" action="<?= base_url('admin/campaigns/' . $row['id'] . '/send') ?>" onsubmit="return confirm('Send this campaign NOW to the selected audience? This cannot be undone.')">
                <?= csrf_field() ?>
                <button class="w-full px-4 py-3 rounded-full bg-rose-500 hover:bg-rose-600 text-white font-bold uppercase tracking-wider shadow-cta">🚀 Send now</button>
            </form>
        <?php elseif ($row && $row['status'] === 'sent'): ?>
            <div class="bg-emerald-50 rounded-2xl p-4 text-sm text-emerald-700">
                ✓ Sent to <strong><?= number_format($row['recipients_n']) ?></strong> on <?= date('j M, g:i A', strtotime($row['sent_at'])) ?>
            </div>
        <?php endif; ?>
    </aside>
</form>

<script>
function campaignAi() {
    return {
        aiChannel: 'email', aiAudience: 'active_customers', aiGoal: '',
        busy: false,
        name:    <?= json_encode($row['name'] ?? '') ?>,
        subject: <?= json_encode($row['subject'] ?? '') ?>,
        body:    <?= json_encode($row['body_html'] ?? '') ?>,
        channel: <?= json_encode($row['channel'] ?? 'email') ?>,
        async draft() {
            this.busy = true;
            const fd = new FormData();
            fd.append('channel', this.aiChannel); fd.append('audience', this.aiAudience); fd.append('goal', this.aiGoal);
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            try {
                const r = await fetch('<?= base_url('admin/campaigns/ai-draft') ?>', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.ok) {
                    this.subject = j.subject || this.subject;
                    this.body = j.body || this.body;
                    if (! this.name) this.name = j.subject;
                } else alert(j.error || 'AI failed');
            } catch (e) { alert('Network error'); }
            this.busy = false;
        }
    }
}
</script>

<?= $this->endSection() ?>
