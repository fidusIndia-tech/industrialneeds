<!DOCTYPE html>
<html>
<head>
    <title>New Quote Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2 style="color: #0056b3;">🧾 New Quote Request Received!</h2>
    <p>A customer has requested a quote on the website. Details:</p>

    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0056b3; margin-bottom: 20px;">
        <p><strong>Reference:</strong> {{ $quote->reference_no ?? ('#' . $quote->id) }}</p>
        <p><strong>Product:</strong> {{ optional($quote->product)->name ?? ('Product #' . $quote->product_id) }}</p>
        <p><strong>Requested Quantity:</strong> {{ $quote->requested_qty }}</p>
        <p><strong>Customer Name:</strong> {{ $quote->customer_name }}</p>
        <p><strong>Phone Number:</strong> {{ $quote->phone_number }}</p>
        <p><strong>Email Address:</strong> {{ $quote->email ?? 'Not provided' }}</p>
    </div>

    <h3>Customer Message:</h3>
    <p style="background-color: #fff; padding: 15px; border: 1px solid #ddd;">
        {{ $quote->message ?? '—' }}
    </p>

    <br>
    <hr style="border: none; border-top: 1px solid #eee;">
    <p style="font-size: 12px; color: #888;">
        <em>Log into the Admin Dashboard to respond with a price.</em>
    </p>

</body>
</html>
