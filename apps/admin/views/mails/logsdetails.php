<div id="popup" style="width:800px;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<table class="formMail">
        <tr>
            <th>Date : <?=$this->dates->formatDate($this->mails_filer->added,'d/m/Y H:i')?></th>
        </tr>
        <tr>
            <th>From : <?=$this->mails_filer->from?></th>
        </tr>
        <tr>
            <th>To : <?=$this->mails_filer->to?></th>
        </tr>
        <tr>
            <th>Sujet : <?=$this->mails_filer->subject?></th>
        </tr>
        <tr>
            <td><iframe src="<?=$this->lurl?>/mails/logsdisplay/<?=$this->mails_filer->id_filermails?>" width="760px" height="400px"></iframe></td>
        </tr>
    </table>    
</div>