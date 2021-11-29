<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\ContextBuilder;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use Symfony\Component\HttpFoundation\Request;

class BorrowerContextBuilder implements SerializerContextBuilderInterface
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

        if (Borrower::class === $resourceClass && Request::METHOD_PATCH === $request->getMethod()) {
            /** @var Borrower $borrower */
            $borrower = $this->iriConverter->getItemFromIri($context['uri']);

            if ($borrower->getReservation()->isAcceptedByManagingCompany()) {
                $index = \array_search('creditGuaranty:borrower:write', $context['groups']);

                if (false !== $index) {
                    unset($context['groups'][$index]);
                }

                $context['groups'][] = 'creditGuaranty:borrower:formalize';
            }
        }

        return $context;
    }
}
