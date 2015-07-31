<div class="popup" >
	<a href="#" class="popup-close">close</a>
	
	<div class="popup-head">
		<h2><?=$this->lng['preteur-alimentation']['transferer-des-fonds']?></h2>
	</div>

	<div class="popup-cnt">
    	<?
		// completude
		if($this->clients_status->status < 50){
			?><p><?=$this->lng['preteur-alimentation']['message-a']?></p><?
		}
		// modification
		elseif($this->clients_status->status == 50){
			?><p><?=$this->lng['preteur-alimentation']['message-b']?></p><?
		}
		?>
        
		
	</div>
	<!-- /popup-cnt -->
</div>