<script type="text/javascript">
	
	function annulerAttribution(id_project,id_reception){
		var conf = confirm("Voulez vous vraiment annuler le prélèvement ?");
		if(conf==true){
			var val = { id_project: id_project, id_reception: id_reception }
			$.post(add_url + '/ajax/annulerAttribution_project', val).done(function(data) {
				if(data != 'nok'){
					$(".num_project_"+id_reception).html('0');
					$(".num_client_"+id_reception).html('0');
					$(".preteur_projet_"+id_reception).html('-');
					$(".ajouter_"+id_reception).show();
					$(".rejete_"+id_reception).hide();
					$(".annuler_"+id_reception).hide();
					$(".statut_prelevement_"+id_reception).html('Reçu');
				}
			});
		}
	}
	
	function rejeteAttribution(id_project,id_reception){
		var conf = confirm("Voulez vous vraiment rejeter le prélèvement attribué au projet "+id_project+" ?");
		if(conf==true){
			var val = { id_project: id_project, id_reception: id_reception }
			$.post(add_url + '/ajax/rejeteAttribution_project', val).done(function(data) {
				if(data != 'nok'){
					$(".statut_prelevement_"+id_reception).html('Rejeté');
					$(".rejete_"+id_reception).hide();
					$(".annuler_"+id_reception).hide();
				}
			});
		}
	}
	
	$(document).ready(function(){
		
		jQuery.tablesorter.addParser({ id: "fancyNumber", is: function(s) { return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s); }, format: function(s) { return jQuery.tablesorter.formatFloat( s.replace(/,/g,'').replace(' €','').replace(' ','') ); }, type: "numeric" });
		
		$(".tablesorter").tablesorter({headers:{6:{sorter: false}}});	
		<?
		if($this->nb_lignes != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});		
		<?
		}
		?>
		$(".inline").colorbox({inline:true, width:"50%"});
		
	});
	<?
	if(isset($_SESSION['freeow']))
	{
	?>
		$(document).ready(function(){
			var title, message, opts, container;
			title = "<?=$_SESSION['freeow']['title']?>";
			message = "<?=$_SESSION['freeow']['message']?>";
			opts = {};
			opts.classes = ['smokey'];
			$('#freeow-tr').freeow(title, message, opts);
		});
	<?
	}
	?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/transferts" >Dépot de fonds</a> - </li>
        <li>Prélèvements</li>
    </ul>
	<h1>Liste des Prélèvements</h1>
    <?
	
	if(count($this->lprelevements) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>  
                    <th>ID</th>
                    <th>motif</th>
                    <th>montant</th>
                    <th>Statut</th>
                    <th>Id client</th>
                    <th>Id projet</th>
                    <th>Prêteur / Projet</th>
                    <th>Date</th>
                    <th>&nbsp;</th>  
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lprelevements as $v)
			{	
			?>
            	<tr<?=($i%2 == 1?'':' class="odd"')?> >
                    <td><?=$v['id_reception']?></td>
                    <td><?=$v['motif']?></td>
                    <td><?=number_format($v['montant']/100, 2, ',', ' ')?></td>
                    
                    <td class="statut_prelevement_<?=$v['id_reception']?>" ><?=$this->statusPrelevement[$v['status_bo']]?> <?=$this->types_remb[$v['type_remb']]?></td>
                    <td class="num_client_<?=$v['id_reception']?>"><?=$v['id_client']?></td>
                    <td class="num_project_<?=$v['id_reception']?>"><?=$v['id_project']?></td>
                    <td class="preteur_projet_<?=$v['id_reception']?>" align="center"><?=($v['id_project'] != 0?'Remb projet '.$v['id_project']:($v['id_client'] != 0?'Prélèvement Prêteur':'-'))?></td>
                    <td><?=date('d/m/Y',strtotime($v['added']))?></td>
                    <td align="center">
                        
                        
                        
                        <a <?=($v['id_client'] != 0?'style="display:none;"':'')?> class="thickbox ajouter_<?=$v['id_reception']?>" href="<?=$this->lurl?>/transferts/attribution_project/<?=$v['id_reception']?>">
                            <img src="<?=$this->surl?>/images/admin/edit.png" alt="Attibution" />
                        </a>
                        
                        
                          <?php /*?><img class="rejete_<?=$v['id_reception']?>" <?=(!in_array($v['status_bo'],array(1,2))?'style="display:none;cursor:pointer;"':'style="cursor:pointer;"')?> onclick="rejeteAttribution(<?=$v['id_project']?>,<?=$v['id_reception']?>)" src="<?=$this->surl?>/images/admin/edit.png" alt="Rejeté" />
                         
						<img class="annuler_<?=$v['id_reception']?>" <?=(!in_array($v['status_bo'],array(1,2))?'style="display:none;cursor:pointer;"':'style="cursor:pointer;"')?>  onclick="annulerAttribution(<?=$v['id_project']?>,<?=$v['id_reception']?>)" src="<?=$this->surl?>/images/admin/delete.png" alt="Attibution" /><?php */
						
						
						?>

                        <span class="rejet_annule_<?=$v['id_reception']?>">
                        
                        </span>
                        
                        <?
						if(in_array($v['status_bo'],array(1,2))){
							?>
							<img class="rejete_<?=$v['id_reception']?>" style="cursor:pointer;" onclick="rejeteAttribution(<?=$v['id_project']?>,<?=$v['id_reception']?>)" src="<?=$this->surl?>/images/admin/edit.png" alt="Rejeté" />
                         
							<img class="annuler_<?=$v['id_reception']?>" style="cursor:pointer;" onclick="annulerAttribution(<?=$v['id_project']?>,<?=$v['id_reception']?>)" src="<?=$this->surl?>/images/admin/delete.png" alt="Attibution" />
                            <?
						}
						?>
                    	
                        
                        <a class='inline' href="#inline_content_<?=$v['id_reception']?>">
                            <img src="<?=$this->surl?>/images/admin/modif.png" alt="Ligne" />
                        </a>
                        <div style="display:none;">
                    		<div id='inline_content_<?=$v['id_reception']?>' style='white-space: nowrap; padding:10px; background:#fff;'><?=$v['ligne']?></div>
                        </div>
                  	</td>
                </tr>   
            <?
				$i++;
            }
            ?>
            </tbody>
        </table>
        
        <?
		if($this->nb_lignes != '')
		{
		?>
			<table>
                <tr>
                    <td id="pager">
                        <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay" />
                        <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                        	<option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                       	</select>
                    </td>
                </tr>
            </table>
		<?
		}
		?>
	<?
    }
    else
    {
    ?>
     	
		<p>Il n'y a aucun recu.</p><?

    }
    ?>
</div>