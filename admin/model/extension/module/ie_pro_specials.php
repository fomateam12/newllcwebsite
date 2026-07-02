<?php
    class ModelExtensionModuleIeProSpecials extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
            $this->cat_name = 'specials';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'product_special';
            $this->main_field = 'product_special_id';
            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        public function pre_import($data_file) {
            //Add manually product_identifier to avoid conflicts with normal import profile of products
            $temp_conditional_fields = $this->conditional_fields;
            array_push($temp_conditional_fields['product_special'], 'product_id');
            $this->conditional_fields = $temp_conditional_fields;

            if(!empty($this->conversion_fields['product_special_product_id'][0]['product_id_identificator'])) {
                $field_search = $this->conversion_fields['product_special_product_id'][0]['product_id_identificator'];
                foreach ($data_file as $key => $val) {
                    $product_id = $this->model_extension_module_ie_pro_products->get_product_id($field_search, $val['product_special']['product_id']);
                    if(empty($product_id))
                        unset($data_file[$key]);
                    else
                        $data_file[$key]['product_special']['product_id'] = $product_id;
                }
                $temp_conversion_fields = $this->conversion_fields;
                unset($temp_conversion_fields['product_special_product_id']);
                $this->conversion_fields = $temp_conversion_fields;
            }

            if(!empty($this->conversion_fields['product_special_customer_group_id'][0]['rule'])) {
                foreach ($data_file as $key => $special) {
                    $customer_group_name = $special['product_special']['customer_group_id'];
                    if(array_key_exists($customer_group_name, $this->all_customer_groups))
                        $data_file[$key]['product_special']['customer_group_id'] = $this->all_customer_groups[$customer_group_name];
                    else
                        $data_file[$key]['product_special']['customer_group_id'] = '';
                }
            }

            return parent::pre_import($data_file);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Product special id' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'product_special_id')),
                'Product id' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'product_id'), 'product_id_identificator' => 'product_id'),
                'Customer group id' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'customer_group_id', 'allow_names' => true, 'conversion_global_var' => 'all_customer_groups')),
                'Priority' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'priority')),
                'Price' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'price')),
                'Date start' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'date_start')),
                'Date end' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'date_end')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }
    }
?>