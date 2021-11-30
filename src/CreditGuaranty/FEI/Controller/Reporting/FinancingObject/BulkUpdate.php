<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting\FinancingObject;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BulkUpdate
{
    /**
     * @throws Exception
     */
    public function __invoke(
        Program $data,
        Request $request,
        ValidatorInterface $validator,
        ReservationRepository $reservationRepository,
        FinancingObjectRepository $financingObjectRepository,
        ReportingQueryGenerator $reportingQueryGenerator
    ): JsonResponse {
        $query = $reportingQueryGenerator->generate($request->query->all());
        $ids   = \array_column(
            $reservationRepository->findByReportingFilters($data, $query),
            'id_financing_object'
        );

        $dataToUpdate = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->validate($validator, $dataToUpdate);

        try {
            $financingObjectRepository->bulkUpdate($ids, $dataToUpdate);
        } catch (Exception $exception) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_OK);
    }

    private function validate(ValidatorInterface $validator, array $dataToUpdate): void
    {
        $constraints = new Assert\Collection([
            'fields'         => \array_map(
                static fn () => new Assert\DateTime('Y-m-d'),
                \array_combine(FieldAlias::MAPPING_REPORTING_DATES, FieldAlias::MAPPING_REPORTING_DATES)
            ),
            'allowExtraFields'   => false,
            'allowMissingFields' => true,
        ]);

        $violations = $validator->validate($dataToUpdate, $constraints);

        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }
    }
}
