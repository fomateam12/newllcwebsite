<?php
    class ModelExtensionModuleIeProImages extends ModelExtensionModuleIePro {
        public function __construct($registry)
        {
            parent::__construct($registry);
            $this->cat_name = 'images';
        }

        public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
            $this->main_table = 'product_image';
            $this->main_field = 'product_image_id';

            parent::set_model_tables_and_fields($special_tables, $special_tables_description);
        }

        public function pre_import($data_file)
        {
            $product_counter = array();
            foreach ($data_file as $key => $data) {
                if(!empty($data['product_image']['image']) && $this->is_url($data['product_image']['image']) && !empty($data['product_image']['product_id'])) {
                    $prod_id = $data['product_image']['product_id'];
                    if(!array_key_exists($prod_id, $product_counter))
                        $product_counter[$prod_id] = 0;
                    $data_file[$key]['product_image']['image'] = $this->download_remote_image('product_image', $data['product_image']['product_id'], $product_counter[$prod_id], $data['product_image']['image']);
                    $product_counter[$prod_id]++;
                }
            }
            return parent::pre_import($data_file);
        }

        public function get_columns($configuration = array()) {
            $columns = parent::get_columns($configuration);
            return $columns;
        }

        function get_columns_formatted($multilanguage) {
            $columns = array(
                'Product image id' => array('hidden_fields' => array('table' => 'product_image', 'field' => 'product_image_id')),
                'Product id' => array('hidden_fields' => array('table' => 'product_image', 'field' => 'product_id'), 'product_id_identificator' => 'product_id'),
                'Image' => array('hidden_fields' => array('table' => 'product_image', 'field' => 'image')),
                'Sort order' => array('hidden_fields' => array('table' => 'product_image', 'field' => 'sort_order')),
                'Deleted' => array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true)),
            );
            $columns = parent::put_type_to_columns_formatted($columns, $multilanguage);
            return $columns;
        }
    }
?>