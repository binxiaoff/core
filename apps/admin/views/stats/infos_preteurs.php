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
	<h1>Requete Infos prêteurs</h1>

    <div style="margin-bottom:20px; float:right;"><a href="<?=$this->lurl?>/stats/infos_preteurs/csv" class="btn_link">Recuperation du CSV</a></div>

    <table class="tablesorter">
        <thead>
            <tr>
                <th>Id client</th>
                <th>Civilite</th>
                <th>Nom</th>
                <th>Nom usage</th>
                <th>Prenom</th>
                <th>Fonction</th>
                <th>Naissance</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Source</th>
                <th>Adresse</th>
                <th>CP</th>
                <th>Ville</th>
                <th>Adresse fiscale</th>
                <th>Ville fiscale</th>
                <th>CP fiscal</th>
                <th>Exonere</th>
                <th>Début exoneration</th>
                <th>Fin exoneration</th>
                <th>Origine des fonds</th>
                <th>Entreprise</th>
                <th>Id company</th>
                <th>forme juridique</th>
                <th>Siren</th>
                <th>Exercices comptables</th>
                <th>Tribunal_com</th>
                <th>Activite</th>
                <th>Lieu exploi</th>
                <th>Capital</th>
                <th>Date creation</th>
                <th>Adresse company</th>
                <th>CP company</th>
                <th>Ville company</th>
                <th>Telephone company</th>
                <th>Status client</th>
                <th>Status conseil externe entreprise</th>
                <th>Civilite dirigeant</th>
                <th>Nom dirigeant</th>
                <th>Prénom dirigeant</th>
                <th>Fonction dirigeant</th>
                <th>Email dirigeant</th>
                <th>Phone dirigeant</th>
                <th>Sector</th>
                <th>Risk</th>
                <th>Code banque</th>
            </tr>
        </thead>
        <tbody>
        <?
        $i = 1;

		$resultat = $this->bdd->query($this->sql);
        while($record = $this->bdd->fetch_array($resultat))
		{
			?>
			<tr<?=($i%2 == 1?'':' class="odd"')?>>
				<?
				for($a=0;$a <=45;$a++){
					?><td><?=$record[$a]?></td><?
				}
				?>
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

</div>
<?php unset($_SESSION['freeow']); ?>