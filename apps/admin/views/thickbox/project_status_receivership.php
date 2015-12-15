<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Passage en statut &laquo; Redressement judiciaire &raquo;</h1>
    <form method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->iProjectId ?>">
        <label for="decision_date"><i>Date du jugement</i></label><br/>
        <input type="text" id="decision_date" name="decision_date" class="input_dp" value="<?= date('d/m/Y') ?>" /><br/>
        <br/><br/>
        <label for="receiver"><i>Coordonnées du mandataire judiciaire</i></label><br/>
        <textarea id="receiver" name="receiver" class="textarea_lng" style="height: 100px;width: 420px;"></textarea>
        <br/><br/>
        <label for="mail_content"><i>Email d'information aux prêteurs</i></label><br/>
        <textarea id="mail_content" name="mail_content" class="textarea_lng" style="height: 100px;width: 420px;"></textarea>
        <br/><br/>
        <label for="site_content"><i>Message d'information aux prêteurs (site)</i></label><br/>
        <textarea id="site_content" name="site_content" class="textarea_lng" style="height:100px; width:420px;"></textarea>
        <br/><br/>
        <div style="text-align:right">
            <input type="hidden" name="problematic_status" value="<?= \projects_status::PROBLEME ?>"/>
            <input type="submit" class="btn_link" value="Sauvegarder"/>
        </div>
    </form>
</div>
<script>
    $('#decision_date').datepicker({
        showOn: 'both',
        buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
        buttonImageOnly: true,
        changeMonth: true,
        changeYear: true,
        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
    });
</script>
