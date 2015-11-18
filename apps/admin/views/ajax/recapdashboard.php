<h1><?= $this->dates->tableauMois['fr'][$this->month] . ' ' . $this->year ?></h1>
<table class="recapDashboard">
    <tr>
        <th>Prêteurs connectés :</th>
        <td><?= $this->nbPreteurLogin ?></td>
        <th>Fonds déposés :</th>
        <td><?= $this->ficelle->formatNumber($this->nbFondsDeposes) ?> €</td>
        <th>Emprunteurs connectés :</th>
        <td><?= $this->nbEmprunteurLogin ?></td>
        <th>Dossiers déposés :</th>
        <td><?= $this->nbDepotDossier ?></td>
    </tr>
    <tr>
        <th>Prêteurs inscrits :</th>
        <td><?= $this->nbInscriptionPreteur ?></td>
        <th>Fonds prêtés :</th>
        <td><?= $this->ficelle->formatNumber($this->nbFondsPretes) ?> €</td>
        <th>Emprunteurs inscrits :</th>
        <td><?= $this->nbInscriptionEmprunteur ?></td>
        <th>Total capital restant dus :</th>
        <td><?= $this->ficelle->formatNumber($this->TotalCapitalRestant) ?> €</td>
    </tr>
</table>
