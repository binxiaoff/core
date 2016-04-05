<div class="form-manage">
    <h2><?= $this->lng['profile']['titre-4'] ?></h2>
    <form action="" method="post">
        <header class="form-head">
            <p><?= $this->lng['gestion-alertes']['contenu'] ?></p>
        </header>
        <div class="form-body">
            <?php foreach($this->infosNotifs as $sGroup => $aNotifications) : ?>
            <div class="form-row">
                <div class="table-manage">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col width="310" />
                            <col width="130" />
                            <col width="130" />
                            <col width="130" />
                            <col width="130" />
                            <col width="130" />
                        </colgroup>
                        <tr>
                            <th><span><?= $this->lng['gestion-alertes'][$sGroup] ?></span></th>
                            <?php if ($sGroup === 'vos-offres-et-vos-projets') :
                            ?>
                            <th><?= $this->lng['gestion-alertes']['immediatement'] ?></th>
                            <th>
                                <p>
                                    <?= $this->lng['gestion-alertes']['synthese-quotidienne'] ?>
                                    <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->lng['gestion-alertes']['synthese-quotidienne-info'] ?>"></i>
                                </p>
                            </th>
                            <th>
                                <p>
                                    <?= $this->lng['gestion-alertes']['synthese-hebdomadaire'] ?>
                                    <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->lng['gestion-alertes']['synthese-hebdomadaire-info'] ?>"></i>
                                </p>
                            </th>
                            <th>
                                <p>
                                    <?= $this->lng['gestion-alertes']['synthese-mensuelle'] ?>
                                    <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->lng['gestion-alertes']['synthese-mensuelle-info'] ?>"></i>
                                </p>
                            </th>
                            <th>
                                <p><?= $this->lng['gestion-alertes']['uniquement-par-notification'] ?></p>
                            </th>
                            <?php else : ?>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <?php endif; ?>
                        </tr>
                        <?php foreach ($aNotifications as $iNotification_type => $aNotification) :?>
                        <tr>
                            <td>
                                <p>
                                    <?= $aNotification['title'] ?>
                                    <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $aNotification['info'] ?>"></i>
                                </p>
                            </td>
                            <td>
                                <?php if (in_array(\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE, $aNotification['available_types'])) : ?>
                                <div class="form-controls">
                                    <div class="checkbox">
                                        <input onchange="checkbox(this.id)" type="checkbox" id="immediatement_<?= $iNotification_type ?>" name="immediatement_<?= $iNotification_type ?>" <?= ($this->NotifC[$iNotification_type]['immediatement'] == 1 ? 'checked' : '') ?> />
                                        <label for="immediatement_<?= $iNotification_type ?>"></label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array(\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY, $aNotification['available_types'])) : ?>
                                    <div class="form-controls">
                                        <div class="checkbox">
                                            <input onchange="checkbox(this.id)" type="checkbox" id="quotidienne_<?= $iNotification_type ?>" name="quotidienne_<?= $iNotification_type ?>" <?= ($this->NotifC[$iNotification_type]['quotidienne'] == 1 ? 'checked' : '') ?> />
                                            <label for="quotidienne_<?= $iNotification_type ?>"></label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array(\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY, $aNotification['available_types'])) : ?>
                                    <div class="form-controls">
                                        <div class="checkbox">
                                            <input onchange="checkbox(this.id)" type="checkbox" id="hebdomadaire_<?= $iNotification_type ?>" name="hebdomadaire_<?= $iNotification_type ?>" <?= (in_array($iNotification_type, array(2)) ? 'class="check-delete" disabled checked' : ($this->NotifC[$iNotification_type]['hebdomadaire'] == 1 ? 'checked' : '')) ?> />
                                            <label for="hebdomadaire_<?= $iNotification_type ?>"></label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array(\clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY, $aNotification['available_types'])) : ?>
                                    <div class="form-controls">
                                        <div class="checkbox">
                                            <input onchange="checkbox(this.id)" type="checkbox" id="mensuelle_<?= $iNotification_type ?>" name="mensuelle_<?= $iNotification_type ?>" <?= (in_array($iNotification_type, array(1, 2)) ? 'class="check-delete" disabled checked' : ($this->NotifC[$iNotification_type]['mensuelle'] == 1 ? 'checked' : '')) ?> />
                                            <label for="mensuelle_<?= $iNotification_type ?>"></label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array(\clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL, $aNotification['available_types'])) : ?>
                                <div class="form-controls">
                                    <div class="radio">
                                        <input onchange="radio_uniquement(this.id)" type="radio" id="uniquement_notif_<?= $iNotification_type ?>" name="uniquement_notif_<?= $iNotification_type ?>" <?= ($this->NotifC[$iNotification_type]['uniquement_notif'] == 1 ? 'checked' : '') ?> />
                                        <label for="uniquement_notif_<?= $iNotification_type ?>"></label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <? endforeach; ?>
        </div>
        <div class="form-foot row row-cols centered">
            <input type="hidden" name="send_gestion_alertes" id="send_gestion_alertes"/>
            <button class="btn" id="submit_gestion_alertes" type="submit" ><?= $this->lng['etape1']['valider-les-modifications'] ?><i class="icon-arrow-next"></i></button>
        </div>
    </form>
</div>

<script type="application/javascript">
    function radio_uniquement(id) {
        if ($('#'+id).prop('checked') == true) {
            var num = id;
            num = num.split("_");
            num = num[2];

            $('#immediatement_' + num).attr('checked', false);
            $('#quotidienne_' + num).attr('checked', false);

            if (num != 2) {
                $('#hebdomadaire_' + num).attr('checked', false);
            }
            if (num != 1 && num != 2) {
                $('#mensuelle_' + num).attr('checked', false);
            }
        }
    }

    function checkbox(id) {
        var num = id;
        num = num.split("_");
        num = num[1];

        if ($('#'+id).prop('checked') == true) {
            $('#uniquement_notif_' + num).attr('checked', false);
        } else {
            var champs = false;

            if (num == 1) {
                if (
                    $('#immediatement_' + num).prop('checked') == false
                    && $('#quotidienne_' + num).prop('checked') == false
                    && $('#hebdomadaire_' + num).prop('checked') == false
                ) {
                    champs = true;
                }
            } else if (num == 2 || num == 3) {
                if (
                    $('#immediatement_' + num).prop('checked') == false
                    && $('#quotidienne_' + num).prop('checked') == false
                ){
                    champs = true;
                }
            } else if(num == 6 || num == 7 || num == 8 || num == 9) {
                if ($('#immediatement_' + num).prop('checked') == false) {
                    champs = true;
                }
            } else {
                if(
                    $('#immediatement_' + num).prop('checked') == false
                    && $('#quotidienne_' + num).prop('checked') == false
                    && $('#hebdomadaire_' + num).prop('checked') == false
                    && $('#mensuelle_' + num).prop('checked') == false
                ) {
                    champs = true;
                }
            }

            if (champs == true) {
                $('#uniquement_notif_'+num).click();
            }
        }
    }
</script>
