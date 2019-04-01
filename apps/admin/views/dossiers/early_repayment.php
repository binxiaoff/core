<h2>Remboursement anticipé / Information</h2>
<?php if (true === $this->earlyRepaymentPossible) : ?>
    <table class="form" style="width: 100%; border: 1px solid #2bc9af;">
        <tr>
            <th>Statut :</th>
            <td>
                <label for="statut"><?= $this->message ?></label>
            </td>
        </tr>
        <?php if ($this->reception) : ?>
            <tr>
                <th>Virement reçu le :</th>
                <td><label for="statut"><?= $this->reception->getAdded()->format('d/m/Y') ?></label></td>
            </tr>
            <tr>
                <th>Identification virement :</th>
                <td><label for="statut"><?= $this->reception->getIdReception() ?></label></td>
            </tr>
            <tr>
                <th>Montant virement :</th>
                <td><label for="statut"><?= ($this->reception->getMontant() / 100) ?> €</label></td>
            </tr>
            <tr>
                <th>Motif du virement :</th>
                <td><label for="statut"><?= $this->reception->getMotif() ?></label></td>
            </tr>
        <?php elseif (isset($this->earlyRepaymentLimitDate)) : ?>
            <tr>
                <th>Virement à émettre avant le :</th>
                <td><label for="statut"><?= $this->earlyRepaymentLimitDate->format('d/m/Y') ?></label></td>
            </tr>
            <tr>
                <th>Montant CRD (*) :</th>
                <td><label for="statut"><?= $this->ficelle->formatNumber($this->lenderOwedCapital) ?>&nbsp;€</label></td>
            </tr>
        <?php endif; ?>

        <?php if ($this->reception instanceof \Unilend\Entity\Receptions) : ?>
            <?php if ($this->wireTransferAmountOk && $this->displayActionButton) : ?>
                <tr>
                    <th>Actions :</th>
                    <td>
                        <a class="inline btn_link" href="#early-repayment">Déclencher le remboursement anticipé</a>
                        <div class="hidden">
                            <div id="early-repayment" style="padding: 10px; min-width: 300px;">
                                <h3 class="text-center">Confirmer le remboursement anticipé</h3>
                                <form action="" method="post" name="action_remb_anticipe">
                                    <input type="hidden" name="id_reception" value="<?= $this->reception->getIdReception() ?>">
                                    <input type="hidden" name="spy_remb_anticipe" value="ok">
                                    <div class="text-center"><br>
                                        <button type="button" class="btn btnDisabled" onclick="parent.$.fn.colorbox.close()">Annuler</button>
                                        <input type="submit" value="Valider" class="btn" data-prevent-doubleclick>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        <?php else : ?>
            <tr>
                <th>Motif à indiquer sur le virement :</th>
                <td><label for="statut">RA-<?= $this->projects->id_project ?></label></td>
            </tr>
        <?php endif; ?>
    </table>
    <?php if (false === $this->reception instanceof \Unilend\Entity\Receptions) : ?>
        <p>* : Le montant correspond aux CRD des échéances restantes après celle du <?= $this->nextScheduledRepaymentDate->format('d/m/Y') ?> qui sera prélevée normalement</p>
    <?php endif; ?>
<?php else: ?>
    <?= $this->message ?>
<?php endif; ?>

<script>
    $('.inline').colorbox({inline: true})
</script>
