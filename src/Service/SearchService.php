<?php

namespace Unilend\Service;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Service\Simulator\EntityManager;

class SearchService
{
    /** @var EntityManager */
    private $entityManager;
    /** @var  TranslatorInterface */
    private $translator;
    /** @var  RouterInterface */
    private $router;
    /** @var string */
    private $deskUser;
    /** @var string */
    private $deskPassword;

    /**
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        RouterInterface $router
    )
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->router        = $router;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    public function search($query)
    {
        $query        = filter_var($query, FILTER_SANITIZE_STRING);
        $result       = [];
        $cmsResult    = $this->searchInCMSContent($query);
        $nonCMSResult = $this->searchInNonCMSPages($query);
        $projects     = $this->searchInProjects($query);

        if (false === empty($nonCMSResult) || false === empty($cmsResult)) {
            $result['unilend'] = array_merge($nonCMSResult, $cmsResult);
        }

        if (false === empty($projects)) {
            $result['projects'] = $projects;
        }

        return $result;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    private function searchInCMSContent($query)
    {
        /** @var \tree $tree */
        $tree   = $this->entityManager->getRepository('tree');
        $result = $tree->search($query);

        return $result;
    }

    /**
     * @param $search
     *
     * @return array
     */
    private function searchInNonCMSPages($search)
    {
        $specificResult = [];
        switch ($search) {
            case 'autolend':
                $specificResult[] = [
                    'title' => $this->translator->trans('seo_autolend-title'),
                    'url'   => $this->router->generate('autolend')
                ];
                break;
            case 'pret':
            case 'prêt':
            case 'operation':
            case 'opération':
                $specificResult[] = [
                    'title' => $this->translator->trans('seo_lender-operations-title'),
                    'url'   => $this->router->generate('lender_operations')
                ];
                break;
            case 'alimentation':
            case 'alimenter':
                $specificResult[] = [
                    'title' => $this->translator->trans('seo_lender-wallet-deposit-title'),
                    'url'   => $this->router->generate('lender_wallet_deposit')
                ];
                break;
            case 'retrait':
                $specificResult[] = [
                    'title' => $this->translator->trans('seo_lender-wallet-withdrawal-title'),
                    'url'   => $this->router->generate('lender_wallet_withdrawal')
                ];
                break;
            default:
                break;
        }

        return $specificResult;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    private function searchInProjects($query)
    {
        /** @var \projects $projectRepository */
        $projectRepository = $this->entityManager->getRepository('projects');

        return $projectRepository->searchProjectsByName($query);
    }
}
