<?php $annualAccountsIds = array_keys($this->aBalanceSheets); ?>
<?php $iOldestAnnualAccountsId = end($annualAccountsIds); ?>
"Projet"
"SIREN";"<?= $this->oCompany->siren ?>"
"Entreprise";"<?= $this->oCompany->name ?>"
"Montant";"<?= $this->oProject->amount ?>"
"Durée";"<?= $this->oProject->period ?>"
""
"Notation externe"
<?php if ($this->bIsProblematicCompany) : ?>"Cette société a déjà eu des problèmes"<?php endif; ?>
"Notes externes";"";"Déclaration client"
"Grade Euler-Hermes";"<?= empty($this->ratings['grade_euler_hermes']['value']) ? 'N/A' : $this->ratings['grade_euler_hermes']['value'] ?>";"Chiffe d'affaires declaré par client";"<?= $this->iDeclaredRevenue ?>"
"Score Altares";"<?php if (isset($this->ratings['score_altares']['value'])) : ?><?= $this->ratings['score_altares']['value'] ?> / 20<?php else : ?>N/A<?php endif; ?>";"Résultat d'exploitation declaré par client";"<?= $this->iDeclaredOperatingIncome ?>"
"Score sectoriel Altares";"<?php if (isset($this->ratings['score_sectoriel_altares']['value'])) : ?><?= round($this->ratings['score_sectoriel_altares']['value'] / 5) ?> / 20<?php else : ?>N/A<?php endif; ?>";"Fonds propres declarés par client";"<?= $this->iDeclaredCapitalStock ?>"
"Note Infolegale";"<?= empty($this->ratings['note_infolegale']['value']) ? 'N/A' : $this->ratings['note_infolegale']['value'] ?>"
"Présence de RPC < 6 mois";"<?= isset($this->ratings['rpc_6mois']['value']) && '1' === $this->ratings['rpc_6mois']['value'] ? 'Oui' : (isset($this->ratings['rpc_6mois']['value']) ? 'Non' : 'N/A') ?>"
"Présence de RPC > 12 mois";"<?= isset($this->ratings['rpc_12mois']['value']) && '1' === $this->ratings['rpc_12mois']['value'] ? 'Oui' : (isset($this->ratings['rpc_6mois']['value']) ? 'Non' : 'N/A') ?>"
"Cotation FIBEN / Note interne banque";"<?php if (isset($this->ratings['cotation_fiben']['value'])) : ?><?= $this->ratings['cotation_fiben']['value'] ?><?php endif; ?>";"<?php if (isset($this->ratings['note_interne_banque']['value'])) : ?><?= $this->ratings['note_interne_banque']['value'] ?><?php endif; ?>";"<?php if (isset($this->ratings['nom_banque']['value'])) : ?><?= $this->ratings['nom_banque']['value'] ?><?php endif; ?>"
"Cotation dirigeant FIBEN";"<?php if (isset($this->ratings['cotation_dirigeant_fiben']['value'])) : ?><?= $this->ratings['cotation_dirigeant_fiben']['value'] ?><?php endif; ?>"
"Score sectoriel Xerfi";"<?php if (isset($this->ratings['xerfi']['value'], $this->ratings['xerfi_unilend']['value'])) : ?><?= $this->ratings['xerfi']['value'] ?> / <?= $this->ratings['xerfi_unilend']['value'] ?><?php else : ?>N/A<?php endif; ?>"
"Date du privilège le plus récent";"<?php if (isset($this->ratings['date_dernier_privilege']['value']) && false === empty($this->ratings['date_dernier_privilege']['value'])) : ?><?= $this->dates->formatDate($this->ratings['date_dernier_privilege']['value'], 'd/m/Y') ?><?php endif; ?>"
"Dernière situation de trésorerie connue";"<?php if (isset($this->ratings['date_tresorerie']['value']) && false === empty($this->ratings['date_tresorerie']['value'])) : ?><?= $this->dates->formatDate($this->ratings['date_tresorerie']['value'], 'd/m/Y') ?><?php endif; ?>";"<?php if (isset($this->ratings['montant_tresorerie']['value'])) : ?><?= $this->ratings['montant_tresorerie']['value'] ?><?php endif; ?>"
"Délais de paiement Altares (à date)";"<?php if (isset($this->ratings['delais_paiement_altares']['value'])) : ?><?= $this->ratings['delais_paiement_altares']['value'] ?><?php endif; ?>"
"Délais de paiement du secteur";"<?php if (isset($this->ratings['delais_paiement_secteur']['value'])) : ?><?= $this->ratings['delais_paiement_secteur']['value'] ?><?php endif; ?>"
"Dailly";"<?= isset($this->ratings['dailly']['value']) && '1' === $this->ratings['dailly']['value'] ? 'Oui' : (isset($this->ratings['dailly']['value']) ? 'Non' : 'N/A') ?>"
"Affacturage";"<?= isset($this->ratings['affacturage']['value']) && '1' === $this->ratings['affacturage']['value'] ? 'Oui' : (isset($this->ratings['affacturage']['value']) ? 'Non' : 'N/A') ?>"
""
"Capital restant dû à date";"<?= $this->fCompanyOwedCapital ?>"
""
"Bilans"
"Actif";<?php foreach ($this->aAnnualAccounts as $aAnnualAccounts) : ?>"<?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?>";"<?= $aAnnualAccounts['duree_exercice_fiscal'] ?>";<?php endforeach; ?>

