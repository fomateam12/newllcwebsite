<?php
    class ModelExtensionModuleIeProAttributes extends ModelExtensionModuleIePro {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->cat_name = 'attributes';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'attribute';
            $this->main_field = 'attribute_id';

            $this->delete_tables = array(
                'attribute_description',
            );

            $special_tables_description = array('attribute_description');
            $delete_tables = array('attribute_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Attribute id' => array('hidden_fields' => array('table' => 'attribute', 'field' => 'attribute_id')),
                'Attribute group id' => array('hidden_fields' => array('table' => 'attribute', 'field' => 'attribute_group_id')),
                'Name' => array('hidden_fields' => array('table' => 'attribute_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
                'Sort order' => array('hidden_fields' => array('table' => 'attribute', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );

            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }

        public function get_all_attributes_export_format($attribute_groups) {
            $final_attributes = array();

            foreach ($attribute_groups as $key => $attg) {
                foreach ($attg['attributes'] as $key2 => $att) {
                    $attr_id = $att['attribute_id'];
                    if(!array_key_exists($attr_id, $final_attributes))
                        $final_attributes[$attr_id] = array();

                    $final_attributes[$attr_id] = $att;
                    $final_attributes[$attr_id]['attribute_group_name'] = $attg['name'];
                    $final_attributes[$attr_id]['attribute_group_sort_order'] = $attg['sort_order'];
                }
            }
            return $final_attributes;
        }

        public function get_all_attributes_import_format($insert_group_id = true) {
            $this->load->model('extension/module/ie_pro_attribute_groups');
            $attribute_groups_export_format = $this->model_extension_module_ie_pro_attribute_groups->get_all_attribute_groups_export_format();
            $export_format = $this->get_all_attributes_export_format($attribute_groups_export_format);

            $final_attributes = array();
            foreach ($export_format as $key => $attr_info) {
                $attr_group_id = $attr_info['attribute_group_id'];
                $attr_id = $attr_info['attribute_id'];
                foreach ($attr_info['name'] as $lang_id => $name) {
                    //Index without sanitize value
                    $index = ($insert_group_id ? $attr_group_id.'_' : '').$name.'_'.$lang_id;
                    $final_attributes[$index] = $attr_id;
                    $index = ($insert_group_id ? $attr_group_id.'_' : '').$this->sanitize_value($name).'_'.$lang_id;
                    $final_attributes[$index] = $attr_id;
                }
            }
            return $final_attributes;
        }
        
        public function create_attributes_from_product($file_data) {
            $this->load->model('extension/module/ie_pro_attribute_groups');
            $all_attribute_groups = $this->model_extension_module_ie_pro_attribute_groups->get_all_attribute_groups_import_format();
            $all_attributes = $this->get_all_attributes_import_format();
            $all_attributes_simple = $this->get_all_attributes_import_format(false);

            $this->update_process($this->language->get('progress_import_from_product_creating_attributes'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_attributes'), 0));
            $created = 0;


            foreach ($file_data as $key => $product) {
                $elements = $product['product_attribute'];
                foreach ($elements as $key => $element) {
                    if(!array_key_exists('attribute_group', $element))
                        break;
                    $names = $element['attribute_group'];
                    $attribute_group_id = false;
                    foreach ($names as $lang_id => $name) {
                        if(!empty($name)) {
                            if(array_key_exists($name.'_'.$lang_id, $all_attribute_groups)) {
                                $attribute_group_id = $all_attribute_groups[$name.'_'.$lang_id];
                                break;
                            }
                        }
                    }

                    $names = array_key_exists('attribute', $element) ? $element['attribute'] : '';
                    if(!empty($names)) {
                        $found = $some_with_name = false;
                        foreach ($names as $lang_id => $name) {
                            if (!empty($name)) {
                                $some_with_name = true;
                                
                                $allow_ids = $this->extract_id_allow_ids($name);
                                if($allow_ids) {
                                    $found = true;
                                    break;
                                }
                                $index = $name . '_' . $lang_id;

                                if(!$attribute_group_id && !array_key_exists($index, $all_attributes_simple))
                                    $this->exception(sprintf($this->language->get('progress_import_from_product_creating_attributes_error_no_group'), $name));

                                if (
                                    (!$attribute_group_id && array_key_exists($index, $all_attributes_simple)) ||
                                    (!empty($attribute_group_id) && array_key_exists($attribute_group_id.'_'.$index, $all_attributes))
                                ){
                                    $found = true;
                                    break;
                                }
                            }
                        }
                        if (!$found && $some_with_name && !empty($attribute_group_id)) {
                            $data_temp = array('name' => $names, 'attribute_group_id' => $attribute_group_id);
                            $attribute_id = $this->create_simple_attribute($data_temp);
                            $created++;
                            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_attributes'), $created), true);
                            foreach ($data_temp['name'] as $language_id => $name) {
                                $index_temp = $attribute_group_id.'_'.$name.'_'.$language_id;
                                $index_temp_simple = $name.'_'.$language_id;
                                $all_attributes[$index_temp] = $name;
                                $all_attributes_simple[$index_temp_simple] = $name;
                            }
                        }
                    }
                }
            }
        }

        public function create_simple_attribute($data) {
            $this->db->query("INSERT INTO ".$this->escape_database_table_name('attribute')." SET ".$this->escape_database_field('sort_order')." = 1, ".$this->escape_database_field('attribute_group_id')." = ".$data['attribute_group_id']);

            $attribute_id = $this->db->getLastId();

            foreach ($data['name'] as $language_id => $name) {
                $this->db->query("INSERT INTO ".$this->escape_database_table_name('attribute_description')." SET ".$this->escape_database_field('attribute_id')." = ".$this->escape_database_value($attribute_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
                if(!$this->multilanguage) {
                    foreach ($this->languages as $key => $lang) {
                        if ($language_id != $lang['language_id'])
                            $this->db->query("INSERT INTO " . $this->escape_database_table_name('attribute_description') . " SET " . $this->escape_database_field('attribute_id') . " = " . $this->escape_database_value($attribute_id) . ", " . $this->escape_database_field('language_id') . " = " . $this->escape_database_value($lang['language_id']) . ", " . $this->escape_database_field('name') . " = " . $this->escape_database_value($name));
                    }
                }
            }

            return $attribute_id;
        }
    }
?>