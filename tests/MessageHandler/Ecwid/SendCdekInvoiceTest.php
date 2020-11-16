<?php
/** @noinspection PhpMissingFieldTypeInspection */

/** @noinspection ReturnTypeCanBeDeclaredInspection */

/** @noinspection PhpParamsInspection */

namespace App\Tests\MessageHandler\Ecwid;

use App\Message\Ecwid\Webhook\Order\UpdatedOrderEcwidWebhook;
use App\MessageHandler\Ecwid\SendCdekInvoice;
use App\Service\EcwidService;
use App\Util\Ecwid\Enum\PaymentMethodsEnum;
use App\Util\Ecwid\Enum\ShippingMethodsEnum;
use App\Util\Ecwid\Order\Order;
use App\Util\Ecwid\Order\ShippingOption;
use App\Util\Ecwid\Order\ShippingPerson;
use CdekSDK2\Actions\Orders;
use CdekSDK2\Client;
use CdekSDK2\Http\ApiResponse;
use ErrorException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SendCdekInvoiceTest extends KernelTestCase
{
    protected $client;
    protected $service;
    protected $message;
    protected $logger;



    public function testSuccessInvoke()
    {
        $service = $this->service;

        $request = $this->createMock(ApiResponse::class);
        $request->method('isOk')->willReturn(true);
        $request->method('getBody')->willReturn('[]');

        $action = $this->createMock(Orders::class);
        $action->expects(self::once())->method('add')->willReturn($request);

        $client = $this->client;
        $client->method('orders')->willReturn($action);

        $test = new SendCdekInvoice($service, $client);
        $test->setLogger($this->logger);

        $test($this->message);
    }

    public function testErrorResponse()
    {
        $request = $this->createMock(ApiResponse::class);
        $request->method('isOk');
        $request->method('getBody')->willReturn('Test Error');

        $action = $this->createMock(Orders::class);
        $action->expects(self::once())->method('add')->willReturn($request);

        $client = $this->client;
        $client->method('orders')->willReturn($action);

        $test = new SendCdekInvoice($this->service, $client);
        $test->setLogger($this->logger);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Test Error');

        $test($this->message);
    }

    public function testContainer()
    {
        self::bootKernel();
        $container = self::$container;

        $client = $container->get(Client::class);
        self::assertInstanceOf(Client::class, $client);
    }

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->method('info')->willReturn(true);

        $this->client = $this->createMock(Client::class);

        $service = $this->createMock(EcwidService::class);
        $service->method('getOrder')->willReturn($this->getOrder());
        $this->service = $service;

        $message = $this->createMock(UpdatedOrderEcwidWebhook::class);
        $message->method('hasChanges')->willReturn(true);
        $message->method('isProcessing')->willReturn(true);
        $message->method('isAwaitingPayment')->willReturn(true);

        $this->message = $message;
    }

    private function getOrder()
    {
        $order = new Order();
        $order->paymentMethod = PaymentMethodsEnum::COD()->getValue();
        $order->shippingOption = ShippingOption::fromArray(
            [
                'shippingMethodName' => ShippingMethodsEnum::CDEK_COURIER()->getValue()
            ]
        );

        $order->shippingPerson = ShippingPerson::fromArray(
            [
                'phone' => '123456'
            ]
        );

        return $order;
    }
}