"Capital souscrit non appelé";"AA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AA'] - $aBalanceSheet['details']['AA']) / $aBalanceSheet['details']['AA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Frais d'établissement";"AB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AB'] - $aBalanceSheet['details']['AB']) / $aBalanceSheet['details']['AB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Frais de développement";"AD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AD'] - $aBalanceSheet['details']['AD']) / $aBalanceSheet['details']['AD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Concessions, brevets et droits similaires";"AF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AF'] - $aBalanceSheet['details']['AF']) / $aBalanceSheet['details']['AF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Fonds commercial";"AH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AH'] - $aBalanceSheet['details']['AH']) / $aBalanceSheet['details']['AH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres immos Incorpo";"AJ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AJ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AJ'] - $aBalanceSheet['details']['AJ']) / $aBalanceSheet['details']['AJ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AJ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Avances et acomptes sur immos Incorpo";"AL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AL'] - $aBalanceSheet['details']['AL']) / $aBalanceSheet['details']['AL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immobilisations incorporelles";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('AB', 'AD', 'AF', 'AH', 'AJ', 'AL'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Terrains";"AN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AN'] - $aBalanceSheet['details']['AN']) / $aBalanceSheet['details']['AN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Constructions";"AP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AP'] - $aBalanceSheet['details']['AP']) / $aBalanceSheet['details']['AP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"ITMOI";"AR";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AR']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AR'] - $aBalanceSheet['details']['AR']) / $aBalanceSheet['details']['AR'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AR'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres immo corpo";"AT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AT'] - $aBalanceSheet['details']['AT']) / $aBalanceSheet['details']['AT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immos en cours";"AV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AV'] - $aBalanceSheet['details']['AV']) / $aBalanceSheet['details']['AV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Avances et acomptes sur immos corpo";"AX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['AX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['AX'] - $aBalanceSheet['details']['AX']) / $aBalanceSheet['details']['AX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['AX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immobilisations corporelles";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('AN', 'AP', 'AR', 'AT', 'AV', 'AX'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Participations évaluées selon la méthode";"CS";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CS']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CS'] - $aBalanceSheet['details']['CS']) / $aBalanceSheet['details']['CS'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CS'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres participations";"CU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CU'] - $aBalanceSheet['details']['CU']) / $aBalanceSheet['details']['CU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Créances rattachées à des participations";"BB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BB'] - $aBalanceSheet['details']['BB']) / $aBalanceSheet['details']['BB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres titres immobilisés";"BD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BD'] - $aBalanceSheet['details']['BD']) / $aBalanceSheet['details']['BD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Prêts";"BF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BF'] - $aBalanceSheet['details']['BF']) / $aBalanceSheet['details']['BF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres immobilisations financières";"BH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BH'] - $aBalanceSheet['details']['BH']) / $aBalanceSheet['details']['BH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immobilisations financières";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('CS', 'CU', 'BB', 'BD', 'BF', 'BH'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Total actif immobilisé";"BJ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BJ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BJ'] - $aBalanceSheet['details']['BJ']) / $aBalanceSheet['details']['BJ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BJ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Matières premières";"BL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BL'] - $aBalanceSheet['details']['BL']) / $aBalanceSheet['details']['BL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"En-cours de bien";"BN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BN'] - $aBalanceSheet['details']['BN']) / $aBalanceSheet['details']['BN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"En-cours de services";"BP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BP'] - $aBalanceSheet['details']['BP']) / $aBalanceSheet['details']['BP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Produits Intermédiaires et finis";"BR";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BR']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BR'] - $aBalanceSheet['details']['BR']) / $aBalanceSheet['details']['BR'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BR'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Marchandises";"BT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BT'] - $aBalanceSheet['details']['BT']) / $aBalanceSheet['details']['BT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Stocks";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('BL', 'BN', 'BP', 'BR', 'BT'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Avances et acomptes versés sur commande";"BV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BV'] - $aBalanceSheet['details']['BV']) / $aBalanceSheet['details']['BV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Clients et comptes rattachés";"BX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BX'] - $aBalanceSheet['details']['BX']) / $aBalanceSheet['details']['BX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres créances + K souscrit non appelé";"BZ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['BZ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BZ'] - $aBalanceSheet['details']['BZ']) / $aBalanceSheet['details']['BZ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BZ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Capital souscrit appelé non versé";"CB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CB'] - $aBalanceSheet['details']['CB']) / $aBalanceSheet['details']['CB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Créances clients et autres";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('BV', 'BX', 'BZ', 'CB'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Disponibilités";"CF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CF'] - $aBalanceSheet['details']['CF']) / $aBalanceSheet['details']['CF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"VMP";"CD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CD'] - $aBalanceSheet['details']['CD']) / $aBalanceSheet['details']['CD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Trésorerie";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('CF', 'CD'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Charges constatées d'avance";"CH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CH'] - $aBalanceSheet['details']['CH']) / $aBalanceSheet['details']['CH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total actif circulant";"CJ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CJ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CJ'] - $aBalanceSheet['details']['CJ']) / $aBalanceSheet['details']['CJ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CJ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Frais d'émission d'emprunt à étaler";"CW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CW'] - $aBalanceSheet['details']['CW']) / $aBalanceSheet['details']['CW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Primes de remboursement des obligations";"CM";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CM']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CM'] - $aBalanceSheet['details']['CM']) / $aBalanceSheet['details']['CM'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CM'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Ecarts de conversion actif";"CN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['details']['CN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CN'] - $aBalanceSheet['details']['CN']) / $aBalanceSheet['details']['CN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Comptes de régularisation";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Total actif";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('AA', 'AB', 'AD', 'AF', 'AH', 'AJ', 'AL', 'AN', 'AP', 'AR', 'AT', 'AV', 'AX', 'CS', 'CU', 'BB', 'BD', 'BF', 'BH', 'BL', 'BN', 'BP', 'BR', 'BT', 'BV', 'BX', 'BZ', 'CB', 'CF', 'CD', 'CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

""
"Passif"
"Capital social";"DA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
          ?>"<?= empty($aBalanceSheet['details']['DA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DA'] - $aBalanceSheet['details']['DA']) / $aBalanceSheet['details']['DA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total capitaux propres";"DL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DL'] - $aBalanceSheet['details']['DL']) / $aBalanceSheet['details']['DL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres fonds propres";"DO";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DO']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DO'] - $aBalanceSheet['details']['DO']) / $aBalanceSheet['details']['DO'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DO'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total fonds propres";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DL', 'DO'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Amortissements sur Immobilisations";"BK";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['BK']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['BK'] - $aBalanceSheet['details']['BK']) / $aBalanceSheet['details']['BK'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['BK'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dépréciation de l'actif circulant";"CK";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['CK']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['CK'] - $aBalanceSheet['details']['CK']) / $aBalanceSheet['details']['CK'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['CK'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Provisions pour risques et charges";"DR";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DR']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DR'] - $aBalanceSheet['details']['DR']) / $aBalanceSheet['details']['DR'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DR'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Emprunts obligataires convertibles";"DS";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DS']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DS'] - $aBalanceSheet['details']['DS']) / $aBalanceSheet['details']['DS'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DS'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres emprunts obligataires";"DT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DT'] - $aBalanceSheet['details']['DT']) / $aBalanceSheet['details']['DT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Emprunts et dettes auprès des établissements de crédit";"DU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DU'] - $aBalanceSheet['details']['DU']) / $aBalanceSheet['details']['DU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Emprunts et dettes financières divers";"DV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DV'] - $aBalanceSheet['details']['DV']) / $aBalanceSheet['details']['DV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes financières";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DS', 'DT', 'DU', 'DV'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Avances et accomptes reçus";"DW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DW'] - $aBalanceSheet['details']['DW']) / $aBalanceSheet['details']['DW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes fournisseurs et comptes rattachés";"DX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DX'] - $aBalanceSheet['details']['DX']) / $aBalanceSheet['details']['DX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes fournisseurs";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DW', 'DX'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Dettes fiscales et sociales";"DY";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DY']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DY'] - $aBalanceSheet['details']['DY']) / $aBalanceSheet['details']['DY'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DY'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes sur immobilisations et comptes rattachés";"DZ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['DZ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['DZ'] - $aBalanceSheet['details']['DZ']) / $aBalanceSheet['details']['DZ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['DZ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres dettes";"EA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
         ?>"<?= empty($aBalanceSheet['details']['EA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['EA'] - $aBalanceSheet['details']['EA']) / $aBalanceSheet['details']['EA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['EA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres dettes";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DY', 'DZ', 'EA'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Produits constatés d'avance";"EB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['EB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['EB'] - $aBalanceSheet['details']['EB']) / $aBalanceSheet['details']['EB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['EB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Écarts de conversion passif";"ED";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['ED']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['ED'] - $aBalanceSheet['details']['ED']) / $aBalanceSheet['details']['ED'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['ED'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Comptes de ";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('EB', 'ED'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Total passif";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DL', 'DO', 'BK', 'CK', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'ED'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

""
"Autres infos"
"2 : CBC, et soldes créditeurs de banques et CCP";"EH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['EH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['EH'] - $aBalanceSheet['details']['EH']) / $aBalanceSheet['details']['EH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['EH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"2 : Prêt participatif";"EI";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['EI']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['EI'] - $aBalanceSheet['details']['EI']) / $aBalanceSheet['details']['EI'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['EI'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"4 : Crédit bail Mobilier";"HP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HP'] - $aBalanceSheet['details']['HP']) / $aBalanceSheet['details']['HP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"4 : Crédit bail Immobilier";"HQ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HQ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HQ'] - $aBalanceSheet['details']['HQ']) / $aBalanceSheet['details']['HQ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HQ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"4 : Transfert de charges";"A1";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['A1']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['A1'] - $aBalanceSheet['details']['A1']) / $aBalanceSheet['details']['A1'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['A1'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"A5 : Investissements";"0J";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['0J']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['0J'] - $aBalanceSheet['details']['0J']) / $aBalanceSheet['details']['0J'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['0J'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"A8 : Dettes à rembourser ds l'année (à plus de 1 an à l'origine)";"VH (2)";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['VH2']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['VH2'] - $aBalanceSheet['details']['VH2']) / $aBalanceSheet['details']['VH2'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['VH2'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"A8 : Groupes et associés (placés en "Emprunts et dettes diverses")";"VI";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['VI']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['VI'] - $aBalanceSheet['details']['VI']) / $aBalanceSheet['details']['VI'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['VI'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

""
"Compte de résultat"
"Chiffre d'Affaires nets";"FL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FL'] - $aBalanceSheet['details']['FL']) / $aBalanceSheet['details']['FL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Production stockée";"FM";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FM']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FM'] - $aBalanceSheet['details']['FM']) / $aBalanceSheet['details']['FM'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FM'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Production immobilisée";"FN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FN'] - $aBalanceSheet['details']['FN']) / $aBalanceSheet['details']['FN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Subventions d'exploitation";"FO";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FO']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FO'] - $aBalanceSheet['details']['FO']) / $aBalanceSheet['details']['FO'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FO'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Reprises sur amort. et prov., transferts de charges";"FP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FP'] - $aBalanceSheet['details']['FP']) / $aBalanceSheet['details']['FP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres produits";"FQ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
           ?>"<?= empty($aBalanceSheet['details']['FQ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FQ'] - $aBalanceSheet['details']['FQ']) / $aBalanceSheet['details']['FQ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FQ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Achats de marchandises";"FS";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FS']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FS'] - $aBalanceSheet['details']['FS']) / $aBalanceSheet['details']['FS'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FS'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Variation de stock (marchandises)";"FT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FT'] - $aBalanceSheet['details']['FT']) / $aBalanceSheet['details']['FT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Achats de matières premières et autres approv.";"FU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FU'] - $aBalanceSheet['details']['FU']) / $aBalanceSheet['details']['FU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Variation de stock (matières premiières et approv.)";"FV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FV'] - $aBalanceSheet['details']['FV']) / $aBalanceSheet['details']['FV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres achats et charges externes";"FW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FW'] - $aBalanceSheet['details']['FW']) / $aBalanceSheet['details']['FW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Impots, taxes et versements assimilés";"FX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FX'] - $aBalanceSheet['details']['FX']) / $aBalanceSheet['details']['FX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Salaires et traitements";"FY";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FY']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FY'] - $aBalanceSheet['details']['FY']) / $aBalanceSheet['details']['FY'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FY'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Charges sociales";"FZ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['FZ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['FZ'] - $aBalanceSheet['details']['FZ']) / $aBalanceSheet['details']['FZ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['FZ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations aux amortissements";"GA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GA'] - $aBalanceSheet['details']['GA']) / $aBalanceSheet['details']['GA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations aux provisions";"GB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GB'] - $aBalanceSheet['details']['GB']) / $aBalanceSheet['details']['GB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations aux provisions (sur actif circulant)";"GC";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GC']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GC'] - $aBalanceSheet['details']['GC']) / $aBalanceSheet['details']['GC'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GC'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotation aux provisions (pour risques et charges)";"GD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GD'] - $aBalanceSheet['details']['GD']) / $aBalanceSheet['details']['GD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres charges";"GE";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
          ?>"<?= empty($aBalanceSheet['details']['GE']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GE'] - $aBalanceSheet['details']['GE']) / $aBalanceSheet['details']['GE'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GE'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Résultat d'Exploitation";"GG";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GG']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GG'] - $aBalanceSheet['details']['GG']) / $aBalanceSheet['details']['GG'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GG'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Résultat financier";"GV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GV'] - $aBalanceSheet['details']['GV']) / $aBalanceSheet['details']['GV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Reprises sur amort. et prov., transferts de charges fi";"GM";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GM']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GM'] - $aBalanceSheet['details']['GM']) / $aBalanceSheet['details']['GM'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GM'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations financières aux amort. Et prov.";"GQ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GQ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GQ'] - $aBalanceSheet['details']['GQ']) / $aBalanceSheet['details']['GQ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GQ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total Charges Financières";"GU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GU'] - $aBalanceSheet['details']['GU']) / $aBalanceSheet['details']['GU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"RCAI";"GW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['GW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['GW'] - $aBalanceSheet['details']['GW']) / $aBalanceSheet['details']['GW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['GW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Produits exceptionnels sur opérations de gestion";"HA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HA'] - $aBalanceSheet['details']['HA']) / $aBalanceSheet['details']['HA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Produits exceptionnels sur opérations de capital";"HB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HB'] - $aBalanceSheet['details']['HB']) / $aBalanceSheet['details']['HB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Reprises sur provisions et transferts de charges";"HC";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HC']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HC'] - $aBalanceSheet['details']['HC']) / $aBalanceSheet['details']['HC'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HC'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Charges exceptionnelles sur opérations de gestion";"HE";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HE']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HE'] - $aBalanceSheet['details']['HE']) / $aBalanceSheet['details']['HE'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HE'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Charges exceptionnelles sur opérations en capital";"HF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HF'] - $aBalanceSheet['details']['HF']) / $aBalanceSheet['details']['HF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations exceptionnelles aux amts et provisions";"HG";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HG']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HG'] - $aBalanceSheet['details']['HG']) / $aBalanceSheet['details']['HG'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HG'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Résultat net";"HN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['HN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['HN'] - $aBalanceSheet['details']['HN']) / $aBalanceSheet['details']['HN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['HN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Nombre d'employés";"YP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['details']['YP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['details']['YP'] - $aBalanceSheet['details']['YP']) / $aBalanceSheet['details']['YP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['details']['YP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }

