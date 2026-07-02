$(function(){
  $.ajaxSetup({ cache: false });
  var container = _get_tab_export_import();
  if(container.length)
      check_profile_selected();
});

$(document).on('confirmation', '.download_libraries', function () {
    var button_confirm_text = remodal_button_confirm_get_text();
    var request = $.ajax({
		url: download_libraries_url,
		dataType: 'json',
		type: "POST",
		beforeSend: function(data) {
            remodal_button_confirm_loading_on();
		},
		success: function(data) {
		    remodal_button_confirm_loading_off(button_confirm_text);
		    if(data.error)
		        remodal_notification(data.message);
		    else {
                remodal_notification(data.message, 'success');
                setTimeout( function(){
					location.reload();
				}  , 2000 );
            }
		},
		error: function (xhr, ajaxOptions, thrownError) {
		    remodal_button_confirm_loading_off(button_confirm_text);
		    remodal_notification(libraries_download_error);
		}
	});
});

var time_to_check_progress = 250; //Miliseconds

function _get_tab_export_import() {
    return $('div#tab-export---import').length ? $('div#tab-export---import') : $('div#tab-Экспорт---Импорт');
}

$(document).on('cancellation', '.remodal.export_import_remodal_process', function () {
    cancel_process( text_process_cancelled_by_user);
    enable_launch_profile_button();
});

$(document).on('opening', '.remodal.export_import_remodal_process', function () {
    $(this).find('button.remodal-confirm').hide();
});

function launch_profile(empty) {
    ErrorHandler.disableAjaxError();

    var is_disabled = $('div.launch_profile.type_button a.button.icon_right').hasClass('disabled');
    if(is_disabled) return false;

    disable_launch_profile_button();

    process_dialog_show_cancel_button();

    var container = _get_tab_export_import();
    var profile_id = ie_pro_tab_get_current_profile();
    var from = container.find('input[name="import_xls_from"]').val();
    var to = container.find('input[name="import_xls_to"]').val();
    var empty = typeof empty !== 'undefined';

    var formData = new FormData();
    formData.append('profile_id', profile_id);
    formData.append('from', from);
    formData.append('to', to);
    formData.append('empty', empty);
    formData.append('file', container.find('input[name="upload"]')[0].files[0]);

    $.ajax({
        url: clean_progress_url,
        dataType: 'json',
        data: {},
        type: "POST",
        beforeSend: function (data) {
            ajax_loading_open();
            clean_remodal_progress();
        },
        success: function (data) {
            $.ajax({
                url: launch_profile_url,
                dataType: 'json',
                data: formData,
                type: "POST",
                processData: false,
                contentType: false,

                beforeSend: function (data) {
                    ajax_loading_close();
                    var inst = get_remodal_instance();
                    inst.open();
                    ajax_check_import_process();
                },

                success: function (data) {
                   ErrorHandler.enableAjaxError();
                },

                error: function (xhr, ajaxOptions, thrownError) {
                    cancel_process(xhr.responseText);
                    enable_launch_profile_button();

                    ErrorHandler.enableAjaxError();
                }
            });
        },

        error: function (xhr, ajaxOptions, thrownError) {
            alert('Internal error clearing previous progress.')
            ErrorHandler.enableAjaxError();
        }
    });
}

function clean_remodal_progress() {
    var panel_content = $('div.remodal.export_import_remodal_process').find('div.remodal_content');
    panel_content.html('');
}
function ie_pro_tab_get_current_profile() {
    var container = _get_tab_export_import();
    var profile_id = container.find('select[name="import_xls_profiles"]').val();
    return profile_id;
}
function get_remodal_instance() {
    return $('[data-remodal-id=export_import_remodal_process]').remodal();
}

