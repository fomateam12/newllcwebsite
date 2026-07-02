<?php
    class ModelExtensionModuleIeProOptionValues extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'option_values';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'option_value';
            $this->main_field = 'option_value_id';

            $delete_tables = array(
                'option_value_description',
            );

            $special_tables = array();
            $special_tables_description = array('option_value_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function pre_import($data_file)
        {
            $this->load->model('extension/module/ie_pro_options');
            $all_options_import = $this->model_extension_module_ie_pro_options->get_all_options_import_format(true);
            $convert_to_id = is_array($this->conversion_fields) && $this->conversion_has_rule('option_value_option_id', 'name_instead_id') ? true : false;
            foreach ($data_file as $key => $data) {
                $option_id = array_key_exists('option_id', $data['option_value']) ? $data['option_value']['option_id'] : '';
                $option_id = $convert_to_id && !empty($option_id) ? $all_options_import[$option_id] : $option_id;
                if(!empty($option_id) && array_key_exists('option_value_description', $data) && !empty($data['option_value_description'])) {
                    foreach ($data['option_value_description'] as $lang_id => $data) {
                        $data_file[$key]['option_value_description'][$lang_id]['option_id'] = $option_id;
                    }
                }
            }
            return parent::pre_import($data_file);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Option value id' => array('hidden_fields' => array('table' => 'option_value', 'field' => 'option_value_id')),
                'Option id' => array('hidden_fields' => array('table' => 'option_value', 'field' => 'option_id', 'allow_names' => true, 'conversion_global_var' => 'all_options', 'conversion_global_index' => 'name')),
                'Name' => array('hidden_fields' => array('table' => 'option_value_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Option image' => array('hidden_fields' => array('table' => 'option_value', 'field' => 'image')),
                'Sort order' => array('hidden_fields' => array('table' => 'option_value', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        function get_all_option_values_export_format($simple_name = false) {
            $this->load->model('extension/module/ie_pro_options');
            $options = $this->model_extension_module_ie_pro_options->get_all_options_export_format();

            $sql = 'SELECT * FROM '.$this->escape_database_table_name('option_value').' optval LEFT JOIN '.$this->escape_database_table_name('option_value_description').' optvald ON(optval.`option_value_id` = optvald.`option_value_id`)';

            $results = $this->db->query($sql);

            $options_values = array();
            foreach ($results->rows as $key => $option_info) {
                $option_value_id = $option_info['option_value_id'];
                $option_id = $option_info['option_id'];
                $lang_id = $option_info['language_id'];
                if(!empty($option_id) && array_key_exists($option_id, $options)) {
                    if (!array_key_exists($option_value_id, $options_values)) {
                        $temp = $option_info;
                        $temp['name'] = !$simple_name ? array() : '';
                        unset($temp['language_id']);
                        $options_values[$option_value_id] = $temp;
                    }

                    if(!$simple_name)
                        $options_values[$option_value_id]['name'][$lang_id] = $option_info['name'];
                    else
                        $options_values[$option_value_id]['name'] = $this->default_language_id == $lang_id ? $option_info['name'] : $options_values[$option_value_id]['name'];

                    $options_values[$option_value_id]['option'] = $options[$option_id];
                }
            }
            return $options_values;
        }

        function get_all_option_values_import_format($simple_name = false) {
            $final_opt_values = array();
            $export_format = $this->get_all_option_values_export_format();

            foreach ($export_format as $option_value_id => $opt) {
                $option_id = $opt['option_id'];
                if (!$simple_name) {
                    foreach ($opt['name'] as $lang_id => $name) {
                        $name = $this->sanitize_value($name);
                        $final_opt_values[$option_id . '_' . $name . '_' . $lang_id] = $option_value_id;
                    }
                } elseif(array_key_exists($this->default_language_id, $opt['name'])) {
                    $index = $this->sanitize_value($opt['name'][$this->default_language_id]);
                    $final_opt_values[$option_id.'_'.$index] = $option_value_id;
                }
            }
            return$final_opt_values;
        }

        function create_option_values_from_product($data_file) {
            $this->load->model('extension/module/ie_pro_options');
            $all_options = $this->model_extension_module_ie_pro_options->get_all_options_import_format();
            $all_option_values = $this->get_all_option_values_import_format();
            
            $this->update_process($this->language->get('progress_import_from_product_creating_option_values'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_option_values'), 0));
            $created = 0;
            foreach ($data_file as $row_number => $data) {
                $option_values = array_key_exists('product_option_value', $data) ? $data['product_option_value'] : array();
                if(!empty($option_values)) {
                    $option_type = array_key_exists('option_type', $option_values) ? $option_values['option_type'] : '';
                    $option_no_values = !empty($option_type) && !in_array($option_type, $this->option_types_with_values);
                    $image = array_key_exists('image', $option_values) ? $option_values['image'] : '';
                    $sort_order = array_key_exists('sort_order', $option_values) ? $option_values['sort_order'] : '';
                    $option_id = false;
                    foreach ($this->languages as $key => $lang) {
                        $language_id = $lang['language_id'];
                        $option_name = array_key_exists($language_id, $option_values) && array_key_exists('option_name', $option_values[$language_id]) ? $option_values[$language_id]['option_name'] : '';

                        if (!empty($option_name)) {
                            if (empty($option_type)) {
                                return false;
                                //$this->exception(sprintf($this->language->get('progress_import_from_product_creating_option_values_error_option_type'), ($row_number + 2), $option_name));
                            }

                            $allow_ids = $this->extract_id_allow_ids($option_name);
                            if($allow_ids) {
                                $option_id = $allow_ids;
                                break;
                            } else {
                                $index = $option_name . '_' . $option_type . '_' . $language_id;

                                if (array_key_exists($index, $all_options)) {
                                    $option_id = $all_options[$index];
                                    break;
                                }
                            }
                        }
                    }

                    $option_value_found = $some_with_name = false;
                    $optio_val_names_temp = array();
                    foreach ($this->languages as $key => $lang) {
                        $language_id = $lang['language_id'];
                        $option_value_name = array_key_exists($language_id, $option_values) && array_key_exists('name', $option_values[$language_id]) ? $option_values[$language_id]['name'] : '';

                        if (!empty($option_value_name)) {
                            $some_with_name = true;
                            if (empty($option_id)) {
                                return false;
                                //$this->exception(sprintf($this->language->get('progress_import_from_product_creating_option_values_error_option'), ($row_number + 2), $option_value_name));
                            }

                            $allow_ids = $this->extract_id_allow_ids($option_value_name);
                            if($allow_ids) {
                                $option_value_found = true;
                                break;
                            } else {
                                $optio_val_names_temp[$language_id] = $option_value_name;
                                $index = $option_id . '_' . $option_value_name . '_' . $language_id;
                                if (array_key_exists($index, $all_option_values)) {
                                    $option_value_found = true;
                                    break;
                                }
                            }
                        }
                    }

                    if(!$option_value_found && $some_with_name) {
                        $temp_data = array(
                            'name' => $optio_val_names_temp,
                            'option_id' => $option_id,
                            'image' => $image,
                            'sort_order' => $sort_order,
                        );
                        
                        $this->create_simple_option_value($temp_data);
                        $created++;
                        $this->update_process(sprintf($this->language->get('progress_import_from_product_created_option_values'), $created), true);
                        $all_option_values = $this->get_all_option_values_import_format();
                    }
                }
            }
        }

        public function create_simple_option_value($data) {

            $sql = "INSERT INTO ".$this->escape_database_table_name('option_value')." SET ".
                $this->escape_database_field('sort_order')." = ".$this->escape_database_value($data['sort_order']).", ".
                $this->escape_database_field('image')." = ".$this->escape_database_value($data['image']).", ".
                $this->escape_database_field('option_id')." = ".$this->escape_database_value($data['option_id']);

            $this->db->query($sql);

            $option_value_id = $this->db->getLastId();
            if(is_string($data['name'])) {
                $name = $data['name'];
                $data['name'] = array();
                foreach ($this->languages as $key => $lang) {
                    $data['name'][$lang['language_id']] = $name;
                }
            }

            foreach ($data['name'] as $language_id => $name) {
                $sql = "INSERT IGNORE INTO ".$this->escape_database_table_name('option_value_description')." SET ".
                    $this->escape_database_field('option_value_id')." = ".$this->escape_database_value($option_value_id).", ".
                    $this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".
                    $this->escape_database_field('name')." = " . $this->escape_database_value($name).", ".
                    $this->escape_database_field('option_id')." = ".$this->escape_database_value($data['option_id']);

                $this->db->query($sql);

                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($language_id != $lang['language_id']) {
                            $sql = "INSERT IGNORE INTO ".$this->escape_database_table_name('option_value_description')." SET ".
                            $this->escape_database_field('option_value_id')." = ".$this->escape_database_value($option_value_id).", ".
                            $this->escape_database_field('language_id')." = ".$this->escape_database_value($lang['language_id']).", ".
                            $this->escape_database_field('name')." = " . $this->escape_database_value($name).", ".
                            $this->escape_database_field('option_id')." = ".$this->escape_database_value($data['option_id']);

                            $this->db->query($sql);
                        }
                    }
                }
            }

            return $option_value_id;
        }

        public function get_product_option_id($product_id, $option_id) {
            $sql = "SELECT ".$this->escape_database_field('product_option_id'). ' FROM '.$this->escape_database_table_name('product_option').
                ' WHERE '. $this->escape_database_field('product_id').' = '.$this->escape_database_value($product_id)." AND ".
                $this->escape_database_field('option_id')." = ".$this->escape_database_value($option_id);
            $result = $this->db->query($sql);
            return array_key_exists('product_option_id', $result->row) ? $result->row['product_option_id'] : false;
        }

        public function pre_import_add_option_id_for_option_value_description_table($descriptions, $option_id) {
            $final_descriptions = array();
            if(!empty($descriptions) && is_array($descriptions)) {
                foreach ($descriptions as $language_id => $fields) {
                    $some_data = array_filter($fields);
                    if(!empty($some_data)) {
                        $fields['option_id'] = is_array($this->conversion_fields) && $this->conversion_has_rule('option_value_option_id', 'name_instead_id') ? $this->all_options_import[$option_id] : $option_id;
                        $final_descriptions[] = $fields;
                    }
                }
            }
            return $final_descriptions;
        }
    }
?>