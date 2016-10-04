/**
 *
 * Uploader
 * @copyright (C) 2016 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * Extension for PunBB (C) 2008-2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * 
 * based on jQuery.filer 
 * Copyright (c) 2015 CreativeDream
 * Website: https://github.com/CreativeDream/jquery.filer
 * Version: 1.0.5 (19-Nov-2015)
 * Requires: jQuery v1.7.1 or later
 * @license MIT Lincese
 */


if (typeof PUNBB.uploader.ready_files == "undefined"||!PUNBB.uploader.ready_files) 
{
	PUNBB.uploader.ready_files=null;
}

$('#filer_input').filer({
    changeInput: '<div class="jFiler-input-dragDrop"><div class="jFiler-input-inner"><div class="jFiler-input-icon"><i class="icon-jfi-cloud-up-o"></i></div><div class="jFiler-input-text"><h3>Drag&Drop files here</h3> <span style="display:inline-block; margin: 15px 0">or</span></div><a class="jFiler-input-choose-btn blue">Browse Files</a></div></div>',
    showThumbs: true,
    //theme: "dragdropbox",
    appendTo:"#uploader_preview_box",
    templates: {
    	item: '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-icon pull-left"><div class="jFiler-item-thumb">{{fi-image}}</div></div><div class="jFiler-item-info pull-left"><div class="jFiler-item-title" title="{{fi-name}}">{{fi-name | limitTo:30}}</div><div class="jFiler-item-others"><span>size: {{fi-size2}}</span><span>type: {{fi-extension}}</span><span class="jFiler-item-status">{{fi-progressBar}}</span></div><div class="jFiler-item-assets"><ul class="list-inline"><li><a class="icon-jfi-external-link jFiler-item-test-action"></a></li><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></li>',
    	itemAppend: '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-icon pull-left"><div class="jFiler-item-thumb">{{fi-image}}</div></div><div class="jFiler-item-info pull-left"><div class="jFiler-item-title">{{fi-name | limitTo:35}}</div><div class="jFiler-item-others"><span>size: {{fi-size2}}</span><span>type: {{fi-extension}}</span><span class="jFiler-item-status"></span></div><div class="jFiler-item-assets"><ul class="list-inline"><li><a class="icon-jfi-external-link jFiler-item-test-action"></a></li><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></li>',
    },
/*    
    templates: {
        box: '<ul class="jFiler-items-list jFiler-items-grid"></ul>',
        item: '<li class="jFiler-item">\
                    <div class="jFiler-item-container">\
                        <div class="jFiler-item-inner">\
                            <div class="jFiler-item-thumb">\
                                <div class="jFiler-item-status"></div>\
                                <div class="jFiler-item-info">\
                                    <span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span>\
                                    <span class="jFiler-item-others">{{fi-size2}}</span>\
                                </div>\
                                {{fi-image}}\
                            </div>\
                            <div class="jFiler-item-assets jFiler-row">\
                                <ul class="list-inline pull-left">\
                                    <li>{{fi-progressBar}}</li>\
                                </ul>\
                                <ul class="list-inline pull-right">\
                                    <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                </ul>\
                            </div>\
                        </div>\
                    </div>\
                </li>',
        itemAppend: '<li class="jFiler-item">\
                        <div class="jFiler-item-container">\
                            <div class="jFiler-item-inner">\
                                <div class="jFiler-item-thumb">\
                                    <div class="jFiler-item-status"></div>\
                                    <div class="jFiler-item-info">\
                                        <span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span>\
                                        <span class="jFiler-item-others">{{fi-size2}}</span>\
                                    </div>\
                                    {{fi-image}}\
                                </div>\
                                <div class="jFiler-item-assets jFiler-row">\
                                    <ul class="list-inline pull-left">\
                                        <li><span class="jFiler-item-others">{{fi-icon}}</span></li>\
                                    </ul>\
                                    <ul class="list-inline pull-right">\
                                        <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                    </ul>\
                                </div>\
                            </div>\
                        </div>\
                    </li>',
        progressBar: '<div class="bar"></div>',
        itemAppendToEnd: false,
        removeConfirmation: true,
        _selectors: {
            list: '.jFiler-items-list',
            item: '.jFiler-item',
            progressBar: '.bar',
            remove: '.jFiler-item-trash-action'
        }
    },
   */
    addMore: (PUNBB.uploader.ready_files) ? true : false,
    files: PUNBB.uploader.ready_files,    
    dragDrop: {
        dragEnter: null,
        dragLeave: null,
        drop: null,
    },
    uploadFile: {
        type: 'POST',
        enctype: 'multipart/form-data',
        beforeSend: function(){},
        success: function(data, el,l, p, o, s, cid){
        	if (data.result == 0) {
        		var parent = el.find(".jFiler-jProgressBar").parent();
        		var filerKit = $("#filer_input").prop("jFiler");
            
        		filerKit.files_list[cid].file.token = data.token;
        		filerKit.files_list[cid].file.id = data.file_id;
        		filerKit.files_list[cid].file.url = data.url;
        		filerKit.files_list[cid].file.thumbnail= data.thumbnail;
            
        		el.find(".jFiler-jProgressBar").fadeOut("slow", function(){
        			$("<div class=\"jFiler-item-others text-success\"><i class=\"icon-jfi-check-circle\"></i> Success</div>").hide().appendTo(parent).fadeIn("slow");    
        		});
            
        		PUNBB.uploader.click_handler(el.find('.jFiler-item-test-action'), cid);
        		
        		el.find('.jFiler-item-test-action').click();
        	}
        	if (data.result == -1) {
                var parent = el.find(".jFiler-jProgressBar").parent();
                el.find(".jFiler-jProgressBar").fadeOut("slow", function(){
                    $("<div class=\"jFiler-item-others text-error\"><i class=\"icon-jfi-minus-circle\"></i> Error! <span>"+data.error+"</span></div>").hide().appendTo(parent).fadeIn("slow");    
                });
                el.find('.jFiler-item-assets').hide();
        	}
        },
        error: function(el){
            var parent = el.find(".jFiler-jProgressBar").parent();
            el.find(".jFiler-jProgressBar").fadeOut("slow", function(){
                $("<div class=\"jFiler-item-others text-error\"><i class=\"icon-jfi-minus-circle\"></i> Error</div>").hide().appendTo(parent).fadeIn("slow");    
            });
        },
        statusCode: null,
        onProgress: null,
        onComplete: null
    },
    onSelect: function(){
    	if ($.modal != 'undefined' && $.modal)
    		$.modal.close();
    },
    onRemove: function(itemEl, file, id, listEl, boxEl, newInputEl, inputEl){
    	$.post(file.url,{csrf_token: file.token}, function(result, text){ },"json").error(function() { alert("Error! Please try again later!");  }).always(function(){})	    	
    }
});



//$(document).ready(function() {

	PUNBB.uploader.click_handler = function(e,i) {
    	var filerKit = $("#filer_input").prop("jFiler");
    	var file = filerKit.files_list[i].file;
    	if (!file.type.match('image.*')) {
    		$(e).hide();
    		return;
    	}
    	$(e).click(function(){
    		if (PUNBB.env.sceditor){
    			$("textarea[name=req_message]").sceditor('instance').wysiwygEditorInsertHtml('<img src="'+file.thumbnail+'" />');
    		}
    		else if (PUNBB.pun_bbcode) {
    			PUNBB.pun_bbcode.insert_text('[img]' +  file.thumbnail,'[/img]');
    			var msgfield = (document.all) ? document.all.req_message : ((document.getElementById('afocus') !== null) ? (document.getElementById('afocus').req_message) : (document.getElementsByName('req_message')[0]));
    			if (msgfield.selectionStart) {
    				msgfield.selectionStart = msgfield.selectionStart + 6;
    			}
    		}
    	});
    	//$(e).click();
	}
	
	$('.jFiler-item-test-action').each(function(){
    	PUNBB.uploader.click_handler(this, $(this).closest('li.jFiler-item').data('jfiler-index'));
	});
	
//});
	
