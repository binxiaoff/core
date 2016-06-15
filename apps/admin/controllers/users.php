<?php

class usersController extends bootstrap
{
    var $Command;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll          = true;
        $this->menu_admin        = 'admin';
        $this->users_zones       = $this->loadData('users_zones');
        $this->users_types       = $this->loadData('users_types');
        $this->users_types_zones = $this->loadData('users_types_zones');
    }

    public function _default()
    {
        $this->users->checkAccess('admin');

        if (isset($_POST['form_add_users'])) {
            if ($this->users->select('email = "' . $_POST['email'] . '"')) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Cet utilisateur existe d&eacute;ja !';

                unset($_POST['form_add_users']);
                header('Location: ' . $this->lurl . '/users');
                die;
            }

            $this->users->firstname    = $_POST['firstname'];
            $this->users->name         = $_POST['name'];
            $this->users->phone        = $_POST['phone'];
            $this->users->mobile       = $_POST['mobile'];
            $this->users->email        = $_POST['email'];
            $this->users->status       = $_POST['status'];
            $this->users->id_user_type = $_POST['id_user_type'];
            $this->users->id_user      = $this->users->create();

            $sNewPassword = $this->ficelle->generatePassword(10);
            $this->users->changePassword($sNewPassword, $this->users, true);

            /** @var \users_zones $users_zones */
            $users_zones = $this->loadData('users_zones');

            $lZones = $this->users_types_zones->select('id_user_type = ' . $this->users->id_user_type . ' ');
            foreach ($lZones as $zone) {
                $users_zones->id_user = $this->users->id_user;
                $users_zones->id_zone = $zone['id_zone'];
                $users_zones->create();
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($this->users, $sNewPassword);

            $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->lurl . '/zones');
            die;
        }

        if (isset($_POST['form_mod_users'])) {
            $this->users->get($this->params[0], 'id_user');

            $this->users->firstname = $_POST['firstname'];
            $this->users->name      = $_POST['name'];
            $this->users->phone     = $_POST['phone'];
            $this->users->mobile    = $_POST['mobile'];
            $this->users->email     = $_POST['email'];

            if ($this->users->checkAccess('admin')) {
                /** @var \users_zones $usersZones */
                $usersZones = $this->loadData('users_zones');

                $this->users->status       = ($this->users->status == 2 ? 2 : isset($_POST['status']) ? $_POST['status'] : $this->users->status);
                $this->users->id_user_type = $_POST['id_user_type'];
                $this->users->update();

                $this->users_zones->delete($this->users->id_user, 'id_user');
                $lZones = $this->users_types_zones->select('id_user_type = ' . $this->users->id_user_type . ' ');

                foreach ($lZones as $zone) {
                    $usersZones->unsetData();
                    $usersZones->id_user = $this->users->id_user;
                    $usersZones->id_zone = $zone['id_zone'];
                    $usersZones->create();
                }
            }

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/users');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->users->get($this->params[0], 'id_user');

            if ($this->users->status != 2) {
                $this->users->delete($this->params[1], 'id_user');
                $this->users_zones->delete($this->params[1], 'id_user');
            }

            $_SESSION['freeow']['title']   = 'Suppression d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien &eacute;t&eacute; supprim&eacute; !';

            header('Location: ' . $this->lurl . '/users');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->users->get($this->params[1], 'id_user');

            if ($this->users->status != 2) {
                $this->users->status = ($this->params[2] == 1 ? 0 : 1);
                $this->users->update();
            }

            $_SESSION['freeow']['title']   = 'Statut d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'Le statut de l\'utilisateur a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/users');
            die;
        }

        $this->lUsers = $this->users->select('id_user != 1', 'name ASC');
    }

    public function _edit()
    {
        $this->users->checkAccess('admin');
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');
        $this->lUsersTypes = $this->users_types->select('', ' label ASC ');
    }

    public function _edit_perso()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');
    }

    public function _add()
    {
        $this->users->checkAccess('admin');
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->lUsersTypes = $this->users_types->select('', ' label ASC ');
    }

    public function _edit_password()
    {
        $_SESSION['request_url'] = $this->url;
        $this->users->get($this->params[0], 'id_user');

        if ($this->users->id_user != $_SESSION['user']['id_user'] && false === $this->users->checkAccess('admin')) {
            header('Location: ' . $this->lurl);
            die;
        }

        if (isset($_POST['form_edit_pass_user']) && $_POST['id_user'] == $_SESSION['user']['id_user']) {
            $this->users->get($_POST['id_user'], 'id_user');

            /** @var \previous_passwords $previousPasswords */
            $previousPasswords = $this->loadData('previous_passwords');

            $this->retour_pass = '';
            if ($_POST['old_pass'] == '' || $_POST['new_pass'] == '' || $_POST['new_pass2'] == '') {
                $this->retour_pass = "Tous les champs sont obligatoires";
            } elseif ($this->users->password != md5($_POST['old_pass']) && $this->users->password != password_verify($_POST['old_pass'], $this->users->password)) {
                $this->retour_pass = "L'ancien mot de passe ne correspond pas";
            } elseif (false === $this->ficelle->password_fo($_POST['new_pass'], 10, true)) {
                $this->retour_pass = "Le mot de passe doit contenir au moins 10 caract&egrave;res, ainsi qu'au moins 1 chiffre et 1 caract&egrave;re sp&eacute;cial";
            } elseif ($_POST['new_pass'] != $_POST['new_pass2']) {
                $this->retour_pass = "La confirmation du nouveau de passe doit &ecirc;tre la m&ecirc;me que votre nouveau mot de passe";
            } elseif (true === $previousPasswords->passwordUsed($_POST['new_pass'], $this->users->id_user)) {
                $this->retour_pass = "Ce mot de passe a d&eacute;ja &eacute;t&eacute; utilis&eacute; !";
            } else {
                $this->users->changePassword($_POST['new_pass'], $this->users, false);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendPasswordModificationEmail($this->users);

                $_SESSION['user']['password']        = $this->users->password;
                $_SESSION['user']['password_edited'] = $this->users->password_edited;

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

                header('Location: ' . $this->lurl);
                die;
            }
        }
    }

    public function _logs()
    {
        $this->loggin_connection_admin = $this->loadData('loggin_connection_admin');
        $this->L_Recuperation_logs     = $this->loggin_connection_admin->select('', 'added DESC', '', 500);
    }

    public function _export_logs()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->requete        = 'SELECT * FROM loggin_connection_admin ORDER BY added desc';
        $this->requete_result = $this->bdd->query($this->requete);
    }

    public function _generate_new_password()
    {
        if ($this->users->checkAccess('admin') && $this->params[0]) {
            $this->users->get($this->params[0], 'id_user');
            $sNewPassword = $this->ficelle->generatePassword(10);
            $this->users->changePassword($sNewPassword, $this->users, true);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($this->users, $sNewPassword);
        }
        header('Location: ' . $this->lurl . '/users');
        die;
    }
}
