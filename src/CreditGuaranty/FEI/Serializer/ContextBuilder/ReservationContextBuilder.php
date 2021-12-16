<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\ContextBuilder;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use Symfony\Component\HttpFoundation\Request;

class ReservationContextBuilder implements SerializerContextBuilderInterface
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

        if (Reservation::class === $resourceClass && Request::METHOD_PATCH === $request->getMethod()) {
            /** @var Reservation $reservation */
            $reservation = $this->iriConverter->getItemFromIri($context['uri']);

            if ($reservation->isInDraft()) {
                $context['groups'][] = 'creditGuaranty:reservation:update:draft';
            }
            if ($reservation->isAcceptedByManagingCompany()) {
                $context['groups'][] = 'creditGuaranty:reservation:update:accepted';
            }
        }

        return $context;
    }
}
