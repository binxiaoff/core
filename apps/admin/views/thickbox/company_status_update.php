<div id="popup" style="width: 470px;">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1><?= $this->statusLabel ?></h1>
    <form id="problematic_status_form" method="post" action="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clientId ?>">
        <div class="form-group">
            <label>Date du jugement</label>
            <input type="text" name="decision_date" value="<?= date('d/m/Y') ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Coordonnées du mandataire judiciaire</label>
            <textarea name="receiver" class="form-control"><?= false ===empty($this->previousReceiver) ? $this->previousReceiver : '' ?></textarea>
        </div>
        <div class="form-group">
            <label>Email d'information aux prêteurs</label>
            <input type="hidden" name="send_email" value="1"/>
            <textarea name="mail_content" class="form-control"><?= false ===empty($this->previousReceiver) ? $this->previousReceiver : '' ?></textarea>
        </div>
        <div class="form-group">
            <label>Message (notification) espace prêteur</label>
            <textarea name="site_content" class="form-control"><?= false ===empty($this->previousReceiver) ? $this->previousReceiver : '' ?></textarea>
        </div>
        <div class="form-group">
            <label>Email d'information à l'emprunteur ?</label> <br>
            <label><input type="radio" name="send_email_borrower" value="1"> Oui</label>
            <label style="margin-left: 10px;"><input type="radio" name="send_email_borrower" value="0"> Non</label>
        </div>
        <div id="problematic_status_error" style="display: none;" class="alert alert-danger">Vous devez saisir tous les champs<br/><br/></div>
        <div style="text-align:right">
            <input type="hidden" name="problematic_status" value="<?= $this->companyStatusId ?>"/>
            <button type="submit" class="btn-primary">Valider</button>
        </div>
    </form>
</div>
<script type="text/javascript">
    $('[name=decision_date]').datepicker({
        showOn: 'both',
        buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
        buttonImageOnly: true,
        changeMonth: true,
        changeYear: true,
        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
    });

    $('#problematic_status_form').submit(function (e) {
        if ('' == $('[name=decision_date]').val()
            || '' == $('[name=receiver]').val()
            || undefined == $('[name=send_email_borrower]:checked').val()
            || 1 == $('[name=send_email]').val() && '' == $('[name=mail_content]').val()
            || '' == $('[name=site_content]').val()
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
