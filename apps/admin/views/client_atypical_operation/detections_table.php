<?php $i = 0; ?>
<table class="tablesorter vigilance_history">
    <thead>
    <tr>
        <th style="width:70px">ID client</th>
        <th style="width:150px">Prénom Nom</th>
        <th style="width:250px">Règle de vigilance</th>
        <th style="width:120px">Statut de vigilance</th>
        <th style="width:120px">Valeur atypique</th>
        <th style="width:150px">Utilisateur</th>
        <th style="width:140px">Date de détection</th>
        <?php if ($this->showUpdated) : ?>
            <th style="width:140px">Date de modification</th>
        <?php endif; ?>
        <th style="width:150px">Commentaire</th>
        <?php if ($this->showActions) : ?>
            <th style="width:50px">Actions</th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->atypicalOperations as $atypicalOperation) : ?>
        <?php
            $currentStatus = $this->clientVigilanceStatusHistory->findOneBy(['client' => $atypicalOperation->getClient()], ['added' => 'DESC']);
        ?>
        <tr id="row-<?= $atypicalOperation->getId() ?>" <?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
            <td >
                <span style="font-weight: bold; font-size: 14px" class="vigilance-status-<?= $currentStatus->getVigilanceStatus() ?>">
                    <?php if (false === empty($this->hideEditLink)) : ?>
                        <?= $atypicalOperation->getClient()->getIdClient() ?>
                    <?php else : ?>
                        <a target="_blank" href="<?= $this->lurl ?>/preteurs/edit/<?= $atypicalOperation->getClient()->getIdClient() ?>">
                            <?= $atypicalOperation->getClient()->getIdClient() ?>
                        </a>
                    <?php endif; ?>
                </span>
            </td>
            <td><?= $atypicalOperation->getClient()->getPrenom() . ' ' . $atypicalOperation->getClient()->getNom() ?></td>
            <td><?= $atypicalOperation->getRule()->getName() ?></td>
            <td><?= $this->translator->trans('client-vigilance_status-' . $atypicalOperation->getRule()->getVigilanceStatus()) ?></td>
            <td><?= $atypicalOperation->getAtypicalValue() ?></td>
            <td>
                <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_CRON === $atypicalOperation->getIdUser()->getIdUser()) : ?>
                    Cron
                <?php elseif (\Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_FRONT === $atypicalOperation->getIdUser()->getIdUser()) : ?>
                    Front
                <?php else : ?>
                    <?= $atypicalOperation->getIdUser()->getFirstname() . ' ' . $atypicalOperation->getIdUser()->getName() ?>
                <? endif; ?>
            </td>
            <td><?= $atypicalOperation->getAdded()->format('d/m/Y H:i') ?></td>
            <?php if ($this->showUpdated) : ?>
            <td>
                <?php if (false === empty($atypicalOperation->getUpdated())) : ?>
                    <?= $atypicalOperation->getUpdated()->format('d/m/Y H:i') ?>
                <?php endif; ?>
            </td>
            <?php endif; ?>
            <td><?= htmlentities($atypicalOperation->getUserComment()) ?></td>
            <?php if ($this->showActions) : ?>
                <td>
                    <?php if ($atypicalOperation->getDetectionStatus() === \Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation::STATUS_PENDING) : ?>
                        <a class="thickbox" href="<?= $this->lurl ?>/client_atypical_operation/process_detection_box/doubt/<?= $atypicalOperation->getId() ?>">
                            <img class="process-detection" src="<?= $this->surl ?>/images/admin/edit.png" alt="Levée du doute" title="Levée du doute"/>
                        </a>
                        <a class="thickbox" href="<?= $this->lurl ?>/client_atypical_operation/process_detection_box/ack/<?= $atypicalOperation->getId() ?>">
                            <img class="process-detection" src="<?= $this->surl ?>/images/admin/modif.png" alt="Soumettre à SFPMEI" title="Soumettre à SFPMEI"/>
                        </a>
                    <?php elseif ($atypicalOperation->getDetectionStatus() === \Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation::STATUS_WAITING_ACK) : ?>
                        <a class="thickbox" href="<?= $this->lurl ?>/client_atypical_operation/process_detection_box/doubt/<?= $atypicalOperation->getId() ?>">
                            <img class="process-detection" src="<?= $this->surl ?>/images/admin/edit.png" alt="Levée du doute" title="Levée du doute"/>
                        </a>
                    <?php else : ?>
                        &nbsp;
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php ++$i; ?>
    <?php endforeach; ?>
    </tbody>
</table>
