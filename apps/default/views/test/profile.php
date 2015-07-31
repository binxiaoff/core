<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
			<div class="shell">
				
				<div class="section-c tabs-c">
					<nav class="tabs-nav">
						<ul>
							<li class="active"><a href="#">Informations personnelles</a></li>
							<li><a href="#">Informations bancaires</a></li>
							<li><a href="#">Gestion de ma sécurité</a></li>
							<li><a href="#">Historique transaction</a></li>
						</ul>
					</nav>

					<div class="tabs">

						<div class="tab">
							<form action="#" methdo="post">
								<h2>Editer mes informations personnelles</h2>
								<p>Sed imperdiet magna non est pellentesque, quis semper turpis tincidunt. Proin viverra eros nisl, id volutpat dolor auctor quis. Aenean non sodales nulla, et pretium leo. Praesent consectetur tellus at condimentum dapibus. Nunc volutpat ligula nibh, in cursus lorem porttitor sed. Fusce gravida iaculis pellentesque. Suspendisse fermentum erat non velit volutpat auctor.</p>
								
								<div class="form-choose fixed">
									<span class="title">Vous êtes</span>
									<div class="radio-holder">
										<label for="particuliers">Particuliers <i class="icon-help tooltip-anchor" data-placement="right" title="Sed imperdiet magna non est pellentesque, quis semper turpis tincidunt. Proin viverra eros nisl, id volutpat dolor auctor quis. Aenean non sodales nulla, et pretium leo."></i></label>
										<input type="radio" class="custom-input" name="radio1" id="particuliers" checked="checked">
									</div><!-- /.radio-holder -->

									<div class="radio-holder">
										<label for="societe">Société <i class="icon-help tooltip-anchor" data-placement="right" title="Sed imperdiet magna non est pellentesque, quis semper turpis tincidunt. Proin viverra eros nisl, id volutpat dolor auctor quis. Aenean non sodales nulla, et pretium leo."></i></label>
										<input type="radio" class="custom-input" name="radio1" id="societe">
									</div><!-- /.radio-holder -->
								</div><!-- /.form-choose -->
								<div class="row">
									<select name="" id="jour" class="custom-select field-med">
										<option value="">Jour</option>
										<option value="">Jour 1</option>
										<option value="">Jour 2</option>
										<option value="">Jour 3</option>
										<option value="">Jour 4</option>
									</select>
									<select name="" id="mois" class="custom-select field-med">
										<option value="">Mois</option>
										<option value="">Mois 1</option>
										<option value="">Mois 2</option>
										<option value="">Mois 3</option>
										<option value="">Mois 4</option>
									</select>
									<select name="" id="année" class="custom-select field-med">
										<option value="">Année</option>
										<option value="">1979 1</option>
										<option value="">1980 2</option>
										<option value="">1981 3</option>
										<option value="">1982 4</option>
									</select>
								</div><!-- /.row -->
								<div class="row">
									<select name="" id="nom" class="custom-select field-xxhalf-large">
										<option value="">Nom</option>
										<option value="">Nam euismod aliquet augue quis tempor. </option>
										<option value="">Mauris id dignissim lorem. </option>
										<option value="">Vivamus mattis lacus mollis,</option>
									</select>
								</div><!-- /.row -->

								<div class="row">
									<div class="cb-holder">
										<label for="mon-addresse">Mon adresse fiscale est identique à mon adresse de correspondance</label>
										<input type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address-correspondance" checked="checked">
									</div><!-- /.cb-holder -->
								</div><!-- /.row -->

								<div class="row">
									<input type="text" id="Prénom" title="Prénom" value="Prénom" class="field field-large required" data-validators="Presence">
									<input type="text" id="téléphone" title="Téléphone" value="Téléphone" class="field field-large required" data-validators="Presence">
								</div><!-- /.row -->

								<div class="row">
									<input type="text" id="adresse" title="Adresse" value="Adresse" class="field field-large required" data-validators="Presence">
								</div><!-- /.row -->

								<div class="row">
									<textarea name="" id="message" title="Message*" class="field field-mega required" data-validators="Presence" cols="30" rows="10">Message*</textarea>
								</div><!-- /.row -->

								<div class="row row-cols row-captcha">
									<div class="col">
										<div class="captcha-holder">
											<img src="css/images/captcha.jpg" alt="">
											<a href="#" class="refresh"><i class="icon-refresh"></i></a>
										</div><!-- /.captcha-holder -->
										<input type="text" name="" title="Captcha" value="Captcha" id="captcha" class="field" data-validators="Presence">
									</div><!-- /.col -->
									<div class="col">
										<div class="coupon">
											<input type="text" name="" class="field field-medium">
											<button class="btn btn-small btn-small-font">Parcourir</button>
										</div><!-- /.coupon -->
									</div><!-- /.col -->
								</div><!-- /.row row-captcha -->
								<button class="btn btn-mega alone-btn">valider les modifications<i class="icon-arrow-next"></i></button>
							</form>
						</div><!-- /.tab -->

						<div class="tab">
							<h2>Transférer des fonds</h2>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Laboriosam, libero optio provident dolorum consequatur placeat natus excepturi facilis amet commodi deserunt nobis quasi beatae nemo eaque omnis quos non maiores.</p>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eos, quos, reiciendis eius aut mollitia quod aliquam nam porro natus rem!</p>
						</div><!-- /.tab -->

						<div class="tab">
							<h2>Transférer des fonds</h2>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Laboriosam, libero optio provident dolorum consequatur placeat natus excepturi facilis amet commodi deserunt nobis quasi beatae nemo eaque omnis quos non maiores.</p>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eos, quos, reiciendis eius aut mollitia quod aliquam nam porro natus rem!</p>
						</div><!-- /.tab -->

						<div class="tab">
							<h2>Historique transactions</h2>
							<p>Sed imperdiet magna non est pellentesque, quis semper turpis tincidunt. Proin viverra eros nisl, id volutpat dolor auctor quis. Aenean non sodales nulla, et pretium leo. Praesent consectetur tellus at condimentum dapibus. Nunc volutpat ligula nibh, in cursus lorem porttitor sed. Fusce gravida iaculis pellentesque. Suspendisse fermentum erat non velit volutpat auctor.</p>
							<div class="table-filter clearfix">
								<p class="left">Historique des projets financés depuis le compte Unilend n°2158795</p>
								<div class="select-box right">
									<select name="" id="aneee" class="custom-select field-mini">
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
									</select>
								</div>
							</div>
							<table class="table transactions-history">
								<tr>
									<th width="230">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-person tooltip-anchor"></i></div>
									</th>
									<th width="82">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-clock tooltip-anchor"></i></div>
									</th>
									<th width="80">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-gauge tooltip-anchor"></i></div>
									</th>
									<th width="76">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-bank tooltip-anchor"></i></div>
									</th>
									<th width="126">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-calendar tooltip-anchor"></i></div>
									</th>
									<th width="51">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-graph tooltip-anchor"></i></div>
									</th>
									<th width="124">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-euro tooltip-anchor"></i></div>
									</th>
									<th width="50">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-empty-folder tooltip-anchor"></i></div>
									</th>
									<th width="131">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-arrow-next tooltip-anchor"></i></div>
									</th>
								</tr>

								<tr>
									<td>
										<div class="description">
											<h5>Equinoa, Web Agency </h5>
											<h6>Paris, 75014</h6>
										</div>
									</td>
									<td>25-12-13</td>
									<td>A</td>
									<td>1 000€</td>
									<td>25-02-14</td>
									<td>10%</td>
									<td>3€/mois</td>
									<td><a class="tooltip-anchor icon-pdf" href="#"></a></td>
									<td><a href="#" class="btn btn-info btn-small">détails</a></td>
								</tr>

								<tr>
									<td>
										<div class="description">
											<h5>Equinoa, Web Agency </h5>
											<h6>Paris, 75014</h6>
										</div>
									</td>
									<td>25-12-13</td>
									<td>A</td>
									<td>1 000€</td>
									<td>25-02-14</td>
									<td>10%</td>
									<td>3€/mois</td>
									<td><a class="tooltip-anchor icon-pdf" href="#"></a></td>
									<td><a href="#" class="btn btn-info btn-small">détails</a></td>
								</tr>

								<tr>
									<td>
										<div class="description">
											<h5>Equinoa, Web Agency </h5>
											<h6>Paris, 75014</h6>
										</div>
									</td>
									<td>25-12-13</td>
									<td>A</td>
									<td>1 000€</td>
									<td>25-02-14</td>
									<td>10%</td>
									<td>3€/mois</td>
									<td><a class="tooltip-anchor icon-pdf" href="#"></a></td>
									<td><a href="#" class="btn btn-info btn-small">détails</a></td>
								</tr>
								
							</table><!-- /.table -->

							<div class="table-filter clearfix">
								<p class="left">Alimentation du compte Unilend n°2158795</p>
								<div class="select-box right">
									<select name="" id="aneee" class="custom-select field-mini">
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
										<option value="">Année 2013</option>
									</select>
								</div>
							</div>

							<table class="table transactions-history">
								<tr>
									<th class="narrow-th" width="210">
										Transaction
									</th>
									<th width="120">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-clock tooltip-anchor"></i></div>
									</th>
									<th width="500">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-bank tooltip-anchor"></i></div>
									</th>
									<th width="120">
										<div class="th-wrap"><i title="Capacité de rembourssement" class="icon-empty-folder tooltip-anchor"></i></div>
									</th>
								</tr>

								<tr>
									<td>Versement initial</td>
									<td>25-12-13</td>
									<td>1 000€</td>
									<td><a class="tooltip-anchor icon-pdf" href="#"></a></td>
								</tr>
								<tr>
									<td>Retrait</td>
									<td>25-12-13</td>
									<td>500€</td>
									<td><a class="tooltip-anchor icon-pdf" href="#"></a></td>
								</tr>
								
							</table><!-- /.table -->

						</div><!-- /.tab -->

					</div>

				</div><!-- /.tabs-c -->

			</div>
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->