<div class="popupTrad">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h2>Modification de la traduction</h2>
    <form method="post" name="mod_traduction" id="mod_traduction" enctype="multipart/form-data" target="_parent">
    	<input type="hidden" name="section" id="section" value="<?=$this->ln->section?>" />
        <input type="hidden" name="nom" id="nom" value="<?=$this->ln->nom?>" />
    	<table>
			<?
            foreach($this->lLangues as $key => $lng)
            {
            ?>
                <tr>
                    <td class="imgDrap">
                        <img src="<?=$this->surl?>/images/admin/langues/<?=$key?>.png" alt="<?=$lng?>" />
                    </td>
                    <td class="textTrad">
                        <textarea class="textarea_lng" name="texte-<?=$key?>" id="texte-<?=$key?>"><?=($key==$this->ln->id_langue?$this->ln->texte:'')?></textarea>
                    </td>
                <tr>
            <?
            }
            ?>
            <tr>
                <td colspan="2" align="right">
                    <input type="hidden" name="form_mod_traduction" id="form_mod_traduction" />
                    <input type="submit" value="Modifier" name="send_traduction" id="send_traduction" class="btn" />
                </td>
            </tr>
        </table>
	</form>
</div>