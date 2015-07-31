<form method="post" name="add_image" id="add_image" enctype="multipart/form-data">
    <table class="form">
        <?
        if(count($this->lImages) < 9)
        {
        ?>
            <tr>
                <th><label for="image_produit">Image :</label></th>
                <td>
                    <input type="file" name="image_produit" id="image_produit" /> 
                    &nbsp;&nbsp;
                    <input type="submit" value="Uploader" name="send_image" id="send_image" class="btn" />
                </td>
            </tr>
        <?
        }
		else
		{
        ?>
        	<tr>    
                <td>Vous avez atteint le maximum de 9 images !</td>
            </tr>
        <?php
		}
		?>
    </table>
</form>
<br /><br />
<table class="form">             
<?
// Remize à zéro des lignes
$premiere = $seconde = $troisieme = '';

// Affichage des images du produit
if(count($this->lImages) > 0)
{
	$i = 1;
	foreach($this->lImages as $img)
	{
		// Creation de la vignette
		if($img['fichier'] != '')
		{
			$vignette = '
			<td class="vign">
				Image '.$i.' 
				<a onclick="if(confirm(\'Etes vous sur de vouloir supprimer cette image ?\')){deleteImageFicheProduit('.$img['id_image'].',\''.$this->params[0].'\');return false;}">
					<img src="'.$this->surl.'/images/admin/delete.png" alt="Supprimer" class="delete" />
				</a>
				<br />
				<a onclick="parent.$.fn.colorbox({href:\''.$this->surl.'/var/images/produits/'.$img['fichier'].'\'});" target="_top">
					<img src="'.$this->photos->display($img['fichier'],'produits','admin_imgs').'" class="vign" />
				</a>
				<br />
				Principale <img onclick="moveImageToFirstOne('.$img['id_image'].',\''.$this->params[0].'\');" src="'.$this->surl.'/images/admin/check_'.($img['ordre'] == 1?'on':'off').'.png" id="principale_'.$img['id_image'].'" class="case" />
			</td>';
		}
		else
		{
			$vignette = '<td class="vign">&nbsp;</td>';
		}
		
		// Positionnement sur les lignes
		if($i <= 3)
		{
			$premiere .= $vignette;
		}
		elseif($i > 3 && $i <= 6)
		{
			$seconde .= $vignette;
		}
		elseif($i > 6 && $i <= 9)
		{
			$troisieme .= $vignette;
		}
		
		$i++;		
	}
	?>
	<tr><?=$premiere?></tr>
	<tr><?=$seconde?></tr>
	<tr><?=$troisieme?></tr>
<?
}
else
{
?>
	<tr>    
		<td>Il n'y a aucune image pour le moment !</td>
	</tr>
<?
}
?>
</table>