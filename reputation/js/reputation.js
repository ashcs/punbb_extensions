/**
 * Reputation 
 * 
 * @author hcs
 * @copyright (C) 2016 hcs reputation extension for PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */



var reputation = {
	response : {},
	url : '',
	
	send_data: function() {

		$.ajax({
			url: reputation.url,
			type: "POST",
			cache: false,
			data: {csrf_token : reputation.response.csrf_token, form_sent : 1, req_message : $("#rep_form_reason").val()},
			dataType: "json",
			timeout: 3000,
			
			success:function(data){
				if (data.error != undefined)
				{
					$("#rep_form_reason").addClass("ui-state-error");
					return;
				}
				
				if (data.message != undefined)
				{
					$("#reputation-modal-container").modal('hide');
					//alert(data.message);
					return;
				}
				
				if (data.destination_url != undefined)
				{
					window.location = data.destination_url;
				}
				window.location.reload(true);
				return;
			},
			
			error: function(){
				alert('error!');
				window.location = reputation.url;
			}
			
		});
	},
	show_form: function(data) {
		$("#modal_action_info").text(data.info);
		if (data.action == 'plus') {
			var alert_class='alert-success';
		}
		else {
			var alert_class='alert-danger';
		}
		$("#modal_action_info").removeClass('alert-danger,alert-success').addClass(alert_class);
		$("#rep_form_reason").val('');
		$("#reputation-modal-container").modal('show');
	},
	init:function(){
		$(document).ready(function(){
			
			
			var cachedData = Array();

			function tooltipGetData(){
			    var element = $(this);

			    var id = element.data('id');

			    if(id in cachedData){
			        return cachedData[id];
			    }

			    cachedData[id] = "loading...";

			    $.ajax(element.data('url'), {
			        success: function(data){
			            cachedData[id] = data.message;
			            element.attr('data-original-title', data.message).tooltip('show')
			        }
			    });


			    return cachedData[id];
			}			
			
			
			
			$(".rep_info_link").click(function(){
				reputation.url = $(this).attr("href");
				$.ajax({
					url: reputation.url,
					type: "GET",
					cache: false,
					dataType: "json",
					timeout: 3000,
					
					success:function(data){
						if (data.code = -1 && data.message != undefined)
						{
							alert(data.message);
							return;
						}
						reputation.response = data;
						reputation.show_form(data);
					},
					
					error: function(){
						window.location = reputation.url;
					}
					
				});		
				return false;
			});
			$("#modal_save_reputation").click(reputation.send_data);
			$('[data-toggle="tooltip"]').tooltip({
			    title: tooltipGetData,
			    html: true,
			    container: 'body',				
			})

		});		
	}
}
reputation.init();

