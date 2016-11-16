<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a><br>
    <h3 style="white-space: nowrap;"><?= $this->message ?></h3>
    <div style="text-align:center;">
        <button type="button" class="btn" onclick="parent.$.fn.colorbox.close();confirmCheckboxChange($('#tax_exemption_<?= $this->params[0]?>'));">Confirmer</button>
        <button type="button" class="btn" onclick="parent.$.fn.colorbox.close();">Annuler</button>
    </div>
</div>
<script type="text/javascript">
    function confirmCheckboxChange(element) {
        element.prop("checked", !element.prop("checked"));
        return;
    }
</script>
