<script type="text/javascript">
	$(document).ready(function(){
		$(".tablesorter").tablesorter();	
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
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Administrateurs</li>
    </ul>
	<h1>Requete dossiers</h1>
    
    <div style="margin-bottom:20px; float:right;"><a href="<?=$this->lurl?>/stats/requete_infosben_csv" class="btn_link">Recuperation du CSV</a></div>
    <?
	if(count($this->lProjects) > 0)
	{
	?>
    	<table class="tablesorter">
        	<thead>
                <tr>
                	<th>Cdos</th>
                    <th>Cbéné</th>
                    <th>CEtabl</th>
                    <th>CGuichet</th>   
                    <th>RéfCompte</th>                     
                    <th>NatCompte</th>
                    <th>TypCompte</th>   
                    <th>CDRC</th>
                </tr>
           	</thead>           	
            <tbody>
            <?
            $i = 1;
            foreach($this->lProjects as $p)
            {	
				$this->companies->get($p['id_company'],'id_company');
				$this->emprunteur->get($this->companies->id_client_owner,'id_client');
			
				$this->lLoans = $this->loans->getPreteurs($p['id_project']);
				
				foreach($this->lLoans as $l)
            	{	
					$this->lenders_accounts->get($l['id_lender'],'id_lender_account');
					$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
					
					// Motif 
					$pre = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
					$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
					//$id_client = str_pad($e['id_client'],6,0,STR_PAD_LEFT);
					$id_client = $this->clients->id_client;
					$motif = mb_strtoupper($id_client.$pre.$nom,'UTF-8');
					//$motif = mb_strtoupper($id_client.$nom,'UTF-8');
					$motif = substr($motif,0,10);
					
					?>
					<tr<?=($i%2 == 1?'':' class="odd"')?>>
						<td><?=$this->emprunteur->id_client?></td>
						<td><?=$motif?></td>
						<td>14378</td>
						<td></td>
						<td><?=$this->clients->id_client?></td>
						<td>4</td>
						<td>6</td>
						<td>P</td>
					</tr>   
					<?	
					$i++;
				}
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
    	<p>Il n'y a aucun dossier pour le moment.</p>
    <?
	}
	?>
</div>
<?php unset($_SESSION['freeow']); ?>