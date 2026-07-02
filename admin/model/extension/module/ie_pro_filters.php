<?php
    class ModelExtensionModuleIeProFilters extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'filters';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'filter';
            $this->main_field = 'filter_id';

            $this->delete_tables = array(
                'filter',
                'filter_description',
                'product_filter',
            );

            $special_tables_description = array('filter_description');
            $delete_tables = array('filter_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        public function pre_import($data_file)
        {
            foreach ($data_file as $key => $filter) {
                if(!empty($filter['filter']['filter_group_id']) && !empty($filter['filter_description'])) {
                    foreach ($filter['filter_description'] as $lang_id => $desk) {
                        $data_file[$key]['filter_description'][$lang_id]['filter_group_id'] = $filter['filter']['filter_group_id'];
                    }
                }
            }
            return parent::pre_import($data_file);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Filter id' => array('hidden_fields' => array('table' => 'filter', 'field' => 'filter_id')),
                'Filter group id' => array('hidden_fields' => array('table' => 'filter', 'field' => 'filter_group_id')),
                'Name' => array('hidden_fields' => array('table' => 'filter_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Sort order' => array('hidden_fields' => array('table' => 'filter', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        public function get_all_filters_export_format() {
            $sql = 'SELECT * FROM '.$this->escape_database_table_name('filter_group').' fg LEFT JOIN '.$this->escape_database_table_name('filter_group_description').' fgd ON(fg.`filter_group_id` = fgd.`filter_group_id`)';

            $fg_query = $this->db->query($sql);

            $filter_groups = array();
            foreach ($fg_query->rows as $key => $fg_info) {
                $fg_id = $fg_info['filter_group_id'];
                $lang_id = $fg_info['language_id'];

                if(!array_key_exists($fg_id, $filter_groups))
                    $filter_groups[$fg_id] = array(
                        'name' => array(),
                        'filter_group_id' => $fg_id,
                        'sort_order' => $fg_info['sort_order'],
                        'filters' => array()
                    );
                $filter_groups[$fg_id]['name'][$lang_id] = $fg_info['name'];
            }

            foreach ($filter_groups as $filter_group_id => $fg_info) {
                $sql = 'SELECT * FROM '.$this->escape_database_table_name('filter').' fi LEFT JOIN '.$this->escape_database_table_name('filter_description').' fid ON(fi.`filter_id` = fid.`filter_id`) WHERE fi.`filter_group_id` = '.$this->escape_database_value($filter_group_id);
                $f_query = $this->db->query($sql);
                
                foreach ($f_query->rows as $key => $f_info) {
                    $filter_id = $f_info['filter_id'];
                    $language_id = $f_info['language_id'];

                    if(!array_key_exists($filter_id, $filter_groups[$filter_group_id]['filters'])) {
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
            return $filter_groups;
        }

        public function get_all_filters_import_format($insert_group_id = true) {
            $export_format = $this->get_all_filters_export_format();

            $filters_final = array();

            foreach ($export_format as $key => $filter_group) {
                $filter_group_id = $filter_group['filter_group_id'];
                
                foreach ($filter_group['filters'] as $key2 => $filter_info) {
                    $filter_id = $filter_info['filter_id'];
                    foreach ($filter_info['name'] as $lang_id => $name) {
                        $name = $this->sanitize_value($name);
                        $index = ($insert_group_id ? $filter_group_id.'_' : '').$name.'_'.$lang_id;
                        $filters_final[$index] = $filter_id;
                    }
                }
            }

            return $filters_final;
        }

        public function create_filters_from_product($file_data) {
            $this->load->model('extension/module/ie_pro_filter_groups');
            $all_filter_groups = $this->model_extension_module_ie_pro_filter_groups->get_all_filter_groups_import_format();
            $all_filters = $this->get_all_filters_import_format();
            $all_filters_simple = $this->get_all_filters_import_format(false);

            $this->update_process($this->language->get('progress_import_from_product_creating_filters'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_filters'), 0));
            $created = 0;
            $filter_number = (int)$this->profile['import_xls_filter_number'];
            if($filter_number > 0) {
                foreach ($file_data as $key => $product) {
                    $elements = array_key_exists('product_filter', $product) ? $product['product_filter'] : '';
                    if(!empty($elements)) {
                        foreach ($elements as $key => $element) {
                            $names = $element['name'];
                            $filter_group_id = false;
                            foreach ($names as $lang_id => $name) {
                                if (!empty($name)) {
                                    if (array_key_exists($name . '_' . $lang_id, $all_filter_groups)) {
                                        $filter_group_id = $all_filter_groups[$name . '_' . $lang_id];
                                        break;
                                    }
                                }
                            }

                            for ($i = 1; $i <= $filter_number; $i++) {
                                $names = array_key_exists($i, $element) && array_key_exists('name', $element[$i]) ? $element[$i]['name'] : '';
                                if (!empty($names)) {
                                    $found = $some_with_name = false;
                                    foreach ($names as $lang_id => $name) {
                                        if (!empty($name)) {

                                            $allow_ids = $this->extract_id_allow_ids($name);
                                            if($allow_ids) {
                                                $found = true;
                                                break;
                                            }

                                            $some_with_name = true;
                                            $index = $name . '_' . $lang_id;

                                            if (!$filter_group_id && !array_key_exists($index, $all_filters_simple))
                                                $this->exception(sprintf($this->language->get('progress_import_from_product_creating_filters_error_no_group'), $name));

                                            if (
                                                (!$filter_group_id && array_key_exists($index, $all_filters_simple)) ||
                                                (!empty($filter_group_id) && array_key_exists($filter_group_id . '_' . $index, $all_filters))
                                            ) {
                                                $found = true;
                                                break;
                                            }
                                        }
                                    }
                                    if (!$found && $some_with_name && !empty($filter_group_id)) {
                                        $data_temp = array('name' => $names, 'filter_group_id' => $filter_group_id);
                                        $this->create_simple_filter($data_temp);
                                        $created++;
                                        $this->update_process(sprintf($this->language->get('progress_import_from_product_created_filters'), $created), true);
                                        $all_filters = $this->get_all_filters_import_format();
                                        $all_filters_simple = $this->get_all_filters_import_format(false);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        public function create_simple_filter($data) {
            $this->db->query("INSERT INTO ".$this->escape_database_table_name('filter')." SET ".$this->escape_database_field('sort_order')." = 1, ".$this->escape_database_field('filter_group_id')." = ".$data['filter_group_id']);

            $filter_id = $this->db->getLastId();

            foreach ($data['name'] as $language_id => $name) {
                $this->db->query("INSERT INTO ".$this->escape_database_table_name('filter_description')." SET ".$this->escape_database_field('filter_group_id')." = ".$this->escape_database_value($data['filter_group_id']).", ".$this->escape_database_field('filter_id')." = ".$this->escape_database_value($filter_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($language_id != $lang['language_id'])
                            $this->db->query("INSERT INTO ".$this->escape_database_table_name('filter_description')." SET ".$this->escape_database_field('filter_group_id')." = ".$this->escape_database_value($data['filter_group_id']).", ".$this->escape_database_field('filter_id')." = ".$this->escape_database_value($filter_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($lang['language_id']).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                    }
                }
            }

            return $filter_id;
        }
    }
?>