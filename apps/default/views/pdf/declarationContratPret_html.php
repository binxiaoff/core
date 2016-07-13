<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="fr-FR" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>SFF cerfa 2062</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
</head>
<body>
<div class="shell">
    <div class="page-break">
        <div class="header">
            <div class="col-left">
                <div class="logocerfa"><a href="#"></a></div>
                <p>
                    <small>N° 10142 * 05 <br/>N° 50058 # 05</small>
                </p>
            </div>
            <div class="col-right">
                <p class="num">N° 2062</p>
                <div class="cl">&nbsp;</div>
            </div>
            <div class="col-center">
                <div class="logorep"><a href="#" class="logo"></a></div>
            </div>
            <div class="cl">&nbsp;</div>
        </div>
        <div class="document">
            <div class="doc-head">
                <h2>Déclaration de contrat de prêt</h2>
                <p class="subtitle">(Code général des impôts : article 242 ter 3, article 49 B de l’annexe III et article 23 L de l’annexe IV)</p>
            </div>
            <div class="doc-body">
                <div class="section">
                    <h3>I. DÉSIGNATION DU DÉCLARANT (intermédiaire ou à défaut emprunteur)</h3>
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="border: 1px solid #231f20;">
                                <p>
                                    <label>Nom et prénom ou raison sociale, profession :</label>
                                    <span class="editable"><?= strtoupper($this->ficelle->speCharNoAccent($this->raisonSociale)) ?></span>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #231f20;">
                                <label>Adresse complète :</label>
                                <span class="editable"><?= strtoupper($this->ficelle->speCharNoAccent($this->adresse)) ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="section">
                    <h3>II. RENSEIGNEMENTS CONCERNANT LES CONDITIONS DU PRÊT ET LES PARTIES AU CONTRAT</h3>
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <th style="border: 1px solid #231f20;" colspan="4">Conditions du prêt</th>
                            <th rowspan="2">
                                Noms, prénoms et adresses complètes (y compris code département) des parties
                                <span>ÉCRIVEZ EN CAPITALES</span>
                                <small>5</small>
                            </th>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #231f20;" class="headings tc" width="42">
                                Date
                                <small>1</small>
                            </td>
                            <td style="border: 1px solid #231f20;" class="headings tc" width="32">
                                Durée
                                <small>2</small>
                            </td>
                            <td style="border: 1px solid #231f20;" class="headings tc" width="22">
                                Taux
                                <small>3</small>
                            </td>
                            <td style="border: 1px solid #231f20;" class="headings tc" width="62">
                                Montant
                                <br/>en principal
                                <small>4</small>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #231f20;" class="tc">
                                <span class="date"><?= date('d/m/Y', strtotime($this->oLoans->added)) ?></span>
                            </td>
                            <td style="border: 1px solid #231f20;" class="tc"><?= $this->projects->period / 12 ?></td>
                            <td style="border: 1px solid #231f20;" class="tc">
                                <small><?= $this->ficelle->formatNumber($this->oLoans->rate, 1) ?></small>
                            </td>
                            <td style="border: 1px solid #231f20;" class="tc"><?= $this->ficelle->formatNumber($this->oLoans->amount / 100, 0) ?></td>
                            <td style="border: 1px solid #231f20;" class="large nopadding">
                                <table cellspacing="0" cellpadding="0" class="title">
                                    <tr>
                                        <td style="border: 1px solid #231f20;" width="16" class="noborder-top noborder-left">A</td>
                                        <td style="border: 1px solid #231f20;" width="290" class="noborder-top">Créancier ou porteur ou prêteur</td>
                                    </tr>
                                </table>
                                <table width="100%" cellspacing="0" cellpadding="0" class="inner">
                                    <tr>
                                        <td><?= strtoupper($this->ficelle->speCharNoAccent($this->nomPreteur)) ?></td>
                                    </tr>
                                    <tr>
                                        <td><?= strtoupper($this->ficelle->speCharNoAccent($this->adressePreteur)) ?></td>
                                    </tr>
                                    <tr>
                                        <td><?= strtoupper($this->cpPreteur . ' ' . $this->ficelle->speCharNoAccent($this->villePreteur)) ?></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                    </tr>
                                </table>
                                <table cellspacing="0" cellpadding="0" class="title">
                                    <tr>
                                        <td style="border: 1px solid #231f20;" width="16" class="noborder-left">B</td>
                                        <td style="border: 1px solid #231f20;" width="290">Débiteur ou émetteur ou emprunteur</td>
                                    </tr>
                                </table>
                                <table width="100%" cellspacing="0" cellpadding="0" class="inner">
                                    <tr>
                                        <td><?= strtoupper($this->ficelle->speCharNoAccent($this->companiesEmp->name)) ?></td>
                                    </tr>
                                    <tr>
                                        <td><?= strtoupper($this->ficelle->speCharNoAccent($this->companiesEmp->adresse1)) ?></td>
                                    </tr>
                                    <tr>
                                        <td><?= strtoupper($this->companiesEmp->zip . ' ' . $this->ficelle->speCharNoAccent($this->companiesEmp->city)) ?></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="border: 1px solid #231f20;" width="24" class="noborder-top"><strong>C</strong>
                            </td>
                            <td style="border: 1px solid #231f20;" width="86" class="noborder-top">
                                <strong>Observations</strong></td>
                            <td style="border: 1px solid #231f20;" class="noborder">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="noborder">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="noborder">&nbsp;</td>
                        </tr>
                    </table>

                    <table width="100%" cellspacing="0" cellpadding="0" class="stats">
                        <tr>
                            <td style="border: 1px solid #231f20;" width="24"><strong>D</strong></td>
                            <td style="border: 1px solid #231f20;" width="80" class="tc">Années</td>
                            <?php for ($i = 0; $i < 10; $i++) : ?>
                                <td style="border: 1px solid #231f20;">
                                    <span class="editable"><?= isset($this->lEcheances[$i]) ? $this->lEcheances[$i]['annee'] : '..........' ?></span>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #231f20;" colspan="2">Montant annuel des intérêts exigibles</td>
                            <?php for ($i = 0; $i < 10; $i++) : ?>
                                <td style="border: 1px solid #231f20;">
                                    <span class="editable"><?= isset($this->lEcheances[$i]) ? $this->ficelle->formatNumber($this->lEcheances[$i]['interets'] / 100) : '&nbsp;' ?></span>
                                </td>
                            <?php endfor; ?>

                        </tr>
                        <tr>
                            <td style="border: 1px solid #231f20;" colspan="2">Montant annuel du principal remboursé</td>
                            <?php for ($i = 0; $i < 10; $i++) : ?>
                                <td style="border: 1px solid #231f20;">
                                    <span class="editable"><?= isset($this->lEcheances[$i]) ? $this->ficelle->formatNumber($this->lEcheances[$i]['capital'] / 100) : '&nbsp;' ?></span>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <?php if (count($this->lEcheances) > 10) : ?>
                            <tr>
                                <td style="border: 1px solid #231f20;" class="empty">&nbsp;</td>
                                <td style="border: 1px solid #231f20;" width="80" class="tc noborder-left">Années</td>
                                <?php for ($i = 10; $i < 20; $i++) : ?>
                                    <td style="border: 1px solid #231f20;">
                                        <span class="editable"><?= isset($this->lEcheances[$i]) ? $this->lEcheances[$i]['annee'] : '..........' ?></span>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #231f20;" colspan="2">Montant annuel des intérêts exigibles</td>
                                <?php for ($i = 10; $i < 20; $i++) : ?>
                                    <td style="border: 1px solid #231f20;">
                                        <span class="editable"><?= isset($this->lEcheances[$i]) ? $this->ficelle->formatNumber($this->lEcheances[$i]['interets'] / 100) : '&nbsp;' ?></span>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #231f20;" colspan="2">Montant annuel du principal remboursé</td>
                                <?php for ($i = 10; $i <= 20; $i++) : ?>
                                    <td style="border: 1px solid #231f20;">
                                        <span class="editable"><?= isset($this->lEcheances[$i]) ? $this->ficelle->formatNumber($this->lEcheances[$i]['capital'] / 100) : '&nbsp;' ?></span>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <div class="doc-foot">
                <div class="signiture">
                    <span>A</span><span class="city editable">PARIS</span><span>, le </span>
                    <span class="date editable"><?= date('d/m/Y', strtotime($this->oLoans->added)) ?></span>
                    <em>Signature :</em>
                    <div class="footLogo"></div>
                </div>
                <div class="logoministere"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>