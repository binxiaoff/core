<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Bids, Clients, CompanyAddress, PaysV2, ProjectsComments, ProjectsNotes, ProjectsStatus, WalletType, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class ajaxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $_SESSION['request_url'] = $this->url;

        // Should not be usefull but we keep it as a second security if someone forgets to check access in a new method
        $this->users->checkAccess();

        $this->hideDecoration();
    }

    /* Fonction AJAX delete image ELEMENT */
    public function _deleteImageElement()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            @unlink($this->spath . 'images/' . $this->tree_elements->value);

            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier ELEMENT */
    public function _deleteFichierElement()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            @unlink($this->spath . 'fichiers/' . $this->tree_elements->value);

            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete image ELEMENT BLOC */
    public function _deleteImageElementBloc()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier ELEMENT BLOC */
    public function _deleteFichierElementBloc()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'fichiers/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete image TREE */
    public function _deleteImageTree()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree->get(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->tree->img_menu);

            // On supprime le fichier de la base
            $this->tree->img_menu = '';
            $this->tree->update(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));
        }
    }

    /* Fonction AJAX chargement des noms de la section de traduction */
    public function _loadNomTexte()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->lNoms = $translationManager->selectNamesForSection($this->params[0]);
        }
    }

    /* Fonction AJAX chargement des traductions de la section de traduction */
    public function _loadTradTexte()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->section     = $this->params[1];
            $this->nom         = $this->params[0];
            $this->translation = $translationManager->noCacheTrans($this->params[1], $this->params[0]);
        }

        $this->setView('../traductions/edit');
    }

    /* Activer un utilisateur sur une zone */
    public function _activeUserZone()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            // Recuperation du statut actuel de l'user
            $this->users_zones->get($this->params[0], 'id_zone = ' . $this->params[1] . ' AND id_user');

            if ($this->users_zones->id != '') {
                $this->users_zones->delete($this->users_zones->id);
                echo $this->surl . '/images/admin/check_off.png';
            } else {
                $this->users_zones->id_user = $this->params[0];
                $this->users_zones->id_zone = $this->params[1];
                $this->users_zones->create();
                echo $this->surl . '/images/admin/check_on.png';
            }
        }
    }

    public function _valid_etapes()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        $errors = [];

        /** @var \Symfony\Component\Translation\Translator translator */
        $this->translator = $this->get('translator');

        /** @var \projects $project */
        $project = $this->loadData('projects');

        if (isset($_POST['id_project'], $_POST['etape']) && filter_var($_POST['id_project'], FILTER_VALIDATE_INT) && $project->get($_POST['id_project'])) {
            /** @var \companies $company */
            $company = $this->loadData('companies');
            /** @var \clients $client */
            $client = $this->loadData('clients');

            $serialize = serialize($_POST);
            $this->users_history->histo(8, 'dossier edit etapes', $_SESSION['user']['id_user'], $serialize);

            if ($_POST['etape'] == 1) {
                if ($_POST['partner_etape1'] != $project->id_partner) {
                    $project->commission_rate_funds     = null;
                    $project->commission_rate_repayment = null;
                }

                $project->amount     = $this->ficelle->cleanFormatedNumber($_POST['montant_etape1']);
                $project->period     = (0 < (int) $_POST['duree_etape1']) ? (int) $_POST['duree_etape1'] : $project->period;
                $project->id_partner = $_POST['partner_etape1'];
                $project->update();

                $company->get($project->id_company, 'id_company');
                $company->siren = $_POST['siren_etape1'];
                $company->update();

                $client->get($company->id_client_owner);
                $client->source = $_POST['source_etape1'] ?: 'Directe';
                $client->update();

                if (empty($company->siren)) {
                    echo json_encode([
                        'success' => false,
                        'error'   => 'SIREN vide'
                    ]);
                    return;
                }

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager $projectRequestManager */
                $projectRequestManager = $this->get('unilend.service.project_request_manager');
                $result                = $projectRequestManager->checkProjectRisk($project, $_SESSION['user']['id_user']);

                // NAF code may be filled in in checkProjectRisk method
                $company->get($project->id_company, 'id_company');

                if (true === is_array($result) && ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN === $result['motive']) {
                    echo json_encode([
                        'success' => false,
                        'error'   => 'SIREN inconu'
                    ]);
                    return;
                } elseif (empty($company->code_naf)) {
                    echo json_encode([
                        'success' => false,
                        'error'   => 'Problème lors de la récupération des données de la société'
                    ]);
                    return;
                }

                if (null === $result) {
                    $projectRequestManager->assignEligiblePartnerProduct($project, $this->userEntity->getIdUser());
                }

                echo json_encode([
                    'success' => true
                ]);
                return;
            } elseif ($_POST['etape'] == 2) {
                /** @var EntityManager $entityManager */
                $entityManager = $this->get('doctrine.orm.entity_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\CompanyAddressRepository $companyAddressRepository */
                $companyAddressRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AddressManager $addressManager */
                $addressManager = $this->get('unilend.service.address_manager');
                $emailRegex    = $entityManager
                    ->getRepository('UnilendCoreBusinessBundle:Settings')
                    ->findOneBy(['type' => 'Regex validation email'])
                    ->getValue();

                /** @var \companies $company */
                $company = $this->loadData('companies');
                $company->get($project->id_company, 'id_company');
                /** @var CompanyAddress $companyMainAddress */
                $companyMainAddress = $companyAddressRepository->findLastModifiedCompanyAddressByType($company->id_company, AddressType::TYPE_MAIN_ADDRESS);
                /** @var CompanyAddress $companyPostalAddress */
                $companyPostalAddress = $companyAddressRepository->findLastModifiedCompanyAddressByType($company->id_company, AddressType::TYPE_POSTAL_ADDRESS);

                /** @var \clients $client */
                $client = $this->loadData('clients');
                $client->get($company->id_client_owner, 'id_client');

                $email = isset($_POST['email_etape2']) ? trim($_POST['email_etape2']) : null;
                if (false === empty($email) && 1 !== preg_match($emailRegex, $email)) {
                    $errors[] = 'Le format de l\'adresse email est invalide';
                } elseif (false === empty($email) && $email !== $client->email) {
                    $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
                    $duplicates       = $clientRepository->findBy(['email' => $email, 'status' => Clients::STATUS_ONLINE]);

                    if (false === empty($duplicates)) {
                        $errors[] = 'Cette adresse email est déjà utilisée par un autre compte';
                    }
                }

                if (false === empty($_POST['creation_date_etape2']) && 1 !== preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $_POST['creation_date_etape2'])) {
                    $errors[] = 'Date de création invalide';
                } elseif (false === empty($_POST['creation_date_etape2'])) {
                    $creationDate           = \DateTime::createFromFormat('d/m/Y', $_POST['creation_date_etape2']);
                    $project->balance_count = empty($project->balance_count) ? $creationDate->diff(new \DateTime())->y : $project->balance_count;
                }

                $company->name          = $_POST['raison_sociale_etape2'];
                $company->forme         = $_POST['forme_juridique_etape2'];
                $company->capital       = $this->ficelle->cleanFormatedNumber($_POST['capital_social_etape2']);
                $company->date_creation = isset($creationDate) ? $creationDate->format('Y-m-d') : $company->date_creation;
                $company->phone         = $_POST['phone_etape2'];

                $client->email     = $email;
                $client->civilite  = isset($_POST['civilite_etape2']) ? $_POST['civilite_etape2'] : $client->civilite;
                $client->nom       = $this->ficelle->majNom($_POST['nom_etape2']);
                $client->prenom    = $this->ficelle->majNom($_POST['prenom_etape2']);
                $client->fonction  = $_POST['fonction_etape2'];
                $client->telephone = $_POST['phone_new_etape2'];
                $client->naissance = empty($_POST['date_naissance_gerant']) ? '0000-00-00' : date('Y-m-d', strtotime(str_replace('/', '-', $_POST['date_naissance_gerant'])));

                if (empty($errors)) {
                    $project->update();
                    $company->update();
                    $client->update();

                    if (false === empty($_POST['address_etape2']) && false === empty($_POST['ville_etape2']) && false === empty($_POST['postal_etape2'])) {
                        $addressManager->saveBorrowerCompanyAddress(
                                $_POST['address_etape2'],
                                $_POST['postal_etape2'],
                                $_POST['ville_etape2'],
                                PaysV2::COUNTRY_FRANCE,
                                $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($company->id_company),
                                AddressType::TYPE_MAIN_ADDRESS
                            );
                    }

                    if (false === empty($_POST['adresse_correspondance_etape2']) && false === empty($_POST['city_correspondance_etape2']) && false === empty($_POST['zip_correspondance_etape2'])) {
                        $addressManager->saveBorrowerCompanyAddress(
                            $_POST['adresse_correspondance_etape2'],
                            $_POST['zip_correspondance_etape2'],
                            $_POST['city_correspondance_etape2'],
                            PaysV2::COUNTRY_FRANCE,
                            $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($company->id_company),
                            AddressType::TYPE_POSTAL_ADDRESS
                        );
                    }

                    if (null !== $companyPostalAddress && isset($_POST['same_address_etape2']) && 'on' === $_POST['same_address_etape2']) {
                        $companyPostalAddress->setDateArchived(new \DateTime('NOW'));

                        $entityManager->flush($companyPostalAddress);
                    }

                }
            } elseif ($_POST['etape'] == 3) {
                if (isset($_FILES['photo_projet']) && false === empty($_FILES['photo_projet']['name'])) {
                    $this->upload->setUploadDir($this->path, 'public/default/images/dyn/projets/source/');
                    $this->upload->setExtValide(array('jpeg', 'JPEG', 'jpg', 'JPG'));

                    $imagick     = new \Imagick($_FILES['photo_projet']['tmp_name']);
                    $imageConfig = $this->getParameter('image_resize');

                    if ($imagick->getImageWidth() > $imageConfig['projets']['width'] || $imagick->getImageHeight() > $imageConfig['projets']['height']) {
                        $error = 'Erreur upload photo : taille max dépassée (' . $imageConfig['projets']['width'] . 'x' . $imageConfig['projets']['height'] . ')';
                    } elseif ($this->upload->doUpload('photo_projet', '', true)) {
                        // Delete previous image of the name was different from the new one
                        if (false === empty($project->photo_projet) && $project->photo_projet != $this->upload->getName()) {
                            @unlink($this->path . 'public/default/images/dyn/projets/source/' . $project->photo_projet);
                        }
                        $project->photo_projet = $this->upload->getName();
                    } else {
                        $error = 'Erreur upload photo : ' . $this->upload->getErrorType();
                    }
                }

                if ($project->create_bo) {
                    $project->comments = $_POST['comments_etape3'];
                }
                $project->objectif_loan        = $_POST['objectif_etape3'];
                $project->nature_project       = $_POST['nature_project'];
                $project->presentation_company = $_POST['presentation_etape3'];
                $project->means_repayment      = $_POST['moyen_etape3'];
                $project->update();

                if (isset($error)) {
                    echo json_encode([
                        'error'   => true,
                        'message' => $error
                    ]);
                    return;
                } else {
                    echo json_encode([
                        'success' => true
                    ]);
                    return;
                }
            } elseif ($_POST['etape'] == 4.1 && $project->status <= ProjectsStatus::COMITY_REVIEW) {
                if (false === empty($_POST['target_ratings']) && false === empty($project->id_target_company)) {
                    /** @var \company_rating_history $targetCompanyRatingHistory */
                    $targetCompanyRatingHistory   = $this->loadData('company_rating_history');
                    $targetCompanyRatingHistory   = $targetCompanyRatingHistory->select('id_company = ' . $project->id_target_company, 'added DESC', 0, 1);
                    $targetCompanyRatingHistoryId = null;

                    if (isset($targetCompanyRatingHistory[0]['id_company_rating_history'])) {
                        $targetCompanyRatingHistoryId = $targetCompanyRatingHistory[0]['id_company_rating_history'];
                    }

                    $this->saveCompanyRatings($_POST['target_ratings'], $project->id_target_company, $targetCompanyRatingHistoryId);
                }

                $companyRatingHistoryId = $this->saveCompanyRatings($_POST['ratings'], $project->id_company, $project->id_company_rating_history);

                if ($project->id_company_rating_history != $companyRatingHistoryId) {
                    $project->id_company_rating_history = $companyRatingHistoryId;
                    $project->update();
                }
            } elseif ($_POST['etape'] == 4.2) {
                if (isset($_POST['box'])) {
                    /** @var companies_bilans $companyAnnualAccounts */
                    $companyAnnualAccounts = $this->loadData('companies_bilans');
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
                    $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');

                    foreach ($_POST['box'] as $balanceSheetId => $boxes){
                        $companyAnnualAccounts->get($balanceSheetId);
                        foreach($boxes as $box => $value) {
                            $value = $this->ficelle->cleanFormatedNumber($value);
                            $companyBalanceSheetManager->saveBalanceSheetDetails($companyAnnualAccounts, $box, $value);
                        }
                        $companyBalanceSheetManager->calculateDebtsAssetsFromBalance($companyAnnualAccounts->id_bilan);
                    }
                }
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->id_project);
                die;
            }
        }

        $this->sendAjaxResponse(empty($errors), null, $errors);
    }

    /**
     * @param array $form
     * @param int   $companyId
     * @param int   $companyRatingHistoryId
     * @return int
     */
    private function saveCompanyRatings(array $form, $companyId, $companyRatingHistoryId = null)
    {
        /** @var \company_rating $companyRating */
        $companyRating = $this->loadData('company_rating');
        $ratings       = $companyRatingHistoryId ? $companyRating->getHistoryRatingsByType($companyRatingHistoryId) : [];
        $addHistory    = false;

        foreach ($form as $rating => $value) {
            switch ($rating) {
                case 'date_dernier_privilege':
                case 'date_tresorerie':
                    if (false === empty($value)) {
                        $value = date('Y-m-d', strtotime(str_replace('/', '-', $value)));
                    }
                    break;
                case 'montant_tresorerie':
                    if ('' === $value) {
                        continue;
                    }
                    $value = $this->ficelle->cleanFormatedNumber($value);
                    break;
            }

            if (false === isset($ratings[$rating]) || $ratings[$rating]['value'] != $value) {
                $addHistory = true;
                $ratings[$rating]['value'] = $value;
            }
        }

        if ($addHistory) {
            /** @var \company_rating_history $companyRatingHistory */
            $companyRatingHistory             = $this->loadData('company_rating_history');
            $companyRatingHistory->id_company = $companyId;
            $companyRatingHistory->id_user    = $_SESSION['user']['id_user'];
            $companyRatingHistory->action     = \company_rating_history::ACTION_USER;
            $companyRatingHistory->create();

            $companyRatingHistoryId = $companyRatingHistory->id_company_rating_history;

            foreach ($ratings as $rating => $value) {
                $companyRating->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
                $companyRating->type                      = $rating;
                $companyRating->value                     = $value['value'];
                $companyRating->create();
            }
        }

        return $companyRatingHistoryId;
    }

    public function _generer_mdp()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->autoFireView = false;

        $this->clients = $this->loadData('clients');

        if (isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client')) {
            $pass = $this->ficelle->generatePassword(8);
            $this->clients->changePassword($this->clients->email, $pass);

            $keywords = [
                'firstName'     => $this->clients->prenom,
                'password'      => 'Mot de passe : ' . $pass,
                'lenderPattern' => $this->clients->getLenderPattern($this->clients->id_client)
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $keywords);

            try {
                $message->setTo($this->clients->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Could not send email : generation-mot-de-passe - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $this->clients->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
                echo 'warning';

                return;
            }
            echo 'success';

            return;
        }
        echo 'error';
    }

    public function _deleteBidPreteur()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->autoFireView = true;

        /** @var \bids $bids */
        $bids = $this->loadData('bids');
        /** @var \projects projects */
        $this->projects = $this->loadData('projects');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BidManager $bidManger */
        $bidManger = $this->get('unilend.service.bid_manager');

        if (isset($_POST['id_bid']) && $bids->get($_POST['id_bid'], 'id_bid')) {
            $serialize = serialize($_POST);
            $this->users_history->histo(4, 'Bid en cours delete', $_SESSION['user']['id_user'], $serialize);

            $bid = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->find($_POST['id_bid']);
            $bidManger->reject($bid, false);

            $this->lBids = $bids->select('id_lender_account = ' . $bid->getIdLenderAccount()->getId() . ' AND status = ' . Bids::STATUS_PENDING, 'added DESC');
        }
    }

    public function _loadMouvTransac()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);

        if (isset($_POST['year'], $_POST['id_client'])) {
            /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
            $this->translator = $this->get('translator');
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var LenderOperationsManager $lenderOperationsManager */
            $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

            $year                   = filter_var($_POST['year'], FILTER_VALIDATE_INT);
            $idClient               = filter_var($_POST['id_client'], FILTER_VALIDATE_INT);
            $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($idClient, WalletType::LENDER);
            $start                  = new \DateTime();
            $start->setDate($year, 1, 1);
            $end                    = new \DateTime();
            $end->setDate($year, 12, 31);
            $this->lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $start, $end, null, LenderOperationsManager::ALL_TYPES);
        }
        $this->setView('../preteurs/transactions');
    }

    public function _check_status_dossier()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \companies $company */
        $company = $this->loadData('companies');
        /** @var \clients $client */
        $client = $this->loadData('clients');

        if (
            false === isset($_POST['status'], $_POST['id_project'])
            || ProjectsStatus::COMMERCIAL_REJECTION == $_POST['status'] && false === isset($_POST['comment'], $_POST['public'])
            || false === $project->get($_POST['id_project'], 'id_project')
            || false === $company->get($project->id_company, 'id_company')
            || false === $client->get($company->id_client_owner, 'id_client')
            || $project->period <= 0
            || $project->amount <= 0
        ) {
            echo 'nok';
            return;
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        $projectStatusManager->addProjectStatus($this->userEntity, $_POST['status'], $project);

        if ($project->status == ProjectsStatus::COMMERCIAL_REJECTION) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->id_project);

            /** @var \projects_status_history_details $historyDetails */
            $historyDetails                              = $this->loadData('projects_status_history_details');
            $historyDetails->id_project_status_history   = $projectStatusHistory->id_project_status_history;
            $historyDetails->commercial_rejection_reason = $_POST['rejection_reason'];
            $historyDetails->create();

            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $projectEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);

            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity->setIdProject($projectEntity);
            $projectCommentEntity->setIdUser($this->userEntity);
            $projectCommentEntity->setContent('<p><u>Rejet commercial</u><p>' . $_POST['comment'] . '</p>');
            $projectCommentEntity->setPublic(empty($_POST['public']) ? false : true);

            $entityManager->persist($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            if (false === empty($client->email) && '1' === $_POST['send_email']) {
                $keywords = [
                    'firstName' => $client->prenom
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);

                try {
                    $message->setTo($client->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->get('logger')->warning(
                        'Could not send email : emprunteur-dossier-rejete - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }

        echo 'ok';
    }

    public function _valid_rejete_etape6()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            false === isset($_POST['id_project'], $_POST['status'])
            || null === ($project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']))
            || $_POST['status'] == 1 && (
                empty($_POST['structure']) || $_POST['structure'] > 10
                || empty($_POST['rentabilite']) || $_POST['rentabilite'] > 10
                || empty($_POST['tresorerie']) || $_POST['tresorerie'] > 10
                || empty($_POST['performance_financiere']) || $_POST['performance_financiere'] > 10
                || empty($_POST['individuel']) || $_POST['individuel'] > 10
                || empty($_POST['global']) || $_POST['global'] > 10
                || empty($_POST['marche_opere']) || $_POST['marche_opere'] > 10
                || empty($_POST['dirigeance']) || $_POST['dirigeance'] > 10
                || empty($_POST['indicateur_risque_dynamique']) || $_POST['indicateur_risque_dynamique'] > 10
                || empty($_POST['avis']) || strlen($_POST['avis']) < 50
            )
        ) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            return;
        }

        if (
            $project->getStatus() !== ProjectsStatus::ANALYSIS_REVIEW
            || $this->userEntity !== $project->getIdAnalyste()
        ) {
            echo json_encode([
                'success' => false,
                'error'   => 'Vous ne disposez pas des droits nécessaires'
            ]);
            return;
        }

        $projectRating = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);

        if (null === $projectRating) {
            $projectRating = new ProjectsNotes();
            $projectRating->setIdProject($project);

            $entityManager->persist($projectRating);
        }

        $projectRating->setStructure(round(str_replace(',', '.', $_POST['structure']), 1));
        $projectRating->setRentabilite(round(str_replace(',', '.', $_POST['rentabilite']), 1));
        $projectRating->setTresorerie(round(str_replace(',', '.', $_POST['tresorerie']), 1));
        $projectRating->setPerformanceFinanciere(round(str_replace(',', '.', $_POST['performance_financiere']), 1));
        $projectRating->setIndividuel(round(str_replace(',', '.', $_POST['individuel']), 1));
        $projectRating->setGlobal(round(str_replace(',', '.', $_POST['global']), 1));
        $projectRating->setMarcheOpere(round(str_replace(',', '.', $_POST['marche_opere']), 1));
        $projectRating->setDirigeance(round(str_replace(',', '.', $_POST['dirigeance']), 1));
        $projectRating->setIndicateurRisqueDynamique(round(str_replace(',', '.', $_POST['indicateur_risque_dynamique']), 1));
        $projectRating->setNote(round($projectRating->getPerformanceFinanciere() * 0.2 + $projectRating->getMarcheOpere() * 0.2 + $projectRating->getDirigeance() * 0.2 + $projectRating->getIndicateurRisqueDynamique() * 0.4, 1));
        $projectRating->setAvis($_POST['avis']);

        $projectRating->setStructureComite(empty($projectRating->getStructureComite()) ? $projectRating->getStructure() : $projectRating->getStructureComite());
        $projectRating->setRentabiliteComite(empty($projectRating->getRentabiliteComite()) ? $projectRating->getRentabilite() : $projectRating->getRentabiliteComite());
        $projectRating->setTresorerieComite(empty($projectRating->getTresorerieComite()) ? $projectRating->getTresorerie() : $projectRating->getTresorerieComite());
        $projectRating->setPerformanceFinanciereComite(empty($projectRating->getPerformanceFinanciereComite()) ? $projectRating->getPerformanceFinanciere() : $projectRating->getPerformanceFinanciereComite());
        $projectRating->setIndividuelComite(empty($projectRating->getIndividuelComite()) ? $projectRating->getIndividuel() : $projectRating->getIndividuelComite());
        $projectRating->setGlobalComite(empty($projectRating->getGlobalComite()) ? $projectRating->getGlobal() : $projectRating->getGlobalComite());
        $projectRating->setMarcheOpereComite(empty($projectRating->getMarcheOpereComite()) ? $projectRating->getMarcheOpere() : $projectRating->getMarcheOpereComite());
        $projectRating->setDirigeanceComite(empty($projectRating->getDirigeanceComite()) ? $projectRating->getDirigeance() : $projectRating->getDirigeanceComite());
        $projectRating->setIndicateurRisqueDynamiqueComite(empty($projectRating->getIndicateurRisqueDynamiqueComite()) ? $projectRating->getIndicateurRisqueDynamique() : $projectRating->getIndicateurRisqueDynamiqueComite());
        $projectRating->setNoteComite(empty($projectRating->getNoteComite()) ? $projectRating->getNote() : $projectRating->getNoteComite());

        $entityManager->flush($projectRating);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');

        if ($_POST['status'] == 1) {
            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::COMITY_REVIEW, $project);
        } elseif ($_POST['status'] == 2) {
            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::ANALYSIS_REJECTION, $project);

            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

            /** @var \projects_status_history_details $historyDetails */
            $historyDetails                            = $this->loadData('projects_status_history_details');
            $historyDetails->id_project_status_history = $projectStatusHistory->id_project_status_history;
            $historyDetails->analyst_rejection_reason  = $_POST['rejection_reason'];
            $historyDetails->create();

            $client = $project->getIdCompany()->getIdClientOwner();

            if ($client instanceof Clients && false === empty($client->getEmail()) && '1' === $_POST['send_email']) {
                $keywords = [
                    'firstName' => $client->getPrenom()
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);

                try {
                    $message->setTo($client->getEmail());
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->get('logger')->warning(
                        'Could not send email : emprunteur-dossier-rejete - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                    echo json_encode(['success' => false, 'error' => 'Email non envoyé à l\'emprunteur.']);
                    return;
                }
            }
        }

        echo json_encode(['success' => true]);
    }

    public function _valid_rejete_etape7()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (
            false === isset($_POST['id_project'], $_POST['status'])
            || null === ($project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($_POST['id_project']))
            || in_array($_POST['status'], [1, 4]) && (
                empty($_POST['structure_comite']) || $_POST['structure_comite'] > 10
                || empty($_POST['rentabilite_comite']) || $_POST['rentabilite_comite'] > 10
                || empty($_POST['tresorerie_comite']) || $_POST['tresorerie_comite'] > 10
                || empty($_POST['performance_financiere_comite']) || $_POST['performance_financiere_comite'] > 10
                || empty($_POST['individuel_comite']) || $_POST['individuel_comite'] > 10
                || empty($_POST['global_comite']) || $_POST['global_comite'] > 10
                || empty($_POST['marche_opere_comite']) || $_POST['marche_opere_comite'] > 10
                || empty($_POST['dirigeance_comite']) || $_POST['dirigeance_comite'] > 10
                || empty($_POST['indicateur_risque_dynamique_comite']) || $_POST['indicateur_risque_dynamique_comite'] > 10
                || empty($_POST['avis_comite']) || strlen($_POST['avis_comite']) < 50
            )
        ) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            return;
        }

        if (
            $project->getStatus() !== ProjectsStatus::COMITY_REVIEW
            || false === $userManager->isGrantedManagement($this->userEntity)
        ) {
            echo json_encode([
                'success' => false,
                'error'   => 'Vous ne disposez pas des droits nécessaires'
            ]);
            return;
        }

        $projectRating = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);

        $projectRating->setStructureComite(round(str_replace(',', '.', $_POST['structure_comite']), 1));
        $projectRating->setRentabiliteComite(round(str_replace(',', '.', $_POST['rentabilite_comite']), 1));
        $projectRating->setTresorerieComite(round(str_replace(',', '.', $_POST['tresorerie_comite']), 1));
        $projectRating->setPerformanceFinanciereComite(round(str_replace(',', '.', $_POST['performance_financiere_comite']), 1));
        $projectRating->setIndividuelComite(round(str_replace(',', '.', $_POST['individuel_comite']), 1));
        $projectRating->setGlobalComite(round(str_replace(',', '.', $_POST['global_comite']), 1));
        $projectRating->setMarcheOpereComite(round(str_replace(',', '.', $_POST['marche_opere_comite']), 1));
        $projectRating->setDirigeanceComite(round(str_replace(',', '.', $_POST['dirigeance_comite']), 1));
        $projectRating->setIndicateurRisqueDynamiqueComite(round(str_replace(',', '.', $_POST['indicateur_risque_dynamique_comite']), 1));
        $projectRating->setNoteComite(round($projectRating->getPerformanceFinanciereComite() * 0.2 + $projectRating->getMarcheOpereComite() * 0.2 + $projectRating->getDirigeanceComite() * 0.2 + $projectRating->getIndicateurRisqueDynamiqueComite() * 0.4, 1));
        $projectRating->setAvisComite($_POST['avis_comite']);

        $entityManager->flush($projectRating);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRatingManager $projectRatingManager */
        $projectRatingManager = $this->get('unilend.service.project_rating_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');

        $riskRating = $projectRatingManager->calculateRiskRating($project);
        $project->setRisk($riskRating);
        $entityManager->flush($project);

        if (null === $project->getIdCompany()->getIdClientOwner() || empty($project->getIdCompany()->getIdClientOwner()->getIdClient())) {
            echo json_encode([
                'success' => false,
                'error'   => 'Impossible de récupérer le dirigeant de la société'
            ]);
            return;
        }

        /** @var \clients $client */
        $client = $this->loadData('clients');
        $client->get($project->getIdCompany()->getIdClientOwner()->getIdClient());

        if ($_POST['status'] == 1) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');

            $existingStatus  = [];
            $companyProjects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $project->getIdCompany()]);

            foreach ($companyProjects as $companyProject) {
                $statusHistory = $projectStatusHistory->getHistoryDetails($companyProject->getIdProject());
                foreach ($statusHistory as $status) {
                    $existingStatus[] = $status['status'];
                }
            }

            if (false === in_array(ProjectsStatus::PREP_FUNDING, $existingStatus)) {
               /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendBorrowerAccount($client, 'ouverture-espace-emprunteur-plein');
            }

            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::PREP_FUNDING, $project);
        } elseif ($_POST['status'] == 2) {
            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::COMITY_REJECTION, $project);

            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

            /** @var \projects_status_history_details $historyDetails */
            $historyDetails                            = $this->loadData('projects_status_history_details');
            $historyDetails->id_project_status_history = $projectStatusHistory->id_project_status_history;
            $historyDetails->comity_rejection_reason   = $_POST['rejection_reason'];
            $historyDetails->create();

            if (false === empty($client->email) && '1' === $_POST['send_email']) {
                $keywords = [
                    'firstName' => $client->prenom
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);

                try {
                    $message->setTo($client->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->get('logger')->warning(
                        'Could not send email : emprunteur-dossier-rejete - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                    echo json_encode(['success' => false, 'error' => 'Email non envoyé à l\'emprunteur.']);
                    return;
                }
            }
        } elseif ($_POST['status'] == 4) {
            $projectCommentEntity = new \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments();
            $projectCommentEntity->setIdProject($project);
            $projectCommentEntity->setIdUser($this->userEntity);
            $projectCommentEntity->setContent('<p><u>Conditions suspensives de mise en ligne</u><p>' . $_POST['suspensive_conditions_comment'] . '</p>');
            $projectCommentEntity->setPublic(true);

            $entityManager->persist($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::SUSPENSIVE_CONDITIONS, $project);
        }

        if (
            false === empty($project->getRisk()) && false === empty($project->getPeriod())
            && false === in_array($project->getStatus(), [ProjectsStatus::COMMERCIAL_REJECTION, ProjectsStatus::ANALYSIS_REJECTION, ProjectsStatus::COMITY_REJECTION])
        ) {
            try {
                $idRate = $projectManager->getProjectRateRangeId($project);
                $project->setIdRate($idRate);
                $entityManager->flush($project);
            } catch (\Exception $exception) {
                echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
                return;
            }
        }

        echo json_encode(['success' => true]);
    }

    public function _session_content_email_completude()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->autoFireView = false;

        if (isset($_POST['id_client']) && isset($_POST['content']) && isset($_POST['liste'])) {
            $_SESSION['content_email_completude'][$_POST['id_client']] = '<ul>' . $this->ficelle->speChar2HtmlEntities($_POST['liste']) . '</ul>' . nl2br(htmlentities($_POST['content']));
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _session_project_completude()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        if (isset($_POST['id_project']) && isset($_POST['content']) && isset($_POST['list'])) {
            $_SESSION['project_submission_files_list'][$_POST['id_project']] = '<ul>' . $this->ficelle->speChar2HtmlEntities($_POST['list']) . '</ul>' . nl2br($_POST['content']);
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _check_force_pass()
    {
        $this->autoFireView = false;
        $this->tab_result   = $this->ficelle->testpassword($_POST['pass']);

        if ($this->tab_result['score'] < 50) {
            $this->couleur = "red";
            $this->wording = "Faible";
        } elseif ($this->tab_result['score'] < 100) {
            $this->couleur = "orange";
            $this->wording = "Moyen";
        } elseif ($this->tab_result['score'] <= 500) {
            $this->couleur = "green";
            $this->wording = "Fort";
        } elseif ($this->tab_result['score'] > 500) {
            $this->couleur = "green";
            $this->wording = "Tr&egrave;s fort";
        }

        echo '
            <p class="password-info" >Indicateur de protection :
                <span style="color:' . $this->couleur . '">' . $this->wording . '</span>
            </p>
        ';
        die;
    }

    public function _get_cities()
    {
        $this->users->checkAccess();
        $this->autoFireView = false;

        $aCities = array();
        if (isset($_GET['term']) && '' !== trim($_GET['term'])) {
            $_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
            $oVilles = $this->loadData('villes');

            $bBirthPlace = false;
            if (isset($this->params[0]) && 'birthplace' === $this->params[0]) {
                $bBirthPlace = true;
            }

            if ($bBirthPlace) {
                $aResults = $oVilles->lookupCities($_GET['term'], array('ville', 'cp'), true);
            } else {
                $aResults = $oVilles->lookupCities($_GET['term']);
            }
            if (false === empty($aResults)) {
                if ($bBirthPlace) {
                    foreach ($aResults as $aItem) {

                        // unique insee code
                        $aCities[$aItem['insee']] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['num_departement'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                    $aCities = array_values($aCities);
                } else {
                    foreach ($aResults as $aItem) {
                        $aCities[] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['cp'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                }
            }
        }

        echo json_encode($aCities);
    }

    public function _updateClientFiscalAddress()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->autoFireView = false;

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $sResult = 'nok';

        if (isset($this->params[0]) && isset($this->params[1])) {
            if ('0' == $this->params[1]) {
                /** @var clients_adresses $oClientAddress */
                $oClientAddress = $this->loadData('clients_adresses');

                if ($oClientAddress->get($this->params[0], 'id_client')) {

                    $oClientAddress->cp_fiscal    = $_POST['zip'];
                    $oClientAddress->ville_fiscal = $_POST['city'];
                    $oClientAddress->update();
                    $sResult = 'ok';
                }
            } elseif ('1' == $this->params[1]) {
                /** @var companies $oCompanies */
                $oCompanies = $this->loadData('companies');
                if ($oCompanies->get($this->params[0], 'id_client_owner')) {

                    $oCompanies->zip = $_POST['zip'];
                    $oCompanies->city = $_POST['city'];
                    $oCompanies->update();
                    $sResult = 'ok';
                }
            }
        }

        echo $sResult;
    }

    public function _patchClient()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);
        $this->autoFireView = false;

        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        /** @var clients $oClient */
        $oClient = $this->loadData('clients');

        $sResult = 'nok';

        if (isset($this->params[0]) && $oClient->get($this->params[0])) {
            foreach ($_POST as $item => $value) {
                $oClient->$item = $value;
            }
            $oClient->update();
            $sResult = 'ok';
        }

        echo $sResult;
    }

    public function _send_email_borrower_area()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->autoFireView = false;

        if (isset($_POST['id_client'], $_POST['type'])) {
            /** @var \clients $client */
            $client = $this->loadData('clients');
            $client->get($_POST['id_client'], 'id_client');

            switch ($_POST['type']) {
                case 'open':
                    $sTypeEmail = 'ouverture-espace-emprunteur';
                    break;
                case 'initialize':
                default:
                    $sTypeEmail = 'mot-de-passe-oublie-emprunteur';
                    break;
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendBorrowerAccount($client, $sTypeEmail);
        }
    }

    public function _quick_search()
    {
        $this->users->checkAccess();
        $this->autoFireView = false;

        $result = [
            'projects' => [],
            'lenders'  => []
        ];

        if ($search = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING)) {
            /** @var EntityManager $entityManager */
            $entityManager      = $this->get('doctrine.orm.entity_manager');
            $projectsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $clientsRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

            foreach ($projectsRepository->findByAutocomplete($search, 5) as $project) {
                $result['projects'][] = [
                    'id'       => $project['idProject'],
                    'title'    => empty($project['title']) ? $project['name'] : $project['title'],
                    'amount'   => $this->ficelle->formatNumber($project['amount'], 0),
                    'duration' => $project['period'],
                    'siren'    => $project['siren'],
                    'status'   => $project['label']
                ];
            }

            foreach ($clientsRepository->findLendersByAutocomplete($search, 5) as $lender) {
                $result['lenders'][] = [
                    'id'   => $lender['idClient'],
                    'name' => $lender['name'],
                    'type' => in_array($lender['type'], [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER]) ? 'Personne morale' : 'Personne physique'
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
