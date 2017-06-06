<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;

class FeedsBPICommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:feeds_out:project:generate')
            ->setDescription('Sends project XML Stream')
            ->addArgument('partner', InputArgument::REQUIRED, 'which partner ? "bpi" or "crowdlending" ? ');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        $translator    = $this->getContainer()->get('translator');
        $router        = $this->getContainer()->get('router');
        $serializer    = $this->getContainer()->get('serializer');

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        /** @var \bids $bids */
        $bids = $entityManager->getRepository('bids');
        /** @var \loans $loans */
        $loans = $entityManager->getRepository('loans');

        $hostUrl  = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $userPath = $this->getContainer()->getParameter('path.user');

        $projectStatuses = [
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
        ];

        $partner      = strtolower($input->getArgument('partner'));
        $products     = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Product')->findAvailableProductsByClient();
        $productIds   = array_map(function (Product $product) {
            return $product->getIdProduct();
        }, $products);

        $projectsToSerialise = [];
        $projectList         = $project->selectProjectsByStatus($projectStatuses, 'AND p.display = ' . \projects::DISPLAY_PROJECT_ON, [], '', '', false, $productIds);
        foreach ($projectList as $item) {
            $project->get($item['id_project']);
            $company->get($project->id_company);

            if ($project->status == \projects_status::EN_FUNDING) {
                $totalBids = $bids->sum('id_project = ' . $project->id_project . ' AND status = ' . \bids::STATUS_BID_PENDING, 'amount') / 100;
            } else {
                $totalBids = $bids->sum('id_project = ' . $project->id_project . ' AND status = ' . \bids::STATUS_BID_ACCEPTED, 'amount') / 100;
            }

            if ($totalBids > $project->amount) {
                $totalBids = $project->amount;
            }

            $details = [
                'reference_partenaire'             => '045',
                'date_export'                      => date('Y-m-d'),
                'reference_projet'                 => $project->id_project,
                'impact_social'                    => 'NON',
                'impact_environnemental'           => 'NON',
                'impact_culturel'                  => 'NON',
                'impact_eco'                       => 'OUI',
                'categorie'                        => [
                    'categorie1' => $this->getBPISector($company->sector)
                ],
                'mots_cles_nomenclature_operateur' => '',
                'mode_financement'                 => 'PRR',
                'type_porteur_projet'              => 'ENT',
                'qualif_ESS'                       => 'NON',
                'code_postal'                      => $company->zip,
                'ville'                            => $company->city,
                'titre'                            => $translator->trans('company-sector_sector-' . $company->sector) . ' - ' . $company->city,
                'description'                      => $project->nature_project,
                'url'                              => $hostUrl . $router->generate('project_detail',
                        ['projectSlug' => $project->slug]) . '/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent',
                'url_photo'                        => $hostUrl . '/images/dyn/projets/169/' . $project->photo_projet,
                'date_debut_collecte'              => $project->date_publication,
                'date_fin_collecte'                => $project->date_retrait,
                'montant_recherche'                => $project->amount,
                'montant_collecte'                 => number_format($totalBids, 0, ',', ''),
                'nb_contributeurs'                 => $loans->getNbPreteurs($project->id_project),
                'succes'                           => $this->getBPISuccess($project->status)
            ];

            if ('crowdlending' === $partner) {
                $details['duree_du_pret'] = $project->period;
                $details['taux']          = round($project->getAverageInterestRate(), 1);
            }

            $projectsToSerialise['projet'][] = $details;
        }
        $xml = $serializer->serialize($projectsToSerialise, 'xml', ['xml_root_node_name' => 'partenaire', 'xml_encoding' => 'UTF-8']);

        $fileName = $this->getFileName($partner);
        if (false === file_put_contents($userPath . 'fichiers/' . $fileName . '.xml', $xml)) {
            $output->writeln('Error occured while creating fichiers/' . $fileName . '.xml');
        } else {
            $output->writeln('fichiers/' . $fileName . '.xml created');
        }

        if (false === file_put_contents($userPath . 'fichiers/' . $fileName . '_historique.xml', $xml, FILE_APPEND)) {
            $output->writeln('Error occured while updating fichiers/' . $fileName . '_historique.xml');
        } else {
            $output->writeln('fichiers/' . $fileName . '_historique.xml updated');
        }
    }

    private function getFileName($partner)
    {
        switch ($partner) {
            case 'crowdlending' :
                $filename = 'digest';
                break;
            case 'bpi' :
            default :
                $filename = '045';
                break;
        }

        return $filename;
    }

    /**
     * @param string $sector
     *
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
     *
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
