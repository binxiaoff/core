<?php

namespace Unilend\Command;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\{Command\Command, Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Service\ServiceTerms\{ServiceTermsGenerator, ServiceTermsManager};

class GenerateLenderAcceptedServiceTermsCommand extends Command
{
    /** @var AcceptationLegalDocsRepository */
    private $acceptationLegalDocsRepository;
    /** @var ServiceTermsGenerator */
    private $serviceTermsGenerator;
    /** @var ServiceTermsManager */
    private $serviceTermsManager;
    /** @var LoggerInterface */
    private $consoleLogger;

    /**
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ServiceTermsGenerator          $serviceTermsGenerator
     * @param ServiceTermsManager            $serviceTermsManager
     * @param LoggerInterface                $consoleLogger
     */
    public function __construct(
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        ServiceTermsGenerator $serviceTermsGenerator,
        ServiceTermsManager $serviceTermsManager,
        LoggerInterface $consoleLogger
    ) {
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->serviceTermsGenerator          = $serviceTermsGenerator;
        $this->serviceTermsManager            = $serviceTermsManager;
        $this->consoleLogger                  = $consoleLogger;

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

        $acceptedServiceTerms = $this->acceptationLegalDocsRepository->findByIdLegalDocWithoutPfd($limit);

        foreach ($acceptedServiceTerms as $accepted) {
            try {
                $this->serviceTermsGenerator->generate($accepted);
            } catch (Exception $exception) {
                $this->consoleLogger->error(sprintf('An error occurred while generating lender terms of sale pdf. Error: %s', $exception->getMessage()), [
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
