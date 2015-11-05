<script type="text/javascript">
    $(function () {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });

        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });

        $("#Reset").click(function () {
            $("#id").val('');
            $("#siren").val('');
            $("#datepik_1").val('');
            $("#datepik_2").val('');
            $('#montant option[value="0"]').attr('selected', true);
            $('#duree option[value="0"]').attr('selected', true);
            $('#status option[value="0"]').attr('selected', true);
            $('#analyste option[value="0"]').attr('selected', true);
        });

        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});
        <?php if ($this->nb_lignes != '') { ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php } ?>
    });
    <?php if (isset($_SESSION['freeow'])) { ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php } ?>
</script>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li>Dossiers en funding</li>
    </ul>
    <h1>Liste des <?= count($this->lProjects) ?> derniers dossiers en funding</h1>
    <?php if (count($this->lProjects) > 0) { ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Société</th>
                    <th>Prêt</th>
                    <th>Prêté</th>
                    <th>Pourcentage</th>
                    <th>Taux moyen</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($this->lProjects as $p) {
                $this->companies->get($p['id_company'], 'id_company');

                $soldeBid = $this->bids->getSoldeBid($p['id_project']);

                $montantHaut = 0;
                $montantBas  = 0;
                foreach ($this->bids->select('id_project = ' . $p['id_project'] . ' AND status = 0') as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
                $tauxMoyen = ($montantHaut / $montantBas);
                $pourcentage = (($soldeBid / $p['amount']) * 100);
                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $p['id_project'] ?></td>
                    <td><?= $this->companies->name ?></td>
                    <td><?= $this->ficelle->formatNumber($p['amount']) ?> €</td>
                    <td><?= number_format($soldeBid, 1, ',', ' ') ?> €</td>
                    <td><?= number_format($pourcentage, 1, ',', ' ') ?> %</td>
                    <td><?= number_format($tauxMoyen, 1, ',', ' ') ?> %</td>
                    <td align="center">
                        <a target="_blank" href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $p['title'] ?>"/>
                        </a>
                    </td>
                </tr>
                <?php
                $i++;
            }
            ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') { ?>
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
        <?php } ?>
    <?php } elseif (isset($_POST['form_search_emprunteur'])) { ?>
        <p>Il n'y a aucun dossier pour cette recherche.</p>
    <?php } ?>
</div>
<?php unset($_SESSION['freeow']); ?>
