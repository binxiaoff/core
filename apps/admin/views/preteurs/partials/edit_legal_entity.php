<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;

?>
<div>
    <h2>Société</h2>
    <div class="row">
        <div class="form-group col-md-3">
            <label for="raison-sociale">Raison sociale</label>
            <input type="text" name="raison-sociale" id="raison-sociale" value="<?= $this->companyEntity->getName() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="form-juridique">Forme juridque</label>
            <input type="text" name="form-juridique" id="form-juridique" value="<?= $this->companyEntity->getForme() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="capital-sociale">Capital social</label>
            <input type="text" name="capital-sociale" id="capital-sociale" value="<?= $this->companyEntity->getCapital() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="tribunal_com">Tribunal de Commerce</label>
            <input type="text" name="tribunal_com" id="tribunal_com" value="<?= $this->companyEntity->getTribunalCom() ?? '' ?>"  class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-3">
            <label for="siren">SIREN</label>
            <input type="text" name="siren" id="siren" value="<?= $this->companyEntity->getSiren() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="siret">SIRET</label>
            <input type="text" name="siret" id="siret" value="<?= $this->companyEntity->getSiret() ?? '' ?>" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-3">
            <label for="phone-societe">Téléphone</label>
            <input type="text" name="phone-societe" id="phone-societe" value="<?= null !== $this->companyEntity->getPhone() ? trim(chunk_split($this->companyEntity->getPhone(), 2, ' ')) : '' ?>" class="form-control">
        </div>
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
    <?php if (null !== $this->companyEntity->getIdAddress()) : ?>
        <div class="col-md-6">
            <h3>Adresse fiscale validée</h3>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="main_address">Adresse</label>
                    <input type="text" name="main_address" id="main_address" value="<?= $this->companyEntity->getIdAddress()->getAddress() ?? '' ?>" class="form-control" disabled>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="main_zip">Code postal</label>
                    <input type="text" name="main_zip" id="main_zip" value="<?= $this->companyEntity->getIdAddress()->getZip() ?? '' ?>" class="form-control" disabled>
                </div>
                <div class="form-group col-md-6">
                    <label for="main_city">Ville</label>
                    <input type="text" name="main_city" id="main_city" value="<?= $this->companyEntity->getIdAddress()->getCity() ?? '' ?>" class="form-control" disabled>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (null !== $this->lastModifiedAddress && $this->lastModifiedAddress !== $this->companyEntity->getIdAddress()): ?>
        <div class="col-md-6">
            <h3>Adresse fiscale en attente de validation</h3>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="adresse">Adresse</label>
                    <input type="text" name="adresse" id="adresse" value="<?= $this->lastModifiedAddress->getAddress() ?? '' ?>" class="form-control" disabled>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="cp">Code postal</label>
                    <input type="text" name="cp" id="cp" value="<?= $this->lastModifiedAddress->getZip() ?? '' ?>" class="form-control" disabled>
                </div>
                <div class="form-group col-md-6">
                    <label for="ville">Ville</label>
                    <input type="text" name="ville" id="ville" value="<?= $this->lastModifiedAddress->getCity() ?? '' ?>" class="form-control" disabled>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<hr>
<div class="form-group">
    <input type="checkbox" name="meme-adresse" id="meme-adresse"<?= ($this->samePostalAddress ? ' checked' : '') ?> disabled>
    <label for="meme-adresse">Mon adresse de correspondance est identique à mon adresse fiscale</label>
</div>
<div class="postal-address"<?= ($this->samePostalAddress ? ' style="display:none;"' : '') ?>>
    <h3>Adresse postale</h3>
    <div class="row">
        <div class="form-group col-md-6">
            <label for="adresse2">Adresse</label>
            <input type="text" name="adresse2" id="adresse2" value="<?= null !== $this->companyEntity->getIdPostalAddress() ? $this->companyEntity->getIdPostalAddress()->getAddress() : '' ?>" class="form-control" disabled>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-3">
            <label for="cp2">Code postal</label>
            <input type="text" name="cp2" id="cp2" value="<?= null !== $this->companyEntity->getIdPostalAddress() ? $this->companyEntity->getIdPostalAddress()->getZip(): '' ?>" class="form-control" disabled>
        </div>
        <div class="form-group col-md-3">
            <label for="ville2">Ville</label>
            <input type="text" name="ville2" id="ville2" value="<?= null !== $this->companyEntity->getIdPostalAddress() ? $this->companyEntity->getIdPostalAddress()->getCity() : '' ?>" class="form-control" disabled>
        </div>
    </div>
