<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientsStatus, Companies
};

?>
<script type="text/javascript">
    $(function() {
        $('#lender-tabs').tabs()
        $('.cgv_accept').tablesorter()

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-90)?>:<?=(date('Y')-17)?>'
        });

        $("#debut").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });

        $("#fin").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });

        initAutocompleteCity($('#ville'), $('#cp'));
        initAutocompleteCity($('#ville2'), $('#cp2'));
        initAutocompleteCity($('#com-naissance'), $('#insee_birth'));

        $('#meme-adresse').on('change', function () {
            if ($(this).prop('checked')) {
                $('.postal-address').slideUp();
            } else {
                $('.postal-address').slideDown();
            }
        });

        function addWordingli(id) {
            var content = $(".content-" + id).html();
            var textarea = $('#content_email_completude').val();

            var champ = "<input class=\"input_li\" type=\"text\" value=\"" + content + "\" name=\"input-" + id + "\" id=\"input-" + id + "\">";
            var clickdelete = "<div onclick='deleteWordingli(this.id);' class='delete_li' id='delete-" + id + "'><img src='" + add_surl + "/images/admin/delete.png' ></div>";
            $('.content_li_wording').append(champ + clickdelete);
        }

        function deleteWordingli(id) {
            var id_delete = id;
            var id_input = id.replace("delete", "input");
            $("#" + id_delete).remove();
            $("#" + id_input).remove();
        }

        $(".add").click(function() {
            var id = $(this).attr("id");
            addWordingli(id);
        });

        $("#completude_edit").click(function() {
            $('.message_completude').slideToggle();
        });

        $("#previsualisation").click(function() {
            var content = $("#content_email_completude").val();
            var input = '';
            $(".input_li").each(function (index) {
                input = input + "<li>" + $(this).val() + "</li>";
            });

            $.post(add_url + "/ajax/session_content_email_completude", {id_client: "<?= $this->client->getIdClient() ?>", content: content, liste: input}).done(function (data) {
                if (data !== 'nok') {
                    $("#completude_preview").get(0).click();
                }
            });
        });

        $('input[name=enterprise]').on('change', function() {
            var status = $('input[name=enterprise]:checked').val()

            switch (status) {
                default:
                case '<?= Companies::CLIENT_STATUS_MANAGER ?>':
                    $('.statut_dirigeant_e').slideUp()
                    $('.statut_dirigeant_e3').slideUp()
                    break
                case '<?= Companies::CLIENT_STATUS_DELEGATION_OF_POWER ?>':
                    $('.statut_dirigeant_e').slideDown()
                    $('.statut_dirigeant_e3').slideUp()
                    break
                case '<?= Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT ?>':
                    $('.statut_dirigeant_e').slideDown()
                    $('.statut_dirigeant_e3').slideDown()
                    break
            }
        })

        // Lender Vigilance / Atypical Operations
        $(document).on('click', '#btn-show-lender-vigilance-history', function () {
            $('#lender-vigilance-history').toggle();
            $(this).text(function (i, text) {
                return text === 'Voir l\'historique de vigilance' ? 'Cacher l\'historique' : 'Voir l\'historique de vigilance'
            })
        })

        $(document).on('click', '#btn-show-lender-atypical-operation', function () {
            $('#lender-atypical-operation').toggle();
            $(this).text(function (i, text) {
                return text === 'Voir les détections' ? 'Cacher les détections' : 'Voir les détections'
            })
        })

        $('.tooltip').tooltip({
            content: function () {
                return $(this).prop('title')
            }
        })
    });
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
    <?php if (empty($this->client->getIdClient())) : ?>
        <div class="attention">Attention : Client <?= $this->params[0] ?> innconu</div>
    <?php elseif (empty($this->wallet)) : ?>
        <div class="attention">Attention : ce compte n’est pas un compte prêteur</div>
    <?php else : ?>
        <div><?= $this->clientStatusMessage ?></div>
        <div class="row">&nbsp;</div>
        <div class="row">&nbsp;</div>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->client->getIdClient() ?>" class="btn-primary">Enchères</a>
            <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->client->getIdClient() ?>" class="btn-primary">Consulter Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->client->getIdClient() ?>" class="btn-primary">Historique des emails</a>
            <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->client->getIdClient() ?>" class="btn-primary">Portefeuille & Performances</a>
        </div>
        <?php if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') : ?>
            <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
            <?php unset($_SESSION['error_email_exist']); ?>
        <?php endif; ?>
        <hr>
        <form method="post" action="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->client->getIdClient() ?>">
            <?php if ($this->client->isNaturalPerson()) : ?>
                <?php $this->fireView('partials/edit_natural_person') ?>
            <?php else : ?>
                <?php $this->fireView('partials/edit_legal_entity') ?>
            <?php endif; ?>
            <div class="text-right">
                <input type="hidden" name="send_edit_preteur" id="send_edit_preteur">
                <button type="submit" class="btn-primary">Sauvegarder</button>
            </div>
        </form>
        <hr>
        <div>
            <input type="button" value="Générer un nouveau mot de passe" class="btn-primary" onclick="generer_le_mdp('<?= $this->client->getIdClient() ?>')">
            <span style="margin-left:5px; color: green; display: none;" class="success">Email envoyé</span>
            <span style="margin-left:5px; color: orange; display: none;" class="warning">Email non envoyé</span>
            <span style="margin-left:5px; color: red; display: none;" class="error">Erreur</span>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <h3>Statut de surveillance</h3>
                <div class="row">
                    <div class="col-md-7">
                        <div class="attention vigilance-status-<?= $this->vigilanceStatus['status'] ?>" style="margin-left: 0; color: black;">
                            <?= $this->vigilanceStatus['message'] ?>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <a class="thickbox btn-primary" href="<?= $this->lurl ?>/client_atypical_operation/process_detection_box/add/<?= $this->client->getIdClient() ?>">
                            Ajouter
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                            <button class="btn-primary" id="btn-show-lender-atypical-operation">Voir les détections</button>
                        <?php endif; ?>
                        <?php if (false === empty($this->vigilanceStatusHistory)) : ?>
                            <button class="btn-primary" id="btn-show-lender-vigilance-history">Voir l'historique de vigilance</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div id="lender-atypical-operation" style="display: none;">
                            <br>
                            <h2>Liste des opérations atypiques détéctés</h2>
                            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                                <?php
                                $this->atypicalOperations = $this->clientAtypicalOperations;
                                $this->showActions        = false;
                                $this->showUpdated        = true;
                                $this->fireView('../client_atypical_operation/detections_table');
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div id="lender-vigilance-history" style="display: none;">
                            <br>
                            <h2>Historique de vigilance du client</h2>
                            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                                <?php $this->fireView('../client_atypical_operation/vigilance_status_history'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div id="lender-tabs">
            <ul>
                <li><a href="#client-status-tab">Historique des statuts</a></li>
                <li><a href="#data-history-tab">Historique données personnelles</a></li>
                <li><a href="#bank-information-tab">Informations bancaires</a></li>
                <li><a href="#mrz-information-tab">MRZ</a></li>
                <li><a href="#attachments-tab">Pièces jointes</a></li>
                <?php if ($this->client->isNaturalPerson()) : ?>
                    <li><a href="#fiscal-history-tab">Historique fiscal</a></li>
                <?php endif; ?>
                <li><a href="#tos-tab">Acceptation CGV</a></li>
            </ul>
            <div id="client-status-tab">
                <div class="row">
                    <div class="col-md-8">
                        <?php $this->fireView('partials/client_status') ?>
                    </div>
                    <div class="col-md-4">
                        <?php $this->fireView('partials/actions') ?>
                    </div>
                </div>
                <?php if ($this->wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId() !== ClientsStatus::STATUS_CLOSED_DEFINITELY) : ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <?php $this->fireView('partials/lender_completeness') ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div id="data-history-tab">
                <?php $this->fireView('partials/data_history') ?>
            </div>
            <div id="bank-information-tab">
                <?php $this->fireView('partials/bank_info') ?>
            </div>
            <div id="mrz-information-tab">
                <?php $this->fireView('partials/mrz_info') ?>
            </div>
            <div id="attachments-tab">
                <?php $this->fireView('partials/lender_attachments') ?>
            </div>
            <?php if ($this->client->isNaturalPerson()) : ?>
                <div id="fiscal-history-tab">
                    <?php $this->fireView('partials/fiscal_history') ?>
                </div>
            <?php endif; ?>
            <div id="tos-tab">
                <?php $this->fireView('../blocs/acceptedLegalDocumentList'); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
