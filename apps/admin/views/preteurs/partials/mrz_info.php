<h2>Informations MRZ</h2>
<div class="col-md-6">
    <h3>Prêteur</h3>
    <table class="table table-condensed">
        <tr>
            <td>Nationalité :</td>
            <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityNationality() : '' ?></td>
        </tr>
        <tr>
            <td>Pays émetteur :</td>
            <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityIssuingCountry() : '' ?></td>
        </tr>
        <tr>
            <td>Autorité émettrice :</td>
            <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityIssuingAuthority() : '' ?></td>
        </tr>
        <tr>
            <td>N°. de la pièce :</td>
            <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityDocumentNumber() : '' ?></td>
        </tr>
    </table>
</div>
<div class="col-md-6">
    <?php if (false === empty($this->hostIdentityMRZData)) : ?>
        <h2>Hébergeur</h2>
        <table class="table table-condensed">
            <tr>
                <td>Nationalité :</td>
                <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityNationality() : '' ?></td>
            </tr>
            <tr>
                <td>Pays émetteur :</td>
                <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityIssuingCountry() : '' ?></td>
            </tr>
            <tr>
                <td>Autorité émettrice :</td>
                <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityIssuingAuthority() : '' ?></td>
            </tr>
            <tr>
                <td>N°. de la pièce :</td>
                <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityDocumentNumber() : '' ?></td>
            </tr>
        </table>
    <?php endif; ?>
</div>

