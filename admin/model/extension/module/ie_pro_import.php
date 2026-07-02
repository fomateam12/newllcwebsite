<?php

/**
 * Class ModelExtensionModuleIeProImport
 */
class ModelExtensionModuleIeProImport extends ModelExtensionModuleIePro {
    public function __construct($registry){
        parent::__construct($registry);
    }

    public function import($profile) {
        $profile_data = $profile['profile'];
        $this->profile = $profile_data;
        $this->multilanguage = array_key_exists('import_xls_multilanguage', $profile_data) && $profile_data['import_xls_multilanguage'];
        $this->cat_tree =  array_key_exists('import_xls_category_tree', $this->profile) && $this->profile['import_xls_category_tree'];
        $this->product_identificator = array_key_exists('import_xls_product_identificator', $this->profile) ? $this->profile['import_xls_product_identificator'] : 'product_id';
        $this->skip_image_download = array_key_exists('import_xls_skip_existing_images', $this->profile) && $this->profile['import_xls_skip_existing_images'];
        $this->skip_on_edit = array_key_exists('import_xls_existing_products', $this->profile) && $this->profile['import_xls_existing_products'] == 'skip';
        $this->skip_on_create = array_key_exists('import_xls_new_products', $this->profile) && $this->profile['import_xls_new_products'] == 'skip';
        $this->last_cat_assign = array_key_exists('import_xls_category_tree_last_child', $this->profile) && $this->profile['import_xls_category_tree_last_child'];
        $this->sum_tax_price_on_create =  array_key_exists('import_xls_sum_tax', $this->profile) && $this->profile['import_xls_sum_tax'];
        $this->rest_tax_price_on_create =  array_key_exists('import_xls_rest_tax', $this->profile) && $this->profile['import_xls_rest_tax'];
        $this->price_tax_operation = $this->sum_tax_price_on_create ? 'sum' : ($this->rest_tax_price_on_create ? 'rest' : '');
        $this->strict_update =  $this->profile['import_xls_i_want'] == 'products' && array_key_exists('import_xls_strict_update', $this->profile) && $this->profile['import_xls_strict_update'];

        $this->no_image_path = version_compare(VERSION, '2', '<') ?'no_image.jpg' : 'no_image.png';

        $version = $this->db->query("SELECT VERSION() as mysql_version");
        $this->mysql_buggy = !empty($version->row['mysql_version']) && version_compare($version->row['mysql_version'], '8.0.30', '>=');


        // Cuando está activo no_check_duplicates también chequeamos
        // que el product identificator NO PUEDE SER product_id
        $this->no_check_duplicates = $this->profile['import_xls_i_want'] == 'products' &&
            isset( $this->profile['import_xls_no_check_duplicates']) &&
            $this->profile['import_xls_no_check_duplicates'] &&
            $this->profile['import_xls_product_identificator'] !== 'product_id';

        $this->auto_seo_generator = array_key_exists('import_xls_autoseo_gerator', $this->profile) ? $this->profile['import_xls_autoseo_gerator'] : false;
        $this->load->model('extension/module/ie_pro_database');
        $this->conditional_fields = $this->model_extension_module_ie_pro_database->get_tables_conditional_fields();
        $this->fields_without_main_conditional = $this->model_extension_module_ie_pro_database->get_tables_fields_main_conditional_remove();
        $this->load->model('extension/module/ie_pro_file');
        $this->filename = $this->model_extension_module_ie_pro_file->get_filename();
        $this->file_tmp_path = $this->path_tmp.$this->filename;

        $this->check_download_image_path();

        //<editor-fold desc="Values to conversions">
        $this->load->model('extension/module/ie_pro_manufacturers');
        $this->all_manufacturers_import = $this->model_extension_module_ie_pro_manufacturers->get_all_manufacturers_import_format();
        $this->stock_statuses_import = $this->get_stock_statuses(true);
        $this->tax_classes_import = $this->get_tax_classes(true);
        $this->weight_classes_import = $this->get_classes_weight(true);
        $this->length_classes_import = $this->get_classes_length(true);
        $this->layouts_import = $this->get_layouts(true);
        //</editor-fold>

        $columns = $this->clean_columns($profile_data['columns']);
        $this->columns = $columns;
        $this->conversion_fields = $this->get_conversion_fields($this->columns);
        $this->splitted_values_fields = $this->get_splitted_values_fields($this->columns);
        $this->custom_columns = $this->get_custom_columns($this->columns);
        $this->conditional_values = $this->get_conditional_values($this->custom_columns);

         //Get all images array
        $images_path = DIR_IMAGE.$this->image_path.$this->extra_image_route;
        $this->all_images = array_diff(scandir($images_path), array('.', '..'));

        $this->related_identificator = array_key_exists('Products related', $this->columns) && array_key_exists('product_id_identificator', $this->columns['Products related']) && !empty($this->columns['Products related']['product_id_identificator']) ? $this->columns['Products related']['product_id_identificator'] : 'model';
        $tables_info = $this->get_tables_info($this->columns);
        $this->tables_info = $tables_info;

        $elements_to_import = $profile['profile']['import_xls_i_want'];
        $this->elements_to_import = $elements_to_import;
        if(in_array($elements_to_import, array('specials', 'discounts', 'images'))) {
            $this->load->model('extension/module/ie_pro_products');
            $this->load->model('extension/module/ie_pro_customer_groups');
            $this->all_customer_groups = $this->model_extension_module_ie_pro_customer_groups->get_all_customer_groups(true, true);
        }

        $model_name = 'ie_pro_'.$elements_to_import;
        $model_path = 'extension/module/'.$model_name;
        $model_loaded = 'model_extension_module_'.$model_name;
        $this->model_loaded = $model_loaded;
        $this->load->model($model_path);
        $this->{$model_loaded}->set_model_tables_and_fields();

        $format = $this->profile['import_xls_file_format'];
        $model_path = 'extension/module/ie_pro_file_'.$format;
        $model_name = 'model_extension_module_ie_pro_file_'.$format;
        $this->load->model('extension/module/ie_pro_file');
        $this->load->model($model_path);

        if($format != 'spreadsheet')
            $this->model_extension_module_ie_pro_file->upload_file_import();

        $data_file = $this->{$model_name}->get_data();

        if(is_file($this->assets_path.'model_ie_pro_import_just_after_get_file_data.php'))
            require_once($this->assets_path.'model_ie_pro_import_just_after_get_file_data.php');

        $this->is_xml = $this->profile['import_xls_file_format'] == 'xml';
        $this->has_options = $this->elements_to_import == 'products' ? $this->model_extension_module_ie_pro_products->check_file_data_has_options($data_file, !$this->is_xml) : false;
        $this->options_columns = $this->elements_to_import == 'products' ? $this->model_extension_module_ie_pro_products->check_file_option_column_keys($data_file, true) : array();

        $data_file = $this->apply_filters($data_file);

        if(empty($data_file['data'])) {
            $data = array(
                'status' => 'error',
                'message' => $this->language->get('progress_import_error_skipped_all_elements')
            );
            $this->update_process($data);
            return false;
        }

        $this->has_main_field_column = $this->check_main_field_column($data_file['columns']);
        $data_file = $this->insert_lost_columns_in_get_data($data_file);

        if(is_file($this->assets_path.'model_ie_pro_import_after_get_file_data.php'))
            require_once($this->assets_path.'model_ie_pro_import_after_get_file_data.php');

        if($this->splitted_values_fields != '')
            $data_file = $this->add_splitted_values($data_file);

        if (isset( $profile['profile']['categories_mapping'])){
            $columnsIdInsteadOfName = $this->get_columns_with_id_instead_of_name(
                $profile['profile']['columns']
            );

            $data_file = $this->apply_categories_mapping(
                $data_file,
                $profile['profile']['categories_mapping'],
                $columnsIdInsteadOfName
            );

            $this->all_categories_mapped = $this->are_all_categories_mapped( $profile['profile']);
        }

        if($this->is_t && !empty($data_file['data']))
            $data_file['data'] = array_slice($data_file['data'], 0, $this->is_t_elem);

        $this->check_columns($data_file['columns']);
        $data_file = $this->remove_unnecesary_columns($data_file);
        $data_file = $this->assign_default_values_to_lost_columns($data_file);

        if($this->conditional_values != '')
            $data_file = $this->insert_conditional_values($data_file);

        if(is_file($this->assets_path.'model_ie_pro_import_after_insert_conditional_values.php'))
            require_once($this->assets_path.'model_ie_pro_import_after_insert_conditional_values.php');

        $data_file = $this->format_data_file($data_file);
        $data_file = $this->{$model_loaded}->pre_import($data_file);

        if(empty($data_file))
            $this->exception($this->language->get('progress_import_error_empty_data'));

        if(in_array($elements_to_import, array('products','categories','manufacturers')))
            $data_file = $this->{'model_extension_module_ie_pro_'.$elements_to_import}->_importing_assign_default_store_and_languages_in_creation($data_file);

        if (!$this->no_check_duplicates)
            $this->check_duplicated_product_ids( $data_file);

        if($elements_to_import == 'product_option_values') {
            $data_file = $this->{'model_extension_module_ie_pro_'.$elements_to_import}->import_create_asociations($data_file);

            $this->load->model('extension/module/ie_pro_options');
            $this->options_import = $this->model_extension_module_ie_pro_options->get_all_options_import_format(true);

            $this->load->model('extension/module/ie_pro_option_values');
            $this->option_values_import = $this->model_extension_module_ie_pro_option_values->get_all_option_values_import_format(true);
        } else if($elements_to_import == 'option_values') {
            $this->load->model('extension/module/ie_pro_options');
            $this->all_options_import = $this->model_extension_module_ie_pro_options->get_all_options_import_format(true);
        }
        //Call function to translate boolean values, names instead of ids....
        $data_file = $this->conversion_values($data_file);

        if(is_file($this->assets_path.'model_ie_pro_import_just_before_call_import_data_function.php'))
            require_once($this->assets_path.'model_ie_pro_import_just_before_call_import_data_function.php');

        list($elements_created, $elements_edited, $elements_deleted) = $this->import_data($data_file);

        if (
            $elements_to_import === 'products' &&
            $this->config->get('config_opt_comb_status') &&
            $this->config->get('config_opt_comb_combinations_as_products')
        ){
            $this->update_process(sprintf($this->language->get('progress_import_updating_combinations_as_products_index'), '<i class="fa fa-coffee"></i>'));
            $this->load->model('extension/module/options_combinations');
            $this->model_extension_module_options_combinations->rebuild_combination_as_product_index();
        }

        if(is_file($this->assets_path.'model_ie_pro_import_just_after_call_import_data_function.php'))
            require_once($this->assets_path.'model_ie_pro_import_just_after_call_import_data_function.php');

        $this->update_process([
            'status' => 'progress_import_import_finished',
            'message' => sprintf(
                $this->language->get('progress_import_finished'),
                '<i class="fa fa-check"></i>&nbsp;&nbsp;&nbsp;',
                $elements_created,
                $elements_edited,
                $elements_deleted
            )
        ]);

        $this->ajax_die('progress_import_import_finished');
    }

