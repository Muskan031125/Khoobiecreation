<?php

namespace App\Libraries\Gst;

use Config\Database;

/**
 * Generate GST-compliant tax invoices from confirmed orders.
 * Computes CGST/SGST (intrastate) or IGST (interstate) per line based on
 * the seller's state and the shipping pincode's state.
 */
class InvoiceService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function generateForOrder(int $orderId): ?int
    {
        $existing = $this->db->table('invoices')->where('order_id', $orderId)->where('invoice_type', 'tax_invoice')->get()->getRowArray();
        if ($existing) return (int) $existing['id'];

        $order = $this->db->table('orders')->where('id', $orderId)->get()->getRowArray();
        if (! $order) return null;
        $items = $this->db->table('order_items oi')
            ->join('products p', 'p.id = oi.product_id')
            ->join('tax_classes tc', 'tc.id = p.tax_class_id', 'left')
            ->select('oi.*, p.hsn_code, p.name AS p_name, tc.rate_pct AS tax_rate, tc.is_inclusive')
            ->where('oi.order_id', $orderId)->get()->getResultArray();

        $shipping = json_decode($order['shipping_address'] ?? '{}', true) ?: [];
        $sellerState = $this->setting('company', 'state', 'Maharashtra');
        $buyerState  = $shipping['state'] ?? $sellerState;
        $isInterstate = strcasecmp(trim($sellerState), trim($buyerState)) !== 0;

        $lines = [];
        $totals = ['taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0, 'total' => 0];

        foreach ($items as $it) {
            $lineGross = (int) $it['line_total']; // inclusive of tax (our prices are inclusive)
            $rate      = (float) ($it['tax_rate'] ?? 0);
            $taxable   = $rate > 0 ? (int) round($lineGross * 100 / (100 + $rate)) : $lineGross;
            $taxAmt    = $lineGross - $taxable;
            $cgst = 0; $sgst = 0; $igst = 0;
            if ($isInterstate) {
                $igst = $taxAmt;
            } else {
                $cgst = (int) round($taxAmt / 2);
                $sgst = $taxAmt - $cgst;
            }
            $lines[] = [
                'name'     => $it['p_name'],
                'sku'      => json_decode($it['product_snapshot'] ?? '{}', true)['sku'] ?? null,
                'hsn'      => $it['hsn_code'] ?? '',
                'qty'      => (int) $it['qty'],
                'rate'     => (int) $it['unit_price'],
                'taxable'  => $taxable,
                'cgst'     => $cgst,
                'sgst'     => $sgst,
                'igst'     => $igst,
                'line_total' => $lineGross,
                'tax_rate' => $rate,
            ];
            $totals['taxable'] += $taxable;
            $totals['cgst']    += $cgst;
            $totals['sgst']    += $sgst;
            $totals['igst']    += $igst;
            $totals['total']   += $lineGross;
        }

        // HSN-wise summary for GSTR-1
        $hsnSummary = [];
        foreach ($lines as $l) {
            $h = $l['hsn'] ?: 'NA';
            if (! isset($hsnSummary[$h])) $hsnSummary[$h] = ['qty' => 0, 'taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0, 'rate' => $l['tax_rate']];
            $hsnSummary[$h]['qty']     += $l['qty'];
            $hsnSummary[$h]['taxable'] += $l['taxable'];
            $hsnSummary[$h]['cgst']    += $l['cgst'];
            $hsnSummary[$h]['sgst']    += $l['sgst'];
            $hsnSummary[$h]['igst']    += $l['igst'];
            $hsnSummary[$h]['total']   += $l['line_total'];
        }

        $invoiceNumber = $this->nextInvoiceNumber($order['placed_at'] ?? $order['created_at']);
        $fy = $this->financialYear($order['placed_at'] ?? $order['created_at']);

        $id = $this->db->table('invoices')->insert([
            'order_id'         => $orderId,
            'invoice_number'   => $invoiceNumber,
            'invoice_type'     => 'tax_invoice',
            'fy'               => $fy,
            'invoice_date'     => date('Y-m-d'),
            'place_of_supply'  => substr('IN-' . strtoupper($buyerState), 0, 5),
            'is_interstate'    => $isInterstate ? 1 : 0,
            'seller_gstin'     => $this->setting('company', 'gstin', ''),
            'seller_name'      => $this->setting('company', 'name', 'Krafty Khoobie Pvt Ltd'),
            'seller_address'   => json_encode(json_decode($this->setting('company', 'registered_address', '{}'), true) ?: []),
            'buyer_gstin'      => null,
            'buyer_name'       => $order['name'],
            'buyer_address'    => json_encode($shipping),
            'shipping_address' => json_encode($shipping),
            'lines'            => json_encode($lines),
            'taxable_amount'   => $totals['taxable'],
            'cgst_amount'      => $totals['cgst'],
            'sgst_amount'      => $totals['sgst'],
            'igst_amount'      => $totals['igst'],
            'cess_amount'      => $totals['cess'],
            'discount_amount'  => $order['discount_total'],
            'shipping_amount'  => $order['shipping_total'],
            'total_amount'     => $order['grand_total'],
            'hsn_summary'      => json_encode($hsnSummary),
            'generated_at'     => date('Y-m-d H:i:s'),
        ], true);

        return (int) $id;
    }

    protected function nextInvoiceNumber(string $orderDate): string
    {
        $prefix = $this->setting('orders', 'invoice_prefix', 'KK');
        $fy = $this->financialYear($orderDate);
        $fyShort = str_replace('-', '', substr($fy, 2, 5)); // e.g. 2627 from 2026-2027
        $last = $this->db->table('invoices')
            ->where('fy', $fy)->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
        $nextSeq = 1;
        if ($last && preg_match('/(\d+)$/', $last['invoice_number'], $m)) {
            $nextSeq = (int) $m[1] + 1;
        }
        return sprintf('%s/%s/%05d', $prefix, $fyShort, $nextSeq);
    }

    protected function financialYear(string $date): string
    {
        $ts = strtotime($date);
        $month = (int) date('n', $ts);
        $year  = (int) date('Y', $ts);
        $start = $month >= 4 ? $year : $year - 1;
        return $start . '-' . ($start + 1);
    }

    protected function setting(string $group, string $key, $default = null)
    {
        $row = $this->db->table('settings')->where('group_key', $group)->where('key', $key)->get()->getRowArray();
        return $row && $row['value'] !== '' ? $row['value'] : $default;
    }
}
