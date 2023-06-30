jQuery(document).ready(function(){
	jQuery('.wdm_model_select').select2();
	jQuery(document).on('click','.wdm_ct_tile',function(){
		jQuery('select[name="wdm_manufacturer"]').hide();
		jQuery('.wdm_models').hide();
		jQuery('.wdm_compatibility_section').hide();
		jQuery('.wdm_notice').html('');
		if(jQuery('.carrier_type').val()!=''){
			jQuery('.wdm_ct_tile').removeClass('wdm_ct_tile_active');
		}
		var product_series=0;
		var product=0;
		if(jQuery('.product_series').length){
			product_series=jQuery('.product_series').val();
		}
		if(jQuery('.product').length){
			product=jQuery('.product').val();
		}
		jQuery('.carrier_type').val(jQuery(this).attr('data-id'));
		jQuery('.carrier_type').data("name",jQuery(this).children('.ct_name').text());
		jQuery(this).addClass('wdm_ct_tile_active');//css({"border":"2px solid #a30d14"});
		var data = {
				'action': 'wdm_get_manufacturer',
				'c_type': jQuery(this).attr('data-id'),
				'product_series': product_series,
				'product': product
			};
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			jQuery('.wdm_manufacturers').html('<div class="wdm_title"><h3>&nbsp;Select Carrier Manufacturer</h3></div>'+response);
			jQuery('.wdm_manufacturers').show();
			if(screen.width>512){
				jQuery('select[name="wdm_manufacturer"]').select2();
			}
		});
	});

	jQuery(document).on('change','select[name="wdm_manufacturer"]',function(){
		var product_series=0;
		var product=0;
		if(jQuery('.product_series').length){
			product_series=jQuery('.product_series').val();
		}
		if(jQuery('.product').length){
			product=jQuery('.product').val();
		}
		jQuery('.carrier_man').val(jQuery(this).val());
		jQuery('.carrier_man').attr('data-name',jQuery(this).find('option:selected').text());
		jQuery('.wdm_models').hide();
		jQuery('.wdm_compatibility_section').hide();
		jQuery('.wdm_notice').html('');
		var data = {
				'action': 'wdm_get_model',
				'manufacturer': jQuery(this).val(),
				'c_type': jQuery('.carrier_type').val(),
				'product_series': product_series,
				'product': product,
				'man_txt':jQuery('.carrier_man').attr('data-name'),
				'c_type_txt': jQuery('.carrier_type').data('name'),
				'is_breaker': jQuery('.is_breaker').val()
			};
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			if(product==0){
				jQuery('.wdm_models').show();
				jQuery('.wdm_models').html('<div class="wdm_title"><h3>&nbsp;Select Carrier Model</h3></div>'+response);
				if(screen.width>512){
					jQuery('.wdm_model').select2();
				}
			}else{
				jQuery('.wdm_compatibility_section').show();
				jQuery('.wdm_compatibility_section').html(response);
				if(jQuery('.is_breaker').val() == 1){
					jQuery('.wdm_compatibility_section').append('<div class="frd_warning">* Breakers listed as optional will fit, but may not provide optimum performance.</div>');
				}
			}
		});
	});

	jQuery(document).on('click','.wdm_models_list .wdm_model',function(){
		var product_series=0;
		if(jQuery('.product_series').length){
			product_series=jQuery('.product_series').val();
		}
		jQuery('.wdm_carrier_type').remove();
		jQuery('.product').val(jQuery(this).text());
		jQuery('.wdm_models_list .wdm_model').each(function(){
			jQuery(this).removeAttr('style');
		});
		jQuery(this).css({"background-color": "#333","color":"white"});
		jQuery('.wdm_compatibility_section').hide();
		jQuery('.wdm_manufacturers').hide();
		var data = {
				'action': 'wdm_get_carrier_types',
				'product': jQuery(this).text(),
				'product_series':product_series
			};
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			jQuery('.wdm_carrier_model').after(response);
		});
	});

	jQuery(document).on('change','.wdm_model_select',function(){
		var product_series=0;
		if(jQuery('.product_series').length){
			product_series=jQuery('.product_series').val();
		}
		jQuery('.wdm_carrier_type').remove();
		jQuery('.product').val(jQuery(this).val());
		jQuery('.wdm_compatibility_section').hide();
		jQuery('.wdm_manufacturers').hide();
		var data = {
				'action': 'wdm_get_carrier_types',
				'product': jQuery(this).val(),
				'product_series':product_series
			};
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			jQuery('.wdm_carrier_model').after(response);
		});
	});

	jQuery(document).on('change','.wdm_models .wdm_model',function(){
		var data;
		var model_txt;
		var carrier_model;
		if(screen.width>512){
			data=jQuery(this).select2('data');
			model_txt=data[0].text;
			carrier_model=data[0].id
		}else{
			carrier_model=jQuery(this).val();
			model_txt=jQuery('.wdm_models .wdm_model option:selected').text();
		}
		var product_series=0;
		var product=0;
		if(jQuery('.product_series').length){
			product_series=jQuery('.product_series').val();
		}
		if(jQuery('.product').length){
			product=jQuery('.product').val();
		}
		if(jQuery('.carrier_model').val()!=''){
			jQuery('.wdm_models .wdm_model[data-id="'+jQuery('.carrier_model').val()+'"]').removeAttr('style');
		}
		jQuery('.carrier_model').val(carrier_model);
		jQuery('.carrier_model').attr('data-name',jQuery(this).text());
		var data = {
				'action': 'wdm_view_compatibility',
				'c_type': jQuery('.carrier_type').val(),
				'manufacturer': jQuery('.carrier_man').attr('value'),
				'carrier_model': carrier_model,
				'product_series': product_series,
				'product': product,
				'c_type_txt': jQuery('.carrier_type').data('name'),
				'man_txt':jQuery('.carrier_man').attr('data-name'),
				'model_txt':model_txt

			};
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			jQuery('.wdm_compatibility_section').show();
			jQuery('.wdm_compatibility_section').html(response);
			jQuery('.wdm_compatibility_section').append('<div class="frd_warning">* Breakers listed as optional will fit, but may not provide optimum performance.</div>');
		});
	});
	
	//Code to show next carrier types
	
	jQuery('.wdm_next').click(function(){
	    jQuery(this).hide();
	    jQuery('.wdm_previous').show();
	    jQuery('.wdm_types').animate({
            scrollLeft: jQuery('.wdm_ct_tile:nth-child(6)').offset().left
        }, 800);
	});
	
	//Code to show previous carrier types
	
	jQuery('.wdm_previous').click(function(){
	    jQuery(this).hide();
	    jQuery('.wdm_next').show();
	    jQuery('.wdm_types').animate({
            scrollLeft: jQuery('.wdm_ct_tile:first-child').offset().left
        }, 800);
	});
});