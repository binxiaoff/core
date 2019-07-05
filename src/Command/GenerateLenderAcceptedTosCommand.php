<?php

namespace Unilend\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Service\Document\LenderTermsOfSaleGenerator;
use Unilend\Service\TermsOfSaleManager;

class GenerateLenderAcceptedTosCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderTermsOfSaleGenerator */
    private $lenderTermsOfSaleGenerator;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var LoggerInterface */
    private $consoleLogger;

    /**
     * @param EntityManagerInterface     $entityManager
     * @param LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator
     * @param TermsOfSaleManager         $termsOfSaleManager
     * @param LoggerInterface            $consoleLogger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator,
        TermsOfSaleManager $termsOfSaleManager,
        LoggerInterface $consoleLogger
    ) {
        $this->entityManager              = $entityManager;
        $this->lenderTermsOfSaleGenerator = $lenderTermsOfSaleGenerator;
        $this->termsOfSaleManager         = $termsOfSaleManager;
        $this->consoleLogger              = $consoleLogger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('unilend:lender:terms_of_sale:generate')
            ->setDescription('Generates terms of sale pdf document')
            ->setHelp(
                <<<'EOF'
The <info>lender:terms_of_sale:generate</info> command generates the pdf version of accepted lenders terms of sale.
<info>php bin/console lender:terms_of_sale:generate</info>
EOF
            )
            ->addOption('limit-tos', 'l', InputOption::VALUE_REQUIRED, 'Number of accepted tos to process')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $limit = $input->getOption('limit-tos');
        $limit = $limit ? $limit : 100;

        $acceptedTermsOfUse = $this->entityManager
            ->getRepository(AcceptationsLegalDocs::class)
            ->findByIdLegalDocWithoutPfd($limit)
        ;

        foreach ($acceptedTermsOfUse as $accepted) {
            try {
                if (false === $this->lenderTermsOfSaleGenerator->exists($accepted)) {
                    $this->lenderTermsOfSaleGenerator->generate($accepted);
                }

                $accepted->setPdfName($this->lenderTermsOfSaleGenerator->getName($accepted));

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
