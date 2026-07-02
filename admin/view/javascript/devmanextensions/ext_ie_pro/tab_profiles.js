$(function(){
    reset_profiles();
    show_active_profile();

    window.ServerInfo = JSON.parse( window.server_info.replaceAll( "'", '"'));

    init_tabs();
});

$(document).on('change', 'td.fields select[name*=field]', function(){
    show_hide_switch_button($(this));
});

$(document).ajaxStart( function(){
    disable_save_button();
});

function disable_profile_inputs() {
    if(typeof extension_version === 'undefined')
        return true;
    if(extension_version >= 875)
        return false;
}

function show_hide_switch_button(select){
    var value = $(select).val();

    if (value !== null && $.trim(value) !== '') {
        var valueArr = value.split('-');

        if (valueArr[4] !== undefined && valueArr[4] === 'allow_ids'){
            var optionSelected = $(select).children('option:selected')[0];
            if ($(optionSelected).attr('allow-ids') != "true"){
                $(select).parent().parent().find('div.switch-allow-ids input').prop("checked", false);
                $(select).parent().parent().parent().find('div.switch-allow-ids input').prop("checked", false);
            }
            else{
                $(select).parent().parent().find('div.switch-allow-ids input').prop("checked", true);
                $(select).parent().parent().parent().find('div.switch-allow-ids input').prop("checked", true);
            }
            $(select).parent().parent().children('div.switch-allow-ids').css("display", "block");
            $(select).parent().parent().parent().children('div.switch-allow-ids').css("display", "block");
        }
        else{
            $(select).parent().parent().children('div.switch-allow-ids').css("display", "none");
            $(select).parent().parent().parent().children('div.switch-allow-ids').css("display", "none");
        }
    }
}

function update_field_type($elm){
    var select = $elm.parent().parent().parent().find('select[name*=field]')[0];
    var optionSelected = $(select).children('option:selected')[0];
    var value = $(select).val();
    var valueArr = value.split('-');
    if ($($elm).prop('checked')){
        $(optionSelected).attr('allow-ids', true);
        valueArr[3] = 'number';
    }
    else{
        $(optionSelected).attr('allow-ids', false);
        valueArr[3] = 'string';
    }
    $(optionSelected).val(valueArr.join('-'));
    $(select).val(valueArr.join('-'));

    FilterManager.resetFilterRow( $(select).closest('tr'));
}

$(document).on('change', 'td.applyto select[name*=applyto]', function () {
    pre_filters_config_actions_applyto($(this))
});

function pre_filters_config_actions_applyto(select) {
    var inputNameArr = select.attr('name').split('[');
    var inputNameArr = inputNameArr[1].split(']');
    var index = inputNameArr[0];

    var actionsSelect = $('td.actions select[name="export_filter[' + index +'][action]"]');
    var apply_to = select.val();

    actionsSelect.find("option").each(function(){
        $(this).prop("disabled", false);
    });

    if(apply_to == 'shop' && get_i_want() != 'products') {
        actionsSelect.find('option[value="skip"]').prop('disabled', true);
    }

    if(get_i_want() != 'products') {
        actionsSelect.find('option[value="set_0"]').prop('disabled', true);
    }

    if(apply_to == 'file') {
        actionsSelect.val("skip");
        actionsSelect.find('option[value!="skip"]').prop('disabled', true);
    }

    actionsSelect.selectpicker('refresh');

    updateFieldNames(index, apply_to);
}

$(document).ajaxComplete(function( e, xhr, ajaxOptions) {
    ErrorHandler.checkResponse( xhr);

    $('td.applyto select[name*=applyto]').each(function(){
        var inputNameArr = $(this).attr('name').split('[');
        inputNameArr = inputNameArr[1].split(']');
        var index = inputNameArr[0];
        if ($(this).val() == 'shop'){
            var actionsSelect = $('td.actions select[name="export_filter[' + index +'][action]"]');
            actionsSelect.children('option').each(function () {
                if ($(this).val() == 'skip' && get_i_want() != 'products') {
                    $(this).prop('disabled', true);
                }
            });
            actionsSelect.selectpicker('refresh');
            updateFieldNames(index, 'shop');
        }
        else
            updateFieldNames(index, 'file');
    });

    //updating filters switch buttons
    $('td.fields select[name*=field]').each(function () {
        show_hide_switch_button($(this));
    })
});

function updateFieldNames(index, type){
    var select = $('td.fields select[name="export_filter[' + index + '][field]"]');
    var showOptionsProcessed = [];
    select.children('option').each(function(){
        $(this).show();
        var value = $(this).val();
        var valueArr = value.split('-');
        if (type == 'file'){
            var name = valueArr[2];
            name = name.split('_').join(' ');
            $(this).html(name);
        }
        else if (type == 'shop') {
            var html_name = jsUcfirst(valueArr[0]).split('_').join(' ') + ' - ' + jsUcfirst(valueArr[1]).split('_').join(' ') + ' (' + valueArr[3] + ')';
            var name = valueArr[0] + '-' + valueArr[1];
            $(this).html(html_name);
            if (showOptionsProcessed.indexOf(name) >= 0) {
                $(this).hide();
            }
            else{
                showOptionsProcessed.push(name);
            }

        }
    });
    select.selectpicker('refresh');
}

function jsUcfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

$(document).on('confirmation', '.remodal.profile_import_spreadsheet_remodal', function () {
    var button_confirm_text = remodal_button_confirm_get_text();

    var formData = new FormData();
    formData.append('file', $('input[name="spreadsheet_json"]')[0].files[0]);

    $.ajax({
        url: spread_sheet_upload_json,
        data: formData,
        type: "POST",
        dataType: 'json',
        processData: false,
        contentType: false,
        beforeSend: function(data) {
            remodal_button_confirm_loading_on();
        },
        success: function(data) {
            remodal_button_confirm_loading_off(button_confirm_text);
            if(data.error) {
                remodal_notification(data.message);
            } else {
                remodal_notification(data.message, 'success');
                setTimeout( function(){
                    location.reload();
                }  , 4000 );
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            remodal_button_confirm_loading_off(button_confirm_text);
            remodal_notification(thrownError);
        }
    });
});

$(document).on('change', '.configuration:not(.columns_configuration):not(.columns_fixed_configuration):not(.filters_configuration):not(.no_refresh_columns) input[type="checkbox"], .configuration:not(.columns_configuration):not(.filters_configuration):not(.columns_fixed_configuration):not(.sort_order_configuration):not(.no_refresh_columns):not(.categories_mapping_configuration) select', function( e) {
    if (e.target.name !== 'import_xls_categories_in_other_xml_node') {
      profile_get_columns_html();
    }
});

$(document).on('change', 'input[name="import_xls_category_tree"]', function() {
    check_cat_tree_no_tree_toogle();
});

var finishTypingInterval = 1000;
var typingTimer;
var inputs_update_columns = '.configuration:not(.profile_name):not(.main_configuration):not(.filters_configuration):not(.columns_fixed_configuration):not(.sort_order_configuration):not(.no_refresh_columns):not(.columns_configuration) input[type="text"]:not(.custom_name):not(.default_value):not(.conditional_value):not(.extra_column_configuration):not(.categories_file_upload_extras input)';
$(document).on('keyup', inputs_update_columns, function () {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(function (){
        profile_get_columns_html();
        if (get_current_profile() == 'import')
            profile_get_filters_html();
    }, finishTypingInterval);
});

$(document).on('keydown', inputs_update_columns, function () {
    clearTimeout(typingTimer);
});

function reset_profiles() {
    var tab_profiles = _get_tab_profiles();
    tab_profiles.find('.profile_import, .profile_export, .profile_import.configuration').hide();

    FilterManager.initialize();
}

function _get_tab_profiles() {
    var tab_profiles = $('div#profiles');

    if($('div#tab-profiles').length)
        tab_profiles = $('div#tab-profiles');
    else if($('div#tab-ÐŸÑ€Ð¾Ñ„Ð¸Ð»Ð¸').length)
        tab_profiles = $('div#tab-ÐŸÑ€Ð¾Ñ„Ð¸Ð»Ð¸');
    else if($('div.container_create_profile').length)
        tab_profiles = $('div.container_create_profile');

    return tab_profiles;
}

function profile_create(type, profile_id) {
    var tab_profiles = _get_tab_profiles();
    profile_id = typeof profile_id!= 'undefined' ? profile_id : '';
    reset_profiles();
    $('input[name="profile_type"]').val(type);
    $('input[name="profile_id"]').val(profile_id);

    if(profile_id != '') {
        $('select[name="import_xls_i_want"]').attr('disabled', 'disabled').selectpicker('refresh');
    } else {
        tab_profiles.find('select[name="import_xls_profiles"]').val('').selectpicker('refresh');
        $('select[name="import_xls_i_want"]').removeAttr('disabled').selectpicker('refresh');
        $('input[name="import_xls_multilanguage"], input[name="import_xls_category_tree"]').removeAttr('disabled');
        $('.profile_import.configuration.products input[type="text"]').removeAttr('disabled');
    }

    var tab_profiles = _get_tab_profiles();
    tab_profiles.find('.profile_'+type+':not(.configuration)').show();
    tab_profiles.find('.profile_'+type+'.main_configuration').show();

    if(profile_id != '') {
        $('.button_delete_profile.profile_'+type).show();
    } else {
        $('.button_delete_profile').hide();
    }

    profile_check_format();

    tab_profiles.find('.profile_import.spreadsheet_name, .profile_import.ftp, .profile_import.url').hide();
    if(type == 'import') {
        tab_profiles.find('.profile_export:not(.profile_import)').hide();
    } else {
        tab_profiles.find('.profile_import:not(.profile_export)').hide();
    }

    if(profile_id == '')
        profile_check_i_want();
}

function check_cat_tree_no_tree_toogle() {
    var checked = $('input[name="import_xls_category_tree"]').is(':checked');
    var container_cat_number = $('input#import_xls_cat_number').closest('div.form-group-columns');
    var container_cat_tree_parent_number = $('input#import_xls_cat_tree_number').closest('div.form-group-columns');
    var container_cat_tree_children_number = $('input#import_xls_cat_tree_children_number').closest('div.form-group-columns');
    var container_cat_tree_last_child_assign = $('input[name="import_xls_category_tree_last_child"]').closest('div.form-group-columns');
    container_cat_number.hide();
    container_cat_tree_parent_number.hide();
    container_cat_tree_children_number.hide();
    container_cat_tree_last_child_assign.hide();

    if(checked) {
        container_cat_tree_parent_number.show();
        container_cat_tree_children_number.show();
        //if(get_current_profile() == 'import')
        container_cat_tree_last_child_assign.show();
    } else {
        container_cat_number.show();
    }
}

function profile_load(select) {
    window.__load_data_errors = false;

    var profile_id = select.val();

    $('input[name="profile_id"]').val(profile_id);
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
                    profile_create(data.type, data.id);
                    $.each(data.profile, function (field_name, val) {
                        if (field_name != 'columns') {
                            var input = $('input[name="' + field_name + '"]');

                            if (input.length > 0) {
                                var type = input.attr('type');
                                if (type == 'text')
                                    input.val(val);
                                else if (type == 'checkbox') {
                                    if (val == 1)
                                        input.prop('checked', true);
                                    else
                                        input.prop('checked', false);
                                }
                            }
                            else {
                                var select = $('select[name="' + field_name + '"]');
                                if (select.length > 0) {
                                    select.val(val);
                                    select.selectpicker('refresh');
                                }
                                else {
                                    var select = $('select[name="' + field_name + '[]"]');
                                    if (select.length > 0) {
                                        select.val(val);
                                        select.selectpicker('refresh');
                                    }
                                    else {
                                        var textarea = $('textarea[name="' + field_name + '"]');
                                        if (textarea.length > 0)
                                            textarea.val(val);
                                    }
                                }
                            }
                        }
                    });
                    profile_check_i_want();

                    if (data.profile.import_xls_i_want == 'products' && disable_profile_inputs()) {
                        $('input[name="import_xls_multilanguage"], input[name="import_xls_category_tree"]').attr('disabled', 'disabled');
                        $('.profile_import.configuration.products input[type="text"]:not(#import_xls_profile_name):not(#import_xls_download_image_route)').attr('disabled', 'disabled');
                    }

                    enable_delete_button();
                    enable_download_profile_button();
                }
                else {
                    Notifications.warning( data.message);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                Notifications.warning( thrownError);
                ajax_loading_close();
            }
        });
    }
}