    public function apply_filters($data_file){
        $this->update_process($this->language->get('progress_import_applying_pre_filters') . '...');
        $profile = $this->profile;
        $filters = array_key_exists('export_filter', $profile) && array_key_exists('filters', $profile['export_filter']) && !empty($profile['export_filter']['filters']) ? $profile['export_filter']['filters'] : array();

        if(empty($filters)) return $data_file;

        $filters = $this->format_filters($filters);
        $element_type_to_import = $profile['import_xls_i_want'];
        $final_data_file = ['columns' => $data_file['columns'], 'data' => []];

        if($element_type_to_import == 'products'){
            //applying file filters
            $final_data_file = $this->apply_products_file_filters($data_file, $final_data_file, $filters['file']);

            //applying shop filters
            $this->apply_products_shop_filters($filters['shop']);

        }else{
            $final_data_file = $this->apply_file_filters($data_file, $final_data_file, $filters['file']);
            //applying shop filters
            $this->apply_shop_filters($filters['shop']);
        }
        return $final_data_file;
    }

    private function apply_file_filters($data_file, $final_data_file, $filters){
        $this->update_process($this->language->get('progress_import_applying_file_filters') . '...');
        $actions_count = array('skipped' => 0, 'deleted' => 0, 'disabled' => 0, 'set_0' => 0);
        foreach ($data_file['data'] as $element){
            $action = $this->evaluate_file_filters($element, $filters, $data_file['columns']);
            switch ($action) {
                case 'none':
                    $final_data_file['data'][] = $element;
                    break;
                case 'skip':
                    $actions_count['skipped']++;
                    break;
               /* case 'delete':
                    $this->{$this->model_loaded}->delete_element($element[0]);
                    $actions_count['deleted']++;
                    $final_data_file['data'][] = $element;
                break;
                case 'set_0':
                    $this->{$this->model_loaded}->set_quantity_0($element[0]);
                    $actions_count['set_0']++;
                break;*/
            }
        }
        $this->print_filters_results($actions_count);
        return $final_data_file;
    }

    private function apply_products_shop_filters($filters){
        $this->update_process($this->language->get('progress_import_applying_shop_filters') . '...');
        $actions_count = array('skipped' => 0, 'deleted' => 0, 'disabled' => 0, 'set_0' => 0);

        $profile_copy = $this->profile;
        if(!empty($profile_copy['export_filter']['filters'])) {

            //Get only shop filters from profile
                $shop_filters = array();
                foreach ($profile_copy['export_filter']['filters'] as $filter) {
                    if($filter['applyto'] == 'shop') {
                        $shop_filters[] = $filter;
                    }
                }
            $elements_skipped = array();
            //Apply shop filters
            if(!empty($shop_filters)) {
                $this->load->model('extension/module/ie_pro_export');
                foreach ($shop_filters as $shop_filter) {
                    $profile_copy = $this->profile;
                    $profile_copy['export_filter']['filters'] = array($shop_filter);

                    $profile_copy = array('profile' => $profile_copy, 'real_type' => 'export');
                    $result = $this->model_extension_module_ie_pro_export->get_elements_id($profile_copy);

                    if(!empty($result)) {
                        if ($shop_filter['action'] == 'disable') {

                            //Skip disable for skipped products.
                            if(!empty($elements_skipped)) {
                                foreach ($result as $key => $prod_id) {
                                    if(in_array($prod_id, $elements_skipped))
                                        unset($result[$key]);
                                }
                                $result = array_values($result);
                            }

                            $sql = "UPDATE  {$this->escape_database_table_name('product')}  SET status=0  WHERE {$this->escape_database_field('product_id')} IN (" . implode(',', $result) . ")";
                            $this->db->query($sql);
                            $actions_count['disabled'] += count($result);
                        } else if ($shop_filter['action'] == 'set_0') {
                            $sql = "UPDATE  {$this->escape_database_table_name('product')}  SET quantity=0  WHERE {$this->escape_database_field('product_id')} IN (" . implode(',', $result) . ")";
                            $this->db->query($sql);
                            $actions_count['set_0'] += count($result);
                        } else if ($shop_filter['action'] == 'delete') {
                            foreach ($result as $key => $prod_id) {
                                //Skip delete product if was disabled.
                                if(!in_array($prod_id, $elements_skipped)) {
                                    $this->{$this->model_loaded}->delete_element($prod_id);
                                    $actions_count['deleted']++;
                                }
                            }
                        } else if ($shop_filter['action'] == 'skip') {
                            foreach ($result as $key => $prod_id) {
                                $elements_skipped[] = $prod_id;
                                $actions_count['skipped']++;
                            }
                        }
                    }
                }
            }

            $this->pre_filters_skip_elements_shop = $elements_skipped;
        }

        $this->print_filters_results($actions_count);
    }

    private function apply_shop_filters($filters){
        $this->update_process($this->language->get('progress_import_applying_shop_filters') . '...');

        $actions_count = array('skipped' => 0, 'deleted' => 0, 'disabled' => 0, "set_0" => 0);

        foreach ($filters as $filter) {
            $result = $this->db->query("SELECT * FROM {$this->escape_database_table_name($filter['table'])}");

            //IMPORTANT: This array will store all products ids that have matched with a filter in oreder to apply the filter only one time. In the futute
            //maybe this is not necessary. The main objective here is to have a correspondence with the file filters.
            $filtered_ids = array();

            if ($result->num_rows > 0){
                $elements = $result->rows;
                foreach ($elements as $element){
                    if (!in_array($element[$this->main_field], $filtered_ids) && $this->evaluate_filter($filter, $element[$filter['field']])){
                        switch ($filter['action']) {
                            case 'delete':
                                $this->{$this->model_loaded}->delete_element($element[$this->main_field]);
                                $actions_count['deleted']++;
                                break;
                            case 'disable':
                                $this->{$this->model_loaded}->disable_element($element[$this->main_field]);
                                $actions_count['disabled']++;
                                break;
                        }
                        $filtered_ids[] = $element[$this->main_field];
                    }
                }
            }
        }

        $this->print_filters_results($actions_count);
    }

    private function apply_products_file_filters($data_file, $final_data_file, $filters){
        $this->update_process($this->language->get('progress_import_applying_file_filters') . '...');

        $product_options_map = $this->map_to_product_options_array($data_file);

        $product_id_index = $column_product_identificator = '';
        foreach ($this->custom_columns as $key => $col_info) {
            if(array_key_exists('field', $col_info) && $col_info['field'] == $this->product_identificator)
                $column_product_identificator = $key;
        }
        $product_id_index = array_search($column_product_identificator, $data_file['columns']);

        $actions_count = array('skipped' => 0, 'deleted' => 0, 'disabled' => 0, 'set_0' => 0);
        $this->product_by_key = $this->model_extension_module_ie_pro_products->get_product_by_key();

        foreach ($product_options_map as $product_option){
            $action_to_apply = $this->evaluate_product_option_file_filters($product_option, $filters, $final_data_file['columns']);
            switch ($action_to_apply) {
                case 'none':
                    $final_data_file['data'] = $this->add_product_option_in_data_file($product_option, $final_data_file['data']);
                    break;
                case 'skip':
                    $actions_count['skipped']++;
                    break;
                case 'delete':
                    $prod_data = $product_option[0];
                    if(is_numeric($product_id_index) && array_key_exists($product_id_index, $prod_data) && !empty($prod_data[$product_id_index])) {
                        $product_id = $this->model_extension_module_ie_pro_products->get_product_id($this->product_identificator, $prod_data[$product_id_index]);
                        if(!empty($product_id)) {
                            $this->{$this->model_loaded}->delete_element($product_id);
                            $actions_count['deleted']++;
                        }
                        $final_data_file['data'] = $this->add_product_option_in_data_file($product_option, $final_data_file['data']);
                    }
                    break;
                case 'disable':
                    $col_index = $this->find_column_index($final_data_file['columns'], 'product', 'status');
                    $product_option[0][$col_index] = 0;
                    $actions_count['disabled']++;
                    $final_data_file['data'] = $this->add_product_option_in_data_file($product_option, $final_data_file['data']);
                    break;
            }
        }

        $this->print_filters_results($actions_count);

        return $final_data_file;
    }

