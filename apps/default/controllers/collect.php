<?php

class collectController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;
    }

    public function _default()
    {
        $this->hideDecoration();
        die;
    }

    public function _prospect()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $key  = 'unilend';
        $time = '60';

        if (isset($_POST['token']) && $this->ficelle->verifier_token(trim($_POST['token']), $key, $time)) {
            $form_ok      = true;
            $erreur       = '';
            $nom          = $this->filterPost('nom');
            $prenom       = $this->filterPost('prenom');
            $email        = $this->filterPost('email', FILTER_SANITIZE_EMAIL);
            $date         = $this->filterPost('date');

            if (! isset($nom) || strlen($nom) > 255 || strlen($nom) <= 0) {
                $form_ok = false;
                $erreur .= 'Nom;';
            }
            if (! isset($prenom) || strlen($prenom) > 255 || strlen($prenom) <= 0) {
                $form_ok = false;
                $erreur .= 'Prenom;';
            }
            if (! isset($email) || $email == '' || strlen($email) > 255 || strlen($email) <= 0) {
                $form_ok = false;
                $erreur .= 'Email;';
            } elseif (! $this->ficelle->isEmail($email)) {
                $form_ok = false;
                $erreur .= 'Format email;';
            } elseif ($this->clients->existEmail($email) == false) {
                $clients_status_history = $this->loadData('clients_status_history');
                if ($this->clients->get($email, 'slug_origine != "" AND email') && $clients_status_history->counter('id_client = ' . $this->clients->id_client) <= 0) {
                    $form_update = true;
                } else {
                    $form_ok = false;
                    $erreur .= 'Email existant;';
                }
            }

            if (! isset($date) || $date == '0000-00-00 00:00:00' || $date == '' || strlen($date) > 19 || strlen($date) < 19) {
                $date      = date('Y-m-d H:i:s');
                $date_diff = false;
            } else {
                $date_diff = true;
            }

            if ($form_ok == true) {
                $this->prospects = $this->loadData('prospects');
                $this->prospects->nom          = $nom;
                $this->prospects->prenom       = $prenom;
                $this->prospects->email        = $email;
                $this->prospects->id_langue    = $this->language;

                /**
                 * Set the UTMs and slug_origine
                 */
                $this->ficelle->setSource($this->prospects);

                if (isset($form_update) && $form_update == true && $this->prospects->get($email, 'email')) {
                    $this->prospects->update();
                } else {
                    $this->prospects->create();
                }

                if ($date_diff == true) {
                    $this->prospects->update_added($date, $this->prospects->id_prospect);
                }

                $reponse     = 'OK';
                $id_prospect = $this->prospects->id_prospect;
            } else {
                $erreur     = explode(';', $erreur);
                $lesErreurs = array_filter($erreur);

                $newErreurs = array();
                foreach ($lesErreurs as $k => $e) {
                    $newErreurs[$k]['erreur'] = $e;
                }
                $erreur  = $newErreurs;
                $reponse = $erreur;
            }
        } else {
            $reponse = array('Erreur' => 'Token');
        }

        echo json_encode(array('reponse' => $reponse, 'id_prospect' => isset($id_prospect) ? $id_prospect : null));
    }

    public function _inscription()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $key  = 'unilend';
        $time = '60';

        if (isset($_POST['token']) && $this->ficelle->verifier_token(trim($_POST['token']), $key, $time)) {
            $this->pays                    = $this->loadData('pays_v2');
            $this->nationalites            = $this->loadData('nationalites_v2');
            $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
            $this->clients                 = $this->loadData('clients');
            $this->clients_adresses        = $this->loadData('clients_adresses');
            $this->lenders_accounts        = $this->loadData('lenders_accounts');

            $forme_preteur = $this->filterPost('forme_preteur');
            $civilite      = $this->filterPost('civilite');
            $nom           = $this->filterPost('nom');
            $nom_usage     = $this->filterPost('nom_usage');
            $prenom        = $this->filterPost('prenom');
            $email         = $this->filterPost('email');
            $password      = $this->filterPost('password');
            $question      = $this->filterPost('question');
            $reponse       = $this->filterPost('reponse');

            $adresse_fiscale = $this->filterPost('adresse_fiscale');
            $ville_fiscale   = $this->filterPost('ville_fiscale');
            $cp_fiscale      = $this->filterPost('cp_fiscale');
            $id_pays_fiscale = $this->filterPost('id_pays_fiscale');

            $adresse = $this->filterPost('adresse');
            $ville   = $this->filterPost('ville');
            $cp      = $this->filterPost('cp');
            $id_pays = $this->filterPost('id_pays');

            $telephone         = $this->filterPost('telephone');
            $id_nationalite    = $this->filterPost('id_nationalite');
            $date_naissance    = $this->filterPost('date_naissance');
            $commune_naissance = $this->filterPost('commune_naissance');
            $id_pays_naissance = $this->filterPost('id_pays_naissance');
            $signature_cgv     = $this->filterPost('signature_cgv');
            $date              = $this->filterPost('date');
            $insee_birth       = $this->filterPost('insee_birth');

            $form_ok     = true;
            $form_update = false;
            $erreur      = '';

            if (! isset($forme_preteur) || ! in_array($forme_preteur, array(1, 3))) {
                $form_ok = false;
                $erreur .= 'Forme preteur;';
            }
            if (! isset($civilite) || ! in_array($civilite, array('M.', 'Mme', 'Mlle'))) {
                $form_ok = false;
                $erreur .= 'Civilite;';
            }
            if (! isset($nom) || strlen($nom) > 255 || strlen($nom) <= 0) {
                $form_ok = false;
                $erreur .= 'Nom;';
            }
            if (strlen($nom_usage) > 255) {
                $form_ok = false;
                $erreur .= 'Nom usage;';
            }
            if (! isset($prenom) || strlen($prenom) > 255 || strlen($prenom) <= 0) {
                $form_ok = false;
                $erreur .= 'Prenom;';
            }
            if (! isset($email) || $email == '' || strlen($email) > 255 || strlen($email) <= 0) {
                $form_ok = false;
                $erreur .= 'Email;';
            } elseif (! $this->ficelle->isEmail($email)) {
                $form_ok = false;
                $erreur .= 'Format email;';
            } elseif ($this->clients->existEmail($email) == false) {
                $clients_status_history = $this->loadData('clients_status_history');
                if ($this->clients->get($email, 'origine = 1 AND email') && $clients_status_history->counter('id_client = ' . $this->clients->id_client) <= 0) {
                    $form_update = true;
                } else {
                    $form_ok = false;
                    $erreur .= 'Email déjà présent;';
                }
            }
            if (! isset($password) || strlen($password) > 255 || strlen($password) <= 0) {
                $form_ok = false;
                $erreur .= 'Mot de passe;';
            }
            if (strlen($question) > 255) {
                $form_ok = false;
                $erreur .= 'Question secrète;';
            }
            if (strlen($reponse) > 255) {
                $form_ok = false;
                $erreur .= 'Reponse secrète;';
            }
            if (! isset($adresse_fiscale) || strlen($adresse_fiscale) > 255 || strlen($adresse_fiscale) <= 0) {
                $form_ok = false;
                $erreur .= 'Adresse fiscale;';
            }
            if (! isset($ville_fiscale) || strlen($ville_fiscale) > 255 || strlen($ville_fiscale) <= 0) {
                $form_ok = false;
                $erreur .= 'Ville fiscale;';
            }
            $oVilles = $this->loadData('villes');
            if (false === isset($cp_fiscale) || false === $oVilles->exist($cp_fiscale, 'cp')) {
                $form_ok = false;
                $erreur .= 'Code postal fiscale;';
            }
            if (! isset($id_pays_fiscale) || $this->pays->get($id_pays_fiscale, 'id_pays') == false) {
                $form_ok = false;
                $erreur .= 'Pays fiscale;';
            }
            if ($adresse == '' && $ville == '' && $cp == '' && in_array($id_pays, array('', 0))) {
                $meme_adresse_fiscal = true;
            } else {
                $meme_adresse_fiscal = false;

                if (isset($adresse) && strlen($adresse) > 255) {
                    $form_ok = false;
                    $erreur .= 'Adresse;';
                }
                if (isset($ville) && strlen($ville) > 255) {
                    $form_ok = false;
                    $erreur .= 'Ville;';
                }
                if (isset($cp) && strlen($cp) != 0 && strlen($cp) != 5) {
                    $form_ok = false;
                    $erreur .= 'Code postal;';
                }
                if (isset($id_pays) && strlen($id_pays) > 0 && $this->pays->get($id_pays, 'id_pays') == false) {
                    $form_ok = false;
                    $erreur .= 'Pays;';
                }
            }
            if (! isset($telephone) || strlen($telephone) < 9 || strlen($telephone) > 14) {
                $form_ok = false;
                $erreur .= 'Téléphone;';
            }
            if (! isset($id_nationalite) || $this->nationalites->get($id_nationalite, 'id_nationalite') == false) {
                $form_ok = false;
                $erreur .= 'Nationalité;';
            }
            if (! isset($date_naissance) || $date_naissance == '0000-00-00 00:00:00' || strlen($date_naissance) != 10 || $this->dates->ageplus18($date_naissance) == false) {
                $form_ok = false;
                $erreur .= 'Date de naissance;';
            }
            if (! isset($commune_naissance) || strlen($commune_naissance) > 255 || strlen($commune_naissance) <= 0) {
                $form_ok = false;
                $erreur .= 'Commune de naissance;';
            }
            if (! isset($id_pays_naissance) || $this->pays->get($id_pays_naissance, 'id_pays') == false) {
                $form_ok = false;
                $erreur .= 'Pays de naissance;';
            }
            if (1 == $id_pays_naissance) {
                if ('' == $insee_birth) {
                    $oVilles = $this->loadData('villes');
                    //for France, the code insee is empty means that the city is not verified with table "villes", check again here.
                    if (false === $oVilles->get($commune_naissance, 'ville')) {
                        $form_ok = false;
                        $erreur .= 'Code INSEE de naissance;';
                    } else {
                        $insee_birth = $oVilles->insee;
                    }
                    unset($oVilles);
                }
            } else {
                /** @var pays_v2 $oPays */
                $oPays = $this->loadData('pays_v2');
                /** @var insee_pays $oInseePays */
                $oInseePays = $this->loadData('insee_pays');

                if ($oPays->get($id_pays_naissance) && $oInseePays->getByCountryIso(trim($oPays->iso))) {
                    $insee_birth = $oInseePays->COG;
                } else {
                    $form_ok = false;
                    $erreur .= 'Code INSEE de naissance;';
                }
                unset($oPays, $oInseePays);
            }
            if (! isset($signature_cgv) || $signature_cgv != 1) {
                $form_ok = false;
                $erreur .= 'Signature cgv;';
            }
            if (! isset($date) || $date == '0000-00-00 00:00:00' || $date == '' || strlen($date) > 19 || strlen($date) < 19) {
                $date      = date('Y-m-d H:i:s');
                $date_diff = false;
            } else {
                $date_diff = true;
            }

            if ($form_ok == true) {
                $this->clients->id_langue = 'fr';

                $this->clients->civilite  = $civilite;
                $this->clients->nom       = $nom;
                $this->clients->nom_usage = $nom_usage;
                $this->clients->prenom    = $prenom;
                $this->clients->slug      = $this->bdd->generateSlug($prenom . '-' . $nom);

                $this->clients->naissance         = $date_naissance;
                $this->clients->id_pays_naissance = $id_pays_naissance;
                $this->clients->ville_naissance   = $commune_naissance;
                $this->clients->insee_birth       = $insee_birth;
                $this->clients->id_nationalite    = $id_nationalite;

                $this->clients->telephone        = $telephone;
                $this->clients->email            = $email;
                $this->clients->password         = $password;
                $this->clients->secrete_question = $question;
                $this->clients->secrete_reponse  = md5($reponse);
                $this->clients->type             = $forme_preteur;

                $this->clients->status_pre_emp             = 1; // preteur
                $this->clients->status                     = 1; // online
                $this->clients->status_inscription_preteur = 1; // inscription terminé
                $this->clients->etape_inscription_preteur  = 1; // etape 1 ok

                /**
                 * Set the UTMs and slug_origine
                 */
                $this->ficelle->setSource($this->clients);

                $this->settings->get('Offre de bienvenue slug', 'type');
                $ArraySlugOffre = explode(';', $this->settings->value);

                $slug_origine = isset($_SESSION['source']['slug_origine']) ? $_SESSION['source']['slug_origine'] : '';

                if (in_array(trim($slug_origine), $ArraySlugOffre)) {
                    $this->clients->origine = 1;
                } // offre ok
                else {
                    $this->clients->origine = 0;
                } // pas d'offre

                if ($form_update == true) {
                    $this->clients->update();
                } else {
                    $this->clients->id_client = $this->clients->create();
                }

                if ($date_diff == true) {
                    $this->clients->update_added($date, $this->clients->id_client);
                }

                if ($form_update == true) {
                    $this->clients_adresses->get($this->clients->id_client, 'id_client');
                } else {
                    $this->clients_adresses->id_client = $this->clients->id_client;
                }

                $this->clients_adresses->adresse_fiscal = $adresse_fiscale;
                $this->clients_adresses->cp_fiscal      = $cp_fiscale;
                $this->clients_adresses->ville_fiscal   = $ville_fiscale;
                $this->clients_adresses->id_pays_fiscal = $id_pays_fiscale;

                if ($meme_adresse_fiscal == true) {
                    $this->clients_adresses->adresse1 = $adresse_fiscale;
                    $this->clients_adresses->cp       = $cp_fiscale;
                    $this->clients_adresses->ville    = $ville_fiscale;
                    $this->clients_adresses->id_pays  = $id_pays_fiscale;
                } else {
                    $this->clients_adresses->adresse1 = $adresse;
                    $this->clients_adresses->cp       = $cp;
                    $this->clients_adresses->ville    = $ville;
                    $this->clients_adresses->id_pays  = $id_pays;
                }

                if ($form_update == true) {
                    $this->clients_adresses->update();
                } else {
                    $this->clients_adresses->create();
                }

                if ($form_update == true) {
                    $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
                } else {
                    $this->lenders_accounts->id_client_owner = $this->clients->id_client;
                }
                $this->lenders_accounts->status = 1;

                if ($form_update == true) {
                    $this->lenders_accounts->update();
                } else {
                    $this->lenders_accounts->create();
                }

                $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                $this->lienConditionsGeneralesParticulier = $this->settings->value;

                if ($this->acceptations_legal_docs->get($this->lienConditionsGeneralesParticulier, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
                    $accepet_ok = true;
                } else {
                    $accepet_ok = false;
                }

                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesParticulier;
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;

                if ($accepet_ok == true) {
                    $this->acceptations_legal_docs->update();
                } else {
                    $this->acceptations_legal_docs->create();
                }

                $this->clients->get($this->clients->id_client, 'id_client');
                $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

                //******************************************************//
                //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
                //******************************************************//
                $this->mails_text->get('confirmation-inscription-preteur', 'lang = "' . $this->language . '" AND type');

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $varMail = array(
                    'surl'           => $this->surl,
                    'url'            => $this->lurl,
                    'prenom'         => utf8_decode($this->clients->prenom),
                    'email_p'        => $this->clients->email,
                    'mdp'            => '',
                    'motif_virement' => $this->clients->getLenderPattern($this->clients->id_client),
                    'lien_fb'        => $lien_fb,
                    'lien_tw'        => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                if ($this->Config['env'] === 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($this->clients->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }

                $_SESSION['LP_id_unique'] = $this->clients->id_client;

                echo json_encode(array(
                        'reponse'  => 'OK',
                        'URL'      => $this->lurl . '/inscription_preteur/etape2/' . $this->clients->hash,
                        'uniqueid' => $this->clients->id_client
                    )
                );
                die;
            } else {
                $lesErreurs = explode(';', $erreur);
                $lesErreurs = array_filter($lesErreurs);

                $newErreurs = array();
                foreach ($lesErreurs as $k => $e) {
                    $newErreurs[$k]['erreur'] = $e;
                }
                $erreur = $newErreurs;
                $reponse = $erreur;
            }
        } else {
            $reponse = array('Erreur' => 'Token');
        }

        echo json_encode(array('reponse' => $reponse, 'URL' => ''));
    }

    /**
     * Filter and sanitize POST field
     * @param string $sFieldName
     * @param int $iFilter
     * @return string
     */
    private function filterPost($sFieldName, $iFilter = FILTER_SANITIZE_STRING)
    {
        if (false !== ($mValue = filter_input(INPUT_POST, $sFieldName, $iFilter))) {
            return trim($mValue);
        }
        return '';
    }
}
