
<script type="text/javascript">

    function annulerAttribution(id_client, id_reception)
    {
        var conf = confirm("Voulez vous vraiment annuler le virement ?");
        if (conf == true)
        {

            var val = {
                id_client: id_client,
                id_reception: id_reception
            }

            $.post(add_url + '/ajax/annulerAttribution', val).done(function (data) {


                if (data != 'nok')
                {
                    $(".num_client_" + id_reception).html('0');
                    $(".ajouter_" + id_reception).show();
                    $(".annuler_" + id_reception).hide();
                    $(".statut_virement_" + id_reception).html('Recu');
                    /*setTimeout(function() {
                     $(".reponse").slideUp();
                     }, 3000);*/
                }
            });

        }
    }

    $(document).ready(function () {

        jQuery.tablesorter.addParser({id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"});

        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});
<?
if ($this->nb_lignes != '') {
    ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
    <?
}
?>
        $(".inline").colorbox({inline: true, width: "50%"});

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
}
?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/transferts" >Dépot de fonds</a> - </li>
        <li>Virements</li>
    </ul>
    <h1>Liste des Virements des remboursements emprunteur</h1>
    <?
    if (count($this->lvirements) > 0) {
        ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>motif</th>
                    <th>montant</th>
                    <th>Statut</th>
                    <th>id projet</th>
                    <th>Date</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($this->lvirements as $v) {
                    $infos = '';
                    if (in_array($v['type_remb'], array(2, 3))) {
                        $this->receptions->get($v['id_reception'], 'id_parent');
                        // Regularisation
                        if ($v['type_remb'] == 2) {
                            $infos = '(' . $this->types_remb[$v['type_remb']] . ' - prélèvement id : ' . $this->receptions->id_reception . ')';
                        }
                        // Recouvrement
                        else {
                            $infos = '<a style="text-decoration:underline;" target="_blanck" href="'.$this->lurl.'/transferts/recouvrement/'.$this->receptions->id_reception.'">(' . $this->types_remb[$v['type_remb']] . ' - prélèvement id : ' . $this->receptions->id_reception . ')</a>';
                        }
                    }
                    // RA
                    elseif ($v['type_remb'] == 1) {
                        $infos = '(' . $this->types_remb[$v['type_remb']] . ')';
                    }
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> >
                        <td><?= $v['id_reception'] ?></td>
                        <td><?= $v['motif'] ?></td>
                        <td><?= $this->ficelle->formatNumber($v['montant'] / 100) ?></td>

                        <td class="statut_virement_<?= $v['id_reception'] ?>" >
                            <?php /* ?><?=($v['id_client'] != 0?'Attribué':$this->statusVirement[$v['status_virement']])?><?php */ ?>
                            <?= $this->statusVirement[$v['status_bo']] ?>
                            <?= $infos ?>
                        </td>


                        <td class="num_client_<?= $v['id_reception'] ?>"><?= $v['id_project'] ?></td>
                        <td><?= date('d/m/Y', strtotime($v['added'])) ?></td>
                        <td align="center">
                            <?php
                            if ($v['id_project'] == 0) {
                                ?>
                                <a class="thickbox ajouter_<?= $v['id_reception'] ?>" href="<?= $this->lurl ?>/transferts/attribution_emprunteur/<?= $v['id_reception'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Attibution" />
                                </a>
                                <?php
                            }
                            ?>
                            <a class='inline' href="#inline_content_<?= $v['id_reception'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Ligne" />
                            </a>
                        </td>
                    </tr>
                    <?
                    $i++;
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
    } else {
        ?>

        <p>Il n'y a aucun virement recu.</p><?
}
    ?>
</div>