    private function print_filters_results($actions_count){
        if ($actions_count['deleted'] > 0)
            $this->update_process(sprintf($this->language->get('progress_import_elements_deleted'),$actions_count['deleted']));
        if ($actions_count['skipped'] > 0)
            $this->update_process(sprintf($this->language->get('progress_import_elements_skipped'),$actions_count['skipped']));
        if ($actions_count['disabled'] > 0)
            $this->update_process(sprintf($this->language->get('progress_import_elements_disabled'),$actions_count['disabled']));
        if ($actions_count['set_0'] > 0)
            $this->update_process(sprintf($this->language->get('progress_import_elements_set_0'),$actions_count['set_0']));
    }

    private function add_product_option_in_data_file($product_option, $data){
        foreach ($product_option as $element){
            $data[] = $element;
        }
        return $data;
    }

    /**
     * @param $product_option
     * @param $filters
     * @param $columns
     * @return bool; true if the product_option needs to be inserted into the system, false otherwise.
     * @throws Exception
     */
    private function evaluate_product_option_file_filters($product_option, $filters, $columns){
        //iterate over the product and the options elements. The first row must to be the product parent
        foreach ($product_option as $element){
            $is_option = $this->has_options && $this->model_extension_module_ie_pro_products->check_is_option_row($element, $this->options_columns);

            $action_to_apply = $this->evaluate_file_filters($element, $filters, $columns, true, $is_option);

            if ($action_to_apply == 'none') continue;

            return $action_to_apply;
        }

        return 'none';
    }

    private function map_to_product_options_array($data_file){
        $options_columns = $this->model_extension_module_ie_pro_products->check_file_option_column_keys($data_file);
        $result_arr = array();
        $elements = $data_file['data'];

        for ($i = 0; $i < count($elements); $i++){
            $element = $elements[$i];
            $is_option_row = $this->model_extension_module_ie_pro_products->check_is_option_row($element, $options_columns);
            if (!$is_option_row){
                //then get all options and form an array with them and the parent product
                $product_option_tmp_arr = array();
                $product_option_tmp_arr[] = $element;
                $ii = $i + 1;
                //until another parent product isn't found
                while($ii < count($elements) && $this->model_extension_module_ie_pro_products->check_is_option_row($elements[$ii], $options_columns)){
                    $product_option_tmp_arr[] = $elements[$ii];
                    $ii++;
                }
                $result_arr[] = $product_option_tmp_arr;
                $i = $ii - 1;
            }
        }
        return $result_arr;
    }

    private function find_column_index($columns, $table, $field, $col_name = null){
        foreach ($columns as $index => $name){
            foreach ($this->columns as $loaded_col_name => $loaded_column){
                if (
                    $name == $loaded_column['custom_name'] &&
                    (!isset($col_name) || $col_name == $loaded_col_name) &&
                    $loaded_column['table'] == $table && $loaded_column['field'] == $field){
                    return $index;
                }
            }
        }
        return -1;
    }

    /**
     * @param $element, being processed
     * @param $filters, filters to apply
     * @param $columns, columns of the data
     * @param bool $product_filter, indicate if the filter is applied to a product
     * @param bool $is_option, indicate if the element being processed is an option product
     * @return array|bool if the element match with a filter returns the action to apply, otherwise returns false
     */
    public function evaluate_file_filters($element, $filters, $columns, $product_filter = false, $is_option = false){
        //iterate over all filters
        foreach ($filters as $filter){

            if ($product_filter){
                if ($is_option && $filter['table'] != 'product_option_value') continue;
                elseif (!$is_option && $filter['table'] == 'product_option_value') continue;
            }

            $column_index = $this->find_column_index($columns, $filter['table'], $filter['field'], $filter['col_name']);

            if ($column_index >= 0 && array_key_exists($column_index, $element) && $this->evaluate_filter($filter, $element[$column_index])){
                return $filter['action'];
            }
        }
        return 'none';
    }

    public function evaluate_filter($filter, $field_value){
        $type = $filter['type'];
        $condition = $filter['condition'];
        if ($type == 'number'){
            switch ($condition){
                case '>=':
                    return ((float)$field_value) >= ((float) $filter['value']);
                case '<=':
                    return ((float)$field_value) >= ((float) $filter['value']);
                case '>':
                    return ((float)$field_value) > ((float) $filter['value']);
                case '<':
                    return ((int)$field_value) < ((float) $filter['value']);
                case '=':
                    return ((float)$field_value) == ((float) $filter['value']);
                case '!=':
                    return ((float)$field_value) != ((float) $filter['value']);
            }
        }
        elseif ($type == 'string'){
            switch ($condition){
                case 'like':
                    return strpos($field_value, $filter['value']) !== false;
                case 'not_like':
                    return strpos($field_value, $filter['value']) === false;
                case '=':
                    return $field_value == $filter['value'];
                case '!=':
                    return $field_value != $filter['value'];
            }
        }
        elseif ($type == 'boolean'){
            switch ($condition){
                case '1':
                    return $field_value == '1';
                case '0':
                    return $field_value == '0';
            }
        }
        elseif ($type == 'date'){
            $filter_field_value_time = strtotime($filter['value']);
            $field_value_time = strtotime($field_value);
            switch ($condition){
                case '>=':
                    return $field_value_time >= $filter_field_value_time;
                case '<=':
                    return $field_value_time <= $filter_field_value_time;
                case '>':
                    return $field_value_time > $filter_field_value_time;
                case '<':
                    return $field_value_time >= $filter_field_value_time;
                case '=':
                    return $field_value_time == $filter_field_value_time;
                case '!=':
                    return $field_value_time != $filter_field_value_time;
                case 'like':
                    return strpos($field_value, $filter['value']) !== false;
                case 'not_like':
                    return strpos($field_value, $filter['value']) === false;
                default:
                    $filter['value'] = str_replace(',', '.', $filter['value']);
                    $arr = explode('.',$filter['value']);
                    $decimals = (isset($arr[1])) ? strlen($arr[1]) : 0;
                    switch ($condition){
                        case 'years_ago':
                            $years = floor((time()- $field_value_time)/(3600*24*365.25) * pow(10, $decimals)) / (($decimals != 0) ? pow(10, $decimals) : 1);
                            return $years <= $filter['value'];
                        case 'months_ago':
                            $months = floor((time()- $field_value_time)/(3600*24*30.44) * pow(10, $decimals)) / (($decimals != 0) ? pow(10, $decimals) : 1);
                            return $months <= $filter['value'];
                        case 'days_ago':
                            $days = floor((time()- $field_value_time)/(3600*24) * pow(10, $decimals)) / (($decimals != 0) ? pow(10, $decimals) : 1);
                            return $days <= $filter['value'];
                        case 'hours_ago':
                            $hours = floor((time()- $field_value_time)/(3600) * pow(10, $decimals)) / (($decimals != 0) ? pow(10, $decimals) : 1);
                            return $hours <= $filter['value'];
                        case 'minutes_ago':
                            $minutes = floor((time()- $field_value_time)/(60) * pow(10, $decimals)) / (($decimals != 0) ? pow(10, $decimals) : 1);
                            return $minutes <= $filter['value'];
                    }
            }
        }
        return false;
    }

    public function format_filters($filters) {
        $final_filters = array('file' => array(), 'shop' => array());

        foreach ($filters as $key => $fil) {
            $field_split = explode('-', $fil['field']);
            $table = $field_split[0];
            $field = $field_split[1];
            $col_name = str_replace('_', ' ', $field_split[2]);
            $type = $field_split[3];

            $condition = $fil['conditional'][$type];

            $final_filters[$fil['applyto']][] = array(
                'table' => $table,
                'field' => $field,
                'value' => $this->db->escape($fil['value']),
                'condition' => html_entity_decode($condition),
                'action' => $fil['action'],
                'col_name' => $col_name,
                'type' => $type
            );
        }
        return $final_filters;
    }

