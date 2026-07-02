<?php
    class ModelExtensionModuleIeProTabMigrations extends ModelExtensionModuleIePro
    {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->load->language($this->real_extension_type.'/ie_pro_tab_migrations');
            $this->load->language($this->real_extension_type.'/ie_pro_file');
        }

        public function get_fields() {
            $this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/tab_migrations.css?'.$this->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/ext_ie_pro/js/tab_migrations.js?'.$this->get_ie_pro_version());

            $database_categories = $this->model_extension_module_ie_pro_database->get_database_categories();

            $to_remove_categories = array(
                'specials',
                'discounts',
                'images',
                'order_totals',
                'order_products',
                'product_option_values',
                'orders_product_data'
            );

            foreach ($database_categories as $key => $val) {
                if(in_array($key, $to_remove_categories))
                    unset($database_categories[$key]);
            }

            $destiny = array(
                '' => $this->language->get('migration_export_legend_destiny_none'),
                1 => $this->language->get('migration_export_legend_destiny_oc1'),
                2 => $this->language->get('migration_export_legend_destiny_oc2'),
                3 => $this->language->get('migration_export_legend_destiny_oc3'),
            );
            $format = array(
                'xlsx' => '.xlsx',
                'xls' => '.xls',
                'ods' => '.ods',
                'xml' => '.xml',
            );
            $fields = array(
                array(
                    'type' => 'legend',
                    'text' => '<i class="fa fa-download"></i>'.$this->language->get('migration_export_legend'),
                    'remove_border_button' => true,
                ),

                    array(
                        'label' => $this->language->get('export_import_profile_load_select'),
                        'type' => 'select',
                        'options' => $this->profiles_select_migration_export,
                        'name' => 'profiles',
                        'onchange' => 'migration_profile_load( $(this))'
                    ),

                array(
                    'label' => $this->language->get('migration_export_legend_destiny_label'),
                    'type' => 'select',
                    'options' => $destiny,
                    'name' => 'destiny',
                ),

                array(
                    'label' => $this->language->get('migration_export_legend_format_label'),
                    'type' => 'select',
                    'options' => $format,
                    'name' => 'format',
                ),
            );
            array_push($fields, array(
                'label' => $this->language->get('migration_export_select_all_label'),
                'type' => 'boolean',
                'name' => 'select_all',
                'onchange' => "migration_select_all_categories($(this).is(':checked'));"
            ));
            foreach ($database_categories as $key => $cat) {
                array_push($fields, array(
                    'label' => $cat,
                    'type' => 'boolean',
                    'name' => 'exportcat_'.$key,
                    'columns' => 6,
                    'class_container' => 'profile_export_category'
                ));
            }

            array_push($fields, array(
                'type' => 'html_hard',
                'html_code' => '<div style="clear:both; height: 10px;"></div>',
            ));
            array_push($fields, array(
                'type' => 'button',
                'label' => $this->language->get('migration_export_button'),
                'text' => '<i class="fa fa-rocket"></i> '.$this->language->get('migration_export_button'),
                'onclick' => !$this->is_t ? 'migration_export();' : 'open_manual_notification(\'Not available in trial\', \'danger\')',
            ));

            array_push( $fields, array(
                'type' => 'html_hard',
                'html_code' => '<div>'
            ));

            array_push( $fields, array(
                'label' => $this->language->get('migration_export_profile_name'),
                'type' => 'text',
                'name' => 'profile_name',
                'class_container' => 'profile_export_category migration_profile_name'
            ));

            array_push( $fields, array(
                'type' => 'button',
                'label' => $this->language->get('migration_export_profile_name'),
                'text' => '<i class="fa fa-floppy-o"></i> ' . $this->language->get('migration_export_save_profile'),
                'onclick' => 'profile_save(\'migration-export\');',
                'class_container' => 'profile_export_category'
            ));

            array_push( $fields, array(
                'type' => 'html_hard',
                'html_code' => '</div>'
            ));

            array_push($fields, array(
                'type' => 'legend',
                'text' => '<i class="fa fa-upload"></i>'.$this->language->get('migration_import_legend'),
                'remove_border_button' => true,
            ));

            array_push($fields, array(
                    'type' => 'button',
                    'class' => 'button_import_migration',
                    'label' => $this->language->get('migration_import_upload_file_button'),
                    'text' => '<i class="fa fa-upload"></i> '.$this->language->get('migration_import_upload_file_button').'<span></span>',
                    'onclick' => "$(this).next('input').click();",
                    'after' => '<input onchange="readURL($(this));" name="migration_file" type="file" style="display:none;">'
            ));

            array_push($fields, array(
                'type' => 'button',
                'label' => $this->language->get('migration_import_button'),
                'text' => '<i class="fa fa-rocket"></i> '.$this->language->get('migration_import_button'),
                'onclick' => !$this->is_t ? 'migration_import();' : 'open_manual_notification(\'Not available in trial\', \'danger\')',
                'after' => '<br>'.$this->get_remodal('migration_import_warning_message', $this->language->get('migration_import_warning_message_title'), $this->language->get('migration_import_warning_message_description'), array('link' => '<b style="color:#f00;">'.$this->language->get('migration_import_warning_message_link').'</b>',  'button_cancel' => false, 'remodal_options' => 'hashTracking: false'))
            ));

            return $fields;
        }

        public function _send_custom_variables_to_view($variables) {
            $variables['migration_export_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=migration_export', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['migration_import_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=migration_import', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            return $variables;
        }

        public function _check_ajax_function($function_name) {
            if($function_name == 'migration_export') {
                $this->migration_export();
            }else if($function_name == 'migration_import') {
                $this->migration_import();
            }
        }

        public function migration_export( $profile = null) {
            set_error_handler(array(&$this, 'customCatchError'));
            register_shutdown_function(array(&$this, 'fatalErrorShutdownHandler'));
            try {
                $post_data = $profile !== null
                             ? $profile
                             : $this->clean_array_extension_prefix($this->request->post);

                $this->file_destiny = 'download';
                $this->file_type = isset( $post_data['format'])
                                   ? $post_data['format']
                                   : $post_data['import_xls_file_format'];

                $this->destiny = isset( $post_data['destiny'])
                                 ? $post_data['destiny']
                                 : $post_data['import_xls_destiny'];

                $this->force_filename = 'Migration-Export';

                $categories = array();

                foreach ($post_data as $key => $value) {
                    if (strpos( $key, 'exportcat_') !== false) {
                        $category = str_replace( 'exportcat_', '', $key);

                        if (strpos( $category, 'import_xls_') === 0) {
                            $category = str_replace( 'import_xls_', '', $category);
                        }

                        $categories[] = $category;
                    }
                }

                if(empty($categories))
                    $this->exception($this->language->get('migration_export_error_select_category'));

                $data = $this->model_extension_module_ie_pro_database->get_database_data($categories);
                // $this->destiny = $post_data['destiny'];
                $data = $this->migration_export_format_database_data($data);

                $some_filled = false;

                foreach ($data as $table_name => $temp) {
                    if(!empty($temp['data'])) {
                        $some_filled = true;
                        break;
                    }
                }

                if(!$some_filled)
                    $this->exception($this->language->get('migration_export_error_empty_data'));

                $model_path = 'extension/module/ie_pro_file_'.$this->file_type;
                $model_name = 'model_extension_module_ie_pro_file_'.$this->file_type;
                $this->load->model('extension/module/ie_pro_file');
                $this->load->model($model_path);
                $this->{$model_name}->create_file();
                $this->{$model_name}->insert_data_multisheet($data);
                $this->{$model_name}->download_file_export();

                $this->ajax_die('Export process finished', false);

            } catch (Exception $e) {
                $data = array(
                    'status' => 'error',
                    'message' => $e->getMessage(),
                );
                $this->update_process($data);
            }
            restore_error_handler();
        }

        public function migration_import() {
            set_error_handler(array(&$this, 'customCatchError'));
            register_shutdown_function(array(&$this, 'fatalErrorShutdownHandler'));
            try {
                $this->validate_permiss();
                $database_schema = $this->model_extension_module_ie_pro_database->get_database_without_groups();
                $file_tmp_name = array_key_exists('file', $_FILES) && array_key_exists('tmp_name', $_FILES['file']) ? $_FILES['file']['tmp_name'] : '';
                $file_name = array_key_exists('file', $_FILES) && array_key_exists('name', $_FILES['file']) ? $_FILES['file']['name'] : '';
                if(empty($file_name) || empty($file_name))
                    $this->exception($this->language->get('migration_import_error_empty_file'));

                $format = pathinfo($file_name, PATHINFO_EXTENSION);
                $formats_allowed = array('xlsx', 'xls', 'ods', 'xml');
                if(!in_array($format, $formats_allowed))
                    $this->exception(sprintf($this->language->get('migration_import_error_extension'), implode(", ", $formats_allowed)));

                $model_path = 'extension/module/ie_pro_file_'.$format;
                $model_name = 'model_extension_module_ie_pro_file_'.$format;
                $this->load->model('extension/module/ie_pro_file');

                $this->file_format = $format;
                $this->force_filename = 'Migration-Import';
                $this->filename = $this->model_extension_module_ie_pro_file->get_filename();
                $this->file_tmp_path = $this->path_tmp.$this->filename;

                $this->load->model($model_path);
                $this->{$model_name}->upload_file_import();
                $data_file = $this->{$model_name}->get_data_multisheet();

                if(empty($data_file))
                    $this->exception($this->language->get('progress_import_error_empty_data'));

                $this->db->query("START TRANSACTION");
                    foreach ($data_file as $table_name => $data_info) {
                        $columns = $data_info['columns'];
                        $columns_count = count($columns);
                        $table_exists = array_key_exists($table_name, $database_schema);

                        if($table_exists) {
                            $this->db->query('TRUNCATE TABLE ' . $this->escape_database_table_name($table_name));

                            if (!empty($data_info['data'])) {
                                $sql_insert_begin = 'INSERT INTO ' . $this->escape_database_table_name($table_name) . ' (';
                                $keys_to_skip = array();
                                foreach ($columns as $col_key => $col_name) {

                                    if($table_name == 'modification' && $col_name == 'xml' && version_compare(VERSION, '2.0.0.0', '='))
                                        $col_name = 'code';

                                    if ($this->check_field_exists($database_schema, $table_name, $col_name))
                                        $sql_insert_begin .= $this->escape_database_field($col_name) . ', ';
                                    else
                                        $keys_to_skip[] = $col_key;
                                }

                                $sql_insert_begin = rtrim($sql_insert_begin, ', ') . ') VALUES ';

                                $elements_to_process = count($data_info['data']);
                                $count = 0;
                                $message = sprintf($this->language->get('migration_import_processing_table'), $table_name, 0, $elements_to_process);
                                $this->update_process($message);

                                $data_split = array_chunk($data_info['data'], 750);
                                foreach ($data_split as $key => $elements_split) {
                                    $insert_sql = '';
                                    foreach ($elements_split as $elements) {
                                        $insert_sql .= '(';
                                        foreach ($elements as $field_key => $val) {
                                            if (!in_array($field_key, $keys_to_skip)) {
                                                $insert_sql .= $this->escape_database_value($val) . ', ';
                                            }
                                        }
                                        $elements_count = count($elements);
                                        if($columns_count > $elements_count) {
                                            $elements_to_add = $columns_count - $elements_count;
                                            for ($i = 1; $i <= $elements_to_add; $i++)
                                               $insert_sql .= "'', ";
                                        }
                                        $insert_sql = rtrim($insert_sql, ', ') . '), ';
                                        $count++;
                                        $message = sprintf($this->language->get('migration_import_processing_table'), $table_name, $count, $elements_to_process);
                                        $this->update_process($message, true);
                                    }
                                    if (!empty($insert_sql)) {
                                        $final_sql = $sql_insert_begin . rtrim($insert_sql, ', ') . '; ';
                                        $this->db->query($final_sql);
                                    }
                                }
                            } else {
                                $this->update_process(sprintf($this->language->get('migration_import_empty_table'), $table_name));
                            }
                        }
                    }

                $this->update_process($this->language->get('progress_import_applying_changes_safely'));
                $this->db->query("COMMIT");

                $data = array(
                    'status' => 'progress_import_import_finished',
                    'message' => sprintf($this->language->get('migration_import_finished'))
                );
                $this->update_process($data);

                $this->ajax_die('progress_import_import_finished');
                } catch (Exception $e) {
                    $this->db->query("ROLLBACK");
                    $data = array(
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    );
                    $this->update_process($data);
                }
            restore_error_handler();
        }

        public function migration_export_format_database_data($data) {
            $database_schema = $this->model_extension_module_ie_pro_database->get_database_fields();
            $table_image_description = array_key_exists('banner_image_description', $data);
            $banner_image_id_count = 1;
            $replace_banner_image = array();
            $seo_url_id_count = 1;
            $replace_seo_url_table = array();

            foreach ($data as $table_name => $table_fields) {
                foreach ($table_fields['data'] as $field_num => $fields) {
                    if(!$table_image_description && $this->destiny != '' && in_array($table_name, array('banner_image'))) {
                        if(in_array($this->destiny, array(1,2)) && version_compare(VERSION, '2.3', '>=')) {
                            $temp = array(
                                $banner_image_id_count,
                                $fields['language_id'],
                                $fields['banner_id'],
                                $fields['title'],
                            );
                            if(!array_key_exists('banner_image_description', $data)) {
                                $data['banner_image_description'] = array(
                                    'columns' => array_keys($database_schema['banners']['banner_image_description']),
                                    'data' => array()
                                );
                            }
                            $data['banner_image_description']['data'][] = $temp;
                            $banner_image_id_count++;
                        }
                    }

                    if($table_image_description && $this->destiny != '' && in_array($table_name, array('banner_image_description'))) {
                        if(in_array($this->destiny, array(2,3)) && version_compare(VERSION, '2.3', '<')) {
                            $banner_id = $fields['banner_id'];
                            $title = $fields['title'];
                            $language_id = $fields['language_id'];
                            $copy_banner_image = $data['banner_image']['data'];

                            if(!in_array('language_id', array_values($data['banner_image']['columns']))) {
                                array_push($data['banner_image']['columns'], 'language_id');
                                array_push($data['banner_image']['columns'], 'title');
                            }

                            $index_banner_id = array_search('banner_id', $data['banner_image']['columns']);
                            $index_banner_image_id = array_search('banner_image_id', $data['banner_image']['columns']);

                            foreach ($copy_banner_image as $key => $data_temp) {
                                if($data_temp[$index_banner_id] == $banner_id) {
                                    $temp = $data_temp;
                                    $temp[$index_banner_image_id] = $banner_image_id_count;
                                    array_push($temp, $language_id);
                                    array_push($temp, $title);
                                    $replace_banner_image[] = $temp;
                                    $banner_image_id_count++;
                                    break;
                                }
                            }
                        }
                    }

                    if($this->destiny != '' && version_compare(VERSION, '3', '>=') && in_array($table_name, array('seo_url'))) {
                        if(in_array($this->destiny, array(1,2))) {
                            $store_id = $fields['store_id'];
                            $language_id = $fields['language_id'];
                            $query = $fields['query'];
                            $keyword = $fields['keyword'];

                            if($store_id == 0 && $language_id = $this->default_language_id) {
                                $replace_seo_url_table[] = array(
                                    'url_alias_id' => $seo_url_id_count,
                                    'query' => $query,
                                    'keyword' => $keyword,
                                );
                                $seo_url_id_count ++;
                            }
                        }
                    }

                    if($this->destiny != '' && version_compare(VERSION, '3', '<') && in_array($table_name, array('url_alias'))) {
                        if(in_array($this->destiny, array(3))) {
                            $query = $fields['query'];
                            $keyword = $fields['keyword'];

                            foreach ($this->stores_import_format as $store_info) {
                                $store_id = $store_info['store_id'];
                                foreach ($this->languages_ids as $language_id => $lang_data) {
                                    $replace_seo_url_table[] = array(
                                        'seo_url_id' => $seo_url_id_count,
                                        'query' => $query,
                                        'keyword' => $keyword,
                                        'language_id' => $language_id,
                                        'store_id' => $store_id
                                    );
                                    $seo_url_id_count ++;
                                }
                            }
                        }
                    }

                    foreach ($fields as $field_name => $value) {
                        if(($this->file_type == 'xlsx' || $this->file_type == 'xls') && strlen($value) > 32767)
                            $this->exception(sprintf($this->language->get('xlsx_error_max_character_by_cell'), $this->escape_database_table_name($table_name), $field_name));

                        if($this->destiny != '' && in_array($field_name, array('image'))) {
                            if(in_array($this->destiny, array(2,3)) && version_compare(VERSION, '2', '<'))
                                $fields[$field_name] = str_replace("data/", "catalog/", $value);
                            if(in_array($this->destiny, array(1)) && version_compare(VERSION, '1', '>'))
                                $fields[$field_name] = str_replace("catalog/", "data/", $value);
                        }
                    }
                    $data[$table_name]['data'][$field_num] = array_values($fields);
                }

                if($table_name == 'customer' && !empty($table_fields['data']) && $this->destiny != '' && in_array($this->destiny, array(2,3)) && version_compare(VERSION, '2', '<')) {
                    array_push($data[$table_name]['columns'], 'safe');
                    foreach ($data[$table_name]['data'] as $key => $row) {
                        array_push($data[$table_name]['data'][$key], 1);
                    }
                }
            }

            if(!empty($replace_banner_image)) {
                $data['banner_image']['data'] = $replace_banner_image;
                unset($data['banner_image_description']);
            }

            if(!empty($replace_seo_url_table)) {
                $columns = array_keys($replace_seo_url_table[0]);

                $final_seo_url_data = array();
                foreach ($replace_seo_url_table as $key => $seo_row) {
                    $final_seo_url_data[] = array_values($seo_row);
                }
                $replace_seo_url_table = $final_seo_url_data;

                if($this->is_oc_3x) {
                    $data['url_alias'] = array(
                        'columns' => $columns,
                        'data' => $replace_seo_url_table
                    );
                    unset($data['seo_url']);
                } else {
                    $data['seo_url'] = array(
                        'columns' => $columns,
                        'data' => $replace_seo_url_table
                    );
                    unset($data['url_alias']);
                }
            }

            return $data;
        }
    }
?>