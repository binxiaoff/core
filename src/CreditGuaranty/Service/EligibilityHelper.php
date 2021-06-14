<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use LogicException;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

class EligibilityHelper
{
    private PropertyAccessorInterface $propertyAccessor;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ProgramChoiceOptionRepository $programChoiceOptionRepository
    ) {
        $this->propertyAccessor              = $propertyAccessor;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
    }

    /**
     * @throws AccessException
     */
    public function getEntity(Reservation $reservation, Field $field)
    {
        $pathParts  = explode('::', $field->getTargetPropertyAccessPath());
        $entityPath = array_shift($pathParts);

        return $this->propertyAccessor->getValue($reservation, $entityPath);
    }

    public function getValue(Program $program, $entity, Field $field)
    {
        $pathParts = explode('::', $field->getTargetPropertyAccessPath());
        array_shift($pathParts);

        $value = $this->propertyAccessor->getValue($entity, implode('.', $pathParts));

        if ($value instanceof NullableMoney || $value instanceof Money) {
            $value = $value->getAmount();
        }

        if (Field::TYPE_LIST !== $field->getType() || $value instanceof ProgramChoiceOption) {
            return $value;
        }

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field,
            'description' => $value,
        ]);

        if (null === $programChoiceOption) {
            throw new LogicException(
                sprintf(
                    'Cannot found programChoiceOption for program #%s, field #%s and description #%s',
                    $program->getId(),
                    $field->getId(),
                    $value
                )
            );
        }

        return $programChoiceOption;
    }
}
