<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/stats/ventes" title="Stats">Stats</a> -</li>
        <li>Google Analytics</li>
    </ul>
	<h1>Google Analytics</h1>
    <form method="post" name="periode" id="periode" action="<?=$this->lurl?>/stats" enctype="multipart/form-data">
  		<table class="cont_periode">
        	<tr>
            	<td>
                	<table class="form_periode">
                        <tr>
                            <th>Choisissez un mois et une année</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="submit" name="prev" id="prev" value="<<" class="btn_periode" />
                                <input type="text" name="mois" id="mois" value="<?=($this->mois<10?'0'.str_replace('0','',$this->mois):$this->mois)?>" class="input_court center" />
                                <input type="text" name="annee" id="annee" value="<?=$this->annee?>" class="input_court center" />
                                <input type="submit" name="voir" id="voir" value="Voir" class="btn_periode" />
                                <input type="submit" name="next" value=">>" class="btn_periode" />
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                	<table class="form_periode">
                        <tr>
                            <th colspan="2">Choisissez une période précise</th>
                        </tr>
                        <tr>
                            <td class="multiple"><strong>Du</strong> <?=$this->dates->selectDateYearDesc($_POST['du-annee'].'-'.$_POST['du-mois'].'-'.$_POST['du-jour'],'du','select_periode')?></td>
                            <td rowspan="2" class="btn_tab"><input type="submit" name="intervalle" id="intervalle" value="Voir" class="btn_periode" /></td>
                        </tr>
                        <tr>
                            <td class="multiple"><strong>Au</strong> <?=$this->dates->selectDateYearDesc($_POST['au-annee'].'-'.$_POST['au-mois'].'-'.$_POST['au-jour'],'au','select_periode')?></td>
                        </tr>
                    </table>
                </td>
           	</tr>
       	</table>
	</form>
    <h2>Accès au compte Google Analytics sur la période du <?=$this->deb_jour.'/'.$this->deb_mois.'/'.$this->deb_annee?> au <?=$this->fin_jour.'/'.$this->fin_mois.'/'.$this->fin_annee?> sur <?=$this->nb_jours?> jour<?=($this->nb_jours>1?'s':'')?></h2>
    <p>
    	<a href="https://www.google.com/analytics/reporting/?reset=1&id=<?=$this->id_profile?>&pdr=<?=$this->deb_annee.$this->deb_mois.$this->deb_jour?>-<?=$this->fin_annee.$this->fin_mois.$this->fin_jour?>" target="_blank" title="Visualiser votre rapport Google Analytics pour cette période">
            <img src="<?=$this->surl?>/images/admin/analytics_logo.gif" alt="Visualiser votre rapport Google Analytics pour cette période" />
        </a>
  	</p>
    <h2>Statistiques du <?=$this->deb_jour.'/'.$this->deb_mois.'/'.$this->deb_annee?> au <?=$this->fin_jour.'/'.$this->fin_mois.'/'.$this->fin_annee?> sur <?=$this->nb_jours?> jour<?=($this->nb_jours>1?'s':'')?></h2>    
    <table class="tablesorter">
    	<thead>
            <tr>
                <th colspan="2">Fréquentation du site</th>
            </tr>
        </thead>
        <tbody>
        	<tr>
                <td width="50%">Visites</td>
                <td><strong><?=$this->ga->getVisits()?></strong></td>
            </tr>
            <tr class="odd">
                <td>Visites unique</td>
                <td><strong><?=$this->ga->getNewVisits()?></strong></td>
            </tr>
            <tr>
                <td>Pages vues</td>
                <td><strong><?=$this->ga->getPageviews()?></strong></td>
            </tr>        
        </tbody>
  	</table>
</div>