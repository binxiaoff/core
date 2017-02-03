<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {};

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Statistiques des AutoLends</h1>
    <form method="post" name="recupCSV">
        <input type="hidden" name="extraction_csv"/>
        <input type="hidden" name="date_from" value="<?= isset($_POST['date_from'])? $_POST['date_from'] : '' ?>"/>
        <input type="hidden" name="date_to" value="<?= isset($_POST['date_to'])? $_POST['date_to'] : '' ?>"/>
    </form>
    <div style="margin-bottom:20px; float:right;"><a onClick="document.forms['recupCSV'].submit();" class="btn colorAdd">Recuperation du CSV</a></div>
    <div style="width:500px; background-color: white; border: 1px solid #A1A5A7; border-radius: 10px; margin: 0 auto 20px; padding: 5px;">
        <form method="post" name="date_select">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <td style="padding-top:23px;"><label>Date debut</label><br/><input type="text" name="date_from" id="datepik_1" class="input_dp" value="<?= isset($_POST['date_from'])? $_POST['date_from'] : '' ?>"/></td>
                        <td style="padding-top:23px;"><label>Date fin</label><br/><input type="text" name="date_to" id="datepik_2" class="input_dp" value="<?= isset($_POST['date_to'])? $_POST['date_to'] : '' ?>"/></td>

                        <td style="padding-top:23px;">
                            <input type="hidden" name="search" id="search"/>
                            <input type="submit" value="Valider" title="Valider" name="send_dossier" id="send_dossier" class="btn"/>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="8" style="">

                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?php if (isset($this->aProjectList) && count($this->aProjectList) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>% d'AutoLend</th>
                    <th>Période</th>
                    <th>Risk</th>
                    <th>Nombre de bids</th>
                    <th>Montant moyen</th>
                    <th>Taux moyen pondéré</th>
                    <th>Montant moyen d'AutoLend</th>
                    <th>Taux moyen pondéré d'AutoLend</th>
                    <th>Status de project</th>
                    <th>Date fin de projet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->aProjectList as $iIndex => $aProject) : ?>
                    <tr<?= ($iIndex % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['percentage'] ?></td>
                        <td><?= $aProject['period'] ?></td>
                        <td><?= $aProject['risk'] ?></td>
                        <td><?= $aProject['bids_nb'] ?></td>
                        <td><?= $aProject['avg_amount'] ?></td>
                        <td><?= $aProject['weighted_avg_rate'] ?></td>
                        <td><?= $aProject['avg_amount_autobid'] ?></td>
                        <td><?= $aProject['weighted_avg_rate_autobid'] ?></td>
                        <td><?= $aProject['status_label'] ?></td>
                        <td><?= $aProject['date_fin'] ?></td>
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
    <?php else : ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
