<?php

namespace App\Message\Ecwid\Webhook\Order;

use App\Message\Ecwid\Webhook\Order\Data\CreatedOrderEcwidWebhookData;

class CreatedOrderEcwidWebhook extends AbstractOrderEcwidWebhook
{
    private CreatedOrderEcwidWebhookData $data;

    protected function __construct($data)
    {
        parent::__construct($data);
        $this->data = new CreatedOrderEcwidWebhookData($data['data']);
    }

    public function getData(): CreatedOrderEcwidWebhookData
    {
        return $this->data;
    }
}