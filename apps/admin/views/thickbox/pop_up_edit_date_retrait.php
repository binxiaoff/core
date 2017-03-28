<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#date_de_retrait").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: 0
        });
    });
</script>
<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <form method="post" enctype="multipart/form-data" action="" target="_parent">
        <h1>Modifier la date de retrait</h1>
        <fieldset>
            <table class="form">
                <tr>
                    <td><label for="date_de_retrait">Date de retrait</label></td>
                    <td>
                        <input type="text" name="date_de_retrait" id="date_de_retrait" class="input_dp" value="<?= $this->date_retrait ?>">
                        <select name="date_retrait_heure" class="selectMini" title="Heure">
                            <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                                <option value="<?= sprintf('%02d', $hour) ?>"<?= ($this->heure_date_retrait == $hour ? ' selected' : '') ?>><?= sprintf('%02d', $hour) ?></option>
                            <?php endfor; ?>
                        </select>&nbsp;h
                        <select name="date_retrait_minute" class="selectMini" title="Minute">
                            <?php for ($minute = 0; $minute < 60; $minute += 5) : ?>
                                <option value="<?= sprintf('%02d', $minute) ?>"<?= ($this->minute_date_retrait == $minute ? ' selected' : '') ?>><?= sprintf('%02d', $minute) ?></option>
                            <?php endfor; ?>
                        </select>&nbsp;m
                    </td>
                    <td>
                        <input type="hidden" name="send_form_date_retrait">
                        <input type="submit" name="modifier" value="Valider" class="btn">
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
