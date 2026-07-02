<?php
    class ModelExtensionModuleIeProTabProfiles extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);

            $this->load->language($this->real_extension_type.'/ie_pro_tab_profiles');

            $this->filter_conditionals_number = array(
                '>=' => '≥',
                '<=' => '≤',
                '>' => '&gt;',
                '<' => '&lt;',
                '=' => '=',
                '!=' => '≠',
            );

            $this->filter_conditionals_string = array(
                'like' => $this->language->get('profile_products_filters_conditional_contain'),
                'not_like' => $this->language->get('profile_products_filters_conditional_not_contain'),
                '=' => $this->language->get('profile_products_filters_conditional_is_exactly'),
                '!=' => $this->language->get('profile_products_filters_conditional_is_not_exactly'),
            );

            $this->filter_conditionals_date = array(
                '>=' => '≥',
                '<=' => '≤',
                '>' => '&gt;',
                '<' => '&lt;',
                '=' => '=',
                '!=' => '≠',
                'like' => $this->language->get('profile_products_filters_conditional_contain'),
                'not_like' => $this->language->get('profile_products_filters_conditional_not_contain'),
                'years_ago' => $this->language->get('profile_products_filters_conditional_years_ago'),
                'months_ago' => $this->language->get('profile_products_filters_conditional_months_ago'),
                'days_ago' => $this->language->get('profile_products_filters_conditional_days_ago'),
                'hours_ago' => $this->language->get('profile_products_filters_conditional_hours_ago'),
                'minutes_ago' => $this->language->get('profile_products_filters_conditional_minutes_ago'),
            );

            $this->filter_conditionals_boolean = array(
                '1' => $this->language->get('profile_products_filters_conditional_is_yes'),
                '0' => $this->language->get('profile_products_filters_conditional_is_no'),
            );

            $this->filter_field_types = array(
                'number',
                'string',
                'date',
                'boolean',
            );

            $this->conditionals = array(
                'AND' => $this->language->get('profile_products_filters_main_conditional_and'),
                'OR' => $this->language->get('profile_products_filters_main_conditional_or'),
            );

            $this->product_identificators = array(
                'product_id' => $this->language->get('profile_product_identificator_product_id'),
                'model' => $this->language->get('profile_product_identificator_model'),
                'sku' => $this->language->get('profile_product_identificator_sku'),
                'upc' => $this->language->get('profile_product_identificator_upc'),
                'ean' => $this->language->get('profile_product_identificator_ean'),
                'jan' => $this->language->get('profile_product_identificator_jan'),
                'isbn' => $this->language->get('profile_product_identificator_isbn'),
                'mpn' => $this->language->get('profile_product_identificator_mpn'),
            );

            $this->possible_values_text = $this->language->get('profile_products_columns_possible_values');
        }

        public function get_fields() {
      		$this->document->addScript( 'view/javascript/devmanextensions/ext_ie_pro/tab_profiles.js?'.$this->get_ie_pro_version());
            $this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/tab_profiles.css?'.$this->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/ext_ie_pro/js/jquery-sortable.js?'.$this->get_ie_pro_version());

            $spread_sheet_account_id = $this->spread_sheet_get_account_id();

            $this->load->model('extension/module/ie_pro_categories');
            $categories_select_format = $this->model_extension_module_ie_pro_categories->get_all_categories_branches_select();

            $this->load->model('extension/module/ie_pro_manufacturers');
            $manufacturers_select_format = $this->model_extension_module_ie_pro_manufacturers->get_all_manufacturers_import_format(true);


            $dir_catalog = str_replace('/catalog', '', DIR_CATALOG);
            $catalog_folder_split = explode('/', $dir_catalog);
            $folders = array();
            foreach ($catalog_folder_split as $key => $folder) {
                if($folder != '')
                    $folders[] = $folder;

                if(count($folders) == 2)
                    break;
            }
            $final_out_folder = '/'.implode('/', $folders).'/';

            $fields = array(
                array(
                    'type' => 'html_hard',
                    'html_code' => '<div class="container_create_profile">'
                ),
                    array(
                        'type' => 'html_hard',
                        'html_code' => '<div class="row">'
                    ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '<div class="col-md-12"><span class="main_title"><span class="retina-design-0788"></span>'.$this->language->get('profile_create_or_edit_profile_main_tile').'</span></div>'
                        ),
                        array(
                            'label' => $this->language->get('profile_select_text'),
                            'type' => 'select',
                            'options' => $this->profiles_select,
                            'name' => 'profiles',
                            'onchange' => 'profile_load($(this))',
                            'class_container' => 'container_select_profile no_border_bottom',
                            'force-columns' => 5,
                            'after' => '<a class="btn btn-danger delete_profile disabled"
                                           data-toggle="tooltip" data-html="true" title=""
                                           data-original-title="' . $this->language->get('profile_delete_configuration').'"
                                           onclick="profile_delete()">
                                           <i class="fa fa-times" style="margin-right:0px;"></i>
                                        </a>' . $this->get_remodal( 'profile_delete_confirm_remodal', $this->language->get( 'profile_delete_confirmation_title'), $this->language->get( 'profile_delete_confirmation_description'), array('button_confirm' => true, 'button_cancel' => true, 'remodal_options' => 'hashTracking: false')) . '

                                        <a class="btn btn-info upload_profile"
                                           data-toggle="tooltip" data-html="true" title=""
                                           data-original-title="' . $this->language->get('profile_upload_tooltip').'"
                                           onclick="$(this).next(\'input\').click();">
                                           <i class="fa fa-download" style="margin-right:0px;"></i>
                                        </a>
                                        <input onchange="window.profile_upload( $(this));"
                                               name="profile_import"
                                               type="file" style="display:none;">' . '

                                        <a class="btn btn-success download_profile disabled"
                                           data-toggle="tooltip" data-html="true" title=""
                                           data-original-title="' . $this->language->get('profile_download_tooltip').'"
                                           onclick="profile_download()">
                                           <i class="fa fa-upload" style="margin-right:0px;"></i>
                                        </a>'
                        ),

                        array(
                            'type' => 'html_code',
                            'html_code' => '- '.$this->language->get('profile_or').' -',
                            'force-columns' => 2,
                            'class_container' => 'or new no_border_bottom',
                        ),
                        array(
                            'type' => 'html_code',
                            'label' => $this->language->get('profile_create_text'),
                            'html_code' => '
                                <a class="button clear_style icon_left" href="javascript:{}" onclick="profile_create(\'import\'); profile_reset_steps(); $(\'div.step_import_profile_configuration legend\').trigger(\'click\');"><span class="retina-gadgets-1497"></span>'.$this->language->get('profile_create_import_text').'</a><input type="hidden" name="profile_type" value=""><input type="hidden" name="profile_id" value="">
                                <a class="button clear_style icon_left success" href="javascript:{}" onclick="profile_create(\'export\'); profile_reset_steps(); $(\'div.step_export_profile_configuration legend\').trigger(\'click\');"><span class="retina-gadgets-1496"></span>'.$this->language->get('profile_create_export_text').'</a>
                            ',
                            'force-columns' => 5,
                            'class_container' => 'no_border_bottom',
                        ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '<div style="clear:both;"></div>'
                        ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '<div class="container_create_profile_steps">'
                        ),
                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_legend_text_import'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_import step_import_profile_configuration',
                            ),
                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_legend_text_export'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_export step_export_profile_configuration',
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                array(
                                    'label' => $this->language->get('profile_import_file_format'),
                                    'type' => 'select',
                                    'options' => array(
                                        'xlsx' => '.xlsx',
                                        'xls' => '.xls',
                                        'csv' => '.csv',
                                        'json' => '.json',
                                        'xml' => '.xml',
                                        'ods' => '.ods',
                                        'spreadsheet' => 'Google Spreadsheet'
                                    ),
                                    'name' => 'file_format',
                                    'class_container' => 'profile_import profile_export main_configuration configuration',
                                    'onchange' => 'profile_check_format($(this).val())',
                                    'after' => '<br>'.$this->get_remodal('mapping_xml_columns', $this->language->get('profile_import_mapping_xml_columns_remodal_title'), sprintf($this->language->get('profile_import_mapping_xml_columns_remodal_description'), $this->get_image_link('xml_mapping_example_1.jpg'), $this->get_image_link('xml_mapping_example_2.jpg'), $this->get_image_link('xml_mapping_example_3.jpg'), $this->get_image_link('xml_mapping_example_4.jpg')), array('button_cancel' => false, 'link' => 'profile_import_mapping_xml_columns_link_title')),
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_csv_separator'),
                                    'help' => $this->language->get('profile_import_csv_separator_help'),
                                    'type' => 'text',
                                    'name' => 'csv_separator',
                                    'class_container' => 'profile_import profile_export csv_separator main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_json_main_node'),
                                    'help' => $this->language->get('profile_import_json_main_node_help'),
                                    'type' => 'text',
                                    'name' => 'json_main_node',
                                    'class_container' => 'only_json profile_import no_refresh_columns file_without_columns main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_force_utf8'),
                                    'type' => 'select',
                                    'name' => 'force_utf8',
                                    'options' => array(
                                        '' => $this->language->get('profile_import_force_utf8_none'),
                                        'windows-1251' => $this->language->get('profile_import_force_utf8_from_windows_1251'),
                                        'windows-1252' => $this->language->get('profile_import_force_utf8_from_windows_1252'),
                                    ),
                                    'after' => '<br>'.$this->get_remodal('force_utf8', $this->language->get('profile_import_products_force_utf8_remodal_title'), $this->language->get('profile_import_products_force_utf8_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_products_force_utf8_link_title')),
                                    'class_container' => 'profile_import no_refresh_columns force_utf8 main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_file_without_columns'),
                                    'type' => 'boolean',
                                    'name' => 'file_without_columns',
                                    'after' => $this->get_remodal('file_without_columns', $this->language->get('profile_import_file_without_columns_remodal_title'), $this->language->get('profile_import_file_without_columns_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_file_without_columns_link_title')),
                                    'class_container' => 'only_csv profile_import profile_export no_refresh_columns file_without_columns main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_export_remove_bom'),
                                    'type' => 'boolean',
                                    'name' => 'remove_bom',
                                    'class_container' => 'only_csv profile_export no_refresh_columns file_without_columns main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_xml_node'),
                                    'type' => 'text',
                                    'name' => 'node_xml',
                                    'class_container' => 'profile_import profile_export node_xml main_configuration configuration',
                                    'after' => '
                                        <select name="xml_nodes_selector"
                                                data-field-name="import_xls_node_xml"
                                                style="display: none; width: 100%;"
                                                onchange="profile_import_xml_main_node_selected( $(this));">
                                        </select>
                                        <a href="javascript:{}" data-remodal-target="profile_import_xml_node">' .
                                            $this->language->get('profile_import_xml_node_link') . '
                                        </a>' .

                                        $this->get_remodal(
                                            'profile_import_xml_node',
                                            $this->language->get('profile_import_xml_node_remodal_title'),
                                            sprintf( $this->language->get('profile_import_xml_node_remodal_description'),
                                                     $this->get_image_link('xml_node.jpg'),
                                                     $this->get_image_link('xml_node_columns.jpg')
                                                   ),
                                            array('button_cancel' => false, 'button_confirm' => false)
                                        ) . '

                                        <div class="col-md-12 form-group-columns"
                                             style="border: 2px solid #e1d8d8; width: 100%;">

                                            <div class="alert alert-info">' .
                                               $this->language->get( 'profile_import_main_xml_nodes_not_analyzed_yet') . '
                                            </div>

                                            <div class="alert alert-success"
                                                 style="display: none;">' .
                                               $this->language->get( 'profile_import_main_xml_nodes_already_analyzed') . '
                                            </div>

                                            <a onclick="profile_get_main_xml_nodes( $(this)); return false;"
                                               class="button button_columns_mapping">
                                               <i class="fa fa-sitemap"></i>' .
                                               $this->language->get( 'profile_import_xml_get_main_nodes') . '
                                            </a>
                                        </div>'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_file_origin'),
                                    'type' => 'select',
                                    'options' => array(
                                        'manual' => $this->language->get('profile_import_file_origin_manual'),
                                        'ftp' => $this->language->get('profile_import_file_origin_ftp'),
                                        'url' => $this->language->get('profile_import_file_origin_url'),
                                    ),
                                    'name' => 'file_origin',
                                    'class_container' => 'profile_import file_origin main_configuration configuration no_refresh_columns',
                                    'onchange' => 'profile_import_check_origin($(this).val())'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_file_destiny'),
                                    'type' => 'select',
                                    'options' => array(
                                        'download' => $this->language->get('profile_import_file_download'),
                                        'server' => $this->language->get('profile_import_file_destiny_server'),
                                        'external_server' => $this->language->get('profile_import_file_destiny_external_server'),
                                    ),
                                    'name' => 'file_destiny',
                                    'class_container' => 'profile_export file_destiny main_configuration configuration no_refresh_columns',
                                    'onchange' => 'profile_export_check_destiny($(this).val())'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_file_destiny_server_path'),
                                    'type' => 'text',
                                    'name' => 'file_destiny_server_path',
                                    'class_container' => 'profile_export server main_configuration configuration',
                                    'after' => '<a href="javascript:{}" data-remodal-target="profile_export_file_destiny_server_path">'.$this->language->get('profile_import_file_destiny_server_path_remodal_link').'</a>'.$this->get_remodal('profile_export_file_destiny_server_path', $this->language->get('profile_import_file_destiny_server_path_remodal_title'), sprintf($this->language->get('profile_import_file_destiny_server_path_remodal_description'), $dir_catalog,$dir_catalog,$dir_catalog,$dir_catalog,$final_out_folder), array('button_cancel' => false, 'button_confirm' => false, '')),
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_file_destiny_server_file_name'),
                                    'help' => $this->language->get('profile_import_file_destiny_server_file_name_help'),
                                    'type' => 'text',
                                    'name' => 'file_destiny_server_file_name',
                                    'class_container' => 'profile_export server main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_file_destiny_server_file_name_sufix'),
                                    'type' => 'select',
                                    'options' => array(
                                        '' => $this->language->get('profile_import_file_destiny_server_file_name_sufix_none'),
                                        'date' => $this->language->get('profile_import_file_destiny_server_file_name_sufix_date'),
                                        'datetime' => $this->language->get('profile_import_file_destiny_server_file_name_sufix_datetime'),
                                    ),
                                    'name' => 'file_destiny_server_file_name_sufix',
                                    'class_container' => 'profile_export server main_configuration configuration no_refresh_columns',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_url'),
                                    'type' => 'text',
                                    'name' => 'url',
                                    'class_container' => 'profile_import url main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_force_refresh_cache'),
                                    'help' => $this->language->get('profile_import_force_refresh_cache_help'),
                                    'type' => 'boolean',
                                    'name' => 'force_refresh_cache',
                                    'class_container' => 'profile_import url main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_http_authentication'),
                                    'type' => 'select',
                                    'options' => array(
                                        '' => $this->language->get('profile_import_http_authentication_none'),
                                        'basic' => $this->language->get('profile_import_http_authentication_basic'),
                                        'digest' => $this->language->get('profile_import_http_authentication_digest'),
                                    ),
                                    'name' => 'http_authentication',
                                    'class_container' => 'profile_import url main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_http_username'),
                                    'type' => 'text',
                                    'name' => 'http_username',
                                    'class_container' => 'profile_import url main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_http_password'),
                                    'type' => 'text',
                                    'name' => 'http_password',
                                    'class_container' => 'profile_import url main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_passive_mode'),
                                    'help' => $this->language->get('profile_import_ftp_passive_mode_help'),
                                    'type' => 'boolean',
                                    'name' => 'ftp_passive_mode',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration no_refresh_columns'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_sftp'),
                                    'help' => $this->language->get('profile_import_ftp_sftp_help'),
                                    'type' => 'boolean',
                                    'name' => 'ftp_sftp',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration no_refresh_columns'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_host'),
                                    'type' => 'text',
                                    'name' => 'ftp_host',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_username'),
                                    'type' => 'text',
                                    'name' => 'ftp_username',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_password'),
                                    'type' => 'text',
                                    'name' => 'ftp_password',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_port'),
                                    'help' => $this->language->get('profile_import_ftp_port_help'),
                                    'type' => 'text',
                                    'name' => 'ftp_port',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration',
                                    'default' => '21'
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_path'),
                                    'help' => $this->language->get('profile_import_ftp_path_help'),
                                    'type' => 'text',
                                    'name' => 'ftp_path',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_ftp_file'),
                                    'help' => $this->language->get('profile_import_ftp_file_help'),
                                    'type' => 'text',
                                    'name' => 'ftp_file',
                                    'class_container' => 'profile_import profile_export ftp main_configuration configuration',
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_spreadsheet_name'),
                                    'help' => $this->language->get('profile_import_spreadsheet_name_help'),
                                    'type' => 'text',
                                    'name' => 'spreadsheet_name',
                                    'class_container' => 'profile_import profile_export spreadsheet_name main_configuration configuration',
                                    'after' => $this->get_remodal('profile_import_spreadsheet_remodal', $this->language->get('profile_import_spreadsheet_remodal_title'), sprintf($this->language->get('profile_import_spreadsheet_remodal_description'), '<input name="spreadsheet_json" type="file">', $spread_sheet_account_id), array('link' => 'profile_import_spreadsheet_remodal_link', 'remodal_options' => 'closeOnConfirm: false, hashTracking: false')),
                                ),

                                array(
                                    'label' => $this->language->get('profile_i_want'),
                                    'type' => 'select',
                                    'options' => $this->ie_categories,
                                    'name' => 'i_want',
                                    'class_container' => 'profile_import profile_export main_configuration configuration',
                                    'onchange' => 'profile_check_i_want()'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_import_products_legend'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_import profile_export configuration products',
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_import_products_images_legend'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_import configuration images',
                            ),

                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),

                                array(
                                    'label' => $this->language->get('profile_import_products_strict_update'),
                                    'after' => '<a href="javascript:{}" data-remodal-target="profile_strict_update">'.$this->language->get('profile_import_products_strict_update_link').'</a>'.$this->get_remodal('profile_strict_update', $this->language->get('profile_import_products_strict_update'), sprintf($this->language->get('profile_import_products_strict_update_help'), $this->get_image_link('strict_update_images_1.jpg'), $this->get_image_link('strict_update_images_2.jpg'), $this->get_image_link('strict_update_images_3.jpg')), array('button_cancel' => false, 'button_confirm' => false)),
                                    'type' => 'boolean',
                                    'name' => 'strict_update',
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_multilanguage'),
                                    'after' => '<a href="javascript:{}" data-remodal-target="profile_multilanguage">'.$this->language->get('profile_import_products_strict_update_link').'</a>'.$this->get_remodal('profile_multilanguage', $this->language->get('profile_import_products_multilanguage'), $this->language->get('profile_import_products_multilanguage_help'), array('button_cancel' => false, 'button_confirm' => false)),
                                    'type' => 'boolean',
                                    'name' => 'multilanguage',
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_category_tree'),
                                    'type' => 'boolean',
                                    'name' => 'category_tree',
                                    'after' => $this->get_remodal('profile_cat_tree', $this->language->get('profile_import_products_profile_cat_tree_remodal_title'), sprintf($this->language->get('profile_import_products_profile_cat_tree_remodal_description'), $this->get_image_link('excel_example_categories.jpg'), $this->get_image_link('excel_example_categories_tree.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_products_profile_cat_tree_link_title')),
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_category_tree_last_child'),
                                    'type' => 'boolean',
                                    'name' => 'category_tree_last_child',
                                    'after' => '<a href="javascript:{}" data-remodal-target="profile_cat_last_tree_assign">'.$this->language->get('profile_import_products_strict_update_link').'</a>'.$this->get_remodal('profile_cat_last_tree_assign', $this->language->get('profile_import_products_category_tree_last_child_modal_title'), sprintf($this->language->get('profile_import_products_category_tree_last_child_modal_description'), $this->get_image_link('strict_update_disabled.jpg'), $this->get_image_link('strict_update_enabled.jpg')), array('button_cancel' => false, 'button_confirm' => false)),
                                    'class_container' => 'profile_import profile_export configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_sum_tax'),
                                    'after' => $this->get_remodal('sum_tax', $this->language->get('profile_import_products_sum_tax_remodal_title'), $this->language->get('profile_import_products_sum_tax_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_products_sum_tax_link_title')),
                                    'type' => 'boolean',
                                    'name' => 'sum_tax',
                                    'class_container' => 'profile_import profile_export configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_rest_tax'),
                                    'after' => $this->get_remodal('rest_tax', $this->language->get('profile_import_products_rest_tax_remodal_title'), $this->language->get('profile_import_products_rest_tax_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_products_rest_tax_link_title')).'<div style="clear:both;"></div>',
                                    'type' => 'boolean',
                                    'name' => 'rest_tax',
                                    'class_container' => 'profile_import profile_export configuration products no_refresh_columns',
                                    'columns' => 3,
                                ),

                                array(
                                    'label' => $this->language->get('profile_product_identificator'),
                                    'type' => 'select',
                                    'name' => 'product_identificator',
                                    'options' => $this->product_identificators,
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'columns' => 3
                                ),
                                array(
                                    'label' => $this->language->get('profile_import_products_autoseo_gerator'),
                                    'type' => 'select',
                                    'name' => 'autoseo_gerator',
                                    'options' => array(
                                        '' => $this->language->get('profile_import_products_autoseo_gerator_none'),
                                        'name' => $this->language->get('profile_import_products_autoseo_gerator_name'),
                                        'name_model' => $this->language->get('profile_import_products_autoseo_gerator_name_model'),
                                        'meta_title' => $this->language->get('profile_import_products_autoseo_gerator_meta_title'),
                                        'model' => $this->language->get('profile_import_products_autoseo_gerator_model')
                                    ),
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_existing_products'),
                                    'type' => 'select',
                                    'name' => 'existing_products',
                                    'options' => array(
                                        'edit' => $this->language->get('profile_import_products_existing_products_edit'),
                                        'skip' => $this->language->get('profile_import_products_existing_products_skip'),
                                    ),
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_new_products'),
                                    'type' => 'select',
                                    'name' => 'new_products',
                                    'options' => array(
                                        'edit' => $this->language->get('profile_import_products_new_products_edit'),
                                        'skip' => $this->language->get('profile_import_products_new_products_skip'),
                                    ),
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_download_image_route'),
                                    'type' => 'text',
                                    'name' => 'download_image_route',
                                    'class_container' => 'profile_import configuration products images no_refresh_columns',
                                    'columns' => 3,
                                    'after' => $this->get_remodal('profile_import_products_download_image_route', $this->language->get('profile_import_products_download_image_route_remodal_title'), sprintf($this->language->get('profile_import_products_download_image_route_remodal_description'), '/image/'.$this->image_path, '/image/'.$this->image_path), array('link' => 'profile_import_products_download_image_route_remodal_link', 'remodal_options' => 'button_cancel: false')),
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_skip_existing_images'),
                                    'help' => $this->language->get('profile_import_products_skip_existing_images_help'),
                                    'type' => 'boolean',
                                    'name' => 'skip_existing_images',
                                    'class_container' => 'profile_import configuration products images no_refresh_columns',
                                    'columns' => 3,
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_sort_attributes'),
                                    'type' => 'boolean',
                                    'name' => 'sort_attributes',

                                    'after' => $this->get_remodal(
                                        'profile_sort_attributes',
                                        $this->language->get( 'profile_import_products_profile_sort_attributes_remodal_title'),
                                        sprintf( $this->language->get( 'profile_import_products_profile_sort_attributes_remodal_description'),
                                                 $this->get_image_link( 'excel_example_attributes_unsorted.jpg'),
                                                 $this->get_image_link( 'excel_example_attributes_sorted.jpg')),
                                        ['button_cancel' => false,
                                         'button_confirm' => false,
                                         'link' => 'profile_import_products_profile_sort_attributes_link_title'
                                        ]
                                    ),

                                    'class_container' => 'profile_export configuration products',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_products_no_check_duplicates'),
                                    'after' => '<a href="javascript:{}" data-remodal-target="profile_no_check_duplicates">'.$this->language->get('profile_import_products_no_check_duplicates_link').'</a>'.$this->get_remodal('profile_no_check_duplicates', $this->language->get('profile_import_products_no_check_duplicates'), $this->language->get('profile_import_products_no_check_duplicates_help'), array('button_cancel' => false, 'button_confirm' => false)),
                                    'type' => 'boolean',
                                    'name' => 'no_check_duplicates',
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'columns' => 3
                                ),


                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>',
                                'name' => 'general_producs_conf_end'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_import_products_data_related_legend'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_import profile_export configuration products profile_import_products_data_related_legend',
                            ),

                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),

                                array(
                                    'label' => $this->language->get('profile_import_cat_number'),
                                    'type' => 'text',
                                    'name' => 'cat_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_cat_number', $this->language->get('profile_cat_number_remodal_title'), sprintf($this->language->get('profile_cat_number_remodal_description'), $this->get_image_link('excel_example_number_categories.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_cat_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_cat_tree_number_parent'),
                                    'type' => 'text',
                                    'name' => 'cat_tree_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_cat_tree_number_parent', $this->language->get('profile_cat_tree_number_parent_remodal_title'), sprintf($this->language->get('profile_cat_tree_number_parent_remodal_description'), $this->get_image_link('excel_example_number_categories_tree_parents.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_cat_tree_number_parent_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_cat_tree_number_children'),
                                    'type' => 'text',
                                    'name' => 'cat_tree_children_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_cat_tree_number_children', $this->language->get('profile_cat_tree_number_children_remodal_title'), sprintf($this->language->get('profile_cat_tree_number_children_remodal_description'), $this->get_image_link('excel_example_number_categories_tree_childrens.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_cat_tree_number_children_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_image_number'),
                                    'type' => 'text',
                                    'name' => 'image_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_image_number', $this->language->get('profile_import_image_number_remodal_title'), sprintf($this->language->get('profile_import_image_number_remodal_description'), $this->get_image_link('excel_example_number_images.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_image_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_attribute_number'),
                                    'type' => 'text',
                                    'name' => 'attribute_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_attribute_number', $this->language->get('profile_import_attribute_number_remodal_title'), sprintf($this->language->get('profile_import_attribute_number_remodal_description'), $this->get_image_link('excel_example_number_attributes.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_attribute_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_special_number'),
                                    'type' => 'text',
                                    'name' => 'special_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_special_number', $this->language->get('profile_import_special_number_remodal_title'), sprintf($this->language->get('profile_import_special_number_remodal_description'), $this->get_image_link('excel_example_number_specials.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_special_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_discount_number'),
                                    'type' => 'text',
                                    'name' => 'discount_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_discount_number', $this->language->get('profile_import_discount_number_remodal_title'), sprintf($this->language->get('profile_import_discount_number_remodal_description'), $this->get_image_link('excel_example_number_discounts.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_discount_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_filter_group_number'),
                                    'type' => 'text',
                                    'name' => 'filter_group_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_filter_group_number', $this->language->get('profile_import_filter_group_number_remodal_title'), sprintf($this->language->get('profile_import_filter_group_number_remodal_description'), $this->get_image_link('excel_example_number_filter_groups.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_filter_group_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_filter_number'),
                                    'type' => 'text',
                                    'name' => 'filter_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_filter_number', $this->language->get('profile_import_filter_number_remodal_title'), sprintf($this->language->get('profile_import_filter_number_remodal_description'), $this->get_image_link('excel_example_number_filters.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_filter_number_link')),
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_import_download_number'),
                                    'type' => 'text',
                                    'name' => 'download_number',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import profile_export configuration products',
                                    'after' => $this->get_remodal('profile_import_download_number', $this->language->get('profile_import_download_number_remodal_title'), sprintf($this->language->get('profile_import_download_number_remodal_description'), $this->get_image_link('excel_example_number_downloads.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_download_number_link')),
                                    'columns' => 3
                                ),
                                array(
                                    'label' => $this->language->get('profile_import_keep_store_assigns'),
                                    'type' => 'boolean',
                                    'name' => 'keep_store_assigns',
                                    'force_value' => 0,
                                    'class_container' => 'profile_import configuration products no_refresh_columns',
                                    'after' => $this->get_remodal('profile_import_keep_store_assigns', $this->language->get('profile_import_keep_store_assigns_remodal_title'), $this->language->get('profile_import_keep_store_assigns_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_keep_store_assigns_link')),
                                    'columns' => 3
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_export_sort_order'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_export configuration generic',
                            ),

                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                array(
                                    'type' => 'html_hard',
                                    'html_code' => '<div class="sort_order_configuration col-md-12"></div>',
                                    'class_container' => 'profile_export configuration generic sort_order_configuration'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),
                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_products_quick_filter'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_export configuration products',
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                 array(
                                    'label' => $this->language->get('profile_products_quick_filter_categories'),
                                    'help' => $this->language->get('profile_products_quick_filter_categories_help'),
                                    'type' => 'select',
                                    'name' => 'quick_filter_category_ids',
                                    'multiple' => true,
                                    'all_options' => true,
                                    'options' => $categories_select_format,
                                    'class_container' => 'profile_export configuration products no_refresh_columns',
                                    'columns' => 3
                                ),

                                array(
                                    'label' => $this->language->get('profile_products_quick_filter_manufacturers'),
                                    'help' => $this->language->get('profile_products_quick_filter_manufacturers_help'),
                                    'type' => 'select',
                                    'name' => 'quick_filter_manufacturer_ids',
                                    'multiple' => true,
                                    'all_options' => true,
                                    'options' => $manufacturers_select_format,
                                    'class_container' => 'profile_export configuration products no_refresh_columns',
                                    'columns' => 3
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_products_filters'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_export configuration generic',
                            ),

                            //Import filter legend
                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_products_import_filters'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_import configuration generic',
                            ),

                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                array(
                                    'type' => 'html_hard',
                                    'html_code' => '<div class="profile_export profile_import configuration generic filters_configuration col-md-12"></div>',
                                    'class_container' => 'profile_export profile_import configuration generic filters_configuration'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_products_columns'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_export profile_import configuration generic',
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">',
                                'name' => 'columns_mapping_begin'
                            ),
                                array(
                                    'type' => 'html_hard',
                                    'html_code' => '<div class="profile_export profile_import configuration generic columns_configuration col-md-12"></div>',
                                    'class_container' => 'profile_import profile_export configuration generic columns_configuration'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_import_categories_mapping'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_import configuration products',
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                array(
                                    'type' => 'html_hard',
                                    'html_code' => '<div class="profile_import configuration products categories_mapping_configuration col-md-12"></div>',
                                    'class_container' => 'profile_import configuration products categories_mapping_configuration'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_products_columns_fixed'),
                                'remove_border_button' => true,
                                'class_container' => 'profile_export configuration generic',
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                array(
                                    'type' => 'html_hard',
                                    'html_code' => '<div class="profile_export configuration generic columns_fixed_configuration col-md-12"></div>',
                                    'class_container' => 'profile_export configuration generic columns_fixed_configuration'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),

                            array(
                                'type' => 'legend',
                                'text' => $this->language->get('profile_save_legend'),
                                'class_container' => 'profile_import profile_export configuration generic legend_save_profile',
                                'remove_border_button' => true,
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="container_step">'
                            ),
                                array(
                                    'label' => $this->language->get('profile_import_profile_name'),
                                    'type' => 'text',
                                    'name' => 'profile_name',
                                    'class_container' => 'profile_import profile_export configuration generic profile_name'
                                ),

                                array(
                                    'type' => 'button',
                                    'label' => $this->language->get('profile_save_configuration_import'),
                                    'text' => '<i class="fa fa-floppy-o"></i> '.$this->language->get('profile_save_configuration_import'),
                                    'onclick' => 'profile_save(\'import\');',
                                    'class_container' => 'profile_import'
                                ),
                                array(
                                    'type' => 'button',
                                    'label' => $this->language->get('profile_save_configuration_export'),
                                    'text' => '<i class="fa fa-floppy-o"></i> '.$this->language->get('profile_save_configuration_export'),
                                    'onclick' => 'profile_save(\'export\');',
                                    'class_container' => 'profile_export'
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '</div>'
                        ),
                    array(
                        'type' => 'html_hard',
                        'html_code' => '</div>'
                    ),
                array(
                    'type' => 'html_hard',
                    'html_code' => '</div>'
                ),
            );

            if (is_file($this->assets_path . 'model_ie_pro_tab_profiles_function_get_fields_add_new_fields.php')){
                require_once($this->assets_path . 'model_ie_pro_tab_profiles_function_get_fields_add_new_fields.php');
                if (isset($new_fields))
                    $fields = $this->add_new_fields($fields, $new_fields);
            }

            if ($this->hasOptionsCombinations) {
                // .. installed
                $new_fields = array(
                    array(
                        'insert_after' => 'general_producs_conf_end',
                        'field_info' => array(
                            'type' => 'legend',
                            'text' => $this->language->get('profile_option_combinations_config_legend'),
                            'remove_border_button' => true,
                            'class_container' => 'profile_import profile_export configuration products',
                            'name' => 'option_combination_legend'
                        )
                    ),
                    array(
                        'insert_after' => 'option_combination_legend',
                        'field_info' =>  array(
                            'type' => 'html_hard',
                            'html_code' => '<div class="container_step">',
                            'name' => 'option_combination_html_div'
                        )
                    ),
                    array(
                        'insert_after' => 'option_combination_html_div',
                        'field_info' => array(
                            'label' => $this->language->get('profile_options_combinations_number_label'),
                            'type' => 'text',
                            'name' => 'option_combinations_number',
                            'force_value' => 2,
                            'default' => 2,
                            'class_container' => 'profile_import profile_export configuration products',
                            'after' => $this->get_remodal('profile_opt_cmb_number', $this->language->get('profile_options_combinations_number_remodal_title'), $this->language->get('profile_options_combinations_number_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_special_number_link')),
                            'columns' => 3
                        )),
                    array(
                        'insert_after' => 'option_combinations_number',
                        'field_info' => array(
                            'label' => $this->language->get('profile_options_combinations_images_number_label'),
                            'type' => 'text',
                            'name' => 'option_combinations_images_number',
                            'force_value' => 0,
                            'class_container' => 'profile_import profile_export configuration products',
//                            'after' => $this->get_remodal('profile_opt_cmb_images_number', $this->language->get('profile_options_combinations_images_number_remodal_title'), $this->language->get('profile_options_combinations_images_number_remodal_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_special_number_link')),
                            'columns' => 3
                        )),
                    array(
                        'insert_after' => 'option_combinations_images_number',
                        'field_info' => array(
                            'label' => $this->language->get('profile_options_combinations_discounts_number_label'),
                            'type' => 'text',
                            'name' => 'option_combinations_discounts_number',
                            'force_value' => 0,
                            'class_container' => 'profile_import profile_export configuration products',
                            'after' => $this->get_remodal('profile_import_special_number', $this->language->get('profile_import_special_number_remodal_title'), sprintf($this->language->get('profile_import_special_number_remodal_description'), $this->get_image_link('excel_example_number_specials.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_special_number_link')),
                            'columns' => 3
                        )),
                    array(
                        'insert_after' => 'option_combinations_discounts_number',
                        'field_info' => array(
                            'label' => $this->language->get('profile_options_combinations_specials_number_label'),
                            'type' => 'text',
                            'name' => 'option_combinations_specials_number',
                            'force_value' => 0,
                            'class_container' => 'profile_import profile_export configuration products',
                            'after' => $this->get_remodal('profile_import_special_number', $this->language->get('profile_import_special_number_remodal_title'), sprintf($this->language->get('profile_import_special_number_remodal_description'), $this->get_image_link('excel_example_number_specials.jpg')), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_special_number_link')),
                            'columns' => 3
                        )),
                    array(
                        'insert_after' => 'option_combinations_specials_number',
                        'field_info' => array(
                            'label' => $this->language->get('profile_options_combinations_prices_by_customer_group_label'),
                            'type' => 'boolean',
                            'name' => 'options_combinations_prices_by_customer_group',
                            'force_value' => 0,
                            'class_container' => 'profile_import profile_export configuration products',
                            'after' => $this->get_remodal('profile_opt_cmb_price_by_customer_group', $this->language->get('profile_options_combinations_prices_by_customer_group_title'), $this->language->get('profile_options_combinations_prices_by_customer_group_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_keep_store_assigns_link')),
                            'columns' => 3
                        )),
                    array(
                        'insert_after' => 'options_combinations_prices_by_customer_group',
                        'field_info' => array(
                            'label' => $this->language->get('profile_options_combinations_points_by_customer_group_label'),
                            'type' => 'boolean',
                            'name' => 'options_combinations_points_by_customer_group',
                            'force_value' => 0,
                            'class_container' => 'profile_import profile_export configuration products',
                            'after' => $this->get_remodal('profile_opt_cmb_points_by_customer_group', $this->language->get('profile_options_combinations_points_by_customer_group_title'), $this->language->get('profile_options_combinations_points_by_customer_group_description'), array('button_cancel' => false, 'button_confirm' => false, 'link' => 'profile_import_keep_store_assigns_link')),
                            'columns' => 3
                        )),
                    array(
                        'insert_after' => 'options_combinations_points_by_customer_group',
                        'field_info' => array(
                            'type' => 'html_hard',
                            'html_code' => '</div>'
                        ))
                );
                $fields = $this->add_new_fields($fields, $new_fields);
            }

            if (is_file($this->assets_path . 'import_xml_subproducts_inside_main_tag_add_new_fields.php')){
                require_once($this->assets_path . 'import_xml_subproducts_inside_main_tag_add_new_fields.php');
                if (isset($new_fields))
                    $fields = $this->add_new_fields($fields, $new_fields);
            }

            return $fields;
        }

        public function add_new_fields($fields, $new_fields){
            foreach ($new_fields as $new_field){
                foreach ($fields as $index => $field){
                    if (isset($field['name']) && $field['name'] == $new_field['insert_after']){
                        $fields1 = array_slice($fields, 0, $index + 1);
                        $fields2 = array_slice($fields, $index + 1);
                        $fields1[] = $new_field['field_info'];
                        $fields = array_merge($fields1, $fields2);
                        break;
                    }
                }
            }
            return $fields;
        }

        function get_columns($profile_id, $from_ajax = false) {
            $profile = $this->{$this->model_profile}->load($profile_id, true);

            if(empty($profile['profile']))
                return array();

            //Add hidden fields when load a profile
            if(!empty($profile_id)) {
                $no_hidden_fields = array('custom_name', 'default_value', 'status');
                $final_columns = array();

                foreach ($profile['profile']['columns'] as $col_name => $col_info) {
                    $internal_configuration = json_decode(str_replace("'", '"', $col_info['internal_configuration']), true);
                    $profile['profile']['columns'][$col_name]['hidden_fields'] = array();
                    foreach ($internal_configuration as $input_name => $value) {
                        if(in_array($input_name, $no_hidden_fields))
                            $profile['profile']['columns'][$col_name][$input_name] = $value;
                        else
                            $profile['profile']['columns'][$col_name]['hidden_fields'][$input_name] = $value;
                    }
                    unset($profile['profile']['columns'][$col_name]['internal_configuration']);
                }
            }

            if($from_ajax) {
                echo json_encode($profile['profile']['columns']); die;
            }

            return $profile['profile']['columns'];
        }

        public function get_filters_from_profile($profile_id) {
            if(empty($profile_id))
                return array();

            $profile = $this->{$this->model_profile}->load($profile_id, true);
            $profile = isset($profile['profile']) ? $profile['profile'] : null;

            $result = [];

            if ($profile)
            {
                if (isset( $profile['export_filter'])){
                    $result = $profile['export_filter'];
                }
                else if (isset( $profile['filters_v2'])){
                    $result = explode( ',', $profile['filters_v2']);
                    $result = [
                       'filters' => $result,
                       'config' => ['main_conditional' => 'AND']
                    ];
                }
            }

            return $result;
        }

        public function get_columns_fixed_from_profile($profile_id) {
            if(empty($profile_id))
                return array();

            $profile = $this->{$this->model_profile}->load($profile_id, true);

            return array_key_exists('profile', $profile) && array_key_exists('export_custom_columns_fixed', $profile['profile']) ? $profile['profile']['export_custom_columns_fixed'] : array();
        }

        public function get_sort_order_from_profile($profile_id) {
            if(empty($profile_id))
                return array();

            $profile = $this->{$this->model_profile}->load($profile_id, true);
            return array_key_exists('profile', $profile) && array_key_exists('export_sort_order', $profile['profile']) ? $profile['profile']['export_sort_order'] : array();
        }

        private function get_profile_columns_html( $columns) {
            $suggestedColumns = $this->profile_import_analyze_columns();
            $columnsAnalyzed = count( $suggestedColumns) > 0;

            if (array_key_exists('profile_id', $this->request->post) && !empty($this->request->post['profile_id'])) {
                $profile_id = $this->request->post['profile_id'];
                $profile_columns = $this->get_columns($this->request->post);
                array_replace($profile_columns, $columns);
            }

            $html = '<div class="row">
                        <div class="form-group type_button columns_mapping_file col-md-12">';

            $fileUploader = new ColumnMappingFileUploader();
            $fileUploader->icon( 'table')
                         ->profile_type( $this->get_current_profile_type())
                         ->action( 'profile_analyze_columns_html')
                         ->button_text( $this->language->get( 'profile_import_columns_mapping_load_columns'))
                         ->button_class( 'button_columns_mapping')
                         ->js_on_change( 'update_get_columns_upload_field')
                         ->pre_processing_message( $this->language->get( 'profile_import_columns_not_analyzed_yet'))
                         ->post_processing_message( $this->language->get( 'profile_import_columns_already_analyzed'))
                         ->alert_message( $this->language->get( 'profile_import_categories_configure_columns_first_warning'))
                         ->processed( count( $suggestedColumns) > 0);

            $html .= $fileUploader->render();

            $html .= '    </div>
                      </div>';

            $html .= '<table class="table table-bordered table-hover">';

            $html .= $this->build_columns_table_header();
            $html .= $this->build_columns_table_body( $columns, $suggestedColumns);

            $html .= '</table>';

            return $html;
        }

        private function build_columns_table_header() {
            $html = '<thead>';

            if (count( $this->profiles_select) > 1) {
                $profile_id = array_key_exists('profile_id', $this->request->post) &&
                              !empty($this->request->post['profile_id'])
                              ? $this->request->post['profile_id']
                              : '';

                $profiles_select_copy = $this->profiles_select;

                if (!empty( $profile_id) && array_key_exists( $profile_id, $profiles_select_copy)) {
                    unset($profiles_select_copy[$profile_id]);
                }

                if (count($profiles_select_copy) > 1) {
                    $html .= '
                        <tr>
                            <td colspan="4" style="text-align: right;">' .
                                $this->language->get('profile_import_column_config_thead_clone_columns').'
                            </td>

                            <td colspan="2">
                                <select name="load_custom_names_from_profile" data-live-search="true"
                                        onchange="profile_get_custom_names_from_profile($(this));"
                                        class="selectpicker form-control">';

                    foreach ($profiles_select_copy as $key => $prof) {
                        $html .= '<option value="'.$key.'">'.$prof.'</option>';
                    }

                    $html .= '</select>';

                    $html .= $this->build_script_tag('
                                $(\'select[name="load_custom_names_from_profile"]\').selectpicker();
                             ');

                    $html .= '
                            </td>
                        </tr>';
                }
            }

            $html .= '
                <tr>
                    <td colspan="5" style="text-align: right;">
                        <a onclick="profile_disable_non_named_columns( $(this)); return false;"
                           class="button button_columns_mapping"
                           style="margin-right: 40px;">
                           <i class="fa fa-toggle-off"></i>' .
                           $this->language->get('profile_import_column_config_thead_disable_non_named') . '
                        </a>' .

                        $this->language->get('profile_import_column_config_thead_select_all') .'
                    </td>

                    <td>
                        <label class="checkbox_container">
                            <input onchange="profile_check_uncheck_all($(this))"
                                   name="columns_select_add" type="checkbox"
                                   class="ios-switch green" value="1"
                                   checked="selected">
                            <div>
                                <div></div>
                            </div>
                        </label>
                    </td>
                </tr>

                <tr>
                    <td style="width:85px;">'.$this->language->get('profile_import_column_config_thead_sort_order').'</td>
                    <td>'.$this->language->get('profile_import_column_config_thead_column').'</td>
                    <td>'.$this->language->get('profile_import_column_config_thead_column_custom_name').'</td>

                    <td style="width: 165px;">' .
                        $this->language->get('profile_import_column_config_thead_column_default_value').$this->get_remodal('columns_default_value', $this->language->get('columns_default_value_title'), $this->language->get('columns_default_value_description'),array('link' => 'columns_default_value_link', 'button_cancel' => false)).
                        '<br>'.
                        $this->language->get('profile_import_column_config_thead_column_conditional_value').$this->get_remodal('columns_conditional_value', $this->language->get('columns_conditional_value_title'), $this->language->get('columns_conditional_value_description'),array('link' => 'columns_conditional_value_link', 'button_cancel' => false)) . '
                    </td>

                    <td>'.$this->language->get('profile_import_column_config_thead_column_extra_configuration').'</td>
                    <td>'.$this->language->get('profile_import_column_config_thead_status').'</td>
                 </tr>';

            $html .= '</thead>';

            return $html;
        }

        private function build_columns_table_body( $columns, $suggestedColumns) {
            $nameFieldStyle = count( $suggestedColumns) > 0
                              ? "display: none;"
                              : '';

            $html = '<tbody>';

            foreach ($columns as $column_name => $col_info) {
                $checked = array_key_exists('status', $col_info) && $col_info['status'];
                $default_value = array_key_exists('default_value', $col_info)
                                 ? $col_info['default_value']
                                 : '';

                $conditional_value = array_key_exists('conditional_value', $col_info)
                                     ? $col_info['conditional_value']
                                     : '';

                $conditional_value = preg_replace( '/"/', '&quot;', $conditional_value);

                $internal_configuration = array('name' => $column_name);

                $hidden_fields = array_key_exists('hidden_fields', $col_info)
                                 ? $col_info['hidden_fields']
                                 : array();

                if (!empty($hidden_fields)) {
                    foreach ($hidden_fields as $input_name => $value) {
                        $internal_configuration[$input_name] = $value;
                    }
                }

                $show = !array_key_exists('only_for',$internal_configuration) ||
                        $internal_configuration['only_for'] == $this->profile_type;

                if (!$show) {
                    continue;
                }

                $extra_configuration = $this->get_profile_columns_html_extra_column_configuration(
                    $column_name,
                    $col_info
                );

                $fieldName = "columns[{$column_name}][custom_name]";

                $nameFieldValue = !empty($suggestedColumns) ?
                                  $this->fix_column_field_value( $col_info['hidden_fields']['name'], $suggestedColumns)
                                  : $col_info['custom_name'];

                $internal_config_value = str_replace('"', "'", json_encode( $internal_configuration));

                $html .= '
                    <tr>
                        <td class="draggable_element">
                            <i class="fa fa-reorder"></i>
                        </td>

                        <td>' .
                            $column_name . $this->get_possible_values($col_info) . '
                        </td>

                        <td>
                            <input type="hidden"
                                    name="columns['.$column_name.'][internal_configuration]"
                                    value="' . $internal_config_value . '">' .

                            $this->build_column_selector(
                                $fieldName,
                                $suggestedColumns,
                                $nameFieldValue
                            ) . '

                            <input placeholder="'.$this->language->get('profile_import_column_config_thead_column_custom_name').'"
                                    type="text" class="form-control custom_name"
                                    name="' . $fieldName . '"
                                    value="' . $nameFieldValue . '"
                                    style="' . $nameFieldStyle . '">
                        </td>

                        <td>
                            <input placeholder="'.$this->language->get('profile_import_column_config_thead_column_default_value').'" type="text" class="form-control default_value" name="columns['.$column_name.'][default_value]" value="'.$default_value.'"><input placeholder="'.$this->language->get('profile_import_column_config_thead_column_conditional_value').'" type="text" class="form-control conditional_value"
                                            name="columns['.$column_name.'][conditional_value]"
                                            value="'.$conditional_value.'">
                        </td>

                        <td class="extra_configuration">' .
                            $extra_configuration . '
                        </td>

                        <td>
                            <label class="checkbox_container">
                                <input name="columns['.$column_name.'][status]"
                                        type="checkbox" class="ios-switch green"
                                        value="1" ' . ($checked ? 'checked="selected"': '' ) . '>
                                <div>
                                  <div></div>
                                </div>
                            </label>
                        </td>
                    </tr>';
            }

            $html .= '</tbody>';

            return $html;
        }

        public function get_profile_columns_html_extra_column_configuration($column_name, $col_info) {
            $hidden_fields = array_key_exists('hidden_fields', $col_info) ? $col_info['hidden_fields'] : array();
            $is_boolean = array_key_exists('is_boolean', $hidden_fields) && $hidden_fields['is_boolean'];
            $allow_ids = array_key_exists('allow_ids', $hidden_fields) && $hidden_fields['allow_ids'];
            $allow_names = array_key_exists('allow_names', $hidden_fields) && $hidden_fields['allow_names'];

            $is_image = $this->profile_type == 'export' && array_key_exists('hidden_fields', $col_info) && array_key_exists('is_image', $col_info['hidden_fields']);
            $product_id_identificator = array_key_exists('product_id_identificator', $col_info) && $col_info['product_id_identificator'];
            $splitted_values = $this->profile_type == 'import' && array_key_exists('splitted_values', $col_info);
            $profit_margin = array_key_exists('profit_margin', $col_info);

            $table = $col_info['hidden_fields']['table'];
            $is_price = in_array($table, array('product', 'product_special', 'product_discount', 'product_option_value')) && in_array($col_info['hidden_fields']['field'], array('price')) ? true : false;
            $is_max_length_text = isset( $hidden_fields['allow_max_length']);
            $is_strip_html_tags = isset( $hidden_fields['strip_html_tags']);

            $is_delete_product_import = $this->profile_type == 'import' && in_array($table, array('empty_columns')) && in_array($col_info['hidden_fields']['field'], array('delete'));

            $extra_configuration = '';

            $extra_configuration_config = array('label_size' => 8);

            if ($is_delete_product_import){
                $delete_associated_images = array_key_exists('delete_associated_images', $col_info) ? $col_info['delete_associated_images'] : 0;
                $checkbox = $this->get_checkbox_html('columns['.$column_name.'][delete_associated_images]', $delete_associated_images);
                $extra_configuration_config['class_label'] = 'checkbox_label';
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_delete_associated_images'), $checkbox, $extra_configuration_config);
            }

            if($is_price) {
                $round = array_key_exists('round', $col_info) ? $col_info['round'] : 0;
                $checkbox = $this->get_checkbox_html('columns['.$column_name.'][round]', $round);
                $extra_configuration_config['class_label'] = 'checkbox_label';
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_round'), $checkbox, $extra_configuration_config);
            }

            if($is_strip_html_tags) {
                $html_tags = array_key_exists('html_tags', $col_info) && !empty( trim( $col_info['html_tags'])) ? $col_info['html_tags'] : '';
                $help = $this->get_tooltip_help_html( $this->language->get('profile_column_config_extra_configuration_strip_html_tags_help'));
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_strip_html_tags').$help, '<input class="form-control extra_column_configuration" name="columns['.$column_name.'][html_tags]" type="text" value="'.$html_tags.'">', $extra_configuration_config);
            }

            if($is_max_length_text) {
                $max_length = array_key_exists('max_length', $col_info) && !empty( trim( $col_info['max_length'])) ? $col_info['max_length'] : '';
                $help = $this->get_tooltip_help_html( $this->language->get('profile_column_config_extra_configuration_max_length_help'));
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_max_length') . $help, '<input class="form-control extra_column_configuration" name="columns['.$column_name.'][max_length]" type="text" value="'.$max_length.'">', $extra_configuration_config);
            }

            if($splitted_values) {
                $value = array_key_exists('splitted_values', $col_info) ? $col_info['splitted_values'] : '';
                if (strpos($value, '&') !== false) {
                    $value = str_replace('&', '&amp;', $value);
                }
                $info_remodal = $this->get_remodal('profile_column_config_extra_splitted_values', $this->language->get('profile_column_config_extra_splitted_values_title'), sprintf($this->language->get('profile_column_config_extra_splitted_values_description'), $this->get_image_link('splitted_values_example.jpg')),array('link' => 'profile_column_config_extra_splitted_values_link', 'button_cancel' => false));
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_splitted_values').$info_remodal, '<input class="form-control extra_column_configuration" name="columns['.$column_name.'][splitted_values]" type="text" value="'.$value.'">', $extra_configuration_config);
            }

            if($allow_ids) {
                $id_instead_of_name = array_key_exists('id_instead_of_name', $col_info) ? $col_info['id_instead_of_name'] : 0;
                $checkbox = $this->get_checkbox_html('columns['.$column_name.'][id_instead_of_name]', $id_instead_of_name);
                $extra_configuration_config['class_label'] = 'checkbox_label';
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_id_instead_of_name'), $checkbox, $extra_configuration_config);
            }

            if($is_boolean) {
                $true_value = array_key_exists('true_value', $col_info) ? $col_info['true_value'] : 1;
                $false_value = array_key_exists('false_value', $col_info) ? $col_info['false_value'] : '0';
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_value_true'), '<input class="form-control extra_column_configuration" name="columns['.$column_name.'][true_value]" type="text" value="'.$true_value.'">', $extra_configuration_config);
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_value_false'), '<input class="form-control extra_column_configuration" name="columns['.$column_name.'][false_value]" type="text" value="'.$false_value.'">', $extra_configuration_config);
            }
            elseif($is_image) {
                $image_full_link = array_key_exists('image_full_link', $col_info) ? $col_info['image_full_link'] : 0;
                $checkbox = $this->get_checkbox_html('columns['.$column_name.'][image_full_link]', $image_full_link);
                $extra_configuration_config['class_label'] = 'checkbox_label';
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_image_link'), $checkbox, $extra_configuration_config);
            } else if($allow_names || (in_array($column_name, array('Out stock status', 'Weight class', 'Length class', 'Tax class')) || strstr($column_name, 'Layout'))) {
                $name_instead_id = array_key_exists('name_instead_id', $col_info) ? $col_info['name_instead_id'] : 0;
                $checkbox = $this->get_checkbox_html('columns['.$column_name.'][name_instead_id]', $name_instead_id);
                $extra_configuration_config['class_label'] = 'checkbox_label';
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_names_instead_of_id'), $checkbox, $extra_configuration_config);
            } else if ($product_id_identificator) {
                $extra_configuration_config = array('label_size' => 6);
                $copy_prod_identificators = $this->product_identificators;
                $extra = array('class' => 'selectpicker form-control');
                $value = array_key_exists('product_id_identificator', $col_info) && !empty($col_info['product_id_identificator']) ? $col_info['product_id_identificator'] : 'product_id';
                $field_select = $this->select_constructor('columns['.$column_name.'][product_id_identificator]', $copy_prod_identificators, $value, $extra);
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_product_id_identificator'), $field_select, $extra_configuration_config);
            } else if($profit_margin) {
                $help = $this->get_tooltip_help_html($this->language->get('profile_column_config_extra_configuration_profit_margin_help'));
                $extra_configuration .= $this->get_field_html($this->language->get('profile_column_config_extra_configuration_profit_margin').$help, '<input class="form-control extra_column_configuration" name="columns['.$column_name.'][profit_margin]" type="text" value="'.$col_info['profit_margin'].'">', $extra_configuration_config);
            }

            if(is_file($this->assets_path.'model_ie_pro_tab_profiles_add_extra_configuration_fields.php'))
                require($this->assets_path.'model_ie_pro_tab_profiles_add_extra_configuration_fields.php');

            return $extra_configuration;
        }

        private function get_categories_mapping_html() {
            $controller = new CategoriesMappingPanelController( $this);

            try
            {
                $controller->execute();

                return $controller->get_result();
            }
            catch (\Exception $ex) {
                $this->output_error( $ex->getMessage());
            }
        }

        public function get_profile_categories_mapping_columns_html(){
            $categories_analyzer = new CategoriesAnalyzer( $this);

            try
            {
               $categories_analyzer->execute();

               return $categories_analyzer->get_result();
            }
            catch (\Exception $ex) {
                $this->output_error( $ex->getMessage());
            }
        }

        private function get_main_xml_nodes() {
            $analyzer = new XmlMainNodesAnalyzer( $this);
            $analyzer->execute();

            return $analyzer->get_result();
        }

        public function get_select_from_database_fields($database_fields, $empty_value = '', $is_an_import_profile = false) {
            $fields_to_select = array();
            if(!empty($empty_value)) {
                $fields_to_select[''] = $empty_value;
            }
            foreach ($database_fields as $table_name => $fields) {
                if (!$is_an_import_profile)
                    $table_name_formatted = $this->get_legible_database_field_name($table_name);
                foreach ($fields as $field_name => $field_info) {
                    $field_name_formatted = $this->get_legible_database_field_name($field_name);
                    $type = $field_info['type'];
                    if (!$is_an_import_profile){
                        $final_name = $table_name_formatted.' - '.$field_name_formatted.' ('.$type.')';
                        $fields_to_select[$table_name.'-'.$field_name.'-'.$type] = $final_name;
                    }
                    else{
                        foreach ($field_info['col_names'] as $final_name) {
                            $final_name_formatted = str_replace(' ', '_', $final_name);
                            if (isset($field_info['allow_ids']) && $field_info['allow_ids']){
                                $fields_to_select[$table_name . '-' . $field_name . '-' . $final_name_formatted . '-' . $type . '-' . 'allow_ids'] = $final_name;
                            }
                            else
                                $fields_to_select[$table_name . '-' . $field_name . '-' . $final_name_formatted . '-' . $type] = $final_name;
                        }
                    }
                }
            }

            return $fields_to_select;
        }

        public function get_profile_filters_html($database_fields, $is_an_import_profile = false, $element_type = null) {
            $profile_id = array_key_exists('profile_id', $this->request->post) && !empty($this->request->post['profile_id']) ? $this->request->post['profile_id'] : '';
            $config_filters = $this->get_filters_from_profile($profile_id);
            $filters_num = 0; // !empty($config_filters['filters']) ? count($config_filters['filters']) : 0;
            $main_conditional = !empty($config_filters) && array_key_exists('main_conditional', $config_filters['config']) ? $config_filters['config']['main_conditional'] : 'AND';

            $fields_to_select = $this->get_select_from_database_fields($database_fields, $empty_value = '', $is_an_import_profile);

            $button_add_filter = '<a href="javascript:{}" onclick="FilterManager.addFilter( $(this));" class="button" title="'.$this->language->get('profile_products_filters_add_filter').'" style="padding: 5px 12px;"><i class="fa fa-plus-square" aria-hidden="true"></i></a>';

            if (!$is_an_import_profile)
            {
              $button_open_group = '<a href="javascript:{}" onclick="FilterManager.openGroup();" class="button open_group disabled" title="'.$this->language->get('profile_products_filters_open_group').'" style="padding: 5px 12px; margin-left: 5px;"><i class="fa fa-indent" aria-hidden="true"></i></a>';
              $button_close_group = '<a href="javascript:{}" onclick="FilterManager.closeGroup();" class="button close_group disabled" title="'.$this->language->get('profile_products_filters_close_group').'" style="padding: 5px 12px; margin-left: 5px;"><i class="fa fa-dedent" aria-hidden="true"></i></a>';
            }

            $button_remove_filter = '<a href="javascript:{}" onclick="FilterManager.removeFilter( $(this));" class="button danger" title="'.$this->language->get('profile_products_filters_remove_filter').'" style="padding: 5px 12px;"><i class="fa fa-minus-square" aria-hidden="true"></i></a>';

            $span_add_filter = !$is_an_import_profile ? 3 : 5;
            $html = '
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <td colspan="'.$span_add_filter.'" style="text-align:right;">' . $this->language->get('profile_products_filters_add_filter').'</td>
                        <td style="width: '.($is_an_import_profile ? '66px' : '142px').';">'.$button_add_filter . (!$is_an_import_profile ? $button_open_group . $button_close_group : '') . '</td>
                    </tr>
                    <tr>
                        <td>'.$this->language->get('profile_products_filters_thead_field').'</td>
                        <td>'.$this->language->get('profile_products_filters_thead_condition').'</td>
                        <td>'.$this->language->get('profile_products_filters_thead_value').'</td>
                        <td>'.$this->language->get('profile_products_filters_thead_actions').'</td>';

                        $html .= $is_an_import_profile ? '<td>'.$this->language->get('profile_products_filters_thead_apply_to').'</td><td>'.$this->language->get('profile_products_filters_thead_actions').'</td>' : '';

                        $html .= '
                    </tr>
                </thead>
                <tbody>';
                    $model_row = $this->get_filter_row('replace_by_number', $fields_to_select, array(), $button_remove_filter, true, $filters_num, $is_an_import_profile, $element_type);

                    if ($is_an_import_profile && !empty($config_filters['filters']))
                    {
                        $html .= str_replace('<tr', '<tr data-filternumber="'.(count($config_filters['filters'])).'"', $model_row);
                        foreach ($config_filters['filters'] as $key => $config_filter) {
                          $html .= $this->get_filter_row($key, $fields_to_select, $config_filter, $button_remove_filter, false, false, $is_an_import_profile, $element_type);
                        }
                    } else
                        $html .= $model_row;
                $html .= '</tbody>
                <tfoot style="display:none;">'.
                (!$is_an_import_profile ?
                    '<tr>
                        <td colspan="2" style="text-align: right; line-height: 36px;">'.$this->language->get('profile_products_filters_main_conditional').'</td>
                        <td colspan="2">'.$this->select_constructor('export_filter[main_conditional]', $this->conditionals, $main_conditional).'
                    </tr>' : '')
                .'</tfoot>
            </table>';


            if (!$is_an_import_profile)
            {
                $html .= '<div class="expression_view"
                               style="display: none;"
                               title="Click to show/hide Expression View">
                            <div style="cursor: pointer; background-color: #0db7ef; color: white; border-radius: 10px 10px 0px 0px; font-size: 20px;"
                                 onclick="FilterManager.toggleExpressionView();">
                              <i class="fa fa-angle-down fa-size-lg" style="margin-left: 10px"></i>
                              Expression View
                            </div>

                            <div class="filter-expr-text"
                                 style="display: none; border-right: solid #ddd 1px; border-left: solid #ddd 1px; border-bottom: solid #ddd 1px; border-radius: 0px 0px 10px 10px; padding: 10px;">
                            </div>
                          </div>';
                $filter_config = $this->build_filters_config( $config_filters, $fields_to_select, $button_remove_filter, $is_an_import_profile, $element_type);
                $html .= '<script>FilterManager.setInitialFilters( ' . json_encode( $filter_config) . '); FilterManager.buildFiltersTable();</script>';
            }

            return $html;
        }

        function select_constructor_allow_ids($select_name, $values, $value, $extra = array()){
            $onchange = array_key_exists('onchange', $extra) ? ' onchange="'.$extra['onchange'].'" ' : '';
            $class = array_key_exists('class', $extra) ? ' class="'.$extra['class'].'" ' : '';

            $select = '<select name="'.$select_name.'"'.$class.$onchange.'data-live-search="true">';
            foreach ($values as $option_value => $option_name) {
                $valueArr = explode('-', $value);
                $optionValueArr = explode('-', $option_value);
                $valuesEquals = $value != '' && $valueArr[0] == $optionValueArr[0] && $valueArr[1] == $optionValueArr[1] && $valueArr[2] == $optionValueArr[2];
                $allow_ids_ennabled = ($valuesEquals && isset($valueArr[4]) && $valueArr[4] == 'allow_ids' && $valueArr[3] == 'number');
                $select .= '<option '.( $valuesEquals ? 'selected="selected"' : '').' value="'. ( $valuesEquals ? $value : $option_value).'"'. ($allow_ids_ennabled ? 'allow-ids="true"' : '') . '>'.$option_name.'</option>';
            }
            $select .= '</select>';

            return $select;
        }

        function get_filter_row($number, $fields_to_select, $filter_config, $button_remove_filter, $is_model = false, $filter_number = false, $is_an_import_profile = false, $element_type = null) {
            $extra = array('class' => 'selectpicker form-control conditional field', 'onchange' => 'FilterManager.resetFilterRow($(this).closest(\'tr\'))');

            $value = !empty($filter_config) && array_key_exists('field', $filter_config) ? $filter_config['field'] : '';
            if ($is_an_import_profile)
                $field_select = $this->select_constructor_allow_ids('export_filter['.$number.'][field]', $fields_to_select, $value, $extra);
            else
                $field_select = $this->select_constructor('export_filter['.$number.'][field]', $fields_to_select, $value, $extra);
            $switch_allow_ids = "<div class='switch-allow-ids' style=\"display: none;\"><label class=\"checkbox_container\" style=\"width: 100%;\">{$this->language->get('profile_switch_allow_ids_label')} <input type=\"checkbox\" class=\"ios-switch green\" value=\"0\" onchange='update_field_type($(this))'/> <div style=\"display: inline-block;\"><div></div></div></label></div>";

            $conditionals_selects = '';
            foreach ($this->filter_field_types as $key => $type) {
                $value = !empty($filter_config) && array_key_exists($type, $filter_config['conditional']) ? $filter_config['conditional'][$type] : '';
                $extra = array('class' => 'selectpicker form-control conditional '.$type);
                $conditionals_selects .= $this->select_constructor('export_filter['.$number.'][conditional]['.$type.']', $this->{'filter_conditionals_'.$type}, $value, $extra);
            }

            if ($is_an_import_profile){
                $actions_to_select = ['delete' => $this->language->get('profile_products_filters_actions_delete'), 'skip' => $this->language->get('profile_products_filters_actions_skip')];
                if ($element_type == 'products') {
                    $actions_to_select['disable'] = $this->language->get('profile_products_filters_actions_disable');
                    $actions_to_select['set_0'] = $this->language->get('profile_products_filters_actions_set_0');

                }
                $value = !empty($filter_config) && array_key_exists('action', $filter_config) ? $filter_config['action'] : '';
                $actions_select = $this->select_constructor('export_filter['.$number.'][action]', $actions_to_select, $value, $extra);

                $value = !empty($filter_config) && array_key_exists('applyto', $filter_config) ? $filter_config['applyto'] : '';
                $apply_to_select_elemets = ['file' => $this->language->get('profile_products_filters_applyto_file'), 'shop' => $this->language->get('profile_products_filters_applyto_shop')];
                $apply_to_select = $this->select_constructor('export_filter['.$number.'][applyto]', $apply_to_select_elemets, $value, $extra);
            }

            $value = !empty($filter_config) && array_key_exists('value', $filter_config) ? $filter_config['value'] : '';
            $value_inputs = '<input name="export_filter['.$number.'][value]" type="text" class="form-control" value="'.$value.'">';

            return '<tr'.($is_model ? ' data-filterNumber="'.$filter_number.'" class="filter_model"': '').'>
                            <td class="fields">'.$field_select. (($is_an_import_profile) ? $switch_allow_ids : '') . '</td>
                            <td class="conditionals">'.$conditionals_selects.'</td>
                            <td class="values">'.$value_inputs.'</td>'
                            . ($is_an_import_profile ? '<td class="actions">'.$actions_select.'</td>' . '<td class="applyto">'.$apply_to_select.'</td>' : '' ).
                            '<td class="remove">'.$button_remove_filter.'</td>
                        </tr>';
        }

        function get_custom_column_fixed_row($number, $custom_columns_fixed_config, $button_remove, $is_model = false, $columns_number = false) {
            $value = !empty($custom_columns_fixed_config) && array_key_exists('name', $custom_columns_fixed_config) ? $custom_columns_fixed_config['name'] : '';
            $name_input = '<input name="export_custom_columns_fixed['.$number.'][name]" type="text" class="form-control" placeholder="'.$this->language->get('profile_products_columns_fixed_column_name').'" value="'.$value.'">';

            $value = !empty($custom_columns_fixed_config) && array_key_exists('value', $custom_columns_fixed_config) ? $custom_columns_fixed_config['value'] : '';
            $value_input = '<input name="export_custom_columns_fixed['.$number.'][value]" type="text" class="form-control" placeholder="'.$this->language->get('profile_products_columns_fixed_column_value').'" value="'.$value.'">';

            $value = !empty($custom_columns_fixed_config) && array_key_exists('sort_order', $custom_columns_fixed_config) ? $custom_columns_fixed_config['sort_order'] : 0;
            $sort_order_input = '<input name="export_custom_columns_fixed['.$number.'][sort_order]" type="text" class="form-control" placeholder="'.$this->language->get('profile_products_columns_fixed_column_sort_order').'" value="'.$value.'">';

            return '<tr'.($is_model ? ' data-customColumnFixedNumber="'.$columns_number.'" class="custom_column_fixed_model"': '').'>
                            <td class="name">'.$name_input.'</td>
                            <td class="value">'.$value_input.'</td>
                            <td class="sort_order">'.$sort_order_input.'</td>
                            <td class="remove">'.$button_remove.'</td>
                        </tr>';
        }

        public function get_profile_columns_fixed_html($database_fields) {
            $profile_id = array_key_exists('profile_id', $this->request->post) && !empty($this->request->post['profile_id']) ? $this->request->post['profile_id'] : '';
            $config_custom_columns_fixed = $this->get_columns_fixed_from_profile($profile_id);

            $columns_num = !empty($config_custom_columns_fixed) ? count($config_custom_columns_fixed) : 0;

            $button_add_column = '<a href="javascript:{}" onclick="profile_add_column_fixed($(this));" class="button" title="'.$this->language->get('profile_products_columns_fixed_add').'"><i class="fa fa-plus-square" aria-hidden="true"></i></a>';
            $button_remove_column = '<a href="javascript:{}" onclick="profile_remove_column_fixed($(this));" class="button danger" title="'.$this->language->get('profile_products_columns_fixed_remove').'"><i class="fa fa-minus-square" aria-hidden="true"></i></a>';

            $html = '
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <td colspan="3" style="text-align:right;">'.$this->language->get('profile_products_columns_fixed_add_explain').'</td>
                        <td style="width: 68px;">'.$button_add_column.'</td>
                    </tr>
                    <tr>
                        <td>'.$this->language->get('profile_products_columns_fixed_column_name').'</td>
                        <td>'.$this->language->get('profile_products_columns_fixed_column_value').$this->get_remodal('columns_fixed_column_value', $this->language->get('columns_fixed_column_value_title'), $this->language->get('columns_fixed_column_value_description'),array('link' => 'columns_fixed_column_value_link', 'button_cancel' => false)).'</td>
                        <td>'.$this->language->get('profile_products_columns_fixed_column_sort_order').$this->get_remodal('columns_fixed_column_sort_order', $this->language->get('columns_fixed_column_sort_order_title'), $this->language->get('columns_fixed_column_sort_order_description'),array('link' => 'columns_fixed_column_sort_order_link', 'button_cancel' => false)).'</td>
                        <td>'.$this->language->get('profile_products_columns_fixed_column_actions').'</td>
                    </tr>
                </thead>
                <tbody>';
                    $html .= $this->get_custom_column_fixed_row('replace_by_number', array(), $button_remove_column, true, $columns_num);
                    if(!empty($config_custom_columns_fixed)) {
                        foreach ($config_custom_columns_fixed as $key => $config_column) {
                            $html .= $this->get_custom_column_fixed_row($key, $config_column, $button_remove_column);
                        }
                    }
                $html .= '</tbody>
            </table>
            ';

            return $html;
        }

        public function get_profile_sort_order_html($database_fields) {
            $fields_to_select = $this->get_select_from_database_fields($database_fields, $this->language->get('profile_export_sort_order_none'));
            $extra = array('class' => 'selectpicker form-control');
            $profile_id = array_key_exists('profile_id', $this->request->post) && !empty($this->request->post['profile_id']) ? $this->request->post['profile_id'] : '';
            $sort_order_config = $this->get_sort_order_from_profile($profile_id);
            $table_field_value = !empty($sort_order_config) && array_key_exists('table_field', $sort_order_config) ? $sort_order_config['table_field'] : '';

            if (!empty($this->request->post['export_sort_order']['table_field']))
                $table_field_value = $this->request->post['export_sort_order']['table_field'];

            $field_select = $this->select_constructor('export_sort_order[table_field]', $fields_to_select, $table_field_value, $extra);

            $select_html = $this->get_field_html($this->language->get('profile_export_sort_order_table_field'), $field_select, array('class' => 'profile_export configuration generic sort_order_configuration'));

            $fields_to_select = array(
                'ASC' => $this->language->get('profile_export_sort_order_asc'),
                'DESC' => $this->language->get('profile_export_sort_order_desc'),
            );
            $sort_order_value = !empty($sort_order_config) && array_key_exists('sort_order', $sort_order_config) ? $sort_order_config['sort_order'] : '';

            if (!empty($this->request->post['export_sort_order']['sort_order']))
                $sort_order_value = $this->request->post['export_sort_order']['sort_order'];

            $field_select = $this->select_constructor('export_sort_order[sort_order]', $fields_to_select, $sort_order_value, $extra);
            $select_html .= $this->get_field_html($this->language->get('profile_export_sort_order_order'), $field_select,  array('class' => 'profile_export configuration generic sort_order_configuration'));

            if (!empty($table_field_value)){
                list($table, $field, $field_type) = explode('-', $table_field_value);
                if (isset($database_fields[$table]['language_id']) && $field != 'language_id'){
                    $this->load->model('localisation/language');
                    $languages = $this->model_localisation_language->getLanguages();
                    $languages_map = [];
                    foreach ($languages as $language){
                        $languages_map[$language['language_id']] = "{$language['name']} ({$language['code']})";
                    }
                    $value = !empty($sort_order_config) && array_key_exists('language_id', $sort_order_config) ? $sort_order_config['language_id'] : '';
                    $field_select = $this->select_constructor('export_sort_order[language_id]', $languages_map, $value, $extra);
                    $select_html .= $this->get_field_html($this->language->get('profile_export_sort_language'), $field_select,  array('class' => 'profile_export configuration generic sort_order_configuration'));
                }
            }

            return $select_html;
        }

        function get_field_html($label, $field, $extra_config = array()) {
            $form_group_class = array_key_exists('class', $extra_config) ? $extra_config['class'] : '';
            $label_class = array_key_exists('class_label', $extra_config) ? $extra_config['class_label'] : '';
            $label_size = array_key_exists('label_size', $extra_config) ? $extra_config['label_size'] : 2;
            $content_size = 12 - $label_size;

            $field_html = '<div class="form-group '.$form_group_class.'">
                <label class="col-md-'.$label_size.' '.$label_class.' control-label">'.$label.'</label>
                <div class="col-md-'.$content_size.'">'.$field.'</div>
            </div>';

            return $field_html;
        }

        function get_tooltip_help_html($message) {
            $tooltip = '<span data-toggle="tooltip" data-html="true" title="" data-original-title="'.$message.'"></span>';
            return $tooltip;
        }

        function get_checkbox_html($name, $checked = false) {
            $field_html = '<label class="checkbox_container"><input name="'.$name.'" type="checkbox" class="ios-switch green" value="1" '.($checked ? 'checked="checked"' : '').'><div><div></div></div></label>';

            return $field_html;
        }

        function get_possible_values($col_info) {
            $possible_values = '';
            $modal_options = array('button_cancel' => false, 'button_confirm' => false, 'link' => $this->possible_values_text);

            $is_image = array_key_exists('hidden_fields', $col_info) && array_key_exists('is_image', $col_info['hidden_fields']);
            $column_name = $col_info['hidden_fields']['name'];

            if($is_image) {
                $possible_values = array(
                    sprintf($this->language->get('profile_products_columns_possible_values_image_local'),$this->image_path, $this->image_path),
                    sprintf($this->language->get('profile_products_columns_possible_values_image_external'),$this->image_path),
                );
            } else {
                switch ($column_name) {
                    case 'Tax class':
                        $possible_values = array();
                        foreach ($this->tax_classes as $key => $tax_class) {
                             $possible_values[] = '<b>'.$tax_class['tax_class_id'].'</b>: '.$tax_class['title'].' ('.$tax_class['description'].')';
                        }
                    break;
                    case 'Out stock status':
                        $possible_values = array();
                        foreach ($this->stock_statuses as $key => $stock_status) {
                             $possible_values[] = '<b>'.$this->language->get('profile_products_columns_possible_values_id').':</b> '.$stock_status['stock_status_id'].' - <b>'.$this->language->get('profile_products_columns_possible_values_name').':</b> '.$stock_status['name'];
                        }
                    break;
                    case 'Products related':
                        $possible_values = array($this->language->get('profile_products_columns_possible_values_products_related'));
                    break;
                    case 'Option type':
                        $possible_values = array(
                            'select',
                            'radio',
                            'checkbox',
                            'image',
                            'text',
                        );
                    break;
                    case 'Option price prefix':case 'Option points prefix':case 'Option weight prefix':
                        $possible_values = array(
                            '+',
                            '-',
                        );
                    break;
                    case 'Store':
                        $possible_values = array(
                            $this->language->get('profile_products_columns_possible_values_stores')
                        );
                        foreach ($this->stores_import_format as $key => $store_info) {
                            $possible_values[] = '<b>'.$store_info['store_id'].'</b>: '.$store_info['name'];
                        }
                    break;
                    case 'Weight class':
                        $possible_values = array();
                        foreach ($this->weight_classes as $key => $weight_class) {
                            $possible_values[] = '<b>'.$this->language->get('profile_products_columns_possible_values_id').':</b> '.$weight_class['weight_class_id'].' - <b>'.$this->language->get('profile_products_columns_possible_values_name').':</b> '.$weight_class['title'];
                        }
                    break;
                    case 'Length class':
                        $possible_values = array();
                        foreach ($this->length_classes as $key => $length_class) {
                            $possible_values[] = '<b>'.$this->language->get('profile_products_columns_possible_values_id').':</b> '.$length_class['length_class_id'].' - <b>'.$this->language->get('profile_products_columns_possible_values_name').':</b> '.$length_class['title'];
                        }
                    break;

                    case 'Parent id':
                        $possible_values = array($this->language->get('profile_categories_columns_possible_values_parent_id'));
                    break;

                    case 'Filters':
                        $possible_values = array(sprintf($this->language->get('profile_categories_columns_possible_values_filters'), $this->default_language_code));
                    break;

                    case strstr($column_name, 'Layout'):
                        $possible_values = array();
                        foreach ($this->layouts as $layout_id => $layout_name) {
                            $possible_values[] = '<b>'.$this->language->get('profile_products_columns_possible_values_id').':</b> '.$layout_id.' - <b>'.$this->language->get('profile_products_columns_possible_values_name').':</b> '.$layout_name;
                        }

                    break;
                }
            }

            if(!empty($possible_values)) {
                $possible_values = '<ul><li>'.implode('</li><li>', $possible_values).'</li></ul>';
                $remodal_identificator = 'possible_value_'.$this->format_column_name($column_name);
                $possible_values = ' - '.$this->get_remodal($remodal_identificator, $this->language->get($this->possible_values_text), $possible_values, $modal_options);
            }
            return is_array($possible_values) ? '' : $possible_values;
        }

        function _check_ajax_function($function_name) {
            switch ($function_name) {
                case 'get_columns_html':
                    if($this->profile == '')
                        $this->profile = $this->request->post;

                    $this->create_progress_file();
                    $type = array_key_exists('import_xls_i_want', $this->request->post) ? $this->request->post['import_xls_i_want'] : die('No import_xls_i_want data');
                    $this->profile_type = array_key_exists('profile_type', $this->request->post) ? $this->request->post['profile_type'] : 'export';
                    $model_name = 'ie_pro_'.$type;
                    $model_path = 'extension/module/'.$model_name;
                    $model_loaded = 'model_extension_module_'.$model_name;
                    $this->load->model($model_path);
                    $columns = $this->{$model_loaded}->get_columns($this->request->post);
                    $columns_html = $this->get_profile_columns_html($columns);
                    $array_return = array('html' => $columns_html);

                    $this->output_json( $array_return);
                    break;

                case 'get_filters_html':
                case 'get_columns_fixed_html':
                    $category = array_key_exists('import_xls_i_want', $this->request->post) ? $this->request->post['import_xls_i_want'] : die('No import_xls_i_want data');
                    $this->load->model('extension/module/ie_pro_database');
                    $tables = $this->model_extension_module_ie_pro_database->get_database($category, array('is_filter' => true));
                    if ($function_name == 'get_filters_html'){
                        $profile_type = array_key_exists('profile_type', $this->request->post) ? $this->request->post['profile_type'] : 'export';
                        if ($profile_type == 'export'){
                            $columns_html = $this->get_profile_filters_html($tables[$category]);
                        }
                        else{
                            $type = array_key_exists('import_xls_i_want', $this->request->post) ? $this->request->post['import_xls_i_want'] : die('No import_xls_i_want data');
                            $model_name = 'ie_pro_'.$type;
                            $model_path = 'extension/module/'.$model_name;
                            $model_loaded = 'model_extension_module_'.$model_name;
                            $this->load->model($model_path);
                            $columns = $this->{$model_loaded}->get_columns($this->request->post);
                            $columns = $this->map_columns_to_import_filters($columns);
                            $columns_html = $this->get_profile_filters_html($columns, true, $type);
                        }
                    }
                    else {
                        $columns_html = $this->get_profile_columns_fixed_html($tables[$category]);
                    }

                    $array_return = array('html' => $columns_html);

                    $this->output_json( $array_return);
                    break;

                case 'get_sort_order_html':
                    $category = array_key_exists('import_xls_i_want', $this->request->post) ? $this->request->post['import_xls_i_want'] : die('No import_xls_i_want data');
                    $this->load->model('extension/module/ie_pro_database');
                    $tables = $this->model_extension_module_ie_pro_database->get_database($category, array('is_filter' => true));
                    $columns_html = $this->get_profile_sort_order_html($tables[$category]);
                    $array_return = array('html' => $columns_html);

                    $this->output_json( $array_return);
                    break;

                case 'get_categories_mapping_html':
                    $html = $this->get_categories_mapping_html();

                    $this->output_json( ['html' => $html]);
                    break;

                case 'get_categories_mapping_columns_html':
                    $html = $this->get_profile_categories_mapping_columns_html();

                    $this->output_json( ['html' => $html]);
                    break;

                case 'get_main_xml_nodes':
                    $this->output_json( $this->get_main_xml_nodes());
                    break;

                case 'profile_save':
                    try {
                        $this->check_profile_mapping_columns_data();

                        $this->{$this->model_profile}->save();
                    } catch (\Exception $ex) {
                        $this->output_error( $ex->getMessage());
                    }
                    break;

                case 'profile_delete':
                    $this->{$this->model_profile}->delete();
                    break;

                case 'profile_download':
                    $this->{$this->model_profile}->download();
                    break;

                case 'profile_upload':
                    $this->{$this->model_profile}->upload();
                    break;

                case 'get_columns':
                    $profile_id = array_key_exists('profile_id', $this->request->post) ? $this->request->post['profile_id'] : die('No profile_id data');
                    $this->get_columns($profile_id, true);
                    break;

                case 'spread_sheet_upload_json':
                    $this->spread_sheet_upload_json();
                    break;
            }
        }

        function map_columns_to_import_filters($columns){
            $final_filters = [];
            foreach ($columns as $name => $value){

                if (array_key_exists('only_for', $value['hidden_fields']) && $value['hidden_fields']['only_for'] != 'import' )
                    continue;

                $table_name = $value['hidden_fields']['table'];
                $field_name = $value['hidden_fields']['field'];
                if(array_key_exists('real_type', $value['hidden_fields'])) {
                    if (!array_key_exists($table_name, $final_filters) || !array_key_exists($field_name, $final_filters[$table_name])){
                        $final_filters[$table_name][$field_name] = array(
                            'is_filter' => true,
                            'type' => $value['hidden_fields']['type'],
                            'real_type' => $value['hidden_fields']['real_type'],
                            'col_names' => array(),
                        );
                        if (array_key_exists('allow_ids', $value['hidden_fields'])){
                            $final_filters[$table_name][$field_name]['type'] = 'string';
                            $final_filters[$table_name][$field_name]['real_type'] = 'varchar';
                            $final_filters[$table_name][$field_name]['allow_ids'] = $value['hidden_fields']['allow_ids'];
                        }
                    }
                    $final_filters[$table_name][$field_name]['col_names'][] = $value['hidden_fields']['name'];
                }
            }
            return $final_filters;
        }

        function spread_sheet_upload_json() {
            $array_return = array('error' => false, 'message' => $this->language->get('profile_import_spreadsheet_remodal_json_uploaded'));
            $file_tmp_name = array_key_exists('file', $_FILES) && array_key_exists('tmp_name', $_FILES['file']) ? $_FILES['file']['tmp_name'] : '';
            $file_name = array_key_exists('file', $_FILES) && array_key_exists('name', $_FILES['file']) ? $_FILES['file']['name'] : '';

            $this->validate_permiss();

            $extension_file =  strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if(!in_array($extension_file, array('json')))
            {
                $array_return['error'] = true;
                $array_return['message'] = $this->language->get('profile_import_spreadsheet_remodal_json_error_extension');
                echo json_encode($array_return); die;
            }

            if(!copy($file_tmp_name, $this->google_spreadsheet_json_file_path)) {
                $array_return['error'] = true;
                $array_return['message'] = $this->language->get('profile_import_spreadsheet_remodal_json_error_uploading');
                echo json_encode($array_return); die;
            }

            echo json_encode($array_return); die;
        }

        function spread_sheet_get_account_id() {
            if(file_exists($this->google_spreadsheet_json_file_path)) {
                $gdrive_config = file_get_contents($this->google_spreadsheet_json_file_path);
                $gdrive_config = json_decode($gdrive_config, true);
                $service_account_id = array_key_exists('client_email', $gdrive_config) ? $gdrive_config['client_email'] : $this->language->get('profile_import_spreadsheet_remodal_json_client_id_not_found');
            } else {
                $service_account_id = $this->language->get('profile_import_spreadsheet_remodal_json_client_id_not_file');
            }
            return $service_account_id;
        }
        function _send_custom_variables_to_view($variables) {
            $variables['get_columns_html_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_columns_html', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_columns_from_profile_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_columns', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_filters_html_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_filters_html', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_columns_fixed_html_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_columns_fixed_html', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_sort_order_html_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_sort_order_html', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_categories_mapping_html_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_categories_mapping_html', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_categories_mapping_columns_html_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_categories_mapping_columns_html', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['get_main_xml_nodes_url'] = htmlspecialchars_decode( $this->url->link( $this->real_extension_type.'/'.$this->extension_name.'&ajax_function=get_main_xml_nodes', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['profile_save_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=profile_save', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['profile_delete_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=profile_delete', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['profile_download_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=profile_download', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['profile_upload_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=profile_upload', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['profile_error_uncompleted'] = $this->language->get('profile_error_uncompleted');
            $variables['spread_sheet_upload_json'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=spread_sheet_upload_json', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));

            // Filter validation error messages
            $variables['profile_export_filters_error_empty_filter'] = $this->language->get('profile_export_filters_error_empty_filter');
            $variables['profile_export_filters_error_numeric_filter_expected'] = $this->language->get('profile_export_filters_error_numeric_filter_expected');
            $variables['profile_export_filters_error_date_filter_expected'] = $this->language->get('profile_export_filters_error_date_filter_expected');
            $variables['profile_export_filters_error_relative_date_filter_expected'] = $this->language->get('profile_export_filters_error_relative_date_filter_expected');

            $variables['profile_delete_confirmation'] = $this->language->get('profile_delete_confirmation');

            $variables['profile_data_error_please_reload'] = $this->language->get('profile_data_error_please_reload');
            $variables['profile_data_error_column_mappings'] = $this->language->get('profile_data_error_column_mappings');
            $variables['profile_data_error_filters'] = $this->language->get('profile_data_error_filters');
            $variables['profile_data_error_custom_fixed_columns'] = $this->language->get('profile_data_error_custom_fixed_columns');
            $variables['profile_data_error_categories_mapping'] = $this->language->get('profile_data_error_categories_mapping');
            $variables['profile_data_error_sort_order'] = $this->language->get('profile_data_error_sort_order');
            $variables['profile_unexpected_data_error'] = $this->language->get('profile_unexpected_data_error');

            $variables['profile_import_error_missing_upload_file_or_invalid_format'] = $this->language->get('profile_import_error_missing_upload_file_or_invalid_format');
            $variables['profile_import_error_missing_url'] = $this->language->get('profile_import_error_missing_url');
            $variables['profile_import_error_missing_ftp_info'] = $this->language->get('profile_import_error_missing_ftp_info');
            $variables['profile_import_error_missing_ftp_invalid_port'] = $this->language->get('profile_import_error_missing_ftp_invalid_port');
            $variables['profile_import_error_categories_columns_not_configured'] = $this->language->get('profile_import_error_categories_columns_not_configured');

            $variables['profile_import_profile_upload_successful'] = $this->language->get('profile_import_profile_upload_successful');
            $variables['profile_import_profile_download_successful'] = $this->language->get('profile_import_profile_download_successful');

            $variables['profile_import_column_name_select_insert_manually'] = $this->language->get( 'profile_import_column_name_select_insert_manually');
            $variables['server_info'] = $this->get_server_info();
            $variables['profile_error_server_limits_overloaded'] = $this->language->get( 'profile_error_server_limits_overloaded');

            return $variables;
        }

        function load_generic_data() {
            $this->profiles_select_import = $this->{$this->model_profile}->get_profiles('import', '', true);
            $this->profiles_select_export = $this->{$this->model_profile}->get_profiles('export', '', true);

            $this->profiles_select_import_export = $this->profiles_select_import + $this->profiles_select_export;

            $this->profiles_select_migration_export = $this->{$this->model_profile}->get_profiles('migration-export', '', true);
            $this->profiles_select = $this->{$this->model_profile}->get_profiles('', '', true);
        }

        function build_filters_config( $config_filters, $fields_to_select, $button_remove_filter, $is_an_import_profile, $element_type){
            $result = null;

            if (isset( $config_filters['filters']) && !empty($config_filters['filters'])){
                $filters = $config_filters['filters'];

                if (is_string( $filters[0])) {
                   $result = $this->build_new_filters_config( $filters);
                }
                else {
                   $result = $this->build_old_filters_config( $filters);
                }
            }

            return $result;
        }

        function build_new_filters_config( $filters) {
            $result = [];
            $count = count( $filters);
            $index = 0;
            $openGroups = 0;

            while ($index < $count) {
                $token = $filters[$index];

                if ($token === '('){
                    if (count( $result) > 0)
                    {
                       $result[] = 'OPEN_GROUP';
                       $openGroups++;
                    }
                }
                else if ($token === ')'){
                    if ($openGroups > 0){
                       $result[] = 'CLOSE_GROUP';
                       $openGroups--;
                    }
                }
                else if (in_array( $token, ['AND', 'OR'])) {
                    $field = $filters[++$index];
                    $originalField = $field;

                    if ($field === '(') {
                        $result[] = $token;
                        $result[] = 'OPEN_GROUP';

                        $openGroups++;
                        $field = $filters[++$index];
                    }

                    $field_parts = preg_split( '/\-/', $field);
                    $type = $field_parts[2];

                    $comparator = $filters[++$index];
                    $value = $filters[++$index];

                    if ($type === 'number') {
                        $value = !is_numeric($value) ? 0 : $value;
                        $value = +preg_replace( '/"/', '', $value);
                    } else if ($value !== '') {
                        while ($value[0] === '"' && $value[strlen( $value) - 1] === '"') {
                            $value = substr( $value, 1, strlen( $value) - 2);
                        }
                    }

                    if ($originalField !== '(') {
                        $result[] = $token;
                    }

                    $result[] = (object)[
                        'field' => $field,
                        'type' => $type,
                        'comparator' => $comparator,
                        'value' => $value
                    ];
                }
                else {
                    $field = $filters[$index];

                    $field_parts = preg_split( '/\-/', $field);
                    $type = $field_parts[2];

                    $comparator = $filters[++$index];
                    $value = $filters[++$index];

                    if ($type === 'number')
                    {
                      $value = +preg_replace( '/"/', '', $value);
                    }
                    else {
                        while ($value[0] === '"' && $value[strlen( $value) - 1] === '"')
                        {
                            $value = substr( $value, 1, strlen( $value) - 2);
                        }
                    }

                    $result[] = (object)[
                        'field' => $field,
                        'type' => $type,
                        'comparator' => $comparator,
                        'value' => $value
                    ];
                }

                $index++;
            }

            return $result;
        }

        function build_old_filters_config( $filters){
            $result = [];
            $count = count( $filters);

            foreach ($filters as $key => $filter){
                $field_parts = preg_split( '/\-/', $filter['field']);
                $type = $field_parts[2];
                $comparator = $filter['conditional'][$type];

                $result[] = (object)[
                    'field' => $filter['field'],
                    'type' => $type,
                    'comparator' => $comparator,
                    'value' => $filter['value']
                ];

                if ($key !== $count - 1)
                {
                    $result[] = 'AND';
                }
            }

            return $result;
        }

        private function check_profile_mapping_columns_data() {
            try
            {
                UploadManager::check_data( $this);
            }
            catch (\Exception $ex) {
                $this->output_error( $ex->getMessage());
            }
        }

        private function profile_import_analyze_columns() {
            try
            {
                $analyzer = new ColumnsAnalyzer( $this);
                $analyzer->execute();

                return $analyzer->get_result();
            }
            catch (\Exception $ex) {
                $this->output_error( $ex->getMessage());
            }
        }

        private function get_post_parameter( $name, $default = '') {
            return isset($this->request->post[$name])
                   ? $this->request->post[$name]
                   : $default;
        }

        private function get_post_parameter_strict( $name, $errorMessage) {
            $result = $this->get_post_parameter( $name, null);

            if ($result === null) {
                die( $errorMessage);
            }

            return $result;
        }

        private function build_column_selector( $fieldName, $items, $selectedItem = null) {
            $result = '';

            if (count( $items) > 0) {
                $result = "<select data-field-name=\"{$fieldName}\"
                                   data-live-search=\"true\"
                                   class=\"select-picker\"
                                   onchange=\"select_column_name($(this));\">\n";

                usort( $items, function( $item1, $item2) {
                    return strcasecmp( $item1->label, $item2->label);
                });

                $firstSelected = empty( $selectedItem) ? 'selected' : '';
                $result .= "<option value=\"\" {$firstSelected}>---</option>";

                foreach ($items as $index => $item) {
                    $selected = $item->label === $selectedItem ? 'selected' : '';

                    $result.= "<option value=\"{$item->label}\" {$selected}>{$item->label}</option>\n";
                }

                $insertManuallyText = $this->language->get( 'profile_import_column_name_select_insert_manually');
                $result .= "<option value=\"manual-select\">{$insertManuallyText}</option>";

                $result .= "</select>\n";
            }

            return $result;
        }

        private function fix_column_field_value( $custom_name, $suggestedColumns) {
            foreach ($suggestedColumns as $column) {
                if ($this->is_custom_name_similar_to_column( $custom_name, $column->label)) {
                    return $column->label;
                }
            }

            return '';
        }

        private function is_custom_name_similar_to_column( $custom_name, $column) {
            $column = strtolower( $column);

            // Cambiamos '_' -> ' '
            $column = preg_replace( '/_/', ' ', $column);

            // Convertimos las secuencias de espacios en un solo espacio
            $column = preg_replace( '/\s+/', ' ', $column);

            $matches = [];

            // Si el nombre de columna termina en un numero, lo eliminamos
            if (preg_match( '/([a-z ]+)\d+/', $column, $matches) > 0) {
                $column = trim( $matches[1]);
            }

            return strcasecmp( $column, strtolower( $custom_name)) === 0;
        }

        public function as_select_format( $items) {
            $result = [];

            usort( $items, function( $item1, $item2) {
                return strcasecmp( $item1, $item2);
            });

            foreach ($items as $index => $item) {
                $result[] = (object)[
                    'label' => $item,
                    'value' => $index
                ];
            }

            return $result;
        }

        public function get_current_profile_type() {
            $result = $this->get_post_parameter( 'profile_type', null);

            if ($result === null) {
                $result = $this->get_post_parameter_strict( 'import_xls_i_want', 'No import_xls_i_want data');
            }

            return $result;
        }

        private function get_server_info() {
            $memory_limit = ini_get( 'memory_limit');
            $max_execution_time = ini_get( 'max_execution_time');
            $upload_max_filesize = ini_get( 'upload_max_filesize');

            return "{'memory_limit': '{$memory_limit}', 'max_execution_time': '{$max_execution_time}', 'upload_max_filesize': '{$upload_max_filesize}'}";
        }

        private function output_json( $data) {
            echo json_encode( $data);
            die();
        }

        private function build_script_tag( $content) {
            return "<script>\n{$content}\n</script>\n";
        }

        private function output_error( $errorMessage){
            $array_return = [
                'error' => true,
                'message' => $errorMessage
            ];

            echo json_encode( $array_return);
            die();
        }
    }
?>
