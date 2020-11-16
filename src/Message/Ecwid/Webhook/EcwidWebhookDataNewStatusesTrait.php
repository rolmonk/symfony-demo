<?php

namespace App\Message\Ecwid\Webhook;

use App\Util\Ecwid\Order\FulfillmentStatusEnum;
use App\Util\Ecwid\Order\PaymentStatusEnum;

trait EcwidWebhookDataNewStatusesTrait
{
    private string $newPaymentStatus;
    private string $newFulfillmentStatus;

    public function getNewFulfillmentStatus(): FulfillmentStatusEnum
    {
        return new FulfillmentStatusEnum($this->newFulfillmentStatus);
    }

    public function getNewPaymentStatus(): PaymentStatusEnum
    {
        return new PaymentStatusEnum($this->newPaymentStatus);
    }
}