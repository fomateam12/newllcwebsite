<?php
    class ColumnsAnalyzer extends IeProProfileObject {
        /**
         * @var array
         */
        private $columns = [];

        private $profile;

        public function execute() {
            if ($this->should_analyze_columns()) {
                $this->do_execute();
            }
        }

        public function get_result() {
            return $this->controller->as_select_format( $this->columns);
        }

        public function get_columns_from_xml_data( $only_first_node = false) {
            list($columns,) = $this->get_columns_from_xml_full_data( $only_first_node);

            return $columns;
        }

        public function get_columns_from_xml_full_data( $only_first_node = false) {
            $result = [];

            $mainNodePath = $this->parameters->get_strict(
                'import_xls_node_xml',
                'Missing parameter: import_xls_node_xml'
            );

            $xmlData = XmlDataLoader::get_xml_data( $this->controller);
            $mainNodeName = $xmlData->get_path_node_name( $mainNodePath);

            $nodes = $xmlData->get_nodes_from_path( $mainNodePath);

            foreach ($nodes as $node) {
                if (ArrayTools::is_simple_array( $node)) {
                    $firstNode = $node[0];

                    if (!$xmlData->node_has_children( $firstNode)) {
                        $result[] = $mainNodeName;

                        if ($xmlData->node_has_id_attribute( $firstNode)) {
                            $result[] = "{$mainNodeName}@id";
                        }

                        foreach (array_keys( $firstNode) as $columnName) {
                            $result[] = $columnName;
                        }
                    }
                } else {
                    $result = array_merge( $result, $this->get_attribute_columns( $node, $mainNodeName));

                    foreach ($node as $property => $value) {
                        if ($property !== '@attributes') {
                            if (is_array( $value)) {
                                foreach ($value as $index => $propValue) {
                                    if (is_array( $propValue)) {
                                        if (isset( $propValue['@attributes'])) {
                                            $propPath = "{$property}>{$index}>";
                                            $result = array_merge( $result, $this->get_attribute_columns( $propValue, $propPath));
                                        }
                                        else {
                                            foreach ($propValue as $subIndex => $innerValue) {
                                                $result[] = "{$property}>{$index}>{$subIndex}";

                                                $propPath = "{$property}>{$index}>{$subIndex}>";
                                                $result = array_merge( $result, $this->get_attribute_columns( $innerValue, $propPath));
                                            }
                                        }
                                    }
                                    else {
                                        $result[] = "{$property}>{$index}";
                                        $result[] = $property;
                                    }
                                }
                            }
                            else {
                                $result[] = $property;
                            }
                        }
                    }
                }

                if ($only_first_node) {
                    break;
                }
            }

            $result = array_unique( $result);

            return [$result, $xmlData];
        }

        private function do_execute() {
            $this->check_file_upload_if_needed();

            $this->model_loader->load_file_model();

            $this->profile_manager->build_fake();
            $format = $this->parameters->get( 'import_xls_file_format');

            if ($format === 'xml') {
                $this->columns = $this->get_columns_from_xml_data();
            } else {
                $this->columns = $this->get_columns_from_non_xml_data( $format);
            }
        }

        private function should_analyze_columns() {
            return $this->parameters->get( 'import_xls_analyze_columns') == '1';
        }

        private function get_columns_from_non_xml_data( $format) {
            if ($format !== 'spreadsheet') {
                $this->controller->model_extension_module_ie_pro_file->upload_file_import();
            }

            $model_name = $this->model_loader->get_file_model_name();
            $result = $this->controller->{$model_name}->get_data();

            if ($format === 'csv') {
                $result = $result['columns'];
                $separator = $this->parameters->get( 'import_xls_csv_separator', ',');

                if ($separator === '') {
                    $separator = ',';
                }

                if (strpos( $result[0], $separator) !== false) {
                    $result = $result[0];
                    $result = preg_split( "/{$separator}/", $result);
                    $result = array_map( [$this, 'unquote'], $result);
                    $result = array_unique( $result);
                }
            } else {
                if (isset( $result['columns'])) {
                    $result = $result['columns'];
                    $result = array_unique( $result);
                }
            }

            return $result;
        }

        private function check_file_upload_if_needed() {
            $error = null;

            // En un profile Import, chequeamos si el modo es un upload manual
            // y hay un file upload
            if ($this->profile_manager->get_type() === 'import') {
                $error = $this->profile_manager->get_file_origin() === 'manual' &&
                         !$this->parameters->has_file_upload();
            } else {
                // En un profile Export SIEMPRE debe haber un file upload
                $error = !$this->parameters->has_file_upload();
            }

            if ($error) {
                throw new \Exception( $this->language->get( 'profile_import_error_expected_file_upload_columns_mapping'));
            }
        }

        private function get_attribute_columns( $node, $path) {
            $result = [];

            if (is_array( $node)) {
                if (isset( $node['@attributes'])) {
                    foreach (array_keys( $node['@attributes']) as $attribute) {
                        $result[] = "{$path}@{$attribute}";
                    }
                } else {
                    if ($path[strlen( $path) - 1] !== '>') {
                        $path .= '>';
                    }

                    foreach (array_keys( $node) as $attribute) {
                        $result[] = "{$path}{$attribute}";
                    }
                }
            }

            return $result;
        }

        private function unquote( $text) {
            $result = $text;
            $len = strlen( $result);

            if ($len > 0) {
                $quotes = ["'", '"', '`'];

                if ($result[0] == $result[$len - 1] &&
                    in_array( $result[0], $quotes)) {
                    $result = substr( $result, 1, $len - 2);
                }
            }

            return $result;
        }
    }
