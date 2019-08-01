<?php

use Doctrine\ORM\EntityManager;
use Unilend\Entity\{LoginConnectionAdmin, Users, UsersTypes, Zones};

class usersController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        $this->menu_admin        = 'admin';
        $this->users_zones       = $this->loadData('users_zones');
        $this->users_types_zones = $this->loadData('users_types_zones');
    }

    public function _default()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($_POST['form_add_users'])) {
            if (false === isset($_POST['email'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Veuillez entrer une adresse e-mail';

                header('Location: ' . $this->url . '/users');
                die;
            } elseif (false === $this->ficelle->isEmail($_POST['email'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Veuillez entrer une adresse e-mail valide';

                header('Location: ' . $this->url . '/users');
                die;
            } elseif ($this->users->select('email = "' . $_POST['email'] . '"')) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Cet utilisateur existe déjà';

                header('Location: ' . $this->url . '/users');
                die;
            }

            $userType = $entityManager->getRepository(UsersTypes::class)->find($_POST['id_user_type']);

            if (null === $userType) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Type d\'utilisateur non renseigné';

                header('Location: ' . $this->url . '/users');
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

            /** @var \Unilend\Service\UnilendMailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($user, $newPassword);

            $_SESSION['freeow']['title']   = 'Ajout d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien été ajouté';

            header('Location: ' . $this->url . '/zones');
            die;
        }

        if (isset($_POST['form_mod_users'])) {
            /** @var Users $user */
            $user = $entityManager->getRepository(Users::class)->find($this->params[0]);

            if (null === $user) {
                $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
                $_SESSION['freeow']['message'] = 'Utilisateur inconnu';

                header('Location: ' . $this->url . '/users');
                die;
            }

            $userType = $entityManager->getRepository(UsersTypes::class)->find($_POST['id_user_type']);

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
            ;

            $entityManager->flush($user);

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'L\'utilisateur a bien été modifié';

            header('Location: ' . $this->url . '/users');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $user = $entityManager->getRepository(Users::class)->find($this->params[1]);
            $user->setStatus($this->params[2] == Users::STATUS_ONLINE ? Users::STATUS_OFFLINE : Users::STATUS_ONLINE);

            $entityManager->flush($user);

            $_SESSION['freeow']['title']   = 'Modification d\'un utilisateur';
            $_SESSION['freeow']['message'] = 'Le statut de l\'utilisateur a bien été modifié';

            header('Location: ' . $this->url . '/users');
            die;
        }

        $this->users  = [
            Users::STATUS_ONLINE  => $this->users->select('id_user NOT IN (' . Users::USER_ID_FRONT . ', ' . Users::USER_ID_CRON . ', ' . Users::USER_ID_WEBSERVICE . ',  1) AND status = ' . Users::STATUS_ONLINE, 'name ASC, firstname ASC'),
            Users::STATUS_OFFLINE => $this->users->select('id_user NOT IN (' . Users::USER_ID_FRONT . ', ' . Users::USER_ID_CRON . ', ' . Users::USER_ID_WEBSERVICE . ',  1) AND status = ' . Users::STATUS_OFFLINE, 'name ASC, firstname ASC')
        ];
    }

    public function _edit()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');

        /** @var EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $this->userTypes = $entityManager->getRepository(UsersTypes::class)->findAll();
    }

    public function _edit_perso()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->users->get($this->params[0], 'id_user');
    }

    public function _add()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        /** @var EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $this->userTypes = $entityManager->getRepository(UsersTypes::class)->findAll();
    }

    public function _logs()
    {
        /** @var EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $this->loginLogs = $entityManager->getRepository(LoginConnectionAdmin::class)->findBy([], ['added' => 'DESC'], 500);
    }

    public function _generate_new_password()
    {
        if (isset($this->params[0]) && $this->users->get($this->params[0], 'id_user')) {
            $newPassword = $this->ficelle->generatePassword(10);
            $this->users->changePassword($newPassword, $this->users, true);

            /** @var \Unilend\Service\UnilendMailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendNewPasswordEmail($this->users, $newPassword);
        }
        $_SESSION['freeow']['title']   = 'Modification du mot de passe';
        $_SESSION['freeow']['message'] = 'Le mot de passe a bien &eacute;t&eacute; modifi&eacute; !';

        header('Location: ' . $this->url . '/users');
        die;
    }

    /* Activer un utilisateur sur une zone */
    public function _activeUserZone()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        if (false === empty(isset($this->params[0]))) {
            $this->users_zones->get($this->params[0], 'id_zone = ' . $this->params[1] . ' AND id_user');

            if ($this->users_zones->id) {
                $this->users_zones->delete($this->users_zones->id);

                echo $this->url . '/images/check_off.png';
            } else {
                $this->users_zones->id_user = $this->params[0];
                $this->users_zones->id_zone = $this->params[1];
                $this->users_zones->create();

                echo $this->url . '/images/check_on.png';
            }
        }
    }
}
