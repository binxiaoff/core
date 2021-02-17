<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\{Constraint, ConstraintValidator, Exception\ConstraintDefinitionException, Exception\UnexpectedTypeException};
use Unilend\Core\Entity\Interfaces\MoneyInterface;
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
    public function validate($value, Constraint $constraint): void
    {
        if (false === $constraint instanceof MoneyGreaterThanOrEqual) {
            throw new UnexpectedTypeException($constraint, MoneyGreaterThanOrEqual::class);
        }

        if (false === $value instanceof MoneyInterface) {
            throw new UnexpectedTypeException($value, MoneyInterface::class);
        }

        $entity = $this->context->getObject();

        if (null === $entity) {
            return;
        }

        $fullyQualifiedName = \get_class($entity);
        $entityManager      = $this->managerRegistry->getManagerForClass($fullyQualifiedName);

        if (false === $entityManager instanceof EntityManagerInterface) {
            throw new ConstraintDefinitionException(sprintf('Unable to find the entity manager associated with an entity of class "%s".', $fullyQualifiedName));
        }

        $previousEntity = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);
        $propertyPath   = $this->context->getPropertyPath();
        $moneyClass     = \get_class($value);
        $previousMoney  = new $moneyClass($previousEntity[$propertyPath . '.currency'], $previousEntity[$propertyPath . '.amount']);

        if (-1 === MoneyCalculator::compare($value, $previousMoney)) {
            $this->context->buildViolation($constraint->message)
                ->atPath($propertyPath)
                ->addViolation()
            ;
        }
    }
}
