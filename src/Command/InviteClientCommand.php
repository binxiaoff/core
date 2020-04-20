<?php

declare(strict_types=1);

namespace Unilend\Command;

use Exception;
use Symfony\Component\Console\{Command\Command, Input\InputArgument, Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\TemporaryToken;
use Unilend\Repository\{ClientsRepository, TemporaryTokenRepository};
use Unilend\Service\Staff\StaffNotifier;

class InviteClientCommand extends Command
{
    protected static $defaultName = 'kls:client:invite';

    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;
    /** @var StaffNotifier */
    private $staffNotifier;

    /**
     * @param ClientsRepository        $clientsRepository
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param StaffNotifier            $staffNotifier
     */
    public function __construct(ClientsRepository $clientsRepository, TemporaryTokenRepository $temporaryTokenRepository, StaffNotifier $staffNotifier)
    {
        parent::__construct();

        $this->clientsRepository        = $clientsRepository;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->staffNotifier            = $staffNotifier;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('This command notify a list of clients to initialise their accounts.');
        $this->addArgument('clients', InputArgument::IS_ARRAY, 'Which clients do you want to sign ?');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $clientIds = $input->getArgument('clients');

        foreach ($clientIds as $clientId) {
            $client = $this->clientsRepository->find($clientId);
            if (null === $client) {
                continue;
            }
            $staff = $client->getStaff();
            foreach ($staff as $employee) {
                if ($client->isInitializationNeeded() && $client->isGrantedLogin()) {
                    $temporaryToken = TemporaryToken::generateUltraLongToken($client);
                    $this->temporaryTokenRepository->save($temporaryToken);
                    if (0 < $this->staffNotifier->notifyClientInitialisation($employee, $temporaryToken)) {
                        break;
                    }
                }
            }
        }

        return 0;
    }
}
