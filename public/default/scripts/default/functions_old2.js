// Autocomplete Data:

/*var ACData = {
	cities: ['Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille', 'Rennes', 'Le', 'Reims', 'Saint', 'Toulon', 'Grenoble', 'Angers', 'Dijon', 'Brest', 'Le', 'Clermont', 'Amiens', 'Aix', 'Limoges', 'Nîmes', 'Tours', 'Saint', 'Villeurbanne', 'Metz', 'Besançon', 'Caen', 'Orléans', 'Mulhouse', 'Rouen', 'Boulogne', 'Perpignan', 'Nancy', 'Roubaix', 'Fort', 'Argenteuil', 'Tourcoing', 'Montreuil', 'Saint', 'Avignon', 'Saint', 'Versailles', 'Nanterre', 'Poitiers', 'Créteil', 'Aulnay', 'Vitry', 'Pau', 'Calais', 'Colombes', 'La', 'Asnières'],

	postCodes: ['24250', '24260', '24270', '24290', '24300', '24310', '24320', '24330', '24340', '24350', '24360', '24370', '24380', '24390', '24400', '24410', '24420', '24430', '24440', '24450', '24460', '24470', '24480', '24490', '24500', '24510', '24520', '24530', '24540', '24550', '24560', '24570', '24580', '24590', '24600', '60120', '60121', '60122', '60123', '60126', '60127', '60128', '60129', '60130', '60131', '60132', '60134', '60138', '60140', '60141', '60149', '60150', '60153', '68509', '68510']
};*/

