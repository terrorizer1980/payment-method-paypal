<?php

namespace Hanoivip\PaymentMethodPaypal;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Exception;

class PaypalController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function callback(Request $request)
    {
        Log::debug("Paypal callback dump:" . print_r($request->all(), true));
        $payment_id = Session::get('paypal_payment_id');
        Log::debug('Paypal payment id' . $payment_id);
        //Session::forget('paypal_payment_id');
        if (!$request->has('PayerID') && !$request->has('token')) {
            //Session::put('paypal_error', 'Payment failed');
            return view('hanoivip::payment-paypal-failure', ['error' => __('hanoivip::payment.paypal.invalid-callback')]);
        }
        $payerId = $request->input('PayerID');
        $token = $request->input('token');
        //$cfg = Session::get('paypal_config');
        //Log::debug(print_r($cfg, true));
        //$apiContext = new ApiContext(new OAuthTokenCredential($cfg['client_id'], $cfg['secret']));
        //$apiContext->setConfig($cfg['settings']);
        $apiContext = Session::get('paypal_api_context');
        $payment = Payment::get($payment_id, $apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);
        /**Execute the payment **/
        $paymentResult = $payment->execute($execution, $apiContext);
        // todo: need to save paypal transaction
        $this->savePaymentResult($payment_id, $payerId, $paymentResult);
        if ($paymentResult->getState() == 'approved') {
            return view('hanoivip::payment-paypal-success');
        }
        //Session::put('paypal_error', 'Payment failed. Please try again.');
        return view('hanoivip::payment-paypal-failure', ['error' => __('hanoivip::payment.paypal.failure')]);
    }
    
    /**
     * 
     * @param string $paymentId
     * @param string $payerId
     * @param Payment $paymentResult
     */
    private function savePaymentResult($paymentId, $payerId, $paymentResult)
    {
        try 
        {
            $log = PaypalTransaction::where('payment_id', $paymentId)->first();
            $log->payer_id = $payerId;
            $log->state = $paymentResult->getState();
            $transactions = $paymentResult->getTransactions();
            if (empty($transactions))
            {
                $log->save();
                return false;
            }
            $total = 0;
            $currency = '';
            foreach ($transactions as $tran)
            {
                $total += $tran->getAmount()->getTotal();
                $currency = $tran->getAmount()->getCurrency();
                //$invoice = $tran->getCustom();
            }
            $log->amount = $total;
            $log->currency = $currency;
            $log->save();
            return true;
        } 
        catch (Exception $ex) 
        {
            Log::error("PaypalController " . $ex->getMessage());
        }
        return false;
    }
}