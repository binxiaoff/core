<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main">
    <div class="shell">
        <div class="section-c">
            <h2><?=$this->lng['emprunteur-societe']['titre']?></h2>
            <p><?=$this->lng['emprunteur-societe']['content']?></p>
            <p style="text-align:center; color:green;display:none;" class="reponse"><?=$this->lng['emprunteur-societe']['modifications-enregistrees']?></p>
            <form action="" method="post" id="form_inscription_preteur_etape1">
               
                <!-- societe-->
                <div class="part_societe1">
                    <div class="row">
                        
                        <input type="text" name="raison_sociale_inscription" id="raison_sociale_inscription" value="<?=($this->companies->name!=''?$this->companies->name:$this->lng['etape1']['raison-sociale'])?>" title="<?=$this->lng['etape1']['raison-sociale']?>" class="field field-large required" data-validators="Presence">
                        
                         <input type="text" name="forme_juridique_inscription" id="forme_juridique_inscription" value="<?=($this->companies->forme!=''?$this->companies->forme:$this->lng['etape1']['forme-juridique'])?>" title="<?=$this->lng['etape1']['forme-juridique']?>" class="field field-large required" data-validators="Presence">
                        
                        <?php /*?><select name="forme_juridique_inscription" id="forme_juridique_inscription" class="custom-select field-large required">
                            <option value=""><?=$this->lng['etape1']['forme-juridique']?></option>
                            <option <?=($this->companies->forme == 'EIRL'?'selected':'')?> value="EIRL">EIRL</option>
                            <option <?=($this->companies->forme == 'EURL'?'selected':'')?> value="EURL">EURL</option>
                            <option <?=($this->companies->forme == 'SASU'?'selected':'')?> value="SASU">SASU</option>
                            <option <?=($this->companies->forme == 'SCI'?'selected':'')?> value="SCI">SCI</option>
                            <option <?=($this->companies->forme == 'SARL'?'selected':'')?> value="SARL">SARL</option>
                            <option <?=($this->companies->forme == 'SNC'?'selected':'')?> value="SNC">SNC</option>
                            <option <?=($this->companies->forme == 'SA'?'selected':'')?> value="SA">SA</option>
                            <option <?=($this->companies->forme == 'SAS'?'selected':'')?> value="SAS">SAS</option>
                            <option <?=($this->companies->forme == 'GIE'?'selected':'')?> value="GIE">GIE</option>
                        </select><?php */?>
                    </div><!-- /.row -->
                    <div class="row rel">
                        <input type="text" name="capital_social_inscription" id="capital_social_inscription" title="<?=$this->lng['etape1']['capital-sociale']?>" value="<?=($this->companies->capital!=0?number_format($this->companies->capital, 2, '.', ' '):$this->lng['etape1']['capital-sociale'])?>" class="field field-large euro-field required" onkeyup="lisibilite_nombre(this.value,this.id);" data-validators="Presence">
                
                        <input type="text" name="siren_inscription" id="siren_inscription" title="<?=$this->lng['etape1']['siren']?>" value="<?=($this->companies->siren!=''?$this->companies->siren:$this->lng['etape1']['siren'])?>" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 8, maximum: 14}" class="field field-large required">
                    </div><!-- /.row -->
                    <div class="row">
                        <input type="text" name="phone_inscription" id="phone_inscription" value="<?=($this->companies->phone!=''?$this->companies->phone:$this->lng['etape1']['telephone'])?>" title="<?=$this->lng['etape1']['telephone']?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
                    </div><!-- /.row -->
                </div>
                <!-- fin societe-->
                
                <div class="les_deux">
                    <p>
                        <?=$this->lng['etape1']['adresse-fiscale']?>  <i class="icon-help tooltip-anchor" data-placement="right" title="<?=$this->lng['etape1']['info-adresse-fiscale']?>"></i>
                    </p>
                    
                        
                    <div class="row">
                        <input type="text" id="adresse_inscription" name="adresse_inscription" title="<?=$this->lng['etape1']['adresse']?>" value="<?=($this->companies->adresse1!= ''?$this->companies->adresse1:$this->lng['etape1']['adresse'])?>" class="field field-mega required" data-validators="Presence">
                    </div><!-- /.row -->
            
                    <div class="row">
                        <input type="text" id="ville_inscription" name="ville_inscription" title="<?=$this->lng['etape1']['ville']?>" value="<?=($this->companies->city!=''?$this->companies->city:$this->lng['etape1']['ville'])?>" class="field field-large required" data-validators="Presence"  data-autocomplete="cities" >
            
                        <input type="text" name="postal" id="postal" data-autocomplete="postCodes" value="<?=($this->companies->zip!=0?$this->companies->zip:$this->lng['etape1']['code-postal'])?>" title="<?=$this->lng['etape1']['code-postal']?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {is: 5}">
                    </div><!-- /.row -->
                    
                    <div class="row">
                        <div class="cb-holder">
                            <label for="mon-addresse"><?=$this->lng['etape1']['meme-adresse']?></label>
                            <input type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address" <?=($this->companies->status_adresse_correspondance == 0?'':'checked="checked"')?>>
                        </div><!-- /.cb-holder -->
                    </div><!-- /.row -->
                        
                
                    <div class="add-address">
                        <div class="row">
                            <input type="text" id="address2" name="adress2" title="<?=$this->lng['etape1']['adresse']?>" value="<?=($this->clients_adresses->adresse1!=''?$this->clients_adresses->adresse1:$this->lng['etape1']['adresse'])?>" class="field field-mega required" data-validators="Presence">
                        </div><!-- /.row -->
                
                        <div class="row">
                            <input type="text" id="ville2" name="ville2" title="<?=$this->lng['etape1']['ville']?>" value="<?=($this->clients_adresses->ville!=''?$this->clients_adresses->ville:$this->lng['etape1']['ville'])?>" class="field field-large required" data-validators="Presence"  data-autocomplete="cities" >
                
                            <input type="text" id="postal2" name="postal2" data-autocomplete="postCodes" value="<?=($this->clients_adresses->cp!=0?$this->clients_adresses->cp:$this->lng['etape1']['code-postal'])?>" title="<?=$this->lng['etape1']['code-postal']?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {is: 5}">
                        </div><!-- /.row -->
                    </div><!-- /.add-address -->
                
                    
                </div>
            
                <!-- societe-->
                <div class="part_societe2">
                    <div class="row">
                        <div class="form-choose list-view">
                            <span class="title"><?=$this->lng['etape1']['vous-etes']?> :</span>
                            <div class="radio-holder">
                                <label for="enterprise-1"><?=$this->lng['etape1']['je-suis-le-dirigeant-de-lentreprise']?></label>
                                <input <?=($this->companies->status_client == 1?'checked="checked"':'')?> value="1" type="radio" class="custom-input" name="enterprise" id="enterprise-1" checked="checked" data-condition="show:.add-new-profile">
                            </div><!-- /.radio-holder -->
                            <div class="radio-holder">
                                <label for="enterprise-2"><?=$this->lng['etape1']['je-ne-suis-pas-le-dirigeant-de-lentreprise']?></label>
                                <input <?=($this->companies->status_client == 2?'checked="checked"':'')?> value="2" type="radio" class="custom-input" name="enterprise" id="enterprise-2" data-condition="show:.add-new-profile, .identification">
                            </div><!-- /.radio-holder -->
                            <div class="radio-holder">
                                <label for="enterprise-3"><?=$this->lng['etape1']['je-suis-un-conseil-externe-de-lenterprise']?></label>
                                <input <?=($this->companies->status_client == 3?'checked="checked"':'')?> value="3" type="radio" class="custom-input" name="enterprise" id="enterprise-3"data-condition="show:.add-new-profile, .identification, .external-consultant">
                            </div><!-- /.radio-holder -->
                        </div><!-- /.form-choose -->
                    </div><!-- /.row -->
                
                
                    <div class="external-consultant">
                        <div class="row">
                
                            <select name="external-consultant" style="width:458px;" id="external-consultant" class="field field-large custom-select required">
                                <option value="">external consultant*</option>
                                <?
								foreach($this->conseil_externe as $k => $conseil_externe){
									?><option <?=($this->companies->status_conseil_externe_entreprise == $k+1?'selected':'')?> value="<?=$k+1?>" ><?=$conseil_externe?></option><?
								}
								?>
                            </select>
                            <input type="text" name="autre_inscription" title="<?=$this->lng['etape1']['autre']?>" value="<?=($this->companies->preciser_conseil_externe_entreprise!=''?$this->companies->preciser_conseil_externe_entreprise:$this->lng['etape1']['autre'])?>" id="autre_inscription" class="field field-large">
                        </div><!-- /.row -->
                    </div><!-- /.external-consultant -->
                
                    <div class="add-new-profile">
                        
                        
                
                        
            
                    </div><!-- /.add-new-profile -->
                
                    <div class="identification">
                
                        <p><?=$this->lng['etape1']['identification-du-dirigeant']?></p>
                
                        <div class="row">
                            <div class="form-choose">
                                <span class="title"><?=$this->lng['etape1']['civilite']?></span>
                                <div class="radio-holder">
                                    <label for="female2"><?=$this->lng['etape1']['madame']?></label>
                                    <input <?=($this->companies->civilite_dirigeant=='Mme'?'checked="checked"':'')?> type="radio" class="custom-input" name="genre2" id="female2" value="Mme">
                                </div><!-- /.radio-holder -->
                                <div class="radio-holder">
                                    <label for="male2"><?=$this->lng['etape1']['monsieur']?></label>
                                    <input type="radio" class="custom-input" name="genre2" id="male2" <?=($this->companies->civilite_dirigeant=='M.'?'checked="checked"':'')?> value="M.">
                                </div><!-- /.radio-holder -->
                            </div><!-- /.form-choose -->
                        </div><!-- /.row -->
                
                        <div class="row">
                            <input type="text" name="nom2_inscription" title="<?=$this->lng['etape1']['nom']?>" value="<?=($this->companies->nom_dirigeant!=''?$this->companies->nom_dirigeant:$this->lng['etape1']['nom'])?>" id="nom2_inscription" class="field field-large required" data-validators="Presence" onKeyup="noNumber(this.value,this.id)">
                            <input type="text" name="prenom2_inscription" title="<?=$this->lng['etape1']['prenom']?>" value="<?=($this->companies->prenom_dirigeant!=''?$this->companies->prenom_dirigeant:$this->lng['etape1']['prenom'])?>" id="prenom2_inscription" class="field field-large required" data-validators="Presence" onKeyup="noNumber(this.value,this.id)">
                        </div><!-- /.row -->
                        <div class="row">
                            <input type="text" name="fonction2_inscription" title="<?=$this->lng['etape1']['fonction']?>" value="<?=($this->companies->fonction_dirigeant!=''?$this->companies->fonction_dirigeant:$this->lng['etape1']['fonction'])?>" id="fonction2_inscription" class="field field-large required" data-validators="Presence">
                            <input type="text" name="email2_inscription" title="<?=$this->lng['etape1']['email']?>" value="<?=($this->companies->email_dirigeant?$this->companies->email_dirigeant:$this->lng['etape1']['email'])?>" id="email2_inscription" class="field field-large required" data-validators="Presence&amp;Email">
                        </div><!-- /.row -->
                        <div class="row">
                            <input type="text" name="phone_new2_inscription" id="phone_new2_inscription" value="<?=($this->companies->phone_dirigeant!=''?$this->companies->phone_dirigeant:$this->lng['etape1']['telephone'])?>" title="<?=$this->lng['etape1']['telephone']?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
                        </div><!-- /.row -->
                
                        <p><?=$this->lng['etape1']['contenu-dirigeant']?></p>
                
                    </div><!-- /.identification -->
                </div>
                 <!-- fin societe-->
                
                <span class="form-caption"><?=$this->lng['etape1']['champs-obligatoires']?></span>
                <div class="form-foot row row-cols centered">
                    <input type="hidden" name="send_form_etape1" id="send_form_etape1" value="">
                    <button class="btn btn-mega alone-btn" type="submit"><?=$this->lng['etape1']['valider-les-modifications']?><i class="icon-arrow-next"></i></button>
                </div><!-- /.form-foot foot-cols -->
            
            </form>
        </div>
    </div>
</div>
		
<!--#include virtual="ssi-footer.shtml"  -->
<?
if($this->form_ok == true)
{
	?><script>
	$(".reponse").slideDown('slow');
	setTimeout(function() {
		$(".reponse").slideUp('slow');
	}, 5000);
	</script><?
}
?>