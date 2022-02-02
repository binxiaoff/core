<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use DateTime;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use LogicException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportingTransformer
{
    private TranslatorInterface $translator;
    private FieldRepository $fieldRepository;
    private ReservationAccessor $reservationAccessor;

    public function __construct(
        TranslatorInterface $translator,
        FieldRepository $fieldRepository,
        ReservationAccessor $reservationAccessor
    ) {
        $this->translator          = $translator;
        $this->fieldRepository     = $fieldRepository;
        $this->reservationAccessor = $reservationAccessor;
    }

    public function transform(array $data, FinancingObject $financingObject): array
    {
        $reservation = $financingObject->getReservation();

        foreach ($data as $reportingKey => $reportingValue) {
            if (null === $reportingValue) {
                continue;
            }

            if (\in_array($reportingKey, \array_values(FieldAlias::DATE_FIELDS), true)) {
                $data[$reportingKey] = $this->transformToDate($reportingValue);

                continue;
            }

            //There is no transcodification for this field
            if (FieldAlias::AID_INTENSITY === $reportingKey && false === \ctype_alpha($reportingValue)) {
                $data[$reportingKey] = $this->transformToPercentage($reportingValue) . '%';

                continue;
            }

            if (\is_string($reportingValue) && false !== \mb_strpos($reportingValue, 'EUR')) {
                $data[$reportingKey] = $this->transformToMoney($reportingValue);

                continue;
            }

            if (FieldAlias::RESERVATION_STATUS === $reportingKey) {
                $data[$reportingKey] = $this->transformReservationStatusToString($reportingValue);

                continue;
            }

            if (\in_array($reportingKey, FieldAlias::VIRTUAL_FIELDS, true)) {
                $data[$reportingKey] = $this->transformVirtualFieldValue($reservation, $reportingKey);

                continue;
            }

            if (FieldAlias::INVESTMENT_THEMATIC === $reportingKey) {
                $data[$reportingKey] = $this->transformInvestmentThematics($reservation);
            }
        }

        return $data;
    }

    public function translateField(array $fields): array
    {
        foreach ($fields as $key => $field) {
            $fieldTranslate = $this->translator->trans('field.' . $field);

            $fields[$key] = $fieldTranslate;
        }

        return $fields;
    }

    private function transformToDate(string $reportingValue): string
    {
        return DateTime::createFromFormat('Y-m-d', $reportingValue)->format('d/m/Y');
    }

    private function transformToPercentage($reportingValue): float
    {
        return (float) $reportingValue * 100;
    }

    private function transformToMoney($reportingValue): string
    {
        return \str_replace('EUR', '€', $reportingValue);
    }

    private function transformReservationStatusToString($reportingValue): string
    {
        return $this->translator->trans('reservation-status.' . $reportingValue);
    }

    private function transformVirtualFieldValue(Reservation $reservation, string $fieldAlias)
    {
        /** @var Field|null $field */
        $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

        if (false === ($field instanceof Field)) {
            throw new LogicException(
                \sprintf('Impossible to generate reporting, field with alias %s is not found', $fieldAlias)
            );
        }

        $value = $this->reservationAccessor->getEntity($reservation, $field);

        if (false === empty($field->getPropertyPath())) {
            $value = $this->reservationAccessor->getValue($value, $field);
        }

        if (FieldAlias::TOTAL_GROSS_SUBSIDY_EQUIVALENT === $fieldAlias) {
            $value .= ' €';
        }

        return $value;
    }

    private function transformInvestmentThematics(Reservation $reservation): string
    {
        $investmentThematics = $reservation->getProject()->getInvestmentThematics()
            ->map(static fn (ProgramChoiceOption $pco) => $pco->getTranscode() ?? $pco->getDescription())
            ->toArray()
        ;

        return \implode(' ; ', $investmentThematics);
    }
}
