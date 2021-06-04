<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use LogicException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;

class EligibilityChecker
{
    private FieldRepository $fieldRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        FieldRepository $fieldRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->fieldRepository                           = $fieldRepository;
        $this->programChoiceOptionRepository             = $programChoiceOptionRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->propertyAccessor                          = $propertyAccessor;
    }

    public function checkByCategory(Reservation $reservation, string $category): bool
    {
        $fields = $this->fieldRepository->findBy(['category' => $category]);

        foreach ($fields as $field) {
            // ignore those not having path since they are not created yet in entities
            if (empty($field->getTargetPropertyAccessPath())) {
                continue;
            }

            if (false === $this->checkByField($reservation, $field)) {
                return false;
            }
        }

        return true;
    }

    private function checkByField(Reservation $reservation, Field $field): bool
    {
        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $reservation->getProgram(),
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibility for program #%s and field #%s',
                    $reservation->getProgram()->getId(),
                    $field->getId()
                )
            );
        }

        $accessPath   = explode('::', $field->getTargetPropertyAccessPath());
        $entityPart   = array_shift($accessPath);
        $entity       = $this->propertyAccessor->getValue($reservation, $entityPart);
        $propertyPath = implode('.', $accessPath);

        if ($entity instanceof Collection) {
            foreach ($entity as $item) {
                $value = $this->getValue($item, $propertyPath, $reservation, $field);

                if (false === $this->check($programEligibility, $value)) {
                    return false;
                }
            }

            return true;
        }

        $value = $this->getValue($entity, $propertyPath, $reservation, $field);

        return $this->check($programEligibility, $value);
    }

    private function check(ProgramEligibility $programEligibility, $value): bool
    {
        $field = $programEligibility->getField();

        switch ($field->getType()) {
            case Field::TYPE_OTHER:
                return $this->checkOther($programEligibility, $value);

            case Field::TYPE_BOOL:
                return $this->checkBool($programEligibility, (bool) $value);

            case Field::TYPE_LIST:
                return $this->checkList($programEligibility, $value);

            default:
                throw new LogicException(sprintf('Unexpected field type %s', $field->getType()));
        }
    }

    private function checkOther(ProgramEligibility $programEligibility, $value): bool
    {
        if (null === $value) {
            return false;
        }

        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s',
                    $programEligibility->getId()
                )
            );
        }

        return $programEligibilityConfiguration->isEligible();
    }

    private function checkBool(ProgramEligibility $programEligibility, bool $value): bool
    {
        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
            'value'              => (int) $value,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s and value #%s',
                    $programEligibility->getId(),
                    (int) $value
                )
            );
        }

        return $programEligibilityConfiguration->isEligible();
    }

    private function checkList(ProgramEligibility $programEligibility, ProgramChoiceOption $value): bool
    {
        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility,
            'programChoiceOption' => $value,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s and programChoiceOption #%s',
                    $programEligibility->getId(),
                    $value->getId()
                )
            );
        }

        return $programEligibilityConfiguration->isEligible();
    }

    private function getValue($entity, string $propertyPath, Reservation $reservation, Field $field)
    {
        $value = $this->propertyAccessor->getValue($entity, $propertyPath);

        if (Field::TYPE_LIST !== $field->getType() || $value instanceof ProgramChoiceOption) {
            return $value;
        }

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $reservation->getProgram(),
            'field'       => $field,
            'description' => $value,
        ]);

        if (null === $programChoiceOption) {
            throw new LogicException(
                sprintf(
                    'Cannot found programChoiceOption for program #%s, field #%s and description #%s',
                    $reservation->getProgram()->getId(),
                    $field->getId(),
                    $value
                )
            );
        }

        return $programChoiceOption;
    }
}
