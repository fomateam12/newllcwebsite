<?php
    class ModelExtensionModuleIePro extends Model {
        public function format_columns_multilanguage_multistore($columns, $lang_fields_skyp = array()) {
            $final_columns = array();

            $languages = $this->languages;

            foreach ($columns as $col_name => $column_info) {
                $multilanguage = $this->count_languages_real > 1 && array_key_exists('multilanguage', $column_info) && $column_info['multilanguage'];
                $multistore = array_key_exists('multistore', $column_info) && $column_info['multistore'];
                $column_info['conditions'] = array();
                $hidden_fields = $column_info['hidden_fields'];
                $table = array_key_exists('table', $hidden_fields) ? $hidden_fields['table'] : '';
                $field = array_key_exists('field', $hidden_fields) ? $hidden_fields['field'] : '';

                if(!$multilanguage || in_array($col_name, $lang_fields_skyp)) {
                    if($multistore) {
                        foreach ($this->stores_import_format as $store) {
                            $final_name = $col_name . ' ' . $store['store_id'];
                            $new_column = $this->change_column_name($column_info, $final_name);
                            $new_column['hidden_fields']['store_id'] = $store['store_id'];

                            if(array_key_exists('identificator', $hidden_fields))
                                $new_column['hidden_fields']['identificator'] .= '_'.$store['store_id'];

                            if(array_key_exists('multilanguage', $new_column)) {
                                $new_column['hidden_fields']['conditions'][] = 'language_id = ' . $this->default_language_id;
                                $new_column['hidden_fields']['language_id'] = $this->default_language_id;
                                if(array_key_exists('identificator', $hidden_fields))
                                    $new_column['hidden_fields']['identificator'] .= '_'.$this->default_language_id;
                            }

                            $final_columns[$final_name] = $new_column;
                        }

                    }
                    else {
                        if(array_key_exists('multilanguage', $column_info)) {
                            $skip_conditions = !$this->is_ocstore && $table == 'manufacturer';
                            if(!$skip_conditions)
                                $column_info['hidden_fields']['conditions'][] = 'language_id = ' . $this->default_language_id;
                            $column_info['hidden_fields']['language_id'] = $this->default_language_id;
                            if(array_key_exists('identificator', $hidden_fields))
                                $column_info['hidden_fields']['identificator'] .= '_'.$this->default_language_id;
                            if(array_key_exists('multistore', $column_info) && $column_info['multistore'])
                                $new_column['hidden_fields']['store_id'] = 0;
                        }
                        $final_columns[$col_name] = $column_info;
                    }
                }
                else
                {
                    foreach ($languages as $key2 => $lang) {
                        if($multistore){
                            foreach ($this->stores_import_format as $store) {
                                $final_name = $col_name.' '.$store['store_id'].' '.$lang['code'];
                                $new_column = $this->change_column_name($column_info, $final_name);
                                $new_column['hidden_fields']['conditions'][] = 'store_id = '.$store['store_id'];
                                $new_column['hidden_fields']['conditions'][] = 'language_id = '.$lang['language_id'];
                                $new_column['hidden_fields']['language_id'] = $lang['language_id'];
                                $new_column['hidden_fields']['store_id'] = $store['store_id'];

                                if(array_key_exists('identificator', $hidden_fields))
                                    $new_column['hidden_fields']['identificator'] .= '_'.$new_column['store_id'].'_'.$lang['language_id'];

                                $final_columns[$final_name] = $new_column;
                            }
                        } else {
                            $final_name = $col_name.' '.$lang['code'];
                            $new_column = $this->change_column_name($column_info, $final_name);
                            $new_column['hidden_fields']['language_id'] = $lang['language_id'];
                            $new_column['hidden_fields']['conditions'][] = 'language_id = '.$lang['language_id'];
                            if(array_key_exists('identificator', $hidden_fields))
                                $new_column['hidden_fields']['identificator'] .= '_'.$lang['language_id'];
                            if(array_key_exists('multistore', $column_info) && $column_info['multistore'])
                                $new_column['hidden_fields']['store_id'] = 0;
                            $final_columns[$final_name] = $new_column;
                        }
                    }
                }
            }

            $columns = $final_columns;
            return $columns;
        }

        public function change_column_name($col_info, $new_name) {
            $col_info['hidden_fields']['name'] = $new_name;
            $col_info['custom_name'] = $new_name;
            return $col_info;
        }

        public function format_column_name($col_name) {
            $col_name_format = str_replace(' ', '_', $col_name);
            $col_name_format = str_replace('-', '_', $col_name_format);
            $col_name_format = str_replace('.', '', $col_name_format);
            $col_name_format = str_replace('*', '', $col_name_format);
            $col_name_format = str_replace('(', '', $col_name_format);
            $col_name_format = str_replace(')', '', $col_name_format);
            $col_name_format = str_replace('%', '', $col_name_format);
            $col_name_format = mb_strtolower($col_name_format);

            return $col_name_format;
        }

        public function get_legible_database_field_name($string) {
            $string = str_replace('_', ' ', $string);
            $string = ucfirst($string);
            return $string;
        }

        public function cyrillic_to_latin($string) {
            $cyr = array(
            'ж',  'ч',  'щ',   'ш',  'ю',  'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ъ', 'ь', 'я',
            'Ж',  'Ч',  'Щ',   'Ш',  'Ю',  'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ъ', 'Ь', 'Я');
            $lat = array(
            'zh', 'ch', 'sht', 'sh', 'yu', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'q',
            'Zh', 'Ch', 'Sht', 'Sh', 'Yu', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'c', 'Y', 'X', 'Q');

            return str_replace($cyr, $lat, $string);
        }

        function change_array_key( $array, $old_key, $new_key ) {
            if( ! array_key_exists( $old_key, $array ) )
                return $array;

            $keys = array_keys( $array );
            $keys[ array_search( $old_key, $keys ) ] = $new_key;

            return array_combine( $keys, $array );
        }

        public function get_remodal($modal_id, $title, $description, $options = array()) {
            $open_on_ready = array_key_exists('open_on_ready', $options) && $options['open_on_ready'];
            $button_close = !array_key_exists('button_close', $options) || (array_key_exists('button_close', $options) && $options['button_close']);
            $button_confirm_text = array_key_exists('button_confirm_text', $options) && !empty($options['button_confirm_text']) ? $options['button_confirm_text'] : $this->language->get('remodal_button_confirm_text');
            $button_cancel_text = array_key_exists('button_cancel_text', $options) && !empty($options['button_cancel_text']) ? $options['button_cancel_text'] : $this->language->get('remodal_button_cancel_text');
            $open_on_ready = array_key_exists('open_on_ready', $options) && $options['open_on_ready'];
            $button_confirm = !array_key_exists('button_confirm', $options) || (array_key_exists('button_confirm', $options) && $options['button_confirm']);
            $button_cancel = !array_key_exists('button_cancel', $options) || (array_key_exists('button_cancel', $options) && $options['button_cancel']);
            $remodal_options = array_key_exists('remodal_options', $options) && !empty($options['remodal_options']) ? $options['remodal_options'] : '';
            $subtitle = array_key_exists('subtitle', $options) && !empty($options['subtitle']) ? $options['subtitle'] : '';
            $link = array_key_exists('link', $options) && !empty($options['link']) ? $this->language->get($options['link']) : '';

            $remodal_html = '';
            if($link) {
                $remodal_html .= '<a href="javascript:{}" data-remodal-target="'.$modal_id.'">'.$link.'</a>';
            }
            $remodal_html .= '
                <div class="remodal '.$modal_id.'" data-remodal-id="'.$modal_id.'"'.($remodal_options ? ' data-remodal-options="'.$remodal_options.'"' : '').'>
                    '.($button_close ? '<button data-remodal-action="close" class="remodal-close"></button>' : '').'
                    <h1>'.$title.'</h1>
                    '.(!empty($subtitle) ? '<h2>'.$subtitle.'</h2>' : '').'
                    <div class="remodal_content">'.$description.'</div>
                    <br>
                    '.($button_cancel ? '<button data-remodal-action="cancel" class="remodal-cancel">'.$button_cancel_text.'</button>' : '').'
                    '.($button_confirm ? '<button data-remodal-action="confirm" class="remodal-confirm">'.$button_confirm_text.'</button>' : '').'
                </div>
            ';

            if($open_on_ready) {
                $remodal_options = !empty($remodal_options) ? '{'.$remodal_options.'}' : '';
                $remodal_html .= '<script type="text/javascript">var inst = $(\'[data-remodal-id='.$modal_id.']\').remodal('.$remodal_options.');inst.open();</script>';
            }

            return $remodal_html;
        }

        public function clean_array_extension_prefix($array) {
            $new_array = array();
            foreach ($array as $key => $val) {
                $new_key = str_replace($this->extension_group_config.'_', '', $key);
                $new_array[$new_key] = $val;
            }
            return $new_array;
        }

        public function get_stores_import_format() {
            $this->load->model('setting/store');
			$stores = array();
			$stores[0] = array(
				'store_id' => '0',
				'name' => $this->config->get('config_name')
			);

			$stores_temp = $this->model_setting_store->getStores();
			foreach ($stores_temp as $key => $value) {
				$stores[] = $value;
			}
			return $stores;
        }

        public function validate_permiss() {
            if (!$this->user->hasPermission('modify', $this->real_extension_type.'/'.$this->extension_name)) {
                if(!empty($this->request->post['no_exit']))
                {
                    $array_return = array(
                        'error' => true,
                        'message' => $this->language->get('error_permission')
                    );
                    echo json_encode($array_return); die;
                }
                else
                    throw new Exception($this->language->get('error_permission'));

                return false;
            }
            return true;
        }

        public function format_default_column($col_name, $column_info, $from_profile = false, $format_custom_name = false) {
            $column_info['hidden_fields'] = array_key_exists('hidden_fields', $column_info) ? $column_info['hidden_fields'] : array();
            $column_info['hidden_fields']['name'] = $col_name;
            if(!$from_profile) {
                $column_info['custom_name'] = $format_custom_name ? $this->format_column_name($col_name) : $col_name;
                $column_info['status'] = 1;
            } else {
                $col_custom_name = array_key_exists('custom_name', $column_info) && !empty($column_info['custom_name']) ? $column_info['custom_name'] : $col_name;
                $column_info['custom_name'] = $format_custom_name ? $this->format_column_name($col_custom_name) : $col_custom_name;
                $column_info['status'] = array_key_exists('status', $column_info) ? $column_info['status'] : 0;
            }
            return $column_info;
        }

        public function escape_database_field($name) {
            return "`".$name."`";
        }
        public function escape_database_value($value, $field_name = '') {
            if(!empty($field_name) && $field_name == 'custom_field')
                return "'".$this->db->escape($value)."'";

            $value = $this->db->escape(str_replace('"', '&quot;', $value));

            if(in_array($field_name, array('name'))) {
                $value = str_replace('<', '&lt;', $value);
                $value = str_replace('>', '&gt;', $value);
                $value = str_replace('&quot;', '"', $value);
                $value = str_replace('&amp;', '&', $value);
                $value = str_replace('&', '&amp;', $value);
            }

            return "'".$value."'";
        }
        public function escape_database_table_name($name) {
            return "`".$this->db_prefix.$name."`";
        }
        public function sanitize_value($value) {
            if(!is_array($value))
                return trim(htmlspecialchars_decode($value));
            else
                return '';
        }

        public function get_database_field_real_type($table_name, $field_name) {
            $type = '';
            if(array_key_exists($table_name, $this->database_schema) && array_key_exists($field_name, $this->database_schema[$table_name])) {
                $type = $this->database_schema[$table_name][$field_name]['real_type'];
            }
            return $type;
        }
        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            if($this->profile['profile_type'] == 'export') {
                array_push($special_tables, 'empty_columns');
                array_push($special_tables, 'custom_fixed_columns');
            }
            $this->special_tables = $special_tables;
            $this->delete_tables = $delete_tables;
            $this->special_tables_description = $special_tables_description;
        }

        public function get_columns($configuration) {
            $configuration = $this->clean_array_extension_prefix($configuration);
            $profile_id = array_key_exists('profile_id', $configuration) && !empty($configuration['profile_id']) ? $configuration['profile_id'] : '';
            //$multilanguage = array_key_exists('multilanguage', $configuration) ? $configuration['multilanguage'] : $this->count_languages > 1;
            $multilanguage = true;

            $columns = $this->get_columns_formatted($multilanguage);
            $columns = $this->format_columns_multilanguage_multistore($columns);

            if(!empty($profile_id)) {
                $col_map = $this->model_extension_module_ie_pro_tab_profiles->get_columns($configuration);
                foreach ($columns as $col_name => $col_info) {
                    if(!array_key_exists($col_name, $col_map)) {
                        $col_info['status'] = 0;
                        $col_map[$col_name] = $col_info;
                    }
                }
                $columns = $col_map;
            }

            $final_columns = array();

            foreach ($columns as $col_name => $col_info)
                $final_columns[$col_name] = $this->format_default_column($col_name, $col_info, !empty($profile_id), $configuration['file_format'] == 'xml' && empty($profile_id));

            return $final_columns;
        }

        public function remove_tables($database_schema, $tables) {
            $final_tables = array();
            $real_tables = array_keys($database_schema);

            foreach ($tables as $key => $table_name) {
                if(in_array($table_name, $real_tables))
                    $final_tables[] = $table_name;
            }

            return $final_tables;
        }

        public function check_columns($columns_from_file) {
            $some_column_found = false;
            foreach ($columns_from_file as $key => $col_name) {
                if(array_key_exists($col_name, $this->custom_columns)) {
                    if($this->custom_columns[$col_name]['field'] != 'delete') {
                        $some_column_found = true;
                        break;
                    }
                }
            }

            if(!$some_column_found) {
                $custom_columns = array();
                foreach ($this->custom_columns as $col_name => $col_info) {
                    $custom_columns[] = $col_name;
                }

                $html_custom_columns = '<ul><li>'.implode("</li><li>", $custom_columns).'</ul>';
                $html_columns = '<ul><li>'.implode("</li><li>", $columns_from_file).'</ul>';
                $this->exception(sprintf($this->language->get('progress_import_error_columns'), $html_columns, $html_custom_columns));
            }
        }

        public function get_custom_columns($columns) {
            $final_columns = array();
            foreach ($columns as $key => $col_info) {
                $final_columns[$col_info['custom_name']] = $col_info;
            }
            return $final_columns;
        }

        public function get_conversion_fields($columns) {
            $fields = array();

            foreach ($columns as $key => $col_info) {

                $index = $col_info['table'].'_'.$col_info['field'].(!empty($col_info['customer_group_id']) ? '_'.$col_info['customer_group_id'] : '');
                $fields[$index] = array();

                $name_instead_of_id = array_key_exists('name_instead_id', $col_info) && $col_info['name_instead_id'];
                $id_instead_of_name = array_key_exists('id_instead_of_name', $col_info) && !empty($col_info['id_instead_of_name']);
                if($name_instead_of_id || $id_instead_of_name) {
                    $temp = array(
                        'rule' => 'name_instead_id',
                        'conversion_global_var' => array_key_exists('conversion_global_var', $col_info) ? $col_info['conversion_global_var'] : '',
                        'conversion_global_index' => array_key_exists('conversion_global_index', $col_info) ? $col_info['conversion_global_index'] : '',
                        'id_instead_of_name' => array_key_exists('id_instead_of_name', $col_info) && $col_info['id_instead_of_name']
                    );
                    $fields[$index][] = $temp;
                }

                if(array_key_exists('true_value', $col_info)) {
                    $temp = array(
                        'rule' => 'boolean_field',
                        'true_value' => $col_info['true_value'],
                        'false_value' => $col_info['false_value'],
                    );
                    $fields[$index][] = $temp;
                }

                if(array_key_exists('product_id_identificator', $col_info) && $col_info['product_id_identificator'] && $col_info['product_id_identificator'] != 'product_id') {
                    $temp = array(
                        'rule' => 'product_id_identificator',
                        'product_id_identificator' => $col_info['product_id_identificator'],
                    );
                    $fields[$index][] = $temp;
                }

                if(array_key_exists('profit_margin', $col_info) && !empty($col_info['profit_margin'])) {
                    $temp = array(
                        'rule' => 'profit_margin',
                        'profit_margin' => $col_info['profit_margin'],
                    );
                    $fields[$index][] = $temp;
                }

                if(array_key_exists('round', $col_info)) {
                    $temp = array(
                        'rule' => 'round',
                        'round' => $col_info['round'],
                    );
                    $fields[$index][] = $temp;
                }

                if(array_key_exists('html_tags', $col_info) && !empty( trim($col_info['html_tags']))) {
                    $temp = array(
                        'rule' => 'strip_html_tags',
                        'html_tags' => $col_info['html_tags'],
                    );
                    $fields[$index][] = $temp;
                }

                if(array_key_exists('max_length', $col_info) && !empty( trim($col_info['max_length']))) {
                    $temp = array(
                        'rule' => 'allow_max_length',
                        'max_length' => $col_info['max_length'],
                    );
                    $fields[$index][] = $temp;
                }

                if(is_file($this->assets_path.'model_ie_pro_get_conversion_fields_add_news.php'))
                    require($this->assets_path.'model_ie_pro_get_conversion_fields_add_news.php');
            }

            return $fields;
        }

        public function conversion_has_rule($field_name, $rule_name){
            /*
            Checks if $field_name has a $rule_name rule in the $this->conversion_fields array
            */
            $has_rule = FALSE;
            if (array_key_exists($field_name, $this->conversion_fields)){
                foreach ($this->conversion_fields[$field_name] as $key => $rule) {
                    if ($rule['rule'] == $rule_name){
                        $has_rule = TRUE;
                        break;
                    }
                }
            }
            return $has_rule;
        }

        public function check_allow_ids($col_info) {
            return array_key_exists('id_instead_of_name', $col_info) && $col_info['id_instead_of_name'];
        }

        public function extract_id_allow_ids($value) {
            if (strpos($value, '-forceId') !== false) {
                $id = str_replace('-forceId', '', $value);

                if(!$this->its_an_option_value_list($id) && (!is_numeric($id) || $id == 0 || substr($id, 0, 1) == 0))
                    $this->exception(sprintf($this->language->get('progress_import_elements_no_numeric_id'), $id));

                return $id;
            }
            return false;
        }

        public function its_an_option_value_list($option_value){
            if (strpos($option_value, '[') === 0 && strpos($option_value, ']') === strlen($option_value) - 1)
                return true;
            return false;
        }

        public function get_splitted_values_fields($columns) {
            $fields = array();

            /*foreach ($columns as $key => $col_info) {
                if(array_key_exists('splitted_values', $col_info) && $col_info['splitted_values']) {
                    $fields[$col_info['custom_name']] = array(
                        'custom_name' => $col_info['custom_name'],
                        'custom_name_real' => explode('>', $col_info['custom_name'])[0],
                        'position' => explode('>', $col_info['custom_name'])[1],
                        'table' => $col_info['table'],
                        'field' => $col_info['field'],
                        'symbol' => $col_info['splitted_values'],
                    );
                }
            }*/

            foreach ($columns as $key => $col_info) {
                if(array_key_exists('splitted_values', $col_info) && $col_info['splitted_values']) {
                    $copy_column_name = $col_info['custom_name'];
                    $column_name_splitted = explode('>', $copy_column_name);
                    $position = array_pop($column_name_splitted);
                    $column_name_without_position = implode('>', $column_name_splitted);

                    $fields[$col_info['custom_name']] = array(
                        'custom_name' => $col_info['custom_name'],
                        'custom_name_real' => $this->profile['import_xls_file_format'] == 'xml' ? $col_info['custom_name'] : $column_name_without_position,
                        'position' => $position,
                        'table' => $col_info['table'],
                        'field' => $col_info['field'],
                        'symbol' => $col_info['splitted_values'],
                    );
                }
            }

            return empty($fields) ? '' : $fields;
        }

        public function add_profit_margin($price, $margin) {
            $price = $this->format_number_thousand_separator($price);
            $price = (float)$price;
            $margin = trim($margin);

            if(!is_numeric($margin) || !is_numeric($price))
                return $price;

            if($margin > 0)
                $multiplicator = ($margin / 100) + 1;
            else
                $multiplicator = (100-abs($margin)) / 100;

            $price *= $multiplicator;
            return $price;
        }

        public function format_number_thousand_separator($number) {
            if(empty($number))
                return 0;

            $number = preg_replace('/[^0-9,.]+/', '', $number);

            //For example 1.250,45 is really 1250.45
            if (strpos($number, ',') !== false && strpos($number, '.') !== false && strpos($number, '.') < strpos($number, ',')) {
                $number = str_replace('.', '', $number);
                $number = str_replace(',', '.', $number);
            }

            //For example 1,250.45 is really 1250.45
            if (strpos($number, ',') !== false && strpos($number, '.') !== false && strpos($number, '.') > strpos($number, ',')) {
                $number = str_replace(',', '', $number);
            }

            //For example 1,250 is really 1250
            else if (strpos($number, ',') !== false && !strpos($number, '.') && strlen(explode(",", $number)[1]) == 3)
                $number = str_replace(',', '', $number);
            //For example 1,25 is really 1.25 or 1,2506 is really 1.2506
            else if (strpos($number, ',') !== false && !strpos($number, '.'))
                $number = str_replace(',', '.', $number);

            if(!is_numeric($number))
                return 0;

            return $number;
        }
        public function format_column_names($columns) {
            foreach ($columns as $column_name => $column_info) {
                $col_name_formatted = $this->format_column_name($column_name);
                $columns[$column_name]['custom_name'] = $col_name_formatted;
            }
            return $columns;
        }

        public function from_camel_case($input) {
            preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
            $ret = $matches[0];
            foreach ($ret as &$match) {
                $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
            }
            return implode('_', $ret);
        }

        public function select_constructor($select_name, $values, $value, $extra = array()) {
            $onchange = array_key_exists('onchange', $extra) ? ' onchange="'.$extra['onchange'].'" ' : '';
            $class = array_key_exists('class', $extra) ? ' class="'.$extra['class'].'" ' : '';

            $select = '<select name="'.$select_name.'"'.$class.$onchange.'data-live-search="true">';
                foreach ($values as $option_value => $option_name) {
                    $select .= '<option '.($value == $option_value ? 'selected="selected"' : '').' value="'.$option_value.'">'.$option_name.'</option>';
                }
            $select .= '</select>';

            return $select;
        }

        public function check_element_exist($table, $conditions) {
            $query = "SELECT * FROM ".$this->escape_database_table_name($table).' WHERE '.$conditions.' LIMIT 1';
            $result = $this->db->query($query);

            return $result->num_rows ? $result->row : 0;
        }

        public function get_sql($fields, $table, $conditions, $update) {
            $sql = '';

            if($update) {
                if (isset( $fields['date_added']) && !strtotime($fields['date_added'])) {
                    $fields['date_added'] = date("Y-m-d H:i:s");
                }

                if (isset( $fields['date_modified']) && !strtotime($fields['date_modified'])) {
                    $fields['date_modified'] = date("Y-m-d H:i:s");
                }

                $sql .= "UPDATE " . $this->escape_database_table_name($table) . ' SET ';

                foreach ($fields as $field_name => $value) {
                    if($this->check_field_exists($this->database_schema, $table, $field_name)) {
                        $value = $this->format_value_by_type($table, $field_name, $value);
                        $sql .= $this->escape_database_field($field_name) . ' = ' . ( $table != 'product_options_combinations' ? $this->escape_database_value($value, $field_name) : "'$value'" ) . ', ';
                    }
                }
                $sql = rtrim($sql, ', ')." WHERE " . $conditions;
            }
            else {
                $sql .= "INSERT INTO " . $this->escape_database_table_name($table);
                $fields_temp = $values_temp = array();

                if($table == 'product' && !array_key_exists("date_available", $fields)) {
                    $fields['date_available'] = date("000-00-00");
                }
                foreach ($fields as $field_name => $value) {
                    if($this->check_field_exists($this->database_schema, $table, $field_name)) {
                        $value = $this->format_value_by_type($table, $field_name, $value);
                        $fields_temp[] = $this->escape_database_field($field_name);
                        $values_temp[] = $table != 'product_options_combinations' ? $this->escape_database_value($value, $field_name) : "'$value'";
                    }
                }

                $sql .= ' ('.implode(", ", $fields_temp).') VALUES ('.implode(", ", $values_temp).')';
            }


            if($update && empty($conditions))
                $this->exception(sprintf($this->language->get('progress_import_error_updating_conditions'), $sql));

            return $sql;
        }

        public function check_field_exists($database_schema, $table, $field) {
            return array_key_exists($table, $database_schema) && array_key_exists($field, $database_schema[$table]);
        }

        public function format_value_by_type($table, $field, $value) {
            $type = array_key_exists($table, $this->database_field_types) && array_key_exists($field, $this->database_field_types[$table]) && array_key_exists('type', $this->database_field_types[$table][$field]) ? $this->database_field_types[$table][$field]['type'] : '';

            if(!empty($type)) {
                if($type == 'boolean')
                    $value = $this->translate_boolean_value($value);
            }

            $real_type = $this->get_database_field_real_type($table, $field);

            if (in_array($real_type, array('float','decimal'))) {
                $value = $this->format_number_thousand_separator($value);
                $value = number_format($value, 4, '.', '');
            }

            return $value;
        }

        public function pre_import($data_file) {
            $id_assigned_count = 1;

            $this->update_process($this->language->get('progress_import_elements_process_start'));
            $element_to_process = count($data_file);
            $element_processed = 0;
            $this->update_process(sprintf($this->language->get('progress_import_elements_processed'), $element_processed, $element_to_process));

            foreach ($data_file as $row_file_num => $fields_tables) {
                $creating = $editting = false;
                $element_id = array_key_exists($this->main_table, $fields_tables) && array_key_exists($this->main_field, $fields_tables[$this->main_table]) && !empty($fields_tables[$this->main_table][$this->main_field]) ? $fields_tables[$this->main_table][$this->main_field] : '';

                if(empty($element_id) && array_key_exists($this->main_table, $fields_tables) && array_key_exists($this->main_table, $this->conditional_fields)) {
                    $element_id = $this->find_element_id_by_conditional_fields($fields_tables[$this->main_table], $this->conditional_fields[$this->main_table], $this->main_table, $this->main_field);
                    if(!empty($element_id) && array_key_exists($this->main_table, $fields_tables)) {
                        $fields_tables[$this->main_table][$this->main_field] = $element_id;
                    }
                }

                if(empty($element_id)) {
                    $creating = true;
                    $element_id = $this->assign_element_id($id_assigned_count);
                    $id_assigned_count++;
                } else {
                    $element_exist = $this->check_element_exist($this->main_table, $this->escape_database_field($this->main_field).' = '.$this->escape_database_value($element_id));
                    if($element_exist)
                        $editting = true;
                    else
                        $creating = true;
                }

                foreach ($fields_tables as $table_name => $data) {
                    if((!$this->special_tables || (!in_array($table_name, $this->special_tables))) && $table_name != 'empty_columns') {
                        $array_depth = $this->array_depth($data_file[$row_file_num][$table_name]);
                        if($array_depth == 1)
                            $data_file[$row_file_num][$table_name][$this->main_field] = $element_id;
                        else {
                            foreach ($data_file[$row_file_num][$table_name] as $key => $data2) {
                                $data_file[$row_file_num][$table_name][$key][$this->main_field] = $element_id;
                            }
                        }
                    }

                    if(in_array($table_name, $this->special_tables_description))
                        $data_file[$row_file_num][$table_name] = $this->add_language_id_table_description($data_file[$row_file_num][$table_name], $element_id);
                }

                if(!array_key_exists('empty_columns', $data_file[$row_file_num]))
                    $data_file[$row_file_num]['empty_columns'] = array();

                $data_file[$row_file_num]['empty_columns']['creating'] = $creating;
                $data_file[$row_file_num]['empty_columns']['editting'] = $editting;

                $element_processed++;
                $this->update_process(sprintf($this->language->get('progress_import_elements_processed'), $element_processed, $element_to_process), true);
            }

            return $data_file;
        }

        public function add_language_id_table_description($descriptions, $element_id) {
            $final_descriptions = array();
            if(!empty($descriptions) && is_array($descriptions)) {
                foreach ($descriptions as $language_id => $fields) {
                    $some_data = array_filter($fields);

                    if(!empty($some_data)) {
                        $fields['language_id'] = $language_id;
                        $fields[$this->main_field] = $element_id;
                        $final_descriptions[] = $fields;
                    }

                }
            }
            return $final_descriptions;
        }

        public function find_element_id_by_conditional_fields($data, $conditional_fields, $table, $main_field) {
            $condition = '';
            foreach ($conditional_fields as $key => $field) {
                $value = array_key_exists($field, $data) ? $data[$field] : '';
                if(!empty($value)) {
                    $condition .= $this->escape_database_field($field).' = '.$this->escape_database_value($value).' AND ';
                }
            }

            if(!empty($condition)) {
                $condition = rtrim($condition, ' AND ');
                $sql = "SELECT ".$this->escape_database_field($main_field)." FROM ".$this->escape_database_table_name($table)." WHERE ".$condition.' LIMIT 1';
                $result = $this->db->query($sql);
                return !empty($result->row) ? $result->row[$main_field] : false;
            }
            return false;
        }
        public function get_conditional_values($custom_columns) {
            $conditional_values = [];

            $parser = new ConditionalExpressionParser(
                $this->conditional_value_conditions,
                $this->columns,
                $this
            );

            foreach ($custom_columns as $custom_name => $custom_column) {
                if (isset( $custom_column['conditional_value']) &&
                    !empty($custom_column['conditional_value'])) {

                    $real_column_name = isset( $custom_column['temp_real_column_name'])
                                        ? $custom_column['temp_real_column_name']
                                        : null;

                    $cond_values = $parser->parse(
                        $custom_column['conditional_value'],
                        $custom_name,
                        $real_column_name
                    );

                    foreach ($cond_values as $name => $value)
                    {
                       $conditional_values[$name] = $value;
                    }
                }
            }

            return !empty($conditional_values) ? $conditional_values : '';
        }

        public function insert_conditional_values($elements) {
            if($this->profile['profile_type'] == 'import') {
                $columns = $elements['columns'];
                $elements = $elements['data'];
                foreach ($elements as $key => $element) {
                    $elements[$key] = array_combine($columns, $element);
                }
            }

            $evaluator = new ConditionalExpressionEvaluator( $this, $this->conditional_values, $this->model_extension_module_ie_pro_products);
            $evaluator->evaluateDataItems( $elements, $this->options_columns);

            if($this->profile['profile_type'] == 'import') {
                foreach ($elements as $key => $element) {
                    $elements[$key] = array_values($element);
                }
                $elements = array(
                    'columns' => $columns,
                    'data' => $elements
                );
            }

            return $elements;
        }

        public function put_type_to_columns_formatted($columns_formated, $multilanguage = false) {
            if(is_file($this->assets_path.'model_ie_pro_add_new_columns_to_native_model.php'))
                require($this->assets_path.'model_ie_pro_add_new_columns_to_native_model.php');

            if($this->has_custom_fields)
                $columns_formated = $this->model_extension_module_ie_pro_tab_custom_fields->add_custom_fields_to_columns($columns_formated, $this->cat_name, $multilanguage);

            foreach ($columns_formated as $col_name => $field_info) {
                if(array_key_exists('hidden_fields', $field_info)) {
                    $table = array_key_exists('table', $field_info['hidden_fields']) ? $field_info['hidden_fields']['table'] : '';
                    if($table != 'empty_columns' && array_key_exists($table, $this->database_schema)) {
                        $field = array_key_exists('field', $field_info['hidden_fields']) ? $field_info['hidden_fields']['field'] : '';
                        $columns_formated[$col_name]['hidden_fields']['real_type'] = array_key_exists($field, $this->database_schema[$table]) && array_key_exists('real_type', $this->database_schema[$table][$field]) ? $this->database_schema[$table][$field]['real_type'] : '';
                        $columns_formated[$col_name]['hidden_fields']['type'] = array_key_exists($field, $this->database_schema[$table]) && array_key_exists('type', $this->database_schema[$table][$field]) ? $this->database_schema[$table][$field]['type'] : '';
                    }
                }
            }

            foreach ($columns_formated as $group_name => $fields) {
                if(in_array($group_name, array('categories_tree', 'specials', 'discounts', 'attributes', 'filters', 'downloads'))) {
                    foreach ($fields as $col_name => $field_info) {
                        if (array_key_exists('hidden_fields', $field_info)) {
                            $table = array_key_exists('table', $field_info['hidden_fields']) ? $field_info['hidden_fields']['table'] : '';
                            if ($table != 'empty_columns' && array_key_exists($table, $this->database_schema)) {
                                $field = array_key_exists('field', $field_info['hidden_fields']) ? $field_info['hidden_fields']['field'] : '';
                                $real_type = array_key_exists($field, $this->database_schema[$table]) && array_key_exists('real_type', $this->database_schema[$table][$field]) ? $this->database_schema[$table][$field]['real_type'] : '';

                                if(empty($real_type)) {
                                    if(
                                        in_array($table, array('product_to_category','product_attribute','product_filter'))
                                        &&
                                        in_array($field, array('name','attribute_group', 'attribute'))
                                    )
                                        $real_type = 'int';
                                    elseif(
                                        in_array($table, array('product_attribute','product_to_download'))
                                        &&
                                        in_array($field, array('attribute_value','name','filename','hash', 'mask'))
                                    )
                                        $real_type = 'string';
                                }

                                $columns_formated[$group_name][$col_name]['hidden_fields']['real_type'] = $real_type;
                                $columns_formated[$group_name][$col_name]['hidden_fields']['type'] = array_key_exists($field, $this->database_schema[$table]) && array_key_exists('type', $this->database_schema[$table][$field]) ? $this->database_schema[$table][$field]['type'] : '';
                            }
                        }
                    }
                }
            }

            foreach ($columns_formated as $col_name => $field_info) {
                if(array_key_exists('hidden_fields', $field_info)) {
                    $table = array_key_exists('table', $field_info['hidden_fields']) ? $field_info['hidden_fields']['table'] : '';
                    $field = array_key_exists('field', $field_info['hidden_fields']) ? $field_info['hidden_fields']['field'] : '';

                    if (!empty($table)
                        && !empty($field)
                        && array_key_exists($table, $this->database_field_types)
                        && array_key_exists($field, $this->database_field_types[$table])
                    ) {
                        if (array_key_exists('type', $this->database_field_types[$table][$field]) && $field != 'image') {
                            $type = $this->database_field_types[$table][$field]['type'];

                            if($field == 'main_category') $type = 'text';

                            if ($type == 'boolean') {
                                $columns_formated[$col_name]['hidden_fields']['is_boolean'] = true;
                            }
                        } else if ($field == 'image') {
                            $columns_formated[$col_name]['hidden_fields']['is_image'] = true;
                        }
                    }
                }
            }

            return $columns_formated;
        }

        public function get_remote_image_data($table_name, $field_name, $element_id, $row_number, $image_url, $force_name = '') {
            $multiple_images = false;
            if($table_name == 'option_value')
                $table_name = 'option-value';
            elseif($table_name == 'product_image') {
                $table_name = 'product_image';
                $multiple_images = true;
            }elseif($table_name == 'product_option_value') {
                $table_name = 'product-option-value';
                $multiple_images = true;
            }

            $img_temp = preg_replace('/\?.*/', '', $image_url);
            $ext = pathinfo($img_temp, PATHINFO_EXTENSION);

            $direct_image_url = in_array(strtolower($ext), array('jpg', 'png', 'gif', 'jpeg', 'webp'));
            $ext = !$direct_image_url ? 'jpg' : $ext;

            $image_name = (!empty($force_name) ? $element_id.'-'.$force_name : ((!empty($table_name) ? $table_name.'-' : '').$element_id.($multiple_images ? '-'.($row_number+1):''))).'.'.$ext;
            $image_local_path = $this->image_path.$this->extra_image_route.$image_name;

            if (strpos($image_url, 'dropbox') !== false) {
                $image_url = preg_replace('/\?.*/', '', $image_url);
                $image_url = str_replace('www', 'dl', $image_url);
                //Devman Extensions - info@devmanextensions.com - 6/4/22 08:25 - Fix for dropbox "copy(): SSL: Connection reset by peer"
                $image_url = str_replace("dropbox.com", "dropboxusercontent.com", $image_url);

            } elseif($direct_image_url) //Remove possible params in direct image url
                $image_url = preg_replace("/\?.*$/", "", $image_url);

            $image_url = str_replace(" ", "%20", $image_url);

            return array(
                'opencart_path' => $image_local_path,
                'local_path' => DIR_IMAGE.$image_local_path,
                'name' => $image_name,
                'url' => $image_url,
                'table_name' => $table_name,
                'field_name' => $field_name,
                'element_id' => $element_id
            );
        }

        public function download_remote_image($img) {
            $download_method = 1;

            $image_local_path = $img['local_path'];
            $image_url = $img['url'];


            if(is_file($this->assets_path.'model_ie_pro_just_call_function_download_remote_image.php'))
                require($this->assets_path.'model_ie_pro_just_call_function_download_remote_image.php');

            $download = !$this->skip_image_download || ($this->skip_image_download && /*is_file is slower*/ !file_exists($image_local_path));

            if($download) {
                try {
                    // !!!ONLY FOR DEBUG!!!
                    // throw new \Exception( "Error downloading: " . $image_url);

                    if($download_method == 1) {
                        copy($image_url, $image_local_path);
                    } elseif($download_method == 2) {
                        $arrContextOptions = array(
                            "ssl" => array(
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ),
                        );
                        $data = file_get_contents($image_url, false, stream_context_create($arrContextOptions));
                        $file = fopen($image_local_path, "w+");
                        fputs($file, $data);
                    } elseif($download_method == 3) {
                        $arrContextOptions = array(
                            'http'=>array(
                                'method'=>"GET",
                                'header'=>"Accept-language: en\r\n" .
                                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:23.0) Gecko/20100101 Firefox/23.0\r\n" .
                                "Referer: http://www.funnyjunk.com\r\n"
                            )
                        );
                        $data = file_get_contents($image_url, false, stream_context_create($arrContextOptions));
                        $file = fopen($image_local_path, "w+");
                        fputs($file, $data);
                    } elseif($download_method == 4) {
                        $ch = curl_init($image_url);
                        curl_setopt($ch, CURLOPT_HEADER, false);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US)");
                        $raw_data = curl_exec($ch);
                        curl_close($ch);
                        $fp = fopen($image_local_path, 'w+');
                        fwrite($fp, $raw_data);
                        fclose($fp);
                    }

                    //Remove downloaded image if is corrupt (0 bytes)
                    if(!filesize($image_local_path)) {
                        unlink($image_local_path);
                        return '';
                    }

                } catch (Exception $e) {
                    if($img['table_name'] != 'options-combinations') {
                        $main_field = '';
                        if($img['table_name'] == 'product')
                            $main_field = 'product_id';
                        elseif($img['table_name'] == 'product_image')
                            $main_field = 'product_image_id';
                        elseif($img['table_name'] == 'category')
                            $main_field = 'category_id';
                        elseif($img['table_name'] == 'manufacturer')
                            $main_field = 'manufacturer_id';
                        elseif($img['table_name'] == 'option_value')
                            $main_field = 'option_value_id';
                        elseif($img['table_name'] == 'product_option_value')
                            $main_field = 'product_option_value_id';

                        if(!empty($main_field))
                            $this->db->query("UPDATE ".DB_PREFIX.$img['table_name']." SET ".$img['field_name']." = '".$this->no_image_path."' WHERE ".$main_field." = ".(int)$img['element_id']);
                    }

                    $this->add_to_image_download_log( $e->getMessage() . "\n");
                    return false;
                }
            }

            return true;
        }

        //Generic function to delete elements from normal tables
        public function delete_element($element_id) {
            $sql = "DELETE FROM ".$this->escape_database_table_name($this->main_table).' WHERE '.$this->escape_database_field($this->main_field).' = '.$this->escape_database_value($element_id).'; ';
            $this->db->query($sql);
            foreach ($this->delete_tables as $key => $table_name) {
                $sql = "DELETE FROM ".$this->escape_database_table_name($table_name).' WHERE '.$this->escape_database_field($this->main_field).' = '.$this->escape_database_value($element_id).'; ';
                $this->db->query($sql);
            }
        }

        function assign_element_id($main_counter_ids) {
            $sql = "SELECT ".$this->escape_database_field($this->main_field)." FROM ".$this->escape_database_table_name($this->main_table)." ORDER BY ".$this->escape_database_field($this->main_field).' DESC LIMIT 1';
            $result = $this->db->query($sql);
            return !empty($result->row[$this->main_field]) ? (int)$result->row[$this->main_field] + $main_counter_ids : $main_counter_ids;
        }

        function array_depth(array $array) {
            $max_depth = 1;
            foreach ($array as $value) {
                if (is_array($value)) {
                    $depth = $this->array_depth($value) + 1;

                    if ($depth > $max_depth) {
                        $max_depth = $depth;
                    }
                }
            }
            return $max_depth;
        }

        function emptyArray($array) {
            $empty = TRUE;
            if (is_array($array)) {
                foreach ($array as $value) {
                    if (!$this->emptyArray($value)) {
                        $empty = FALSE;
                    }
                }
            }
            elseif (!empty($array)) {
                $empty = FALSE;
            }
            return $empty;
        }


        public function exception($message) {
            throw new Exception($message);
        }

        public function ajax_die($message, $error = true) {
            $array_return = array();
            $array_return['error'] = $error;
            $array_return['message'] = $message;
            echo json_encode($array_return); die;
        }

        public function clean_columns($columns) {
            foreach ($columns as $key => $col) {
                if ((!array_key_exists('status', $col) || !$col['status'])) {
                    unset($columns[$key]);
                }
                else {
                    $internal_configuration = json_decode(str_replace("'", '"', $col['internal_configuration']), true);

                    if(is_array($internal_configuration))
                        foreach ($internal_configuration as $input_name => $value)
                            $columns[$key][$input_name] = $value;

                    if($this->profile['profile_type'] == 'import' && !$this->is_ocstore && $columns[$key]['table'] == 'manufacturer' && $columns[$key]['field'] == 'name') {
                        unset($columns[$key]['language_id']);
                        unset($columns[$key]['conditions']);
                    }

                    unset($columns[$key]['internal_configuration']);

                    if(empty($col['custom_name'])) {
                        $columns[$key]['custom_name'] = $col['name'];
                    }
                }
            }

            return $columns;
        }
        
        public function get_column_index_by_field($file_columns, $table, $field, $identificator = '', $return_name = false) {
            $index_column = 0;
            foreach ($this->custom_columns as $key => $col_info) {
                $meet_identificator = empty($identificator) || (!empty($col_info['identificator']) && !empty($identificator) && $col_info['identificator'] === $identificator);

                //For know index of first spplited value, line "Image>0"
                $col_info['custom_name'] = str_replace(">0", "", $col_info['custom_name']);

                if($meet_identificator && !empty($col_info['table']) && $col_info['table'] == $table && !empty($col_info['field']) && ($col_info['field'] == $field || (!empty($col_info['inner_field']) && $col_info['inner_field'] == $field))) {
                    if(!$return_name)
                        return !empty($file_columns) ? array_search($col_info['custom_name'], $file_columns) : $key;
                    else
                        return $col_info['custom_name'];
                }
                $index_column++;
            }
            return false;
        }

        //For Options combinations special prices fields
        function get_column_index_by_field_key($file_columns, $table, $field, $key, $identificator = '') {
            $index_column = 0;

            foreach ($this->custom_columns as $column_index => $col_info) {
                $meet_identificator = empty($identificator) || (!empty($col_info['identificator']) && !empty($identificator) && $col_info['identificator'] === $identificator);
                $meet_table = !empty($col_info['table']) && $col_info['table'] == $table;
                $meet_field = !empty($col_info['inner_field']) && $col_info['inner_field'] == $field;
                $meet_key = !empty($col_info['key']) && $col_info['key'] == $key;

                if($meet_identificator && $meet_table && $meet_field && $meet_key) {
                    return !empty($file_columns) ? array_search($col_info['custom_name'], $file_columns) : $column_index;
                }
                $index_column++;
            }
            return false;
        }

        public function get_columns_field_format($columns) {
            $final_columns = array();
            foreach ($columns as $custom_name => $column) {
                $final_columns[$column['field']] = $custom_name;
            }
            return $final_columns;
        }

        public function get_columns_custom_names_format($columns) {
            $final_columns = array();
            foreach ($columns as $custom_name => $column) {
                $final_columns[$column['field']] = $column['custom_name'];
            }
            return $final_columns;
        }

        public function check_main_field_column($columns) {
            foreach ($columns as $key => $col_name) {
                $field = !empty($this->custom_columns[$col_name]) && array_key_exists('field', $this->custom_columns[$col_name]) ? $this->custom_columns[$col_name]['field'] : '';
                if($field == $this->main_field)
                    return true;
            }
            return false;
        }

        public function is_special_xml_name($elemen_name) {
            return preg_match("/(\>|\*|\@)/s", $elemen_name);
        }

        public function get_tables_info($colums) {
            $tables_info = array();
            foreach ($colums as $key => $col) {
                $table_name = $col['table'];
                if(!array_key_exists($table_name, $tables_info)) {
                    $tables_info[$table_name] = array(
                        'language_id' => false,
                        'store_id' => false,
                        'customer_group_id' => false,
                        'conditions' => false,
                        'identificator' => false,
                    );

                    if(array_key_exists('language_id', $col) && !empty($col['language_id']))
                         $tables_info[$table_name]['language_id'] = true;
                    if(array_key_exists('store_id', $col) && !empty($col['store_id']))
                         $tables_info[$table_name]['store_id'] = true;
                    if(array_key_exists('customer_group_id', $col) && !empty($col['customer_group_id']))
                         $tables_info[$table_name]['customer_group_id'] = true;
                    if(array_key_exists('conditions', $col) && !empty($col['conditions']))
                         $tables_info[$table_name]['conditions'] = $col['conditions'];
                    if(array_key_exists('identificator', $col) && !empty($col['identificator']))
                         $tables_info[$table_name]['identificator'] = $col['identificator'];
                }
            }

            return $tables_info;
        }

        public function get_stock_statuses($import_format = false) {
			$sql = "SELECT * FROM ".$this->escape_database_table_name('stock_status')." WHERE language_id = ".(int)$this->default_language_id.";";
			$result = $this->db->query( $sql );
			$stock_statuses = $result->rows;
			$final_statuses = array();
			foreach ($stock_statuses as $key => $status) {
			    if(!$import_format)
			        $final_statuses[$status['stock_status_id']] = $status;
			    else
                    $final_statuses[$status['name']] = $status['stock_status_id'];
			}
			if(!$import_format) {
                $stock_statuses = $this->model_extension_devmanextensions_tools->aasort($final_statuses, 'name');
                return $stock_statuses;
            } else {
			    return $final_statuses;
            }
		}

		public function get_classes_length($import_format = false) {
            $this->load->model('localisation/length_class');
            $length_classes = $this->model_localisation_length_class->getLengthClasses();
            $final_length_classes = array();
            $config = $this->config->get('config_length_class_id');
            foreach ($length_classes as $key => $class_length) {
                $id = $class_length['length_class_id'];
                if($config == $id)
                    $class_length['default'] = true;

                if(!$import_format)
                    $final_length_classes[$id] = $class_length;
                else
                    $final_length_classes[$class_length['title']] = $id;
            }
            return $final_length_classes;
        }

        public function get_layouts($import_format = false) {
            $this->load->model('design/layout');
            $layouts_temp = $this->model_design_layout->getLayouts();
            $layouts = array();
            foreach ($layouts_temp as $key => $layout) {
                if(!$import_format)
                    $layouts[$layout['layout_id']] = $layout['name'];
                else
                    $layouts[$layout['name']] = $layout['layout_id'];
            }
            return $layouts;
        }

        public function get_tax_classes($import_format = false) {
            $this->load->model('localisation/tax_class');
            $this->load->model('localisation/tax_rate');
            $tax_clases = $this->model_localisation_tax_class->getTaxClasses(array('order' => 'ASC'));
            $final_tax = array();

            if(version_compare(VERSION, '1.5.1', '>'))
            {
                foreach ($tax_clases as $key => $tax_class) {
                    $tax_rules = $this->model_localisation_tax_class->getTaxRules($tax_class['tax_class_id']);

                    foreach ($tax_rules as $key2 => $tax_rule) {
                        if($tax_rule['based'] == 'store')
                        {
                            $tax_rate = $this->model_localisation_tax_rate->getTaxRate($tax_rule['tax_rate_id']);
                            $tax_clases[$key]['rule'] = $tax_rate;
                        }
                    }
                }
            }
            else
            {
                foreach ($tax_clases as $key => $tax_class) {
                    $tax_rate = $this->model_localisation_tax_class->getTaxRates($tax_class['tax_class_id']);
                    $tax_clases[$key]['rule'] = $tax_rate;
                }
            }

            foreach ($tax_clases as $key => $tax_class) {
                if(!$import_format)
                    $final_tax[$tax_class['tax_class_id']] = $tax_class;
                else
                    $final_tax[$tax_class['title']] = $tax_class['tax_class_id'];
            }
            return $final_tax;
        }

        public function get_classes_weight($import_format = false) {
            $this->load->model('localisation/weight_class');
            $weight_classes = $this->model_localisation_weight_class->getWeightClasses();
            $final_weight_classes = array();
            $config = $this->config->get('config_weight_class_id');
            foreach ($weight_classes as $key => $class_weight) {
                $id = $class_weight['weight_class_id'];
                if($config == $id)
                    $class_weight['default'] = true;

                if(!$import_format)
                    $final_weight_classes[$id] = $class_weight;
                else
                    $final_weight_classes[$class_weight['title']] = $id;
            }
            return $final_weight_classes;
        }

        public function price_combination_tax_calculate($tax_class_id, $price, $operation, $price_type, $price_prefix)
        {
            $tax = array_key_exists((int)$tax_class_id, $this->tax_classes) ? $this->tax_classes[$tax_class_id] : '';
            if (!empty($tax) && array_key_exists('rule', $tax)) {
                $rule = $tax['rule'];
                $type = $rule['type'];
                $rate = $rule['rate'];
                $tax_formated = 1+($rate/100);
                if ($type == 'F') {
                    if ($price_type == 'option_price' && $price_prefix == '=') {
                        $price = $operation == 'sum' ? $price + $rate : $price - $rate;
                    } else if (in_array($price_type, ['option_special', 'option_discount'])) {
                        $price = $operation == 'sum' ? $price + $rate : $price - $rate;
                    }
                } else if ($operation == 'sum') {
                    $price = $price * $tax_formated;
                } else {
                    $price = $price / $tax_formated;
                }
            }
            return $price;
        }

        public function price_tax_calculate($product_id, $price, $operation, $force_tax_class_id = false) {
            $tax_class_id = !empty($force_tax_class_id) ? $force_tax_class_id : $this->model_extension_module_ie_pro_products->get_product_field($product_id,'tax_class_id');
            if(is_numeric($tax_class_id)) {
                $tax = array_key_exists((int)$tax_class_id, $this->tax_classes) ? $this->tax_classes[$tax_class_id] : '';
                if(!empty($tax) && array_key_exists('rule', $tax)) {
                    $rule = $tax['rule'];
                    $type = $rule['type'];
                    $rate = $rule['rate'];
                    $tax_formated = 1+($rate/100);

                    if($type == 'F')
                        $price = $operation == 'sum' ? $price + $rate : $price - $rate;
                    else if($operation == 'sum') {
                        $price = $price * $tax_formated;
                    } else {
                        $tax_formated = 1+($rate/100);
                        $price = $price / $tax_formated;
                    }
                }
            }

            return $price;
        }

        public function format_seo_url($string) {
            if($this->custom_format_seo_url)
                require($this->assets_path.'model_ie_pro_function_format_seo_url.php');
            $string = trim($string); // Trim String
            $string = strtolower($string); //Unwanted:  {UPPERCASE} ; / ? : @ & = + $ , . ! ~ * ' ( )
            $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);  //Strip any unwanted characters
            $string = preg_replace("/[\s-]+/", " ", $string); // Clean multiple dashes or whitespaces
            $string = preg_replace("/[\s_]/", "-", $string); //Convert whitespaces and underscore to dash
            $string = str_replace('/','_',$string);
            return $string;
        }

        function is_url($string) {
            $latin_url = preg_match( '/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' , $string);
            $russian_url = strpos($string, 'xn--') !== false && strpos($string, '--p1ai') !== false;
            $force_url = !$latin_url && !$russian_url && substr($string, 0, 4) == 'http';
            return $latin_url || $russian_url || $force_url;
        }

        public function create_progress_file() {
            if(!is_dir($this->path_progress)) {
                mkdir($this->path_progress, 0755);
            }

            $htaccess_file = $this->path_progress . '.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess = 'AddType text/iepro iepro
                    <FilesMatch "\.(json|xlsx|xls|ods|xml|xls|txt|iepro)$">
                        allow from all
                    </FilesMatch>';
                file_put_contents($htaccess_file, $htaccess);
            }

            if(!is_dir($this->path_tmp))
                mkdir($this->path_tmp, 0755);

            //$fp = fopen($this->path_progress_file, 'w');
            file_put_contents($this->path_progress_file, '[]');

            //Clean folder temp
            $files = glob($this->path_tmp.'*');
            if(is_array($files))
                foreach($files as $file){
                    if(is_file($file)) unlink($file);
                }

            if (file_exists( $this->path_progress_cancelled_file)) {
                unlink( $this->path_progress_cancelled_file);
            }

            return true;
        }

        public function create_image_download_log() {
            $filename = $this->get_image_download_log_filename();

            if (file_exists( $filename)) {
                unlink( $filename);
            }

            touch( $filename);
        }

        function update_process($data, $replace_last_line = false) {
            if (file_exists( $this->path_progress_cancelled_file)) {
                exit( -1);
            }

            $data = is_string($data) ? array('message' => $data) : $data;
            $continue = array_key_exists('continue', $data) ? $data['continue'] : true;
            $status = array_key_exists('status', $data) ? $data['status'] : '';
            $message = array_key_exists('message', $data) ? $data['message'] : $this->language->get($status);
            $redirect = array_key_exists('redirect', $data) ? $data['redirect'] : '';

            switch ($status) {
                case 'progress_import_import_finished':
                    $continue = false;
                    $message = '<div class="alert alert-success">'.$message.'</div>';
                    break;

                case 'progress_export_finished':
                    $continue = false;
                    $message = '<div class="alert alert-success">'.$message.'</div>';
                    break;

                case 'progress_import_export_cancelled':
                    $continue = false;
                    $message = '<div class="alert alert-danger">' . $message . '</div>';
                    touch( $this->path_progress_cancelled_file);
                    break;

                case 'error':
                    $continue = false;
                    $message = '<div class="alert alert-danger">'.$message.'</div>';
                    break;

                default:
                    $message = date('Y-m-d H:i:s').' - '.$message.' - [Memory used: '.$this->convert_memory(memory_get_usage(true)).']';
                break;
            }

            $content = file_get_contents($this->path_progress_file);
            $content_array = json_decode($content, true);

            $content_array['continue'] = $continue;
            $content_array['status'] = $status;
            $content_array['redirect'] = $redirect;

            if (!array_key_exists('message', $content_array))
                $content_array['message'] = array();

            if ($replace_last_line)
                array_pop($content_array['message']);

            $content_array['message'][] = $message;

            file_put_contents($this->path_progress_file, json_encode($content_array));

            if(!$this->is_cron_task && in_array($status, array('error'))) {
                echo json_encode($content_array); die;
            }

            if($this->is_cron_task) {
                if(in_array($status, array('error')) || !$continue) {
                    echo implode('<br>', $content_array['message']);
                    echo '<br><br>----------------<br><br>';
                    $this->load->model("extension/module/ie_pro_tab_crons");
                    $this->model_extension_module_ie_pro_tab_crons->email_report(implode('<br>', $content_array['message']));
                    die('<b>Finished!</b>');
                }
            }
            return true;
        }

        function convert_memory($size)
        {
            $unit=array('b','kb','mb','gb','tb','pb');
            return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        }

        function cancel_process($error) {
            $params = array(
                'message' => $error,
                'status' => 'progress_import_export_cancelled'
            );

            $this->update_process($params);
        }

        function translate_boolean_value($value) {
            $value = strtolower($value);

            $true_values = array(1, 'true', 'yes');
            if(in_array($value, $true_values))
                return true;

            return false;
        }

        function fatalErrorShutdownHandler()
        {
            $last_error = error_get_last();
            if(is_array($last_error)) {
                $code = array_key_exists('code', $last_error) ? $last_error['code'] : '';
                $type = array_key_exists('type', $last_error) ? $last_error['type'] : '';
                $message = array_key_exists('message', $last_error) ? $last_error['message'] : '';
                $file = array_key_exists('file', $last_error) ? str_replace(DIR_APPLICATION, '', $last_error['file']) : '';
                $line = array_key_exists('line', $last_error) ? $last_error['line'] : '';
                $skip_error = strpos($message, 'use mysqli or PDO') !== false;
                if(!$skip_error) {
                    $final_message = '<ul>';
                    $final_message .= !empty($code) ? '<li><b>Error code:</b> ' . $code . '</li>' : '';
                    $final_message .= !empty($file) ? '<li><b>Error file:</b> ' . $file . '</li>' : '';
                    $final_message .= !empty($line) ? '<li><b>Error line:</b> ' . $line . '</li>' : '';
                    $final_message .= !empty($message) ? '<li><b>Error message:</b> ' . $message . '</li>' : '';
                    $final_message .= '</ul>';

                    $special_error = false;

                    $special_error = !$special_error && $file == 'Unknown';

                    if(!$special_error) {
                        $data = array(
                            'status' => 'error',
                            'message' => $final_message,
                        );
                        $this->update_process($data);

                        throw new Exception($final_message);
                    }
                }
            }
            return false;
        }

        function customCatchError($errno = '', $errstr = '', $errfile = '', $errline = '') {
            $file = str_replace(DIR_APPLICATION, '', $errfile);

            $skip_error = strpos($errstr, 'use mysqli or PDO') !== false;

            if(!$skip_error) {
                if (!$errno) {
                    if (strpos($errstr, 'Request Timeout') !== false || strpos($errstr, '504 Gateway Timeout') !== false || strpos($errstr, '500 Internal Server Error') !== false || substr($errstr, 0, 5) == '<html')
                        $final_message = $this->language->get("export_import_server_error");
                    else
                        $final_message = $errstr;
                } else {
                    $final_message = '<ul>';
                    $final_message .= '<li><b>Error code:</b> ' . $errno . '</li>';
                    $final_message .= '<li><b>Error file:</b> ' . $file . '</li>';
                    $final_message .= '<li><b>Error line:</b> ' . $errline . '</li>';
                    $final_message .= '<li><b>Error message:</b> ' . $errstr . '</li>';
                    $final_message .= '</ul>';
                }

                throw new Exception($final_message);
            }
        }

        function onDie(){
            $message = ob_get_contents();
            ob_end_clean();

            throw new Exception($message);
        }

        function get_image_link($image_name) {
            $img_link = $this->api_url.'opencart_admin/ext_ie_pro/img/'.$image_name;
            return $img_link;
        }

        function IsOptionsCombinationsInstalled(){
            $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `type` = 'module' AND `code` = 'options_combinations'");
            return $result->num_rows ? true : false;
        }


        function get_ie_pro_version() {
            return str_replace('.','',$this->language->get('extension_version'));
        }

        public function get_image_download_errors() {
            return preg_split( '/\n/', $this->get_image_download_log_contents());
        }

        public function get_image_download_log_html() {
            return preg_replace( '/\n/', '<br>', $this->get_image_download_log_contents());
        }

        public function get_image_download_log_contents() {
            return trim( file_get_contents( $this->get_image_download_log_filename()));
        }

        private function add_to_image_download_log( $message) {
            $hFile = fopen( $this->get_image_download_log_filename(), 'a');

            fwrite( $hFile, $message);
            fclose( $hFile);
        }

        private function get_image_download_log_filename() {
            if ($this->image_log_filename === null) {
               $user_token = $this->is_cron_task ? 'cron' : (array_key_exists($this->token_name, $_GET) ? $_GET[$this->token_name] : '');

               $this->image_log_filename = "{$this->path_tmp}image-log-{$user_token}.html";
            }

            return $this->image_log_filename;
        }

        public function get_image_download_log_url() {
            $user_token = $this->is_cron_task ? 'cron' : (array_key_exists($this->token_name, $_GET) ? $_GET[$this->token_name] : '');

            return "{$this->path_tmp_public}image-log-{$user_token}.html";
        }
    }

    class ConditionalExpressionParser
    {
        const PATTERN = '#\((([^()]+|(?R))*)\)#';

        /**
         * @var array
         */
        private $conditional_value_conditions;

        /**
         * @var array
         */
        private $columns;

        private $controller;

        private $language;

        public function __construct( $conditional_value_conditions, $columns, $controller) {
            $this->conditional_value_conditions = $conditional_value_conditions;
            $this->columns = $columns;
            $this->controller = $controller;

            $this->language = $this->controller->language;
        }

        public function parse( $expression, $column_name, $real_column_name) {
            $result = [];
            $expression = trim( $expression);

            preg_match_all( self::PATTERN, $expression, $conditions);

            $conditions = $conditions[1];

            foreach ($conditions as $conditional_expr) {
                $parsed_expr = $this->parse_expression(
                    $conditional_expr,
                    $column_name,
                    $real_column_name
                );

                foreach ($parsed_expr as $column => $value) {
                    if (isset( $result[$column])) {
                        $result[$column][] = $value[0];
                    } else {
                        $result[$column] = $value;
                    }
                }
            }

            return $result;
        }

        private function parse_expression( $expr, $column_name, $real_column_name) {
            $result = [];

            $comparator = $this->detect_comparator( $expr);

            $conditional_expr = $this->parse_expression_by_comparator(
                $expr,
                $comparator,
                $column_name,
                $real_column_name
            );

            $conditional_expr['table'] = $this->to_effective_column(
                $conditional_expr['table'],
                $column_name
            );

            $conditional_expr['result'] = $this->to_effective_column(
                $conditional_expr['result'],
                $column_name
            );

            if (isset( $conditional_expr['else'])) {
                $conditional_expr['else'] = $this->to_effective_column(
                    $conditional_expr['else'],
                    $column_name
                );
            }

            $result[$column_name] = [$conditional_expr];

            return $result;
        }

        private function detect_comparator( $expr) {
            $result = null;

            preg_match_all("/\[[^\]]*\]/", $expr, $matches);
            if(!empty($matches[0][0])) {
                $column_name_with_symbols = $matches[0][0];
                $expr = str_replace($matches[0][0], '[column_name]', $expr);
            }

            foreach ($this->conditional_value_conditions as $comparator) {
                $comparator_index = strpos( $expr, $comparator);

                if ($comparator_index !== false) {
                    if ($result !== null && $comparator_index !== 0 && $comparator !== '*') {
                        // Mas de un comparator
                        $this->exception( sprintf( $this->language->get('progress_import_export_error_wrong_conditional_value_multiple_symbols'), $expr, $comparator));
                    }

                    if ($result === null || $comparator !== '*') {
                        $result = $comparator;
                        break;
                    }

                }
            }

            if ($result === null) {
                // No hay un comparator
                $this->exception( sprintf( $this->language->get('progress_import_export_error_conditional_missing_symbol'), $expr));
            }

            return $result;
        }

        private function parse_expression_by_comparator(
            $expression,
            $comparator,
            $column_name,
            $real_column_name) {
            $result = [];

            preg_match_all("/\[[^\]]*\]/", $expression, $matches);

            if(!empty($matches[0][0])) {
                $column_name_with_symbols = $matches[0][0];
                $expression = str_replace($matches[0][0], '[column_name]', $expression);
            }

            $expression_parts = explode( $comparator, $expression);

            if (count( $expression_parts) != 2) {
                $this->exception(sprintf($this->language->get('progress_import_export_error_wrong_conditional_value'), $expression));
            }

            if(!empty($column_name_with_symbols))
                $expression_parts[0] = $column_name_with_symbols;

            $result['table'] = $real_column_name !== null
                                ? $real_column_name
                                : trim( $expression_parts[0]);

            $result['symbol'] = trim( $comparator);
            $value = $expression_parts[1];
            $value_parts = explode('=', $value);

            if (count( $value_parts) != 2) {
                $this->exception(sprintf($this->language->get('progress_import_export_error_wrong_conditional_value'), $expression));
            }

            $result['value'] = trim( $value_parts[0]);

            $value_expr = $value_parts[1];

            $value_parsed = $this->parse_value_expression( $value_expr);

            $result['result'] = $value_parsed['result'];

            if(!empty($column_name_with_symbols) && !empty($result['result']) && strpos($result['result'], '[column_name]') !== false) {
                $result['result'] = str_replace("[column_name]", $column_name_with_symbols, $result['result']);
            }

            if (isset( $value_parsed['else'])) {
                $result['else'] = $value_parsed['else'];
            }

            return $result;
        }

        private function parse_value_expression( $value_expr) {
            return (!empty( $value_expr) && $value_expr[0] === "'")
                   ? $this->parse_single_quoted_value_expr( $value_expr)
                   : $this->parse_simple_value_expr( $value_expr);
        }

        private function parse_single_quoted_value_expr( $value_expr) {
            $result = [
                'result' => null,
            ];

            $error_message = sprintf($this->language->get('progress_import_export_error_wrong_conditional_value'), $value_expr);

            $else_cond = null;
            $if_cond = $this->parse_quoted_text( $value_expr, 0, $error_message);

            $len = strlen( $value_expr);
            $i = strlen( $if_cond) + 2;

            if ($i < $len) {
                // Si aun hay caracteres después del "'" de cierre
                // pero no es un ":" -> ERROR
                if ($value_expr[$i] !== ':') {
                    $this->exception( $error_message);
                }

                $i++;

                // Si el ultimo caracter es un ":" -> ERROR
                if ($i === $len) {
                    $this->exception( $error_message);
                }

                // "else" con comillas
                if ($value_expr[$i] === "'") {
                    $else_cond = $this->parse_quoted_text(
                        $value_expr,
                        $i,
                        $error_message
                    );
                } else {
                    // "else" sin comillas
                    $else_cond = substr( $value_expr, $i);

                    // Si el "else" no comienza por comillas
                    // pero tiene alguna dentro -> ERROR
                    if (strpos( $else_cond, "'") !== false) {
                        $this->exception( $error_message);
                    }
                }
            }

            $result['result'] = $if_cond;

            if ($else_cond !== null) {
                $result['else'] = $else_cond;
            }

            return $result;
        }

        private function parse_simple_value_expr( $value_expr) {
            $result = [];

            $parts = explode( ':', $value_expr);

            //Conditional value with link -> (!=''=https://mmaniak.pl[picture>0]:)
            if(!empty($parts[0]) && in_array($parts[0], array('https','http'))) {
                $parts = array(
                    $parts[0].':'.$parts[1],
                    $parts[2]
                );
            }

            if (count( $parts) == 1) {
                $result['result'] = trim( $parts[0]);
            } else if(count( $parts) == 2) {
                $result['result'] = trim( $parts[0]);
                $result['else'] = trim( $parts[1]);
            } else {
                $this->exception(
                    sprintf( $this->language->get('progress_import_export_error_wrong_conditional_value'),
                             $value_expr)
                );
            }

            return $result;
        }

        private function has_multiple_comparators( $expression) {
            foreach ($this->conditional_value_conditions as $comparator) {
                if (substr_count( $expression, $comparator) > 1) {
                    return true;
                }
            }

            return false;
        }

        private function is_single_quoted( $text) {
            $last = strlen( $text) - 1;

            if ($text[0] === "'" || $text[$last] === "'") {
                if ($text[0] !== $text[$last]) {
                    $this->exception( sprintf( $this->language->get( 'progress_import_export_error_incorrect_quoted_string'), $text));
                }

                return true;
            }

            return false;
        }

        private function parse_quoted_text( $text, $index, $error_message) {
            $result = '';
            $i = $index + 1;
            $len = strlen( $text);

            while ($i < $len && $text[$i] !== "'") {
                $result .= $text[$i];
                $i++;
            }

            if ($i === $len) {
                // Llegamos al final sin encontrar un "'" -> ERROR!
                $this->exception( $error_message);
            }

            return $result;
        }

        private function to_effective_column( $name, $owner_column) {
            return preg_replace_callback( '/\[([^\]]+)\]/', function( $matches) use( $owner_column) {
                $column_name = $matches[1];
                $filter = '';

                $pipe_index = strpos( $column_name, '|');

                if ($pipe_index !== false) {
                    $filter = substr( $column_name, $pipe_index + 1);
                    $column_name = substr( $column_name, 0, $pipe_index);
                }

                $column = $this->find_column_matching_name( $column_name);

                if ($column === null) {
                    $this->exception( sprintf( $this->language->get( 'profile_import_export_conditional_expression_invalid_column_name'), $owner_column, $column_name));
                }

                $effective_column = $column['custom_name'];

                if (!empty( $filter)) {
                    $filter = "|{$filter}";
                }

                return "[{$effective_column}{$filter}]";
            }, $name);
        }

        private function find_column_matching_name( $name) {
            $result = null;

            if (isset( $this->columns[$name])) {
                $result = $this->columns[$name];
            } else {
                foreach ($this->columns as $column) {
                    if ($column['custom_name'] === $name) {
                        $result = $column;
                        break;
                    }
                }
            }

            return $result;
        }

        private function exception( $message) {
            $this->controller->exception( $message);
        }
    }

    class ConditionalExpressionEvaluator {
        private $controller;

        private $loader;

        private $conditional_values;

        private $language;

        public function __construct( $controller, $conditional_values, $model_extension_module_ie_pro_products) {
            $this->controller = $controller;
            $this->conditional_values = $conditional_values;
            $this->language = $this->controller->language;
            $this->model_extension_module_ie_pro_products = $model_extension_module_ie_pro_products;

            $this->filter_evaluator = new FilterEvaluator( $controller);
        }

        public function evaluateDataItems( &$items, $options_columns = array()) {

            $options_columns_keys = !empty($options_columns) ? array_values($options_columns) : array();

            foreach ($items as $key => &$item) {
                $is_option_row = !empty($options_columns) ? $this->model_extension_module_ie_pro_products->check_is_option_row($item, $options_columns) : false;

                foreach ($this->conditional_values as $custom_name => $conditionals) {

                    $insert = !$is_option_row || ($is_option_row && in_array($custom_name, $options_columns_keys));

                    if ($insert && isset( $item[$custom_name])) {
                        if (count( $conditionals) === 1) {
                            list($value,) = $this->evaluate(
                                $conditionals[0],
                                $item,
                                $custom_name
                            );

                            $items[$key][$custom_name] = $value;
                        } else {
                            $conditionSatisfied = false;
                            $value = '';

                            foreach ($conditionals as $cond) {
                                list($value, $conditionSatisfied) = $this->evaluate(
                                    $cond,
                                    $item,
                                    $custom_name
                                );

                                if ($conditionSatisfied || (!$conditionSatisfied && isset($cond['else']))) {
                                    $items[$key][$custom_name] = $value;
                                    break;
                                }
                            }

                            /*if (!$conditionSatisfied) {
                                $items[$key][$custom_name] = '';
                            }*/
                        }
                    }
                }
            }
        }

        public function evaluate( $cond, $element, $custom_name) {
            $column_name = isset( $cond['table']) ? $cond['table'] : '';

            preg_match_all("/\[([^\]]*)\]/", $column_name, $matches);
            $column_name = !empty( $matches[1]) ? $matches[1][0] : $column_name;

            $original_value = array_key_exists($custom_name, $element) ? $element[$custom_name] : '';

            $current_value = $this->evaluate_condition_value(
                $column_name,
                $element,
                $cond,
                $custom_name
            );

            $symbol = $cond['symbol'];
            $condition_value = $cond['value'];

            //Maybe conditional_value has column name
            preg_match_all("/\[([^\]]*)\]/", $condition_value, $matches);
            if(!empty($matches[1][0]))
                 $condition_value = array_key_exists($matches[1][0], $element) ? $element[$matches[1][0]] : $condition_value;


            $result_value = $cond['result'];

            $self = $this;

            $result_value = preg_replace_callback( "/\[([^\]]*)\]/",
                function ($matches) use( $element, $self) {
                    return $self->evaluate_result_value( $matches[1], $element);
                }, $result_value);

            $met_condition = false;

            if (!in_array( $symbol, array( '*', '!*'))) {
                if ($symbol === '==' && $condition_value === '') {
                    $real_value = isset( $element[$custom_name]) ? $element[$custom_name] : '';
                    $met_condition = empty( $real_value) && $real_value !== 0;
                }
                else if ($symbol === '~=') {
                    $met_condition = $this->evaluate_condition_contains( $current_value, $condition_value);
                }
                else if ($symbol === 'like') {
                    $met_condition = $this->evaluate_condition_like( $current_value, $condition_value);
                }
                else {
                    $format_current_value = $this->format_value( $current_value);
                    $format_condition_value = $this->format_value( $condition_value);

                    if ($symbol === '==') {
                        $met_condition = $format_current_value == $format_condition_value;
                    } elseif ($symbol === '!=') {
                        $met_condition = $format_current_value != $format_condition_value;
                    }  else {
                        if(!is_numeric($format_current_value) || !is_numeric($format_condition_value))
                            $met_condition = false;
                        else
                            $met_condition = eval("return {$format_current_value}{$symbol}{$format_condition_value};");
                    }
                }
            } else {
                if ($symbol == '*') {
                    $met_condition = !empty($condition_value) && strpos(strtolower($current_value), strtolower($condition_value)) !== false;
                }
                elseif($symbol == '!*') {
                    $met_condition = !empty($condition_value) && strpos(strtolower($current_value), strtolower($condition_value)) === false;
                }
            }

            if ($met_condition) {
                $result = $result_value;
            } else {
                if (isset( $cond['else'])) {
                    $else = $cond['else'];

                    $else = preg_replace_callback( "/\[([^\]]*)\]/",
                        function ($matches) use( $element, $self) {
                            return $self->evaluate_result_value( $matches[1], $element);
                        }, $else);

                    $result = $else;
                } else {
                    $result = $original_value;
                }
            }

            return [$result, $met_condition];
        }

        private function evaluate_condition_value( $column_name, $element, $cond, $custom_name) {
            $result = null;
            list($column_name, $filter, $args) = $this->parse_column( $column_name);

            if (!empty($column_name) && isset( $element[$column_name])) {
                $result = $element[$column_name];
            } else if (empty( $column_name)) {
                if (isset($element[$custom_name]) && (!empty($element[$custom_name]) || is_numeric($element[$custom_name]))) {
                    $result = $element[$custom_name];
                } else {
                    $temp_table = $cond['result'];
                    $result = $temp_table;
                    preg_match_all( "/\[([^\]]*)\]/", $temp_table, $matches);
                    $temp_table = !empty( $matches[1]) ? $matches[1][0] : '';

                    if (!empty($temp_table) && isset($element[$temp_table])) {
                        $result = $element[$temp_table];
                    }
                }
            }
            else {
                $result = null;
            }

            if ($filter !== null) {
                $result = $this->evaluate_filter( $result, $filter, $args);
            }

            return $result;
        }

        private function evaluate_result_value( $column_name, $element) {
            list($column_name, $filter, $args) = $this->parse_column( $column_name);

            $result = isset( $element[$column_name])
                      ? $element[$column_name]
                      : '';

            if ($filter !== null) {
                $result = $this->evaluate_filter( $result, $filter, $args);
            }

            return $result;
        }

        private function evaluate_filter( $value, $filter, $args = []) {
            return $this->filter_evaluator->evaluate( $filter, $value, $args);
        }

        private function parse_column( $column_name) {
            $filter = null;
            $args = [];
            $pipe_index = strpos( $column_name, '|');

            if ($pipe_index !== false) {
                $filter = substr( $column_name, $pipe_index + 1);
                $column_name = substr( $column_name, 0, $pipe_index);

                list($filter,$args) = $this->parse_filter_args( $filter);
            }

            return [$column_name, $filter, $args];
        }

        private function parse_filter_args( $filter) {
            if (!preg_match( '/([^\(]+)(\((.+)\))?/', $filter, $matches)) {
                $this->exception( sprintf( $this->language->get( 'progress_import_export_error_invalid_filter_syntax'), $filter));
            }

            $args = [];

            if (count( $matches) === 4) {
                $args_list = $matches[3];

                if (!preg_match( '/([^,]+)(,([^,]+))*/', $args_list, $dummy)) {
                    $this->exception( sprintf( $this->language->get( 'progress_import_export_error_invalid_filter_syntax'), $filter));
                }

                $args = preg_split( '/,/', $args_list);
            }

            return [$matches[1], $args];
        }

        private function evaluate_condition_contains( $current_value, $condition_value) {
            $current_value = $this->format_value( $current_value);

            $regex = $this->build_contains_regex( $condition_value);
            $matches = preg_match( $regex, $current_value);

            if ($matches === false) {
                $this->exception( sprintf( $this->language->get( 'progress_import_export_error_invalid_filter_syntax'), $condition_value));
            }

            return $matches !== 0;
        }

        private function evaluate_condition_like( $current_value, $condition_value){
            $current_value = $this->format_value( $current_value);

            $regex = $this->build_like_regex( $condition_value);
            $matches = preg_match( $regex, $current_value);

            if ($matches === false) {
                $this->exception( sprintf( $this->language->get( 'progress_import_export_error_invalid_filter_syntax'), $condition_value));
            }

            return $matches !== 0;
        }

        private function build_contains_regex( $condition) {
            return $this->build_regex_ignore_spaces( $condition, '/\\s+/');
        }

        private function build_like_regex( $condition) {
            return $this->build_regex_ignore_spaces( $condition, '/%/');
        }

        private function build_regex_ignore_spaces( $condition, $spaces_regex) {
            $result = $this->format_value( $condition);
            $result = $this->unquote( $result);
            $result = $this->escape_regex_characters( $result);

            $result = preg_replace( $spaces_regex, '\\s+', $result);
            $result = '/' . $result . '/i';

            return $result;
        }

        private function escape_regex_characters( $text) {
            $result = $text;

            $regex_chars = ['[', ']', '(', ')', '{', '}', '.', '+', '*'];

            foreach ($regex_chars as $char) {
                $regex = '/\\' . $char . '/';
                $replacement = "\\{$char}";

                $result = preg_replace( $regex, $replacement, $result);
            }

            return $result;
        }

        private function format_value( $value) {
            return is_numeric( $value) || $value === 0 || $this->is_quoted( $value)
                   ? $value
                   : "'{$value}'";
        }

        private function is_quoted( $text) {
            return !empty( $text) &&
                   $text[0] === $text[strlen( $text) - 1] && in_array( $text[0], ["'", '"']);
        }

        private function unquote( $text) {
            $result = $text;

            if ($this->is_quoted( $text)) {
                $result = substr( $result, 1, strlen( $result) - 2);
            }

            return $result;
        }

        private function exception( $message) {
            $this->controller->exception( $message);
        }
    }

    class FilterEvaluator {
        /**
         * @var array
         */
        private static $FILTERS = [];

        private $controller;

        private $language;

        public static function register( $names, $filter_funct) {
            if (!is_array( $names)) {
                $names = [$names];
            }

            foreach ($names as $name) {
                self::$FILTERS[$name] = $filter_funct;
            }
        }

        public function __construct( $controller) {
            $this->controller = $controller;
            $this->language = $this->controller->language;
        }

        public function evaluate( $name, $value, $args = []) {
            $pipe_index = strpos( $name, '|');

            if ($pipe_index === false) {
                if (!$this->exists( $name)) {
                    $this->exception( sprintf( $this->language->get( 'progress_import_export_error_missing_conditional_filter'), $name));
                }

                $filter = self::$FILTERS[$name];

                try {
                    $args = array_merge( [$value], $args);

                    $result = call_user_func_array( $filter, $args);
                } catch( \Exception $ex) {
                    $this->exception( sprintf( $this->language->get( 'progress_import_export_error_evaluating_filter'), $name, $ex->getMessage()));
                }
            } else {
                $values = array_reverse( preg_split( '/\|/', $name));

                if (count( $values) !== 2) {
                    $this->exception( sprintf( $this->language->get( 'progress_import_export_error_invalid_filter_syntax'), $value));
                }

                $value .= '';

                if (!in_array( $value, ['0', '1'])) {
                    $this->exception( sprintf( $this->language->get( 'progress_import_export_error_invalid_boolean_filter'), $value));
                }

                $result = $values[$value];
            }

            return $result;
        }

        private function exists( $name) {
            return isset( self::$FILTERS[$name]);
        }

        private function exception( $message) {
            $this->controller->exception( $message);
        }
    }

    // Filters
    FilterEvaluator::register( ['len', 'length'], 'strlen');
    FilterEvaluator::register( ['uppercase', 'upper'], 'strtoupper');
    FilterEvaluator::register( ['lowercase', 'lower'], 'strtolower');
    FilterEvaluator::register( 'capitalize', 'ucfirst');
    FilterEvaluator::register( 'trim', 'trim');
    FilterEvaluator::register( 'substr', 'substr');

    FilterEvaluator::register( 'ellipsis', function( $text, $max_length) {
        $result = $text;
        $len = strlen( $text);

        if ($len > $max_length) {
            $result = substr( $text, 0, $max_length - 3) . '...';
        }

        return $result;
    });
?>
