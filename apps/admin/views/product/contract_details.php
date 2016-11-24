<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteur">Emprunteur</a> -</li>
        <li><a href="<?=$this->lurl?>/product" title="Gestion des produits">Gestion des produits</a> -</li>
        <li>Consultation d'un contrat sous-jacent</li>
    </ul>
    <h1>Contrat sous-jacent</h1>
    <table class="form">
        <tr>
            <th>Nom :</th>
            <td><?= $this->translator->trans('contract-type-label_' . $this->contract->label) ?></td>
        </tr>
        <tr>
            <th>Type(s) de prêteur éligible :</th>
            <td>
                <?php if (empty($this->lenderType)) : ?>
                    pas de contrôle
                <?php else:
                    foreach ($this->lenderType as $type) {
                        switch ($type) {
                            case 1 :
                                echo '<li>Physique</li>';
                                break;
                            case 2 :
                                echo '<li>Morale</li>';
                                break;
                            case 3 :
                                echo '<li>Physique etrangère</li>';
                                break;
                            case 4 :
                                echo '<li>Morale etrangère</li>';
                                break;
                        }
                    }
                    endif;
                ?>
            </td>
        </tr>
        <tr>
            <th>Montant de prêt max par projet :</th>
            <td><?= isset($this->loanAmountMax[0]) ? $this->loanAmountMax[0] : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Quantité de prêt max par projet :</th>
            <td><?= isset($this->loanQtyMax[0]) ? $this->loanQtyMax[0] : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Durée de prêt max :</th>
            <td><?= isset($this->loanDurationMax[0]) ? $this->loanDurationMax[0] : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Eligibilité d'Autolend :</th>
            <td><?= isset($this->eligibilityAutobid[0]) ? ($this->eligibilityAutobid[0] == 1 ? 'éligible' : 'non éligible') : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Jours de creation min :</th>
            <td><?= isset($this->creationDaysMin[0]) ? $this->creationDaysMin[0] : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>RCS :</th>
            <td><?= isset($this->rcs[0]) ? ($this->rcs[0] == 1 ? 'La société doit être RCS.' : 'La société doit être non RCS.') : 'pas de contrôle' ?></td>
        </tr>
    </table>
</div>
