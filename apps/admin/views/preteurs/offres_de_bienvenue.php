<script type="text/javascript">
	$(document).ready(function(){
		
		jQuery.tablesorter.addParser({ id: "fancyNumber", is: function(s) { return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s); }, format: function(s) { return jQuery.tablesorter.formatFloat( s.replace(/,/g,'').replace(' €','').replace(' ','') ); }, type: "numeric" }); 
		$(".tablesorter").tablesorter();
		
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		$("#datepik_1").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		$("#datepik_2").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		
		<?
		if($this->nb_lignes != '')
		{
		?>
			$(".tablesorter").tablesorterPager({container: $("#pager"),positionFixed: false,size: <?=$this->nb_lignes?>});		
		<?
		}
		?>
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

<style type="text/css">
	table.formColor{width:697px;}
	.select{width:251px;}
	.fenetre_offres_de_bienvenues{width:697px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;}
</style>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li>Gestion de offre de bienvenue</li>
    </ul>
    <h1>Gestion offre de bienvenue</h1>
    
    <div class="fenetre_offres_de_bienvenues">
        <form method="post" name="form_offres" id="form_offres" enctype="multipart/form-data" action="" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="datepik_1">Debut de l'offre :</label></th>
                        <td><input type="text" name="debut" id="datepik_1" class="input_dp" value="<?=$this->debut?>"/></td>
                        <th><label for="datepik_2">Fin de l'offre :</label></th>
                        <td><input type="text" name="fin" id="datepik_2" class="input_dp" value="<?=$this->fin?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="montant">Montant de l'offre :</label></th>
                        <td><input type="text" name="montant" id="montant" class="input_moy" value="<?=$this->montant?>"/> €</td>
                        <th><label for="montant">Dépenses max :</label></th>
                        <td><input type="text" name="montant_limit" id="montant_limit" class="input_moy" value="<?=$this->montant_limit?>"/> €</td>
                    </tr>
                    <tr>
                        <th><label>Motif :</label></th>
                        <td><?=$this->motifOffreBienvenue?></td>
                        <th><label for="montant">Solde Reel disponible :</label></th>
                        <td><?=number_format($this->sumDispoPourOffres/100, 2, ',', ' ')?> €</td>
                    </tr>
                    
                    <tr>
                        <th colspan="4" style="text-align:center;">
                            <input type="hidden" name="form_send_offres" id="form_send_offres" />
                            <input type="submit" value="Mettre à jour" title="Mettre à jour" name="send_offres" id="send_offres" class="btn" />
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?
	if(count($this->lOffres) > 0)
	{
	?>
    	<h2>Somme des offres de bienvenue déjà donnée : <?=number_format($this->sumOffres/100, 2, ',', ' ')?> €</h2>
    	<table class="tablesorter">
        	<thead>
                <tr>
                	<th>Motif</th>
                    <th>Source3</th>
                    <th>Id client</th>
                    <th>Nom</th>
                    <th>Prenom</th>
                    <th>Email</th>
                    <th>Montant</th>
                    <th>Date</th>
                </tr>
           	</thead>
            <tbody>
            <?
			$i = 1;
			foreach($this->lOffres as $o)
			{
				$this->clients->get($o['id_client'],'id_client');
				?>
				<tr class="<?=($i%2 == 1?'':'odd')?> " > 
                	<td><?=$o['motif']?></td>
                    <td><?=$this->clients->slug_origine?></td>
                    <td><?=$o['id_client']?></td>
                    <td><?=$this->clients->nom?></td>
                    <td><?=$this->clients->prenom?></td>
                    <td><?=$this->clients->email?></td>
                    <td align="center"><?=number_format($o['montant']/100, 2, ',', ' ')?> €</td>
                    <td align="center"><?=date('d/m/y H:i:s',strtotime($o['added']))?></td>
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
	}
    ?>
    
</div>
<?php unset($_SESSION['freeow']); ?>