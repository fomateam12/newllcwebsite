<?php
$_['export_import_launch_profile_main_title'] = 'Launch profile';
$_['export_import_launch_profile_description'] = '<p>Welcome to <b>Import Export PRO</b>. From this section you can launch import/export profiles previously created. If you didn\'t create any profile, go down to section "<b>Create or edit Import/Export profile</b>" where you can create your first profile.</p>
<p>For launch a profile, select it in right part and press red button "<b>Launch profile selected</b>".</p>
<p>From "<a href="javascript:{}" onclick="$(\'a.tab_cron-jobs, a.tab_cron-задания\').click()"><b>CRON Jobs</b></a>" tab, you can configure CRON Jobs to launch automatically your import/export processes when you wish, this is ideal for synchronization with suppliers (stock, prices...) or full backup of your system.</p>';
$_['export_import_profile_legend_text'] = 'Select a profile to continue with export/import process.';
$_['export_import_profile_load_select'] = 'Select profile';
$_['export_import_profile_upload_file'] = 'Upload file';
$_['export_import_profile_input_from'] = 'From (only numbers)';
$_['export_import_profile_input_from_help'] = 'Leave blank to remove "from" range';
$_['export_import_profile_input_to'] = 'To (only numbers)';
$_['export_import_profile_input_to_help'] = 'Leave blank to remove "to" range';
$_['export_import_profile_upload_file_help'] = 'Keep in mind the format file has to be compatible with the format saved in your profile.';
$_['export_import_start_button'] = 'Launch profile selected';
$_['export_import_error_empty_profile'] = '<b>Error:</b> Select a profile';
$_['export_import_error_profile_not_found'] = '<b>Error:</b> Profile not found.';
$_['export_import_error_xml_item_node'] = '<b>Error:</b> XML Item node empty. Load your profile configuration and set a XML Item node.';
$_['export_import_error_xml_item_node_not_found'] = '<b>Error:</b> XML Node "<b>%s</b>" not found in XML data.';
$_['export_import_remodal_process_title'] = 'Operation progress';
$_['export_import_remodal_process_subtitle'] = 'The PHP Process launched from web browser cannot be stopped manually. Process will finish by itself when completed. While this process is running, you may notice your website running slower, this only affects your browser session, the rest of your visitors will not notice any difference.';
$_['export_import_remodal_server_config_title'] = 'Server limitations';
$_['export_import_remodal_server_config_description'] = '        <p>We work very hard to achieve the highest possible optimization in the import and export processes. However, if you try do an operation that is exceeds your server limits you will experience errors and processes won\'t be completed.</p>
<p><b>Such errors are not related to this tool</b>, you will need to edit the following PHP directives in your server:</p>

        <p>The process is exceeds some of these PHP directives values:</p>
        <ul>
            <li><b>memory_limit</b> (Megabytes)</li>
            <li><b>max_execution_time</b> (Seconds)</li>
            <li><b>upload_max_filesize</b> (Megabytes)</li>
            <li><b>post_max_size</b> (Megabytes)</li>
        </ul>

        <p>The values depend on the size of the process or file that you are trying to load, for example:</p>
        <ul>
            <li><b>memory_limit</b>: 512M</li>
            <li><b>max_execution_time</b>: 800</li>
            <li><b>upload_max_filesize</b>: 240M</li>
            <li><b>post_max_size</b>: 250M</li>
        </ul>

        <p>The best way to increase these values is doing it directly in your server configuration, we recommend to ask your hosting company if you are unable to change them yourself.</p>
        <p>---------------------------</p>
        <p>You also can try these manual methods:</p>

        <p><b>METHOD 1: MODIFY OR ADD THE FOLLOWING VALUES IN ADMIN/PHP.INI:</b></p>
        <p>
        <b style="color: #ff0000;">These are orientative values, maybe you will have to set higher values.</b><br>
        memory_limit = 512M<br>
        max_execution_time = 800<br>
        upload_max_filesize = 240M<br>
        post_max_size = 250M<br>
        </p>

        <p><b>METHOD 2: CREATE THE FILE ADMIN/.HTACCESS WITH THE FOLLOWING CONTENT:</b></p>
        <p>
        <b style="color: #ff0000;">These are orientative values, maybe you will have to set higher values.</b><br>
        php_value memory_limit 512M<br>
        php_value max_execution_time 800<br>
        php_value upload_max_filesize 240M<br>
        php_value post_max_size 250M<br>
        </p>
        
        <p><b>METHOD 3: ARE YOU USING nginx?:</b></p>
        <p>
        In case that your server is using nginx, make sure that you edit your <b>nginx .conf</b> file with next settings:<br>
        proxy_read_timeout 3000;
        proxy_connect_timeout 3000;
        proxy_send_timeout 3000;
        send_timeout 3000;
        </p>

        <p><b>HOW TO CHECK IF VALUES WERE APPLIED (Method 1 and 2):</b></p>
        <ol>
            <li>Create a file in your root path called "phpinfo.php" with next PHP content:<pre>&#60;?php phpinfo(); ?></pre></li>
            <li>Access to http://yourdomain.com/phpinfo.php</li>
            <li>Press CTRL+F/COMMAND+F and search the directive names</li>
        </ol>

        <p>If you still see the old values, it is possible that METHOD 1 and METHOD 2 did not work because your server does not allow this type of changes across php.ini/.htaccess files. If that is the case, your changes have to be applied directly in server configuration and apache have to be restarted.</p>
        ';
$_['export_import_remodal_server_config_link'] = 'IMPORTANT! READ BEFORE LAUNCHING YOUR PROFILE';
$_['progress_export_starting_process'] = '<b>Starting export process...</b>';
$_['progress_export_element_numbers'] = 'Element to export <b>%s</b>';
$_['progress_export_processing_elements'] = '<b>Processing elements to export...</b>';
$_['progress_export_processing_elements_processed'] = 'Elements processed: <b>%s</b> of <b>%s</b>';
$_['progress_export_elements_inserted'] = 'Elements inserted: <b>%s</b> of <b>%s</b>';
$_['progress_export_error_range'] = '<b>Error:</b> Range "<b>from</b>" is bigger than range "<b>to</b>"';
$_['progress_export_error_fixed_columns_match_operation'] = 'The next mathematical operation is not possible: "<b>%s</b>" for element: %s';

$_['progress_import_error_columns'] = '<b>Error:</b> System detected that file uploaded hasn\'t any column expected by your profile configuration:
        <br><br>
        <b>Columns in FILE:</b>
        %s
        <br>
        <b>Columns in PROFILE:</b>
        %s
    ';
$_['progress_import_starting_process'] = '<b>Starting import process...</b>';
$_['progress_import_from_product_creating_categories'] = '<b>Creating categories...</b>';
$_['progress_import_from_product_created_categories'] = 'Categories created <b>%s</b>';
$_['progress_import_from_product_error_cat_repeat_categories'] = '<b>Error:</b> Category name <a href="%s" target="_blank"><b>%s</b></a> is repeated, rename it or use "Category tree" in your profile.';
$_['progress_import_from_product_creating_filter_groups'] = '<b>Creating filter groups...</b>';
$_['progress_import_from_product_created_filter_groups'] = 'Filter groups created <b>%s</b>';
$_['progress_import_from_product_creating_filter_groups_error_repeat'] = '<b>Error:</b> Filter group name <a href="%s">"<b>%s</b>"</a> is repeated.';
$_['progress_import_from_product_creating_filters'] = '<b>Creating filters...</b>';
$_['progress_import_from_product_created_filters'] = 'Filters created <b>%s</b>';
$_['progress_import_from_product_creating_filters_error_no_group'] = 'Unable to create filter "<b>%s</b>", Filter group to this filter was not assigned.';
$_['progress_import_from_product_creating_attribute_groups'] = '<b>Creating attribute groups...</b>';
$_['progress_import_from_product_created_attribute_groups'] = 'Attribute groups created <b>%s</b>';
$_['progress_import_from_product_creating_attribute_groups_error_repeat'] = '<b>Error:</b> Attribute group name <a href="%s">"<b>%s</b>"</a> is repeated.';
$_['progress_import_from_product_creating_attributes'] = '<b>Creating attributes...</b>';
$_['progress_import_from_product_created_attributes'] = 'Attributes created <b>%s</b>';
$_['progress_import_from_product_creating_attributes_error_no_group'] = 'System can\'t create attribute "<b>%s</b>", Attribute group to this attribute was not assigned.';
$_['progress_import_from_product_creating_manufacturers'] = '<b>Creating manufacturers...</b>    ';
$_['progress_import_from_product_created_manufacturers'] = 'Manufacturers created <b>%s</b>';
$_['progress_import_from_product_creating_options_error_empty_main_field'] = '<b>Error:</b>  product ID "<b>%s</b>" was not found in your file.
If you wish to use product options, enable the product ID in your profile configuration. Otherwise, disable option columns "<b>Option XXXX</b>".';
$_['progress_import_from_product_creating_options'] = '<b>Creating options...</b>';
$_['progress_import_from_product_created_options'] = 'Options created <b>%s</b>';
$_['progress_import_from_product_creating_options_error_repeat'] = '<b>Error:</b> Option name <a href="%s">"<b>%s</b>"</a>, type "<b>%s</b>" is repeated.';
$_['progress_import_from_product_creating_options_error_option_type'] = '<b>Error:</b> To operate with product options, Option Type have to be assigned to option "<b>%s</b>"';
$_['progress_import_from_product_creating_option_values'] = '<b>Creating option values...</b>';
$_['progress_import_from_product_created_option_values'] = 'Option values created <b>%s</b>';
$_['progress_import_from_product_creating_option_values_error_option_type'] = 'Error row <b>%s</b>: To operate with product options, Option Type have to be assigned to option "<b>%s</b>"';
$_['progress_import_from_product_creating_option_values_error_option'] = 'Error row <b>%s</b>: To operate with product option values, Option have to be assigned to option value "<b>%s</b>"';
$_['progress_import_from_product_creating_downloads'] = '<b>Creating downloads...</b>';
$_['progress_import_from_product_created_downloads'] = 'Downloads created <b>%s</b>';
$_['progress_import_product_error_option_data_in_main_row'] = '<b>Error row %s</b>: Detected option data in product main row. Delete content of all "<b>Option xxxxx</b>" columns.';
$_['progress_import_product_error_product_related_not_found'] = '<b>Error in row %s</b>: The related product model %s was not found in your store.  Make sure that product appears in your spreadsheet <b>BEFORE row %s</b>.';
$_['progress_import_product_error_product_id_limit'] = 'Detected wrong <b>Product ID</b> value: <b>%s</b>, needs to be a <b>NUMERIC VALUE</b>. Edit import profile, disable column "Product ID", and use another column like product identifier (model, sku, ean...).';
$_['progress_import_elements_process_start'] = '<b>Starting element processing...</b>';
$_['progress_import_elements_processed'] = 'Elements processed: <b>%s</b> of <b>%s</b>';
$_['progress_import_error_main_identificator'] = 'Main product identificator "<b>%s</b>" doesn\'t exist in your data, make sure that you enabled this column in "<b>Column mapping</b>" section or this <b>column exist</b> in file that you are trying import.';
$_['progress_import_process_format_data_file'] = '<b>Formating data file...</b>';
$_['progress_import_process_format_data_file_progress'] = 'Elements formatted: <b>%s</b> of <b>%s</b>';
$_['progress_import_elements_conversion_start'] = '<b>Converting element values...</b>';
$_['progress_import_elements_converted'] = 'Elements values converted:  <b>%s</b> of <b>%s</b>';
$_['progress_import_process_start'] = '<b>Starting importing process...</b> Warning: please be patient, this process normally takes a long time! %s';
$_['progress_import_process_imported'] = 'Elements imported:  <b>%s</b> of <b>%s</b>';
$_['progress_import_applying_changes_safely'] = '<b>Applying changes safely...</b>';
$_['progress_import_finished'] = '<b>%s</b><b>Import finished successfully!</b>
                <ul>
                    <li>Elements created: <b>%s</b></li>
                    <li>Elements modified: <b>%s</b></li>
                    <li>Elements deleted: <b>%s</b></li>
                </ul>';
$_['progress_import_error_updating_conditions'] = 'INTERNAL ERROR: Trying update table row without conditions: <b>%s</b>';
$_['progress_import_error_skipped_all_elements'] = 'All elements inside this file was skipped, check "<b>Pre-filter</b>" configuration in profile.';
$_['progress_import_error_empty_data'] = '<b>Error:</b> Empty data. Make sure the uploaded file is compatible with your profile columns.';
$_['export_import_download_empy_file'] = 'Click to download a sample profile file';
$_['progress_import_elements_splitted_values_start'] = '<b>Splitting and getting values...</b>';
$_['progress_import_elements_splitted_progress'] = 'Elements processed:  <b>%s</b> of <b>%s</b>';
$_['progress_import_export_error_wrong_conditional_value'] = 'Conditional value "<b>%s</b>" is not constructed correctly. Check "<b>Conditional value</b>" help.';
$_['progress_import_export_error_wrong_conditional_value_multiple_symbols'] = 'Conditional value "<b>%s</b>" is not constructed correctly. One or more conditional value "<b>%s</b>" found. Check "<b>Conditional value</b>" help.';
$_['progress_import_export_error_incorrect_quoted_string'] = 'Incorrectly quoted string (start or end quote missing): %s';
$_['progress_import_export_error_missing_conditional_filter'] = 'Incorrect or missing conditional filter name: "%s"';
$_['progress_import_export_error_evaluating_filter'] = 'Error evaluating filter "%s": %s';
$_['progress_import_export_error_invalid_filter_syntax'] = 'Invalid filter syntax: "%s"';
$_['progress_import_export_error_invalid_boolean_filter'] = 'Invalid boolean filter value (expected 1 or 0): "%s"';
$_['progress_import_export_error_conditional_missing_symbol'] = 'Conditional expression: missing comparator: "%s"';
$_['progress_import_product_error_empty_description'] = '<b>Error creating product</b>: Trying to create a product without description data (name, description, etc.), json product: %s.
';
$_['progress_import_elements_no_numeric_id'] = '<b>Error ID no numeric</b>: You enabled "ID instead of names" to few columns, system detects a non numeric ID: <b>%s</b>.';
$_['progress_import_product_option_values_error_option_doesnt_exist'] = '<b>Error in file row %s:</b> Option "<b>%s</b>" doesn\'t exist, make sure that you imported all options before import product option value asociations.';
$_['progress_import_product_option_values_error_not_product_identificator'] = '<b>Error in file row %s:</b> Product identificator doesn\'t exist';
$_['progress_import_applying_pre_filters'] = '<b>Applying Pre-Filters</b>';
$_['progress_import_applying_file_filters'] = 'Applying <b>file-filters</b>';
$_['progress_import_applying_shop_filters'] = 'Applying <b>shop-filters</b>';
$_['progress_import_elements_deleted'] = '<b>%s</b> elements deleted';
$_['progress_import_elements_skipped'] = '<b>%s</b> elements skipped';
$_['progress_import_elements_disabled'] = '<b>%s</b> elements disabled';
$_['progress_import_elements_set_0'] = '<b>%s</b> elements set 0 quantity';
$_['progress_import_mapping_categories'] = '<b>Mapping Categories</b>';

$_['progress_import_updating_combinations_as_products_index'] = '<b>Updating combination as product index ... </b> Warning: please be patient, this could take a time! %s';

$_['export_import_server_error'] = '<b>Error from server:</b> Your server stopped progress by next possible reasons: <ul><li>Not enough <b>max_execution_time</b> value</li><li>Not enough <b>memory_limit</b> value</li></ul><br>For more information, check <b>tab FAQ</b> point <b>1</b>';

$_['progress_import_downloading_remote_images'] = '<b>Downloading remote images...</b>';
$_['progress_import_downloading_remote_images_progress'] = 'Images processed: <b>%s</b> of <b>%s</b>';

?>