$(document).on('opening', '[data-remodal-id=cron_config_remodal]', function () {
    $(this).find('div.alert').remove();
});

function get_cron_config(button_pressed) {
    var tr = button_pressed.closest('tr');
    var copy_cron = cron_command;

    var profile_id = tr.find('select.profile_id').val();

    if(profile_id == '') {
        open_manual_notification(cron_error_profile_id, 'warning', 'exclamation');
        return false;
    }

    var cron_description = $('div.cron_config_remodal_description').html();

    cron_arguments = 'action=cron_start profile_id='+profile_id;
    cron_link_to_exec_now = cron_link_to_exec_now.replace(/PROFILEID/g, profile_id);
    cron_link_to_exec_now = cron_link_to_exec_now.replace('&amp;', '&');
    cron_description = cron_description.replace(/CRON_PATH/g, cron_main_path);
    cron_description = cron_description.replace(/CRON_ARGUMENTS/g, cron_arguments);
    cron_description = cron_description.replace(/CRON_WGET_COMMAND/g, 'wget "'+cron_link_to_exec_now+'"');
    cron_description = cron_description.replace(/EXECUTE_PROFILE_NOW/g, cron_link_to_exec_now);

    var remodal_content = get_remodal_cron_config();
    remodal_content.html(cron_description);

    var inst = $('[data-remodal-id=cron_config_remodal]').remodal();
    inst.open();
}


function copy_text_to_clipboard(text) {
    text = text.replace(/&amp;/g, '&');
    remodal_notification(cron_command_copied, 'success', 'before');
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
}

function save_cron_configuration() {

    var cron_inputs_area = $('div[id^="tab-cron-"]');
    config_values = $('div[id^="tab-cron-"] input, div[id^="tab-cron-"] select');

    /*
    if($('div#tab-cron-jobs').length)
        config_values = $('div#tab-cron-jobs input, div#tab-cron-jobs select');
    else
        config_values = $('div#tab-cron-задания input, div#tab-cron-задания select');*/

    cron_inputs_area.find('input[type="checkbox"]').each(function(){
        if($(this).is(':checked')) {
            $(this).attr("checked", "checked");
        } else {
            $(this).removeAttr("checked");
        }
    });

    config_values_serialized = config_values.serialize();
    var request = $.ajax({
        url: cron_save_configuration_url,
        dataType: 'json',
        data: config_values_serialized,
        type: "POST",
        beforeSend: function (data) {
            ajax_loading_open();
        },
        success: function (data) {
            ajax_loading_close();
            if (data.error)
                open_manual_notification(data.message, 'warning', 'exclamation');
            else
                open_manual_notification(data.message, 'success', 'ok');
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajax_loading_close();
            open_manual_notification(thrownError, 'warning', 'exclamation');
        }
    });
}

function get_remodal_cron_config() {
    return $('[data-remodal-id=cron_config_remodal]').find('div.remodal_content');

}