<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
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
<div id="contener">
    <style>
        .edit_pass {
            width: 50% !important;
        }
        table.edit_pass td {
            text-align: left;
        }
        .large {
            width: 41% !important;
        }
        #contenu {
            margin-top: 90px;
        }
    </style>
    <script type="text/javascript">
        $(function() {
            <?php if (false === empty($_SESSION['msgErreur'])) : ?>
                $.fn.colorbox({
                    href: "<?= $this->lurl ?>/thickbox/<?= $_SESSION['msgErreur'] ?>"
                });
            <?php endif; ?>

            $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

            <?php if (false === empty($this->nb_lignes)) : ?>
                $(".tablesorter").tablesorterPager({
                    container: $("#pager"),
                    positionFixed: false,
                    size: <?= $this->nb_lignes ?>});
            <?php endif; ?>

            <?php if (isset($_SESSION['freeow'])) : ?>
                var title = "<?= $_SESSION['freeow']['title'] ?>",
                    message = "<?= $_SESSION['freeow']['message'] ?>",
                    opts = {classes: ['smokey']};

                $('#freeow-tr').freeow(title, message, opts);
                <?php unset($_SESSION['freeow']); ?>
            <?php endif; ?>
        });
    </script>
    <div id="logo_site">
        <a href="<?= $this->lurl ?>" title="Administration du site">
            <img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Administration du site"/>
        </a>
    </div>
    <div id="freeow-tr" class="freeow freeow-top-right"></div>
    <div id="contenu">
        <center>
            <form method="post" name="edit_password" id="edit_password" enctype="multipart/form-data">
                <br/>
                <h1>Modification de votre mot de passe</h1>

                <h3 style="color:orange;">Votre mot de passe à expiré, vous devez le mettre à jour afin de conserver un niveau de sécurité optimal</h3>
                <br/>
                <i>Votre mot de passe doit contenir au minimum 10 caractères.
                    <br/>Au moins 1 chiffre et 1 caractère spécial.</i>
                <br/>
                <?php if (false === empty($this->retour_pass)) : ?>
                    <br/>
                    <div style="color:red; font-weight:bold;"><?= $this->retour_pass ?></div>
                <?php endif; ?>
                <br/><br/>
                <table class="large edit_pass">
                    <tr>
                        <th><label for="old_pass">Ancien mot de passe* :</label></th>
                        <td>
                            <input type="password" name="old_pass" id="old_pass" value="" autocomplete="off" class="input_large"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="new_pass">Nouveau mot de passe* :</label></th>
                        <td>
                            <input type="password" name="new_pass" id="new_pass" value="" onKeyUp="check_force_pass();" autocomplete="off" class="input_large"/>
                            <div id="indicateur_force"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="new_pass2">Vérification du nouveau mot de passe* :</label></th>
                        <td>
                            <input type="password" name="new_pass2" id="new_pass2" value="" autocomplete="off" class="input_large"/>
                        </td>
                    </tr>
                </table>
                <br/>
                <table class="large">
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="form_edit_pass_user" id="form_edit_pass_user"/>
                            <input type="hidden" name="id_user" value="<?= $this->users->id_user ?>"/>
                            <input type="submit" value="Valider la modification du mot de passe" class="btn button_valid"/>
                        </td>
                    </tr>
                </table>
            </form>
        </center>
    </div>
</div>
</body>
</html>