$iBalanceSheetsCount     = count($this->aBalanceSheets);
$aOperationalCashFlow    = array();
$aGrossOperatingSurplus  = array();
$aMediumLongTermDebt     = array();
$aBalanceTotal           = array();
$iLastAnnualAccountsId   = current($annualAccountsIds);
$iOldestAnnualAccountsId = end($annualAccountsIds);

foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
    $aOperationalCashFlow[$iBalanceSheetId] =
        $aBalanceSheet['details']['HN']
        - $aBalanceSheet['details']['FP']
        + $aBalanceSheet['details']['GA']
        + $aBalanceSheet['details']['GB']
        + $aBalanceSheet['details']['GC']
        + $aBalanceSheet['details']['GD']
        - $aBalanceSheet['details']['GM']
        + $aBalanceSheet['details']['GQ']
        - $aBalanceSheet['details']['HB']
        - $aBalanceSheet['details']['HC']
        + $aBalanceSheet['details']['HF']
        + $aBalanceSheet['details']['HG'];

    $aGrossOperatingSurplus[$iBalanceSheetId] =
        $aBalanceSheet['details']['GG']
        + $aBalanceSheet['details']['GA']
        + $aBalanceSheet['details']['GB']
        + $aBalanceSheet['details']['GC']
        + $aBalanceSheet['details']['GD']
        - $aBalanceSheet['details']['FP']
        - $aBalanceSheet['details']['FQ']
        + $aBalanceSheet['details']['GE'];

    $aMediumLongTermDebt[$iBalanceSheetId] =
        $aBalanceSheet['details']['DS']
        + $aBalanceSheet['details']['DT']
        + $aBalanceSheet['details']['DU']
        + $aBalanceSheet['details']['DV']
        - $aBalanceSheet['details']['EH']
        - $aBalanceSheet['details']['VI'];

    $aBalanceTotal[$iBalanceSheetId] =
        $aBalanceSheet['details']['AN']
        + $aBalanceSheet['details']['AP']
        + $aBalanceSheet['details']['AR']
        + $aBalanceSheet['details']['AT']
        + $aBalanceSheet['details']['AV']
        + $aBalanceSheet['details']['AX']
        + $aBalanceSheet['details']['AB']
        + $aBalanceSheet['details']['AD']
        + $aBalanceSheet['details']['AF']
        + $aBalanceSheet['details']['AH']
        + $aBalanceSheet['details']['AJ']
        + $aBalanceSheet['details']['AL']
        + $aBalanceSheet['details']['CS']
        + $aBalanceSheet['details']['CU']
        + $aBalanceSheet['details']['BB']
        + $aBalanceSheet['details']['BD']
        + $aBalanceSheet['details']['BF']
        + $aBalanceSheet['details']['BH']
        + $aBalanceSheet['details']['BL']
        + $aBalanceSheet['details']['BN']
        + $aBalanceSheet['details']['BP']
        + $aBalanceSheet['details']['BR']
        + $aBalanceSheet['details']['BT']
        + $aBalanceSheet['details']['BV']
        + $aBalanceSheet['details']['BX']
        + $aBalanceSheet['details']['BZ']
        + $aBalanceSheet['details']['CB']
        + $aBalanceSheet['details']['CH']
        + $aBalanceSheet['details']['CF']
        + $aBalanceSheet['details']['CD']
        - $aBalanceSheet['details']['BK']
        - $aBalanceSheet['details']['CK'];

}
?>

