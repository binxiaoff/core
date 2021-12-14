<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reservation;

use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Service\EligibilityChecker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Ineligibilities
{
    private const ALLOWED_BOOLEAN_VALUES = [
        '0', '1', 'true', 'false',
    ];

    private EligibilityChecker $eligibilityChecker;

    public function __construct(EligibilityChecker $eligibilityChecker)
    {
        $this->eligibilityChecker = $eligibilityChecker;
    }

    public function __invoke(Reservation $data, Request $request): JsonResponse
    {
        if (
            false === \in_array(
                $request->query->get('withConditions'),
                self::ALLOWED_BOOLEAN_VALUES,
                true
            )
        ) {
            $condition = false;
        } else {
            $condition = \filter_var($request->query->get('withConditions'), FILTER_VALIDATE_BOOLEAN);
        }

        $category = $request->query->get('category');

        $ineligibles = $this->eligibilityChecker->check(
            $data,
            $condition,
            $category
        );

        // Actually the front can submit a reservation without any financingObject
        // because eligibility checking returns true
        // so we need to force the eligibility to false for this special case
        if (false === \array_key_exists('loan', $ineligibles) && 0 === $data->getFinancingObjects()->count()) {
            $ineligibles['loan'] = [];
        }

        return new JsonResponse(['ineligibles' => $ineligibles], Response::HTTP_OK);
    }
}
