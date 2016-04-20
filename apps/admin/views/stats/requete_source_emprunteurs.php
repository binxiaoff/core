<script type="text/javascript">
    $(document).ready(function () {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});
        <?php if($this->nb_lignes != '') : ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php endif; ?>

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });
        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

    });
    <?php if(isset($_SESSION['freeow'])) : ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php endif; ?>
</script>
<style>
    .datepicker_table {
        width: 650px;
        margin: 0 auto 20px;
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        padding: 5px;
        padding-bottom: 20px;
    }

    .csv {
        margin-bottom: 20px;
        float: right;
    }

    .search_fields td {
        padding-top: 23px;
        padding-left: 10px;
        width: 25%;
    }
</style>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Administrateurs</li>
    </ul>
    <h1>Etape d'inscription des utilisateurs</h1>
    <div class="csv">
        <a href="<?= $this->lurl ?>/stats/csv_requete_source_emprunteurs" class="btn colorAdd">Recuperation du CSV</a>
    </div>

    <div class="datepicker_table">
        <form method="post" name="date_select">
            <fieldset>
                <table class="search_fields">
                    <tr>
                        <td>
                            <label>Date debut</label><br/>
                            <input type="text" name="dateStart" id="datepik_1" class="input_dp"
                                   value="<?= (false === empty($_POST['dateStart'])) ? $_POST['dateStart'] : '' ?>"/>
                        </td>
                        <td>
                            <label>Date fin</label><br/>
                            <input type="text" name="dateEnd" id="datepik_2" class="input_dp"
                                   value="<?= (false === empty($_POST['dateEnd'])) ? $_POST['dateEnd'] : '' ?>"/>
                        </td>
                        <td>
                            <br><label>Siren dédoublonne</label>
                            <input type="checkbox" name="groupBySiren" />
                        </td>
                        <td>
                            <br>
                            <input type="hidden" name="spy_search" id="spy_search"/>
                            <input type="submit" value="Valider" title="Valider" name="send_query" id="send_query"
                                   class="btn"/>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?php if (empty($this->aBorrowers)) : ?>
        <p>Il n'y a aucun emprunteur pour le moment.</p>
    <?php else: ?>
        <div class="table">
            <table class="tablesorter">
                <thead>
                <tr>
                    <th>Id Projet</th>
                    <th>Siren</th>
                    <th>Nombre d'occurences de ce Siren</th>
                    <th>Nom du client</th>
                    <th>Pr&eacute;nom du client</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Téléphone</th>
                    <th>Source</th>
                    <th>Source 2</th>
                    <th>Project Status</th>
                    <th>Date de création du projet</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->aBorrowers as $aBorrower) : ?>
                    <tr>
                        <td><?= $aBorrower['id_project'] ?></td>
                        <td><?= $aBorrower['siren'] ?></td>
                        <td><?= isset($aBorrower['count_siren']) ? $aBorrower['count_siren'] : '' ?></td>
                        <td><?= $aBorrower['nom'] ?></td>
                        <td><?= $aBorrower['prenom'] ?></td>
                        <td><?= $aBorrower['email'] ?></td>
                        <td><?= $aBorrower['mobile'] ?></td>
                        <td><?= $aBorrower['telephone'] ?></td>
                        <td><?= $aBorrower['source'] ?></td>
                        <td><?= $aBorrower['source2'] ?></td>
                        <td><?= $this->dates->formatDateMysqltoShortFR($aBorrower['added']) ?></td>
                        <td><?= $aBorrower['label'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php unset($_SESSION['freeow']); ?>