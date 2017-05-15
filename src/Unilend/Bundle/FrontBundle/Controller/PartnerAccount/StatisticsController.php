<?PHP

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class StatisticsController extends Controller
{
    /**
     * @Route("partenaire/performance", name="partner_statistics")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function statisticsAction()
    {
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $projectsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $statusList         = [ProjectsStatus::COMPLETE_REQUEST, ProjectsStatus::PREP_FUNDING, ProjectsStatus::REMBOURSEMENT];
        $template           = [
            'timeAxis' => [],
            'graph'    => [
                'user' => []
            ]
        ];

        $datePeriod = new \DatePeriod(
            (new \DateTime('first day of next month last year'))->setTime(0, 0, 1),
            new \DateInterval('P1M'),
            (new \DateTime('first day of this month'))
        );

        foreach ($datePeriod as $month) {
            $template['timeAxis'][] = strftime('%b %Y', $month->getTimestamp());
        }

        foreach ($statusList as $status) {
            $graph = $projectsRepository->getMonthlyStatistics($status, $datePeriod, null, $this->getUser()->getClientId());
            $template['graph']['user'][$status]['count'] = array_map('intval', array_column($graph, 'count'));
            $template['graph']['user'][$status]['sum']   = array_map('intval', array_column($graph, 'sum'));
        }

        if (in_array(UserPartner::ROLE_ADMIN, $this->getUser()->getRoles())) {
            $companies = $this->getUserCompanies();
            $template['graph']['admin'] = [];

            foreach ($statusList as $status) {
                $graph = $projectsRepository->getMonthlyStatistics($status, $datePeriod, $companies);
                $template['graph']['admin'][$status]['count'] = array_map('intval', array_column($graph, 'count'));
                $template['graph']['admin'][$status]['sum']   = array_map('intval', array_column($graph, 'sum'));
            }
        }

        return $this->render('/partner_account/statistics.html.twig', $template);
    }

    /**
     * @return Companies[]
     */
    private function getUserCompanies()
    {
        /** @var UserPartner $user */
        $user      = $this->getUser();
        $companies = [$user->getCompany()];

        if (in_array(UserPartner::ROLE_ADMIN, $user->getRoles())) {
            $companies = $this->getCompanyTree($user->getCompany(), $companies);
        }

        return $companies;
    }

    /**
     * @param Companies $rootCompany
     * @param array     $tree
     *
     * @return Companies[]
     */
    private function getCompanyTree(Companies $rootCompany, array $tree)
    {
        $childCompanies = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Companies')
            ->findBy(['idParentCompany' => $rootCompany]);

        foreach ($childCompanies as $company) {
            $tree[] = $company;
            $tree = $this->getCompanyTree($company, $tree);
        }

        return $tree;
    }
}
