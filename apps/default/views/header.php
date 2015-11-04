<?php if (isset($_SESSION['lexpress'])) { ?>
    <iframe name="lexpress" SRC="<?= $_SESSION['lexpress']['header'] ?>" scrolling="no" height="138px" width="100%" FRAMEBORDER="no"></iframe>
<? } ?>
<div class="wrapper">
<?php
    if (
        $this->lurl == 'http://prets-entreprises-unilend.capital.fr'
        || $this->lurl == 'http://partenaire.unilend.challenges.fr'
        || $this->lurl == 'http://financementparticipatifpme.lefigaro.fr'
        || $this->lurl == 'http://financementparticipatifpme.lefigaro.fr'
    ) {
        ?>
        <style type="text/css">
            .header, .footer {display: none;}
            .main {padding-bottom: 10px;}
        </style>
<?php } ?>
    <div class="header">
        <div class="shell clearfix">
            <div class="logo">
                <a href="<?= $this->lurl ?>">Unilend</a>
            </div>
            <!-- /.logo -->
            <div class="toggle-buttons">
                <div class="nav-toggle"></div>
                <!-- /.nav-toggle -->
                <div class="login-toggle"></div>
                <!-- /.login-toggle -->
                <div class="search-toggle"></div>
                <!-- /.search-toggle -->
            </div>
            <!-- /.toggle-buttons -->
            <?php
            if ($this->clients->checkAccess()) {
                $this->fireView('../blocs/header-account');
            } else {
                ?>
                <div class="login-panel" style="width:525px;">
                    <div class="login-toggle"></div>
                    <form action="" method="post" id="form_connect" name="form_connect">
                        <div style="height:30px;" class="error_login_mobile">
                            <?php
                            // on lance le captcha
                            if ($_SESSION['login']['nb_tentatives_precedentes'] > 5 && ! isset($_POST['project_detail'])) {
                                ?>
                                <input type="text" name="captcha" class="field field-mini input_captcha_login" id="captcha" value="captcha" title="captcha">
                                <div class="content_captcha_login">
                                    <img class="captcha_login" src="<?= $this->surl ?>/images/default/securitecode.php" alt="captcha"/>
                                </div>
                                <img class="reload_captcha_login" src="<?= $this->surl ?>/images/default/icon-reload.gif" alt="Reload captcha"/>
                                <script type="text/javascript">
                                    $(".reload_captcha_login").click(function () {
                                        $.post(add_url + "/ajax/captcha_login").done(function (data) {
                                            $('.content_captcha_login').html(data);
                                        });
                                    });
                                </script>
                                <?php
                            } // on lance le message d'attente
                            elseif ($_SESSION['login']['nb_tentatives_precedentes'] > 1 && ! isset($_POST['project_detail'])) {
                                echo '<p class="error_login error_wait" style="display:block;">' . $this->lng['header']['vous-devez-attendre'] . ' ' . $_SESSION['login']['duree_waiting'] . ' ' . $this->lng['header']['secondes-avant-de-pourvoir-vous-connecter'] . '</p>';
                            } // message d'erreur
                            elseif ($_SESSION['login']['nb_tentatives_precedentes'] <= 1 && ! isset($_POST['project_detail'])) {
                                echo '<p class="error_login">' . $this->error_login . '</p>';
                            }
                            ?>
                        </div>
                        <span class="headConnect"><?= $this->lng['header']['se-connecter'] ?></span>
                        <input type="text" name="login" value="<?= $this->lng['header']['identifiant'] ?>" title="<?= $this->lng['header']['identifiant'] ?>" class="field field-tiny" style="width:129px;">
                        <span class="pass-field-holder">
                            <input type="password" name="password" title="<?= $this->lng['header']['mot-de-passe'] ?>" class="field field-tiny">
                        </span>

                        <div style="width:48px;display:inline-block;">
                            <div class="btn_login" <?= ($_SESSION['login']['nb_tentatives_precedentes'] > 1 && $_SESSION['login']['nb_tentatives_precedentes'] <= 5 ? 'style="display:none;"' : '') ?>>
                                <button type="submit" name="connect" class="btn btn-mini btn-warning"><?= $this->lng['header']['ok'] ?></button>
                            </div>
                            <div class="error_wait" <?= ($_SESSION['login']['nb_tentatives_precedentes'] > 1 && $_SESSION['login']['nb_tentatives_precedentes'] <= 5 ? 'style="display:block;"' : '') ?>>
                                <img src="<?= $this->surl ?>/styles/default/images/loading.gif" alt="loading...">
                            </div>
                        </div>
                    </form>
                    <?php
                    // On desactive la validation par la touche enter
                    if ($_SESSION['login']['nb_tentatives_precedentes'] > 1 && $_SESSION['login']['nb_tentatives_precedentes'] <= 5) {
                        ?>
                        <script type="text/javascript">
                            $("input").keypress(function (event) {
                                if (event.keyCode == 13) {
                                    event.preventDefault();
                                }
                            });
                        </script>
                        <?php
                    }
                    ?>
                    <div style="clear:both;"></div>
                    <a class="popup-link lienHeader" style="margin-right:65px;" href="<?= $this->lurl ?>/thickbox/pop_up_mdp"><?= $this->lng['header']['mot-de-passe-oublie'] ?></a>
                    <a class="lienHeader" style="margin-right:75px;" href="<?= $this->lurl . '/' . $this->tree->getSlug(127, $this->language) ?>"><?= $this->lng['header']['se-creer-un-compte'] ?></a>
                </div><!-- /.login-panel -->
                <?php
            }
            ?>
            <div class="navigation">
                <div class="shell clearfix">
                    <div class="nav-toggle"></div>
                    <!-- /.nav-toggle -->
                    <ul>
                        <li class="active nav-item-home" style="margin-top:15px;">
                            <a href="<?= $this->lurl ?>"><i class="icon-home"></i></a></li>
                        <?php
                        foreach ($this->tree->getNavigation(1, $this->language) as $key => $n) {
                            ?>
                            <li><?php

                            $sNav = $this->tree->getNavigation($n['id_tree'], $this->language);
                            if ($sNav != false && $n['id_template'] != 2) {
                                ?>
                                <ul><?php
                                foreach ($sNav as $key => $sn) {
                                    ?>
                                    <li>
                                    <a <?= ($this->tree->id_tree == $sn['id_tree'] ? 'class="active"' : '') ?> href="<?= $this->lurl . '/' . $sn['slug'] ?>"><?= $sn['title'] ?></a>
                                    </li><?php
                                }
                                ?></ul><?php
                            }

                            ?>
                            <a <?= ($this->tree->id_tree == $n['id_tree'] || $this->tree->id_parent == $n['id_tree'] || isset($this->navigateurActive) && $this->navigateurActive == $n['id_tree'] ? 'class="active"' : '') ?> href="<?= $this->lurl . '/' . $n['slug'] ?>"><?= $n['title'] ?></a>
                            </li><?php
                        }
                        ?>
                    </ul>
                    <!-- /.nav-main -->
                    <div class="search">
                        <form action="<?= $this->lurl ?>/search" method="post">
                            <input type="text" name="search" value="<?= $this->lng['header']['recherche'] ?>" title="<?= $this->lng['header']['recherche'] ?>" class="field field-mini">
                            <button type="submit" class="icon-search"></button>
                        </form>
                    </div>
                    <!-- /.search -->
                </div>
                <!-- /.shell -->
            </div>
            <!-- /.navigation -->
            <div class="search mobile-search">
                <form action="<?= $this->lurl ?>/search" method="post">
                    <input type="text" name="search" value="<?= $this->lng['header']['recherche'] ?>" title="<?= $this->lng['header']['recherche'] ?>" class="field field-mini">
                    <button type="submit" class="icon-search"></button>
                </form>
            </div>
        </div>
        <!-- /.shell -->
    </div>
    <!-- /.header -->
