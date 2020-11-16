<?php

namespace App\Message\Ecwid\Webhook;

use App\Util\Ecwid\Order\FulfillmentStatusEnum;
use App\Util\Ecwid\Order\PaymentStatusEnum;

trait EcwidWebhookDataOldStatusesTrait
{
    private string $oldFulfillmentStatus;
    private string $oldPaymentStatus;

    public function getOldFulfillmentStatus(): FulfillmentStatusEnum
    {
        return new FulfillmentStatusEnum($this->oldFulfillmentStatus);
    }

    public function getOldPaymentStatus(): PaymentStatusEnum
    {
        return new PaymentStatusEnum($this->oldPaymentStatus);
    }
}