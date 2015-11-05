<h1>Ratios <?=$this->dates->tableauMois['fr'][$this->month].' '.$this->year?></h1>
<table class="ratioDashboard">
    <tr>
        <th>% Dossier :</th>
        <td><?= $this->ficelle->formatNumber($this->ratioProjects) ?> %</td>
        <th>Montant déposé moyen :</th>
        <td><?= $this->ficelle->formatNumber($this->moyenneDepotsFonds) ?> €</td>
        <th>part de reprêt sur 1 financement :</th>
        <td><?= $this->ficelle->formatNumber($this->tauxRepret) ?> %</td>
        <th>Taux attrition :</th>
        <td><?= $this->ficelle->formatNumber($this->tauxAttrition) ?> %</td>
    </tr>
</table>
