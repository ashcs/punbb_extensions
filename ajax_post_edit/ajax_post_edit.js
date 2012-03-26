PUNBB.env.ape = {

	textAreaHeight : 600, // height of edit box
	temp_post : '', // post message with html
	id : -1, // currently edited post id
	menu_hovered : false, // if menu is hovered
	update_values : '',
	url : PUNBB.env.base_url + 'extensions/ajax_post_edit/edit.php',	
	
	/*
	 * Sends request for get post message
	 */
	quick_edit : function(id) {
		// checking if link 'Quick Edit' exists, if not exists user probably
		// typed on adress bar javascript:PUNBB.env.ape.quick_edit(id)
		if ($('#edit' + id)) {
			// if other post is editing, cancel edit
			if (this.id != id && this.id != -1) {
				if (confirm("Are you sure you want to cancel last edit?"))
					this.cancel_edit(this.id);
				else {
					this.hide_menu();
					return;
				}
			}

			if (this.id != id) {
				this.hide_menu();
				this.id = id;
				this.temp_post = $('#post' + this.id).html();

				// Show loading info
				$('#post' + this.id).html(
						$('#post' + this.id).html()
								+ '<div style="float:right"><img src="'
								+ PUNBB.env.base_url
								+ 'extensions/ajax_post_edit/loading.gif"> '
								+ PUNBB.env.ape_vars['Loading'] + '</div>');

				var values = {
					action : 'get',
					id : this.id,
					csrf_token : PUNBB.env.ape_vars['csrf_token']
				};
				$.post(this.url, values, function(data) {
					PUNBB.env.ape.on_ready_get_post(data);
				});
			}
		}
	},

	/*
	 * Function executed after receiving post data
	 */
	on_ready_get_post : function(data) {
		var parsed_message = this.match(data, 'parsed_message');
		// If there aren't any errors
		if (parsed_message != '') {
			
			var entry_content_html = data.substring(0, data
					.indexOf('<!-- END FORM -->'));

			$('#post' + this.id).fadeOut('fast', function() {
				$(this).html(entry_content_html);

				$('#postedit').height(this.textAreaHeight + 'px');

				$('#post' + PUNBB.env.ape.id).fadeIn('fast', function() {
					$('#postedit').attr({'rows':8}).css({'width':'99%'}).focus();
				});
			});

			return 1;
		}

		if (data.substring(0, 12) == 'csrf_confirm') {
			response = data.split(':');
			if (confirm(response[1])) {
				PUNBB.env.ape_vars['csrf_token'] = response[2];

				var values = {
					action : 'get',
					id : this.id,
					csrf_token : response[2]
				};
				$.post(this.url, values, function(data) {
					PUNBB.env.ape.on_ready_get_post(data);
				});
			}
		} else
			alert(data);

		$('#post' + this.id).html(this.temp_post);
		id = -1;
	},

	/*
	 * Sends ajax request with typed message
	 */
	update_post : function() {
		$('#post_edit_form input, #post_edit_form textarea').attr('disabled',
				'disabled');

		this.update_values = {
			action : 'update',
			id : this.id,
			req_message : $('#postedit').val(),
			csrf_token : PUNBB.env.ape_vars['csrf_token']
		};

		if ($('#fldsilent'))
			this.update_values['silent'] = $('#fldsilent').attr('checked') ? 1 : 0;

		if ($('#req_subject'))
			this.update_values['req_subject'] = $('req_subject').val();

		$.post(this.url, this.update_values, function(data) {
			PUNBB.env.ape.on_ready_update_post(data);
		});

		// Show saving info
		$('#edit_info').show();
	},

	/*
	 * Function executed after receiving update request
	 */
	on_ready_update_post : function(data) {
		var message = this.match(data, 'message');
		if (message != '') {
			// Update post message for pun_quote extension
			try {
				pun_quote_posts[this.id] = $('#postedit').val;
			} catch (e) {
			}

			var last_edit = this.match(data, 'last_edit');

			var sig = '';

			if (this.temp_post.toLowerCase()
					.indexOf('<div class="sig-content">') != -1) // if post
																	// has
																	// signature
				sig = this.temp_post.substring(this.temp_post.toLowerCase()
						.indexOf('<div class="sig-content">')); // get it

			// display post
			$('#post' + this.id).fadeOut('fast', function() {
				$('#post' + PUNBB.env.ape.id).html(message + last_edit + sig);
				$('#post' + PUNBB.env.ape.id).fadeIn('fast');
				PUNBB.env.ape.id = -1;
			});
			return 1;
		}

		var error = this.match(data, 'error');
		// if message has errors
		if (error != '') {
			var cur_message = $('#postedit').val();

			// if recently displayed errors
			if (document.getElementById('edit-error')) {
				error = error.substring(error.indexOf('>') + 1);
				error = error.substring(0, error.lastIndexOf('</'));
				$('#edit-error').html(error);
			} else {
				if ($('#fldsilent'))
					var cur_silent = $('#fldsilent').checked;

				var html = $('#post' + this.id).html();
				var replace = html.substring(0, html.indexOf('<', 2));
				html = html.replace(replace, replace + error);
				$('#post' + this.id).html(html);

				$('#postedit').val(cur_message);
				if ($('#fldsilent'))
					$('#fldsilent').attr('checked', cur_silent);

			}
			$('#post_edit_form input, #post_edit_form textarea').removeAttr(
					'disabled');

			// Hide saving info
			$('#edit_info').hide();
		} else {
			if (data.substring(0, 12) == 'csrf_confirm') {
				response = data.split(':');
				if (confirm(response[1])) {
					PUNBB.env.ape_vars['csrf_token'] = response[2];
					this.update_values['csrf_token'] = response[2];
					$.post(this.url, this.update_values, function(data) {
						PUNBB.env.ape.on_ready_update_post(data);
					});
				} else {
					$('#post_edit_form input, #post_edit_form textarea')
							.removeAttr('disabled');

					// Hide saving info
					$('#edit_info').hide();
				}
			} else
				alert(data);
		}
	},

	/*
	 * Hides edit box and show recently used post content
	 */
	cancel_edit : function() {
		$('#post' + this.id).fadeOut('fast', function() {
			$('#post' + PUNBB.env.ape.id).html(PUNBB.env.ape.temp_post);
			$('#post' + PUNBB.env.ape.id).fadeIn('fast');
			PUNBB.env.ape.id = -1;
		});
	},

	/*
	 * Shows popup menu
	 */
	show_menu : function(id) {
		if (this.id == id)
			return;

		var pos = this.findPos(document.getElementById('menu' + id));
		pos[0] += document.getElementById('menu' + id).offsetWidth;
		pos[1] += document.getElementById('menu' + id).offsetHeight + 3;

		var menu = document.createElement('div');
		menu.setAttribute('id', 'menu');

		menu.style.left = pos[0] + 'px';
		menu.style.top = pos[1] + 'px';

		menu.className = 'popup';
		menu.onmouseover = function() {
			PUNBB.env.ape.menu_hovered = true;
		};
		menu.onmouseout = function() {
			PUNBB.env.ape.menu_hovered = false;
		};

		menu.innerHTML = '<ul>' + '<li><a id="edit' + id
				+ '" href="javascript:PUNBB.env.ape.quick_edit(' + id + ')">'
				+ PUNBB.env.ape_vars['Quick edit'] + '</a></li>' + '<li><a href="'
				+ PUNBB.env.base_url + PUNBB.env.ape_vars['url_edit'].replace('$1', id)
				+ '">' + PUNBB.env.ape_vars['Normal edit'] + '</a></li>' + '</ul>';

		document.body.appendChild(menu);

		$('#menu').fadeIn('fast');

		document.getElementById('menu').style.left = pos[0] + 4
				- document.getElementById('menu').clientWidth + 'px';

		document.onclick = function() {
			if (PUNBB.env.ape.menu_hovered == false)
				PUNBB.env.ape.hide_menu();
		};
	},

	/*
	 * Hides popup menu
	 */
	hide_menu : function() {
		$('#menu').fadeOut('fast', function() {
			$(this).remove();
		});
	},

	/*
	 * Returns obj absolute position [x,y]
	 */
	findPos : function(obj) {
		var curleft = curtop = 0;
		if (obj.offsetParent) {
			curleft = obj.offsetLeft;
			curtop = obj.offsetTop;
			while (obj = obj.offsetParent) {
				curleft += obj.offsetLeft;
				curtop += obj.offsetTop;
			}
		}
		return [ curleft, curtop ];
	},
	/*
	 * This function matches text from str beetwen <substr> and </substr>
	 */
	match : function(str, substr) {
		// if str contains <substr>
		if (str.indexOf('<' + substr + '>') != -1) {
			newstr = str.substring(str.indexOf('<' + substr + '>') + substr.length + 2);
			newstr = newstr.substring(0, newstr.indexOf('</' + substr + '>'));
			return newstr;
		} else
			return '';
	}
};