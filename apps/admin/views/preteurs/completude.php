<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="search_preteur" id="search_preteur" enctype="multipart/form-data" action="<?=$this->lurl?>/preteurs/edit_preteur/<?=$this->params[0]?>" target="_parent">
        <h1>Complétude - Personnalisation du message</h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                   
                    <td>
                    <label for="id">Saisir votre message :</label>
                    <textarea style="width:500px;height:300px;" name="content_email_completude" id="content_email_completude"><?=$text = str_replace(array("<br>","<br />"),"",$_SESSION['content_email_completude'][$this->params[0]])?></textarea>
                    </td>
                </tr>
                <tr>
                    
                	<th>
                    	
                        <input type="button" value="Prévisualiser" title="Prévisualiser" name="previsualisation" id="previsualisation" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
    
    <script type="text/javascript">
	
	$( "#previsualisation" ).click(function() {
		$.post( add_url+"/ajax/session_content_email_completude", { id_client: "<?=$this->params[0]?>", content: $("#content_email_completude").val() }).done(function( data ) {
			//alert( "Data Loaded: " + data );
			if(data != 'nok')
			{
				$( "#completude_preview" ).get(0).click();
			}
		});
	});
	</script>
    
</div>