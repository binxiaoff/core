<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {3: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
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
                        <div style="color:red"><?= $this->retour ?></div>
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
