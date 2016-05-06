(function ($) {
    var $doc = $(document),
        $win = $(window),
        navPos = 0,
        sidPos = 0,
        footerPos = 0;

    $doc.on('ready', function () {
        navPos = $('.navigation').offset().top;

        var sidebar_exist = false;
        if ($('.sidebar').length) {
            sidPos = $('.sidebar').offset().top;
            footerPos = $('.footer').offset().top;
        }

        // Blink fields
        $doc.on('focusin', '.field, textarea', function () {
            if (this.title == this.value) {
                this.value = '';
            }
        }).on('focusout', '.field, textarea', function () {
            if (this.value == '') {
                $(this).removeClass('populated');
                this.value = this.title;
            } else {
                $(this).addClass('populated');
            }

        }).on('click', 'a.popup-close, .close-btn', function (event) {
            event.preventDefault();
            $.colorbox.close();

        });

        $('.logedin-panel').hover(function () {
            $(this).find('.dd').stop(true, true).show();
        }, function () {
            $(this).find('.dd').hide();
        });

        $('.tooltip-anchor').tooltip();

        $('.custom-select').c2Selectbox();

        $("#datepicker").datepicker({
            inline: true,
            changeMonth: true,
            changeYear: true
        });

        CInput.init();

        Form.initialise({
            selector: 'form'
        });


        var detailTabToggle = $(".detail-nav > th > span"),
            filterMode,
            triEl,
            dirEl;

        if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
            dirEl = $("#direction_mobile");
            triEl = $("#tri_mobile");
        }
        else{
            dirEl = $("#direction");
            triEl = $("#tri");
        }

        function changeDirection(){
            if(dirEl.html() == "1"){
                dirEl.html("2");
            }
            else{
                dirEl.html("1");
            }
        }

        $(".detail-nav span").click(function() {
            if ('rate' == $(this).attr("class")) {
                return false;
            }
            filterMode = triEl.html();
            if((filterMode == $(this).attr("class"))){
                changeDirection();
            }
            else{
                dirEl.html("1");
            }
            filterMode = $(this).attr("class");
            triEl.html(filterMode);

            var targ = $(this).parent().parent().prev();
            var el = $(this).parent().parent().prev().children().children();
            var rate = targ.data('rate');
            var project = targ.data('project');
            var sort = triEl.html();
            var direction = dirEl.html();
            $(".table-body.detail").remove();
            $.get(
                add_url + '/ajax/displayDetail/' + project +'/' + rate + '/',
                {
                    sort: sort,
                    direction: direction
                }
            ).done(function (data) {
                $(data).insertAfter(targ.next());
            });
            targ.addClass("line-focus");
            el.addClass("expand");
        });


        $(".table-body").click(function() {
            var targ = $(this);
            var el = $(this).children().children().children();
            if(el.hasClass("expand")){
                targ.removeClass("line-focus");
                el.removeClass("expand");
                $(".table-body.detail").remove();
            }
            else {
                if($(".table-body.detail").length){
                    $(".line-focus").removeClass("line-focus");
                    $(".expand").removeClass("expand");
                    $(".table-body.detail").remove();
                }
                dirEl.html("1");
                var rate = targ.data('rate');
                var project = targ.data('project');
                var sort = triEl.html();
                var direction = dirEl.html();
                $.get(
                    add_url + '/ajax/displayDetail/' + project +'/' + rate + '/'
                ).done(function (data) {
                    $(data).insertAfter(targ.next());
                });
                targ.addClass("line-focus");
                el.addClass("expand");
            }
        });

        $('.tabs-nav').on('click', 'a', function (event) {
            if (!$(this).parent().is('.active')) {
                var index = $(this).parent().index();
                $('.tabs-nav li').eq(index).addClass('active').siblings('li').removeClass('active');
                $('.tabs .tab').hide().eq(index).stop(true, true).fadeIn();
            }
            event.preventDefault()
        });

        $(".table-body").click(function(){
            $("html, body").animate({
                scrollTop:$(this).offset().top -35
            }, "slow");
            return false
        });

        $('#scrollUp').click(function(){
            $('html, body').animate({
                scrollTop:$("body").offset().top +200
            }, 'slow');
            return false;
        });

        $('.ex-article > h3').on('click', function (event) {
            $(this).next('.article-entry').stop(true, true).slideToggle();
            $(this).find('i').toggleClass('up');
            event.preventDefault()
        });

        $('.esc-btn').on('click', function (event) {
            $(this).parent().fadeOut();
            event.preventDefault()
        });

        $('.post-schedule h2').on('click', function () {
            $(this).next('.body').stop(true, true).slideToggle();
        });

        // ProgressBar
        $(window).load(function () {
            if ($('.progressBar').length) {
                $('.progressBar').each(function () {
                    var per = $(this).data('percent');
                    progress(per, $(this));
                });
            }
            function progress(percent, $element) {
                var progressBarWidth = percent * $element.width() / 100;
                $element.find('div').animate({width: progressBarWidth}, 1200, function () {
                    var leftP = $(this).width();

                    $(this).find('span').html(percent.toString().replace('.', ',') + "%&nbsp;").css('left', leftP);
                })
            }
        });

        $('.euro-field').each(function () {
            $(this).before('<span class="euro-sign">&euro;</span>')
        });

        $('.popup-link').colorbox({
            opacity: 0.5,
            scrolling: false,
            onComplete: function () {
                $('.popup .custom-select').c2Selectbox();

                $('input.file-field').on('change', function () {
                    var $self = $(this),
                        val = $self.val();
                    if (val.length != 0 || val != '') {
                        $self.closest('.uploader').find('input.field').val(val);

                        var idx = $('#rule-selector').val();
                        $('.rules-list li[data-rule="' + idx + '"]').addClass('valid');
                    }
                });

                $('#rule-selector').on('change', function () {
                    var idx = $(this).val();
                    $('.uploader[data-file="' + idx + '"]').slideDown().siblings('.uploader:visible').slideUp();
                });
                Form.initialise({
                    selector: 'form'
                });
            }
        });

        Highcharts.setOptions({
            lang: {
                decimalPoint: ","
            }
        });

        // Graphic Chart
        if ($('.graphic-box').length) {
            var titlePrete = $('#titlePrete').html();
            var titleArgentRemb = $('#titleArgentRemb').html();
            var titleInteretsRecu = $('#titleInteretsRecu').html();

            var leSoldePourcent = parseFloat($('#leSoldePourcent').html());
            var sumBidsEncoursPourcent = parseFloat($('#sumBidsEncoursPourcent').html());
            var sumPretsPourcent = parseFloat($('#sumPretsPourcent').html());
            var sumProblemsPourcent = parseFloat($('#sumProblemsPourcent').html());

            $('#pie-chart').highcharts({
                chart: {
                    backgroundColor: '#fafafa',
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    width: 460
                },
                colors: ['#b10366', '#f7922b', '#40b34f', '#bfa913'],
                title: {
                    text: ''
                },

                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            colors: ['#a1a5a7', '#a1a5a7', '#a1a5a7', '#a1a5a7'],
                            connectorColor: '#ffffff',
                            format: '{point.name}'
                        }
                    }
                },
                series: [{
                    type: 'pie',
                    name: '',
                    data: [
                        [$('#sumPrets').html(), sumPretsPourcent],
                        [$('#sumBidsEncours').html(), sumBidsEncoursPourcent],
                        [$('#leSolde').html(), leSoldePourcent],
                        [$('#sumProblems').html(), sumProblemsPourcent]
                    ]
                }],
                tooltip: {
                    pointFormat: '',
                    enabled: false
                }
            });

            var argentPrete = parseFloat($('#argentPrete').html());
            var argentRemb = parseFloat($('#argentRemb').html());
            var interets = parseFloat($('#interets').html());

            $('#bar-chart').highcharts({

                chart: {
                    backgroundColor: '#fafafa',
                    type: 'bar',
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false
                },
                title: {
                    text: ''
                },
                colors: ['#b10366', '#8462a7', '#b10366'],
                xAxis: {
                    title: {
                        enabled: null,
                        text: null
                    },
                    categories: [titlePrete, titleArgentRemb, titleInteretsRecu],
                },
                legend: {
                    enabled: false
                },
                yAxis: {
                    gridLineColor: 'transparent',
                    labels: {
                        enabled: false
                    },
                    title: {
                        enabled: null,
                        text: null
                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    valueSuffix: ' €',
                },

                plotOptions: {
                    bar: {
                        pointWidth: 35,
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            colors: ['#b10366', '#f7922b', '#40b34f'],
                            format: '{point.name}'
                        }
                    }
                },
                series: [{
                    name: 'Mouvement de ',
                    data: [
                        {
                            color: '#b10366',
                            y: argentPrete
                        },
                        {
                            color: '#8462a7',
                            y: argentRemb
                        },
                        {
                            color: '#ee5396',
                            y: interets
                        }
                    ]
                }]
            });
        }
    });

    $win.on('scroll', function () {
        if ($('body').is('.has-fixed-nav')) {
            var scrolled = $win.scrollTop();
            var newfooterPos = footerPos - 800;

            if (scrolled >= navPos) {
                $('body').addClass('nav-fixed');
            } else {
                $('body').removeClass('nav-fixed');
            }

            if ($('.sidebar').length) {
                if (scrolled >= sidPos - 60) {
                    $('.sidebar').addClass('sidebar-fixed');
                } else {
                    $('.sidebar').removeClass('sidebar-fixed');
                }
            }
        }
    });

    var CInput = {
        init: function ($cnt) {
            var that = this;
            if (!$cnt) {
                $cnt = $doc;
            }
            $('label', $cnt).off('click').on('click', function (event) {
                that.handle($(this), event);
            });

            $('input', $cnt).off('disable enable').on('disable enable', function (event) {
                that.handle($(this).parent().find('label'), event);
            });

            $('input', $cnt).each(function () {
                var $self = $(this);
                if ($self.is('[type="checkbox"]') || $self.is('[type="radio"]')) {
                    if ($self.is(':checked')) {
                        $self.parent().addClass('checked');
                    }
                    if ($self.is(':disabled')) {
                        $self.parent().addClass('disabled');
                    }
                }
            });
        },
        handle: function ($label, event) {
            var $input = $('input#' + $label.attr('for')),
                $parent = $label.parent();

            if ($(event.target).is('a')) {
                event.stopPropagation();
            } else {
                if ($input.is('.custom-input')) {
                    if ($input.is('[type="checkbox"]') || $input.is('[type="radio"]')) {
                        event.preventDefault();

                        if (event.type == 'disable' || event.type == 'enable') {
                            if (event.type == 'disable') {
                                $parent.addClass('disabled');
                                $input.prop('disabled', true);
                            } else {
                                $parent.removeClass('disabled');
                                $input.prop('disabled', false);
                            }
                        } else {
                            if (!$parent.is('.disabled')) {
                                if ($parent.is('.checked')) {
                                    if (!$input.is('[type="radio"]')) {
                                        $parent.removeClass('checked');
                                        $input.prop('checked', false).trigger('change');
                                    }
                                } else {
                                    if ($input.is('[type="radio"]')) {
                                        $radioGroup = $('input[type="radio"][name="' + $input.attr('name') + '"]');
                                        $radioGroup.each(function () {
                                            var $radioInput = $(this);
                                            $('label[for=' + $radioInput.attr('id') + ']').parent().removeClass('checked');
                                            if ($radioInput.prop('checked') == true) {
                                                $radioInput.prop('checked', false);
                                            }
                                            $radioInput.trigger('change');
                                        });
                                    }
                                    $parent.addClass('checked');
                                    $input.prop('checked', true).trigger('change');
                                }
                            }
                        }
                    }
                }
            }
        }
    }
})(jQuery);

