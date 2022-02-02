<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use KLS\Core\Entity\NafNace;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Constant\ReportingFilter;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use ReflectionException;

trait ReportingTemplateTrait
{
    use PropertyValueTrait;
    use FieldTrait;
    use ProgramTrait;

    /**
     * @throws Exception
     */
    protected function createReportingTemplate(string $name): ReportingTemplate
    {
        return new ReportingTemplate($this->createProgram(), $name, $this->createStaff());
    }

    /**
     * @param Field[]|array $fields
     *
     * @throws ReflectionException
     */
    protected function withMultipleReportingTemplateFields(ReportingTemplate $reportingTemplate, array $fields): void
    {
        $reportingTemplateFields = new ArrayCollection();

        foreach ($fields as $key => $field) {
            $reportingTemplateField = new ReportingTemplateField($reportingTemplate, $field);
            $reportingTemplateField->setPosition((int) $key);
            $reportingTemplateFields->add($reportingTemplateField);
        }

        $this->forcePropertyValue($reportingTemplate, 'reportingTemplateFields', $reportingTemplateFields);
    }

    /**
     * @return Field[]|array
     */
    protected function createFieldsForReportingTemplate(): array
    {
        return [
            $this->createBeneficiaryNameField(),
            $this->createBorrowerTypeField(),
            $this->createLoanNafCodeField(),
            $this->createProgramDurationField(),
            $this->createProjectTotalAmountField(),
            $this->createReservationSigningDateField(),
            $this->createReservationStatusField(),
            $this->createSupportingGenerationsRenewalField(),
            $this->createTotalEsbField(),
        ];
    }

    protected function getFieldAliasQueryMapping(): array
    {
        return [
            FieldAlias::BENEFICIARY_NAME => [
                'mappingOperators'         => [],
                'propertyPathFormatted'    => 'borrower.beneficiaryName',
                'propertyPathNotFormatted' => 'borrower.beneficiaryName',
                'searchable'               => true,
                'joins'                    => [Borrower::class => ['r.borrower', 'borrower']],
            ],
            FieldAlias::BORROWER_TYPE => [
                'mappingOperators'      => [],
                'propertyPathFormatted' => \sprintf(
                    'COALESCE(pco_%s.transcode, pco_%s.description)',
                    FieldAlias::BORROWER_TYPE,
                    FieldAlias::BORROWER_TYPE,
                ),
                'propertyPathNotFormatted' => \sprintf(
                    'COALESCE(pco_%s.transcode, pco_%s.description)',
                    FieldAlias::BORROWER_TYPE,
                    FieldAlias::BORROWER_TYPE
                ),
                'searchable' => true,
                'joins'      => [
                    Borrower::class => [
                        'r.borrower',
                        'borrower',
                    ],
                    FieldAlias::BORROWER_TYPE => [
                        ProgramChoiceOption::class,
                        \sprintf('pco_%s', FieldAlias::BORROWER_TYPE),
                        Join::WITH,
                        \sprintf('pco_%s.id = borrower.borrowerType', FieldAlias::BORROWER_TYPE),
                    ],
                ],
            ],
            FieldAlias::FIRST_RELEASE_DATE => [
                'mappingOperators'      => ReportingFilter::MAPPING_DATE_OPERATORS,
                'propertyPathFormatted' => \sprintf(
                    'DATE_FORMAT(%s, %s)',
                    'financingObjects.firstReleaseDate',
                    '\'%Y-%m-%d\''
                ),
                'propertyPathNotFormatted' => 'financingObjects.firstReleaseDate',
                'searchable'               => false,
                'joins'                    => [],
            ],
            FieldAlias::LOAN_NAF_CODE => [
                'mappingOperators'         => [],
                'propertyPathFormatted'    => \sprintf('pco_naf_nace_%s.naceCode', FieldAlias::LOAN_NAF_CODE),
                'propertyPathNotFormatted' => \sprintf('pco_naf_nace_%s.naceCode', FieldAlias::LOAN_NAF_CODE),
                'searchable'               => true,
                'joins'                    => [
                    FieldAlias::LOAN_NAF_CODE => [
                        ProgramChoiceOption::class,
                        \sprintf('pco_%s', FieldAlias::LOAN_NAF_CODE),
                        Join::WITH,
                        \sprintf('pco_%s.id = financingObjects.loanNafCode', FieldAlias::LOAN_NAF_CODE),
                    ],
                    FieldAlias::NAF_NACE_FIELDS[FieldAlias::LOAN_NAF_CODE] => [
                        NafNace::class,
                        \sprintf('pco_naf_nace_%s', FieldAlias::LOAN_NAF_CODE),
                        Join::WITH,
                        \sprintf(
                            'pco_%s.description = pco_naf_nace_%s.nafCode',
                            FieldAlias::LOAN_NAF_CODE,
                            FieldAlias::LOAN_NAF_CODE,
                        ),
                    ],
                ],
            ],
            FieldAlias::PROGRAM_DURATION => [
                'mappingOperators'         => [],
                'propertyPathFormatted'    => 'program.guarantyDuration',
                'propertyPathNotFormatted' => 'program.guarantyDuration',
                'searchable'               => false,
                'joins'                    => [],
            ],
            FieldAlias::PROJECT_TOTAL_AMOUNT => [
                'mappingOperators'      => [],
                'propertyPathFormatted' => \sprintf(
                    'CONCAT(%s.amount, \' \', %s.currency)',
                    'project.fundingMoney',
                    'project.fundingMoney'
                ),
                'searchable'               => false,
                'propertyPathNotFormatted' => 'project.fundingMoney.amount',
                'joins'                    => [Project::class => ['r.project', 'project']],
            ],
            FieldAlias::RESERVATION_SIGNING_DATE => [
                'mappingOperators'         => ReportingFilter::MAPPING_RANGE_OPERATORS,
                'propertyPathFormatted'    => \sprintf('DATE_FORMAT(%s, %s)', 'r.signingDate', '\'%Y-%m-%d\''),
                'propertyPathNotFormatted' => 'r.signingDate',
                'searchable'               => false,
                'joins'                    => [],
            ],
            FieldAlias::RESERVATION_STATUS => [
                'mappingOperators'         => [],
                'propertyPathFormatted'    => \sprintf('rs_%s.status', FieldAlias::RESERVATION_STATUS),
                'propertyPathNotFormatted' => \sprintf('rs_%s.status', FieldAlias::RESERVATION_STATUS),
                'searchable'               => false,
                'joins'                    => [
                    FieldAlias::RESERVATION_STATUS => [
                        ReservationStatus::class,
                        \sprintf('rs_%s', FieldAlias::RESERVATION_STATUS),
                        Join::WITH,
                        \sprintf('rs_%s.id = r.currentStatus', FieldAlias::RESERVATION_STATUS),
                    ],
                ],
            ],
            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => [
                'mappingOperators'         => [],
                'propertyPathFormatted'    => 'financingObjects.supportingGenerationsRenewal',
                'propertyPathNotFormatted' => 'financingObjects.supportingGenerationsRenewal',
                'searchable'               => false,
                'joins'                    => [],
            ],
            FieldAlias::TOTAL_GROSS_SUBSIDY_EQUIVALENT => [
                'mappingOperators'         => [],
                'propertyPathFormatted'    => '\'\'',
                'propertyPathNotFormatted' => '\'\'',
                'searchable'               => false,
                'joins'                    => [Project::class => ['r.project', 'project']],
            ],
        ];
    }

