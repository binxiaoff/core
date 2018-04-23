<div class="row">
    <div class="form-group col-md-3">
        <input type="radio" name="civilite" id="civilite1" <?= (null !== $this->client->getCivilite() && $this->client->getCivilite() == 'Mme' ? 'checked' : '') ?> value="Mme"> <label for="civilite1">Madame</label>
        <input type="radio" name="civilite" id="civilite2" <?= (null !== $this->client->getCivilite() && $this->client->getCivilite() == 'M.' ? 'checked' : '') ?> value="M."> <label for="civilite2">Monsieur</label>
    </div>
</div>
<div class="row">
    <div class="form-group col-md-3">
        <label for="nom-famille">Nom de famille</label>
        <input type="text" name="nom-famille" id="nom-famille" class="form-control" value="<?= $this->client->getNom() ?? '' ?>">
    </div>
    <div class="form-group col-md-3">
        <label for="nom-usage">Nom d'usage</label>
        <input type="text" name="nom-usage" id="nom-usage" class="form-control" value="<?= $this->client->getNomUsage() ?? '' ?>">
    </div>
    <div class="form-group col-md-3">
        <label for="prenom">Prénom</label>
        <input type="text" name="prenom" id="prenom" class="form-control" value="<?= $this->client->getPrenom() ?? '' ?>">
    </div>
</div>
<div class="row">
    <div class="form-group col-md-3">
        <label for="email">Email</label>
        <input type="text" name="email" id="email" class="form-control" value="<?= $this->client->getEmail() ?? '' ?>">
    </div>
    <div class="form-group col-md-3">
        <label for="phone">Téléphone</label>
        <input type="text" name="phone" id="phone" class="form-control" value="<?= $this->client->getTelephone() ?? '' ?>">
    </div>
    <div class="form-group col-md-3">
        <label for="mobile">Mobile</label>
        <input type="text" name="mobile" id="mobile" class="form-control" value="<?= $this->client->getMobile() ?? '' ?>">
    </div>
</div>
<div class="row">
    <div class="form-group col-md-3">
        <label for="naissance">Naissance</label>
        <input type="text" id="naissance" name="naissance" class="form-control" value="<?= null !== $this->client->getNaissance() ? $this->client->getNaissance()->format('d/m/Y') : '' ?>"/>
    </div>
    <div class="form-group col-md-3">
        <label for="com-naissance">Commune de naissance</label>
        <input type="text" name="com-naissance" id="com-naissance" class="form-control" value="<?= $this->client->getVilleNaissance() ?? '' ?>" data-autocomplete="birth_city">
        <input type="hidden" id="insee_birth" name="insee_birth" value="<?= $this->client->getInseeBirth() ?? '' ?>">
    </div>
