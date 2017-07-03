<div id="popup" class="takeover-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <?php if (empty($this->companies)) : ?>
        <h1>Aucunne société trouvée</h1>
        <a href="/company/add/<?= $this->siren ?>" class="btn_link" target="_blank">Créer la société</a>
    <?php else : ?>
        <h1>Sélectionnez une société attacher à <?= $this->partner->getIdCompany()->getName() ?></h1>
        <form method="post" action="<?= $this->lurl ?>/partner/third_party_add/<?= $this->partner->getId() ?>">
            <fieldset>
                <?php
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $company */
                foreach ($this->companies as $company) : ?>
                    <label>
                        <input type="radio" name="id_company" value="<?= $company->getIdCompany() ?>" required>
                        <?= $company->getName() ?>
                    </label>
                    (<a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $company->getIdClientOwner() ?>" target="_blank"><?= $company->getIdClientOwner() ?></a>)
                    <br>
                <?php endforeach; ?>
            </fieldset>
            <br><br>
            <fieldset>
                <label>Type de tiers
                    <select name="third_party_type" class="select">
                        <?php
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\PartnerThirdPartyType $type */
                        foreach ($this->thirdPartyTypes as $type) : ?>
                            <option value="<?= $type->getId() ?>"><?= $this->translator->trans('partner_third-party-type-' . $type->getLabel()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div style="margin-top: 15px; text-align: right">
                    <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
                    <input type="submit" value="Sélectionner" class="btn">
                </div>
            </fieldset>
        </form>
    <?php endif; ?>
</div>
