(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function() {
		
		
		$( "#depot_de_dossier" ).submit(function( event ) {
			var radio = true;
	
			if($('input[type=radio][name=exercices_comptables]:checked').length){
				$('.exercice_comptable_check').css('color','#999');
			}
			else
			{
				$('.exercice_comptable_check').css('color','#c84747');
				radio = false
			}
			
			if(radio == false)
			{
				event.preventDefault();	
			}
		});
		
		
		$('.custom-select').c2Selectbox();
		
		CInput.init();
		
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
			$('.featured-articles .carousel ul').carouFredSel({
				width: '100%',				
				align: 'left',
				auto: false,
				scroll: {
					items: 1
				},
				prev: {
					button: '.prev-slide',
					key: 'left'
				},
				next: {
					button: '.next-slide',
					key: 'right'
				},
				swipe: {
					onTouch: true
				}
			});
		});
	});
	
	
	var CInput = {
		init: function($cnt){
			var that = this;
			if(!$cnt){
				$cnt = $doc;
			}
			$('label', $cnt).off('click').on('click', function(event){
				that.handle($(this), event);
			});

			$('input', $cnt).off('disable enable').on('disable enable', function(event){
				that.handle($(this).parent().find('label'), event);
			});

			$('input', $cnt).each(function(){
				var $self = $(this);
				if($self.is('[type="checkbox"]') || $self.is('[type="radio"]')){
					if($self.is(':checked')){
						$self.parent().addClass('checked');
					}
					if($self.is(':disabled')){
						$self.parent().addClass('disabled');
					}
				}
			});
		},
		handle: function($label, event){
			var $input = $('input#' + $label.attr('for')),
				$parent = $label.parent();

			if($(event.target).is('a')){
				event.stopPropagation();
			}else{
				if($input.is('.custom-input')){
					if($input.is('[type="checkbox"]') || $input.is('[type="radio"]')){
						event.preventDefault();

						if(event.type == 'disable' || event.type == 'enable'){
							if(event.type == 'disable'){
								$parent.addClass('disabled');
								$input.prop('disabled', true);
							}else{
								$parent.removeClass('disabled');
								$input.prop('disabled', false);
							}
						}else{
							if(!$parent.is('.disabled')){
								if($parent.is('.checked')){
									if(!$input.is('[type="radio"]')){
										$parent.removeClass('checked')
										$input.prop('checked', false).trigger('change');
									}
								}else{
									if($input.is('[type="radio"]')){
										$radioGroup = $('input[type="radio"][name="'+$input.attr('name')+'"]');
										$radioGroup.each(function(){
											var $radioInput = $(this);
											$('label[for='+ $radioInput.attr('id') +']').parent().removeClass('checked');
											if($radioInput.prop('checked') == true){
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
		$('[data-validators]:visible', $cnt).each(function(){
			var $self = $(this),
				fieldTitle = $self[0].title,
				validators = $self.data('validators').split('&'),
				validationObject = new LiveValidation(this.id);
				
			
			for (var i = validators.length - 1; i >= 0; i--) {
				var str = 'validationObject.add(Validate.' + validators[i] + ')';
				eval(str);
			};

			if($self.is('.required')){
				validationObject.add(Validate.Exclusion, { within: [ fieldTitle ] });
			}

			$self.data('vaildation-instance', validationObject);
		});
	}
	
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
