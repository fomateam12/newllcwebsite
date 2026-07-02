<?php
    class ModelExtensionModuleIeProOptions extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'options';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'option';
            $this->main_field = 'option_id';

            $special_tables_description = array('option_description');
            $delete_tables = array('option_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Option id' => array('hidden_fields' => array('table' => 'option', 'field' => 'option_id')),
                'Name' => array('hidden_fields' => array('table' => 'option_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Option type' => array('hidden_fields' => array('table' => 'option', 'field' => 'type')),
                'Sort order' => array('hidden_fields' => array('table' => 'option', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        function get_all_options_export_format($simple_name = false) {
            $sql = 'SELECT * FROM '.$this->escape_database_table_name('option').' opt LEFT JOIN '.$this->escape_database_table_name('option_description').' optd ON(opt.`option_id` = optd.`option_id`)';

            $results = $this->db->query($sql);

            $options = array();
            foreach ($results->rows as $key => $option_info) {
                $option_id = $option_info['option_id'];
                $lang_id = $option_info['language_id'];

                if(!array_key_exists($option_id, $options)) {
                    $temp = $option_info;
                    $temp['name'] = !$simple_name ? array() : '';
                    unset($temp['language_id']);
                    $options[$option_id] = $temp;
                }

                if(!$simple_name)
                    $options[$option_id]['name'][$lang_id] = $option_info['name'];
                else
                    $options[$option_id]['name'] = $this->default_language_id == $lang_id ? $option_info['name'] : $options[$option_id]['name'];
            }

            return $options;
        }

        function get_all_options_import_format($simple_name = false) {
            $export_format = $this->get_all_options_export_format();
            $final_options = array();

            foreach ($export_format as $key => $opt) {
                $option_id = $opt['option_id'];
                $type = $opt['type'];

                if(!$simple_name) {
                    foreach ($opt['name'] as $lang_id => $name) {
                        $name = $this->sanitize_value($name);
                        $index = $name . '_' . $type . '_' . $lang_id;
                        if (array_key_exists($index, $final_options)) {
                            $link_edit = $this->url->link('catalog/option/edit', $this->token_name . '=' . $this->session->data[$this->token_name], 'SSL') . '&option_id=' . $option_id;
                            $this->exception(sprintf($this->language->get('progress_import_from_product_creating_options_error_repeat'), $link_edit, $name, $type));
                        }
                        $final_options[$index] = $option_id;
                    }
                } else {
                    if(array_key_exists($this->default_language_id, $opt['name'])) {
                        $index = $this->sanitize_value($opt['name'][$this->default_language_id]);
                        $final_options[$index] = $option_id;
                    }
                }
            }
            return $final_options;
        }

        function get_all_product_options() {
            $result = $this->db->query('SELECT CONCAT(product_id, "_", option_id) as association, '.$this->escape_database_field('product_option_id').' FROM '.$this->escape_database_table_name('product_option'));
            $product_options = array();
            foreach ($result->rows as $key => $re) {
                $product_options[$re['association']] = $re['product_option_id'];
            }
            return $product_options;
        }

        function create_product_option($product_id, $option_id) {
            $this->db->query("INSERT INTO ".$this->escape_database_table_name('product_option')." SET ".$this->escape_database_field('required')." = 1, ".$this->escape_database_field('option_id')." = ".$this->escape_database_value($option_id).", ".$this->escape_database_field('product_id')." = ".$this->escape_database_value($product_id));
            $product_option_id = $this->db->getLastId();
            return $product_option_id;
        }
        
        function create_options_from_product($data_file) {
            $all_options = $this->get_all_options_import_format();
            $this->update_process($this->language->get('progress_import_from_product_creating_options'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_options'), 0));
            $created = 0;
            foreach ($data_file as $key => $data) {
                $options = array_key_exists('product_option_value', $data) ? $data['product_option_value'] : array();
                if(!empty($options)) {
                    $option_type = array_key_exists('option_type', $options) ? $options['option_type'] : '';

                    $some_with_name = $option_found = false;
                    $option_names_temp = array();
                    foreach ($this->languages as $key2 => $lang) {
                        $language_id = $lang['language_id'];
                        $option_name = array_key_exists($language_id, $options) && array_key_exists('option_name', $options[$language_id]) ? $options[$language_id]['option_name'] : '';

                        if (!empty($option_name)) {
                            $option_names_temp[$language_id] = $option_name;
                            $some_with_name = true;
                            if (empty($option_type))
                                $this->exception(sprintf($this->language->get('progress_import_from_product_creating_options_error_option_type'), $option_name));

                            $allow_ids = $this->extract_id_allow_ids($option_name);
                            if($allow_ids) {
                                $option_found = true;
                                break;
                            } else {
                                $index = $option_name . '_' . $option_type . '_' . $language_id;

                                if (array_key_exists($index, $all_options)) {
                                    $option_found = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$option_found && $some_with_name) {
                        $temp_data = array(
                            'name' => $option_names_temp,
                            'type' => $option_type
                        );

                        $this->create_simple_option($temp_data);
                        $created++;
                        $this->update_process(sprintf($this->language->get('progress_import_from_product_created_options'), $created), true);
                        $all_options = $this->get_all_options_import_format();
                    }
                }
            }
        }

        public function create_simple_option($data) {
            $this->db->query("INSERT INTO ".$this->escape_database_table_name('option')." SET ".$this->escape_database_field('sort_order')." = 0, ".$this->escape_database_field('type')." = ".$this->escape_database_value($data['type']));

            $option_id = $this->db->getLastId();

            foreach ($data['name'] as $language_id => $name) {
                $this->db->query("INSERT INTO ".$this->escape_database_table_name('option_description')." SET ".$this->escape_database_field('option_id')." = ".$this->escape_database_value($option_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($language_id != $lang['language_id'])
                            $this->db->query("INSERT INTO " . $this->escape_database_table_name('option_description') . " SET " . $this->escape_database_field('option_id') . " = " . $this->escape_database_value($option_id) . ", " . $this->escape_database_field('language_id') . " = " . $this->escape_database_value($lang['language_id']) . ", " . $this->escape_database_field('name') . " = " . $this->escape_database_value($name));
                    }
                }
            }

            return $option_id;
        }
    }
?>