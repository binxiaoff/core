<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use DateTime;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
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

    public function transform(array $item): array
    {
        foreach ($item as $reportingItem => $reportingValue) {
            if (null === $reportingValue) {
                continue;
            }

            if (\in_array($reportingItem, \array_values(FieldAlias::DATE_FIELDS), true)) {
                $item[$reportingItem] = $this->transformToDate($reportingValue);

                continue;
            }

            //There is no transcodification for this field
            if (FieldAlias::AID_INTENSITY === $reportingItem && false === \ctype_alpha($reportingValue)) {
                $item[$reportingItem] = $this->transformToPercentage($reportingValue) . '%';

                continue;
            }

            if (\is_string($reportingValue) && false !== \mb_strpos($reportingValue, 'EUR')) {
                $item[$reportingItem] = $this->transformToMoney($reportingValue);

                continue;
            }

            if (FieldAlias::RESERVATION_STATUS === $reportingItem) {
                $item[$reportingItem] = $this->transformReservationStatusToString($reportingValue);
            }
        }

        return $item;
    }

    public function translateField(array $fields): array
    {
        foreach ($fields as $key => $field) {
            $fieldTranslate = $this->translator->trans('field.' . $field);

            $fields[$key] = $fieldTranslate;
        }

        return $fields;
    }

    public function transformVirtualFieldValue(Reservation $reservation, string $fieldAlias)
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
            $value .= ' EUR';
        }

        return $value;
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
}
