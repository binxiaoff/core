<?php

use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Clients, CompanyStatus, Elements, Loans, ProjectCgv, ProjectsStatus, UnderlyingContract, UniversignEntityInterface};
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentScheduleManager;

class pdfController extends bootstrap
{
    /**
     * Path of tmp pdf file
     */
    const TMP_PATH_FILE = '/tmp/pdfUnilend/';

    /** @var Pdf */
    private $oSnapPdf;
    /** @var LoggerInterface */
    private $oLogger;
    /** @var projects_pouvoir */
    private $oProjectsPouvoir;
    /** @var loans */
    public $oLoans;
    /**
     * $clients may also be used in common methods so use this instance for document related client
     * @var \clients
     */
    public $pdfClient;

    /**
     * @desc contains html returns ($this->execute())
     * @var    string $sDisplay
     */
    public $sDisplay;

    public function initialize()
    {
        parent::initialize();

        if (false === isset($this->params)) {
            $this->params = $this->Command->getParameters();
        }

        $this->hideDecoration();

        $this->oSnapPdf = new Pdf('/usr/local/bin/wkhtmltopdf');
        $this->oLogger  = $this->get('logger');
    }

    /**
     * @param string $sView name of view file
     */
    public function setDisplay($sView = '', $sContent = null)
    {
        $this->content = (false === is_null($sContent)) ? $sContent : '';
        $this->setView($sView);

        ob_start();
        if ($this->autoFireHead) {
            $this->fireHead();
        }
        if ($this->autoFireHeader) {
            $this->fireHeader();
        }
        if ($this->autoFireView) {
            $this->fireView();
        }
        if ($this->autoFireFooter) {
            $this->fireFooter();
        }

        $this->sDisplay = ob_get_contents();
        ob_end_clean();

        $this->view = '';
    }

    /**
     * @param string $sPathPdf full path with name of pdf
     * @param string $sTypePdf for log and css
     */
    public function WritePdf($sPathPdf, $sTypePdf = 'authority')
    {
        if (1 !== preg_match('/\.pdf$/i', $sPathPdf)) {
            $sPathPdf .= '.pdf';
        }

        $iTimeStartPdf = microtime(true);

        switch ($sTypePdf) {
            case 'authority':
                $this->oSnapPdf->setOption('footer-right', '[page]/[toPage]');
                $this->oSnapPdf->setOption('footer-font-size', '7');
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                break;
            case 'warranty':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                break;
            case 'claims':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/styleClaims.css');
                break;
            case 'dec_pret':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/declarationContratPret/print.css');
                break;
            default:
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                break;
        }
        $this->oSnapPdf->generateFromHtml($this->sDisplay, $sPathPdf, array(), true);

        $iTimeEndPdf = microtime(true) - $iTimeStartPdf;

        $this->oLogger->info($sTypePdf . ' PDF successfully generated in ' . round($iTimeEndPdf, 2) . ' seconds', array('class' => __CLASS__, 'function' => __FUNCTION__));
    }

    /**
     * @param string $sPathPdf full path with name of pdf
     * @param string $sNamePdf pdf's name for client
     */
    public function ReadPdf($sPathPdf, $sNamePdf)
    {
        if (1 !== preg_match('/\.pdf$/i', $sPathPdf)) {
            $sPathPdf .= '.pdf';
        }

        header('Content-disposition: attachment; filename=' . $sNamePdf . '.pdf');
        header('Content-Type: application/force-download');

        if (false === readfile($sPathPdf)) {
            $this->oLogger->error('File "' . $sPathPdf . '"" not readable', array('class' => __CLASS__, 'function' => __FUNCTION__));
        }
    }

