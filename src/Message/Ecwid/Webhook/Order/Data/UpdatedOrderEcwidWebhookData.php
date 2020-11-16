<?php

namespace App\Message\Ecwid\Webhook\Order\Data;

use App\Message\Ecwid\Webhook\EcwidWebhookDataNewStatusesTrait;
use App\Message\Ecwid\Webhook\EcwidWebhookDataOldStatusesTrait;

class UpdatedOrderEcwidWebhookData extends AbstractOrderEcwidWebhookData
{
    use EcwidWebhookDataNewStatusesTrait;
    use EcwidWebhookDataOldStatusesTrait;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->newPaymentStatus = $data['newPaymentStatus'];
        $this->newFulfillmentStatus = $data['newFulfillmentStatus'];
        $this->oldFulfillmentStatus = $data['oldFulfillmentStatus'];
        $this->oldPaymentStatus = $data['oldPaymentStatus'];
    }
}