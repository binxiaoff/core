<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>declaration de creances</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="<?=$this->surl?>/styles/default/pdf/images/favicon.ico" />
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/style.css" type="text/css" media="all" />
</head>
<body style="text-align:center;" >

<style type="text/css">
.img{
	background-image:url(<?=$this->surl?>/images/default/declaration_de_creances.jpg);
	background-repeat: no-repeat;
	background-size: 960px auto;
	width:960px;
	height: 1285px;
	margin:auto;
	text-align:left;
	font-size: 16px;
}

.creancier{
float: left;
    height: 174px;
    left: 40px;
    position: relative;
    top: 193px;
    width: 408px;
    padding: 5px 5px 5px 11px;
}

.mandataire_du_creancier{
	float: right;
    height: 174px;
    right: 47px;
    position: relative;
    top: 193px;
    width: 408px;
	padding: 5px 5px 5px 11px;
}

.debiteur{
	float: left;
    height: 174px;
    left: 40px;
    position: relative;
    top: 275px;
    width: 408px;
	padding: 5px 5px 5px 11px;
}

.procedure{
	float: right;
    height: 174px;
    right: 47px;
    position: relative;
    top: 305px;
    width: 408px;
	padding: 5px 5px 5px 11px;
}

.creance_declaree{
	height: 188px;
    left: 40px;
    position: relative;
    top: 408px;
    width: 873px;
}

.case1{
	float:left;
	height: 51px;
    left: 143px;
    padding: 5px;
    position: relative;
    width: 231px;
	text-align:right;
}
.case2{
	float:left;
	height: 51px;
    left: 143px;
    padding: 5px;
    position: relative;
    width: 232px;
}
.case3{
    float: right;
    height: 179px;
    padding: 5px;
    position: relative;
    right: 0;
    width: 235px;
}
.case4{
	float:left;
	height: 51px;
    left: 143px;
    padding: 5px;
    position: relative;
    width: 231px;
	top: 2px;
	text-align:right;
}
.case5{
	float:left;
	height: 51px;
    left: 143px;
    padding: 5px;
    position: relative;
    width: 232px;
	top: 2px;
}
.case6{
	float:left;
	height: 51px;
    left: 143px;
    padding: 5px;
    position: relative;
    width: 231px;
	top: 5px;
	text-align:right;
}
.case7{
	float:left;
	height: 51px;
    left: 143px;
    padding: 5px;
    position: relative;
    width: 232px;
	top: 5px;
}

.fait_a{
	float:left;
	height: 33px;
    left: 90px;
    position: relative;
    top: 430px;
    width: 185px;
}

.fait_le{
	float: right;
    height: 33px;
    position: relative;
    right: 492px;
    top: 430px;
    width: 163px;
}

.signataire{
	height: 39px;
    left: 245px;
    position: relative;
    top: 478px;
    width: 227px;
}

.montant_total{
	height: 20px;
    left: 90px;
    position: relative;
    text-align: right;
    top: 528px;
    width: 227px;
}

</style>

<div class="img">
	
	<div class="creancier">
    	<?
		// particulier
		if(in_array($this->clients->type,array(1,4))){
			?>
            
			<?=$this->clients->prenom?> <?=$this->clients->nom?>
			<br />
			<?=$this->clients_adresses->adresse_fiscal?>
			<br />
			<?=$this->clients_adresses->cp_fiscal?> <?=$this->clients_adresses->ville_fiscal?>
			<br />
			<?=$this->pays_fiscal?>
			
			<?
		}
		// entreprise
		else{
			?>
            
			<?=$this->companies->name?>
			<br />
			<?=$this->companies->adresse1?>
			<br />
			<?=$this->companies->zip?> <?=$this->companies->city?>
			<br />
			<?=$this->pays_fiscal?>
			
			<?
		}
		?>
        
        <br /><br />
        n° de bon caisse : <?=$this->loans->id_loan?>
    </div>
    
    <div class="mandataire_du_creancier">
	<?=$this->mandataires_var?>
    </div>
    
    <div class="debiteur">
        <?=$this->companiesEmpr->forme?> <?=$this->companiesEmpr->name?>
        <br />
        <?=$this->companiesEmpr->adresse1?>
        <br />
        <?=$this->companiesEmpr->zip?> <?=$this->companiesEmpr->city?>
        <br />
        <?=$this->companiesEmpr->rcs?>
    </div>
    
    <div class="procedure">
        <?=$this->nature_var?>
        <br />

        <div style="margin-top:55px;"><?=$this->arrayDeclarationCreance[$this->projects->id_project]?></div>
    </div>
	
    <div style="clear:both;"></div>
    
    <div class="creance_declaree">
		<div class="case1">
        	<?=number_format($this->echu, 2, ',', ' ')?>
        </div>
        <div class="case2">
        	
        </div>
        <div class="case3">
        	Bon de caisse à ordre, émis le  <?=date('d/m/Y',strtotime($this->loans->added))?>, échéance au <?=$this->lastEcheance?>, d’un montant de <?=number_format(($this->loans->amount/100), 2, ',', ' ')?>€ assorti d’un taux d’intérêt annuel de <?=number_format($this->loans->rate, 1, ',', ' ')?>%, amortissable mensuellement.
        </div>
        
        <div class="case4">
        	<?=number_format($this->echoir, 2, ',', ' ')?>
        </div>
        <div class="case5">
        
        </div>
        <div class="case6">
        	<?=number_format($this->total, 2, ',', ' ')?>
        </div>
        <div class="case7"></div>
        
    </div>
 	
    <div style="clear:both;"></div>
    
    <div class="fait_a">
    	<?
    	// particulier
		if(in_array($this->clients->type,array(1,4))){	
			echo $this->clients_adresses->ville_fiscal;
		}
		// entreprise
		else{
			echo $this->companies->city;
		}
		?>
    </div>
    
    <div class="fait_le">
    	<?=date('d/m/Y')?>
    </div>
    
    
    <div style="clear:both;"></div>
    
    <div class="signataire">
    	<?
    	// particulier
		if(in_array($this->clients->type,array(1,4))){	
			echo $this->clients->prenom.' '.$this->clients->nom;
		}
		// entreprise
		else{
			// pas le dirigeant
			if($this->companies->status_client != 1){
				echo $this->companies->prenom_dirigeant.' '.$this->companies->nom_dirigeant.'<br> Fonction : '.$this->companies->fonction_dirigeant;
			}
			else{ 
				echo $this->clients->prenom.' '.$this->clients->nom.'<br> Fonction : '.$this->clients->fonction;
			}
		}
		?>
    </div>
    
    <div style="clear:both;"></div>
    
    <div class="montant_total">
    	<?=number_format($this->total, 2, ',', ' ')?>
    </div>
    
</div>

</body>
</html>