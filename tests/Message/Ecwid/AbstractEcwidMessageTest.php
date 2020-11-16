<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace App\Tests\Message\Ecwid;

use App\Message\Ecwid\Webhook\AbstractEcwidWebhook;
use App\Message\Ecwid\Webhook\Order\CreatedOrderEcwidWebhook;
use App\Message\Ecwid\Webhook\Order\UpdatedOrderEcwidWebhook;
use App\Util\Ecwid\Enum\EcwidOrderEventsEnum;
use App\Util\Ecwid\Order\FulfillmentStatusEnum;
use PHPUnit\Framework\TestCase;

class AbstractEcwidMessageTest extends TestCase
{
    public function testOrderCreateMessage()
    {
        $json = file_get_contents(__DIR__ . '/../../data/ecwid/webhooks/order.created.json');
        $message = AbstractEcwidWebhook::create(json_decode($json, true, 512, JSON_THROW_ON_ERROR));

        self::assertInstanceOf(CreatedOrderEcwidWebhook::class, $message);
        /** @var CreatedOrderEcwidWebhook $message */
        self::assertTrue($message->getEventType()->equals(EcwidOrderEventsEnum::EVENT_ORDER_CREATED()));
        self::assertTrue(
            $message->getData()->getNewFulfillmentStatus()->equals(FulfillmentStatusEnum::AWAITING_PROCESSING())
        );
    }

    public function testOrderUpdateMessage()
    {
        $json = file_get_contents(__DIR__ . '/../../data/ecwid/webhooks/order.updated.json');
        $message = AbstractEcwidWebhook::create(json_decode($json, true, 512, JSON_THROW_ON_ERROR));

        self::assertInstanceOf(UpdatedOrderEcwidWebhook::class, $message);
        /** @var UpdatedOrderEcwidWebhook $message */
        self::assertTrue($message->getEventType()->equals(EcwidOrderEventsEnum::EVENT_ORDER_UPDATED()));
        self::assertTrue($message->getData()->getNewFulfillmentStatus()->equals(FulfillmentStatusEnum::DELIVERED()));
    }
}
