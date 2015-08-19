<?php // ajout pour la gestion du RIB */ ?>
<div id="popup">
	<!--<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>-->
    
    <? if($this->alreadySended > 0){ ?>
    <p>Attention, un prélèvement est déjà lancé pour le <?=date('d/m/Y',strtotime($this->sendedEcheance))?>.</p>
    <p>Ce changement de RIB sera effectif à partir du prélèvement prévu pour le <?=date('d/m/Y',strtotime($this->nextEcheance))?>.</p>
    <p>Valider tout de même ?</p>
    <? } else { ?>
    <p>Confirmer le changement de RIB pour ce projet?</p>
    <p>Prise en compte pour l'échéance du <?=date('d/m/Y',strtotime($this->nextEcheance))?></p>
    <? } ?>
    <center><a onclick="document.getElementById('edit_emprunteur').submit()" class='btn' >Valider</a><a onclick="parent.$.fn.colorbox.close();" class='btn' style="margin-left:15px;"  >Refuser</a></center>
</div>