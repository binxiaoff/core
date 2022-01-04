<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
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
        FinancingObjectRepository $financingObjectRepository,
        ReportingQueryGenerator $reportingQueryGenerator
    ): Paginator {
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 60);
        $page         = (int) $request->query->get('page', 1);
        $query        = $reportingQueryGenerator->generate($request->query->all(), $data);

        return $financingObjectRepository->getPaginatorByReportingFilters(
            $data->getProgram(),
            $query,
            ($page - 1) * $itemsPerPage,
            $itemsPerPage
        );
    }
}
