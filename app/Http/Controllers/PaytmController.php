<?php

namespace App\Http\Controllers;

use App\CPU\CartManager;
use App\CPU\Helpers;
use App\CPU\OrderManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaytmController extends Controller
{
    function encrypt_e($input, $ky)
    {
        $key = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_encrypt($input, "AES-128-CBC", $key, 0, $iv);
        return $data;
    }

    function decrypt_e($crypt, $ky)
    {
        $key = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_decrypt($crypt, "AES-128-CBC", $key, 0, $iv);
        return $data;
    }

    function generateSalt_e($length)
    {
        $random = "";
        srand((double)microtime() * 1000000);

        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";

        for ($i = 0; $i < $length; $i++) {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }

        return $random;
    }

    function checkString_e($value)
    {
        if ($value == 'null')
            $value = '';
        return $value;
    }

    function getChecksumFromArray($arrayList, $key, $sort = 1)
    {
        if ($sort != 0) {
            ksort($arrayList);
        }
        $str = $this->getArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    function getChecksumFromString($str, $key)
    {

        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    function verifychecksum_e($arrayList, $key, $checksumvalue)
    {
        $arrayList = $this->removeCheckSumParam($arrayList);
        ksort($arrayList);
        $str = $this->getArray2StrForVerify($arrayList);
        $paytm_hash = $this->decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);

        $finalString = $str . "|" . $salt;

        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;

        $validFlag = "FALSE";
        if ($website_hash == $paytm_hash) {
            $validFlag = "TRUE";
        } else {
            $validFlag = "FALSE";
        }
        return $validFlag;
    }

    function verifychecksum_eFromStr($str, $key, $checksumvalue)
    {
        $paytm_hash = $this->decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);

        $finalString = $str . "|" . $salt;

        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;

        $validFlag = "FALSE";
        if ($website_hash == $paytm_hash) {
            $validFlag = "TRUE";
        } else {
            $validFlag = "FALSE";
        }
        return $validFlag;
    }

    function getArray2Str($arrayList)
    {
        $findme = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos !== false || $pospipe !== false) {
                continue;
            }

            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    function getArray2StrForVerify($arrayList)
    {
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    function redirect2PG($paramList, $key)
    {
        $hashString = $this->getchecksumFromArray($paramList);
        $checksum = $this->encrypt_e($hashString, $key);
    }

    function removeCheckSumParam($arrayList)
    {
        if (isset($arrayList["CHECKSUMHASH"])) {
            unset($arrayList["CHECKSUMHASH"]);
        }
        return $arrayList;
    }

    function getTxnStatus($requestParamList)
    {
        return $this->callAPI("PAYTM_STATUS_QUERY_URL", $requestParamList);
    }

    function getTxnStatusNew($requestParamList)
    {
        return $this->callNewAPI("PAYTM_STATUS_QUERY_NEW_URL", $requestParamList);
    }

    function initiateTxnRefund($requestParamList)
    {
        $CHECKSUM = $this->getRefundChecksumFromArray($requestParamList, "PAYTM_MERCHANT_KEY", 0);
        $requestParamList["CHECKSUM"] = $CHECKSUM;
        return $this->callAPI("PAYTM_REFUND_URL", $requestParamList);
    }

    function callAPI($apiURL, $requestParamList)
    {
        $jsonResponse = "";
        $responseParamList = array();
        $JsonData = json_encode($requestParamList);
        $postData = 'JsonData=' . urlencode($JsonData);
        $ch = curl_init($apiURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData))
        );
        $jsonResponse = curl_exec($ch);
        $responseParamList = json_decode($jsonResponse, true);
        return $responseParamList;
    }

    function callNewAPI($apiURL, $requestParamList)
    {
        $jsonResponse = "";
        $responseParamList = array();
        $JsonData = json_encode($requestParamList);
        $postData = 'JsonData=' . urlencode($JsonData);
        $ch = curl_init($apiURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData))
        );
        $jsonResponse = curl_exec($ch);
        $responseParamList = json_decode($jsonResponse, true);
        return $responseParamList;
    }

    function getRefundChecksumFromArray($arrayList, $key, $sort = 1)
    {
        if ($sort != 0) {
            ksort($arrayList);
        }
        $str = $this->getRefundArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    function getRefundArray2Str($arrayList)
    {
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            $pospipe = strpos($value, $findmepipe);
            if ($pospipe !== false) {
                continue;
            }

            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    function callRefundAPI($refundApiURL, $requestParamList)
    {
        $jsonResponse = "";
        $responseParamList = array();
        $JsonData = json_encode($requestParamList);
        $postData = 'JsonData=' . urlencode($JsonData);
        $ch = curl_init($refundApiURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $refundApiURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $jsonResponse = curl_exec($ch);
        $responseParamList = json_decode($jsonResponse, true);
        return $responseParamList;
    }

    //payment functions
    public function payment(Request $request)
    {
        // Paytm rejects duplicate ORDER_IDs for the same MID. Previously this used
        // the latest existing Order.id, which repeated across retries and caused
        // intermittent "Page not found" / silent failures. Generate a fresh ID per attempt.
        $ORDER_ID = 'INO' . time() . strtoupper(Str::random(4));
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $value = CartManager::cart_grand_total() - $discount;
        $user = Helpers::get_customer();

        $paramList = array();
        $CUST_ID = $user['id'];
        // Paytm legacy flow expects these every request. Default to the standard
        // web/Retail pair when the upstream link omits them, instead of sending nulls.
        $INDUSTRY_TYPE_ID = $request["INDUSTRY_TYPE_ID"] ?? 'Retail';
        $CHANNEL_ID       = $request["CHANNEL_ID"]       ?? 'WEB';
        $TXN_AMOUNT = round($value, 2);

        // Create an array having all required parameters for creating checksum.
        $paramList["MID"] = Config::get('config_paytm.PAYTM_MERCHANT_MID');
        $paramList["ORDER_ID"] = $ORDER_ID;
        $paramList["CUST_ID"] = $CUST_ID;
        $paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
        $paramList["CHANNEL_ID"] = $CHANNEL_ID;
        $paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
        $paramList["WEBSITE"] = Config::get('config_paytm.PAYTM_MERCHANT_WEBSITE');

        $paramList["CALLBACK_URL"] = route('paytm-response');
        $paramList["MSISDN"] = $user['phone']; //Mobile number of customer
        $paramList["EMAIL"] = $user['email']; //Email ID of customer
        $paramList["VERIFIED_BY"] = "EMAIL"; //
        $paramList["IS_USER_VERIFIED"] = "YES"; //

        //Here checksum string will return by getChecksumFromArray() function.
        $checkSum = $this->getChecksumFromArray($paramList, Config::get('config_paytm.PAYTM_MERCHANT_KEY'));

        // Debug-level so it's silent in prod unless LOG_LEVEL=debug. PII (email, phone,
        // customer id) is intentionally NOT logged.
        $gatewayUrl = Config::get('config_paytm.PAYTM_TXN_URL');
        $merchantKey = Config::get('config_paytm.PAYTM_MERCHANT_KEY');
        Log::debug('[Paytm] payment() preparing redirect form', [
            'normalized_environment' => Config::get('config_paytm.PAYTM_ENVIRONMENT'),
            'gateway_url' => $gatewayUrl,
            'gateway_url_blank' => empty($gatewayUrl),
            'mid_present' => !empty($paramList['MID']),
            'website' => $paramList['WEBSITE'] ?? null,
            'channel_id' => $paramList['CHANNEL_ID'] ?? null,
            'industry_type_id' => $paramList['INDUSTRY_TYPE_ID'] ?? null,
            'order_id' => $paramList['ORDER_ID'] ?? null,
            'txn_amount' => $paramList['TXN_AMOUNT'] ?? null,
            'merchant_key_present' => !empty($merchantKey),
            'checksum_generated' => !empty($checkSum),
            'param_fields' => array_keys($paramList),
        ]);

        // Stash for the callback so we can cross-check what Paytm reports
        // against what we actually requested.
        session()->put('paytm_order_id', $ORDER_ID);
        session()->put('paytm_txn_amount', $TXN_AMOUNT);

        return view('paytm-payment-view', compact('checkSum', 'paramList'));
    }

    public function callback(Request $request)
    {
        $paramList = $_POST;
        $paytmChecksum = $_POST["CHECKSUMHASH"] ?? "";
        $merchantKey = Config::get('config_paytm.PAYTM_MERCHANT_KEY');

        $isValidChecksum = $this->verifychecksum_e($paramList, $merchantKey, $paytmChecksum);

        // Diagnostic fields. No key, no PII beyond what Paytm itself echoes back.
        $diagnostic = [
            'response_order_id' => $paramList['ORDERID'] ?? null,
            'response_status' => $paramList['STATUS'] ?? null,
            'response_respcode' => $paramList['RESPCODE'] ?? null,
            'response_respmsg' => $paramList['RESPMSG'] ?? null,
            'response_txn_amount' => $paramList['TXNAMOUNT'] ?? null,
            'response_bank_txn_id' => $paramList['BANKTXNID'] ?? null,
            'response_txn_id' => $paramList['TXNID'] ?? null,
            'checksum_valid' => $isValidChecksum,
            'session_order_id' => session('paytm_order_id'),
            'session_txn_amount' => session('paytm_txn_amount'),
        ];

        if ($isValidChecksum !== "TRUE") {
            Log::warning('[Paytm] callback rejected: invalid checksum', $diagnostic);
            return $this->paymentFailureResponse();
        }

        // ORDER_ID echoed by Paytm must match what we sent in this session.
        // Prevents replay / cross-session confusion.
        if (!empty($diagnostic['session_order_id'])
            && !empty($diagnostic['response_order_id'])
            && $diagnostic['session_order_id'] !== $diagnostic['response_order_id']) {
            Log::warning('[Paytm] callback rejected: ORDERID does not match session', $diagnostic);
            return $this->paymentFailureResponse();
        }

        // Paytm best practice: don't trust the browser-redirected POST status.
        // Re-verify server-to-server via the Status Query API before granting the order.
        $statusResp = $this->verifyTxnStatus($paramList['ORDERID'] ?? '', $merchantKey);
        $diagnostic['status_api_status'] = $statusResp['STATUS'] ?? null;
        $diagnostic['status_api_respcode'] = $statusResp['RESPCODE'] ?? null;
        $diagnostic['status_api_respmsg'] = $statusResp['RESPMSG'] ?? null;
        $diagnostic['status_api_order_id'] = $statusResp['ORDERID'] ?? null;
        $diagnostic['status_api_txn_amount'] = $statusResp['TXNAMOUNT'] ?? null;

        $serverConfirmedSuccess = is_array($statusResp)
            && (($statusResp['STATUS'] ?? '') === 'TXN_SUCCESS')
            && (($statusResp['ORDERID'] ?? '') === ($paramList['ORDERID'] ?? ''));

        if (!$serverConfirmedSuccess) {
            Log::warning('[Paytm] callback rejected: Status API did not confirm success', $diagnostic);
            return $this->paymentFailureResponse();
        }

        Log::info('[Paytm] callback accepted', $diagnostic);

        $unique_id = OrderManager::gen_unique_id();
        $paytmTxnId = $paramList['TXNID'] ?? ($paramList['ORDERID'] ?? $unique_id);
        $order_ids = [];
        foreach (CartManager::get_cart_group_ids() as $group_id) {
            $data = [
                'payment_method' => 'paytm',
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'transaction_ref' => 'paytm_' . $paytmTxnId,
                'order_group_id' => $unique_id,
                'cart_group_id' => $group_id,
            ];
            $order_ids[] = OrderManager::generate_order($data);
        }

        session()->forget(['paytm_order_id', 'paytm_txn_amount']);
        CartManager::cart_clean();

        if (session()->has('payment_mode') && session('payment_mode') == 'app') {
            return redirect()->route('payment-success');
        }
        return view('web-views.checkout-complete');
    }

    private function paymentFailureResponse()
    {
        if (session()->has('payment_mode') && session('payment_mode') == 'app') {
            return redirect()->route('payment-fail');
        }
        Toastr::error('Payment process failed!');
        return back();
    }

    private function verifyTxnStatus(string $orderId, string $merchantKey): ?array
    {
        if ($orderId === '' || empty($merchantKey)) {
            return null;
        }
        $url = Config::get('config_paytm.PAYTM_STATUS_QUERY_NEW_URL');
        if (empty($url)) {
            return null;
        }
        $payload = [
            'MID' => Config::get('config_paytm.PAYTM_MERCHANT_MID'),
            'ORDERID' => $orderId,
        ];
        $payload['CHECKSUMHASH'] = $this->getChecksumFromArray($payload, $merchantKey);
        try {
            return $this->callNewAPI($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('[Paytm] Status Query API call failed: '.$e->getMessage(), [
                'order_id' => $orderId,
                'status_url' => $url,
            ]);
            return null;
        }
    }
}
