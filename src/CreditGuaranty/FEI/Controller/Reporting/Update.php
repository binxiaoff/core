<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use KLS\Core\Serializer\Encoder\XlsxEncoder;
use KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Update
{
    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(
        Request $request,
        FinancingObjectUpdater $financingObjectUpdater,
        XlsxEncoder $xlsxEncoder
    ): JsonResponse {
        $data = $xlsxEncoder->decode($request->getContent(), 'xlsx');

        $response = $financingObjectUpdater->update($data);

        if ((\count($response['violations']) || (\count($response['notFoundFinancingObject'])) > 0)) {
            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
