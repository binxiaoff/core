<?php

namespace Unilend\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Service\Document\ServiceTermsGenerator;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class GenerateLenderAcceptedServiceTermsCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ServiceTermsGenerator */
    private $serviceTermsGenerator;
    /** @var ServiceTermsManager */
    private $serviceTermsManager;
    /** @var LoggerInterface */
    private $consoleLogger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ServiceTermsGenerator  $serviceTermsGenerator
     * @param ServiceTermsManager    $serviceTermsManager
     * @param LoggerInterface        $consoleLogger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ServiceTermsGenerator $serviceTermsGenerator,
        ServiceTermsManager $serviceTermsManager,
        LoggerInterface $consoleLogger
    ) {
        $this->entityManager         = $entityManager;
        $this->serviceTermsGenerator = $serviceTermsGenerator;
        $this->serviceTermsManager   = $serviceTermsManager;
        $this->consoleLogger         = $consoleLogger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('unilend:lender:service_terms:generate')
            ->setDescription('Generates terms of sale pdf document')
            ->setHelp(
                <<<'EOF'
The <info>lender:service_terms:generate</info> command generates the pdf version of accepted lenders terms of sale.
<info>php bin/console lender:service_terms:generate</info>
EOF
            )
            ->addOption('limit-service-terms', 'l', InputOption::VALUE_REQUIRED, 'Number of accepted service terms to process')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $limit = $input->getOption('limit-service-terms');
        $limit = $limit ? $limit : 100;

        $acceptedServiceTerms = $this->entityManager
            ->getRepository(AcceptationsLegalDocs::class)
            ->findByIdLegalDocWithoutPfd($limit)
        ;

        foreach ($acceptedServiceTerms as $accepted) {
            try {
                if (false === $this->serviceTermsGenerator->exists($accepted)) {
                    $this->serviceTermsGenerator->generate($accepted);
                }

                $accepted->setPdfName($this->serviceTermsGenerator->getName($accepted));

                $this->entityManager->flush();
            } catch (Exception $exception) {
                $this->consoleLogger->error('An error occurred while generating lender terms of sale pdf. Message: ' . $exception->getMessage(), [
                    'class'          => __CLASS__,
                    'function'       => __FUNCTION__,
                    'file'           => $exception->getFile(),
                    'line'           => $exception->getLine(),
                    'id_acceptation' => $accepted->getIdAcceptation(),
                ]);

                continue;
            }
        }
    }
}
