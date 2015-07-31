<h1>Ratios <?=$this->dates->tableauMois['fr'][$this->month].' '.$this->year?></h1>
<table class="ratioDashboard">
    <tr>
        <th>% Dossier :</th>
        <td><?=number_format($this->ratioProjects,2,',',' ')?> %</td>
        
        <th>Montant déposé moyen :</th>
        <td><?=number_format($this->moyenneDepotsFonds,2,',',' ')?> €</td>
        
        <th>part de reprêt sur 1 financement :</th>
        <td><?=number_format($this->tauxRepret,2,',',' ')?> %</td>
        
        <th>Taux attrition :</th>
        <td><?=number_format($this->tauxAttrition,2,',',' ')?> %</td>
    </tr>
</table>
