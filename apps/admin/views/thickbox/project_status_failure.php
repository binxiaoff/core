<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Passage en statut &laquo; Défaut &raquo;</h1>
    <form method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->iProjectId ?>">
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
            <input type="hidden" name="problematic_status" value="<?= \projects_status::DEFAUT ?>"/>
            <input type="submit" class="btn_link" value="Sauvegarder"/>
        </div>
    </form>
</div>
