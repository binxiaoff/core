<script>
    var nbPages = <?= isset($this->nb_lignes) && $this->nb_lignes > 0 ? ceil($this->iCountProjects / $this->nb_lignes) : 0 ?>;

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });

        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
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
            $("#raison-sociale").val('');
            $('#montant option[value="0"]').prop('selected', true);
            $('#duree option[value=""]').prop('selected', true);
            $('#status option[value=""]').prop('selected', true);
            $('#analyste option[value="0"]').prop('selected', true);
            $('#commercial option[value="0"]').prop('selected', true);
        });

        $('#raison-sociale').autocomplete({
          source: '<?= $this->url ?>/dossiers/autocompleteCompanyName/',
          minLength: 3,
          delay: 100
        });

        $(".tablesorter").tablesorter({headers: {9: {sorter: false}, 5: {sorter: 'digit'}}});

        $('#displayPager').html($('#pageActive').val() + '/' + nbPages);

        $('#send_dossier').click(function () {
            $('#nbLignePagination').val(0);
            $('#pageActive').val(1);
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

    function paginationDossiers(directionPagination) {
        var nbLignePagination = Math.round($('#nbLignePagination').val());
        var pageActive = Math.round($('#pageActive').val());
        var totalLignePagination = <?= $this->iCountProjects - $this->nb_lignes ?>;
        switch (directionPagination) {
            case 'first':
                $('#nbLignePagination').val(0);
                $('#pageActive').val(1);
                break;
            case 'prev':
                if (nbLignePagination > <?= $this->nb_lignes ?>) {
                    nbLignePagination = nbLignePagination -<?= $this->nb_lignes ?>;
                    $('#pageActive').val(pageActive - 1);
                }
                $('#nbLignePagination').val(nbLignePagination);
                break;
            case 'next':
                nbLignePagination = nbLignePagination +<?= $this->nb_lignes ?>;
                if (nbLignePagination <= totalLignePagination) {
                    $('#nbLignePagination').val(nbLignePagination);
                    $('#pageActive').val(pageActive + 1);
                }
                break;
            case 'last':
                nbLignePagination = totalLignePagination;
                $('#nbLignePagination').val(nbLignePagination);
                $('#pageActive').val(nbPages);
                break;
        }
        $("#search_dossier").submit();
    }
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <?php if (isset($_POST['form_search_client'])) : ?>
        <h1>Résultats de la recherche de dossiers <?= (count($this->lProjects) > 0 ? '(' . count($this->lProjects) . ')' : '') ?></h1>
    <?php else : ?>
        <h1>Liste des <?= (isset($this->iCountProjects)) ? $this->iCountProjects : 0 ?> dossiers</h1>
    <?php endif; ?>
    <div class="btnDroite"><a href="<?= $this->lurl ?>/dossiers/add/create" class="btn_link">Créer un dossier</a></div>
    <style>
        table.formColor {width: 1115px;}
        .select {width: 100px;}
    </style>
    <div style="width:1115px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
        <form method="post" name="search_dossier" id="search_dossier" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <td>
                            <label for="id">ID :</label><br/>
                            <input type="text" name="id" id="id" class="input_court" title="id" value="<?= (isset($_POST['id'])) ? $_POST['id'] : '' ?>"/>
                            <input type="hidden" name="nbLignePagination" id="nbLignePagination" value="<?= (isset($_POST['nbLignePagination'])) ? $_POST['nbLignePagination'] : 0 ?>"/>
                            <input type="hidden" name="pageActive" id="pageActive" value="<?= (isset($_POST['pageActive'])) ? $_POST['pageActive'] : 1 ?>"/>
                        </td>
                        <td>
                            <label for="siren">Siren :</label><br/><input type="text" name="siren" id="siren" class="input_moy" style="width:100px;" title="siren" value="<?= (isset($_POST['siren'])) ? $_POST['siren'] : '' ?>"/>
                        </td>
                        <td>
                            <label for="siren">Raison sociale :</label><br/><input type="text" name="raison-sociale" id="raison-sociale" class="input_moy" title="Raison sociale" value="<?= (isset($_POST['raison-sociale'])) ? $_POST['raison-sociale'] : '' ?>"/>
                        </td>
                        <td style="padding-top:23px;">
                            <input type="text" name="date1" id="datepik_1" class="input_dp" value="<?= (isset($_POST['date1'])) ? $_POST['date1'] : '' ?>"/>
                        </td>
                        <td style="padding-top:23px;">
                            <input type="text" name="date2" id="datepik_2" class="input_dp" value="<?= (isset($_POST['date2'])) ? $_POST['date2'] : '' ?>"/>
                        </td>
                        <td style="padding-top:23px;">
                            <select name="montant" id="montant" class="select" style="width:80px;">
                                <option value="0">Montant</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 50 ? 'selected' : '') ?> value="50">50</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 100 ? 'selected' : '') ?> value="100">100</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 500 ? 'selected' : '') ?> value="500">500</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 1000 ? 'selected' : '') ?> value="1000">1 000</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 1500 ? 'selected' : '') ?> value="1500">1 500</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 2000 ? 'selected' : '') ?> value="2000">2 000</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 5000 ? 'selected' : '') ?> value="2000">5 000</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 10000 ? 'selected' : '') ?> value="10000">10 000</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 50000 ? 'selected' : '') ?> value="50000">50 000</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 100000 ? 'selected' : '') ?> value="100000">100 000</option>
                                <option <?= (isset($_POST['montant']) && $_POST['montant'] == 250000 ? 'selected' : '') ?> value="250000">250 000</option>
                            </select>
                        </td>
                        <td style="padding-top:23px;">
                            <select name="duree" id="duree" class="select" style="width:80px;">
                                <option value="">Durée</option>
                                <?php foreach ($this->fundingTimeValues as $sFundingtime) : ?>
                                    <option <?= (isset($_POST['duree']) && $_POST['duree'] == $sFundingtime ? 'selected' : '') ?> value="<?= $sFundingtime ?>"><?= $sFundingtime ?> mois</option>
                                <?php endforeach; ?>
                                <option <?= (isset($_POST['duree']) && $_POST['duree'] == '1000000' ? 'selected' : '') ?> value="1000000">je ne sais pas</option>
                            </select>
                        </td>
                        <td style="padding-top:23px;">
                            <select name="status" id="status" class="select" style="width:80px;">
                                <option value="">Status</option>
                                <?php foreach ($this->lProjects_status as $s) : ?>
                                    <option <?= (isset($_POST['status']) && $_POST['status'] == $s['status'] || isset($this->params[0]) && $this->params[0] == $s['status'] ? 'selected' : '') ?> value="<?= $s['status'] ?>">
                                        <?= 55 == $this->sessionIdUser && \projects_status::COMITE == $s['status'] ? 'En attente beau papa' : $s['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="padding-top:23px;">
                            <select name="commercial" id="commercial" class="select">
                                <option value="0">Commercial</option>
                                <?php foreach ($this->aSalesPersons as $aSalesPerson) : ?>
                                    <option <?= (isset($_POST['commercial']) && $_POST['commercial'] == $aSalesPerson['id_user'] ? 'selected' : '') ?> value="<?= $aSalesPerson['id_user'] ?>"><?= $aSalesPerson['firstname'] ?> <?= $aSalesPerson['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="padding-top:23px;">
                            <select name="analyste" id="analyste" class="select">
                                <option value="0">Analyste</option>
                                <?php foreach ($this->aAnalysts as $aAnalyst) : ?>
                                    <option <?= (isset($_POST['analyste']) && $_POST['analyste'] == $aAnalyst['id_user'] ? 'selected' : '') ?> value="<?= $aAnalyst['id_user'] ?>"><?= $aAnalyst['firstname'] ?> <?= $aAnalyst['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="10" style="text-align:center;">
                            <input type="hidden" name="form_search_dossier" id="form_search_dossier"/>
                            <input type="submit" value="Valider" title="Valider" name="send_dossier" id="send_dossier" class="btn"/>
                            <input style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;" type="button" value="Reset" title="Reset" name="Reset" id="Reset" class="btn"/>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?php if (isset($this->lProjects) && count($this->lProjects) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th style="width:4%">ID</th>
                    <th style="width:4%">Siren</th>
                    <th style="width:24%">Raison sociale</th>
                    <th style="width:7%">Date demande</th>
                    <th style="width:7%">Date modification</th>
                    <th style="width:10%">Montant</th>
                    <th style="width:6%">Durée</th>
                    <th style="width:13%">Statut</th>
                    <th style="width:10%">Commercial</th>
                    <th style="width:10%">Analyste</th>
                    <th style="width:2%">Presc.</th>
                    <th style="width:2%">Comment.</th>
                    <th style="width:1%">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lProjects as $p) : ?>
                    <?php
                        $this->oUserAnalyst->get($p['id_analyste'], 'id_user');
                        $this->oUserSalesPerson->get($p['id_commercial'], 'id_user');
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> id="ledossier<?= $p['id_project'] ?>">
                        <td><?= $p['id_project'] ?></td>
                        <td><?= $p['siren'] ?></td>
                        <td><?= $p['name'] ?></td>
                        <td><?= $this->dates->formatDate($p['added'], 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($p['updated'], 'd/m/Y') ?></td>
                        <td><?= $this->ficelle->formatNumber($p['amount'], 0) ?> €</td>
                        <td><?= ($p['period'] == 1000000 || $p['period'] == 0) ? 'Je ne sais pas' : $p['period'] . ' mois' ?></td>
                        <td><?= 55 == $this->sessionIdUser && \projects_status::COMITE == $p['status'] ? 'En attente beau papa' : $p['label'] ?></td>
                        <td><?= $this->oUserSalesPerson->firstname ?> <?= $this->oUserSalesPerson->name ?></td>
                        <td><?= $this->oUserAnalyst->firstname ?> <?= $this->oUserAnalyst->name ?></td>
                        <td><?= ($p['id_prescripteur']) ? '<img src="'. $this->surl .'/images/admin/check.png" alt="a prescripteur"/>' : '' ?></td>
                        <td data-toggle="tooltip" class="tooltip" title="<?= $p['comments'] && $p['comments'] != '' ? $p['comments'] : '' ?>"><?= $p['comments'] && $p['comments'] != '' ? 'oui' : 'non' ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $p['title'] ?>"/>
                            </a>
                            <script>
                                $("#ledossier<?=$p['id_project']?>").click(function () {
                                    $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/<?=$p['id_project']?>');
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
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first" onclick="paginationDossiers('first');"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev" onclick="paginationDossiers('prev');"/>
                        <span id="displayPager"></span>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next" onclick="paginationDossiers('next');"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last" onclick="paginationDossiers('last');"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php elseif (isset($_POST['form_search_emprunteur'])) : ?>
        <p>Il n'y a aucun dossier pour cette recherche.</p>
    <?php endif; ?>
</div>
