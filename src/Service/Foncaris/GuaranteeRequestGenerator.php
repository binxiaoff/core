<?php

declare(strict_types=1);

namespace Unilend\Service\Foncaris;

use Doctrine\Common\Persistence\ManagerRegistry;
use NumberFormatter;
use PhpOffice\PhpSpreadsheet\{Exception as PhpSpreadsheetException, Spreadsheet, Writer\Exception as PhpSpreadsheetWriterException, Writer\Xlsx};
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{FoncarisRequest, Interfaces\FileStorageInterface, Project, Tranche, TrancheAttribute};
use Unilend\Repository\ConstantList\{FoncarisFundingTypeRepository, FoncarisSecurityRepository};
use Unilend\Service\Document\AbstractDocumentGenerator;

class GuaranteeRequestGenerator extends AbstractDocumentGenerator
{
    private const FILE_PREFIX = 'demande-garantie';
    private const PATH        = 'foncaris';

    /** @var FoncarisFundingTypeRepository */
    private $foncarisFundingTypeRepository;
    /** @var FoncarisSecurityRepository */
    private $foncarisSecurityRepository;
    /** @var NumberFormatter */
    private $currencyFormatterNoDecimal;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param string                        $documentRootDirectory
     * @param FoncarisFundingTypeRepository $foncarisFundingTypeRepository
     * @param FoncarisSecurityRepository    $foncarisSecurityRepository
     * @param NumberFormatter               $currencyFormatterNoDecimal
     * @param TranslatorInterface           $translator
     * @param ManagerRegistry               $managerRegistry
     */
    public function __construct(
        string $documentRootDirectory,
        FoncarisFundingTypeRepository $foncarisFundingTypeRepository,
        FoncarisSecurityRepository $foncarisSecurityRepository,
        NumberFormatter $currencyFormatterNoDecimal,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($documentRootDirectory, $managerRegistry);

        $this->foncarisFundingTypeRepository = $foncarisFundingTypeRepository;
        $this->foncarisSecurityRepository    = $foncarisSecurityRepository;
        $this->currencyFormatterNoDecimal    = $currencyFormatterNoDecimal;
        $this->translator                    = $translator;
    }

