<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-20 bg-slate-50 text-center min-h-[50vh]">
    <div class="mx-auto max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-2xl font-black">Complete your payment</h1>
            <p class="mt-2 text-sm text-slate-600">Order <span class="font-mono font-bold">#<?= esc($order['order_number']) ?></span></p>
            <div class="mt-4 text-4xl font-black"><?= kb_money((int)($amount)) ?></div>
            <button id="kb-pay" class="mt-6 btn-primary w-full">Pay Now &rarr;</button>
            <p class="mt-3 text-xs text-slate-500">Powered by Razorpay · UPI · Cards · Netbanking · Wallets</p>
        </div>
    </div>
</section>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const opts = {
    key:      <?= json_encode($rzp['key_id']) ?>,
    amount:   <?= (int) $rzp['amount'] ?>,
    currency: <?= json_encode($rzp['currency']) ?>,
    name:     <?= json_encode(env('khoobie.brand_name', 'Krafty Khoobie')) ?>,
    description: 'Order #<?= esc($order['order_number']) ?>',
    order_id: <?= json_encode($rzp['order_id']) ?>,
    prefill: {
        name:    <?= json_encode($order['name']) ?>,
        email:   <?= json_encode($order['email']) ?>,
        contact: <?= json_encode($order['phone']) ?>
    },
    theme: { color: '#FF6F61' },
    handler: async function (response) {
        const fd = new FormData();
        fd.append('order_id', <?= (int) $order['id'] ?>);
        fd.append('razorpay_order_id', response.razorpay_order_id);
        fd.append('razorpay_payment_id', response.razorpay_payment_id);
        fd.append('razorpay_signature', response.razorpay_signature);
        const r = await fetch('<?= base_url('api/payment/razorpay/verify') ?>', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.ok) location.href = j.redirect; else alert('Verification failed.');
    },
    modal: {
        ondismiss: function () {
            location.href = '<?= base_url('checkout/thank-you/' . $order['order_number']) ?>';
        }
    }
};
document.getElementById('kb-pay').addEventListener('click', () => new Razorpay(opts).open());
</script>

<?= $this->endSection() ?>
