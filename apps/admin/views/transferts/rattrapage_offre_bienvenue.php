<script type="text/javascript">
    $(document).ready(function () {
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
    <?php
    if(isset($_SESSION['freeow'])) : ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php
    endif; ?>
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
    }
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/transferts">Dépot de fonds</a> -</li>
        <li>Rattrapage offre de bienvenue</li>
    </ul>
    <h1>Rattrapage offre de bienvenue</h1>

    <div class="csv">
        <a href="<?= $this->lurl ?>/transferts/csv_rattrapage_offre_bienvenue/" class="btn colorAdd">Recuperation du
            CSV</a>
    </div>

    <div class="datepicker_table">
        <form method="post" name="date_select">
            <fieldset>
                <table class="search_fields">
                    <tr>
                        <td><label for="id">ID ou liste d'IDs (séparés par virgules):</label><br/>
                            <input type="text" name="id" id="id" class="input_large"
                                   value="<?= (empty($_POST['dateStart']) && empty($_POST['dateEnd']) && false === empty($_POST['id'])) ? $_POST['id'] : '' ?>"/>
                        </td>
                        <td><label>Date debut</label><br/>
                            <input type="text" name="dateStart"
                                   id="datepik_1"
                                   class="input_dp"
                                   value="<?= (empty($_POST['id']) && false === empty($_POST['dateStart'])) ? $_POST['dateStart'] : '' ?>"/>
                        </td>
                        <td><label>Date fin</label><br/>
                            <input type="text" name="dateEnd"
                                   id="datepik_2" class="input_dp"
                                   value="<?= (empty($_POST['id']) && false === empty($_POST['dateEnd'])) ? $_POST['dateEnd'] : '' ?>"/>
                        </td>
                        <td><br>
                            <input type="hidden" name="spy_search" id="spy_search"/>
                            <input type="submit" value="Valider" title="Valider" name="send_dossier" id="send_dossier"
                                   class="btn"/>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?php
    if (empty($this->aLenders)) : ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
        <?php
    else: ?>
        <div class="table">
            <table class="tablesorter">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Nom</th>
                    <th>Pr&eacute;nom</th>
                    <th>Date de création</th>
                    <th>Date de validation</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($this->aLenders as $aLender) : ?>
                    <tr>
                        <td><?= $aLender['id_lender'] ?></td>
                        <td><?= empty($aLender['company']) ? $aLender['nom'] : $aLender['company'] ?></td>
                        <td><?= empty($aLender['company']) ? $aLender['prenom'] : '' ?></td>
                        <td><?= $this->dates->formatDateMysqltoShortFR($aLender['date_creation']) ?></td>
                        <td><?= (false === empty($aLender['date_validation'])) ? $this->dates->formatDateMysqltoShortFR($aLender['date_validation']) : '' ?></td>
                        <td>
                            <?php
                            if (false === empty($aLender['date_validation'])) : ?>
                                <a href="<?= $this->lurl ?>/transferts/affect_welcome_offer/<?= $aLender['id_lender'] ?>"
                                   class="link thickbox"><img alt="Modifier " src="<?= $this->surl ?>/images/admin/edit.png"></a>
                                <?php
                            endif; ?>
                        </td>
                    </tr>
                    <?php
                endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    endif; ?>
</div>
<?php
unset($_SESSION['freeow']); ?>
