<form action="" method="post" id="form_inscription_preteur_particulier_etape_1" name="form_inscription_preteur_particulier_etape_1">
    <div class="part_particulier1">
        <div class="row" id="radio_sex">
            <div class="form-choose fixed">
                <span class="title"><?=$this->lng['etape1']['civilite']?></span>
                <div class="radio-holder validationRadio1">
                    <label for="female"><?=$this->lng['etape1']['madame']?></label>
                    <input <?=($this->modif == true && $this->clients->civilite=='Mme'?'checked="checked"':'')?> type="radio" class="custom-input" name="sex" id="female"  value="Mme">
                </div>
                <div class="radio-holder validationRadio2">
                    <label for="male"><?=$this->lng['etape1']['monsieur']?></label>
                    <input <?=($this->modif == true && $this->clients->civilite=='M.'?'checked="checked"':'')?> type="radio" class="custom-input" name="sex" id="male"  value="M.">
                </div>
            </div>
        </div>
        <div class="row">
            <input type="text" name="nom-famille" id="nom-famille" title="<?=$this->lng['etape1']['nom-de-famille']?>" value="<?=($this->clients->nom!=''?$this->clients->nom:$this->lng['etape1']['nom-de-famille'])?>" class="field field-large required <?=($this->clients->nom!=''?"LV_valid_field":"")?>" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}" >
            <input type="text" name="nom-dusage" id="nom-dusage" title="<?=$this->lng['etape1']['nom-dusage']?>" value="<?=($this->clients->nom_usage!=''?$this->clients->nom_usage:$this->lng['etape1']['nom-dusage'])?>" class="field field-large" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
        </div>
        <div class="row">
            <input type="text" name="prenom" id="prenom" title="<?=$this->lng['etape1']['prenom']?>" value="<?=($this->clients->prenom!=''?$this->clients->prenom:$this->lng['etape1']['prenom'])?>" class="field field-large required <?=($this->clients->prenom!=''?"LV_valid_field":"")?>" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
        </div>
        <div class="row">
            <span class="pass-field-holder">
                <input type="text" name="email" id="email" title="<?=$this->lng['etape1']['email']?>" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape1']['email'])?>" class="field field-large required <?=($this->clients->email!=''?"LV_valid_field":"")?>" data-validators="Presence&amp;Email&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" onkeyup="checkConf(this.value,'conf_email')" >
                <em><?=$this->lng['etape1']['info-email']?></em>
            </span>
            <span class="pass-field-holder">
                <?php //Ajout CM 06/08/14 ?>
                <input type="text" name="conf_email" id="conf_email" title="<?=$this->lng['etape1']['confirmation-email']?>" value="<?=(($this->modif || isset($_SESSION['landing_client']['email']) && $_SESSION['landing_client']['email']!='') && $this->clients->email!=''?$this->clients->email:$this->lng['etape1']['confirmation-email'])?>" class="field field-large required <?=($this->clients->email!=''?"LV_valid_field":"")?>" data-validators="Confirmation,{ match: 'email' }&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/ }" >
            </span>
        </div>
        <div class="row">
            <span class="pass-field-holder">
                <input type="password" name="pass" id="pass" title="<?=$this->lng['etape1']['mot-de-passe']?>" value="" class="field field-large required">
                <em><?=$this->lng['etape1']['info-mdp']?></em>
            </span>
            <span class="pass-field-holder">
                <input type="password" name="pass2" id="pass2" title="<?=$this->lng['etape1']['confirmation-de-mot-de-passe']?>" value="" class="field field-large " data-validators="Confirmation,{ match: 'pass' }">
            </span>
        </div>
        <div class="row">
            <input type="text" id="secret-question" name="secret-question" title="<?=$this->lng['etape1']['question-secrete']?>" value="<?=($this->clients->secrete_question!=''?$this->clients->secrete_question:$this->lng['etape1']['question-secrete'])?>" class="field field-mega required" data-validators="Presence">
        </div>
        <div class="row">
            <input type="text" id="secret-response" name="secret-response" title="<?=$this->lng['etape1']['response']?>" value="<?=$this->lng['etape1']['response']?>" class="field field-mega required" data-validators="Presence">
        </div>
    </div>
    <div class="les_deux">
        <p><?= $this->lng['etape1']['adresse-fiscale'] ?> <i class="icon-help tooltip-anchor" data-placement="right" title="<?=$this->lng['etape1']['info-adresse-fiscale']?>"></i></p>
        <div class="row">
            <input type="text" id="adresse_inscription" name="adresse_inscription" title="<?=$this->lng['etape1']['adresse']?>" value="<?=($this->clients_adresses->adresse_fiscal!= ''?$this->clients_adresses->adresse_fiscal:$this->lng['etape1']['adresse'])?>" class="field field-mega required" data-validators="Presence">
        </div>
        <div class="row row-triple-fields">
            <input type="text" id="postal" name="postal" class="field field-small required" data-autocomplete="post_code"
                   placeholder="<?=$this->lng['etape1']['code-postal']?>" title="<?=$this->lng['etape1']['code-postal']?>" value="<?=$this->clients_adresses->cp_fiscal?>"/>
            <input type="text" id="ville_inscription" name="ville_inscription" class="field field-small required" data-autocomplete="city"
                   placeholder="<?=$this->lng['etape1']['ville']?>" title="<?=$this->lng['etape1']['ville']?>" value="<?=$this->clients_adresses->ville_fiscal?>"/>
            <select name="pays1" id="pays1" class="custom-select required field-small">
                <option value=""><?=$this->lng['etape1']['pays']?></option>
                <option value=""><?=$this->lng['etape1']['pays']?></option>
                <?php foreach ($this->lPays as $p) { ?>
                    <option <?=($this->modif == true && $this->clients_adresses->id_pays_fiscal == $p['id_pays']?'selected':($this->clients_adresses->id_pays_fiscal == 0 && $p['id_pays'] == 1?'selected':''))?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option>
                <?php } ?>
            </select>
        </div>
        <div class="row">
            <div class="cb-holder">
                <label for="mon-addresse"><?=$this->lng['etape1']['meme-adresse']?></label>
                <input <?=($this->modif == true && $this->clients_adresses->meme_adresse_fiscal == 0?'':'checked="checked"')?> type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address">
            </div>
        </div>
        <div class="add-address">
            <p><?=$this->lng['etape1']['adresse-de-correspondance']?></p>
            <div class="row">
                <input type="text" id="address2" name="adress2" title="<?=$this->lng['etape1']['adresse']?>" value="<?=($this->clients_adresses->adresse1!=''?$this->clients_adresses->adresse1:$this->lng['etape1']['adresse'])?>" class="field field-mega required" data-validators="Presence">
            </div>
            <div class="row row-triple-fields">
                <input type="text" id="postal2" name="postal2" class="field field-small required" data-autocomplete="post_code"
                       placeholder="<?=$this->lng['etape1']['code-postal']?>" value="<?=$this->clients_adresses->cp?>" title="<?=$this->lng['etape1']['code-postal']?>" />
                <input type="text" id="ville2" name="ville2" class="field field-small required" data-autocomplete="city"
                       placeholder="<?=$this->lng['etape1']['ville']?>" title="<?=$this->lng['etape1']['ville']?>" value="<?=$this->clients_adresses->ville?>" />
                <select name="pays2" id="pays2" class="custom-select required field-small">
                    <option value=""><?=$this->lng['etape1']['pays']?></option>
                    <option value=""><?=$this->lng['etape1']['pays']?></option>
                    <?php foreach ($this->lPays as $p) { ?>
                        <option <?=($this->modif == true && $this->clients_adresses->id_pays == $p['id_pays']?'selected':($this->clients_adresses->id_pays == 0 && $p['id_pays'] == 1?'selected':''))?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="row row-alt">
            <span class="inline-text inline-text-alt"><?=$this->lng['etape1']['telephone']?> :</span>
            <input type="text" name="phone" id="phone" value="<?=($this->clients->telephone!=''?$this->clients->telephone:$this->lng['etape1']['telephone'])?>" title="<?=$this->lng['etape1']['telephone']?>" class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
            <span class="inline-text inline-text-alt"><?=$this->lng['etape1']['nationalite']?> :</span>
            <select name="nationalite" id="nationalite" class="custom-select required field-small">
                <option value=""><?=$this->lng['etape1']['nationalite']?></option>
                <option value=""><?=$this->lng['etape1']['nationalite']?></option>
                <?php foreach ($this->lNatio as $p) { ?>
                    <option <?=($this->modif == true && $this->clients->id_nationalite == $p['id_nationalite']?'selected':($this->clients->id_nationalite == 0 && $p['id_nationalite'] == 1?'selected':''))?> value="<?=$p['id_nationalite']?>"><?=$p['fr_f']?></option>
                <?php } ?>
            </select>
        </div>
        <div class="row etranger">
            <div class="cb-holder">
                <label style="margin-left:524px;" class="check_etranger" for="check_etranger"><?=$this->lng['etape1']['checkbox-etranger']?></label>
                <input type="checkbox" class="custom-input" name="check_etranger" id="check_etranger">
            </div>
            <p class="message_check_etranger" ><?=$this->lng['etape1']['checkbox-etranger-message']?></p>
        </div>
        <div class="row small-select">
            <span class="inline-text inline-text-alt"><?=$this->lng['etape1']['date-de-naissance']?> :</span>
            <select name="jour_naissance" id="jour_naissance" class="custom-select required field-tiny">
                <option value=""><?=$this->lng['etape1']['jour']?></option>
                <option value=""><?=$this->lng['etape1']['jour']?></option>
                <?php for ($i = 1; $i <= 31; $i++) { ?>
                    <option <?=($this->modif == true && $this->jour == $i?'selected':'')?> value="<?=$i?>"><?=$i?></option>
                <?php } ?>
            </select>
            <select name="mois_naissance" id="mois_naissance" class="custom-select required field-tiny">
                <option value=""><?=$this->lng['etape1']['mois']?></option>
                <option value=""><?=$this->lng['etape1']['mois']?></option>
                <?php
                foreach ($this->dates->tableauMois['fr'] as $k => $mois) {
                    if ($k > 0) {
                        echo '<option '.($this->modif == true && $this->mois == $k?"selected":"").' value="'.$k.'">'.$mois.'</option>';
                    }
                }
                ?>
            </select>
            <select name="annee_naissance" id="annee_naissance" class="custom-select required field-tiny">
                <option value=""><?=$this->lng['etape1']['annee']?></option>
                <option value=""><?=$this->lng['etape1']['annee']?></option>
                <?php for ($i = date('Y') - 18; $i >= 1910; $i--) {
                    echo '<option '.($this->modif == true && $this->annee == $i?"selected":"").' value="'.$i.'">'.$i.'</option>';
                }
                ?>
            </select>
            <div style="clear: both;"></div>
            <em class="error_age"><?=$this->lng['etape1']['erreur-age']?></em>
            <span class="check_age" style="display:none">true</span>
        </div>
        <div class="row row-triple-fields row-triple-fields-alt">
            <span class="inline-text inline-text-alt inline-text-alt-small"><?=$this->lng['etape1']['commune-de-naissance']?> :</span>
            <input type="text" name="naissance" id="naissance" class="field field-small required" data-autocomplete="birth_city"
                   placeholder="<?=$this->lng['etape1']['commune-de-naissance']?>" value="<?=$this->clients->ville_naissance?>">
            <input type="hidden" id="insee_birth" name="insee_birth" value="<?=$this->clients->insee_birth!=''?$this->clients->insee_birth:''?>"/>
            <span class="inline-text inline-text-alt inline-text-alt-small"><?=$this->lng['etape1']['pays-de-naissance']?> :</span>
            <select name="pays3" id="pays3" class="custom-select required field-small">
                <option value=""><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <option value=""><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <?php foreach($this->lPays as $p) { ?>
                    <option <?=($this->modif == true && $this->clients->id_pays_naissance == $p['id_pays']?'selected':($this->clients->id_pays_naissance == 0 && $p['id_pays'] == 1?'selected':''))?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="cb-holder">
            <label class="check" for="accept-cgu"><a style="color:#A1A5A7; text-decoration: underline;" class="check" target="_blank" href="<?=$this->lurl.'/cgv_preteurs'?>"><?=$this->lng['etape3']['jaccepte-les-cgu-dunilend']?></a></label>
            <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
            <span class="form-caption"><?=$this->lng['etape1']['champs-obligatoires']?></span>
        </div>
    </div>
    <div class="form-foot row row-cols centered">
           <input type="hidden" name="form_inscription_preteur_particulier_etape_1">
        <button class="btn"  type="submit"><?=$this->lng['etape1']['suivant']?><i class="icon-arrow-next"></i></button>
    </div>
</form>

<?php

//Ajout CM 06/08/14
$_SESSION['landing_client'] = array();

?>
