<br /><br />
<table class="form">
    <?
    if(count($this->lProduitCrosseling) > 0)
    {
        foreach($this->lProduitCrosseling as $pc)
        {
            $p = $this->produits->getInfosProduit($pc['id_crosseling']);
            ?>
            <tr>
            	<td><img src="<?=$this->photos->display($p['image'],'produits','admin_comp')?>" /></td>
                <td><?=$p['nom']?></td>
                <td>
                    <?
                    if(count($this->lProduitCrosseling) > 1)
                    {
                        if($pc['ordre'] > 0)
                        {
                        ?>
                            <a onclick="moveProduitComp('up','<?=$pc['id_produit']?>','<?=$pc['id_crosseling']?>');" title="Remonter">
                                <img src="<?=$this->surl?>/images/admin/up.png" alt="Remonter" />
                            </a>
                        <?
                        }
                        
                        if($pc['ordre'] < (count($this->lProduitCrosseling)-1))
                        {
                        ?>
                            <a onclick="moveProduitComp('down','<?=$pc['id_produit']?>','<?=$pc['id_crosseling']?>');" title="Descendre">
                                <img src="<?=$this->surl?>/images/admin/down.png" alt="Descendre" />
                            </a>
                        <?
                        }
                    }
                    ?>
                    
                    <a title="Supprimer <?=$p['nom']?>" onclick="if(confirm('Etes vous sur de vouloir supprimer <?=$p['nom']?> ?')){ deleteProduitComp('<?=$pc['id_produit']?>','<?=$pc['id_crosseling']?>'); }">
                        <img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer <?=$p['nom']?>" />
                    </a>
                </td>
            </tr>
        <?								
        }
    }
    else
    {
    ?>
        <tr>
            <td>Il n'y a aucun produit pour le moment !</td>
        </tr>
    <?
    }
    ?>
</table>