(function($){
	var $doc = $(document),
		$win = $(window),
		navPos = 0,
		sidPos = 0,
		footerPos = 0;
		
	$doc.on('ready', function(){

		navPos = $('.navigation').offset().top;
		
		var sidebar_exist = false;
		if($('.sidebar').length)
		{
			sidPos = $('.sidebar').offset().top;
			footerPos = $('.footer').offset().top;
		}
		
		
		
		
		// Blink fields
		$doc.on('focusin', '.field, textarea', function(){
			if(this.title==this.value) {
				this.value = '';
			}
			$(this).siblings('.fake-field').hide();
		}).on('focusout', '.field, textarea', function(){
			if(this.value=='') {
				$(this).removeClass('populated');
				if($(this).is('[type="password"]')){
					$(this).siblings('.fake-field').show();
				}else{
					this.value = this.title;
				}
			}else{
				$(this).addClass('populated');
			}
			
		}).on('click', 'a.popup-close, .close-btn', function(event){
			event.preventDefault();
			$.colorbox.close();

		});
		
		$('.logedin-panel').hover(function(){
			$(this).find('.dd').stop(true,true).show();
		},function(){
			$(this).find('.dd').hide();
		})

		$('.tooltip-anchor').tooltip();

		$('.custom-select').c2Selectbox();
		
		$( "#datepicker" ).datepicker({
			inline: true,
			changeMonth: true,
			changeYear: true
		});

		CInput.init();

		Form.initialise({
			selector: 'form'
		});

		$('.tabs-nav').on('click', 'a', function(event){
			if( !$(this).parent().is('.active') ){
				var index = $(this).parent().index();
				$('.tabs-nav li').eq(index).addClass('active').siblings('li').removeClass('active');
				$('.tabs .tab').hide().eq(index).stop(true,true).fadeIn();
			}
			event.preventDefault()
		});

		/*$('.fav-btn').on('click', function(event){
			$(this).toggleClass('active');
			event.preventDefault()
		});*/

		$('.ex-article > h3').on('click', function(event){
			$(this).next('.article-entry').stop(true,true).slideToggle();
			$(this).find('i').toggleClass('up');
			event.preventDefault()
		});

		$('.esc-btn').on('click', function(event){
			$(this).parent().fadeOut();
			event.preventDefault()
		})

		$('.post-schedule h2').on('click', function(){
			$(this).next('.body').stop(true,true).slideToggle();
		})

		// ProgressBar
		$(window).load(function(){
			if ( $('.progressBar').length ){
				$('.progressBar').each(function(){
					var per = $(this).data('percent');
					progress(per, $(this));
				});		
			}
			function progress(percent, $element) {
				var progressBarWidth = percent * $element.width() / 100;
				$element.find('div').animate({ width: progressBarWidth }, 1200, function(){
					var leftP = $(this).width();
					
					
					$(this).find('span').html(percent.toString().replace('.',',') + "%&nbsp;").css('left', leftP);	
				})
			}
		})
		
		// Pass fields
		$('.pass-field-holder').each(function(){
			var $self = $(this),
				$input = $self.find('input'),
				$fake = $('<span class="fake-field">' + $input.attr('title') + '</span>');
			
			$self.append($fake);
			$fake.on('click', function(){
				$fake.hide()
				$input.trigger('focus');
			});

			if($input[0].value.length){
				$fake.hide();
			}
		});

		
		
		

		$('.euro-field').each(function(){
			$(this).before('<span class="euro-sign">&euro;</span>')
		});



		$('.popup-link').colorbox({
				opacity: 0.5,
				scrolling: false,
				onComplete: function(){
					$('.popup .custom-select').c2Selectbox();

					$('input.file-field').on('change', function(){
						var $self = $(this),
							val = $self.val()
						if( val.length != 0 || val != '' ){
							$self.closest('.uploader').find('input.field').val(val);
							
							var idx = $('#rule-selector').val();
							$('.rules-list li[data-rule="'+idx+'"]').addClass('valid');
							
						}
					})

					$('#rule-selector').on('change', function(){
						var idx = $(this).val();
						$('.uploader[data-file="'+idx+'"]').slideDown().siblings('.uploader:visible').slideUp();
					})
				}

		});
		
		
		Highcharts.setOptions({
			lang: {
				decimalPoint: ","
			}
		});
		
		// Graphic Chart
		if( $('.graphic-box').length ){
			var titlePrete = $('#titlePrete').html();
			var titleArgentRemb = $('#titleArgentRemb').html();
			var titleInteretsRecu = $('#titleInteretsRecu').html();
			
			var leSoldePourcent = parseFloat($('#leSoldePourcent').html());
			var sumBidsEncoursPourcent = parseFloat($('#sumBidsEncoursPourcent').html());
			var sumPretsPourcent = parseFloat($('#sumPretsPourcent').html());

			$('#pie-chart').highcharts({
		        chart: {
					backgroundColor:'#fafafa',
		            plotBackgroundColor: null,
		            plotBorderWidth: null,
		            plotShadow: false,
					width: 460
		        },
		        colors: ['#b10366', '#f7922b', '#40b34f'],
		        title: {
		            text: ''
		        },
		        
		        plotOptions: {
		            pie: {
		                allowPointSelect: true,
		                cursor: 'pointer',
		                dataLabels: {
		                    enabled: true,
		                    colors: ['#a1a5a7', '#a1a5a7', '#a1a5a7'],
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
		                [$('#leSolde').html(), leSoldePourcent]
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
					backgroundColor:'#fafafa',
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
	            series:[{
	            	name:'Mouvement de ',
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
			
			if($('#remb1').html() === undefined) var remb1 = 0;
			else var remb1 = parseFloat($('#remb1').html());
			if($('#remb2').html() === undefined) var remb2 = 0;
			else var remb2 = parseFloat($('#remb2').html());
			if($('#remb3').html() === undefined) var remb3 = 0;
			else var remb3 = parseFloat($('#remb3').html());
			if($('#remb4').html() === undefined) var remb4 = 0;
			else var remb4 = parseFloat($('#remb4').html());
			if($('#remb5').html() === undefined) var remb5 = 0;
			else var remb5 = parseFloat($('#remb5').html());
			if($('#remb6').html() === undefined) var remb6 = 0;
			else var remb6 = parseFloat($('#remb6').html());
			if($('#remb7').html() === undefined) var remb7 = 0;
			else var remb7 = parseFloat($('#remb7').html());
			if($('#remb8').html() === undefined) var remb8 = 0;
			else var remb8 = parseFloat($('#remb8').html());
			if($('#remb9').html() === undefined) var remb9 = 0;
			else var remb9 = parseFloat($('#remb9').html());
			if($('#remb10').html() === undefined) var remb10 = 0;
			else var remb10 = parseFloat($('#remb10').html());
			if($('#remb11').html() === undefined) var remb11 = 0;
			else var remb11 = parseFloat($('#remb11').html());
			if($('#remb12').html() === undefined) var remb12 = 0;
			else var remb12 = parseFloat($('#remb12').html());
			
			if($('#inte1').html() === undefined) var inte1 = 0;
			else var inte1 = parseFloat($('#inte1').html());
			if($('#inte2').html() === undefined) var inte2 = 0;
			else var inte2 = parseFloat($('#inte2').html());
			if($('#inte3').html() === undefined) var inte3 = 0;
			else var inte3 = parseFloat($('#inte3').html());
			if($('#inte4').html() === undefined) var inte4 = 0;
			else var inte4 = parseFloat($('#inte4').html());
			if($('#inte5').html() === undefined) var inte5 = 0;
			else var inte5 = parseFloat($('#inte5').html());
			if($('#inte6').html() === undefined) var inte6 = 0;
			else var inte6 = parseFloat($('#inte6').html());
			if($('#inte7').html() === undefined) var inte7 = 0;
			else var inte7 = parseFloat($('#inte7').html());
			if($('#inte8').html() === undefined) var inte8 = 0;
			else var inte8 = parseFloat($('#inte8').html());
			if($('#inte9').html() === undefined) var inte9 = 0;
			else var inte9 = parseFloat($('#inte9').html());
			if($('#inte10').html() === undefined) var inte10 = 0;
			else var inte10 = parseFloat($('#inte10').html());
			if($('#inte11').html() === undefined) var inte11 = 0;
			else var inte11 = parseFloat($('#inte11').html());
			if($('#inte12').html() === undefined) var inte12 = 0;
			else var inte12 = parseFloat($('#inte12').html());
			
			if($('#fiscal1').html() === undefined) var fiscal1 = 0;
			else var fiscal1 = parseFloat($('#fiscal1').html());
			if($('#fiscal2').html() === undefined) var fiscal2 = 0;
			else var fiscal2 = parseFloat($('#fiscal2').html());
			if($('#fiscal3').html() === undefined) var fiscal3 = 0;
			else var fiscal3 = parseFloat($('#fiscal3').html());
			if($('#fiscal4').html() === undefined) var fiscal4 = 0;
			else var fiscal4 = parseFloat($('#fiscal4').html());
			if($('#fiscal5').html() === undefined) var fiscal5 = 0;
			else var fiscal5 = parseFloat($('#fiscal5').html());
			if($('#fiscal6').html() === undefined) var fiscal6 = 0;
			else var fiscal6 = parseFloat($('#fiscal6').html());
			if($('#fiscal7').html() === undefined) var fiscal7 = 0;
			else var fiscal7 = parseFloat($('#fiscal7').html());
			if($('#fiscal8').html() === undefined) var fiscal8 = 0;
			else var fiscal8 = parseFloat($('#fiscal8').html());
			if($('#fiscal9').html() === undefined) var fiscal9 = 0;
			else var fiscal9 = parseFloat($('#fiscal9').html());
			if($('#fiscal10').html() === undefined) var fiscal10 = 0;
			else var fiscal10 = parseFloat($('#fiscal10').html());
			if($('#fiscal11').html() === undefined) var fiscal11 = 0;
			else var fiscal11 = parseFloat($('#fiscal11').html());
			if($('#fiscal12').html() === undefined) var fiscal12 = 0;
			else var fiscal12 = parseFloat($('#fiscal12').html());
			
			
			$('#bar-mensuels-1').highcharts({
	            chart: {
	                type: 'column',
					backgroundColor:'#fafafa',
	                plotBackgroundColor: null,
		            plotBorderWidth: null,
		            plotShadow: false
	            },
	            colors: ['#8462a7','#ee5396','#b10366'],
	            title: {
	                text: ''
	            },
	            xAxis: {
	            	color: '#a1a5a7',
	            	title: {
	            		enabled: null,
	            		text : null
	            	},
	                categories: [' <b>JAN</>', ' <b>FEV</b>', ' <b>MAR</b>']
	            },
	            yAxis: {
					reversedStacks: false,
	            	title: {
	            		enabled: null,
	            		text: null
	            	},
	                min: 0
	            },
	            legend: {
	            	borderColor: '#ffffff',
	            	enabled: true
	            },
	            plotOptions: {
	                column: {
	                	pointWidth: 80,
	                    stacking: 'normal',
		                dataLabels: {
		                	color: '#fff',
		                    enabled: true,
		                    format: '{point.name}'
		                }
	                }
	            },
				tooltip: {
					valueSuffix: ' €',
				},
	            series: [
				
				
	            {
	                name: ' <b>'+$('.capital_rembourse').html()+'</b>',
	                data:  [
						[' <b>'+remb1.toString().replace('.',',')+'€</b>',  remb1],
						[' <b>'+remb2.toString().replace('.',',')+'€</b>',  remb2],
						[' <b>'+remb3.toString().replace('.',',')+'€</b>',   remb3]
		            ]
	            },
				{
	                name: ' <b>'+$('.interets_recu').html()+'</b>',
	                data: [
						[' <b>'+inte1.toString().replace('.',',')+' €</b>',  inte1],
						[' <b>'+inte2.toString().replace('.',',')+' €</b>',  inte2],
						[' <b>'+inte3.toString().replace('.',',')+' €</b>',inte3]]
	            },
				{
	                name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
	                data:  [
		                [' <b>'+fiscal1.toString().replace('.',',')+'€</b>',  fiscal1],
		                [' <b>'+fiscal2.toString().replace('.',',')+'€</b>',  fiscal2],
		                [' <b>'+fiscal3.toString().replace('.',',')+'€</b>',   fiscal3]
		            ]
	            }]
	        });

			$('#bar-mensuels-2').highcharts({
	            chart: {
	                type: 'column',
					backgroundColor:'#fafafa',
	                plotBackgroundColor: null,
		            plotBorderWidth: null,
		            plotShadow: false
	            },
	            colors: ['#8462a7','#ee5396','#b10366'],
	            title: {
	                text: ''
	            },
	            xAxis: {
	            	color: '#a1a5a7',
	            	title: {
	            		enabled: null,
	            		text : null
	            	},
	                categories: [' <b>AVR</>', ' <b>MAI</b>', ' <b>JUIN</b>']
	            },
	            yAxis: {
					reversedStacks: false,
					title: {
	            		enabled: null,
	            		text: null
	            	},
	                min: 0
	            },
	            legend: {
	            	borderColor: '#ffffff',
	            	enabled: true
	            },
	            plotOptions: {
	                column: {
	                	pointWidth: 80,
	                    stacking: 'normal',
		                dataLabels: {
		                	color: '#fff',
		                    enabled: true,
		                    format: '{point.name}'
		                }
	                }
	            },
				tooltip: {
					valueSuffix: ' €',
				},
	            series: [
				
				
	            {
	                name: ' <b>'+$('.capital_rembourse').html()+'</b>',
	                data:  [
		                [' <b>'+remb4.toString().replace('.',',')+' €</b>',  remb4],
		                [' <b>'+remb5.toString().replace('.',',')+'€</b>',  remb5],
		                [' <b>'+remb6.toString().replace('.',',')+'€</b>',   remb6]
		            ]
	            },
				{
	               name: ' <b>'+$('.interets_recu').html()+'</b>',
	               data: [
		                [' <b>'+inte4.toString().replace('.',',')+' €</b>',  inte4],
		                [' <b>'+inte5.toString().replace('.',',')+' €</b>',  inte5],
		                [' <b>'+inte6.toString().replace('.',',')+' €</b>',   inte6]
		            ]
	            },
				{
	                name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
	                data:  [
		                [' <b>'+fiscal4.toString().replace('.',',')+'€</b>',  fiscal4],
		                [' <b>'+fiscal5.toString().replace('.',',')+'€</b>',  fiscal5],
		                [' <b>'+fiscal6.toString().replace('.',',')+'€</b>',   fiscal6]
		            ]
	            }]
	        });

			$('#bar-mensuels-3').highcharts({
	            chart: {
	                type: 'column',
					backgroundColor:'#fafafa',
	                plotBackgroundColor: null,
		            plotBorderWidth: null,
		            plotShadow: false
	            },
	            colors: ['#8462a7','#ee5396','#b10366'],
	            title: {
	                text: ''
	            },
	            xAxis: {
	            	color: '#a1a5a7',
	            	title: {
	            		enabled: null,
	            		text : null
	            	},
	                categories: [' <b>JUIL</>', ' <b>AOUT</b>', ' <b>SEPT</b>']
	            },
	            yAxis: {
					reversedStacks: false,
	            	title: {
	            		enabled: null,
	            		text: null
	            	},
	                min: 0
	            },
	            legend: {
	            	borderColor: '#ffffff',
	            	enabled: true
	            },
	            plotOptions: {
	                column: {
	                	pointWidth: 80,
	                    stacking: 'normal',
		                dataLabels: {
		                	color: '#fff',
		                    enabled: true,
		                    format: '{point.name}'
		                }
	                }
	            },
				tooltip: {
					valueSuffix: ' €',
				},
				series: [
				
				
				{
					 name: ' <b>'+$('.capital_rembourse').html()+'</b>',
					data:  [
						[' <b>'+remb7.toString().replace('.',',')+'€</b>',  remb7],
						[' <b>'+remb8.toString().replace('.',',')+'€</b>',  remb8],
						[' <b>'+remb9.toString().replace('.',',')+'€</b>',   remb9]
					]
				},
				{
					name: ' <b>'+$('.interets_recu').html()+'</b>',
					data: [
						[' <b>'+inte7.toString().replace('.',',')+' €</b>',  inte7],
						[' <b>'+inte8.toString().replace('.',',')+' €</b>',  inte8],
						[' <b>'+inte9.toString().replace('.',',')+' €</b>',   inte9]
					]
				},
				{
	                name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
	                data:  [
		                [' <b>'+fiscal7.toString().replace('.',',')+'€</b>',  fiscal7],
		                [' <b>'+fiscal8.toString().replace('.',',')+'€</b>',  fiscal8],
		                [' <b>'+fiscal9.toString().replace('.',',')+'€</b>',   fiscal9]
		            ]
	            }]
	        });

			$('#bar-mensuels-4').highcharts({
	            chart: {
	                type: 'column',
					backgroundColor:'#fafafa',
	                plotBackgroundColor: null,
		            plotBorderWidth: null,
		            plotShadow: false
	            },
	            colors: ['#8462a7','#ee5396','#b10366'],
	            title: {
	                text: ''
	            },
	            xAxis: {
	            	color: '#a1a5a7',
	            	title: {
	            		enabled: null,
	            		text : null
	            	},
	                categories: [' <b>OCT</>', ' <b>NOV</b>', ' <b>DEC</b>']
	            },
	            yAxis: {
					reversedStacks: false,
	            	title: {
	            		enabled: null,
	            		text: null
	            	},
	                min: 0
	            },
	            legend: {
	            	borderColor: '#ffffff',
	            	enabled: true
	            },
	            plotOptions: {
	                column: {
	                	pointWidth: 80,
	                    stacking: 'normal',
		                dataLabels: {
		                	color: '#fff',
		                    enabled: true,
		                    format: '{point.name}'
		                }
	                }
	            },
				tooltip: {
					valueSuffix: ' €',
				},
	            series: [
				
				
	            {
	               	name: ' <b>'+$('.capital_rembourse').html()+'</b>',
	                data:  [
		                [' <b>'+remb10.toString().replace('.',',')+'€</b>',  remb10],
		                [' <b>'+remb11.toString().replace('.',',')+'€</b>',  remb11],
		                [' <b>'+remb12.toString().replace('.',',')+'€</b>',   remb12]
		            ]
	            },
				{
					name: ' <b>'+$('.interets_recu').html()+'</b>',
					data: [
						[' <b>'+inte10.toString().replace('.',',')+' €</b>',  inte10],
						[' <b>'+inte11.toString().replace('.',',')+' €</b>',  inte11],
						[' <b>'+inte12.toString().replace('.',',')+' €</b>',  inte12]
					]
	            },
				{
	                name: ' <b>'+$('.prelevements_fiscaux').html()+'</b>',
	                data:  [
		                [' <b>'+fiscal10.toString().replace('.',',')+'€</b>',  fiscal10],
		                [' <b>'+fiscal11.toString().replace('.',',')+'€</b>',  fiscal11],
		                [' <b>'+fiscal12.toString().replace('.',',')+'€</b>',   fiscal12]
		            ]
	            }]
	        });

		}

	});
		
		
		/*$doc.on('click', 'a.popup-close, .close-btn', function(event){
			event.preventDefault();
			$.colorbox.close();
		});
	});*/

	$win.on('scroll', function(){
		if($('body').is('.has-fixed-nav')){
			var scrolled = $win.scrollTop();
			
			var newfooterPos = footerPos-800;
			
			if(scrolled >= navPos){
				$('body').addClass('nav-fixed');
				
			}else{
				$('body').removeClass('nav-fixed');
				
			}
			
			if($('.sidebar').length)
			{
				//if(scrolled >= sidPos-60 && scrolled < newfooterPos){
				if(scrolled >= sidPos-60){
					
					$('.sidebar').addClass('sidebar-fixed');
				}else{
					
					$('.sidebar').removeClass('sidebar-fixed');
				}
				//if(scrolled >= newfooterPos){
//					
//					$('.sidebar').addClass('sidebar-fixed2');
//				}else{
//					
//					$('.sidebar').removeClass('sidebar-fixed2');
//				}
			}
			
		}
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


})(jQuery);

// Form
var Form = (function($){

	var settings = {
		selector: null
	};

	function initialise(config){
		settings = config;

		bindEvents();

		initAutocomplete(settings.selector);
		initConditionals(settings.selector);
		initValidation(settings.selector);
		
	}

	function bindEvents(){
		$(settings.selector).on('submit', function(event){
			var $form = $(this);
			
			/*if($('.validationRadio1').attr("checked"))
			{
				alert('check');
				
			}
			else
			{
				alert('ncheck');	
			}*/
			
			
			
			if(
				$('.LV_invalid_field:visible', $form).length ||
				$('input.required:visible', $form).value == '' ||
				$('textarea.required:visible', $form).value == '' ||
				$('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').length ||
				$('.required[type="checkbox"]:not(:checked)', $form).length
			){
				
				if(!$('select.required', $form).next('.c2-sb-wrap:visible').is('.populated')){
					$('select.required', $form).next('.c2-sb-wrap:visible:not(.populated)').addClass('field-error');
					
				}
				return false;
			}
		});
	}

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

	/*function initAutocomplete($cnt){
		$('[data-autocomplete]', $cnt).each(function(){
			var $field = $(this);

			$field.autocomplete({
				source: ACData[$field.data('autocomplete')]
			});
		});

	}*/
	
	function initAutocomplete($cnt){
		$('[data-autocomplete]', $cnt).each(function(){
			var $field = $(this);
			
			if($field.data('autocomplete') == 'cities')
			{
				$field.autocomplete({
					source: add_url + '/ajax/villes/',
					minLength: 2,
					select: function( event, ui ) {
						
						if($(this).attr('id') == 'ville_inscription' || $(this).attr('id') == 'ville')
						{
							var val = { 
								ville: ui.item.value
							}
							$.post(add_url + '/ajax/autocompleteCp', val).done(function(data) {
								
								if(data != 'nok')
								{
									$("#postal").val(data);
								}
							});
							
						}
					}
				});	
			}
			else if($field.data('autocomplete') == 'postCodes')
			{
				$field.autocomplete({
					source: add_url + '/ajax/villes/cp/',
					minLength: 2
				});
			}
			
		});

	}
	

	function initConditionals($cnt){
		$('[data-condition]', $cnt).on('change', function(){
			var $self = $(this),
				isChecked = $self.is(':checked'),
				cond = $self.data('condition').split(':'),
				action = cond[0],
				$target = $(cond[1]);


			if((isChecked && action == 'hide') || isChecked == false && action == 'show'){
				$target.addClass('condition-hidden');
				destroyValidation($target);
			}else{
				$target.removeClass('condition-hidden');
				initValidation($target);
			}
		}).trigger('change');
		$('[data-condition]:checked').trigger('change');
	}

	function destroyValidation(){
		$('[data-validators]', '.condition-hidden').each(function(){
			var $field = $(this);
			if($field.data('vaildation-instance')){
				$field.data('vaildation-instance').destroy();
			}
		})
	}


	return {
		initialise: initialise
	}

}(jQuery));
