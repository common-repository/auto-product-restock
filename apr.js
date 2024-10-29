jQuery(document).ready(function($){
	$('.nwp_apr_option_metas').hide();

	if ( $('#_manage_stock').is(':checked') ){
		$('.nwp_apr_option_metas').show();
	}

	$('#_manage_stock').on('click', function(){
		if ( $('#_manage_stock').is(':checked') ){
			$('.nwp_apr_option_metas').show();
		}else{
			$('.nwp_apr_option_metas').hide();
		}
	});
});