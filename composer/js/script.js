$(document).ready(function(){
	$("#output").append("Executing Started");
	$("#output").append("\nPlease wait...\n");
	$.post($('#composer-installer-form').data('composer-url'), {id: $('input[name="id"]').val(), csrf_token:   $('input[name="composer_token"]').val(), action: $('#composer-installer-form').data('composer-action')},
		function(data) {
			var i = data.indexOf('exec_result=');
			var code = data.substring(i+12);
			data = data.substring(0,i);
			$("#output").append(data);
			$("#output").append("Execution Ended");
			$("#o-container").animate({scrollTop: $("#o-container").get(0).scrollHeight}, 2000);
			
			if (code == 0) {
				$('input[name="finish"]').removeAttr('disabled');
			}
			else {
				$('input[name="install_cancel"]').css({display: 'block'});
				$('input[name="finish"]').hide();
			}
		}
	);
	
	$('input[name="finish"]').click(function(){

		$('input[name="finish"]').attr('disabled', 'disabled');
		if ($('#composer-installer-form').data('composer-action') == 'uninstall') {
			$.post(PUNBB.env.base_url + 'extensions/composer/main.php', {id: $('input[name="id"]').val(), csrf_token:   $('input[name="composer_token"]').val(), action: 'finish'}, 
			function(data) {
				$('input[name="uninstall_comply"]').click();
		    });
			return false;
		}
		
		$.post(PUNBB.env.base_url + 'extensions/composer/main.php', {id: $('input[name="id"]').val(), csrf_token:   $('input[name="composer_token"]').val(), action: 'finish'}, 
		function(data) {
	        $('input[name="install_comply"]').click()
	    });
		return false;
	});
});       