    public function remove_related_elements($table_name, $rows, $main_condition, $element_id){
        $db_prefix = DB_PREFIX;
        $extra_conditions = [];
        if (!empty($rows)){
            // normalize the rows
            $rows = $this->array_depth($rows) == 1 ? array($rows) : $rows;

            if ($table_name != 'product_options_combinations') {
                // getting the condition fields of the current table, conditional fields are the ones we are going to use to look for the equal elements in db
                $conditional_fields = array_key_exists($table_name, $this->conditional_fields) ? $this->conditional_fields[$table_name] : [];
                foreach ($rows as $row) {
                    if (is_array($row) && !empty($row)) {
                        foreach ($conditional_fields as $conditional_field) {
                            if ($conditional_field != $this->main_field && array_key_exists($conditional_field, $row)) {
                                if (empty($extra_conditions[$conditional_field]))
                                    $extra_conditions[$conditional_field] = [];
                                $extra_conditions[$conditional_field][] = $this->escape_database_value($row[$conditional_field]);
                            }
                        }
                    }
                }
            }
            else if ($this->hasOptionsCombinations){
                $optioncombinations_lib = new \optionscombinations\OptionsCombinationsLib($this->registry);
                $extra_conditions['id'] = [];
                foreach ($rows as $row) {
                    if (is_array($row) && !empty($row)) {
                        $options = json_decode($row['options'],true);
                        $option_value_ids = [];
                        foreach (array_values($options) as $option_value){
                            if (is_array($option_value))
                                foreach ($option_value as $option_checkbox_value_id)
                                    $option_value_ids[] = $option_checkbox_value_id;
                            else
                                $option_value_ids[] = $option_value;
                        }
                        sort($option_value_ids);

                        // here we look for combinations that has at least one of the option_values of the importing row
                        $combinations = $optioncombinations_lib->getCombinations([
                            'product_id' => $element_id,
                            'option_value_id' => $option_value_ids
                        ]);

                        // here we look for the combination that has the same option_values as the importing row
                        $matched_combination = null;
                        foreach ($combinations as $combination){
                            $option_values = $optioncombinations_lib->getCombinationOptionValues($combination['id']);
                            $values_ids = array_map(function ($item){
                                return $item['option_value_id'];
                            }, $option_values);
                            sort($values_ids);
                            if ($option_value_ids == $values_ids) {
                                $matched_combination = $combination;
                                break;
                            }
                        }
                        if ($matched_combination){
                            $extra_conditions['id'][] = $matched_combination['id'];
                        }
                    }
                }
            }
        }

        if(in_array($table_name, array('seo_url', 'url_alias'))){
            if (isset($extra_conditions['query']))
                unset($extra_conditions['query']);
            $main_condition = $this->escape_database_field('query').' = '.$this->escape_database_value($this->main_field.'='.$element_id);
        }

        $conditions = $main_condition;

        if (!empty($extra_conditions)) {
            $conditions .= ' AND NOT (1=1';
            foreach ($extra_conditions as $extra_condition_field => $extra_condition_values) {
                if(!empty($extra_condition_values))
                    $conditions .= " AND {$extra_condition_field} IN (" . join(",", $extra_condition_values) . ")";
            }
            $conditions .= ')';
        }

        $this->db->query('DELETE FROM ' . $this->escape_database_table_name($table_name) . ' WHERE ' . $conditions);
    }

    public function import_data($data_file) {
        $elements_created = 0;
        $elements_edited = 0;
        $elements_deleted = 0;
        $main_condition = $this->escape_database_field($this->main_field).' = ';
        $remote_images = array();

        $this->update_process(sprintf($this->language->get('progress_import_process_start'), '<i class="fa fa-coffee"></i>'));
        $element_to_process = count($data_file);
        $element_processed = 0;
        $this->update_process(sprintf($this->language->get('progress_import_process_imported'), $element_processed, $element_to_process));

        $this->create_image_download_log();

        if ($this->hasOptionsCombinations) {
            $optioncombinations_lib = new \optionscombinations\OptionsCombinationsLib($this->registry);
            $this->load->model('extension/module/options_combinations');
        }

        foreach ($data_file as $file_row => $elements) {
            $element_id = $elements[$this->main_table][$this->main_field];
            $main_condition_temp = $main_condition.$this->escape_database_value($element_id);

            $empty_columns = array_key_exists('empty_columns', $elements);
            $delete_element = $empty_columns && array_key_exists('delete', $elements['empty_columns']) && $this->translate_boolean_value($elements['empty_columns']['delete']);
            if($delete_element) {
                $this->{$this->model_loaded}->delete_element($element_id);
                $elements_deleted++;
            } else {
                $creating = $empty_columns && array_key_exists('creating', $elements['empty_columns']) && $elements['empty_columns']['creating'];
                $elements_created += $creating ? 1 : 0;
                $editting = $empty_columns && array_key_exists('editting', $elements['empty_columns']) && $elements['empty_columns']['editting'];
                $elements_edited += $editting ? 1 : 0;

                if($creating && array_key_exists('forced_id', $elements['empty_columns'])) {
                    $creating = $elements['empty_columns']['creating'] = false;
                    $editting = $elements['empty_columns']['editting'] = true;
                }

                unset($elements['empty_columns']);
                foreach ($elements as $table_name => $fields) {

                    if($this->strict_update && $table_name != $this->main_table) {
                        $this->remove_related_elements($table_name, $fields, $main_condition_temp, $element_id);
                    }

                    if(!empty($fields)) {
                        $conditional_fields = array_key_exists($table_name, $this->conditional_fields) ? $this->conditional_fields[$table_name] : '';

                        $depth = $this->array_depth($fields);

                        if ($depth == 1) $final_fields = array($fields);
                        else $final_fields = $fields;

                        foreach ($final_fields as $row_number => $row) {
                            if(is_array($row) && !empty($row)) {

                                if ($table_name == 'product_options_combinations' && array_key_exists('options', $row)){
                                    $options = json_decode($row['options'],true);
                                    $option_value_ids = [];
                                    foreach (array_values($options) as $option_value){
                                        if (is_array($option_value))
                                            foreach ($option_value as $option_checkbox_value_id)
                                                $option_value_ids[] = $option_checkbox_value_id;
                                        else if (is_numeric($option_value))
                                            $option_value_ids[] = $option_value;
                                    }
                                    sort($option_value_ids);

                                    // here we look for combinations that has at least one of the option_values of the importing row
                                    $combinations = $optioncombinations_lib->getCombinations([
                                        'product_id' => $element_id,
                                        'option_value_id' => $option_value_ids
                                    ]);

                                    // here we look for the combination that has the same option_values as the importing row
                                    $matched_combination = null;
                                    foreach ($combinations as $combination){
                                        $option_values = $optioncombinations_lib->getCombinationOptionValues($combination['id']);
                                        $values_ids = array_map(function ($item){
                                            return $item['option_value_id'];
                                        }, $option_values);
                                        sort($values_ids);
                                        if ($option_value_ids == $values_ids) {
                                            $matched_combination = $combination;
                                            break;
                                        }
                                    }

                                    if ($matched_combination){
                                        $combination_id = $matched_combination['id'];
                                        $this->model_extension_module_options_combinations->update_option_combination($combination_id, array_merge($matched_combination, $row));
                                        $this->model_extension_module_options_combinations->removeOptionValues($combination_id);
                                    }
                                    else{
                                        $combination_id = $this->model_extension_module_options_combinations->insert_option_combination($row);
                                    }

                                    foreach($options as $option_id => $option_value){
                                        if (is_array($option_value)){
                                            foreach($option_value as $option_checkbox_value_id){
                                                $this->model_extension_module_options_combinations->addOptionValue($combination_id, $element_id, $option_checkbox_value_id, $option_id);
                                            }
                                        } else if(is_numeric($option_value)){
                                            $this->model_extension_module_options_combinations->addOptionValue($combination_id, $element_id, $option_value, $option_id);
                                        } else {
                                            $this->model_extension_module_options_combinations->addOptionValue($combination_id, $element_id, 'NULL', $option_id, "'$option_value'");
                                        }
                                    }
                                    continue;
                                }

                                //Remote image managing
                                $remote_image = array();
                                if(in_array($table_name, $this->tables_with_images)) {
                                    $image_fields = array(
                                        'image',
                                        'option_image',
                                    );

                                    $final_field = '';
                                    foreach ($image_fields as $field) {
                                        if (array_key_exists($field, $row) && !empty($row[$field])) {
                                            $final_field = $field;
                                            break;
                                        }
                                    }

                                    if (!empty($final_field) && $this->is_url($row[$final_field])) {
                                        $image_info = $this->get_remote_image_data($table_name, $final_field, $element_id, $row_number, $row[$final_field]);
                                        $image_path = $image_info['opencart_path'];
                                        $remote_image = $image_info;
                                        if(!empty($image_path))
                                            $row[$final_field] = $image_path;
                                        else
                                            unset($row[$final_field]);
                                    } else if (!empty($final_field) && $this->extra_image_route != '') {
                                        $row[$final_field] = $this->extra_image_route . $row[$final_field];
                                    }
                                }

                                $table_conditions = $main_condition_temp;
                                $extra_conditions = '';

                                $insert_extra_conditions = $table_name != $this->main_table || ($table_name == $this->main_table && !$this->has_main_field_column);
                                if (!empty($conditional_fields) && $insert_extra_conditions) {
                                    foreach ($conditional_fields as $field_name) {
                                        if (array_key_exists($field_name, $row)) {
                                            $extra_conditions .= ' AND ' . $this->escape_database_field($field_name) . ' = ' . ( $table_name != 'product_options_combinations' ? $this->escape_database_value($row[$field_name]) : "'$row[$field_name]'" );
                                        }
                                    }
                                }

                                $fields_no_main_conditional = '';
                                if (array_key_exists($table_name, $this->fields_without_main_conditional)) {
                                    $field_name_condition = '';
                                    foreach ($row as $fi_name => $value) {
                                        if(array_key_exists($fi_name, $this->fields_without_main_conditional[$table_name])) {
                                            $field_name_condition = $fi_name;
                                            break;
                                        }
                                    }

                                    if (!empty($field_name_condition)) {
                                        $table_conditions = $this->escape_database_field($field_name_condition) . ' = ' . $this->escape_database_value($row[$field_name_condition]) . $extra_conditions;
                                    }
                                } else {
                                    $table_conditions .= $extra_conditions;
                                }

                                $exist_element = $this->check_element_exist($table_name, $table_conditions);

                                if(is_file($this->assets_path.'model_ie_pro_import_function_import_data_just_before_save_product.php'))
                                    require($this->assets_path.'model_ie_pro_import_function_import_data_just_before_save_product.php');

                                if($exist_element && !empty($row[$this->main_field]))
                                    unset($row[$this->main_field]);

                                $sql = $this->get_sql($row, $table_name, $table_conditions, $exist_element);
                                $this->db->query($sql);
                                $last_id = $this->db->getLastId();

                                if(!empty($remote_image)) {
                                    $remote_image['element_id'] = !empty($element_id) ? $element_id : $last_id;
                                    $remote_images[] = $remote_image;
                                }

                                if ($table_name == 'product_option_value' && !$exist_element) {
                                    $option_id = array_key_exists('option_id', $row) && !empty($row['option_id']) ? $row['option_id'] : '';
                                    if (!empty($option_id)) {
                                        $product_option_id = $this->model_extension_module_ie_pro_option_values->get_product_option_id($element_id, $option_id);
                                        if (!empty($product_option_id)) {

                                            $fields = array(
                                                'product_option_id' => $product_option_id
                                            );
                                            $conditions_temp = $this->escape_database_field('product_option_value_id') . ' = ' . $this->escape_database_value($last_id);
                                            $sql = $this->get_sql($fields, $table_name, $conditions_temp, true);
                                            $this->db->query($sql);
                                        }
                                    }
                                }

                                if(is_file($this->assets_path.'model_ie_pro_import_function_import_data_just_after_save_product.php'))
                                    require($this->assets_path.'model_ie_pro_import_function_import_data_just_after_save_product.php');
                            }
                        }
                    }
                }
            }

            $element_processed++;
            $this->update_process(sprintf($this->language->get('progress_import_process_imported'), $element_processed, $element_to_process), true);
        }

        //Update main quantity of all products which his options combinations was edited.
        if($this->elements_to_import === 'products' && $this->hasOptionsCombinations && array_key_exists('product_options_combinations', $data_file[0])) {
            $this->model_extension_module_ie_pro_products->options_combinations_update_main_product_quantities($data_file);
        }

        if($this->profile['profile_type'] == 'import' && $this->profile['import_xls_i_want'] == 'categories')
            $this->model_extension_module_ie_pro_categories->reset_path($data_file);

        $this->update_process($this->language->get('progress_import_applying_changes_safely'));
        $this->db->query("COMMIT");

        if(is_file($this->assets_path.'model_ie_pro_import_import_data_after_sql_commit.php'))
            require_once($this->assets_path.'model_ie_pro_import_import_data_after_sql_commit.php');

        $data_file = '';
        //Download remote images
        if(!empty($remote_images)) {
            $this->update_process($this->language->get('progress_import_downloading_remote_images'));
            $element_to_process = count($remote_images);
            $this->update_process(sprintf($this->language->get('progress_import_downloading_remote_images_progress'), 0, $element_to_process));
            $images_downloaded = 1;
            foreach ($remote_images as $key => $img) {
                $download = !$this->skip_image_download || ($this->skip_image_download && !is_numeric(array_search($img['name'], $this->all_images)));

                if($download)
                    $this->download_remote_image($img);

                $this->update_process(sprintf($this->language->get('progress_import_downloading_remote_images_progress'), $images_downloaded, $element_to_process), true);
                $images_downloaded++;
            }
        }

        $image_log_contents = $this->get_image_download_log_html();

        if (!empty( $image_log_contents)) {
            $error_log_url = $this->get_image_download_log_url();
            $error_message_tpl = $this->language->get( 'profile_import_errors_downloading_remote_images_tpl');
            $error_message = preg_replace( '/\{log_url*}/', $error_log_url, $error_message_tpl);

            $this->update_process( "<div class=\"alert alert-danger\">{$error_message}</div>");
        }

        return [$elements_created, $elements_edited, $elements_deleted];
    }

