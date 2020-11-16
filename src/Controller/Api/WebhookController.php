<?php

namespace App\Controller\Api;

use App\Message\Ecwid\Webhook\AbstractEcwidWebhook;
use JsonException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class WebhookController
 * @Route("/api/webhook")
 */
class WebhookController extends AbstractController
{
    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $bus;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(MessageBusInterface $bus, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->bus = $bus;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @Route("/ecwid")
     * @param Request $request
     * @return Response
     * @throws LogicException
     * @throws JsonException
     */
    public function ecwid(Request $request): Response
    {
        $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->logger->info('Ecwid webhook request', $requestBody);

        try {
            $message = AbstractEcwidWebhook::create($requestBody);
            $this->bus->dispatch($message);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }

        return new Response();
    }
}