</div>
<hr>
<div>
    <h2>Contact</h2>
    <div class="row">
        <div class="form-group col-md-12">
            <input <?= (null !== $this->client->getCivilite() && $this->client->getCivilite() == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite_e" id="civilite_e1" value="Mme">
            <label for="civilite_e1">Madame</label>
            <input <?= (null !== $this->client->getCivilite() && $this->client->getCivilite() == 'M.' ? 'checked' : '') ?> type="radio" name="civilite_e" id="civilite_e2" value="M.">
            <label for="civilite_e2">Monsieur</label>
        </div>
        <div class="form-group col-md-3">
            <label for="nom_e">Nom</label>
            <input type="text" name="nom_e" id="nom_e" value="<?= $this->client->getNom() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="prenom_e">Prénom</label>
            <input type="text" name="prenom_e" id="prenom_e" value="<?= $this->client->getPrenom() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-6">
            <label for="fonction_e">Fonction</label>
            <input type="text" name="fonction_e" id="fonction_e" value="<?= $this->client->getFonction() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="email_e">Email</label>
            <input type="text" name="email_e" id="email_e" value="<?= $this->client->getEmail() ?? '' ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="phone_e">Téléphone</label>
            <input type="text" name="phone_e" id="phone_e" value="<?= $this->client->getTelephone() ? trim(chunk_split($this->client->getTelephone(), 2, ' ')) : '' ?>" class="form-control">
        </div>
    </div>
</div>
<div>
    <div class="form-group">
        <input<?= (Companies::CLIENT_STATUS_MANAGER == $this->companyEntity->getStatusClient() ? ' checked' : '') ?> type="radio" name="enterprise" id="enterprise1" value="1">
        <label for="enterprise1">Je suis le dirigeant de l'entreprise </label>
    </div>
    <div class="form-group">
        <input<?= (Companies::CLIENT_STATUS_DELEGATION_OF_POWER == $this->companyEntity->getStatusClient() ? ' checked' : '') ?> type="radio" name="enterprise" id="enterprise2" value="2">
        <label for="enterprise2">Je ne suis pas le dirigeant de l'entreprise mais je bénéficie d'une délégation de pouvoir </label>
    </div>
    <div class="form-group">
        <input<?= (Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $this->companyEntity->getStatusClient() ? ' checked' : '') ?> type="radio" name="enterprise" id="enterprise3" value="3">
        <label for="enterprise3"> Je suis un conseil externe de l'entreprise </label>
    </div>
</div>
<div class="row statut_dirigeant_e3>
    <div class="form-group col-md-3">
        <label for="status_conseil_externe_entreprise">Autre</label>
        <select name="status_conseil_externe_entreprise" id="status_conseil_externe_entreprise" class="form-control">
            <option value="0">Choisir</option>
            <?php foreach ($this->conseil_externe as $k => $conseil_externe) : ?>
                <option<?= ($this->companyEntity->getStatusConseilExterneEntreprise() == $k ? ' selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group col-md-3">
        <label for="preciser_conseil_externe_entreprise">Autre (préciser)</label>
        <input type="text" name="preciser_conseil_externe_entreprise" id="preciser_conseil_externe_entreprise" value="<?= $this->companyEntity->getPreciserConseilExterneEntreprise() ?>" class="form-control">
    </div>
</div>
<div class="row">
    <div class="col-md-12 statut_dirigeant_e societe"<?= ($this->companyEntity->getStatusClient() == Companies::CLIENT_STATUS_MANAGER ? ' style="display:none;"' : '') ?>>
        <h2 >Identification du dirigeant</h2>
        <div class="row">
            <div class="form-group col-md-12">
                <input <?= (null !== $this->companyEntity->getCiviliteDirigeant() && $this->companyEntity->getCiviliteDirigeant() == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite2_e" id="civilite21_e" value="Mme">
                <label for="civilite21_e">Madame</label>
                <input <?= (null !== $this->companyEntity->getCiviliteDirigeant() && $this->companyEntity->getCiviliteDirigeant() == 'M.' ? 'checked' : '') ?> type="radio" name="civilite2_e" id="civilite22_e" value="M.">
                <label for="civilite22_e">Monsieur</label>
            </div>
            <div class="form-group col-md-3">
                <label for="nom2_e">Nom</label>
                <input type="text" name="nom2_e" id="nom2_e" class="form-control" value="<?= $this->companyEntity->getNomDirigeant() ?? '' ?>">
            </div>
            <div class="form-group col-md-3">
                <label for="prenom2_e">Prénom</label>
                <input type="text" name="prenom2_e" id="prenom2_e" class="form-control" value="<?= $this->companyEntity->getPrenomDirigeant() ?? '' ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="fonction2_e">Fonction</label>
                <input type="text" name="fonction2_e" id="fonction2_e" class="form-control" value="<?= $this->companyEntity->getFonctionDirigeant() ?? '' ?>">
            </div>
            <div class="form-group col-md-3">
                <label for="email2_e">Email</label>
                <input type="text" name="email2_e" id="email2_e" class="form-control" value="<?= $this->companyEntity->getEmailDirigeant() ?? '' ?>">
            </div>
            <div class="form-group col-md-3">
                <label for="phone2_e">Téléphone</label>
                <input type="text" name="phone2_e" id="phone2_e" class="form-control" value="<?= $this->companyEntity->getPhoneDirigeant() ?? '' ?>">
            </div>
        </div>
    </div>
</div>
