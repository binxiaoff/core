<script type="text/javascript">
    $(function() {
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
    });
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
<div id="contenu">
    <div class="row">
        <div class="col-sm-6">
            <h1>Rattrapage offre de bienvenue</h1>
        </div>
        <div class="col-sm-6">
            <a href="<?= $this->lurl ?>/transferts/csv_rattrapage_offre_bienvenue/" class="btn-primary pull-right thickbox">Recuperation du CSV</a>
        </div>
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
    <?php if (empty($this->aClientsWithoutWelcomeOffer)) : ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php else : ?>
        <div class="table">
            <table class="tablesorter">
                <thead>
                <tr>
                    <th>Id Client</th>
                    <th>Nom</th>
                    <th>Pr&eacute;nom</th>
                    <th>Email</th>
                    <th>Date de création</th>
                    <th>Date de validation</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->aClientsWithoutWelcomeOffer as $aClient) : ?>
                    <tr>
                        <td><?= $aClient['id_client'] ?></td>
                        <td><?= empty($aClient['company']) ? $aClient['nom'] : $aClient['company'] ?></td>
                        <td><?= empty($aClient['company']) ? $aClient['prenom'] : '' ?></td>
                        <td><?= $aClient['email'] ?></td>
                        <td><?= \DateTime::createFromFormat('Y-m-d', $aClient['date_creation'])->format('d/m/Y') ?></td>
                        <td><?= (false === empty($aClient['date_validation'])) ? \DateTime::createFromFormat('Y-m-d H:i:s', $aClient['date_validation'])->format('d/m/Y') : '' ?></td>
                        <td>
                            <?php if (false === empty($aClient['date_validation'])) : ?>
                                <a href="<?= $this->lurl ?>/transferts/affect_welcome_offer/<?= $aClient['id_client'] ?>"
                                   class="link thickbox"><img alt="Modifier " src="<?= $this->surl ?>/images/admin/edit.png"></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
