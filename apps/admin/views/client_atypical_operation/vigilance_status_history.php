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
            <td style="border-radius: 7px; font-weight: bold; font-size: 14px; background-color: <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusColor[$vigilanceStatus->getVigilanceStatus()] ?>;">
                <a target="_blank" href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lendersAccount->findOneBy(['idClientOwner' => $vigilanceStatus->getClient()->getIdClient()])->getIdLenderAccount() ?>"><?= $vigilanceStatus->getClient()->getIdClient() ?></a>
            </td>
            <td><?= $vigilanceStatus->getClient()->getPrenom() . ' ' . $vigilanceStatus->getClient()->getNom() ?></td>
            <td><?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel[$vigilanceStatus->getVigilanceStatus()] ?></td>
            <td>
                <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_CRON === $vigilanceStatus->getIdUser()) : ?>
                    Cron
                <?php elseif (\Unilend\Bundle\CoreBusinessBundle\Entity\Users::USER_ID_FRONT === $vigilanceStatus->getIdUser()) : ?>
                    Front
                <?php else : ?>
                    <?php
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users $user */
                    $user = $this->userEntity->find($vigilanceStatus->getIdUser())
                    ?>
                    <?= $user->getName() . ' ' . $user->getFirstname() ?>
                <? endif; ?>
            </td>
            <td><?= htmlentities($vigilanceStatus->getUserComment()) ?></td>
            <td><?= $vigilanceStatus->getAdded()->format('d/m/Y - H\hi') ?></td>
        </tr>
        <?php ++$i; ?>
    <?php endforeach; ?>
    </tbody>
</table>
