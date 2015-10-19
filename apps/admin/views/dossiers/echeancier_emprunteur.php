<script type="text/javascript">
    $(document).ready(function () {

        jQuery.tablesorter.addParser({id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"});

        $(".tablesorter").tablesorter();
<?
if ($this->nb_lignes != '') {
    ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
    <?
}
?>

    });
<?
if (isset($_SESSION['freeow'])) {
    ?>
        $(document).ready(function () {
            var title, message, opts, container;
            title = "<?= $_SESSION['freeow']['title'] ?>";
            message = "<?= $_SESSION['freeow']['message'] ?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
    <?
    unset($_SESSION['freeow']);
}
?>
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
            <td><?= number_format($this->commission / 100, 2, ',', ' ') ?> €</td>
        </tr><tr>    
            <th style="width:140px;">Commission / Mois : </th>
            <td><?= number_format($this->comParMois / 100, 2, ',', ' ') ?> €</td>
        </tr><tr>    
            <th style="width:140px;">Commission TTC / Mois : </th>
            <td><?= number_format($this->comTtcParMois / 100, 2, ',', ' ') ?> €</td>
        </tr><tr>    
            <th style="width:140px;">TVA : </th>
            <td><?= number_format($this->tva / 100, 2, ',', ' ') ?> €</td>
        </tr><tr>   
            <th style="width:140px;">Total TVA : </th>
            <td><?= number_format($this->totalTva / 100, 2, ',', ' ') ?> €</td>
        </tr>
    </table>
    <br />
    <?
    if (count($this->lRemb) > 0) {
        ?>
        <table class="tablesorter">
            <thead>
                <tr> 
                    <th>Echeance</th>
                    <th>Interets</th>
                    <th>Capital</th>
                    <th>Montant preteur (I+C)</th>
                    <th>Commission</th>
                    <th>TVA</th>
                    <th>Montant emprunteur (I+C+ComTTC)</th>
                    <th>Capital restant</th>
                    <th>Date echeance Emprunteur</th>
                    <th>Date d'envoi du prélèvement</th>
                    <?php /* ?><th>Statut</th><?php */ ?>
                </tr>
            </thead>
            <tbody>
                <?
                $i = 1;
                $capRestant = $this->capital;
                foreach ($this->lRemb as $r) {
                    $montantEmprunteur = $this->echeanciers->getMontantRembEmprunteur($r['montant'], $r['commission'], $r['tva']);

                    $capRestant -= $r['capital'];
                    if ($capRestant < 0)
                        $capRestant = 0;

                    //on va récuperer la date d'envoi du prelevement, pour cela on doit lier la table echeancier_emp à prelevements, on utilisera la clé "Ordre + id_projet"
                    $date_envoi_prelevement = "";

                    if ($this->prelevements->get($r['id_project'], 'num_prelevement = ' . $r['ordre'] . ' AND id_project')) {

                        $date_envoi_prelevement = $this->dates->formatDate($this->prelevements->date_execution_demande_prelevement, 'd/m/Y');
                    }
                    ?>
                
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>

                        <td><?= $r['ordre'] ?></td>
                        <td><?= number_format($r['interets'] / 100, 2, ',', ' ') ?></td>
                        <td><?= number_format($r['capital'] / 100, 2, ',', ' ') ?></td>
                        <td><?= number_format($r['montant'] / 100, 2, ',', ' ') ?></td>
                        <td><?= number_format($r['commission'] / 100, 2, ',', ' ') ?></td>
                        <td><?= number_format($r['tva'] / 100, 2, ',', ' ') ?></td>
                        <td><?= number_format($montantEmprunteur / 100, 2, ',', ' ') ?></td>
                        <td><?= number_format($capRestant / 100, 2, ',', ' ') ?></td>
                        <td><?= $date_envoi_prelevement ?></td>
                        <td><?= $this->dates->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
        <?php /* ?><td><?=($r['status']==1?'Remboursé':'A venir')?></td><?php */ ?>
                    </tr>   
                        <?
                        $i++;
                    }
                    // ajout de la ligne du RA
                    if ($this->montant_ra > 0) {
                        ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>

                        <td><?= $r['ordre'] + 1 ?></td>
                        <td>0</td>
                        <td><?= number_format($this->montant_ra, 2, ',', ' ') ?></td>
                        <td><?= number_format($this->montant_ra, 2, ',', ' ') ?></td>
                        <td>0</td>
                        <td>0</td>
                        <td><?= number_format($this->montant_ra, 2, ',', ' ') ?></td>
                        <td>0</td>
                        <td><?= $this->dates->formatDate($this->date_ra, 'd/m/Y') ?></td>
        <?php /* ?><td><?=($r['status']==1?'Remboursé':'A venir')?></td><?php */ ?>
                    </tr>
                        <?php
                    }
                    ?>
            </tbody>
        </table>
                <?
                if ($this->nb_lignes != '') {
                    ?>
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
        <?
    }
    ?>
        <?
    }
    ?>
</div>
    <?php unset($_SESSION['freeow']); ?>
