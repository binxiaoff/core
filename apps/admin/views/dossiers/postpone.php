<div id="popup" class="postpone-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" id="postpone_form" action="<?= $this->lurl ?>/dossiers/postpone/<?= $this->projects->id_project ?>">
        <h1>Reporter un dossier</h1>
        <fieldset>
            <table class="form">
                <tr>
                    <th><label for="postpone_comment">Mémo&nbsp;*</label></th>
                    <td><textarea name="comment" id="postpone_comment" cols="75" rows="5" class="textarea" style="width: 480px" autofocus></textarea></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: right">
                        <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
                        <input type="submit" value="Valider" class="btn">
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
<script>
    $(function() {
        $('#postpone_form').submit(function(event) {
            if (! $('#postpone_comment').val()) {
                event.preventDefault()
                alert('Vous devez obligatoirement saisir un mémo')
            }
        })
    })
</script>
