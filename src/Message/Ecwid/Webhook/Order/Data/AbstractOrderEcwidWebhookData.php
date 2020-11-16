<?php

namespace App\Message\Ecwid\Webhook\Order\Data;

abstract class AbstractOrderEcwidWebhookData
{
    protected string $orderId;

    protected function __construct(array $data)
    {
        $this->orderId = $data['orderId'];
    }

    /**
     * @return mixed|string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }
}
