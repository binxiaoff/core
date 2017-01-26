<div id="popup" style="width: 433px;">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Passage en statut &laquo;&nbsp;<?= $this->projects_status->label ?>&nbsp;&raquo;</h1>
    <form id="problematic_status_form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->iProjectId ?>">
        <?php if ($this->bDecisionDate) : ?>
            <label for="decision_date"><em>Date du jugement</em></label><br/><br/>
            <input type="text" id="decision_date" name="decision_date" class="input_dp" value="<?= date('d/m/Y') ?>" /><br/><br/><br/>
        <?php endif; ?>
        <?php if ($this->bReceiver) : ?>
            <label for="receiver"><em>Coordonnées du mandataire judiciaire</em></label><br/><br/>
            <textarea id="receiver" name="receiver" class="textarea_lng" style="height: 100px;width: 420px;"><?= isset($this->sPreviousReceiver) ? $this->sPreviousReceiver : '' ?></textarea><br/><br/>
        <?php endif; ?>
        <?php if ($this->bAskEmail) : ?>
            <em>Envoyer un email d'information aux prêteurs</em><br/><br/>
            <label><input type="radio" name="send_email" value="1"/> Oui</label>
            <label><input type="radio" name="send_email" value="0"/> Non</label><br/><br/>
            <div class="hidden-fields">
        <?php else : ?>
            <input type="hidden" name="send_email" value="1"/>
        <?php endif; ?>
        <?php if ($this->bCustomEmail) : ?>
            <label for="mail_content"><em>Email d'information aux prêteurs</em></label><br/><br/>
            <textarea id="mail_content" name="mail_content" class="textarea_lng" style="height: 100px;width: 420px;"><?= isset($this->mailInfoStatusChange) ? $this->mailInfoStatusChange : '' ;?></textarea><br/><br/>
        <?php endif; ?>
        <?php if ($this->bAskEmail) : ?>
            </div>
        <?php endif; ?>
        <?php if ($this->bCustomSite) : ?>
            <label for="site_content"><em>Message d'information aux prêteurs (site)</em></label><br/><br/>
            <textarea id="site_content" name="site_content" class="textarea_lng" style="height:100px; width:420px;"><?= isset($this->sInfoStatusChange) ? $this->sInfoStatusChange : '' ;?></textarea>
            <br/><br/>
        <?php endif; ?>
        <?php if ($this->bAskEmailBorrower) : ?>
            <em>Envoyer un email d'information aux emprunteurs</em><br/><br/>
            <label><input type="radio" name="send_email_borrower" value="1"/> Oui</label>
            <label><input type="radio" name="send_email_borrower" value="0"/> Non</label><br/><br/>
        <?php else : ?>
            <input type="hidden" name="send_email_borrower" value="1"/>
        <?php endif; ?>
        <div id="problematic_status_error">Vous devez saisir tous les champs<br/><br/></div>
        <div style="text-align:right">
            <input type="hidden" name="problematic_status" value="<?= $this->projects_status->status ?>"/>
            <input type="submit" class="btn_link" value="Sauvegarder"/>
        </div>
    </form>
</div>
<script type="text/javascript">
    <?php if ($this->bAskEmail) : ?>
        $('[name=send_email]').change(function() {
            if (1 == $(this).val()) {
                $('.hidden-fields').slideDown(function() {
                    $.colorbox.resize();
                });
            } else {
                $('.hidden-fields').slideUp(function() {
                    $.colorbox.resize();
                });
            }
        });
    <?php endif; ?>

    <?php if ($this->bDecisionDate) : ?>
        $('#decision_date').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });
    <?php endif; ?>

    $('#problematic_status_form').submit(function(e) {
        var error = false;

        <?php if ($this->bDecisionDate) : ?>
            if ('' == $('#decision_date').val()) {
                error = true;
            }
        <?php endif; ?>

        <?php if ($this->bReceiver) : ?>
            if ('' == $('#receiver').val()) {
                error = true;
            }
        <?php endif; ?>

        <?php if ($this->bAskEmail) : ?>
            if (undefined == $('[name=send_email]:checked').val()) {
                error = true;
            }
        <?php endif; ?>

        <?php if ($this->bAskEmailBorrower) : ?>
            if (undefined == $('[name=send_email_borrower]:checked').val()) {
                error = true;
            }
        <?php endif; ?>

        <?php if ($this->bCustomEmail) : ?>
            if (1 == $('[name=send_email]:checked').val() && '' == $('#mail_content').val()) {
                error = true;
            }
        <?php endif; ?>

        <?php if ($this->bCustomSite) : ?>
            if ('' == $('#site_content').val()) {
                error = true;
            }
        <?php endif; ?>

        if (error) {
            e.preventDefault();

            $("#problematic_status_error").slideDown(function() {
                $.colorbox.resize();
            });

            setTimeout(function() {
                $("#problematic_status_error").slideUp(function() {
                    $.colorbox.resize();
                });
            }, 3000);
        }
    });
</script>
