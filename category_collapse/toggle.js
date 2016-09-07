if (typeof FORUM === "undefined" || !FORUM) {
	var FORUM = {};
}

function createCookie(name, value, days, path) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}


FORUM.toggleCats = function() {
	return {
		
		unique : function (list) {
		    var result = [];
		    $.each(list, function(i, e) {
		        if ($.inArray(e, result) == -1) result.push(e);
		    });
		    return result;
		},
		
		init : function() {
			$(".collapse_category_wrapper .main-head").css({
				cursor : "pointer"
			}).prepend('<p class="options toggler"><span><!-- --></span></p>');
			
			var listCollapsed = readCookie("collapsed_category");
			if (!listCollapsed || listCollapsed == undefined || listCollapsed.length < 1) {
				listCollapsed = new Array();
			} else {
				listCollapsed = listCollapsed.split(":");
				listCollapsed = $.map(listCollapsed, function(num) {
					return parseInt(num, 10);
				});
			}

			$(".collapse_category_wrapper .main-head").click(function() {
				var cookie = null;
				var c = $(this).closest('.collapse_category_wrapper');
				var cid = c.data('category-id');
				if (!cid) { return;	}
				var e = c.find(".main-category"); 
				if (c.hasClass("collapsed")) {
					e.show(function() {
						e.animate({height : "show"}, 100, "swing", function(){
							$(this).prev().animate({height : "show"}, 50, "swing");
						});
						c.removeClass("collapsed");
					});
					var index = listCollapsed.indexOf(cid);
					if (index >= 0) {
						listCollapsed.splice( index, 1 );
					}
				} else {
					e.animate({	height : "hide"}, 100, "swing",function(){
						$(this).prev().animate({height : "hide"	}, 50, "swing", function(){
							c.addClass("collapsed");
						});
					});
					listCollapsed.push(cid);
				}						
				if (listCollapsed.length > 0) {
					listCollapsed = FORUM.toggleCats.unique(listCollapsed)
					cookie = listCollapsed.join(":");
				} else {
					cookie = null;
				}
				createCookie("collapsed_category", cookie, 365, '/');						
			});
		}
	};
}();
jQuery(function() {
	FORUM.toggleCats.init();
});