    function format_data_file($data) {
        $columns = $data['columns'];
        $data = $data['data'];

        $this->update_process($this->language->get('progress_import_process_format_data_file'));
        $element_to_process = count($data);
        $element_processed = 0;
        $this->update_process(sprintf($this->language->get('progress_import_process_format_data_file_progress'), $element_processed, $element_to_process));

        $importing_products = $this->elements_to_import == 'products';
        $check_options = $importing_products && $this->has_options;
        if($check_options) {
            $options_columns = $this->model_extension_module_ie_pro_products->check_file_option_column_keys(array("columns" => $columns, "data" => $data), false);
            $options_columns_keys = array_values($options_columns);
        }

        $final_data = array();
        foreach ($data as $key => $fields) {
            $temp_data = array();

            $is_option_row = $check_options ? $this->model_extension_module_ie_pro_products->check_is_option_row($fields, $options_columns) : false;

            foreach ($fields as $col_index => $value) {
                $column_name = array_key_exists($col_index, $columns) ? $columns[$col_index] : '';
                if(!empty($column_name) && array_key_exists($column_name, $this->custom_columns)) {
                    $column_data = $this->custom_columns[$column_name];
                    $table = $column_data['table'];
                    $field = $column_data['field'];
                    if ($this->hasOptionsCombinations) {
                        $inner_field = isset($column_data['inner_field']) ? $column_data['inner_field'] : null;
                        $key = isset($column_data['key']) ? $column_data['key'] : null;
                    }

                    if (!array_key_exists($table, $temp_data))
                        $temp_data[$table] = array();

                    $identificator = array_key_exists('identificator', $column_data) ? $column_data['identificator'] : '';
                    $store_id = array_key_exists('store_id', $column_data) ? $column_data['store_id'] : '';
                    $language_id = array_key_exists('language_id', $column_data) ? $column_data['language_id'] : '';
                    $default_value = array_key_exists('default_value', $column_data) ? $column_data['default_value'] : '';
                    $assign_default_value = !$is_option_row || ($is_option_row && in_array($col_index, $options_columns_keys));

                    preg_match_all("/\[([^\]]*)\]/", $default_value, $matches);
                    $another_column_value = array_key_exists(0, $matches[1]) && !empty($matches[1][0]);

                    if ($another_column_value) {
                        $another_column_name = $matches[1][0];
                        $column_index = array_search($another_column_name, $columns);
                        if (is_numeric($column_index) && !is_array($column_index))
                            $default_value = array_key_exists($column_index, $fields) && !empty($fields[$column_index]) ? $fields[$column_index] : '';
                    }

                    if(!$assign_default_value)
                        $default_value = '';

                    $value = empty($value) && !empty($default_value) ? $this->sanitize_value($default_value) : $this->sanitize_value($value);

                    if(array_key_exists('real_type', $column_data) && $column_data['real_type'] == 'decimal' && !empty($value))
                        $value = (float)$this->format_number_thousand_separator($value);

                    $allow_ids = $this->check_allow_ids($this->custom_columns[$column_name]);
                    $value .= $allow_ids && !empty($value) ? '-forceId' : '';

                    if ($this->hasOptionsCombinations && $table == 'product_options_combinations') {
                        if (empty($identificator) && $store_id === '' && empty($language_id)) {
                            if (isset($inner_field) && isset($key)) {
                                $temp_data[$table][$field][$inner_field][$key] = $value;
                            } elseif (isset($inner_field)) {
                                $temp_data[$table][$field][$inner_field] = $value;
                            } else {
                                $temp_data[$table][$field] = $value;
                            }
                        } elseif (!empty($language_id) && $store_id === '' && empty($identificator)) {
                            if (isset($inner_field) && isset($key)) {
                                $temp_data[$table][$field][$inner_field][$key][$language_id] = $value;
                            } elseif (isset($inner_field)) {
                                $temp_data[$table][$field][$inner_field][$language_id] = $value;
                            } else {
                                $temp_data[$table][$field][$language_id] = $value;
                            }
                        } elseif ($store_id !== '' && empty($language_id) && empty($identificator)) {
                            if (isset($inner_field) && isset($key)) {
                                $temp_data[$table][$store_id][$field][$inner_field][$key] = $value;
                            } elseif (isset($inner_field)) {
                                $temp_data[$table][$store_id][$field][$inner_field] = $value;
                            } else {
                                $temp_data[$table][$store_id][$field] = $value;
                            }
                        } elseif (!empty($identificator) && empty($language_id) && $store_id === '') {
                            $levels = explode("_", $identificator);
                            if (count($levels) == 1) {
                                if (isset($inner_field) && isset($key)) {
                                    $temp_data[$table][$field][$inner_field][$levels[0]][$key] = $value;
                                } elseif (isset($inner_field)) {
                                    $temp_data[$table][$field][$levels[0]][$inner_field] = $value;
                                } else {
                                    $temp_data[$table][$field][$levels[0]] = $value;
                                }
                            } else if (count($levels) == 2) {
                                if (isset($inner_field) && isset($key)) {
                                    $temp_data[$table][$field][$inner_field][$levels[0]][$levels[1]][$key] = $value;
                                } elseif (isset($inner_field) && !empty($value)) {
                                    $temp_data[$table][$field][$levels[0]][$levels[1]][$inner_field] = $value;
                                } else {
                                    $temp_data[$table][$levels[0]][$levels[1]][$field] = $value;
                                }
                            }
                        } elseif (!empty($language_id) && $store_id !== '' && empty($identificator)) {
                            if (isset($inner_field) && isset($key)) {
                                $temp_data[$table][$store_id][$field][$inner_field][$key][$language_id] = $value;
                            } elseif (isset($inner_field)) {
                                $temp_data[$table][$store_id][$field][$inner_field][$language_id] = $value;
                            } else {
                                $temp_data[$table][$store_id][$field][$language_id] = $value;
                            }
                        } elseif (!empty($identificator) && !empty($language_id) && $store_id === '') {
                            $levels = explode("_", $identificator);
                            unset($levels[count($levels) - 1]);
                            if (count($levels) == 1) {
                                if (isset($inner_field) && isset($key)) {
                                    $temp_data[$table][$field][$inner_field][$levels[0]][$key][$language_id] = $value;
                                } elseif (isset($inner_field)) {
                                    $temp_data[$table][$field][$levels[0]][$inner_field][$language_id] = $value;
                                } else {
                                    $temp_data[$table][$levels[0]][$field][$language_id] = $value;
                                }
                            } else if (count($levels) == 2) {
                                if (isset($inner_field) && isset($key)) {
                                    $temp_data[$table][$levels[0]][$levels[1]][$field][$inner_field][$key][$language_id] = $value;
                                } elseif (isset($inner_field)) {
                                    $temp_data[$table][$levels[0]][$levels[1]][$field][$inner_field][$language_id] = $value;
                                } else {
                                    $temp_data[$table][$levels[0]][$levels[1]][$field][$language_id] = $value;
                                }
                            }
                        }
                    } else {
                        if (empty($identificator) && $store_id === '' && empty($language_id)) {
                            $temp_data[$table][$field] = $value;
                        } elseif (!empty($language_id) && $store_id === '' && empty($identificator)) {
                            $temp_data[$table][$language_id][$field] = $value;
                        } elseif ($store_id !== '' && empty($language_id) && empty($identificator)) {
                            $temp_data[$table][$store_id][$field] = $value;
                        } elseif (!empty($identificator) && empty($language_id) && $store_id === '') {
                            $levels = explode("_", $identificator);
                            if (count($levels) == 1) {
                                $temp_data[$table][$levels[0]][$field] = $value;
                            } else if (count($levels) == 2) {
                                $temp_data[$table][$levels[0]][$levels[1]][$field] = $value;
                            }
                        } elseif (!empty($language_id) && $store_id !== '' && empty($identificator)) {
                            $temp_data[$table][$store_id][$field][$language_id] = $value;
                        } elseif (!empty($identificator) && !empty($language_id) && $store_id === '') {
                            $levels = explode("_", $identificator);
                            unset($levels[count($levels) - 1]);
                            if (count($levels) == 1) {
                                $temp_data[$table][$levels[0]][$field][$language_id] = $value;
                            } else if (count($levels) == 2) {
                                $temp_data[$table][$levels[0]][$levels[1]][$field][$language_id] = $value;
                            }
                        }
                        /*if(!empty($language_id) && !$this->multilanguage && $this->count_language > 1) {
                            foreach ($this->languages as $key => $lang_info) {

                            }
                        }*/
                    }
                }
            }

            if(!empty($temp_data))
                $final_data[] = $temp_data;

            $element_processed++;
            $this->update_process(sprintf($this->language->get('progress_import_process_format_data_file_progress'), $element_processed, $element_to_process), true);
        }

        return $final_data;
    }

