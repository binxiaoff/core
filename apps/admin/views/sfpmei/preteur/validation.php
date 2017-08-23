<div class="row">
    <div class="col-md-12">
        <h3>Documents</h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Type</th>
                <th>Statut de validation</th>
                <th>Statut GreenPoint</th>
                <th>Fichier</th>
            </tr>
            </thead>
            <tbody>
                <?php foreach ($this->attachmentTypes as $attachmentType) : ?>
                    <?php
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
                    $currentAttachment     = null;
                    $greenPointAttachment  = null;
                    $greenpointLabel       = 'Non Contrôlé par GreenPoint';
                    $greenpointColor       = 'danger';
                    $greenpointFinalStatus = '';

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $attachment */
                    foreach ($this->attachments as $attachment) {
                        if ($attachment->getType() === $attachmentType) {
                            $currentAttachment = $attachment;
                            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment $greenPointAttachment */
                            $greenPointAttachment = $currentAttachment->getGreenpointAttachment();
                            break;
                        }
                    }

                    if (null === $currentAttachment) {
                        continue;
                    }

                    if ($greenPointAttachment) {
                        $greenpointLabel = empty($greenPointAttachment->getValidationStatusLabel()) ? 'Erreur d\'appel GreenPoint' : $greenPointAttachment->getValidationStatusLabel();
                        if (\Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
                            $greenpointFinalStatus = 'Statut définitif';
                        } else {
                            $greenpointFinalStatus = 'Statut peut être modifié par un retour asychrone';
                        }

                        if (0 == $greenPointAttachment->getValidationStatus()) {
                            $greenpointColor = 'danger';
                        } elseif (8 > $greenPointAttachment->getValidationStatus()) {
                            $greenpointColor = 'warning';
                        } else {
                            $greenpointColor = 'success';
                        }
                    }
                    ?>
                    <tr>
                        <th><?= $attachmentType->getLabel() ?></th>
                        <td><?= $greenpointFinalStatus ?></td>
                        <td class="<?= $greenpointColor?>"><?= $greenpointLabel ?></td>
                        <td class="text-center">
                            <a href="<?= $this->lurl ?>/attachment/download/id/<?= $attachment->getId() ?>/file/<?= urlencode($attachment->getPath()) ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le document">
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th>Mandat</th>
                    <td></td>
                    <td></td>
                    <td class="text-center">
                        <?php if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) : ?>
                            <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $this->clients_mandats->name ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le document">
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (false === empty($this->transfers)) : ?>
    <div class="row">
        <div class="col-md-12">
            <h3>Documents de transfert (en cas de succession)</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Fichier</th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->transfers as $transfer) : ?>
                        <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $transfer */ ?>
                        <?php foreach ($transfer->getAttachments() as $transferAttachment) : ?>
                            <tr>
                                <th><?= $transferAttachment->getAttachment()->getType()->getLabel() ?></th>
                                <td class="text-center">
                                    <a href="<?= $this->lurl ?>/attachment/download/id/<?= $transferAttachment->getAttachment()->getId() ?>/file/<?= urlencode($transferAttachment->getAttachment()->getPath()) ?>">
                                        <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le document">
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="attention vigilance-status-<?= $this->vigilanceStatus['status'] ?>">
            <?= $this->vigilanceStatus['message'] ?>
        </div>
    </div>
</div>

<?php if (false === empty($this->clientAtypicalOperations) || false === empty($this->vigilanceStatusHistory)) : ?>
    <div class="row">
        <div class="col-md-12">
            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                <button class="btn-primary" id="btn-atypical-operation">Voir les opérations atypiques</button>
            <?php endif; ?>

            <?php if (false === empty($this->vigilanceStatusHistory)) : ?>
                <button class="btn-primary" id="btn-vigilance-history">Voir l'historique de vigilance</button>
            <?php endif; ?>

            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                <div id="atypical-operation" style="display: none; margin-top: 15px">
                    <h4>Liste des opérations atypiques détéctés</h4>
                    <?php
                        $this->atypicalOperations = $this->clientAtypicalOperations;
                        $this->showActions        = false;
                        $this->showUpdated        = true;
                        $this->fireView('../client_atypical_operation/detections_table');
                    ?>
                </div>
            <?php endif; ?>

            <?php if (false === empty($this->clientAtypicalOperations)) : ?>
                <div id="vigilance-history" style="display: none; margin-top: 15px">
                    <h4>Historique de vigilance du client</h4>
                    <?php $this->fireView('../client_atypical_operation/vigilance_status_history'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    $(function () {
        $('#btn-atypical-operation').on('click', function () {
            $('#atypical-operation').toggle(0, function () {
                $('#btn-atypical-operation').text($('#atypical-operation').is(':visible') ? 'Masquer les opérations atypiques': 'Voir les opérations atypiques');
            });
        });

        $('#btn-vigilance-history').on('click', function () {
            $('#vigilance-history').toggle(0, function () {
                $('#btn-atypical-operation').text($('#atypical-operation').is(':visible') ? 'Masquer l\'historique de vigilance': 'Voir l\'historique de vigilance');
            });
        });
    })
</script>

