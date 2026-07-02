<?php
    class ModelExtensionModuleIeProExport extends ModelExtensionModuleIePro {
        public function __construct($registry){
            parent::__construct($registry);
        }

        public function export($profile) {
            $profile_data = $profile['profile'];
            $this->profile = $profile_data;
            $this->multilanguage = array_key_exists('import_xls_multilanguage', $profile_data) && $profile_data['import_xls_multilanguage'];
            $this->cat_tree =  array_key_exists('import_xls_category_tree', $this->profile) && $this->profile['import_xls_category_tree'];
            $this->last_cat_assign = array_key_exists('import_xls_category_tree_last_child', $this->profile) && $this->profile['import_xls_category_tree_last_child'];
            $columns = $this->clean_columns($profile_data['columns']);
            $this->custom_fixed_columns = $this->get_custom_fixed_columns($this->profile);

            if($this->custom_fixed_columns != '')
                $columns = $this->insert_custom_fixed_columns($columns, true);

            $this->columns = $columns;
            $this->related_identificator = array_key_exists('Products related', $this->columns) && array_key_exists('product_id_identificator', $this->columns['Products related']) && !empty($this->columns['Products related']['product_id_identificator']) ? $this->columns['Products related']['product_id_identificator'] : 'model';
            $this->conversion_fields = $this->get_conversion_fields($this->columns);
            $this->custom_columns = $this->get_custom_columns($this->columns);
            $this->columns_fields = $this->get_columns_field_format($this->columns);
            $this->conditional_values = $this->get_conditional_values($this->custom_columns);
            $elements_to_export = $profile['profile']['import_xls_i_want'];
            $this->elements_to_export = $elements_to_export;
            $model_name = 'ie_pro_'.$elements_to_export;
            $model_path = 'extension/module/'.$model_name;
            $this->model_loaded = 'model_extension_module_'.$model_name;
            $this->load->model($model_path);
            $this->{$this->model_loaded}->set_model_tables_and_fields();
            $format = $this->profile['import_xls_file_format'];

            if(in_array($elements_to_export, array('products'))) {
                $this->load->model('extension/module/ie_pro_categories');
                $this->all_categories = $this->model_extension_module_ie_pro_categories->get_all_categories_export_format();

                if($this->hasFilters) {
                    $this->load->model('extension/module/ie_pro_filters');
                    $this->all_filters = $this->model_extension_module_ie_pro_filters->get_all_filters_export_format();
                }

                $this->load->model('extension/module/ie_pro_attribute_groups');
                $this->all_attribute_groups = $this->model_extension_module_ie_pro_attribute_groups->get_all_attribute_groups_export_format();

                $this->load->model('extension/module/ie_pro_attributes');
                $this->all_attributes = $this->model_extension_module_ie_pro_attributes->get_all_attributes_export_format($this->all_attribute_groups);

                $this->load->model('extension/module/ie_pro_manufacturers');
                $this->all_manufacturers = $this->model_extension_module_ie_pro_manufacturers->get_all_manufacturers_export_format();

                $this->load->model('extension/module/ie_pro_option_values');
                $this->all_option_values = $this->model_extension_module_ie_pro_option_values->get_all_option_values_export_format();

                $this->all_options = $this->model_extension_module_ie_pro_options->get_all_options_export_format();

                $this->load->model('extension/module/ie_pro_customer_groups');
                $this->all_customer_groups = $this->model_extension_module_ie_pro_customer_groups->get_all_customer_groups();

                $this->load->model('extension/module/ie_pro_downloads');
                $this->all_downloads = $this->model_extension_module_ie_pro_downloads->get_all_downloads_export_format();
            } else if(in_array($elements_to_export, array('specials', 'discounts', 'images'))) {
                $this->load->model('extension/module/ie_pro_products');
                $this->load->model('extension/module/ie_pro_customer_groups');
                $this->all_customer_groups = $this->model_extension_module_ie_pro_customer_groups->get_all_customer_groups(true);
            } else if(in_array($elements_to_export, array('product_option_values'))) {
                $this->load->model('extension/module/ie_pro_options');
                $this->options = $this->model_extension_module_ie_pro_options->get_all_options_export_format(true);

                $this->load->model('extension/module/ie_pro_option_values');
                $this->option_values = $this->model_extension_module_ie_pro_option_values->get_all_option_values_export_format(true);
            } else if(in_array($elements_to_export, array('option_values'))) {
                $this->load->model('extension/module/ie_pro_options');
                $this->all_options = $this->model_extension_module_ie_pro_options->get_all_options_export_format(true);
            }

            if(is_file($this->assets_path.'model_ie_pro_export_function_export_add_elements.php'))
                require_once($this->assets_path.'model_ie_pro_export_function_export_add_elements.php');

            $element_ids = $this->get_elements_id($profile);
            $this->update_process(sprintf($this->language->get('progress_export_element_numbers'), count($element_ids)));
            $elements = array();
            if(!empty($element_ids)) {
                $tables_fields = $this->get_col_map_tables_fields($columns);
                if(!empty($tables_fields)) {
                    $this->update_process(sprintf($this->language->get('progress_export_processing_elements'), count($element_ids)));
                    $elements = $this->get_elements($tables_fields, $element_ids);
                }
            }

            if(is_file($this->assets_path.'model_ie_pro_export_just_after_function_get_elements.php'))
                require_once($this->assets_path.'model_ie_pro_export_just_after_function_get_elements.php');

            $elements = $this->insert_default_values($this->columns, $elements);
            $elements = $this->conversion_values($elements);
            $elements = $this->insert_modifications_values($this->columns, $elements);
            if($this->custom_fixed_columns != '')
                $elements = $this->insert_custom_fixed_columns($elements);
            if($this->conditional_values != '')
                $elements = $this->insert_conditional_values($elements);

            if($format == 'xlsx') {
                $this->load->model('extension/module/ie_pro_file');
                $this->load->model('extension/module/ie_pro_file_xlsx');
                $this->model_extension_module_ie_pro_file_xlsx->check_cell_limit($elements);
            }

            if($format == 'xls') {
                $this->load->model('extension/module/ie_pro_file');
                $this->load->model('extension/module/ie_pro_file_xls');
                $this->model_extension_module_ie_pro_file_xls->check_cell_limit($elements);
            }

            if($format == 'xml') {
                $elements = $this->order_xml_columns($elements);
            }

            if(is_file($this->assets_path.'model_ie_pro_export_just_before_function_insert_elemements_into_file.php'))
                require_once($this->assets_path.'model_ie_pro_export_just_before_function_insert_elemements_into_file.php');

            $this->insert_elemements_into_file($elements);
        }

        /*
         * Proccess all export filters and optain only element_id
         * */
        public function get_elements_id($profile) {
            $category = $profile['profile']['import_xls_i_want'];

            $main_table = $this->main_table;
            $main_field = $this->main_field;
            $main_table_formatted = $this->escape_database_field($this->db_prefix.$main_table);
            $main_field_formatted = 'main_table.'.$main_field;

            $sql = 'SELECT '.$main_field_formatted.' FROM '.$main_table_formatted.' main_table ';

            if (isset( $profile['profile']['filters_v2']))
            {
                $joinsAndWhere = $this->process_filters_v2( $profile['real_type'], $profile['profile']['filters_v2'], $main_table, $main_field);

                $joins = $joinsAndWhere->joins;
                $where = $joinsAndWhere->where;
            }
            else
            {
                $joinsAndWhere = $this->process_filters_old( $profile, $main_table, $main_field);

                $joins = $joinsAndWhere->joins;
                $where = $joinsAndWhere->where;
            }

            if($category == 'products') {
                $category_id_filters = array_key_exists('import_xls_quick_filter_category_ids', $profile['profile']) && !empty($profile['profile']['import_xls_quick_filter_category_ids']);
                if(!empty($category_id_filters)) {
                    $joins .= ' INNER JOIN ' . $this->escape_database_table_name('product_to_category') . ' ptc ON (main_table.product_id = ptc.product_id AND ptc.category_id IN(' . implode(',', $profile['profile']['import_xls_quick_filter_category_ids']) . ')) ';
                }

                $manufacturer_id_filters = array_key_exists('import_xls_quick_filter_manufacturer_ids', $profile['profile']) && !empty($profile['profile']['import_xls_quick_filter_manufacturer_ids']);
                if(!empty($manufacturer_id_filters)) {
                     if(empty($where))
                         $where .= ' WHERE ';
                     else
                         $where = str_replace('WHERE', 'WHERE (', $where).') AND';

                     $where .= ' main_table.manufacturer_id IN ('.implode(',', $profile['profile']['import_xls_quick_filter_manufacturer_ids']).')';
                }
            }

            $sql = $sql.$joins.$where.' GROUP BY main_table.'.$main_field;
            $from = array_key_exists('from', $this->request->post) ? (int)$this->request->post['from'] : 0;
            $to = array_key_exists('to', $this->request->post) ? (int)$this->request->post['to'] : 0;

            if($to < $from)
                $this->exception($this->language->get('progress_export_error_range'));

            if($this->is_t && ($to - $from) > $this->is_t_elem)
                $this->exception(sprintf($this->language->get('trial_operation_restricted_export'), $this->is_t_elem, ($to - $from)));

            if($from > 0 || $to > 0)
                $sql .= ' LIMIT ';
            if($from > 0)
                $sql .= ($from-1).($to == 0 ? ',100000000000000':'');
            if($to > 0)
                $sql .= ($from == 0 ? '1':'').','.( ($to-$from) + 1);

            $sort_order = array_key_exists('export_sort_order', $profile['profile']) && !empty($profile['profile']['export_sort_order']['table_field']);
            if($sort_order) {
                $table_field = $profile['profile']['export_sort_order']['table_field'];
                $sort_mode = $profile['profile']['export_sort_order']['sort_order'];
                $table_field_split = explode('-', $table_field);
                $table = $table_field_split[0];
                $field = $table_field_split[1];

                $has_language = !in_array($table, array('order')) && array_key_exists($table, $this->database_schema) && array_key_exists('language_id', $this->database_schema[$table]) && $field != 'language_id';

                $final_sql = 'SELECT results.* FROM ('.$sql.') as results ';

                $extra_lang_condition = '';
                if ($has_language){
                    $language_id = $profile['profile']['export_sort_order']['language_id'];
                    $extra_lang_condition = ' AND innerJoinTable.language_id = '.$this->escape_database_value($language_id);
                }

                $final_sql .= ' INNER JOIN '.$this->escape_database_table_name($table).' innerJoinTable ON(innerJoinTable.'.$this->main_field.' = results.'.$this->main_field.$extra_lang_condition.') ORDER BY innerJoinTable.'.$field.' '.$sort_mode;
            } else
                $final_sql = $sql;

            if(is_file($this->assets_path.'model_ie_pro_export_function_get_elements_id_end.php'))
                require_once($this->assets_path.'model_ie_pro_export_function_get_elements_id_end.php');

            $result = $this->db->query($final_sql);

            $final_result = array();
            if(!empty($result->rows)) {
                foreach ($result->rows as $key => $val) {
                    $final_result[] = $val[$main_field];
                }
            }

            if($this->is_t && count($final_result) > $this->is_t_elem)
                $this->exception(sprintf($this->language->get('trial_operation_restricted_export'), $this->is_t_elem, count($final_result)));

            return $final_result;
        }

        public function translate_condition($condition) {
            if(is_numeric($condition))
                return '=';

            switch ($condition) {
                case 'not_like':
                    return 'NOT LIKE';
                    break;
                case 'years_ago': case 'months_ago': case 'days_ago': case 'hours_ago': case 'minutes_ago':
                    return '>=';
                    break;
                default:
                    return $condition;
                    break;
            }
        }

        public function translate_condition_value($condition, $value) {
            if(in_array($condition, array('years_ago', 'months_ago', 'days_ago', 'hours_ago', 'minutes_ago'))) {
                $php_name = '';
                if($condition == 'years_ago') $php_name = 'years';
                elseif($condition == 'months_ago') $php_name = 'months';
                elseif($condition == 'days_ago') $php_name = 'days';
                elseif($condition == 'hours_ago') $php_name = 'hours';
                elseif($condition == 'minutes_ago') $php_name = 'minutes';

                return date('Y-m-d H:i:s', strtotime('-'.(int)$value.' '.$php_name));
            }

            if(is_numeric($condition))
                return $condition;

            return $value;
        }

        public function get_elements($table_fields, $ids) {

            $table_fields_formatted = array();

            foreach ($this->columns as $col_name_real => $col_info) {
                $custom_name = array_key_exists('custom_name', $col_info) ? $col_info['custom_name'] : '';
                if(!empty($custom_name))
                    $table_fields_formatted[$custom_name] = $col_info;
            }

            $final_data = array();

            $element_to_process = count($ids);
            $element_processed = 0;
            $this->update_process(sprintf($this->language->get('progress_export_processing_elements_processed'), $element_processed, $element_to_process));

            foreach ($ids as $key => $id) {
                $final_data[$id] = array();
                foreach ($table_fields as $table_name => $fields_info) {

                    //<editor-fold desc="Related tables">
                        if($this->related_tables != '' && is_array($this->related_tables) && array_key_exists($table_name, $this->related_tables)) {
                            $table_formatted = $this->escape_database_field($this->db_prefix . $table_name);
                            $main_field_formatted = $this->escape_database_field($this->related_tables[$table_name]);
                            $fields_formatted = $this->extract_related_table_fields($table_name);

                            $fields_sql = array();
                            foreach ($fields_formatted as $fii) {
                                $fields_sql[] = $this->escape_database_field($fii['field']) . ' AS "' . $fii['custom_name'] . '"';
                            }
                            $fields_formatted = implode(",", $fields_sql);
                            $sql = 'SELECT ' . $fields_formatted . ' FROM ' . $table_formatted . ' WHERE ' . $main_field_formatted . ' = ' . $this->escape_database_value($id);
                            $result = $this->db->query($sql);

                            if(!empty($result->rows)) {
                                foreach ($result->rows as $key => $row) {
                                    if($key == 0)
                                        $final_data[$id] = array_merge($final_data[$id], $row);
                                    else
                                        $final_data[$id.'-'.($key+1)] = array_merge($final_data[$id], $row);
                                }
                            }
                            continue;
                        }
                    //</editor-fold>

                    $explode_table = explode("-", $table_name);
                    $table_name = $explode_table[0];

                    $conversion_global_vars = array();
                    //Normal process to get fields from database (include multilanguages)
                    if(!in_array($table_name, $this->special_tables)) {
                        $fields = array();
                        $conditions = array();

                        $conditions_query = '';
                        foreach ($fields_info as $key => $fii) {

                            if(!empty($fii['virtual_field']))
                                continue;

                            $fields[] = $this->escape_database_field($fii['field']) . ' AS "' . $fii['custom_name'] . '"';
                            if (array_key_exists('conditions', $fii) && !empty($fii['conditions'])) {
                                foreach ($fii['conditions'] as $cond) {
                                    $conditions[] = $cond;
                                }
                            }
                            $conversion_global_vars = $this->_extract_conversion_values($fii, $conversion_global_vars);
                        }

                        if (!empty($conditions)) {
                            $conditions = array_unique($conditions);
                            $conditions_query = ' AND ' . implode(' AND ', $conditions);
                        }

                        $fields_formatted = implode(",", $fields);
                        $table_formatted = $this->escape_database_field($this->db_prefix . $table_name);
                        $main_field_formatted = $this->escape_database_field($this->main_field);
                        $sql = 'SELECT ' . $fields_formatted . ' FROM ' . $table_formatted . ' WHERE ' . $main_field_formatted . ' = ' . $this->escape_database_value($id) . $conditions_query;

                        $result = $this->db->query($sql);
                        if(!empty($result->row)) {
                            $values_converted = $this->_apply_conversions_to_row($result->row, $conversion_global_vars);
                            foreach ($values_converted as $colname => $final_val)
                                $final_data[$id][$colname] = $final_val;
                        }
                    } else {
                        if(!in_array($table_name, array('product_option_value', 'empty_columns', 'custom_fixed_columns', 'product_options_combinations'))) {
                            $final_data[$id] = $this->{$this->model_loaded}->{'_exporting_process_' . $table_name}($final_data[$id], $id, $fields_info);
                        } else {
                            if($table_name == 'product_option_value') {
                                $options_rows = $this->{$this->model_loaded}->{'_exporting_process_' . $table_name}($id, $fields_info);
                                if (!empty($options_rows)) {
                                    foreach ($options_rows as $key => $option_row) {
                                        $final_data[$id . '_option_' . ($key + 1)] = $option_row;
                                    }
                                }
                            }
                            elseif($table_name == 'product_options_combinations'){
                                $options_cmb_rows = $this->{$this->model_loaded}->{'_exporting_process_' . $table_name}($id, $fields_info);
                                if (!empty($options_cmb_rows)) {
                                    foreach ($options_cmb_rows as $key => $option_cmb_row) {
                                        $final_data[$id . '_opt_cmb_' . ($key + 1)] = $option_cmb_row;
                                    }
                                }
                            }
                            elseif($table_name == 'empty_columns') {
                                $final_data[$id] = $this->_exporting_empty_columns($final_data[$id], $id, $fields_info);
                            }
                        }
                    }
                }

                $element_processed++;
                $this->update_process(sprintf($this->language->get('progress_export_processing_elements_processed'), $element_processed, $element_to_process), true);
            }

            // Dos condiciones:
            // 1- Esta activo el ordenamiento de attributes
            // 2- Hay al menos una columna de attributes con status activo
            if ($this->should_sort_attributes() &&
                isset( $table_fields['product_attribute'])) {
                $final_data = $this->sort_attributes( $final_data, $table_fields['product_attribute']);
            }

            return $final_data;
        }

        function extract_related_table_fields($table_name) {
            $fields = array();
            foreach ($this->custom_columns as $key => $col) {
                if($table_name == $col['table'])
                    $fields[$key] = $col;

            }
            return $fields;
        }

        function _apply_conversions_to_row($row, $conversion_global_vars) {
            $temp = array();
            foreach ($row as $col_name => $final_val) {
                if(!empty($conversion_global_vars) && array_key_exists($col_name, $conversion_global_vars)) {
                    $var_name = $conversion_global_vars[$col_name]['var_name'];
                    if(array_key_exists($final_val, $this->{$var_name})) {
                        $index = array_key_exists('index', $conversion_global_vars[$col_name]) ? $conversion_global_vars[$col_name]['index'] : '';
                        $multilanguage = $conversion_global_vars[$col_name]['multilanguage'];
                        if(empty($index) && !empty($multilanguage)) {
                            $final_val = $this->{$var_name}[$final_val][$this->default_language_id];
                        }else if(!empty($index)) {
                            $final_val = $this->{$var_name}[$final_val][$index];
                        }
                        else
                            $final_val = $this->{$var_name}[$final_val];
                    } else {
                        $final_val = '';
                    }
                }

                $row[$col_name] = !is_array($final_val) ? htmlspecialchars_decode(trim($final_val)) : $final_val;
            }
            return $row;
        }
        function _extract_conversion_values($field_info, $conversion_global_vars) {
            if(array_key_exists('id_instead_of_name', $field_info) && !empty($field_info['id_instead_of_name']))
                return $conversion_global_vars;

            if (array_key_exists('conversion_global_var', $field_info) && !empty($field_info['conversion_global_var']) && array_key_exists('name_instead_id', $field_info) && !empty($field_info['name_instead_id'])) {
                $conversion_global_vars[$field_info['custom_name']] = array();
                $conversion_global_vars[$field_info['custom_name']]['var_name'] = $field_info['conversion_global_var'];
                $conversion_global_vars[$field_info['custom_name']]['index'] = array_key_exists('conversion_global_index', $field_info) ? $field_info['conversion_global_index'] : '';
                $conversion_global_vars[$field_info['custom_name']]['multilanguage'] = array_key_exists('multilanguage', $field_info) && $field_info['multilanguage'] || $field_info['name'] = 'Manufacturer';
            }
            return $conversion_global_vars;
        }
        public function _exporting_empty_columns($current_data, $product_id, $columns) {
            foreach ($columns as $key => $col_info) {
                 $current_data[$col_info['custom_name']] = 0;
            }

            return $current_data;
        }

        public function format_filters_by_table($filters) {
            $final_filters = array();

            foreach ($filters as $key => $fil) {
                $field_split = explode('-', $fil['field']);
                $table = $field_split[0];
                $field = $field_split[1];
                $type = array_key_exists(3, $field_split) ? $field_split[3] : $field_split[2];

                //Devman Extensions - info@devmanextensions.com - 23/12/2019 14:43 - quick fix - sucede cuando tenemos un pre-filter de shop con cat tree 1 como ID
                if($table == 'product_to_category' && $field == 'name' && strpos($fil['field'], 'allow_ids') !== false)
                    $field = 'category_id';

                if(!array_key_exists($table, $final_filters))
                    $final_filters[$table] = array();
                if(!array_key_exists($field, $final_filters[$table]))
                    $final_filters[$table][$field] = array();

                $condition = $fil['conditional'][$type];

                $final_filters[$table][$field][] = array(
                    'value' => $this->db->escape($fil['value']),
                    'condition' => html_entity_decode($condition)
                );
            }

            return $final_filters;
        }

        public function format_filters_by_table_v2( $filters, $main_table) {
            $filters = explode( ',', $filters);
            $count = count( $filters);
            $i = 0;

            while ($i < $count)
            {
                $token = $filters[$i];

                if (!in_array( $token, ['AND', 'OR', '(', ')']))
                {
                    if ($i + 1 < $count && $this->is_comparator( $filters[$i + 1]))
                    {
                        // Es un field
                        $field_split = explode( '-', $token);

                        $table = $field_split[0];
                        $table = $table === $main_table ? 'main_table' : $table;

                        $field = $field_split[1];
                        $type = $field_split[2];

                        $filters[$i] = (object)[
                            'type' => 'field',
                            'value' => ($table !== 'main_table' ? "ij_{$table}.{$field}" : "{$table}.{$field}"),
                            'table' => $table,
                            'field' => $field,
                            'valueType' => $type
                        ];
                    }
                    else if ( $this->is_comparator( $token))
                    {
                        // Un comparador
                        $filters[$i] = (object)[
                            'type' => 'comparator',
                            'value' => strtoupper( html_entity_decode( $token))
                        ];
                    }
                    else if ($i > 0 && $this->is_comparator( $filters[$i - 1]))
                    {
                        // Un value
                        $value = $filters[$i];
                        $value = $this->strip_quotes( $value);

                        if ($type === 'string')
                        {
                            $value = $this->db->escape( $filters[$i]);
                        }

                        $filters[$i] = (object)[
                            'type' => 'value',
                            'value' => $value
                        ];
                    }
                }
                else if (in_array( $token, ['(', ')']))
                {
                   $filters[$i] = (object)[
                       'type' => 'group',
                       'value' => $token
                   ];
                }
                else // AND/OR
                {
                   $filters[$i] = (object)[
                       'type' => 'join',
                       'value' => $token
                   ];
                }


                $i++;
            }

            return $filters;
        }

        private function is_comparator( $value){
            if (is_string( $value))
            {
                $value = strtoupper( html_entity_decode( $value));
                $result = in_array( $value, ['>', '<', '>=', '<=', '=', '!=',
                                             'LIKE', 'NOT_LIKE',
                                             'YEARS_AGO', 'MONTHS_AGO', 'DAYS_AGO', 'HOURS_AGO', 'MINUTES_AGO']);
            }
            else
            {
                $result = $value->type === 'comparator';
            }

            return $result;
        }

        public function get_col_map_tables_fields($columns) {
            $final_fields = array();

            foreach ($columns as $key => $val) {
                $table = $val['table'];
                if($this->special_tables && !in_array($table, $this->special_tables)) {
                    if (array_key_exists('language_id', $val) && !empty($val['language_id']))
                        $table .= '-language_id_'.$val['language_id'];
                    if (array_key_exists('store_id', $val) && !empty($val['store_id']))
                        $table .= '-store_id_'.$val['store_id'];
                }

                if(!array_key_exists($table, $final_fields))
                    $final_fields[$table] = array();

                if(!array_key_exists('skip_export_conditions', $val)) {
                    if (array_key_exists('conditions', $val) && is_array($val['conditions']) && $this->array_depth($val['conditions']) == 2) {
                        $final_conditions = array();
                        foreach ($val['conditions'] as $key2 => $cond) {
                            $final_conditions[] = $this->escape_database_table_name($val['table']) . '.' . $this->escape_database_field($cond['field']) . ' ' . $cond['condition'] . ' ' . ($cond['condition'] == 'LIKE' ? '"%' . $cond['value'] . '%"' : $this->escape_database_value($cond['value']));
                        }
                        $val['conditions'] = $final_conditions;
                    }
                } else $val['conditions'] = array();

                $final_fields[$table][] = $val;
            }
            return $final_fields;
        }

        public function insert_default_values($columns, $elements) {
            foreach ($elements as $el_id => $el) {
                foreach ($this->columns as $key => $col_info) {
                    $default_value = $col_info['default_value'];
                    $custom_name = $col_info['custom_name'];

                    $result = preg_match_all("/\[([^\]]*)\]/", $default_value, $matches);

                    if ($result >= 1)
                    {
                      foreach ($matches[1] as $fieldName)
                      {
                        $fieldName = trim( $fieldName);

                        if (!empty( $fieldName) && isset( $el[$fieldName]))
                        {
                          $default_value = str_replace( "[{$fieldName}]", $this->conversion_value($fieldName, $el[$fieldName]), $default_value);
                        }
                      }
                    }

                    if(!empty($default_value) && array_key_exists($custom_name, $el) && empty($el[$custom_name]))
                        $elements[$el_id][$custom_name] = $default_value;
                }
            }

            return $elements;
        }

        public function conversion_values($elements) {
            foreach ($elements as $key => $fields) {
                foreach ($fields as $col_name => $value) {
                    $elements[$key][$col_name] = $this->conversion_value($col_name, $value);
                }
            }
            return $elements;
        }

        public function conversion_value($col_name, $value) {
            $col_info = array_key_exists($col_name, $this->custom_columns) ? $this->custom_columns[$col_name]: false;
            $final_value = $value;
            if($col_info) {
                if(array_key_exists('html_tags', $col_info) && !empty( trim( $col_info['html_tags']))) {
                    if($col_info['html_tags'] == 'all')
                        $final_value = strip_tags(str_replace('&nbsp', ' ', html_entity_decode($final_value)));
                    else
                        $final_value = strip_tags(html_entity_decode($final_value), "<" . str_ireplace(',', '><', trim($col_info['html_tags'])) . ">");
                }
                if(array_key_exists('max_length', $col_info) && !empty( trim( $col_info['max_length'])))
                    $final_value = mb_substr( $final_value, 0, $col_info['max_length']);

                if(array_key_exists('image_full_link', $col_info) && $col_info['image_full_link'] && !empty($final_value))
                    $final_value = HTTPS_CATALOG.'image/'.$final_value;

                if(array_key_exists('true_value', $col_info))
                    $final_value = $final_value ? $col_info['true_value'] : $col_info['false_value'];

                if(array_key_exists('product_id_identificator', $col_info) && !in_array($col_info['table'], array('product_related')) && !in_array($col_info['field'], array('related'))) {
                    $field = $col_info['product_id_identificator'];
                    $identificator = $this->model_extension_module_ie_pro_products->get_product_field($final_value,$field);
                    $identificator = !$identificator ? '' : $identificator;
                    $final_value = $identificator;
                }

                if(array_key_exists('conversion_product_link', $col_info) && !empty($final_value)) {
                    $language_id = array_key_exists('language_id', $col_info) ? $col_info['language_id'] : $this->default_language_id;
                    $store_id = array_key_exists('store_id', $col_info) ? $col_info['store_id'] : 0;
                    $final_value = $this->{$this->model_loaded}->get_product_url($final_value, $store_id, $language_id);
                }

                if(array_key_exists('profit_margin', $col_info) && $col_info['profit_margin']) {
                    $newValue = $this->add_profit_margin( $final_value, $col_info['profit_margin']);
                    $final_value = $newValue . '';
                }

                if(array_key_exists('round', $col_info) && $col_info['round']) {
                    $final_value = round($final_value, 2) . '';
                }
            }
            return $final_value;
        }

        public function insert_modifications_values($columns, $elements) {
            $is_products = $this->profile['import_xls_i_want'] == 'products';
            $tax_rest = array_key_exists('import_xls_rest_tax', $this->profile) && $this->profile['import_xls_rest_tax'];
            $tax_sum = array_key_exists('import_xls_sum_tax', $this->profile) && $this->profile['import_xls_sum_tax'];

            $some_modification = $is_products && ($tax_rest || $tax_sum);

            if($some_modification) {
                foreach ($elements as $el_id => $el) {
                    foreach ($this->columns as $column_name => $col_info) {
                        $custom_name = $col_info['custom_name'];
                        $is_field_price = array_key_exists('field', $col_info) && $col_info['field'] == 'price';

                        $is_opc_field_price = $this->hasOptionsCombinations &&
                            $col_info['table'] == 'product_options_combinations' &&
                            array_key_exists('field', $col_info) &&
                            $col_info['field'] == 'prices' &&
                            in_array($col_info['inner_field'], ['option_price', 'option_special', 'option_discount'])  &&
                            $col_info['key'] == 'price';

                        if($is_field_price && array_key_exists($custom_name, $el) && !empty($el[$custom_name]) && is_numeric($el[$custom_name]))
                            $elements[$el_id][$custom_name] = $this->price_tax_calculate($el_id, $el[$custom_name], $tax_sum ? 'sum' : 'rest');
                        else if ($is_opc_field_price && !empty($el[$custom_name])){
                            // Custom tax class computation
                            $price = (float)$el[$custom_name];
                            $price_prefix = '+';
                            if ($col_info['inner_field'] == 'option_price' && isset($this->columns['Opt. Comb. Price Prefix'])){
                                $price_prefix_column_custom_name = $this->columns['Opt. Comb. Price Prefix']['custom_name'];
                                $price_prefix = $el[$price_prefix_column_custom_name];
                            }
                            $tax_class_id = $this->model_extension_module_ie_pro_products->get_product_field($el_id, 'tax_class_id');
                            if (is_numeric($tax_class_id)) {
                                $price = $this->price_combination_tax_calculate($tax_class_id, $price, $tax_sum ? 'sum' : 'rest', $col_info['inner_field'], $price_prefix);
                            }
                            $elements[$el_id][$custom_name] = $price;
                        }
                    }
                }
            }
            return $elements;
        }

        public function insert_elemements_into_file($elements) {
            $format = $this->profile['import_xls_file_format'];
            $model_path = 'extension/module/ie_pro_file_'.$format;
            $model_name = 'model_extension_module_ie_pro_file_'.$format;
            $this->load->model('extension/module/ie_pro_file');
            $this->load->model($model_path);
            $this->{$model_name}->create_file();
            if($this->skip_columns ==  '')
                $this->{$model_name}->insert_columns($this->columns);
            $this->{$model_name}->insert_data($this->columns, $elements);
            $this->{$model_name}->download_file_export();

            $this->ajax_die('Export process finished', false);
        }

        public function get_custom_fixed_columns($profile) {
            return array_key_exists('export_custom_columns_fixed', $profile) && !empty($profile['export_custom_columns_fixed']) ? $profile['export_custom_columns_fixed'] : '';
        }

        public function insert_custom_fixed_columns($elements, $columns = false) {
            if(!$columns) {
                $operations = array('*','/','+','-','%');
                $operations_match = "/(\\".implode('|\\', $operations).")/s";

                //Devman Extensions - info@devmanextensions.com - 30/07/2019 09:59 - Check if data to export has options
                $has_options = false;
                if($this->elements_to_export == 'products') {
                    $data_file = array('columns' => array_keys($this->custom_columns), 'data' => $elements);
                    $has_options = $this->model_extension_module_ie_pro_products->check_file_data_has_options($data_file, true);
                    if ($has_options) $option_columns = $this->model_extension_module_ie_pro_products->check_file_option_column_keys($data_file, true);
                }

                foreach ($elements as $key => $element) {
                    $is_not_option_row = !$has_options || ($has_options && !$this->model_extension_module_ie_pro_products->check_is_option_row($element, $option_columns));
                    if($is_not_option_row) {
                        foreach ($this->custom_fixed_columns as $col_info) {
                            $sort_order = array_key_exists('sort_order', $col_info) && is_numeric($col_info['sort_order']) ? $col_info['sort_order'] : 0;
                            $final_value = $complete_name = $col_info['value'];

                            $is_operation = count(preg_split($operations_match, $complete_name)) > 1;
                            preg_match_all("/\[([^\]]*)\]/", $col_info['value'], $matches);
                            $get_value_from_column = !empty($matches[1]);

                            if ($get_value_from_column) {
                                if ($this->is_string_expression( $final_value)) {
                                    $final_value = mb_substr( $final_value, 1, strlen( $final_value) - 2);

                                    $final_value = preg_replace_callback( "/\[([^\]]*)\]/",
                                        function( $matches) use ($element, $key, $elements) {
                                            $result = '';
                                            $column_name = $matches[1];

                                            if (!isset( $element[$column_name]) &&
                                                isset( $this->columns[$column_name])) {
                                                if ($this->columns[$column_name]['field'] === 'custom_fixed_column') {
                                                    $result = $this->get_custom_fixed_column_value( $column_name);
                                                } else {
                                                    $column_name = $this->columns[$column_name]['custom_name'];

                                                    if (isset( $element[$column_name])) {
                                                        $result = $element[$column_name];
                                                    }
                                                }
                                            } else if (isset( $element[$column_name])) {
                                                $result = $element[$column_name];
                                            } else {
                                                $value = $this->find_option_value( $column_name, $key, $elements);

                                                if ($value !== null) {
                                                    $result = $value;
                                                }
                                            }

                                            return $result;
                                        }, $final_value);
                                } else {

                                    //Comento esta línea porque por ejemplo para una columna con espacios [Option price]*2 da problemas.
                                    //$eval_final = $is_operation ? str_replace(' ', '', $complete_name) : $complete_name;
                                    $eval_final = $complete_name;

                                    foreach ($matches[1] as $key_match => $column_name) {
                                        $column_name_with_squared = $matches[0][$key_match];
                                        $final_value = array_key_exists($column_name, $element) ? $element[$column_name] : '';
                                        $eval_final = str_replace($column_name_with_squared, $final_value, $eval_final);
                                    }

                                    if ($is_operation) {
                                        $match_values = preg_split($operations_match, $eval_final);
                                        foreach ($match_values as $val)
                                            if (!is_numeric($val))
                                                $this->exception(sprintf($this->language->get('progress_export_error_fixed_columns_match_operation'), $eval_final, json_encode($element)));

                                        $operation_result = eval('return ' . $eval_final . ';');
                                        $final_value = number_format($operation_result, 4, '.', '');
                                    } else {
                                        $final_value = $eval_final;
                                    }
                                }
                            }

                            if ($sort_order === '0')
                                $elements[$key] = array($col_info['name'] => $final_value) + $elements[$key];
                            else
                                $elements[$key] = array_slice($elements[$key], 0, $sort_order, true) +
                                    array($col_info['name'] => $final_value) +
                                    array_slice($elements[$key], $sort_order, count($elements[$key]) - 1, true);
                        }
                    }
                }
            } else {
                foreach ($this->custom_fixed_columns as $col_info) {
                    $col_fixed_info = array(
                        'custom_name' => $col_info['name'],
                        'status' => true,
                        'table' => 'custom_fixed_columns',
                        'field' => 'custom_fixed_column',
                        'default_value' => '',
                    );

                    $sort_order = array_key_exists('sort_order', $col_info) && is_numeric($col_info['sort_order']) ? $col_info['sort_order'] : 0;
                    if($sort_order === '0') {
                        $elements = array($col_info['name'] => $col_fixed_info) + $elements;
                    }
                    else
                        $elements = array_slice($elements, 0, $sort_order, true) +
                            array($col_info['name'] => $col_fixed_info) +
                            array_slice($elements, $sort_order, count($elements) - 1, true);
                }
            }
            return $elements;
        }

        private function process_filters_old( $profile, $main_table, $main_field)
        {
            $filters = array_key_exists('export_filter', $profile['profile']) && array_key_exists('filters', $profile['profile']['export_filter']) && !empty($profile['profile']['export_filter']['filters']) ? $profile['profile']['export_filter']['filters'] : array();
            $filters_config = array_key_exists('export_filter', $profile['profile']) && array_key_exists('config', $profile['profile']['export_filter']) && !empty($profile['profile']['export_filter']['config']) ? $profile['profile']['export_filter']['config'] : array();

            $joins = '';
            $where = '';

            if(!empty($filters)) {
                $main_conditional = (array_key_exists('main_conditional', $filters_config) ? $filters_config['main_conditional'] : 'OR') . ' ';

                if ($profile['real_type'] == 'export') {
                    $filters_by_table = $this->format_filters_by_table($filters);

                    foreach ($filters_by_table as $table_name => $field) {
                        if ($table_name == $main_table) {
                            if (empty($where))
                                $where .= ' WHERE ';

                            foreach ($field as $field_name => $values) {
                                foreach ($values as $key2 => $val) {
                                    $condition = $this->translate_condition($val['condition']);
                                    $value = $this->translate_condition_value($val['condition'], $val['value']);
                                    $like = in_array($val['condition'], array('like', 'not_like'));
                                    $where .= 'main_table.' . $field_name . " " . $condition . " '" . ($like ? '%' : '') . $value . ($like ? '%' : '') . "' " . $main_conditional;
                                }
                            }
                            $where = rtrim($where, $main_conditional);
                        } else {
                            $table_formatted = $this->escape_database_field($this->db_prefix . $table_name);
                            $table_join = 'ij_' . $table_name;

                            $table_info = $this->database_schema[$table_name];

                            $id_condition = array_key_exists($main_field, $table_info) ? 'main_table.' . $main_field . ' = ' . $table_join . '.' . $main_field . ' AND ' : '';

                            $joins .= ' INNER JOIN ' . $table_formatted . ' ' . $table_join . ' ON (' . $id_condition . '(';
                            foreach ($field as $field_name => $values) {
                                foreach ($values as $key2 => $val) {
                                    $condition = $this->translate_condition($val['condition']);
                                    $value = $this->translate_condition_value($val['condition'], $val['value']);
                                    $like = in_array($val['condition'], array('like', 'not_like'));
                                    $joins .= $table_join . '.' . $field_name . " " . $condition . " '" . ($like ? '%' : '') . $value . ($like ? '%' : '') . "' " . $main_conditional;
                                }
                            }
                            $joins = rtrim($joins, $main_conditional);
                            $joins .= ')) ' . "\n";
                        }
                    }
                }
            }

            return (object)[
                'joins' => $joins,
                'where' => $where
            ];
        }

        private function process_filters_v2( $profile_type, $filters, $main_table, $main_field)
        {
            $joins = '';
            $where = '';

            if (!empty( $filters))
            {
                $main_conditional = 'AND';

                if ($profile_type === 'export') {
                    $filters = $this->format_filters_by_table_v2( $filters, $main_table);
                    $count = count( $filters);
                    $tables = [];
                    $i = 0;

                    while ($i < $count)
                    {
                        $filter = $filters[$i];

                        switch ($filter->type)
                        {
                            case 'field':
                                if ($filter->table !== 'main_table' && !in_array( $filter->table, $tables))
                                {
                                    $tables[] = $filter->table;
                                }

                                $valueType = $filter->valueType;
                                break;

                            case 'comparator':
                                $condition = strtolower( $filter->value);

                                $filter->value = strtoupper( $this->translate_condition( $condition));
                                break;

                            case 'value':
                                $filter->value = $this->translate_condition_value( $condition, $filter->value);

                                if ($valueType === 'string' &&
                                    in_array( $condition, ['like', 'not_like']))
                                {
                                    $filter->value = "%{$filter->value}%";
                                }

                                if (!in_array( $valueType, ['boolean', 'number']))
                                {
                                    $filter->value = "\"{$filter->value}\"";
                                }

                                break;
                        }

                        $where .= $filter->value . ' ';
                        $i++;
                    }

                    if (!empty( $tables))
                    {
                        foreach ($tables as $table)
                        {
                            // This is a patch for custom work: 2019-07-16 Custom Order Products
                            if ($main_table == 'order_product' && $table == 'order')
                                $main_field = 'order_id';

                            $table_join = "ij_{$table}";
                            $table_info = $this->database_schema[$table];

                            $field_found = false;
                            $count_fil = 0;
                            while (!$field_found) {
                                if(!empty($filters[$count_fil]->field)) {
                                    $id_field = $filters[$count_fil]->field;
                                    $field_found = true;
                                } else
                                    $count_fil++;
                            }

                            if(!$field_found)
                                continue;

                            $id_condition = isset( $table_info[$main_field])
                                            ? "main_table.{$main_field} = {$table_join}.{$main_field}"
                                            : "main_table.{$id_field} = {$table_join}.{$id_field}";

                            $tableRealName = $this->escape_database_field( "{$this->db_prefix}{$table}");
                            $joins .= " INNER JOIN {$tableRealName} {$table_join} ON {$id_condition}";
                        }
                    }
                }
            }

            $where = trim( $where);

            if (!empty($where))
            {
                if(in_array(strtoupper(substr($where, 0, 3)), array("AND","OR")))
                    $where = substr($where, 3);

                $where = " WHERE {$where}";
            }

            return (object)[
                'joins' => $joins,
                'where' => $where
            ];
        }

        public function order_xml_columns($elements) {
            $columns_key = array_keys($this->custom_columns);

            $final_elements = array();

            foreach ($elements as $key => $element) {
                $element_temp = array();
                foreach ($columns_key as $key2 => $column_name) {
                    $element_temp[$column_name] = array_key_exists($column_name, $element) ? $element[$column_name] : '';
                }
                $final_elements[] = $element_temp;
            }

            return $final_elements;
        }
        private function should_sort_attributes() {
            return isset( $this->profile['import_xls_sort_attributes']) &&
                   $this->profile['import_xls_sort_attributes'] === '1';
        }

        private function sort_attributes( $rows, $attributeColumns) {
            $result = [];

            $attribute_counter = $this->get_attribute_counter($rows, $attributeColumns);
            $attribute_group_columns = $this->get_attributes_columns($attributeColumns, "attribute_group");
            $attribute_columns = $this->get_attributes_columns($attributeColumns, "attribute");
            $attribute_value_columns = $this->get_attributes_columns($attributeColumns, "attribute_value");

            foreach ($rows as $key => $row) {
                $row_attributes = $this->get_attribute_counter_row($row, $attributeColumns);

                //Empty all attribute columns
                    $columns = array_merge( $this->get_attributes_columns($attributeColumns, "attribute_group"), $this->get_attributes_columns($attributeColumns, "attribute"), $this->get_attributes_columns($attributeColumns, "attribute_value"));
                    foreach ($columns as $cols) {
                        foreach ($cols as $lang_id => $col_name) {
                            if(!empty($row[$col_name]))
                                $rows[$key][$col_name] = '';
                        }
                    }
                //END Empty all attribute columns

                $attri_position = 0;

                foreach ($attribute_counter as $attr_index => $attr_group) {
                    $col_group_name = !empty($attribute_group_columns[$attri_position]) ? $attribute_group_columns[$attri_position] : '';
                    $col_attr_name = !empty($attribute_columns[$attri_position]) ? $attribute_columns[$attri_position] : '';
                    $col_val_name = !empty($attribute_value_columns[$attri_position]) ? $attribute_value_columns[$attri_position] : '';
                    if(!empty($row_attributes[$attr_index])) {
                        if(!empty($col_group_name)) {
                            foreach ($col_group_name as $lang_id => $col_name) {
                                $rows[$key][$col_name] = !empty($row[$row_attributes[$attr_index]['group_column'][$lang_id]]) ? $row[$row_attributes[$attr_index]['group_column'][$lang_id]] : '';
                            }
                        }
                        if(!empty($col_attr_name)) {
                            foreach ($col_attr_name as $lang_id => $col_name) {
                                $rows[$key][$col_name] = !empty($row[$row_attributes[$attr_index]['attribute_column'][$lang_id]]) ? $row[$row_attributes[$attr_index]['attribute_column'][$lang_id]] : '';
                            }
                        }
                        if(!empty($col_val_name)) {
                            foreach ($col_val_name as $lang_id => $col_name) {
                                $rows[$key][$col_name] = !empty($row[$row_attributes[$attr_index]['value_column'][$lang_id]]) ? $row[$row_attributes[$attr_index]['value_column'][$lang_id]] : '';
                            }
                        }
                    } else { //Dejamos el hueco
                        if(!empty($col_group_name)) {
                            foreach ($col_group_name as $lang_id => $col_name)
                                $rows[$key][$col_name] = '';
                        }
                        if(!empty($col_attr_name)) {
                            foreach ($col_attr_name as $lang_id => $col_name)
                                $rows[$key][$col_name] = '';
                        }
                        if(!empty($col_val_name)) {
                            foreach ($col_val_name as $lang_id => $col_name)
                                $rows[$key][$col_name] = '';
                        }
                    }

                    $attri_position++;
                }
            }

            return $rows;
        }


        private function get_attribute_counter($rows, $attributeColumns)
        {
            $attributes_counter = array();
            $language_id = $this->default_language_id;

           foreach ($rows as $key => $row) {
                $row_attributes = $this->get_attribute_counter_row($row, $attributeColumns);

                foreach ($row_attributes as $key_attr => $attr) {
                    if (!array_key_exists($key_attr, $attributes_counter))
                        $attributes_counter[$key_attr] = array(
                            'count' => 0
                        );

                    $attributes_counter[$key_attr]['count']++;
                }

                //Sort order by count
                    $attributes_counters_temp = array();
                    foreach ($attributes_counter as $key => $row) {
                        $attributes_counters_temp[$key] = $row['count'];
                    }
                    array_multisort($attributes_counters_temp, SORT_DESC, $attributes_counter);
            }

            return $attributes_counter;
        }

        private function get_attribute_counter_row($row, $attributeColumns) {
            $attributes_counter = array();
            $language_id = $this->default_language_id;

            $attribute_group_columns = $this->get_attributes_columns($attributeColumns, "attribute_group");
            $attribute_columns = $this->get_attributes_columns($attributeColumns, "attribute");
            $value_columns = $this->get_attributes_columns($attributeColumns, "attribute_value");

            $attribute_groups = array();
            foreach ($attribute_group_columns as $key => $col) {
                $attribute_groups[] = !empty($row[$col[$language_id]]) ? $row[$col[$language_id]] : '';
            }

            $attributes = array();

            foreach ($attribute_columns as $key => $col) {
                $attributes[] = !empty($row[$col[$language_id]]) ? $row[$col[$language_id]] : '';
            }

            $product_attributes = array();
            for ($i = 0; $i<count($attribute_columns); $i++) {
                $attri_group = !empty($attribute_groups[$i]) ? $attribute_groups[$i] : '';
                $attri = !empty($attributes[$i]) ? $attributes[$i] : '';
                if(empty($attri_group) && empty($attri)) continue;
                $product_attributes[$attri_group.'||'.$attri] = array(
                    'group_column' => !empty($attribute_group_columns[$i]) ? $attribute_group_columns[$i] : '',
                    'attribute_column' => !empty($attribute_columns[$i]) ? $attribute_columns[$i] : '',
                    'value_column' => !empty($value_columns[$i]) ? $value_columns[$i] : '',
                );
            }

            return $product_attributes;
        }

        private function get_attributes_columns($columns, $col_name) {
            $columns_return = array();

            foreach ($columns as $col) {
                if ($col['field'] == $col_name) {
                    $position = explode("_",$col['identificator'])[0];
                    if(!array_key_exists($position, $columns_return))
                        $columns_return[$position] = array();
                    $columns_return[$position][$col["language_id"]] = $col['custom_name'];
                }
            }

            return array_values($columns_return);
        }

        function order_attributes_by_counter($a,$b){
            return ($a["count"] <= $b["count"]) ? -1 : 1;
        }

        private function strip_quotes( $value){
            $result = $value;

            while (!empty( $result) && $result[0] === '"' && $result[strlen( $result) - 1] === '"')
            {
                $result = mb_substr( $result, 1, strlen( $result) - 2);
            }

            return $result;
        }

        private function is_string_expression( $expression) {
            return $expression[0] === $expression[strlen( $expression) - 1] &&
                   $expression[0] === "'";
        }

        private function get_custom_fixed_column_value( $name) {
            foreach ($this->custom_fixed_columns as $fixed_column) {
                if ($name === $fixed_column['name']) {
                    return $fixed_column['value'];
                }
            }

            return null;
        }

        private function find_option_value( $name, $id, $elements) {
            $result = null;
            $option_column = null;

            foreach ($this->columns as $column_name => $column_info) {
               if ($this->is_option_column( $column_name) && $name === $column_info['custom_name']) {
                   $option_column = $column_info;
                   break;
               }
            }

            if ($option_column !== null) {
                foreach ($elements as $key => $element) {
                    if ($this->starts_with( $key, "{$id}_option") &&
                        isset( $element[$name])) {
                        $result = $element[$name];
                        break;
                    }
                }
            }

            return $result;
        }

        private function is_option_column( $name) {
            return $this->starts_with( $name, 'Option ');
        }

        private function is_option_row_for_product( $name, $product_id) {
            $regex = "/{$product_id}_option_\\d+/";

            return preg_match( $regex, $name) === 1;
        }

        private function starts_with( $text, $suffix) {
            return strpos( $text, $suffix) === 0;
        }
    }
?>