var Form = (function ($) {
    var settings = {
        selector: null
    };

    function initialise(config) {
        settings = config;

        bindEvents();

        initAutocomplete(settings.selector);
        initConditionals(settings.selector);
        initValidation(settings.selector);
    }

    function bindEvents() {
        $(settings.selector).on('submit', function (event) {
            var $form = $(this);

            if (
                $('.LV_invalid_field:visible', $form).length ||
                $('input.required:visible', $form).value == '' ||
                $('textarea.required:visible', $form).value == '' ||
                $('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').length ||
                $('.required[type="checkbox"]:not(:checked)', $form).length
            ) {
                if (!$('select.required', $form).next('.c2-sb-wrap:visible').is('.populated')) {
                    $('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').addClass('field-error');
                }
                return false;
            }
        });
    }

    function initValidation($cnt) {
        $('[data-validators]:visible', $cnt).each(function () {
            var $self = $(this),
                fieldTitle = $self[0].title,
                validators = $self.data('validators').split('&'),
                validationObject = new LiveValidation(this.id);

            for (var i = validators.length - 1; i >= 0; i--) {
                var str = 'validationObject.add(Validate.' + validators[i] + ')';
                eval(str);
            }

            if ($self.is('.required')) {
                validationObject.add(Validate.Exclusion, {within: [fieldTitle]});
            }

            $self.data('vaildation-instance', validationObject);
        });
    }

    function initAutocomplete($cnt) {
        $('[data-autocomplete]', $cnt).each(function () {
            var $field = $(this);

            if ($field.data('autocomplete') == 'cities') {
                $field.autocomplete({
                    source: add_url + '/ajax/villes/',
                    minLength: 2,
                    select: function (event, ui) {

                        if ($(this).attr('id') == 'ville_inscription' || $(this).attr('id') == 'ville') {
                            var val = {
                                ville: ui.item.value
                            };
                            $.post(add_url + '/ajax/autocompleteCp', val).done(function (data) {
                                if (data != 'nok') {
                                    $("#postal").val(data);
                                }
                            });
                        }
                    }
                });
            } else if ($field.data('autocomplete') == 'postCodes') {
                $field.autocomplete({
                    source: add_url + '/ajax/villes/cp/',
                    minLength: 2
                });
            }
        });
    }

    function initConditionals($cnt) {
        $('[data-condition]', $cnt).on('change', function () {
            var $self = $(this),
                isChecked = $self.is(':checked'),
                cond = $self.data('condition').split(':'),
                action = cond[0],
                $target = $(cond[1]);

            if ((isChecked && action == 'hide') || isChecked == false && action == 'show') {
                $target.addClass('condition-hidden');
                destroyValidation($target);
            } else {
                $target.removeClass('condition-hidden');
                initValidation($target);
            }
        }).trigger('change');
        $('[data-condition]:checked').trigger('change');
    }

    function destroyValidation() {
        $('[data-validators]', '.condition-hidden').each(function () {
            var $field = $(this);
            if ($field.data('vaildation-instance')) {
                $field.data('vaildation-instance').destroy();
            }
        })
    }

    return {
        initialise: initialise
    }
}(jQuery));
