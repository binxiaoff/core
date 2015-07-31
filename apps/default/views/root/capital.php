<?=$this->haut?>

<style type="text/css" >

#LeftColumn{width:100%;}
#pageContent_sideBar{display:none;}


h1 {
	color: #00589d;
	font-family: arial, serif;
	font-size: 26px;
	font-weight: bold;
	letter-spacing: 0;
	line-height: 40px;
	margin-top:10px;
	text-transform: uppercase;
}
h2 {
	color: #00589d;
	font-family: arial, serif;
	font-size: 16px;
	font-weight: bold;
	letter-spacing: 0;
	line-height: 30px;
	margin-top:10px;
	text-transform: uppercase;
	width:700px;
	float:left;
	
}
.bloc_recap {
	height: 150px;
	border-top: 5px solid #dedede;
	border-bottom: 5px solid #dedede;
	padding-top: 30px;
    padding-bottom: 30px;
	margin-bottom: 25px;
}

.bloc_recap ul li {
    float: left;
    padding: 0 34px;
    width: 260px;
	background-image:url('<?=$this->surl?>/images/default/icon-arrow-right-big.png');
	background-repeat:no-repeat;
	background-position:right;
}
.bloc_recap ul li.last {
	background-image:none;
}

.bloc_recap ul li img {
    display: block;
    height: 75px;
    margin: 0 auto 36px;
    width: auto;
}
.bloc_recap ul li p strong {
    color: #b40167;
}
.bloc_recap ul li p {
    color: #a3a4a6;
    font-size: 15px;
    line-height: 17px;
	text-transform: uppercase;
	font-weight: bold;
}

.partenariat{
	float:right;
	color:#727272;
	text-transform: uppercase;
	font-family: arial, serif;
	font-weight: bold;
	letter-spacing: 0;
	line-height: 30px;
	font-size:14px;
	margin-bottom:10px;
	margin-right: 25px;
}
.partenariat img{width:auto;height:33px;float:right;}

</style>

<div style="margin-left:7.5px;">
    <h1>Financement Participatif</h1>
    <h2>Prêtez aux entreprises françaises & Recevez des intérêts chaque mois</h2>
</div>

<div class="partenariat">
En partenariat avec<br>
<a href="<?=$this->surl?>"><img alt="Unilend" src="<?=$this->surl?>/styles/default/images/logo.png"></a>
</div>

<div style="clear:both;"></div>

<div class="bloc_recap">
  <ul class="cf">
    <li> <img alt="" src="https://www.unilend.fr/var/images/picto_landing_page_1404306233-img-house.png">
      <p> <strong>1.</strong> <span style="color:#727272;"> Choisissez les projets d'entreprises françaises. </span> </p>
    </li>
    <li> <img alt="" src="https://www.unilend.fr/var/images/picto_landing_page_1404900514-img-pig.png">
      <p> <strong>2.</strong> <span style="color:#727272;"> Proposez le montant et le taux d'intérêt. </span> </p>
    </li>
    <li class="last"> <img alt="" src="https://www.unilend.fr/var/images/picto_landing_page_1404306235-img-calendar.png">
      <p> <strong>3.</strong> <span style="color:#727272;"> Recevez chaque mois des intérêts. </span> </p>
    </li>
  </ul>
</div>
<iframe scrolling="auto" src="<?=$this->lurl?>/projets-a-financer" width="100%" height="1200px"></iframe>
<?=$this->bas?>
