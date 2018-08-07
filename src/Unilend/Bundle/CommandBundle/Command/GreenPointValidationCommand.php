<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
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
    /** @var EntityManager */
    private $entityManager;
    /** @var GreenPointValidationManager */
    private $validationManager;

    /**
     * @param LoggerInterface             $logger
     * @param EntityManager               $entityManager
     * @param GreenPointValidationManager $validationManager
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManager $entityManager,
        GreenPointValidationManager $validationManager
    )
    {
        $this->logger            = $logger;
        $this->entityManager     = $entityManager;
        $this->validationManager = $validationManager;

        parent::__construct('lender:greenpoint_validation');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
        $clients = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getLendersInStatus(GreenPointValidationManager::STATUS_TO_CHECK, true);

        if (empty($clients)) {
            return;
        }

        foreach ($clients as $client) {
            foreach ($client->getAttachments() as $attachment) {
                if (false === $this->validationManager->isEligibleForValidation($attachment)) {
                    continue;
                }

                try {
                    $this->validationManager->validateAttachement($attachment);
                } catch (\Exception $exception) {
                    $this->logger->error(
                        'Un error occurred during sending attachment to GreenPoint - Message: ' . $exception->getMessage() . ' - Code: ' . $exception->getCode(), [
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
                    'Un error occurred during getting of KYC status from GreenPoint - Message: ' . $exception->getMessage() . ' - Code: ' . $exception->getCode(), [
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
