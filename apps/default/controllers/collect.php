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
        $key  = 'unilend';
        $time = '60';

        if (isset($_POST['token']) && $this->ficelle->verifier_token(trim($_POST['token']), $key, $time)) {
            $form_ok = true;

            $erreur = '';

            $nom          = isset($_POST['nom']) ? trim($_POST['nom']) : '';
            $prenom       = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
            $email        = isset($_POST['email']) ? trim($_POST['email']) : '';
            $date         = isset($_POST['date']) ? trim($_POST['date']) : '';
            $slug_origine = isset($_POST['slug_origine']) ? trim($_POST['slug_origine']) : '';
            $utm_source   = isset($_POST['utm_source']) ? trim($_POST['utm_source']) : empty($slug_origine) ? $this->lurl . '/prospect' : $slug_origine;
            $utm_source2  = isset($_POST['utm_source2']) ? trim($_POST['utm_source2']) : '';
            $utm_source3  = isset($_POST['utm_source3']) ? trim($_POST['utm_source3']) : '';

            // Verif nom
            if (! isset($nom) || strlen($nom) > 255 || strlen($nom) <= 0) {
                $form_ok = false;
                $erreur .= 'Nom;';
            }
            // Verif prenom
            if (! isset($prenom) || strlen($prenom) > 255 || strlen($prenom) <= 0) {
                $form_ok = false;
                $erreur .= 'Prenom;';
            }
            // Verif email
            if (! isset($email) || $email == '' || strlen($email) > 255 || strlen($email) <= 0) {
                $form_ok = false;
                $erreur .= 'Email;';
            } // Verif format mail
            elseif (! $this->ficelle->isEmail($email)) {
                $form_ok = false;
                $erreur .= 'Format email;';
            } // Si exite déjà
            elseif ($this->clients->existEmail($email) == false) {

                $clients_status_history = $this->loadData('clients_status_history');
                if ($this->clients->get($email, 'slug_origine != "" AND email') && $clients_status_history->counter('id_client = ' . $this->clients->id_client) <= 0) {
                    $form_update = true;
                } else {
                    $form_ok = false;
                    $erreur .= 'Email existant;';
                }
            }
            // Verif date presente ou pas
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
                $this->prospects->source       = $utm_source;
                $this->prospects->source2      = $utm_source2;
                $this->prospects->source3      = $utm_source3;
                $this->prospects->slug_origine = $slug_origine;

                if (isset($form_update) && $form_update == true && $this->prospects->get($email, 'email')) {
                    $this->prospects->update();
                } else {
                    $this->prospects->create();
                }

                // on modifie la date du added en bdd
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

        echo json_encode(array('reponse' => $reponse, 'id_prospect' => $id_prospect));

        die;
    }

    public function _inscription()
    {
        $key  = 'unilend';
        $time = '60';

        if (isset($_POST['token']) && $this->ficelle->verifier_token(trim($_POST['token']), $key, $time)) {

            // chargement des datas
            $this->pays                    = $this->loadData('pays_v2');
            $this->nationalites            = $this->loadData('nationalites_v2');
            $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
            $this->clients                 = $this->loadData('clients');
            $this->clients_adresses        = $this->loadData('clients_adresses');
            $this->lenders_accounts        = $this->loadData('lenders_accounts');

            $utm_source    = isset($_POST['utm_source']) ? trim($_POST['utm_source']) : '';
            $utm_source2   = isset($_POST['utm_source2']) ? trim($_POST['utm_source2']) : '';
            $utm_source3   = isset($_POST['utm_source3']) ? trim($_POST['utm_source3']) : '';
            $slug_origine  = isset($_POST['slug_origine']) ? trim($_POST['slug_origine']) : '';
            $forme_preteur = isset($_POST['forme_preteur']) ? trim($_POST['forme_preteur']) : '';
            $civilite      = isset($_POST['civilite']) ? trim($_POST['civilite']) : '';
            $nom           = isset($_POST['nom']) ? trim($_POST['nom']) : '';
            $nom_usage     = isset($_POST['nom_usage']) ? trim($_POST['nom_usage']) : '';
            $prenom        = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
            $email         = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password      = isset($_POST['password']) ? trim($_POST['password']) : '';
            $question      = isset($_POST['question']) ? trim($_POST['question']) : '';
            $reponse       = isset($_POST['reponse']) ? trim($_POST['reponse']) : '';

            $adresse_fiscale = isset($_POST['adresse_fiscale']) ? trim($_POST['adresse_fiscale']) : '';
            $ville_fiscale   = isset($_POST['ville_fiscale']) ? trim($_POST['ville_fiscale']) : '';
            $cp_fiscale      = isset($_POST['cp_fiscale']) ? trim($_POST['cp_fiscale']) : '';
            $id_pays_fiscale = isset($_POST['id_pays_fiscale']) ? trim($_POST['id_pays_fiscale']) : '';

            $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
            $ville   = isset($_POST['ville']) ? trim($_POST['ville']) : '';
            $cp      = isset($_POST['cp']) ? trim($_POST['cp']) : '';
            $id_pays = isset($_POST['id_pays']) ? trim($_POST['id_pays']) : '';

            $telephone         = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
            $id_nationalite    = isset($_POST['id_nationalite']) ? trim($_POST['id_nationalite']) : '';
            $date_naissance    = isset($_POST['date_naissance']) ? trim($_POST['date_naissance']) : '';
            $commune_naissance = isset($_POST['commune_naissance']) ? trim($_POST['commune_naissance']) : '';
            $id_pays_naissance = isset($_POST['id_pays_naissance']) ? trim($_POST['id_pays_naissance']) : '';
            $signature_cgv     = isset($_POST['signature_cgv']) ? trim($_POST['signature_cgv']) : '';
            $date              = isset($_POST['date']) ? trim($_POST['date']) : '';
            $insee_birth       = isset($_POST['insee_birth']) ? trim($_POST['insee_birth']) : '';

            $form_ok = true;
            $form_update = false;

            $erreur = '';

            // Verif forme preteur
            if (! isset($forme_preteur) || ! in_array($forme_preteur, array(1, 3))) {
                $form_ok = false;
                $erreur .= 'Forme preteur;';
            }
            // Verif civilite
            if (! isset($civilite) || ! in_array($civilite, array('M.', 'Mme', 'Mlle'))) {
                $form_ok = false;
                $erreur .= 'Civilite;';
            }
            // Verif nom
            if (! isset($nom) || strlen($nom) > 255 || strlen($nom) <= 0) {
                $form_ok = false;
                $erreur .= 'Nom;';
            }
            // Verif nom usage
            if (strlen($nom_usage) > 255) {
                $form_ok = false;
                $erreur .= 'Nom usage;';
            }
            // Verif prenom
            if (! isset($prenom) || strlen($prenom) > 255 || strlen($prenom) <= 0) {
                $form_ok = false;
                $erreur .= 'Prenom;';
            }
            // Verif email
            if (! isset($email) || $email == '' || strlen($email) > 255 || strlen($email) <= 0) {
                $form_ok = false;
                $erreur .= 'Email;';
            } // Verif format mail
            elseif (! $this->ficelle->isEmail($email)) {
                $form_ok = false;
                $erreur .= 'Format email;';
            } // Si exite déjà
            elseif ($this->clients->existEmail($email) == false) {
                $clients_status_history = $this->loadData('clients_status_history');
                if ($this->clients->get($email, 'origine = 1 AND email') && $clients_status_history->counter('id_client = ' . $this->clients->id_client) <= 0) {
                    $form_update = true;
                } else {
                    $form_ok = false;
                    $erreur .= 'Email déjà présent;';
                }
            }
            // Verif mot de passe
            if (! isset($password) || strlen($password) > 255 || strlen($password) <= 0) {
                $form_ok = false;
                $erreur .= 'Mot de passe;';
            }
            // Verif question
            if (strlen($question) > 255) {
                $form_ok = false;
                $erreur .= 'Question secrète;';
            }
            // Verif reponse
            if (strlen($reponse) > 255) {
                $form_ok = false;
                $erreur .= 'Reponse secrète;';
            }

            // Verif adresse fiscale
            if (! isset($adresse_fiscale) || strlen($adresse_fiscale) > 255 || strlen($adresse_fiscale) <= 0) {
                $form_ok = false;
                $erreur .= 'Adresse fiscale;';
            }
            // Verif ville fiscale
            if (! isset($ville_fiscale) || strlen($ville_fiscale) > 255 || strlen($ville_fiscale) <= 0) {
                $form_ok = false;
                $erreur .= 'Ville fiscale;';
            }
            // Verif cp fiscale
            $oVilles = $this->loadData('villes');
            if (false === isset($cp_fiscale) || false === $oVilles->exist($cp_fiscale, 'cp')) {
                $form_ok = false;
                $erreur .= 'Code postal fiscale;';
            }
            // Verif id pays fiscale
            if (! isset($id_pays_fiscale) || $this->pays->get($id_pays_fiscale, 'id_pays') == false) {
                $form_ok = false;
                $erreur .= 'Pays fiscale;';
            }


            // meme adresse ou non
            if ($adresse == '' && $ville == '' && $cp == '' && in_array($id_pays, array('', 0))) {
                $meme_adresse_fiscal = true;
            } else {
                $meme_adresse_fiscal = false;

                // Verif adresse
                if (isset($adresse) && strlen($adresse) > 255) {
                    $form_ok = false;
                    $erreur .= 'Adresse;';
                }

                // Verif ville
                if (isset($ville) && strlen($ville) > 255) {
                    $form_ok = false;
                    $erreur .= 'Ville;';
                }

                // Verif cp
                if (isset($cp) && strlen($cp) != 0 && strlen($cp) != 5) {
                    $form_ok = false;
                    $erreur .= 'Code postal;';
                }

                // Verif id pays
                if (isset($id_pays) && strlen($id_pays) > 0 && $this->pays->get($id_pays, 'id_pays') == false) {
                    $form_ok = false;
                    $erreur .= 'Pays;';
                }

            }


            // Verif telephone
            if (! isset($telephone) || strlen($telephone) < 9 || strlen($telephone) > 14) {
                $form_ok = false;
                $erreur .= 'Téléphone;';
            }

            // Verif id nationalite
            if (! isset($id_nationalite) || $this->nationalites->get($id_nationalite, 'id_nationalite') == false) {
                $form_ok = false;
                $erreur .= 'Nationalité;';
            }

            // Verif date de naissance
            if (! isset($date_naissance) || $date_naissance == '0000-00-00 00:00:00' || strlen($date_naissance) != 10 || $this->dates->ageplus18($date_naissance) == false) {
                $form_ok = false;
                $erreur .= 'Date de naissance;';
            }


            // Verif Commune de naissance
            if (! isset($commune_naissance) || strlen($commune_naissance) > 255 || strlen($commune_naissance) <= 0) {
                $form_ok = false;
                $erreur .= 'Commune de naissance;';
            }
            // Verif id pays naissance
            if (! isset($id_pays_naissance) || $this->pays->get($id_pays_naissance, 'id_pays') == false) {
                $form_ok = false;
                $erreur .= 'Pays de naissance;';
            }
            // Verif code insee de naissance
            if (1 == $id_pays_naissance) {
                //Check birth city
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

            // Verif signature cgv
            if (! isset($signature_cgv) || $signature_cgv != 1) {
                $form_ok = false;
                $erreur .= 'Signature cgv;';
            }

            // Verif date presente ou pas
            if (! isset($date) || $date == '0000-00-00 00:00:00' || $date == '' || strlen($date) > 19 || strlen($date) < 19) {
                $date      = date('Y-m-d H:i:s');
                $date_diff = false;
            } else {
                $date_diff = true;
            }

            // slug_origine
            if (! isset($slug_origine) || $slug_origine == '') {
                $slug_origine = '';
            }

            // utm source
            if (! isset($utm_source) || $utm_source == '') {

                if ($slug_origine != '') {
                    $utm_source = $slug_origine;
                } else {
                    $utm_source = $this->lurl . '/inscription';
                }
            }

            // utm source
            if (! isset($utm_source2) || $utm_source2 == '') {
                $utm_source2 = '';
            }
            // utm source
            if (! isset($utm_source3) || $utm_source3 == '') {
                $utm_source3 = '';
            }

            // Si ok
            if ($form_ok == true) {


                // client
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
                $this->clients->source                     = $utm_source;
                $this->clients->source2                    = $utm_source2;
                $this->clients->source3                    = $utm_source3;
                $this->clients->slug_origine               = $slug_origine;

                // slugs autorisés à une offre de bienvenue
                $this->settings->get("Offre de bienvenue slug", 'type');
                $ArraySlugOffre = explode(';', $this->settings->value);

                if (in_array(trim($slug_origine), $ArraySlugOffre)) {
                    $this->clients->origine = 1;
                } // offre ok
                else {
                    $this->clients->origine = 0;
                } // pas d'offre

                // enregistrement
                if ($form_update == true) {
                    $this->clients->update();
                } else {
                    $this->clients->id_client = $this->clients->create();
                }

                // on modifie la date du added en bdd
                if ($date_diff == true) {
                    $this->clients->update_added($date, $this->clients->id_client);
                }

                // client adresse
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

                // enregistrement
                if ($form_update == true) {
                    $this->clients_adresses->update();
                } else {
                    $this->clients_adresses->create();
                }

                // lender
                if ($form_update == true) {
                    $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
                } else {
                    $this->lenders_accounts->id_client_owner = $this->clients->id_client;
                }
                $this->lenders_accounts->status = 1; // statut lender online

                // enregistrement
                if ($form_update == true) {
                    $this->lenders_accounts->update();
                } else {
                    $this->lenders_accounts->create();
                }

                // acceptations_legal_docs
                $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                $this->lienConditionsGeneralesParticulier = $this->settings->value;

                if ($this->acceptations_legal_docs->get($this->lienConditionsGeneralesParticulier, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
                    $accepet_ok = true;
                } else {
                    $accepet_ok = false;
                }

                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesParticulier;
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;

                // enregistrement
                if ($accepet_ok == true) {
                    $this->acceptations_legal_docs->update();
                } else {
                    $this->acceptations_legal_docs->create();
                }

                // on recup les infos client
                $this->clients->get($this->clients->id_client, 'id_client');
                $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');


                // Motif virement
                $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                $nom       = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                $id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                // email inscription preteur //

                //******************************************************//
                //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
                //******************************************************//

                // Recuperation du modele de mail
                $this->mails_text->get('confirmation-inscription-preteur', 'lang = "' . $this->language . '" AND type');

                $surl = $this->surl;
                $url  = $this->lurl;

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $varMail = array(
                    'surl'           => $surl,
                    'url'            => $url,
                    'prenom'         => utf8_decode($this->clients->prenom),
                    'email_p'        => $this->clients->email,
                    'mdp'            => '',
                    'motif_virement' => $motif,
                    'lien_fb'        => $lien_fb,
                    'lien_tw'        => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email', array());
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

                $reponse = 'OK';

                echo json_encode(array('reponse' => $reponse, 'URL' => $this->lurl . '/inscription_preteur/etape2/' . $this->clients->hash, 'uniqueid' => $this->clients->id_client));
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

        die;
    }
}