    public function _projet()
    {
        $this->pdfClient = $this->loadData('clients');

        if (
            isset($this->params[0], $this->params[1])
            && 1 === preg_match('/^[0-9a-f-]{32,36}$/', $this->params[0])
            && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)
            && $this->pdfClient->get($this->params[0], 'hash')
            && $this->companies->get($this->pdfClient->id_client, 'id_client_owner')
            && $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
            && $this->projects->status != ProjectsStatus::PRET_REFUSE
        ) {
            $proxy   = $this->commonProxy();
            $mandate = $this->commonMandate();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager $beneficialOwnerManager */
            $beneficialOwnerManager                 = $this->get('unilend.service.beneficial_owner_manager');
            $projectNeedsBeneficialOwnerDeclaration = $beneficialOwnerManager->projectNeedsBeneficialOwnerDeclaration($this->projects);
            if ($projectNeedsBeneficialOwnerDeclaration) {
                $beneficialOwnerDeclaration = $beneficialOwnerManager->createProjectBeneficialOwnerDeclaration($this->projects, $this->pdfClient);
            }

            if ('read' === $proxy['action'] && 'read' === $mandate['action'] && ($projectNeedsBeneficialOwnerDeclaration && 'read' === $beneficialOwnerDeclaration['action'])) {
                /** @var \Symfony\Component\Routing\RouterInterface $router */
                $router = $this->get('router');
                header('Location: ' . $router->generate('universign_signature_status', ['signatureType' => \Unilend\Bundle\FrontBundle\Controller\UniversignController::SIGNATURE_TYPE_PROJECT, 'signatureId' => $this->projects->id_project, 'clientHash' => $this->pdfClient->hash]));
                exit;
            } elseif (
                'redirect' === $proxy['action'] && 'redirect' === $mandate['action'] && ($projectNeedsBeneficialOwnerDeclaration && 'redirect' === $beneficialOwnerDeclaration['action'])
                && $proxy['url'] === $mandate['url'] && ($projectNeedsBeneficialOwnerDeclaration && $proxy['url'] === $beneficialOwnerDeclaration['url'])
            ) {
                header('Location: ' . $proxy['url']);
                exit;
            } elseif ('sign' === $proxy['action'] || 'sign' === $mandate['action'] || ($projectNeedsBeneficialOwnerDeclaration && 'sign' === $beneficialOwnerDeclaration['action'])) {
                /** @var \Symfony\Component\Routing\RouterInterface $router */
                $router = $this->get('router');
                header('Location: ' . $router->generate('universign_project_generation', ['projectId' => $this->projects->id_project]));
                exit;
            }
        }

