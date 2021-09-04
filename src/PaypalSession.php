<?php

namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\PaymentMethodContract\IPaymentSession;

class PaypalSession implements IPaymentSession
{
    private $trans;
    
    private $paymentId;
    
    private $checkoutUrl;
    
    public function __construct($trans, $paymentId, $checkoutUrl)
    {
        $this->trans = $trans;
        $this->paymentId = $paymentId;
        $this->checkoutUrl = $checkoutUrl;
    }
    
    public function getSecureData()
    {
        return ['paymentId' => $this->paymentId];
    }

    public function getGuide()
    {
        return __('hanoivip::payment.paypal.guide');
    }

    public function getTransId()
    {
        return $this->trans->trans_id;
    }

    public function getData()
    {
        return ['checkoutUrl' => $this->checkoutUrl];
    }

    
}