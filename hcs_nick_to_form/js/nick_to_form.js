
jQuery(function () {
	var title = $("#nick_to_form").attr("title");
	if ($("textarea[name=req_message]").size() > 0) {
		$(".posthead").each(function () {
			var el = $(this),
			el_id = parseInt($(el).attr("id").split("p")[1], 10) || 0,
			el_insert = $(el).find(".post-byline").find("a");
			if (el_id === 0) {
				return;
			}
			$('<a href="#reqmessage" id="paste_nick' +el_id+ '"  title="'+title+'" style="display:inline; margin-left: .5em; padding: 0 .5em;">').insertAfter(el_insert).html('&dArr;').click(function(){
				
				var postID = parseInt($(this).attr("id").split("paste_nick")[1], 10) || 0;
				name = $("#p"+postID).find(".post-byline").find("a").eq(0).text();
				if (name) {
					PUNBB.pun_bbcode.insert_text('[b]' +  name,'[/b], ');
					var msgfield = (document.all) ? document.all.req_message : ((document.getElementById('afocus') !== null) ? (document.getElementById('afocus').req_message) : (document.getElementsByName('req_message')[0]));
					if (msgfield.selectionStart)
						msgfield.selectionStart = msgfield.selectionStart + 7;						
				}
				return false;
			});
		});
	}
});
					
