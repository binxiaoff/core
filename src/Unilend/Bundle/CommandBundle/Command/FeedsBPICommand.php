<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class FeedsBPICommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:bpi')
            ->setDescription('Sends BPI XML Stream');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \projects $projects */
        $projects = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        /** @var \bids $bids */
        $bids = $entityManager->getRepository('bids');
        /** @var \loans $loans */
        $loans = $entityManager->getRepository('loans');

        $staticUrl = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $userPath  = $this->getContainer()->getParameter('path.user');

        $projectStatuses = array(
            \projects_status::EN_FUNDING,
            \projects_status::FUNDE,
            \projects_status::FUNDING_KO,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSE,
            \projects_status::REMBOURSEMENT_ANTICIPE,
            \projects_status::PROBLEME,
            \projects_status::PROBLEME_J_X,
            \projects_status::RECOUVREMENT,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<partenaire>';

        $projects = $projects->selectProjectsByStatus(implode(',', $projectStatuses), '', '', array(), '', '', false);
        foreach ($projects as $project) {
            $company->get($project['id_company'], 'id_company');

            if ($project['status'] === \projects_status::EN_FUNDING) {
                $totalBids = $bids->sum('id_project = ' . $project['id_project'] . ' AND status = ' . \bids::STATUS_BID_PENDING, 'amount') / 100;
            } else {
                $totalBids = $bids->sum('id_project = ' . $project['id_project'] . ' AND status = ' . \bids::STATUS_BID_ACCEPTED, 'amount') / 100;
            }

            if ($totalBids > $project['amount']) {
                $totalBids = $project['amount'];
            }

            $xml .= '<projet>';
            $xml .= '<reference_partenaire>045</reference_partenaire>';
            $xml .= '<date_export>' . date('Y-m-d') . '</date_export>';
            $xml .= '<reference_projet>' . $project['id_project'] . '</reference_projet>';
            $xml .= '<impact_social>NON</impact_social>';
            $xml .= '<impact_environnemental>NON</impact_environnemental>';
            $xml .= '<impact_culturel>NON</impact_culturel>';
            $xml .= '<impact_eco>OUI</impact_eco>';
            $xml .= '<categorie><categorie1>' . $this->getBPISector($company->sector) . '</categorie1></categorie>';
            $xml .= '<mots_cles_nomenclature_operateur></mots_cles_nomenclature_operateur>';
            $xml .= '<mode_financement>PRR</mode_financement>';
            $xml .= '<type_porteur_projet>ENT</type_porteur_projet>';
            $xml .= '<qualif_ESS>NON</qualif_ESS>';
            $xml .= '<code_postal>' . $company->zip . '</code_postal>';
            $xml .= '<ville><![CDATA["' . utf8_encode($company->city) . '"]]></ville>';
            $xml .= '<titre><![CDATA["' . $company->name . '"]]></titre>';
            $xml .= '<description><![CDATA["' . $project['nature_project'] . '"]]></description>';
            $xml .= '<url><![CDATA[' . $staticUrl . '/projects/detail/' . $project['slug'] . '/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent]]></url>';
            $xml .= '<url_photo><![CDATA[' . $staticUrl . '/images/dyn/projets/169/' . $project['photo_projet'] . ']]></url_photo>';
            $xml .= '<date_debut_collecte>' . $project['date_publication'] . '</date_debut_collecte>';
            $xml .= '<date_fin_collecte>' . $project['date_retrait'] . '</date_fin_collecte>';
            $xml .= '<montant_recherche>' . $project['amount'] . '</montant_recherche>';
            $xml .= '<montant_collecte>' . number_format($totalBids, 0, ',', '') . '</montant_collecte>';
            $xml .= '<nb_contributeurs>' . $loans->getNbPreteurs($project['id_project']) . '</nb_contributeurs>';
            $xml .= '<succes>' . $this->getBPISuccess($project['status']) . '</succes>';
            $xml .= '</projet>';
        }
        $xml .= '</partenaire>';

        if (false === file_put_contents($userPath . 'fichiers/045.xml', $xml)) {
            $output->writeln('Error occured while creating fichiers/045.xml');
        } else {
            $output->writeln('fichiers/045.xml created');
        }

        if (false === file_put_contents($userPath . 'fichiers/045_historique.xml', $xml, FILE_APPEND)) {
            $output->writeln('Error occured while updating fichiers/045_historique.xml');
        } else {
            $output->writeln('fichiers/045_historique.xml updated');
        }
    }

    /**
     * @param string $sector
     * @return string
     */
    private function getBPISector($sector)
    {
        switch ($sector) {
            case 2:
            case 5:
            case 7:
            case 18:
            case 20:
            case 29:
                return '23';
            case 17:
            case 22:
            case 23:
            case 25:
                return '21';
            case 4:
                return '44';
            case 15:
                return '63';
            case 16:
                return '61';
            case 27:
                return '03';
            default:
                return '22';
        }
    }

    /**
     * @param int $status
     * @return string
     */
    private function getBPISuccess($status)
    {
        switch ($status) {
            case \projects_status::EN_FUNDING:
            case \projects_status::PROBLEME:
            case \projects_status::REMBOURSEMENT:
            case \projects_status::REMBOURSE:
            case \projects_status::REMBOURSEMENT_ANTICIPE:
            case \projects_status::FUNDE:
            case \projects_status::PROBLEME_J_X:
            case \projects_status::RECOUVREMENT:
            case \projects_status::PROCEDURE_SAUVEGARDE:
            case \projects_status::REDRESSEMENT_JUDICIAIRE:
            case \projects_status::LIQUIDATION_JUDICIAIRE:
            case \projects_status::DEFAUT:
                return 'OUI';
            case \projects_status::FUNDING_KO:
                return 'NON';
            default:
                return '';
        }
    }
}
