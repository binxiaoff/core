<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {7: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $("#Reset").click(function() {
            $("#siren").val('');
            $("#nom").val('');
            $("#societe").val('');
            $("#prenom").val('');
            $("#email").val('');
        });
    });
</script>
<style>
    .form-container {
        width: 697px;
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        margin: 0 auto 20px;
        padding: 5px;
    }
    table.formColor {
        width: 697px;
    }
</style>
<div id="contenu">
    <h1>Liste des <?= isset($this->lClients) ? count($this->lClients) : 0 ?> derniers emprunteurs</h1>
    <div class="form-container">
        <form method="post" name="search_emprunteurs" id="search_emprunteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/emprunteurs/gestion" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="siren">SIREN :</label></th>
                        <td>
                            <input type="text" name="siren" id="siren" class="input_large" value="<?= isset($_POST['siren']) ? $_POST['siren'] : '' ?>"/>
                        </td>
                        <th><label for="societe">Raison sociale :</label></th>
                        <td>
                            <input type="text" name="societe" id="societe" class="input_large" value="<?= isset($_POST['societe']) ? $_POST['societe'] : '' ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nom">Nom :</label></th>
                        <td>
                            <input type="text" name="nom" id="nom" class="input_large" value="<?= isset($_POST['nom']) ? $_POST['nom'] : '' ?>"/>
                        </td>
                        <th><label for="prenom">Prénom :</label></th>
                        <td>
                            <input type="text" name="prenom" id="prenom" class="input_large" value="<?= isset($_POST['prenom']) ? $_POST['prenom'] : '' ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="email">Email :</label></th>
                        <td colspan="3">
                            <input type="text" name="email" id="email" class="input_large" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:center;">
                            <input type="hidden" name="form_search_emprunteur" id="form_search_emprunteur"/>
                            <button type="submit" class="btn-primary" style="margin-right: 5px;">Rechercher</button>
                            <button type="submit" id="Reset" class="btn-default">Réinitialiser</button>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>

    <?php if (isset($this->lClients) && count($this->lClients) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Société</th>
                    <th>Montant cumulé</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->lClients as $c) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> id="emprunteur<?= $c['id_client'] ?>">
                    <td><?= $c['id_client'] ?></td>
                    <td><?= $c['nom'] ?></td>
                    <td><?= $c['prenom'] ?></td>
                    <td><?= $c['email'] ?></td>
                    <td><?= $c['name'] ?></td>
                    <td><?= $this->ficelle->formatNumber($this->clients->totalmontantEmprunt($c['id_client']), 0) ?></td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $c['id_client'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $c['nom'] . ' ' . $c['prenom'] ?>"/>
                        </a>
                        <script>
                            $("#emprunteur<?= $c['id_client'] ?>").click(function () {
                                $(location).attr('href', '<?= $this->lurl ?>/emprunteurs/edit/<?= $c['id_client'] ?>');
                            });
                        </script>
                    </td>
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
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
        <p>Il n'y a aucun emprunteur pour cette recherche.</p>
    <?php endif; ?>
</div>
