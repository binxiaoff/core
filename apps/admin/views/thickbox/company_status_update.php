<div id="popup" style="width: 470px;">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Passage en statut &laquo;&nbsp;<?= $this->statusLabel ?>&nbsp;&raquo;</h1>
    <form id="problematic_status_form" method="post" action="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clientId ?>">
        <label for="decision_date"><em>Date du jugement</em></label><br/><br/>
        <input type="text" id="decision_date" name="decision_date" class="input_dp" value="<?= date('d/m/Y') ?>"/>
        <br/><br/><br/>
        <label for="receiver"><em>Coordonnées du mandataire judiciaire</em></label><br/><br/>
        <textarea id="receiver" name="receiver" class="textarea_lng" style="height: 100px;width: 420px;"><?= false ===empty($this->previousReceiver) ? $this->previousReceiver : '' ?></textarea>
        <br/><br/>
        <input type="hidden" name="send_email" value="1"/>
        <label for="mail_content"><em>Email d'information aux prêteurs</em></label><br/><br/>
        <textarea id="mail_content" name="mail_content" class="textarea_lng" style="height: 100px;width: 420px;"></textarea>
        <br/><br/>

        <label for="site_content"><em>Message d'information aux prêteurs (site)</em></label><br/><br/>
        <textarea id="site_content" name="site_content" class="textarea_lng" style="height:100px; width:420px;"></textarea>
        <br/><br/>
        <em>Envoyer un email d'information à l'emprunteur</em><br/><br/>
        <label><input type="radio" name="send_email_borrower" value="1"/> Oui</label>
        <label><input type="radio" name="send_email_borrower" value="0"/> Non</label><br/><br/>

        <div id="problematic_status_error" style="display: none;">Vous devez saisir tous les champs<br/><br/></div>
        <div style="text-align:right">
            <input type="hidden" name="problematic_status" value="<?= $this->companyStatusId ?>"/>
            <button type="submit" class="btn-primary">Sauvegarder</button>
        </div>
    </form>
</div>
<script type="text/javascript">
    $('#decision_date').datepicker({
        showOn: 'both',
        buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
        buttonImageOnly: true,
        changeMonth: true,
        changeYear: true,
        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
    });

    $('#problematic_status_form').submit(function (e) {
        if ('' == $('#decision_date').val()
            || '' == $('#receiver').val()
            || undefined == $('[name=send_email_borrower]:checked').val()
            || 1 == $('[name=send_email]:checked').val() && '' == $('#mail_content').val()
            || '' == $('#site_content').val()
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
