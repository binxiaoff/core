<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"});

        $(".tablesorter").tablesorter();
        <?php if ($this->nb_lignes != '') { ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php } ?>

        <?php if (isset($_SESSION['freeow'])) { ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php }?>
    });
</script>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/dossiers" title="Dossiers">Dossiers</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers/remboursements" title="Remboursements">Remboursements</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->params[0] ?>" title="Detail remboursements">Detail remboursements</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers/detail_remb_preteur/<?= $this->params[0] ?>" title="Detail prêteur">Detail prêteur</a> -</li>
        <li>Detaile échéance prêteur</li>
    </ul>

    <h1>Liste des <?= count($this->lRemb) ?> derniers remboursements</h1>
    <br />
    <style>
        table.recap{border:2px solid #B10366;}
        table.recap td,table.recap th{padding:10px;border: 2px solid;}
    </style>
    <table class="recap">
        <tr>
            <th style="width:140px;">Total Com : </th>
            <td><?= $this->ficelle->formatNumber($this->commission / 100) ?> €</td>
        </tr>
        <tr>
            <th style="width:140px;">Commission / Mois : </th>
            <td><?= $this->ficelle->formatNumber($this->comParMois / 100) ?> €</td>
        </tr>
        <tr>
            <th style="width:140px;">Commission TTC / Mois : </th>
            <td><?= $this->ficelle->formatNumber($this->comTtcParMois / 100) ?> €</td>
        </tr>
        <tr>
            <th style="width:140px;">TVA : </th>
            <td><?= $this->ficelle->formatNumber($this->tva / 100) ?> €</td>
        </tr>
        <tr>
            <th style="width:140px;">Total TVA : </th>
            <td><?= $this->ficelle->formatNumber($this->totalTva / 100) ?> €</td>
        </tr>
    </table>
    <br />
    <?php if (count($this->lRemb) > 0) { ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Echeance</th>
                    <th style="width:70px;">Interets</th>
                    <th style="width:70px;">Capital</th>
                    <th style="width:70px;">Montant preteur (I+C)</th>
                    <th>Commission</th>
                    <th style="width:60px;">TVA</th>
                    <th style="width:70px;">Montant emprunteur (I+C+ComTTC)</th>
                    <th style="width:70px;">Capital restant</th>
                    <th>Date d'envoi du prélèvement</th>
                    <th>Date echeance Emprunteur</th>
                    <th>Date echeance Prêteur</th>
                    <th>Statut Emprunteur</th>
                    <th>Statut Prêteur</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $i = 1;
                $capRestant = $this->capital;
                foreach ($this->lRemb as $r) {
                    $montantEmprunteur = round($r['montant'] + $r['commission'] + $r['tva'], 2);

                    $capRestant -= $r['capital'];

                    if ($capRestant < 0) {
                        $capRestant = 0;
                    }

                    //on va récuperer la date d'envoi du prelevement, pour cela on doit lier la table echeancier_emp à prelevements, on utilisera la clé "Ordre + id_projet"
                    $date_envoi_prelevement = '';
                    $sStatus                = '';

                    if ($this->prelevements->get($r['id_project'], 'num_prelevement = ' . $r['ordre'] . ' AND id_project')) {
                        $date_envoi_prelevement = $this->dates->formatDate($this->prelevements->date_execution_demande_prelevement, 'd/m/Y');

                        switch ($this->prelevements->status) {
                            case \prelevements::STATUS_PENDING:
                                $sStatus = 'A venir';
                                break;
                            case \prelevements::STATUS_SENT:
                                $sStatus = 'Envoyé';
                                break;
                            case \prelevements::STATUS_VALID:
                                $sStatus = 'Validé';
                                break;
                            case \prelevements::STATUS_TERMINATED:
                                $sStatus = 'Terminé';
                                break;
                            case \prelevements::STATUS_TEMPORARILY_BLOCKED:
                                $sStatus = 'Bloqué temporairement';
                                break;
                            default:
                                $sStatus = 'Inconnu';
                                break;
                        }
                    }
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $r['ordre'] ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($r['montant'] / 100) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($r['commission'] / 100) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($r['tva'] / 100) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($montantEmprunteur / 100) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($capRestant / 100) ?></td>
                        <td><?= $date_envoi_prelevement ?></td>
                        <td><?= $this->dates->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($r['date_echeance_preteur'], 'd/m/Y') ?></td>
                        <td><?= $sStatus ?></td>
                        <td><?= $r['statut_preteur'] ?></td>
                    </tr>
                        <?php
                        $i++;
                    }
                    // ajout de la ligne du RA
                    if ($this->montant_ra > 0) {
                        ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $r['ordre'] + 1 ?></td>
                        <td style="text-align:right">0</td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($this->montant_ra) ?></td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($this->montant_ra) ?></td>
                        <td style="text-align:right">0</td>
                        <td style="text-align:right">0</td>
                        <td style="text-align:right"><?= $this->ficelle->formatNumber($this->montant_ra) ?></td>
                        <td style="text-align:right">0</td>
                        <td><?= $this->dates->formatDate($this->date_ra, 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($this->date_ra, 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($r['date_echeance_preteur'], 'd/m/Y') ?></td>
                        <td><?= $sStatus ?></td>
                        <td><?= $r['statut_preteur'] ?></td>
                    </tr>
                    <?php } ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') { ?>
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
        <?php } ?>
    <?php } ?>
</div>
