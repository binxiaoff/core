<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\ContextBuilder;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use Symfony\Component\HttpFoundation\Request;

class FinancingObjectContextBuilder implements SerializerContextBuilderInterface
{
    private SerializerContextBuilderInterface $decorated;
    private IriConverterInterface $iriConverter;

    public function __construct(SerializerContextBuilderInterface $decorated, IriConverterInterface $iriConverter)
    {
        $this->decorated    = $decorated;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @throws Exception
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context       = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        if (FinancingObject::class === $resourceClass && Request::METHOD_PATCH === $request->getMethod()) {
            /** @var FinancingObject $financingObject */
            $financingObject = $this->iriConverter->getItemFromIri($context['uri']);
            $reservation     = $financingObject->getReservation();

            if ($reservation->isInDraft()) {
                $context['groups'][] = 'creditGuaranty:financingObject:write';
            }
            if ($reservation->isFormalized()) {
                $context['groups'][] = 'creditGuaranty:financingObject:update:formalized';
            }
        }

        return $context;
    }
}
