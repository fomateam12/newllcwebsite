<?php
    class XmlMainNodesAnalyzer extends IeProProfileObject {
        /**
         * @var XmlData
         */
        private $xml_data;

        /**
         * @var array
         */
        private $main_nodes;

        public function execute() {
            $this->xml_data = XmlDataLoader::get_xml_data( $this->controller);
            $this->main_nodes = $this->find_main_nodes( $this->xml_data->to_array(), '');
        }

        public function get_result() {
            return $this->controller->as_select_format( $this->main_nodes);
        }

        private function find_main_nodes( $node, $path) {
            $result = [];

            if ($this->xml_data->node_has_id_attribute( $node)) {
                $result[] = $path;
            } else {
                if (ArrayTools::is_simple_array( $node)) {
                    $result[] = $path;
                }
                else {
                    foreach ($node as $key => $value) {
                        if (is_array( $value)) {
                            $subPath = $path !== '' ? $path . '>' . $key : $key;

                            if (!empty( $path)) {
                                $result[] = $path;
                            }

                            $result = array_merge(
                                $result,
                                $this->find_main_nodes( $value, $subPath)
                            );
                        }
                    }
                }
            }

            return $result;
        }
    }