    public function insert_lost_columns_in_get_data($elements) {
        //<editor-fold desc="Remove columns if empty">
        $exists_empty_columns = false;
        $indexes_to_delete = array();
        foreach ($elements['columns'] as $key => $col_name) {
            if(trim($col_name) === '') {
                $exists_empty_columns = true;
                $indexes_to_delete[] = $key;
                unset($elements['columns'][$key]);
            }
        }

        if($exists_empty_columns) {
            $elements['columns'] = array_values($elements['columns']);
            foreach ($elements['data'] as $key => $rows) {
                foreach ($indexes_to_delete as $index) {
                    if(array_key_exists($index, $rows))
                        unset($elements['data'][$key][$index]);
                }
                $elements['data'][$key] = array_values($elements['data'][$key]);
            }
        }
        //</editor-fold>

        if(array_key_exists('columns', $elements) && array_key_exists('data', $elements) && !empty($elements['data'])) {
            $num_columns = count($elements['columns']);
            foreach ($elements['data'] as $key => $dat) {
                if (count($dat) < $num_columns) {
                    $to_add = $num_columns - count($dat);
                    for ($i = 1; $i <= $to_add; $i++) {
                        $elements['data'][$key][] = '';
                    }
                }
            }
        }
        return $elements;
    }

    public function remove_unnecesary_columns($data_file) {
        $columns_allowed = array_keys($this->custom_columns);
        $keys_originals = array_keys($data_file['columns']);

        foreach ($data_file['columns'] as $key => $col_name) {
            if (!in_array($col_name, $columns_allowed)) {
                unset($data_file['columns'][$key]);
            }
        }

        $keys_allowed = array_keys($data_file['columns']);
        $data_file['columns'] = array_values($data_file['columns']);

        if($this->profile['import_xls_file_format'] == 'json') {
            $final_data = array();
            foreach ($data_file['data'] as $element) {
                $temp = array();
                foreach ($data_file['columns'] as $key => $col_name) {
                    $temp[] = array_key_exists($col_name, $element) ? $element[$col_name] : '';
                }
                $final_data[] = $temp;
            }
            $data_file['data'] = $final_data;
        } else {
            if (count($keys_originals) != count($keys_allowed)) {
                foreach ($data_file['data'] as $key => $row) {
                    foreach ($row as $key2 => $val) {
                        if (!in_array($key2, $keys_allowed))
                            unset($row[$key2]);
                    }
                    $data_file['data'][$key] = array_values($row);
                }
            }
        }
        return $data_file;
    }

    public function assign_default_values_to_lost_columns($elements) {
        $importing_products = $this->elements_to_import == 'products';

        $columns = $elements['columns'];
        $elements = $elements['data'];
        $column_number = count($columns);
        foreach ($elements as $key => $element) {
            if(count($element) > $column_number) {
                $to_delete = count($element) - $column_number;
                for ($i = 0; $i < $to_delete; $i++)
                    array_pop($element);
            }

            //Devman Extensions - info@devmanextensions.com - 26/04/2019 16:55 - Control for repeat column names.
            $count_repeat_columns = array_count_values($columns);
            foreach ($count_repeat_columns as $col_name => $num_column) {
                if($num_column > 1) {
                    $columns = array_reverse($columns);
                    $number_of_changes = $num_column;
                    foreach ($columns as $key_col => $col_name_2) {
                        if($col_name == $col_name_2) {
                            $columns[$key_col] = $col_name_2.'-'.($number_of_changes);
                            $number_of_changes--;
                            if($number_of_changes == 1)
                                break;
                        }
                    }
                    $columns = array_reverse($columns);
                }
            }

            $elements[$key] = array_combine($columns, $element);
        }

        $columns_expected = array_keys($this->custom_columns);
        $column_lost = array_diff($columns_expected, $columns);

        if(!empty($column_lost)) {
            foreach ($column_lost as $key => $col_nane) {
                if(array_key_exists($col_nane, $this->custom_columns) && array_key_exists('default_value', $this->custom_columns[$col_nane])) {
                    array_push($columns, $col_nane);
                }
            }
        }

        if($importing_products) {
            $options_columns_keys = array_values($this->options_columns);
        }

        if(!empty($column_lost)) {
            foreach ($column_lost as $key => $col_nane) {
                if(array_key_exists($col_nane, $this->custom_columns) && array_key_exists('default_value', $this->custom_columns[$col_nane])) {
                    //array_push($columns, $col_nane);
                    $default_value = $this->custom_columns[$col_nane]['default_value'];

                    foreach ($elements as $row_number => $data) {
                        if($default_value === '') {
                            $elements[$row_number][$col_nane] = '';
                            continue;
                        }
                        $insert = true;
                        $is_option_row =  $importing_products && $this->has_options ? $this->model_extension_module_ie_pro_products->check_is_option_row($data, $this->options_columns) : false;
                        $insert = !$is_option_row || ($is_option_row && in_array($col_nane, $options_columns_keys));

                        if(is_array($data) && $insert)
                            $elements[$row_number][$col_nane] = $this->get_default_value($default_value, $data, true);
                        elseif(is_array($data))
                            $elements[$row_number][$col_nane] = '';
                    }
                }
            }
        }

        //Check also all possible existing columns that has a contatenation text
        foreach ($elements as $key => $elemts) {
            $is_option_row =  $importing_products && $this->has_options ? $this->model_extension_module_ie_pro_products->check_is_option_row($elemts, $this->options_columns) : false;
            foreach ($elemts as $column_name => $data) {
                if(in_array($column_name, $column_lost))
                    continue;
                $col_info = array_key_exists($column_name, $this->custom_columns) ? $this->custom_columns[$column_name] : false;
                $default_value = !empty($col_info) && array_key_exists('default_value', $col_info) && $col_info['default_value'] !== '' ? $col_info['default_value'] : '';
                $conditional_value = !empty($col_info) && array_key_exists('conditional_value', $col_info) && !empty($col_info['conditional_value']) ? $col_info['conditional_value'] : '';

                //Is possible that default value has the same column inside it for concatenate, in this case, apply default value.
                $concatenation = false;
                if($default_value !== '') {
                    $col_name = !empty($col_info['custom_name']) ? '['.$col_info['custom_name'].']' : '';
                    $concatenation = !empty($data) && !empty($col_name) && strpos($default_value, $col_name) !== false;

                    if(!$concatenation) {
                        $elements[$key][$column_name] = '';
                        continue;
                    }
                }

                if($default_value !== '' && ($concatenation || empty($elements[$key][$column_name]))) {
                    $insert = true;
                    $insert = !$is_option_row || ($is_option_row && in_array($column_name, $options_columns_keys));
                    if($insert)
                        $elements[$key][$column_name] = $this->get_default_value($default_value, $elemts, true);
                } elseif(!empty($conditional_value) && ($elements[$key][$column_name] != 0 && empty($elements[$key][$column_name])))
                    $elements[$key][$column_name] = '';
            }
        }


        foreach ($elements as $key => $element) {
            $elements[$key] = array_values($element);
        }
        $elements = array(
            'columns' => $columns,
            'data' => $elements
        );

        return $elements;
    }

