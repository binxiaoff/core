<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers:{7: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <h1>Liste des Prêteurs à matcher</h1>
    <?php if (isset($this->aLenders) && count($this->aLenders) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID Client</th>
                    <th>Ville de Naissance</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->aLenders as $i => $aLender) : ?>
                    <tr<?=($i % 2 == 1 ? '' : ' class="odd"')?> id="row_"<?= $aLender['id_client'] ?>>
                        <td><?= $aLender['id_client'] ?></td>
                        <td id="td_city_<?= $aLender['id_client'] ?>"><?= $aLender['ville_naissance'] ?></td>
                        <td><?= $aLender['nom'] ?></td>
                        <td><?= $aLender['prenom'] ?></td>
                        <td align="center">
                            <a href="#" class="edit_lender" data-clientId="<?= $aLender['id_client'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $aLender['nom'] . ' ' . $aLender['prenom'] ?>" />
                            </a>
                        </td>
                    </tr>
                    <tr id="edit_lenders_<?= $aLender['id_client'] ?>" style="display: none">
                        <td colspan="3">
                            <label for="city_<?= $aLender['id_client'] ?>">Ville ou CP de Naissance :</label>
                            <input type="text" class="input_large" name="city" id="city_<?= $aLender['id_client'] ?>" data-autocomplete="birth_city" >
                            <input type="hidden" name="insee_birth" id="insee_<?= $aLender['id_client'] ?>">
                        </td>
                        <td><a href="#" class="save_lender btn_link" data-clientId="<?= $aLender['id_client'] ?>">Sauvegarder</a></td>
                        <td><a href="#" class="close_edit btn_link" data-clientId="<?= $aLender['id_client'] ?>">Fermer</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php elseif (isset($_POST['form_search_emprunteur'])) : ?>
        <p>Il n'y a aucun prêteur à matcher.</p>
    <?php endif; ?>
</div>
<script>
    $('.edit_lender').click(function(e){
        e.preventDefault();
        var clientId = $(this).data('clientid');
        $('#edit_lenders_'+clientId).show("slow");
        initAutocompleteCity($('#city_'+clientId), $('#insee_'+clientId));
    });

    $('.close_edit').click(function(e){
        e.preventDefault();
        var clientId = $(this).data('clientid');
        $('#edit_lenders_'+clientId).hide("fast");
    });

    $('.save_lender').click(function(e){
        e.preventDefault();
        var clientId = $(this).data('clientid');

        var insee = $('#insee_'+clientId).val();
        var city = $('#city_'+clientId).val();
        $.post(
            '<?= $this->lurl ?>/ajax/patchClient/' + clientId,
            {insee_birth: insee, ville_naissance: city}
        ).done(function(data){
            if (data == 'ok') {
                $('#td_city_'+clientId).html(city);
                $('#edit_lenders_'+clientId).hide("fast");
            } else {
                alert('Erreur, merci de réessayer.');
            }
        });
    });
</script>
