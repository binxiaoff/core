<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\{Constraint, ConstraintValidator, Exception\ConstraintDefinitionException, Exception\UnexpectedTypeException};
use Unilend\Core\Entity\Interfaces\MoneyInterface;

abstract class AbstractMoneyPreviousValueComparisonValidator extends ConstraintValidator
{

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
    public function validate($value, Constraint $constraint): void
    {
        if (false === $constraint instanceof AbstractMoneyPreviousValueComparison) {
            throw new UnexpectedTypeException($constraint, AbstractMoneyPreviousValueComparison::class);
        }

        if (false === $value instanceof MoneyInterface) {
            throw new UnexpectedTypeException($value, MoneyInterface::class);
        }

        $entity = $this->context->getObject();

        if (null === $entity) {
            return;
        }

        $entityManager = $this->managerRegistry->getManagerForClass(\get_class($entity));

        if (false === $entityManager instanceof EntityManagerInterface) {
            throw new ConstraintDefinitionException(sprintf('Unable to find the entity manager associated with an entity of class "%s".', \get_class($entity)));
        }

        $previousEntity = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);
        $propertyPath   = $this->context->getPropertyPath();
        $moneyClass     = \get_class($value);
        $previousMoney  = new $moneyClass($previousEntity[$propertyPath . '.currency'], $previousEntity[$propertyPath . '.amount']);

        if (false === $this->compareValues($value, $previousMoney)) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath($propertyPath)
                ->addViolation()
            ;
        }
    }

    /**
     * @param MoneyInterface $value
     * @param MoneyInterface $previousValue
     *
     * @return bool true if the relationship is valid, false otherwise
     */
    abstract protected function compareValues(MoneyInterface $value, MoneyInterface $previousValue): bool;
}
