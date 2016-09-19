var alertbox = $('<div id="ulogin_alert_box" class="error-box"><h2 class="warn hn"></h2></div>').hide();
	
function ulogin_callback(token){
	alertbox.remove();
	$.post($('.ulogin1').data('action'), {'token': token, 'csrf_token' : $('.ulogin1').data('csrf_token') }, function(result, status){ 
		if(status == 'error'){
			alert("Error! Please try again later!");
		}else{
			process_response(result, $('#ulogin-network-available'));
		}
	},"json").error(function() { alert("Error! Please try again later!");  });		
	return false;
}

function process_response(res, area) {
	alertbox = $('<div id="ulogin_alert_box" class="error-box"><h2 class="warn hn"></h2></div>').hide();
	
	if(res.error != undefined){
		area.before(alertbox.addClass('alert-warning').append(res.error));
		alertbox.slideDown(300).delay(5000).fadeTo(700, 0).slideUp(300, function() {
		    $(this).remove();
		});		
	}
	if (res.code != undefined) {
		if (res.code == -2) {
			if (res.destination_url != undefined) {
				document.location.href = res.destination_url;
			}
		}
		if (res.code == -3) {
			alert('Invalid security token! Please reload page.');
			return false;
		}
		if (res.code == 0) {
			
			if (res.reload !== undefined) {
				document.location.reload();
				return;
			}
			
			if (res.message != undefined) {
				area.before(alertbox.addClass('alert-success').append(res.message));
				alertbox.slideDown(300).delay(5000).fadeTo(700, 0).slideUp(300, function() {
				    $(this).remove();
				});						
			}
			return false;
		}
	}
}

$(document).ready(function(){
	$('.reg-link.attached').click(function(){
		alertbox.remove();
		$.post($('.ulogin1').data('action'), {'delete': $(this).data('deletenetwork'), 'csrf_token' : $('.ulogin1').data('csrf_token') }, function(reselt, status){ 
			if(status == 'error'){
				alert("Error! Please try again later!");
			}else{
				process_response(result, $('#ulogin-network-attached'));
			}
		},"json").error(function() { alert("Error! Please try again later!");  });		
		return false;	
	});
});