    protected function getFilterClauseByKey(string $key): array
    {
        $date1 = new DateTime('2021-01-01');
        $date2 = new DateTime('-12 MONTH');

        switch ($key) {
            case ReportingFilter::FILTER_SEARCH:
                return [
                    'expression' => $this->getSearchExpression($this->createFieldsForReportingTemplate()),
                    'parameter'  => ['search', '%search%'],
                ];

            case ReportingFilter::FILTER_REPORTING_DATES:
                return [
                    'expression' => 'DATE_FORMAT(financingObjects.reportingFirstDate, \'%Y-%m-%d\') IS NULL ' .
                        'OR DATE_FORMAT(financingObjects.reportingLastDate, \'%Y-%m-%d\') IS NULL ' .
                        'OR DATE_FORMAT(financingObjects.reportingValidationDate, \'%Y-%m-%d\') IS NULL',
                    'parameter' => [],
                ];

            case FieldAlias::FIRST_RELEASE_DATE:
                return [
                    'expression' => 'DATE_FORMAT(financingObjects.firstReleaseDate, \'%Y-%m-%d\')' .
                        ' > :first_release_date_value',
                    'parameter' => ['first_release_date_value', $date1->format('Y-m-d')],
                ];

            case FieldAlias::RESERVATION_SIGNING_DATE:
                return [
                    'expression' => 'DATE_FORMAT(r.signingDate, \'%Y-%m-%d\') >= :reservation_signing_date_value',
                    'parameter'  => ['reservation_signing_date_value', $date2->format('Y-m-d')],
                ];

            case FieldAlias::LOAN_REMAINING_CAPITAL:
                return [
                    'expression' => 'financingObjects.remainingCapital.amount = :loan_remaining_capital_value',
                    'parameter'  => ['loan_remaining_capital_value', '42'],
                ];

            default:
                return [];
        }
    }

    /**
     * @param Field[]|array $fields
     */
    private function getSearchExpression(array $fields): string
    {
        $fieldMappings = $this->getFieldAliasQueryMapping();
        $propertyPaths = [];

        foreach ($fields as $field) {
            $mapping = $fieldMappings[$field->getFieldAlias()];
            if ($mapping['searchable']) {
                $propertyPaths[] = \sprintf('%s LIKE :search', $mapping['propertyPathNotFormatted']);
            }
        }

        return \implode(' OR ', $propertyPaths);
    }
}
