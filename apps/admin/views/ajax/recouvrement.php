<table class="tablesorter">
    <tbody>
        <tr>
            <td class="first">Capital échus</td>
            <td><?= number_format($this->CapitalEchu, 2, ',', ' ') ?> €</td>
        </tr>
        <tr class="odd">
            <td class="first">Intérêts échus</td>
            <td><?= number_format($this->InteretsEchu, 2, ',', ' ') ?> €</td>
        </tr>
        <tr>
            <td class="first">Intérêts courus</td>
            <td><?= number_format($this->interetsCourus, 2, ',', ' ') ?> €</td>
        </tr>
        <tr class="odd">
            <td class="first">Capital restant dû</td>
            <td><?= number_format($this->CapitalRestantDu, 2, ',', ' ') ?> €</td>
        </tr>
        <tr>
            <td class="first">Montant recouvrés</td>
            <td><?= number_format($this->montantRecouvre, 2, ',', ' ') ?> €</td>
        </tr>
        <tr class="odd">
            <td class="first" style="vertical-align: middle;">Fichier csv (répartition)</td>
            <td><input type="file" name="csv"></td>
        </tr>
    </tbody>
</table>