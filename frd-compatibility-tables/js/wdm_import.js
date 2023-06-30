jQuery(document).ready(function(){


	var stripesAnim;
	var calcPercent;

	var progress = jQuery('.progress-bar');
	var percent = jQuery('.percentage');
	var stripes = jQuery('.progress-stripes');
	stripes.text('////////////////////////');
	
	jQuery(window).load(function() {
		// Animate loader off screen
		jQuery(".se-pre-con").fadeOut("slow");
		jQuery(".se-pre-con").hide();
		console.log('Loading screenis hidden now');
	});
	jQuery(document).ready(function($) {
	    // Animate loader off screen
		$(".se-pre-con").fadeOut("slow");
		$(".se-pre-con").hide();
		console.log('Loading screen is hidden now');
	});


	function stripesAnimate() {
		animating();
		stripesAnim = setInterval(animating, 2500);
	}

	function animating() {
		stripes.animate({
			marginLeft: "-=30px"
		}, 2500, "linear").append('/');
	}

	jQuery(document).load(function(){
		jQuery('#hide_page').remove();
		jQuery('#display_page_popup').remove();
	});
	jQuery(document).on('click','#ui-id-2',function(){
		jQuery('#hide_page').remove();
		jQuery('#display_page_popup').remove();
	});

	var total_rows=0;
	var completed=0;
	jQuery( "#tabs" ).tabs();
	jQuery("#comp_table").DataTable();
	if(jQuery('.wdm_loader').length){
		jQuery('#tabs').css('text-align','center');
		// jQuery('#hide_functionality').show();
		// jQuery('#display_result_popup').show();
		// jQuery('.wdm_status_border').show();
		var total_rows=jQuery('.wdm_loader').data('count');
		if(total_rows > 1){
			jQuery('.loader').removeAttr('style');
			jQuery('.loding_img').removeAttr('style');
			jQuery('.loader').attr('class', 'loader blue');
			jQuery('span').hasClass('loaded') ? jQuery('span').attr('class', 'loaded blue') : jQuery('span').attr('class', 'blue');
			stripesAnimate();
			progress.animate({
				width: "10%"
			});
			var calls=Math.ceil(total_rows/1000);
			var data = {
				'action': 'wdm_import_data',
				'file': jQuery('.wdm_loader').data('file_name'),
				'start': 1
			};
			wdmSendAjaxCall(calls,data);
		}else{
			// jQuery('#hide_functionality').hide();
			// jQuery('#display_result_popup').hide();
			// jQuery('.wdm_status_border').hide();
			// swal("Something went wrong!", "Please check your CSV file.", "error");
			swal({
			  title: "Something went wrong!",
			  text: "Please check your CSV file.",
			  type: "error",
			  showCancelButton: false,
			  confirmButtonColor: "#DD6B55",
			  confirmButtonText: "OK",
			  closeOnConfirm: false
			},
			function(){
			  location.reload();
			});
		}
	}
	function wdmSendAjaxCall(calls,data){
		for(i=0;i<calls;i++){
			if(i<10){
				data['start']=(i===0) ? data['start'] : data['start']+1000;
					jQuery.post(ajax_object.ajax_url, data, function(response) {
						completed=completed+1000;
						var percentage = Math.ceil((completed/total_rows)*100);
						percent.text(percentage + '%');
						progress.animate({
							width: percentage + "%"
						});
						if(percentage>=100){
							var purge_data = {
								'action': 'wdm_clear_cache',
							};
							jQuery.post(ajax_object.ajax_url, purge_data , function(response) {
								swal({
								  title: "Data Imported!",
								  text: "Updated data in the database.",
								  type: "success",
								  showCancelButton: false,
								  confirmButtonColor: "#DD6B55",
								  confirmButtonText: "OK",
								  closeOnConfirm: false
								},
								function(){
								  location.reload();
								});
							});
						}
						// jQuery('.wdm_updated').text(percentage+'%)');
						// jQuery('.wdm_status').css('width',percentage+'%');
						if(i>8){
							data['start']=data['start']+1000;
							calls=calls-10;
							if(calls>0){
								wdmSendAjaxCall(calls,data);
							}
						}
					});
			}
		}
	}

	jQuery('.wdm_reset').on('click',function(){
		var data = {
				'action': 'wdm_delete_all_data',
			};
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			alert('deleted all the data from database.');
			location.reload();
		});
	});
	jQuery('.wdm_cached_page').select2();
});
