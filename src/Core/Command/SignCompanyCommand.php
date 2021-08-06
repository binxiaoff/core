<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\{CompanyStatus};
use Unilend\Core\Repository\CompanyModuleRepository;
use Unilend\Core\Repository\CompanyRepository;
use Unilend\Core\Service\Staff\StaffNotifier;

class SignCompanyCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'kls:company:sign';

    private CompanyRepository $companyRepository;

    private StaffNotifier $staffNotifier;

    private CompanyModuleRepository $moduleRepository;

    public function __construct(
        CompanyRepository $companyRepository,
        StaffNotifier $staffNotifier,
        CompanyModuleRepository $moduleRepository
    ) {
        parent::__construct();

        $this->companyRepository = $companyRepository;
        $this->staffNotifier     = $staffNotifier;
        $this->moduleRepository  = $moduleRepository;
    }

    protected function configure(): void
    {
        $this->setDescription('This command change the status to "contract signed" for the given companies, then, notify their staff to initialise their accounts.');
        $this->addArgument('companies', InputArgument::IS_ARRAY, 'Which companies do you want to sign ?');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $inputCompanies = $input->getArgument('companies');
        foreach ($inputCompanies as $companyId) {
            $company = $this->companyRepository->find($companyId);
            if (null === $company) {
                continue;
            }
            $company->setCurrentStatus(new CompanyStatus($company, CompanyStatus::STATUS_SIGNED));
            $this->companyRepository->save($company);

            foreach ($company->getStaff() as $staff) {
                $this->staffNotifier->notifyUserInitialisation($staff);
            }

            $modules = $company->getModules()->toArray();

            foreach (CompanyModule::getAvailableModuleCodes() as $moduleName) {
                if (false === isset($modules[$moduleName])) {
                    $this->moduleRepository->save(new CompanyModule($moduleName, $company));
                }
            }
        }

        return 0;
    }
}
