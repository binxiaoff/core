<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Unilend\Core\Repository\CompanyRepository;

class EmailDomainValidator extends ConstraintValidator
{
    /** @var CompanyRepository */
    private $companyRepository;

    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailDomain) {
            throw new UnexpectedTypeException($constraint, EmailDomain::class);
        }

        $atSignPosition = \mb_strpos($value, '@');

        if (null === $value || '' === $value || false === $atSignPosition) {
            return;
        }

        $emailDomain = \mb_substr($value, $atSignPosition + 1);

        if (null === $this->companyRepository->findOneBy(['emailDomain' => $emailDomain])) {
            $violationBuilder = $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ emailDomain }}', $emailDomain)
            ;

            $violationBuilder->addViolation();
        }
    }
}
