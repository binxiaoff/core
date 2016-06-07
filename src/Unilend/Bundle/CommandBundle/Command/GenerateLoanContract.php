<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Service\Simulator\EntityManager;
use Unilend\core\Loader;

class GenerateLoanContract extends ContainerAwareCommand
{
    /** @var string */
    private $sRootPath;
    /** @var  array */
    private $aConfig;

    protected function configure()
    {
        $this
            ->setName('lender:loan_contract')
            ->setDescription('Generates loan contract pdf document')
            ->setHelp(<<<EOF
The <info>lender:loan_contract</info> command generates the loan contract pdf document for the lenders.
<info>php bin/console lender:loan_contract</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sRootDir        = $this->getContainer()->getParameter('kernel.root_dir');
        $this->sRootPath = $sRootDir . '/../';
        $this->aConfig   = Loader::loadConfig();

        require_once $this->sRootPath . 'core/command.class.php';
        require_once $this->sRootPath . 'core/controller.class.php';
        require_once $this->sRootPath . 'apps/default/bootstrap.php';
        require_once $this->sRootPath . 'apps/default/controllers/pdf.php';

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \loans $loans */
        $loans = $entityManager->getRepository('loans');
        /** @var \projects $projects */
        $projects = $entityManager->getRepository('projects');
        /** load for class constants */
        $entityManager->getRepository('projects_status');
        /** @var LoggerInterface $oLogger */
        $oLogger = $this->getContainer()->get('monolog.logger.console');

        $aProjectStatus = array(
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::DEFAUT,
            \projects_status::REMBOURSEMENT_ANTICIPE,
            \projects_status::PROBLEME_J_X,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE
        );
        $aProjects = $projects->selectProjectsByStatus(implode(', ', $aProjectStatus), '', '', array(), '', '', false);

        if (count($aProjects) > 0) {
            $iIndex       = 0;
            $sProjectList = '';
            foreach ($aProjects as $project) {
                $sProjectList .= ($iIndex == 0 ? '' : ',') . $project['id_project'];
                $iIndex++;
            }

            if (! is_dir($this->sRootPath . 'protected/declarationContratPret/')) {
                mkdir($this->sRootPath . 'protected/declarationContratPret/');
            }

            $aLoans = $loans->select('status = "0" AND fichier_declarationContratPret = "" AND id_project IN(' . $sProjectList . ')', 'id_loan ASC', 0, 10);
            if (count($aLoans) > 0) {
                foreach ($aLoans as $aLoan) {
                    $projects->get($aLoan['id_project'], 'id_project');

                    if (! is_dir($this->sRootPath . 'protected/declarationContratPret/' . substr($aLoan['added'], 0, 4))) {
                        mkdir($this->sRootPath . 'protected/declarationContratPret/' . substr($aLoan['added'], 0, 4));
                    }
                    $path = $this->sRootPath . 'protected/declarationContratPret/' . substr($aLoan['added'], 0, 4) . '/' . $projects->slug . '/';

                    if (! is_dir($path)) {
                        mkdir($path);
                    }
                    $sDocumentName = 'Unilend_declarationContratPret_' . $aLoan['id_loan'] . '.pdf';
                    $oCommandPdf = new \Command('pdf', 'declarationContratPret_html', array(
                                                    $aLoan['id_loan'], $path
                                                ), 'fr');

                    $oPdf = new \pdfController($oCommandPdf, $this->aConfig, 'default');
                    $oPdf->setContainer($this->getContainer());

                    $_SERVER['REQUEST_URI'] = '';
                    $oPdf->initialize();
                    $oPdf->autoFireView = true;

                    try {
                        $oPdf->_declarationContratPret_html($aLoan['id_loan'], $path);
                    } catch (\Exception $exception) {
                        $oLogger->error('Could not generate the loan contract pdf for id_loan=' . $aLoan['id_loan'] . ' and id_project=' . $aLoan['id_project'] .
                            ' Exception message: ' . $exception->getMessage() . ' -In file: ' . $exception->getFile() . ' -At line: ' . $exception->getLine(),
                            array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $aLoan['id_project'], 'id_loan' => $aLoan['id_loan']));
                        continue;
                    }
                    $loans->get($aLoan['id_loan'], 'id_loan');
                    $loans->fichier_declarationContratPret = $sDocumentName;
                    $loans->update();
                    $oLogger->info('Loan contract pdf generated for id_loan=' . $aLoan['id_loan'] . ' ', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $aLoan['id_project'], 'id_loan' => $aLoan['id_loan']));
                }
            }
        }
    }
}