function profile_check_format(format) {
    var profile_type = get_current_profile();

    format = typeof format == 'undefined' ? get_current_format() : format;

    var tab_profiles = _get_tab_profiles();

    tab_profiles.find('.node_xml').hide();
    tab_profiles.find('.spreadsheet_name').hide();
    tab_profiles.find('.csv_separator').hide();
    tab_profiles.find('.force_utf8').hide();
    tab_profiles.find('.only_csv').hide();
    tab_profiles.find('.only_json').hide();

    tab_profiles.find('a[data-remodal-target="mapping_xml_columns"]').css('display', 'none');

    if(profile_type == 'import') {
        tab_profiles.find('.profile_import.file_origin').hide();
        if (format == 'spreadsheet') {
            tab_profiles.find('.profile_import.file_origin').hide();
        } else if (format != 'spreadsheet') {
            tab_profiles.find('.profile_import.file_origin').show();
        }
        if(format == 'xml')
            tab_profiles.find('a[data-remodal-target="mapping_xml_columns"]').css('display', 'block');

        profile_import_check_origin(tab_profiles.find('select[name="import_xls_file_origin"]').val());
    } else if(profile_type == 'export') {
        tab_profiles.find('.profile_export.file_destiny').hide();

        if (format == 'spreadsheet') {
            tab_profiles.find('.profile_export.file_destiny').hide();
        } else if (format != 'spreadsheet') {
            tab_profiles.find('.profile_export.file_destiny').show();
        }
        profile_export_check_destiny(tab_profiles.find('select[name="import_xls_file_destiny"]').val());
    }

    if(format == 'xml') {
        tab_profiles.find('.node_xml').show();
    } else if(format == 'spreadsheet') {
        tab_profiles.find('.spreadsheet_name').show();
    }else if(format == 'csv') {
        tab_profiles.find('.csv_separator').show();
        tab_profiles.find('.only_csv').show();
        if(profile_type == 'import')
            tab_profiles.find('.force_utf8').show();
    }

    tab_profiles.find('div.profile_'+profile_type+'.only_'+format).show();

    var categoriesFileUploadExtrasPanel = tab_profiles.find('.profile_import_mapping_categories_extra_fields_panel');

    if (format === 'xml') {
        categoriesFileUploadExtrasPanel.show();
    } else {
        categoriesFileUploadExtrasPanel.hide();
    }
}

function profile_import_check_origin(origin) {
    var format = get_current_format();
    var tab_profiles = _get_tab_profiles();
    tab_profiles.find('.profile_import.ftp, .profile_import.url').hide();
    if(origin == 'ftp' && format != 'spreadsheet')
        tab_profiles.find('.profile_import.ftp').show();
    else if(origin == 'url' && format != 'spreadsheet')
        tab_profiles.find('.profile_import.url').show();

    toggle_column_mappings_file_upload_by_origin_destiny( origin);
}

function profile_export_check_destiny(destiny) {
    var tab_profiles = _get_tab_profiles();
    tab_profiles.find('.profile_export.server, .profile_export.ftp').hide();
    if(destiny == 'server')
        tab_profiles.find('.profile_export.server').show();
    else if(destiny == 'external_server')
        tab_profiles.find('.profile_export.ftp').show();
}

function profile_check_i_want() {
    var type = get_current_profile();
    var tab_profiles = _get_tab_profiles();
    var i_want = get_i_want();
    tab_profiles.find('.profile_'+type+'.configuration').hide();
    tab_profiles.find('.profile_'+type+'.configuration.main_configuration').show();
    profile_check_format();
    if(i_want != '') {
        tab_profiles.find('.profile_' + type + '.configuration.' + i_want).show();
        tab_profiles.find('.profile_' + type + '.configuration.generic').show();
        profile_get_columns_html();
        profile_get_filters_html();
        if(type == 'export') {
            profile_get_columns_fixed_html();
            profile_get_sort_order_html();
        }
        else if (type === 'import'){
            profile_get_categories_mapping_html();
        }

        if($('div.legend_save_profile').next('div.container_step').css('display') == 'none')
            $('div.legend_save_profile legend').trigger('click');
    }
    if(i_want == 'products')
        check_cat_tree_no_tree_toogle();
}

function migration_profile_load( select) {
    var profile_id = select.val();

    if (profile_id === '') {
        clear_migration_profile_data();
    } else {
        ProfileManager.loadMigrationExport( profile_id)
                      .then( load_migration_profile_data);
    }
}

function clear_migration_profile_data( clearProfileSelector) {
    var profileIdField = get_profile_id_field();
    profileIdField.val( '');

    var migrationTab = get_migration_tab();
    var fields = migrationTab.find( 'input,select');

    fields.each( function( _, field) {
        if (clearProfileSelector || field.name !== 'import_xls_profiles') {
            update_field( $(field), '');
        }
    });
}

function load_migration_profile_data( data) {
    clear_migration_profile_data( false);

    var profileIdField = $('input[name="profile_id"]');
    profileIdField.val( data.id);

    data.profile.import_xls_format = data.profile.import_xls_file_format;

    var migrationTab = get_migration_tab();

    for (var fieldName in data.profile) {
        var field = migrationTab.find( 'input[name="' + fieldName + '"]');

        if (field.length === 0) {
            field = migrationTab.find( 'select[name="' + fieldName + '"]');
        }

        if (field.length === 1) {
            update_field( field, data.profile[fieldName]);
        }
    }
}

function update_field( field, value) {
    if (field[0].tagName === 'SELECT') {
        field.val( value);
        field.selectpicker( 'refresh');
    } else if (field.attr( 'type') === 'checkbox') {
        field[0].checked = (value !== '' && value !== 0);
    } else {
        field.val( value);
    }
}

function get_migration_tab() {
    return $('#tab-migrations-or-backups');
}

function get_current_format() {
    var format = $('select[name="import_xls_file_format"]').val();
    return format;
}

function get_current_origin() {
    return $('select[name="import_xls_file_origin"]').val();
}

function get_current_profile() {
    return $('input[name="profile_type"]').val();
}

function get_current_profile_id() {
    return get_profile_id_field().val();
}

function get_xml_item_node() {
    return $('input[name="import_xls_node_xml"]').val();
}

function get_profile_id_field() {
    return $('input[name="profile_id"]');
}

function profile_get_columns_html() {
    var type = get_current_profile();
    var i_want = get_i_want();
    var container = $('.columns_configuration');

    if (i_want != '') {
        var formData = get_current_form_data();

        if (type === 'export') {
          formData.append( 'import_xls_file_origin', file_destiny_to_origin());
        }

        if (get_current_format() === 'xml') {
            formData.append( 'import_xls_node_xml', get_xml_item_node());
        }

        $.ajax({
            url: get_columns_html_url,
            dataType: 'json',
            data: formData,
            type: 'POST',
            processData: false,
            contentType: false,

            beforeSend: function (data) {
                ajax_loading_open(container);
            },

            success: function (data) {
                ajax_loading_close( container);

                if (ErrorHandler.isValidResponseData( data))
                {
                    // hide_data_error();

                    container.html(data.html);
                    container.find('table').sortable({
                        containerSelector: 'table',
                        itemPath: '> tbody',
                        itemSelector: 'tr',
                        handle: 'i.fa-reorder',
                        placeholder: '<tr class="placeholder"/>'
                    });
                    container.find('select').selectpicker();
                    remodal_event(container);
                }
                else {
                    ErrorHandler.showErrorFromData( data);
                }
            },

            error: function (xhr) {
                ajax_loading_close(container);

                container.html(xhr.responseText);
            }
        });
    }
}

function profile_analyze_columns_html( button) {
    var i_want = get_i_want();

    if (i_want != '') {
        if (get_current_origin() === 'manual') {
            show_file_upload_dialog( function( file) {
               do_profile_analyze_columns( file);
            });
        } else {
            do_profile_analyze_columns();
        }
    }
}

function show_file_upload_dialog( callback) {
    var uploadFileField = $('<input type="file" style="display: none;">');
    $('body').append( uploadFileField);

    uploadFileField.on( 'change', function(){
        var file = uploadFileField[0].files[0];
        uploadFileField.remove();

        callback( file);
    });

    uploadFileField.click();
}

function do_profile_analyze_columns( uploadFile) {
    uploadFile = uploadFile || null;

    var type = get_current_profile();
    var container = $('.columns_configuration');

    var formData = get_current_form_data();

    formData.append( 'import_xls_analyze_columns', 1);

    if (type === 'export') {
        formData.append( 'import_xls_file_origin', file_destiny_to_origin());
    }

    if (get_current_format() === 'xml') {
        formData.append( 'import_xls_node_xml', get_xml_item_node());
    }

    if (uploadFile !== null) {
        formData.append( 'file', uploadFile);
    }

    $.ajax({
        url: get_columns_html_url,
        dataType: 'json',
        data: formData,
        type: 'POST',
        processData: false,
        contentType: false,

        beforeSend: function (data) {
            ajax_loading_open(container);
        },

        success: function (data) {
            ajax_loading_close( container);

            if (ErrorHandler.isValidResponseData( data))
            {
                // hide_data_error();

                container.html(data.html);
                container.find('table').sortable({
                    containerSelector: 'table',
                    itemPath: '> tbody',
                    itemSelector: 'tr',
                    handle: 'i.fa-reorder',
                    placeholder: '<tr class="placeholder"/>'
                });
                container.find('select').selectpicker();
                remodal_event(container);
            }
            else {
                ErrorHandler.showErrorFromData( data);
            }
        },

        error: function (xhr) {
            ajax_loading_close(container);

            container.html(xhr.responseText);
        }
    });
}

