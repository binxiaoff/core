<h2>Informations MRZ</h2>
<?php if (empty($this->lenderIdentityMRZData)) : ?>
    <div class="col-md-12">
        <p>Aucune donnée MRZ disponible pour ce prêteur</p>
    </div>
<?php else: ?>
    <div class="col-md-6">
        <h3>Prêteur</h3>
        <table class="table table-condensed">
            <tr>
                <td>Nationalité :</td>
                <td><?= $this->lenderIdentityMRZData->getIdentityNationality() ?? '' ?></td>
            </tr>
            <tr>
                <td>Pays émetteur :</td>
                <td><?= $this->lenderIdentityMRZData->getIdentityIssuingCountry() ?? '' ?></td>
            </tr>
            <tr>
                <td>Autorité émettrice :</td>
                <td><?= $this->lenderIdentityMRZData->getIdentityIssuingAuthority() ?? '' ?></td>
            </tr>
            <tr>
                <td>N°. de la pièce :</td>
                <td><?= $this->lenderIdentityMRZData->getIdentityDocumentNumber() ?? '' ?></td>
            </tr>
        </table>
    </div>
<?php endif; ?>
<?php if (false === empty($this->hostIdentityMRZData)) : ?>
    <div class="col-md-6">
        <h2>Hébergeur</h2>
        <table class="table table-condensed">
            <tr>
                <td>Nationalité :</td>
                <td><?= $this->hostIdentityMRZData->getIdentityNationality() ?? '' ?></td>
            </tr>
            <tr>
                <td>Pays émetteur :</td>
                <td><?= $this->hostIdentityMRZData->getIdentityIssuingCountry() ?? '' ?></td>
            </tr>
            <tr>
                <td>Autorité émettrice :</td>
                <td><?= $this->hostIdentityMRZData->getIdentityIssuingAuthority() ?? '' ?></td>
            </tr>
            <tr>
                <td>N°. de la pièce :</td>
                <td><?= $this->hostIdentityMRZData->getIdentityDocumentNumber() ?? '' ?></td>
            </tr>
        </table>
    </div>
<?php endif; ?>

