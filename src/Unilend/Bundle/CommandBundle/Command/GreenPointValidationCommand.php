<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\GreenPointValidationManager;

class GreenPointValidationCommand extends Command
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var GreenPointValidationManager */
    private $validationManager;

    /**
     * @param LoggerInterface             $logger
     * @param EntityManagerInterface      $entityManager
     * @param GreenPointValidationManager $validationManager
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        GreenPointValidationManager $validationManager
    )
    {
        $this->logger            = $logger;
        $this->entityManager     = $entityManager;
        $this->validationManager = $validationManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('lender:greenpoint_validation')
            ->setDescription('Validate the lenders attachment via GreenPoint service')
            ->setHelp(<<<EOF
The <info>lender:loan_contract</info> validates lenders documents : identity, bank details and address.
<info>php bin/console lender:greenpoint_validation</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var Clients[] $clients */
        $clients = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getLendersForGreenpointCheck();
        if (empty($clients)) {
            return;
        }

        foreach ($clients as $client) {
            foreach ($client->getAttachments() as $attachment) {
                try {
                    $this->validationManager->validateAttachement($attachment);
                } catch (\Exception $exception) {
                    $this->logger->error(
                        'An error occurred during sending attachment to GreenPoint - Message: ' . $exception->getMessage() . ' - Code: ' . $exception->getCode(), [
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                        'id_client' => $client->getIdClient()
                    ]);
                }
            }

            try {
                $this->validationManager->saveClientKycStatus($client);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'An error occurred during getting of KYC status from GreenPoint - Message: ' . $exception->getMessage() . ' - Code: ' . $exception->getCode(), [
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'id_client' => $client->getIdClient()
                ]);
            }
        }
    }
}
