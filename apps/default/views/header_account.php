<?
// header personalisé pour l'express
if (isset($_SESSION['lexpress']))
{
    ?>
    <iframe name="lexpress" SRC="<?= $_SESSION['lexpress']['header'] ?>" scrolling="no" height="138px" width="100%" FRAMEBORDER="no"></iframe>
    <?
}
?>
<div class="wrapper">

    <div class="header">
        <div class="shell clearfix">
            <div class="logo"><a href="<?= $this->lurl ?>"><?= $this->lng['header']['unilend'] ?></a></div><!-- /.logo -->

            <div class="toggle-buttons">
                <div class="nav-toggle"></div><!-- /.nav-toggle -->

                <div class="login-toggle"></div><!-- /.login-toggle -->

                <div class="search-toggle"></div><!-- /.search-toggle -->
            </div><!-- /.toggle-buttons -->

            <?= $this->fireView('../blocs/header-account') ?>


        </div><!-- /.shell -->
    </div><!-- /.header -->
    <?
// preteur
    if ($this->clients->status_pre_emp == 1)
    {

        //Affichage de la popup de CGV si on a pas encore valide
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
        ?>
        <script type="text/javascript">
            $(document).ready(function () {
    <?php
    if (!$this->accept_ok_header /* &&  ($_SERVER['REMOTE_ADDR']=='93.26.42.99' or $_SERVER['REMOTE_ADDR']=='92.154.67.76' or $_SERVER['REMOTE_ADDR']=='83.204.169.192') */)
    {
        ?>
                    $.colorbox({
                        href: "<?= $this->lurl ?>/thickbox/pop_up_cgv",
                        fixed: true,
                        maxWidth: '90%',
                        onClosed: function () {
                            /*location.reload();*/
                        }
                    });
        <?php
    }
    ?>
            });</script>
        <?php
        // end popup CGV
        ?>
        <style type="text/css">
            .navigation .styled-nav{width: 100%;}
        </style>
        <div class="navigation ">
            <div class="shell clearfix">
                <ul class="styled-nav">
                    <li class="active nav-item-home" style="position: relative;top: 10px;height: 16px;overflow:hidden;"><a href="<?= $this->lurl ?>/synthese"><i class="icon-home"></i></a></li>

                    <?php /* ?><li><a <?=($this->page=='synthese'?'class="active"':'')?> href="<?=$this->lurl?>/synthese"><?=$this->lng['header']['synthese']?></a></li><?php */ ?>
                    <li><a <?= ($this->page == 'alimentation' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/alimentation"><?= $this->lng['header']['alimentation'] ?></a></li>
                    <li><a <?= ($this->page == 'projects' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/projects"><?= $this->lng['header']['projets'] ?></a></li>
                    <li><a <?= ($this->page == 'operations' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/operations"><?= $this->lng['header']['operations'] ?></a></li>
                    <li><a <?= ($this->page == 'profile' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/profile"><?= $this->lng['header']['mon-profil'] ?></a></li>

                    <?
                    //if($_SERVER['REMOTE_ADDR'] == '93.26.42.99'){
                    ?>
                    <li style="float:right;width:45px;padding:0px;margin-right:10px;" class="sidebar-notifs">

                        <div class="bell-notif">
                            <?
                            if ($this->NbNotifHeader > 0)
                            {

                                if ($this->NbNotifHeader < 100)
                                {
                                    ?>
                                    <span class="nb-notif" <?= ($this->NbNotifHeader > 9 ? 'style="padding-left: 1px;"' : '') ?> >
                                        <?= $this->NbNotifHeader ?>
                                    </span>
                                    <?
                                }
                                else
                                {
                                    ?><span class="nb-notif" style="padding-left: 2px;" >...</span><?
                                }
                            }
                            ?></div>

                        <div class="dd">
                            <span class="bullet notext">bullet</span>
                            <div class="content">
                                <div class="title_notif" style="padding-left:5px;">Notifications <?= ($this->NbNotifHeader > 0 ? '<a class="marquerlu">Marquer comme lu</a>' : '') ?></div>

                                <?
                                foreach ($this->lNotifHeader as $r)
                                {
                                    ?>
                                    <div class="notif <?= ($r['status'] == 1 ? 'view' : '') ?>">
                                        <?
                                        // Offre refusée
                                        if ($r['type'] == 1)
                                        {
                                            $this->bids->get($r['id_bid'], 'id_bid');
                                            $this->projects_notifs->get($r['id_project'], 'id_project');
                                            $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');

                                            // decoupé
                                            if ($this->bids->amount != $r['amount'])
                                            {
                                                ?>
                                                <b><?= $this->lng['notifications']['offre-partiellement-refusee'] ?></b><br />

                                                <div class="content_notif">
                                                    <?
                                                    $montant = ($this->bids->amount - $r['amount']);
                                                    ?><?= $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 2, ',', ' ') ?> %</b><?= $this->lng['notifications']['offre-refusee-pour-un-montant-de'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->amount / 100, 2) ?> €</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?> <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-refusee-a-ete-decoupe'] ?> <b style="color:#b20066;"><?= number_format($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['offre-refusee-point'] ?>
                                                </div><?
                                            }
                                            else
                                            {
                                                ?>
                                                <b><?= $this->lng['notifications']['offre-refusee'] ?></b><br />

                                                <div class="content_notif">
                                                    <?= $this->lng['notifications']['offre-refusee-attention-votre-offre-de-pret-a'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->bids->rate, 2, ',', ' ') ?> %</b> <?= $this->lng['notifications']['offre-refusee-pour-un-montant-de'] ?> <b style="color:#b20066;"><?= number_format($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['offre-refusee-sur-le-projet'] ?> <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-refusee-nest-plus-recevable'] ?>
                                                </div>
                                                <?
                                            }
                                        }
                                        // Remboursement
                                        elseif ($r['type'] == 2)
                                        {
                                            $this->projects_notifs->get($r['id_project'], 'id_project');
                                            ?>
                                            <b><?= $this->lng['notifications']['remboursement'] ?></b><br />
                                            <div class="content_notif">
                                                <?= $this->lng['notifications']['remboursement-vous-venez-de-recevoir-un-remboursement-de'] ?> <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['remboursement-pour-le-projet'] ?> <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a><?= $this->lng['notifications']['remboursement-point'] ?>
                                            </div>
                                            <?
                                        }
                                        // Offre placée
                                        elseif ($r['type'] == 3)
                                        {
                                            $this->bids->get($r['id_bid'], 'id_bid');
                                            $this->projects_notifs->get($r['id_project'], 'id_project');
                                            $this->companies_notifs->get($this->projects_notifs->id_company, 'id_company');
                                            ?>
                                            <b><?= $this->lng['notifications']['offre-placee'] ?></b><br />
                                            <div class="content_notif">
                                                <?= $this->lng['notifications']['offre-placee-votre-offre-de-pret-de'] ?> <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->bids->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-placee-a'] ?>
                                                <b style="color:#b20066;"><?= $this->ficelle->formatNumber((float) $this->bids->rate) ?> %</b> <?= $this->lng['notifications']['offre-placee-sur-le-projet'] ?> <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->companies_notifs->name ?></a> <?= $this->lng['notifications']['offre-placee-point'] ?>
                                            </div>
                                            <?
                                        }
                                        // Offre acceptée
                                        elseif ($r['type'] == 4)
                                        {

                                            $this->loans->get($r['id_bid'], 'id_bid');
                                            $this->projects_notifs->get($r['id_project'], 'id_project');
                                            ?>
                                            <b><?= $this->lng['notifications']['offre-acceptee'] ?></b><br />
                                            <div class="content_notif">
                                                <?= $this->lng['notifications']['offre-acceptee-votre-offre-de-pret-de'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($this->loans->rate, 2, ',', ' ') ?> %</b> <?= $this->lng['notifications']['offre-acceptee-pour-un-montant-de'] ?> <b style="color:#b20066;white-space:nowrap;"><?= number_format($this->loans->amount / 100) ?> €</b> <?= $this->lng['notifications']['offre-acceptee-sur-le-projet'] ?> <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['offre-acceptee-a-ete-acceptee'] ?>
                                            </div>
                                            <?
                                        }
                                        // Confirmation alimentation par virement
                                        elseif ($r['type'] == 5)
                                        {
                                            ?>
                                            <b><?= $this->lng['notifications']['conf-alim-virement'] ?></b><br />
                                            <div class="content_notif">
                                                <?= $this->lng['notifications']['conf-alim-virement-votre-alim-par-virement-dun-montant-de'] ?> <b style="white-space:nowrap;color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-virement-a-ete-ajoute-a-votre-solde'] ?>
                                            </div>
                                            <?
                                        }
                                        // Confirmation alimentation par carte bancaire
                                        elseif ($r['type'] == 6)
                                        {
                                            ?>
                                            <b><?= $this->lng['notifications']['conf-alim-cb'] ?></b><br />
                                            <div class="content_notif">
                                                <?= $this->lng['notifications']['conf-alim-cb-votre-alim-par-cb-dun-montant-de'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b> <?= $this->lng['notifications']['conf-alim-cb-a-ete-ajoute-a-votre-solde'] ?>
                                            </div>
                                            <?
                                        }
                                        // Confirmation de retrait
                                        elseif ($r['type'] == 7)
                                        {
                                            ?>
                                            <b><?= $this->lng['notifications']['conf-retrait'] ?></b><br />
                                            <div class="content_notif">
                                                <?= $this->lng['notifications']['conf-retrait-votre-retrait-dun-montant-de'] ?> <b style="color:#b20066;"><?= $this->ficelle->formatNumber($r['amount'] / 100) ?> €</b><?= $this->lng['notifications']['conf-retrait-a-ete-pris-en-compte'] ?>
                                            </div>
                                            <?
                                        }
                                        // Annonce nouveau projet
                                        elseif ($r['type'] == 8)
                                        {
                                            $this->projects_notifs->get($r['id_project'], 'id_project');
                                            ?>
                                            <b><?= $this->lng['notifications']['annonce-nouveau-projet'] ?></b><br />
                                            <div class="content_notif"><?= $this->lng['notifications']['annonce-nouveau-projet-nouveau-projet'] ?> <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects_notifs->slug ?>"><?= $this->projects_notifs->title ?></a> <?= $this->lng['notifications']['annonce-nouveau-projet-mis-en-ligne-le'] ?> <?= date('d/m/Y', strtotime($this->projects_notifs->date_publication_full)) ?> <?= $this->lng['notifications']['annonce-nouveau-projet-a'] ?> <?= date('H\Hi', strtotime($this->projects_notifs->date_publication_full)) ?><?= $this->lng['notifications']['annonce-nouveau-projet-montant-demande'] ?> <b style="color:#b20066;white-space:nowrap;"><?= $this->ficelle->formatNumber($this->projects_notifs->amount) ?> €</b> <?= $this->lng['notifications']['annonce-nouveau-projet-sur-une-periode-de'] ?> <?= $this->projects_notifs->period ?> <?= $this->lng['notifications']['annonce-nouveau-projet-mois'] ?>
                                            </div>
                                            <?
                                        }
                                        ?>
                                        <span class="date_notif" ><?= date('d/m/Y', strtotime($r['added'])) ?></span>
                                    </div>

                                    <?
                                }

                                if ($this->NbNotifHeaderEnTout > $this->nbNotifdisplay)
                                {
                                    ?><div class="notif_plus">Afficher plus</div><?
                                }
                                ?>

                                <div style="display:none" class="compteur_notif"><?= $this->nbNotifdisplay ?></div>
                            </div>
                        </div>
                    </li>
                    <?
                    //}
                    ?>
                </ul><!-- /.nav-main -->


            </div><!-- /.shell -->

            <?
            //if($_SERVER['REMOTE_ADDR'] == '93.26.42.99'){
            ?>
            <script type="text/javascript">
                $(".notif_plus").click(function () {
                    $.post(add_surl + "/ajax/notifications_header", {compteur_notif: $('.compteur_notif').html()}).done(function (data) {
                        if (data == 'noMore') {
                            $('.notif_plus').html('');
                        }
                        else {
                            $('.compteur_notif').html('true');
                            $('.sidebar-notifs .notif:last').after(data);
                        }
                    });
                });


                $(".marquerlu").click(function () {
                    $.post(add_surl + "/ajax/notifications_header", {marquerlu: true}).done(function (data) {

                        $('.sidebar-notifs .nbNonLu').remove();
                        $('.sidebar-notifs .marquerlu').remove();
                        $('.sidebar-notifs .notif').remove();
                        $('.sidebar-notifs .title_notif').after(data);

                    });
                });

                $('.sidebar-notifs').hover(function () {
                    $(this).find('.dd').stop(true, true).show();
                }, function () {
                    $(this).find('.dd').hide();
                })
            </script>
            <?
            //}
            ?>

        </div><!-- /.navigation -->
        <?
    }
// emprunteur
    else
    {
        ?>
        <style type="text/css">
            .navigation .styled-nav{width: 713px;}
        </style>

        <?
        if ($this->etape_transition == true)
        {
            ?>
            <div class="navigation ">
                <div class="shell">
                    <h1><?= $this->tree->title ?></h1>
                </div>
            </div>
            <?
        }
        else
        {
            ?>
            <div class="navigation ">
                <div class="shell clearfix">
                    <ul class="styled-nav">
                        <li><a <?= ($this->page == 'synthese' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/synthese_emprunteur"><?= $this->lng['header']['synthese'] ?></a></li>
                        <?
                        if ($this->nbProjets > 1)
                        {
                            ?>
                            <li><a <?= ($this->page == 'projects' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/projects_emprunteur"><?= $this->lng['header']['projets'] ?></a></li>
                            <?
                        }
                        ?>
                        <li><a <?= ($this->page == 'societe' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/societe_emprunteur"><?= $this->lng['header']['societe'] ?></a></li>
                        <li><a <?= ($this->page == 'unilend_emprunteur' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/unilend_emprunteur"><?= $this->lng['header']['unilend'] ?></a></li>
                    </ul><!-- /.nav-main -->

                    <a class="outnav right" href="<?= $this->lurl ?>/create_project_emprunteur"><span><?= $this->lng['header']['nouveau-projet'] ?></span></a>
                </div><!-- /.shell -->
            </div><!-- /.navigation -->
            <?
        }
    }
    ?>