function remodal_event(selector) {

    $(selector).find('div.remodal').each(function(){
        var remodal_id = $(this).attr('data-remodal-id');
        if($('div.remodal-wrapper > div.'+remodal_id).length > 0) {
            $('div.remodal-wrapper > div.'+remodal_id).parent().remove();
        }
        $(this).remodal();
    });
}

function profile_get_filters_html() {
    FilterManager.getHtml();
}

function profile_get_columns_fixed_html() {
    if($('div.columns_fixed_configuration').length) {
        var selector = get_config_selector();
        var config_values = get_profile_configuration_values();
        var type = get_current_profile();
        var i_want = get_i_want();
        var profile_id = get_current_profile_id();
        var container = $('.columns_fixed_configuration');

        if (i_want != '') {
            $.ajax({
                url: get_columns_fixed_html_url,
                dataType: 'json',
                data: config_values,
                type: "POST",
                beforeSend: function (data) {
                    container.html('');
                    ajax_loading_open(container);
                },

                success: function (data) {
                    if (ErrorHandler.isValidResponseData( data))
                    {
                        // hide_data_error();

                        var selector = '.columns_fixed_configuration';
                        container.html(data.html);
                        remodal_event(container);
                        ajax_loading_close(container);
                    }
                    else {
                        ErrorHandler.showError( profile_data_error_custom_fixed_columns);
                    }
                },

                error: function (xhr) {
                    ajax_loading_close(container);
                    container.html(xhr.responseText);
                }
            });
        }
    }
}

$(document).on('change', 'select[name="export_sort_order[table_field]"]', function (){
    profile_get_sort_order_html();
});

function profile_get_sort_order_html() {
    var selector = get_config_selector();
    var config_values = get_profile_configuration_values();
    var type = get_current_profile();
    var i_want = get_i_want();
    var profile_id = get_current_profile_id();
    var container = $('.sort_order_configuration');

    config_values.add('select[name="export_sort_order[table_field]"], select[name="export_sort_order[sort_order]"]');

    if(i_want != '') {
        var request = $.ajax({
            url: get_sort_order_html_url,
            dataType: 'json',
            data: config_values,
            type: "POST",
            beforeSend: function (data) {
                ajax_loading_open(container);
            },
            success: function (data) {
                ajax_loading_open(container);

                if (ErrorHandler.isValidResponseData( data))
                {
                    // hide_data_error();

                    container.html(data.html);
                    container.find('select').selectpicker();
                }
                else {
                    ErrorHandler.showError( profile_data_error_sort_order);
                }
            },
            error: function (xhr) {
                ajax_loading_close( container);
                container.html(xhr.responseText);
            }
        });
    }
}

function profile_get_categories_mapping_html() {
    if (get_i_want() !== ''){
        ProfileManager.getCategoriesMappingHtml();
    }
}

function profile_add_column_fixed(button_pressed) {
    var model_row = button_pressed.closest('table').find('tr.custom_column_fixed_model');
    var filter_number = parseInt(model_row.attr('data-customcolumnfixednumber'));
    var clone = model_row.html();
    tr = clone.replaceAll('replace_by_number', (filter_number));

    table = $('div.columns_fixed_configuration table tbody');

    table.append('<tr>'+tr+'</tr>');

    button_pressed.closest('table').find('tr.custom_column_fixed_model').attr('data-customcolumnfixednumber', (filter_number+1));
}

function profile_remove_column_fixed(button_pressed) {
    button_pressed.closest('tr').remove();
}

function profile_get_custom_names_from_profile(select) {
    var profile_id = select.val();
    if(profile_id != '') {
        var request = $.ajax({
            url: get_columns_from_profile_url,
            dataType: 'json',
            data: {profile_id : profile_id},
            type: "POST",
            beforeSend: function (data) {
                ajax_loading_open();
            },
            success: function (data) {
                $.each(data, function( real_name, column_data ) {
                    real_name = real_name.replace(/"/g, '\\"');
                    $('div.columns_configuration').find('input[name="columns['+real_name+'][custom_name]"]').val(column_data.custom_name);
                    $('div.columns_configuration').find('input[name="columns['+real_name+'][default_value]"]').val(column_data.default_value);
                    $('div.columns_configuration').find('input[name="columns['+real_name+'][conditional_value]"]').val(column_data.conditional_value);
                    if(typeof column_data.true_value != 'undefined') {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][true_value]"]').val(column_data.true_value);
                    }
                    if(typeof column_data.false_value != 'undefined') {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][false_value]"]').val(column_data.false_value);
                    }

                    if(typeof column_data.product_id_identificator != 'undefined') {
                        $('div.columns_configuration').find('select[name="columns['+real_name+'][product_id_identificator]"]').val(column_data.product_id_identificator).selectpicker('refresh');;
                    }

                    if(typeof column_data.name_instead_id != 'undefined') {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][name_instead_id]"]').prop('checked', 'checked');
                    } else if($('div.columns_configuration').find('input[name="columns['+real_name+'][name_instead_id]"]').length) {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][name_instead_id]"]').prop('checked', false);
                    }

                    if(typeof column_data.id_instead_of_name != 'undefined') {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][id_instead_of_name]"]').prop('checked', 'checked');
                    } else if($('div.columns_configuration').find('input[name="columns['+real_name+'][id_instead_of_name]"]').length) {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][id_instead_of_name]"]').prop('checked', false);
                    }

                    if(typeof column_data.image_full_link != 'undefined') {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][image_full_link]"]').prop('checked', 'checked');
                    } else if($('div.columns_configuration').find('input[name="columns['+real_name+'][image_full_link]"]').length) {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][image_full_link]"]').prop('checked', false);
                    }

                    if(typeof column_data.status != 'undefined') {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][status]"]').prop('checked', 'checked');
                    } else if($('div.columns_configuration').find('input[name="columns['+real_name+'][status]"]').length) {
                        $('div.columns_configuration').find('input[name="columns['+real_name+'][status]"]').prop('checked', false);
                    }
                });
                ajax_loading_close();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert( thrownError);
                ajax_loading_close();
            }
        });
    }
}

String.prototype.replaceAll = function(searchStr, replaceStr) {
    var str = this;
    // no match exists in string?
    if(str.indexOf(searchStr) === -1) {
        // return string
        return str;
    }
    // replace and remove first match, and do another recursirve search/replace
    return (str.replace(searchStr, replaceStr)).replaceAll(searchStr, replaceStr);
}

function get_config_selector() {
    var type = get_current_profile();
    var i_want = get_i_want();
    var tab_profiles = _get_tab_profiles();
    var selector = '.profile_'+type+'.'+i_want;
    return selector;
}

function get_profile_configuration_values() {
    var selector = get_config_selector();
    var tab_profiles = _get_tab_profiles();
    var type = get_current_profile();

    var i_want = get_i_want();

    if(i_want != '') {
        find_string = selector + ' input[type=checkbox]:checked, ';
        find_string += selector + ' input[type=text], ';
        find_string += selector + ' input[type=hidden], ';
        find_string += selector + ' select, input[type="hidden"], ';

        find_string += ' .profile_' + type + '.main_configuration input[type=checkbox]:checked, ';
        find_string += ' .profile_' + type + '.main_configuration input[type="text"], ';
        find_string += ' .profile_' + type + '.main_configuration input[type="hidden"], ';
        find_string += ' .profile_' + type + '.main_configuration select,';

        find_string += ' .profile_' + type + '.configuration.generic input[type=checkbox]:checked, ';
        find_string += ' .profile_' + type + '.configuration.generic input[type="text"], ';
        find_string += ' .profile_' + type + '.configuration.generic input[type="hidden"], ';
        find_string += ' .profile_' + type + '.configuration.generic select';

        var config_values = tab_profiles.find(find_string);

        return config_values;
    } else {
        return false;
    }
}

function profile_check_uncheck_all(checkbox) {
    var checked = checkbox.is(':checked');
    var table = checkbox.closest('table').find('tbody');
    table.find('input[type="checkbox"]').prop('checked', checked);
}

function profile_disable_non_named_columns( button) {
    var table = button.closest('table').find('tbody');

    table.find('input[type="checkbox"]').each( function( _, item){
        var el = $(item);
        var row = el.parents( 'tr');
        var fields = row.find( '.custom_name');

        if (fields.length === 1) {
            var field = $(fields[0]);

            if (field.val().trim() === '') {
                el.prop('checked', false);
            }
        }
    });
}

function get_i_want() {
    var type = get_current_profile();
    return $('.profile_'+type).find('select[name="import_xls_i_want"]').val();
}

function profile_delete() {
    get_profile_delete_confirm_remodal().open();
}

function profile_download() {
    ProfileManager.download( get_current_profile_id());
}

function profile_upload( input) {
    ProfileManager.upload( input[0].files[0])
                  .fail( function( error) {
                              input.val( '');

                              Notifications.error( error);
                          });
}

function profile_save(type) {
    if (type === 'migration-export') {
        profile_save_migration_export( type);
    } else {
        profile_save_import_export( type);
    }
}

function profile_save_import_export( type) {
    var errors = FilterManager.validateFilters();

    if (errors !== null)
    {
        insert_error(errors, 'div.profile_export.type_button a.button');
        insert_error(errors, 'div.profile_import.type_button a.button');
    }
    else
    {
        var i_want = get_i_want();

        if (i_want != '') {
            var config_values = get_profile_configuration_values();
            remove_disabled_from_all_inputs();

            config_values = FilterManager.fixFiltersConfig( config_values);

            var request = $.ajax({
                url: profile_save_url,
                dataType: 'json',
                data: config_values,
                type: "POST",
                beforeSend: function (data) {
                    ajax_loading_open();
                },
                success: function (data) {
                    if (data.error) {
                        ajax_loading_close();
                        Notifications.warning( data.message);
                    }
                    else
                        location.reload();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    ajax_loading_close();

                    Notifications.warning( thrownError);
                }
            });
        } else {
            Notifications.warning( profile_error_uncompleted);
        }
    }
}

function profile_save_migration_export() {
    ProfileManager.saveMigrationExport()
                  .then( function( data) {
                    if ('profile_updated' in data) {
                        Notifications.success( 'Migration/Backup profile successfully saved.');
                    } else {
                        window.location.reload();
                    }
                  })
                  .fail( Notifications.error);
}

function insert_error(message, container, position, class_custom, icon_class)
{
	var container = $(container);

	if(typeof  position == 'undefined') class_custom = 'danger';
	if(typeof  position == 'undefined') position = 'after';
	if(typeof  icon_class == 'undefined') icon_class = 'exclamation';

	container.children('div.alert').remove();
	container.next('div.alert').remove();
	container.prev('div.alert').remove();

	var error_message = '<div class="alert alert-'+class_custom+'">';
    error_message += '<i class="fa fa-'+icon_class+ '-circle fa-2x"></i><br>';
    error_message += message;
    error_message += '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';

    if(position == 'after')
	    container.after(error_message);
    else if(position == 'before')
	    container.before(error_message);
    else if(position == 'prepend')
	    container.prepend(error_message);
}

function remove_disabled_from_all_inputs() {
    var container = _get_tab_profiles();
    container.find('select:disabled, input:disabled, textarea:disabled').removeAttr('disabled');
}

