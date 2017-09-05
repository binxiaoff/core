$(function(){
    // Page helpers are initialised in app.js and can be called quickly using their name
    App.initHelpers(['validation', 'datepicker', 'datatables', 'table-tools', 'notify']);

    // Trigger element for all allerts
    var alertTrigger = $('#js-alerts-trigger')
    var modalTrigger = $('#js-modal-trigger')

    // Dynamic modal for Campaign Edit
    $('#modal-parrainage-list').on('show.bs.modal', function (e) {
        if (e.namespace === 'bs.modal') {
            var $modal = $(this)
            var $button = $(e.relatedTarget)
            var $row = $button.closest('tr')
            var $table = $row.closest('table')
            var campaignId = $button.data('campaign-id')

            var startIndex = $table.find('th.start').index()
            var endIndex = $table.find('th.end').index()
            var validityIndex = $table.find('th.validity').index()
            var amountSponseeIndex = $table.find('th.amount-sponsee').index()
            var amountSponsorIndex = $table.find('th.amount-sponsor').index()
            var maxSponsee = $table.find('th.max-sponsee').index()


            $modal.find('.block-title').text('Modifier la campagne')
            $modal.find('input[name="start"]').val($row.find('td').eq(startIndex).text())
            $modal.find('input[name="end"]').val($row.find('td').eq(endIndex).text())
            $modal.find('input[name="validity_days"]').val(parseFloat($row.find('td').eq(validityIndex).text()))
            $modal.find('input[name="amount_sponsee"]').val(parseFloat($row.find('td').eq(amountSponseeIndex).text()))
            $modal.find('input[name="amount_sponsor"]').val(parseFloat($row.find('td').eq(amountSponsorIndex).text()))
            $modal.find('input[name="max_number_sponsee"]').val($row.find('td').eq(maxSponsee).text())
            $modal.find('input[name="id_campaign"]').val(campaignId)
        }
    })

    // Blacklist Search Results
    $('#parrainage-blacklist-search').submit(function(e){
        e.preventDefault()

        var $form = $(this)
        var $modal = $('#modal-blacklist-search')

        if (!$form.is('.has-errors')) {
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $modal.find('.client-id').text(response.client.idClient)
                        $modal.find('input[name="id_client"]').val(response.client.idClient)
                        $modal.find('.nom').text(response.client.lastName)
                        $modal.find('.prenom').text(response.client.firstName)

                        modalTrigger.attr('data-target', '#' + $modal.attr('id')).trigger('click')
                    } else {
                        $.each( response.error, function(i, val){
                            alertTrigger.data('notify-message', val).trigger('click')
                        });
                    }
                },
                error: function () {
                    alertTrigger.data('notify-message', 'Server is not responding').trigger('click')
                }
            })
        }
    })

    // Adjust Prime Search
    $('#parrainage-adjust-prime-search').submit(function(e){
        e.preventDefault()

        var $form = $(this)
        var modal = '#modal-prime-release'
        var $table = $('#parrainage-adjust-prime-table')

        if (!$form.is('.has-errors')) {
            $.ajax({
                url : $form.attr('action'),
                type : 'post',
                dataType : 'json',
                data: $form.serialize(),
                success : function(response){

                    if (response.success) {
                        var html = '';

                        for (i = 0; i < response.sponsorships.length; i++) {
                            var s = response.sponsorships[i];
                            var sponsorReceived = 'versée'
                            var sponseeReceived = 'versée'
                            var type = ''

                            if ("false" == s.sponsor_reward_paid) {
                                type = $table.data('type-sponsor')
                                if ("true" == s.sponsee_reward_paid) {
                                    sponsorReceived = '<button class="btn btn-primary btn-xs" data-toggle="modal" data-target="' + modal + '" data-type="' + type + '" data-sponsorship-id="' + s.id_sponsorship + '">Débloquer</button>'
                                } else {
                                    sponsorReceived = 'pas encore possible'
                                }
                            }

                            if ("false" == s.sponsee_reward_paid) {
                                type = $table.data('type-sponsee')
                                sponseeReceived = '<button class="btn btn-primary btn-xs" data-toggle="modal" data-target="' + modal + '" data-type="' + type + '" data-sponsorship-id="' + s.id_sponsorship + '">Débloquer</button>'
                            }

                            html += '<tr>' +
                                '<td class="sponsee-id">' + s.id_client_sponsee + '</td> ' +
                                '<td class="sponsee-first-name">' + s.sponsee_first_name + '</td> ' +
                                '<td class="sponsee-last-name">' + s.sponsee_last_name + '</td>' +
                                '<td class="sponsee-prime">' + sponseeReceived + '</td>' +
                                '<td class="sponsor-id">' + s.id_client_sponsor + '</td>' +
                                '<td class="sponsor-first-name">' + s.sponsor_first_name + '</td> ' +
                                '<td class="sponsor-last-name">' + s.sponsor_last_name + '</td> ' +
                                '<td class="sponsor-prime">' + sponsorReceived + '</td> ' +
                                '</tr>'

                            $table.find('tbody').html(html)
                        }
                        $table.removeClass('hide')
                    } else {
                        $.each( response.error, function(i, val){
                            alertTrigger.data('notify-message', val).trigger('click')
                        });
                    }
                },
                error: function() {
                    alert('Server error :(')
                }
            });
        }
    })

    // Dynamic modal for Adjust Prime
    $('#modal-prime-release').on('show.bs.modal', function (e) {
        if (e.namespace === 'bs.modal') {
            var $modal = $(this)
            var $button = $(e.relatedTarget)
            var type = $button.data('type')
            var idSponsorship = $button.data('sponsorship-id')

            $modal.find('input[name="type_reward"]').val(type)
            $modal.find('input[name="id_sponsorship"]').val(idSponsorship)
        }
    })

    // Adjust Prime Search
    $('#parrainage-establish-link-search').submit(function(e){
        e.preventDefault()

        var $form = $(this)
        var $sponsorTable = $('#link-sponsor-table')
        var $sponseeTable = $('#link-sponsee-table')
        var $submit = $('#link-submit')
        var $container = $('#establish-link-results')

        if (!$form.is('.has-errors')) {
            $.ajax({
                url : $form.attr('action'),
                type : 'post',
                dataType : 'json',
                data: $form.serialize(),
                success: function(response){
                    if (response.success) {

                        var sponsorshipData = response.sponsorshipData

                        var welcomeOffer = ''
                        if (sponsorshipData.sponseeHasReceivedWelcomeOffer) {
                            welcomeOffer += '<div class="alert alert-info"><p>Filleul a reçu l\'offre de bienvenue et ne recevra donc pas de prime de parrainage</p></div>'
                        }

                        $sponsorTable.find('.sponsor-id').html(sponsorshipData.idClientSponsor)
                        $sponsorTable.find('.sponsor-first-name').text(sponsorshipData.firstNameSponsor)
                        $sponsorTable.find('.sponsor-last-name').text(sponsorshipData.lastNameSponsor)

                        $sponseeTable.find('.sponsee-id').text(sponsorshipData.idClientSponsee)
                        $sponseeTable.find('.sponsee-first-name').text(sponsorshipData.firstNameSponsee)
                        $sponseeTable.find('.sponsee-last-name').text(sponsorshipData.lastNameSponsee)
                        $sponseeTable.find('.sponsee-date-inscription').text(sponsorshipData.subscriptionSponsee)
                        $sponseeTable.find('.sponsee-date-validation').text(sponsorshipData.sponseeValidationDate)
                        $sponseeTable.find('.sponsee-welcome-offer').html(welcomeOffer)

                        $submit.data('sponsee-id', sponsorshipData.idClientSponsee).data('sponsor-id', sponsorshipData.idClientSponsor)

                        $container.removeClass('hide')
                    } else {
                        $.each( response.error, function(i, val){
                            alertTrigger.data('notify-message', val).trigger('click')
                        });
                    }
                },
                error: function() {
                    alert('Server error :(')
                }
            });
        }
    })

    // Dynamic modal for Adjust Prime
    $('#modal-establish-link').on('show.bs.modal', function (e) {
        if (e.namespace === 'bs.modal') {
            var $modal = $(this)
            var $button = $(e.relatedTarget)

            $modal.find('input[name="id_client_sponsor"]').val($button.data('sponsor-id'))
            $modal.find('input[name="id_client_sponsee"]').val($button.data('sponsee-id'))
        }
    })
})
