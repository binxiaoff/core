<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Bids, Clients, CompanyRating, OperationType, Product, ProjectProductAssessment, Projects, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\IfuManager;

class statsController extends bootstrap
{
    public function initialize()
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1200);

        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_STATISTICS);

        $this->menu_admin = 'stats';
    }

    public function _default()
    {
        header('Location: /queries');
        die;
    }

    // Ressort un csv avec les process des users
    public function _etape_inscription()
    {
        // Récup des dates
        if (isset($_POST['date1']) && $_POST['date1'] != '') {
            $d1    = explode('/', $_POST['date1']);
            $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
        } else {
            $_POST['date1'] = date('d/m/Y', strtotime('first day of this month'));
            $date1          = date('Y-m-d', strtotime('first day of this month'));

        }

        if (isset($_POST['date2']) && $_POST['date2'] != '') {
            $d2    = explode('/', $_POST['date2']);
            $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
        } else {
            $_POST['date2'] = date('d/m/Y', strtotime('last day of this month'));
            $date2          = date('Y-m-d', strtotime('last day of this month'));

        }

        $sql = '
            SELECT
                c.id_client,
                c.nom,
                c.prenom,
                c.email,
                c.telephone,
                c.mobile,
                c.added,
                c.etape_inscription_preteur,
                c.source,
                c.source2
            FROM clients c
            INNER JOIN wallet w ON c.id_client = w.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType::LENDER . '"
            WHERE c.etape_inscription_preteur > 0 
                AND c.status = ' . Clients::STATUS_ONLINE . '
                AND c.added >= "' . $date1 . ' 00:00:00"
                AND c.added <= "' . $date2 . ' 23:59:59"';

        $result = $this->bdd->query($sql);

        $this->L_clients = array();
        while ($record = $this->bdd->fetch_assoc($result)) {
            $this->L_clients[] = $record;
        }

        if (isset($_POST['recup'])) {
            $this->autoFireView = false;
            $this->hideDecoration();

            header('Content-type: application/vnd.ms-excel');
            header('Content-disposition: attachment; filename="Export_etape_inscription.csv"');

            if ($_POST['spy_date1'] != '') {
                $d1    = explode('/', $_POST['spy_date1']);
                $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
            } else {
                $date1 = date('Y-m-d', strtotime('first day of this month'));
            }

            if ($_POST['spy_date2'] != '') {
                $d2    = explode('/', $_POST['spy_date2']);
                $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
            } else {
                $date2 = date('Y-m-d', strtotime('last day of this month'));
            }

            $sql = '
                SELECT
                    c.id_client,
                    c.nom,
                    c.prenom,
                    c.email,
                    c.telephone,
                    c.mobile,
                    c.added,
                    c.etape_inscription_preteur,
                    c.source,
                    c.source2
                FROM clients c
                INNER JOIN wallet w ON c.id_client = w.id_client
                INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType::LENDER . '"
                WHERE c.etape_inscription_preteur > 0 
                    AND c.status = ' . Clients::STATUS_ONLINE . ' 
                    AND c.added >= "' . $date1 . ' 00:00:00"
                    AND c.added <= "' . $date2 . ' 23:59:59"';

            $result = $this->bdd->query($sql);

            $this->L_clients = array();
            while ($record = $this->bdd->fetch_assoc($result)) {
                $this->L_clients[] = $record;
            }

            $csv = "id_client;nom;prenom;email;tel;date_inscription;etape_inscription;Source;Source 2;\n";

            foreach ($this->L_clients as $u) {
                $csv .= utf8_decode($u['id_client']) . ';' . utf8_decode($u['nom']) . ';' . utf8_decode($u['prenom']) . ';' . utf8_decode($u['email']) . ';' . utf8_decode($u['telephone'] . ' ' . $u['mobile']) . ';' . utf8_decode($this->dates->formatDate($u['added'],
                        'd/m/Y')) . ';' . utf8_decode($u['etape_inscription_preteur']) . ';' . $u['source'] . ';' . $u['source2'] . ';' . "\n";
            }

            print($csv);
        }
    }

    private function downloadIfufile($fileName)
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\IfuManager $ifuManager */
        $ifuManager = $this->get('unilend.service.ifu_manager');
        $filePath   = $ifuManager->getStorageRootPath() . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\";");
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            ob_clean();
            flush();
            readfile($filePath);
            exit;
        } else {
            echo "Le fichier n'a pas été généré cette nuit. Le cron s'execute que de novembre à mars";
        }
    }

    public function _requete_infosben_download()
    {
        $this->downloadIfufile(IfuManager::FILE_NAME_INFOSBEN);
    }

    public function _requete_beneficiaires_download()
    {
        $this->downloadIfufile(IfuManager::FILE_NAME_BENEFICIARY);
    }

    /**
     * File generated by cron QueriesLenderRevenueCommand.php
     * Only November to March
     */
    public function _requete_revenus_download()
    {
        $this->downloadIfufile(IfuManager::FILE_NAME_INCOME);
    }

    public function _requete_encheres()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $header = "id_project;id_bid;id_client;added;statut;amount;rate;";
        $header = utf8_encode($header);

        $csv = "";
        $csv .= $header . " \n";

        $sql = 'SELECT 
                  id_project, 
                  id_bid, 
                  (SELECT id_client FROM wallet w WHERE w.id = b.id_lender_account) AS id_client, 
                  added, 
                  (CASE status WHEN ' . Bids::STATUS_PENDING . ' THEN "En cours" WHEN ' . Bids::STATUS_ACCEPTED . ' THEN "OK" WHEN ' . Bids::STATUS_REJECTED . ' THEN "KO" END) AS Statut, 
                  ROUND((amount / 100), 0), REPLACE(rate, ".", ",") AS rate 
                FROM bids b';

        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($resultat)) {
            for ($i = 0; $i <= 6; $i++) {
                $csv .= $record[$i] . ";";
            }
            $csv .= " \n";
        }

        $titre = 'toutes_les_encheres_' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

    public function _tous_echeanciers_pour_projet()
    {
        if (isset($_POST['form_envoi_params']) && $_POST['form_envoi_params'] == "ok" && false == empty($_POST['id_projet'])) {
            $this->autoFireView = false;
            $this->hideDecoration();

            $header = "Id_echeancier;Id_client;Id_projet;Id_loan;Ordre;Montant;Capital;Capital_restant;Interets;Prelevements_obligatoires;Retenues_source;CSG;Prelevements_sociaux;Contributions_additionnelles;Prelevements_solidarite;CRDS;Date_echeance;Date_echeance_reel;Date_echeance_emprunteur;Date_echeance_emprunteur_reel;Status;";
            $header = utf8_encode($header);

            $csv = "";
            $csv .= $header . " \n";

            $sql = 'SELECT
                      e.id_echeancier,
                      w.id_client,
                      e.id_project,
                      e.id_loan,
                      e.ordre,
                      e.montant,
                      e.capital,
                      SUM(e.capital - e.capital_rembourse) AS capitalRestant,
                      e.interets,
                      SUM(prelevements_obligatoires.amount) AS prelevements_obligatoires,
                      SUM(retenues_source.amount) AS retenues_source,
                      SUM(csg.amount) AS csg,
                      SUM(prelevements_sociaux.amount) AS prelevements_sociaux,
                      SUM(contributions_additionnelles.amount) AS contributions_additionnelles,
                      SUM(prelevements_solidarite.amount) AS prelevements_solidarite,
                      SUM(crds.amount) AS crds,
                      e.date_echeance,
                      e.date_echeance_reel,
                      e.date_echeance_emprunteur,
                      e.date_echeance_emprunteur_reel,
                      e.status
                    FROM echeanciers e
                      INNER JOIN wallet w ON w.id = e.id_lender
                      LEFT JOIN operation prelevements_obligatoires ON prelevements_obligatoires.id_repayment_schedule = e.id_echeancier AND prelevements_obligatoires.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES . '\')
                      LEFT JOIN operation retenues_source ON retenues_source.id_repayment_schedule = e.id_echeancier AND retenues_source.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_RETENUES_A_LA_SOURCE . '\')
                      LEFT JOIN operation csg ON csg.id_repayment_schedule = e.id_echeancier AND csg.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_CSG . '\')
                      LEFT JOIN operation prelevements_sociaux ON prelevements_sociaux.id_repayment_schedule = e.id_echeancier AND prelevements_sociaux.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX . '\')
                      LEFT JOIN operation contributions_additionnelles ON contributions_additionnelles.id_repayment_schedule = e.id_echeancier AND contributions_additionnelles.id_type  = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES . '\')
                      LEFT JOIN operation prelevements_solidarite ON prelevements_solidarite.id_repayment_schedule = e.id_echeancier AND prelevements_solidarite.id_type  = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE . '\')
                      LEFT JOIN operation crds ON crds.id_repayment_schedule = e.id_echeancier AND crds.id_type  = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_CRDS . '\')
                    WHERE e.id_project = ' . $_POST['id_projet'] . '
                    GROUP BY e.id_echeancier';

            $resultat = $this->bdd->query($sql);
            while ($record = $this->bdd->fetch_array($resultat)) {
                $csv .= $record['id_echeancier'] . ";" . $record['id_client'] . ";" . $record['id_project'] . ";" . $record['id_loan'] . ";" . $record['ordre'] . ";" . $record['montant'] . ";" . $record['capital'] . ";" . $record['capitalRestant'] . ";" . $record['interets'] . ";" . $record['prelevements_obligatoires'] . ";" . $record['retenues_source'] . ";" . $record['csg'] . ";" . $record['prelevements_sociaux'] . ";" . $record['contributions_additionnelles'] . ";" . $record['prelevements_solidarite'] . ";" . $record['crds'] . ";" . $record['date_echeance'] . ";" . $record['date_echeance_reel'] . ";" . $record['date_echeance_emprunteur'] . ";" . $record['date_echeance_emprunteur_reel'] . ";" . $record['status'] . ";";
                $csv .= " \n";
            }

            $titre = 'tous_echeanciers_pour_projet_' . date('Ymd');
            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

            print(utf8_decode($csv));
        }
    }

    private function exportCSV($aData, $sFileName, array $aHeaders = null)
    {
        $this->bdd->close();

        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (count($aHeaders) > 0) {
            foreach ($aHeaders as $iIndex => $sColumnName) {
                $oActiveSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName);
            }
        }

        foreach ($aData as $iRowIndex => $aRow) {
            $iColIndex = 0;
            foreach ($aRow as $sCellValue) {
                $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $sCellValue);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $sFileName . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        $oWriter->save('php://output');

        die;
    }

    public function _autobid_statistic()
    {
        $oProject = $this->loadData('projects');

        if (isset($_POST['date_from'], $_POST['date_to']) && false === empty($_POST['date_from']) && false === empty($_POST['date_to'])) {
            $aProjectList = $oProject->getAutoBidProjectStatistic(
                \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_from'] . ' 00:00:00'),
                \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_to'] . ' 23:59:59')
            );

            $this->aProjectList = [];
            foreach ($aProjectList as $aProject) {
                $fRisk                = constant(Projects::class . '::RISK_' . trim($aProject['risk']));
                $this->aProjectList[] = [
                    'id_project'                => $aProject['id_project'],
                    'percentage'                => round(($aProject['amount_total_autobid'] / $aProject['amount_total']) * 100, 2) . ' %',
                    'period'                    => $aProject['period'],
                    'risk'                      => $fRisk,
                    'bids_nb'                   => $aProject['bids_nb'],
                    'avg_amount'                => $aProject['avg_amount'],
                    'weighted_avg_rate'         => round($aProject['weighted_avg_rate'], 1),
                    'avg_amount_autobid'        => $aProject['avg_amount_autobid'],
                    'weighted_avg_rate_autobid' => false === empty($aProject['weighted_avg_rate_autobid']) ? round($aProject['weighted_avg_rate_autobid'], 2) : '',
                    'status_label'              => $aProject['status_label'],
                    'date_fin'                  => $aProject['date_fin']
                ];
            }

            if (isset($_POST['extraction_csv'])) {
                $aHeader = array(
                    'id_project',
                    'pourcentage',
                    'period',
                    'risk',
                    'nombre de bids',
                    'montant moyen',
                    'taux moyen pondéré',
                    'montant moyen autolend',
                    'taux moyen pondéré autolend',
                    'status',
                    'date fin de projet'
                );
                $this->exportCSV($this->aProjectList, 'statistiques_autolends' . date('Ymd'), $aHeader);
            }
        }
    }

    public function _requete_source_emprunteurs()
    {
        /** @var \clients $oClient */
        $oClient          = $this->loadData('clients');
        $this->aBorrowers = array();

        if (isset($_POST['dateStart'], $_POST['dateEnd']) && false === empty($_POST['dateStart']) && false === empty($_POST['dateEnd'])) {
            $oDateTimeStart = \DateTime::createFromFormat('d/m/Y', $_POST['dateStart']);
            $oDateTimeEnd   = \DateTime::createFromFormat('d/m/Y', $_POST['dateEnd']);

            if (isset($_POST['queryOptions']) && 'allLines' == $_POST['queryOptions']) {
                $this->aBorrowers = $oClient->getBorrowersContactDetailsAndSource($oDateTimeStart, $oDateTimeEnd, false);
            }
            if (isset($_POST['queryOptions']) && in_array($_POST['queryOptions'], array(
                    'groupBySirenWithDetails',
                    'groupBySiren'
                ))
            ) {
                $this->aBorrowers = $oClient->getBorrowersContactDetailsAndSource($oDateTimeStart, $oDateTimeEnd, true);

                if ('groupBySirenWithDetails' == $_POST['queryOptions']) {
                    foreach ($this->aBorrowers as $iKey => $aBorrower) {
                        if ($aBorrower['countSiren'] > 1) {
                            $this->aBorrowers[$iKey]['firstEntrySource'] = $oClient->getFirstSourceForSiren($aBorrower['siren'], $oDateTimeStart, $oDateTimeEnd);
                            $this->aBorrowers[$iKey]['lastEntrySource']  = $oClient->getLastSourceForSiren($aBorrower['siren'], $oDateTimeStart, $oDateTimeEnd);
                            $this->aBorrowers[$iKey]['lastLabel']        = $this->aBorrowers[$iKey]['label'];
                            $aHeaderExtended                             = array_keys(($this->aBorrowers[$iKey]));
                        }
                    }
                }
            }

            if (isset($_POST['extraction_csv'])) {
                $aHeader = isset($aHeaderExtended) ? $aHeaderExtended : array_keys(array_shift($this->aBorrowers));
                $this->exportCSV($this->aBorrowers, 'requete_source_emprunteurs' . date('Ymd'), $aHeader);
            }
        }
    }

    public function _declarations_bdf()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $declarationList       = $entityManager->getRepository('UnilendCoreBusinessBundle:TransmissionSequence')->findAll();
        $declarationPath       = $this->getParameter('path.sftp') . 'bdf/emissions/declarations_mensuelles/';
        $this->declarationList = [];

        if (isset($this->params[0], $this->params[1]) && 'file' === $this->params[0] && is_string($this->params[1])) {
            $this->download($declarationPath . $this->params[1]);
        }
        foreach ($declarationList as $declaration) {
            $absoluteFileName = $declarationPath . $declaration->getElementName();

            if (file_exists($absoluteFileName)) {
                if ('01' === $declaration->getAdded()->format('m')) {
                    $year = $declaration->getAdded()->format('Y') - 1;
                } else {
                    $year = $declaration->getAdded()->format('Y');
                }
                $declarationDate                = \DateTime::createFromFormat('Ym', substr($declaration->getElementName(), 6, 6));
                $this->declarationList[$year][] = [
                    'declarationDate' => strftime('%B %Y', $declarationDate->getTimestamp()),
                    'creationDate'    => $declaration->getAdded()->format('d/m/Y H:i'),
                    'link'            => '/stats/declarations_bdf/file/' . $declaration->getElementName(),
                    'fileName'        => $declaration->getElementName()
                ];
            }
        }
    }

    /**
     * @param string $filePath
     */
    protected function download($filePath)
    {
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '";');
            @readfile($filePath);

            exit;
        }
    }

    public function _projects_eligibility()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $assessmentRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityAssessment');
        $companyRatingHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $companyRatingRepository        = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRating');
        $extraction                     = [];

        $evaluatedProjects = $assessmentRepository->getEvaluatedProjects();

        $productBLend    = $entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findOneBy(['label' => Product::PRODUCT_BLEND]);
        $productIfp      = $entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findOneBy(['label' => 'amortization_ifp_fr']);
        $productProfLib  = $entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findOneBy(['label' => 'amortization_ifp_liberal_profession_fr']);
        $productTakeover = $entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findOneBy(['label' => 'amortization_ifp_takeover_fr']);

        foreach ($evaluatedProjects as $project) {
            $company              = $project->getIdCompany();
            $motivation           = $entityManager->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->find($project->getIdBorrowingMotive());
            $status               = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => $project->getStatus()]);
            $projectNote          = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);
            $companyRatingHistory = $companyRatingHistoryRepository->findOneBy(['idCompany' => $company->getIdCompany()]);
            $scoreAltares         = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_ALTARES_SCORE_20
            ]);
            $trafficLightEuler    = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT
            ]);
            $gradeEuler           = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_EULER_HERMES_GRADE
            ]);
            $scoreInfolegale      = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_INFOLEGALE_SCORE
            ]);

            $source = '';
            if ($company->getIdClientOwner() && false == $project->getCreateBo() && false === empty($company->getIdClientOwner()->getSource())) {
                $source = $company->getIdClientOwner()->getSource();
            }

            $partner = '';
            if ($project->getIdPartner()) {
                $partner = $project->getIdPartner()->getIdCompany()->getName();
            }

            $adviserName = 'Non';
            if ($project->getIdPrescripteur()) {
                $adviser = $entityManager->getRepository('UnilendCoreBusinessBundle:Prescripteurs')->find($project->getIdPrescripteur());
                if ($adviser) {
                    $adviserClient = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($adviser->getIdClient());
                    if ($adviserClient) {
                        $adviserName = $adviserClient->getPrenom() . ' ' . $adviserClient->getNom();
                    }
                }
            }

            $row = [
                'id projet'        => $project->getIdProject(),
                'added'            => $project->getAdded()->format('d/m/Y'),
                'company_name'     => $company->getName(),
                'siren'            => $company->getSiren(),
                'date_creation'    => $company->getDateCreation() ? $company->getDateCreation()->format('d/m/Y') : '',
                'source'           => $source,
                'partner'          => $partner,
                'adviser'          => $adviserName,
                'motivation'       => $motivation ? $motivation->getMotive() : '',
                'amount'           => $project->getAmount(),
                'duration'         => $project->getPeriod(),
                'prescore'         => $projectNote ? ($projectNote->getPreScoring() ? $projectNote->getPreScoring() : 'PAS DE DONNEE') : 'Pas de donnée',
                'score_altares'    => $scoreAltares ? $scoreAltares->getValue() : 'Pas de donnée',
                'traffic_light'    => $trafficLightEuler ? $trafficLightEuler->getValue() : 'Pas de donnée',
                'grade_euler'      => $gradeEuler ? $gradeEuler->getValue() : 'Pas de donnée',
                'score_infolegale' => $scoreInfolegale ? $scoreInfolegale->getValue() : 'Pas de donnée',
                'turnover'         => $project->getCaDeclaraClient(),
                'own_funds'        => $project->getFondsPropresDeclaraClient(),
                'operation_income' => $project->getResultatExploitationDeclaraClient(),
                'is_rcs'           => empty($company->getRcs()) ? 'Non' : 'Oui',
                'naf'              => $company->getCodeNaf(),
                'status'           => $status ? $status->getLabel() : '',
            ];

            $projectEligibilityAssessment = $assessmentRepository->findOneBy(
                ['idProject' => $project],
                ['added' => 'DESC', 'id' => 'DESC']
            );

            $row['common_check']           = $projectEligibilityAssessment->getStatus() ? 'OK' : $projectEligibilityAssessment->getIdRule()->getLabel();
            $row['b_lend_check']           = 'Pas d\'évaluation';
            $row['ifp_product_check']      = 'Pas d\'évaluation';
            $row['prof_lib_product_check'] = 'Pas d\'évaluation';
            $row['takeover_product_check'] = 'Pas d\'évaluation';
            if ('OK' === $row['common_check']) {
                $productAssessment   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectProductAssessment')->findOneBy([
                    'idProject' => $project,
                    'idProduct' => $productBLend,
                    'status'    => ProjectProductAssessment::STATUS_CHECK_KO,
                ], ['added' => 'DESC']);
                $row['b_lend_check'] = 'OK';
                if ($productAssessment) {
                    $productAttribute = $entityManager->getRepository('UnilendCoreBusinessBundle:ProductAttribute')->findOneBy([
                        'idProduct' => $productAssessment->getIdProduct(),
                        'idType'    => $productAssessment->getIdProductAttributeType()
                    ]);

                    $row['b_lend_check'] = $productAssessment->getIdProductAttributeType()->getLabel();
                    $row['b_lend_check'] .= $productAttribute->getIdRule() ? ' (' . $productAttribute->getIdRule()->getLabel() . ')' : '';
                }

                $productAssessment        = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectProductAssessment')->findOneBy([
                    'idProject' => $project,
                    'idProduct' => $productIfp,
                    'status'    => ProjectProductAssessment::STATUS_CHECK_KO,
                ], ['added' => 'DESC']);
                $row['ifp_product_check'] = 'OK';
                if ($productAssessment) {
                    $row['ifp_product_check'] = $productAssessment->getIdProductAttributeType()->getLabel();
                }

                $productAssessment             = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectProductAssessment')->findOneBy([
                    'idProject' => $project,
                    'idProduct' => $productProfLib,
                    'status'    => ProjectProductAssessment::STATUS_CHECK_KO,
                ], ['added' => 'DESC']);
                $row['prof_lib_product_check'] = 'OK';
                if ($productAssessment) {
                    $row['prof_lib_product_check'] = $productAssessment->getIdProductAttributeType()->getLabel();
                }

                $productAssessment             = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectProductAssessment')->findOneBy([
                    'idProject' => $project,
                    'idProduct' => $productTakeover,
                    'status'    => ProjectProductAssessment::STATUS_CHECK_KO,
                ], ['added' => 'DESC']);
                $row['takeover_product_check'] = 'OK';
                if ($productAssessment) {
                    $row['takeover_product_check'] = $productAssessment->getIdProductAttributeType()->getLabel();
                }
            }

            $extraction[] = $row;
        }

        $header = [
            'id_project',
            'date dépôt',
            'raison sociale',
            'siren',
            'date_creation',
            'source',
            'partenaire',
            'prescripteur',
            'motif exprimé',
            'montant',
            'durée',
            'prescore',
            'score Altares',
            'trafficLight Euler',
            'grade Euler',
            'score Infolegale',
            'CA',
            'FP',
            'REX',
            'RCS',
            'NAF',
            'statut projet',
            'tronc commun',
            'b-lend',
            'produit ifp maison',
            'prof lib',
            'reprise et transmission'
        ];

        $this->exportCSV($extraction, 'projects_eligibility-' . date('YmdHi'), $header);
    }

    public function _requete_crs_dac()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $year     = date('Y') - 1;
        $fileName = 'preteurs_crs_dac' . $year . '.xlsx';
        $filePath = $this->getParameter('path.protected') . '/queries/' . $fileName;

        if (file_exists($filePath)) {
            $this->download($filePath);
        } else {
            echo "Le fichier n'a pas été généré. ";
        }
    }

    public function _logs_webservices()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WsMonitoringManager $wsMonitoringManager */
        $wsMonitoringManager = $this->get('unilend.service.ws_monitoring_manager');
        $data                = $wsMonitoringManager->getDataForChart();
        $this->chartData     = json_encode($data);
    }

    public function _loi_eckert()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $filePath = $this->getParameter('path.protected') . '/queries/loi_eckert.xlsx';

        if (file_exists($filePath)) {
            $this->download($filePath);
        } else {
            echo "Le fichier n'a pas été généré. ";
        }
    }

    public function _reporting_sfpmei()
    {
        $directoryPath       = $this->getParameter('path.protected') . '/queries/';
        $this->reportingList = [];

        if (
            isset($this->params[0], $this->params[1])
            && 'file' === $this->params[0]
            && is_string($this->params[1])
            && false !== strpos($this->params[1], 'reporting_mensuel_sfpmei')
        ) {
            $this->download($directoryPath . $this->params[1]);
        }

        $files = scandir($directoryPath);
        foreach ($files as $file) {
            if ('reporting_mensuel_sfpmei' == substr($file, 0, 24)) {
                $fileDate                                     = \DateTime::createFromFormat('Ymd', substr($file, -13, 8));
                $this->reportingList[$fileDate->format('Ym')] = [
                    'displayDate' => strftime('%B %Y', $fileDate->getTimestamp()),
                    'link'        => '/stats/reporting_sfpmei/file/' . $file,
                    'name'        => $file
                ];
                krsort($this->reportingList, SORT_NUMERIC);
            }
        }
    }
}
