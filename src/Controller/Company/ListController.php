<?php

declare(strict_types=1);

namespace Unilend\Controller\Company;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Companies;
use Unilend\Repository\CompaniesRepository;
use Unilend\Service\WebServiceClient\InseeManager;

class ListController extends AbstractController
{
    /**
     * @Route("/societe/search", name="company_search_by_siren", methods={"GET"})
     *
     * @param Request             $request
     * @param InseeManager        $inseeManager
     * @param CompaniesRepository $companyRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return JsonResponse
     */
    public function search(Request $request, InseeManager $inseeManager, CompaniesRepository $companyRepository): JsonResponse
    {
        $siren = $request->query->get('term');

        if (false === empty($siren) && 1 !== preg_match('/^\d{9}$/', $siren)) {
            return $this->json([
                'success' => false,
                'error'   => 'Invalid parameters',
            ]);
        }

        $company = $companyRepository->findOneBy(['siren' => $siren]);

        if (null === $company) {
            $name = $inseeManager->searchSiren($siren);

            if (empty($name)) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Unknown SIREN',
                ]);
            }

            $company = new Companies();
            $company
                ->setSiren($siren)
                ->setName($name)
            ;

            $companyRepository->save($company);
        }

        return $this->json([
            'success' => true,
            'data'    => [
                'id'    => $company->getIdCompany(),
                'name'  => $company->getName(),
                'siren' => $company->getSiren(),
            ],
        ]);
    }
}
