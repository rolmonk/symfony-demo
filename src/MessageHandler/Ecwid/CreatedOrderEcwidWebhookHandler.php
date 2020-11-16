<?php

namespace App\MessageHandler\Ecwid;

use App\Message\Ecwid\Webhook\Order\CreatedOrderEcwidWebhook;
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

class CreatedOrderEcwidWebhookHandler implements MessageHandlerInterface, LoggerAwareInterface
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
     * @param CreatedOrderEcwidWebhook $webhook
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface|Throwable
     */
    public function __invoke(CreatedOrderEcwidWebhook $webhook)
    {
        $order = $this->ecwidService->getOrder($webhook->getData()->getOrderId());

        try {
            $this->ecwidService->updateEmailFromComment($order);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        try {
            $this->carrotService->updateLead($order, $webhook->getEventType());
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}