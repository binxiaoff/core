<div class="form-manage">
    <h2><?= $this->lng['profile']['titre-4'] ?></h2>

    <form action="" method="post">
        <header class="form-head">
            <p><?= $this->lng['gestion-alertes']['contenu'] ?></p>
        </header><!-- /.form-head -->

        <div class="form-body">
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
                            <th><span><?= $this->lng['gestion-alertes']['vos-offres-et-vos-projets'] ?></span></th>

                            <th><?= $this->lng['gestion-alertes']['immediatement'] ?></th>

                            <th>
                        <p><?= $this->lng['gestion-alertes']['synthese-quotidienne'] ?>

                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->lng['gestion-alertes']['synthese-quotidienne-info'] ?>"></i>
                        </p>
                        </th>

                        <th>
                        <p><?= $this->lng['gestion-alertes']['synthese-hebdomadaire'] ?>

                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->lng['gestion-alertes']['synthese-hebdomadaire-info'] ?>"></i>
                        </p>
                        </th>

                        <th>
                        <p><?= $this->lng['gestion-alertes']['synthese-mensuelle'] ?>

                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->lng['gestion-alertes']['synthese-mensuelle-info'] ?>"></i>
                        </p>
                        </th>

                        <th>
                        <p><?= $this->lng['gestion-alertes']['uniquement-par-notification'] ?></p>
                        </th>
                        </tr>
                        <?php
                        foreach ($this->lTypeNotifs as $k => $n) {
                            $id_notif = $n['id_client_gestion_type_notif'];

                            if (in_array($id_notif, array(1, 2, 3, 4, 9))) {
                                ?>
                                <tr>
                                    <td>
                                        <p>
                                            <?= $this->infosNotifs['title'][$id_notif] ?>
                                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->infosNotifs['info'][$id_notif] ?>"></i>
                                        </p>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="checkbox">
                                                <input onchange="checkbox(this.id)" type="checkbox" id="immediatement_<?= $id_notif ?>" name="immediatement_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['immediatement'] == 1 ? 'checked' : '') ?> />
                                                <label for="immediatement_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (false === in_array($id_notif, array(9))): ?>
                                            <div class="form-controls">
                                                <div class="checkbox">
                                                    <input onchange="checkbox(this.id)" type="checkbox" id="quotidienne_<?= $id_notif ?>" name="quotidienne_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['quotidienne'] == 1 ? 'checked' : '') ?> />
                                                    <label for="quotidienne_<?= $id_notif ?>"></label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (false === in_array($id_notif, array(2, 3, 9))): ?>
                                            <div class="form-controls">
                                                <div class="checkbox">
                                                    <input onchange="checkbox(this.id)" type="checkbox" id="hebdomadaire_<?= $id_notif ?>" name="hebdomadaire_<?= $id_notif ?>" <?= (in_array($id_notif, array(2)) ? 'class="check-delete" disabled checked' : ($this->NotifC[$id_notif]['hebdomadaire'] == 1 ? 'checked' : '')) ?> />
                                                    <label for="hebdomadaire_<?= $id_notif ?>"></label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (false === in_array($id_notif, array(1, 2, 3, 9))): ?>
                                            <div class="form-controls">
                                                <div class="checkbox">
                                                    <input onchange="checkbox(this.id)" type="checkbox" id="mensuelle_<?= $id_notif ?>" name="mensuelle_<?= $id_notif ?>" <?= (in_array($id_notif, array(1, 2)) ? 'class="check-delete" disabled checked' : ($this->NotifC[$id_notif]['mensuelle'] == 1 ? 'checked' : '')) ?> />
                                                    <label for="mensuelle_<?= $id_notif ?>"></label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="radio">
                                                <input onchange="radio_uniquement(this.id)" type="radio" id="uniquement_notif_<?= $id_notif ?>" name="uniquement_notif_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['uniquement_notif'] == 1 ? 'checked' : '') ?> />
                                                <label for="uniquement_notif_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="form-row">
                <div class="table-manage">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col width="310"/>
                            <col width="130"/>
                            <col width="130"/>
                            <col width="130"/>
                            <col width="130"/>
                            <col width="130"/>
                        </colgroup>
                        <tr>
                            <th><span><?= $this->lng['gestion-alertes']['vos-remboursements'] ?></span></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <?php
                        foreach ($this->lTypeNotifs as $k => $n) {
                            $id_notif = $n['id_client_gestion_type_notif'];
                            if (in_array($id_notif, array(5))) {
                                ?>
                                <tr>
                                    <td>
                                        <p>
                                            <?= $this->infosNotifs['title'][$id_notif] ?>
                                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->infosNotifs['info'][$id_notif] ?>"></i>
                                        </p>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="checkbox">
                                                <input onchange="checkbox(this.id)" type="checkbox" id="immediatement_<?= $id_notif ?>" name="immediatement_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['immediatement'] == 1 ? 'checked' : '') ?> />
                                                <label for="immediatement_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="form-controls">
                                            <div class="checkbox">
                                                <input onchange="checkbox(this.id)" type="checkbox" id="quotidienne_<?= $id_notif ?>" name="quotidienne_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['quotidienne'] == 1 ? 'checked' : '') ?> />
                                                <label for="quotidienne_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="checkbox">
                                                <input onchange="checkbox(this.id)" type="checkbox" id="hebdomadaire_<?= $id_notif ?>" name="hebdomadaire_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['hebdomadaire'] == 1 ? 'checked' : '') ?> />
                                                <label for="hebdomadaire_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="checkbox">
                                                <input onchange="checkbox(this.id)" type="checkbox" id="mensuelle_<?= $id_notif ?>" name="mensuelle_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['mensuelle'] == 1 ? 'checked' : '') ?> />
                                                <label for="mensuelle_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="radio">
                                                <input onchange="radio_uniquement(this.id)" type="radio" id="uniquement_notif_<?= $id_notif ?>" name="uniquement_notif_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['uniquement_notif'] == 1 ? 'checked' : '') ?> />
                                                <label for="uniquement_notif_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="form-row">
                <div class="table-manage">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <colgroup>
                            <col width="310"/>
                            <col width="130"/>
                            <col width="130"/>
                            <col width="130"/>
                            <col width="130"/>
                            <col width="130"/>
                        </colgroup>
                        <tr>
                            <th><span><?= $this->lng['gestion-alertes']['mouvements-sur-votre-compte'] ?></span></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <?php
                        foreach ($this->lTypeNotifs as $k => $n) {
                            $id_notif = $n['id_client_gestion_type_notif'];
                            if (in_array($id_notif, array(6, 7, 8))) {
                                ?>
                                <tr>
                                    <td>
                                        <p>
                                            <?= $this->infosNotifs['title'][$id_notif] ?>
                                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?= $this->infosNotifs['info'][$id_notif] ?>"></i>
                                        </p>
                                    </td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="checkbox">
                                                <input onchange="checkbox(this.id)" type="checkbox" id="immediatement_<?= $id_notif ?>" name="immediatement_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['immediatement'] == 1 ? 'checked' : '') ?> />
                                                <label for="immediatement_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <div class="form-controls">
                                            <div class="radio">
                                                <input onchange="radio_uniquement(this.id)" type="radio" id="uniquement_notif_<?= $id_notif ?>" name="uniquement_notif_<?= $id_notif ?>" <?= ($this->NotifC[$id_notif]['uniquement_notif'] == 1 ? 'checked' : '') ?> />

                                                <label for="uniquement_notif_<?= $id_notif ?>"></label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        <div class="form-foot row row-cols centered">
            <input type="hidden" name="send_gestion_alertes" id="send_gestion_alertes"/>
            <button class="btn" id="submit_gestion_alertes" type="submit" ><?= $this->lng['etape1']['valider-les-modifications'] ?><i class="icon-arrow-next"></i></button>
        </div>
    </form>
</div>

<script type="application/javascript">
    function radio_uniquement(id){
        if($('#'+id).prop('checked') == true){

            var num = id;
            num = num.split("_");
            num = num[2];

            $('#immediatement_'+num).attr('checked',false);
            $('#quotidienne_'+num).attr('checked',false);

            if(num != 2)$('#hebdomadaire_'+num).attr('checked',false);
            if(num != 1 && num != 2)$('#mensuelle_'+num).attr('checked',false);
        }
    }

    function checkbox(id){

        var num = id;
        num = num.split("_");
        num = num[1];

        if($('#'+id).prop('checked') == true) $('#uniquement_notif_'+num).attr('checked',false);
        else{

            var champs = false;

            // cas 1
            if(num == 1){
                if($('#immediatement_'+num).prop('checked') == false
                && $('#quotidienne_'+num).prop('checked') == false
                && $('#hebdomadaire_'+num).prop('checked') == false){
                    champs = true;
                }
            }
            // cas 2 ou cas 3
            else if(num == 2 || num == 3){
                if($('#immediatement_'+num).prop('checked')== false
                && $('#quotidienne_'+num).prop('checked')== false){
                    champs = true;
                }
            }
            // cas 6,7,8
            else if(num == 6 || num == 7 || num == 8 || num == 9){
                if($('#immediatement_'+num).prop('checked')== false){
                    champs = true;
                }
            }
            // tous
            else{

                if($('#immediatement_'+num).prop('checked')== false
                && $('#quotidienne_'+num).prop('checked')== false
                && $('#hebdomadaire_'+num).prop('checked')== false
                && $('#mensuelle_'+num).prop('checked')== false){
                    champs = true;
                }
            }

            if(champs == true){$('#uniquement_notif_'+num).click();}
        }
    }
</script>
