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
        $this->hideDecoration();

        if (isset($_POST['form_new_password'])) {
            if ($this->users->get(trim($_POST['email']), 'email')) {
                $newPassword = $this->ficelle->generatePassword(10);
                $this->users->changePassword($newPassword, $this->users, true);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendNewPasswordEmail($newPassword, $this->users);

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

                $_SESSION['msgErreur']   = 'newPassword';
                $_SESSION['newPassword'] = 'OK';

                header('Location:' . $this->lurl . '/login');
                die;
            } else {
                $_SESSION['msgErreur']   = 'newPassword';
                $_SESSION['newPassword'] = 'NOK';

                header('Location:' . $this->lurl . '/login');
                die;
            }
        }

        if (isset($_SESSION['auth'], $_SESSION['token']) && $_SESSION['auth'] && false === empty(trim($_SESSION['token']))) {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _logout()
    {
        $this->autoFireView = false;

        $_SESSION['request_url'] = $this->lurl;

        $this->users->handleLogout();
    }

    public function _default()
    {
        $this->users->checkAccess('dashboard');

        /** @var \users $user */
        $user = $this->loadData('users');
        $user->get($_SESSION['user']['id_user']);

        if (
            in_array($user->id_user_type, [\users_types::TYPE_COMMERCIAL, \users_types::TYPE_RISK])
            || in_array($user->id_user, [23, 28])
        ) {
            header('Location: ' . $this->lurl . '/dashboard');
            die;
        }

        $this->menu_admin = 'dashboard';

        $this->projects_status = $this->loadData('projects_status');
        $this->projects        = $this->loadData('projects');

        $this->lProjectsNok = $this->projects->selectProjectsByStatus([\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE]);
        $this->lStatus      = $this->projects_status->select('', 'status ASC');
    }

    public function _edit_password()
    {
        $this->hideDecoration();

        $this->users = $this->loadData('users');

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
                $this->retour_pass = "Le mot de passe doit contenir au moins 10 caractères, ainsi qu'au moins 1 majuscule, minuscule, chiffre et caractère spécial";
            } elseif (false === $previousPasswords->isValidPassword($_POST['new_pass'], $this->users->id_user)) {
                $this->retour_pass = "Ce mot de passe a déja été utilisé";
            } elseif ($_POST['new_pass'] == $_POST['new_pass2']) {
                $this->users->password        = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
                $this->users->password_edited = date("Y-m-d H:i:s");
                $this->users->update();

                $_SESSION['user']['password']        = $this->users->password;
                $_SESSION['user']['password_edited'] = $this->users->password_edited;

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendPasswordModificationEmail($this->users);

                $previousPasswords->id_user  = $this->users->id_user;
                $previousPasswords->password = $this->users->password;
                $previousPasswords->archived = date("Y-m-d H:i:s");
                $previousPasswords->create();
                $previousPasswords->deleteOldPasswords($this->users->id_user);

                $this->loggin_connection_admin                 = $this->loadData('loggin_connection_admin');
                $this->loggin_connection_admin->id_user        = $this->users->id_user;
                $this->loggin_connection_admin->nom_user       = $this->users->firstname . " " . $this->users->name;
                $this->loggin_connection_admin->email          = $this->users->email;
                $this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
                $this->loggin_connection_admin->ip             = $_SERVER["REMOTE_ADDR"];
                $this->loggin_connection_admin->pays           = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                $this->loggin_connection_admin->statut         = 2;
                $this->loggin_connection_admin->create();

                $_SESSION['freeow']['title']   = 'Modification de votre mot de passe';
                $_SESSION['freeow']['message'] = 'Votre mot de passe a bien &eacute;t&eacute; modifi&eacute; !';

                header('Location:' . $this->lurl);
                die;
            } else {
                $this->retour_pass = "La confirmation du nouveau de passe doit être la même que votre nouveau mot de passe";
            }
        }
    }
}
