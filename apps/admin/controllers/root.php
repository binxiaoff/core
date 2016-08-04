<?php

class rootController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
    }

    public function _login()
    {
        // On masque le header et le footer
        $this->autoFireHead   = false;
        $this->autoFireHeader = false;
        $this->autoFireDebug  = false;
        $this->autoFireFooter = false;

        // Formulaire d'envoi d'un nouveau password
        if (isset($_POST['form_new_password'])) {
            if ($this->users->get(trim($_POST['email']), 'email')) {
                // Generation du nouveau mot de passe
                $this->new_password    = $this->ficelle->generatePassword(7);
                $this->users->password = md5($this->new_password);
                $this->users->update();

                // On enregistre la modif du mot de passe
                $this->loggin_connection_admin                 = $this->loadData('loggin_connection_admin');
                $this->loggin_connection_admin->id_user        = $this->users->id_user;
                $this->loggin_connection_admin->nom_user       = $this->users->firstname . " " . $this->users->name;
                $this->loggin_connection_admin->email          = $this->users->email;
                $this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
                $this->loggin_connection_admin->ip             = $_SERVER["REMOTE_ADDR"];
                $country_code                                  = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                $this->loggin_connection_admin->pays           = $country_code;
                $this->loggin_connection_admin->statut         = 2;
                $this->loggin_connection_admin->create();
                //***********************************************//
                //*** ENVOI DU MAIL AVEC NEW PASSWORD NON EMT ***//
                //***********************************************//

                $aVars = array(
                    '$cms'      => $this->cms,
                    '$surl'     => $this->surl,
                    '$url'      => $this->lurl,
                    '$email'    => trim($_POST['email']),
                    '$password' => $this->new_password
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('admin-nouveau-mot-de-passe', $aVars, false);
                $message->setTo(trim($_POST['email']));
                $mailer = $this->get('mailer');
                $mailer->send($message);

                // Mise en session du message
                $_SESSION['msgErreur']   = 'newPassword';
                $_SESSION['newPassword'] = 'OK';

                // Renvoi sur la page de login
                header('Location:' . $this->lurl . '/login');
                die;
            } else {
                // Mise en session du message
                $_SESSION['msgErreur']   = 'newPassword';
                $_SESSION['newPassword'] = 'NOK';

                header('Location:' . $this->lurl . '/login');
                die;
            }
        }
    }

    public function _logout()
    {
        $this->autoFireView = false;

        $_SESSION['request_url'] = $this->lurl;

        $this->users->handleLogout();
    }

    public function _sitemap()
    {
        // Controle d'acces à la rubrique
        $this->users->checkAccess('edition');

        // Activation du menu
        $this->menu_admin = 'edition';

        // Recuperation du sitemap
        $sitemap = $this->tree->getSitemap($this->language, $this->params[0]);

        // Mise en session du message
        $_SESSION['freeow']['title'] = 'Sitemap du site';

        // On enregistre le sitemap dans le fichier
        $fichier = $this->path . 'public/default/sitemap.xml';
        $handle  = fopen($fichier, "w");

        // Regarde si le fichier est accessible en écriture
        if (is_writable($fichier)) {
            // Ecriture echec
            if (fwrite($handle, $sitemap) === FALSE) {
                $_SESSION['freeow']['message'] = 'Impossible d\'écrire dans le fichier : ' . $fichier;
                exit;
            }

            // Ecriture réussie
            $_SESSION['freeow']['message'] = 'Le sitemap a bien &eacute;t&eacute; cr&eacute;&eacute; !';

            fclose($handle);
        } else {
            $_SESSION['freeow']['message'] = 'Impossible d\'écrire dans le fichier : ' . $fichier;
        }

        // Renvoi sur la page de gestion
        header('Location:' . $this->lurl . '/tree');
        die;
    }

    public function _indexation()
    {
        // Controle d'acces à la rubrique
        $this->users->checkAccess('edition');

        $this->menu_admin = 'edition';

        $_SESSION['freeow']['title']   = 'Indexation du site';
        $_SESSION['freeow']['message'] = 'Le site a bien &eacute;t&eacute; index&eacute; !';

        // Renvoi sur la page de gestion
        header('Location:' . $this->lurl . '/tree');
        die;
    }

    public function _default()
    {
        // Check de la plateforme
        if ($this->cms == 'iZinoa') {
            // Renvoi sur la page de gestion de l'arbo
            header('Location:' . $this->lurl . '/tree');
            die;
        }

        // Controle d'acces à la rubrique
        $this->users->checkAccess('dashboard');

        // Activation du menu
        $this->menu_admin = 'dashboard';

        $this->projects_status   = $this->loadData('projects_status');
        $this->projects          = $this->loadData('projects');

        $this->lProjectsNok = $this->projects->selectProjectsByStatus([\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE]);
        $this->lStatus      = $this->projects_status->select();
    }

    public function _edit_password()
    {
        // On masque le header et le footer
        $this->autoFireHead   = false;
        $this->autoFireHeader = false;
        $this->autoFireDebug  = false;
        $this->autoFireFooter = false;

        $this->users = $this->loadData('users');

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Recuperation des infos de la personne
        $this->users->get($this->params[0], 'id_user');

        // on check si le user en session est bien celui chargé, sinon on bloque
        if ($this->users->id_user != $_SESSION['user']['id_user']) {
            // Renvoi sur la liste des utilisateurs
            header('Location:' . $this->lurl . '/users');
            die;
        }

        if (isset($_POST['form_edit_pass_user'])) {

            // on check si le user qui post est le même que celui qu'on a en session, sinon on bloque tout
            if ($_POST['id_user'] == $_SESSION['user']['id_user']) {
                // Recuperation des infos de la personne
                $this->users->get($_POST['id_user'], 'id_user');

                $this->retour_pass      = "";
                $changement_pass_valide = false;

                // on check si tout est bien rempli
                if ($_POST['old_pass'] != "" && $_POST['new_pass'] != "" && $_POST['new_pass2'] != "") {
                    // on va checker si l'ancien mot de passe est bien le mot de passe courant du user
                    if ($this->users->password == md5($_POST['old_pass'])) {
                        // on check si le nouveau mot de passe est valide avec les regles en vigueurs
                        if ($this->ficelle->password_bo($_POST['new_pass'])) {
                            //on check si les 2 nouveaux mots de passe sont identiques
                            if ($_POST['new_pass'] == $_POST['new_pass2']) {
                                // tout est good donc on enregistre le nouveau passe.
                                $this->users->password        = md5($_POST['new_pass']);
                                $this->users->password_edited = date('Y-m-d H:i:s');
                                $this->users->update();

                                // on change le pass en session pour ne pas etre déco
                                $_SESSION['user']['password']        = md5($_POST['new_pass']);
                                $_SESSION['user']['password_edited'] = date('Y-m-d H:i:s');

                                //***********************************************//
                                //*** ENVOI DU MAIL AVEC NEW PASSWORD NON EMT ***//
                                //***********************************************//

                                $aVars = array(
                                    '$cms'      => $this->cms,
                                    '$surl'     => $this->surl,
                                    '$url'      => $this->lurl,
                                    '$email'    => trim($this->users->email),
                                    '$password' => $_POST['new_pass']
                                );

                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('admin-nouveau-mot-de-passe', $aVars, false);
                                $message->setTo(trim($this->users->email));

                                $oSettings = $this->loadData('settings');
                                $oSettings->get('alias_tracking_log', 'type');
                                if (false === empty($oSettings->value)) {
                                    $message->setBcc($oSettings->value);
                                }

                                $mailer = $this->get('mailer');
                                $mailer->send($message);


                                // On enregistre la modif du mot de passe
                                $this->loggin_connection_admin                 = $this->loadData('loggin_connection_admin');
                                $this->loggin_connection_admin->id_user        = $this->users->id_user;
                                $this->loggin_connection_admin->nom_user       = $this->users->firstname . " " . $this->users->name;
                                $this->loggin_connection_admin->email          = $this->users->email;
                                $this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
                                $this->loggin_connection_admin->ip             = $_SERVER["REMOTE_ADDR"];
                                $country_code                                  = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                                $this->loggin_connection_admin->pays           = $country_code;
                                $this->loggin_connection_admin->statut         = 2;
                                $this->loggin_connection_admin->create();


                                // Mise en session du message
                                $_SESSION['freeow']['title']   = 'Modification de votre mot de passe';
                                $_SESSION['freeow']['message'] = 'Votre mot de passe a bien &eacute;t&eacute; modifi&eacute; !';

                                // Renvoi sur la liste des utilisateurs
                                header('Location:' . $this->lurl);
                                die;

                            } else {
                                $this->retour_pass = "La confirmation du nouveau de passe doit être la même que votre nouveau mot de passe";
                            }
                        } else {
                            $this->retour_pass = "Le mot de passe doit contenir au moins 10 caract&egrave;res, ainsi qu'au moins 1 chiffre et un caract&egrave;re sp&eacute;cial";
                        }

                    } else {
                        $this->retour_pass = " L'ancien mot de passe ne correspond pas";
                    }
                } else {
                    $this->retour_pass = "Tous les champs sont obligatoires";
                }
            } else {
                // Renvoi sur la liste des utilisateurs
                header('Location:' . $this->lurl . '/users');
                die;
            }
        }
    }

    public function _captcha()
    {
        $_SESSION['request_url'] = '/';

        require_once($this->path . 'librairies/captcha/classes/captcha.class.php');
        PhocaCaptcha::displayCaptcha($this->path . 'librairies/captcha/images/06.jpg');
        $this->captchaCode = $_SESSION['captcha'];
    }
}