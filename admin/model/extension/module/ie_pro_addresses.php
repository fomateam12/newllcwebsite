<?php
    class ModelExtensionModuleIeProAddresses extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
            $this->cat_name = 'addresses';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'address';
            $this->main_field = 'address_id';

            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Address id' => array('hidden_fields' => array('table' => 'address', 'field' => 'address_id')),
                'Customer id' => array('hidden_fields' => array('table' => 'address', 'field' => 'customer_id')),
                'First name' => array('hidden_fields' => array('table' => 'address', 'field' => 'firstname')),
                'Last name' => array('hidden_fields' => array('table' => 'address', 'field' => 'lastname')),
                'Company' => array('hidden_fields' => array('table' => 'address', 'field' => 'company')),
                'Address 1' => array('hidden_fields' => array('table' => 'address', 'field' => 'address_1')),
                'Address 2' => array('hidden_fields' => array('table' => 'address', 'field' => 'address_2')),
                'Postcode' => array('hidden_fields' => array('table' => 'address', 'field' => 'postcode')),
                'City' => array('hidden_fields' => array('table' => 'address', 'field' => 'city')),
                'Zone id' => array('hidden_fields' => array('table' => 'address', 'field' => 'zone_id')),
                'Country id' => array('hidden_fields' => array('table' => 'address', 'field' => 'country_id')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            $columns = parent::put_type_to_columns_formatted($columns);
            return $columns;
        }
    }
?>