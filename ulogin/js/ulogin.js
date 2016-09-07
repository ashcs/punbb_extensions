function ulogin_callback(token){
	
	$.post($('.ulogin1').data('action'), {'token': token, 'csrf_token' : $('.ulogin1').data('csrf_token') }, function(res, status){ 
		if(status == 'error'){
			alert("Error! Please try again later!");
		}else{
			if(res.errors){
				
			}else if (res.code != undefined) {
				if (res.code == -2) {
					if (res.destination_url != undefined) {
						document.location.href = res.destination_url;
					}
				}
				if (res.code == -3) {
					alert('Invalid security token! Please reload page.');
					return false;
				}
			}
			
		}
	},"json").error(function() { alert("Error! Please try again later!");  }).always(function(){});		
	return false;
}