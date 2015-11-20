<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>SFF cerfa</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="<?=$this->surl?>/styles/default/declarationContratPret/print.css" type="text/css" media="all" />
</head>
<body>
	<div class="shell">
		<div class="page-break">
			<div class="header">
				<div class="col-left">
					<div class="logocerfa"><a href="#"></a></div>
					<p><small>N° 10142 * 05 <br />N° 50058 # 05</small></p>
				</div><!-- /.col-left -->
				<div class="col-right">
					<?php /*?><div class="buttons">
						<a href="#"><img src="<?=$this->surl?>/styles/default/declarationContratPret/images/btn1.png" alt="" /></a>
						<a href="#"><img src="<?=$this->surl?>/styles/default/declarationContratPret/images/btn2.png" alt="" /></a>
					</div><!-- /.buttons --><?php */?>
					<p class="num">N° 2062</p><!-- /.num -->
					<div class="cl">&nbsp;</div>
				</div><!-- /.col-right -->
				<div class="col-center">
					<div class="logorep"><a href="#" class="logo"></a></div>
				</div><!-- /.col-center -->
				<div class="cl">&nbsp;</div>
			</div><!-- /.header -->
			<div class="document">
				<div class="doc-head">
					<h2>Déclaration de contrat de prêt</h2>
					<p class="subtitle">(Code général des impôts : article 242 ter 3, article 49 B de l’annexe III et article 23 L de l’annexe IV)</p><!-- /.subtitle -->
				</div><!-- /.doc-head -->
				<div class="doc-body">
					<div class="section">
						<h3>I. DÉSIGNATION DU DÉCLARANT (intermédiaire ou à défaut emprunteur)</h3>
						<table width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td style="border: 1px solid #231f20;"><p><label>Nom et prénom ou raison sociale, profession :</label> <span class="editable"><?=strtoupper($this->ficelle->speCharNoAccent(utf8_decode($this->raisonSociale)))?></span></p></td>
							</tr>
							<tr>
								<td style="border: 1px solid #231f20;"><label>Adresse complète :</label> <span class="editable"><?=strtoupper($this->ficelle->speCharNoAccent(utf8_decode($this->adresse)))?></span></td>
							</tr>

						</table>
					</div><!-- /.section -->
					<div class="section">
						<h3>II. RENSEIGNEMENTS CONCERNANT LES CONDITIONS DU PRÊT ET LES PARTIES AU CONTRAT</h3>
						<table width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<th style="border: 1px solid #231f20;" colspan="4">Conditions du prêt</th>
								<th rowspan="2">Noms, prénoms et adresses complètes (y compris code département) des parties <span>ÉCRIVEZ EN CAPITALES</span><small>5</small></th>
							</tr>
							<tr>
								<td style="border: 1px solid #231f20;" class="headings tc" width="42">Date <small>1</small></td>
								<td style="border: 1px solid #231f20;" class="headings tc" width="32">Durée <small>2</small></td>
								<td style="border: 1px solid #231f20;" class="headings tc" width="22">Taux <small>3</small></td>
								<td style="border: 1px solid #231f20;" class="headings tc" width="62">Montant <br />en principal <small>4</small></td>
							</tr>
							<tr>
								<td style="border: 1px solid #231f20;" class="tc"><span class="date"><?=date('d/m/Y',strtotime($this->oLoans->added))?></span></td>
								<td style="border: 1px solid #231f20;" class="tc"><?=($this->projects->period/12)?></td>
								<td style="border: 1px solid #231f20;" class="tc"><small><?=$this->ficelle->formatNumber($this->oLoans->rate, 1)?></small></td>
								<td style="border: 1px solid #231f20;" class="tc"><?=$this->ficelle->formatNumber(($this->oLoans->amount/100), 0)?></td>
								<td style="border: 1px solid #231f20;" class="large nopadding">
									<table cellspacing="0" cellpadding="0" class="title">
										<tr>
											<td style="border: 1px solid #231f20;" width="16" class="noborder-top noborder-left">A</td>
											<td style="border: 1px solid #231f20;" width="290" class="noborder-top">Créancier ou porteur ou prêteur</td>
										</tr>
									</table>
									<table width="100%" cellspacing="0" cellpadding="0" class="inner">
										<tr>
											<td><?=strtoupper($this->ficelle->speCharNoAccent(utf8_decode($this->nomPreteur)))?></td>
										</tr>
										<tr>
											<td><?=strtoupper($this->ficelle->speCharNoAccent(utf8_decode($this->adressePreteur)))?></td>
										</tr>
										<tr>
											<td><?=strtoupper($this->cpPreteur.' '.$this->ficelle->speCharNoAccent(utf8_decode($this->villePreteur)))?></td>
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
											<td><?=strtoupper($this->ficelle->speCharNoAccent(utf8_decode($this->companiesEmp->name)))?></td>
										</tr>
										<tr>
											<td><?=strtoupper($this->ficelle->speCharNoAccent(utf8_decode($this->companiesEmp->adresse1)))?></td>
										</tr>
										<tr>
											<td><?=strtoupper($this->companiesEmp->zip.' '.$this->ficelle->speCharNoAccent(utf8_decode($this->companiesEmp->city)))?></td>
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
								<td style="border: 1px solid #231f20;" width="24" class="noborder-top"><strong>C</strong></td>
								<td style="border: 1px solid #231f20;" width="86" class="noborder-top"><strong>Observations</strong></td>
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
                                <?
								for($i=0;$i<10;$i++)
								{
									?><td style="border: 1px solid #231f20;"><span class="editable"><?=($this->lEcheances[$i]==false?'..........':$this->lEcheances[$i]['annee'])?></span></td><?
								}
								?>


							</tr>
							<tr>
								<td style="border: 1px solid #231f20;" colspan="2">Montant annuel des intérêts exigibles</td>
								<?
								for($i=0;$i<10;$i++)
								{
									?><td style="border: 1px solid #231f20;"><span class="editable"><?=($this->lEcheances[$i]==false?'&nbsp;':$this->ficelle->formatNumber(($this->lEcheances[$i]['interets']/100)))?></span></td><?
								}
								?>

							</tr>
							<tr>
								<td style="border: 1px solid #231f20;" colspan="2">Montant annuel du principal remboursé</td>
								<?
								for($i=0;$i<10;$i++)
								{
									?><td style="border: 1px solid #231f20;"><span class="editable"><?=($this->lEcheances[$i]==false?'&nbsp;':$this->ficelle->formatNumber(($this->lEcheances[$i]['capital']/100)))?></span></td><?
								}
								?>
							</tr>
                            <?
							if(count($this->lEcheances) > 10)
							{
							?>
							<tr>
								<td style="border: 1px solid #231f20;" class="empty">&nbsp;</td>
								<td style="border: 1px solid #231f20;" width="80" class="tc noborder-left">Années</td>
								<?
								for($i=10;$i<20;$i++)
								{
									?><td style="border: 1px solid #231f20;"><span class="editable"><?=($this->lEcheances[$i]==false?'..........':$this->lEcheances[$i]['annee'])?></span></td><?
								}
								?>
							</tr>
							<tr>
								<td style="border: 1px solid #231f20;" colspan="2">Montant annuel des intérêts exigibles</td>
								<?
								for($i=10;$i<20;$i++)
								{
									?><td style="border: 1px solid #231f20;"><span class="editable"><?=($this->lEcheances[$i]==false?'&nbsp;':$this->ficelle->formatNumber(($this->lEcheances[$i]['interets']/100)))?></span></td><?
								}
								?>
							</tr>
							<tr>
								<td style="border: 1px solid #231f20;" colspan="2">Montant annuel du principal remboursé</td>
								<?
								for($i=10;$i<=20;$i++)
								{
									?><td style="border: 1px solid #231f20;"><span class="editable"><?=($this->lEcheances[$i]==false?'&nbsp;':$this->ficelle->formatNumber(($this->lEcheances[$i]['capital']/100)))?></span></td><?
								}
								?>
							</tr>
                            <?
							}
                            ?>
						</table>
					</div><!-- /.section -->
				</div><!-- /.doc-body -->
				<div class="doc-foot">
					<div class="signiture">
						<span>A</span><span class="city editable">PARIS</span><span>, le </span> <span class="date editable"><?=date('d/m/Y',strtotime($this->oLoans->added))?></span> <em>Signature :</em>
                        <div class="footLogo"></div>
					</div><!-- /.signiture -->
					<div class="logoministere"></div>
				</div><!-- /.doc-foot -->
			</div><!-- /.document -->
		</div><!-- /.page-break -->

		<?php /*?><div class="page-break">
			<div class="post">
				<div class="two-cols">
					<div class="col left">
						<h2>NOTICE <br />POUR REMPLIR <br />LA DÉCLARATION</h2>
						<h3>INDICATIONS GÉNÉRALES</h3>
						<p class="indent"><strong><em>La déclaration de contrat de prêt doit être souscrite soit par l’intermédiaire qui intervient dans la conclusion du contrat ou la rédaction de l’acte, soit, en l’absence d’intermédiaire , par le débiteur , pour chaque contrat de prêt d’un montant en principal supérieur à 760 €.</em></strong> </p>
						<p><strong>REMARQUE</strong> : Les renseignements concernant les bons de caisse, de capitalisation et titres assimilés doivent désormais être portés sur la déclaration unique n° 2561 (IFU)</p>
						<ul>
							<li><strong>Pluralité de contrats au nom d’un même débiteur ou créancier :</strong>lorsque plusieurs contrats de prêts d’un montant unitaire inférieur à 760 € sont conclus au cours d’une année au nom d’un même débiteur ou d’un même créancier et que leur total en principal dépasse 760 € , tous les contrats ainsi conclus doivent être déclarés. Dans cette situation la déclaration n° 2062 est souscrite, suivant le cas, par le débiteur ou le créancier au nom duquel l’ensemble des contrats ont été conclus. Il lui suffit alors d’utiliser un seul imprimé (cf. ci-contre : <em>&laquo; Modalités d’utilisation de l’imprimé &raquo;</em>, § II).</li>
							<li>
								<strong>Sont dispensés de déclaration :</strong>
								<ul>
									<li>– les prêts conclus par l’État, les établissements publics, les collectivités locales, les organismes d’HLM, les caisses d’épargne, les caisses de crédit agricole mutuel, les sociétés d’investissement ou assimilées ainsi que les sociétés d’économie mixte soumises au contrôle de la Commission de vérification des comptes des entreprises publiques ;</li>
									<li>– les prêts dans lesquels les banques inscrites et les établis- sements financiers enregistrés par le Conseil national du crédit et du titre ainsi que les banques et établissements de crédit à statut légal spécial interviennent comme prêteurs ou emprunteurs ;</li>
									<li>– les prêts réalisés sous forme de bons de caisse par des banques et d’émissions publiques d’obligations ;</li>
									<li>– les prêts consentis à des particuliers par les compagnies d’assurances, les caisses de sécurité sociale et d’allocations familiales du régime général et des régimes spéciaux ;</li>
									<li>– sous réserve de l’autorisation du d irecteur départemental des fi nances publiques , les prêts consentis à des particuliers par les orga nismes gérant des régimes complémentaires de sécurité sociale et par les autres organismes à but non lucratif visés à l’article 206-5 du Code général des impôts ;</li>
									<li>– les prêts consentis par les vendeurs professionnels en cas de vente à crédit ;</li>
									<li>– les prêts accordés par les employeurs au titre de leur participation à l’effort de construction ;</li>
									<li>– les prêts consentis aux associations diocésaines et œuvres concourant au financement de la construction d’églises ;</li>
									<li>– les prêts consentis aux sociétés de courses de province par le Fonds commun de l’élevage et des courses.</li>
								</ul>
							</li>
						</ul>
					</div><!-- /.col -->
					<div class="col right">
						<h3>MODALITÉS D’UTILISATION <br />DE L’IMPRIMÉ</h3>
						<ol>
							<li><strong>I. Renseignements concernant le déclarant :</strong> à remplir s’il est différent de ceux indiqués cadres A et B</li>
							<li>
								<strong>II. Renseignements concernant les conditions du prêt et les parties au contrat :</strong>
								<ul>
									<li>– col. 1 à 4 : à remplir dans tous les cas ;</li>
									<li>– col. 5 : pour les femmes mariées, mentionner également le nom de jeune fille.</li>
									<li class="indent">Dans le cas où plusieurs créanciers ou débiteurs sont parties au contrat de prêt, indiquer sur une déclaration séparée la fraction du prêt en principal consentie par chaque créan- cier ou dont chaque débiteur est redevable ainsi que, s’il y a lieu, les différents délais de remboursement et taux d’intérêt prévus au contrat pour chaque créancier ou débiteur.</li>
									<li class="indent">Dans le cas visé ci-contre (<em>&laquo; Indications générales, § &bull; Plu- ralité de contrats au nom d’un même débiteur ou créancier &raquo; </em>), mentionner, pour chaque prêt, les noms, prénoms et adresses du ou des créanciers et débiteurs, ainsi que les conditions du prêt</li>
									<li>
								</ul>
							</li>
						</ol>
						<blockquote>Si les cadres A et B ne sont pas suffisants, utiliser l’imprimé n° 2062 ANNEXE.</blockquote>
						<p class="indent"><strong>Cadre C</strong> : indiquer, le cas échéant, les conditions particulières du prêt : clause d’indexation, clause résolutoire, exonération de certains prêts familiaux (BOI 5-I-05-06).</p><!-- /.indent -->
						<p class="indent tidy"><strong>Cadre D</strong> : en cas de pluralité de créanciers ou débiteurs, porter le montant <strong>global</strong> des intérêts et de la fraction du principal qui doivent être versés au cours de chaque année</p><!-- /.indent -->
						<p class="indent tidy">Dans l’hypothèse visée ci-contre (<em>&laquo; Indications géné- rales, § &bull; Pluralité de contrats au nom d’un même débiteur ou créancier &raquo;</em> ), mentionner simplement, pour la totalité des prêts, le montant <strong>global</strong> des intérêts et de la fraction de capital qui doivent être versés chaque année par ou au déclarant.</p><!-- /.indent -->
						<p class="indent">Indiquer, s’il y a lieu, que les intérêts ont été payés d’avance lors de la conclusion du contrat, en précisant le montant de la somme ainsi versée, ou que le principal doit être remboursé au terme du contrat.</p><!-- /.indent -->
						<div class="box">
							<p>Lorsque la déclaration n° 2062 est souscrite par l’intermédiaire, celle-ci est adressée, dès la rédaction du contrat de prêt ou au plus tard le 15 février de l’année suivant celle de la conclusion du prêt, à la direction des finances publiques du lieu du domicile réel ou du principal établissement de la personne physique ou morale déclarante. </p>
							<p>Lorsque le débiteur ou le créancier est tenu de souscrire la déclaration n o 2062, celle-ci est adressée au service des impôts dont il dépend en même temps que la déclaration de ses revenus ou que la déclaration de ses résultats.</p>
							<p>Le défaut de production de la déclaration dans les délais susvisés ou les omissions ou inexactitudes relevées dans la déclaration, à la charge de la personne tenue de déclarer le contrat de prêt (cf. ci-avant : &laquo; Indications générales &raquo;), donnent lieu, le cas échéant, aux sanctions pénales qui frappent les personnes visées à l’article 1743-2 e du code général des impôts.</p>
						</div><!-- /.box -->
					</div><!-- /.col -->
					<div class="cl">&nbsp;</div>
				</div><!-- /.two-cols -->
			</div><!-- /.post -->
		</div><?php */?><!-- /.page-break -->
	</div><!-- /.shell -->
</body>
</html>