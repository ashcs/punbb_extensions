	function _save_handler(event) {
		var el = $(event.currentTarget)
		var btn = el.button('loading');
		var target = el.data('target');
		var target_result = el.data('result');
		var form = $(target);
		
		$(target + ' .hidden input[name=form_action]').remove()
		$(target + '_alert').remove();
		$(target + ' .hidden').append('<input type="hidden" name="form_action" value="'+el.val()+'" />')
		
		var el_data = '';
		
		$.each(el.data(), function(index, value){
			if (index.substr(0,4) == 'post') {
				el_data = el_data + '&' + index.substr(4).toLowerCase() + '=' + value;
			}
		});
		
		$.post(form.attr('action'), form.serialize() + el_data, function(res, status){ 
    		if(status == 'error'){
    			alert("Error! Please try again later!");
    		}else{
    			if(res.errors){
					var alertbox = $('<div id="' + target + '_alert" class="ct-box info-box alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>');
					if (res.head) {
						alertbox.append('<h4>' + res.head + '</h4>');
					}
					alertbox.hide().append(res.errors+'<br />');
					form.before(alertbox.addClass('bg-danger'));//.append(res.errors));
					alertbox.fadeIn(200).delay(12000).fadeTo(400, 0.1).slideUp(300, function() {
					    $(this).alert('close');
					});					
					
    			}else if (res.code == -2 ){
    				document.location.href = res.destination_url;
    			}else if(target_result) {
    				$(target_result).html( res );
    			}
    		}
    	},"json").error(function() { alert("Error! Please try again later!");  }).always(function(){btn.button('reset')});		
		return false;
	}	

$(document).ready(function(){
	$('#remote-modal-container').on('hidden.bs.modal', function(e) {
		$(this).removeData('bs.modal');
	});
	
	$('.btn-ajax').on('click',
		$.proxy(_save_handler, this)
	)
	
});