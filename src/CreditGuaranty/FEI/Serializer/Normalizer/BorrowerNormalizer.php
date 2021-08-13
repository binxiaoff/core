<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\Normalizer;

use KLS\Core\Entity\NafNace;
use KLS\Core\Repository\NafNaceRepository;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class BorrowerNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private NafNaceRepository $nafNaceRepository;

    public function __construct(NafNaceRepository $nafNaceRepository)
    {
        $this->nafNaceRepository = $nafNaceRepository;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Borrower && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param Borrower $object
     *
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $context[static::ALREADY_CALLED] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        $nafNace = $this->nafNaceRepository->findOneBy(['nafCode' => $object->getCompanyNafCodeDescription()]);

        if ($nafNace instanceof NafNace) {
            $data['companyNaceCode'] = $nafNace->getNaceCode();
        }

        return $data;
    }
}
