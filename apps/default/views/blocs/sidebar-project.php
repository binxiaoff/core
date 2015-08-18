<?
// pour éviter de refaire tous les conditions une autre fois pour le mobile, on place une variable qui sera a true si le client peut prêter
$this->preter_by_mobile_ok = false;

// si on n'est pas connecté
if (!$this->clients->checkAccess())
{
    // project RA
    if ($this->projects_status->status == 130)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top" style="line-height: 1.43;"><?= $this->lng['preteur-projets']['projet-rembourse-a-100'] ?></div>
                <div class="widget-body">
                    <div class="article">
                        <p><?= $this->lng['preteur-projets']['ce-projet-a-ete-totalement-rembourse-le'] ?> <strong class="pinky-span"> <?= date('d/m/Y', strtotime($this->lastStatushisto['added'])) ?></strong></p>
                        <p><?= $this->lng['preteur-projets']['vous-lui-avez-prete'] ?> <strong class="pinky-span"><?= number_format($this->bidsvalid['solde'], 0, ',', ' ') ?> €</strong> <br /><?= $this->lng['preteur-projets']['au-taux-moyen-de'] ?> <strong class="pinky-span"><?= number_format($this->AvgLoansPreteur, 1, ',', ' ') ?> %</strong></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // project fundé
    elseif ($this->projects_status->status == 60 || $this->projects_status->status >= 80)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top">
                    <?= $this->lng['preteur-projets']['projet-finance-a-100'] ?>
                </div>
                <div class="widget-body">
                    <div class="article">
                        <p><?= $this->lng['preteur-projets']['ce-projet-est-integralement-finance-par'] ?> <strong class="pinky-span"> <?= number_format($this->NbPreteurs, 0, ',', ' ') ?> <?= $this->lng['preteur-projets']['preteur'] ?><?= ($this->NbPreteurs > 1 ? 's' : '') ?></strong> <br /><?= $this->lng['preteur-projets']['au-taux-de'] ?><strong class="pinky-span"> <?= number_format($this->AvgLoans, 1, ',', ' ') ?> %</strong> <br /><?= $this->lng['preteur-projets']['en'] ?> <?= ($this->interDebutFin['day'] > 0 ? $this->interDebutFin['day'] . ' jours ' : '') ?><?= ($this->interDebutFin['hour'] > 0 ? $this->interDebutFin['hour'] . ' heures ' : '') ?> <?= $this->lng['preteur-projets']['et'] ?> <?= $this->interDebutFin['minute'] ?> <?= $this->lng['preteur-projets']['minutes'] ?></p>
                        <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // Funding KO
    elseif ($this->projects_status->status == 70)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top" style="line-height: 36px;">
                    <?= $this->lng['preteur-projets']['projet-na-pas-pu-etre-finance-a-100'] ?>
                </div>
                <div class="widget-body">
                    <div class="article">
                        <p><?= $this->lng['preteur-projets']['ce-projet-a-ete-finance-a'] ?><?= number_format($this->pourcentage, $this->decimalesPourcentage, ',', '') ?>%</p>
                        <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // en funding
    else
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-price">
                <div class="widget-top">
                    <i class="icon-pig"></i>
                    <?= number_format($this->projects->amount, 0, ',', ' ') ?> €
                </div>
                <div class="widget-body">
                    <?php /* ?><div class="widget-cat progress-cat clearfix">
                      <div class="prices clearfix">
                      <span class="price less">
                      <strong><?=number_format($this->payer,$this->decimales, ',', ' ')?> €</strong>
                      <?=$this->lng['preteur-projets']['de-pretes']?>
                      </span>
                      <i class="icon-arrow-gt"></i>
                      <span class="price">
                      <strong><?=number_format($this->resteApayer,$this->decimales, ',', ' ')?> €</strong>
                      <?=$this->lng['preteur-projets']['restent-a-preter']?>
                      </span>

                      </div>

                      <div class="progressBar" data-percent="<?=number_format($this->pourcentage,$this->decimalesPourcentage, '.', '')?>">
                      <div><span></span></div>
                      </div>
                      </div><?php */ ?>

                    <div class="widget-cat progress-cat clearfix">
                        <div class="prices clearfix">
                            <span class="price less">
                                <strong><?= number_format($this->payer, $this->decimales, ',', ' ') ?> €</strong>
                                <?= $this->lng['preteur-projets']['de-pretes'] ?>
                            </span>
                            <i class="icon-arrow-gt"></i>
                            <?
                            if ($this->soldeBid >= $this->projects->amount)
                            {
                                ?>
                                <p style="font-size:14px;"><?= $this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a'] ?> <?= number_format($this->txLenderMax, 1, ',', ' ') ?>%</p>
                                <?
                            }
                            else
                            {
                                ?>
                                <span class="price">
                                    <strong><?= number_format($this->resteApayer, $this->decimales, ',', ' ') ?> €</strong>
                                    <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                                </span>
                                <?
                            }
                            ?>
                        </div>

                        <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                            <div><span></span></div>
                        </div>
                    </div>

                    <?
                    // restriction pour capital
                    if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr' || $this->lurl == 'http://partenaire.unilend.challenges.fr')
                    {
                        
                    }
                    else
                    {
                        ?>

                        <div class="widget-cat" style="padding-top:25px;">  	
                            <i class="plusmoins" id="close-seconnecter"></i>
                            <div class="seconnecter" <?= (isset($_POST['project_detail']) ? 'style="display:block"' : '') ?> >

                                <div style="display:none" class="seconnecteropen"><?= (isset($_POST['project_detail']) ? 'true' : 'false') ?></div>
                                <form target="_parent" method="post" action="<?= $this->url_form ?>/projects/detail/<?= $this->params[0] ?>" name="projectseconnecter" id="projectseconnecter">
                                    <div class="row">
                                        <input class="field field-medium" type="text" title="<?= $this->lng['header']['identifiant'] ?>" value="<?= $this->lng['header']['identifiant'] ?>" name="login" autocomplete="off">	
                                    </div>
                                    <div class="row">
                                        <span class="pass-field-holder">
                                            <input class="field field-medium" type="password" title="<?= $this->lng['header']['mot-de-passe'] ?>" name="password" autocomplete="off">
                                        </span>
                                        <a class="popup-link mdpoublie" href="<?= $this->lurl ?>/thickbox/pop_up_mdp"><?= $this->lng['header']['mot-de-passe-oublie'] ?></a>
                                    </div>


                                    <?
                                    // on lance le captcha
                                    if ($_SESSION['login']['nb_tentatives_precedentes'] > 10 && isset($_POST['project_detail']))
                                    {
                                        ?>
                                        <div class="row">
                                            <input type="text" name="captcha" class="field field-mini input_captcha_login" id="captcha" value="captcha" title="captcha">
                                            <div class="content_captcha_login" style="float:left;width:126px;">
                                                <img class="captcha_login" src="<?= $this->surl ?>/images/default/securitecode.php" alt="captcha" />
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
                                        <?
                                    }
                                    ?>


                                    <input type="hidden" name="connect" />
                                    <input type="hidden" name="project_detail" value="projects/detail/<?= $this->params[0] ?>" />
                                </form>
                            </div>
                            <div style="clear:both;"></div>

                            <a target="_parent" class="btn" id="seconnecter" style="width:210px; display:block;margin:auto;"><?= $this->lng['preteur-projets']['se-connecter'] ?></a>

                            <?
                            // on lance le message d'attente
                            if ($_SESSION['login']['nb_tentatives_precedentes'] <= 10 && $_SESSION['login']['nb_tentatives_precedentes'] > 1 && isset($_POST['project_detail']))
                            {
                                echo '<p class="error_login error_wait" style="display:block;text-align:center;">' . $this->lng['header']['vous-devez-attendre'] . ' ' . $_SESSION['login']['duree_waiting'] . ' ' . $this->lng['header']['secondes-avant-de-pourvoir-vous-connecter'] . '</p>';
                                ?>
                                <script type="text/javascript">
                                    $(".seconnecteropen").html('blocked');
                                    setTimeout(function () {
                                        $(".seconnecteropen").html('true');
                                    }, <?= ($_SESSION['login']['duree_waiting'] * 1000) ?>);
                                </script> 
                                <?
                            }
                            // message d'erreur
                            elseif ($_SESSION['login']['nb_tentatives_precedentes'] <= 1 && isset($_POST['project_detail']))
                            {
                                echo '<p class="error_login" style="text-align:center;">' . $this->error_login . '</p>';
                            }
                            ?>

                            <script type="text/javascript">
                                $("#seconnecter").click(function () {
                                    if ($(".seconnecteropen").html() == 'blocked') {
                                    }
                                    else if ($(".seconnecteropen").html() == 'false') {
                                        $(".seconnecter").slideDown();
                                        $(".seconnecteropen").html('true');
                                        $("#close-seconnecter").css('background-position', 'right');
                                    }
                                    else {
                                        $("#projectseconnecter").submit();
                                    }
                                });
                                $("#close-seconnecter").click(function () {
                                    if ($(".seconnecteropen").html() == 'true') {
                                        $(".seconnecter").slideUp();
                                        $(".seconnecteropen").html('false');
                                        $(this).css('background-position', 'left')
                                    }
                                    else {
                                        $(".seconnecter").slideDown();
                                        $(".seconnecteropen").html('true');
                                        $(this).css('background-position', 'right');
                                    }
                                });
                            </script>

                        </div>
                        <?
                    }
                    ?>

                    <div class="widget-cat" style="padding-top:25px;">

                        <i class="plusmoins" id="close-sinscrire"></i>
                        <div class="sinscrire"<?= ($this->retour_form != false ? 'style="display:block;"' : '') ?> >

                            <form target="_parent" method="post" action="<?= $this->url_form ?>/projects/detail/<?= $this->params[0] ?><?= $this->utm_source ?>" name="projectsinscrire" id="projectsinscrire">
                                <div style="display:none" class="sinscrireopen"><?= ($this->retour_form != false ? 'true' : 'false') ?></div>
                                <div class="row">
                                    <input class="field field-medium required" type="text" value="<?= (isset($_POST['nom']) ? $_POST['nom'] : $this->lng['landing-page']['nom']) ?>" title="<?= $this->lng['landing-page']['nom'] ?>" name="nom" id="signup-first-name" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}" >	
                                </div>
                                <div class="row">
                                    <input class="field field-medium required" type="text" value="<?= (isset($_POST['prenom']) ? $_POST['prenom'] : $this->lng['landing-page']['prenom']) ?>" title="<?= $this->lng['landing-page']['prenom'] ?>" name="prenom" id="signup-last-name" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}" >	
                                </div>
                                <div class="row">
                                    <input class="field field-medium required" type="text" value="<?= (isset($_POST['email']) ? $_POST['email'] : $this->lng['landing-page']['email']) ?>" title="<?= $this->lng['landing-page']['email'] ?>" name="email" id="email" data-validators="Presence&amp;Email" oncopy="return false;" oncut="return false;" onkeyup="checkConf(this.value, 'conf_email')">	
                                </div>
                                <div class="row">
                                    <input class="field field-medium required" type="text" value="<?= (isset($_POST['email-confirm']) ? $_POST['email-confirm'] : $this->lng['landing-page']['confirmation-email']) ?>" title="<?= $this->lng['landing-page']['confirmation-email'] ?>" name="conf_email" id="conf_email" data-validators="Confirmation,{ match: 'email' }" onpast="return false;" >	
                                </div>
                                <input type="hidden" name="send_inscription_project_detail" />
                            </form>
                        </div>

                        <a target="_parent" class="btn sinscrire_cta" id="sinscrire" style=""><?= $this->lng['preteur-projets']['sinscrire'] ?></a>
                        <p class="error_login" style="text-align:center;display:inline;"><?= $this->retour_form ?></p>

                        <script type="text/javascript">
                            $("#sinscrire").click(function () {
                                if ($(".sinscrireopen").html() == 'false') {
                                    $(".sinscrire").slideDown();
                                    $(".sinscrireopen").html('true');
                                    Form.initialise({selector: 'form'});
                                    $("#close-sinscrire").css('background-position', 'right');
                                }
                                else {
                                    $("#projectsinscrire").submit();
                                }
                            });
                            $("#close-sinscrire").click(function () {
                                if ($(".sinscrireopen").html() == 'true') {
                                    $(".sinscrire").slideUp();
                                    $(".sinscrireopen").html('false');
                                    $(this).css('background-position', 'left');
                                }
                                else {
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
        <?
    }
}
// Si on est connecté
else
{
    if ($this->page_attente == true)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-price">
                <div class="widget-top">
                    <i class="icon-pig"></i>
                    <?= number_format($this->projects->amount, 0, ',', ' ') ?> €
                </div>
                <div class="widget-body">
                    <div class="widget-cat progress-cat clearfix">
                        <div class="prices clearfix">
                            <span class="price less">
                                <strong><?= number_format($this->payer, $this->decimales, ',', ' ') ?> €</strong>
                                <?= $this->lng['preteur-projets']['de-pretes'] ?>
                            </span>
                            <i class="icon-arrow-gt"></i>
                            <span class="price">
                                <strong><?= number_format($this->resteApayer, $this->decimales, ',', ' ') ?> €</strong>
                                <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                            </span>

                        </div>

                        <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                            <div><span></span></div>
                        </div>
                    </div>
                </div>
                <div class="widget-body">
                    <div class="article">

                        <p style="padding:8px;"><?= $this->lng['preteur-projets']['periode-denchere-du-projet-terminee'] ?><br /></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // project RA
    elseif ($this->projects_status->status == 130)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top" style="line-height: 1.43;"><?= $this->lng['preteur-projets']['projet-rembourse-a-100'] ?></div>
                <div class="widget-body">
                    <div class="article">
                        <p><?= $this->lng['preteur-projets']['ce-projet-a-ete-totalement-rembourse-le'] ?> <strong class="pinky-span"> <?= date('d/m/Y', strtotime($this->lastStatushisto['added'])) ?></strong></p>
                        <p><?= $this->lng['preteur-projets']['vous-lui-avez-prete'] ?> <strong class="pinky-span"><?= number_format($this->bidsvalid['solde'], 0, ',', ' ') ?> €</strong> <br /><?= $this->lng['preteur-projets']['au-taux-moyen-de'] ?> <strong class="pinky-span"><?= number_format($this->AvgLoansPreteur, 1, ',', ' ') ?> %</strong></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // project fundé
    elseif ($this->projects_status->status == 60 || $this->projects_status->status >= 80)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top">
                    <?= $this->lng['preteur-projets']['projet-finance-a-100'] ?>
                </div>
                <div class="widget-body">
                    <div class="article">
                        <p><?= $this->lng['preteur-projets']['ce-projet-est-integralement-finance-par'] ?> <strong class="pinky-span"> <?= number_format($this->NbPreteurs, 0, ',', ' ') ?> <?= $this->lng['preteur-projets']['preteur'] ?><?= ($this->NbPreteurs > 1 ? 's' : '') ?></strong> <br /><?= $this->lng['preteur-projets']['au-taux-de'] ?><strong class="pinky-span"> <?= number_format($this->AvgLoans, 1, ',', ' ') ?> %</strong> <br /><?= $this->lng['preteur-projets']['en'] ?> <?= ($this->interDebutFin['day'] > 0 ? $this->interDebutFin['day'] . ' jours ' : '') ?><?= ($this->interDebutFin['hour'] > 0 ? $this->interDebutFin['hour'] . ' heures ' : '') ?> <?= $this->lng['preteur-projets']['et'] ?> <?= $this->interDebutFin['minute'] ?> <?= $this->lng['preteur-projets']['minutes'] ?></p>
                        <p><?= $this->lng['preteur-projets']['vous-lui-avez-prete'] ?> <strong class="pinky-span"><?= number_format($this->bidsvalid['solde'], 0, ',', ' ') ?> €</strong> <br /><?= $this->lng['preteur-projets']['au-taux-moyen-de'] ?> <strong class="pinky-span"><?= number_format($this->AvgLoansPreteur, 1, ',', ' ') ?> %</strong></p>
                        <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // Funding KO
    elseif ($this->projects_status->status == 70)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top" style="line-height: 36px;">
                    <?= $this->lng['preteur-projets']['projet-na-pas-pu-etre-finance-a-100'] ?>
                </div>
                <div class="widget-body">
                    <div class="article">
                        <p><?= $this->lng['preteur-projets']['ce-projet-a-ete-finance-a'] ?><?= number_format($this->pourcentage, $this->decimalesPourcentage, ',', '') ?>%</p>
                        <p><?= $this->lng['preteur-projets']['merci-a-tous'] ?></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // Prêt refusé
    elseif ($this->projects_status->status == 75)
    {
        ?>
        <div class="sidebar right">
            <aside class="widget widget-info">
                <div class="widget-top">
                    <?= $this->lng['preteur-projets']['projet-pret-rejete-titre'] ?>
                </div>
                <div class="widget-body">
                    <div class="article">

                        <p><?= $this->lng['preteur-projets']['projet-pret-rejete'] ?></p>
                    </div>
                </div>
            </aside>
        </div>
        <?
    }
    // en funding
    else
    {

        // Si profile non validé par unilend
        if ($this->clients_status->status < 60)
        {
            ?>
            <div class="sidebar right">
                <aside class="widget widget-price">
                    <div class="widget-top">
                        <i class="icon-pig"></i>
                        <?= number_format($this->projects->amount, 0, ',', ' ') ?> €
                    </div>
                    <div class="widget-body">
                        <div class="article">
                            <p style="padding:20px;"><?= $this->lng['preteur-projets']['completude-message'] ?>
                            </p>

                        </div>
                    </div>
                </aside>
            </div>
            <?
        }
        else
        {
            ?>
            <div class="sidebar right">
                <aside class="widget widget-price">
                    <div class="widget-top">
                        <i class="icon-pig"></i>
                        <?= number_format($this->projects->amount, 0, ',', ' ') ?> €
                    </div>
                    <div class="widget-body">
                        <form action="" method="post">
                            <div class="widget-cat progress-cat clearfix">
                                <div class="prices clearfix">
                                    <span class="price less">
                                        <strong><?= number_format($this->payer, $this->decimales, ',', ' ') ?> €</strong>
                                        <?= $this->lng['preteur-projets']['de-pretes'] ?>
                                    </span>
                                    <i class="icon-arrow-gt"></i>
                                    <?
                                    if ($this->soldeBid >= $this->projects->amount)
                                    {
                                        ?>
                                        <p style="font-size:14px;"><?= $this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a'] ?> <?= number_format($this->txLenderMax, 1, ',', ' ') ?>%</p>
                                        <?
                                    }
                                    else
                                    {
                                        ?>
                                        <span class="price">
                                            <strong><?= number_format($this->resteApayer, $this->decimales, ',', ' ') ?> €</strong>
                                            <?= $this->lng['preteur-projets']['restent-a-preter'] ?>
                                        </span>
                                        <?
                                    }
                                    ?>
                                </div>

                                <div class="progressBar" data-percent="<?= number_format($this->pourcentage, $this->decimalesPourcentage, '.', '') ?>">
                                    <div><span></span></div>
                                </div>
                            </div>
                            <?
                            if ($this->bidsEncours['nbEncours'] > 0)
                            {
                                ?>
                                <div class="widget-cat">
                                    <style>
                                        #plusOffres{cursor:pointer;}
                                        #lOffres{display:none;}
                                        #lOffres ul{list-style: none outside none;padding-left: 14px;font-size:15px;}
                                    </style>
                                    <h4 id="plusOffres"><?= $this->lng['preteur-projets']['offre-en-cours'] ?> <i class="icon-plus"></i></h4>
                                    <p style="font-size:14px;"><?= $this->lng['preteur-projets']['vous-avez'] ?> : <br /><?= $this->bidsEncours['nbEncours'] ?> <?= $this->lng['preteur-projets']['offres-en-cours-pour'] ?> <?= number_format($this->bidsEncours['solde'], 0, ',', ' ') ?> €</p>

                                    <div id="lOffres">
                                        <ul>
                                            <?
                                            foreach ($this->lBids as $b)
                                            {
                                                ?>
                                                <li>Offre de <?= number_format($b['amount'] / 100, 0, ',', ' ') ?> € au taux de <?= number_format($b['rate'], 1, ',', ' ') ?>%</li>
                                                <?
                                            }
                                            ?>
                                        </ul>
                                    </div>

                                </div>
                                <?
                            }

                            // a modifier pour quand mon met preteur/emprunteur
                            if ($this->clients->status_pre_emp != 2)
                            {
                                ?>

                                <div class="widget-cat">
                                    <h4><?= $this->lng['preteur-projets']['faire-une-offre'] ?></h4>
                                    <div class="row">
                                        <label><?= $this->lng['preteur-projets']['je-prete-a'] ?></label>
                                        <select name="tx_p" id="tx_p" class="custom-select field-hundred">
                                            <option value="<?= $this->projects->target_rate ?>"><?= $this->projects->target_rate ?></option>
                                            <?
                                            if ($this->soldeBid >= $this->projects->amount)
                                            {
                                                if (number_format($this->txLenderMax, 1, '.', ' ') > '10.0')
                                                {
                                                    ?><option <?= ($this->projects->target_rate == '10.0' ? 'selected' : '') ?> value="10.0">10,0%</option><?
                                                }
                                                for ($i = 9; $i >= 4; $i--)
                                                {
                                                    for ($a = 9; $a >= 0; $a--)
                                                    {
                                                        if (number_format($this->txLenderMax, 1, '.', ' ') > $i . '.' . $a)
                                                        {
                                                            ?><option <?= ($this->projects->target_rate == $i . '.' . $a ? 'selected' : '') ?> value="<?= $i . '.' . $a ?>"><?= $i . ',' . $a ?>%</option><?
                                                        }
                                                    }
                                                }
                                            }
                                            else
                                            {
                                                ?><option <?= ($this->projects->target_rate == '10.0' ? 'selected' : '') ?> value="10.0">10,0%</option><?
                                                for ($i = 9; $i >= 4; $i--)
                                                {
                                                    for ($a = 9; $a >= 0; $a--)
                                                    {
                                                        ?><option <?= ($this->projects->target_rate == $i . '.' . $a ? 'selected' : '') ?> value="<?= $i . '.' . $a ?>"><?= $i . ',' . $a ?>%</option><?
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>

                                    </div>
                                    <div class="row last-row">
                                        <label><?= $this->lng['preteur-projets']['la-somme-de'] ?></label>
                                        <input name="montant_p" id="montant_p" type="text" title="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" value="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" class="field" onkeyup="lisibilite_nombre(this.value, this.id);"/> <span style="margin-left: -15px;position: relative;top: 4px;">€</span>
                                    </div>

                                    <p class="laMensual" style="font-size:14px;display:none;"><?= $this->lng['preteur-projets']['soit-un-remboursement-mensuel-de'] ?></p>
                                    <div class="laMensual" style="font-size:14px;width:245px;display:none;">
                                        <div style="text-align:center;"><span id="mensualite">xx</span> €</div>
                                    </div>
                                    <br />

                                    <?php
                                    // on check si on a coché les cgv ou pas 
                                    // cgu societe
                                    if (in_array($this->clients->type, array(2, 4)))
                                    {
                                        $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                                        $this->lienConditionsGenerales_header = $this->settings->value;
                                    }
                                    // cgu particulier
                                    else
                                    {
                                        $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                                        $this->lienConditionsGenerales_header = $this->settings->value;
                                    }

                                    // liste des cgv accpeté
                                    $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
                                    //$listeAccept = array();
                                    // Initialisation de la variable
                                    $this->update_accept_header = false;

                                    // On cherche si on a déjà le cgv
                                    if (in_array($this->lienConditionsGenerales, $listeAccept_header))
                                    {
                                        $this->accept_ok_header = true;
                                    }
                                    else
                                    {
                                        $this->accept_ok_header = false;
                                        // Si on a deja des cgv d'accepté
                                        if ($listeAccept_header != false)
                                        {
                                            $this->update_accept_header = true;
                                        }
                                    }
                                    
                                    // pour éviter de refaire tous les conditions une autre fois pour le mobile, on place une variable qui sera a true si le client peut prêter
                                    $this->preter_by_mobile_ok = true;
                                    //$this->accept_ok_header = false;
                                    ?>


                                    <a style="width:76px; display:block;margin:auto;" href="<?= (!$this->accept_ok_header ? $this->lurl . '/thickbox/pop_up_cgv' : $this->lurl . '/thickbox/pop_valid_pret/' . $this->projects->id_project) ?>" class="btn btn-medium popup-link <?= (!$this->accept_ok_header ? 'thickbox' : '') ?>"><?= $this->lng['preteur-projets']['preter'] ?></a> 
                                </div>
                                <?
                            }
                            ?>
                        </form>
                    </div>
                </aside>
            </div>
            <?
        }
    }
}
