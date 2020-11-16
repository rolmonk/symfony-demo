<?php

namespace App\MessageHandler\Ecwid;

use App\Message\Ecwid\Webhook\Order\UpdatedOrderEcwidWebhook;
use App\Service\EcwidService;
use App\Util\Cdek\TariffsEnum;
use App\Util\Ecwid\Enum\PaymentMethodsEnum;
use App\Util\Ecwid\Enum\ShippingMethodsEnum;
use App\Util\Ecwid\Order\Order;
use App\Util\Ecwid\Order\OrderItem;
use CdekSDK2\BaseTypes\Contact;
use CdekSDK2\BaseTypes\Item;
use CdekSDK2\BaseTypes\Location;
use CdekSDK2\BaseTypes\Money;
use CdekSDK2\BaseTypes\Package;
use CdekSDK2\BaseTypes\Phone;
use CdekSDK2\Client;
use CdekSDK2\Exceptions\RequestException;
use ErrorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SendCdekInvoice implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EcwidService
     */
    private EcwidService $ecwidService;
    /**
     * @var Client
     */
    private Client $client;

    public function __construct(EcwidService $ecwidService, Client $client)
    {
        $this->ecwidService = $ecwidService;
        $this->client = $client;
    }

    /**
     * @param UpdatedOrderEcwidWebhook $message
     * @throws ClientExceptionInterface
     * @throws ErrorException
     * @throws RedirectionExceptionInterface
     * @throws RequestException
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function __invoke(UpdatedOrderEcwidWebhook $message)
    {
        $ecwidOrder = $this->ecwidService->getOrder($message->getData()->getOrderId());

        if ($this->supports($message, $ecwidOrder)) {
            $items = [];

            foreach ($ecwidOrder->items as $item) {
                $items[] = Item::create(
                    [
                        'name' => 'ТНП',
                        'ware_key' => $this->ecwidService->getProduct($item->productId)->sku,
                        'payment' => new Money(
                            ['value' => $ecwidOrder->paymentMethod === PaymentMethodsEnum::COD() ? $item->price : 0]
                        ),
                        'cost' => $item->price,
                        'weight' => $item->weight * 1000,
                        'amount' => $item->quantity,

                    ]
                );
            }

            $weight = (int)$this->getWeight($ecwidOrder);

            /** @var \CdekSDK2\BaseTypes\Order $cdekOrder */
            $cdekOrder = \CdekSDK2\BaseTypes\Order::create(
                [
                    'type' => 1,
                    'number' => (string)$ecwidOrder->orderNumber,
                    'tariff_code' => (new TariffsEnum($ecwidOrder->shippingOption->shippingMethodName))->getCode(),
                    'recipient' => Contact::create(
                        [
                            'name' => $ecwidOrder->shippingPerson->name,
                            'phones' => [
                                new Phone(
                                    [
                                        'number' => $this->formatPhone($ecwidOrder->shippingPerson->phone)
                                    ]
                                )
                            ],
                        ],
                    ),
                    'packages' => [
                        $package = Package::create(
                            [
                                'number' => $ecwidOrder->orderNumber,
                                'weight' => $weight,
                                'items' => $items,
                            ]
                        )
                    ],
                    'print' => 'barcode'
                ]
            );

            if ($ecwidOrder->shippingOption->shippingMethodName === TariffsEnum::COURIER()->getValue()) {
                $cdekOrder->to_location = Location::create(
                    [
                        'postal_code' => $ecwidOrder->shippingPerson->postalCode,
                        'country_code' => $ecwidOrder->shippingPerson->countryCode,
                        'region' => $ecwidOrder->shippingPerson->stateOrProvinceName,
                        'city' => $ecwidOrder->shippingPerson->city,
                        'address' => $ecwidOrder->shippingPerson->street,
                    ]
                );
            } else {
                preg_match('/Point ID: \[(.+?)]/', $ecwidOrder->shippingPerson->street, $matches);
                $cdekOrder->delivery_point = $matches[1] ?? '';
            }

            $response = $this->client->orders()->add($cdekOrder);
            if (!$response->isOk()) {
                $this->logger->error(
                    'CDEK invoice error',
                    ['request' => $cdekOrder, 'response' => $response->getBody()]
                );
                throw new ErrorException($response->getBody());
            }
        }
    }

    private function supports(UpdatedOrderEcwidWebhook $message, Order $order): bool
    {
        if (!$message->hasChanges()) {
            return false;
        }

        $shippingMethodName = $order->shippingOption->shippingMethodName;

        if ($shippingMethodName !== ShippingMethodsEnum::CDEK_COURIER()->getValue()
            && $shippingMethodName !== ShippingMethodsEnum::CDEK_STORE()->getValue()) {
            return false;
        }

        if (!$message->isProcessing()) {
            return false;
        }

        $paymentMethodName = $order->paymentMethod;

        if ($message->isAwaitingPayment() && $paymentMethodName === PaymentMethodsEnum::COD()->getValue()) {
            return true;
        }

        if ($message->isPaid() && $paymentMethodName !== PaymentMethodsEnum::COD()->getValue()) {
            return true;
        }

        return false;
    }

    private function getWeight(Order $order): float
    {
        return array_reduce(
            $order->items,
            static function (int $acc, OrderItem $item) {
                return $acc + $item->weight * 1000;
            },
            0
        );
    }

    private function formatPhone(string $phone): string
    {
        $formatted = preg_replace('/[\D]/', '', $phone);

        if (strpos($formatted, '8') === 0) {
            $formatted = substr_replace($formatted, '7', 0, 1);
        }

        return '+' . $formatted;
    }
}