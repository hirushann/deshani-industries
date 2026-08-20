<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 40px; color: #333; line-height: 1.5; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 50px; padding-bottom: 25px; border-bottom: 2px solid #f4f4f5; }
        .company-info-wrapper { display: flex; align-items: center; gap: 30px; }
        .company-details h1 { margin: 0 0 5px 0; color: #18181b; font-size: 26px; font-weight: 700; letter-spacing: -0.5px; }
        .company-details p { margin: 2px 0; color: #52525b; font-size: 14px; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0 0 10px 0; color: #18181b; font-size: 28px; letter-spacing: 1px; font-weight: 800; }
        .invoice-details p { margin: 4px 0; color: #52525b; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .totals { float: right; width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .totals-row.final { font-weight: bold; border-top: 2px solid #333; }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="invoice-header">
        <div class="company-info-wrapper">
            
            <div class="company-details">
                <div class="company-logo">
                    <img src="{{ asset('img/v-logo.png') }}" alt="Deshani Industries Logo" style="max-width: 220px; height: auto;">
                </div>
                <p>101/1 Bogahawatta road,</p>
                <p>Hekitta, Wattala.</p>
                <p>Tel: 0777 386 356</p>
            </div>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p>Invoice No: <strong>#{{ $invoice->invoice_number }}</strong></p>
            <p>Date: <strong>{{ \Carbon\Carbon::parse($invoice->issued_date)->format('M d, Y') }}</strong></p>
        </div>
    </div>

    <div style="margin-bottom: 30px;">
        <strong>Bill To:</strong><br>
        {{ $invoice->order->customer->name }}<br>
        {{ $invoice->order->customer->address ?? 'No Address' }}<br>
        {{ $invoice->order->customer->phone ?? '' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align: right">Quantity</th>
                <th style="text-align: right">Unit Price</th>
                <th style="text-align: right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order->items as $item)
            <tr>
                <td>
                    {{ $item->product->name }}
                    @if($item->product->sku)
                        <br><span style="font-size: 0.85em; color: #666;">{{ $item->product->sku }}</span>
                    @endif
                </td>
                <td style="text-align: right">{{ $item->quantity }}</td>
                <td style="text-align: right">{{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row final">
            <span>Total Amount:</span>
            <span>LKR {{ number_format($invoice->total_amount, 2) }}</span>
        </div>
        @if($invoice->payments->sum('amount') > 0)
        <div class="totals-row">
            <span>Paid:</span>
            <span>({{ number_format($invoice->payments->sum('amount'), 2) }})</span>
        </div>
        @endif
        <div class="totals-row">
            <span>Balance Due:</span>
            <span>LKR {{ number_format($invoice->balance_due, 2) }}</span>
        </div>
    </div>
</body>
</html>
