<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryGenerator;
use Symfony\Component\HttpFoundation\Request;

class Reporting
{
    /**
     * @throws Exception
     */
    public function __invoke(
        Request $request,
        ReportingTemplate $data,
        ReservationRepository $reservationRepository,
        ReportingQueryGenerator $reportingQueryGenerator
    ): Paginator {
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 100);
        $page         = (int) $request->query->get('page', 1);
        $query        = $reportingQueryGenerator->generate($request->query->all(), $data);

        return $reservationRepository->findByReportingFilters($data->getProgram(), $query, $itemsPerPage, $page);
    }
}
