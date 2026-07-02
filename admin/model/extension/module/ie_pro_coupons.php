<?php
    class ModelExtensionModuleIeProCoupons extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
            $this->cat_name = 'coupons';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'coupon';
            $this->main_field = 'coupon_id';

            $delete_tables = array(
                'coupon_product',
                'coupon_category',
                'coupon_history',
            );
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Coupon id' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'coupon_id')),
                'Name' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'name')),
                'Code' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'code')),
                'Type' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'type')),
                'Discount' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'discount')),
                'Logged' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'logged')),
                'Shipping' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'shipping')),
                'Total' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'total')),
                'Date start' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'date_start')),
                'Date end' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'date_end')),
                'Uses total' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'uses_total')),
                'Uses customer' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'uses_customer')),
                'Status' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'status')),
                'Date added' => array('hidden_fields' => array('table' => 'coupon', 'field' => 'date_added')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);

            return $columns;
        }
    }
?>