<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Users as UsersEntity;
use Unilend\core\Loader;

class users extends users_crud
{
    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `users`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `users` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_user')
    {
        $sql    = 'SELECT * FROM `users` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    //******************************************************************************************//
    //**************************************** AJOUTS ******************************************//
    //******************************************************************************************//

    var $userTable = 'users';
    var $securityKey = 'users';
    var $userMail = 'email';
    var $userPass = 'password';

    public function handleLogin($email, $password)
    {
        $user = $this->login($email, $password);

        if (false === $user) {
            $_SESSION['msgErreur'] = 'loginError';
            header('Location: ' . $this->params['lurl'] . '/login');
            die;
        }

        if ($user != false && $this->get($user['email'], 'email')) {
            $_SESSION['auth']  = true;
            $_SESSION['token'] = md5(md5(time() . $this->securityKey));
            $_SESSION['user']  = $user;

            if (md5($password) === $user['password'] || password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $user['password']             = password_hash($password, PASSWORD_DEFAULT);
                $_SESSION['user']['password'] = $user['password'];
                $this->password               = $user['password'];
            }

            $this->lastlogin = date('Y-m-d H:i:s');
            $this->update();
            $this->checkExpiredPassword();

            if (false === empty($_SESSION['request_url']) && false === in_array($_SESSION['request_url'], ['login'])) {
                header('Location: ' . $_SESSION['request_url']);
                die;
            } else {
                header('Location: ' . $this->params['lurl'] . '/');
                die;
            }
        } else {
            $_SESSION['msgErreur'] = 'loginError';
            header('Location: ' . $this->params['lurl'] . '/login');
            die;
        }
    }

    private function checkExpiredPassword()
    {
        $maxEditDate = new \DateTime('3 months ago');

        if ($maxEditDate->format('Y-m-d H:i:s') >= $this->password_edited) {
            $_SESSION['freeow']['title']   = 'Modification de votre mot de passe';
            $_SESSION['freeow']['message'] = 'Votre mot de passe doit être mis à jour afin de conserver un niveau de sécurité optimal!';

            header('Location: ' . $this->params['lurl'] . '/edit_password/');
            die;
        }
    }

    public function handleLogout()
    {
        unset($_SESSION['auth']);
        unset($_SESSION['token']);
        unset($_SESSION['user']);
        unset($_SESSION['request_url']);

        header('Location: ' . $this->params['lurl'] . '/login');
    }

    /**
     * @param string $email
     * @param string $pass
     *
     * @return bool|array
     */
    public function login($email, $pass)
    {
        $email = $this->bdd->escape_string($email);
        $sql   = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '" AND status = ' . UsersEntity::STATUS_ONLINE;
        $res   = $this->bdd->query($sql);

        if ($res->rowCount() === 1) {
            $user = $res->fetch(\PDO::FETCH_ASSOC);

            if (md5($pass) === $user['password'] || password_verify($pass, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function existEmail($email)
    {
        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '"';
        $res = $this->bdd->query($sql);

        return ($this->bdd->num_rows($res) >= 1);
    }

    public function checkAccess($zone = '')
    {
        if (false === isset($_SESSION['auth']) || $_SESSION['auth'] != true) {
            header('Location: ' . $this->params['lurl'] . '/login');
            die;
        }

        if (false === isset($_SESSION['token']) || trim($_SESSION['token']) == '') {
            header('Location: ' . $this->params['lurl'] . '/login');
            die;
        }

        $sql = 'SELECT COUNT(*) FROM ' . $this->userTable . ' WHERE id_user = "' . $_SESSION['user']['id_user'] . '" AND password = "' . $_SESSION['user']['password'] . '"';
        $res = $this->bdd->query($sql);

        if ($this->bdd->result($res, 0) != 1) {
            $_SESSION['msgErreur'] = 'loginError';

            header('Location: ' . $this->params['lurl'] . '/login');
            die;
        } elseif ($zone != '') {
            $sql    = 'SELECT id_zone FROM zones WHERE slug = "' . $zone . '"';
            $result = $this->bdd->query($sql);
            $record = $this->bdd->fetch_array($result);

            $id_zone = $record['id_zone'];

            $sql    = 'SELECT * FROM users_zones WHERE id_user = ' . $_SESSION['user']['id_user'] . ' AND id_zone = "' . $id_zone . '"';
            $result = $this->bdd->query($sql);
            $nb     = $this->bdd->num_rows($result);

            if ($nb == 1) {
                return true;
            }

            $userZones = new users_zones($this->bdd);
            $zones     = $userZones->selectZonesUser($_SESSION['user']['id_user']);

            if (false === empty($zones)) {
                header('Location: ' . $this->params['lurl'] . '/' . $zones[0]);
                die;
            }

            die('Aucun accès disponible, veuillez contacter l\'administrateur');
        }
    }

    public function getName($iUserId)
    {
        if ($iUserId == -1) {
            return 'Cron';
        } elseif ($iUserId == -2) {
            return 'Front office';
        } elseif ($this->get($iUserId)) {
            return trim($this->firstname . ' ' . $this->name);
        }
        return '';
    }

    /**
     * @param string $password
     * @param users $user
     * @param $bExpired
     */
    public function changePassword($password, \users $user, $bExpired)
    {
        /** @var \previous_passwords $previousPasswords */
        $previousPasswords = Loader::loadData('previous_passwords');

        $user->password        = password_hash($password, PASSWORD_DEFAULT);
        $user->password_edited = $bExpired ? '0000-00-00 00:00:00' : date('Y-m-d H:i:s');
        $user->update();

        $previousPasswords->id_user  = $user->id_user;
        $previousPasswords->password = $user->password;
        $previousPasswords->archived = date('Y-m-d H:i:s');
        $previousPasswords->create();
        $previousPasswords->deleteOldPasswords($user->id_user);
    }

    /**
     * Returns true if password contains at least 10 characters
     * including digits, lower, upper case and special characters
     * @param string $password
     * @return bool
     */
    public function checkPasswordStrength($password)
    {
        if (1 === preg_match('/(?=.*[A-Z])(?=.*[$&+,:;=?@#|\'<>.^_*()%!-])(?=.*[a-z])(?=.*\d).{10,}/', $password)) {
            return true;
        }
        return false;
    }

}
