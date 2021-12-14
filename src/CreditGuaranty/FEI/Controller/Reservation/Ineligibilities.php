<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reservation;

use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityChecker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Ineligibilities
{
    private EligibilityChecker $eligibilityChecker;

    public function __construct(EligibilityChecker $eligibilityChecker)
    {
        $this->eligibilityChecker = $eligibilityChecker;
    }

    public function __invoke(Reservation $data): JsonResponse
    {
        return new JsonResponse(['ineligibles' => $this->eligibilityChecker->check($data)], Response::HTTP_OK);
    }
}
