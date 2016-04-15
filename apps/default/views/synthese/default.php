<div class="main">
    <?php if (in_array('', array($this->clients->secrete_question, $this->clients->secrete_reponse)) && $_SESSION['qs'] != date('d') || isset($_SESSION['qs_ok']) && $_SESSION['qs_ok'] == 'OK') { ?>
        <script type="text/javascript">
            $.colorbox({href: add_url + "/thickbox/pop_up_qs", opacity: 0.5, scrolling: false});
        </script>
        <?php $_SESSION['qs'] = date('d'); ?>
    <?php } ?>
    <div class="shell">
        <div class="section-c dashboard clearfix">
            <div class="page-title clearfix">
                <h1 class="left"><?= $this->lng['preteur-synthese']['votre-tableau-de-bord'] ?></h1>
                <strong class="right">au <?= $this->dates->formatDateComplete(date('Y-m-d H:i:s')) ?> à <?= date('H\hi') ?></strong>
            </div>
            <?php if ($this->accept_ok == false) { ?>
                <div class="notification-primary">
                    <div class="notification-head">
                        <h3 class="notification-title"><?= $this->bloc_cgv['titre-242'] ?></h3>
                    </div>
                    <div class="notification-body">
                        <?php if ($this->update_accept && 0 === $this->iLoansCount) : ?>
                            <?= $this->bloc_cgv['content-2'] ?>
                        <?php elseif ($this->update_accept && 0 < $this->iLoansCount) : ?>
                            <?= $this->bloc_cgv['content-3'] ?>
                        <?php else : ?>
                            <?= $this->bloc_cgv['content-1'] ?>
                        <?php endif; ?>
                        <div class="form-terms">
                            <form action="" method="post">
                                <div class="checkbox">
                                    <input type="checkbox" name="terms" id="terms"/>
                                    <label for="terms"><a target="_blank" href="<?= $this->lurl ?>/cgv_preteurs/nosign"><?= $this->bloc_cgv['checkbox-cgv'] ?></a></label>
                                </div>
                                <div class="form-actions">
                                    <button type="button" id="cta_cgv" class="btn form-btn">
                                        <?= $this->bloc_cgv['cta-valider'] ?>
                                        <i class="ico-arrow"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    $("#cta_cgv").click(function () {
                        if ($("#terms").is(':checked') == true) {
                            $.post(add_url + "/ajax/accept_cgv", {
                                terms: $("#terms").val(),
                                id_legal_doc: "<?= $this->lienConditionsGenerales ?>"
                            }).done(function(data) {
                                $(".notification-primary").fadeOut();
                                setTimeout(function () {
                                    $(".notification-primary").remove();
                                }, 1000);
                            });
                        } else {
                            $(".checkbox a").css('color', '#c84747');
                        }
                    });
                    $("#terms").change(function () {
                        if ($(this).is(':checked') == true) {
                            $(".checkbox a").css('color', '#727272');
                        }
                    });
                </script>
            <?php } ?>
            <?php if ($this->nblFavP > 0 && ! isset($_SESSION['lFavP'])) { ?>
                <div class="natification-msg">
                    <h3><?= $this->lng['preteur-synthese']['notifications'] ?></h3>
                    <ul>
                    <?php if ($this->nblFavP > 0) { ?>
                        <li>
                            <strong><?= $this->lng['preteur-synthese']['mes-favoris'] ?> :</strong>
                            <ul>
                            <?php
                                foreach ($this->lFavP as $f) {
                                    $this->projects->get($f['id_project'], 'id_project');
                                    $_SESSION['lFavP'] = true;
                                    ?>
                                    <li>
                                        <?= ($f['datediff'] > 0 ? 'Plus que ' . $f['datediff'] . ' jours' : 'Dernier jour') ?>
                                        <?= $this->lng['preteur-synthese']['pour-faire-une-offre-de-pret-sur-le-projet'] ?>
                                        <a href="<?= $this->lurl ?>/projects/detail/<?= $this->projects->slug ?>"><?= $f['title'] ?></a>.
                                    </li>
                            <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>
                    </ul>
                    <a class="esc-btn" href="#"></a>
                </div>
            <?php } ?>
            <div class="col left">
                <?= $this->fireView('../blocs/synthese-preteur-bloc-gauche') ?>
            </div>
            <div class="col right">
                <?= $this->fireView('../blocs/synthese-preteur-bloc-droit') ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('.chart-slider').carouFredSel({
        width: 420,
        height: 260,
        auto: false,
        prev: '.slider-c .arrow.prev',
        next: '.slider-c .arrow.next',
        items: {
            visible: 1
        }
    });
</script>