</div>
<div class="row">
    <div class="form-group col-md-3">
        <label for="id_pays_naissance">Pays de naissance</label>
        <select name="id_pays_naissance" id="id_pays_naissance" class="form-control">
            <?php foreach ($this->lPays as $pays) : ?>
                <option <?= ($this->client->getIdPaysNaissance() == $pays['id_pays'] ? 'selected' : '') ?> value="<?= $pays['id_pays'] ?>"><?= $pays['fr'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group col-md-3">
        <label for="nationalite">Nationalité</label>
        <select name="nationalite" id="nationalite" class="form-control">
            <?php foreach ($this->lNatio as $p) : ?>
                <option <?= ($this->client->getIdNationalite() == $p['id_nationalite'] ? 'selected' : '') ?> value="<?= $p['id_nationalite'] ?>"><?= $p['fr_f'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<hr>
<div class="row">
    <?php if (null == $this->client->getIdAddress() && null == $this->lastModifiedAddress) : ?>
        <div class="col-md-6">
            <h3>Adresse fiscale</h3>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger" role="alert">Le client n'a pas d'adresse fiscale</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (null !== $this->client->getIdAddress()) : ?>
        <div class="col-md-6">
            <h3>Adresse fiscale validée</h3>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="main_address">Adresse</label>
                    <input type="text" name="main_address" id="main_address" value="<?= $this->client->getIdAddress()->getAddress() ?>" class="form-control" disabled>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="main_zip">Code postal</label>
                    <input type="text" name="main_zip" id="main_zip" value="<?= $this->client->getIdAddress()->getZip() ?>" class="form-control" disabled>
                </div>
                <div class="form-group col-md-6">
                    <label for="main_city">Ville</label>
                    <input type="text" name="main_city" id="main_city" value="<?= $this->client->getIdAddress()->getCity() ?>" class="form-control" disabled>
                </div>
                <div class="form-group col-md-6">
                    <label for="main_country">Pays</label>
                    <select name="main_country" id="main_country" class="form-control" disabled>
                        <?php foreach ($this->lPays as $pays) : ?>
                            <option <?= ($this->client->getIdAddress()->getIdCountry()->getIdPays() == $pays['id_pays'] ? 'selected' : '') ?> value="<?= $pays['id_pays'] ?>">
                                <?= $pays['fr'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    <? endif; ?>
    <?php if (null !== $this->lastModifiedAddress && $this->lastModifiedAddress !== $this->client->getIdAddress()): ?>
        <div class="col-md-6">
            <h3>Adresse fiscale en attente de validation</h3>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="adresse">Adresse</label>
                    <input type="text" name="adresse" id="adresse" value="<?= $this->lastModifiedAddress->getAddress() ?>" class="form-control" disabled>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="cp">Code postal</label>
                    <input type="text" name="cp" id="cp" value="<?= $this->lastModifiedAddress->getZip() ?>" class="form-control" disabled>
                </div>
                <div class="form-group col-md-6">
                    <label for="ville">Ville</label>
                    <input type="text" name="ville" id="ville" value="<?= $this->lastModifiedAddress->getCity() ?>" class="form-control" disabled>
                </div>
                <div class="form-group col-md-6">
                    <label for="id_pays">Pays</label>
                    <select name="id_pays" id="id_pays" class="form-control" disabled>
                        <?php foreach ($this->lPays as $pays) : ?>
                            <option <?= ($this->lastModifiedAddress->getIdCountry()->getIdPays() == $pays['id_pays'] ? 'selected' : '') ?> value="<?= $pays['id_pays'] ?> ">
                                <?= $pays['fr'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<hr>
<div class="form-group">
    <input type="checkbox" name="meme-adresse" id="meme-adresse"<?= ($this->samePostalAddress ? ' checked' : '') ?> disabled>
    <label for="meme-adresse">Mon adresse de correspondance est identique à mon adresse fiscale </label>
</div>
<div class="postal-address"<?= ($this->samePostalAddress ? ' style="display:none;"' : '') ?>>
    <h3>Adresse postale</h3>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="adresse2">Adresse</label>
            <input type="text" name="adresse2" id="adresse2" value="<?= $this->client->getIdPostalAddress() ? $this->client->getIdPostalAddress()->getAddress() : '' ?>" class="form-control" disabled>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-3">
            <label for="cp2">Code postal</label>
            <input type="text" name="cp2" id="cp2" value="<?= $this->client->getIdPostalAddress() ? $this->client->getIdPostalAddress()->getZip() : '' ?>" class="form-control" disabled>
        </div>
        <div class="form-group col-md-3">
            <label for="ville2">Ville</label>
            <input type="text" name="ville2" id="ville2" value="<?= $this->client->getIdPostalAddress() ? $this->client->getIdPostalAddress()->getCity() : '' ?>" class="form-control" disabled>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="id_pays2">Pays</label>
            <select name="id_pays2" id="id_pays2" class="form-control" disabled>
                <?php foreach ($this->lPays as $pays) : ?>
                    <option <?= (null !== $this->client->getIdPostalAddress() ? $this->client->getIdPostalAddress()->getIdCountry()->getIdPays() == $pays['id_pays'] ? 'selected' : '' : '' ) ?> value="<?= $pays['id_pays'] ?>">
                        <?= $pays['fr'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
