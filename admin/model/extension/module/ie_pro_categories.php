<?php
    class ModelExtensionModuleIeProCategories extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'categories';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'category';
            $this->main_field = 'category_id';

            $special_tables = array(
                'seo_url',
                'category_to_store',
                'category_to_layout',
                'category_filter',
            );

            if(version_compare(VERSION, '1.5.2.1', '<')) {
                $key = array_search('category_filter', $special_tables);
                unset($special_tables[$key]);
            }

            $delete_tables = array(
                'category_path',
                'category_description',
                'category_filter',
                'category_to_store',
                'category_to_layout',
                'product_to_category',
                'coupon_category',
            );

            if(version_compare(VERSION, '1.5.6.4', '<')) {
                $key = array_search('category_path', $delete_tables);
                unset($delete_tables[$key]);
                $key = array_search('coupon_category', $delete_tables);
                unset($delete_tables[$key]);
            }

            if(!$this->hasFilters) {
                $key = array_search('category_filter', $special_tables);
                unset($special_tables[$key]);

                $key = array_search('category_filter', $delete_tables);
                unset($delete_tables[$key]);
            }


            $delete_tables_special = array(
                'seo_url',
                'url_alias',
                'category_path',
            );

            if(version_compare(VERSION, '1.5.6.4', '<')) {
                $key = array_search('category_path', $delete_tables_special);
                unset($delete_tables_special[$key]);
            }

            $this->delete_tables_special = $delete_tables_special;

            $special_tables_description = array('category_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Category id' => array('hidden_fields' => array('table' => 'category', 'field' => 'category_id')),
                'Parent id' => array('hidden_fields' => array('table' => 'category', 'field' => 'parent_id')),
                'Name' => array('hidden_fields' => array('table' => 'category_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Description' => array('hidden_fields' => array('table' => 'category_description', 'field' => 'description'), 'multilanguage' => $multilanguage),
                'Meta title' => array('hidden_fields' => array('table' => 'category_description', 'field' => 'meta_title'), 'multilanguage' => $multilanguage),
                'Meta H1' => array('hidden_fields' => array('table' => 'category_description', 'field' => 'meta_h1'), 'multilanguage' => $multilanguage),
                'Meta description' => array('hidden_fields' => array('table' => 'category_description', 'field' => 'meta_description'), 'multilanguage' => $multilanguage),
                'Meta keywords' => array('hidden_fields' => array('table' => 'category_description', 'field' => 'meta_keyword'), 'multilanguage' => $multilanguage),
                'SEO url' => array('hidden_fields' => array('table' => 'seo_url', 'field' => 'keyword'), 'multilanguage' => $multilanguage && $this->is_oc_3x, 'multistore' => $this->is_oc_3x),
                'Image' => array('hidden_fields' => array('table' => 'category', 'field' => 'image')),
                'Top' => array('hidden_fields' => array('table' => 'category', 'field' => 'top')),
                'Columns' => array('hidden_fields' => array('table' => 'category', 'field' => 'column')),
                'Sort order' => array('hidden_fields' => array('table' => 'category', 'field' => 'sort_order')),
                'Status' => array('hidden_fields' => array('table' => 'category', 'field' => 'status')),
                'Stores' => array('hidden_fields' => array('table' => 'category_to_store', 'field' => 'store_id')),
                'Filters' => array('hidden_fields' => array('table' => 'category_filter', 'field' => 'filter')),
                'Layout' => array('multistore' => true, 'hidden_fields' => array('table' => 'category_to_layout', 'field' => 'layout_id', 'conversion_global_var' => 'layouts')),
                'Date added' => array('hidden_fields' => array('table' => 'category', 'field' => 'date_added')),
                'Date modified' => array('hidden_fields' => array('table' => 'category', 'field' => 'date_modified')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            if(version_compare(VERSION, '2', '<'))
                 unset($columns['Meta title']);

            if(!$this->hasFilters)
                unset($columns['Filters']);

            if(!$this->is_ocstore)
                unset($columns['Meta H1']);

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);

            return $columns;
        }

        function pre_import($data_file)
        {
            //Call parent function to assign a element id to all tables
            $data_file = parent::pre_import($data_file);

            $this->load->model('extension/module/ie_pro_filters');
            $this->all_filters_simple = $this->hasFilters ? $this->model_extension_module_ie_pro_filters->get_all_filters_import_format(false) : array();

            foreach ($data_file as $row_file_num => $fields_tables) {
                $element_id = $fields_tables[$this->main_table][$this->main_field];

                foreach ($fields_tables as $table_name => $data) {
                    if(in_array($table_name, $this->special_tables)) {
                        $data_formatted = $this->{'_importing_process_format_'.$table_name}($data, $element_id, $row_file_num);
                        if($table_name == 'seo_url' && !$this->is_oc_3x) {
                            unset($data_file[$row_file_num][$table_name]);
                            $data_file[$row_file_num]['url_alias'] = $data_formatted;
                        } else {
                            $data_file[$row_file_num][$table_name] = $data_formatted;
                        }
                    }
                }

                if(version_compare(VERSION, '1.5.6', '>=')) {
                    $level = 0;
                    $parent_id = array_key_exists('parent_id', $fields_tables[$this->main_table]) && !empty($fields_tables[$this->main_table]['parent_id']) ? $fields_tables[$this->main_table]['parent_id'] : '';
                    $category_id = array_key_exists('category_id', $fields_tables[$this->main_table]) && !empty($fields_tables[$this->main_table]['category_id']) ? $fields_tables[$this->main_table]['category_id'] : '';
                    if(!empty($category_id)) {
                        $this->db->query("DELETE FROM " . $this->escape_database_table_name('category_path') . " WHERE " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($category_id)." AND " . $this->escape_database_field('path_id') . " = " . $this->escape_database_value($category_id));
                        $this->db->query("INSERT INTO " . $this->escape_database_table_name('category_path') . " SET " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($category_id) . ", " . $this->escape_database_field('path_id') . " = " . $this->escape_database_value($category_id) . ", " . $this->escape_database_field('level') . " = " . $this->escape_database_value($level));
                    }
                }
            }
            return $data_file;
        }

        function reset_path($data_file) {

            $all_categories = $this->db->query("SELECT * FROM " . $this->escape_database_table_name('category'));
            $all_categories_array = array();
            foreach ($all_categories->rows as $key => $cat) {
                $all_categories_array[] = $cat['category_id'];
            }

            foreach ($all_categories->rows as $key => $cat) {
                $parent_id = $cat['parent_id'];
                $category_id = $cat['category_id'];
                if(!in_array($parent_id, $all_categories_array))
                    $this->db->query("UPDATE " . $this->escape_database_table_name('category') . " SET " . $this->escape_database_field('parent_id') . " = 0 WHERE " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($category_id));
            }

            foreach ($data_file as $row_file_num => $fields_tables) {
                $element_id = $fields_tables[$this->main_table][$this->main_field];

                if(version_compare(VERSION, '1.5.6', '>=')) {
                    $level = 0;
                    $parent_id = array_key_exists('parent_id', $fields_tables[$this->main_table]) && !empty($fields_tables[$this->main_table]['parent_id']) ? $fields_tables[$this->main_table]['parent_id'] : '';
                    $category_id = array_key_exists('category_id', $fields_tables[$this->main_table]) && !empty($fields_tables[$this->main_table]['category_id']) ? $fields_tables[$this->main_table]['category_id'] : '';
                    if(!empty($category_id)) {
                        $this->db->query("DELETE FROM " . $this->escape_database_table_name('category_path') . " WHERE " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($category_id));
                        if (!empty($parent_id)) {
                            $query = $this->db->query("SELECT * FROM " . $this->escape_database_table_name('category_path') . " WHERE " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($parent_id) . " ORDER BY " . $this->escape_database_field('level') . " ASC");
                            foreach ($query->rows as $result) {
                                $this->db->query("INSERT INTO " . $this->escape_database_table_name('category_path') . " SET " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($category_id) . ", " . $this->escape_database_field('path_id') . " = " . $this->escape_database_value($result['path_id']) . ", " . $this->escape_database_field('level') . " = " . $this->escape_database_value($level));
                                $level++;
                            }
                        }
                        $this->db->query("INSERT INTO " . $this->escape_database_table_name('category_path') . " SET " . $this->escape_database_field('category_id') . " = " . $this->escape_database_value($category_id) . ", " . $this->escape_database_field('path_id') . " = " . $this->escape_database_value($category_id) . ", " . $this->escape_database_field('level') . " = " . $this->escape_database_value($level));
                    }
                }
            }

            return true;
        }

        public function get_all_categories_export_format() {
            $query = 'SELECT '.$this->escape_database_field('category_id').', '.$this->escape_database_field('parent_id').' FROM '.$this->escape_database_table_name('category');
            $category_ids = $this->db->query($query);
            $final_categories = array();
            foreach ($category_ids->rows as $key => $cat) {
                $cat_id = $cat['category_id'];
                $final_categories[$cat_id] = array();
                $final_categories[$cat_id]['name'] = array();
                foreach ($this->languages as $lang) {
                    $lang_id = $lang['language_id'];
                    $query = 'SELECT '.$this->escape_database_field('name')
                        .' FROM '.
                        $this->escape_database_table_name('category_description')
                        .' WHERE '.
                        $this->escape_database_field('category_id').' = '.$this->escape_database_value($cat_id)
                        .' AND '.
                        $this->escape_database_field('language_id').' = '.$this->escape_database_value($lang_id);
                    $result = $this->db->query($query);

                    $name = !empty($result->row) && !empty($result->row['name']) ? $result->row['name'] : '';
                    if(!empty($name)) {
                        $final_categories[$cat_id]['name'][$lang_id] = $this->sanitize_value($name);
                        $final_categories[$cat_id]['parent_id'] = $cat['parent_id'];
                        $final_categories[$cat_id]['category_id'] = $cat_id;
                    }
                }
            }
            return $final_categories;
        }

        public function get_all_categories_tree_import_format() {
            $export_format = $this->get_all_categories_export_format();
            $final_categories = array();

            foreach ($export_format as $key => $cat) {
                foreach ($cat['name'] as $lang_id => $name) {
                    if(!empty($name)) {
                        $name = $this->sanitize_value($name);
                        $parent_id = $cat['parent_id'];
                        $final_categories[$name . '_' . $parent_id . '_' . $lang_id] = $cat['category_id'];
                    }
                }
            }

            return $final_categories;
        }

        public function get_all_categories_branches_select() {
            $this->load->model('catalog/category');
            $categories = $this->model_catalog_category->getCategories(true);
            if (empty($categories))
                $categories = $this->model_catalog_category->getCategories();

            $final_categories = array();

            if(is_array($categories)) {
                foreach ($categories as $key => $cat_info) {
                    $final_categories[$cat_info['category_id']] = $cat_info['name'];
                }
            }

            return $final_categories;
        }

        public function get_all_categories_import_format($select_format = false, $skip_errors = false) {
            $skip_errors = !$skip_errors ? $this->check_skip_duplicate_category_name_error() : $skip_errors;
            $export_format = $this->get_all_categories_export_format();
            $final_categories = array();

            foreach ($export_format as $key => $cat) {
                foreach ($cat['name'] as $lang_id => $name) {
                    $continue = !$select_format || ($select_format && $lang_id == $this->default_language_id);
                    if($continue) {
                        $name = $this->sanitize_value($name);
                        $index = $name .(!$select_format ? '_' . $lang_id : '');
                        if (!$skip_errors && array_key_exists($index, $final_categories)) {
                            $link_edit = $this->url->link('catalog/category/edit', $this->token_name . '=' . $this->session->data[$this->token_name], 'SSL') . '&category_id=' . $cat['category_id'];
                            $this->exception(sprintf($this->language->get('progress_import_from_product_error_cat_repeat_categories'), $link_edit, $name));
                        }
                        $final_categories[$index] = $cat['category_id'];
                    }
                }
            }

            if($select_format) {
                $final_categories_select = array();
                foreach ($final_categories as $cat_name => $cat_id) {
                    $final_categories_select[$cat_id] = $cat_name;
                }
                $final_categories = $final_categories_select;
            }

            return $final_categories;
        }

        public function get_all_categories_catalog() {
            $this->load->model('catalog/category');
            $categories = $this->model_catalog_category->getCategories(true);

            if (empty($categories)) {
                $categories = $this->model_catalog_category->getCategories();
            }

            return $categories;
        }

        public function create_categores_from_product($file_data) {
            $all_categories = $this->get_all_categories_tree_import_format();
            $this->update_process($this->language->get('progress_import_from_product_creating_categories'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_categories'), 0));
            $cats_created = 0;
            $previous_parent_id = 0;
            if($this->cat_tree) {
                $child_number = (int)$this->profile['import_xls_cat_tree_children_number']+1;

                foreach ($file_data as $key => $product) {
                    $cat_data = array_key_exists('product_to_category', $product) ? $product['product_to_category'] : array();
                    foreach ($cat_data as $position => $cat_names) {
                        if(!is_numeric($position))
                            continue;
                        for ($i = 0; $i < $child_number; $i++) {
                            $parent_id = false;
                            if($i == 0)
                                $cat_names_temp = array_key_exists('name', $cat_names) ? $cat_names['name'] : '';
                            else
                                $cat_names_temp = array_key_exists($i, $cat_names) ? $cat_names[$i]['name'] : '';

                            $some_cat_with_name = false;
                            if(!empty($cat_names_temp)) {
                                foreach ($cat_names_temp as $lang_id => $cat_name) {
                                    if(!empty($cat_name)) {
                                        $some_cat_with_name = true;
                                        if ($i == 0)
                                            $previous_parent_id = 0;

                                        $allow_ids = $this->extract_id_allow_ids($cat_name);
                                        if($allow_ids) {
                                            $parent_id = $allow_ids;
                                            $previous_parent_id = $parent_id;
                                            break;
                                        } else {
                                            $name_formatted = $cat_name . '_' . $previous_parent_id . '_' . $lang_id;

                                            if (array_key_exists($name_formatted, $all_categories)) {
                                                $parent_id = $all_categories[$name_formatted];
                                                $previous_parent_id = $parent_id;
                                                break;
                                            } else {
                                                $parent_id = false;
                                                /*$cat_name_prefix = "{$cat_name}_";

                                                foreach ($all_categories as $name => $id) {
                                                    if (strpos( $name, $cat_name_prefix) === 0) {
                                                        $parent_id = true;
                                                        break 2;
                                                    }
                                                }*/
                                            }
                                        }
                                    }
                                }

                                if(!$parent_id && $some_cat_with_name) {
                                    $cat_data_temp = array(
                                        'parent_id' => $previous_parent_id,
                                        'name' => $cat_names_temp
                                    );
                                    if (isset($this->profile['import_xls_keep_store_assigns']) && $this->profile['import_xls_keep_store_assigns'] == '1'){
                                        $store_ids = array_key_exists('product_to_store', $product) && !empty($product['product_to_store']['store_id']) ? $product['product_to_store']['store_id'] : '0';
                                        $previous_parent_id = $this->create_simple_category($cat_data_temp, $store_ids);
                                    }
                                    else{
                                        $previous_parent_id = $this->create_simple_category($cat_data_temp);
                                    }
                                    $cats_created++;
                                    $this->update_process(sprintf($this->language->get('progress_import_from_product_created_categories'), $cats_created), true);
                                    $all_categories = $this->get_all_categories_tree_import_format();
                                }
                            }
                        }
                    }
                }
            } else {
                $all_categories = $this->get_all_categories_import_format( false, $this->all_categories_mapped);
                $cat_number = (int)$this->profile['import_xls_cat_number'];
                foreach ($file_data as $key => $product) {
                    $cat_data = array_key_exists('product_to_category', $product) ? $product['product_to_category'] : array();
                    if(!empty($cat_data)) {
                        for ($i = 1; $i <= $cat_number; $i++) {
                            $cat_names = array_key_exists($i, $cat_data) && array_key_exists('category_id', $cat_data[$i]) ? $cat_data[$i]['category_id'] : array();
                            $cat_found = $some_cat_with_name = false;
                            foreach ($cat_names as $lang_id => $name) {
                                if (!empty($name)) {
                                    $allow_ids = $this->extract_id_allow_ids($name);
                                    if($allow_ids) {
                                        $cat_found = true;
                                        break;
                                    }
                                    $some_cat_with_name = true;
                                    if (array_key_exists($name . '_' . $lang_id, $all_categories)) {
                                        $cat_found = true;
                                        break;
                                    }
                                }
                            }
                            if (!$cat_found && $some_cat_with_name) {
                                $cat_data_temp = array(
                                    'parent_id' => 0,
                                    'name' => $cat_names
                                );

                                if (isset($this->profile['import_xls_keep_store_assigns']) && $this->profile['import_xls_keep_store_assigns'] == '1'){
                                    $store_ids = array_key_exists('product_to_store', $product) && !empty($product['product_to_store']['store_id']) ? $product['product_to_store']['store_id'] : '0';
                                    $previous_parent_id = $this->create_simple_category($cat_data_temp, $store_ids);
                                }
                                else{
                                    $previous_parent_id = $this->create_simple_category($cat_data_temp);
                                }
                                $cats_created++;
                                $this->update_process(sprintf($this->language->get('progress_import_from_product_created_categories'), $cats_created), true);
                                $all_categories = $this->get_all_categories_import_format( false, $this->all_categories_mapped);
                            }

                        }
                    }
                }
            }
        }

        public function check_skip_duplicate_category_name_error() {
            $id_instead_of_name = false;
            foreach ($this->columns as $key => $col_info) {
                if($col_info['field'] != 'category_id')
                    continue;
                if(array_key_exists('id_instead_of_name', $col_info) && $col_info['id_instead_of_name'])
                    $id_instead_of_name = true;
                else {
                    $id_instead_of_name = false;
                    break;
                }
            }
            return $id_instead_of_name;
        }
        public function create_category_description($cat_id, $lang_id, $name) {
            $sql = 'INSERT INTO '.$this->escape_database_table_name('category_description').' SET '.$this->escape_database_field('name').' = '.$this->escape_database_value($name).', '.$this->escape_database_field('language_id').' = '.$this->escape_database_value($lang_id).', '.$this->escape_database_field('category_id').' = '.$this->escape_database_value($cat_id);
            $this->db->query($sql);
            $cat_description_id = $this->db->getLastId();

            $condition = $this->escape_database_field('category_id').' = '.$this->escape_database_value($cat_id).' AND '.$this->escape_database_field('language_id').' = '.$this->escape_database_value($lang_id);

            //Insert meta_h1 only for ocstore
                if($this->is_ocstore)
                    $this->db->query('UPDATE '.$this->escape_database_table_name('category_description').' SET '.$this->escape_database_field('meta_h1').' = '.$this->escape_database_value($name).' WHERE '.$condition);
            //Insert meta_title only for opencart 2.x and more
                if(version_compare(VERSION, '1.5.6.4', '>='))
                    $this->db->query('UPDATE '.$this->escape_database_table_name('category_description').' SET '.$this->escape_database_field('meta_title').' = '.$this->escape_database_value($name).' WHERE '.$condition);

        }

        public function create_simple_category($cat_data, $store_ids = null) {
            if(is_file($this->assets_path.'model_ie_pro_categories_create_simple_category_begin.php'))
                require($this->assets_path.'model_ie_pro_categories_create_simple_category_begin.php');


            $sql = 'INSERT INTO '.$this->escape_database_table_name('category').' SET '.$this->escape_database_field('parent_id').' = '.$this->escape_database_value($cat_data['parent_id']).', '.$this->escape_database_field('status').' = 1, '.$this->escape_database_field('top').' = 1, '.$this->escape_database_field('column').' = 1, '.$this->escape_database_field('date_added').' = NOW(), '.$this->escape_database_field('date_modified').' = NOW()';

            $this->db->query($sql);
            $cat_id = $this->db->getLastId();

            foreach ($cat_data['name'] as $lang_id => $name) {
                $this->create_category_description($cat_id, $lang_id, $name);
                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($lang_id != $lang['language_id'])
                            $this->create_category_description($cat_id, $lang['language_id'], $name);
                    }
                }
            }

            if(version_compare(VERSION, '1.5.6.4', '>=')) {
                $level = 0;

                $query = $this->db->query("SELECT * FROM ".$this->escape_database_table_name('category_path')." WHERE ".$this->escape_database_field('category_id')." = " . $this->escape_database_value($cat_data['parent_id']) . " ORDER BY ".$this->escape_database_field('level')." ASC");

                foreach ($query->rows as $result) {
                    $this->db->query("INSERT INTO ".$this->escape_database_table_name('category_path')." SET ".$this->escape_database_field('category_id')." = " . $this->escape_database_value($cat_id) . ", ".$this->escape_database_field('path_id')." = " . $this->escape_database_value($result['path_id']) . ", ".$this->escape_database_field('level')." = " . $this->escape_database_value($level));
                    $level++;
                }

                $this->db->query("INSERT INTO ".$this->escape_database_table_name('category_path')." SET ".$this->escape_database_field('category_id')." = " . $this->escape_database_value($cat_id) . ", ".$this->escape_database_field('path_id')." = " . $this->escape_database_value($cat_id) . ", ".$this->escape_database_field('level')." = " . $this->escape_database_value($level));
            }

            if (is_null($store_ids)) {
                foreach ($this->stores_import_format as $key => $store) {
                    $store_id = $store['store_id'];
                    $sql = 'INSERT INTO ' . $this->escape_database_table_name('category_to_store') . ' SET ' . $this->escape_database_field('category_id') . ' = ' . $this->escape_database_value($cat_id) . ', ' . $this->escape_database_field('store_id') . ' = ' . $this->escape_database_value($store_id);
                    $this->db->query($sql);
                }
            }
            else{
                $store_ids = explode(',', $store_ids);
                foreach ($store_ids as $store_id){
                    $sql = 'INSERT INTO ' . $this->escape_database_table_name('category_to_store') . ' SET ' . $this->escape_database_field('category_id') . ' = ' . $this->escape_database_value($cat_id) . ', ' . $this->escape_database_field('store_id') . ' = ' . $this->escape_database_value($store_id);
                    $this->db->query($sql);
                }
            }

            return $cat_id;
        }

        public function get_parent_id($cat_id) {
            $temporal_sql = "SELECT c.parent_id FROM `" . $this->db_prefix . "category` c WHERE c.category_id = ".$cat_id;
            $result = $this->db->query( $temporal_sql );

            return !empty($result->row['parent_id']) ? $result->row['parent_id'] : '';
        }

        function build_categories_tree(array $data) {
			$tree = array();
		    foreach($data as &$v){
		    	if(array_key_exists('category_id', $v))
		    	{
					// Get childs
					if(isset($tree[$v['category_id']])) $v['childrens'] =& $tree[$v['category_id']];
					// push node into parent
					$tree[$v['parent_id']][$v['category_id']] =& $v;

					// push childrens into node
					$tree[$v['category_id']] =& $v['childrens'];
				}
			}

			// return Tree
			if(!empty($tree[0]))
				return $this->array_values_recursive($tree[0]);
			else
				return array();
		}

		public function get_category_seo_urls($category_id) {
            if($this->is_oc_3x) {
                $final_seo_urls = array();
                $url = $this->db->query('SELECT '.$this->escape_database_field('keyword').','.$this->escape_database_field('language_id').','.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('seo_url').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('category_id='.$category_id));
                foreach ($url->rows as $key => $seo_url) {
                    if(!array_key_exists($seo_url['store_id'], $final_seo_urls))
                        $final_seo_urls[$seo_url['store_id']] = array();

                    $final_seo_urls[$seo_url['store_id']][$seo_url['language_id']] = $seo_url['keyword'];
                }
                return $final_seo_urls;
            } else {
                $url = $this->db->query('SELECT '.$this->escape_database_field('keyword').' FROM '.$this->escape_database_table_name('url_alias').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('category_id='.$category_id));
                return array_key_exists('keyword', $url->row) ? $url->row['keyword'] : '';
            }
        }

        public function get_category_stores($category_id) {
            $stores = $this->db->query('SELECT '.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('category_to_store').' WHERE category_id = '.$this->escape_database_value($category_id));
            $final_stores = array();
            foreach ($stores->rows as $key => $val) {
                $final_stores[] = $val['store_id'];
            }
            return $final_stores;
        }

        public function get_category_filters($category_id) {
            $filters = $this->db->query('SELECT catfd.name FROM '.$this->escape_database_table_name('category_filter').' catf LEFT JOIN '.$this->escape_database_table_name('filter_description').' catfd ON(catfd.filter_id = catf.filter_id AND catfd.language_id = '.$this->escape_database_value($this->default_language_id).') WHERE catf.category_id = '.$this->escape_database_value($category_id));
            $final_filters = array();
            foreach ($filters->rows as $key => $val) {
                $final_filters[] = $val['name'];
            }
            return $final_filters;
        }

        public function get_category_layouts($category_id) {
            $layouts = $this->db->query('SELECT '.$this->escape_database_field('layout_id').', '.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('category_to_layout').' WHERE category_id = '.$this->escape_database_value($category_id));
            $final_layouts = array();
            foreach ($layouts->rows as $key => $val) {
                $store_id = $val['store_id'];
                $final_layouts[$store_id] = $val['layout_id'];
            }
            return $final_layouts;
        }



        public function _exporting_process_category_filter($current_data, $category_id, $columns) {
            if($this->hasFilters) {
                $filters = $this->get_category_filters($category_id);

                if (!empty($filters)) {
                    foreach ($columns as $key => $col_info) {
                        $current_data[$col_info['custom_name']] = implode('|', $filters);
                    }
                }
            }
            return $current_data;
        }

        public function _importing_process_format_category_filter($filters, $category_id, $row_file_number) {
            $final_filters = array();
            if($this->hasFilters) {
                $filters = explode('|', $filters['filter']);

                foreach ($filters as $key => $name) {
                    $index = $name . '_' . $this->default_language_id;
                    $filter_id = array_key_exists($index, $this->all_filters_simple) ? $this->all_filters_simple[$index] : '';
                    if (!empty($filter_id)) {
                        $final_filters[] = array(
                            'category_id' => $category_id,
                            'filter_id' => $filter_id,
                        );
                    }
                }
            }

            return $final_filters;
        }

        public function _exporting_process_category_to_store($current_data, $category_id, $columns) {
            $stores = $this->get_category_stores($category_id);

            if(!empty($stores)) {
                foreach ($columns as $key => $col_info) {
                    $current_data[$col_info['custom_name']] = implode('|',$stores);
                }
            }
            return $current_data;
        }

        public function _importing_process_format_category_to_store($stores, $category_id, $row_file_number) {
            $final_stores = array();
            $stores = explode('|', $stores['store_id']);

            foreach ($stores as $key => $store_id) {
                $final_stores[] = array(
                    'category_id' => $category_id,
                    'store_id' => $store_id,
                );

            }
            return $final_stores;
        }


        public function _exporting_process_category_to_layout($current_data, $category_id, $columns) {
            $layouts = $this->get_category_layouts($category_id);

            if(!empty($layouts)) {
                foreach ($columns as $key => $col_info) {
                    $store_id = $col_info['store_id'];
                    if(array_key_exists($store_id, $layouts))
                     $current_data[$col_info['custom_name']] = $layouts[$store_id];
                }
            }
            return $current_data;
        }

        public function _importing_process_format_category_to_layout($layouts, $category_id, $row_file_number) {
            $final_layouts = array();
            if(!empty($layouts) && is_array($layouts)) {
                foreach ($layouts as $store_id => $layout_id) {
                    $final_layouts[] = array(
                        'layout_id' => $layout_id['layout_id'],
                        'store_id' => $store_id,
                        'category_id' => $category_id,
                    );

                }
            }
            return $final_layouts;
        }

		public function _exporting_process_seo_url($current_data, $category_id, $columns) {
            $seo_urls = $this->get_category_seo_urls($category_id);
            if($this->is_oc_3x) {
                foreach ($columns as $key => $col_info) {
                    $store_id = $col_info['store_id'];
                    $language_id = !array_key_exists('language_id', $col_info) ? $this->default_language_id : $col_info['language_id'];
                    foreach ($seo_urls as $seo_url_store_id => $seo_url_names) {
                        if ($store_id == $seo_url_store_id && array_key_exists($language_id, $seo_url_names) && !empty($seo_url_names[$language_id]))
                            $current_data[$col_info['custom_name']] = $seo_url_names[$language_id];
                    }
                }
            } else {
                foreach ($columns as $key => $col_info) {
                    $current_data[$col_info['custom_name']] = $seo_urls;
                }
            }
            return $current_data;
        }

        public function _importing_process_format_seo_url($seo_urls, $category_id, $row_file_number) {
            $query = 'category_id='.$category_id;
            $final_seo_url = array();
            if($this->is_oc_3x) {
                foreach ($seo_urls as $store_id => $names) {
                    foreach ($names['keyword'] as $lang_id => $name) {
                        if(!empty($name)) {
                            $final_seo_url[] = array(
                                'query' => $query,
                                'store_id' => $store_id,
                                'language_id' => $lang_id,
                                'keyword' => $this->format_seo_url($name)
                            );
                        }
                    }

                }
            } else {
                $final_seo_url = array(
                    'query' => $query,
                    'keyword' => array_key_exists($this->default_language_id, $seo_urls) && array_key_exists('keyword', $seo_urls[$this->default_language_id]) ? $this->format_seo_url($seo_urls[$this->default_language_id]['keyword']) : '',
                );
            }

            return $final_seo_url;
        }

        function array_values_recursive( $array ) {
		    $newarray = array();
		    if(!empty($array))
		    {
				foreach ($array as $value) {
			        $value["childrens"] = $this->array_values_recursive($value["childrens"]);
			        $newarray[] = $value;
				}
			}
			return $newarray;
		}

		public function _importing_assign_default_store_and_languages_in_creation($elements) {
            foreach ($elements as $key => $element) {
                $creating = array_key_exists('empty_columns', $element) && is_array($element['empty_columns']) && array_key_exists('creating', $element['empty_columns']) && $element['empty_columns']['creating'];
                if($creating && !array_key_exists('category_to_store', $element)) {
                    $category_id = array_key_exists('category', $element) && is_array($element['category']) && array_key_exists('category_id', $element['category']) ? $element['category']['category_id'] : '';
                    if ($category_id) {
                        $elements[$key]['category_to_store'] = array(
                            array(
                                'category_id' => $category_id,
                                'store_id' => 0
                            )
                        );
                    }
                }
            }

            return $elements;
        }

		public function delete_element($element_id) {
            foreach ($this->delete_tables_special as $key => $table_name) {
                if(array_key_exists($table_name, $this->database_schema)) {
                    if(in_array($table_name, array('seo_url', 'url_alias')))
                        $this->db->query("DELETE FROM ".$this->escape_database_table_name($table_name)." WHERE ".$this->escape_database_field('query')." = ".$this->escape_database_value($this->main_field.'='.(int)$element_id));
                    else if(in_array($table_name, array('category_path'))) {
                        $this->db->query("DELETE FROM ".$this->escape_database_table_name($table_name)." WHERE ".$this->escape_database_field('path_id')." = ".$this->escape_database_value($this->main_field.'='.(int)$element_id));
                    }
                }
            }
            parent::delete_element($element_id);
            $this->cache->delete('category');
        }
    }
?>
