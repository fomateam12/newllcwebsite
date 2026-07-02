<?php
    class ModelExtensionModuleIeProCustomers extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'customers';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'customer';
            $this->main_field = 'customer_id';
            $delete_tables = array(
                'customer_activity',
                'customer_affiliate',
                'customer_approval',
                'customer_reward',
                'customer_transaction',
                'customer_ip',
                'address',
            );
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Customer ID' => array('hidden_fields' => array('table' => 'customer', 'field' => 'customer_id')),
                'Group id' => array('hidden_fields' => array('table' => 'customer', 'field' => 'customer_group_id')),
                'Store id' => array('hidden_fields' => array('table' => 'customer', 'field' => 'store_id')),
                'Address id' => array('hidden_fields' => array('table' => 'customer', 'field' => 'address_id')),
                'Language id' => array('hidden_fields' => array('table' => 'customer', 'field' => 'language_id')),
                'First name' => array('hidden_fields' => array('table' => 'customer', 'field' => 'firstname')),
                'Last name' => array('hidden_fields' => array('table' => 'customer', 'field' => 'lastname')),
                'Email' => array('hidden_fields' => array('table' => 'customer', 'field' => 'email')),
                'Telephone' => array('hidden_fields' => array('table' => 'customer', 'field' => 'telephone')),
                'Fax' => array('hidden_fields' => array('table' => 'customer', 'field' => 'fax')),
                'Custom field' => array('hidden_fields' => array('table' => 'customer', 'field' => 'custom_field')),
                'Password' => array('hidden_fields' => array('table' => 'customer', 'field' => 'password')),
                'Salt' => array('hidden_fields' => array('table' => 'customer', 'field' => 'salt')),
                'Newsletter' => array('hidden_fields' => array('table' => 'customer', 'field' => 'newsletter')),
                'Approved' => array('hidden_fields' => array('table' => 'customer', 'field' => 'approved')),
                'Safe' => array('hidden_fields' => array('table' => 'customer', 'field' => 'safe')),
                'Cart' => array('hidden_fields' => array('table' => 'customer', 'field' => 'cart')),
                'Wish list' => array('hidden_fields' => array('table' => 'customer', 'field' => 'wishlist')),
                'IP' => array('hidden_fields' => array('table' => 'customer', 'field' => 'ip')),
                'Token' => array('hidden_fields' => array('table' => 'customer', 'field' => 'token')),
                'Code' => array('hidden_fields' => array('table' => 'customer', 'field' => 'code')),
                'Status' => array('hidden_fields' => array('table' => 'customer', 'field' => 'status')),
                'Date added' => array('hidden_fields' => array('table' => 'customer', 'field' => 'date_added')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            if(!$this->is_oc3) {
                unset($columns['Approved']);
            }
            if(version_compare(VERSION, '1.5.3.1', '<=')) {
                unset($columns['Salt']);
            }
            if(version_compare(VERSION, '2.3', '<')) {
                unset($columns['Language id']);
                unset($columns['Code']);
            }
            if(version_compare(VERSION, '2', '<')) {
                unset($columns['Custom field']);
                unset($columns['Safe']);
                unset($columns['Code']);
            }

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }
    }
?>