<?php
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

class WcOrderNotesTester {
    /**
     * @var \WC_Order
     */
    private $_order;

    private $_initialInternalOrderNotesCount;

    private $_initialCustomerOrderNotesCount;

    public function __construct(\WC_Order $order) {   
        $this->_order = $order;
        $this->_initialInternalOrderNotesCount = $this->_countInternalOrderNotes();
        $this->_initialCustomerOrderNotesCount = $this->_countCustomerOrderNotes();
    }

    public function currentInternalOrderNotesCountDiffersBy($diff) {
        wp_cache_flush();
        return ($this->_countInternalOrderNotes() - $this->_initialInternalOrderNotesCount) 
            == $diff;
    }

    public function currentCustomerOrderNotesCountDiffersBy($diff) {
        wp_cache_flush();
        return ($this->_countCustomerOrderNotes() - $this->_initialCustomerOrderNotesCount)
            == $diff;
    }

    private function _countInternalOrderNotes() {
        $notes =  wc_get_order_notes(array(
            'order_id' => $this->_order->get_id(),
            'type' => 'internal'
        ));

        return !empty($notes) 
            ? count($notes) 
            : 0;
    }

    private function _countCustomerOrderNotes() {
        $notes =  wc_get_order_notes(array(
            'order_id' => $this->_order->get_id(),
            'type' => 'customer'
        ));

        return !empty($notes) 
            ? count($notes) 
            : 0;
    }
}