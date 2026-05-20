<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting to Paytm…</title>
</head>
<body>
@php($paytmAction = \Illuminate\Support\Facades\Config::get('config_paytm.PAYTM_TXN_URL'))

<center>
    <h1>Please do not refresh this page...</h1>
    @if(empty($paytmAction))
        <p style="color:#c00;font-weight:bold">
            Paytm gateway URL is not configured. Check admin → payment methods → Paytm,
            then run <code>php artisan config:clear</code>.
        </p>
    @endif
</center>

<form id="paytmForm" name="paytmForm" method="post" action="{{ $paytmAction }}">
    @foreach($paramList as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <input type="hidden" name="CHECKSUMHASH" value="{{ $checkSum }}">

    <noscript>
        <center>
            <p>JavaScript is disabled. Click the button below to continue.</p>
            <button type="submit">Continue to Paytm</button>
        </center>
    </noscript>
</form>

<center style="margin-top:20px;">
    <button type="button" id="paytmManualSubmit" style="display:none;padding:10px 20px;">
        Click here if you are not redirected automatically
    </button>
</center>

<script>
    (function () {
        var form = document.getElementById('paytmForm');
        if (!form || !form.action) {
            console.error('[Paytm] form or action URL missing — cannot submit');
            return;
        }
        try {
            form.submit();
        } catch (e) {
            console.error('[Paytm] auto-submit failed:', e);
        }
        // Show a manual fallback button after 3 s in case the auto-submit was blocked.
        setTimeout(function () {
            var btn = document.getElementById('paytmManualSubmit');
            if (btn) {
                btn.style.display = 'inline-block';
                btn.onclick = function () { form.submit(); };
            }
        }, 3000);
    })();
</script>
</body>
</html>