    public function get_default_value($default_value, $element, $skip_id_instead_of_name = false) {
        $result = preg_match_all("/\[([^\]]*)\]/", $default_value, $matches);

        if ($result >= 1) {
            foreach ($matches[1] as $fieldName) {
                $fieldName = $fieldName;
                $col_info = array_key_exists($fieldName, $this->custom_columns) ? $this->custom_columns[$fieldName] : '';
                if (!empty($fieldName) && isset($element[$fieldName])) {
                    $default_value = str_replace("[{$fieldName}]", $this->conversion_value($col_info['table'], $col_info['field'] , $element[$fieldName], $skip_id_instead_of_name), $default_value);
                }
            }
        }
        return $default_value;
    }

    public function conversion_values($data_file) {

        $this->update_process($this->language->get('progress_import_elements_conversion_start'));
        $element_to_process = count($data_file);
        $element_processed = 0;
        $this->update_process(sprintf($this->language->get('progress_import_elements_converted'), $element_processed, $element_to_process));

        foreach ($data_file as $key => $rows) {
            $creating = array_key_exists('empty_columns', $rows) && array_key_exists('creating', $rows['empty_columns']) && $rows['empty_columns']['creating'];
            $editting = array_key_exists('empty_columns', $rows) && array_key_exists('editting', $rows['empty_columns']) && $rows['empty_columns']['editting'];
            foreach ($rows as $table_name => $fields) {
                if(!empty($fields)) {
                    $depth = $this->array_depth($fields);
                    if ($depth == 2)
                        $temp = $fields;
                    else
                        $temp = array(0 => $fields);
                    foreach ($temp as $key2 => $row_data) {
                        if(!is_array($row_data))
                                continue;
                        foreach ($row_data as $field_name => $value) {
                            $temp_field_name = $field_name;
                            if(in_array($table_name, array('product_discount','product_special')) && !empty($row_data['customer_group_id']))
                                $temp_field_name .= '_'.$row_data['customer_group_id'];

                            $value_converted = $this->conversion_value($table_name, $temp_field_name, $value, false, $rows);

                            if ($depth == 2)
                                $data_file[$key][$table_name][$key2][$field_name] = $value_converted;
                            else
                                $data_file[$key][$table_name][$field_name] = $value_converted;
                        }
                    }
                }
            }

            $element_processed++;
            $this->update_process(sprintf($this->language->get('progress_import_elements_converted'), $element_processed, $element_to_process), true);
        }
        return $data_file;
    }

    function conversion_value($table, $field, $value, $skip_id_instead_of_name = false, $full_row = array()) {
        $table_field = $table.'_'.$field;
        $final_val = $value;

        //Taxes in prices
        if($this->elements_to_import == 'products' && $this->price_tax_operation != '' && strpos($field, 'price') !== false && !empty($value) && is_numeric($value)) {
            $tax_class_id = array_key_exists('product', $full_row) && array_key_exists('tax_class_id', $full_row['product']) && $full_row['product']['tax_class_id'] ? $full_row['product']['tax_class_id'] : '';
            if(!empty($tax_class_id) && is_numeric($tax_class_id))
                $final_val = $this->model_extension_module_ie_pro->price_tax_calculate('', $value, $this->price_tax_operation, $tax_class_id);
        }
        // Taxes in combination prices
        if ($this->hasOptionsCombinations &&
            $this->elements_to_import == 'products' &&
            $this->price_tax_operation != '' &&
            $table == 'product_options_combinations' &&
            $field == 'prices'
        ){
            $tax_class_id = array_key_exists('product', $full_row) && array_key_exists('tax_class_id', $full_row['product']) && $full_row['product']['tax_class_id'] ? $full_row['product']['tax_class_id'] : '';
            $prices = json_decode($value, true);
            foreach ($prices as $price_name => $comb_prices) {
                // Custom tax class computation
                if (in_array($price_name, ['option_price', 'option_discount', 'option_special'])) {
                    foreach ($comb_prices as $index => &$comb_price) {
                        $price_value = (float)$comb_price['price'];
                        $price_prefix = isset($comb_price['price_prefix']) ? $comb_price['price_prefix'] : null;
                        if (is_numeric($tax_class_id)) {
                            $prices[$price_name][$index]['price'] = $this->price_combination_tax_calculate($tax_class_id, $price_value, $this->price_tax_operation, $price_name, $price_prefix);
                        }
                    }
                }
            }
            $final_val = json_encode($prices);
        }

        if (array_key_exists($table_field, $this->conversion_fields)) {
            $conv_fields = $this->conversion_fields[$table_field];

            foreach ($conv_fields as $index => $conv_field_info) {
                // $conv_field_info = $this->conversion_fields[$table_name.'_'.$field_name];
                $rule = $conv_field_info['rule'];

                if($rule == 'strip_html_tags'){
                    if($conv_field_info['html_tags'] == 'all')
                        $final_val = strip_tags(str_replace('&nbsp', ' ', html_entity_decode(isset($final_val) ? $final_val : $value)));
                    else {
                        $html_tags_allowed = "<" .str_ireplace(',', '><', trim($conv_field_info['html_tags'])). ">";
                        $html_tags_allowed .= strtoupper($html_tags_allowed);
                        $final_val = strip_tags($final_val, $html_tags_allowed);
                    }
                }

                if($rule == 'allow_max_length'){
                    $final_val = substr($final_val, 0, $conv_field_info['max_length']);
                }

                if($rule == 'boolean_field') {
                    $true_value = $conv_field_info['true_value'];
                    $final_val = $final_val == $true_value ? 1 : 0;
                }

                if($rule == 'product_id_identificator' && !empty($final_val)) {
                    $field_temp = $conv_field_info['product_id_identificator'];
                    $temp_val = $this->model_extension_module_ie_pro_products->get_product_id($field_temp, $final_val);
                    $temp_val = !$temp_val ? '' : $temp_val;
                    $final_val = $temp_val;
                }

                if($rule == 'name_instead_id') {
                    if($conv_field_info['id_instead_of_name'] && !empty($final_val) && !in_array($field, array('main_category'))) {
                        $final_val = $this->extract_id_allow_ids($final_val);
                    } else {
                        if(!$skip_id_instead_of_name) {
                            $conversion_global_var = $conv_field_info['conversion_global_var'] . '_import';
                            $conversion_global_index = $conv_field_info['conversion_global_index'];

                            if ($field == 'manufacturer_id')
                                $final_val = strtolower($final_val) . '_' . $this->default_language_id;

                            if (is_array($this->{$conversion_global_var}) && array_key_exists($final_val, $this->{$conversion_global_var})) {
                                $final_val = $this->{$conversion_global_var}[$final_val];
                            }
                        }
                    }
                }

                if($rule == 'profit_margin' && !empty($final_val) && (float)$final_val > 0) {
                    $profit_margin = $conv_field_info['profit_margin'];
                    $final_val  = $this->add_profit_margin($final_val, $profit_margin);
                }

                if($rule == 'round'){
                    $final_val = round((float)$final_val);
                }

                if(is_file($this->assets_path.'model_ie_pro_import_conversion_value_add_news.php'))
                    require($this->assets_path.'model_ie_pro_import_conversion_value_add_news.php');
            }
        }

        return $final_val;
    }

    public function add_splitted_values($data_file) {
        $import_format = $this->profile['import_xls_file_format'];
        //<editor-fold desc="Add columns">
        if($import_format != 'xml')
            foreach ($this->splitted_values_fields as $column_name => $split_info) {
                $data_file['columns'][] = $column_name;
            }
        //</editor-fold>

        //<editor-fold desc="Add splited values to each element">
        $columns_file = $data_file['columns'];
        foreach ($data_file['data'] as $key => $element) {
            foreach ($this->splitted_values_fields as $column_name => $split_info) {
                $custom_name_real = $split_info['custom_name_real'];
                $index_element = array_search($custom_name_real, $columns_file);
                $final_value = '';
                if(is_numeric($index_element) && array_key_exists($index_element, $element) && is_numeric($split_info['position'])) {
                    $value_splited = explode($split_info['symbol'], $element[$index_element]);
                    if(array_key_exists($split_info['position'], $value_splited))
                        $final_value = $value_splited[$split_info['position']];
                }
                if($import_format != 'xml')
                    $data_file['data'][$key][] = $final_value;
                else
                    $data_file['data'][$key][$index_element] = $final_value;
            }
        }
        //</editor-fold>

        //<editor-fold desc="Remove real column names">
        if($import_format != 'xml') {
            foreach ($this->splitted_values_fields as $column_name => $split_info) {
                $index_to_delete = array_search($split_info['custom_name_real'], $data_file['columns']);
                if (is_numeric($index_to_delete)) {
                    unset($data_file['columns'][$index_to_delete]);
                    $data_file['columns'] = array_values($data_file['columns']);

                    foreach ($data_file['data'] as $key => $element) {
                        unset($element[$index_to_delete]);
                        $element = array_values($element);
                        $data_file['data'][$key] = $element;
                    }
                }
            }
        }
        //</editor-fold>

        return $data_file;
    }

