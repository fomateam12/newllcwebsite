<?php
    class ModelExtensionModuleIeProDiscounts extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
            $this->cat_name = 'discounts';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'product_discount';
            $this->main_field = 'product_discount_id';
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        public function pre_import($data_file) {
            //Add manually product_identifier to avoid conflicts with normal import profile of products
            $temp_conditional_fields = $this->conditional_fields;
            array_push($temp_conditional_fields['product_discount'], 'product_id');
            $this->conditional_fields = $temp_conditional_fields;

            if(!empty($this->conversion_fields['product_discount_product_id'][0]['product_id_identificator'])) {
                $field_search = $this->conversion_fields['product_discount_product_id'][0]['product_id_identificator'];
                foreach ($data_file as $key => $val) {
                    $product_id = $this->model_extension_module_ie_pro_products->get_product_id($field_search, $val['product_discount']['product_id']);
                    if(empty($product_id))
                        unset($data_file[$key]);
                    else
                        $data_file[$key]['product_discount']['product_id'] = $product_id;
                }
                $temp_conversion_fields = $this->conversion_fields;
                unset($temp_conversion_fields['product_discount_product_id']);
                $this->conversion_fields = $temp_conversion_fields;
            }

            if(!empty($this->conversion_fields['product_discount_customer_group_id'][0]['rule'])) {
                foreach ($data_file as $key => $discount) {
                    $customer_group_name = $discount['product_discount']['customer_group_id'];
                    if(array_key_exists($customer_group_name, $this->all_customer_groups))
                        $data_file[$key]['product_discount']['customer_group_id'] = $this->all_customer_groups[$customer_group_name];
                    else
                        $data_file[$key]['product_discount']['customer_group_id'] = '';
                }
            }

            return parent::pre_import($data_file);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Product discount id' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'product_discount_id')),
                'Product id' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'product_id'), 'product_id_identificator' => 'product_id'),
                'Customer group id' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'customer_group_id', 'allow_names' => true, 'conversion_global_var' => 'all_customer_groups')),
                'Quantity' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'quantity')),
                'Priority' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'priority')),
                'Price' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'price')),
                'Date_start' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'date_start')),
                'Date_end' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'date_end')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);

            return $columns;
        }
    }
?>