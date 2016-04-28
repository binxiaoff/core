<?php

class usersController extends bootstrap
{
    var $Command;

    function usersController(&$command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;
        $this->menu_admin = 'admin';

        $this->users_zones       = $this->loadData('users_zones');
        $this->users_types       = $this->loadData('users_types');
        $this->users_types_zones = $this->loadData('users_types_zones');
    }

    function _default()
    {
        $this->users->checkAccess('admin');

        // Formulaire d'ajout d'un utilisateur
        if (isset($_POST['form_add_users'])) {
            $this->users->firstname    = $_POST['firstname'];
            $this->users->name         = $_POST['name'];
            $this->users->phone        = $_POST['phone'];
            $this->users->mobile       = $_POST['mobile'];
            $this->users->email        = $_POST['email'];
            $this->users->password     = md5($_POST['password']);
            $this->users->id_tree      = $_POST['id_tree'];
            $this->users->status       = $_POST['status'];
            $this->users->id_user_type = $_POST['id_user_type'];
            $this->users->id_user      = $this->users->create();

            $lZones = $this->users_types_zones->select('id_user_type = ' . $this->users->id_user_type . ' ');
            foreach ($lZones as $zone) {
                $users_zones = $this->loadData('users_zones');

                $users_zones->id_user = $this->users->id_user;
                $users_zones->id_zone = $zone['id_zone'];

                $users_zones->create();
            }

            $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location:' . $this->lurl . '/zones');
            die;
        }

        // Formulaire de modification d'un utilisateur
        if (isset($_POST['form_mod_users'])) {
            $users_zones = $this->loadData('users_zones');

            $this->users->get($this->params[0], 'id_user');

            $this->users->firstname = $_POST['firstname'];
            $this->users->name      = $_POST['name'];
            $this->users->phone     = $_POST['phone'];
            $this->users->mobile    = $_POST['mobile'];
            $this->users->email     = $_POST['email'];
            if ($_POST['password'] != '') {
                $this->users->password = md5($_POST['password']);
            }
            $this->users->id_tree      = $_POST['id_tree'];
            $this->users->status       = ($this->users->status == 2 ? 2 : isset($_POST['status']) ? $_POST['status'] : $this->users->status);
            $this->users->id_user_type = $_POST['id_user_type'];
            $this->users->update();

            $this->users_zones->delete($this->users->id_user, 'id_user');
            $lZones = $this->users_types_zones->select('id_user_type = ' . $this->users->id_user_type . ' ');

            foreach ($lZones as $zone) {
                $users_zones->id_user = $this->users->id_user;
                $users_zones->id_zone = $zone['id_zone'];
                $users_zones->create();
            }

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location:' . $this->lurl . '/users');
            die;
        }

        // Suppression d'un utilisateur
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->users->get($this->params[0], 'id_user');

            if ($this->users->status != 2) {
                $this->users->delete($this->params[1], 'id_user');
                $this->users_zones->delete($this->params[1], 'id_user');
            }

            $_SESSION['freeow']['title']   = 'Suppression d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; supprim&eacute; !';

            header('Location:' . $this->lurl . '/users');
            die;
        }

        // Modification du status d'un utilisateur
        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->users->get($this->params[1], 'id_user');

            if ($this->users->status != 2) {
                $this->users->status = ($this->params[2] == 1 ? 0 : 1);
                $this->users->update();
            }

