<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingTransformer;
use LogicException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/**
 * This normalizer is used to process some reporting data that cannot be done in sql
 * (see ReportingExtractor.php for more details,
 * where selects, joins and clauses are generated from reporting template fields).
 */
class ReportingNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private IriConverterInterface $iriConverter;
    private FinancingObjectRepository $financingObjectRepository;
    private ReportingTransformer $reportingTransformer;

    public function __construct(
        IriConverterInterface $iriConverter,
        FinancingObjectRepository $financingObjectRepository,
        ReportingTransformer $reportingTransformer
    ) {
        $this->iriConverter              = $iriConverter;
        $this->financingObjectRepository = $financingObjectRepository;
        $this->reportingTransformer      = $reportingTransformer;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return
            $data instanceof Paginator
            && !isset($context[static::ALREADY_CALLED])
            && ReportingTemplate::class === $context['resource_class']
            && isset($context['item_operation_name'])
            && 'reporting' === $context['item_operation_name']
        ;
    }

    /**
     * @param Paginator $object
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = $object->getQuery()->getResult();

        foreach ($data as &$row) {
            $financingObject = $this->financingObjectRepository->find($row['id_financing_object']);

            if (false === ($financingObject instanceof FinancingObject)) {
                throw new LogicException(
                    \sprintf(
                        'Impossible to generate reporting, FinancingObject id %s is not found',
                        $row['id_financing_object']
                    )
                );
            }

            $row                        = $this->reportingTransformer->transform($row, $financingObject);
            $row['id_financing_object'] = $this->iriConverter->getIriFromItem($financingObject);
        }

        return $data;
    }
}
