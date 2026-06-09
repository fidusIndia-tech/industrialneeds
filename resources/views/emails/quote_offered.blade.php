<!DOCTYPE html>
<html>
<head>
    <title>Your Quote</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2 style="color: #0056b3;">Your Quote is Ready</h2>
    <p>Dear {{ $quote->customer_name }},</p>
    <p>Thank you for your interest. Here is our quote for your request
        <strong>{{ $quote->reference_no ?? ('#' . $quote->id) }}</strong>:</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Product</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ optional($quote->product)->name ?? ('Product #' . $quote->product_id) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Quantity</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $quote->quoted_qty ?? $quote->requested_qty }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Unit Price</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $quote->quoted_unit_price }}</td>
        </tr>
        @if($quote->quote_valid_until)
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Valid Until</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ \Illuminate\Support\Carbon::parse($quote->quote_valid_until)->format('d M Y') }}</td>
        </tr>
        @endif
    </table>

    @if($quote->admin_note)
    <p style="background-color: #f9f9f9; padding: 12px; border-left: 4px solid #0056b3;">
        {{ $quote->admin_note }}
    </p>
    @endif

    <p style="margin: 24px 0;">
        <a href="{{ $acceptUrl }}"
           style="background-color: #0056b3; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
            View &amp; Accept Quote
        </a>
    </p>

    <hr style="border: none; border-top: 1px solid #eee;">
    <p style="font-size: 12px; color: #888;"><em>If you did not request this quote, you can ignore this email.</em></p>

</body>
</html>
