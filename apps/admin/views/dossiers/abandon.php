<div id="popup" class="abandon-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" id="abandon_form" action="<?= $this->lurl ?>/dossiers/abandon/<?= $this->projects->id_project ?>">
        <h1>Abandonner un dossier</h1>
        <fieldset>
            <table class="form">
                <tr>
                    <th><label for="comment">Motif&nbsp;*</label></th>
                    <td>
                        <select name="reason" id="reason" class="select">
                            <option value=""></option>
                            <?php $reasons = $this->loadData('project_abandon_reason'); ?>
                            <?php $reasons = $reasons->select('', 'label'); ?>
                            <?php foreach ($reasons as $reason) : ?>
                                <option value="<?= $reason['id_abandon'] ?>"><?= $reason['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="comment">Mémo</label></th>
                    <td><textarea id="comment" name="comment" cols="75" rows="5" class="textarea" style="width: 480px"></textarea></td>
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
        $('#abandon_form').submit(function(event) {
            if (! $('#reason').val()) {
                event.preventDefault()
                alert('Vous devez obligatoirement saisir un motif')
            }
        })
    })
</script>
