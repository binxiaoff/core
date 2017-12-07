<div id="popup" style="width: 470px;">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Passage en statut &laquo;&nbsp;<?= $this->statusLabel ?>&nbsp;&raquo;</h1>
    <form id="problematic-status-form" method="post" action="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clientId ?>">
        <div class="form-group">
            <label for="decision-date">Date du jugement</label>
            <input type="text" id="decision-date" name="decision_date" class="form-control" value="<?= date('d/m/Y') ?>">
        </div>
        <div class="form-group">
            <label for="receiver">Coordonnées du mandataire judiciaire</label>
            <textarea id="receiver" name="receiver" class="form-control" style="height: 100px;"><?= empty($this->previousReceiver) ? '' : $this->previousReceiver ?></textarea>
        </div>
        <div class="form-group">
            <label for="mail-content">Email d'information aux prêteurs</label>
            <textarea id="mail-content" name="mail_content" class="form-control" style="height: 100px;"></textarea>
        </div>
        <div class="form-group">
            <label for="site-content">Message d'information aux prêteurs (site)</label>
            <textarea id="site-content" name="site_content" class="form-control" style="height:100px;"></textarea>
        </div>
        <div id="problematic_status_error" style="display: none;">
            Vous devez saisir tous les champs
        </div>
        <div class="text-right">
            <input type="hidden" name="problematic_status" value="<?= $this->companyStatusId ?>">
            <button type="submit" class="btn-primary">Valider</button>
        </div>
    </form>
</div>
<script>
    $('#decision-date').datepicker({
        showOn: 'both',
        buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
        buttonImageOnly: true,
        changeMonth: true,
        changeYear: true,
        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
    });

    $('#problematic-status-form').submit(function (e) {
        if (
            '' === $('#decision-date').val()
            || '' === $('#receiver').val()
            || '' === $('#mail-content').val()
            || '' === $('#site-content').val()
        ) {
            e.preventDefault();

            $('#problematic_status_error').slideDown(function () {
                $.colorbox.resize();
            });

            setTimeout(function () {
                $('#problematic_status_error').slideUp(function () {
                    $.colorbox.resize();
                });
            }, 3000);
        }
    });
</script>
