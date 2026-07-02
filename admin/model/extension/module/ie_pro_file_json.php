<?php
    class ModelExtensionModuleIeProFileJson extends ModelExtensionModuleIeProFile {
        public function __construct($registry){
            parent::__construct($registry);
        }
        function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;
        }
        function insert_columns($columns) {

        }

        function insert_data($columns, $elements) {

            $elements_to_insert = count($elements);
            $message = sprintf($this->language->get('progress_export_elements_inserted'), 0, $elements_to_insert);
            $this->update_process($message);

            $arrayElements = array();
            $count = 0;
            foreach ($elements as $element_id => $element) {
                $temp = array();
                foreach ($columns as $col_name => $col_info) {
                    $custom_name = $col_info['custom_name'];
                    $temp[$custom_name] = array_key_exists($custom_name, $element) ? $element[$custom_name] : '';
                }
                $arrayElements[] = $temp;
                $count++;
                $message = sprintf($this->language->get('progress_export_elements_inserted'), $count, $elements_to_insert);
                $this->update_process($message, true);
            }

            $fp = fopen($this->filename_path, 'w');
            fwrite($fp, json_encode($arrayElements));
            fclose($fp);
        }

        function insert_data_multisheet($data) {

        }

        function get_data() {

            $jsonData = file_get_contents($this->file_tmp_path);
            $rows = json_decode($jsonData, true);

            if(!empty($this->profile['import_xls_json_main_node'])) {
                $node_children = explode('>', trim($this->profile['import_xls_json_main_node']));
                $final_data = array();
                foreach ($node_children as $node_name) {
                    if(!empty($rows[$node_name]))
                        $rows = $rows[$node_name];
                    else
                         $this->exception(sprintf($this->language->get('import_xls_json_main_node_not_found'), $node_name));
                }
            }

            $final_excel = array(
                'columns' => array(),
                'data' => array(),
            );

            foreach($rows as $key => $row) {
                foreach($row as $key => $row_value) {
                    $final_excel['columns'][] = $key;
                }
                // iter only once
                break;
            }            

            foreach($rows as $iter => $row) {
                $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $iter+1), true);

                if (!empty(array_filter($row))) {
                    foreach($row as $key => $row_value) {
                        if (is_a($row_value, 'DateTime')) {
                            $temp = $row_value->format('Y-m-d');
                            $row[$key] = $temp;
                        }
                    }
                    $final_excel['data'][] = $row;
                }
            }

            return $final_excel;
        }

        public function get_data_multisheet() {
            return NULL;
        }
    }
?>