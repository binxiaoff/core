<script type="text/javascript">
    $(function() {
        $('#last-annual-accounts').change(function() {
            $('#last-annual-accounts-form').submit();
        });

        $('.annual_accounts_dates').click(function() {
            $box = $('#annual-accounts-dates-popup').clone();
            $box.find('[name=duree_exercice_fiscal]').val($(this).data('duration'));
            $box.find('[name=id_annual_accounts]').val($(this).data('annual-account'));
            $box.find('[name=id_annual_accounts_remove]').val($(this).data('annual-account'));
            $box.find('[name=cloture_exercice_fiscal]').val($(this).data('closing')).datepicker({
                showOn: 'both',
                buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                buttonImageOnly: true,
                changeMonth: true,
                changeYear: true,
                yearRange: '<?= (date('Y') - 5) ?>:<?= (date('Y') + 5) ?>'
            });
            $.colorbox({html: $box.show()});
        });

        $('.annual-accounts .numbers').on('focus', function() {
            $(this).closest('tr').addClass('highlighted');
        }).on('blur', function() {
            $(this).closest('tr').removeClass('highlighted');
        });

        $('.collapse_expand').on('click', function() {
            if ($(this).hasClass('expanded')) {
                $(this).attr('src', '<?= $this->surl ?>/images/admin/down.png');
                $(this).closest('table').children('tbody').hide();
                $(this).removeClass('expanded').addClass('collapsed');
            } else {
                $(this).attr('src', '<?= $this->surl ?>/images/admin/up.png');
                $(this).closest('table').children('tbody').show();
                $(this).removeClass('collapsed').addClass('expanded');
            }
        });

        $('#tax-form-type').change(function() {
            if ($(this).val() != '') {
                $('#add-balance-submit').prop('disabled', false);
            } else {
                $('#add-balance-submit').prop('disabled', true);
            }
        });
    });
</script>

<style type="text/css">
    #annual-accounts-dates-popup {
        background-color: #FFF;
        padding: 20px;
        border: 2px solid #E3E4E5;
        border-radius: 10px;
    }

    .balance-form {
        display: inline-block;
        width: 378px;
        margin-bottom: 10px;
    }

    .collapse_expand {
        cursor: pointer;
        float: left;
    }
</style>

<div id="annual-accounts-dates-popup" style="display: none;">
    <h2>Modifier l'exercice fiscal</h2>
    <form action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post">
        <input type="text" name="cloture_exercice_fiscal" class="numbers input_dp datepicker" placeholder="Date de cloture">
        <input type="text" name="duree_exercice_fiscal" class="numbers input_court numbers" placeholder="Durée (mois)"> mois
        <br><br>
        <div style="text-align: right">
            <input type="hidden" name="id_annual_accounts">
            <input type="hidden" name="change_annual_accounts_info" value="1">
            <button type="submit" class="btn-primary pull-right">Sauvegarder</button>
        </div>
    </form>
    <br>
    <br>
    <h2>Supprimer l'exercice fiscal</h2>
    <form action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post" onsubmit="return confirm('Voulez-vous supprimer ce bilan ?');">
        <div style="text-align: right">
            <input name="submit-button" type="submit" id="remove-yearly-balance" value="Supprimer" class="btn_link">
            <input type="hidden" name="id_annual_accounts_remove">
        </div>
    </form>
</div>

<a class="tab_title" id="section-balance-sheets" href="#section-balance-sheets">4.2. Bilans</a>
<div class="tab_content" id="etape4_2">
    <form action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post" style="float:right">
        <h2>Ajouter un bilan</h2>
        <input type="hidden" name="add_annual_accounts" value="1">
        <label for="tax-form-type">
            <select id="tax-form-type" name="tax_form_type" onchange="" class="select">
                <option value="">Type de liasse</option>
                <?php foreach ($this->taxFormTypes as $type) : ?>
                    <option value="<?= $type['id_type'] ?>"><?= $type['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input id="add-balance-submit" type="submit" class="btn-small btn_link" value="Ajouter" disabled style="float:right">
    </form>
    <form id="last-annual-accounts-form" action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post" class="balance-form">
        <h2>
            Dernier bilan
            <?php if ($this->lastBalanceSheet) : ?>
                <a href="<?= $this->url ?>/attachment/download/id/<?= $this->lastBalanceSheet->getId() ?>/file/<?= urlencode($this->lastBalanceSheet->getPath()) ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="Dernière liasse fiscale"></a>
            <?php endif; ?>
        </h2>
        <?php if (empty($this->aAllAnnualAccounts)) : ?>
            Aucun bilan saisi actuellement
        <?php else : ?>
            <select id="last-annual-accounts" name="last_annual_accounts" title="Dernier bilan" class="select">
            <?php foreach ($this->aAllAnnualAccounts as $aAnnualAccounts) : ?>
                <option value="<?= $aAnnualAccounts['id_bilan'] ?>"<?= $aAnnualAccounts['id_bilan'] == $this->projects->id_dernier_bilan ? ' selected' : '' ?>><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</option>
            <?php endforeach; ?>
        <?php endif; ?>
        </select>
    </form>
    <form id="balance-count-form" action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post" class="balance-form">
        <h2><label for="balance-count">Nombre de bilans</label></h2>
        <input type="text" name="balance_count" id="balance-count" value="<?= empty($this->projects->balance_count) ? '' : $this->projects->balance_count ?>" class="input_moy">
        <input type="submit" class="btn-small btn_link" value="Modifier">
    </form>
    <br>
    <hr style="border: 1px solid #b20066;">
    <br>
    <h1>Déclaration client</h1>
    <table class="form">
        <tbody>
        <tr>
            <th style="width: 17%;"><label for="ca_declara_client">Chiffe d'affaires declaré</label></th>
            <td style="width: 17%;"><?= $this->ficelle->formatNumber($this->projects->ca_declara_client, 0) ?>&nbsp;€</td>
            <th style="width: 16%;"><label for="resultat_exploitation_declara_client">Résultat d'exploitation declaré</label></th>
            <td style="width: 16%;"><?= $this->ficelle->formatNumber($this->projects->resultat_exploitation_declara_client, 0) ?>&nbsp;€</td>
            <th style="width: 17%;"><label for="fonds_propres_declara_client">Fonds propres declarés</label></th>
            <td style="width: 17%;"><?= $this->ficelle->formatNumber($this->projects->fonds_propres_declara_client, 0) ?>&nbsp;€</td>
        </tr>
        </tbody>
    </table>
    <br>
    <form id="dossier_etape4_2" action="/ajax/valid_etapes" method="post">
        <input type="hidden" name="id_project" value="<?= $this->projects->id_project ?>">
        <input type="hidden" name="etape" value="4.2">
        <?php if (in_array(company_tax_form_type::FORM_2033, array_column($this->aBalanceSheets, 'form_type'))) : ?>
            <?php $this->fireView('blocs/balance_sheet/2033'); ?>
        <?php endif; ?>
        <br>
        <?php if (in_array(company_tax_form_type::FORM_2035, array_column($this->aBalanceSheets, 'form_type'))) : ?>
            <?php $this->fireView('blocs/balance_sheet/2035'); ?>
        <?php endif; ?>
        <?php if (false === empty($this->aBalanceSheets)) : ?>
            <button type="submit" class="btn-primary pull-right">Sauvegarder les bilans</button>
        <?php endif; ?>
    </form>
</div>
