<div id="contenu">
    <h1>Detail prescripteur : <?= $this->clients->nom . ' ' . $this->clients->prenom ?></h1>
    <?php if (false === empty($_SESSION['error_email_exist'])) : ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?php unset($_SESSION['error_email_exist']); ?>
    <?php endif; ?>
    <form method="post" name="edit_prescripteur" id="edit_prescripteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/prescripteurs/edit/<?= $this->prescripteurs->id_prescripteur ?>" target="_parent">
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th>Civilité :</th>
                <td colspan="3">
                    <input <?= $this->clients->civilite == 'Mme' ? 'checked' : '' ?> type="radio" name="civilite" id="civilite_mme" value="Mme"/>
                    <label for="civilite_mme">Madame</label>

                    <input <?= $this->clients->civilite == 'M.' ? 'checked' : '' ?> type="radio" name="civilite" id="civilite_m" value="M."/>
                    <label for="civilite_m">Monsieur</label>
                </td>
            </tr>
            <tr>
                <th><label for="nom">Nom :</label></th>
                <td><input type="text" name="nom" id="nom" class="input_large" value="<?= $this->clients->nom ?>"/></td>
                <th><label for="prenom">Prénom :</label></th>
                <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?= $this->clients->prenom ?>"/></td>
            </tr>
            <tr>
                <th><label for="email">Email :</label></th>
                <td><input type="text" name="email" id="email" class="input_large" value="<?= $this->clients->email ?>"/></td>
                <th><label for="telephone">Téléphone :</label></th>
                <td><input type="text" name="telephone" id="telephone" class="input_large" value="<?= $this->clients->telephone ?>"/></td>
            </tr>
            <tr>
                <th><label for="adresse">Adresse :</label></th>
                <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value="<?= $this->clients_adresses->adresse1 ?>"/></td>
            </tr>
            <tr>
                <th><label for="cp">Code postal :</label></th>
                <td><input type="text" name="cp" id="cp" class="input_large" value="<?= $this->clients_adresses->cp ?>"/></td>
                <th><label for="ville">Ville :</label></th>
                <td><input type="text" name="ville" id="ville" class="input_large" value="<?= $this->clients_adresses->ville ?>"/></td>
            </tr>
            <tr>
                <th><label for="company_name">Raison sociale :</label></th>
                <td><input type="text" name="company_name" id="company_name" class="input_large" value="<?= $this->companies->name ?>"/></td>
                <th><label for="siren">Siren :</label></th>
                <td><input type="text" name="siren" id="siren" class="input_large" value="<?= $this->companies->siren ?>"/></td>
            </tr>
            <tr>
                <th><label for="iban">IBAN :</label></th>
                <td><input type="text" name="iban" id="iban" class="input_large" value="<?= $this->companies->iban ?>"/></td>
                <th><label for="bic">BIC :</label></th>
                <td><input type="text" name="bic" id="bic" class="input_large" value="<?= $this->companies->bic ?>"/></td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="form_edit_prescripteur" id="form_edit_prescripteur" />
                    <input type="submit" value="Valider" title="Valider" name="send_edit_prescripteur" id="send_edit_prescripteur" class="btn" />
                </th>
            </tr>
        </table>
    </form>
</div>
<div style="margin: 30px auto; padding: 10px 20px 20px; width: 1160px; background-color: #fff; text-align: left;">
    <h1>Liste des dossiers<?= $this->iProjectsCount > 0 ? ' (' . $this->iProjectsCount . ' résultat' . ($this->iProjectsCount == 1 ? '' : 's') . ')' : '' ?></h1>
    <?php if ($this->iProjectsCount > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Siren</th>
                    <th>Raison sociale</th>
                    <th>Date demande</th>
                    <th>Date modification</th>
                    <th>Montant</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th>Analyste</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->aProjects as $p) : ?>
                    <?php $this->users->get($p['id_analyste'], 'id_user'); ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> id="ledossier<?= $p['id_project'] ?>">
                        <td><?= $p['id_project'] ?></td>
                        <td><?= $p['siren'] ?></td>
                        <td><?= $p['name'] ?></td>
                        <td><?= $this->dates->formatDate($p['added'], 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($p['updated'], 'd/m/Y') ?></td>
                        <td><?= $this->ficelle->formatNumber($p['amount']) ?> €</td>
                        <td><?= (in_array($p['period'], array(0, 1000000))) ? 'Je ne sais pas' : $p['period'] . ' mois' ?></td>
                        <td><?= $p['label'] ?></td>
                        <td><?= $this->users->firstname ?> <?= $this->users->name ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $p['title'] ?>"/>
                            </a>
                            <script>
                                $("#ledossier<?= $p['id_project']?> ").click(function () {
                                    $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>');
                                });
                            </script>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        Aucun dossier
    <?php endif; ?>
</div>
