<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Unilend\Core\Entity\CompanyGroupTag as Entity;
use Unilend\Core\Entity\Team;
use Unilend\Core\Repository\CompanyRepository;
use Unilend\Core\Validator\Constraints\CompanyGroupTag as CustomConstraint;

class CompanyGroupTagValidator extends ConstraintValidator
{
    private CompanyRepository $companyRepository;

    private PropertyAccessorInterface $propertyAccessor;

    /**
     * @param CompanyRepository         $companyRepository
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(CompanyRepository $companyRepository, PropertyAccessorInterface $propertyAccessor)
    {
        $this->companyRepository = $companyRepository;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @inheritDoc
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (false === $value instanceof Entity) {
            throw new UnexpectedTypeException($value, Entity::class);
        }

        if (false === $constraint instanceof CustomConstraint) {
            throw new UnexpectedTypeException($constraint, CustomConstraint::class);
        }

        $validatedObject = $this->context->getObject();

        if ($constraint->companyPropertyPath) {
            $validatedObject = $this->propertyAccessor->getValue($this->context->getObject(), $constraint->companyPropertyPath);
        }

        if ($constraint->teamPropertyPath) {
            $validatedObject = $this->propertyAccessor->getValue($this->context->getObject(), $constraint->teamPropertyPath);
        }

        $company = $validatedObject instanceof Team ? $this->companyRepository->findOneByTeam($validatedObject) : $validatedObject;

        $expectedCompanyGroup = $company->getCompanyGroup();

        if (false === ($value->getCompanyGroup() === $expectedCompanyGroup)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ expectedCompanyGroup }}', $expectedCompanyGroup ? $expectedCompanyGroup->getName() : null)
                ->addViolation();
        }
    }
}
