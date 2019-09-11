<?php

declare(strict_types=1);

namespace Unilend\Service\Foncaris;

use Doctrine\Common\Persistence\ManagerRegistry;
use League\Flysystem\{FileExistsException, FilesystemInterface};
use NumberFormatter;
use PhpOffice\PhpSpreadsheet\{Exception as PhpSpreadsheetException, Spreadsheet, Style\Alignment, Style\Border, Style\Fill, Worksheet\Worksheet,
    Writer\Exception as PhpSpreadsheetWriterException, Writer\Xlsx};
use Unilend\Entity\{FoncarisRequest, Interfaces\FileStorageInterface, Tranche, TrancheAttribute};
use Unilend\Repository\ConstantList\{FoncarisFundingTypeRepository, FoncarisSecurityRepository};
use Unilend\Service\Document\AbstractDocumentGenerator;
use URLify;

class GuaranteeRequestGenerator extends AbstractDocumentGenerator
{
    private const FILE_PREFIX = 'demande garantie CALS - ';
    private const PATH        = 'foncaris';

    private const START_COLUMN = 'A';
    private const START_ROW    = 1;

    /** @var FoncarisFundingTypeRepository */
    private $foncarisFundingTypeRepository;
    /** @var FoncarisSecurityRepository */
    private $foncarisSecurityRepository;
    /** @var NumberFormatter */
    private $currencyFormatterNoDecimal;
    /** @var NumberFormatter */
    private $percentageFormatter;
    /** @var FilesystemInterface */
    private $generatedDocumentFilesystem;

    /**
     * @param FilesystemInterface           $generatedDocumentFilesystem
     * @param FoncarisFundingTypeRepository $foncarisFundingTypeRepository
     * @param FoncarisSecurityRepository    $foncarisSecurityRepository
     * @param NumberFormatter               $currencyFormatterNoDecimal
     * @param NumberFormatter               $percentageFormatter
     * @param ManagerRegistry               $managerRegistry
     */
    public function __construct(
        FilesystemInterface $generatedDocumentFilesystem,
        FoncarisFundingTypeRepository $foncarisFundingTypeRepository,
        FoncarisSecurityRepository $foncarisSecurityRepository,
        NumberFormatter $currencyFormatterNoDecimal,
        NumberFormatter $percentageFormatter,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($managerRegistry);

        $this->foncarisFundingTypeRepository = $foncarisFundingTypeRepository;
        $this->foncarisSecurityRepository    = $foncarisSecurityRepository;
        $this->currencyFormatterNoDecimal    = $currencyFormatterNoDecimal;
        $this->percentageFormatter           = $percentageFormatter;
        $this->generatedDocumentFilesystem   = $generatedDocumentFilesystem;
    }