$(document).on('ready', function(){
    $('div.container_create_profile_steps legend').on('click', function () {
        profile_toggle_step($(this));
    })
});

function profile_toggle_step(legend_pressed) {
    var container_step = legend_pressed.closest('div.form-group').nextAll('div.container_step').first();
    if(container_step.is(':visible'))
        legend_pressed.removeClass('opened');
    else
        legend_pressed.addClass('opened');
    container_step.slideToggle('fast');
}

function profile_reset_steps() {
    $('div.container_create_profile_steps legend').each(function(){
        $(this).removeClass('opened');
    });
    $('div.container_create_profile_steps div.container_step').each(function(){
        $(this).hide();
    });
}

function enable_save_button(){
    return enable_button( get_save_button());
}

function disable_save_button(){
    return disable_button( get_save_button());
}

function enable_delete_button(){
    return enable_button( get_delete_button());
}

function enable_download_profile_button(){
    return enable_button( get_download_profile_button());
}

function enable_button( button){
    return button.removeClass( 'disabled')
                 .css( 'pointer-events', 'all');
}

function disable_button( button){
    return button.addClass( 'disabled')
                 .css( 'pointer-events', 'none');
}

function get_save_button(){
    return $('.profile_import.type_button a.button');
}

function get_delete_button(){
    return $('.delete_profile');
}

function get_download_profile_button(){
    return $('.download_profile');
}

function get_load_categories_mapping_columns_button(){
    return $('.profile_import.categories_mapping_configuration .button_categories_mapping');
}

function file_destiny_to_origin() {
    var destinyField = $('.profile_export.file_destiny.main_configuration select[name="import_xls_file_destiny"]');
    var destiny = destinyField.val();

    result = destiny === 'download' ? 'manual' : destiny;

    return result;
}

function is_integer( value){
    return /^\d+$/.test( value);
}

function is_valid_date( value){
    var result = false;
    value = $.trim(value);

    // Reconocemos fechas con formato: año-mes-dia, y opcionalmente con horas:minutos:segundos
    var matches = /^(\d+)\-(\d+)\-(\d+)(\s+(\d{2}):(\d{2}):(\d{2}))?$/.exec( value);

    if (matches !== null && matches.length === 8)
    {
        var matchesDateTime = typeof matches[4] !== 'undefined';

        var year = matches[1];
        var month = +matches[2] - 1;
        var day = +matches[3];

        if (year.length === 2)
        {
            year = '20' + year;
        }

        year = +year;

        var hours = minutes = seconds = 0;

        if (matchesDateTime)
        {
            hours = +matches[5];
            minutes = +matches[6];
            seconds = +matches[7];
        }

        // Para chequear que sea una fecha correcta
        // (años bisiestos, febrero > 29 dias, meses con 31/30 dias, etc) creamos una instancia
        // de Date, que "se mueve" automaticamete hasta una fecha valida.
        // La fecha corregida por Date debe ser igual a value, sino es una fecha incorrecta.
        var date = new Date( year, month, day, hours, minutes, seconds);

        var result = date.getDate() === day &&
                     date.getMonth() === month &&
                     date.getFullYear() === year;

        if (result && matchesDateTime)
        {
            result = date.getHours() === hours &&
                     date.getMinutes() === minutes &&
                     date.getSeconds() === seconds;
        }
    }

    return result;
}

function get_profile_delete_confirm_remodal(){
    if (!window.__profile_delete_confirmation_remodal){
        window.__profile_delete_confirmation_remodal = build_profile_delete_confirm_remodal();
    }

    return window.__profile_delete_confirmation_remodal;
}

function build_profile_delete_confirm_remodal(){
    var result = $('[data-remodal-id=profile_delete_confirm_remodal]').remodal();
    profile_delete_confirm_remodal_set_events();

    return result;
}

function profile_delete_confirm_remodal_set_events(){
    $(document).on('confirmation', '.profile_delete_confirm_remodal', function(){
        $.ajax({
            url: profile_delete_url,
            dataType: 'json',
            data: {profile_id : get_current_profile_id()},
            type: "POST",

            beforeSend: function () {
                ajax_loading_open();
            },

            success: function (data) {
                if (data.error) {
                    ajax_loading_close();

                    Notifications.warning( data.message);
                }
                else {
                    location.reload();
                }
            },

            error: function (_, _, thrownError) {
                ajax_loading_close();
                Notifications.warning( thrownError);
            }
        });
    });
}

function profile_get_main_xml_nodes() {
    if (get_current_origin() === 'manual') {
        show_file_upload_dialog( function( file) {
            do_profile_get_main_xml_nodes( file);
        });
    } else {
        do_profile_get_main_xml_nodes();
    }
}

function do_profile_get_main_xml_nodes( file) {
    XmlAnalyzer.getMainNodes( file);
}

function has_file_upload( field) {
    return field.length > 0 &&
           'files' in field[0] &&
           field[0].files.length > 0;
}

function update_get_categories_upload_field(){
    var format = get_current_format();
    var origin = get_current_origin();

    var fileInput = $('.profile_import.configuration.categories_mapping_configuration input[name="categories_mapping_file"]');

    var inputCt = fileInput.closest( 'div');

    if (origin === 'manual'){
        inputCt.show();
    } else {
        inputCt.hide();
    }
}

function update_get_columns_upload_field(){
    var format = get_current_format();
    var origin = get_current_origin();

    var fileInput = $('.profile_import.configuration.columns_configuration input[name="columns_mapping_file"]');
    var inputCt = fileInput.closest( 'div');

    if (origin === 'manual'){
        inputCt.show();
    } else {
        inputCt.hide();
    }
}

function profile_open_save_section(){
    var legend = $('div.legend_save_profile legend');

    if (!legend.hasClass( 'opened'))
    {
        legend.trigger('click');
    }
}

function update_categories_mapping_upload_button(){
    var uploadFileButton = get_load_categories_mapping_columns_button()

    if (can_load_categories_mapping_columns()){
        enable_button( uploadFileButton);
    } else {
        disable_button( uploadFileButton);
    }
}

function is_checked( field){
    return $(field).is(':checked');
}

function is_visible( field){
    return $(field).is(':visible');
}

function profile_get_categories_mapping_columns_html(){
    var i_want = get_i_want();

    if (i_want != '') {
        if (get_current_origin() === 'manual') {
            show_file_upload_dialog( function( file) {
               do_profile_analyze_categories( file);
            });
        } else {
            do_profile_analyze_categories();
        }
    }
}

function do_profile_analyze_categories( uploadFile) {
    var config_fields = get_categories_mapping_columns_fields().toArray();
    var container = $('.categories_mapping_columns');

    var formData = new FormData();

    config_fields = config_fields.concat([
        '.main_configuration.type_text input[name="import_xls_node_xml"]',
        '.main_configuration.type_text input[name="import_xls_url"]',
        '.main_configuration.type_text input[name="import_xls_csv_separator"]',
        '.main_configuration.type_boolean input[name="import_xls_file_without_columns"]',
        '.main_configuration.type_text input[name="import_xls_ftp_host"]',
        '.main_configuration.type_text input[name="import_xls_ftp_username"]',
        '.main_configuration.type_text input[name="import_xls_ftp_password"]',
        '.main_configuration.type_text input[name="import_xls_ftp_port"]',
        '.main_configuration.type_text input[name="import_xls_ftp_path"]',
        '.main_configuration.type_text input[name="import_xls_ftp_file"]',
        '.main_configuration.type_boolean input[name="import_xls_ftp_passive_mode"]',
        '.configuration.type_boolean input[name="import_xls_multilanguage"]',
        '.main_configuration select[name="import_xls_http_authentication"]',
        '.main_configuration input[name="import_xls_http_username"]',
        '.main_configuration input[name="import_xls_http_password"]'

    ]);

    var categoriesInOtherNode = $('input[name="import_xls_categories_in_other_xml_node"]');

    if (categoriesInOtherNode[0].checked) {
        config_fields = config_fields.concat([
            '.categories_file_upload_extras input[name="import_xls_categories_node_xml"]',
            '.categories_file_upload_extras input[name="import_xls_category_id_attribute"]',
            '.categories_file_upload_extras input[name="import_xls_category_parent_id_attribute"]',
            '.categories_file_upload_extras input[name="import_xls_category_value_attribute"]'
        ]);
    }

    var categoryTreeCheckbox = $('.profile_import.configuration.type_boolean input[name="import_xls_category_tree"]');

    if (is_checked( categoryTreeCheckbox)) {
        config_fields = config_fields.concat([
            categoryTreeCheckbox,
            '.profile_import.configuration.type_text input[name="import_xls_cat_tree_number"]',
            '.profile_import.configuration.type_text input[name="import_xls_cat_tree_children_number"]'
        ]);
    } else {
        config_fields.push( '.profile_import.configuration.type_text input[name="import_xls_cat_number"]');
    }

    config_fields.forEach( function( field){
        add_field_to_form_data( formData, field);
    });

    uploadFile = uploadFile || null;

    if (uploadFile !== null) {
        formData.append( 'file', uploadFile);
    }

    $.ajax({
        url: get_categories_mapping_columns_html_url,
        dataType: 'json',
        data: formData,
        type: 'POST',
        processData: false,
        contentType: false,

        beforeSend: function( data) {
            ajax_loading_open( container);
        },

        success: function (data) {
            if (ErrorHandler.isValidResponseData( data))
            {
                // hide_data_error();
                ajax_loading_close( container);

                container.html( data.html);
                container.find( 'select').selectpicker();

                init_autocomplete();
            }
            else {
                ajax_loading_close( container);
                ErrorHandler.showErrorFromData( data);
            }
        },

        error: function( xhr) {
            ajax_loading_close(container);
            container.html( xhr.responseText);
        }
    });
}

function add_field_to_form_data( formData, fieldOrSelector){
    var field = $(fieldOrSelector);
    var fieldEl = field[0];
    var value = field.val();

    if (fieldEl.tagName === 'INPUT' &&
        fieldEl.type === 'checkbox') {
        value = fieldEl.checked ? 1 : 0;
    }

    formData.append( field.attr( 'name'), value);
}

function get_categories_mapping_columns_fields(){
    var fieldSelectors = get_columns_mapping_field_selectors();
    fieldSelectors.push( '.profile_import.main_configuration.configuration select');

    var cssSelector = fieldSelectors.join( ',');
    var tab_profiles = _get_tab_profiles();

    return tab_profiles.find( cssSelector);
}

function get_columns_mapping_field_selectors(){
    var result = [];
    var selectorPrefix = '.profile_import.columns_configuration';

    result.push( selectorPrefix + ' input[type=checkbox]:checked');
    result.push( selectorPrefix + ' input[type="text"]');
    result.push( selectorPrefix + ' input[type="hidden"]');
    result.push( selectorPrefix + ' select');

    return result;
}

function toggle_categories_file_upload_extras_form( e) {
    if (e.target.checked) {
        $('.categories_file_upload_extras').show();
    } else {
        $('.categories_file_upload_extras').hide();
    }
}

