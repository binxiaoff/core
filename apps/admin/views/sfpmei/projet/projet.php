<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Société</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Raison sociale</th>
                <td><a href="<?= $this->lurl ?>/sfpmei/emprunteur/<?= $this->companies->id_client_owner ?>"><?= $this->companies->name ?></a></td>
            </tr>
            <tr>
                <th>SIREN</th>
                <td><?= $this->companies->siren ?></td>
            </tr>
            <tr>
                <th>Forme juridique</th>
                <td><?= $this->companies->forme ?></td>
            </tr>
            <tr>
                <th>Tribunal de commerce</th>
                <td><?= $this->companies->tribunal_com ?></td>
            </tr>
            <tr>
                <th>Activité</th>
                <td><?= empty($this->companies->activite) ? (empty($this->xerfi->naf) ? '' : $this->xerfi->label) : $this->companies->activite ?><?php if (false === empty($this->companies->code_naf)) : ?> (<?= $this->companies->code_naf ?>)<?php endif; ?></td>
            </tr>
            <tr>
                <th>Secteur de la société</th>
                <td>
                    <?php if (empty($this->companies->sector)) : ?>
                        -
                    <?php else : ?>
                        <?= $this->translator->trans('company-sector_sector-' . $this->companies->sector) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Capital social</th>
                <td><?= $this->ficelle->formatNumber($this->companies->capital, 0) ?>&nbsp;€</td>
            </tr>
            <tr>
                <th>Date de création</th>
                <td><?= empty($this->companies->date_creation) || $this->companies->date_creation === '0000-00-00' ? '' : \DateTime::createFromFormat('Y-m-d', $this->companies->date_creation)->format('d/m/Y') ?></td>
            </tr>
            <tr>
                <th>Adresse du siège social</th>
                <td>
                    <?= null !== $this->companyMainAddress ? $this->companyMainAddress->getAddress() : '' ?><br>
                    <?= null !== $this->companyMainAddress ? $this->companyMainAddress->getZip() : '' ?> <?= null !== $this->companyMainAddress ? $this->companyMainAddress->getCity() : '' ?>
                </td>
            </tr>
            <tr>
                <th>Adresse de correspondance</th>
                <td>
                    <?= null !== $this->companyPostalAddress ? $this->companyPostalAddress->getAddress() : '' ?><br>
                    <?= null !== $this->companyPostalAddress ? $this->companyPostalAddress->getZip() : '' ?> <?= null !== $this->companyPostalAddress ? $this->companyPostalAddress->getCity() : '' ?>
                </td>
            </tr>
            <tr>
                <th>Téléphone du siège social</th>
                <td><?= $this->companies->phone ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Projet</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="2">
                    <a href="<?= $this->furl ?>/projects/detail/<?= $this->projects->slug ?>" target="_blank"><?= $this->furl ?>/projects/detail/<?= $this->projects->slug ?></a>
                </td>
            </tr>
            <tr>
                <th>Date de la demande</th>
                <td><?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->projects->added)->format('d/m/Y H:i') ?></td>
            </tr>
            <tr>
                <th>Montant du prêt</th>
                <td><?= $this->ficelle->formatNumber($this->projects->amount, 0) ?>&nbsp;€</td>
            </tr>
            <tr>
                <th>Durée du prêt</th>
                <td><?= $this->projects->period ?>&nbsp;mois</td>
            </tr>
            <tr>
                <th>Motif de l'emprunt</th>
                <td><?= (false === empty($this->projects->id_borrowing_motive)) ? $this->translator->trans('borrowing-motive_motive-' . $this->projects->id_borrowing_motive) : '' ?></td>
            </tr>
            <tr>
                <th>Type de besoin</th>
                <td>
                    <?php foreach ($this->needs as $need) : ?>
                        <?php foreach ($need['children'] as $needChild) : ?>
                            <?php if ($this->projects->id_project_need == $needChild['id_project_need']) : ?>
                                <?= $needChild['label'] ?>
                                <?php break 2; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th>Niveau de risque</th>
                <?php $stars = ['A' => '5', 'B' => '4,5', 'C' => '4', 'D' => '3,5', 'E' => '3', 'F' => '2,5', 'G' => '2', 'H' => '1,5']; ?>
                <td><?= (isset($stars[$this->projects->risk])) ?  $stars[$this->projects->risk] . ' étoiles' : '' ?></td>
            </tr>
            <tr>
                <th>Date de publication</th>
                <td><?= ($this->projects->date_publication != '0000-00-00 00:00:00' ? $this->formatDate($this->projects->date_publication, 'd/m/Y H:i') : '') ?></td>
            </tr>
            <tr>
                <th>Date de retrait</th>
                <td><?= ($this->projects->date_retrait != '0000-00-00 00:00:00' ? $this->formatDate($this->projects->date_retrait, 'd/m/Y H:i') : '') ?></td>
            </tr>
            <?php if ($this->projects_pouvoir->get($this->projects->id_project, 'id_project') && $this->projects_pouvoir->status == \Unilend\Entity\ProjectsPouvoir::STATUS_SIGNED) : ?>
                <tr>
                    <th>Pouvoir</th>
                    <td>
                        <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>"><?= $this->projects_pouvoir->name ?></a>
                        <?php if ($this->projects_pouvoir->status_remb == \Unilend\Entity\ProjectsPouvoir::STATUS_REPAYMENT_VALIDATED) : ?>
                            <span style="color:green;">&nbsp;Validé</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-12">
        <?php if (false === empty($this->project_cgv->id)) : ?>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>CGV</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        CGV envoyées le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->project_cgv->added)->format('d/m/Y à H:i:s') ?>
                        (<a href="<?= $this->furl . $this->project_cgv->getUrlPath() ?>" target="_blank">PDF</a>)
                        <?php if (\Unilend\Entity\ProjectCgv::STATUS_SIGNED == $this->project_cgv->status && false === empty($this->project_cgv->updated)) : ?>
                            <strong>signées</strong> le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->project_cgv->updated)->format('d/m/Y à H:i:s') ?>
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
