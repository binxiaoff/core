<div class="tab_title" id="title_etape4_2">Etape 4.2 - Bilans</div>
<div class="tab_content" id="etape4_2">
    <h2>Actif</h2>
    <table class="tablesorter annual-accounts" style="text-align:center;">
        <thead>
            <tr>
                <td></td>
                <?php foreach ($this->aAnnualAccountsDates as $aAnnualAccountsDate): ?>
                    <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Capital souscrit non appelé</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AA" value="<?= $aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Frais d'établissement</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AB" value="<?= $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Frais de développement</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AD" value="<?= $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Concessions, brevets et droits similaires</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AF" value="<?= $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Fonds commercial</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AH" value="<?= $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Autres immos Incorpo</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AJ" value="<?= $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Avances et acomptes sur immos Incorpo</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AL" value="<?= $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr class="sub-total">
                <td>Immobilisations incorporelles</td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><?= $aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']] ?>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td></td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><input type="text" name="AL" value="<?= $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']] ?>"/>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
            <tr class="sub-total">
                <td></td>
                <?php foreach ($this->aBalanceSheets as $aBalanceSheet): ?>
                    <td><?= $aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']] ?>&nbsp;€</td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
<?php
/*
Terrains	AN
Constructions	AP
ITMOI	AR
Autres immo corpo	AT
Immos en cours	AV
Avances et acomptes sur immos corpo	AX
Immobilisations corporelles	AN+AP+AR+AT+AV+AX
Participations évaluées selon la méthode…	CS
Autres participations	CU
Créances rattachées à des participations	BB
Autres titres immobilisés	BD
Prêts	BF
Autres immobilisations financières	BH
Immobilisations financières	CS+CU+BB+BD+BF+BH
Total actif immobilisé	BJ
Matières premières	BL
En-cours de bien	BN
En-cours de services	BP
Produits Intermédiaires et finis	BR
Marchandises	BT
Stocks	BL+BN+BP+BR+BT
Avances et acomptes versés sur commande	BV
Clients et comptes rattachés	BX
Autres créances + K souscrit non appelé	BZ
Capital souscrit appelé non versé	CB
Créances clients et autres	BV+BX+BZ+CB
Disponibilités	CF
VMP	CD
Trésorerie	CF+CD
Charges constatées d'avance	CH
Total actif circulant	CJ
Frais d'émission d'emprunt à étaler	CW
Primes de remboursement des obligations	CM
Ecarts de conversion actif	CN
Comptes de régularisation	CH+CW+CM+CN
total Actif	Calcul

Passif
Capital social	DA
Total capitaux propres	DL
Autres fonds propres	DO
Total fonds propres	DL+DO
Amortissements sur Immobilisations	BK
Dépréciation de l'actif circulant	CK
Provisions pour risques et charges	DR
Emprunts obligataires convertibles	DS
Autres emprunts obligataires	DT
Emprunts et dettes auprès des établissements de crédit	DU
Emprunts et dettes financières divers	DV
Dettes financières	DS+DT+DU+DV
Avances et accomptes reçus	DW
Dettes fournisseurs et comptes rattachés	DX
Dettes fournisseurs	DW+DX
Dettes fiscales et sociales	DY
Dettes sur immobilisations et comptes rattachés	DZ
Autres dettes	EA
Autres dettes	DY+DZ+EA
Produits constatés d'avance	EB
Écarts de conversion passif	ED
Comptes de régularisation	EB+ED
Total Passif	Calcul

Autres infos
2 : CBC, et soldes créditeurs de banques et CCP	EH
2 : Prêt participatif	EI
4 : Crédit bail Mobilier	HP
4 : Crédit bail Immobilier	HQ
A5 : Investissements	ØJ
A8 : Dettes à rembourser ds l'année (à plus de 1 an à l'origine) 	VH
A8 : Groupes et associés (placés en "Emprunts et dettes diverses")	VI

Compte de résultat
Chiffre d'Affaires nets	FL
Production stockée	FM
Production immobilisée	FN
Subventions d'exploitation	FO
Reprises sur amort. et prov., transferts de charges	FP
Autres produits	FQ
Achats de marchandises	FS
Variation de stock (marchandises)	FT
Achats de matières premières et autres approv.	FU
Variation de stock (matières premiières et approv.)	FV
Autres achats et charges externes	FW
Impots, taxes et versements assimilés	FX
Salaires et traitements	FY
Charges sociales	FZ
Dotations aux amortissements	GA
Dotations aux provisions	GB
Dotations aux provisions (sur actif circulant)	GC
Dotation aux provisions (pour risques et charges)	GD
Autres charges	GE
Résultat d'Exploitation	GG
Résultat financier 	GV
Reprises sur amort. et prov., transferts de charges fi	GM
Dotations financières aux amort. Et prov.	GQ
Total Charges Financières	GU
RCAI 	GW
Produits exceptionnels sur opérations de gestion	HA
Produits exceptionnels sur opérations de capital	HB
Reprises sur provisions et transferts de charges	HC
Charges exceptionnelles sur opérations de gestion	HE
Charges exceptionnelles sur opérations en capital	HF
Dotations exceptionnelles aux amts et provisions	HG
Résultat net 	HN
*/ ?>
</div>