function show_active_profile() {
    var profileId = UrlHashManager.get( 'profile_id');

    if (profileId !== null) {
        var profileSelect = $('.container_select_profile select[name="import_xls_profiles"]');
        profileSelect.val( profileId);

        profile_load( profileSelect);
    }

    var showUploadMessage = UrlHashManager.get( 'show_upload_message') == '1';

    if (showUploadMessage) {
        open_manual_notification( profile_import_profile_upload_successful, 'success', 'exclamation');

        UrlHashManager.remove( 'show_upload_message');
    }
}

function toggle_column_mappings_file_upload_by_origin_destiny( originOrDestiny) {
    var column_mappings_file_upload_panel = get_column_mappings_file_upload_panel();

    if (originOrDestiny === 'manual' || originOrDestiny === 'download') {
        column_mappings_file_upload_panel.show();
    } else {
        column_mappings_file_upload_panel.hide();
    }
}

function get_column_mappings_file_upload_panel( ) {
    return $('.columns_mapping_file_upload');
}

function select_column_name( select) {
    var value = select.val();
    var fieldName = select.data( 'field-name');
    var field = $('input[name="' + fieldName + '"]');

    if (value === 'manual-select') {
        field.show();
    } else {
        field.hide();
        field.val( value);
    }
}

function filter_by_prefix( prefix, listName) {
    var result = [];

    if (listName in window) {
        var prefixLower = $.trim(prefix.toLowerCase());

        result = window[listName].filter( function( item){
            var itemLower = item.label.toLowerCase();

            return itemLower !== prefixLower &&
                   itemLower.indexOf( prefixLower) === 0;
        });
    }

    return result;
}

function profile_import_xml_main_node_selected( select) {
    var value = select.val();
    var fieldName = select.data( 'field-name');
    var field = $('input[name="' + fieldName + '"]');

    if (value === 'manual-select') {
        field.show();
    } else {
        field.hide();
        field.val( value);
    }
}

function get_current_form_data() {
    var formData = new FormData();
    var values = get_form_values();

    values.each( function( _, field){
        add_field_to_form_data( formData, field);
    });

    return formData;
}

function get_form_values() {
    var selector = '.profile_import '; //get_config_selector();
    var tab_profiles = _get_tab_profiles();
    var type = get_current_profile();

    find_string = selector + ' input[type=checkbox]:checked, ';
    find_string += selector + ' input[type=text], ';
    find_string += selector + ' input[type=hidden], ';
    find_string += selector + ' select, input[type="hidden"], ';

    find_string += ' .profile_' + type + '.main_configuration input[type=checkbox]:checked, ';
    find_string += ' .profile_' + type + '.main_configuration input[type="text"], ';
    find_string += ' .profile_' + type + '.main_configuration input[type="hidden"], ';
    find_string += ' .profile_' + type + '.main_configuration select,';

    find_string += ' .profile_' + type + '.configuration.generic input[type=checkbox]:checked, ';
    find_string += ' .profile_' + type + '.configuration.generic input[type="text"], ';
    find_string += ' .profile_' + type + '.configuration.generic input[type="hidden"], ';
    find_string += ' .profile_' + type + '.configuration.generic select';

    return tab_profiles.find(find_string);
}

function init_tabs() {
    $('.nav.nav-tabs a[data-toggle="tab"]').on( 'click', function( e) {
        var url = $(e.target).attr( 'href');
        var hashIndex = url.lastIndexOf( '#');
        var hash = url.substring( hashIndex);

        window.location.hash = hash;
    });

    select_active_tab();
}

function select_active_tab() {
    var hash = window.location.hash.trim();

    if (hash !== '' && hash !== '#') {
        var tab = $('a[href="' + hash + '"]');

        if (tab.length === 1) {
            tab.click();
        }
    }
}

function sprintf( formatText /*, values... */){
    var values = Array.prototype.slice.call( arguments, 1);
    var index = 0;

    var result = formatText.replace( /%s/g, function( match){
       return values[index++];
    });

    return result;
}

