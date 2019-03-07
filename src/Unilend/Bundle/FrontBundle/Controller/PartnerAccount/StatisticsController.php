<?PHP

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ProjectsStatus};

class StatisticsController extends Controller
{
    /**
     * @Route("partenaire/performance", name="partner_statistics")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function statisticsAction(?UserInterface $partnerUser): Response
    {
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $partnerManager     = $this->get('unilend.service.partner_manager');
        $projectsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $statusList         = [ProjectsStatus::STATUS_REVIEW, ProjectsStatus::STATUS_REPAYMENT];
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
            $graph                                       = $projectsRepository->getMonthlyStatistics($status, $datePeriod, null, $partnerUser->getIdClient());
            $template['graph']['user'][$status]['count'] = array_map('intval', array_column($graph, 'count'));
            $template['graph']['user'][$status]['sum']   = array_map('intval', array_column($graph, 'sum'));
        }

        if (in_array(Clients::ROLE_PARTNER_ADMIN, $partnerUser->getRoles())) {
            $companies                  = $partnerManager->getUserCompanies($partnerUser);
            $template['graph']['admin'] = [];

            foreach ($statusList as $status) {
                $graph                                        = $projectsRepository->getMonthlyStatistics($status, $datePeriod, $companies);
                $template['graph']['admin'][$status]['count'] = array_map('intval', array_column($graph, 'count'));
                $template['graph']['admin'][$status]['sum']   = array_map('intval', array_column($graph, 'sum'));
            }
        }

        return $this->render('/partner_account/statistics.html.twig', $template);
    }
}
