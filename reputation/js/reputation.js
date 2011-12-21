/**
 * Reputation 
 * 
 * @author hcs
 * @copyright (C) 2011 hcs reputation extension for PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package reputation
 */
$(function() {

	var shown = false, cur_el = null;
	
	$("#brd-wrap").append('<table class="rep_popup"><tbody><tr><td id="topleft" class="corner"></td><td class="top"></td><td id="topright" class="corner"></td></tr><tr><td class="left"></td><td id="tooltip_content"></td><td class="right"></td></tr><tr><td class="corner" id="bottomleft"></td><td class="bottom"><span class="arrow"><!-- //--></span></td><td id="bottomright" class="corner"></td></tr></tbody></table>');
	var tip = $('.rep_popup').css('opacity', 0);

	function get(e, func){
		$.ajax({
			url: cur_el,
			type: "GET",
			cache: false,
			dataType: "json",
			timeout: 3000,
			
			success:function(data){
				$('#tooltip_content').html(data.message);
				func();
			},
			
			error: function(){
				$('#tooltip_content').html(data.message);
				func();
			}
		});		
		return false;
	}
	
	function show(e) {
		var h = $(e).attr('rel');
		if (shown) {
			if (h != cur_el)	{
				tip.css('display', 'none');
			}
			else {
				return hide();
			}
		}
		cur_el = h;
		
		var offset = $(e).offset();
		get(e, function(){
			tip.css( {
				top : offset.top - tip.height() + 10,
				left : offset.left - tip.width()/2 + $(e).width()/2,
				display : 'block'
			}).animate( {
				top : '-=10px',
				opacity : 1
			}, 250, 'swing', function() {
				shown = true;
			});
		});
		return false;
	}
	
	function hide(){
		if (!shown)
			return false;
		tip.animate( {
			top : '-=10px',
			opacity : 0
		}, 250, 'swing', function() {
			shown = false;
			tip.css('display', 'none');
		});
		return false;
	}
	
	$(document).ready(function(){
		$(".rep_plus_minus a").click(function(e){e.stopPropagation();show(this); return false});
		$('body').click(function(){hide();});
	});
});


var reputation = {
	response : {},
	url : '',

	updateTips: function(t) {
		$(".validateTips").html(t).addClass("ui-state-highlight");
		setTimeout(function() {
				$(".validateTips").removeClass("ui-state-highlight", 1500);
			},500
		);
	},
	
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
					reputation.updateTips(data.error);
					$("#rep_form_reason").addClass("ui-state-error");
					return;
				}
				
				if (data.message != undefined)
				{
					$("#rep_form").dialog("close");
					alert(data.message);
					return;
				}
				
				if (data.destination_url != undefined)
				{
					window.location = data.destination_url;
					return;
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
		$("#rep_form_description").html(data.description);
		$("#rep_form").dialog({
			height: "auto",
			width: 350,
			title : data.title,
			show: "fade",
			hide: "fade",
			resizable: false,
			modal: true,
			buttons: [{
				text: data.submit,
				click: function() {
					reputation.send_data();
				}
			}],
			close: function() {
				$(".validateTips").empty();
				$("#rep_form_reason").val("").removeClass("ui-state-error");
			}				
		});			
	},
	init:function(){
		$(document).ready(function(){
			$("#brd-wrap").append('<div id="rep_form" style="display:none;pading:0 0;"><p id="rep_form_description"></p><p class="validateTips"></p><textarea style="width:97%;height:118px" id="rep_form_reason" /></div><div id="rep_error" style="display:none;pading:0 0;"><p></p></div>');
			
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
							$("#rep_error").dialog({resizable: false});
							$("#rep_error p").html(data.message);
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
		});		
	}
}
reputation.init();