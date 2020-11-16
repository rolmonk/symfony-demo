<?php

namespace App\MessageHandler\Ecwid;

use App\Message\Ecwid\Webhook\Order\UpdatedOrderEcwidWebhook;
use App\Service\CarrotService;
use App\Service\EcwidService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;
use Veksa\Carrot\Exceptions\Exception;
use Veksa\Carrot\Exceptions\InvalidJsonException;

final class UpdatedOrderEcwidWebhookHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EcwidService
     */
    private EcwidService $ecwidService;
    /**
     * @var CarrotService
     */
    private CarrotService $carrotService;

    public function __construct(EcwidService $ecwidService, CarrotService $carrotService)
    {
        $this->ecwidService = $ecwidService;
        $this->carrotService = $carrotService;
    }

    /**
     * @param UpdatedOrderEcwidWebhook $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Throwable
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws InvalidJsonException
     */
    public function __invoke(UpdatedOrderEcwidWebhook $message)
    {
        $order = $this->ecwidService->getOrder($message->getData()->getOrderId());

        try {
            $this->carrotService->updateLead($order, $message->getEventType());
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
