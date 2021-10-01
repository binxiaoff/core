<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Service\ReportingExtractor;
use Symfony\Component\HttpFoundation\Request;

class Reporting
{
    private ReportingExtractor $reportingExtractor;

    public function __construct(ReportingExtractor $reportingExtractor)
    {
        $this->reportingExtractor = $reportingExtractor;
    }

    /**
     * @throws Exception
     */
    public function __invoke(Request $request, ReportingTemplate $data): Paginator
    {
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 100);
        $page         = (int) $request->query->get('page', 1);

        return $this->reportingExtractor->extracts($data, $itemsPerPage, $page);
    }
}
