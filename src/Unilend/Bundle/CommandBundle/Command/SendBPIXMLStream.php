<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;

class SendBPIXMLStream extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('bpistream')
            ->setDescription('Sends BPI XML Stream');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \projects $projects */
        $projects = Loader::loadData('projects');
        /** @var \companies $companies */
        $companies = Loader::loadData('companies');
        /** @var \bids $bids */
        $bids = Loader::loadData('bids');
        /** @var \loans $loans */
        $loans = Loader::loadData('loans');

        $this->aConfig = Loader::loadConfig();

        $aProjectStatuses = array(
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
        $aProjects = $projects->selectProjectsByStatus(implode(',', $aProjectStatuses), '', '', array(), '', '', false);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<partenaire>';

        foreach ($aProjects as $aProject) {
            $companies->get($aProject['id_company'], 'id_company');

            if ($aProject['status'] === \projects_status::EN_FUNDING) {
                $iTotalbids = $bids->sum('id_project = ' . $aProject['id_project'] . ' AND status = 0', 'amount') / 100;
            } else {
                $iTotalbids = $bids->sum('id_project = ' . $aProject['id_project'] . ' AND status = 1', 'amount') / 100;
            }

            if ($iTotalbids > $aProject['amount']) {
                $iTotalbids = $aProject['amount'];
            }

            $iLenders = $loans->getNbPreteurs($aProject['id_project']);
            switch ($aProject['status']) {
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
                    $sProjectsuccess = 'OUI';
                    break;
                case \projects_status::FUNDING_KO:
                    $sProjectsuccess = 'NON';
                    break;
                default:
                    $sProjectsuccess = '';
                    break;
            }

            switch ($companies->sector) {
                case 2:
                case 5:
                case 7:
                case 18:
                case 20:
                case 29:
                    $sSector = '23';
                    break;
                case 17:
                case 22:
                case 23:
                case 25:
                    $sSector = '21';
                    break;
                case 4:
                    $sSector = '44';
                    break;
                case 15:
                    $sSector = '63';
                    break;
                case 16:
                    $sSector = '61';
                    break;
                case 27:
                    $sSector = '03';
                    break;
                default:
                    $sSector = '22';
                    break;
            }

            $xml .= '<projet>';
            $xml .= '<reference_partenaire>045</reference_partenaire>';
            $xml .= '<date_export>' . date('Y-m-d') . '</date_export>';
            $xml .= '<reference_projet>' . $aProject['id_project'] . '</reference_projet>';
            $xml .= '<impact_social>NON</impact_social>';
            $xml .= '<impact_environnemental>NON</impact_environnemental>';
            $xml .= '<impact_culturel>NON</impact_culturel>';
            $xml .= '<impact_eco>OUI</impact_eco>';
            $xml .= '<categorie><categorie1>' . $sSector . '</categorie1></categorie>';
            $xml .= '<mots_cles_nomenclature_operateur></mots_cles_nomenclature_operateur>';
            $xml .= '<mode_financement>PRR</mode_financement>';
            $xml .= '<type_porteur_projet>ENT</type_porteur_projet>';
            $xml .= '<qualif_ESS>NON</qualif_ESS>';
            $xml .= '<code_postal>' . $companies->zip . '</code_postal>';
            $xml .= '<ville><![CDATA["' . utf8_encode($companies->city) . '"]]></ville>';
            $xml .= '<titre><![CDATA["' . $companies->name . '"]]></titre>';
            $xml .= '<description><![CDATA["' . $aProject['nature_project'] . '"]]></description>';
            $xml .= '<url><![CDATA["' . $this->aConfig['static_url'][$this->aConfig['env']] . '/projects/detail/' . $aProject['slug'] . '/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent"]]></url>';
            $xml .= '<url_photo><![CDATA["' . $this->aConfig['static_url'][$this->aConfig['env']] . '/images/dyn/projets/169/' . $aProject['photo_projet'] . '"]]></url_photo>';
            $xml .= '<date_debut_collecte>' . $aProject['date_publication'] . '</date_debut_collecte>';
            $xml .= '<date_fin_collecte>' . $aProject['date_retrait'] . '</date_fin_collecte>';
            $xml .= '<montant_recherche>' . $aProject['amount'] . '</montant_recherche>';
            $xml .= '<montant_collecte>' . number_format($iTotalbids, 0, ',', '') . '</montant_collecte>';
            $xml .= '<nb_contributeurs>' . $iLenders . '</nb_contributeurs>';
            $xml .= '<succes>' . $sProjectsuccess . '</succes>';
            $xml .= '</projet>';
        }
        $xml .= '</partenaire>';

        if (false === file_put_contents($this->aConfig['user_path'][$this->aConfig['env']] . 'fichiers/045.xml', $xml)) {
            $output->writeln('Error occured while creating fichiers/045.xml');
        } else {
            $output->writeln('fichiers/045.xml created');
        }

        if (false === file_put_contents($this->aConfig['user_path'][$this->aConfig['env']] . 'fichiers/045_historique.xml', $xml, FILE_APPEND)) {
            $output->writeln('Error occured while updating fichiers/045_historique.xml');
        } else {
            $output->writeln('fichiers/045_historique.xml updated');
        }
    }
}
