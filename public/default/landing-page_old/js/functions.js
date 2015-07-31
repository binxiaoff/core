(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function() {
		Form.initialise({
			selector: 'form'
		});

		$doc
		.on('focusin', '.field, textarea', function() {
			if ( this.title == this.value ) {
				this.value = '';
			}
		})
		.on('focusout', '.field, textarea', function() {
			if ( this.value == '' ) {
				this.value = this.title;
			}
		});

		$win.on('load', function() {
			$('.featured-articles .carousel').flexslider({
				animation: 'slide',
				animationLoop: true,
				slideshow: true,
				controlNav: false,
				itemWidth: 320,
				itemMargin: 0,
				move: 1,
				prevText: '',
				nextText: ''
			});
		});
	});
})(jQuery, window, document);

// Form
var Form = (function($) {
	var settings = {
		selector: null
	};

	function initialise(config){
		settings = config;
		bindEvents();
		initAutocomplete(settings.selector);
		initConditionals(settings.selector);
		initValidation(settings.selector);
	};

	function bindEvents(){
		$(settings.selector).on('submit', function(event) {
			var $form = $(this);

			if (
				$('.LV_invalid_field:visible', $form).length ||
				$('input.required:visible', $form).value == '' ||
				$('textarea.required:visible', $form).value == '' ||
				$('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').length ||
				$('.required[type="checkbox"]:not(:checked)', $form).length
			) {
				if ( !$('select.required', $form).next('.c2-sb-wrap:visible').is('.populated') ) {
					$('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').addClass('field-error');
				}
				return false;
			}
		});
	};

	function initValidation($cnt){
		$('[data-validators]:visible', $cnt).each(function() {
			var $self = $(this),
				fieldTitle = $self[0].title,
				validators = $self.data('validators').split('&'),
				validationObject = new LiveValidation(this.id);
			
			for ( var i = validators.length - 1; i >= 0; i--) {
				var str = 'validationObject.add(Validate.' + validators[i] + ')';
				eval(str);
			}

			if ( $self.is('.required') ) {
				validationObject.add(Validate.Exclusion, { within: [ fieldTitle ] });
			}

			$self.data('vaildation-instance', validationObject);
		});
	};
	
	function initAutocomplete($cnt){
		$('[data-autocomplete]', $cnt).each(function(){
			var $field = $(this);
			
			if ( $field.data('autocomplete') == 'cities' ) {
				$field.autocomplete({
					source: add_url + '/ajax/villes/',
					minLength: 2,
					select: function( event, ui ) {
						if ( $(this).attr('id') == 'ville_inscription' || $(this).attr('id') == 'ville' ) {
							var val = { 
								ville: ui.item.value
							};

							$.post(add_url + '/ajax/autocompleteCp', val).done(function(data) {
								if (data != 'nok' ) {
									$("#postal").val(data);
								}
							});
						}
					}
				});	
			} else if ( $field.data('autocomplete') == 'postCodes' ) {
				$field.autocomplete({
					source: add_url + '/ajax/villes/cp/',
					minLength: 2
				});
			}
		});
	};

	function initConditionals($cnt){
		$('[data-condition]', $cnt).on('change', function() {
			var $self = $(this),
				isChecked = $self.is(':checked'),
				cond = $self.data('condition').split(':'),
				action = cond[0],
				$target = $(cond[1]);

			if ( (isChecked && action == 'hide') || isChecked == false && action == 'show' ) {
				$target.addClass('condition-hidden');
				destroyValidation($target);
			} else {
				$target.removeClass('condition-hidden');
				initValidation($target);
			}
		}).trigger('change');

		$('[data-condition]:checked').trigger('change');
	};

	function destroyValidation(){
		$('[data-validators]', '.condition-hidden').each(function(){
			var $field = $(this);

			if ( $field.data('vaildation-instance') ) {
				$field.data('vaildation-instance').destroy();
			}
		});
	};

	return {
		initialise: initialise
	};
}(jQuery));


function noNumber(val,id)
{
	var nb = val.length;
	val = val.split('');
	var newval = '';
	
	for(i=0;i<nb;i++)
	{
		if(val[i] == " ")
		{
			newval = newval+val[i];
		}
		if(isNaN(val[i]) == true)
		{
			newval = newval+val[i];
		}
		
	}
		$("#"+id).val(newval);
}


function check_conf_mail(mail)
{
	if($('#signup-email').val() != $('#signup-email-confirm').val())
	{		
		$('#signup-email-confirm').addClass('LVinvalidfield2');		
		$('#signup-email-confirm').addClass('LV_invalid_field');		
		$('#signup-email-confirm').removeClass('LV_valid_field');
	}
	else
	{
		$('#signup-email-confirm').removeClass('LVinvalidfield2');
		$('#signup-email-confirm').addClass('LV_valid_field');
		$('#signup-email-confirm').removeClass('LV_invalid_field');
	}
}
