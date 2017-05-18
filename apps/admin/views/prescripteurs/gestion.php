<script>
    $(function() {
        $(".tablesorter").tablesorter({headers:{7: {sorter: false}}});

        <?php  if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $("#Reset").click(function() {
            $("#nom").val('');
            $("#prenom").val('');
            $("#email").val('');
            $("#siren").val('');
            $("#company_name").val('');
            $('#status option[value="choisir"]').prop('selected', true);
        });
    });
</script>
<style>
    table.formColor{width: 100%;}
    .search-form-container{
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        margin: 0 auto 20px;
        padding: 5px;
    }
</style>
<div id="contenu">
    <div class="row">
        <div class="col-sm-6">
            <?php if (isset($_POST['form_search_prescripteur'])) : ?>
                <h1>Résultats de la recherche d'un prescripteur <?= count($this->aPrescripteurs) > 0 ? '(' . count($this->aPrescripteurs) . ')' : '' ?></h1>
            <?php elseif (isset($this->aPrescripteurs)) : ?>
                <h1>Liste des <?= count($this->aPrescripteurs) ?> derniers prescripteurs</h1>
            <?php endif; ?>
        </div>
        <div class="col-sm-6">
            <a href="<?= $this->lurl ?>/prescripteurs/add_client" class="btn-primary pull-right thickbox">Ajouter un prescripteur</a>
        </div>
    </div>
    <div class="search-form-container">
        <form method="post" name="search_prescripteurs" id="search_prescripteurs" enctype="multipart/form-data" action="<?= $this->lurl ?>/prescripteurs/gestion" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="nom">Nom&nbsp;:</label></th>
                        <td><input type="text" name="nom" id="nom" class="input_large" value="<?= $_POST['nom'] ?>"></td>
                        <th><label for="prenom">Prénom&nbsp;:</label></th>
                        <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?= $_POST['prenom'] ?>"></td>
                        <th><label for="email">Email&nbsp;:</label></th>
                        <td><input type="text" name="email" id="email" class="input_large" value="<?= $_POST['email'] ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="company_name">Raison sociale&nbsp;:</label></th>
                        <td><input type="text" name="company_name" id="company_name" class="input_large" value="<?= $_POST['company_name'] ?>"></td>
                        <th><label for="siren">SIREN&nbsp;:</label></th>
                        <td colspan="3"><input type="text" name="siren" id="siren" class="input_large" value="<?= $_POST['siren'] ?>"></td>
                    </tr>
                    <tr>
                        <th colspan="6" style="text-align:center;">
                            <input type="hidden" name="form_search_prescripteur" id="form_search_prescripteur">
                            <button type="submit" class="btn-primary" style="margin-right: 5px;">Rechercher</button>
                            <button type="submit" id="Reset" class="btn-default">Réinitialiser</button>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>

    <?php if (false === empty($this->aPrescripteurs)) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->aPrescripteurs as $c) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> id="prescripteur<?= $c['id_prescripteur'] ?>">
                        <td><?= $c['id_prescripteur'] ?></td>
                        <td><?= $c['nom'] ?></td>
                        <td><?= $c['prenom'] ?></td>
                        <td><?= $c['email'] ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/prescripteurs/edit/<?= $c['id_prescripteur'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $c['nom'] . ' ' . $c['prenom'] ?>">
                            </a>
                            <script>
                                $("#prescripteur<?= $c['id_prescripteur'] ?>").click(function() {
                                    $(location).attr('href','<?= $this->lurl ?>/prescripteurs/edit/<?= $c['id_prescripteur'] ?>');
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
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                        <input type="text" class="pagedisplay">
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php elseif (isset($_POST['form_search_prescripteurs'])) : ?>
        <p>Il n'y a aucun prescripteurs pour cette recherche.</p>
    <?php endif; ?>
</div>
