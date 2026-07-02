<?php
    class ModelExtensionModuleIeProAttributeGroups extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'attribute_groups';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'attribute_group';
            $this->main_field = 'attribute_group_id';

            $special_tables_description = array('attribute_group_description');
            $delete_tables = array('attribute_group_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Attribute group id' => array('hidden_fields' => array('table' => 'attribute_group', 'field' => 'attribute_group_id')),
                'Name' => array('hidden_fields' => array('table' => 'attribute_group_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Sort order' => array('hidden_fields' => array('table' => 'attribute_group', 'field' => 'sort_order'), ),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        public function get_all_attribute_groups_export_format() {
            $sql = 'SELECT * FROM '.$this->escape_database_table_name('attribute_group').' fg LEFT JOIN '.$this->escape_database_table_name('attribute_group_description').' fgd ON(fg.`attribute_group_id` = fgd.`attribute_group_id`)';

            $fg_query = $this->db->query($sql);

            $attribute_groups = array();
            foreach ($fg_query->rows as $key => $ag_info) {
                $ag_id = $ag_info['attribute_group_id'];
                $lang_id = $ag_info['language_id'];

                if(!array_key_exists($ag_id, $attribute_groups))
                    $attribute_groups[$ag_id] = array(
                        'name' => array(),
                        'attribute_group_id' => $ag_id,
                        'sort_order' => $ag_info['sort_order'],
                        'attributes' => array()
                    );
                $attribute_groups[$ag_id]['name'][$lang_id] = $ag_info['name'];
            }

            foreach ($attribute_groups as $attribute_group_id => $ag_info) {
                $sql = 'SELECT * FROM '.$this->escape_database_table_name('attribute').' fi LEFT JOIN '.$this->escape_database_table_name('attribute_description').' fid ON(fi.`attribute_id` = fid.`attribute_id`) WHERE fi.`attribute_group_id` = '.$this->escape_database_value($attribute_group_id);
                $f_query = $this->db->query($sql);
                foreach ($f_query->rows as $key => $f_info) {
                    $attribute_id = $f_info['attribute_id'];
                    $language_id = $f_info['language_id'];

                    if(!array_key_exists($attribute_id, $attribute_groups[$attribute_group_id]['attributes'])) {
                        $attribute_groups[$attribute_group_id]['attributes'][$attribute_id] = array(
                            'attribute_id' => $attribute_id,
                            'attribute_group_id' => $attribute_group_id,
                            'sort_order' => $f_info['sort_order'],
                            'name' => array()
                        );
                    }
                    $attribute_groups[$attribute_group_id]['attributes'][$attribute_id]['name'][$language_id] = $f_info['name'];
                }
            }
            return $attribute_groups;
        }
        
        public function get_all_attribute_groups_import_format() {
            $export_format = $this->get_all_attribute_groups_export_format();

            $final_attribute_groups = array();

            $attribute_group_ids = false;
            foreach ($this->custom_columns as $key => $column_info) {
                $field = array_key_exists('field', $column_info) ? $column_info['field'] : '';
                if($field == 'attribute_group' && array_key_exists('allow_ids', $column_info) && !empty($column_info['allow_ids'])) {
                    $attribute_group_ids = true;
                    break;
                }
            }

            foreach ($export_format as $key => $attrg) {
                $attrg_id = $attrg['attribute_group_id'];
                foreach ($attrg['name'] as $lang_id => $name) {
                    $name = $this->sanitize_value($name);
                    $index = $name . '_' . $lang_id;
                    if(array_key_exists($index, $final_attribute_groups) && !$attribute_group_ids) {
                        $link_edit = $this->url->link('catalog/attribute_group/edit', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL').'&attribute_group_id='.$attrg_id;
                        $this->exception(sprintf($this->language->get('progress_import_from_product_creating_attribute_groups_error_repeat'), $link_edit, $name));
                    }

                    $final_attribute_groups[$index] = $attrg_id;
                }
            }
            return $final_attribute_groups;
        }

        public function create_attribute_groups_from_product($file_data) {
            $all_attributegroups = $this->get_all_attribute_groups_import_format();
            $this->update_process($this->language->get('progress_import_from_product_creating_attribute_groups'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_attribute_groups'), 0));
            $created = 0;
            foreach ($file_data as $key => $product) {
                $elements = $product['product_attribute'];
                foreach ($elements as $key => $element) {
                    if(!array_key_exists('attribute_group', $element))
                        break;
                    $names = $element['attribute_group'];
                    $found = $some_with_name = false;
                    foreach ($names as $lang_id => $name) {
                        if(!empty($name)) {
                            $allow_ids = $this->extract_id_allow_ids($name);
                            if($allow_ids) {
                                $found = true;
                                break;
                            }

                            $some_with_name = true;
                            if(array_key_exists($name.'_'.$lang_id, $all_attributegroups)) {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if(!$found && $some_with_name) {
                        $data_temp = array('name' => $names);
                        $attribute_group_id = $this->create_simple_attribute_group($data_temp);
                        $created++;
                        $this->update_process(sprintf($this->language->get('progress_import_from_product_created_attribute_groups'), $created), true);

                        foreach ($data_temp['name'] as $language_id => $name) {
                            $all_attributegroups[$name.'_'.$language_id] = $attribute_group_id;
                        }
                    }
                }
            }
        }

        public function create_simple_attribute_group($data) {
            $this->db->query("INSERT INTO ".$this->escape_database_table_name('attribute_group')." SET ".$this->escape_database_field('sort_order')." = 1");

            $attribute_group_id = $this->db->getLastId();

            foreach ($data['name'] as $language_id => $name) {
                $this->db->query("INSERT INTO ".$this->escape_database_table_name('attribute_group_description')." SET ".$this->escape_database_field('attribute_group_id')." = ".$this->escape_database_value($attribute_group_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($language_id != $lang['language_id'])
                            $this->db->query("INSERT INTO " . $this->escape_database_table_name('attribute_group_description') . " SET " . $this->escape_database_field('attribute_group_id') . " = " . $this->escape_database_value($attribute_group_id) . ", " . $this->escape_database_field('language_id') . " = " . $this->escape_database_value($lang['language_id']) . ", " . $this->escape_database_field('name') . " = " . $this->escape_database_value($name));
                    }
                }
            }
            return $attribute_group_id;
        }
    }
?>