    function process_special_row($config_row, $fields) {
        $config_split = explode('|', $config_row);
        $table = $config_split[1];
        $store_id = $config_split[3];
        $language_id = $config_split[5];
        $identificator = $config_split[7];
        if (!empty($store_id)) $fields['store_id'] = $store_id;
        if (!empty($language_id)) $fields['language_id'] = $language_id;

        return $fields;
    }

    function check_download_image_path() {
        $extra_route = array_key_exists('import_xls_download_image_route', $this->profile) && !empty($this->profile['import_xls_download_image_route']) ? $this->profile['import_xls_download_image_route'] : '';
        if(!empty($extra_route) && !is_dir(DIR_IMAGE.$this->image_path.$extra_route)) {
            mkdir(DIR_IMAGE.$this->image_path.$extra_route, 0755, true);
        }
        $this->extra_image_route = !empty($extra_route) ? rtrim($extra_route, '/').'/' : '';
    }

    private function apply_categories_mapping( $data_file, $categoriesMapping, $columnsIdInsteadOfName){

        $options_columns = $this->elements_to_import == 'products' ? $this->model_extension_module_ie_pro_products->check_file_option_column_keys($data_file, !$this->is_xml) : array();
        $this->update_process( $this->language->get('progress_import_mapping_categories') . '...');

        $this->load->model( 'extension/module/ie_pro_categories');
        $this->allCategories = $this->model_extension_module_ie_pro_categories->get_all_categories_export_format();

        $columnNames = $data_file['columns'];

        $categoryColumns = $this->get_categories_columns( $columnNames);
        $data = [];

        foreach ($data_file['data'] as $row){

            $is_option = $this->has_options && $this->model_extension_module_ie_pro_products->check_is_option_row($row, $options_columns);

            if(!$is_option)
            foreach ($categoryColumns as $categoryColumn){
                $useIdInsteadOfName = in_array( $columnNames[$categoryColumn[0]], $columnsIdInsteadOfName);

                $this->apply_category_column_mapping(
                    $row,
                    $categoryColumn,
                    $categoriesMapping,
                    $useIdInsteadOfName
                );
            }

            $data[] = $row;
        }

        $data_file['data'] = $data;

        return $data_file;
    }

    private function get_categories_columns( $columnNames){
        $result = [];

        if ($this->cat_tree) {
            // Category Tree
            $catTree = 1;

            while (isset( $this->columns["Cat. tree {$catTree} parent"])){
                $columnDef = $this->columns["Cat. tree {$catTree} parent"];
                $columnName = $columnDef['custom_name'];
                $columnIndex = array_search( $columnName, $columnNames);

                $columnIndices = [$columnIndex];

                $subCategories = $this->get_sub_categories_columns( $catTree, $columnNames);

                $columnIndices = array_merge( $columnIndices, $subCategories);

                $result[] = $columnIndices;

                $catTree++;
            }
        } else {
            // Categorias simples
            foreach ($this->columns as $key => $col) {
                if(!empty($col['field']) && $col['field'] == 'category_id') {
                    $columnName = $col['custom_name'];
                    $columnIndex = array_search( $columnName, $columnNames);

                    $result[] = [$columnIndex];
                }
            }
        }

        return $result;
    }

    private function get_sub_categories_columns( $catTree, $columnNames){
        $result = [];
        $level = 1;

        while (isset( $this->columns["Cat. tree {$catTree} level {$level}"])){
            $columnDef = $this->columns["Cat. tree {$catTree} level {$level}"];
            $columnName = $columnDef['custom_name'];
            $columnIndex = array_search( $columnName, $columnNames);

            $result[] = $columnIndex;

            $level++;
        }

        return $result;
    }

    private function apply_category_column_mapping(
        &$row,
        $categoryColumns,
        $categoriesMapping,
        $useIdInsteadOfName){

        $defaultCategoryId = !empty( $categoriesMapping['default'])
            ? $categoriesMapping['default']
            : null;

        $defaultCategory = $this->get_category_name( $defaultCategoryId);

        $mappings = $categoriesMapping['mappings'];
        $idMappings = !empty($categoriesMapping['id_mappings']) ? $categoriesMapping['id_mappings'] : '';

        $fullProviderCategoryName = $this->get_full_provider_category_name( $row, $categoryColumns);
        $categoryName = $this->find_best_category_match( $fullProviderCategoryName, $categoriesMapping);

        if ($categoryName !== null) {
            // Limpiamos las categorias originales
            for ($i = 0; $i < count( $categoryColumns); $i++) {
                $row[$categoryColumns[$i]] = '';
            }

            $row[$categoryColumns[0]] = $categoryName;
        } else {
            foreach ($categoryColumns as $index => $columnIndex) {
                if ($index === 0) {
                    $categoryName = $row[$columnIndex];
                } else {
                    $categoryName .= ',' . $row[$columnIndex];
                }

                if ($useIdInsteadOfName && !empty( $idMappings)) {
                    $row[$columnIndex] = array_key_exists($categoryName, $idMappings) ? $idMappings[$categoryName] : '';
                } elseif (isset( $mappings[$categoryName]) && !empty( $mappings[$categoryName])) {
                    $categoryId = $mappings[$categoryName];

                    if ($useIdInsteadOfName) {
                        $row[$columnIndex] = $categoryId;
                    } else {
                        if ($index < count( $categoryColumns)) {
                            $category_name = $this->get_category_name_components( $categoryId);
                            $i = 0;

                            // Limpiamos las categorias originales
                            for ($i = 0; $i < count( $categoryColumns); $i++) {
                                $row[$categoryColumns[$i]] = '';
                            }

                            $row[$categoryColumns[0]] = count( $category_name) - 1 >= 0 ? $category_name[count( $category_name) - 1] : '';

                        } else if (isset( $this->allCategories[$categoryId])) {
                            $row[$columnIndex] = $this->get_category_name( $categoryId);
                        }
                    }
                } elseif ($defaultCategory !== null) {
                    $row[$columnIndex] = $defaultCategory;
                }
            }
        }
    }

    private function get_full_provider_category_name( $row, $categoryColumns) {
        $names = [];

        foreach ($categoryColumns as $column) {
            if (!empty( $row[$column])) {
                $names[] = trim( $row[$column]);
            }
        }

        return join( ',', $names);
    }

    private function find_best_category_match( $fullProviderCategoryName, $categoriesMapping) {
        $result = null;

        if (!empty( $categoriesMapping['mappings'][$fullProviderCategoryName])) {
            $result = $categoriesMapping['mappings'][$fullProviderCategoryName];
        }

        if ($result !== null) {
            if (is_numeric( $result)) {
                $result .= '-forceId';
            } else {
                $category_name = $this->get_category_name_components( $result);
                $result = count( $category_name) - 1 >= 0 ? $category_name[count( $category_name) - 1] : null;
            }
        }

        return $result;
    }

    private function get_columns_with_id_instead_of_name( $columnDefs) {
        $result = [];

        foreach ($columnDefs as $columnDef) {
            if (isset( $columnDef['id_instead_of_name']) &&
                $columnDef['id_instead_of_name'] === '1') {
                $result[] = $columnDef['custom_name'];
            }
        }

        return $result;
    }

    private function get_category_name( $categoryId) {
        $result = null;

        if ($categoryId !== null && isset( $this->allCategories[$categoryId])) {
            $result = $this->allCategories[$categoryId]['name'][$this->default_language_id];
        }

        return $result;
    }

    private function get_category_name_components( $categoryId) {
        $result = [];

        while ($categoryId !== null && isset( $this->allCategories[$categoryId])) {
            $result[] = $this->allCategories[$categoryId]['name'][$this->default_language_id];

            if (isset( $this->allCategories[$categoryId]['parent_id'])) {
                $categoryId = $this->allCategories[$categoryId]['parent_id'];
            }
        }

        $result = array_reverse( $result);

        return $result;
    }

    private function check_duplicated_product_ids( $data_file) {
        $product_ids = [];

        if (count( $data_file) > 0 && isset( $data_file[0]['product'])) {
            foreach ($data_file as $item) {
                $product_id = $item['product']['product_id'];

                if (!in_array( $product_id, $product_ids)) {
                    $product_ids[] = $product_id;
                } else {
                    $this->exception( sprintf($this->language->get( 'profile_import_duplicated_product_id_found'),$product_id, json_encode($item['product'])));
                }
            }
        }
    }

    /* private function build_product_description( $product_id, $name) {
        return [
            'description' => $name,
            'name' => $name,
            'meta_description' => '',
            'meta_title' => '',
            'meta_keyword' => '',
            'tag' => '',
            'language_id' => $this->default_language_id,
            'product_id' => $product_id
        ];
    } */

    private function are_all_categories_mapped( $profile) {
        foreach ($profile['categories_mapping']['mappings'] as $target_category_id) {
            if ($target_category_id === '') {
                return false;
            }
        }

        return true;
    }
}
?>
