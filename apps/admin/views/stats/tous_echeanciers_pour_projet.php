<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {3: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <h1>Tous les échéanciers du projet</h1>
    <form method="post" name="from_param_id_projet" id="from_param_id_projet" enctype="multipart/form-data" action="" target="_parent">
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="id_projet">ID projet :</label></th>
                    <td><input type="text" name="id_projet" id="id_projet" class="input_large"/></td>
                </tr>
                <tr>
                    <th>&nbsp;</th>
                    <td>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_envoi_params" id="form_envoi_params" value="ok"/>
                        <input type="submit" value="Valider" title="Valider" name="send_params" id="send_params" class="btn"/>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
