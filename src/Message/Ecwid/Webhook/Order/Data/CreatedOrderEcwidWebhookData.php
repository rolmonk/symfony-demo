<?php

namespace App\Message\Ecwid\Webhook\Order\Data;

use App\Message\Ecwid\Webhook\EcwidWebhookDataNewStatusesTrait;

class CreatedOrderEcwidWebhookData extends AbstractOrderEcwidWebhookData
{
    use EcwidWebhookDataNewStatusesTrait;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->newPaymentStatus = $data['newPaymentStatus'];
        $this->newFulfillmentStatus = $data['newFulfillmentStatus'];
    }
}