var FilterManager = {
    setInitialFilters( filters) {
        this.initialFilters = filters || null;
    },

    initialize() {
        this.initialFilters = null;
        this.exprBuilder = new ExpressionBuilder();
    },

    getHtml() {
        if (window.get_i_want() !== '') {
            this.loadFiltersData();
        }
    },

    validateFilters() {
        var errors = this.exprBuilder.validate();

        return errors.length > 0 ? errors.join('<br>') : null;
    },

    fixFiltersConfig( config_values) {
        var result;

        if (this.isImportProfile()) {
            result = config_values.serialize();
        } else {
            config_values = config_values.filter( function( index, el){
                return el.name.indexOf( 'export_filter[') !== 0;
            });

            result = config_values.serialize();

            if (!this.exprBuilder.isEmpty()) {
                result += '&filters_v2=' + this.exprBuilder.serialize();
            }
        }

        return result;
    },

    loadFiltersData() {
        var configValues = window.get_profile_configuration_values();
        this.container = this.getFiltersContainer();

        $.ajax({
            url: window.get_filters_html_url,
            dataType: 'json',
            data: configValues,
            type: 'POST',

            beforeSend: this.initDataLoad.bind( this),
            success: this.onDataLoadSuccess.bind( this),
            error: this.onDataLoadError.bind( this)
        });
    },

    initDataLoad() {
        this.container.html('');

        window.ajax_loading_open( this.container);
    },

    onDataLoadSuccess( data) {
        if (ErrorHandler.isValidResponseData( data)) {
            this.showTableWithData( data);

            window.ajax_loading_close(this.container);
        } else {
            ErrorHandler.showError( profile_data_error_filters);
        }
    },

    onDataLoadError( xhr) {
        window.ajax_loading_close( this.container);

        this.container.html( xhr.responseText);
    },

    showTableWithData( data) {
        this.container.html( data.html);
        this.container.find( 'select').selectpicker();

        var filterTable = this.container.find( 'table tbody');

        filterTable.find('tr:not(.filter_model)').each( function( _, el) {
            this.resetFilterRow( $(el));
        }.bind( this));
    },

    refreshFieldSelector( fieldSelect, config) {
        if (config.field) {
            fieldSelect.val( config.field);
            fieldSelect.selectpicker('refresh');
        }
    },

    resetFilterRow( tableRow) {
        var rowFields = tableRow.find( 'td.fields');
        tableRow.find('td.conditionals > div, td.values > input').hide();
        tableRow.find('td.values > div').hide();

        var fieldValue;

        if (this.isImportProfile()) {
          fieldValue = rowFields.find('select').val();
        } else {
            var selects = rowFields.find('select');
            var fieldSelect = selects.length === 2
                              ? $(selects[1])
                              : $(selects[0]);
            fieldValue = fieldSelect.val();
        }

        var fieldValueSplitted = fieldValue.split('-');

        var type = this.isImportProfile()
                   ? fieldValueSplitted[3]
                   : fieldValueSplitted[2];

        tableRow.find('td.conditionals > div.conditional.' + type).show();

        if (type !== 'boolean') {
            tableRow.find('td.values > input').show();
        }

        this.toggleMainConditional();
    },

    toggleMainConditional() {
        var filterTable = this.getFilterTable();
        var filterNumber = filterTable.find('tbody tr:not(.filter_model)').length;
        var footer = filterTable.find('tfoot');

        if (filterNumber > 0) {
            footer.show();
        } else {
            footer.hide();
        }
    },

    addFilter( buttonPressed) {
        if (this.isImportProfile()) {
            this.importAddFilter( buttonPressed);
        } else {
            this.exportAddFilter( buttonPressed);
        }
    },

    importAddFilter( buttonPressed) {
        var modelRow = buttonPressed.closest('table').find('tr.filter_model');
        var filterNumber = parseInt( modelRow.attr('data-filternumber'));
        var clone = modelRow.html();

        var rowContent = clone.replaceAll( 'replace_by_number', filterNumber);

        var table = this.getFilterTable().find( 'tbody');
        table.append('<tr>' + rowContent + '</tr>');

        this.resetAllFields( table);

        buttonPressed.closest('table')
                     .find('tr.filter_model')
                     .attr('data-filternumber', filterNumber + 1);

        pre_filters_config_actions_applyto($("div.filters_configuration").find("td.applyto select[name*=applyto]").last());

        this.toggleMainConditional();
    },

    exportAddFilter() {
        var footer = this.getFilterTable().find( 'tfoot');

        var joiner = footer.is(':visible')
                     ? footer.find( 'select[name="export_filter[main_conditional]"]').val()
                     : null;

        this.addNewFilterRow({
            joiner: joiner
        });
    },

    resetAllFields( table) {
        var lastRow = table.find('tr').last();
        lastRow.find('.btn.dropdown-toggle').remove();
        lastRow.find('select.selectpicker').selectpicker();

        this.resetFilterRow( lastRow);
    },

    addFilterWithData( filterCfg, joiner, openGroup) {
        this.addNewFilterRow({
            field: filterCfg.field,
            comparator: filterCfg.comparator,
            value: filterCfg.value,
            joiner: joiner,
            openGroup: openGroup
        });
    },

    openGroup() {
        var footer = this.getFilterTable().find( 'tfoot');

        var joiner = footer.is(':visible')
                     ? footer.find( 'select[name="export_filter[main_conditional]"]').val()
                     : null;

        this.addNewFilterRow({
            joiner: joiner,
            openGroup: true
        });
    },

    closeGroup(){
        var filterTable = this.getFilterTable();

        this.exprBuilder.dedent();

        if (this.exprBuilder.getIndent() === 0) {
            this.disableElement( filterTable.find( '.close_group'));
        }
    },

    removeFilter( buttonPressed) {
        if (this.isExportProfile()) {
            var filterItem = buttonPressed.data('filterItem');

            this.exprBuilder.remove( filterItem);

            this.updateExpressionView();
            this.rebuildFiltersTable();
        }
        else {
            buttonPressed.closest('tr').remove();
        }

        this.toggleMainConditional();
        this.hideExpressionViewIfNeeded();
    },

    addNewFilterRow( config) {
        var newRow = this.createFilterRow();

        this.toggleMainConditional();

        var fieldSelect = newRow.find('td.fields select.selectpicker');
        var conditionSelect = newRow.find('td.conditionals select.selectpicker');
        var valueTxt = newRow.find('td.values input');

        this.refreshFieldSelector( fieldSelect, config);
        this.resetFilterRow( newRow);
        this.updateConditionSelector( conditionSelect, config);
        this.updateFilterValue( valueTxt, config);
        this.configChangeEvents( fieldSelect, conditionSelect, valueTxt);

        valueTxt.focus();

        var joiner = typeof config.joiner === 'undefined' ? null : config.joiner;

        if (config.openGroup) {
            this.exprBuilder.indent();
        }

        var filterItem = this.exprBuilder.add({
          fieldEl: fieldSelect,
          conditionEl: conditionSelect,
          valueEl: valueTxt,
        }, joiner);

        var removeBtn = newRow.find('td.remove > a');
        removeBtn.data( 'filterItem', filterItem);

        if (joiner) {
            this.addFilterWithJoiner( newRow, joiner, filterItem);
        }

        this.updateExpressionView();
        this.updateGroupVisuals( newRow, config);

        $('.expression_view').show();
    },

    createFilterRow() {
        var filterTable = this.getFilterTable();
        var modelRow = filterTable.find('tr.filter_model');
        var filterNumber = parseInt( modelRow.attr('data-filternumber'));
        var rowContent = modelRow.html().replaceAll('replace_by_number', filterNumber);

        var tbody = filterTable.find('tbody');
        tbody.append('<tr>' + rowContent + '</tr>');

        modelRow.attr( 'data-filternumber', filterNumber + 1);

        var result = tbody.find('tr').last();

        result.find('.btn.dropdown-toggle').remove();
        result.find('select.selectpicker').selectpicker();

        return result;
    },

    updateGroupVisuals( filterRow, config) {
        var padding = this.exprBuilder.getIndent() * 5 + '%';
        var fieldCt = filterRow.find('td.fields > div:first');
        fieldCt.css('padding-left', padding);

        this.setGroupBackgroundColor( filterRow);

        var filterTable = this.getFilterTable();
        this.enableElement( filterTable.find( '.open_group'));

        if (config.openGroup) {
            this.enableElement( filterTable.find( '.close_group'));
        }
    },

    updateConditionSelector( conditionSelect, config) {
        if (config.comparator) {
            comparator = this.getComparatorByFieldType( config)

            conditionSelect.val( comparator);
            conditionSelect.selectpicker('refresh');
        }
    },

    updateFilterValue( valueTxt, config) {
        if (typeof config.value !== 'undefined') {
            valueTxt.val( config.value);
        }
    },

    configChangeEvents( fieldSelect, conditionSelect, valueTxt) {
        fieldSelect.on( 'change', this.updateExpressionView.bind( this));
        conditionSelect.on( 'change', this.updateExpressionView.bind( this));
        valueTxt.on( 'keyup', this.updateExpressionView.bind( this));
    },

    getComparatorByFieldType( config) {
        var result = config.comparator;
        var fieldType = this.getFieldType( config.field);

        switch (fieldType) {
            case 'boolean':
                result = config.value === 'TRUE' ? 1 : 0;
                break;

            case 'string':
            case 'date':
                result = (result === 'NOT LIKE' ? 'NOT_LIKE' : result).toLowerCase();
                break;
        }

        return result;
    },

    getFieldType( fieldName){
        var parts = fieldName.split( '-');

        return parts[2];
    },

    updateExpressionView() {
        console.log( this.exprBuilder.toString())
        $('.filter-expr-text').html( this.exprBuilder.toHtml());
    },

    rebuildFiltersTable() {
        this.initialFilters = this.buildInitialFilters();
        this.exprBuilder.clear();

        this.clearFiltersTable();
        this.buildFiltersTable();
    },

    addFilterWithJoiner( filterRow, joiner, filterItem){
        var fieldSelectCt = this.getFilterSelectContainer(
            filterRow.find('td.fields select.selectpicker')
        );

        this.buildFilterSelectPicker( filterItem, joiner, filterRow, fieldSelectCt);

        fieldSelectCt.css( 'width', '75%');
    },

    getFilterSelectContainer( fieldSelect) {
        var result = fieldSelect.closest('.bootstrap-select');
        var fieldSelectCtParent = result.parent();

        if (!fieldSelectCtParent.hasClass( 'fields')) {
            result = fieldSelectCtParent;
        }

        return result;
    },

    buildFilterSelectPicker( filterItem, joiner, filterRow, fieldSelectCt) {
        var joinFieldCt = $('<div></div>');
        filterRow.find( 'td.fields').append( joinFieldCt);
        joinFieldCt.append( fieldSelectCt);

        var result = $('<select class="selectpicker"> <option>AND</option> <option>OR</option> </select>');
        result.insertBefore( fieldSelectCt).selectpicker();
        result.selectpicker('val', joiner);

        result.closest( '.bootstrap-select').css({
            width: '23%',
            marginRight: '5px'
        }).on( 'change', function( e){
            filterItem.joiner = $(e.target).val();

            this.updateExpressionView();
        }.bind( this));
    },

    setGroupBackgroundColor( rowInserted) {
        var level = this.exprBuilder.getIndent();

        if (level > 0) {
            rowInserted.css( 'background-color', this.getColorByLevel( level));
        }
    },

    getColorByLevel( level) {
        var rgb_colors = [
            'bde0ff',
            'ffddbd',
            'dbbdff',
            'ffbdfd',
            'dacba5',
            'c4f8ef',
            'c8f4d5',
            'fffad2',
            '4d493e',
        ];

        var result = rgb_colors[level] != undefined ? rgb_colors[level] : 'bde0ff';

        return '#' + result;
    },

    buildInitialFilters() {
        var filtersParser = new FiltersParser( this.exprBuilder);

        return filtersParser.parse();
    },

    clearFiltersTable() {
        var filterTable = this.getFilterTable();
        var filterRows = filterTable.find( 'tbody tr[class!="filter_model"]');

        filterRows.remove();

        this.disableElement( filterTable.find( '.open_group'));
        this.disableElement( filterTable.find( '.close_group'));
    },

    buildFiltersTable() {
        if (this.initialFilters !== null) {
            var filters = this.initialFilters;
            var joiner = null;
            var i = 0;

            while (i < filters.length) {
                var filter = filters[i++];

                if (typeof filter === 'string') {
                    var openGroup = false;
                    var joiner = filter;

                    if (filter === 'CLOSE_GROUP') {
                        this.exprBuilder.dedent();
                    }

                    filter = filters[i++];

                    if (filter === 'OPEN_GROUP') {
                        openGroup = true;
                        filter = filters[i++];
                    }

                    if (joiner !== 'CLOSE_GROUP') {
                        this.addFilterWithData( filter, joiner, openGroup);
                    } else if (['AND', 'OR'].includes( filter)) {
                        i--;
                    }
                } else {
                    this.addFilterWithData( filter);
                }
            }

            this.updateExpressionView();

            this.initialFilters = null;

            this.exprBuilder.indentToLatest();
        }
    },

    allFiltersToEndAreClosingGroup( index) {
        var filters = this.initialFilters;
        var i = index;

        while (i < filters.length && filters[i] === 'CLOSE_GROUP') {
            i++;
        }

        return i === filters.length;
    },

    toggleExpressionView(){
        var exprView = $('.expression_view .filter-expr-text');
        var panelIcon = $('.expression_view i');

        if (exprView.is( ':visible')) {
          exprView.hide();

          panelIcon.removeClass( 'fa-angle-up');
          panelIcon.addClass( 'fa-angle-down');
        } else {
          exprView.show();

          panelIcon.removeClass( 'fa-angle-down');
          panelIcon.addClass( 'fa-angle-up');
        }
    },

    getFiltersContainer() {
        return $('.filters_configuration');
    },

    getFilterTable() {
        return $('div.filters_configuration table');
    },

    hideExpressionViewIfNeeded() {
        if (this.isExportProfile() && this.exprBuilder.isEmpty()) {
            $('.expression_view').hide();
            $('.expression_view .filter-expr-text').hide();
        }
    },

    isImportProfile(){
        return window.get_current_profile() === 'import';
    },

    isExportProfile(){
        return window.get_current_profile() === 'export';
    },

    enableElement( el){
        el.removeClass( 'disabled');
    },

    disableElement( el){
        el.addClass( 'disabled');
    }
};

function FiltersParser( exprBuilder) {
    this.exprBuilder = exprBuilder;
}

FiltersParser.prototype = {
    parse() {
        this.result = [];

        this.filters = this.exprBuilder.toExprList();
        this.index = 0;
        this.openGroups = 0;

        while (this.index < this.filters.length) {
            this.parseToken( this.filters[this.index]);

            this.index++;
        }

        return this.result;
    },

    parseToken( token) {
        if (token === '(') {
            this.parseOpenParen();
        }
        else if (token === ')') {
            this.parseClosedParen();
        }
        else if (['AND', 'OR'].includes( token)) {
            this.parseAndOr( token);
        }
        else {
            this.parseOther();
        }
    },

    parseOpenParen() {
        if (this.result.length > 0) {
            this.result.push( 'OPEN_GROUP');
            this.openGroups++;
        }
    },

    parseClosedParen() {
        if (this.openGroups > 0) {
            this.result.push( 'CLOSE_GROUP');
            this.openGroups--;
        }
    },

    parseAndOr( token) {
        var field = this.filters[++this.index];
        var originalField = field;

        if (field === '(') {
            this.result.push( token);
        }

        while (field === '(') {
            this.result.push( 'OPEN_GROUP');
            this.openGroups++;

            field = this.filters[++this.index];
        }

        var fieldParts = field.split( '-');
        var type = fieldParts[2];
        var comparator = this.filters[++this.index];
        var value = this.filters[++this.index];

        if (value.trim() !== '') {
            if (type === 'number') {
                value = +value.replace( '"', '');
                value = !is_integer( value) ? 0 : value;
            } else {
                value = this.extractQuotedText( value);
            }
        }

        if (originalField !== '(') {
            this.result.push( token);
        }

        this.result.push({
            field: field,
            type: type,
            comparator: comparator,
            value: value
        });
    },

    parseOther() {
        var field = this.filters[this.index];
        var fieldParts = field.split( '-');
        var type = fieldParts[2];
        var comparator = this.filters[++this.index];
        var value = this.filters[++this.index];

        if (value.trim() !== '') {
            if (type === 'number') {
                value = +value.replace( '"', '');
            } else {
                value = this.extractQuotedText( value);
            }
        }

        this.result.push({
            field: field,
            comparator: comparator,
            value: value,
            type: type
        });
    },

    extractQuotedText( value) {
        while (value[0] === '"' && value[value.length - 1] === '"') {
            value = value.substring( 1, value.length - 1);
        }

        return value;
    }
};

