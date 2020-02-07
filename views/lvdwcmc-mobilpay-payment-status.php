<?php
/**
 * Copyright (c) 2019-2020 Alexandru Boia
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
    defined('LVD_WCMC_LOADED') or die;
?>

<div class="lvdwcmc-mobilpay-return-container order-status-<?php echo $data->orderStatus; ?>">
    <?php if (in_array($data->orderStatus, array('cancelled', 'failed'))): ?>
        <p><?php echo __('Your payment could not be processed or has been cancelled.', LVD_WCMC_TEXT_DOMAIN); ?></p>
    <?php elseif ($data->orderStatus === 'on-hold'):  ?>
        <p><?php echo __('Your payment is currently being processed.', LVD_WCMC_TEXT_DOMAIN); ?></p>
        <p><?php echo __('Order Id', LVD_WCMC_TEXT_DOMAIN) . ': ' . $data->orderId; ?></p>
        <p><?php echo __('Detailed order status') ?>: <strong><?php echo wc_get_order_status_name($data->orderStatus); ?></strong></p>
    <?php else: ?>
        <p><?php echo __('We have successfully received your payment', LVD_WCMC_TEXT_DOMAIN); ?></p>
        <p><?php echo __('Order Id', LVD_WCMC_TEXT_DOMAIN) . ': ' . $data->orderId; ?></p>
        <p><?php echo __('Detailed order status') ?>: <strong><?php echo wc_get_order_status_name($data->orderStatus); ?></strong></p>
    <?php endif; ?>
</div>