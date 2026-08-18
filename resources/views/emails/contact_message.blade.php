<!DOCTYPE html>
<html>
<head>
    <title>New Contact Enquiry</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2 style="color: #0056b3;">📩 New Contact Enquiry</h2>
    <p>Someone has submitted the contact form on the website. Details below:</p>

    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0056b3; margin-bottom: 20px;">
        <p><strong>Name:</strong> {{ $contact->name ?: 'Not provided' }}</p>
        <p><strong>Mobile:</strong> {{ $contact->mobile_number ?: 'Not provided' }}</p>
        <p><strong>Email:</strong> {{ $contact->email ?: 'Not provided' }}</p>
        <p><strong>Subject:</strong> {{ $contact->subject ?: 'Not provided' }}</p>
        <p><strong>Received:</strong> {{ $contact->created_at }}</p>
    </div>

    <h3>Message:</h3>
    <p style="background-color: #fff; padding: 15px; border: 1px solid #ddd;">
        {{ $contact->message }}
    </p>

    <br>
    <hr style="border: none; border-top: 1px solid #eee;">
    <p style="font-size: 12px; color: #888;">
        <em>Reply to this email to answer the customer directly. The full list is in
        Admin &rsaquo; Contacts.</em>
    </p>

</body>
</html>
