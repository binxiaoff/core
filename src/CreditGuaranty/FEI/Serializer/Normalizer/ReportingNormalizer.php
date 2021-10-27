<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use LogicException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ReportingNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private IriConverterInterface $iriConverter;
    private FieldRepository $fieldRepository;
    private FinancingObjectRepository $financingObjectRepository;
    private ReservationAccessor $reservationAccessor;

    public function __construct(
        IriConverterInterface $iriConverter,
        FieldRepository $fieldRepository,
        FinancingObjectRepository $financingObjectRepository,
        ReservationAccessor $reservationAccessor
    ) {
        $this->iriConverter              = $iriConverter;
        $this->financingObjectRepository = $financingObjectRepository;
        $this->fieldRepository           = $fieldRepository;
        $this->reservationAccessor       = $reservationAccessor;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            $data instanceof Paginator
            && !isset($context[static::ALREADY_CALLED])
            && ReportingTemplate::class === $context['resource_class']
            && 'reporting'              === $context['item_operation_name']
        ;
    }

    /**
     * @param Paginator $object
     *
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = $object->getQuery()->getResult();

        foreach ($data as &$row) {
            $financingObject = null;

            // transform id into iri
            if (false === empty($row['id_financing_object'])) {
                $financingObject = $this->financingObjectRepository->find($row['id_financing_object']);

                if (false === ($financingObject instanceof FinancingObject)) {
                    throw new LogicException(
                        \sprintf(
                            'Impossible to generate reporting, FinancingObject id %s is not found',
                            $row['id_financing_object']
                        )
                    );
                }

                $row['id_financing_object'] = $this->iriConverter->getIriFromItem($financingObject);
            }

            // add value to virtual fields
            foreach (FieldAlias::VIRTUAL_FIELDS as $fieldAlias) {
                if (\array_key_exists($fieldAlias, $row)) {
                    $row[$fieldAlias] = $this->getVirtualFieldValue($financingObject->getReservation(), $fieldAlias);
                }
            }

            // format dates
            foreach (FieldAlias::DATE_FIELDS as $fieldAlias) {
                if (false === empty($row[$fieldAlias])) {
                    $row[$fieldAlias] = ($row[$fieldAlias])->format('Y-m-d');
                }
            }

            // special case for transforming decimal to percentage
            // TODO: if there is another one decimal field, create a constant in FieldAlias file and loop through it
            if (false === empty($row[FieldAlias::AID_INTENSITY])) {
                $row[FieldAlias::AID_INTENSITY] = ($row[FieldAlias::AID_INTENSITY] * 100) . ' %';
            }

            // delete naf code fields to keep only its nace code
            // because CASA only want nace code in generating reporting
            foreach (FieldAlias::NAF_NACE_FIELDS as $fieldAlias => $relatedFieldAlias) {
                if (\array_key_exists($fieldAlias, $row) && \array_key_exists($relatedFieldAlias, $row)) {
                    unset($row[$fieldAlias]);
                }
            }
        }

        return $data;
    }

    private function getVirtualFieldValue(Reservation $reservation, string $fieldAlias)
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
}