""
"Ratios et analyses"
"Solvabilité"
"Dette financière nette";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['details']['DS']
            + $aBalanceSheet['details']['DT']
            + $aBalanceSheet['details']['DU']
            + $aBalanceSheet['details']['DV']
            - $aBalanceSheet['details']['CF']
            - $aBalanceSheet['details']['CD']
            - $aBalanceSheet['details']['EH']
            - $aBalanceSheet['details']['VI'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"CAF";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = $aOperationalCashFlow[$iBalanceSheetId];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"CAF disponible";<?php
    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        ?>"<?= ($aOperationalCashFlow[$iBalanceSheetId] - $this->aBalanceSheets[$iBalanceSheetId]['details']['VH2']) ?>";<?php
        break;
    }
    ?>

"CAF moyenne pondérée sur 3 ans";"<?php if (3 === $iBalanceSheetsCount) : ?><?php list($iLastOperationalCashFlow, $iPreviousOperationalCashFlow, $iSecondToLastOperationalCashFlow) = array_values($aOperationalCashFlow); ?><?= round((2 * $iLastOperationalCashFlow + $iPreviousOperationalCashFlow + 0.5 * $iSecondToLastOperationalCashFlow) / 3.5) ?><?php endif; ?>";

"DMLT / CAF";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aOperationalCashFlow[$iBalanceSheetId]) ? 0 : round($aMediumLongTermDebt[$iBalanceSheetId] / $aOperationalCashFlow[$iBalanceSheetId], 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / EBE";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aGrossOperatingSurplus[$iBalanceSheetId]) ? 0 : round($aMediumLongTermDebt[$iBalanceSheetId] / $aGrossOperatingSurplus[$iBalanceSheetId], 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Solvabilité générale";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['details']['DW']
            + $aBalanceSheet['details']['DX']
            + $aBalanceSheet['details']['DY']
            + $aBalanceSheet['details']['DZ']
            + $aBalanceSheet['details']['EA']
            + $aBalanceSheet['details']['EB']
            + $aBalanceSheet['details']['EH']
            - $aBalanceSheet['details']['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['BL']
                + $aBalanceSheet['details']['BN']
                + $aBalanceSheet['details']['BP']
                + $aBalanceSheet['details']['BR']
                + $aBalanceSheet['details']['BT']
                + $aBalanceSheet['details']['BV']
                + $aBalanceSheet['details']['BX']
                + $aBalanceSheet['details']['BZ']
                + $aBalanceSheet['details']['CB']
                + $aBalanceSheet['details']['CH']
            ) / $iDivisor, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Liquidité générale";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['details']['DW']
            + $aBalanceSheet['details']['DX']
            + $aBalanceSheet['details']['DY']
            + $aBalanceSheet['details']['DZ']
            + $aBalanceSheet['details']['EA']
            + $aBalanceSheet['details']['EB']
            + $aBalanceSheet['details']['EH']
            - $aBalanceSheet['details']['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['BL']
                + $aBalanceSheet['details']['BN']
                + $aBalanceSheet['details']['BP']
                + $aBalanceSheet['details']['BR']
                + $aBalanceSheet['details']['BT']
                + $aBalanceSheet['details']['BV']
                + $aBalanceSheet['details']['BX']
                + $aBalanceSheet['details']['BZ']
                + $aBalanceSheet['details']['CB']
                + $aBalanceSheet['details']['CH']
                + $aBalanceSheet['details']['CF']
                + $aBalanceSheet['details']['CD']
            ) / $iDivisor, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Liquidité réduite";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['details']['DW']
            + $aBalanceSheet['details']['DX']
            + $aBalanceSheet['details']['DY']
            + $aBalanceSheet['details']['DZ']
            + $aBalanceSheet['details']['EA']
            + $aBalanceSheet['details']['EB']
            + $aBalanceSheet['details']['EH']
            - $aBalanceSheet['details']['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['BV']
                + $aBalanceSheet['details']['BX']
                + $aBalanceSheet['details']['BZ']
                + $aBalanceSheet['details']['CB']
                + $aBalanceSheet['details']['CH']
                + $aBalanceSheet['details']['CF']
                + $aBalanceSheet['details']['CD']
            ) / $iDivisor, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

""
"Endettement et structure"
"FP / Total bilan net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : round((
                $aBalanceSheet['details']['DL']
                + $aBalanceSheet['details']['DO']
            ) / $aBalanceTotal[$iBalanceSheetId] * 100);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Quasi FP / Total bilan net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : round((
                $aBalanceSheet['details']['DL']
                + $aBalanceSheet['details']['DO']
                + $aBalanceSheet['details']['EI']
                + $aBalanceSheet['details']['VI']
            ) / $aBalanceTotal[$iBalanceSheetId] * 100);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / Total bilan net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : round($aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId] * 100, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / Quasi FP (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['details']['DL']
            + $aBalanceSheet['details']['DO']
            + $aBalanceSheet['details']['EI']
            + $aBalanceSheet['details']['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : round($aMediumLongTermDebt[$iBalanceSheetId] / $iDivisor * 100, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"GEARING (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['details']['DL']
            + $aBalanceSheet['details']['DO']
            + $aBalanceSheet['details']['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['DS']
                + $aBalanceSheet['details']['DT']
                + $aBalanceSheet['details']['DU']
                + $aBalanceSheet['details']['DV']
                - $aBalanceSheet['details']['CF']
                - $aBalanceSheet['details']['CD']
                - $aBalanceSheet['details']['EH']
                - $aBalanceSheet['details']['VI']
            ) / $iDivisor * 100);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Charges fi / EBE (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['details']['GG']
            + $aBalanceSheet['details']['GA']
            + $aBalanceSheet['details']['GB']
            + $aBalanceSheet['details']['GC']
            + $aBalanceSheet['details']['GD']
            - $aBalanceSheet['details']['FP']
            - $aBalanceSheet['details']['FQ']
            + $aBalanceSheet['details']['GE'];
        $iCurrentNumber = empty($iDivisor) ? 0 : round(abs($aBalanceSheet['details']['GU']) / $iDivisor * 100);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Charges fi / Résultat net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['details']['HN']) ? 0 : round(abs($aBalanceSheet['details']['GU']) / $aBalanceSheet['details']['HN'] * 100);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"CCA stables";<?php if (1 === $iBalanceSheetsCount) { ?>"<?= $this->aBalanceSheets[$iLastAnnualAccountsId]['details']['VI'] ?>";<?php } elseif (2 <= $iBalanceSheetsCount) {
        $iLastNumber = $this->aBalanceSheets[$iLastAnnualAccountsId]['details']['VI'];
        foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
            if ($iBalanceSheetId !== $iLastAnnualAccountsId) {
                ?>"<?= min($iLastNumber, $aBalanceSheet['details']['VI']) ?>";<?php
                break;
            }
        }
    }
    ?>

