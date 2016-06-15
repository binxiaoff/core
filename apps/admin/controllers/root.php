<?php

use Unilend\core\Loader;

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
                $sNewPassword = $this->ficelle->generatePassword(10);
                $this->users->changePassword($sNewPassword, $this->users);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendNewPasswordEmail($sNewPassword, $this->users);

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
    }

    public function _logout()
    {
        $_SESSION['request_url'] = $this->lurl;

        $this->users->handleLogout();
    }

    public function _sitemap()
    {
        $this->users->checkAccess('edition');

        $this->menu_admin = 'edition';

        $sitemap = $this->tree->getSitemap($this->language, $this->params[0]);

        $_SESSION['freeow']['title'] = 'Sitemap du site';

        $fichier = $this->path . 'public/default/sitemap.xml';
        $handle  = fopen($fichier, "w");

        if (is_writable($fichier)) {
            if (fwrite($handle, $sitemap) === FALSE) {
                $_SESSION['freeow']['message'] = 'Impossible d\'écrire dans le fichier : ' . $fichier;
                exit;
            }

            $_SESSION['freeow']['message'] = 'Le sitemap a bien &eacute;t&eacute; cr&eacute;&eacute; !';

            fclose($handle);
        } else {
            $_SESSION['freeow']['message'] = 'Impossible d\'écrire dans le fichier : ' . $fichier;
        }

        header('Location:' . $this->lurl . '/tree');
        die;
    }

    public function _indexation()
    {
        $this->users->checkAccess('edition');

        $this->menu_admin = 'edition';

        $_SESSION['freeow']['title']   = 'Indexation du site';
        $_SESSION['freeow']['message'] = 'Le site a bien &eacute;t&eacute; index&eacute; !';

        header('Location:' . $this->lurl . '/tree');
        die;
    }

    public function _default()
    {
        if ($this->cms == 'iZinoa') {
            header('Location:' . $this->lurl . '/tree');
            die;
        }

        $this->users->checkAccess('dashboard');

        $this->menu_admin = 'dashboard';

        $this->projects_status = $this->loadData('projects_status');
        $this->projects        = $this->loadData('projects');

        $this->lProjectsNok = $this->projects->selectProjectsByStatus(implode(', ', array(\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE)));
        $this->lStatus      = $this->projects_status->select();
    }

    public function _edit_password()
    {
        $this->hideDecoration();

        $this->users = $this->loadData('users');

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        if ($this->users->get($this->params[0], 'id_user') && $this->users->id_user != $_SESSION['user']['id_user']) {
            header('Location:' . $this->lurl . '/users');
            die;
        }

        if (isset($_POST['form_edit_pass_user'])) {
            /** @var \previous_passwords $previous_passwords */
            $previous_passwords = Loader::loadData('previous_passwords');

            // on check si le user qui post est le même que celui qu'on a en session, sinon on bloque tout
            if ($_POST['id_user'] != $_SESSION['user']['id_user']) {
                header('Location:' . $this->lurl . '/users');
                die;
            }
            $this->users->get($_POST['id_user'], 'id_user');

            $this->retour_pass = '';
            if ($_POST['old_pass'] == '' || $_POST['new_pass'] == '' || $_POST['new_pass2'] == '') {
                $this->retour_pass = "Tous les champs sont obligatoires";
            }

            if ($this->users->password != md5($_POST['old_pass']) && $this->users->password != password_verify($_POST['old_pass'], $this->users->password)) {
                $this->retour_pass = "L'ancien mot de passe ne correspond pas";
            }

            if (false == $this->ficelle->password_bo($_POST['new_pass'], 10, true)) {
                $this->retour_pass = "Le mot de passe doit contenir au moins 10 caract&egrave;res, ainsi qu'au moins 1 chiffre et un caract&egrave;re sp&eacute;cial";
            }

            if (true === $previous_passwords->passwordUsed($_POST['old_pass'], $this->users->id_user)) {
                $this->retour_pass = "Non";
            }

            if ($_POST['new_pass'] == $_POST['new_pass2']) {
                $this->users->changePassword($_POST['new_pass'], $this->users, false);

                $_SESSION['user']['password']        = $this->users->password;
                $_SESSION['user']['password_edited'] = $this->users->password_edited;

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendPasswordModificationEmail($this->users);

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
                $this->retour_pass = "La confirmation du nouveau de passe doit être la même que votre nouveau mot de passe";
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