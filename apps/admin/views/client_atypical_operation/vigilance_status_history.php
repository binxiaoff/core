<?php $i = 0; ?>
<table class="tablesorter vigilance_history">
    <thead>
    <tr>
        <th style="width:70px">ID client</th>
        <th style="width:150px">Prénom Nom</th>
        <th style="width:120px">Statut de vigilance</th>
        <th style="width:150px">Utilisateur</th>
        <th style="width:150px">Commentaire</th>
        <th style="width:140px">Date</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->vigilanceStatusHistory as $vigilanceStatus) : ?>
        <tr id="row-<?= $vigilanceStatus->getId() ?>" <?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
            <td>
                <span style="font-weight: bold; font-size: 14px" class="vigilance-status-<?= $vigilanceStatus->getVigilanceStatus() ?>">
                    <?php if (false === empty($this->hideEditLink)) : ?>
                        <?= $vigilanceStatus->getClient()->getIdClient() ?>
                    <?php else : ?>
                        <a target="_blank" href="<?= $this->lurl ?>/preteurs/edit/<?= $vigilanceStatus->getClient()->getIdClient() ?>"><?= $vigilanceStatus->getClient()->getIdClient() ?></a>
                    <?php endif; ?>
                </span>
            </td>
            <td><?= $vigilanceStatus->getClient()->getPrenom() . ' ' . $vigilanceStatus->getClient()->getNom() ?></td>
            <td><?= $this->translator->trans('client-vigilance_status-' . $vigilanceStatus->getVigilanceStatus()) ?></td>
            <td>
                <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_CRON === $vigilanceStatus->getIdUser()->getIdUser()) : ?>
                    Cron
                <?php elseif (\Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_FRONT === $vigilanceStatus->getIdUser()->getIdUser()) : ?>
                    Front
                <?php else : ?>
                    <?= $vigilanceStatus->getIdUser()->getFirstName() . ' ' .  $vigilanceStatus->getIdUser()->getName() ?>
                <? endif; ?>
            </td>
            <td><?= htmlentities($vigilanceStatus->getUserComment()) ?></td>
            <td><?= $vigilanceStatus->getAdded()->format('d/m/Y - H\hi') ?></td>
        </tr>
        <?php ++$i; ?>
    <?php endforeach; ?>
    </tbody>
</table>
