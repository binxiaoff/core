<script type="text/javascript">
	$(document).ready(function(){
		
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		
		$("#date_de_retrait").datepicker({
				showOn: 'both', 
				buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', 
				buttonImageOnly: true,
				changeMonth: true,
				changeYear: true,
				minDate: new Date(<?=date('Y')?>, <?=date('m')?>-1, <?=date('d')?>)
		});
	});
</script>

<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    
    <form method="post" name="form_date_retrait" id="form_date_retrait" enctype="multipart/form-data" action="" target="_parent">
        <h1>Date de retrait</h1>            
        <fieldset>
            <table class="form">
                <tr>
                    <td><label for="date_de_retrait">Date de retrait</label></td>
                    <td>
                    	<input type="text" name="date_de_retrait" id="date_de_retrait" class="input_dp" value="<?=$this->date_retrait?>" />
                        &agrave;                                
                        <select name="date_retrait_heure" class="selectMini">
                            <?php
                            for($h = 0; $h < 24; $h++)
                            {
                                ?>
                                <option value="<?=(strlen($h)<2?"0".$h:$h)?>" <?=($this->heure_date_retrait == $h?"selected=selected":"")?>><?=(strlen($h)<2?"0".$h:$h)?></option>
                                <?php
                            }
                            ?>                                        
                        </select>h                            
                        
                        <select name="date_retrait_minute" class="selectMini">
                            <?php
                            for($m = 0; $m < 60; $m++)
                            {
                                ?>
                                <option value="<?=(strlen($m)<2?"0".$m:$m)?>" <?=($this->minute_date_retrait == $m?"selected=selected":"")?>><?=(strlen($m)<2?"0".$m:$m)?></option>
                                <?php
                            }
                            ?>                                        
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="send_form_date_retrait" id="send_form_date_retrait" />
                        <input type="submit" value="Valider" title="Valider" name="modifier" id="modifier" class="btn" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>  
    
</div>


