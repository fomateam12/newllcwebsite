<?php
    class ModelExtensionModuleIeProOrderProducts extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'order_products';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'order_product';
            $this->main_field = 'order_product_id';

            if(is_file($this->assets_path.'add_special_tables_to_order_products.php')){
                require($this->assets_path.'add_special_tables_to_order_products.php');
            }

            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Order product id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'order_product_id')),
                'Order id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'order_id')),
                'Product id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'product_id')),
                'Name' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'name')),
                'Model' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'model')),
                'Quantity' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'quantity')),
                'Price' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'price')),
                'Total' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'total')),
                'Tax' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'tax')),
                'Reward' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'reward')),
            );

            if(is_file($this->assets_path.'add_fields_to_order_products.php')){
                require($this->assets_path.'add_fields_to_order_products.php');
            }

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }


        // CUSTOM WORK: 2019-07-16 Custom Order Products
        public function _exporting_process_order($element, $order_product_id, $fields){
            $order_product = $this->get_order_product($order_product_id);
            $order = $this->get_order($order_product['order_id']);
            foreach ($fields as $field){
                $element[$field['custom_name']] = $order[$field['field']];
            }
            return $element;
        }

        public function _exporting_process_calculate_net_price($element, $order_product_id, $fields){
            $order_product = $this->get_order_product($order_product_id);
            $net_price = $order_product['total'] * ($order_product['profit_percentage'] / 100);
            $element[$fields[0]['custom_name']] = $net_price;
            return $element;
        }
        // CUSTOM WORK END

        public function get_order_product($order_product_id){
            $result = $this->db->query("
            SELECT * FROM {$this->escape_database_table_name('order_product')} WHERE {$this->escape_database_field('order_product_id')} = {$order_product_id};
            ");
            return $result->row;
        }

        public function get_order($order_id){
            $result = $this->db->query("
            SELECT * FROM {$this->escape_database_table_name('order')} WHERE {$this->escape_database_field('order_id')} = {$order_id};
            ");
            return $result->row;
        }

    }
?>