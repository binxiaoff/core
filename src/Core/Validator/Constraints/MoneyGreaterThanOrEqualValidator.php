<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\{Constraint, ConstraintValidator, Exception\ConstraintDefinitionException, Exception\UnexpectedTypeException};
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Service\MoneyCalculator;

class MoneyGreaterThanOrEqualValidator extends ConstraintValidator
{
    /** @var ManagerRegistry */
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function validate($entity, Constraint $constraint): void
    {
        if (false === $constraint instanceof MoneyGreaterThanOrEqual) {
            throw new UnexpectedTypeException($constraint, MoneyGreaterThanOrEqual::class);
        }

        $fieldName = $constraint->field;

        if (false === \is_string($fieldName)) {
            throw new UnexpectedTypeException($fieldName, 'string');
        }

        if (empty($fieldName)) {
            throw new ConstraintDefinitionException('The money field has to be specified.');
        }

        if (null === $entity) {
            return;
        }

        $fullyQualifiedName = \get_class($entity);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($fullyQualifiedName);

        if (!$entityManager) {
            throw new ConstraintDefinitionException(sprintf('Unable to find the object manager associated with an entity of class "%s".', $fullyQualifiedName));
        }

        $class = $entityManager->getClassMetadata($fullyQualifiedName);

        if (false === $class->hasField($fieldName) && false === $class->hasAssociation($fieldName)) {
            throw new ConstraintDefinitionException(sprintf('The field "%s" is not mapped by Doctrine, so it cannot be validated.', $fieldName));
        }

        $money = $class->reflFields[$fieldName]->getValue($entity);

        if (false === $money instanceof Money) {
            throw new UnexpectedTypeException($money, Money::class);
        }

        $previousEntity = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);

        $previousMoney = new Money($previousEntity[$fieldName . '.currency'], $previousEntity[$fieldName . '.amount']);

        if (-1 === MoneyCalculator::compare($money, $previousMoney)) {
            $this->context->buildViolation($constraint->message)
                ->atPath($fieldName)
                ->addViolation()
            ;
        }
    }
}
