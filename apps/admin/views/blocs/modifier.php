<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        <?php foreach ($this->lLangues as $key => $lng) : ?>
            $("#datepik_<?= $key ?>").datepicker({
                showOn: 'both',
                buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                buttonImageOnly: true,
                changeMonth: true,
                changeYear: true,
                yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
            });
        <?php endforeach; ?>
    });
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
    <?php if (count($this->lLangues) > 1) : ?>
        <div id="onglets">
            <?php foreach ($this->lLangues as $key => $lng) : ?>
                <a onclick="changeOngletLangue('<?= $key ?>');" id="lien_<?= $key ?>" title="<?= $lng ?>" class="<?= ($key == $this->language ? 'active' : '') ?>"><?= $lng ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" name="edit_bloc" id="edit_bloc" enctype="multipart/form-data">
        <input type="hidden" name="id_bloc" id="id_bloc" value="<?= $this->blocs->id_bloc ?>"/>
        <input type="hidden" name="lng_encours" id="lng_encours" value="<?= $this->language ?>"/>
        <?php foreach ($this->lLangues as $key => $lng) : ?>
            <?php $this->lElements = $this->elements->select('status = 1 AND id_bloc = "' . $this->params[0] . '" AND id_bloc != 0', 'ordre ASC'); ?>
            <div id="langue_<?= $key ?>"<?= ($key != $this->language ? ' style="display:none;"' : '') ?>>
                <fieldset>
                    <?php if (count($this->lElements) > 0) : ?>
                        <h1>Modification du bloc <?= $this->blocs->name ?></h1>
                        <table class="large">
                            <?php foreach ($this->lElements as $element) : ?>
                                <?php $this->tree->displayFormElement($this->blocs->id_bloc, $element, 'bloc', $key); ?>
                            <?php endforeach; ?>
                        </table>
                        <table class="large">
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="form_edit_bloc" id="form_edit_bloc"/>
                                    <button type="submit" class="btn-primary">Valider</button>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </fieldset>
            </div>
        <?php endforeach; ?>
    </form>
</div>
