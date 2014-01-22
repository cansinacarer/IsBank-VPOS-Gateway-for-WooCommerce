jQuery(document).ready(function($){
	$('#threeD').change(function() {
		if ($('#threeD').is(':checked')) {
			$('#3dSecureOdeme').show();
		} else {
		    $("#3dSecureOdeme").hide();
		} 
	});
});