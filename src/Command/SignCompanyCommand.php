<?php

declare(strict_types=1);

namespace Unilend\Command;

use Exception;
use Symfony\Component\Console\{Command\Command, Input\InputArgument, Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\{CompanyStatus, TemporaryToken};
use Unilend\Repository\{CompaniesRepository, TemporaryTokenRepository};
use Unilend\Service\Staff\StaffNotifier;

class SignCompanyCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'kls:company:sign';
    /** @var CompaniesRepository */
    private $companiesRepository;
    /** @var StaffNotifier */
    private $staffNotifier;
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /**
     * @param CompaniesRepository      $companiesRepository
     * @param StaffNotifier            $staffNotifier
     * @param TemporaryTokenRepository $temporaryTokenRepository
     */
    public function __construct(CompaniesRepository $companiesRepository, StaffNotifier $staffNotifier, TemporaryTokenRepository $temporaryTokenRepository)
    {
        parent::__construct();

        $this->companiesRepository      = $companiesRepository;
        $this->staffNotifier            = $staffNotifier;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('This command change the status to "contract signed" for the given companies, then, notify their staff to initialise their accounts.');
        $this->addArgument('companies', InputArgument::IS_ARRAY, 'Which companies do you want to sign ?');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $inputCompanies = $input->getArgument('companies');
        foreach ($inputCompanies as $companyId) {
            $company = $this->companiesRepository->find($companyId);
            if (null === $company) {
                continue;
            }

            $company->setCurrentStatus(CompanyStatus::STATUS_SIGNED);

            $this->companiesRepository->save($company);

            foreach ($company->getStaff() as $staff) {
                $client = $staff->getClient();

                if ($client->isInvited() && $client->isGrantedLogin()) {
                    $temporaryToken = TemporaryToken::generateUltraLongToken($client);
                    $this->temporaryTokenRepository->save($temporaryToken);
                    $this->staffNotifier->notifyClientInitialisation($staff, $temporaryToken);
                }
            }
        }

        return 0;
    }
}
