<?php

use Doctrine\ORM\EntityManager;
use Unilend\Entity\{Users, Zones};

class usersController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->menu_admin        = 'admin';
        $this->users_zones       = $this->loadData('users_zones');
        $this->users_types_zones = $this->loadData('users_types_zones');
    }

    public function _default()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

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

            $userType = $entityManager->getRepository(UsersTypes::class)->find($_POST['id_user_type']);

            if (null === $userType) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Type d\'utilisateur non renseigné';

                header('Location: ' . $this->lurl . '/users');
                die;
            }

            $newPassword = $this->ficelle->generatePassword(10);
            $user        = new Users();
            $user
                ->setFirstname($_POST['firstname'])
                ->setName($_POST['name'])
                ->setPhone($_POST['phone'])
                ->setMobile($_POST['mobile'])
                ->setEmail($_POST['email'])
                ->setSlack($_POST['slack'])
                ->setIp($_POST['ip'])
                ->setStatus($_POST['status'])
                ->setIdUserType($userType)
                ->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));

            $entityManager->persist($user);
            $entityManager->flush($user);

            /** @var \users_zones $usersZones */
            $usersZones = $this->loadData('users_zones');
            $lZones     = $this->users_types_zones->select('id_user_type = ' . $userType->getIdUserType() . ' ');
            foreach ($lZones as $zone) {
                $usersZones->id_user = $user->getIdUser();
                $usersZones->id_zone = $zone['id_zone'];
                $usersZones->create();
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($user, $newPassword);

            $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien été ajouté';

            header('Location: ' . $this->lurl . '/zones');
            die;
        }

        if (isset($_POST['form_mod_users'])) {
            $user = $entityManager->getRepository(Users::class)->find($this->params[0]);

            if (null === $user) {
                $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Utilisateur inconnu';

                header('Location: ' . $this->lurl . '/users');
                die;
            }

            $user
                ->setFirstname($_POST['firstname'])
                ->setName($_POST['name'])
                ->setPhone($_POST['phone'])
                ->setMobile($_POST['mobile'])
                ->setEmail($_POST['email'])
                ->setSlack($_POST['slack'])
                ->setIp($_POST['ip'])
                ->setStatus($_POST['status'])
                ->setIdU($_POST['id_user_type']);

            $entityManager->flush($user);

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien été modifié';

            header('Location: ' . $this->lurl . '/users');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $user = $entityManager->getRepository(Users::class)->find($this->params[1]);
            $user->setStatus($this->params[2] == Users::STATUS_ONLINE ? Users::STATUS_OFFLINE : Users::STATUS_ONLINE);

            $entityManager->flush($user);

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'Le statut de l\'utilisateur a bien été modifié';

            header('Location: ' . $this->lurl . '/users');
            die;
        }

        $this->users  = [
            Users::STATUS_ONLINE  => $this->users->select('id_user NOT IN (' . Users::USER_ID_FRONT . ', ' . Users::USER_ID_CRON . ', ' . Users::USER_ID_WEBSERVICE . ',  1) AND status = ' . Users::STATUS_ONLINE, 'name ASC, firstname ASC'),
            Users::STATUS_OFFLINE => $this->users->select('id_user NOT IN (' . Users::USER_ID_FRONT . ', ' . Users::USER_ID_CRON . ', ' . Users::USER_ID_WEBSERVICE . ',  1) AND status = ' . Users::STATUS_OFFLINE, 'name ASC, firstname ASC')
        ];
    }

    public function _edit()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');

        /** @var EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $this->userTypes = $entityManager->getRepository(UsersTypes::class)->findAll();
    }

    public function _edit_perso()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');
    }

    public function _add()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        /** @var EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $this->userTypes = $entityManager->getRepository(UsersTypes::class)->findAll();
    }

    public function _edit_password()
    {
        $this->users->checkAccess();

        $template = [];

        if (isset($_POST['form_edit_pass_user'], $_SESSION['user']['id_user']) && $this->users->get($_SESSION['user']['id_user'])) {
            /** @var \previous_passwords $previousPasswords */
            $previousPasswords = $this->loadData('previous_passwords');

            if (empty($_POST['old_pass']) || empty($_POST['new_pass']) || empty($_POST['new_pass2'])) {
                $template['error'] = "Tous les champs sont obligatoires";
            } elseif ($this->users->password != md5($_POST['old_pass']) && $this->users->password != password_verify($_POST['old_pass'], $this->users->password)) {
                $template['error'] = "L'ancien mot de passe ne correspond pas";
            } elseif (false === $this->users->checkPasswordStrength($_POST['new_pass'])) {
                $template['error'] = "Le mot de passe doit contenir au moins 10 caractères, ainsi qu'au moins 1 chiffre et 1 caractère spécial";
            } elseif ($_POST['new_pass'] != $_POST['new_pass2']) {
                $template['error'] = "La confirmation du nouveau de passe doit être la même que votre nouveau mot de passe";
            } elseif (false === $previousPasswords->isValidPassword($_POST['new_pass'], $this->users->id_user)) {
                $template['error'] = "Ce mot de passe a déja été utilisé";
            } else {
                // @todo migrate to Doctrine
                $oldPassword                  = $this->users->password;
                $this->users->password        = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
                $this->users->password_edited = date('Y-m-d H:i:s');
                $this->users->update();

                $_SESSION['user']['password']        = $this->users->password;
                $_SESSION['user']['password_edited'] = $this->users->password_edited;

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendAdminPasswordModificationEmail($this->users);

                $previousPasswords->id_user  = $this->users->id_user;
                $previousPasswords->password = $oldPassword;
                $previousPasswords->archived = date('Y-m-d H:i:s');
                $previousPasswords->create();
                $previousPasswords->deleteOldPasswords($this->users->id_user);

                $_SESSION['notification']['title']   = 'Modification de votre mot de passe';
                $_SESSION['notification']['message'] = 'Votre mot de passe a bien été modifié';

                header('Location: ' . $this->lurl);
                die;
            }
        }

        $this->render(null, $template);
    }

    public function _logs()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        /** @var EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $this->loginLogs = $entityManager->getRepository(LoginConnectionAdmin::class)->findBy([], ['added' => 'DESC'], 500);
    }

    public function _generate_new_password()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        if (isset($this->params[0]) && $this->users->get($this->params[0], 'id_user')) {
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
