<?php
    class ModelExtensionModuleIeProOrdersProductData extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'orders_product_data';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'order';
            $this->main_field = 'order_id';

            $this->related_tables = array(
                'order_product' => 'order_id'
            );

            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        /* public function get_database_fields() {
            return [
                'order_product' => [
                    'order_product_id' => array('is_conditional_field' => true),
                    'order_id' => array('is_filter' => true),
                    'name' => array('is_filter' => true),
                    'model' => array('is_filter' => true),
                    'quantity' => array('is_filter' => true),
                    'price' => array('is_filter' => true)
                ]
            ];
        }*/

        public function get_columns($configuration = array()) {
            return parent::get_columns( $configuration);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Order id' => array('hidden_fields' => array('table' => 'order', 'field' => 'order_id')),
                'Email' => array('hidden_fields' => array('table' => 'order', 'field' => 'email')),
                'Firstname' => array('hidden_fields' => array('table' => 'order', 'field' => 'firstname')),
                'Lastname' => array('hidden_fields' => array('table' => 'order', 'field' => 'lastname')),
                'Order product id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'order_product_id')),
                'Product id' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'product_id')),
                'Name' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'name')),
                'Model' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'model')),
                'Quantity' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'quantity')),
                'Price' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'price')),
                'Total' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'total')),
                'Tax' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'tax')),
                'Reward' => array('hidden_fields' => array('table' => 'order_product', 'field' => 'reward'))
            );

            return parent::put_type_to_columns_formatted($columns, $multilanguage);
        }

        function pre_import($data_file)
        {
            $currency_code = $this->config->get( 'config_currency');
            $currency_id = $this->currency->getId( $currency_code);

            $stores = $this->get_stores_import_format();

            if (!empty( $stores)) {
                $store = $stores[0];

                $store_id = $store['store_id'];
                $store_name = $store['name'];;
            } else {
                $store_id = 0;
                $store_name = '';
            }

            $now = date( 'Y-m-d h:i:s');

            foreach ($data_file as &$row) {
                if (!isset( $row['order']['total'])) {
                    $row['order']['total'] = 0;
                }

                $row['order']['total'] += $row['order_product']['total'];

                $row['order']['store_id'] = $now;
                $row['order']['store_name'] = $now;
                $row['order']['order_status_id'] = 1;
                $row['order']['language_id'] = $this->default_language_id;
                $row['order']['currency_code'] = $currency_code;
                $row['order']['currency_id'] = $currency_id;
                $row['order']['date_added'] = $now;
                $row['order']['date_modified'] = $now;

                $row['order_product']['order_id'] = $row['order']['order_id'];
            }

            return $data_file;
        }
    }
?>
