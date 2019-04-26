<?php

use Unilend\Entity\ProjectsStatus;

?>
<div id="popup" style="width: 433px;">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Passage en statut &laquo;&nbsp;<?= $this->projects_status->label ?>&nbsp;&raquo;</h1>
    <form id="problematic_status_form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->projectId ?>">
        <div class="form-group">
            <p><em>Envoyer un email d'information aux prêteurs</em></p>
            <label><input type="radio" name="send_email" value="1"> Oui</label>
            <label><input type="radio" name="send_email" value="0"> Non</label>
        </div>
        <div class="form-group hidden-fields">
            <label for="mail-content">Email d'information aux prêteurs</label>
            <textarea id="mail-content" name="mail_content" class="form-control" style="height: 100px;"><?= isset($this->mailInfoStatusChange) ? $this->mailInfoStatusChange : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="site-content">Message d'information aux prêteurs (site)</label>
            <textarea id="site-content" name="site_content" class="form-control" style="height:100px;"><?= isset($this->sInfoStatusChange) ? $this->sInfoStatusChange : ''; ?></textarea>
        </div>

        <?php if ($this->projects_status->status == ProjectsStatus::STATUS_LOST) : ?>
            <div class="form-group">
                <p><em>Envoyer un email d'information aux emprunteurs</em></p>
                <label><input type="radio" name="send_email_borrower" value="1"> Oui</label>
                <label><input type="radio" name="send_email_borrower" value="0"> Non</label>
            </div>
        <?php endif; ?>
        <div id="problematic_status_error">Vous devez saisir tous les champs</div>
        <div class="text-right">
            <input type="hidden" name="problematic_status" value="<?= $this->projects_status->status ?>">
            <button type="submit" class="btn-primary">Valider</button>
        </div>
    </form>
</div>
<script type="text/javascript">
    $('[name=send_email]').change(function () {
        if ('1' === $(this).val()) {
            $('.hidden-fields').slideDown(function () {
                $.colorbox.resize();
            });
        } else {
            $('.hidden-fields').slideUp(function () {
                $.colorbox.resize();
            });
        }
    });

    $('#problematic_status_form').submit(function (e) {
        if (
            '' === $('#site-content').val()
            || '' === $('[name=send_email]:checked').val()
            || '1' === $('[name=send_email]:checked').val() && '' === $('#mail-content').val()
            || $('[name=send_email_borrower]').length && '' === $('[name=send_email_borrower]:checked').val()
        ) {
            e.preventDefault();

            $("#problematic_status_error").slideDown(function () {
                $.colorbox.resize();
            });

            setTimeout(function () {
                $("#problematic_status_error").slideUp(function () {
                    $.colorbox.resize();
                });
            }, 3000);
        }
    });
</script>
