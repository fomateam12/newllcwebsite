<?php
    class ModelExtensionModuleIeProDownloads extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'download';
            $this->main_field = 'download_id';

            $this->delete_tables = array(
                'download_description',
                'product_to_download',
            );

            $special_tables_description = array('download_description');
            $delete_tables = array('download_description');
            parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
        }

        public function get_all_downloads_export_format() {
            $sql = 'SELECT * FROM '.$this->escape_database_table_name('download').' dw LEFT JOIN '.$this->escape_database_table_name('download_description').' dwd ON(dw.`download_id` = dwd.`download_id`)';

            $results = $this->db->query($sql);

            $downloads = array();
            foreach ($results->rows as $key => $download_info) {
                $download_id = $download_info['download_id'];
                $lang_id = $download_info['language_id'];

                if(!array_key_exists($download_id, $downloads)) {
                    $explode_filename = explode('.', $download_info['filename']);

                    if(count($explode_filename) > 1) {
                        $downloads[$download_id] = array(
                            'name' => array(),
                            'filename' => $explode_filename[0] . '.' . $explode_filename[1],
                            'hash' => array_key_exists(2,$explode_filename) ? $explode_filename[2] : '',
                            'mask' => $download_info['mask'],
                        );
                    }
                }

                $downloads[$download_id]['name'][$lang_id] = $download_info['name'];
            }

            return $downloads;
        }

        function get_all_downloads_import_format() {
            $final_downloads = array();
            $export_format = $this->get_all_downloads_export_format();

            foreach ($export_format as $download_id => $download) {
                foreach ($download['name'] as $lang_id => $name) {
                    $name = $this->sanitize_value($name);
                    $final_downloads[$name.'_'.$lang_id] = $download_id;
                }
            }
            return $final_downloads;
        }
        
        function create_downloads_from_product($data_file) {
            $all_downloads = $this->get_all_downloads_import_format();
            $this->update_process($this->language->get('progress_import_from_product_creating_downloads'));
            $this->update_process(sprintf($this->language->get('progress_import_from_product_created_downloads'), 0));
            $created = 0;
            foreach ($data_file as $key => $data) {
                $elements = array_key_exists('product_to_download', $data) ? $data['product_to_download'] : array();
                foreach ($elements as $key => $element) {
                    $names = $element['name'];
                    $found = $some_with_name = false;
                    foreach ($names as $lang_id => $name) {
                        if(!empty($name)) {
                            $some_with_name = true;
                            if(array_key_exists($name.'_'.$lang_id, $all_downloads)) {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if(!$found && $some_with_name) {
                        $data_temp = array(
                            'name' => $names,
                            'filename' => array_key_exists('filename', $element) ? $element['filename'] : '',
                            'hash' => array_key_exists('hash', $element) ? $element['hash'] : '',
                            'mask' => array_key_exists('mask', $element) ? $element['mask'] : '',
                        );
                        $download_id = $this->create_simple_download($data_temp);
                        $created++;
                        $this->update_process(sprintf($this->language->get('progress_import_from_product_created_downloads'), $created), true);
                        $all_downloads = $this->get_all_downloads_import_format();
                    }
                }
            }
        }

        public function create_simple_download($data) {
            $sql = "INSERT INTO ".$this->escape_database_table_name('download')." SET ".
                $this->escape_database_field('filename')." = ".$this->escape_database_value($data['filename']).", ".
                $this->escape_database_field('mask')." = ".$this->escape_database_value($data['mask']).", ".
                $this->escape_database_field('date_added')." = NOW()";

            $this->db->query($sql);
            
            $download_id = $this->db->getLastId();

            foreach ($data['name'] as $language_id => $name) {
                $this->db->query("INSERT INTO ".$this->escape_database_table_name('download_description')." SET ".$this->escape_database_field('download_id')." = ".$this->escape_database_value($download_id).", ".$this->escape_database_field('language_id')." = ".$this->escape_database_value($language_id).", ".$this->escape_database_field('name')." = " . $this->escape_database_value($name));
            }

            return $download_id;
        }
    }
?>