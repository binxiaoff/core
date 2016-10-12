<?php

class usersController extends bootstrap
{
    const STATUS_ONLINE  = 1;
    const STATUS_OFFLINE = 0;

    var $Command;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->menu_admin = 'admin';

        $this->users_zones       = $this->loadData('users_zones');
        $this->users_types       = $this->loadData('users_types');
        $this->users_types_zones = $this->loadData('users_types_zones');
    }

    public function _default()
    {
        $this->users->checkAccess('admin');

        if (isset($_POST['form_add_users'])) {
            if (false === isset($_POST['email'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Veuillez entrer une adresse e-mail';

                header('Location: ' . $this->lurl . '/users');
                die;
            } elseif (false === $this->ficelle->isEmail($_POST['email'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Veuillez entrer une adresse e-mail valide';

                header('Location: ' . $this->lurl . '/users');
                die;
            } elseif ($this->users->select('email = "' . $_POST['email'] . '"')) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Cet utilisateur existe déjà';

                header('Location: ' . $this->lurl . '/users');
                die;
            }

            $newPassword               = $this->ficelle->generatePassword(10);
            $this->users->firstname    = $_POST['firstname'];
            $this->users->name         = $_POST['name'];
            $this->users->phone        = $_POST['phone'];
            $this->users->mobile       = $_POST['mobile'];
            $this->users->email        = $_POST['email'];
            $this->users->status       = $_POST['status'];
            $this->users->id_user_type = $_POST['id_user_type'];
            $this->users->password     = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->users->id_user      = $this->users->create();

            /** @var \users_zones $usersZones */
            $usersZones = $this->loadData('users_zones');

            $lZones = $this->users_types_zones->select('id_user_type = ' . $this->users->id_user_type . ' ');
            foreach ($lZones as $zone) {
                $usersZones->id_user = $this->users->id_user;
                $usersZones->id_zone = $zone['id_zone'];
                $usersZones->create();
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($this->users, $newPassword);

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

        $this->onlineUsers  = $this->users->select('id_user != 1 AND status = ' . \usersController::STATUS_ONLINE, 'name ASC');
        $this->offlineUsers = $this->users->select('id_user != 1 AND status = ' . \usersController::STATUS_OFFLINE, 'name ASC');
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

        if (isset($_POST['form_edit_pass_user']) && isset($_SESSION['user']['id_user']) && $this->users->get($_SESSION['user']['id_user'])) {
            /** @var \previous_passwords $previousPasswords */
            $previousPasswords = $this->loadData('previous_passwords');

            $this->retour_pass = '';
            if ($_POST['old_pass'] == '' || $_POST['new_pass'] == '' || $_POST['new_pass2'] == '') {
                $this->retour_pass = "Tous les champs sont obligatoires";
            } elseif ($this->users->password != md5($_POST['old_pass']) && $this->users->password != password_verify($_POST['old_pass'], $this->users->password)) {
                $this->retour_pass = "L'ancien mot de passe ne correspond pas";
            } elseif (false === $this->users->checkPasswordStrength($_POST['new_pass'])) {
                $this->retour_pass = "Le mot de passe doit contenir au moins 10 caractères, ainsi qu'au moins 1 chiffre et 1 caractère spécial";
            } elseif ($_POST['new_pass'] != $_POST['new_pass2']) {
                $this->retour_pass = "La confirmation du nouveau de passe doit être la même que votre nouveau mot de passe";
            } elseif (false === $previousPasswords->isValidPassword($_POST['new_pass'], $this->users->id_user)) {
                $this->retour_pass = "Ce mot de passe a déja été utilisé";
            } else {
                $oldPassword                  = $this->users->password;
                $this->users->password        = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
                $this->users->password_edited = date("Y-m-d H:i:s");
                $this->users->update();

                $_SESSION['user']['password']        = $this->users->password;
                $_SESSION['user']['password_edited'] = $this->users->password_edited;

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendPasswordModificationEmail($this->users);

                $previousPasswords->id_user  = $this->users->id_user;
                $previousPasswords->password = $oldPassword;
                $previousPasswords->archived = date("Y-m-d H:i:s");
                $previousPasswords->create();
                $previousPasswords->deleteOldPasswords($this->users->id_user);

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
        if ($this->users->checkAccess('admin') && isset($this->params[0]) && $this->users->get($this->params[0], 'id_user')) {
            $newPassword = $this->ficelle->generatePassword(10);
            $this->users->changePassword($newPassword, $this->users, true);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($this->users, $newPassword);
        }
        $_SESSION['freeow']['title']   = 'Modification du mot de passe';
        $_SESSION['freeow']['message'] = 'Le mot de passe a bien &eacute;t&eacute; modifi&eacute; !';

        header('Location: ' . $this->lurl . '/users');
        die;
    }
}
