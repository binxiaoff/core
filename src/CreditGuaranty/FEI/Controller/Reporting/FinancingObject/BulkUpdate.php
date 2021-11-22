<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting\FinancingObject;

use Exception;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkUpdate
{
    /**
     * @throws Exception
     */
    public function __invoke(
        Program $data,
        Request $request,
        ReservationRepository $reservationRepository,
        FinancingObjectRepository $financingObjectRepository,
        ReportingQueryGenerator $reportingQueryGenerator
    ): JsonResponse {
        $dataToUpdate = \json_decode($request->getContent(), true);
        $query        = $reportingQueryGenerator->generate($request->query->all());

        $ids = \array_column(
            $reservationRepository->findByReportingFilters($data, $query),
            'id_financing_object'
        );

        foreach (\array_keys($dataToUpdate) as $property) {
            if (false === \in_array($property, FieldAlias::MAPPING_REPORTING_DATES)) {
                unset($dataToUpdate[$property]);
            }
        }

        try {
            $financingObjectRepository->bulkUpdate($ids, $dataToUpdate);
        } catch (Exception $exception) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
