<?php

namespace App\Providers;

use App\CPU\Helpers;
use App\Model\BusinessSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PaymentConfigProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        
        try {
            $data = BusinessSetting::where(['type' => 'paypal'])->first();
            $paypal = json_decode($data['value'], true);
            if ($paypal) {
                $mode = $paypal['environment']??'sandbox';
                if ($mode == 'live') {
                    $paypal_mode = "live";
                } else {
                    $paypal_mode = "sandbox";
                }

                $config = array(
                    'client_id' => $paypal['paypal_client_id'], // values : (local | production)
                    'secret' => $paypal['paypal_secret'],
                    'settings' => array(
                        'mode' => env('PAYPAL_MODE', $paypal_mode), //live||sandbox
                        'http.ConnectionTimeOut' => 30,
                        'log.LogEnabled' => true,
                        'log.FileName' => storage_path() . '/logs/paypal.log',
                        'log.LogLevel' => 'ERROR'
                    ),
                );
                Config::set('paypal', $config);
            }

            /*$data = BusinessSetting::where(['type' => 'ssl_commerz_payment'])->first();
            $ssl = json_decode($data['value'], true);
            if ($ssl) {
                $mode = $ssl['environment']??'sandbox';
                if ($mode == 'live') {
                    $url = "https://securepay.sslcommerz.com";
                    $host = false;
                } else {
                    $url = "https://sandbox.sslcommerz.com";
                    $host = true;
                }
                $config = array(
                    'projectPath' => env('PROJECT_PATH'),
                    'apiDomain' => env("API_DOMAIN_URL", $url),
                    'apiCredentials' => [
                        'store_id' => $ssl['store_id'],
                        'store_password' => $ssl['store_password'],
                    ],
                    'apiUrl' => [
                        'make_payment' => "/gwprocess/v4/api.php",
                        'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
                        'order_validate' => "/validator/api/validationserverAPI.php",
                        'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
                        'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
                    ],
                    'connect_from_localhost' => env("IS_LOCALHOST", $host), // For Sandbox, use "true", For Live, use "false"
                    'success_url' => '/success',
                    'failed_url' => '/fail',
                    'cancel_url' => '/cancel',
                    'ipn_url' => '/ipn',
                );
                Config::set('sslcommerz', $config);
            }*/

            $data = BusinessSetting::where(['type' => 'razor_pay'])->first();
            $razor = json_decode($data['value'], true);
            if ($razor) {
                $config = array(
                    'razor_key' => env('RAZOR_KEY', $razor['razor_key']),
                    'razor_secret' => env('RAZOR_SECRET', $razor['razor_secret'])
                );
                Config::set('razor', $config);
            }

            $data = BusinessSetting::where(['type' => 'paystack'])->first();
            $paystack = json_decode($data['value'], true);
            if ($paystack) {
                $config = array(
                    'publicKey' => env('PAYSTACK_PUBLIC_KEY', $paystack['publicKey']),
                    'secretKey' => env('PAYSTACK_SECRET_KEY', $paystack['secretKey']),
                    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', $paystack['paymentUrl']),
                    'merchantEmail' => env('MERCHANT_EMAIL', $paystack['merchantEmail']),
                );
                Config::set('paystack', $config);
            }

            $data = BusinessSetting::where(['type' => 'flutterwave'])->first();
            $flutterwave = json_decode($data['value'], true);
            if ($flutterwave) {
                $config = array(
                    'publicKey' => env('FLW_PUBLIC_KEY', $flutterwave['public_key']), // values : (local | production)
                    'secretKey' => env('FLW_SECRET_KEY', $flutterwave['secret_key']),
                    'secretHash' => env('FLW_SECRET_HASH', $flutterwave['hash']),
                );
                Config::set('flutterwave', $config);
            }


            //paytm
            $paytm = Helpers::get_business_settings('paytm');
            if (isset($paytm)) {

                // Normalize admin's raw env string ("sandbox"/"live") into a canonical
                // STAGE/PROD pair so every downstream consumer agrees on a single naming.
                $rawEnv = $paytm['environment'] ?? 'sandbox';
                $isLive = (strtolower($rawEnv) === 'live' || strtolower($rawEnv) === 'prod' || strtolower($rawEnv) === 'production');
                $normalizedEnv = $isLive ? 'PROD' : 'STAGE';

                if ($isLive) {
                    $PAYTM_STATUS_QUERY_NEW_URL = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
                    $PAYTM_TXN_URL             = 'https://securegw.paytm.in/theia/processTransaction';
                } else {
                    $PAYTM_STATUS_QUERY_NEW_URL = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
                    $PAYTM_TXN_URL             = 'https://securegw-stage.paytm.in/theia/processTransaction';
                }

                // Paytm sandbox (securegw-stage) rejects live WEBSITE values and surfaces it
                // as a "503 No server available" page. Force a staging WEBSITE in sandbox mode.
                // In live mode, fall back to "DEFAULT" (Paytm's standard prod website) if admin left it blank.
                $paytmWebsite = env('PAYTM_MERCHANT_WEBSITE', $paytm['paytm_merchant_website'] ?? '');
                if (!$isLive && stripos($paytmWebsite, 'STAG') === false) {
                    $paytmWebsite = 'WEBSTAGING';
                } elseif ($isLive && trim($paytmWebsite) === '') {
                    $paytmWebsite = 'DEFAULT';
                }

                // Sandbox endpoint with live MID/key fails handshake and Paytm returns
                // "503 No server available". Warn when MID looks like a live credential
                // (Paytm sandbox MIDs typically contain "test"/"stag"/"sandbox").
                $paytmMid = env('PAYTM_MERCHANT_MID', $paytm['paytm_merchant_mid'] ?? '');
                if (!$isLive && $paytmMid !== '' &&
                    stripos($paytmMid, 'test') === false &&
                    stripos($paytmMid, 'stag') === false &&
                    stripos($paytmMid, 'sandbox') === false) {
                    Log::warning('[Paytm] Sandbox environment selected but MID does not look like a test credential ('.substr($paytmMid, 0, 6).'***). Verify that sandbox MID and key from the Paytm dashboard are used.');
                }

                $config = array(
                    'PAYTM_ENVIRONMENT' => $normalizedEnv,
                    'PAYTM_MERCHANT_KEY' => env('PAYTM_MERCHANT_KEY', $paytm['paytm_merchant_key']),
                    'PAYTM_MERCHANT_MID' => env('PAYTM_MERCHANT_MID', $paytm['paytm_merchant_mid']),
                    'PAYTM_MERCHANT_WEBSITE' => $paytmWebsite,
                    'PAYTM_REFUND_URL' => env('PAYTM_REFUND_URL', $paytm['paytm_refund_url']),
                    'PAYTM_STATUS_QUERY_URL' => env('PAYTM_STATUS_QUERY_URL', $PAYTM_STATUS_QUERY_NEW_URL),
                    'PAYTM_STATUS_QUERY_NEW_URL' => env('PAYTM_STATUS_QUERY_NEW_URL', $PAYTM_STATUS_QUERY_NEW_URL),
                    'PAYTM_TXN_URL' => env('PAYTM_TXN_URL', $PAYTM_TXN_URL),
                );

                Config::set('config_paytm', $config);
            } else {
                Log::warning('[Paytm] business_settings row "paytm" missing — config_paytm will be empty and the form action will be blank.');
            }

        } catch (\Exception $ex) {
            Log::error('[Paytm] PaymentConfigProvider boot exception: '.$ex->getMessage());
        }
    }
}
