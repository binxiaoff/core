<link media ="all" href="<?= $this->lurl ?>/styles/default/synthese1.css" type="text/css" rel="stylesheet" />
<div class="popup" style="background-color: #E3E4E5;">
    <a href="#" class="popup-close">close</a>
    <div class="popup-head">
        <h2><?= $this->lng['preteur-profile']['pop-up-cgv-titre'] ?></h2>
    </div>
    <div class="popup-cnt">
        <p>
            <div class="notification-primary">
                <div class="notification-body">
                    <?php if ($this->update_accept_header && 0 === $this->iLoansCount) : ?>
                        <?= $this->bloc_cgv['content-2'] ?>
                    <?php elseif ($this->update_accept_header && 0 < $this->iLoansCount) : ?>
                        <?= $this->bloc_cgv['content-3'] ?>
                    <?php else : ?>
                        <?= $this->bloc_cgv['content-1'] ?>
                    <?php endif; ?>
                    <div class="form-terms">
                        <form action="" method="post">
                            <div class="checkbox checkbox_pop" >
                                <input type="checkbox" name="terms_pop" id="terms_pop"/>
                                <label for="terms_pop"><?= str_replace(array('[', ']'), array('<a target="_blank" href="' . $this->lurl . '/cgv_preteurs/nosign">', '</a>'), $this->bloc_cgv['checkbox-cgv']) ?></label>
                            </div>
                            <div class="form-actions">
                                <button type="button" id="cta_cgv_pop" class="btn form-btn">
                                    <?= $this->bloc_cgv['cta-valider'] ?>
                                    <i class="ico-arrow"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                $( "#cta_cgv_pop" ).click(function() {
                    if ($("#terms_pop").prop('checked')) {
                        $.post( add_url+"/ajax/accept_cgv", { terms: $("#terms_pop").val(), id_legal_doc: "<?= $this->lienConditionsGenerales_header ?>" }).done(function(data) {
                            location.reload();
                        });
                    } else{
                        $(".checkbox_pop a").css('color','#c84747');
                    }
                });
                $( "#terms_pop" ).change(function() {
                    if ($(this).prop('checked')) {
                        $(".checkbox_pop a").css('color','#727272');
                    }
                });
            </script>
        </p>
    </div>
</div>
