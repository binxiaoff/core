<script type="text/javascript">
    $(function() {
        $("#creation_date_etape2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 100) ?>:<?= (date('Y')) ?>'
        });

        $("#date_naissance_gerant").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 100) ?>:<?= (date('Y')) ?>'
        });

        $('#same_address_etape2').click(function () {
            if ($(this).prop('checked')) {
                $('.same_adresse').hide('slow');
            }
            else {
                $('.same_adresse').show('slow');
            }
        });

        $('.phone-call').on('click', function (e) {
            e.preventDefault()
            var $button = $(this)
            var $field = $button.siblings('input[type=text]')
            var number = $field.val().replace(/[ .\-]/g, '')
            window.location = 'tel:' + 0 + number
        })

        // STREETVIEW
        // Avoid re-initialisation
        var streetviewOpen = false;

        // 1. Prepare Streetview container
        var initStreetview = function(street, postcode, city) {
            if (!streetviewOpen) {
                streetviewOpen = true;
                var streetview = document.getElementById('streetview');
                var container = document.getElementById('streetview_container');
                var offsetSpace = 50;
                var aspectRatio = 0.5625;
                var animationTime = 200;
                var screenHeight = window.innerHeight;
                var availableHeight = screenHeight - offsetSpace;
                var streetviewContainerWidth = $(container).width();
                var containerRatioHeight = streetviewContainerWidth * aspectRatio;
                $(container).show();
                // Scroll window to streetview
                $('html, body').animate({scrollTop: $(container).offset().top - offsetSpace}, animationTime, function () {
                    var streetviewAspectRatio = '';
                    if (containerRatioHeight > availableHeight) {
                        streetviewAspectRatio = (availableHeight / streetviewContainerWidth) * 100 + '%';
                    } else {
                        streetviewAspectRatio = aspectRatio * 100 + '%';
                    }
                    $(container).animate({'padding-bottom': streetviewAspectRatio}, animationTime, function () {
                        resolveAddress(street, postcode, city);
                        initAutocomplete();
                    });
                });
            } else {
                resolveAddress(street, postcode, city);
                initAutocomplete();
            }
        }

        // 2. Check if Address is valid
        var resolveAddress = function(street, postcode, city) {
            var address = [street, postcode, city];
            address = address.join(", ");
            console.log('Resolving address... ' + address)

            // Init Geocoder
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': address}, function(results, status) {
                if (status === 'OK') {
                    console.log('Address resolved.');
                    console.log('Checking if Streetview is available... ');
                    var location = results[0].geometry.location;
                    resolveStreetview(location)
                } else {
                    console.log('Address is invalid.');
                    streetviewError('address');
                }
            });
        }

        // 3. Check if Streetview is available
        var resolveStreetview = function(location) {
            var streetViewService = new google.maps.StreetViewService();
            var STREETVIEW_MAX_DISTANCE = 30;
            streetViewService.getPanoramaByLocation(location, STREETVIEW_MAX_DISTANCE, function (streetViewPanoramaData, status) {
                if (status === google.maps.StreetViewStatus.OK) {
                    console.log('Streetview is available');
                    displayStreetView(location);
                } else {
                    console.log('Streetview is not available. Please try a different address');
                    streetviewError('streetview');
                }
            });
        }

        // 4. Display Streetview
        var displayStreetView = function(location) {
            new google.maps.StreetViewPanorama(document.getElementById('streetview'), {
                position: location,
                pov: {heading: 165, pitch: 0},
                zoom: 1
            });
        }

        // 5. Streetview Errors
        var streetviewError = function(error) {
            var $el = $('#popup-streetview-error')
            if (error === 'address') {
                $el.find('.title').text('Invalid Address')
            } else if (error === 'streetview') {
                $el.find('.title').text('Streetview not available')
            }
            $.colorbox({inline:true, href:"#popup-streetview-error"});
        }

        // AUTOCOMPLETE
        var googleAutoComplete;
        var initAutocomplete = function() {
            googleAutoComplete = new google.maps.places.Autocomplete((document.getElementById('google-autocomplete')),{types: ['geocode']});
            googleAutoComplete.addListener('place_changed', initAutocompleteStreetview);
        }
        var getAddressFromFields = function() {
            var street = $('#address_etape2').val();
            var city = $('#ville_etape2').val();
            var postcode = $('#postal_etape2').val();
            var address = [street, postcode, city];
            return address
        }
        var initAutocompleteStreetview = function() {
            // Get the place details from the autocomplete object.
            var $place = $('<div id="autocomplete-address" />');
            $place.html(googleAutoComplete.getPlace().adr_address)
            var street = $place.find('.street-address').text();
            var postcode = $place.find('.postal-code').text();
            var city = $place.find('.locality').text();
            initStreetview(street, postcode, city);
        }

        // Open streetview
        $("#etape2").on('click', '#streetview_open', function(e) {
            e.preventDefault();
            var address = getAddressFromFields();
            initStreetview(address);
            $('#google-autocomplete').val(address);
        });
        // Close streetview
        $("#etape2").on('click', '#streetview_close', function(e) {
            e.preventDefault();
            var $container = $(this).closest('#streetview_container')
            $container.animate({'padding-bottom': 0}, 200, function(){
                $container.hide();
                streetviewOpen = false;
            });
        });
    });
