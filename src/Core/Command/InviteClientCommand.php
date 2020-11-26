<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use Exception;
use Symfony\Component\Console\{Command\Command, Input\InputArgument, Input\InputInterface, Output\OutputInterface};
use Unilend\Core\Entity\Clients;
use Unilend\Core\Repository\ClientsRepository;
use Unilend\Core\Service\Staff\StaffNotifier;

class InviteClientCommand extends Command
{
    protected static $defaultName = 'kls:client:invite';

    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var StaffNotifier */
    private $staffNotifier;

    /**
     * @param ClientsRepository $clientsRepository
     * @param StaffNotifier     $staffNotifier
     */
    public function __construct(ClientsRepository $clientsRepository, StaffNotifier $staffNotifier)
    {
        parent::__construct();

        $this->clientsRepository = $clientsRepository;
        $this->staffNotifier     = $staffNotifier;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('This command notify a list of clients to initialise their accounts.');
        $this->addArgument('clients', InputArgument::IS_ARRAY, 'Which clients do you want to sign (separate multiple id with a space)?');
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
            $staff  = $client instanceof Clients ? $client->getStaff() : [];
            if (0 === $staff->count()) {
                continue;
            }
            $currentStaff = $staff->current();
            while ($currentStaff && 1 > $this->staffNotifier->notifyClientInitialisation($currentStaff)) {
                $currentStaff = $staff->next();
            }
        }

        return 0;
    }
}
