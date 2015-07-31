							
							<?php if(in_array($this->current_projects_status->status,array(20,30))) { ?>
	                            <input  type="button" id="status_dosier_valider" class="btn" onClick="check_status_dossier(40,<?=$this->projects->id_project?>);" style="background:#009933;border-color:#009933;font-size:10px;" value="Valider le dossier">
                            <?php } ?>
                            <?php if(in_array($this->current_projects_status->status,array(20))) { ?>
	                            <input type="button" id="status_dosier_rejeter" class="btn" onClick="check_status_dossier(30,<?=$this->projects->id_project?>);" style="background:#CC0000;border-color:#CC0000;font-size:10px;" value="Rejeter le dossier">
                            <?php } ?>