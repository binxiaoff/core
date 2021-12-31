<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FinancingObjectImportDataValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof FinancingObjectImportData) {
            throw new UnexpectedTypeException($constraint, FinancingObjectImportData::class);
        }

        $headerDiff = \array_diff(FinancingObjectUpdater::IMPORT_FILE_COLUMNS, \array_keys($value));
        if (\count($headerDiff) > 0) {
            $this->context->buildViolation(
                'CreditGuaranty.Reservation.financingObject.import.headerEntries'
            )
                ->setParameter('{{missingColumn}}', \implode(', ', $headerDiff))
                ->setCode(FinancingObjectImportData::INVALID_DATA_ERROR)
                ->addViolation()
            ;

            return;
        }

        [
            FinancingObjectUpdater::GREEN_COLUMN     => $green,
            FinancingObjectUpdater::OPERATION_COLUMN => $operationNumber,
            FinancingObjectUpdater::CRD_COLUMN       => $crd,
            FinancingObjectUpdater::MATURITY_COLUMN  => $maturity,
            'line'                                   => $line,
        ] = $value;

        if (false === \is_numeric($green) || $green < 0) {
            $this->context->buildViolation(
                'CreditGuaranty.Reservation.financingObject.import.green'
            )
                ->setParameter('{{line}}', (string) $line)
                ->atPath(FinancingObjectUpdater::GREEN_COLUMN)
                ->setCode(FinancingObjectImportData::INVALID_DATA_ERROR)
                ->addViolation()
            ;
        }

        if (false === \is_numeric($operationNumber) || $operationNumber < 0) {
            $this->context->buildViolation(
                'CreditGuaranty.Reservation.financingObject.import.operation'
            )
                ->setParameter('{{line}}', (string) $line)
                ->atPath(FinancingObjectUpdater::OPERATION_COLUMN)
                ->setCode(FinancingObjectImportData::INVALID_DATA_ERROR)
                ->addViolation()
            ;
        }

        if (false === \is_numeric($crd) || $crd < 0) {
            $this->context->buildViolation(
                'CreditGuaranty.Reservation.financingObject.import.crd'
            )
                ->setParameter('{{line}}', (string) $line)
                ->atPath(FinancingObjectUpdater::CRD_COLUMN)
                ->setCode(FinancingObjectImportData::INVALID_DATA_ERROR)
                ->addViolation()
            ;
        }

        if (false === \is_numeric($maturity) || $maturity < 0) {
            $this->context->buildViolation(
                'CreditGuaranty.Reservation.financingObject.import.maturity'
            )
                ->setParameter('{{line}}', (string) $line)
                ->atPath(FinancingObjectUpdater::MATURITY_COLUMN)
                ->setCode(FinancingObjectImportData::INVALID_DATA_ERROR)
                ->addViolation()
            ;
        }
    }
}
