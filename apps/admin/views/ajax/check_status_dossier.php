<?php if($this->bloc_statut=='ok') { ?>
	<?php if(count($this->lProjects_status)>0) { ?>
		<select name="status" id="status" class="select">
			<?php foreach($this->lProjects_status as $s) { ?>
				<option <?=($this->current_projects_status->status == $s['status']?'selected':'')?> value="<?=$s['status']?>"><?=$s['label']?></option>
			<?php } ?>
		</select>
	<?php } else { ?>
    	<input type="hidden" name="status" id="status" value="<?=$this->current_projects_status->status?>" />
		<?=$this->current_projects_status->label?>
	<?php } ?>
<?php } ?>