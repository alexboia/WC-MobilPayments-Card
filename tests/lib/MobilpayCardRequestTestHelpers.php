<?php

use Automattic\Jetpack\Constants;

/**
 * Copyright (c) 2019-2021 Alexandru Boia
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 *	1. Redistributions of source code must retain the above copyright notice, 
 *		this list of conditions and the following disclaimer.
 *
 * 	2. Redistributions in binary form must reproduce the above copyright notice, 
 *		this list of conditions and the following disclaimer in the documentation 
 *		and/or other materials provided with the distribution.
 *
 *	3. Neither the name of the copyright holder nor the names of its contributors 
 *		may be used to endorse or promote products derived from this software without 
 *		specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, 
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY 
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, 
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 */

trait MobilpayCardRequestTestHelpers {
    use GenericTestHelpers;

    /**
     * @return \Mobilpay_Payment_Request_Card 
     */
    protected function _generateCardPaymentRequestFromOrder(\WC_Order $order) {
        $faker = $this->_getFaker();

        $paymentRequest = new \Mobilpay_Payment_Request_Card();
        $paymentRequest->signature = $this->_generateMobilpayAccountId();
        $paymentRequest->orderId = $faker->uuid;
        
        $paymentRequest->confirmUrl = $faker->url;
        $paymentRequest->returnUrl = $faker->url;

        $paymentRequest->invoice = new \Mobilpay_Payment_Invoice();
        $paymentRequest->invoice->currency = $order->get_currency();
        $paymentRequest->invoice->amount = sprintf('%.2f', $order->get_total());
        $paymentRequest->invoice->details = $faker->text();

        $paymentRequest->params = array(
            '_lvdwcmc_order_id' => $order->get_id(),
            '_lvdwcmc_customer_id' => $order->get_customer_id(),
            '_lvdwcmc_customer_ip' => $order->get_customer_ip_address()
        );
        
        return $paymentRequest;
    }

    /**
     * @return \Mobilpay_Payment_Request_Card 
     */
    protected function _generateFullPaymentCompletedCardPaymentRequestFromOrder(\WC_Order $order) {
        $request = $this->_generateCardPaymentRequestFromOrder($order);

        return $this->_generateAndAddPaymentRequestResponseNotification($request, 
            MobilpayConstants::MOBILPAY_ACTION_CONFIRMED, 
            $request->invoice->amount);
    }

    /**
     * @return \Mobilpay_Payment_Request_Card 
     */
    protected function _generatePartialPaymentCompletedCardPaymentRequestFromOrder(\WC_Order $order) {
        $request = $this->_generateCardPaymentRequestFromOrder($order);
        $processedAmount = $this->_generatePartialProcessAmount($request);

        return $this->_generateAndAddPaymentRequestResponseNotification($request, 
            MobilpayConstants::MOBILPAY_ACTION_CONFIRMED, 
            $processedAmount);
    }

    /**
     * @return \Mobilpay_Payment_Request_Card[]
     */
    protected function _generatePartialPaymentSplitRequestsFromOrder(\WC_Order $order) {
        $requests = array();
        $amounts = $this->_generateWcOrderAmountSplit($order);
        $amountsCount = count($amounts);

        for ($i = 0; $i < $amountsCount; $i ++) {
            $request = $this->_generateCardPaymentRequestFromOrder($order);
            $request = $this->_generateAndAddPaymentRequestResponseNotification($request, 
                MobilpayConstants::MOBILPAY_ACTION_CONFIRMED, 
                $amounts[$i]);

            $requests[] = $request;
        }

        return $requests;
    }

    /**
     * @return \Mobilpay_Payment_Request_Card
     */
    protected function _generateFullPaymentCreditedCardPaymentRequestFromOrder(\WC_Order $order) {
        $request = $this->_generateCardPaymentRequestFromOrder($order);

        return $this->_generateAndAddPaymentRequestResponseNotification($request, 
            MobilpayConstants::MOBILPAY_ACTION_CREDITED, 
            $request->invoice->amount);
    }

    /**
     * @return \Mobilpay_Payment_Request_Card[]
     */
    protected function _generatePartialCreditSplitRequestsFromOrder(\WC_Order $order) {
        $requests = array();
        $amounts = $this->_generateWcOrderAmountSplit($order);
        $amountsCount = count($amounts);

        for ($i = 0; $i < $amountsCount; $i ++) {
            $request = $this->_generateCardPaymentRequestFromOrder($order);
            $request = $this->_generateAndAddPaymentRequestResponseNotification($request, 
                MobilpayConstants::MOBILPAY_ACTION_CREDITED, 
                $amounts[$i]);

            $requests[] = $request;
        }

        return $requests;
    }

    /**
     * @return \Mobilpay_Payment_Request_Card
     */
    private function _generateAndAddPaymentRequestResponseNotification(\Mobilpay_Payment_Request_Card $request, $action, $processedAmount) {
        $faker = $this->_getFaker();

        $request->objPmNotify = new \Mobilpay_Payment_Request_Notify();
        $request->objPmNotify->action = $action;
        $request->objPmNotify->originalAmount = $request->invoice->amount;
        $request->objPmNotify->processedAmount = $processedAmount;
        $request->objPmNotify->pan_masked = $faker->creditCardNumber;
        $request->objPmNotify->errorCode = 0;
        $request->objPmNotify->errorMessage = '';
        $request->objPmNotify->purchaseId = $faker->uuid;

        return $request;
    }

    private function _generateWcOrderAmountSplit(\WC_Order $order) {
        return $this->_getFaker()->generateFractionsOfAmount($order->get_total(), 
            wc_get_price_decimals());
    }

    private function _generatePartialProcessAmount(\Mobilpay_Payment_Request_Card $request) {
        $minProcessAmount = 0.01;
        $maxProcessAmount = round(0.90 * $request->invoice->amount, 2, 
            PHP_ROUND_HALF_DOWN);

        $faker = $this->_getFaker();
        return $faker->randomFloat(2, $minProcessAmount, $maxProcessAmount);
    }

    private function _generateMobilpayAccountId() {
        return $this->_getFaker()->uuid;
    }
}