<?php
    class ModelExtensionModuleIeProFilterGroups extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'filter_groups';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'filter_group';
            $this->main_field = 'filter_group_id';

            $delete_tables = array(
                'filter_group_description',
                'filter',
                'filter_description',
            );

            $special_tables_description = array('filter_group_description');
            $delete_tables = array('filter_group_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Filter Group id' => array('hidden_fields' => array('table' => 'filter_group', 'field' => 'filter_group_id')),
                'Name' => array('hidden_fields' => array('table' => 'filter_group_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Sort order' => array('hidden_fields' => array('table' => 'filter_group', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }
        
        public function get_all_filter_groups_export_format() {
            $sql = 'SELECT * FROM '.$this->escape_database_table_name('filter_group').' fg LEFT JOIN '.$this->escape_database_table_name('filter_group_description').' fgd ON(fg.`filter_group_id` = fgd.`filter_group_id`)';

            $fg_query = $this->db->query($sql);

            $filter_groups = array();
            foreach ($fg_query->rows as $key => $ag_info) {
                $ag_id = $ag_info['filter_group_id'];
                $lang_id = $ag_info['language_id'];

                if(!array_key_exists($ag_id, $filter_groups))
                    $filter_groups[$ag_id] = array(
                        'name' => array(),
                        'filter_group_id' => $ag_id,
                        'sort_order' => $ag_info['sort_order'],
                        'filters' => array()
                    );
                $filter_groups[$ag_id]['name'][$lang_id] = $ag_info['name'];
            }

            foreach ($filter_groups as $filter_group_id => $ag_info) {
                $sql = 'SELECT * FROM '.$this->escape_database_table_name('filter').' fi LEFT JOIN '.$this->escape_database_table_name('filter_description').' fid ON(fi.`filter_id` = fid.`filter_id`) WHERE fi.`filter_group_id` = '.$this->escape_database_value($filter_group_id);
                $f_query = $this->db->query($sql);
                foreach ($f_query->rows as $key => $f_info) {
                    if(!empty($f_info['name'])) {
                        $filter_id = $f_info['filter_id'];
                        $language_id = $f_info['language_id'];

                        if (!array_key_exists($filter_id, $filter_groups[$filter_group_id]['filters'])) {
                            $filter_groups[$filter_group_id]['filters'][$filter_id] = array(
                                'filter_id' => $filter_id,
                                'filter_group_id' => $filter_group_id,
                                'sort_order' => $f_info['sort_order'],
                                'name' => array()
                            );
                        }
                        $filter_groups[$filter_group_id]['filters'][$filter_id]['name'][$language_id] = $f_info['name'];
                    }
                }
            }
            return $filter_groups;
        }
        
        public function get_all_filter_groups_import_format() {
            $export_format = $this->get_all_filter_groups_export_format();

            $final_filter_groups = array();

            foreach ($export_format as $key => $attrg) {
                $filer_group_id = $attrg['filter_group_id'];
                foreach ($attrg['name'] as $lang_id => $name) {
                    if(!empty($name)) {
                        $name = $this->sanitize_value($name);
                        $index = $name . '_' . $lang_id;
                        if(array_key_exists($index, $final_filter_groups)) {
                            $link_edit = $this->url->link('catalog/filter/edit', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL').'&filter_group_id='.$filer_group_id;
                            $this->exception(sprintf($this->language->get('progress_import_from_product_creating_filter_groups_error_repeat'), $link_edit, $name));
                        }

                        $final_filter_groups[$index] = $filer_group_id;
                    }
                }
            }
            return $final_filter_groups;
        }

        public function create_filter_groups_from_product($file_data) {
            $all_filtergroups = $this->get_all_filter_groups_import_format();
            $this->update_process($this->language->get('progress_import_from_product_creating_filter_groups'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_filter_groups'), 0));
            $created = 0;
            foreach ($file_data as $key => $product) {
                $elements = array_key_exists('product_filter', $product) ? $product['product_filter'] : '';

                if(!empty($elements)) {
                    foreach ($elements as $key => $element) {
                        $names = $element['name'];
                        $found = $some_with_name = false;
                        foreach ($names as $lang_id => $name) {
                            if (!empty($name)) {
                                $allow_ids = $this->extract_id_allow_ids($name);
                                if($allow_ids) {
                                    $found = true;
                                    break;
                                }

                                $some_with_name = true;
                                if (array_key_exists($name . '_' . $lang_id, $all_filtergroups)) {
                                    $found = true;
                                    break;
                                }
                            }
                        }
                        if (!$found && $some_with_name) {
                            $data_temp = array('name' => $names);
                            $filter_group_id = $this->create_simple_filter_group($data_temp);
                            $created++;
                            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_filter_groups'), $created), true);
                            $all_filtergroups = $this->get_all_filter_groups_import_format();
                        }
                    }
                }
            }
        }

        public function create_simple_filter_group($data) {
            $this->db->query("INSERT INTO ".$this->escape_database_table_name('filter_group')." SET ".$this->escape_database_field('sort_order')." = 1");

            $filter_group_id = $this->db->getLastId();

            foreach ($data['name'] as $language_id => $name) {
                $this->db->query("INSERT INTO ".$this->escape_database_table_name('filter_group_description')." SET ".$this->escape_database_field('filter_group_id')." = ".$this->escape_database_value($filter_group_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($language_id != $lang['language_id'])
                            $this->db->query("INSERT INTO ".$this->escape_database_table_name('filter_group_description')." SET ".$this->escape_database_field('filter_group_id')." = ".$this->escape_database_value($filter_group_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($lang['language_id']).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                    }
                }
            }

            return $filter_group_id;
        }
    }
?>