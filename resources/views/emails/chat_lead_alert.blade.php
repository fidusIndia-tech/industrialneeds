<!DOCTYPE html>
<html>
<head><title>Chat Lead</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    @if($engagementOnly)
        <h2 style="color: #0056b3;">👀 A visitor just opened the chat</h2>
        <p>Someone started a chat on the website. Details so far:</p>
    @else
        <h2 style="color: #0056b3;">📞 New Website Lead — call them now</h2>
        <p>A visitor left their details in the website chat:</p>
    @endif

    <div style="background:#f9f9f9; padding:15px; border-left:4px solid #0056b3; margin-bottom:20px;">
        <p><strong>Name:</strong> {{ $lead->name ?? '—' }}</p>
        <p><strong>Company:</strong> {{ $lead->company_name ?? '—' }}</p>
        <p><strong>Location:</strong> {{ $lead->location ?? '—' }}</p>
        <p><strong>Phone:</strong>
            @if($lead->phone)<a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>@else — @endif
        </p>
        <p><strong>Email:</strong> {{ $lead->email ?? '—' }}</p>
        <p><strong>Looking for:</strong> {{ $lead->product ?? '—' }}
            @if($lead->quantity) (qty {{ $lead->quantity }})@endif</p>
        <p><strong>Intent:</strong> {{ $lead->intent ?? '—' }}</p>
        @if($lead->message)<p><strong>Note:</strong> {{ $lead->message }}</p>@endif
        <p><strong>Page:</strong> {{ $lead->page_url ?? '—' }}</p>
        <p><strong>Time:</strong> {{ optional($lead->created_at)->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</p>
    </div>

    <hr style="border:none;border-top:1px solid #eee;">
    <p style="font-size:12px;color:#888;"><em>View all chat leads in your Admin Dashboard → Chat Leads.</em></p>
</body>
</html>