function ajax_check_import_process() {
	$.getJSON(progress_route, function( data ) {
		if(data != null && data.length != 0) {
			if(data.message.length != 0)
				ajax_loading_put_progress(data.message);

            if (data.continue) {
                setTimeout(function () {
					ajax_check_import_process();
				}, time_to_check_progress);
            } else if(typeof data.continue != 'undefined' && !data.continue) {
				ajax_loading_put_progress(data.message);

            	if(data.redirect)
                    window.location = data.redirect;

                enable_launch_profile_button();
            	show_remodal_process_confirm_button();
			} else {
            	setTimeout(function () {
					ajax_check_import_process();
				}, time_to_check_progress);
			}
        } else {
			setTimeout(function () {
				ajax_check_import_process();
			}, time_to_check_progress);
		}
	}).error(function() {
		setTimeout(function () {
			ajax_check_import_process();
		}, time_to_check_progress);
	});
}

function ajax_loading_put_progress(content) {
	var panel_content = $('div.remodal.export_import_remodal_process').find('div.remodal_content');
	var type = typeof panel_content;
	panel_content.html('');
	$.each(content, function( index, value ) {
        panel_content.append(value + '<br>');
        panel_content.scrollTop = panel_content.scrollHeight;
        panel_content.animate({
            scrollTop: panel_content.get(0).scrollHeight
        }, 0);
	});
}

function show_remodal_process_confirm_button() {
    $('div.remodal.export_import_remodal_process').find('button.remodal-cancel').hide();
    $('div.remodal.export_import_remodal_process').find('button.remodal-confirm').show();
}

function process_dialog_show_cancel_button() {
    $('div.remodal.export_import_remodal_process').find('button.remodal-cancel').show();
}

function readURL(input) {
    var a_button = $(input).parent().find('a span');
	a_button.html('');
	var filename = input.val();
	filename = filename.replace('c:\\fakepath\\', '');
	filename = filename.replace('C:\\fakepath\\', '');
	filename = filename.replace('fakepath', '');

	a_button.html(' <b>('+filename+')</b>');
}

function check_profile_selected() {
    hide_all_profile_inputs();
    var profile_id = ie_pro_tab_get_current_profile();
    if(profile_id != '') {
        var request = $.ajax({
            url: profile_load_url,
            dataType: 'json',
            data: {profile_id: profile_id},
            type: "POST",
            beforeSend: function (data) {
                ajax_loading_open();
            },
            success: function (data) {
                ajax_loading_close();
                if (!data.error) {
                    var not_show = (data.type == 'import' && data.profile.import_xls_file_format == 'spreadsheet') || (data.type == 'import' &&  data.profile.import_xls_file_origin != 'manual');

                    if(!not_show)
                        show_profile_inputs(data.type);
                }
                else
                    open_manual_notification(data.message, 'warning', 'exclamation');

                enable_launch_profile_button();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                open_manual_notification(thrownError, 'warning', 'exclamation');
                ajax_loading_close();
            }
        });
    }
    else {
      disable_launch_profile_button();
    }
}

function hide_all_profile_inputs() {
    var container = _get_tab_export_import();
    container.find('.input_profile_export').each(function(){
        $(this).closest('div.form-group').hide();
    });
    container.find('.input_profile_import').each(function(){
        $(this).closest('div.form-group').hide();
    });
}

function show_profile_inputs(type) {
    var container = _get_tab_export_import();
    container.find('.input_profile_'+type).closest('div.form-group').show();
}

function cancel_process(error_message) {
    ErrorHandler.enableAjaxError();

    $.ajax({
        url: cancel_process_url,
        dataType: 'json',
        data: {'error' : error_message},
        type: 'POST'
    });
}

function enable_launch_profile_button(){
  get_launch_profile_button().removeClass( 'disabled').css( 'pointer-events', 'all');
}

function disable_launch_profile_button(){
  get_launch_profile_button().addClass( 'disabled').css( 'pointer-events', 'none');
}

function get_launch_profile_button(){
  return $('.launch_profile.type_button a');
}
