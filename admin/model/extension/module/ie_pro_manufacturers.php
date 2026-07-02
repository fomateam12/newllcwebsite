<?php
    class ModelExtensionModuleIeProManufacturers extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'manufacturers';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'manufacturer';
            $this->main_field = 'manufacturer_id';
            $special_tables = array(
                'seo_url',
                'manufacturer_to_store',
            );

            $delete_tables = array(
                'manufacturer_description',
                'manufacturer_to_store',
            );

            $this->delete_tables_special = array(
                'seo_url',
                'url_alias',
            );

            $special_tables_description = array('manufacturer_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function pre_import($data_file)
        {
            //Call parent function to assign a element id to all tables
            $data_file = parent::pre_import($data_file);
            foreach ($data_file as $row_file_num => $fields_tables) {
                $element_id = $fields_tables[$this->main_table][$this->main_field];

                foreach ($fields_tables as $table_name => $data) {
                    if(in_array($table_name, $this->special_tables)) {
                        $data_file[$row_file_num][$table_name] = $this->{'_importing_process_format_'.$table_name}($data, $element_id, $row_file_num);

                        if($table_name == 'seo_url' && !$this->is_oc_3x) {
                            $data_file[$row_file_num]['url_alias'] = $data_file[$row_file_num][$table_name];
                            unset($data_file[$row_file_num][$table_name]);
                        }
                    }
                }
            }

            return $data_file;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Manufacturer id' => array('hidden_fields' => array('table' => 'manufacturer', 'field' => 'manufacturer_id')),
                'Name' => array('multilanguage' => $multilanguage, 'hidden_fields' => array('table' => $multilanguage && $this->manufacturer_multilanguage ? 'manufacturer_description' : 'manufacturer', 'field' => 'name')),
                'Description' => array('multilanguage' => $multilanguage && $this->is_ocstore, 'hidden_fields' => array('table' => 'manufacturer_description', 'field' => 'description')),
                'Meta title' => array('multilanguage' => $multilanguage && $this->is_ocstore, 'hidden_fields' => array('table' => 'manufacturer_description', 'field' => 'meta_title')),
                'Meta H1' => array('multilanguage' => $multilanguage && $this->is_ocstore, 'hidden_fields' => array('table' => 'manufacturer_description', 'field' => 'meta_h1')),
                'Meta description' => array('multilanguage' => $multilanguage && $this->is_ocstore, 'hidden_fields' => array('table' => 'manufacturer_description', 'field' => 'meta_description')),
                'Meta keywords' => array('multilanguage' => $multilanguage && $this->is_ocstore, 'hidden_fields' => array('table' => 'manufacturer_description', 'field' => 'meta_keyword')),
                'Manufacturer image' => array('hidden_fields' => array('table' => 'manufacturer', 'field' => 'image')),
                'Sort order' => array('hidden_fields' => array('table' => 'manufacturer', 'field' => 'sort_order')),
                'SEO url' => array('hidden_fields' => array('table' => 'seo_url', 'field' => 'keyword'), 'multilanguage' => $multilanguage && $this->is_oc_3x, 'multistore' => $this->is_oc_3x),
                'Stores' => array('hidden_fields' => array('table' => 'manufacturer_to_store', 'field' => 'store_id')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            if(!$this->is_ocstore) {
                unset($columns['Description']);
                unset($columns['Meta title']);
                unset($columns['Meta H1']);
                unset($columns['Meta description']);
                unset($columns['Meta keywords']);
            }

            if(!$this->manufacturer_multilanguage)
                unset($columns['Name']['multilanguage']);

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        public function get_manufacturer_seo_urls($manufacturer_id) {
            if($this->is_oc_3x) {
                $final_seo_urls = array();
                $url = $this->db->query('SELECT '.$this->escape_database_field('keyword').','.$this->escape_database_field('language_id').','.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('seo_url').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('manufacturer_id='.$manufacturer_id));
                foreach ($url->rows as $key => $seo_url) {
                    if(!array_key_exists($seo_url['store_id'], $final_seo_urls))
                        $final_seo_urls[$seo_url['store_id']] = array();

                    $final_seo_urls[$seo_url['store_id']][$seo_url['language_id']] = $seo_url['keyword'];
                }
                return $final_seo_urls;
            } else {
                $url = $this->db->query('SELECT '.$this->escape_database_field('keyword').' FROM '.$this->escape_database_table_name('url_alias').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('manufacturer_id='.$manufacturer_id));
                return array_key_exists('keyword', $url->row) ? $url->row['keyword'] : '';
            }
        }

        public function get_manufacturer_stores($manufacturer_id) {
            $stores = $this->db->query('SELECT '.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('manufacturer_to_store').' WHERE manufacturer_id = '.$this->escape_database_value($manufacturer_id));
            $final_stores = array();
            foreach ($stores->rows as $key => $val) {
                $final_stores[] = $val['store_id'];
            }
            return $final_stores;
        }

        public function get_all_manufacturers_export_format() {
            $final_manufacturers = array();
            if(!$this->is_ocstore || ($this->is_ocstore && $this->is_oc_3x)) {
                $manufacturers = $result = $this->db->query('SELECT name, manufacturer_id FROM ' . $this->escape_database_table_name('manufacturer'));
                foreach ($manufacturers->rows as $key => $ma) {
                    $final_manufacturers[$ma['manufacturer_id']][$this->default_language_id] = $ma['name'];
                }
            } else {
                $sql = 'SELECT * FROM '.$this->escape_database_table_name('manufacturer');
                $ma_query = $this->db->query($sql);

                $final_manufacturers = array();
                foreach ($ma_query->rows as $key => $ma) {
                    $ma_id = $ma['manufacturer_id'];
                    $sql = 'SELECT * FROM '.$this->escape_database_table_name('manufacturer_description').' WHERE '.$this->escape_database_field('manufacturer_id').' = '.$this->escape_database_value($ma_id);
                    $mad_query = $this->db->query($sql);

                    if($mad_query->num_rows) {
                        foreach ($mad_query->rows as $key2 => $ma_de) {
                            $lang_id = $ma_de['language_id'];
                            $name = array_key_exists('name', $ma_de) ? $ma_de['name'] : $ma['name'];
                            if (!array_key_exists($ma_id, $final_manufacturers))
                                $final_manufacturers[$ma_id] = array();

                            $final_manufacturers[$ma_id][$lang_id] = $name;
                        }
                    } else
                        $final_manufacturers[$ma_id][$this->default_language_id] = '';
                }
            }
            return $final_manufacturers;
        }

        public function get_all_manufacturers_import_format($select_format = false) {
            $export_format = $this->get_all_manufacturers_export_format();
            $final_manufacturers = array();

            foreach ($export_format as $manufacturer_id => $names) {
                foreach ($names as $lang_id => $name) {
                    $continue = !$select_format || ($select_format && $lang_id == $this->default_language_id);
                    if($continue) {
                        $name = $this->sanitize_value($name);
                        $final_manufacturers[(!$select_format ?  strtolower($name) . '_' . $lang_id : $name)] = $manufacturer_id;
                    }
                }
            }

            if($select_format) {
                $final_manufacturers_select = array();
                foreach ($final_manufacturers as $cat_name => $cat_id) {
                    $final_manufacturers_select[$cat_id] = $cat_name;
                }
                $final_manufacturers = $final_manufacturers_select;
            }

            return $final_manufacturers;
        }

        public function create_manufacturers_from_product($file_data) {
            $all_manufacturers = $this->get_all_manufacturers_import_format();
            $this->update_process($this->language->get('progress_import_from_product_creating_manufacturers'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_manufacturers'), 0));
            $created = 0;
            foreach ($file_data as $key => $product) {
                $found = $some_with_name = false;
                $manufacturer_name = $real_manufacturer_name = array_key_exists($this->main_table, $product) && array_key_exists('manufacturer_id', $product[$this->main_table]) && !empty($product[$this->main_table]['manufacturer_id']) ? $product[$this->main_table]['manufacturer_id'] : '';
                if(!empty($manufacturer_name)) {
                    $manufacturer_name = strtolower($this->sanitize_value($manufacturer_name));
                    $some_with_name = true;

                    $allow_ids = $this->extract_id_allow_ids($manufacturer_name);
                    if($allow_ids)
                        $found = true;
                    else if (array_key_exists($manufacturer_name . '_' . $this->default_language_id, $all_manufacturers))
                        $found = true;

                    /*
                    if (!$this->is_ocstore) {
                        $some_with_name = true;
                        if (array_key_exists($manufacturer_name . '_' . $this->default_language_id, $all_manufacturers)) {
                            $found = true;
                        }
                    } else {
                        foreach ($manufacturer_name as $lang_id => $name) {
                            if (!empty($name)) {
                                $some_with_name = true;
                                if (array_key_exists($name . '_' . $lang_id, $all_manufacturers)) {
                                    $found = true;
                                    break;
                                }
                            }
                        }
                    }*/

                    if (!$found && $some_with_name) {
                        $data_temp = array('name' => array($this->default_language_id => $real_manufacturer_name));
                        if (isset($this->profile['import_xls_keep_store_assigns']) && $this->profile['import_xls_keep_store_assigns'] == '1'){
                            $store_ids = array_key_exists('product_to_store', $product) && !empty($product['product_to_store']['store_id']) ? $product['product_to_store']['store_id'] : '0';
                            $manufacturer_id = $this->create_simple_manufacturer($data_temp, $store_ids);
                        }
                        else
                            $manufacturer_id = $this->create_simple_manufacturer($data_temp);

                        $created++;
                        $this->update_process(sprintf($this->language->get('progress_import_from_product_created_manufacturers'), $created), true);
                        $all_manufacturers = $this->get_all_manufacturers_import_format();
                    }
                }
            }
        }

        public function _exporting_process_seo_url($current_data, $manufacturer_id, $columns) {
            $seo_urls = $this->model_extension_module_ie_pro_manufacturers->get_manufacturer_seo_urls($manufacturer_id);
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

        public function _importing_process_format_seo_url($seo_urls, $manufacturer_id, $row_file_number) {
            $query = 'manufacturer_id='.$manufacturer_id;
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
                if($this->manufacturer_multilanguage) {
                    foreach ($seo_urls as $lang_id => $seo_word) {
                        $final_seo_url[] = array(
                            'query' => $query,
                            'keyword' => $seo_word['keyword'],
                            'language_id' => $lang_id,
                        );
                    }
                } else {
                    $final_seo_url = array(
                        'query' => $query,
                        'keyword' => array_key_exists($this->default_language_id, $seo_urls) && array_key_exists('keyword', $seo_urls[$this->default_language_id]) ? $seo_urls[$this->default_language_id]['keyword'] : '',
                    );
                }
            }
            return $final_seo_url;
        }

        public function _exporting_process_manufacturer_to_store($current_data, $manufacturer_id, $columns) {
            $stores = $this->model_extension_module_ie_pro_manufacturers->get_manufacturer_stores($manufacturer_id);

            if(!empty($stores)) {
                foreach ($columns as $key => $col_info) {
                    $current_data[$col_info['custom_name']] = implode('|',$stores);
                }
            }
            return $current_data;
        }

        public function _importing_process_format_manufacturer_to_store($stores, $manufacturer_id, $row_file_number) {
            $final_stores = array();
            $stores = explode('|', $stores['store_id']);

            foreach ($stores as $key => $store_id) {
                $final_stores[] = array(
                    'manufacturer_id' => $manufacturer_id,
                    'store_id' => $store_id,
                );

            }
            return $final_stores;
        }

        public function create_simple_manufacturer($data, $store_ids = null) {
            $sql = "INSERT INTO ".$this->escape_database_table_name('manufacturer')." SET ".$this->escape_database_field('sort_order')." = 1";
            if($this->manufacturer_name_in_table_manufacturer) {
                $sql .= ', '.$this->escape_database_field('name')." = ".$this->escape_database_value($data['name'][$this->default_language_id]);
            }
            $this->db->query($sql);

            $manufacturer_id = $this->db->getLastId();

            if($this->manufacturer_multilanguage) {
                foreach ($data['name'] as $language_id => $name) {
                    if(!empty($name)) {
                        $sql = "INSERT INTO " . $this->escape_database_table_name('manufacturer_description') . " SET " .
                            $this->escape_database_field('manufacturer_id') . " = " . $this->escape_database_value($manufacturer_id) . ", " .
                            $this->escape_database_field('language_id') . " = " . $this->escape_database_value($language_id) . ", ";

                        if($this->manufacturer_name_in_table_manufacturer_description)
                            $sql .= $this->escape_database_field('name') . " = " . $this->escape_database_value($name) . ", ";

                        $sql .= $this->escape_database_field('meta_title') . " = " . $this->escape_database_value($name) . ", " .
                            $this->escape_database_field('meta_h1') . " = " . $this->escape_database_value($name);

                        $this->db->query($sql);
                    }
                }
            }

            if (is_null($store_ids)) {
                foreach ($this->stores_import_format as $key => $store) {
                    $store_id = $store['store_id'];
                    $sql = 'INSERT INTO ' . $this->escape_database_table_name('manufacturer_to_store') . ' SET ' . $this->escape_database_field('manufacturer_id') . ' = ' . $this->escape_database_value($manufacturer_id) . ', ' . $this->escape_database_field('store_id') . ' = ' . $this->escape_database_value($store_id);
                    $this->db->query($sql);
                }
            }
            else{
                $store_ids = explode(',', $store_ids);
                foreach ($store_ids as $store_id){
                    $sql = 'INSERT INTO ' . $this->escape_database_table_name('manufacturer_to_store') . ' SET ' . $this->escape_database_field('manufacturer_id') . ' = ' . $this->escape_database_value($manufacturer_id) . ', ' . $this->escape_database_field('store_id') . ' = ' . $this->escape_database_value($store_id);
                    $this->db->query($sql);
                }
            }

            //Insert SEO URL
                if($this->is_oc_3x) {
                    foreach ($data['name'] as $language_id => $name) {
                        if(!empty($name)) {
                            $keyword = $this->format_seo_url($name);
                            foreach ($this->stores_import_format as $key => $store) {
                                $store_id = $store['store_id'];
                                $query = 'manufacturer_id=' . $manufacturer_id;

                                $conditions = array(
                                    'query='.$this->escape_database_value($query),
                                    'language_id='.(int)$language_id,
                                    'store_id='.(int)$store_id
                                );

                                $manufacturer_seo_exists = $this->check_element_exist("seo_url", implode(" AND ", $conditions));

                                if(!empty($manufacturer_seo_exists)) {
                                    $seo_url_id = $this->db->query("SELECT seo_url_id FROM ".DB_PREFIX."seo_url WHERE ".implode(" AND ", $conditions))->row['seo_url_id'];
                                    $sql = 'UPDATE ' . $this->escape_database_table_name('seo_url') . ' SET ' . $this->escape_database_field('keyword') . ' = ' . $this->escape_database_value($keyword)." WHERE seo_url_id = ".$seo_url_id;
                                } else {
                                    $sql = 'INSERT INTO ' . $this->escape_database_table_name('seo_url') . ' SET ' . $this->escape_database_field('query') . ' = ' . $this->escape_database_value($query) . ', ' . $this->escape_database_field('store_id') . ' = ' . $this->escape_database_value($store_id) . ', ' . $this->escape_database_field('language_id') . ' = ' . $this->escape_database_value($language_id). ', ' . $this->escape_database_field('keyword') . ' = ' . $this->escape_database_value($keyword);
                                }
                                $this->db->query($sql);
                            }
                        }
                    }
                } else {
                    if(!empty($data['name'][$this->default_language_id])) {
                        $keyword = $this->format_seo_url($data['name'][$this->default_language_id]);

                        $query = 'manufacturer_id=' . $manufacturer_id;

                        $conditions = array(
                            'query='.$this->escape_database_value($query),
                        );

                        $manufacturer_seo_exists = $this->check_element_exist("url_alias",implode(" AND ", $conditions));

                        if(!empty($manufacturer_seo_exists)) {
                            $sql = 'UPDATE ' . $this->escape_database_table_name('url_alias') . ' SET ' . $this->escape_database_field('keyword') . ' = ' . $this->escape_database_value($keyword)." WHERE url_alias = ".$manufacturer_seo_exists['url_alias'];
                        } else {
                            $sql = 'INSERT INTO ' . $this->escape_database_table_name('url_alias') . ' SET ' . $this->escape_database_field('query') . ' = ' . $this->escape_database_value($query) .", ". $this->escape_database_field('keyword') . ' = ' . $this->escape_database_value($keyword);
                        }
                        $this->db->query($sql);
                    }
                }

            return $manufacturer_id;
        }

        public function _importing_assign_default_store_and_languages_in_creation($elements) {
            foreach ($elements as $key => $element) {
                $creating = array_key_exists('empty_columns', $element) && is_array($element['empty_columns']) && array_key_exists('creating', $element['empty_columns']) && $element['empty_columns']['creating'];
                if($creating && !array_key_exists('manufacturer_to_store', $element)) {
                    $manufacturer_id = array_key_exists('manufacturer', $element) && is_array($element['manufacturer']) && array_key_exists('manufacturer_id', $element['manufacturer']) ? $element['manufacturer']['manufacturer_id'] : '';
                    if ($manufacturer_id) {
                        $elements[$key]['manufacturer_to_store'] = array(
                            array(
                                'manufacturer_id' => $manufacturer_id,
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
                }
            }
            parent::delete_element($element_id);
            $this->cache->delete('manufacturer');
        }
    }
?>