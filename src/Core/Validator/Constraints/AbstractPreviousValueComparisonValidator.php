<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

abstract class AbstractPreviousValueComparisonValidator extends ConstraintValidator
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
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (false === $constraint instanceof AbstractPreviousValueComparison) {
            throw new UnexpectedTypeException($constraint, AbstractPreviousValueComparison::class);
        }

        $this->checkPreconditions($value, $constraint);

        $entity = $this->context->getObject();
        if (null === $entity) {
            return;
        }

        $entityManager = $this->managerRegistry->getManagerForClass(\get_class($entity));
        if (false === $entityManager instanceof EntityManagerInterface) {
            throw new ConstraintDefinitionException(sprintf('Unable to find the entity manager associated with an entity of class "%s".', \get_class($entity)));
        }

        $previousEntity = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);

        if (false === $this->compareValues($value, $this->getPreviousValue($previousEntity, $value))) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath($this->context->getPropertyPath())
                ->addViolation()
            ;
        }
    }

    /**
     * @param mixed      $value
     * @param Constraint $constraint
     *
     * @throw \Exception
     *
     * @return mixed
     */
    abstract protected function checkPreconditions($value, Constraint $constraint): void;

    /**
     * @param array $previousEntity
     * @param mixed $value
     *
     * @return mixed
     */
    abstract protected function getPreviousValue(array $previousEntity, $value);

    /**
     * @param mixed $value
     * @param mixed $previousValue
     *
     * @return bool true if the relationship is valid, false otherwise
     */
    abstract protected function compareValues($value, $previousValue): bool;
}
