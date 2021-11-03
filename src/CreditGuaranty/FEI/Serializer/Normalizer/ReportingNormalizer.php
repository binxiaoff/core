<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use LogicException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/**
 * This normalizer is used to process some reporting data that cannot be done in sql
 * (see ReportingExtractor.php for more details,
 * where selects, joins and clauses are generated from reporting template fields).
 */
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

            // set virtual fields value
            foreach (FieldAlias::VIRTUAL_FIELDS as $fieldAlias) {
                if (\array_key_exists($fieldAlias, $row)) {
                    $row[$fieldAlias] = $this->getVirtualFieldValue($financingObject->getReservation(), $fieldAlias);
                }
            }

            // special case for transforming decimal to percentage
            // TODO: if there is another one decimal field, create a constant in FieldAlias file and loop through it
            if (false === empty($row[FieldAlias::AID_INTENSITY])) {
                $row[FieldAlias::AID_INTENSITY] = ($row[FieldAlias::AID_INTENSITY] * 100) . ' %';
            }

            // concatenate all investment thematics of project here because it was hard to do in sql all at once
            if (\array_key_exists(FieldAlias::INVESTMENT_THEMATIC, $row)) {
                $investmentThematics = $financingObject
                    ->getReservation()
                    ->getProject()
                    ->getInvestmentThematics()
                    ->map(fn (ProgramChoiceOption $pco) => $pco->getDescription())
                    ->toArray()
                ;

                $row[FieldAlias::INVESTMENT_THEMATIC] = \implode(' ; ', $investmentThematics);
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
