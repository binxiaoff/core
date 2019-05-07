<?php

declare(strict_types=1);

namespace Unilend\Controller\Company;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Companies;
use Unilend\Service\WebServiceClient\InseeManager;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class CompanySearchController extends AbstractController
{
    /**
     * @Route("/societe/search", name="company_search_by_siren", methods={"GET"})
     *
     * @param Request                $request
     * @param InseeManager           $inseeManager
     * @param EntityManagerInterface $entityManager
     *
     * @return JsonResponse
     */
    public function companySearch(Request $request, InseeManager $inseeManager, EntityManagerInterface $entityManager): JsonResponse
    {
        $siren = $request->query->get('term');

        if (false === empty($siren) && 1 !== preg_match('/^[0-9]{9}$/', $siren)) {
            return $this->json([
                'success' => false,
                'error'   => 'Invalid parameters',
            ]);
        }

        $companyRepository = $entityManager->getRepository(Companies::class);
        $company           = $companyRepository->findOneBy(['siren' => $siren]);

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

            $entityManager->persist($company);
            $entityManager->flush();
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
