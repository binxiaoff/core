<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Administration <?= $this->cms ?></title>
    <link rel="shortcut icon" href="<?= $this->surl ?>/images/admin/favicon.png" type="image/x-icon"/>
    <script type="text/javascript">
        var add_surl = '<?= $this->surl ?>';
        var add_url = '<?= $this->lurl ?>';
    </script>
    <?= $this->callCss() ?>
    <?= $this->callJs() ?>
</head>
<body class="loginBody">
<div id="contener">
    <script type="text/javascript">
        $(document).ready(function () {
            <?php if(isset($_SESSION['msgErreur']) && $_SESSION['msgErreur'] != '') : ?>
            $.fn.colorbox({
                href: "<?=$this->lurl?>/thickbox/<?=$_SESSION['msgErreur']?>"
            });
            <?php endif; ?>
        });
    </script>
    <?php if (isset($_SESSION['login_user']) && ($_SESSION['login_user']['nb_tentatives_precedentes'] > 1 && $_SESSION['login_user']['nb_tentatives_precedentes'] <= 5)) : ?>
        <script type="text/javascript">
            $(document).ready(function () {

                $('#bloc_action').hide();
                $('#load_bloc').show();

                setTimeout(function () {
                    $('#load_bloc').hide();
                    $('#bloc_action').show();
                }, <?= ($_SESSION['login_user']['duree_waiting'] * 1000) ?>);
            });
        </script>
    <?php endif; ?>
    <div id="logo_site">
        <a href="<?= $this->lurl ?>" title="<?= $this->cms ?>"><img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Unilend"/></a>
    </div>
    <div id="contenu_login">
        <form method="post" name="connexion_admin" id="connexion_admin" enctype="multipart/form-data">
            <fieldset>
                <h1>Connexion à l'administration</h1>
                <?php if (isset($this->error_login) && $this->error_login != "") : ?>
                    <h2 style="margin-bottom:20px;" class="notice_error"><?= $this->error_login ?></h2>
                <?php endif; ?>

                <?php if (isset($this->displayCaptchaError) && $this->displayCaptchaError != "") : ?>
                    <h2 style="margin-bottom:20px;" class="notice_error"><?= $this->displayCaptchaError ?></h2>
                <?php endif; ?>

                <?php if (isset($this->type_retour_new_pass) && $this->retour_new_pass != "") : ?>
                    <h2 style="margin-bottom:20px; color:<?= ($this->type_retour_new_pass == 0 ? 'red' : 'green') ?>;"><?= $this->retour_new_pass ?></h2>
                <?php endif; ?>
                <table class="login">
                    <tr>
                        <td><label for="login">Adresse Email</label></td>
                        <td><input autocomplete="off" type="text" name="login" id="login" class="input_login"/></td>
                    </tr>
                    <tr>
                        <td><label for="password">Mot de passe</label></td>
                        <td>
                            <input autocomplete="off" type="password" name="password" id="password" class="input_login"/>
                        </td>
                    </tr>

                    <?php if (isset($_SESSION['login_user']) && $_SESSION['login_user']['nb_tentatives_precedentes'] > 5) : ?>
                        <tr>
                            <td>
                                <img onclick="reloadCaptcha();" class="reloadCaptcha" src="<?= $this->surl ?>/images/default/Captcha-icon-reload.gif" alt="Reload captcha"/>
                                <img id="phoca-captcha" src="<?= $this->url ?>/captcha/<?= time() ?>.jpg" alt="captcha"/>
                            </td>
                            <td><input type="text" name="captcha" id="captcha" class="input_login"/></td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td colspan="2" class="center">
                            <?php if (isset($_SESSION['login_user']) && ($_SESSION['login_user']['nb_tentatives_precedentes'] > 1 && $_SESSION['login_user']['nb_tentatives_precedentes'] <= 5)): ?>
                                <div id="load_bloc">
                                    <img src="<?= $this->surl ?>/images/default/loading.gif" alt="loading...">
                                    <h2 style="margin-bottom:20px;" class="notice_error"><?= 'Par mesure de sécurité vous devez attendre ' . $_SESSION['login_user']['duree_waiting'] . ' secondes' ?></h2>
                                </div>
                            <?php endif ; ?>

                            <div id="bloc_action">
                                <input type="submit" value="Se connecter" title="Se connecter" name="connect" id="connect" class="btn"/>
                            </div>
                            <?php if (isset($_SESSION['login_user']) && ($_SESSION['login_user']['nb_tentatives_precedentes'] > 1 && $_SESSION['login_user']['nb_tentatives_precedentes'] <= 5)) : ?>
                                <script type="text/javascript">
                                    $("input").keypress(function (event) {
                                        if (event.keyCode == 13) {
                                            event.preventDefault();
                                        }
                                    });
                                </script>
                            <?php elseif (isset($_SESSION['login_user']) && $_SESSION['login_user']['nb_tentatives_precedentes'] > 5) : ?>
                                <script type="text/javascript">
                                    function reloadCaptcha() {
                                        now = new Date();
                                        var capObj = document.getElementById('phoca-captcha');
                                        if (capObj) {
                                            capObj.src = capObj.src + (capObj.src.indexOf('?') > -1 ? '&' : '?') + Math.ceil(Math.random() * (now.getTime()));
                                        }
                                    }
                                </script>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <div id="footer_login"><?= $this->cms ?> 1.1 -
        <a href="http://www.equinoa.com" title="Agence Web Equinoa">Equinoa</a> &copy;<?= date('Y') ?></div>
</div>
</body>
</html>