<?php

namespace App\Message\Ecwid\Webhook\Order;

use App\Message\Ecwid\Webhook\Order\Data\UpdatedOrderEcwidWebhookData;
use App\Util\Ecwid\Order\FulfillmentStatusEnum;
use App\Util\Ecwid\Order\PaymentStatusEnum;

class UpdatedOrderEcwidWebhook extends AbstractOrderEcwidWebhook
{
    private UpdatedOrderEcwidWebhookData $data;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->data = new UpdatedOrderEcwidWebhookData($data['data']);
    }

    /**
     * @return UpdatedOrderEcwidWebhookData
     */
    public function getData(): UpdatedOrderEcwidWebhookData
    {
        return $this->data;
    }

    public function hasChanges(): bool
    {
        return $this->isFulfillmentChanged() || $this->isPaymentChanged();
    }

    public function isFulfillmentChanged(): bool
    {
        return !$this->getData()->getOldFulfillmentStatus()->equals($this->getData()->getNewFulfillmentStatus());
    }

    public function isPaymentChanged(): bool
    {
        return !$this->getData()->getOldPaymentStatus()->equals($this->getData()->getNewPaymentStatus());
    }

    public function isAwaitingPayment(): bool
    {
        return $this->getData()->getNewPaymentStatus()->equals(PaymentStatusEnum::AWAITING_PAYMENT());
    }

    public function isPaid(): bool
    {
        return $this->getData()->getNewPaymentStatus()->equals(PaymentStatusEnum::PAID());
    }

    public function isProcessing(): bool
    {
        return $this->getData()->getNewFulfillmentStatus()->equals(FulfillmentStatusEnum::PROCESSING());
    }
}