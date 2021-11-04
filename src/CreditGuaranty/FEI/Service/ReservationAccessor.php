<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ReservationAccessor
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
        $value = $this->propertyAccessor->getValue($entity, $field->getPropertyPath());

        if ($value instanceof MoneyInterface) {
            $value = $value->getAmount();
        }

        return $value;
    }
}
