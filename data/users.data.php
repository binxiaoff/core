<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //
class users extends users_crud
{
    const USER_ID_CRON  = -1;
    const USER_ID_FRONT = -2;

    public function __construct($bdd, $params = '')
    {
        parent::users($bdd, $params);
    }

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

    public function handleLogin($button, $email, $pass)
    {
        if (isset($_POST[$button])) {
            $user = $this->login($_POST[$email], $_POST[$pass]);

            if ($user != false) {
                $_SESSION['auth']  = true;
                $_SESSION['token'] = md5(md5(time() . $this->securityKey));
                $_SESSION['user']  = $user;

                $sql = 'UPDATE ' . $this->userTable . ' SET lastlogin = NOW() WHERE email = "' . $_POST[$email] . '" AND password = "' . md5($_POST[$pass]) . '"';
                $this->bdd->query($sql);

                if (isset($_SESSION['request_url']) && $_SESSION['request_url'] != '' && $_SESSION['request_url'] != 'login' && $_SESSION['request_url'] != 'captcha') {
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
    }

    public function handleLogout()
    {
        unset($_SESSION['auth']);
        unset($_SESSION['token']);
        unset($_SESSION['user']);
        unset($_SESSION['request_url']);

        header('Location: ' . $this->params['lurl'] . '/login');
    }

    public function login($email, $pass)
    {
        $email = $this->bdd->escape_string($email);

        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '" AND ' . $this->userPass . ' = "' . md5($pass) . '" AND status = 1';
        $res = $this->bdd->query($sql);

        if ($this->bdd->num_rows($res) == 1) {
            return $this->bdd->fetch_array($res);
        } else {
            return false;
        }
    }

    public function changePassword($email, $pass)
    {
        $sql = 'UPDATE ' . $this->userTable . ' SET ' . $this->userPass . ' = "' . md5($pass) . '" WHERE ' . $this->userMail . ' = "' . $email . '"';
        $this->bdd->query($sql);
    }

    public function existEmail($email)
    {
        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '"';
        $res = $this->bdd->query($sql);

        if ($this->bdd->num_rows($res) == 1) {
            return false;
        } else {
            return true;
        }
    }

    public function checkAccess($zone = '', $superAdmin = false)
    {
        if ($superAdmin) {
            if (! isset($_SESSION['user'])) {
                $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE id_user = 1';
                $res = $this->bdd->query($sql);

                if ($this->bdd->num_rows($res) == 1) {
                    $_SESSION['auth']  = true;
                    $_SESSION['token'] = md5(md5(time() . $this->securityKey));
                    $_SESSION['user']  = $this->bdd->fetch_array($res);
                } else {
                    $_SESSION['msgErreur'] = 'loginError';

                    header('Location: ' . $this->params['lurl'] . '/login');
                    die;
                }
            }
        } else {
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
            } else {
                if ($zone != '') {
                    $sql    = 'SELECT id_zone FROM zones WHERE slug = "' . $zone . '"';
                    $result = $this->bdd->query($sql);
                    $record = $this->bdd->fetch_array($result);

                    $id_zone = $record['id_zone'];

                    $sql    = 'SELECT * FROM users_zones WHERE id_user = ' . $_SESSION['user']['id_user'] . ' AND id_zone = "' . $id_zone . '"';
                    $result = $this->bdd->query($sql);
                    $nb     = $this->bdd->num_rows($result);

                    if ($nb == 1) {
                        return true;
                    } else {
                        $_SESSION['msgErreur'] = 'loginInterdit';

                        header('Location: ' . $this->params['lurl'] . '/login');
                        die;
                    }
                }
            }
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
}
