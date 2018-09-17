<?php

namespace Unilend\Bundle\CommandBundle\Command\Dev;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Unilend\Bundle\CoreBusinessBundle\Service\Document\LenderTermsOfSaleGenerator;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};

class GenerateOldLenderTosFilesCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderTermsOfSaleGenerator  */
    private $lenderTermsOfSaleGenerator;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface     $entityManager
     * @param LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator,
        LoggerInterface $logger
    )
    {
        $this->entityManager              = $entityManager;
        $this->lenderTermsOfSaleGenerator = $lenderTermsOfSaleGenerator;
        $this->logger                     = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('unilend:dev_tools:lender:legacy_terms_of_sale:generate')
            ->setDescription('Generates terms of sale pdf document')
            ->setHelp(<<<EOF
The <info>unilend:dev_tools:lender:legacy_terms_of_sale:generate</info> command generates the pdf version of accepted lenders terms of sale for previous terms of sale.
<info>php bin/console unilend:dev_tools:lender:legacy_terms_of_sale:generate</info>
EOF
            )
            ->addOption('limit-tos', 'l', InputOption::VALUE_REQUIRED, 'Number of accepted tos to process');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $limit = $input->getOption('limit-tos');
        $limit = $limit ? $limit : 1000;

        $acceptedTermsOfUse = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')
            ->findWithoutPfdForLender($limit);

        if (empty($acceptedTermsOfUse)) {
            $output->writeln('No more accepted terms of sale without pdf');
        }

        $generatedPdf = 0;

        foreach ($acceptedTermsOfUse as $accepted) {
            try {
                if (false === $this->lenderTermsOfSaleGenerator->exists($accepted)) {
                    $this->lenderTermsOfSaleGenerator->generate($accepted);
                }

                $accepted->setPdfName($this->lenderTermsOfSaleGenerator->getName($accepted));

                $this->entityManager->flush();

            } catch (\Exception $exception) {
                $this->logger->error('An error occurred while generating lender terms of sale pdf. Message: ' . $exception->getMessage(), [
                    'class'          => __CLASS__,
                    'function'       => __FUNCTION__,
                    'file'           => $exception->getFile(),
                    'line'           => $exception->getLine(),
                    'id_acceptation' => $accepted->getIdAcceptation()
                ]);
                continue;
            }
            $generatedPdf++;
        }

        $output->writeln($generatedPdf . ' lender TOS pdfs have been generated');
    }
}
