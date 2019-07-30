<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn" id="closeButton"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <form method="post" name="envoi_params" id="envoi_params" enctype="multipart/form-data"
          action="<?= $this->lurl ?>/queries/<?= isset($this->params[1]) && $this->params[1] == 'export' ? 'export' : 'execute' ?>/<?= $this->queries->id_query ?>" target="_parent">
        <h1>Paramètres de <?= $this->queries->name ?></h1>
        <fieldset>
            <table class="formColor">
                <?php foreach ($this->sqlParams as $param) : ?>
                    <tr>
                        <th>
                            <label for="param_<?= str_replace('@', '', $param[0]) ?>"><?= str_replace('@', '', $param[0]) ?> :</label></th>
                        <td>
                            <input type="text" name="param_<?= str_replace('@', '', $param[0]) ?>" id="param_<?= str_replace('@', '', $param[0]) ?>" class="input_large"/>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_envoi_params" id="form_envoi_params"/>
                        <button type="submit" id="send_params" class="btn-primary">Valider</button>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>

<?php if (isset($this->params[1]) && $this->params[1] == 'export') : ?>
    <script>
        $('#send_params').click(function(){
            $('#closeButton').click();
        });
    </script>
<?php endif; ?>

