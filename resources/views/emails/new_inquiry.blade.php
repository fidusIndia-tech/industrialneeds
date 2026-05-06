<!DOCTYPE html>
<html>
<head>
    <title>New Lead Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2 style="color: #0056b3;">🚨 New Product Inquiry Received!</h2>
    <p>A new customer has submitted an inquiry form on the website. Here are their details:</p>

    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0056b3; margin-bottom: 20px;">
        <p><strong>Customer Name:</strong> {{ $inquiry->customer_name }}</p>
        <p><strong>Phone Number:</strong> {{ $inquiry->phone_number }}</p>
        <p><strong>Email Address:</strong> {{ $inquiry->email ?? 'Not provided' }}</p>
    </div>

    <h3>Customer Message:</h3>
    <p style="background-color: #fff; padding: 15px; border: 1px solid #ddd;">
        {{ $inquiry->message }}
    </p>

    <br>
    <hr style="border: none; border-top: 1px solid #eee;">
    <p style="font-size: 12px; color: #888;">
        <em>Please log into your Admin Dashboard to update the status of this lead.</em>
    </p>

</body>
</html>