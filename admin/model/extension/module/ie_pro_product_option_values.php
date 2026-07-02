<?php
    class ModelExtensionModuleIeProProductOptionValues extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
            $this->cat_name = 'product_option_values';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'product_option_value';
            $this->main_field = 'product_option_value_id';

            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Product option value id' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'product_option_value_id')),
                'Product id' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'product_id'), 'product_id_identificator' => 'product_id'),
                'Option id' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'option_id', 'conversion_global_var' => 'options', 'conversion_global_index' => 'name', 'allow_names' => true)),
                'Option value id' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'option_value_id', 'conversion_global_var' => 'option_values', 'conversion_global_index' => 'name', 'allow_names' => true)),
                'Quantity' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'quantity')),
                'Subtract' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'subtract')),
                'Price' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'price')),
                'Price prefix' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'price_prefix')),
                'Points' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'points')),
                'Points prefix' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'points_prefix')),
                'Weight' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'weight')),
                'Weight prefix' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'weight_prefix')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        function import_create_asociations($data_file) {
            $this->load->model('extension/module/ie_pro_options');
            $all_options = $this->model_extension_module_ie_pro_options->get_all_options_import_format(true);
            $all_product_options = $this->model_extension_module_ie_pro_options->get_all_product_options();

            $this->load->model('extension/module/ie_pro_option_values');
            $all_option_values = $this->model_extension_module_ie_pro_option_values->get_all_option_values_import_format(true);

            $all_product_option_values = $this->get_all_product_option_values();

            $option_id_conversion = $this->conversion_fields != '' && is_array($this->conversion_fields) && array_key_exists('product_option_value_option_id', $this->conversion_fields) && count( $this->conversion_fields['product_option_value_option_id']) > 0;
            $option_value_id_conversion = $this->conversion_fields != '' && is_array($this->conversion_fields) && array_key_exists('product_option_value_option_value_id', $this->conversion_fields) && count( $this->conversion_fields['product_option_value_option_value_id']) > 0;
            $product_id_conversion = $this->conversion_fields != '' && is_array($this->conversion_fields) && array_key_exists('product_option_value_product_id', $this->conversion_fields) && array_key_exists(0, $this->conversion_fields['product_option_value_product_id']) ? $this->conversion_fields['product_option_value_product_id'][0]['product_id_identificator'] : false;

            foreach ($data_file as $key => $data) {
                $option_id = array_key_exists('option_id', $data['product_option_value']) ? $data['product_option_value']['option_id'] : '';
                if($option_id !== '' && $option_id_conversion) {
                    $option_id = utf8_decode($option_id);
                    $option_id = array_key_exists($option_id, $all_options) ? $all_options[$option_id] : '';
                    $data_file[$key]['product_option_value']['option_id'] = $option_id;
                }

                if(empty($option_id))
                    $this->exception(sprintf($this->language->get('progress_import_product_option_values_error_option_doesnt_exist'), $option_id, $key+2));

                //Cretate product_option is doenst exist
                $product_id = array_key_exists('product_id', $data['product_option_value']) ? $data['product_option_value']['product_id'] : '';

                if((!empty($product_id_conversion) && $product_id_conversion != 'product_id') || empty($product_id))
                    $product_id = $this->model_extension_module_ie_pro_products->get_product_id($product_id_conversion, $product_id);

                if(empty($product_id)) {
                    if ($this->profile['profile_type'] === 'import' &&
                        $this->profile['import_xls_i_want'] === 'product_option_values') {
                        continue;
                    } else {
                        $this->exception(sprintf($this->language->get('progress_import_product_option_values_error_not_product_identificator'), $key+2));
                    }
                }

                $key_temp = $product_id.'_'.$option_id;
                if(!array_key_exists($key_temp, $all_product_options)) {
                    $product_option_id = $this->model_extension_module_ie_pro_options->create_product_option($product_id, $option_id);
                    $all_product_options[$key_temp] = $product_option_id;
                } else {
                    $product_option_id = $all_product_options[$key_temp];
                }

                $data_file[$key]['product_option_value']['product_option_id'] = $product_option_id;

                $option_value_id = array_key_exists('option_value_id', $data['product_option_value']) ? $data['product_option_value']['option_value_id'] : '';
                if($option_value_id !== '' && $option_value_id_conversion) {
                    $index = $option_id.'_'.$option_value_id;
                    if(!array_key_exists($index, $all_option_values)) {
                        //Create option value
                        $temp_data = array(
                            'name' => $option_value_id,
                            'option_id' => $option_id,
                            'image' => '',
                            'sort_order' => '',
                        );
                        $new_option_value_id = $this->model_extension_module_ie_pro_option_values->create_simple_option_value($temp_data);
                        $all_option_values[$option_id.'_'.$option_value_id] = $new_option_value_id;
                        $option_value_id = $new_option_value_id;
                    } else
                        $option_value_id = $all_option_values[$index];

                    $data_file[$key]['product_option_value']['option_value_id'] = $option_value_id;
                }

                if(!array_key_exists('product_option_value_id', $data) || empty($data['product_option_value_id'])) {
                    $key_temp = implode('_', array($product_option_id, $product_id, $option_id, $option_value_id));
                    if(array_key_exists($key_temp, $all_product_option_values)) {
                        $product_option_value_id = $all_product_option_values[$key_temp];
                        $data_file[$key]['empty_columns']['creating'] = 0;
                        $data_file[$key]['empty_columns']['editting'] = 1;

                        foreach ($data_file[$key] as $table_name => $element) {
                            if(array_key_exists('product_option_value_id', $element)) {
                                $data_file[$key][$table_name]['product_option_value_id'] = $product_option_value_id;
                                if($table_name != 'product_option_value')
                                    $data_file[$key][$table_name]['product_id'] = $product_id;
                            }
                        }
                    }
                }
            }

            //Devman Extensions - info@devmanextensions.com - 13/08/2020 16:19 - FIX conversion option values conversions
            $temp_conversion =  $this->conversion_fields['product_option_value_product_id'];
            $this->fields_conversion = array();
            if(!empty($temp_conversion))
                $this->fields_conversion = array('product_option_value_product_id' => $temp_conversion);

            //Devman Extensions - info@devmanextensions.com - 19/1/22 12:17 - FIX product_option_value with product_id = 0
            foreach ($data_file as $key => $val) {
                if(empty($val['product_option_value']['product_id']) || empty($val['product_option_value']['product_option_id']) || empty($val['product_option_value']['option_value_id']))
                        unset($data_file[$key]);
            }

            return $data_file;
        }

        function get_all_product_option_values() {
            $result = $this->db->query('SELECT CONCAT(product_option_id, "_", product_id, "_", option_id, "_", option_value_id) as association, '.$this->escape_database_field('product_option_value_id').' FROM '.$this->escape_database_table_name('product_option_value'));
            $product_option_values = array();
            foreach ($result->rows as $key => $re) {
                $product_option_values[$re['association']] = $re['product_option_value_id'];
            }
            return $product_option_values;
        }

        /* function get_database_fields() {
            $fields = array(
                'product_option_value' => array(
                    'product_option_value_id' => array('is_filter' => true),
                    'product_option_id' => array('is_filter' => true),
                    'product_id' => array('is_filter' => true),
                    'option_id' => array('is_filter' => true),
                    'option_value_id' => array('is_filter' => true),
                    'quantity' => array('is_filter' => true),
                    'subtract' => array('is_filter' => true),
                    'price' => array('is_filter' => true),
                    'price_prefix' => array('is_filter' => true),
                    'points' => array('is_filter' => true),
                    'points_prefix' => array('is_filter' => true),
                    'weight' => array('is_filter' => true),
                    'weight_prefix' => array('is_filter' => true),
                )
            );

            return $fields;
        }*/
    }
?>
