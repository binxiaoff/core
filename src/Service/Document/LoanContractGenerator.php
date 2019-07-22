<?php

namespace Unilend\Service\Document;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException};
use Exception;
use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Twig\Environment;
use Unilend\Entity\{AddressType, Blocs, BlocsElements, ClientAddress, Companies, CompaniesActifPassif, CompaniesBilans, CompanyAddress, Echeanciers, Elements,
    Interfaces\FileStorageInterface, Loans, ProjectsStatus, ProjectsStatusHistory, TaxType, UnderlyingContract};
use Unilend\Service\LoanManager;

class LoanContractGenerator extends AbstractDocumentGenerator
{
    private const PATH        = 'loan_contract';
    private const FILE_PREFIX = 'contrat-pret';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Environment */
    private $twig;
    /** @var Pdf */
    private $snappy;
    /** @var string */
    private $staticUrl;
    /** @var string */
    private $publicDirectory;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var \NumberFormatter */
    private $currencyFormatter;
    /** @var LoanManager */
    private $loanManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $publicDirectory
     * @param Environment            $twig
     * @param Pdf                    $snappy
     * @param Packages               $assetsPackages
     * @param \NumberFormatter       $numberFormatter
     * @param \NumberFormatter       $currencyFormatter
     * @param LoanManager            $loanManager
     * @param LoggerInterface        $logger
     * @param string                 $documentRootDirectory
     * @param ManagerRegistry        $managerRegistry
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $publicDirectory,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter,
        LoanManager $loanManager,
        LoggerInterface $logger,
        string $documentRootDirectory,
        ManagerRegistry $managerRegistry
    ) {
        $this->entityManager     = $entityManager;
        $this->publicDirectory   = $publicDirectory;
        $this->twig              = $twig;
        $this->snappy            = $snappy;
        $this->staticUrl         = $assetsPackages->getUrl('');
        $this->numberFormatter   = $numberFormatter;
        $this->currencyFormatter = $currencyFormatter;
        $this->loanManager       = $loanManager;
        $this->logger            = $logger;

        $this->snappy->setBinary('/usr/local/bin/wkhtmltopdf');

        parent::__construct($documentRootDirectory, $managerRegistry);
    }

    /**
     * @param Loans|FileStorageInterface $loan
     *
     * @throws NonUniqueResultException
     * @throws Exception
     */
    protected function generateDocument(FileStorageInterface $loan): void
    {
        $template = [
            'staticUrl' => $this->staticUrl,
            'content'   => $this->getContentData($loan),
            'loan'      => $this->getLoanData($loan),
            'borrower'  => $this->getBorrowerData($loan),
            'lender'    => $this->getLenderData($loan),
            'project'   => $loan->getTranche()->getName(),
        ];

        if (UnderlyingContract::CONTRACT_BDC === $loan->getUnderlyingContract()->getLabel()) {
            if (false === empty($loan->getTranche()->getIdDernierBilan())) {
                $annualAccountsRepository = $this->entityManager->getRepository(CompaniesBilans::class);
                $lastAnnualAccounts       = $annualAccountsRepository->find($loan->getTranche()->getIdDernierBilan());

                if (null !== $lastAnnualAccounts) {
                    $assetsDebtsRepository = $this->entityManager->getRepository(CompaniesActifPassif::class);

                    $template['finance'] = [
                        'lastAnnualAccountsDate' => $lastAnnualAccounts->getClotureExerciceFiscal()->format('d/m/Y'),
                        'assetsDebts'            => $assetsDebtsRepository->findOneBy(['idBilan' => $lastAnnualAccounts->getIdBilan()]),
                    ];
                }
            }
        }

        $content = $this->twig->render('/pdf/contract/' . $loan->getUnderlyingContract()->getDocumentTemplate() . '.html.twig', $template);

        $this->snappy->setOption('user-style-sheet', $this->publicDirectory . 'styles/default/pdf/style.css');
        $this->snappy->generateFromHtml($content, $this->getFilePath($loan), [], true);
    }

    /**
     * @param Loans|FileStorageInterface $loan
     *
     * @return string
     */
    protected function getFileName(FileStorageInterface $loan): string
    {
        return self::FILE_PREFIX . '-' . $loan->getWallet()->getIdClient()->getHash() . '-' . $loan->getIdLoan() . '.pdf';
    }

    /**
     * @param Loans|FileStorageInterface $loan
     *
     * @return string
     */
    protected function generateRelativeDirectory(FileStorageInterface $loan): string
    {
        return self::PATH . DIRECTORY_SEPARATOR . $loan->getTranche()->getProject()->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(FileStorageInterface $loan): bool
    {
        return $loan instanceof Loans;
    }

    /**
     * @param Loans $loan
     *
     * @return array
     */
    private function getLoanData(Loans $loan): array
    {
        $interests         = 0;
        $loanAmount        = bcdiv($loan->getAmount(), 100);
        $repaymentSchedule = $this->entityManager
            ->getRepository(Echeanciers::class)
            ->findBy(['idLoan' => $loan], ['ordre' => 'ASC'])
        ;

        foreach ($repaymentSchedule as $repayment) {
            $interests = bcadd($interests, bcdiv($repayment->getInterets(), 100, 5), 5);
        }

        $repaymentAmount = bcdiv(bcadd($repaymentSchedule[0]->getCapital(), $repaymentSchedule[0]->getInterets()), 100, 5);

        $repaymentStatus = $this->entityManager
            ->getRepository(ProjectsStatus::class)
            ->findOneBy(['status' => ProjectsStatus::STATUS_CONTRACTS_SIGNED])
        ;

        $repaymentStatusHistory = $this->entityManager
            ->getRepository(ProjectsStatusHistory::class)
            ->findOneBy(
                ['idProject' => $loan->getTranche()->getProject(), 'idProjectStatus' => $repaymentStatus],
                ['added' => 'ASC', 'idProjectStatusHistory' => 'ASC']
            )
        ;

        $vatRate                    = $this->entityManager->getRepository(TaxType::class)->findOneBy(['idTaxType' => TaxType::TYPE_VAT]);
        $fundsReleaseCommissionRate = round(bcdiv($loan->getTranche()->getCommissionRateFunds(), 100, 4), 2);
        $repaymentCommissionRate    = round(bcdiv($loan->getTranche()->getCommissionRateRepayment(), 100, 4), 2);
        $repaymentCommission        = \repayment::getRepaymentCommission($loanAmount, $loan->getTranche()->getPeriod(), $repaymentCommissionRate, $vatRate->getRate());
        $fundsReleaseCommission     = bcmul($loanAmount, $fundsReleaseCommissionRate, 5);
        $borrowerFees               = bcadd($repaymentCommission['commission_total'], $fundsReleaseCommission, 5);
        $borrowerCost               = bcadd($borrowerFees, $interests, 5);

        return [
            'id'                => $loan->getIdLoan(),
            'amount'            => $loanAmount,
            'formattedAmount'   => $this->numberFormatter->format($loanAmount),
            'rate'              => $this->numberFormatter->format($loan->getRate()->getMargin()),
            'interests'         => $this->currencyFormatter->formatCurrency($interests, 'EUR'),
            'repaymentAmount'   => $this->currencyFormatter->formatCurrency($repaymentAmount, 'EUR'),
            'creationDate'      => $repaymentStatusHistory ? $repaymentStatusHistory->getAdded()->format('d/m/Y') : date('d/m/Y'),
            'lastRepaymentDate' => array_slice($repaymentSchedule, -1)[0]->getDateEcheance()->format('d/m/Y'),
            'borrowerFees'      => $this->currencyFormatter->formatCurrency($borrowerFees, 'EUR'),
            'borrowerCost'      => $this->currencyFormatter->formatCurrency($borrowerCost, 'EUR'),
            'repaymentSchedule' => $repaymentSchedule,
        ];
    }

    /**
     * @param Loans $loan
     *
     * @return string[]
     */
    private function getContentData(Loans $loan): array
    {
        if (empty($loan->getUnderlyingContract()->getBlockSlug())) {
            return [];
        }

        $content = [];
        $block   = $this->entityManager
            ->getRepository(Blocs::class)
            ->findOneBy(['slug' => $loan->getUnderlyingContract()->getBlockSlug()])
        ;

        $blockElements = $this->entityManager
            ->getRepository(BlocsElements::class)
            ->findBy(['idBloc' => $block->getIdBloc()])
        ;

        $elementRepository = $this->entityManager->getRepository(Elements::class);

        foreach ($blockElements as $blockElement) {
            $element                      = $elementRepository->find($blockElement->getIdElement());
            $content[$element->getSlug()] = $blockElement->getValue();
        }

        return $content;
    }

    /**
     * @param Loans $loan
     *
     * @return array
     */
    private function getBorrowerData(Loans $loan): array
    {
        $company = $loan->getTranche()->getIdCompany();

        return [
            'siren'           => $company->getSiren(),
            'name'            => $company->getName(),
            'legalStatus'     => $company->getForme(),
            'capitalStock'    => $this->numberFormatter->format(max($company->getCapital(), 0)),
            'commercialCourt' => $company->getTribunalCom(),
            'activity'        => $company->getActivite(),
            'address'         => [
                'address' => $company->getIdAddress() ? $company->getIdAddress()->getAddress() : '',
                'zip'     => $company->getIdAddress() ? $company->getIdAddress()->getZip() : '',
                'city'    => $company->getIdAddress() ? $company->getIdAddress()->getCity() : '',
            ],
        ];
    }

    /**
     * @param Loans $loan
     *
     * @throws Exception
     * @throws NonUniqueResultException
     *
     * @return array
     */
    private function getLenderData(Loans $loan): array
    {
        $client = $loan->getWallet()->getIdClient();

        if ($loan->getTransfer()) {
            $client = $this->loanManager->getFirstOwner($loan);
        }

        if ($client->isNaturalPerson()) {
            $address = $client->getIdAddress();
        } else {
            $company = $this->entityManager
                ->getRepository(Companies::class)
                ->findOneBy(['idClientOwner' => $client])
            ;

            $address = $company->getIdAddress();
        }

        if (null === $address) {
            $this->logger->warning('Client ' . $client->getIdClient() . ' has no validated main address. Only validated addresses should be used in official documents', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
            ]);

            if ($client->isNaturalPerson()) {
                $address = $this->entityManager
                    ->getRepository(ClientAddress::class)
                    ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS)
                ;
            } else {
                $address = $this->entityManager
                    ->getRepository(CompanyAddress::class)
                    ->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS)
                ;
            }
        }

        if (null === $address) {
            throw new Exception(sprintf('No address found for client %s', $client->getIdClient()));
        }

        $lenderData = [
            'isNaturalPerson' => $client->isNaturalPerson(),
            'firstName'       => $client->getFirstName(),
            'lastName'        => $client->getLastName(),
            'birthDate'       => $client->getDateOfBirth()->format('d/m/Y'),
            'address'         => [
                'address' => $address->getAddress(),
                'zip'     => $address->getZip(),
                'city'    => $address->getCity(),
            ],
        ];

        if (false === $client->isNaturalPerson()) {
            $lenderData['company'] = [
                'siren'           => $company->getSiren(),
                'name'            => $company->getName(),
                'legalStatus'     => $company->getForme(),
                'capitalStock'    => $this->numberFormatter->format(max($company->getCapital(), 0)),
                'commercialCourt' => $company->getTribunalCom(),
            ];
        }

        return $lenderData;
    }
}
