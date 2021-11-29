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

            if ($reservation->isAcceptedByManagingCompany()) {
                $index = \array_search('creditGuaranty:reservation:write', $context['groups']);

                if (false !== $index) {
                    unset($context['groups'][$index]);
                }

                $context['groups'][] = 'creditGuaranty:reservation:formalize';
            }
        }

        return $context;
    }
}