        header('Location: ' . $this->lurl);
        exit;
    }

    // mandat emprunteur
    public function _mandat()
    {
        $this->pdfClient = $this->loadData('clients');

        if (
            isset($this->params[0], $this->params[1])
            && 1 === preg_match('/^[0-9a-f-]{32,36}$/', $this->params[0])
            && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)
            && $this->pdfClient->get($this->params[0], 'hash')
            && $this->companies->get($this->pdfClient->id_client, 'id_client_owner')
            && $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
            && $this->projects->status != ProjectsStatus::PRET_REFUSE
        ) {
            $return = $this->commonMandate();

            switch ($return['action']) {
                case 'redirect':
                    header('Location: ' . $return['url']);
                    exit;
                case 'read':
                    $this->ReadPdf($return['path'], $return['name']);
                    exit;
                case 'sign':
                    header('Location: ' . $this->url . '/universign/mandat/' . $return['mandate']->id_mandat);
                    exit;
            }
        }

        header('Location: ' . $this->lurl);
        exit;
    }

    /**
     * @return array
     */
    private function commonMandate()
    {
        /** @var \clients_mandats $mandates */
        $mandates        = $this->loadData('clients_mandats');
        $path            = $this->path . 'protected/pdf/mandat/';
        $namePDFClient   = 'MANDAT-UNILEND-' . $this->projects->slug . '-' . $this->pdfClient->id_client;
        $projectMandates = $mandates->select(
            'id_project = ' . $this->projects->id_project . ' AND id_client = ' . $this->pdfClient->id_client . ' AND status IN (' . UniversignEntityInterface::STATUS_PENDING . ',' . UniversignEntityInterface::STATUS_SIGNED . ')',
            'id_mandat DESC'
        );

        if (false === empty($projectMandates)) {
            $mandate = array_shift($projectMandates);

            foreach ($projectMandates as $mandateToArchive) {
                $mandates->get($mandateToArchive['id_mandat']);
                $mandates->status = UniversignEntityInterface::STATUS_ARCHIVED;
                $mandates->update();
            }

            if (UniversignEntityInterface::STATUS_SIGNED == $mandate['status']) {
                return [
                    'action' => 'read',
                    'path'   => $path . $mandate['name'],
                    'name'   => $namePDFClient
                ];
            } elseif (UniversignEntityInterface::STATUS_CANCELED == $mandate['status']) {
                return [
                    'action' => 'redirect',
                    'url'    => $this->lurl . '/espace_emprunteur/operations'
                ];
            }

            $mandates->get($mandate['id_mandat']);
        } else {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $bankAccount   = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->pdfClient->id_client);
            if (null === $bankAccount) {
                return [
                    'action' => 'redirect',
                    'url'    => $this->lurl
                ];
            }

            $mandates->id_client  = $this->pdfClient->id_client;
            $mandates->url_pdf    = '/pdf/mandat/' . $this->pdfClient->hash . '/' . $this->projects->id_project;
            $mandates->name       = 'mandat-' . $this->pdfClient->hash . '-' . $this->projects->id_project . '.pdf';
            $mandates->id_project = $this->projects->id_project;
            $mandates->status     = UniversignEntityInterface::STATUS_PENDING;
            $mandates->iban       = $bankAccount->getIban();
            $mandates->bic        = $bankAccount->getBic();
            $mandates->create();
        }

        if (false === file_exists($path . $mandates->name)) {
            $this->GenerateWarrantyHtml($mandates);
            $this->WritePdf($path . $mandates->name, 'warranty');
        }

        return [
            'action'  => 'sign',
            'mandate' => $mandates
        ];
    }

    private function GenerateWarrantyHtml($mandates)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->iban  = $mandates->iban;
        $this->bic   = $mandates->bic;

        if (isset($this->params[1]) && $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->params[1])) {
            $borrowerManager      = $this->get('unilend.service.borrower_manager');
            $this->motif          = $borrowerManager->getProjectBankTransferLabel($project);
            $company              = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->companies->id_company);
            $this->companyAddress = $company->getIdAddress();
        }

        $this->settings->get('Créancier adresse', 'type');
        $this->creancier_adresse = $this->settings->value;

        $this->settings->get('Créancier cp', 'type');
        $this->creancier_cp = $this->settings->value;

        $this->settings->get('ICS de SFPMEI', 'type');
        $this->creancier_identifiant = $this->settings->value;

        $this->settings->get('Créancier nom', 'type');
        $this->creancier = $this->settings->value;

        $this->settings->get('Créancier pays', 'type');
        $this->creancier_pays = $this->settings->value;

        $this->settings->get('Créancier ville', 'type');
        $this->creancier_ville = $this->settings->value;

        $this->settings->get('Créancier code identifiant', 'type');
        $this->creancier_code_id = $this->settings->value;

        $this->settings->get('Adresse retour', 'type');
        $this->adresse_retour = $this->settings->value;

        $this->setDisplay('mandat_html');
    }

    public function _pouvoir()
    {
        $this->pdfClient = $this->loadData('clients');

        if (
            isset($this->params[0], $this->params[1])
            && 1 === preg_match('/^[0-9a-f-]{32,36}$/', $this->params[0])
            && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)
            && $this->pdfClient->get($this->params[0], 'hash')
            && $this->companies->get($this->pdfClient->id_client, 'id_client_owner')
            && $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
            && $this->projects->status != \projects_status::PRET_REFUSE
        ) {
            $return = $this->commonProxy();

            switch ($return['action']) {
                case 'redirect':
                    header('Location: ' . $return['url']);
                    exit;
                case 'read':
                    $this->ReadPdf($return['path'], $return['name']);
                    exit;
                case 'sign':
                    $regenerationUniversign = $return['regenerate'] ? '' : '/NoUpdateUniversign';
                    header('Location: ' . $this->url . '/universign/pouvoir/' . $return['proxy']->id_pouvoir . $regenerationUniversign);
                    exit;
            }
        }

        header('Location: ' . $this->lurl);
        exit;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function commonProxy(): array
    {
        $this->oProjectsPouvoir = $this->loadData('projects_pouvoir');

        $signed        = false;
        $path          = $this->path . 'protected/pdf/pouvoir/';
        $namePdfClient = 'POUVOIR-UNILEND-' . $this->projects->slug . '-' . $this->pdfClient->id_client;
        $fileName      = 'pouvoir-' . $this->pdfClient->hash . '-' . $this->projects->id_project . '.pdf';

        $projectPouvoir        = $this->oProjectsPouvoir->select('id_project = ' . $this->projects->id_project, 'added ASC');
        $projectPouvoirToTreat = (is_array($projectPouvoir) && false === empty($projectPouvoir)) ? array_shift($projectPouvoir) : null;

        if (is_array($projectPouvoir) && 0 < count($projectPouvoir)) {
            foreach ($projectPouvoir as $projectPouvoirToDelete) {
                $this->oLogger->info('Deleting proxy (' . $projectPouvoirToDelete['id_pouvoir'] . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project));
                $this->oProjectsPouvoir->delete($projectPouvoirToDelete['id_pouvoir'], 'id_pouvoir');
            }
        }

        if (false === is_null($projectPouvoirToTreat)) {
            $this->oProjectsPouvoir->get($projectPouvoirToTreat['id_pouvoir'], 'id_pouvoir');
            if ($this->oProjectsPouvoir->status == UniversignEntityInterface::STATUS_CANCELED) {
                return [
                    'action' => 'redirect',
                    'url'    => $this->lurl . '/espace_emprunteur/operations'
                ];
            }

            // si c'est un upload manuel du BO on affiche directement
            if ($projectPouvoirToTreat['id_universign'] == 'no_universign' && file_exists($path . $projectPouvoirToTreat['name'])) {
                return [
                    'action' => 'read',
                    'path'   => $path . $projectPouvoirToTreat['name'],
                    'name'   => $namePdfClient
                ];
            }

            $signed        = $projectPouvoirToTreat['status'] == UniversignEntityInterface::STATUS_SIGNED;
            $instantCreate = false;

            if (false === file_exists($path . $projectPouvoirToTreat['name'])) {
                $this->GenerateProxyHtml();
                $this->WritePdf($path . $projectPouvoirToTreat['name'], 'authority');
                $signed        = false;
                $instantCreate = true;
            }
        } else {
            $this->GenerateProxyHtml();
            $this->WritePdf($path . $fileName, 'authority');

            $this->oProjectsPouvoir->id_project = $this->projects->id_project;
            $this->oProjectsPouvoir->url_pdf    = '/pdf/pouvoir/' . $this->pdfClient->hash . '/' . $this->projects->id_project . '/';
            $this->oProjectsPouvoir->name       = $fileName;
            $this->oProjectsPouvoir->create();

            $instantCreate = true;
        }

        if (false === $signed) {
            if (file_exists($path . $fileName) && filesize($path . $fileName) > 0 && date('Y-m-d', filemtime($path . $fileName)) != date('Y-m-d')) {
                unlink($path . $fileName);

                $this->oLogger->info('File "' . $path . $fileName . '" deleted', array('class' => __CLASS__, 'function' => __FUNCTION__));

                $this->GenerateProxyHtml();
                $this->WritePdf($path . $fileName, 'authority');
                $instantCreate = true;
            }

            if (date('Y-m-d', strtotime($this->oProjectsPouvoir->updated)) == date('Y-m-d') && false === $instantCreate && false === empty($this->oProjectsPouvoir->url_universign)) {
                $regenerationUniversign = false;
            } else {
                $regenerationUniversign = true;
                $this->oProjectsPouvoir->update();
            }

            return [
                'action'     => 'sign',
                'proxy'      => $this->oProjectsPouvoir,
                'regenerate' => $regenerationUniversign
            ];
        } else {
            return [
                'action' => 'read',
                'path'   => $path . $fileName,
                'name'   => $namePdfClient
            ];
        }
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function GenerateProxyHtml(): void
    {
        $this->lng['pdf-pouvoir'] = $this->ln->selectFront('pdf-pouvoir', $this->language, $this->App);

        // Update repayment schedule dates based on proxy generation date
        // Once proxy has been generated, do not update repayment schedule anymore
        $this->updateRepaymentSchedules();

        /** @var product $product */
        $product = $this->loadData('product');
        $product->get($this->projects->id_product);
        $template = $product->proxy_template;

        if (false === empty($product->proxy_block_slug)) {
            $this->blocs->get($product->proxy_block_slug, 'slug');
            $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
            foreach ($lElements as $b_elt) {
                $this->elements->get($b_elt['id_element']);
                $this->bloc_pouvoir[$this->elements->slug]           = $b_elt['value'];
                $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
            }
        }
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                = $this->get('doctrine.orm.entity_manager');
        $this->companyAddress         = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->companies->id_company)->getIdAddress();
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->companies_bilans       = $this->loadData('companies_bilans');
        $this->echeanciers            = $this->loadData('echeanciers');
        $this->oLoans                 = $this->loadData('loans');
        /** @var underlying_contract $contract */
        $contract               = $this->loadData('underlying_contract');
        $this->walletRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

        $contract->get(UnderlyingContract::CONTRACT_BDC, 'label');
        $BDCContractId = $contract->id_contract;

        $contract->get(UnderlyingContract::CONTRACT_IFP, 'label');
        $IFPContractId = $contract->id_contract;

        $contract->get(UnderlyingContract::CONTRACT_MINIBON, 'label');
        $minibonContractId = $contract->id_contract;

        $this->montantPrete        = $this->projects->amount;
        $this->commissionRateFunds = round(bcdiv($this->projects->commission_rate_funds, 100, 4), 2);
        $commissionAmount          = round(bcmul($this->projects->amount, $this->commissionRateFunds, 4), 2);
        $vatTax                    = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(\Unilend\Bundle\CoreBusinessBundle\Entity\TaxType::TYPE_VAT);
        $vatAmount                 = round(bcmul($commissionAmount, $vatTax->getRate() / 100, 5), 2);
        $this->releasedNetAmount   = bcsub(round($this->projects->amount * bcsub(1, $this->commissionRateFunds, 2)), $vatAmount, 2);
        $this->taux                = $this->projects->getAverageInterestRate();
        $this->nbLoansBDC          = $this->oLoans->counter('id_type_contract = ' . $BDCContractId . ' AND id_project = ' . $this->projects->id_project);
        $this->nbLoansIFP          = $this->oLoans->counter('id_type_contract = ' . $IFPContractId . ' AND id_project = ' . $this->projects->id_project);
        $this->nbLoansMinibon      = $this->oLoans->counter('id_type_contract = ' . $minibonContractId . ' AND id_project = ' . $this->projects->id_project);
        $this->lRemb               = $this->loadData('echeanciers_emprunteur')->select('id_project = ' . $this->projects->id_project, 'ordre ASC');
        $this->rembByMonth         = bcdiv($this->lRemb[0]['montant'] + $this->lRemb[0]['commission'] + $this->lRemb[0]['tva'], 100, 2);
        $this->dateLastEcheance    = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);

        $this->capital = 0;
        foreach ($this->lRemb as $r) {
            $this->capital += $r['capital'];
        }

        $this->companies_bilans->get($this->projects->id_dernier_bilan, 'id_bilan');
        $this->l_AP             = $this->companies_actif_passif->select('id_bilan = ' . $this->projects->id_dernier_bilan);
        $this->totalActif       = $this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement'] + $this->l_AP[0]['comptes_regularisation_actif'];
        $this->totalPassif      = $this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes'] + $this->l_AP[0]['comptes_regularisation_passif'];
        $this->lLenders         = $this->getFormattedLenderListForProxy($this->projects->id_project);
        $this->dateRemb         = date('d/m/Y');
        $this->dateDernierBilan = date('d/m/Y', strtotime($this->companies_bilans->cloture_exercice_fiscal)); // @todo Intl

        $this->setDisplay($template);
    }

    /**
     * @param int $projectId
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getFormattedLenderListForProxy(int $projectId): array
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $loans         = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->findBy(['idProject' => $projectId], ['rate' => 'ASC']);
        $lenderList    = [];

        /** @var Loans $loan */
        foreach ($loans as $loan) {
            $wallet        = $loan->getWallet();
            $client        = $wallet->getIdClient();
            $lenderAddress = null;

            if ($client->isNaturalPerson()) {
                $validatedAddress = $client->getIdAddress();
            } else {
                $lenderCompany    = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')
                    ->findOneBy(['idClientOwner' => $client->getIdClient()]);
                $validatedAddress = $lenderCompany->getIdAddress();
            }

            if (null !== $validatedAddress) {
                $lenderAddress = $validatedAddress;
            } else {
                $this->logWarningAboutNotValidatedLenderAddress($client, __LINE__);

                try {
                    if ($client->isNaturalPerson()) {
                        $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                            ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                    } else {
                        $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                            ->findLastModifiedNotArchivedAddressByType($lenderCompany, AddressType::TYPE_MAIN_ADDRESS);
                    }
                    $lenderAddress = $lastModifiedAddress;
                } catch (\Exception $exception) {
                    $this->oLogger->error('An exception occurred while getting last modified client address. Message: ' . $exception->getMessage(), [
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $client->getIdClient(),
                        'id_company' => isset($lenderCompany) ? $lenderCompany->getIdCompany() : 'lender is natural person',
                    ]);
                }

                if (null === $lenderAddress)
                    $this->oLogger->error('Lender has no main address. His address in proxy will be empty.', [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $client->getIdClient(),
                        'id_company' => isset($lenderCompany) ? $lenderCompany->getIdCompany() : 'lender is natural person',
                    ]);
            }

            $lenderList[] = [
                'name'      => $client->isNaturalPerson() ? $client->getNom() : $lenderCompany->getName(),
                'firstName' => $client->isNaturalPerson() ? $client->getPrenom() : $lenderCompany->getSiren(),
                'address'   => null !== $lenderAddress ? $lenderAddress->getAddress() : '',
                'zip'       => null !== $lenderAddress ? $lenderAddress->getZip() : '',
                'city'      => null !== $lenderAddress ? $lenderAddress->getCity() : '',
                'amount'    => $this->ficelle->formatNumber($loan->getAmount() / 100, 0),
                'rate'      => $this->ficelle->formatNumber($loan->getRate(), 1)
            ];
        }

        return $lenderList;
    }

    public function _cgv_emprunteurs()
    {
        if (false === isset($this->params[0], $this->params[1]) || false === is_numeric($this->params[0])) {
            header('Location:' . $this->lurl);
            return;
        }
        $iProjectId     = $this->params[0];
        $sFileName      = $this->params[1];
        $sNamePdfClient = 'CGV-UNILEND-' . $iProjectId;
        $oProjectCgv    = $this->loadData('project_cgv');
        $path           = $this->path . ProjectCgv::BASE_PATH;

        if ($oProjectCgv->get($iProjectId, 'id_project') && false === empty($oProjectCgv->name) && false === empty($oProjectCgv->id_tree)) {
            if ($sFileName !== $oProjectCgv->name) {
                header('Location: ' . $this->lurl);
                return;
            }

            // and if it's signed
            if ($oProjectCgv->status == UniversignEntityInterface::STATUS_SIGNED && file_exists($path . $oProjectCgv->name)) {
                $this->ReadPdf($path . $oProjectCgv->name, $sNamePdfClient);
                return;
            }

            if ('' != $oProjectCgv->url_universign) {
                header('Location: ' . $oProjectCgv->url_universign);
                return;
            }
        } else {
            header('Location: ' . $this->lurl);
            return;
        }

        if (false === file_exists($path . $oProjectCgv->name)) {
            // Recuperation du pdf du tree
            $elements = $this->tree_elements->select('id_tree = "' . $oProjectCgv->id_tree . '" AND id_element = ' . Elements::TYPE_PDF_TERMS_OF_SALE . ' AND id_langue = "' . $this->language . '"');

            if (false === isset($elements[0]['value']) || '' == $elements[0]['value']) {
                header('Location: ' . $this->lurl);
                return;
            }

            $sPdfPath = $this->path . 'public/default/var/fichiers/' . $elements[0]['value'];

            if (false === file_exists($sPdfPath)) {
                header('Location: ' . $this->lurl);
                return;
            }
            if (false === is_dir($this->path . ProjectCgv::BASE_PATH)) {
                mkdir($this->path . ProjectCgv::BASE_PATH, 0777, true);
            }
            if (false === file_exists($this->path . ProjectCgv::BASE_PATH . $oProjectCgv->name)) {
                copy($sPdfPath, $this->path . ProjectCgv::BASE_PATH . $oProjectCgv->name);
            }
        }

        header('Location: ' . $this->url . '/universign/cgv_emprunteurs/' . $oProjectCgv->id . '/' . $oProjectCgv->name);
    }

    // Mise a jour des dates echeances preteurs et emprunteur (utilisé pour se baser sur la date de creation du pouvoir)
    private function updateRepaymentSchedules()
    {
        ini_set('max_execution_time', 300);

        if ($this->projects->status == \projects_status::FUNDE) {
            /** @var \echeanciers $lenderRepaymentSchedule */
            $lenderRepaymentSchedule = $this->loadData('echeanciers');
            /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
            $borrowerRepaymentSchedule = $this->loadData('echeanciers_emprunteur');
            /** @var ProjectRepaymentScheduleManager $projectRepaymentScheduleManager */
            $projectRepaymentScheduleManager = $this->get(ProjectRepaymentScheduleManager::class);

            $repaymentBaseDate = new DateTime();

            for ($order = 1; $order <= $this->projects->period; $order++) {
                $currentLenderRepaymentDates   = $lenderRepaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $order, '', 0, 1)[0];
                $currentBorrowerRepaymentDates = $borrowerRepaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $order, '', 0, 1)[0];

                $lenderRepaymentDate   = $projectRepaymentScheduleManager->generateLenderMonthlyAmortizationDate($repaymentBaseDate, $order);
                $borrowerRepaymentDate = $projectRepaymentScheduleManager->generateBorrowerMonthlyAmortizationDate($repaymentBaseDate, $order);

                if (
                    substr($currentLenderRepaymentDates['date_echeance'], 0, 10) !== $lenderRepaymentDate->format('Y-m-d')
                    || substr($currentLenderRepaymentDates['date_echeance_emprunteur'], 0, 10) !== $borrowerRepaymentDate->format('Y-m-d')
                ) {
                    $lenderRepaymentSchedule->onMetAjourLesDatesEcheances($this->projects->id_project, $order, $lenderRepaymentDate->format('Y-m-d H:i:00'), $borrowerRepaymentDate->format('Y-m-d H:i:00'));
                }

                if (substr($currentBorrowerRepaymentDates['date_echeance_emprunteur'], 0, 10) !== $borrowerRepaymentDate->format('Y-m-d')) {
                    $borrowerRepaymentSchedule->onMetAjourLesDatesEcheancesE($this->projects->id_project, $order, $borrowerRepaymentDate->format('Y-m-d H:i:00'));
                }
            }
        }
    }

    public function _declarationContratPret_html($iIdLoan)
    {
        $this->oLoans          = $this->loadData('loans');
        $this->companiesEmp    = $this->loadData('companies');
        $this->emprunteur      = $this->loadData('clients');
        $this->preteur         = $this->loadData('clients');
        $this->preteurCompanie = $this->loadData('companies');
        $this->echeanciers     = $this->loadData('echeanciers');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($iIdLoan) && $this->oLoans->get($iIdLoan, 'status = "' . Loans::STATUS_ACCEPTED . '" AND id_loan')) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
            $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($this->oLoans->id_wallet);
            $lenderAddress = null;
            $client        = $wallet->getIdClient();

            $this->settings->get('Declaration contrat pret - adresse', 'type');
            $this->adresse = $this->settings->value;

            $this->settings->get('Declaration contrat pret - raison sociale', 'type');
            $this->raisonSociale = $this->settings->value;

            $this->projects->get($this->oLoans->id_project, 'id_project');
            $this->companiesEmp->get($this->projects->id_company, 'id_company');
            $this->emprunteur->get($this->companiesEmp->id_client_owner, 'id_client');
            $this->preteur->get($wallet->getIdClient()->getIdClient(), 'id_client');
            $this->borrowerCompanyAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->projects->id_company)->getIdAddress();

            $this->lEcheances = array_values($this->echeanciers->getYearlySchedule(array('id_loan' => $this->oLoans->id_loan)));

            if ($client->isNaturalPerson()) {
                $validatedAddress    = $client->getIdAddress();
            } else {
                $lenderCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')
                    ->findOneBy(['idClientOwner' => $client]);
                $validatedAddress    = $lenderCompany->getIdAddress();
            }

            if (null !== $validatedAddress) {
                $lenderAddress = $validatedAddress;
            } else {
                $this->logWarningAboutNotValidatedLenderAddress($client, __LINE__);

                try {
                    if ($client->isNaturalPerson()) {
                        $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                            ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                    } else {
                        $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                            ->findLastModifiedNotArchivedAddressByType($lenderCompany, AddressType::TYPE_MAIN_ADDRESS);
                    }

                    $lenderAddress = $lastModifiedAddress;
                } catch (\Exception $exception) {
                    $this->oLogger->error('An exception occurred while getting last modified client address. Message: ' . $exception->getMessage(), [
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $client->getIdClient(),
                        'id_company' => isset($lenderCompany) ? $lenderCompany->getIdCompany() : 'lender is natural person',
                    ]);
                }

                if (null === $lenderAddress)
                    $this->oLogger->error('Lender has no main address. Declaration contrat de prêt could not be generated.', [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $client->getIdClient(),
                        'id_company' => isset($lenderCompany) ? $lenderCompany->getIdCompany() : 'lender is natural person',
                    ]);
                exit;
            }

            $this->nomPreteur     = $client->isNaturalPerson() ? $client->getPrenom() . ' ' . $client->getNom() : $lenderCompany->getName();
            $this->adressePreteur = $lenderAddress->getAddress();
            $this->cpPreteur      = $lenderAddress->getZip();
            $this->villePreteur   = $lenderAddress->getCity();
            $this->lenderCountry  = $lenderAddress->getIdCountry()->getFr();

            $this->setDisplay('declarationContratPret_html');
        }
    }

    public function _declaration_de_creances()
    {
        if (false === isset($this->params[0], $this->params[1])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \clients $clients */
        $clients = $this->loadData('clients');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $loan = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($this->params[1]);

        if (null === $loan) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
        $wallet = $loan->getWallet();

        if (false === $wallet->getIdClient()->isLender()) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $clients->get($wallet->getIdClient()->getIdClient(), 'id_client');

        $filePath      = $this->path . 'protected/pdf/declaration_de_creances/' . $loan->getProject()->getIdProject() . '/';
        $filePath      = ($loan->getProject()->getIdProject() == '1456') ? $filePath : $filePath . $clients->id_client . '/';
        $filePath      = $filePath . 'declaration-de-creances' . '-' . $clients->hash . '-' . $loan->getIdLoan() . '.pdf';
        $namePdfClient = 'DECLARATION-DE-CREANCES-UNILEND-' . $clients->hash . '-' . $loan->getIdLoan();

        if (false === file_exists($filePath)) {
            $this->GenerateClaimsHtml($clients, $loan);
            $this->WritePdf($filePath, 'claims');
        }

        $this->ReadPdf($filePath, $namePdfClient);
    }

    /**
     * @param \clients $client
     * @param Loans    $loan
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function GenerateClaimsHtml(\clients $client, Loans $loan): void
    {
        /** @var \loans oLoans */
        $this->loan = $loan;
        /** @var \clients clients */
        $this->clients = $client;
        /** @var \projects projects */
        $this->projects = $this->loadData('projects');

        $this->projects->get($loan->getProject()->getIdProject());
        /** @var \echeanciers echeanciers */
        $this->echeanciers = $this->loadData('echeanciers');
        /** @var \projects_status_history projects_status_history */
        $this->projects_status_history = $this->loadData('projects_status_history');
        /** @var \projects_status_history_details projects_status_history_details */
        $this->projects_status_history_details = $this->loadData('projects_status_history_details');
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
        $wallet = $loan->getWallet();
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies borrowerCompany */
        $this->borrowerCompany        = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->projects->id_company);
        $this->borrowerCompanyAddress = $this->borrowerCompany->getIdAddress();
        $status                       = [
            CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
            CompanyStatus::STATUS_RECEIVERSHIP,
            CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
        ];
        $this->lenderAddress          = null;

        if (in_array($this->borrowerCompany->getIdStatus()->getLabel(), $status)) {
            if (in_array($client->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->lenderCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')
                    ->findOneBy(['idClientOwner' => $client->id_client]);
                $clientEntity        = $this->lenderCompany->getIdClientOwner();
                $validatedAddress    = $this->lenderCompany->getIdAddress();
            } else {
                $clientEntity     = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
                $validatedAddress = $clientEntity->getIdAddress();
            }

            if (null !== $validatedAddress) {
                $this->lenderAddress = $validatedAddress;
            } else {
                $this->logWarningAboutNotValidatedLenderAddress($clientEntity, __LINE__);

                try {
                    if ($clientEntity->isNaturalPerson()) {
                        $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                            ->findLastModifiedNotArchivedAddressByType($clientEntity, AddressType::TYPE_MAIN_ADDRESS);
                    } else {
                        $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                            ->findLastModifiedNotArchivedAddressByType($this->lenderCompany, AddressType::TYPE_MAIN_ADDRESS);
                    }

                    $this->lenderAddress = $lastModifiedAddress;
                } catch (\Exception $exception) {
                    $this->oLogger->error('An exception occurred while getting last modified client address. Message: ' . $exception->getMessage(), [
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $clientEntity->getIdClient(),
                        'id_company' => isset($this->lenderCompany) ? $this->lenderCompany->getIdCompany() : 'lender is natural person',

                    ]);
                }

                if (null === $this->lenderAddress)
                    $this->oLogger->error('Lender has no main address. Claims could not be generated.', [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $clientEntity->getIdClient(),
                        'id_company' => isset($this->lenderCompany) ? $this->lenderCompany->getIdCompany() : 'lender is natural person',
                    ]);
                exit;
            }

            $companyStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory');
            $companyStatusHistory           = $companyStatusHistoryRepository->findFirstHistoryByCompanyAndStatus($this->borrowerCompany->getIdCompany(), $status);

            $this->date            = $companyStatusHistory->getChangedOn();
            $this->mandataires_var = $companyStatusHistory->getReceiver();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');

            $creditorClaimAmounts = $projectManager->getCreditorClaimAmounts($loan);
            $this->echu           = $creditorClaimAmounts['expired'];
            $this->echoir         = $creditorClaimAmounts['to_expired'];
            $this->total          = round(bcadd($creditorClaimAmounts['expired'], $creditorClaimAmounts['to_expired'], 4), 2);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager $companyManager */
            $companyManager   = $this->get('unilend.service.company_manager');
            $this->nature_var = $companyManager->getCompanyStatusNameByLabel($companyStatusHistory->getIdStatus()->getLabel());

            $lastEcheance       = $this->echeanciers->select('id_lender = ' . $wallet->getId() . ' AND id_loan = ' . $loan->getIdLoan(), 'ordre DESC', 0, 1);
            $this->lastEcheance = date('d/m/Y', strtotime($lastEcheance[0]['date_echeance']));

            $this->contract = $loan->getIdTypeContract();

            $this->setDisplay('declaration_de_creances_html');
        } else {
            header('Location: ' . $this->lurl);
        }
    }

    /**
     * @param Clients $client
     * @param int     $line
     */
    private function logWarningAboutNotValidatedLenderAddress(Clients $client, int $line): void
    {
        $this->get('logger')
            ->warning('Client ' . $client->getIdClient() . ' has no validated main address. Only validated addresses should be used in official documents.', [
            'file'      => __FILE__,
            'line'      => $line,
            'id_client' => $client->getIdClient()
        ]);
    }
}
