<?php
    class ModelExtensionModuleIeProCustomerGroups extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'customer_groups';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'customer_group';
            $this->main_field = 'customer_group_id';

            $special_tables_description = $this->hasCustomerGroupDescriptions ? array('customer_group_description') : array();
            $delete_tables = $this->hasCustomerGroupDescriptions ? array('customer_group_description') : array();
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Customer Group id' => array('hidden_fields' => array('table' => 'customer_group', 'field' => 'customer_group_id')),
                'Name' => array('hidden_fields' => array('table' => $this->hasCustomerGroupDescriptions ? 'customer_group_description' : 'customer_group', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Description' => array('hidden_fields' => array('table' => 'customer_group_description', 'field' => 'description'), 'multilanguage' => $multilanguage),
                'Approve' => array('hidden_fields' => array('table' => 'customer_group', 'field' => 'approval')),
                'Sort order' => array('hidden_fields' => array('table' => 'customer_group', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            if(!$this->hasCustomerGroupDescriptions) {
                unset($columns['Description']);
                unset($columns['Approve']);
                unset($columns['Sort']);
            }


            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        public function get_all_customer_groups($select_format = false, $import = false){
            if($this->hasCustomerGroupDescriptions) {
                $result = $this->db->query(
                    "SELECT * FROM {$this->escape_database_table_name('customer_group')} cg
                JOIN {$this->escape_database_table_name('customer_group_description')} cgd
                ON (cg.{$this->escape_database_field('customer_group_id')} = cgd.{$this->escape_database_field('customer_group_id')})");
            } else {
                $result = $this->db->query("SELECT * FROM {$this->escape_database_table_name('customer_group')} cg");
            }

            if($select_format) {
                $select = array();
                foreach ($result->rows as $cg) {
                    if($import)
                        $select[$cg['name']] = $cg['customer_group_id'];
                    else
                        $select[$cg['customer_group_id']][$this->default_language_id] = $cg['name'];
                }
                return $select;
            }

            return ($result->row) ? $result->rows : array();
        }

        public function create_customer_group($language_id, $name, $description = '', $approval = 1, $sort_order = 1){
            if($this->hasCustomerGroupDescriptions) {
                $this->db->query(
                    "INSERT INTO {$this->escape_database_table_name('customer_group')} 
                    SET {$this->escape_database_field('approval')} = {$this->escape_database_value($approval)}, 
                    {$this->escape_database_field('sort_order')} = {$this->escape_database_value($sort_order)}"
                );

                $id = $this->db->getLastId();

                $this->db->query(
                    "INSERT INTO {$this->escape_database_table_name('customer_group_description')}
                    SET {$this->escape_database_field('customer_group_id')} = {$id},
                    {$this->escape_database_field('language_id')} = {$language_id},
                    {$this->escape_database_field('name')} = {$this->escape_database_value($name)},
                    {$this->escape_database_field('description')} = {$this->escape_database_value($description)}"
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$this->escape_database_table_name('customer_group')} 
                    SET {$this->escape_database_field('name')} = {$this->escape_database_value($name)}"
                );

                $id = $this->db->getLastId();
            }

            return $id;
        }
    }
?>