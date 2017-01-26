<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if($this->nb_lignes != '') : ?>
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
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<style>
    .datepicker_table {
        width: 65%;
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
        /*width: 25%;*/
    }
</style>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Sources Emprunteurs</h1>

    <form method="post" name="recupCSV">
        <input type="hidden" name="extraction_csv"/>
        <input type="hidden" name="dateStart" value="<?= (false === empty($_POST['dateStart']) ? $_POST['dateStart'] : '' ) ?>"/>
        <input type="hidden" name="dateEnd" value="<?= (false === empty($_POST['dateEnd']) ? $_POST['dateEnd'] : '' ) ?>"/>
        <input type="hidden" name="queryOptions" value="<?= (false === empty($_POST['queryOptions']) ? $_POST['queryOptions'] : '' ) ?>" />
    </form>
    <div class="csv">
        <a onclick="document.forms['recupCSV'].submit();" class="btn colorAdd">Recuperation du CSV</a>
    </div>

    <div class="datepicker_table">
        <form method="post" name="date_select">
            <fieldset>
                <table class="search_fields">
                    <tr>
                        <td width = 20%>
                            <label>Date debut</label><br/>
                            <input type="text" name="dateStart" id="datepik_1" class="input_dp"
                                   value="<?= (false === empty($_POST['dateStart']) ? $_POST['dateStart'] : '' ) ?>"/>
                        </td>
                        <td width = 25%>
                            <label>Date fin</label><br/>
                            <input type="text" name="dateEnd" id="datepik_2" class="input_dp"
                                   value="<?= (false === empty($_POST['dateEnd']) ? $_POST['dateEnd'] : '' ) ?>"/>
                        </td>
                        <td width = 40%>
                            <input type="radio" name="queryOptions" value="allLines"
                                   <?= (isset($_POST['queryOptions'])) ? ('allLines' == $_POST['queryOptions']) ? 'checked="checked"' : '' : 'checked="checked"' ?>/>
                            Choisir toutes les lignes (relativement rapide)<br />
                            <input type="radio" name="queryOptions" value="groupBySiren"
                                   <?= (isset($_POST['queryOptions']) && 'groupBySiren' == $_POST['queryOptions']) ? 'checked="checked"' : '' ?>/>
                                Siren dédoublonnée (lent)<br />
                            <input type="radio" name="queryOptions" value="groupBySirenWithDetails"
                                   <?= (isset($_POST['queryOptions']) && 'groupBySirenWithDetails' == $_POST['queryOptions']) ? 'checked="checked"' : '' ?>/>
                                Siren dédoublonnée avec détail (très lent)<br />
                        </td>
                        <td width = 15%>
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
    <?php else : ?>
        <?php if (isset($_POST['queryOptions']) && in_array($_POST['queryOptions'], array('groupBySirenWithDetails', 'groupBySiren'))) : ?>
            <div style="margin: 25px 25px; background-color:#F2F258; padding: 5px 5px;">
                <span style="font-weight: bold; padding: 5px 5px;">
                Note: Les siren dédoublonnées affichent la ligne correspondante à la premiere occurence de ce siren. <br />
                    L'option "plus de détails" y rajoute les colonnes "Source première occurence", "Source dernière occurence" et "Dernier label".
                </span>
            </div>
        <?php endif; ?>
        <div class="table" style="width: 100%;">
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th>Id Projet</th>
                        <th>Siren</th>
                        <?php if(isset($_POST['queryOptions']) && in_array($_POST['queryOptions'], array('groupBySirenWithDetails', 'groupBySiren'))) : ?>
                        <th>Nombre d'occurences de ce Siren</th>
                        <?php endif; ?>
                        <th>Nom du client</th>
                        <th>Pr&eacute;nom du client</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Téléphone</th>
                        <th>Source</th>
                        <th>Source2</th>
                        <th>Date de création du projet</th>
                        <th>Project Status</th>
                        <?php if(isset($_POST['queryOptions']) && 'groupBySirenWithDetails' == $_POST['queryOptions']) : ?>
                            <th>Source première occurence</th>
                            <th>Source dernière occurence</th>
                            <th>Dernier label</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->aBorrowers as $aBorrower) : ?>
                        <tr>
                            <td><?= $aBorrower['id_project'] ?></td>
                            <td><?= $aBorrower['siren'] ?></td>
                            <?php if (isset($_POST['queryOptions']) && in_array($_POST['queryOptions'], array('groupBySirenWithDetails', 'groupBySiren'))) : ?>
                                <td><?= isset($aBorrower['countSiren']) ? $aBorrower['countSiren'] : '' ?></td>
                            <?php endif; ?>
                            <td><?= $aBorrower['nom'] ?></td>
                            <td><?= $aBorrower['prenom'] ?></td>
                            <td><?= $aBorrower['email'] ?></td>
                            <td><?= $aBorrower['mobile'] ?></td>
                            <td><?= $aBorrower['telephone'] ?></td>
                            <td><?= $aBorrower['source'] ?></td>
                            <td><?= $aBorrower['source2'] ?></td>
                            <td><?= $this->dates->formatDateMysqltoShortFR($aBorrower['added']) ?></td>
                            <td><?= $aBorrower['label'] ?></td>
                            <?php if (isset($_POST['queryOptions']) && 'groupBySirenWithDetails' == $_POST['queryOptions']) : ?>
                                <td><?= isset($aBorrower['firstEntrySource']) ? $aBorrower['firstEntrySource'] : '' ?></td>
                                <td><?= isset($aBorrower['lastEntrySource']) ? $aBorrower['lastEntrySource'] : ''  ?></td>
                                <td><?= isset($aBorrower['lastLabel']) ? $aBorrower['lastLabel'] : '' ?></td>
                            <?php endif; ?>
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
        </div>
    <?php endif; ?>
</div>
