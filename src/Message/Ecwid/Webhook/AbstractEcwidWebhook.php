<?php

namespace App\Message\Ecwid\Webhook;

use App\Message\AsyncMessageInterface;
use App\Message\Ecwid\Webhook\Order\AbstractOrderEcwidWebhook;
use App\Message\Ecwid\Webhook\Product\ProductEcwidWebhook;
use App\Util\Ecwid\Enum\EcwidOrderEventsEnum;
use App\Util\Ecwid\Enum\EcwidProductEventsEnum;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use MyCLabs\Enum\Enum;
use Webmozart\Assert\Assert;

abstract class AbstractEcwidWebhook implements AsyncMessageInterface
{
    protected string $eventId;
    protected string $eventType;
    protected int $eventCreated;
    protected int $storeId;

    protected function __construct(array $data)
    {
        $this->eventId = $data['eventId'];
        $this->eventType = $data['eventType'];
        $this->eventCreated = $data['eventCreated'];
        $this->storeId = $data['storeId'];
    }

    public static function create(array $data): self
    {
        Assert::keyExists($data, 'eventType');

        if (EcwidOrderEventsEnum::isValid($data['eventType'])) {
            return AbstractOrderEcwidWebhook::create($data);
        }

        if (EcwidProductEventsEnum::isValid($data['eventType'])) {
            return new ProductEcwidWebhook($data);
        }

        throw new InvalidArgumentException('Unknown event type');
    }

    abstract public function getEventType(): Enum;

    /**
     * @return string
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * @return DateTimeImmutable
     * @throws Exception
     */
    public function getEventCreated(): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp($this->eventCreated);
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
    }
}