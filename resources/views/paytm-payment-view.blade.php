<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting to Paytm…</title>
</head>
<body>
<center>
    <h1>Please do not refresh this page...</h1>
    @if(empty($formAction) || empty($txnToken))
        <p style="color:#c00;font-weight:bold">
            Paytm could not start this payment. Please go back and try again.
        </p>
    @endif
</center>

<form id="paytmForm" name="paytmForm" method="post" action="{{ $formAction }}">
    <input type="hidden" name="mid"      value="{{ $mid }}">
    <input type="hidden" name="orderId"  value="{{ $orderId }}">
    <input type="hidden" name="txnToken" value="{{ $txnToken }}">

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
