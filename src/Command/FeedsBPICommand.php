<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{Bids, Product, Projects, ProjectsStatus};

/**
 * https://unilend.atlassian.net/browse/RUN-1065
 * https://tousnosprojets.bpifrance.fr/var/storage/20160422-bpifrance-tnp-import-projets.pdf
 */
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
        $logger                 = $this->getContainer()->get('monolog.logger.console');
        $projectRepository      = $entityManager->getRepository(Projects::class);

        /** @var \bids $bids */
        $bids = $entityManagerSimulator->getRepository('bids');
        /** @var \loans $loans */
        $loans = $entityManagerSimulator->getRepository('loans');

        $projectStatus = [
            ProjectsStatus::STATUS_PUBLISHED,
            ProjectsStatus::STATUS_FUNDED,
            ProjectsStatus::STATUS_CANCELLED,
            ProjectsStatus::STATUS_CONTRACTS_SIGNED,
            ProjectsStatus::STATUS_FINISHED,
            ProjectsStatus::STATUS_LOST
        ];

        $hostUrl  = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . getenv('HOST_DEFAULT_URL');
        $userPath = $this->getContainer()->getParameter('directory.user');
        $partner  = strtolower($input->getArgument('partner'));
        $products = $entityManager->getRepository(Product::class)->findAvailableProductsByClient();

        $projectsToSerialise = [];
        $projects            = $projectRepository->findBy([
            'status'    => $projectStatus,
            'idProduct' => $products
        ]);

        foreach ($projects as $project) {
            if ((empty($project->getDatePublication()) || empty($project->getDateRetrait())) && 'bpi' === $partner) {
                $logger->warning('The project ' . $project->getIdProject() . ' will not be added into xml file. No publishing/withdrawal date was set', [
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'id_project' => $project->getIdProject()
                ]);
                continue;
            }

            if ($project->getStatus() === ProjectsStatus::STATUS_PUBLISHED) {
                $totalBids = $bids->sum('id_project = ' . $project->getIdProject() . ' AND status = ' . Bids::STATUS_PENDING, 'amount') / 100;
            } else {
                $totalBids = $bids->sum('id_project = ' . $project->getIdProject() . ' AND status = ' . Bids::STATUS_ACCEPTED, 'amount') / 100;
            }

            if ($totalBids > $project->getAmount()) {
                $totalBids = $project->getAmount();
            }

            $company = $project->getIdCompany();
            $details = [
                'reference_partenaire'             => '045',
                'date_export'                      => date('Y-m-d'),
                'reference_projet'                 => $project->getIdProject(),
                'impact_social'                    => 'NON',
                'impact_environnemental'           => 'NON',
                'impact_culturel'                  => 'NON',
                'impact_eco'                       => 'OUI',
                'categorie'                        => [
                    'categorie1' => $this->getBPISector($company->getSector())
                ],
                'mots_cles_nomenclature_operateur' => '',
                'mode_financement'                 => 'PRR',
                'type_porteur_projet'              => 'ENT',
                'qualif_ESS'                       => 'NON',
                'code_postal'                      => $company->getIdAddress()->getZip(),
                'ville'                            => $company->getIdAddress()->getCity(),
                'titre'                            => $translator->trans('company-sector_sector-' . $company->getSector()) . ' - ' . $company->getIdAddress()->getCity(),
                'description'                      => $project->getNatureProject(),
                'url'                              => $hostUrl . $router->generate('project_detail',
                        ['projectSlug' => $project->getSlug()]) . '/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent',
                'url_photo'                        => $hostUrl . '/images/dyn/projets/169/' . $project->getPhotoProjet(),
                'date_debut_collecte'              => $project->getDatePublication() ? $project->getDatePublication()->format('Y-m-d') : '',
                'date_fin_collecte'                => $project->getDateRetrait() ? $project->getDateRetrait()->format('Y-m-d') : '',
                'montant_recherche'                => $project->getAmount(),
                'montant_collecte'                 => number_format($totalBids, 0, ',', ''),
                'nb_contributeurs'                 => $loans->getNbPreteurs($project->getIdProject()),
                'succes'                           => $this->getBPISuccess($project->getStatus())
            ];

            if ('crowdlending' === $partner) {
                $details['duree_du_pret'] = $project->getPeriod();
                $details['taux']          = round($projectRepository->getAverageInterestRate($project), 1);
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

    /**
     * @param $partner
     *
     * @return string
     */
    private function getFileName(string $partner): string
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
    private function getBPISector(string $sector): string
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
    private function getBPISuccess(int $status): string
    {
        switch ($status) {
            case ProjectsStatus::STATUS_PUBLISHED:
            case ProjectsStatus::STATUS_LOST:
            case ProjectsStatus::STATUS_CONTRACTS_SIGNED:
            case ProjectsStatus::STATUS_FINISHED:
            case ProjectsStatus::STATUS_FUNDED:
                return 'OUI';
            case ProjectsStatus::STATUS_CANCELLED:
                return 'NON';
            default:
                return '';
        }
    }
}
