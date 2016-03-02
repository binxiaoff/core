<div class="sidebar right">
    <aside class="widget widget-price">
        <div class="widget-top">
            <i class="icon-pig"></i>
            <?= $this->ficelle->formatNumber($this->projects->amount, 0) ?> €
        </div>
        <div class="widget-body">
            <div class="widget-cat progress-cat clearfix">
                <div class="prices clearfix">
                    <span class="price less">
                        <strong><?= $this->ficelle->formatNumber($this->payer, $this->decimales) ?> €</strong>
                        <?= $this->lng['preteur-projets']['de-pretes'] ?>
                    </span>
                    <i class="icon-arrow-gt"></i>
                    <?php if ($this->soldeBid >= $this->projects->amount) { ?>
                        <p style="font-size:14px;"><?= $this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a'] ?> <?= $this->ficelle->formatNumber($this->txLenderMax, 1) ?>%</p>
                    <?php } else { ?>
                        <span class="price">
                            <strong><?= $this->ficelle->formatNumber($this->resteApayer, $this->decimales) ?> €</strong>
                            <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                        </span>
                    <?php } ?>
                </div>
                <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                    <div><span></span></div>
                </div>
            </div>
            <?php if (false === in_array($this->lurl, array('http://prets-entreprises-unilend.capital.fr', $this->lurl == 'http://partenaire.unilend.challenges.fr'))) { ?>
                <div class="widget-cat" style="padding-top:25px;">
                    <i class="plusmoins" id="close-seconnecter"></i>
                    <div class="seconnecter" <?= (isset($_POST['project_detail']) ? 'style="display:block"' : '') ?> >
                        <div style="display:none" class="seconnecteropen"><?= (isset($_POST['project_detail']) ? 'true' : 'false') ?></div>
                        <form target="_parent" method="post" action="<?= $this->url_form ?>/projects/detail/<?= $this->params[0] ?>" name="projectseconnecter" id="projectseconnecter">
                            <div class="row">
                                <input class="field field-medium" type="text" placeholder="<?= $this->lng['header']['identifiant'] ?>" name="login" autocomplete="off">
                            </div>
                            <div class="row">
                                <span class="pass-field-holder">
                                    <input class="field field-medium" type="password" placeholder="<?= $this->lng['header']['mot-de-passe'] ?>" name="password" autocomplete="off">
                                </span>
                                <a class="popup-link mdpoublie" href="<?= $this->lurl ?>/thickbox/pop_up_mdp"><?= $this->lng['header']['mot-de-passe-oublie'] ?></a>
                            </div>
                            <?php if (isset($_POST['project_detail'], $_SESSION['login']) && $_SESSION['login']['nb_tentatives_precedentes'] > 10) { ?>
                                <div class="row">
                                    <input type="text" name="captcha" class="field field-mini input_captcha_login" id="captcha" value="captcha">
                                    <div class="content_captcha_login" style="float:left;width:126px;">
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
                                </div>
                            <?php } ?>
                            <input type="hidden" name="connect"/>
                            <input type="hidden" name="project_detail" value="projects/detail/<?= $this->params[0] ?>"/>
                        </form>
                    </div>
                    <div style="clear:both;"></div>
                    <a target="_parent" class="btn" id="seconnecter" style="width:210px; display:block;margin:auto;"><?= $this->lng['preteur-projets']['se-connecter'] ?></a>
                    <?php if (isset($_POST['project_detail'], $_SESSION['login']['nb_tentatives_precedentes']) && $_SESSION['login']['nb_tentatives_precedentes'] <= 10 && $_SESSION['login']['nb_tentatives_precedentes'] > 1) { ?>
                        <p class="error_login error_wait" style="display:block; text-align:center;">
                            <?= $this->lng['header']['vous-devez-attendre'] ?>
                            <?= $_SESSION['login']['duree_waiting'] ?>
                            <?= $this->lng['header']['secondes-avant-de-pourvoir-vous-connecter'] ?>
                        </p>
                        <script type="text/javascript">
                            $(".seconnecteropen").html('blocked');
                            setTimeout(function () {
                                $(".seconnecteropen").html('true');
                            }, <?= ($_SESSION['login']['duree_waiting'] * 1000) ?>);
                        </script>
                    <?php } elseif (isset($_POST['project_detail'], $_SESSION['login']['nb_tentatives_precedentes']) && $_SESSION['login']['nb_tentatives_precedentes'] <= 1) { ?>
                        <p class="error_login" style="text-align:center;"><?= $this->error_login ?></p>
                    <?php } ?>

                    <script type="text/javascript">
                        $("#seconnecter").click(function () {
                            if ($(".seconnecteropen").html() == 'blocked') {
                            } else if ($(".seconnecteropen").html() == 'false') {
                                $(".seconnecter").slideDown();
                                $(".seconnecteropen").html('true');
                                $("#close-seconnecter").css('background-position', 'right');
                            } else {
                                $("#projectseconnecter").submit();
                            }
                        });

                        $("#close-seconnecter").click(function () {
                            if ($(".seconnecteropen").html() == 'true') {
                                $(".seconnecter").slideUp();
                                $(".seconnecteropen").html('false');
                                $(this).css('background-position', 'left')
                            } else {
                                $(".seconnecter").slideDown();
                                $(".seconnecteropen").html('true');
                                $(this).css('background-position', 'right');
                            }
                        });
                    </script>
                </div>
            <?php } ?>
            <div class="widget-cat" style="padding-top:25px;">
                <i class="plusmoins" id="close-sinscrire"></i>
                <div class="sinscrire"<?= (empty($this->retour_form) ? '' : 'style="display:block;"') ?> >
                    <form target="_parent" method="post" action="<?= $this->url_form ?>/projects/detail/<?= $this->params[0] ?><?= $this->utm_source ?>" name="projectsinscrire" id="projectsinscrire">
                        <div style="display:none" class="sinscrireopen"><?= (empty($this->retour_form) ? 'false' : 'true') ?></div>
                        <div class="row">
                            <input class="field field-medium required" type="text" value="<?= (isset($_POST['nom']) ? $_POST['nom'] : '') ?>" placeholder="<?= $this->lng['landing-page']['nom'] ?>" name="nom" id="signup-first-name" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                        </div>
                        <div class="row">
                            <input class="field field-medium required" type="text" value="<?= (isset($_POST['prenom']) ? $_POST['prenom'] : '') ?>" placeholder="<?= $this->lng['landing-page']['prenom'] ?>" name="prenom" id="signup-last-name" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                        </div>
                        <div class="row">
                            <input class="field field-medium required" type="text" value="<?= (isset($_POST['email']) ? $_POST['email'] : '') ?>" placeholder="<?= $this->lng['landing-page']['email'] ?>" name="email" id="email" data-validators="Presence&amp;Email" oncopy="return false;" oncut="return false;" onkeyup="checkConf(this.value,'conf_email')">
                        </div>
                        <div class="row">
                            <input class="field field-medium required" type="text" value="<?= (isset($_POST['email-confirm']) ? $_POST['email-confirm'] : '') ?>" placeholder="<?= $this->lng['landing-page']['confirmation-email'] ?>" name="conf_email" id="conf_email" data-validators="Confirmation,{ match: 'email' }" onpast="return false;">
                        </div>
                        <input type="hidden" name="send_inscription_project_detail"/>
                    </form>
                </div>
                <a target="_parent" class="btn sinscrire_cta" id="sinscrire" style=""><?= $this->lng['preteur-projets']['sinscrire'] ?></a>
                <?php if (false === empty($this->retour_form)) : ?>
                    <p class="error_login" style="text-align:center;display:inline;"><?= $this->retour_form ?></p>
                <?php endif; ?>
                <script type="text/javascript">
                    $("#sinscrire").click(function () {
                        if ($(".sinscrireopen").html() == 'false') {
                            $(".sinscrire").slideDown();
                            $(".sinscrireopen").html('true');
                            Form.initialise({selector: 'form'});
                            $("#close-sinscrire").css('background-position', 'right');
                        } else {
                            $("#projectsinscrire").submit();
                        }
                    });

                    $("#close-sinscrire").click(function () {
                        if ($(".sinscrireopen").html() == 'true') {
                            $(".sinscrire").slideUp();
                            $(".sinscrireopen").html('false');
                            $(this).css('background-position', 'left');
                        } else {
                            $(".sinscrire").slideDown();
                            $(".sinscrireopen").html('true');
                            $(this).css('background-position', 'right');
                        }
                    });
                </script>
            </div>
        </div>
    </aside>
</div>
