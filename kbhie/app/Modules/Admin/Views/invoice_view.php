<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $lines = json_decode($invoice['lines'] ?? '[]', true) ?: []; $seller = json_decode($invoice['seller_address'] ?? '{}', true) ?: []; $buyer = json_decode($invoice['buyer_address'] ?? '{}', true) ?: []; ?>
<div class="bg-white rounded-2xl shadow-sm p-8 max-w-3xl mx-auto">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-black">TAX INVOICE</h1>
            <div class="mt-1 text-xs text-slate-500">Invoice <?= esc($invoice['invoice_number']) ?> · <?= esc($invoice['invoice_date']) ?></div>
        </div>
        <div class="text-right text-sm">
            <div class="font-bold"><?= esc($invoice['seller_name']) ?></div>
            <div class="text-xs text-slate-500">GSTIN: <?= esc($invoice['seller_gstin'] ?: '—') ?></div>
        </div>
    </div>
    <div class="mt-6 grid sm:grid-cols-2 gap-6 text-sm">
        <div>
            <div class="text-xs uppercase text-slate-500">Bill to</div>
            <div class="font-semibold"><?= esc($invoice['buyer_name']) ?></div>
            <div class="text-xs"><?= esc($buyer['line1'] ?? '') ?>, <?= esc($buyer['city'] ?? '') ?>, <?= esc($buyer['state'] ?? '') ?> <?= esc($buyer['pincode'] ?? '') ?></div>
            <?php if ($invoice['buyer_gstin']): ?><div class="text-xs">GSTIN: <?= esc($invoice['buyer_gstin']) ?></div><?php endif; ?>
        </div>
        <div>
            <div class="text-xs uppercase text-slate-500">Place of supply</div>
            <div><?= esc($invoice['place_of_supply']) ?> <?= $invoice['is_interstate'] ? '(interstate)' : '(intrastate)' ?></div>
        </div>
    </div>
    <table class="w-full text-sm mt-6">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-2 text-left">Item</th>
                <th class="px-3 py-2 text-left">HSN</th>
                <th class="px-3 py-2 text-right">Qty</th>
                <th class="px-3 py-2 text-right">Rate</th>
                <th class="px-3 py-2 text-right">Taxable</th>
                <th class="px-3 py-2 text-right">Tax</th>
                <th class="px-3 py-2 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($lines as $ln): ?>
                <tr>
                    <td class="px-3 py-2"><?= esc($ln['name']) ?></td>
                    <td class="px-3 py-2"><?= esc($ln['hsn']) ?></td>
                    <td class="px-3 py-2 text-right"><?= $ln['qty'] ?></td>
                    <td class="px-3 py-2 text-right"><?= kb_money((int)($ln['rate'])) ?></td>
                    <td class="px-3 py-2 text-right"><?= kb_money((int)($ln['taxable'])) ?></td>
                    <td class="px-3 py-2 text-right"><?= kb_money((int)(($ln['cgst'] + $ln['sgst'] + $ln['igst']))) ?></td>
                    <td class="px-3 py-2 text-right font-semibold"><?= kb_money((int)($ln['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="mt-4 text-right text-sm space-y-1">
        <div>Taxable: <?= kb_money((int)($invoice['taxable_amount'])) ?></div>
        <div>CGST: <?= kb_money((int)($invoice['cgst_amount'])) ?></div>
        <div>SGST: <?= kb_money((int)($invoice['sgst_amount'])) ?></div>
        <div>IGST: <?= kb_money((int)($invoice['igst_amount'])) ?></div>
        <div class="text-lg font-black border-t border-slate-200 pt-2 mt-2">Total: <?= kb_money((int)($invoice['total_amount'])) ?></div>
    </div>
</div>

<?= $this->endSection() ?>
