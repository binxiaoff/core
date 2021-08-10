<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Service;

use Doctrine\Common\Collections\Collection;
use KLS\CreditGuaranty\Entity\Borrower;
use KLS\CreditGuaranty\Entity\Field;
use KLS\CreditGuaranty\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\Entity\ProgramEligibility;
use KLS\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\Entity\Project;
use KLS\CreditGuaranty\Entity\Reservation;
use KLS\CreditGuaranty\Repository\FieldRepository;
use KLS\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\Repository\ProgramEligibilityRepository;
use LogicException;

class EligibilityChecker
{
    private FieldRepository $fieldRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private EligibilityHelper $eligibilityHelper;
    private EligibilityConditionChecker $eligibilityConditionChecker;

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

    public function check(Reservation $reservation, bool $withConditions, ?string $category): array
    {
        $this->supports($reservation, $withConditions, $category);

        $fields = (false === empty($category))
            ? $this->fieldRepository->findBy(['category' => $category])
            : $this->fieldRepository->findAll();

        $ineligibles = [];

        foreach ($fields as $field) {
            if (false === $this->checkByField($reservation, $field, $withConditions)) {
                $ineligibles[$field->getCategory()][] = $field->getFieldAlias();
            }
        }

        return $ineligibles;
    }

    private function supports(Reservation $reservation, bool $withConditions, ?string $category): void
    {
        if (
            ($withConditions || empty($category) || 'profile' === $category)
            && false === ($reservation->getBorrower() instanceof Borrower)
        ) {
            throw new LogicException(
                \sprintf(
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
                \sprintf(
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
                \sprintf(
                    'Cannot check conditions without FinancingObject(s) in reservation #%s',
                    $reservation->getId()
                )
            );
        }
    }

    private function checkByField(Reservation $reservation, Field $field, bool $withConditions): bool
    {
        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $reservation->getProgram(),
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
            throw new LogicException(
                \sprintf(
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

                if (false === $this->isEligible($reservation, $programEligibility, $withConditions, $value)) {
                    return false;
                }
            }

            return true;
        }

        $value = $this->eligibilityHelper->getValue($entity, $field);

        return $this->isEligible($reservation, $programEligibility, $withConditions, $value);
    }

    private function isEligible(Reservation $reservation, ProgramEligibility $programEligibility, bool $withConditions, $value): bool
    {
        if (null === $value) {
            return false;
        }

        $programEligibilityConfiguration = $this->getConfigurationByFieldType($programEligibility, $value);

        if (false === $programEligibilityConfiguration->isEligible()) {
            return false;
        }

        if ($withConditions && false === $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration)) {
            return false;
        }

        return true;
    }

    private function getConfigurationByFieldType(ProgramEligibility $programEligibility, $value): ProgramEligibilityConfiguration
    {
        switch ($programEligibility->getField()->getType()) {
            case Field::TYPE_OTHER:
                $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility' => $programEligibility,
                ]);

                break;

            case Field::TYPE_BOOL:
                $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility' => $programEligibility,
                    'value'              => (int) $value,
                ]);

                break;

            case Field::TYPE_LIST:
                $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility'  => $programEligibility,
                    'programChoiceOption' => $value,
                ]);

                break;

            default:
                // the check is done in ProgramEligibility::initialiseConfigurations
                throw new LogicException('This code should not be reached');
        }

        if (false === ($programEligibilityConfiguration instanceof ProgramEligibilityConfiguration)) {
            throw new LogicException(
                \sprintf(
                    'Cannot found programEligibilityConfiguration for programEligibility #%s with value %s',
                    $programEligibility->getId(),
                    ($value instanceof ProgramChoiceOption) ? $value->getDescription() : $value
                )
            );
        }

        return $programEligibilityConfiguration;
    }
}
