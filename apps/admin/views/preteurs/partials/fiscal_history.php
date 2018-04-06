<div class="row">
    <div class="col-md-6">
        <h2>Historique Fiscal</h2>
        <div class="row">
            <div class="col-md-6">
            <?php if (false === empty($this->taxationCountryHistory)) : ?>
                <table class="tablesorter histo_status_client">
                    <?php if (array_key_exists('error', $this->taxationCountryHistory)) : ?>
                        <tr>
                            <td><?= $this->taxationCountryHistory['error'] ?></td>
                        </tr>
                    <?php else:
                        foreach ($this->taxationCountryHistory as $row) { ?>
                            <tr>
                                <td>
                                    Nouveau pays fiscal: <b><?= $row['country_name'] ?></b>.
                                    Modifié par <?= $row['user_firstname'] ?> <?= $row['user_name'] ?> le <?= date('d/m/Y H:i:s', strtotime($row['added'])) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <h2>Exonération fiscale</h2>
        <form method="post" action="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->client->getIdClient() ?>">
            <div class="row">
                <div class="form-group col-md-3">
                    <?php if (false === in_array($this->nextYear, $this->exemptionYears)) : ?>
                        <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $this->nextYear ?>/check" class="thickbox cboxElement">
                            <input type="checkbox" id="tax_exemption_<?= $this->nextYear ?>" name="tax_exemption[<?= $this->nextYear ?>]" value="1">
                        </a>
                        <label for="tax_exemption_<?= $this->nextYear ?>"><?= $this->nextYear ?></label>
                        <br>
                    <?php endif; ?>
                    <?php foreach ($this->exemptionYears as $exemptionYear) : ?>
                        <?php if ($this->nextYear == $exemptionYear) : ?>
                            <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $exemptionYear ?>/uncheck" class="thickbox cboxElement">
                                <input type="checkbox" id="tax_exemption_<?= $exemptionYear ?>" name="tax_exemption[<?= $exemptionYear ?>]" value="1" checked>
                            </a>
                        <?php else: ?>
                            <input type="checkbox" id="tax_exemption_<?= $exemptionYear ?>" name="tax_exemption[<?= $exemptionYear ?>]" value="1" checked disabled>
                        <?php endif; ?>
                        <label for="tax_exemption_<?= $exemptionYear ?>"><?= $exemptionYear ?></label>
                        <br>
                    <?php endforeach; ?>
                </div>
                <div class="form-group col-md-3">
                    <div class="text-right">
                        <input type="hidden" name="send_tax_exemption" id="send_tax_exemption"/>
                        <button type="submit" class="btn-primary">Sauvegarder</button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12">
                    <?php if (false === empty($this->taxExemptionUserHistoryAction)) : ?>
                        <table class="tablesorter histo_status_client">
                            <?php foreach ($this->taxExemptionUserHistoryAction as $actions) : ?>
                                <?php foreach ($actions['modifications'] as $action) : ?>
                                    <tr>
                                        <td>Dispense de prélèvement fiscal <b>année <?= $action['year'] ?></b>.
                                            <?php if ('adding' === $action['action']) : ?>
                                                Ajoutée
                                            <?php elseif ('deletion' === $action['action']) : ?>
                                                Supprimée
                                            <?php endif; ?>
                                            le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $actions['date'])->format('d/m/Y H:i:s') ?> par <?= $actions['user'] ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
