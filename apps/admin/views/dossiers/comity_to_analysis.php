<div id="popup" class="comity-to-analysis-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" id="comity-to-analysis-form" action="<?= $this->lurl ?>/dossiers/comity_to_analysis/<?= $this->projects->id_project ?>">
        <h1>Retour à l'analyse</h1>
        <fieldset>
            <table class="form">
                <tr>
                    <th><label for="comment">Mémo&nbsp;*</label></th>
                    <td><textarea name="comment" id="comment" cols="75" rows="5" class="textarea memo" style="width: 480px"></textarea></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: right">
                        <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
                        <button type="submit" class="btn-primary">Valider</button>
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
<script>
    $(function() {
        $('#comity-to-analysis-form').submit(function(event) {
            if (! $('#comment').val()) {
                event.preventDefault()
                alert('Vous devez obligatoirement saisir un mémo')
            }
        })
    })
</script>
