<?php // ajout pour la gestion du RIB */ ?>
<div id="popup">
	<!--<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>-->
       
    <p>Attention, cet IBAN est le même que celui de l’entreprise : <br />
        <?=$this->list_comp?></p>    
    
    <center><a onclick="document.getElementById('edit_emprunteur').submit()" class='btn' >Oui</a><a onclick="parent.$.fn.colorbox.close();" class='btn' style="margin-left:15px;"  >Non</a></center>
</div>