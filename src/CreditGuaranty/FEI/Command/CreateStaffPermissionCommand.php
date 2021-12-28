<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Command;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Model\Bitmask;
use KLS\Core\Repository\StaffRepository;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateStaffPermissionCommand extends Command
{
    private const BATCH_SIZE = 10;

    protected static $defaultName = 'kls:fei:staff:permission:create';

    private StaffRepository $staffRepository;
    private StaffPermissionRepository $staffPermissionRepository;

    public function __construct(StaffRepository $staffRepository, StaffPermissionRepository $staffPermissionRepository)
    {
        parent::__construct();

        $this->staffRepository           = $staffRepository;
        $this->staffPermissionRepository = $staffPermissionRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create StaffPermission(s) with staff ids list as argument')
            ->addArgument('staffIds', InputArgument::IS_ARRAY, 'a list of staff ids (separated by a whitespace)')
            ->setHelp(self::$defaultName . ' 1 2 3 5 8 13 21')
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $staffIds = $input->getArgument('staffIds');

        if (empty($staffIds)) {
            $io->warning('No staff ids passed as argument on which to create permission');

            return Command::SUCCESS;
        }

        $i = 0;

        foreach ($staffIds as $staffId) {
            $staff = $this->staffRepository->find($staffId);

            if (false === ($staff instanceof Staff)) {
                $io->warning(\sprintf('No Staff found with id #%s', $staffId));

                continue;
            }

            $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $staff]);

            if ($staffPermission instanceof StaffPermission) {
                $io->warning(\sprintf('Staff #%s already has StaffPermission', $staffId));

                continue;
            }

            $permissions = (Company::SHORT_CODE_CASA === $staff->getCompany()->getShortCode())
                ? StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS
                : StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS;

            $staffPermission = new StaffPermission($staff, new Bitmask($permissions));
            $staffPermission->setGrantPermissions($permissions);

            $this->staffPermissionRepository->persist($staffPermission);
            ++$i;

            if (0 === $i % self::BATCH_SIZE) {
                $this->staffPermissionRepository->flush();
            }
        }

        $this->staffPermissionRepository->flush();

        $io->success(\sprintf('%s StaffPermission(s) created', $i));

        return Command::SUCCESS;
    }
}
