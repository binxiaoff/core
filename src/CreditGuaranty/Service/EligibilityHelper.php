<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Reservation;

class EligibilityHelper
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @throws AccessException
     */
    public function getEntity(Reservation $reservation, Field $field)
    {
        return $this->propertyAccessor->getValue($reservation, $field->getReservationPropertyName());
    }

    public function getValue($entity, Field $field)
    {
        $pathParts = explode('::', $field->getPropertyPath());

        return $this->propertyAccessor->getValue($entity, implode('.', $pathParts));
    }
}
