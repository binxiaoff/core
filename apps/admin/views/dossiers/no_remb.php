<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter({headers: {10: {sorter: false}}});
        <?php if ($this->nb_lignes != ''): ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $("#Reset").click(function() {
            $("#siren").val('');
            $("#nom").val('');
            $("#societe").val('');
            $("#prenom").val('');
            $("#email").val('');
            $("#projet").val('');
        });

        <?php if (isset($_SESSION['freeow'])): ?>
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
<style>
    table.formColor {width: 673px;}
    .select {width: 251px;}
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/dossiers" title="Dossiers">Dossiers</a> -</li>
        <li>Incidents de remboursement</li>
    </ul>
    <?php if (isset($_POST['form_search_remb'])): ?>
        <h1>Résultats de la recherche des incidents de remboursements <?= (count($this->lProjects) > 0 ? '(' . count($this->lProjects) . ')' : '') ?></h1>
    <?php else: ?>
        <h1>Liste des <?= count($this->lProjects) ?> derniers incidents de remboursements</h1>
    <?php endif; ?>
    <div style="width:673px; background-color:white; border:1px solid #A1A5A7; border-radius:10px; margin:0 auto 20px; padding:5px;">
        <form method="post" name="search_remb" id="search_remb" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/no_remb" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="siren">Siren :</label></th>
                        <td><input type="text" name="siren" id="siren" class="input_large" value="<?= isset($_POST['siren']) ? $_POST['siren'] : '' ?>"/></td>
                        <th><label for="societe">Société :</label></th>
                        <td><input type="text" name="societe" id="societe" class="input_large" value="<?= isset($_POST['societe']) ? $_POST['societe'] : '' ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="nom">Nom :</label></th>
                        <td><input type="text" name="nom" id="nom" class="input_large" value="<?= isset($_POST['nom']) ? $_POST['nom'] : '' ?>"/></td>
                        <th><label for="prenom">Prénom :</label></th>
                        <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?= isset($_POST['prenom']) ? $_POST['prenom'] : '' ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="projet">Projet :</label></th>
                        <td><input type="text" name="projet" id="projet" class="input_large" value="<?= isset($_POST['projet']) ? $_POST['projet'] : '' ?>"/></td>
                        <th><label for="email">Email :</label></th>
                        <td><input type="text" name="email" id="email" class="input_large" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>"/></td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:center;">
                            <input type="hidden" name="form_search_remb" id="form_search_remb"/>
                            <input type="submit" value="Valider" title="Valider" name="send_remb" id="send_remb" class="btn"/>
                            <input style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;" type="button" value="Reset" title="Reset" name="Reset" id="Reset" class="btn"/>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?php if (count($this->lProjects) > 0): ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Société</th>
                    <th>Projet</th>
                    <th>Statut projet</th>
                    <th>Montant</th>
                    <th>Date</th>
                    <th>Auto</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($this->lProjects as $r) {
                $this->projects->get($r['id_project'], 'id_project');
                $this->companies->get($this->projects->id_company, 'id_company');
                $this->clients->get($this->companies->id_client_owner, 'id_client');

                $datePremiereEcheance = $this->echeanciers->getDatePremiereEcheance($r['id_project']);
                $rembTotal            = $this->echeanciers->getRembTotalEmprunteur($r['id_project']);
                $prochainRemb         = $this->echeanciers_emprunteur->select('id_project = ' . $r['id_project'] . ' AND status_emprunteur = 0', 'ordre ASC');

                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>"><?= $this->projects->id_project ?></a></td>
                    <td><?= $this->clients->nom ?></td>
                    <td><?= $this->clients->prenom ?></td>
                    <td><?= $this->clients->email ?></td>
                    <td><?= $this->companies->name ?></td>
                    <td><?= $this->projects->title_bo ?></td>
                    <td><?= $r['status_label'] ?></td>
                    <td style="text-align:right;"><?= $this->ficelle->formatNumber(($prochainRemb[0]['montant'] + $prochainRemb[0]['commission'] + $prochainRemb[0]['tva']) / 100) ?>&nbsp;&euro;</td>
                    <td><?= $this->dates->formatDate($prochainRemb[0]['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                    <td><?= ($this->projects->remb_auto == 1 ? 'Non' : 'Oui') ?></td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->projects->id_project ?>">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="detail"/>
                        </a>
                    </td>
                </tr>
                <?php
                $i++;
            }
            ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != ''): ?>
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
    <?php endif; ?>
</div>
