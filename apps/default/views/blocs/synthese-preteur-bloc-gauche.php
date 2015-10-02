<div class="graphic-box">
    <header>
        <h2><?=$this->lng['preteur-synthese']['situation-de-votre-compte-unilend']?></h2>
        <p><?=$this->lng['preteur-synthese']['solde-de-mon-compte']?> :<strong> <?=number_format($this->solde,2,',',' ')?> €</strong></p>
    </header>
    <div class="body">
        <style>
                        #leSolde,#leSoldePourcent,#sumBidsEncours,#sumBidsEncoursPourcent,#sumPrets,#sumPretsPourcent,#sumRembMontant,#nbLoan,#argentPrete,#argentRemb,#interets,#titlePrete,#titleArgentRemb,#titleInteretsRecu{display:none;}

        #cboxLoadedContent{margin-bottom:0px;}
        .popup{background-color:#E3E4E5;}


        </style>
        <?
        if($this->solde > 0 || $this->soldePourcent > 0 || $this->sumBidsEncoursPourcent > 0 || $this->sumPretsPourcent > 0){
            // On met ca pour eviter les débordements
            if($this->solde >= 1000) $fondsdispo = str_replace(' ','<br>',$this->lng['preteur-synthese']['de-fond-disponible']);
            else $fondsdispo = $this->lng['preteur-synthese']['de-fond-disponible'];
			?>

			<span id="leSolde"><b><?=number_format($this->solde,2,',',' ')?> € <br /><?=$fondsdispo?></b></span>
			<span id="leSoldePourcent"><?=number_format($this->soldePourcent,1,'.','')?></span>

			<span id="sumBidsEncours"><b><?=number_format($this->sumBidsEncours,2,',',' ')?> € <br /><?=$this->lng['preteur-synthese']['de-fond-bloques']?></b></span>
			<span id="sumBidsEncoursPourcent"><?=number_format($this->sumBidsEncoursPourcent,1,'.','')?></span>

			<?php /*?><span id="sumPrets"><b><?=number_format($this->sumPrets,2,',',' ')?> € <br /><?=$this->lng['preteur-synthese']['pretes-a']?> <?=$this->nbLoan?> <?=$this->lng['preteur-synthese']['entreprise']?><?=($this->nbLoan>1?'s':'')?> <br /><?=$this->lng['preteur-synthese']['dont']?> <?=number_format($this->sumRembMontant,2,',','')?> <?=$this->lng['preteur-synthese']['de-recouvrement']?></b></span><?php */?>

			<span id="sumPrets"><b><?=number_format($this->sumRestanteARemb,2,',',' ')?> € <br /><?=$this->lng['preteur-synthese']['pretes-a']?> <?=$this->nbLoan?> <?=$this->lng['preteur-synthese']['entreprise']?><?=($this->nbLoan>1?'s':'')?><br />et restant à rembourser
			<?

			if($this->sumRestanteARemb>0){
				/*?><br /><?=$this->lng['preteur-synthese']['et']?> <?=number_format($this->sumRestanteARemb,2,',','')?> <?=$this->lng['preteur-synthese']['de-recouvrement']?><?*/
			}
			?>
			</b></span>

			<span id="sumPretsPourcent"><?=number_format($this->sumPretsPourcent,1,'.','')?></span>

			<div id="pie-chart"></div>
			<?
        }
        ?>
    </div>
</div>

<div class="post-schedule">
    <h2><?=$this->lng['preteur-synthese']['encheres-en-cours']?> <span><?=($this->lProjetsBidsEncours!=false?count($this->lProjetsBidsEncours):0)?> <i class="icon-box-arrow"></i></span></h2>
    <div class="body">
        <?
        if($this->lProjetsBidsEncours != false){
            foreach($this->lProjetsBidsEncours as $f){

                $this->companies->get($f['id_company'],'id_company');
                $this->projects_status->getLastStatut($f['id_project']);

                // date fin 21h a chaque fois
                $inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$f['date_retrait'].' '.$this->heureFinFunding.':00');
                if($inter['mois']>0) $dateRest = $inter['mois'].' '.$this->lng['preteur-projets']['mois'];
                else $dateRest = '';

                // dates pour le js
                $mois_jour = $this->dates->formatDate($f['date_retrait'],'F d');
                $annee = $this->dates->formatDate($f['date_retrait'],'Y');

                // la sum des encheres
                $soldeBid = $this->bids->getSoldeBid($f['id_project']);

                // solde payé
                $payer = $soldeBid;

                // Reste a payer
                $resteApayer = ($f['amount']-$soldeBid);

                $pourcentage = ((1-($resteApayer/$f['amount']))*100);

                $decimales = 2;
                $decimalesPourcentage = 2;

                if($soldeBid >= $f['amount']){
                    $payer = $f['amount'];
                    $resteApayer = 0;
                    $pourcentage = 100;
                    $decimales = 0;
                    $decimalesPourcentage = 0;
                }

                // nb encheres
                $CountEnchere = $this->bids->counter('id_project = '.$f['id_project']);

                // moyenne pondéré
                $montantHaut = 0;
                $montantBas = 0;
                // si fundé ou remboursement
                if($this->projects_status->status==60 || $this->projects_status->status==80){
                    foreach($this->loans->select('id_project = '.$f['id_project']) as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }
                // funding ko
                elseif($this->projects_status->status==70){
                    foreach($this->bids->select('id_project = '.$f['id_project']) as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }
                // emprun refusé
                elseif($this->projects_status->status==75){
                    foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 1') as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }
                else{
                    foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 0') as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }

                if($montantHaut > 0 && $montantBas > 0) $avgRate = ($montantHaut/$montantBas);

                ?>
                <div class="post-box clearfix">
                    <h3><?=$f['title']?>, <small><?=$this->companies->city?><?=($this->companies->city!=''?',':'')?> <?=$this->companies->zip?></small></h3>
                    <?
                    if($this->projects_status->status > 50){
                        $dateRest = $this->lng['preteur-synthese']['termine'];
                        $reste = '';
                        ;
                    }
                    else{
                        $reste = $this->lng['preteur-synthese']['reste'].' ';
                        ?>
                        <script>
                            var cible<?=$f['id_project']?> = new Date('<?=$mois_jour?>, <?=$annee?> <?=$this->heureFinFunding?>:00');
                            var letime<?=$f['id_project']?> = parseInt(cible<?=$f['id_project']?>.getTime() / 1000, 10);
                            setTimeout('decompte(letime<?=$f['id_project']?>,"val<?=$f['id_project']?>")', 500);
                        </script>
                        <?
                    }
                    if($f['photo_projet'] != ''){
                        ?><a href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>" class="img-holder"><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $f['photo_projet'] ?>" alt="<?=$f['photo_projet']?>"></a><?
                    }
                    ?>
                    <div class="info">
                        <ul class="list">
                            <li><i class="icon-pig-gray"></i><?=number_format($f['amount'],0,',',' ')?> €</li>
                            <li><i class="icon-clock-gray"></i><?=($reste==''?'':$reste)?> <span id="val<?=$f['id_project']?>"><?=$dateRest?></span></li>
                            <li><i class="icon-target"></i><?=$this->lng['preteur-synthese']['couvert-a']?> <?=number_format($pourcentage,$decimalesPourcentage, ',',' ')?> %</li>

                            <?
                            if($CountEnchere>0)
                            {
                                ?><li><i class="icon-graph-gray"></i><?=number_format($avgRate, 2, ',', ' ')?> %</li><?
                            }
                            else
                            {
                                ?><li><i class="icon-graph-gray"></i><?=($f['target_rate']=='-'?'-':number_format($f['target_rate'], 2, ',',' ').' %')?></li><?
                            }
                            ?>

                        </ul>

                        <a class="btn alone" href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>"><?=$this->lng['preteur-synthese']['voir-le-projet']?></a>
                    </div>
                </div>
                <?
            }
        }
        ?>
    </div>
</div>

<div class="post-schedule">
    <h2><?=$this->lng['preteur-synthese']['projets-a-decouvrir']?> <span><?=($this->lProjetEncours!=false?count($this->lProjetEncours):0)?> <i class="icon-box-arrow"></i></span></h2>
    <div class="body">
        <?
        if($this->lProjetEncours != false){
            foreach($this->lProjetEncours as $f){

                $this->companies->get($f['id_company'],'id_company');
                $this->projects_status->getLastStatut($f['id_project']);

                // date fin 21h a chaque fois
                $inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$f['date_retrait'].' '.$this->heureFinFunding.':00');
                if($inter['mois']>0) $dateRest = $inter['mois'].' '.$this->lng['preteur-projets']['mois'];
                else $dateRest = '';

                // dates pour le js
                $mois_jour = $this->dates->formatDate($f['date_retrait'],'F d');
                $annee = $this->dates->formatDate($f['date_retrait'],'Y');

                // la sum des encheres
                $soldeBid = $this->bids->getSoldeBid($f['id_project']);

                // solde payé
                $payer = $soldeBid;

                // Reste a payer
                $resteApayer = ($f['amount']-$soldeBid);

                $pourcentage = ((1-($resteApayer/$f['amount']))*100);

                $decimales = 2;
                $decimalesPourcentage = 1;

                if($soldeBid >= $f['amount']){
                    $payer = $f['amount'];
                    $resteApayer = 0;
                    $pourcentage = 100;
                    $decimales = 0;
                    $decimalesPourcentage = 0;
                }

                // nb encheres
                $CountEnchere = $this->bids->counter('id_project = '.$f['id_project']);

                // moyenne pondéré
                $montantHaut = 0;
                $montantBas = 0;
                // si fundé ou remboursement
                if($this->projects_status->status==60 || $this->projects_status->status==80){
                    foreach($this->loans->select('id_project = '.$f['id_project']) as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }
                // funding ko
                elseif($this->projects_status->status==70){
                    foreach($this->bids->select('id_project = '.$f['id_project']) as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }
                // emprun refusé
                elseif($this->projects_status->status==75){
                    foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 1') as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }
                else{
                    foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 0') as $b){
                        $montantHaut += ($b['rate']*($b['amount']/100));
                        $montantBas += ($b['amount']/100);
                    }
                }

                if($montantHaut > 0 && $montantBas > 0) $avgRate = ($montantHaut/$montantBas);

                ?>
                <div class="post-box clearfix">
                    <h3><?=$f['title']?>, <small><?=$this->companies->city?><?=($this->companies->city!=''?',':'')?> <?=$this->companies->zip?></small></h3>
                    <?
                    if($this->projects_status->status > 50){
                        $dateRest = $this->lng['preteur-synthese']['termine'];
                        $reste = '';
                        ;
                    }
                    else{
                        $reste = $this->lng['preteur-synthese']['reste'].' ';
                        ?>
                        <script>
                            var cible<?=$f['id_project']?> = new Date('<?=$mois_jour?>, <?=$annee?> <?=$this->heureFinFunding?>:00');
                            var letime<?=$f['id_project']?> = parseInt(cible<?=$f['id_project']?>.getTime() / 1000, 10);
                            setTimeout('decompte(letime<?=$f['id_project']?>,"val<?=$f['id_project']?>")', 500);
                        </script>
                        <?
                    }
                    if($f['photo_projet'] != ''){
                        ?><a href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>" class="img-holder"><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $f['photo_projet'] ?>" alt="<?=$f['photo_projet']?>"></a><?
                    }
                    ?>
                    <div class="info">
                        <ul class="list">
                            <li><i class="icon-pig-gray"></i><?=number_format($f['amount'],0,',',' ')?> €</li>
                            <li><i class="icon-clock-gray"></i><?=($reste==''?'':$reste)?> <span id="val<?=$f['id_project']?>"><?=$dateRest?></span></li>
                            <li><i class="icon-target"></i><?=$this->lng['preteur-synthese']['couvert-a']?> <?=number_format($pourcentage,$decimalesPourcentage, ',',' ')?> %</li>

                            <?
                            if($CountEnchere>0)
                            {
                                ?><li><i class="icon-graph-gray"></i><?=number_format($avgRate, 1, ',', ' ')?> %</li><?
                            }
                            else
                            {
                                ?><li><i class="icon-graph-gray"></i><?=($f['target_rate']=='-'?'-':number_format($f['target_rate'], 2, ',',' ').' %')?></li><?
                            }
                            ?>

                        </ul>

                        <a class="btn alone" href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>"><?=$this->lng['preteur-synthese']['voir-le-projet']?></a>
                    </div>
                </div>
                <?
            }
        }
        ?>
    </div>
</div>
