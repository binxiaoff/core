<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Core\Entity\NafNace;
use Unilend\Core\Repository\NafNaceRepository;
use Unilend\CreditGuaranty\Entity\FinancingObject;

class FinancingObjectNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
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
        return $data instanceof FinancingObject && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param FinancingObject $object
     *
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        $nafNace = $this->nafNaceRepository->findOneBy(['nafCode' => $object->getLoanNafCodeDescription()]);

        if ($nafNace instanceof NafNace) {
            $data['loanNaceCode'] = $nafNace->getNaceCode();
        }

        return $data;
    }
}
