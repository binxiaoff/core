<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\LoginConnectionAdmin;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\UsersTypes;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class rootController extends bootstrap
{
    public function _login()
    {
        $this->hideDecoration();

        if (isset($_POST['form_new_password'])) {
            if ($this->users->get(trim($_POST['email']), 'email')) {
                $newPassword = $this->ficelle->generatePassword(10);
                $this->users->changePassword($newPassword, $this->users, true);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendNewPasswordEmail($this->users, $newPassword);

                $loginLog = new LoginConnectionAdmin();
                $loginLog->setIdUser($this->users->id_user);
                $loginLog->setNomUser($this->users->firstname . ' ' . $this->users->name);
                $loginLog->setEmail($this->users->email);
                $loginLog->setDateConnexion(new \DateTime('now'));
                $loginLog->setIp($_SERVER['REMOTE_ADDR']);

                /** @var EntityManager $entityManager */
                $entityManager = $this->get('doctrine.orm.entity_manager');
                $entityManager->persist($loginLog);
                $entityManager->flush();

                $_SESSION['msgErreur']   = 'newPassword';
                $_SESSION['newPassword'] = 'OK';

                header('Location: ' . $this->lurl . '/login');
                die;
            } else {
                $_SESSION['msgErreur']   = 'newPassword';
                $_SESSION['newPassword'] = 'NOK';

                header('Location: ' . $this->lurl . '/login');
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
        $this->users->checkAccess(Zones::ZONE_LABEL_DASHBOARD);

        /** @var \users $user */
        $user = $this->loadData('users');
        $user->get($_SESSION['user']['id_user']);

        if (
            in_array($user->id_user_type, [UsersTypes::TYPE_COMMERCIAL, UsersTypes::TYPE_RISK])
            || in_array($user->id_user, [Users::USER_ID_ALAIN_ELKAIM, Users::USER_ID_ARNAUD_SCHWARTZ])
        ) {
            header('Location: ' . $this->lurl . '/dashboard');
            die;
        }

        $this->menu_admin = 'dashboard';

        $this->projects_status = $this->loadData('projects_status');
        $this->projects        = $this->loadData('projects');

        $this->lProjectsNok = $this->projects->selectProjectsByStatus([\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::PROBLEME, \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::LOSS]);
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

                $loginLog = new LoginConnectionAdmin();
                $loginLog->setIdUser($this->users->id_user);
                $loginLog->setNomUser($this->users->firstname . ' ' . $this->users->name);
                $loginLog->setEmail($this->users->email);
                $loginLog->setDateConnexion(new \DateTime('now'));
                $loginLog->setIp($_SERVER['REMOTE_ADDR']);

                /** @var EntityManager $entityManager */
                $entityManager = $this->get('doctrine.orm.entity_manager');
                $entityManager->persist($loginLog);
                $entityManager->flush();

                $_SESSION['freeow']['title']   = 'Modification de votre mot de passe';
                $_SESSION['freeow']['message'] = 'Votre mot de passe a bien &eacute;t&eacute; modifi&eacute; !';

                header('Location: ' . $this->lurl);
                die;
            } else {
                $this->retour_pass = "La confirmation du nouveau de passe doit être la même que votre nouveau mot de passe";
            }
        }
    }
}