    /**
     * @param FoncarisRequest|FileStorageInterface $foncarisRequest
     *
     * @throws PhpSpreadsheetException
     * @throws PhpSpreadsheetWriterException
     * @throws FileExistsException
     */
    public function generateDocument(FileStorageInterface $foncarisRequest): void
    {
        if (FoncarisRequest::FONCARIS_GUARANTEE_NEED !== $foncarisRequest->getChoice()) {
            return;
        }

        if ($this->generatedDocumentFilesystem->has($this->getFilePath($foncarisRequest))) {
            return;
        }

        $project = $foncarisRequest->getProject();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $currentRow     = self::START_ROW;
        $sheetWidth     = count($project->getTranches()) + 2;
        $sheetEndColumn = $this->getColumnByOffset(self::START_COLUMN, --$sheetWidth);

        //Title
        $sheet->mergeCells(self::START_COLUMN . $currentRow . ':' . $sheetEndColumn . $currentRow);
        $sheet->setCellValue(self::START_COLUMN . $currentRow, 'Demande de garantie FONCARIS  par un acheteur');
        $sheet->getStyle(self::START_COLUMN . $currentRow)->applyFromArray($this->getTitleStyle());

        //Group
        $currentRow  = $this->localizeCurrentRow($sheet);
        $sectionData = [
            ['Nom du Groupe de Risque', ''],
            ['ID AGORA du groupe ( = RICOS = TIGRE = TCA)', ''],
        ];
        $this->fillSection($sheet, 'Groupe', $sectionData, $sheetEndColumn, $currentRow, $this->getGroupSectionStyle());

        //Borrower
        $currentRow  = $this->localizeCurrentRow($sheet);
        $sectionData = [
            ['Nom Emprunteur', $project->getBorrowerCompany()->getName()],
            ['SIREN', $project->getBorrowerCompany()->getSiren()],
            ['ID AGORA de l\'emprunteur ( = RICOS = TIGRE = TCA)', ''],
        ];
        $this->fillSection($sheet, 'Emprunteur', $sectionData, $sheetEndColumn, $currentRow, $this->getBorrowerSectionStyle());

        //Regional bank
        $currentRow  = $this->localizeCurrentRow($sheet);
        $sectionData = [
            ['Nom de la CR demandeuse', $project->getSubmitterCompany()->getName()],
            ['Nom du contact dans la CR demandeuse', $project->getSubmitterClient()->getFirstName() . ' ' . $project->getSubmitterClient()->getLastName()],
            ['Email du contact dans la CR demandeuse', $project->getSubmitterClient()->getEmail()],
        ];
        $this->fillSection($sheet, 'CR', $sectionData, $sheetEndColumn, $currentRow, $this->getRegionalBankSectionStyle());

        //Operation (tranche)
        $currentRow     = $this->localizeCurrentRow($sheet);
        $greenIds       = ['IG GREEN'];
        $fundingTypes   = ['Nature du financement'];
        $fundingObjects = ['Objet du financement'];
        $implementation = ['Mise en place'];
        $amounts        = ['Montant soumis à garantie'];
        $durations      = ['Échéance ou durée (en mois)'];
        $step           = ['Palier'];
        $amortizables   = ['Amortissable'];
        $margins        = ['Marge'];
        $securities     = ['Sureté'];

        foreach ($project->getTranches() as $tranche) {
            $greenIds[]       = $this->getGreenId($tranche);
            $fundingTypes[]   = $this->getFoncarisFundingType($tranche);
            $fundingObjects[] = $tranche->getName();
            $implementation[] = 'N';
            $amounts[]        = $this->currencyFormatterNoDecimal->formatCurrency((float) $tranche->getMoney()->getAmount(), $tranche->getMoney()->getCurrency());
            $durations[]      = $tranche->getDuration();
            $step[]           = 'N';

            $amortizable = 'N';
            $margin      = 'N/A';
            if (Tranche::REPAYMENT_TYPE_AMORTIZABLE === $tranche->getRepaymentType()) {
                $amortizable = 'O';
                $margin      = '';
                if ($tranche->getRate()->getMargin()) {
                    $margin = $this->percentageFormatter->format($tranche->getRate()->getMargin());
                }
            }

            $amortizables[] = $amortizable;
            $margins[]      = $margin;
            $securities[]   = $this->getFoncarisFundingSecurity($tranche);
        }

        $sectionData = [
            $greenIds,
            $fundingTypes,
            $fundingObjects,
            $implementation,
            ['Date de signature du contrat'],
            $amounts,
            $durations,
            $step,
            $amortizables,
            $margins,
            $securities,
        ];
        $this->fillSection($sheet, 'Opération', $sectionData, $sheetEndColumn, $currentRow, $this->getOperationSectionStyle(), false);

        //Other
        $currentRow  = $this->localizeCurrentRow($sheet);
        $sectionData = [
            ['Commentaire', $foncarisRequest->getComment()],
        ];
        $this->fillSection($sheet, 'Autre', $sectionData, $sheetEndColumn, $currentRow, $this->getOtherSectionStyle());

        foreach (range(self::START_COLUMN, $sheet->getHighestDataColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        $this->generatedDocumentFilesystem->write($this->getFilePath($foncarisRequest), $excelOutput);
    }

    /**
     * @param FoncarisRequest|FileStorageInterface $foncarisRequest
     *
     * @return string
     */
    protected function getFileName(FileStorageInterface $foncarisRequest): string
    {
        return self::FILE_PREFIX . URLify::filter($foncarisRequest->getProject()->getBorrowerCompany()->getName()) . '-ISOLE.xlsx';
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

    /**
     * @param string $column
     * @param int    $offset
     *
     * @return string
     */
    private function getColumnByOffset(string $column, int $offset): string
    {
        return chr(ord($column) + $offset);
    }

    /**
     * @param Worksheet $sheet
     * @param string    $sectionTitle
     * @param array     $sectionData
     * @param string    $sheetEndColumn
     * @param int       $currentRow
     * @param array     $styleArray
     * @param bool      $mergeValueCells
     *
     * @throws PhpSpreadsheetException
     */
    private function fillSection(
        Worksheet $sheet,
        string $sectionTitle,
        array $sectionData,
        string $sheetEndColumn,
        int $currentRow,
        array $styleArray,
        bool $mergeValueCells = true
    ) {
        $rowsCount   = count($sectionData);
        $labelColumn = $this->getColumnByOffset(self::START_COLUMN, 1);
        $valueColumn = $this->getColumnByOffset(self::START_COLUMN, 2);

        $sheet->getStyle(self::START_COLUMN . $currentRow . ':' . $sheetEndColumn . ($currentRow + $rowsCount - 1))->applyFromArray($styleArray);
        $sheet->mergeCells(self::START_COLUMN . $currentRow . ':' . self::START_COLUMN . ($currentRow + $rowsCount - 1));
        $sheet->getStyle(self::START_COLUMN . $currentRow . ':' . self::START_COLUMN . $currentRow)->applyFromArray($this->getSectionTitleStyle());
        $sheet->setCellValue(self::START_COLUMN . $currentRow, $sectionTitle);

        $sheet->fromArray($sectionData, null, $labelColumn . $currentRow);

        if ($mergeValueCells) {
            for ($i = 0; $i < $rowsCount; ++$i) {
                $sheet->mergeCells($valueColumn . $currentRow . ':' . $sheetEndColumn . $currentRow);
                ++$currentRow;
            }
        }
    }

    /**
     * @return array
     */
    private function getTitleStyle(): array
    {
        return [
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
    }

    /**
     * @return array
     */
    private function getSectionTitleStyle(): array
    {
        return [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];
    }

    /**
     * @return array
     */
    private function getGroupSectionStyle(): array
    {
        return $this->generateSectionStyle('FFE2EFDA');
    }

    /**
     * @return array
     */
    private function getBorrowerSectionStyle(): array
    {
        return $this->generateSectionStyle('FFFFF2CC');
    }

    /**
     * @return array
     */
    private function getRegionalBankSectionStyle(): array
    {
        return $this->generateSectionStyle('FFEDEDED');
    }

    /**
     * @return array
     */
    private function getOperationSectionStyle(): array
    {
        return $this->generateSectionStyle('FFFCE4D6');
    }

    /**
     * @return array
     */
    private function getOtherSectionStyle(): array
    {
        return $this->generateSectionStyle('FFC8D9EF');
    }

    /**
     * @param string $backgroundColor
     *
     * @return array
     */
    private function generateSectionStyle(string $backgroundColor): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => $backgroundColor,
                ],
            ],
        ];
    }

    /**
     * @param Worksheet $worksheet
     *
     * @return int
     */
    private function localizeCurrentRow(Worksheet $worksheet): int
    {
        $currentRow = $worksheet->getHighestDataRow();

        return ++$currentRow;
    }
}
