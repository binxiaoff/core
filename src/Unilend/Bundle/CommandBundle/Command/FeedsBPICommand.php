<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Bids, Product, ProjectsStatus
};

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
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager          = $this->getContainer()->get('doctrine.orm.entity_manager');
        $translator             = $this->getContainer()->get('translator');
        $router                 = $this->getContainer()->get('router');
        $serializer             = $this->getContainer()->get('serializer');

        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManagerSimulator->getRepository('companies');
        /** @var \bids $bids */
        $bids = $entityManagerSimulator->getRepository('bids');
        /** @var \loans $loans */
        $loans = $entityManagerSimulator->getRepository('loans');
        $logger = $this->getContainer()->get('monolog.logger.console');

        $hostUrl  = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $userPath = $this->getContainer()->getParameter('path.user');

        $projectStatuses = [
            ProjectsStatus::EN_FUNDING,
            ProjectsStatus::FUNDE,
            ProjectsStatus::FUNDING_KO,
            ProjectsStatus::REMBOURSEMENT,
            ProjectsStatus::REMBOURSE,
            ProjectsStatus::REMBOURSEMENT_ANTICIPE,
            ProjectsStatus::PROBLEME,
            ProjectsStatus::LOSS
        ];

        $partner    = strtolower($input->getArgument('partner'));
        $products   = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Product')->findAvailableProductsByClient();
        $productIds = array_map(function (Product $product) {
            return $product->getIdProduct();
        }, $products);

        $projectsToSerialise = [];
        $projectList         = $project->selectProjectsByStatus($projectStatuses, 'AND p.display = ' . \projects::DISPLAY_PROJECT_ON, [], '', '', false, $productIds);
        foreach ($projectList as $item) {
            $project->get($item['id_project']);
            $company->get($project->id_company);
            $companyAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedCompanyAddressByType($company->id_company, AddressType::TYPE_MAIN_ADDRESS);

            $projectPublicationDate = \DateTime::createFromFormat('Y-m-d H:i:s', $project->date_publication);
            $projectWithdrawalDate  = \DateTime::createFromFormat('Y-m-d H:i:s', $project->date_retrait);

            if ((empty($projectPublicationDate) || empty($projectWithdrawalDate)) && 'bpi' === $partner) {
                $logger->warning(
                    'The project ' . $project->id_project . ' will not be added into xml file. No publishing/withdrawal date was set',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                );
                continue;
            }

            if ($project->status == ProjectsStatus::EN_FUNDING) {
                $totalBids = $bids->sum('id_project = ' . $project->id_project . ' AND status = ' . Bids::STATUS_PENDING, 'amount') / 100;
            } else {
                $totalBids = $bids->sum('id_project = ' . $project->id_project . ' AND status = ' . Bids::STATUS_ACCEPTED, 'amount') / 100;
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
                'code_postal'                      => $companyAddress->getZip(),
                'ville'                            => $companyAddress->getCity(),
                'titre'                            => $translator->trans('company-sector_sector-' . $company->sector) . ' - ' . $company->city,
                'description'                      => $project->nature_project,
                'url'                              => $hostUrl . $router->generate('project_detail',
                        ['projectSlug' => $project->slug]) . '/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent',
                'url_photo'                        => $hostUrl . '/images/dyn/projets/169/' . $project->photo_projet,
                'date_debut_collecte'              => $projectPublicationDate->format('Y-m-d'),
                'date_fin_collecte'                => $projectWithdrawalDate->format('Y-m-d'),
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
            case ProjectsStatus::EN_FUNDING:
            case ProjectsStatus::PROBLEME:
            case ProjectsStatus::REMBOURSEMENT:
            case ProjectsStatus::REMBOURSE:
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
            case ProjectsStatus::FUNDE:
            case ProjectsStatus::LOSS:
                return 'OUI';
            case ProjectsStatus::FUNDING_KO:
                return 'NON';
            default:
                return '';
        }
    }
}