var ErrorHandler = {
    activeError: false,
    showAjaxError: true,

    init: function() {
        $(document).ajaxError( this._onAjaxError.bind( this));
        $(document).ajaxStop( this._onAjaxStop.bind( this));
    },

    showError: function( errorMessage) {
        if (!this.activeError) {
            Notifications.warning( errorMessage);
            $('div.alert')[0].scrollIntoView();

            this.activeError = true;
        }
    },

    showErrorFromData: function( data) {
        var errorMessage = null;

        if ('message' in data) {
            errorMessage = data.message;
        } else if ('html' in data) {
            errorMessage = data.html;
        } else {
            errorMessage = data;
        }

        this.showError( errorMessage);
    },

    showUnexpectedDataError: function() {
        this.showError( profile_unexpected_data_error);
    },

    disableAjaxError: function() {
        this.showAjaxError = false;
    },

    enableAjaxError: function() {
        this.showAjaxError = true;
    },

    checkResponse: function( xhr) {
        var errorMessage = this.getErrorMessage( xhr);

        if (errorMessage) {
            this.showError( errorMessage);
        }
    },

    getErrorMessage: function( xhr) {
        var result = null;

        if (typeof xhr.responseJSON !== 'undefined' &&
            typeof xhr.responseJSON.html !== 'undefined')
        {
            if (!this.isValidResponseData( xhr.responseJSON))
            {
                result = profile_unexpected_data_error;
            }
        }
        else if (this.isErrorResponse( xhr.responseText))
        {
            result = this._getResponseMessage( xhr.responseText);
        }

        return result;
    },

    isValidResponseData: function( data){
        return typeof data.html !== 'undefined' &&
               !this.isErrorResponse( data.html);
    },

    isErrorResponse: function( text){
        text = this._getResponseMessage( text);

        return text !== null && (text.indexOf( '<b>Fatal') === 0 ||
                                 text.indexOf( '<b>Error') === 0 ||
                                 text.indexOf( '<b>Warning') === 0 ||
                                 text.indexOf( '<b>Notice') === 0);
    },

    _getResponseMessage: function( text) {
        var result = text || null;

        try {
            var json = JSON.parse( result);

            if ('message' in json) {
                result = json.message
            } else {
                result = json.html;
            }
        }
        catch (e) {
        }

        if (result !== null) {
           result = $.trim( result);
        }

        return result;
    },

    _onAjaxError: function( _, xhr) {
        if (this.showAjaxError) {
            var errorText = $.trim(xhr.responseText);

            // Ignoramos los responses en blanco del server,
            // a veces pasa ejecutando un profile (no se por que)
            // pero no debemos considerarlos error
            if (errorText !== '') {
                if (errorText.indexOf( '<html') === 0) {
                    this._showResourceOverloadingError();
                } else {
                    this.showUnexpectedDataError();
                }
            }
        }
    },

    _onAjaxStop: function( e) {
        enable_save_button();

        this.activeError = false;
    },

    _showResourceOverloadingError: function() {
        var tpl = new Template( profile_error_server_limits_overloaded);

        this.showError( tpl.render( ServerInfo));
    }
};

ErrorHandler.init();

var Notifications = {
    success: function( message) {
        open_manual_notification( message, 'success', 'exclamation');
    },

    warning: function( message) {
        open_manual_notification( message, 'warning', 'exclamation');
    },

    error: function( message) {
        open_manual_notification( message, 'warning', 'exclamation');
    }
}

var ProfileManager = {
    upload: function( fileUpload, errorCallback) {
        var formData = new FormData();
        formData.append( 'file', fileUpload);

        return ApiRequest.post( profile_upload_url, formData)
                         .then( this._onUploadSuccess.bind( this));
    },

    download: function( id) {
        ApiRequest.post( profile_download_url, { profile_id: id })
                  .then( this._onDownloadSuccess.bind( this))
                  .fail( this._onError.bind( this));
    },

    getCategoriesMappingHtml: function() {
        this._categoriesMappingContainer = $('.categories_mapping_configuration');
        this._categoriesMappingContainer.html( '');

        ApiRequest.post(
            get_categories_mapping_html_url,
            get_profile_configuration_values(),
            this._categoriesMappingContainer
        )
        .then( this._onCategoriesMappingHtmlSuccess.bind( this))
        .fail( this._onCategoriesMappingHtmlError.bind( this));
    },

    saveMigrationExport: function() {
        var values = this._getMigrationProfileValues();

        return ApiRequest.post( profile_save_url, values)
                         .fail( this._onError.bind( this));
    },

    loadMigrationExport: function( profile_id) {
        return ApiRequest.post( profile_load_url, {profile_id: profile_id})
                         .fail( this._onError.bind( this));
    },


    _onUploadSuccess: function( data) {
        if (data.profile_id) {
            UrlHashManager.setParameters({
                profile_id: data.profile_id,
                show_upload_message: 1
            });

            window.location.reload();
        }
    },

    _onDownloadSuccess: function( data) {
        if (data.redirect) {
            window.location = data.redirect;

            open_manual_notification( profile_import_profile_download_successful, 'success', 'exclamation');
        }

        return $.Deferred().resolve( data);
    },

    _onCategoriesMappingHtmlSuccess: function( data) {
        if (ErrorHandler.isValidResponseData( data)) {
            this._categoriesMappingContainer.html( data.html);
            this._categoriesMappingContainer.find('select').selectpicker();

            remodal_event( this._categoriesMappingContainer);
        }
        else {
            ErrorHandler.showError( profile_data_error_categories_mapping);
        }

        return $.Deferred().resolve( data);
    },

    _onError: function( xhr, _, error) {
        Notifications.warning( error);

        return $.Deferred().reject( error);
    },

    _onCategoriesMappingHtmlError: function( errorMessageOrException, xhr) {
        this._categoriesMappingContainer.html( xhr.responseText);
    },

    _getMigrationProfileValues: function() {
        var migrationTab = get_migration_tab();

        var subSelectors = [
            'input[type="checkbox"]',
            'input[type="text"]',
            'input[type="hidden"]',
            'select'
        ];

        var selector = subSelectors.join( ',');
        var fields = migrationTab.find( selector);
        fields = fields.filter( function( _, el) {
            return el.name !== 'import_xls_profiles';
        });

        var result = 'profile_type=migration-export&' + fields.serialize();
        var profile_id = get_profile_id_field().val();

        if (profile_id !== '') {
            result += '&profile_id=' + profile_id;
        }

        result = result.replace( 'import_xls_format', 'import_xls_file_format');
        result += '&import_xls_file_destiny=server&import_xls_file_destiny_server_path=' + window.cron_backup_path;

        return result;
    }
};

var XmlAnalyzer = {
    getMainNodes: function( file) {
        file = file || null;

        var formData = get_current_form_data();

        if (file !== null) {
            formData.append( 'file', file);
        }

        ApiRequest.post( get_main_xml_nodes_url, formData)
                  .then( this._onMainNodesSuccess.bind( this))
                  .fail( this._onError.bind( this));
    },

    _onMainNodesSuccess: function( data) {
        var xmlNodesSelector = $('select[name="xml_nodes_selector"]');

        xmlNodesSelector.empty();
        xmlNodesSelector.append( $('<option value="">---</option>'));

        data.forEach( function( item) {
            xmlNodesSelector.append(
                $('<option value="' + item.label + '">' + item.label + '</option>')
            );
        });

        xmlNodesSelector.append( $('<option value="manual-select">' + profile_import_column_name_select_insert_manually + '</option>'));

        xmlNodesSelector.show();
        xmlNodesSelector.selectpicker('refresh');
        xmlNodesSelector.parent( '.bootstrap-select').css( 'width', '100%');

        var xmlNodesInput = $('input[name="import_xls_node_xml"]');
        xmlNodesInput.hide();

        var container = $('.profile_import.node_xml');

        container.find( '.alert.alert-info').hide();
        container.find( '.alert.alert-success').show();
    },

    _onError: function( errorMessageOrException) {
        Notifications.warning( errorMessageOrException);
    }
};

var ApiRequest = {
    post: function( url, data, loaderContainer) {
        loaderContainer = loaderContainer || null;

        var processData = true;
        var contentType = window.undefined;

        if (data instanceof FormData) {
            processData = false;
            contentType = false;
        }

        this._startLoader( loaderContainer);

        return $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            processData: processData,
            contentType: contentType,

            beforeSend: function( xhr) {
                xhr._apiOptions = {
                    data: data,
                    loaderContainer: loaderContainer || null
                };
            }
        })
        .then( this._onSuccess.bind( this))
        .fail( this._onError.bind( this));
    },

    _onSuccess: function( data, _, xhr) {
        this._closeLoader( this._getLoaderContainer( xhr));

        var options = this._getOptions( xhr);

        if (data.error) {
            return $.Deferred().reject( data.message, xhr);
        }
        else {
            return $.Deferred().resolve( data, options.extraData);
        }
    },

    _onError: function( xhr, error, thrownError) {
        this._closeLoader( this._getLoaderContainer( xhr));

        return $.Deferred().reject( thrownError, xhr);
    },

    _startLoader: function( container) {
        ajax_loading_open( container || window.undefined);
    },

    _closeLoader: function( container) {
        ajax_loading_close( container);
    },

    _getLoaderContainer: function( xhr) {
        return this._getOptions( xhr).loaderContainer || window.undefined;
    },

    _getOptions: function( xhr) {
        return xhr._apiOptions;
    }
}

var UrlHashManager = {
    params: null,

    getParameters: function() {
        if (this.params === null) {
            this.params = this._buildParameters();
        }

        return this.params;
    },

    setParameters: function( params) {
        this.params = params;

        this._updateHashString();
    },

    has: function( name) {
        return name in this.getParameters();
    },

    get: function( name, defaultValue) {
        var result = defaultValue || null;

        if (this.has( name)) {
            return this.getParameters()[name];
        }

        return result;
    },

    set: function( name, value) {
        var params = this.getParameters();
        params[name] = value;

        this._updateHashString();
    },

    remove: function( name) {
        if (this.has( name)) {
            delete this.params[name];

            this._updateHashString();
        }
    },

    _buildParameters: function() {
        var hash = window.location.hash;
        var result = {};

        if (hash.length > 0) {
            hash = hash.substr( 1);

            if (hash.length > 0) {
                var segments = hash.split( '&');

                segments.forEach( function( segment){
                    var parts = segment.split( '=');
                    var name = parts[0];
                    var value = parts[1];

                    result[name] = value;
                })
            }
        }

        return result;
    },

    _updateHashString: function() {
        window.location.hash = this._buildHashString();
    },

    _buildHashString: function() {
        var pairs = [];

        for (var prop in this.params) {
            if (this.params.hasOwnProperty( prop)) {
                pairs.push( prop + '=' + this.params[prop]);
            }
        }

        return '#' + pairs.join( '&');
    }
};

function Template( contents) {
    this.contents = contents;
}

Template.prototype.render = function (values) {
    return this.contents.replace( /\{\{(.+?)\}\}/g, function( _, match) {
        if (!(match in values)) {
            throw new Error( 'Missing value for template: "' + match + '"');
        }

        return values[match];
    });
};

function ExpressionBuilder() {
    this.items = [];
    this.currentIndent = 0;
    this.latestIndent = 0;

    this.htmlRenderer = new ExpressionHtmlRenderer();
    this.textRenderer = new ExpressionTextRenderer();
    this.serializeRenderer = new ExpressionSerializeRenderer();
}

