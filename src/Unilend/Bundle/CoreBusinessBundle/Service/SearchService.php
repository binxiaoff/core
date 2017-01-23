<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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

    public static $keywords = [
        'autolend',
        'pret',
        'prêt',
        'operation',
        'alimentation',
        'alimenter',
        'retrait'
    ];

    /**
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     * @param string              $deskUser
     * @param string              $deskPassword
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        RouterInterface $router,
        $deskUser,
        $deskPassword
    ) {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->router        = $router;
        $this->deskUser      = $deskUser;
        $this->deskPassword  = $deskPassword;
    }

    /**
     * @param string $query
     * @param bool   $includeProjects
     *
     * @return array
     */
    public function search($query, $includeProjects = false)
    {
        $query        = filter_var($query, FILTER_SANITIZE_STRING);
        $result       = [];
        $cmsResult    = $this->searchInCMSContent($query);
        $nonCMSResult = $this->searchInNonCMSPages($query);

        if (false === empty($nonCMSResult) || false === empty($cmsResult)) {
            $result['unilend'] = array_merge($nonCMSResult, $cmsResult);
        }

        if ($includeProjects) {
            $result['projects'] = $this->searchInProjects($query);
        }

        $deskResult = $this->searchInDesk($query);

        if (false !== $deskResult) {
            $result['desk'] = $deskResult;
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
     * @param string $query
     *
     * @return array|bool
     */
    private function searchInDesk($query)
    {
        $parameters = [
            'text'              => $query,
            'sort_field'        => 'score',
            'sort_direction'    => 'desc',
            // 'in_support_center' => true, Parameter exists in documentation but does not seem to work. Support request has been done to Desk. When this is working, parameter may be uncommented and condition deleted in above code.
            'per_page'          => 10
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://unilend.desk.com/api/v2/articles/search?' . http_build_query($parameters));
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->deskUser . ':' . $this->deskPassword);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $response = curl_exec($ch);

        curl_close($ch);

        if ($response) {
            $response = json_decode($response, true);

            if (isset($response['_embedded']['entries']) && false === empty($response['_embedded']['entries'])) {
                $deskResult = [];

                foreach ($response['_embedded']['entries'] as $entry) {
                    if (true == $entry['in_support_center']) {
                        $deskResult[] = [
                            'title' => $entry['subject'],
                            'url'   => $entry['public_url']
                        ];
                    }
                }

                return $deskResult;
            }
        }
        return false;
    }

    /**
     * @param string $search
     *
     * @return array|bool
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
