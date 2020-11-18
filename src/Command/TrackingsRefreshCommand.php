<?php

namespace App\Command;

use App\Service\CarrotService;
use App\Service\TrackingService;
use App\Util\Carrot\Types\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Veksa\Carrot\Exceptions\Exception;
use Veksa\Carrot\Exceptions\InvalidArgumentException;

class TrackingsRefreshCommand extends Command
{
    protected static $defaultName = 'app:trackings:refresh';
    /**
     * @var CarrotService
     */
    private CarrotService $carrotService;
    /**
     * @var TrackingService
     */
    private TrackingService $trackingService;

    public function __construct(CarrotService $carrotService, TrackingService $trackingService, string $name = null)
    {
        parent::__construct($name);
        $this->carrotService = $carrotService;
        $this->trackingService = $trackingService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Get and update order trackings');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $leads = $this->carrotService->getLeads();
        $leads = array_filter($leads, function (User $lead) {
            $props = $lead->getProps();
            return isset($props['Трекинг-номер']);
        });

        $this->trackingService->track(...$leads);

        return 0;
    }
}