    /**
     * @param FoncarisRequest|FileStorageInterface $foncarisRequest
     *
     * @throws PhpSpreadsheetException
     * @throws PhpSpreadsheetWriterException
     */
    public function generateDocument(FileStorageInterface $foncarisRequest): void
    {
        if (FoncarisRequest::FONCARIS_GUARANTEE_NEED !== $foncarisRequest->getChoice()) {
            return;
        }

        $project = $foncarisRequest->getProject();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Demande de Garantie');

        $sheet->setCellValue('A3', 'Nom Emprunteur');
        $sheet->setCellValue('B3', $project->getBorrowerCompany()->getName());

        $sheet->setCellValue('C3', 'SIREN');
        $sheet->setCellValue('D3', $project->getBorrowerCompany()->getSiren());

        $sheet->setCellValue('A4', 'La CR demandeuse');
        $sheet->setCellValue('B4', $project->getArranger()->getCompany()->getName());

        $sheet->setCellValue('A5', 'Contact dans la CR demandeuse');
        $sheet->setCellValue('B5', sprintf(
            '%s %s (%s)',
            $project->getSubmitterClient()->getFirstName(),
            $project->getSubmitterClient()->getLastName(),
            $project->getSubmitterClient()->getEmail()
        ));

        $trancheNb = count($project->getTranches());
        $sheet->setCellValue('A7', 'Opération');
        $sheet->setCellValue('B7', sprintf('%s / %s', $trancheNb, $trancheNb));

        $sheet->setCellValue('A8', 'ID Green');
        $sheet->setCellValue('B8', 'Nature du financement');
        $sheet->setCellValue('C8', 'Objet du financement');
        $sheet->setCellValue('D8', 'Mise en place');
        $sheet->setCellValue('E8', 'Montant soumis à garantie');
        $sheet->setCellValue('F8', 'Échéance ou durée (en mois)');
        $sheet->setCellValue('G8', 'Palier');
        $sheet->setCellValue('H8', 'Amortissable');
        $sheet->setCellValue('I8', 'Taux');
        $sheet->setCellValue('J8', 'Date de 1ère échéance');
        $sheet->setCellValue('K8', 'Periodicité capitale (mois)');
        $sheet->setCellValue('L8', 'Periodicité intérêts (mois)');
        $sheet->setCellValue('M8', 'Sureté');
        $sheet->setCellValue('N8', 'Taux de garantie Foncaris demandé');

        $row = 9;
        foreach ($project->getTranches() as $tranche) {
            $column = 1;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->getGreenId($tranche));
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->getFoncarisFundingType($tranche));
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, $tranche->getName());
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, 'N');
            ++$column;

            $sheet->setCellValueByColumnAndRow(
                $column,
                $row,
                $this->currencyFormatterNoDecimal->formatCurrency((float) $tranche->getMoney()->getAmount(), $tranche->getMoney()->getCurrency())
            );
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, $tranche->getDuration());
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, 'N');
            ++$column;

            $isAmortizable = Tranche::REPAYMENT_TYPE_AMORTIZABLE === $tranche->getRepaymentType();
            $sheet->setCellValueByColumnAndRow($column, $row, $isAmortizable ? 'O' : 'N');
            ++$column;

            $rate = 'N/A';
            if ($isAmortizable && $tranche->getRate()->getIndexType() && $tranche->getRate()->getMargin()) {
                $indexType = $this->translator->trans('interest-rate-index.index_' . mb_strtolower($tranche->getRate()->getIndexType()));
                $rate      = $indexType . ' + ' . $tranche->getRate()->getMargin() . ($tranche->getRate()->getFloor() ? ' flooré à ' . $tranche->getRate()->getFloor() : '');
            }
            $sheet->setCellValueByColumnAndRow($column, $row, $rate);
            ++$column;

            if ($isAmortizable) {
                $sheet->setCellValueByColumnAndRow($column, $row, $tranche->getExpectedStartingDate() ? $tranche->getExpectedStartingDate()->format('d/m/Y') : '');
            } else {
                $sheet->setCellValueByColumnAndRow($column, $row, 'N/A');
            }
            ++$column;

            if ($isAmortizable) {
                $sheet->setCellValueByColumnAndRow($column, $row, $tranche->getCapitalPeriodicity());
            } else {
                $sheet->setCellValueByColumnAndRow($column, $row, 'N/A');
            }
            ++$column;

            if ($isAmortizable) {
                $sheet->setCellValueByColumnAndRow($column, $row, $tranche->getInterestPeriodicity());
            } else {
                $sheet->setCellValueByColumnAndRow($column, $row, 'N/A');
            }
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->getFoncarisFundingSecurity($tranche));
            ++$column;

            $sheet->setCellValueByColumnAndRow($column, $row, '50%');

            ++$row;
        }

        $writer = new Xlsx($spreadsheet);

        $filePath  = $this->getFilePath($foncarisRequest);
        $directory = dirname($filePath);

        if (false === is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $writer->save($filePath);
    }

    /**
     * @param FoncarisRequest|FileStorageInterface $foncarisRequest
     *
     * @return string
     */
    protected function getFileName(FileStorageInterface $foncarisRequest): string
    {
        return self::FILE_PREFIX . '-' . $foncarisRequest->getProject()->getHash() . '.xlsx';
    }

    /**
     * @param FoncarisRequest|FileStorageInterface $foncarisRequest
     *
     * @return string
     */
    protected function generateRelativeDirectory(FileStorageInterface $foncarisRequest): string
    {
        return self::PATH . DIRECTORY_SEPARATOR . $foncarisRequest->getProject()->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(FileStorageInterface $document): bool
    {
        return $document instanceof FoncarisRequest;
    }

    /**
     * @param Tranche $tranche
     *
     * @return string
     */
    private function getGreenId(Tranche $tranche): string
    {
        $trancheAttribute = $tranche->getTrancheAttributes(TrancheAttribute::ATTRIBUTE_CREDIT_AGRICOLE_GREEN_ID)->first();

        if ($trancheAttribute instanceof TrancheAttribute) {
            return (string) $trancheAttribute->getAttribute()->getValue();
        }

        return '';
    }

    /**
     * @param Tranche $tranche
     *
     * @return string
     */
    private function getFoncarisFundingType(Tranche $tranche): string
    {
        $trancheAttribute = $tranche->getTrancheAttributes(TrancheAttribute::ATTRIBUTE_FONCARIS_FUNDING_TYPE)->first();

        if ($trancheAttribute instanceof TrancheAttribute) {
            $fundingType = $this->foncarisFundingTypeRepository->find($trancheAttribute->getAttribute()->getValue());
            if ($fundingType) {
                return $fundingType->getDescription();
            }
        }

        return '';
    }

    /**
     * @param Tranche $tranche
     *
     * @return string
     */
    private function getFoncarisFundingSecurity(Tranche $tranche): string
    {
        $trancheAttributes         = $tranche->getTrancheAttributes(TrancheAttribute::ATTRIBUTE_FONCARIS_FUNDING_SECURITY);
        $foncarisFundingSecurities = [];

        foreach ($trancheAttributes as $trancheAttribute) {
            $foncarisFundingSecurity = $this->foncarisSecurityRepository->find($trancheAttribute->getAttribute()->getValue());
            if ($foncarisFundingSecurity) {
                $foncarisFundingSecurities[] = $foncarisFundingSecurity->getDescription();
            }
        }

        return implode(', ', $foncarisFundingSecurities);
    }
}
