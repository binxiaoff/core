<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{BankAccount, Projects};

?>
<script>
    $('body').on('click', '[data-project]', function (event) {
        var projectId = $(this).data('project')
        if (projectId && !$(event.target).is('a') && !$(event.target).is('img')) {
            $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + projectId)
        }
    })
</script>

<div id="contenu">
    <h1>Prescripteur</h1>
    <h2><?= $this->clients->nom ?> <?= $this->clients->prenom ?></h2>
    <?php if (false === empty($_SESSION['error_email_exist'])) : ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?php unset($_SESSION['error_email_exist']); ?>
    <?php endif; ?>

    <form method="post" action="<?= $this->lurl ?>/prescripteurs/edit/<?= $this->prescripteurs->id_prescripteur ?>">
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th>Civilité</th>
                <td colspan="3">
                    <input <?= $this->clients->civilite === 'Mme' ? 'checked' : '' ?> type="radio" name="civilite" id="civilite_mme" value="Mme"/>
                    <label for="civilite_mme">Madame</label>

                    <input <?= $this->clients->civilite === 'M.' ? 'checked' : '' ?> type="radio" name="civilite" id="civilite_m" value="M."/>
                    <label for="civilite_m">Monsieur</label>
                </td>
            </tr>
            <tr>
                <th><label for="nom">Nom</label></th>
                <td><input type="text" name="nom" id="nom" class="input_large" value="<?= $this->clients->nom ?>"/></td>
                <th><label for="prenom">Prénom</label></th>
                <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?= $this->clients->prenom ?>"/></td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="text" name="email" id="email" class="input_large" value="<?= $this->clients->email ?>"/></td>
                <th><label for="telephone">Téléphone</label></th>
                <td><input type="text" name="telephone" id="telephone" class="input_large" value="<?= $this->clients->telephone ?>"/></td>
            </tr>
            <tr>
                <th><label for="adresse">Adresse</label></th>
                <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value="<?= $this->clients_adresses->adresse1 ?>"/></td>
            </tr>
            <tr>
                <th><label for="cp">Code postal</label></th>
                <td><input type="text" name="cp" id="cp" class="input_large" value="<?= $this->clients_adresses->cp ?>"/></td>
                <th><label for="ville">Ville</label></th>
                <td><input type="text" name="ville" id="ville" class="input_large" value="<?= $this->clients_adresses->ville ?>"/></td>
            </tr>
            <tr>
                <th><label for="company_name">Raison sociale</label></th>
                <td><input type="text" name="company_name" id="company_name" class="input_large" value="<?= $this->companies->name ?>"/></td>
                <th><label for="siren">SIREN</label></th>
                <td><input type="text" name="siren" id="siren" class="input_large" value="<?= $this->companies->siren ?>"/></td>
            </tr>
            <tr>
                <th><label for="iban">IBAN</label></th>
                <td><input type="text" name="iban" id="iban" class="input_large" value="<?= $this->bankAccount instanceof BankAccount ? $this->bankAccount->getIban() : '' ?>"/></td>
                <th><label for="bic">BIC</label></th>
                <td><input type="text" name="bic" id="bic" class="input_large" value="<?= $this->bankAccount instanceof BankAccount ? $this->bankAccount->getBic() : '' ?>"/></td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="form_edit_prescripteur"/>
                    <button type="submit" class="btn-primary">Valider</button>
                </th>
            </tr>
        </table>
    </form>
</div>

<div style="margin: 30px auto; padding: 10px 20px 20px; background-color: #fff; text-align: left;">
    <h1>Liste des projets</h1>
    <?php if (count($this->projects) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID projet</th>
                    <th>Nom</th>
                    <th>SIREN</th>
                    <th>Raison sociale</th>
                    <th>Montant</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th>Date demande</th>
                    <th>Analyste</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php /** @var Projects $project */ ?>
                <?php foreach ($this->projects as $project) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> data-project="<?= $project->getIdProject() ?>">
                        <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>"><?= $project->getIdProject() ?></a></td>
                        <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>"><?= $project->getTitle() ?></a></td>
                        <td><a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getSiren() ?></a></td>
                        <td><a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getName() ?></a></td>
                        <td class="text-right"><?= $this->ficelle->formatNumber($project->getAmount(), 0) ?> €</td>
                        <td><?= empty($project->getPeriod()) ? '' : $project->getPeriod() . ' mois' ?></td>
                        <td><?= $this->projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel() ?></td>
                        <td><?= $project->getAdded()->format('d/m/Y') ?></td>
                        <td><?= $project->getIdAnalyste() && $project->getIdAnalyste()->getIdUser() ? $project->getIdAnalyste()->getFirstname() . ' ' . $project->getIdAnalyste()->getName() : '' ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $project->getTitle() ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        Aucun projet
    <?php endif; ?>
</div>