            $_SESSION['freeow']['title']   = 'Statut d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'Le statut de l\'utilisateur a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location:' . $this->lurl . '/users');
            die;
        }

        $this->lUsers = $this->users->select('id_user != 1', 'name ASC');
    }

    function _edit()
    {
        $this->users->checkAccess('admin');
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');
        $this->lTree       = $this->tree->listChilds(0, '-', array(), $this->language);
        $this->lUsersTypes = $this->users_types->select('', ' label ASC ');
    }

    function _edit_perso()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');
        $this->lTree = $this->tree->listChilds(0, '-', array(), $this->language);
    }


    function _add()
    {
        $this->users->checkAccess('admin');
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->lTree       = $this->tree->listChilds(0, '-', array(), $this->language);
        $this->lUsersTypes = $this->users_types->select('', ' label ASC ');
    }

    // on copie le traitement de default car on peut ne pas avoir les droits sur les users et modifier quand meme ses infos
    function _edit_perso_user()
    {
        // Formulaire de modification d'un utilisateur
        if (isset($_POST['form_mod_users'])) {
            $this->users->get($this->params[0], 'id_user');

            $this->users->firstname = $_POST['firstname'];
            $this->users->name      = $_POST['name'];
            $this->users->phone     = $_POST['phone'];
            $this->users->mobile    = $_POST['mobile'];
            $this->users->email     = $_POST['email'];
            if ($_POST['password'] != '') {
                $this->users->password = md5($_POST['password']);
            }

            $this->users->update();

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location:' . $this->lurl);
            die;
        }
    }

    function _edit_password()
    {
        $_SESSION['request_url'] = $this->url;
        $this->users->get($this->params[0], 'id_user');

        if ($this->users->id_user != $_SESSION['user']['id_user']) {
            header('Location:' . $this->lurl);
            die;
        }

        if (isset($_POST['form_edit_pass_user'])) {
            if ($_POST['id_user'] == $_SESSION['user']['id_user']) {
                $this->users->get($_POST['id_user'], 'id_user');

                $this->retour_pass      = "";

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

                                // on change le pass en session pour ne pas etre d�co
                                $_SESSION['user']['password']        = md5($_POST['new_pass']);
                                $_SESSION['user']['password_edited'] = date('Y-m-d H:i:s');

                                //***********************************************//
                                //*** ENVOI DU MAIL AVEC NEW PASSWORD NON EMT ***//
                                //***********************************************//

                                // Recuperation du modele de mail
                                $this->mails_text->get('admin-nouveau-mot-de-passe', 'lang = "' . $this->language . '" AND type');

                                // Variables du mailing
                                $cms      = $this->cms;
                                $surl     = $this->surl;
                                $url      = $this->lurl;
                                $email    = trim($this->users->email);
                                $password = $_POST['new_pass'];

                                // Attribution des donn�es aux variables
                                $sujetMail = $this->mails_text->subject;
                                eval("\$sujetMail = \"$sujetMail\";");

                                $texteMail = $this->mails_text->content;
                                eval("\$texteMail = \"$texteMail\";");

                                $exp_name = $this->mails_text->exp_name;
                                eval("\$exp_name = \"$exp_name\";");

                                $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                                $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                                // Envoi du mail
                                $this->email = $this->loadLib('email', array());
                                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                                $this->email->addRecipient(trim($this->users->email));

                                // ajout du tracking
                                $this->settings->get('alias_tracking_log', 'type');
                                $this->alias_tracking_log = $this->settings->value;
                                if ($this->alias_tracking_log != "") {
                                    $this->email->addBCCRecipient($this->alias_tracking_log);
                                }

                                $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
                                $this->email->setHTMLBody($texteMail);
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);

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

                                $_SESSION['freeow']['title']   = 'Modification de votre mot de passe';
                                $_SESSION['freeow']['message'] = 'Votre mot de passe a bien &eacute;t&eacute; modifi&eacute; !';

                                header('Location:' . $this->lurl);
                                die;
                            } else {
                                $this->retour_pass = "La confirmation du nouveau de passe doit �tre la m�me que votre nouveau mot de passe";
                            }
                        } else {
                            $this->retour_pass = "Le mot de passe doit contenir au moins 10 caract&egrave;res, ainsi qu'au moins 1 chiffre et 1 caract&egrave;re sp&eacute;cial";
                        }
                    } else {
                        $this->retour_pass = " L'ancien mot de passe ne correspond pas";
                    }
                } else {
                    $this->retour_pass = "Tous les champs sont obligatoires";
                }
            } else {
                header('Location:' . $this->lurl);
                die;
            }
        }
    }

    function _logs()
    {
        $this->loggin_connection_admin = $this->loadData('loggin_connection_admin');
        $this->L_Recuperation_logs     = $this->loggin_connection_admin->select('', 'added DESC', '', 500);
    }

    function _export_logs()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->requete        = 'SELECT * FROM loggin_connection_admin ORDER BY added desc';
        $this->requete_result = $this->bdd->query($this->requete);
    }
}
