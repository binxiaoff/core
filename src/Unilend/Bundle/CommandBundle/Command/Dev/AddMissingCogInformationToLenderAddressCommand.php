<?php

namespace Unilend\Bundle\CommandBundle\Command\Dev;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Unilend\Bundle\CoreBusinessBundle\Service\AddressManager;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};

class AddMissingCogInformationToLenderAddressCommand extends Command
{
    /** @var EntityManagerInterface  */
    private $entityManager;
    /** @var AddressManager  */
    private $addressManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AddressManager         $addressManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, AddressManager $addressManager, LoggerInterface $logger)
    {
        $this->entityManager  = $entityManager;
        $this->addressManager = $addressManager;
        $this->logger         = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('unilend:dev_tools:lender_address:add_cog')
            ->setDescription('Add the code officiel géographique to lender addresses')
            ->setHelp(<<<EOF
The <info>unilend:dev_tools:lender_address:add_cog</info> command adds the geographical information needed for IFU to lender addresses
<info>unilend:dev_tools:lender_address:add_cog</info>
EOF
            )
        ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of addresses to process (should be round)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $limit = $input->getOption('limit');
        $limit = $limit ? round($limit / 2, 0, PHP_ROUND_HALF_DOWN) : 100;

        $clientAddress = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ClientAddress')
            ->findLenderAddressWithoutCog($limit);

        $companyAddress = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
            ->findLenderAddressWithoutCog($limit);

        $addressWithMissingCog = array_merge($clientAddress, $companyAddress);

        foreach ($addressWithMissingCog as $address) {
            try {
                $this->addressManager->addCogToLenderAddress($address);

            } catch (\Exception $exception) {
                $this->logger->error('An error occurred during adding cog to lender address. message: ' . $exception->getMessage(), [
                    'class'        => __CLASS__,
                    'function'     => __FUNCTION__,
                    'file'         => $exception->getFile(),
                    'line'         => $exception->getLine(),
                    'addressClass' => get_class($address),
                    'id_address'   => $address->getId()
                ]);
            }
        }
    }
}
