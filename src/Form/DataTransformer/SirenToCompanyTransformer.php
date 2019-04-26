<?php

declare(strict_types=1);

namespace Unilend\Form\DataTransformer;

use LogicException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Unilend\Entity\Companies;
use Unilend\Repository\CompaniesRepository;

class SirenToCompanyTransformer implements DataTransformerInterface
{
    /**
     * @var CompaniesRepository
     */
    private $companiesRepository;

    /**
     * SirenToCompanyTransformer constructor.
     *
     * @param CompaniesRepository $companiesRepository
     */
    public function __construct(CompaniesRepository $companiesRepository)
    {
        $this->companiesRepository = $companiesRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }
        if (!$value instanceof Companies) {
            throw new LogicException('The Transformer can only transfer Companies objects');
        }

        return $value->getSiren();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $company = $this->companiesRepository->findOneBy(['siren' => $value]);

        if (!$company) {
            throw new TransformationFailedException(sprintf('No company found with siren "%s"', $value));
        }

        return $company;
    }
}
