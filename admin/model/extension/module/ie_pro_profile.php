<?php
    class ModelExtensionModuleIeProProfile extends ModelExtensionModuleIePro {

        const IMAGES_NUMBER = 3;

        function save() {
            if (isset($this->request->post['import_xls_option_combinations_images_number']) &&
                $this->request->post['import_xls_option_combinations_images_number'] > ModelExtensionModuleIeProProfile::IMAGES_NUMBER){
                $this->exception(sprintf($this->language->get('profile_options_combinations_image_number_error'), ModelExtensionModuleIeProProfile::IMAGES_NUMBER));
            }

            $this->request->post['no_exit'] = true;
            $this->validate_permiss();
            $profile_id = array_key_exists('profile_id', $this->request->post) ? $this->request->post['profile_id'] : '';
            $profile_name = array_key_exists('import_xls_profile_name', $this->request->post) ? $this->request->post['import_xls_profile_name'] : '';
            $profile_type = array_key_exists('profile_type', $this->request->post) ? $this->request->post['profile_type'] : '';

            $array_return = array('error' => false, 'message' => '');

            if(count($this->request->post, true) > ini_get('max_input_vars'))
                $this->exception(sprintf($this->language->get('profile_error_max_input_vars'), ini_get('max_input_vars'), count($this->request->post, true)));

            if(empty($profile_name))
                $this->exception($this->language->get('profile_error_empty_name'));

            if(array_key_exists('export_filter', $this->request->post)) {
                unset($this->request->post['export_filter']['replace_by_number']);

                $final_config = array(
                    'filters' => array(),
                    'config' => array(),
                );
                foreach ($this->request->post['export_filter'] as $key => $val) {
                    if(is_numeric($key)) {
                        $final_config['filters'][] = $val;
                    } else {
                        $final_config['config'][$key] = $val;
                    }
                }

                $this->request->post['export_filter'] = $final_config;

                if(empty($this->request->post['export_filter']['filters']))
                    unset($this->request->post['export_filter']);
                else
                    $this->request->post['export_filter']['filters'] = array_values($this->request->post['export_filter']['filters']);
            }

            if(array_key_exists('export_custom_columns_fixed', $this->request->post)) {
                unset($this->request->post['export_custom_columns_fixed']['replace_by_number']);

                if(empty($this->request->post['export_custom_columns_fixed']))
                    unset($this->request->post['export_custom_columns_fixed']);
                else
                    $this->request->post['export_custom_columns_fixed'] = array_values($this->request->post['export_custom_columns_fixed']);
            }

            if(array_key_exists('columns', $this->request->post) && !empty($this->request->post['columns'])) {
                $custom_names = array();
                foreach ($this->request->post['columns'] as $key => $col_info) {
                    if (isset( $col_info['status']) && $col_info['status'] == '1') {
                        if (empty($col_info['custom_name'])) {
                           $this->exception($this->language->get('profile_error_empty_column_custom_name'));
                        } else {
                           array_push($custom_names, $col_info['custom_name']);
                        }
                    }
                }

                $coun_repeats = array_count_values($custom_names);
                foreach ($coun_repeats as $col_name => $number) {
                    if($number > 1)
                        $this->exception(sprintf($this->language->get('profile_error_repeat_column_custom_name'), $number, $col_name));
                }

                /*$option_with_default_value = false;
                $table_option = array('product_option_value');
                $fields_option = array('option_name', 'name');

                foreach ($this->request->post['columns'] as $key => $col_info) {
                    if(array_key_exists('internal_configuration', $col_info) && !empty($col_info['internal_configuration'])) {
                        $internal_configuration = json_decode(str_replace("'", '"', $col_info['internal_configuration']), true);
                        if(array_key_exists('table', $internal_configuration) && in_array($internal_configuration['table'], $table_option) && array_key_exists('field', $internal_configuration) && in_array($internal_configuration['field'], $fields_option) && !empty($col_info['default_value'])) {
                            if(!empty($option_with_default_value))
                                $this->exception(sprintf($this->language->get('profile_error_option_option_value_default_filled'), $option_with_default_value, $col_info['custom_name']));
                            $option_with_default_value = $col_info['custom_name'];
                        }
                    }
                }*/
            }

            if ($profile_type === 'import' &&
                isset( $this->request->post['categories_mapping'])){

                $this->build_categories_mapping();
            }

            if (isset( $this->request->post['columns'])) {
                if ($this->request->post['import_xls_file_format'] == 'xml') {
                    foreach ($this->request->post['columns'] as $key => $col_info) {
                        if (array_key_exists('status', $col_info) && $col_info['status'] && !$this->isValidXmlElementName($col_info['custom_name'])){
                            $this->exception(sprintf($this->language->get('profile_error_xml_custom_columns'), $col_info['custom_name']));
                        }
                    }
                }

                foreach ($this->request->post['columns'] as $key => $col_info) {
                    if(array_key_exists('splitted_values', $col_info) && !empty($col_info['splitted_values']) && !preg_match("/(\>)/s", html_entity_decode($col_info['custom_name'])))
                        $this->exception(sprintf($this->language->get('profile_error_splitted_values'), $col_info['custom_name'], $col_info['custom_name']));
                }
            }

            if (empty( $this->request->post['import_xls_http_authentication'])) {
                $this->request->post['import_xls_http_username'] = '';
                $this->request->post['import_xls_http_password'] = '';
            }

            $config_json = $this->db->escape(json_encode($this->request->post));
            $profile_name = $this->db->escape($profile_name);

            if(!empty($profile_id)) {
                $sql = "UPDATE ".$this->escape_database_table_name('ie_pro_profiles')." SET ".$this->escape_database_field('type')." = ".$this->escape_database_value($profile_type).", ".$this->escape_database_field('name')." = ".$this->escape_database_value($profile_name).", ".$this->escape_database_field('profile')." = '".$config_json."', ".$this->escape_database_field('modified')." = NOW() WHERE id = ".$profile_id;
                $array_return['profile_updated'] = true;
            } else {
                $sql = "INSERT INTO ".$this->escape_database_table_name('ie_pro_profiles')." SET ".$this->escape_database_field('type')." = ".$this->escape_database_value($profile_type).", ".$this->escape_database_field('name')." = ".$this->escape_database_value($profile_name).", ".$this->escape_database_field('profile')." = '".$config_json."', ".$this->escape_database_field('created')." = NOW(), ".$this->escape_database_field('modified')." = NOW();";
            }
            $this->db->query($sql);
            echo json_encode($array_return); die;
        }

        function isValidXmlElementName($elementName)
        {
            $elementName = html_entity_decode($elementName);
            if($this->is_special_xml_name($elementName) && !preg_match('/\s/',$elementName))
                return true;

            $elementName = str_replace('>', '', $elementName);
            $elementName = str_replace(':', '', $elementName);

            try {
                new DOMElement($elementName);
                return true;
            } catch (DOMException $e) {
                return false;
            }
            return false;
        }

        function delete() {
            $this->request->post['no_exit'] = true;
            $this->validate_permiss();
            $profile_id = array_key_exists('profile_id', $this->request->post) ? $this->request->post['profile_id'] : '';
            $array_return = array('error' => false, 'message' => '');
            if(empty($profile_id)) {
                $array_return['error'] = true;
                $array_return['message'] = $this->language->get('profile_error_delete_profile_id_empty');
                echo json_encode($array_return); die;
            }
            $sql = "DELETE FROM ".$this->escape_database_table_name('ie_pro_profiles')." WHERE ".$this->escape_database_field('id')." = ".$this->escape_database_value($profile_id);
            $this->db->query($sql);
            echo json_encode($array_return); die;
        }

        function download() {
            $this->request->post['no_exit'] = true;
            $this->validate_permiss();

            $profile_id = isset( $this->request->post['profile_id']) ? $this->request->post['profile_id'] : '';

            $array_return = [
                'error' => false,
                'message' => ''
            ];

            if (empty( $profile_id)) {
                $this->output_error( $this->language->get('profile_error_profile_id_empty'));
            }

            $table = $this->escape_database_table_name( 'ie_pro_profiles');
            $idField = $this->escape_database_field( 'id');
            $idValue = $this->escape_database_value( $profile_id);

            $sql = "SELECT *
                    FROM {$table}
                    WHERE {$idField} = {$idValue}";

            $result = $this->db->query( $sql);
            $profile = $result->row;

            $profile['profile'] = json_decode( $profile['profile']);

            $content = $this->profile_to_json( $profile);

            $timestamp = date( 'Ymdhis');
            $this->filename = "Profile-{$profile_id}-{$timestamp}.json";

            $fullPath = "{$this->path_tmp}{$this->filename}";

            file_put_contents( $fullPath, $content);

            $array_return['redirect'] = html_entity_decode( $this->url->link( $this->real_extension_type . '/import_xls/download_file', "{$this->token_name}=" . $this->session->data[$this->token_name] . '&filename=' . $this->filename, 'SSL'));

            echo json_encode( $array_return);
            die();
        }

        function upload() {
            $this->request->post['no_exit'] = true;
            $this->validate_permiss();

            $array_return = [
                'error' => false,
                'message' => ''
            ];

            if (!isset( $_FILES['file']) && !isset( $_FILES['file']['tmp_name'])) {
                $this->output_error( $this->language->get( 'profile_upload_data_is_missing'));
            }

            $filename = $_FILES['file']['tmp_name'];
            $json = file_get_contents( $filename);
            $profile = json_decode( $json);

            if (empty( $profile)) {
                $this->output_error( $this->language->get( 'profile_upload_data_format_is_invalid'));
            }

            $this->fix_columns_languages( $profile);

            $profileName = $this->build_unique_profile_name( $profile->name);

            $tableName = $this->escape_database_table_name('ie_pro_profiles');

            $values = [
               $this->escape_database_field('type') => $this->escape_database_value( $profile->type),
               $this->escape_database_field('name') => $this->escape_database_value( $profileName),
               $this->escape_database_field('profile') => '"' . $this->db->escape( json_encode( $profile->profile)) .'"',
               $this->escape_database_field('created') => 'NOW()',
               $this->escape_database_field('modified') => 'NOW()'
            ];

            $valuesList = [];

            foreach ($values as $name => $value) {
                $valuesList[] = "{$name} = {$value}";
            }

            $valuesCsv = join( ',', $valuesList);

            $sql = "INSERT INTO {$tableName}
                    SET {$valuesCsv}";

            $this->db->query( $sql);

            $array_return['profile_id'] = $this->db->getLastId();

            echo json_encode( $array_return);
            die();
        }

        function load($force_id = '', $skip_json_encode = false) {
            $array_return = array('error' => false, 'message' => '');

            $profile_id_ajax = array_key_exists('profile_id', $this->request->post) ? $this->request->post['profile_id'] : '';
            $json_encode = !empty($profile_id_ajax) && !$skip_json_encode;
            $profile_id = !empty($profile_id_ajax) ? $profile_id_ajax : $force_id;

            $sql = "SELECT * FROM ".$this->escape_database_table_name('ie_pro_profiles')." WHERE ".$this->escape_database_field('id')." = ".$this->escape_database_value($profile_id);
            $result = $this->db->query($sql);

            if(empty($result->row)) {
                $array_return['error'] = true;
                $array_return['message'] = $this->language->get('profile_load_error_not_found');
                echo json_encode($array_return); die;
            }

            $result->row['profile'] = json_decode($this->sanitize_value(str_replace('&quot;', '\"', $result->row['profile'])), true);
            $result->row['profile']['profile_id'] = $result->row['id'];
            $result->row['real_type'] = $result->row['type'];

            if($json_encode) {
                $result->row['profile']['columns'] = $this->fix_conditional_values( $result->row['profile']['columns']);

                echo json_encode($result->row); die;
            }
            else return $result->row;
        }

        function get_profiles($type = '', $id = '', $select_format = false) {
            $sql = "SELECT * FROM ".$this->escape_database_table_name('ie_pro_profiles');
            if(!empty($type))
                $sql .= ' WHERE '.$this->escape_database_field('type').' = '.$this->escape_database_value($type);
            if(!empty($id))
                $sql .= ' WHERE '.$this->escape_database_field('id').' = "'.$this->escape_database_value($id);

            $sql .= ' ORDER BY '.$this->escape_database_field('type').' ASC, '.$this->escape_database_field('modified').' ASC';

            $result = $this->db->query( $sql);

            foreach ($result->rows as $key => $profile) {
                if(is_string($profile['profile'])) {
                    $result->rows[$key]['profile'] = json_decode($profile['profile'], true);
                    $result->rows[$key]['simple_name'] = $profile['name'];
                    $result->rows[$key]['name'] = $this->language->get('profile_select_prefix_' . $profile['type']) . ' - [' . $profile['id'] . '] - ' . $profile['name'];
                    $result->rows[$key]['profile']['profile_id'] = $profile['id'];
                }
            }

            $result->rows = $this->sort_profiles( $result->rows);

            if(!$select_format) {
                return $result->rows;
            } else {
                $final_profiles = array('' => $this->language->get('profile_select_text_empty'));
                foreach ($result->rows as $key => $profile) {
                    $final_profiles[$profile['id']] = $profile['name'];
                }
                return $final_profiles;
            }
        }

        public function _check_profiles_table() {
            $this->db->query("CREATE TABLE IF NOT EXISTS ".$this->escape_database_table_name('ie_pro_profiles')." (
              ".$this->escape_database_field('id')." int(11) unsigned NOT NULL AUTO_INCREMENT,
              ".$this->escape_database_field('type')." varchar(100) DEFAULT NULL,
              ".$this->escape_database_field('name')." varchar(360) DEFAULT NULL,
              ".$this->escape_database_field('profile')." MEDIUMTEXT,
              ".$this->escape_database_field('created')." datetime DEFAULT NULL,
              ".$this->escape_database_field('modified')." datetime DEFAULT NULL,
              PRIMARY KEY (".$this->escape_database_field('id').")
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }

        private function build_categories_mapping(){
            $mappings = [];
            $id_mappings = [];

            $categories_mapping_default = $this->request->post['categories_mapping_default'];
            $categories_mappings = $this->request->post['categories_mapping'];
            $categories_id_mappings = $this->request->post['categories_id_mapping'];
            $opencart_mappings = $this->request->post['categories_mapping_opencart'];

            foreach ($categories_mappings as $index => $category_name){
                if (!empty( $category_name)){
                   $mappings[$category_name] = $opencart_mappings[$index];
                }
            }

            foreach ($categories_id_mappings as $index => $category_id){
                if (!empty( $category_id)){
                   $id_mappings[$category_id] = $opencart_mappings[$index];
                }
            }

            unset( $this->request->post['categories_mapping_default']);
            unset( $this->request->post['categories_mapping']);
            unset( $this->request->post['categories_id_mapping']);
            unset( $this->request->post['categories_mapping_opencart']);

            $this->request->post['categories_mapping'] = [
                'default' => $categories_mapping_default,
                'mappings' => $mappings,
                'id_mappings' => $id_mappings
            ];
        }

        private function sort_profiles( $profiles) {
            usort( $profiles, function( $profile1, $profile2) {
                $name1 = strtolower( $profile1['type'] . '-' . $profile1['simple_name']);
                $name2 = strtolower( $profile2['type'] . '-' . $profile2['simple_name']);

                return strcmp( $name1, $name2);
            });

            return $profiles;
        }

        private function profile_to_json( $profile) {
            return json_encode( $profile);
        }

        private function build_unique_profile_name( $profileName) {
            $tableName = $this->escape_database_table_name( 'ie_pro_profiles');
            $nameField = $this->escape_database_field( 'name');

            $sql = "SELECT {$nameField}
                    FROM {$tableName}
                    ORDER BY {$nameField}";

            $queryResult = $this->db->query( $sql);

            $lastNumber = null;

            foreach ($queryResult->rows as $row) {
                $name = $row['name'];

                if ($name === $profileName && $lastNumber === null) {
                    $lastNumber = 1;
                } else {
                    preg_match( '/(.+)\s*\((\d+)\)/', $name, $matches);

                    if (count( $matches) > 2) {
                        $text = $matches[1];
                        $number = +$matches[2];

                        if ($text === $profileName &&
                            ($lastNumber === null || $number > $lastNumber)) {
                            $lastNumber = $number;
                        }
                    }
                }

            }

            $result = $profileName;

            if ($lastNumber !== null) {
                $lastNumber++;

                $result = "{$profileName} ({$lastNumber})";
            }

            return $result;
        }

        private function fix_conditional_values( $columns) {
            $result = [];

            foreach ($columns as $name => $column) {
                if (isset( $column['conditional_value']) && !empty( $column['conditional_value'])) {
                   $column['conditional_value'] = $this->escape_double_quotes( $column['conditional_value']);
                }

                $result[$name] = $column;
            }

            return $result;
        }

        private function fix_columns_languages( &$profile) {
            $language_ids = $this->get_language_ids();
            $columns = $profile->profile->columns;

            foreach ($columns as &$column) {
                $internal_config = json_decode( str_replace( "'", '"', $column->internal_configuration), true);

                if (isset( $internal_config['language_id']) &&
                    !in_array( $internal_config['language_id'], $language_ids)) {
                    $internal_config['language_id'] = $this->default_language_id;
                }

                if (isset( $internal_config['conditions'])) {
                    foreach ($internal_config['conditions'] as &$condition) {
                        if (preg_match( '/language_id\s*=\s*(\d+)/', $condition, $matches) === 1) {
                            $language_id = $matches[1];

                            if (!in_array( $language_id, $language_ids)) {
                                $condition = "language_id = {$this->default_language_id}";
                            }
                        }
                    }
                }

                $column->internal_configuration = str_replace( '"', "'", json_encode( $internal_config));
            }

            $profile->profile->columns = $columns;
        }

        private function get_language_ids() {
            $this->load->model( 'localisation/language');

            return array_values( array_map( function( $language) {
                return $language['language_id'];
            }, $this->model_localisation_language->getLanguages()));
        }

        private function escape_double_quotes( $text) {
            return preg_replace( '/"/', '&quot;', $text);
        }

        private function output_error( $errorMessage){
            $array_return = [
                'error' => true,
                'message' => $errorMessage
            ];

            echo json_encode( $array_return);
            die();
        }
    }
?>
