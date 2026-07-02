<?php
    class ModelExtensionModuleIeProOrderTotals extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'order_totals';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'order_total';
            $this->main_field = 'order_total_id';
            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Order total id' => array('hidden_fields' => array('table' => 'order_total', 'field' => 'order_total_id')),
                'Order id' => array('hidden_fields' => array('table' => 'order_total', 'field' => 'order_id')),
                'Code' => array('hidden_fields' => array('table' => 'order_total', 'field' => 'code')),
                'Title' => array('hidden_fields' => array('table' => 'order_total', 'field' => 'title')),
                'Value' => array('hidden_fields' => array('table' => 'order_total', 'field' => 'value')),
                'Sort order' => array('hidden_fields' => array('table' => 'order_total', 'field' => 'sort_order')),
            );

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }
    }
?>