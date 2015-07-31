<script type="text/javascript">
	$(document).ready(function(){
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		<?
		foreach($this->lLangues as $key => $lng)
		{
		?>
			$("#datepik_<?=$key?>").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		<?
		}
		?>		
	});
</script>
<script type="text/javascript" src="<?=$this->url?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/tree" title="Edition">Edition</a> -</li>
        <li><a href="<?=$this->lurl?>/blocs" title="Blocs">Blocs</a> -</li>
        <li>Modification du bloc</li>
    </ul>
    <?php
	if(count($this->lLangues) > 1)
	{
	?>
        <div id="onglets">
            <?
            foreach($this->lLangues as $key => $lng)
            {
            ?>
                <a onclick="changeOngletLangue('<?=$key?>');" id="lien_<?=$key?>" title="<?=$lng?>" class="<?=($key==$this->language?'active':'')?>"><?=$lng?></a>
            <?
            }
            ?>    	
        </div>
   	<?php
	}
	?>
    <form method="post" name="edit_bloc" id="edit_bloc" enctype="multipart/form-data">
        <input type="hidden" name="id_bloc" id="id_bloc" value="<?=$this->blocs->id_bloc?>" />
        <input type="hidden" name="lng_encours" id="lng_encours" value="<?=$this->language?>" />
    	<?
		foreach($this->lLangues as $key => $lng)
		{
			// Recuperation de la liste des elements du bloc
			$this->lElements = $this->elements->select('status = 1 AND id_bloc = "'.$this->params[0].'" AND id_bloc != 0','ordre ASC');
		?>
        	<div id="langue_<?=$key?>"<?=($key!=$this->language?' style="display:none;"':'')?>>
                <fieldset>
                	<!-- DEBUT DES ELEMENTS DU BLOC -->
                    <?
                    if(count($this->lElements) > 0)
                    {
                    ?>
                    	<h1>Modification du bloc <?=$this->blocs->name?></h1>
                        <table class="large">
							<?
                            foreach($this->lElements as $element)
                            {
                                $this->tree->displayFormElement($this->blocs->id_bloc,$element,'bloc',$key);	
                            }
                            ?>
                        </table>
                        <table class="large">
                        	<tr>
                                <td colspan="2">
                                    <input type="hidden" name="form_edit_bloc" id="form_edit_bloc" />
                                	<input type="submit" value="Valider" name="send_bloc" id="send_bloc" class="btn" />
                                </td>
                            </tr>
                        </table>
                    <?
                    }
                    ?>
                    <!-- FIN DES ELEMENTS DU BLOC -->            
                </fieldset>
  			</div>
        <?	
		}
		?>
    </form>
</div>