</script>
<style>
    #streetview_container {
        width: 100%;
        height: 0;
        position: relative;
        background: #ECECEC;
        display: none;
        margin-bottom: 30px;
    }
    #streetview_container .controls {
        position: absolute;
        box-sizing: border-box;
        top: 0;
        left: 0;
        width: 100%;
        height: 40px;
        padding: 5px 10px;
    }
    #streetview_container .controls .btn {
        float: right;
        width: 30px;
        height: 30px;
        text-align: center;
    }
    #streetview_container .iframe {
        position: absolute;
        top: 40px;
        left: 10px;
        right: 10px;
        bottom: 10px;
    }
    #autocomplete_container {
        width: 290px;
        float: left;
    }
    #google-autocomplete {
        padding: 5px 10px;
        width: 100%;
        height: 20px;
        background: #494c4a;
        color: #fff;
        border: 0;
    }
</style>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAJuJqNo7NITU0E8B7mMVlR_nq3UvABu8U&libraries=places"></script>

<a class="tab_title" id="section-contact-details" href="#section-contact-details">2. Coordonnées</a>
<div class="tab_content" id="etape2">
    <div id="streetview_container">
        <div class="controls">
            <div id="autocomplete_container">
                <input type="text" id="google-autocomplete">
            </div>
            <button id="streetview_close" class="btn">X</button>
        </div>
        <div id="streetview" class="iframe"></div>
    </div>
    <form method="post" id="dossier_etape2" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" onsubmit="valid_etape2(<?= $this->projects->id_project ?>); return false;">
        <table class="form" style="width: 100%;">
            <tr>
                <th style="width: 10%;"><label for="raison_sociale_etape2">Raison sociale</label></th>
                <td style="width: 45%;">
                    <input type="text" name="raison_sociale_etape2" id="raison_sociale_etape2" class="input_large" value="<?= $this->companies->name ?>">
                    <a class="btn-small btn_link" target="_blank" href="https://www.google.fr/#q=<?= urlencode($this->companies->name) ?>+site:bolden.fr+OR+site:credit.fr+OR+site:lendix.com+OR+site:lendopolis.com+OR+site:lookandfin.com+OR+site:pretstory.fr+OR+site:pretup.fr+OR+site:prexem.com+OR+site:raizers.com+OR+site:crowdlending.fr+OR+site:tributile.fr+OR+site:lesentrepreteurs.com" style="margin-left: 5px">Rechercher sur Google</a>
                </td>
                <th style="width: 15%;"><label for="forme_juridique_etape2">Forme juridique</label></th>
                <td style="width: 30%;"><input type="text" name="forme_juridique_etape2" id="forme_juridique_etape2" class="input_large" value="<?= $this->companies->forme ?>"></td>
            </tr>
            <tr>
                <th><label for="capital_social_etape2">Capital social</label></th>
                <td><input type="text" name="capital_social_etape2" id="capital_social_etape2" class="input_large" value="<?= empty($this->companies->capital) ? '' : $this->ficelle->formatNumber($this->companies->capital, 0) ?>"></td>
                <th><label for="creation_date_etape2">Date de création</label></th>
                <td><input readonly="readonly" type="text" name="creation_date_etape2" id="creation_date_etape2" class="input_dp" value="<?= empty($this->companies->date_creation) || $this->companies->date_creation === '0000-00-00' ? '' : $this->dates->formatDate($this->companies->date_creation, 'd/m/Y') ?>"></td>
            </tr>
            <tr>
                <th colspan="4" style="text-align:left;"><br>Coordonnées du siège social</th>
            </tr>
            <tr>
                <th><label for="address_etape2">Adresse</label></th>
                <td>
                    <input type="text" name="address_etape2" id="address_etape2" class="input_large" value="<?= $this->companies->adresse1 ?>">
                    <?php if (false === empty($this->companies->adresse1)) : ?>
                        <a id="streetview_open" class="btn-small btn_link">Voir Streetview</a>
                        <div style="display: none;">
                            <div id="popup-streetview-error" style="width: 300px; text-align: center">
                                <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn">
                                    <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/>
                                </a>
                                <div id="popup-content">
                                    <h2 style="padding-top: 30px" class="title">Erreur</h2>
                                    <p style="margin: 0; line-height: 18px;">Veuillez saisir une nouvelle adresse, ville ou code postale et réessayez.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </td>
                <th><label for="phone_etape2">Téléphone</label></th>
                <td>
                    <input type="text" name="phone_etape2" id="phone_etape2" class="input_moy" value="<?= $this->companies->phone ?>">
                    <a class="btn-small btn_link phone-call">Appeler</a>
                </td>
            </tr>
            <tr>
                <th><label for="postal_etape2">Code postal</label></th>
                <td><input type="text" name="postal_etape2" id="postal_etape2" class="input_court" value="<?= $this->companies->zip ?>"></td>
                <th><label for="latitude">Latitude</label></th>
                <td><input type="text" name="latitude" id="latitude" class="input_court" value="<?php if (false === empty($this->latitude)) : ?><?= $this->latitude ?><?php endif; ?>"> N</td>
            </tr>
            <tr>
                <th><label for="ville_etape2">Ville</label></th>
                <td><input type="text" name="ville_etape2" id="ville_etape2" class="input_large" value="<?= $this->companies->city ?>"></td>
                <th><label for="longitude">Longitude</label></th>
                <td><input type="text" name="longitude" id="longitude" class="input_court" value="<?php if (false === empty($this->longitude)) : ?><?= $this->longitude ?><?php endif; ?>"> E</td>
            </tr>
            <tr>
                <td colspan="4" style="padding-top: 15px">
                    <input<?= ($this->companies->status_adresse_correspondance == 1 ? ' checked' : '') ?> type="checkbox" name="same_address_etape2" id="same_address_etape2">
                    <label for="same_address_etape2">L'adresse de correspondance est la même que l'adresse du siège social </label>
                </td>
            </tr>
            <tr<?= ($this->companies->status_adresse_correspondance == 0 ? '' : ' style="display:none;"') ?> class="same_adresse">
                <th colspan="4" style="text-align:left;"><br>Coordonnées de l'adresse de correspondance</th>
            </tr>
            <tr<?= ($this->companies->status_adresse_correspondance == 0 ? '' : ' style="display:none;"') ?> class="same_adresse">
                <th><label for="adresse_correspondance_etape2">Adresse</label></th>
                <td><input type="text" name="adresse_correspondance_etape2" id="adresse_correspondance_etape2" class="input_large" value="<?= $this->clients_adresses->adresse1 ?>"></td>
                <th><label for="city_correspondance_etape2">Ville</label></th>
                <td><input type="text" name="city_correspondance_etape2" id="city_correspondance_etape2" class="input_large" value="<?= $this->clients_adresses->ville ?>"></td>
            </tr>
            <tr<?= ($this->companies->status_adresse_correspondance == 0 ? '' : ' style="display:none;"') ?>
                class="same_adresse">
                <th><label for="zip_correspondance_etape2">Code postal</label></th>
                <td><input type="text" name="zip_correspondance_etape2" id="zip_correspondance_etape2" class="input_court" value="<?= $this->clients_adresses->cp ?>"></td>
                <th><label for="phone_correspondance_etape2">Téléphone</label></th>
                <td>
                    <input type="text" name="phone_correspondance_etape2" id="phone_correspondance_etape2" class="input_moy" value="<?= $this->clients_adresses->telephone ?>">
                    <a class="btn-small btn_link phone-call">Appeler</a>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 15px;"><h2>Identification du dirigeant</h2></td>
                <?php if ($this->projectEntity->getIdClientSubmitter() && $this->projectEntity->getIdClientSubmitter()->getIdClient()) : ?>
                    <td colspan="2" style="padding-top: 15px; background-color: #ececec;">
                        <h2>Déposant</h2>
                    </td>
                <?php elseif ($this->hasAdvisor) : ?>
                    <td colspan="2" style="padding-top: 15px; background-color: #ececec;">
                        <h2>Prescripteur</h2>
                    </td>
                <?php else : ?>
                    <td colspan="2"></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th><label for="fonction_etape2">Fonction</label></th>
                <td><input type="text" name="fonction_etape2" id="fonction_etape2" class="input_large" value="<?= $this->clients->fonction ?>"></td>
                <?php if ($this->projectEntity->getIdClientSubmitter() && $this->projectEntity->getIdClientSubmitter()->getIdClient()) : ?>
                    <th style="background-color: #ececec;">Nom</th>
                    <td style="background-color: #ececec;"><?= $this->projectEntity->getIdClientSubmitter()->getPrenom() ?> <?= $this->projectEntity->getIdClientSubmitter()->getNom() ?></td>
                <?php elseif ($this->hasAdvisor) : ?>
                    <th style="background-color: #ececec;">Nom</th>
                    <td style="background-color: #ececec;"><?= $this->clients_prescripteurs->civilite ?> <?= $this->clients_prescripteurs->prenom ?> <?= $this->clients_prescripteurs->nom ?></td>
                <?php else : ?>
                    <td colspan="2"></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th>Civilité</th>
                <td>
                    <input <?= $this->clients->civilite == 'Mme' ? 'checked' : '' ?> type="radio" name="civilite_etape2" id="civilite1_etape2" value="Mme">
                    <label for="civilite1_etape2">Madame</label>
                    <input <?= $this->clients->civilite == 'M.' ? 'checked' : '' ?> type="radio" name="civilite_etape2" id="civilite2_etape2" value="M.">
                    <label for="civilite2_etape2">Monsieur</label>
                </td>
                <?php if ($this->projectEntity->getIdClientSubmitter() && $this->projectEntity->getIdClientSubmitter()->getIdClient()) : ?>
                    <th style="background-color: #ececec;">Téléphone</th>
                    <td style="background-color: #ececec;"><a href="tel:0<?= str_replace([' ', '.', '-'], '', $this->projectEntity->getIdClientSubmitter()->getTelephone()) ?>"><?= $this->projectEntity->getIdClientSubmitter()->getTelephone() ?></a></td>
                <?php elseif ($this->hasAdvisor) : ?>
                    <th style="background-color: #ececec;">Téléphone</th>
                    <td style="background-color: #ececec;"><a href="tel:0<?= str_replace([' ', '.', '-'], '', $this->clients_prescripteurs->telephone) ?>"><?= $this->clients_prescripteurs->telephone ?></a></td>
                <?php else : ?>
                    <td colspan="2"></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th><label for="prenom_etape2">Prénom</label></th>
                <td><input type="text" name="prenom_etape2" id="prenom_etape2" class="input_large" value="<?= $this->clients->prenom ?>"></td>
                <?php if ($this->projectEntity->getIdClientSubmitter() && $this->projectEntity->getIdClientSubmitter()->getIdClient()) : ?>
                    <th style="background-color: #ececec;">Email</th>
                    <td style="background-color: #ececec;"><a href="mailto:<?= $this->projectEntity->getIdClientSubmitter()->getEmail() ?>"><?= $this->projectEntity->getIdClientSubmitter()->getEmail() ?></a></td>
                <?php elseif ($this->hasAdvisor) : ?>
                    <th style="background-color: #ececec;">Email</th>
                    <td style="background-color: #ececec;"><a href="mailto:<?= $this->clients_prescripteurs->email ?>"><?= $this->clients_prescripteurs->email ?></a></td>
                <?php else : ?>
                    <td colspan="2"></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th><label for="nom_etape2">Nom</label></th>
                <td><input type="text" name="nom_etape2" id="nom_etape2" class="input_large" value="<?= $this->clients->nom ?>"></td>
                <?php if ($this->projectEntity->getIdCompanySubmitter() && $this->projectEntity->getIdCompanySubmitter()->getIdCompany()) : ?>
                    <th style="background-color: #ececec;">Agence</th>
                    <td style="background-color: #ececec;"><?= $this->projectEntity->getIdCompanySubmitter()->getName() ?></td>
                <?php elseif ($this->hasAdvisor) : ?>
                    <th style="background-color: #ececec;">Raison sociale</th>
                    <td style="background-color: #ececec;"><?= $this->companies_prescripteurs->name ?><?= empty($this->companies_prescripteurs->siren) ? '' : ' (' . $this->companies_prescripteurs->siren ?></td>
                <?php else : ?>
                    <td colspan="2"></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th><label for="phone_new_etape2">Téléphone</label></th>
                <td>
                    <input type="text" name="phone_new_etape2" id="phone_new_etape2" class="input_moy" value="<?= $this->clients->telephone ?>">
                    <a class="btn-small btn_link phone-call">Appeler</a>
                </td>
                <?php if ($this->projectEntity->getIdCompanySubmitter() && $this->projectEntity->getIdCompanySubmitter()->getIdCompany()) : ?>
                    <th style="background-color: #ececec;">Téléphone agence</th>
                    <td style="background-color: #ececec;"><a href="tel:0<?= str_replace([' ', '.', '-'], '', $this->projectEntity->getIdCompanySubmitter()->getPhone()) ?>"><?= $this->projectEntity->getIdCompanySubmitter()->getPhone() ?></a></td>
                <?php elseif ($this->hasAdvisor) : ?>
                    <td colspan="2" style="background-color: #ececec; color: #c84747;">Un prescripteur est une personne non enregistrée en tant que partenaire qui a fait un dépôt de dossier via le site.</td>
                <?php else : ?>
                    <td colspan="2"></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th><label for="email_etape2">Email</label></th>
                <td colspan="3"><input type="email" name="email_etape2" id="email_etape2" class="input_large" value="<?= $this->clients->email ?>"></td>
            </tr>
            <tr>
                <th><label for="date_naissance_gerant">Date de naissance</label></th>
                <td colspan="3"><input type="text" name="date_naissance_gerant" id="date_naissance_gerant" class="input_dp" value="<?= empty($this->clients->naissance) || $this->clients->naissance === '0000-00-00' ? '' : $this->dates->formatDate($this->clients->naissance, 'd/m/Y') ?>"></td>
            </tr>
        </table>
        <div id="spinner_etape2" class="spinner_etape"></div>
        <div id="error_etape2" class="error_etape"></div>
        <div id="valid_etape2" class="valid_etape">Données sauvegardées</div>
        <div class="btnDroite">
            <button type="submit" class="btn-primary">Sauvegarder</button>
        </div>
    </form>
</div>
