<?php
    class XmlData extends IeProProfileObject {
        /**
         * @var array
         */
        private $xml_array;

        public function __construct( $controller, $xml_array) {
            parent::__construct( $controller);

            $this->xml_array = $xml_array;
        }

        public function to_array() {
            return $this->xml_array;
        }

        public function get_nodes_from_path( $path) {
            $result = $this->xml_array;

            $segments = $this->get_path_segments( $path);

            foreach ($segments as $segment) {
                if (!isset( $result[$segment])) {
                    $this->output_error( $this->language->get( 'profile_import_error_invalid_xml_main_node'));
                }

                $result = $result[$segment];
            }

            if (!ArrayTools::is_simple_array( $result)) {
                $result = [$result];
            }

            return $result;
        }

        public function strip_node_name_from_attribute( $attributeName, $nodeName, $displayName) {
            $result = null;

            if (!empty( $attributeName)) {
                $parts = preg_split( '/@/', $attributeName);

                if (count( $parts) !== 2 || $parts[0] !== $nodeName) {
                    throw new \Exception( $this->language->get( 'profile_import_error_categories_xml_invalid_attribute_name') . ' ' . $displayName);
                }

                $result = $parts[1];
            }

            return $result;
        }

        public function get_path_node_name( $path) {
            $segments = $this->get_path_segments( $path);

            return $segments[count( $segments) - 1];
        }

        public function get_path_segments( $path) {
            $path = preg_replace( '/&gt;/', '>', $path);

            return preg_split( '/\d*\>\d*/', $path);
        }

        public function node_has_children( $node) {
            foreach ($node as $property => $value) {
                if (is_array( $value)) {
                    return true;
                }
            }

            return false;
        }

        public function node_has_id_attribute( $node) {
            return isset( $node['@attributes']) && isset( $node['@attributes']['id']);
        }

        public function get_property_value( $node, $property_name) {
            if ($this->is_path_property( $property_name)) {
                $result = $this->get_path_property_value( $node, $property_name);
            } else {
                $result = $this->get_simple_property_value( $node, $property_name);
            }

            return $result;
        }

        private function get_simple_property_value( $node, $property_name) {
            $result = $this->get_any_property_value( $node, $property_name);

            if (is_array( $result)) {
                throw new \Exception( "Expected simple attribute but array found on column: " . $property_name);
            }

            return $result;
        }

        private function get_any_property_value( $node, $property_name) {
            if (isset( $node[$property_name])) {
                 $result = $node[$property_name];

            } else if (isset( $node['@attributes']) &&
                       isset( $node['@attributes'][$property_name])) {
                $result = $node['@attributes'][$property_name];
            }

            return $result;
        }

        private function get_path_property_value( $node, $property_name) {
            $parts = preg_split( '/>/', $property_name);
            $result = $this->get_any_property_value( $node, $parts[0]);

            if (count( $parts) > 1) {
                if (!is_array( $result)) {
                    return null;
                }

                for ($i = 1; $i < count( $parts); $i++) {
                    if (!is_array( $result)) {
                        throw new \Exception( "Invalid column name: " . $property_name);
                    }

                    $part = $parts[$i];

                    if (!isset( $result[$part])) {
                        // En este punto, detenemos porque se esta buscando un item nodo>N,
                        // donde N no existe en el XML -> se ignora
                        $result = null;
                        break;
                    } else {
                        $result = $result[$part];
                    }
                }

                if (is_array( $result)) {
                    throw new \Exception(
                        "Expected simple attribute but array found on column: " . $property_name
                    );
                }
            }

            return $result;
        }

        private function is_path_property( $property_name) {
            return strpos( $property_name, '>') !== false;
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
