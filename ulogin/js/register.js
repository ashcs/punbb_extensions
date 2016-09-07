$(document).ready(function(){
	$('input[name="req_email1"]').val(ulogin_data['email']).attr('readonly', 'readonly');
	$('input[name="req_username"]').val(ulogin_data['nickname']);
});