ExpressionBuilder.prototype = {
    isEmpty() {
        return this.items.length === 0;
    },

    clear() {
        this.items = [];
        this.currentIndent = 0;
        this.latestIndent = 0;
    },

    add( expr, joiner) {
        var result;

        if (joiner === null || typeof joiner === 'undefined') {
            result = new FilterItem({ expr: expr });

            this.items = [result];
        } else {
            result = this._doAdd( expr, joiner);
        }

        return result;
    },

    remove( item) {
        var index = this.items.indexOf( item);

        if (index === -1) {
            throw new Error( 'Item no existe!');
        }

        this.items.splice( index, 1);

        if (index === 0 && typeof this.items[0] !== "undefined") {
            this.items[0].joiner = null;
        }
    },

    validate() {
        var result = [];

        this.items.forEach( function( item) {
            var errorMessage = item.validate();

            if (errorMessage !== null) {
                result.push( errorMessage);
            }
        });

       return result;
    },

    indent() {
        var lastItemIndent = this.getLastItemIndent();

        if (this.currentIndent - lastItemIndent < 2) {
            this.currentIndent++;
        }
    },

    dedent() {
        if (this.currentIndent > 0) {
            this.latestIndent = this.currentIndent;

            this.currentIndent--;
        }
    },

    indentToLatest() {
        this.currentIndent = this.latestIndent;
    },

    getLastItemIndent() {
        return this.items.length > 0
               ? this.items[this.items.length - 1].indent
               : 0;
    },

    getIndent() {
        return this.currentIndent;
    },

    buildFilterItem( expr, joiner) {
        return new FilterItem({
            expr: expr,
            joiner: joiner,
            indent: this.currentIndent
        });
    },

    toString() {
        return this.textRenderer.render( this.items);
    },

    toHtml() {
        return this.htmlRenderer.render( this.items)
    },

    toExprList() {
        var result = [];
        var serialized = this.serialize( true);

        if (serialized.length > 0) {
            result = serialized.split( ',');
        }

        return result;
    },

    serialize( noEncode) {
        return this.serializeRenderer.render( this.items, !noEncode);
    },

    _doAdd( expr, joiner) {
        var result = this.buildFilterItem( expr, joiner);

        this.items.push( result);

        return result;
    }
};

function ExpressionHtmlRenderer() {
    this.openParens = '<strong>(</strong>';
    this.closeParens = '<strong>)</strong>';

    this.parensChecker = new ParensChecker(
        this.addOpenParens.bind( this),
        this.addCloseParens.bind( this)
    );
}

ExpressionHtmlRenderer.prototype = {
    render( items) {
        var result = '';
        var currentIndent = 0;
        this.parensChecker.init();

        items = this.fixItems( items)

        items.forEach( function( item) {
            if (item.joiner) {
                if (item.indent < currentIndent) {
                    result = this.parensChecker.close( result);
                }

                result += item.joiner;
            }

            if (item.indent > currentIndent) {
                result = this.parensChecker.open( result);
            } else if (item.indent < currentIndent && !item.joiner) {
                result = this.parensChecker.close( result);
            }

            currentIndent = item.indent;

            result += item.toHtml();
        }.bind( this));

        result = this.parensChecker.fixLastParens( result, currentIndent)

        this.parensChecker.check( result);

        return result;
    },

    fixItems( items) {
        return items.map( function( item) {
            const result = item.clone();

            if (result.joiner !== null) {
                result.joiner = ' <strong>' + result.joiner + '</strong> ';
            }

            return result;
        });
    },

    addOpenParens( value) {
        return value + ` ${this.openParens}`;
    },

    addCloseParens( value) {
        return value + `${this.closeParens} `;
    }
}

function ExpressionTextRenderer() {
    this.openParens = '(';
    this.closeParens = ')';

    this.parensChecker = new ParensChecker(
        this.addOpenParens.bind( this),
        this.addCloseParens.bind( this)
    );
}

ExpressionTextRenderer.prototype = {
    render( items) {
        var result = '';
        var currentIndent = 0;
        this.parensChecker.init();

        items = this.fixItems( items);

        items.forEach( function( item) {
            if (item.joiner) {
                if (item.indent < currentIndent) {
                    result = this.parensChecker.close( result);
                }

                result += item.joiner;
            }

            if (item.indent > currentIndent) {
                result = this.parensChecker.open( result);
            } else if (item.indent < currentIndent && !item.joiner) {
                result = this.parensChecker.close( result);
            }

            currentIndent = item.indent;

            result += item.toString();
        }.bind( this));

        result = this.parensChecker.fixLastParens( result, currentIndent)

        this.parensChecker.check( result);

        return result;
    },

    fixItems( items) {
        return items.map( function( item) {
            let result = item.clone();

            if (result.joiner) {
                result.joiner = ` ${result.joiner} `;
            }

            return result;
        });
    },

    addOpenParens( value) {
        return value + this.openParens;
    },

    addCloseParens( value) {
        return value + this.closeParens;
    }
}

function ExpressionSerializeRenderer() {
    this.openParens = '(';
    this.closeParens = ')';
    this.parensChecker = new ParensChecker(
        this.addOpenParens.bind( this),
        this.addCloseParens.bind( this)
    );
}

ExpressionSerializeRenderer.prototype = {
    render( items, encode) {
        var result = [];
        var currentIndent = 0;
        this.parensChecker.init();

        items = this.fixItems( items)

        items.forEach( function( item) {
            if (item.joiner) {
                if (item.indent < currentIndent) {
                    result = this.parensChecker.close( result)
                }

                result.push( item.joiner);
            }

            if (item.indent > currentIndent) {
                result = this.parensChecker.open( result);
            } else if (item.indent < currentIndent && !item.joiner) {
                result = this.parensChecker.close( result);
            }

            currentIndent = item.indent;

            result = this.appendItem( result, item);
        }.bind( this));

        result = this.parensChecker.fixLastParens( result, currentIndent)

        this.parensChecker.check( result);

        return this.asText( result, encode);
    },

    fixItems( items) {
        return items.map( function( item) {
            let result = item.clone();

            comparator = result.comparator();

            if (comparator.includes( ' ')) {
                result.setComparator( comparator.replace( ' ', '_'));
            }

            return result;
        });
    },

    appendItem( list, item) {
        list.push( item.field());
        list.push( item.comparator());
        list.push( item.value());

        return list;
    },

    asText( list, encode) {
        return this.encodeItems( list, encode).join( ',');
    },

    encodeItems( list, encode) {
        if (encode) {
            list = list.map( function( value) {
                return window.encodeURIComponent( value);
            });
        }

        return list;
    },

    addOpenParens( list) {
        list.push( this.openParens);

        return list;
    },

    addCloseParens( list) {
        list.push( this.closeParens);

        return list;
    }
}

function ParensChecker( openFn, closeFn) {
    this.parensCount = 0;
    this.openFn = openFn;
    this.closeFn = closeFn;
}

ParensChecker.prototype = {
    init() {
        this.parensCount = 0;
    },

    open( value) {
        const result = this.openFn( value)
        this.parensCount++;

        return result;
    },

    close( value) {
        const result = this.closeFn( value)
        this.parensCount--;

        return result;
    },

    fixLastParens( value, indent) {
        let result = value;

        while (indent > 0) {
            result = this.close( result);

            --indent;
        }

        return result;
    },

    check( value) {
        if (this.parensCount > 0) {
            console.error( value);

            throw new Error( "Unbalanced parens");
        }
    }
}

function FilterItem( config) {
    this.expr = config.expr || null;
    this.joiner = config.joiner || null;
    this.indent = config.indent || 0;
    this.fixedComparator = null;
}

FilterItem.prototype = {
    field() {
        return this.expr.fieldEl[0].selectedOptions[0].value;
    },

    displayField() {
        var result = this.expr.fieldEl[0].selectedOptions[0].text;
        var lastClosedParenIndex = result.lastIndexOf( '(');
        result = $.trim(result.substring( 0, lastClosedParenIndex));

        return result;
    },

    fieldType() {
      return this.field().split( '-')[2];
    },

    comparator() {
        if (this.fixedComparator !== null) {
            return this.fixedComparator;
        } else {
            return this._getComparator()
        }
    },

    setComparator( comparator) {
        this.fixedComparator = comparator;
    },

    value() {
        if (this.fieldType() === 'boolean') {
            result = this.comparatorRawValue() === '0' ? 'FALSE' : 'TRUE';
        } else {
            result = this.expr.valueEl.val();
        }

        return result;
    },

    validate() {
        var result = null;

        var value = $.trim( this.value());

        if (value === '' && this.fieldType() == 'number') {
            result = sprintf(
                profile_export_filters_error_empty_filter,
                this.displayField()
            );
        } else {
            switch (this.fieldType()) {
                case 'number':
                    if (!is_integer( value)) {
                        result = sprintf(
                            profile_export_filters_error_numeric_filter_expected,
                            this.displayField(),
                            value
                        );
                    }
                    break;

                case 'date':
                    var comparator = this.comparator();

                    if (['YEARS AGO', 'MONTHS AGO', 'DAYS AGO', 'HOURS AGO', 'MINUTES AGO'].includes( comparator)) {
                        // Filtro de comparacion de fecha relativo: el value debe ser un numero
                        if (!is_integer( value)) {
                            result = sprintf(
                                profile_export_filters_error_relative_date_filter_expected,
                                this.displayField(),
                                value
                            );
                        }
                    } else if (!['LIKE', 'NOT LIKE'].includes( comparator)) {
                        // Filtro de comparacion de fecha completa (=, !=, >, >=, <, <=):
                        // el value debe ser una fecha valida
                        if (!is_valid_date( value)) {
                            result = sprintf(
                                profile_export_filters_error_date_filter_expected,
                                this.displayField(),
                                value
                            );
                        }
                    }
                    break;
            }
        }

        return result;
    },

    comparatorRawValue() {
        var select = this.expr.conditionEl.filter( '.' + this.fieldType());

        return select.val();
    },

    toString() {
        var result = '';

        if (this.joiner) {
            result += ' ' + this.joiner +  ' ';
        }

        var field = '[' + this.field() + ']';

        value = this.value();
        var comparator = this.comparator();

        if (this.fieldType() === 'string') {
            if (comparator === 'LIKE' || comparator === 'NOT LIKE') {
                value = '%' + value + '%';
            }

            value = '"' + value + '"';
        }

        if (value === '') {
            value = '?';
        }

        result = field + ' ' + comparator + ' ' + value;

        return result;
    },

    toHtml() {
        var result = '';

        var field = '<em>[' + this.displayField() + ']</em>';

        value = $.trim( this.value());
        var comparator = this.comparator();

        switch (this.fieldType()) {
            case 'string':
                if (comparator === 'LIKE' || comparator === 'NOT LIKE') {
                    value = '%' + value + '%';
                }

                value = '"' + value + '"';
                break;

            case 'date':
                if (comparator === 'LIKE' || comparator === 'NOT LIKE') {
                    value = '%' + value + '%';
                }

                if (!['YEARS AGO', 'MONTHS AGO', 'DAYS AGO',
                        'HOURS AGO', 'MINUTES AGO'].includes( comparator)) {
                    value = '"' + value + '"';
                }
                break;

            case 'boolean':
                value = '<strong>' + value + '</strong>';
                break;

            case 'number':
                if (value === '' || !is_integer( value)) {
                    value = '?';
                }
                break;
        }

        value = '<em>' + value + '</em>';

        result += field + ' <strong>' + comparator + '</strong> ' + value;

        return result;
    },

    clone() {
        return new FilterItem({
            expr: this.expr,
            joiner: this.joiner,
            indent: this.indent
        });
    },

    _getComparator() {
        var result = this.comparatorRawValue();

        if (this.fieldType() === 'boolean') {
            result = '=';
        }

        result = result.toUpperCase();

        if (result.indexOf( '_') !== -1) {
            result = result.replace( '_', ' ');
        }

        return result;
    }
};
