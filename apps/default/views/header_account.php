<?php if (isset($_SESSION['lexpress'])): ?>
    <iframe name="lexpress" SRC="<?= $_SESSION['lexpress']['header'] ?>" scrolling="no" height="138px" width="100%" FRAMEBORDER="no"></iframe>
<?php endif; ?>
<div class="wrapper">
    <div class="header">
        <div class="shell clearfix">
            <div class="logo"><a href="<?= $this->lurl ?>"><?= $this->lng['header']['unilend'] ?></a></div>
            <div class="toggle-buttons">
                <div class="nav-toggle"></div>
                <div class="login-toggle"></div>
                <div class="search-toggle"></div>
            </div>
            <?= $this->fireView('../blocs/header-account') ?>
        </div>
    </div>

    <?php if ($this->bDisplayHeaderLender) :
        if (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) :
            $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
            $this->lienConditionsGenerales_header = $this->settings->value;
        else :  // particulier
            $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $this->lienConditionsGenerales_header = $this->settings->value;
        endif;

        $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
        $this->update_accept_header = false;

        if (in_array($this->lienConditionsGenerales, $listeAccept_header)) :
            $this->accept_ok_header = true;
        else :
            $this->accept_ok_header = false;

            if ($listeAccept_header != false) :
                $this->update_accept_header = true;
            endif;
        endif; ?>
    <?php if (! $this->accept_ok_header) : ?>
            <script type="text/javascript">
                $(function() {
                    $.colorbox({
                        href: "<?= $this->lurl ?>/thickbox/pop_up_cgv",
                        fixed: true,
                        maxWidth: '90%'
                    });
                });
            </script>
    <?php endif; ?>
        <style type="text/css">
            .navigation .styled-nav {
                width: 100%;
            }
        </style>
        <div class="navigation">
            <div class="shell clearfix">
                <ul class="styled-nav">
                    <li class="active nav-item-home" style="position: relative;top: 10px;height: 16px;overflow:hidden;">
                        <a href="<?= $this->lurl ?>/synthese"><i class="icon-home"></i></a>
                    </li>
                    <li>
                        <a<?= ($this->page == 'alimentation' ? ' class="active"' : '') ?> href="<?= $this->lurl ?>/alimentation"><?= $this->lng['header']['alimentation'] ?></a>
                    </li>
                    <li>
                        <a<?= ($this->page == 'projects' ? ' class="active"' : '') ?> href="<?= $this->lurl ?>/projects"><?= $this->lng['header']['projets'] ?></a>
                    </li>
                    <li>
                        <a<?= ($this->page == 'operations' ? ' class="active"' : '') ?> href="<?= $this->lurl ?>/operations"><?= $this->lng['header']['operations'] ?></a>
                    </li>
                    <li>
                        <a<?= ($this->page == 'profile' ? ' class="active"' : '') ?> href="<?= $this->lurl ?>/profile"><?= $this->lng['header']['mon-profil'] ?></a>
                    </li>
                    <li style="float:right;width:45px;padding:0;margin-right:10px;" class="sidebar-notifs">
                        <div class="bell-notif">
                            <?php if ($this->NbNotifHeader > 0): ?>
                                <?php if ($this->NbNotifHeader < 100): ?>
                                    <span class="nb-notif"<?= ($this->NbNotifHeader > 9 ? ' style="padding-left: 1px;"' : '') ?>>
                                        <?= $this->NbNotifHeader ?>
                                    </span>
                                <?php else: ?>
                                    <span class="nb-notif" style="padding-left: 2px;">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dd">
                            <span class="bullet notext">bullet</span>
                            <div class="content">
                                <div class="title_notif" style="padding-left:5px;">Notifications <?= ($this->NbNotifHeader > 0 ? '<a class="marquerlu">Marquer comme lu</a>' : '') ?></div>
                                <?= $this->fireView('../ajax/notifications_header') ?>
                                <?php if ($this->NbNotifHeaderEnTout > $this->nbNotifdisplay): ?>
                                    <div class="notif_plus">Afficher plus</div>
                                <?php endif; ?>
                                <div style="display:none" class="compteur_notif"><?= $this->nbNotifdisplay ?></div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <script type="text/javascript">
                $(".notif_plus").click(function() {
                    $.post(add_surl + "/ajax/notifications_header", {compteur_notif: $('.compteur_notif').html()}).done(function (data) {
                        if (data == 'noMore') {
                            $('.notif_plus').html('');
                        } else {
                            $('.compteur_notif').html('true');
                            $('.sidebar-notifs .notif:last').after(data);
                        }
                    });
                });

                $(".marquerlu").click(function() {
                    $.post(add_surl + "/ajax/notifications_header", {marquerlu: true}).done(function (data) {
                        $('.sidebar-notifs .nbNonLu').remove();
                        $('.sidebar-notifs .marquerlu').remove();
                        $('.sidebar-notifs .notif').remove();
                        $('.sidebar-notifs .title_notif').after(data);
                    });
                });

                $('.sidebar-notifs').hover(function() {
                    $(this).find('.dd').stop(true, true).show();
                }, function () {
                    $(this).find('.dd').hide();
                })
            </script>
        </div>
    <?php elseif($this->bDisplayHeaderBorrower) : ?>
        <style type="text/css">
            .navigation .styled-nav{width: 100%;}
        </style>
            <div class="navigation ">
                <div class="shell clearfix">
                    <ul class="styled-nav" style="margin-top: 10px;">
                        <li class="active nav-item-home" style="position: relative;top: 10px;height: 16px;overflow:hidden;"><a href="<?= $this->lurl ?>/espace_emprunteur/projets"><i class="icon-home"></i></a></li>
                        <li><a <?= ($this->page == 'projets' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/espace_emprunteur/projets"><?= $this->lng['header']['vos-projets'] ?></a></li>
                        <li><a <?= ($this->page == 'operations' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/espace_emprunteur/operations"><?= $this->lng['header']['vos-operations'] ?></a></li>
                        <li><a <?= ($this->page == 'profil' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/espace_emprunteur/profil"><?= $this->lng['header']['votre-profil'] ?></a></li>
                        <li><a <?= ($this->page == 'faq' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/espace_emprunteur/faq"><?= $this->lng['header']['faq'] ?></a></li>
                        <li><a <?= ($this->page == 'contact' ? 'class="active"' : '') ?> href="<?= $this->lurl ?>/espace_emprunteur/contact"><?= $this->lng['header']['contact'] ?></a></li>
                    </ul>
                </div>
            </div>
    <?php endif; ?>
