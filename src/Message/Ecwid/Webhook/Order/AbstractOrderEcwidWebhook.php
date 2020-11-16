<?php

namespace App\Message\Ecwid\Webhook\Order;

use App\Message\Ecwid\Webhook\AbstractEcwidWebhook;
use App\Util\Ecwid\Enum\EcwidOrderEventsEnum;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

abstract class AbstractOrderEcwidWebhook extends AbstractEcwidWebhook
{
    public static function create(array $data): self
    {
        Assert::true(EcwidOrderEventsEnum::isValid($data['eventType']));

        switch ($data['eventType']) {
            case EcwidOrderEventsEnum::EVENT_ORDER_CREATED():
                return new CreatedOrderEcwidWebhook($data);
            case EcwidOrderEventsEnum::EVENT_ORDER_UPDATED():
                return new UpdatedOrderEcwidWebhook($data);
        }

        throw new InvalidArgumentException('Unknown order type');
    }

    public function getEventType(): EcwidOrderEventsEnum
    {
        return new EcwidOrderEventsEnum($this->eventType);
    }
}