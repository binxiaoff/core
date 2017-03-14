<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class rootController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
    }

    public function _default()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->hideDecoration();
        $this->setView('../root/404');
    }

    /**
     * Pas de appel dans le log nginx , à supprimer après vérification.
     */
    public function _xmlAllProjects()
    {
        $file = $this->getParameter('path.user') . 'fichiers/045.xml';
        if (file_exists($file)) {
            header("Content-Type: application/xml; charset=utf-8");
            header("Content-Disposition: inline; filename=xmlAllProjects");
            header('Content-Length: ' . filesize($file));
            readfile($file);
        }
        exit;
    }

    // Enregistrement et lecture du pdf cgv
    public function _pdf_cgv_preteurs()
    {
        $this->autoFireView = false;

        include_once $this->path . '/apps/default/controllers/pdf.php';

        // hack the symfony guard token
        $session = $this->get('session');

        /** @var \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken $token */
        $token =  unserialize($session->get('_security_default'));
        if (!$token instanceof \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken) {
            header('Location: ' . $this->lurl);
            exit;
        }
        /** @var \Unilend\Bundle\FrontBundle\Security\User\UserLender $user */
        $user = $token->getUser();
        if (!$user instanceof \Unilend\Bundle\FrontBundle\Security\User\UserLender) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $this->clients->get($user->getClientId(), 'id_client')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $this->clients->checkAccessLender();

        $listeAccept = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client, 'added DESC', 0, 1);
        $listeAccept = array_shift($listeAccept);

        $id_tree_cgu = $listeAccept['id_legal_doc'];

        $contenu = $this->tree_elements->select('id_tree = "' . $id_tree_cgu . '" AND id_langue = "' . $this->language . '"');
        foreach ($contenu as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug]    = $elt['value'];
            $this->complement[$this->elements->slug] = $elt['complement'];
        }

        // si c'est un ancien cgv de la liste on lance le pdf
        if (in_array($id_tree_cgu, array(92, 95, 93, 254, 255))) {
            header("Content-disposition: attachment; filename=" . $this->content['pdf-cgu']);
            header("Content-Type: application/force-download");
            @readfile($this->surl . '/var/fichiers/' . $this->content['pdf-cgu']);
        } else {
            $oCommandPdf    = new \Command('pdf', 'cgv_preteurs', array($this->clients->hash), $this->language);
            $oPdf           = new \pdfController($oCommandPdf, 'default');
            $oPdf->setContainer($this->container);
            $oPdf->initialize();
            $path           = $this->path . 'protected/pdf/cgv_preteurs/' . $this->clients->id_client . '/';
            $sNamePdf       = 'cgv_preteurs-' . $this->clients->hash . '-' . $id_tree_cgu;
            $sNamePdfClient = 'CGV-UNILEND-PRETEUR-' . $this->clients->id_client . '-' . $id_tree_cgu;

            if (false  === file_exists($path . $sNamePdf)) {
                $this->cgv_preteurs(true, $oPdf, array($this->clients->hash));
                $oPdf->WritePdf($path . $sNamePdf, 'cgv_preteurs');
            }

            $oPdf->ReadPdf($path . $sNamePdf, $sNamePdfClient);
        }
    }

    // lecture page du cgv en html
    private function cgv_preteurs($bPdf = false, pdfController $oPdf = null, array $aParams = null)
    {
        $this->params = (false === is_null($aParams)) ? $aParams : $this->params;

        $this->pays                    = $this->loadData('pays_v2');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->companies               = $this->loadData('companies');

        $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
        $id_tree_cgu = $this->settings->value;

        foreach ($this->tree_elements->select('id_tree = "' . $id_tree_cgu . '" AND id_langue = "' . $this->language . '"') as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug]    = $elt['value'];
            $this->complement[$this->elements->slug] = $elt['complement'];
        }

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {
            if (isset($this->params[0]) && $this->params[0] != 'morale' && $this->params[0] != 'nosign') {
                $this->autoFireHeader = false;
                $this->autoFireHead   = true;
                $this->autoFireFooter = false;
            }

            if (isset($this->params[0]) && $this->params[0] == 'nosign') {
                $dateAccept = '';
            } else {
                $listeAccept = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client, 'added DESC', 0, 1);
                $listeAccept = array_shift($listeAccept);

                $dateAccept  = 'Sign&eacute; &eacute;lectroniquement le ' . date('d/m/Y', strtotime($listeAccept['added']));
            }

            $this->settings->get('Date nouvelles CGV avec 2 mandats', 'type');
            $sNewTermsOfServiceDate = $this->settings->value;

            /** @var \lenders_accounts $oLenderAccount */
            $oLenderAccount = $this->loadData('lenders_accounts');
            $oLenderAccount->get($this->clients->id_client, 'id_client_owner');

            /** @var \loans $oLoans */
            $oLoans      = $this->loadData('loans');
            $iLoansCount = $oLoans->counter('id_lender = ' . $oLenderAccount->id_lender_account . ' AND added < "' . $sNewTermsOfServiceDate . '"');

            if (in_array($this->clients->type, array(Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER))) {
                $this->clients_adresses->get($this->clients->id_client, 'id_client');

                if ($this->clients_adresses->id_pays_fiscal == 0) {
                    $this->clients_adresses->id_pays_fiscal = 1;
                }
                $this->pays->get($this->clients_adresses->id_pays_fiscal, 'id_pays');

                $aReplacements = array(
                    '[Civilite]'            => $this->clients->civilite,
                    '[Prenom]'              => utf8_encode($this->clients->prenom),
                    '[Nom]'                 => utf8_encode($this->clients->nom),
                    '[date]'                => date('d/m/Y', strtotime($this->clients->naissance)),
                    '[ville_naissance]'     => utf8_encode($this->clients->ville_naissance),
                    '[adresse_fiscale]'     => utf8_encode($this->clients_adresses->adresse_fiscal . ', ' . $this->clients_adresses->ville_fiscal . ', ' . $this->clients_adresses->cp_fiscal . ', ' . $this->pays->fr),
                    '[date_validation_cgv]' => $dateAccept
                );

                $this->mandat_de_recouvrement           = str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement']);
                $this->mandat_de_recouvrement_avec_pret = $iLoansCount > 0 ? str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement-avec-pret']) : '';
            } else {
                $this->companies->get($this->clients->id_client, 'id_client_owner');

                if ($this->companies->id_pays == 0) {
                    $this->companies->id_pays = 1;
                }
                $this->pays->get($this->companies->id_pays, 'id_pays');

                $aReplacements = array(
                    '[Civilite]'            => $this->clients->civilite,
                    '[Prenom]'              => utf8_encode($this->clients->prenom),
                    '[Nom]'                 => utf8_encode($this->clients->nom),
                    '[Fonction]'            => utf8_encode($this->clients->fonction),
                    '[Raison_sociale]'      => utf8_encode($this->companies->name),
                    '[SIREN]'               => $this->companies->siren,
                    '[adresse_fiscale]'     => utf8_encode($this->companies->adresse1 . ', ' . $this->companies->zip . ', ' . $this->companies->city . ', ' . $this->pays->fr),
                    '[date_validation_cgv]' => $dateAccept
                );

                $this->mandat_de_recouvrement           = str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement-personne-morale']);
                $this->mandat_de_recouvrement_avec_pret = $iLoansCount > 0 ? str_replace(array_keys($aReplacements), $aReplacements, $this->content['mandat-de-recouvrement-avec-pret-personne-morale']) : '';
            }
        } elseif (isset($this->params[0]) && $this->params[0] == 'morale') {
            $variables                              = array('[Civilite]', '[Prenom]', '[Nom]', '[Fonction]', '[Raison_sociale]', '[SIREN]', '[adresse_fiscale]', '[date_validation_cgv]');
            $tabVariables                           = explode(';', $this->content['contenu-variables-par-defaut-morale']);
            $contentVariables                       = $tabVariables;
            $this->mandat_de_recouvrement           = str_replace($variables, $contentVariables, $this->content['mandat-de-recouvrement-personne-morale']);
            $this->mandat_de_recouvrement_avec_pret = '';
        } else {
            $variables                              = array('[Civilite]', '[Prenom]', '[Nom]', '[date]', '[ville_naissance]', '[adresse_fiscale]', '[date_validation_cgv]');
            $tabVariables                           = explode(';', $this->content['contenu-variables-par-defaut']);
            $contentVariables                       = $tabVariables;
            $this->mandat_de_recouvrement           = str_replace($variables, $contentVariables, $this->content['mandat-de-recouvrement']);
            $this->mandat_de_recouvrement_avec_pret = '';
        }

        if (true === $bPdf && false === is_null($oPdf)) {
            $this->content['mandatRecouvrement']         = $this->mandat_de_recouvrement;
            $this->content['mandatRecouvrementAvecPret'] = $this->mandat_de_recouvrement_avec_pret;
            $oPdf->setDisplay('cgv_preteurs', $this->content);
        }
    }

    public function contactForm()
    {
        $this->lng['contact'] = $this->ln->selectFront('contact', $this->language, $this->App);

        $this->demande_contact            = $this->loadData('demande_contact');
        $this->demande_contact->demande   = $_POST['demande'];
        $this->demande_contact->preciser  = '';
        $this->demande_contact->nom       = $this->ficelle->majNom($_POST['nom']);
        $this->demande_contact->prenom    = $this->ficelle->majNom($_POST['prenom']);
        $this->demande_contact->email     = $_POST['email'];
        $this->demande_contact->message   = $_POST['message'];
        $this->demande_contact->societe   = $_POST['societe'];
        $this->demande_contact->telephone = $_POST['telephone'];

        $this->form_ok = true;

        $this->error_demande = 'ok';
        $this->error_message = 'ok';
        $this->error_nom     = 'ok';
        $this->error_prenom  = 'ok';
        $this->error_email   = 'ok';

        if (isset($_POST['telephone']) && $_POST['telephone'] != '' && $_POST['telephone'] != $this->lng['contact']['telephone']) {
            $this->error_telephone = 'ok';

            if (! is_numeric($_POST['telephone'])) {
                $this->form_ok         = false;
                $this->error_telephone = 'nok';
            }
        }

        if (! isset($_POST['demande']) || $_POST['demande'] == '' || $_POST['demande'] == 0) {
            $this->form_ok       = false;
            $this->error_demande = 'nok';
        }

        if (! isset($_POST['nom']) || $_POST['nom'] == '' || $_POST['nom'] == $this->lng['contact']['nom']) {
            $this->form_ok   = false;
            $this->error_nom = 'nok';
        }

        if (! isset($_POST['prenom']) || $_POST['prenom'] == '' || $_POST['prenom'] == $this->lng['contact']['prenom']) {
            $this->form_ok      = false;
            $this->error_prenom = 'nok';
        }

        if (! isset($_POST['email']) || $_POST['email'] == '' || $_POST['email'] == $this->lng['contact']['email']) {
            $this->form_ok     = false;
            $this->error_email = 'nok';
        } elseif (! $this->ficelle->isEmail($_POST['email'])) {
            $this->form_ok     = false;
            $this->error_email = 'nok';
        }

        if (! isset($_POST['message']) || $_POST['message'] == '' || $_POST['message'] == $this->lng['contact']['message']) {
            $this->form_ok       = false;
            $this->error_message = 'nok';
        }

        if ($this->form_ok == true) {
            $this->confirmation = $this->lng['contact']['confirmation'];

            if ($this->demande_contact->demande != 5) {
                $this->demande_contact->preciser = '';
            }

            $this->demande_contact->create();

            // Liste des objets
            $objets = array('', 'Relation presse', 'Demande preteur', 'Demande Emprunteur', 'Recrutement', 'Autre', 'Partenariat');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $pageProjets = $this->tree->getSlug(4, $this->language);

            $varMail = array(
                'surl'     => $this->surl,
                'url'      => $this->lurl,
                'email_c'  => $this->demande_contact->email,
                'prenom_c' => $this->demande_contact->prenom,
                'nom_c'    => $this->demande_contact->nom,
                'objet'    => $objets[$this->demande_contact->demande],
                'projets'  => $this->lurl . '/' . $pageProjets,
                'lien_fb'  => $lien_fb,
                'lien_tw'  => $lien_tw
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact', $varMail);
            $message->setTo($_POST['email']);
            $mailer = $this->get('mailer');
            $mailer->send($message);

            if ($this->demande_contact->demande == 1) {
                $this->settings->get('Adresse presse', 'type');
            } elseif ($this->demande_contact->demande == 2) {
                $this->settings->get('Adresse preteur', 'type');
            } elseif ($this->demande_contact->demande == 3) {
                $this->settings->get('Adresse emprunteur', 'type');
            } elseif ($this->demande_contact->demande == 4) {
                $this->settings->get('Adresse recrutement', 'type');
            } elseif ($this->demande_contact->demande == 5) {
                $this->settings->get('Adresse autre', 'type');
            } elseif ($this->demande_contact->demande == 6) {
                $this->settings->get('Adresse partenariat', 'type');
            }

            $destinataire = $this->settings->value;

            $infos = '<ul>';
            $infos .= '<li>Type demande : ' . $objets[$this->demande_contact->demande] . '</li>';
            if ($this->demande_contact->demande == 5) {
                $infos .= '<li>Preciser :' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->preciser) . '</li>';
            }
            $infos .= '<li>Nom : ' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->nom) . '</li>';
            $infos .= '<li>Prenom : ' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->prenom) . '</li>';
            $infos .= '<li>Email : ' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->email) . '</li>';
            $infos .= '<li>telephone : ' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->telephone) . '</li>';
            $infos .= '<li>Societe : ' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->societe) . '</li>';
            $infos .= '<li>Message : ' . $this->ficelle->speChar2HtmlEntities($this->demande_contact->message) . '</li>';
            $infos .= '</ul>';

            $variablesInternalMail = array(
                '$surl'   => $this->surl,
                '$url'    => $this->lurl,
                '$email'  => $this->demande_contact->email,
                '$nom'    => $this->demande_contact->nom,
                '$prenom' => $this->demande_contact->prenom,
                '$objet'  => $objets[$this->demande_contact->demande],
                '$infos'  => $infos
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact', $variablesInternalMail, false);
            $message->setTo($destinataire);
            $message->setReplyTo(array($this->demande_contact->email => $this->demande_contact->prenom . ' ' . $this->demande_contact->nom));
            $mailer = $this->get('mailer');
            $mailer->send($message);

            $this->demande_contact->demande   = '';
            $this->demande_contact->preciser  = '';
            $this->demande_contact->nom       = '';
            $this->demande_contact->prenom    = '';
            $this->demande_contact->email     = '';
            $this->demande_contact->message   = '';
            $this->demande_contact->societe   = '';
            $this->demande_contact->telephone = '';

            $this->error_demande = '';
            $this->error_message = '';
            $this->error_nom     = '';
            $this->error_prenom  = '';
            $this->error_email   = '';
            $this->error_captcha = '';
        }
    }
}
