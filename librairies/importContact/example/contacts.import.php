<form id="import_form" action="" class="center" method="post">
<?php if (!$this->current_class->ExternalAuth) {?>
    <table>
        <tr>
            <td>Email:</td>
            <td><input type="text" name="email" value="" style="width:90%" /></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type="password" name="pswd" value="" style="width:90%" /></td>
        </tr>
    </table>
    <?php if ($this->captcha_required && $this->captcha_url) {
            echo "<img src='{$this->captcha_url}'/>"; ?><br/>
            Enter text: <input type="text" name="captcha" value=""/><br/>
    <?php }	?>
<?php } ?>
    <input type="hidden" name="state" value=""/>
    <input type="hidden" name="contacts_option" value="Gmail"/>
<?php if ($this->error_returned && $this->error_message) {?>
    <span style="color:red;"><?php echo $this->error_message; ?></span><br/>
<?php } ?>
    <button type="submit" id="btnContactsForm" value="import"><?php echo $this->current_class->ExternalAuth? "Authorize Externally" : "Import Contacts"; ?></button>
</form>





<?php

if(!is_null($this->contacts[0])){ ?>
		<form action="" id="invite_form" class="center" method="post">
		<table cellpadding="4px" cellspacing="0" width="100%;">
			<tr style="background-color:#D9D9D9; overflow:hidden;">
				<th><input type="checkbox" id="ToggleSelectedAll" checked="checked" title="Toggle Selected"/></th>
				<th id="NameColumn">Name</th>
				<th id="EmailColumn">Email</th>
			</tr><?php foreach($this->contacts as $contact) {?>
			<tr style="overflow:hidden">
				<td><input type="checkbox" name="contacts[<?php echo $contact->email; ?>]" value="<?php echo $contact->name; ?>" checked="checked" /></td>
				<td><span class="Names"><?php echo $contact->name; ?></span></td>
				<td><?php echo $contact->email; ?></td>
			</tr><?php } ?>
		</table>
		<button type="submit" id="btnContactsForm" value="invite">Do Something (Displays Contacts, No email is sent)</button>
	</form>

	
<?php }?>