""
"Rotations"
"FR";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['details']['DL']
            + $aBalanceSheet['details']['DO']
            + $aBalanceSheet['details']['DU']
            - $aBalanceSheet['details']['EH']
            + $aBalanceSheet['details']['VI']
            - (
                $aBalanceSheet['details']['CS']
                + $aBalanceSheet['details']['CU']
                + $aBalanceSheet['details']['BB']
                + $aBalanceSheet['details']['BD']
                + $aBalanceSheet['details']['BF']
                + $aBalanceSheet['details']['BH']
                + $aBalanceSheet['details']['AN']
                + $aBalanceSheet['details']['AP']
                + $aBalanceSheet['details']['AR']
                + $aBalanceSheet['details']['AT']
                + $aBalanceSheet['details']['AV']
                + $aBalanceSheet['details']['AX']
                + $aBalanceSheet['details']['AB']
                + $aBalanceSheet['details']['AD']
                + $aBalanceSheet['details']['AF']
                + $aBalanceSheet['details']['AH']
                + $aBalanceSheet['details']['AJ']
                + $aBalanceSheet['details']['AL']
                - $aBalanceSheet['details']['BK']
            );

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"FR (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['DL']
                + $aBalanceSheet['details']['DO']
                + $aBalanceSheet['details']['DU']
                - $aBalanceSheet['details']['EH']
                + $aBalanceSheet['details']['VI']
                - (
                    $aBalanceSheet['details']['CS']
                    + $aBalanceSheet['details']['CU']
                    + $aBalanceSheet['details']['BB']
                    + $aBalanceSheet['details']['BD']
                    + $aBalanceSheet['details']['BF']
                    + $aBalanceSheet['details']['BH']
                    + $aBalanceSheet['details']['AN']
                    + $aBalanceSheet['details']['AP']
                    + $aBalanceSheet['details']['AR']
                    + $aBalanceSheet['details']['AT']
                    + $aBalanceSheet['details']['AV']
                    + $aBalanceSheet['details']['AX']
                    + $aBalanceSheet['details']['AB']
                    + $aBalanceSheet['details']['AD']
                    + $aBalanceSheet['details']['AF']
                    + $aBalanceSheet['details']['AH']
                    + $aBalanceSheet['details']['AJ']
                    + $aBalanceSheet['details']['AL']
                    - $aBalanceSheet['details']['BK']
                )
            ) / $iDivisor * 360);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"BFR";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['details']['BL']
            + $aBalanceSheet['details']['BN']
            + $aBalanceSheet['details']['BP']
            + $aBalanceSheet['details']['BR']
            + $aBalanceSheet['details']['BT']
            + $aBalanceSheet['details']['BV']
            + $aBalanceSheet['details']['BX']
            + $aBalanceSheet['details']['BZ']
            + $aBalanceSheet['details']['CB']
            + $aBalanceSheet['details']['CH']
            - (
                $aBalanceSheet['details']['DV']
                + $aBalanceSheet['details']['DW']
                + $aBalanceSheet['details']['DX']
                + $aBalanceSheet['details']['DY']
                + $aBalanceSheet['details']['DZ']
                + $aBalanceSheet['details']['EA']
                + $aBalanceSheet['details']['EB']
                + $aBalanceSheet['details']['ED']
                - $aBalanceSheet['details']['VI']
            );

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"BFR (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['BL']
                + $aBalanceSheet['details']['BN']
                + $aBalanceSheet['details']['BP']
                + $aBalanceSheet['details']['BR']
                + $aBalanceSheet['details']['BT']
                + $aBalanceSheet['details']['BV']
                + $aBalanceSheet['details']['BX']
                + $aBalanceSheet['details']['BZ']
                + $aBalanceSheet['details']['CB']
                + $aBalanceSheet['details']['CH']
                - (
                    $aBalanceSheet['details']['DV']
                    + $aBalanceSheet['details']['DW']
                    + $aBalanceSheet['details']['DX']
                    + $aBalanceSheet['details']['DY']
                    + $aBalanceSheet['details']['DZ']
                    + $aBalanceSheet['details']['EA']
                    + $aBalanceSheet['details']['EB']
                    + $aBalanceSheet['details']['ED']
                    - $aBalanceSheet['details']['VI']
                )
            ) / $iDivisor * 360);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Trésorerie nette";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['details']['CF']
            + $aBalanceSheet['details']['CD']
            - $aBalanceSheet['details']['EH'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Stocks (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['details']['FL']) ? 0 : round((
                $aBalanceSheet['details']['BL']
                + $aBalanceSheet['details']['BN']
                + $aBalanceSheet['details']['BP']
                + $aBalanceSheet['details']['BR']
                + $aBalanceSheet['details']['BT']
            ) / $aBalanceSheet['details']['FL'] * 360);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Créances clients (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : round($aBalanceSheet['details']['BX'] / $iDivisor * 360);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Dettes fournisseurs (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : round(($aBalanceSheet['details']['DW'] + $aBalanceSheet['details']['DX']) / $iDivisor * 360);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Écart créances clients - Dettes fournisseurs";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : round((
                $aBalanceSheet['details']['BX']
                - $aBalanceSheet['details']['DW']
                - $aBalanceSheet['details']['DX']
            ) / $iDivisor * 360);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

""
"Rentabilité"
"EBE / CA (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['details']['FL']) ? 0 : round($aGrossOperatingSurplus[$iBalanceSheetId] / $aBalanceSheet['details']['FL'] * 100, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Résultat net / CA (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['details']['FL']) ? 0 : round($aBalanceSheet['details']['HN'] / $aBalanceSheet['details']['FL'] * 100, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Disponibilités / Total bilan net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : round($aBalanceSheet['details']['CF'] / $aBalanceTotal[$iBalanceSheetId] * 100, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Rentabilité des capitaux (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['details']['DA']) ? 0 : round($aBalanceSheet['details']['DL'] / $aBalanceSheet['details']['DA'] * 100, 1);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>
