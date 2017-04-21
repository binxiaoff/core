<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Administration du site</title>
    <link rel="shortcut icon" href="<?= $this->surl ?>/images/admin/favicon.png" type="image/x-icon"/>
    <script type="text/javascript">
        var add_surl = '<?= $this->surl ?>';
        var add_url = '<?= $this->lurl ?>';
    </script>
    <?= $this->callCss() ?>
    <?= $this->callJs() ?>
</head>
<body class="loginBody">
<div id="contener" class="container">
    <script type="text/javascript">
        $(function() {
            <?php if (false === empty($_SESSION['msgErreur'])) : ?>
                $.fn.colorbox({
                    href: "<?= $this->lurl ?>/thickbox/<?= $_SESSION['msgErreur'] ?>"
                });
            <?php endif; ?>

            <?php if (isset($_SESSION['login_user']) && ($_SESSION['login_user']['nb_tentatives_precedentes'] > 1 && $_SESSION['login_user']['nb_tentatives_precedentes'] <= 5)) : ?>
                $('#bloc_action').hide();
                $('#load_bloc').show();

                setTimeout(function () {
                        $('#load_bloc').hide();
                        $('#bloc_action').show();
                    }, <?= ($_SESSION['login_user']['duree_waiting'] * 1000) ?>
                );
            <?php endif; ?>
        });
    </script>
    <div id="logo_site">
        <a href="<?= $this->lurl ?>" title="Administration du site">
            <img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Administration du site"/>
        </a>
    </div>
    <div id="contenu_login">
        <form method="post" name="connexion_admin" id="connexion_admin" enctype="multipart/form-data">
            <fieldset>
                <h1>Connexion à l'administration</h1>
                <?php if (false === empty($this->error_login)) : ?>
                    <h2 style="margin-bottom:20px;" class="notice_error"><?= $this->error_login ?></h2>
                <?php endif; ?>
                <?php if (false === empty($this->type_retour_new_pass)) : ?>
                    <h2 style="margin-bottom:20px; color:<?= ($this->type_retour_new_pass == 0 ? 'red' : 'green') ?>;"><?= $this->retour_new_pass ?></h2>
                <?php endif; ?>
                <table class="login">
                    <tr>
                        <td><label for="login">Adresse Email</label></td>
                        <td><input type="text" name="login" id="login" autocomplete="off" autofocus class="input_login"/></td>
                    </tr>
                    <tr>
                        <td><label for="password">Mot de passe</label></td>
                        <td>
                            <input type="password" name="password" id="password" autocomplete="off" class="input_login"/>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" class="center">
                            <div id="bloc_action">
                                <button type="submit" id="connect" class="btn-primary">Se connecter</button>
                            </div>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
</body>
</html>
