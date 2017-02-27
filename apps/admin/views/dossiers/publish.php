<div id="popup" class="publish-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" id="publish_form" action="<?= $this->lurl ?>/dossiers/publish/<?= $this->projects->id_project ?>">
        <h1>Publier un dossier</h1>
        <fieldset>
            <table class="form">
                <tr>
                    <th><label for="date_publication">Date de publication&nbsp;*</label></th>
                    <td>
                        <input type="text" name="date_publication" id="date_publication" class="input_dp" value="<?= ($this->projects->date_publication != '0000-00-00 00:00:00' ? $this->dates->formatDate($this->projects->date_publication, 'd/m/Y') : '') ?>">
                        <select name="date_publication_heure" class="selectMini" title="Heure">
                            <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                                <option value="<?= sprintf('%02d', $hour) ?>"<?= (substr($this->projects->date_publication, 11, 2) == $hour ? ' selected' : '') ?>><?= sprintf('%02d', $hour) ?></option>
                            <?php endfor; ?>
                        </select>&nbsp;h
                        <select name="date_publication_minute" class="selectMini" title="Minute">
                            <?php for ($minute = 0; $minute < 60; $minute += 5) : ?>
                                <option value="<?= sprintf('%02d', $minute) ?>"<?= (substr($this->projects->date_publication, 14, 2) == $minute ? ' selected' : '') ?>><?= sprintf('%02d', $minute) ?></option>
                            <?php endfor; ?>
                        </select>&nbsp;m
                    </td>
                </tr>
                <tr>
                    <th><label for="date_retrait">Date de retrait&nbsp;*</label></th>
                    <td>
                        <input type="text" name="date_retrait" id="date_retrait" class="input_dp" value="<?= ($this->projects->date_retrait != '0000-00-00 00:00:00' ? $this->dates->formatDate($this->projects->date_retrait, 'd/m/Y') : '') ?>">
                        <select name="date_retrait_heure" class="selectMini" title="Heure">
                            <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                                <option value="<?= sprintf('%02d', $hour) ?>"<?= (substr($this->projects->date_retrait, 11, 2) == $hour ? ' selected' : '') ?>><?= sprintf('%02d', $hour) ?></option>
                            <?php endfor; ?>
                        </select>&nbsp;h
                        <select name="date_retrait_minute" class="selectMini" title="Minute">
                            <?php for ($minute = 0; $minute < 60; $minute += 5) : ?>
                                <option value="<?= sprintf('%02d', $minute) ?>"<?= (substr($this->projects->date_retrait, 14, 2) == $minute ? ' selected' : '') ?>><?= sprintf('%02d', $minute) ?></option>
                            <?php endfor; ?>
                        </select>&nbsp;m
                    </td>
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
        $('#date_publication').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: 0
        })

        $('#date_retrait').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: 0
        })

        $('#publish_form').submit(function(event) {
            if (! $('#date_publication').val() || ! $('#date_retrait').val()) {
                alert('Vous devez saisir la date de publication et la date de retrait')
                event.preventDefault()
            }
        })
    })
</script>
