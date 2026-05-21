<?php

namespace App\Http\Controllers;

use App\CPU\CartManager;
use App\CPU\Helpers;
use App\CPU\OrderManager;
use App\Library\PaytmChecksum;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaytmController extends Controller
{
    public function payment(Request $request)
    {
        $merchantKey = (string) Config::get('config_paytm.PAYTM_MERCHANT_KEY');
        $mid         = (string) Config::get('config_paytm.PAYTM_MERCHANT_MID');
        $website     = (string) Config::get('config_paytm.PAYTM_MERCHANT_WEBSITE');
        $initiateUrl = (string) Config::get('config_paytm.PAYTM_INITIATE_TXN_URL');
        $showPayUrl  = (string) Config::get('config_paytm.PAYTM_SHOW_PAYMENT_URL');

        if ($merchantKey === '' || $mid === '' || $initiateUrl === '') {
            Log::warning('[Paytm] payment() aborted: missing config', [
                'mid_present'  => $mid !== '',
                'key_present'  => $merchantKey !== '',
                'initiate_url' => $initiateUrl,
            ]);
            Toastr::error('Paytm is not configured correctly.');
            return back();
        }

        $orderId  = 'INO' . time() . strtoupper(Str::random(4));
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $amount   = round(CartManager::cart_grand_total() - $discount, 2);
        $user     = Helpers::get_customer();

        $body = [
            'requestType' => 'Payment',
            'mid'         => $mid,
            'websiteName' => $website,
            'orderId'     => $orderId,
            'callbackUrl' => route('paytm-response'),
            'txnAmount'   => [
                'value'    => number_format($amount, 2, '.', ''),
                'currency' => 'INR',
            ],
            'userInfo'    => [
                'custId' => (string) ($user['id'] ?? 'GUEST'),
                'email'  => (string) ($user['email'] ?? ''),
                'mobile' => (string) ($user['phone'] ?? ''),
            ],
        ];

        $signature = PaytmChecksum::generateSignature(json_encode($body), $merchantKey);
        $payload   = ['body' => $body, 'head' => ['signature' => $signature]];

        $endpoint = $initiateUrl . '?mid=' . urlencode($mid) . '&orderId=' . urlencode($orderId);
        $response = $this->postJson($endpoint, $payload);

        $resultStatus = $response['body']['resultInfo']['resultStatus'] ?? null;
        $resultCode   = $response['body']['resultInfo']['resultCode']   ?? null;
        $resultMsg    = $response['body']['resultInfo']['resultMsg']    ?? null;
        $txnToken     = $response['body']['txnToken']                   ?? null;

        Log::debug('[Paytm] initiateTransaction response', [
            'normalized_environment' => Config::get('config_paytm.PAYTM_ENVIRONMENT'),
            'endpoint'      => $initiateUrl,
            'website'       => $website,
            'order_id'      => $orderId,
            'txn_amount'    => $amount,
            'result_status' => $resultStatus,
            'result_code'   => $resultCode,
            'result_msg'    => $resultMsg,
            'token_present' => !empty($txnToken),
            'http_error'    => $response['_error'] ?? null,
            'http_code'     => $response['_http']  ?? null,
        ]);

        if ($resultStatus !== 'S' || empty($txnToken)) {
            Toastr::error('Paytm could not start the payment: ' . ($resultMsg ?: 'unknown error'));
            return back();
        }

        session()->put('paytm_order_id', $orderId);
        session()->put('paytm_txn_amount', $amount);

        $formAction = $showPayUrl . '?mid=' . urlencode($mid) . '&orderId=' . urlencode($orderId);

        return view('paytm-payment-view', [
            'formAction' => $formAction,
            'mid'        => $mid,
            'orderId'    => $orderId,
            'txnToken'   => $txnToken,
        ]);
    }

    public function callback(Request $request)
    {
        $paramList   = $_POST;
        $merchantKey = (string) Config::get('config_paytm.PAYTM_MERCHANT_KEY');
        $checksum    = $paramList['CHECKSUMHASH'] ?? '';

        $diagnostic = [
            'response_order_id'   => $paramList['ORDERID']   ?? null,
            'response_status'     => $paramList['STATUS']    ?? null,
            'response_respcode'   => $paramList['RESPCODE']  ?? null,
            'response_respmsg'    => $paramList['RESPMSG']   ?? null,
            'response_txn_amount' => $paramList['TXNAMOUNT'] ?? null,
            'response_txn_id'     => $paramList['TXNID']     ?? null,
            'response_bank_txn'   => $paramList['BANKTXNID'] ?? null,
            'session_order_id'    => session('paytm_order_id'),
            'session_txn_amount'  => session('paytm_txn_amount'),
        ];

        $isValid = $checksum !== ''
            && PaytmChecksum::verifySignature($paramList, $merchantKey, $checksum);
        $diagnostic['checksum_valid'] = $isValid;

        if (!$isValid) {
            Log::warning('[Paytm] callback rejected: invalid checksum', $diagnostic);
            return $this->paymentFailureResponse();
        }

        if (!empty($diagnostic['session_order_id'])
            && !empty($diagnostic['response_order_id'])
            && $diagnostic['session_order_id'] !== $diagnostic['response_order_id']) {
            Log::warning('[Paytm] callback rejected: ORDERID does not match session', $diagnostic);
            return $this->paymentFailureResponse();
        }

        $status = $this->verifyTxnStatusV3((string) ($paramList['ORDERID'] ?? ''), $merchantKey);
        $diagnostic['status_result_status'] = $status['body']['resultInfo']['resultStatus'] ?? null;
        $diagnostic['status_result_code']   = $status['body']['resultInfo']['resultCode']   ?? null;
        $diagnostic['status_result_msg']    = $status['body']['resultInfo']['resultMsg']    ?? null;
        $diagnostic['status_order_id']      = $status['body']['orderId']                    ?? null;
        $diagnostic['status_txn_amount']    = $status['body']['txnAmount']                  ?? null;

        $serverConfirmed = is_array($status)
            && (($status['body']['resultInfo']['resultStatus'] ?? '') === 'TXN_SUCCESS')
            && (($status['body']['orderId'] ?? '') === ($paramList['ORDERID'] ?? ''));

        if (!$serverConfirmed) {
            Log::warning('[Paytm] callback rejected: v3/order/status did not confirm success', $diagnostic);
            return $this->paymentFailureResponse();
        }

        Log::info('[Paytm] callback accepted', $diagnostic);

        $uniqueId   = OrderManager::gen_unique_id();
        $paytmTxnId = $paramList['TXNID'] ?? ($paramList['ORDERID'] ?? $uniqueId);
        foreach (CartManager::get_cart_group_ids() as $groupId) {
            OrderManager::generate_order([
                'payment_method'  => 'paytm',
                'order_status'    => 'confirmed',
                'payment_status'  => 'paid',
                'transaction_ref' => 'paytm_' . $paytmTxnId,
                'order_group_id'  => $uniqueId,
                'cart_group_id'   => $groupId,
            ]);
        }

        session()->forget(['paytm_order_id', 'paytm_txn_amount']);
        CartManager::cart_clean();

        if (session()->has('payment_mode') && session('payment_mode') === 'app') {
            return redirect()->route('payment-success');
        }
        return view('web-views.checkout-complete');
    }

    private function paymentFailureResponse()
    {
        if (session()->has('payment_mode') && session('payment_mode') === 'app') {
            return redirect()->route('payment-fail');
        }
        Toastr::error('Payment process failed!');
        return back();
    }

    private function verifyTxnStatusV3(string $orderId, string $merchantKey): ?array
    {
        $url = (string) Config::get('config_paytm.PAYTM_STATUS_V3_URL');
        $mid = (string) Config::get('config_paytm.PAYTM_MERCHANT_MID');
        if ($orderId === '' || $url === '' || $mid === '' || $merchantKey === '') {
            return null;
        }
        $body      = ['mid' => $mid, 'orderId' => $orderId];
        $signature = PaytmChecksum::generateSignature(json_encode($body), $merchantKey);
        $payload   = ['body' => $body, 'head' => ['signature' => $signature]];
        return $this->postJson($url, $payload);
    }

    private function postJson(string $url, array $payload): array
    {
        $json = json_encode($payload);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
            ],
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['_error' => $err, '_http' => $code];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['_error' => 'invalid_json', '_http' => $code, '_raw' => substr((string) $raw, 0, 500)];
        }
        $decoded['_http'] = $code;
        return $decoded;
    }
}
