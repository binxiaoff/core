<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use LogicException;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;

class EligibilityChecker
{
    private FieldRepository $fieldRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private EligibilityHelper $eligibilityHelper;
    private EligibilityConditionChecker $eligibilityConditionChecker;
    private array $ineligibles = [];

    public function __construct(
        FieldRepository $fieldRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        EligibilityHelper $eligibilityHelper,
        EligibilityConditionChecker $eligibilityConditionChecker
    ) {
        $this->fieldRepository                           = $fieldRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->eligibilityHelper                         = $eligibilityHelper;
        $this->eligibilityConditionChecker               = $eligibilityConditionChecker;
    }

    public function check(Reservation $reservation, bool $withConditions, ?string $category = null): array
    {
        $this->supportsChecking($reservation, $withConditions, $category);

        $fields = (false === empty($category))
            ? $this->fieldRepository->findBy(['category' => $category])
            : $this->fieldRepository->findAll();

        foreach ($fields as $field) {
            $this->checkByField($reservation, $field, $withConditions);
        }

        array_walk($this->ineligibles, function (&$value) {
            $value = array_values(array_unique($value));
        });

        return $this->ineligibles;
    }

    private function supportsChecking(Reservation $reservation, bool $withConditions, ?string $category = null): void
    {
        if (
            ($withConditions || empty($category) || 'profile' === $category)
            && false === ($reservation->getBorrower() instanceof Borrower)
        ) {
            throw new LogicException(
                sprintf(
                    'Cannot check conditions without Borrower in reservation #%s',
                    $reservation->getId()
                )
            );
        }

        if (
            ($withConditions || empty($category) || 'project' === $category)
            && false === ($reservation->getProject() instanceof Project)
        ) {
            throw new LogicException(
                sprintf(
                    'Cannot check conditions without Project in reservation #%s',
                    $reservation->getId()
                )
            );
        }

        if (
            ($withConditions || empty($category) || 'loan' === $category)
            && 0 === $reservation->getFinancingObjects()->count()
        ) {
            throw new LogicException(
                sprintf(
                    'Cannot check conditions without FinancingObject(s) in reservation #%s',
                    $reservation->getId()
                )
            );
        }
    }

    private function checkByField(Reservation $reservation, Field $field, bool $withConditions): void
    {
        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $reservation->getProgram(),
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
            // in case of eligibility field is removed from CASA side, no need to throw exception, right ?
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibility for program #%s and field #%s',
                    $reservation->getProgram()->getId(),
                    $field->getId()
                )
            );
        }

        $entity = $this->eligibilityHelper->getEntity($reservation, $field);

        if ($entity instanceof Collection) {
            foreach ($entity as $entityItem) {
                $value = $this->eligibilityHelper->getValue($entityItem, $field);

                if (false === $this->checkByType($reservation, $programEligibility, $withConditions, $value)) {
                    $this->ineligibles[$field->getCategory()][] = $field->getFieldAlias();
                }
            }

            return;
        }

        $value = $this->eligibilityHelper->getValue($entity, $field);

        if (false === $this->checkByType($reservation, $programEligibility, $withConditions, $value)) {
            $this->ineligibles[$field->getCategory()][] = $field->getFieldAlias();
        }
    }

    private function checkByType(Reservation $reservation, ProgramEligibility $programEligibility, bool $withConditions, $value): bool
    {
        $field = $programEligibility->getField();

        switch ($field->getType()) {
            case Field::TYPE_OTHER:
                return $this->checkOther($reservation, $programEligibility, $withConditions, $value);

            case Field::TYPE_BOOL:
                return $this->checkBool($reservation, $programEligibility, $withConditions, (bool) $value);

            case Field::TYPE_LIST:
                return $this->checkList($reservation, $programEligibility, $withConditions, $value);

            default:
                // the check is done in ProgramEligibility::initialiseConfigurations
                throw new LogicException('This code should not be reached');
        }
    }

    private function checkOther(Reservation $reservation, ProgramEligibility $programEligibility, bool $withConditions, $value): bool
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

        return $this->isEligible($reservation, $programEligibilityConfiguration, $withConditions);
    }

    private function checkBool(Reservation $reservation, ProgramEligibility $programEligibility, bool $withConditions, bool $value): bool
    {
        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
            'value'              => (int) $value,
        ]);

        if (null === $programEligibilityConfiguration) {
            throw new LogicException(
                sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s and value %s',
                    $programEligibility->getId(),
                    (int) $value
                )
            );
        }

        return $this->isEligible($reservation, $programEligibilityConfiguration, $withConditions);
    }

    private function checkList(Reservation $reservation, ProgramEligibility $programEligibility, bool $withConditions, ProgramChoiceOption $value): bool
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

        return $this->isEligible($reservation, $programEligibilityConfiguration, $withConditions);
    }

    private function isEligible(Reservation $reservation, ProgramEligibilityConfiguration $programEligibilityConfiguration, bool $withConditions): bool
    {
        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        if ($withConditions) {
            $ineligibles = $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration);

            if (empty($ineligibles)) {
                return true;
            }

            $this->ineligibles = array_merge_recursive($this->ineligibles, $ineligibles);

            return false;
        }

        return true;
    }
}
