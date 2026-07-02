<?php
class ModelExtensionModuleIeProProducts extends ModelExtensionModuleIePro
{
    var $products_models = array();
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->cat_name = 'products';
    }

    public function set_model_tables_and_fields($special_tables = array(), $special_tables_description = array(), $delete_tables = array()) {
        $this->main_table = 'product';
        $this->main_field = 'product_id';

        $delete_tables = array(
            'product_attribute',
            'product_description',
            'product_discount',
            'product_filter',
            'product_image',
            'product_option',
            'product_option_value',
            'product_related',
            'product_related',
            'product_reward',
            'product_special',
            'product_to_category',
            'product_to_download',
            'product_to_layout',
            'product_to_store',
            'review',
            'product_recurring',
            'product_profile',
            'coupon_product',
        );

        $delete_tables = $this->remove_tables($this->database_schema, $delete_tables);

        $this->delete_tables_special = array(
            'product_related',
            'seo_url',
            'url_alias',
        );

        $special_tables = array(
            'product_to_category',
            'product_filter',
            'product_attribute',
            'product_special',
            'product_discount',
            'product_related',
            'product_reward',
            'product_image',
            'product_to_store',
            'product_to_download',
            'product_to_layout',
            'product_option_value',
            'seo_url',
        );

        if($this->hasOptionsCombinations) {
            array_push($special_tables, 'product_options_combinations');
            array_push($delete_tables, 'product_options_combinations');
            array_push($delete_tables, 'product_options_combinations_bullets');
        }

        $special_tables_description = array('filter_group_description');
        parent::set_model_tables_and_fields($special_tables, $special_tables_description, $delete_tables);
    }
    public function get_columns($configuration = array())
    {
        $configuration = $this->clean_array_extension_prefix($configuration);
        $profile_id = array_key_exists('profile_id', $configuration) && !empty($configuration['profile_id']) ? $configuration['profile_id'] : '';

        $multilanguage = array_key_exists('multilanguage', $configuration) ? $configuration['multilanguage'] : false;
        $category_tree = array_key_exists('category_tree', $configuration) && $configuration['category_tree'];
        $cat_number = array_key_exists('cat_number', $configuration) && $configuration['cat_number'] ? (int)$configuration['cat_number'] : 0;
        $cat_tree_number = array_key_exists('cat_tree_number', $configuration) && $configuration['cat_tree_number'] ? (int)$configuration['cat_tree_number'] : 0;
        $cat_tree_children_number = array_key_exists('cat_tree_children_number', $configuration) && $configuration['cat_tree_children_number'] ? (int)$configuration['cat_tree_children_number'] : 0;
        $image_number = array_key_exists('image_number', $configuration) && $configuration['image_number'] ? (int)$configuration['image_number'] : 0;
        $attribute_number = array_key_exists('attribute_number', $configuration) && $configuration['attribute_number'] ? (int)$configuration['attribute_number'] : 0;
        $special_number = array_key_exists('special_number', $configuration) && $configuration['special_number'] ? (int)$configuration['special_number'] : 0;
        $discount_number = array_key_exists('discount_number', $configuration) && $configuration['discount_number'] ? (int)$configuration['discount_number'] : 0;
        $filter_group_number = array_key_exists('filter_group_number', $configuration) && $configuration['filter_group_number'] ? (int)$configuration['filter_group_number'] : 0;
        $filter_number = array_key_exists('filter_number', $configuration) && $configuration['filter_number'] ? (int)$configuration['filter_number'] : 0;
        $download_number = array_key_exists('download_number', $configuration) && $configuration['download_number'] ? (int)$configuration['download_number'] : 0;

        $is_option_combinations_installed = $this->hasOptionsCombinations;
        if ($is_option_combinations_installed) {
            $opt_comb_number = array_key_exists('option_combinations_number', $configuration) ? $configuration['option_combinations_number'] : 2;
            $opt_comb_images_number = array_key_exists('option_combinations_images_number', $configuration) ? $configuration['option_combinations_images_number'] : 0;
            $opt_comb_discounts_number = array_key_exists('option_combinations_discounts_number', $configuration) ? $configuration['option_combinations_discounts_number'] : 0;
            $opt_comb_specials_number = array_key_exists('option_combinations_specials_number', $configuration) ? $configuration['option_combinations_specials_number'] : 0;
            $opt_comb_prices_by_customer_group = array_key_exists('options_combinations_prices_by_customer_group', $configuration) && $configuration['options_combinations_prices_by_customer_group'];
            $opt_comb_points_by_customer_group = array_key_exists('options_combinations_points_by_customer_group', $configuration) && $configuration['options_combinations_points_by_customer_group'];
        }

        $columns = $this->get_columns_formatted($multilanguage);

        $final_columns = array();
        foreach ($columns as $column_name => $field_info) {
            if ($column_name == 'categories_tree' || $column_name == 'filters') {
                $col_number = $column_name == 'categories_tree' ? $cat_tree_number : $filter_group_number;
                $col_number2 = $column_name == 'categories_tree' ? $cat_tree_children_number : $filter_number;
                $col_number = $column_name == 'categories_tree' && !$category_tree ? 0 : $col_number;

                if ($col_number > 0) {
                    $col_parent = $field_info['parent'];
                    $col_parent_name = $field_info['parent']['name'];
                    $col_children = $field_info['children'];
                    $col_children_name = $field_info['children']['name'];
                    if ($col_number > 0) {
                        for ($i = 1; $i <= $col_number; $i++) {
                            $col_info = $col_parent;
                            $col_name = sprintf($col_info['hidden_fields']['name'], $i);
                            $col_info['hidden_fields']['name'] = $col_name;
                            $col_info['custom_name'] = $col_name;
                            $col_info['hidden_fields']['identificator'] = $i;
                            $final_columns[$col_name] = $col_info;

                            if ($col_number2 > 0) {
                                for ($j = 1; $j <= $col_number2; $j++) {
                                    $col_info = $col_children;
                                    $col_name = sprintf($col_info['hidden_fields']['name'], $i, $j);
                                    $col_info['hidden_fields']['name'] = $col_name;
                                    $col_info['custom_name'] = $col_name;
                                    $col_info['hidden_fields']['identificator'] = $i . '_' . $j;
                                    $final_columns[$col_name] = $col_info;

                                }
                            }
                        }
                    }
                }
            } else if (($column_name == 'Cat. %s') || $column_name == 'Image %s') {
                $col_number = $column_name == 'Cat. %s' ? $cat_number : $image_number;
                $col_number = $column_name == 'Cat. %s' && $category_tree ? 0 : $col_number;

                if ($col_number > 0) {
                    for ($i = 1; $i <= $col_number; $i++) {
                        $col_info = $field_info;
                        $col_name = sprintf($col_info['hidden_fields']['name'], $i);
                        $col_info['hidden_fields']['name'] = $col_name;
                        $col_info['hidden_fields']['identificator'] = $i;
                        $col_info['custom_name'] = $col_name;
                        $final_columns[$col_name] = $col_info;
                    }
                }
            } else if (in_array($column_name, array('specials', 'discounts'))) {
                $col_number = $column_name == 'specials' ? $special_number : $discount_number;

                if ($col_number > 0) {
                    for ($i = 1; $i <= $col_number; $i++) {
                        foreach ($this->customer_groups as $id => $cg) {
                            foreach ($field_info as $field_info_temp) {
                                $col_info = $field_info_temp;
                                $col_name = sprintf($col_info['hidden_fields']['name'], $i, $cg['name']);
                                $col_info['hidden_fields']['name'] = $col_name;
                                $col_info['hidden_fields']['customer_group_id'] = $cg['customer_group_id'];
                                $col_info['custom_name'] = $col_name;
                                $col_info['hidden_fields']['identificator'] = $i.'_'.$cg['customer_group_id'];
                                $final_columns[$col_name] = $col_info;
                            }
                        }
                    }
                }
            } else if ($column_name == 'Points %s') {
                foreach ($this->customer_groups as $id => $cg) {
                    $col_name = sprintf($column_name, $cg['name']);
                    $field_info['hidden_fields']['name'] = $col_name;
                    $field_info['hidden_fields']['customer_group_id'] = $cg['customer_group_id'];
                    $field_info['hidden_fields']['identificator'] = $cg['customer_group_id'];
                    $field_info['custom_name'] = $col_name;
                    $final_columns[$col_name] = $field_info;
                }
            } else if (in_array($column_name, array('attributes', 'downloads'))) {
                $col_number = $column_name == 'attributes' ? $attribute_number : $download_number;

                if ($col_number > 0) {
                    for ($i = 1; $i <= $col_number; $i++) {
                        foreach ($field_info as $field_info_temp) {
                            $col_info = $field_info_temp;
                            $col_name = sprintf($col_info['hidden_fields']['name'], $i);
                            $col_info['hidden_fields']['identificator'] = $i;
                            $col_info['hidden_fields']['name'] = $col_name;
                            $col_info['custom_name'] = $col_name;
                            $final_columns[$col_name] = $col_info;
                        }
                    }
                }
            } else if (
                in_array($column_name,
                    array('Opt. Comb. Model', 'Opt. Comb. Quantity', 'options_combinations_prices', 'opt_cmb_points', 'option_combinations',
                        'Opt. Comb. Subtract stock', 'opt_cmb_specials', 'opt_cmb_discounts', 'Opt. Comb. Image %s', 'Opt. Comb. Weight',
                        'Opt. Comb. Weight Prefix', 'Opt. Comb. Length', 'Opt. Comb. Width', 'Opt. Comb. Height', 'Opt. Comb. Extra',)) &&
                $is_option_combinations_installed) {

                if ($column_name == 'options_combinations_prices' || $column_name == 'opt_cmb_points') {
                    $by_customer_group = 0;
                    switch ($column_name){
                        case 'options_combinations_prices':
                            $by_customer_group = $opt_comb_prices_by_customer_group;
                            break;
                        case 'opt_cmb_points':
                            $by_customer_group = $opt_comb_points_by_customer_group;
                            break;
                    }
                    if ($by_customer_group) {
                        foreach ($this->customer_groups as $cg) {
                            foreach ($field_info as $col_name => $col_info) {
                                $col_name = sprintf($col_name, $cg['name']);
                                $col_info['hidden_fields']['name'] = $col_name;
                                $col_info['hidden_fields']['customer_group_id'] = $cg['customer_group_id'];
                                $col_info['hidden_fields']['identificator'] = $cg['customer_group_id'];
                                $col_info['custom_name'] = $col_name;
                                $final_columns[$col_name] = $col_info;
                            }
                        }
                    } else {
                        foreach ($field_info as $col_name => $col_info) {
                            $col_name = str_replace(' %s', '', $col_name);
                            $col_info['hidden_fields']['name'] = $col_name;
                            $col_info['custom_name'] = $col_name;
                            $col_info['hidden_fields']['customer_group_id'] = $this->config->get('config_customer_group_id');
                            $col_info['hidden_fields']['identificator'] = $this->config->get('config_customer_group_id');
                            $final_columns[$col_name] = $col_info;
                        }
                    }
                } else if (in_array($column_name, ['option_combinations', 'opt_cmb_specials', 'opt_cmb_discounts'])) {
                    $number = 0;
                    switch ($column_name){
                        case 'option_combinations':
                            $number = $opt_comb_number;
                            break;
                        case 'opt_cmb_specials':
                            $number = $opt_comb_specials_number;
                            break;
                        case 'opt_cmb_discounts':
                            $number = $opt_comb_discounts_number;
                            break;
                    }
                    for ($i = 1; $i <= $number; $i++) {
                        foreach ($field_info as $col_name => $col_info) {
                            $col_name = sprintf($col_name, $i);
                            $col_info['hidden_fields']['name'] = $col_name;
                            $col_info['hidden_fields']['identificator'] = $i;
                            $col_info['custom_name'] = $col_name;
                            $final_columns[$col_name] = $col_info;
                        }
                    }
                } elseif ($column_name == 'Opt. Comb. Image %s') {
                    for ($i = 1; $i <= $opt_comb_images_number; $i++) {
                        $col_name = sprintf($column_name, $i);
                        $field_info['hidden_fields']['name'] = $col_name;
                        $field_info['hidden_fields']['identificator'] = $i;
                        $field_info['custom_name'] = $col_name;
                        $final_columns[$col_name] = $field_info;
                    }
                }
                else {
                    $final_columns[$column_name] = $field_info;
                }
            }  else {
                $final_columns[$column_name] = $field_info;
            }
        }
        $columns = $this->format_columns_multilanguage_multistore($final_columns);

        if (!empty($profile_id)) {
            $col_map = $this->model_extension_module_ie_pro_tab_profiles->get_columns($profile_id);
            // Check if a column was added and add it to the profile columns
            foreach ($columns as $col_name => $col_info) {
                if (!array_key_exists($col_name, $col_map)) {
                    $col_info['status'] = 0;
                    $col_map[$col_name] = $col_info;
                }
            }
            // Check if a column was removed and remove it from the profile columns
            foreach ($col_map as $col_name => $col_info){
                if (!array_key_exists($col_name, $columns)) {
                    unset($col_map[$col_name]);
                }
            }

            $columns = $col_map;
        }

        if($configuration['file_format'] == 'xml' && empty($profile_id)) {
            foreach ($columns as $column_name => $col_info) {
                $columns[$column_name]['custom_name'] = $this->format_column_name($col_info['custom_name']);
            }
        }

        return $columns;
    }

    function get_columns_formatted($multilanguage)
    {
        $fields = array(
            'Product ID' => array('hidden_fields' => array('table' => 'product', 'field' => 'product_id')),
            'Model' => array('hidden_fields' => array('table' => 'product', 'field' => 'model')),
            'Name' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'name'), 'multilanguage' => $multilanguage),
            'Description' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'description', 'allow_max_length' => true, 'strip_html_tags' => true), 'multilanguage' => $multilanguage),
            'Meta description' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'meta_description'), 'multilanguage' => $multilanguage),
            'Meta title' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'meta_title'), 'multilanguage' => $multilanguage),
            'Meta H1' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'meta_h1'), 'multilanguage' => $multilanguage),
            'Meta keywords' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'meta_keyword'), 'multilanguage' => $multilanguage),
            'Product link' => array('hidden_fields' => array('table' => 'product', 'field' => 'product_id', 'conversion_product_link' => true, 'skip_export_conditions' => true, 'only_for' => 'export'), 'multilanguage' => $multilanguage && $this->is_oc_3x, 'multistore' => $this->is_oc_3x),
            'SEO url' => array('hidden_fields' => array('table' => 'seo_url', 'field' => 'keyword'), 'multilanguage' => $multilanguage && $this->is_oc_3x, 'multistore' => $this->is_oc_3x),
            'Tags' => array('hidden_fields' => array('table' => 'product_description', 'field' => 'tag'), 'multilanguage' => $multilanguage),
            'SKU' => array('hidden_fields' => array('table' => 'product', 'field' => 'sku')),
            'EAN' => array('hidden_fields' => array('table' => 'product', 'field' => 'ean')),
            'UPC' => array('hidden_fields' => array('table' => 'product', 'field' => 'upc')),
            'JAN' => array('hidden_fields' => array('table' => 'product', 'field' => 'jan')),
            'MPN' => array('hidden_fields' => array('table' => 'product', 'field' => 'mpn')),
            'ISBN' => array('hidden_fields' => array('table' => 'product', 'field' => 'isbn')),
            'Minimum' => array('hidden_fields' => array('table' => 'product', 'field' => 'minimum')),
            'Subtract' => array('hidden_fields' => array('table' => 'product', 'field' => 'subtract')),
            'Out stock status' => array('hidden_fields' => array('table' => 'product', 'field' => 'stock_status_id', 'conversion_global_var' => 'stock_statuses', 'conversion_global_index' => 'name')),
            'Price' => array('hidden_fields' => array('table' => 'product', 'field' => 'price'), 'profit_margin' => ''),
            'Tax class' => array('hidden_fields' => array('table' => 'product', 'field' => 'tax_class_id', 'conversion_global_var' => 'tax_classes', 'conversion_global_index' => 'title')),
            'Quantity' => array('hidden_fields' => array('table' => 'product', 'field' => 'quantity')),
            'Main image' => array('hidden_fields' => array('table' => 'product', 'field' => 'image'), 'splitted_values' => ''),
            'Image %s' => array('hidden_fields' => array('table' => 'product_image', 'field' => 'image'), 'splitted_values' => ''),
            'Manufacturer' => array('hidden_fields' => array('table' => 'product', 'field' => 'manufacturer_id', 'name_instead_id' => true, 'conversion_global_var' => 'all_manufacturers', 'allow_ids' => true)),
            'Main category' => array('hidden_fields' => array('table' => 'product_to_category', 'field' => 'main_category', 'allow_ids' => true)),
            'Cat. %s' => array('hidden_fields' => array('table' => 'product_to_category', 'field' => 'category_id', 'allow_ids' => true), 'multilanguage' => $multilanguage, 'splitted_values' => ''),
            'categories_tree' => array(
                'parent' => array('name' => 'Cat. tree %s parent', 'hidden_fields' => array('table' => 'product_to_category', 'field' => 'name', 'allow_ids' => true), 'multilanguage' => $multilanguage, 'splitted_values' => ''),
                'children' => array('name' => 'Cat. tree %s level %s', 'hidden_fields' => array('table' => 'product_to_category', 'field' => 'name', 'allow_ids' => true), 'multilanguage' => $multilanguage, 'splitted_values' => ''),
            ),
            'Points' => array('hidden_fields' => array('table' => 'product', 'field' => 'points')),
            'Points %s' => array('hidden_fields' => array('table' => 'product_reward', 'field' => 'points')),
            'Weight class' => array('hidden_fields' => array('table' => 'product', 'field' => 'weight_class_id', 'conversion_global_var' => 'weight_classes', 'conversion_global_index' => 'title')),
            'Weight' => array('hidden_fields' => array('table' => 'product', 'field' => 'weight')),
            'Length class' => array('hidden_fields' => array('table' => 'product', 'field' => 'length_class_id', 'conversion_global_var' => 'length_classes', 'conversion_global_index' => 'title')),
            'Length' => array('hidden_fields' => array('table' => 'product', 'field' => 'length')),
            'Width' => array('hidden_fields' => array('table' => 'product', 'field' => 'width')),
            'Height' => array('hidden_fields' => array('table' => 'product', 'field' => 'height')),
            'Option' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'option_name', 'allow_ids' => true), 'multilanguage' => $multilanguage),
            'Option required' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'option_required')),
            'Option type' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'option_type')),
            'Option value' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'name', 'allow_ids' => true), 'multilanguage' => $multilanguage),
            'Option value sort order' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'sort_order')),
            'Option subtract' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'subtract')),
            'Option image' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'image')),
            'Option quantity' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'quantity')),
            'Option price prefix' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'price_prefix')),
            'Option price' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'price'), 'profit_margin' => ''),
            'Option points prefix' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'points_prefix')),
            'Option points' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'points')),
            'Option weight prefix' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'weight_prefix')),
            'Option weight' => array('hidden_fields' => array('table' => 'product_option_value', 'field' => 'weight')),
            'Products related' => array('hidden_fields' => array('table' => 'product_related', 'field' => 'related'), 'product_id_identificator' => 'model'),
            'Date available' => array('hidden_fields' => array('table' => 'product', 'field' => 'date_available')),
            'Date added' => array('hidden_fields' => array('table' => 'product', 'field' => 'date_added')),
            'Date modified' => array('hidden_fields' => array('table' => 'product', 'field' => 'date_modified')),
            'Requires shipping' => array('hidden_fields' => array('table' => 'product', 'field' => 'shipping')),
            'Location' => array('hidden_fields' => array('table' => 'product', 'field' => 'location')),
            'Sort order' => array('hidden_fields' => array('table' => 'product', 'field' => 'sort_order')),
            'Store' => array('hidden_fields' => array('table' => 'product_to_store', 'field' => 'store_id')),
            'Status' => array('hidden_fields' => array('table' => 'product', 'field' => 'status')),
            'Layout' => array('multistore' => true, 'hidden_fields' => array('table' => 'product_to_layout', 'field' => 'layout_id', 'conversion_global_var' => 'layouts')),
            'specials' => array(
                'Spe. %s Priority %s' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'priority'),),
                'Spe. %s Price %s' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'price'), 'profit_margin' => ''),
                'Spe. %s Date start %s' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'date_start'),),
                'Spe. %s Date end %s' => array('hidden_fields' => array('table' => 'product_special', 'field' => 'date_end'),),
            ),
            'discounts' => array(
                'Dis. %s Quantity %s' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'quantity'),),
                'Dis. %s Priority %s' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'priority'),),
                'Dis. %s Price %s' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'price'), 'profit_margin' => ''),
                'Dis. %s Date start %s' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'date_start'),),
                'Dis. %s Date end %s' => array('hidden_fields' => array('table' => 'product_discount', 'field' => 'date_end'),),
            ),
            'attributes' => array(
                'Attr. Group %s' => array('hidden_fields' => array('table' => 'product_attribute', 'field' => 'attribute_group', 'allow_ids' => true), 'multilanguage' => $multilanguage),
                'Attribute %s' => array('hidden_fields' => array('table' => 'product_attribute', 'field' => 'attribute', 'allow_ids' => true), 'multilanguage' => $multilanguage),
                'Attribute value %s' => array('hidden_fields' => array('table' => 'product_attribute', 'field' => 'attribute_value'), 'multilanguage' => $multilanguage),
            ),
            'filters' => array(
                'parent' => array('hidden_fields' => array('table' => 'product_filter', 'field' => 'name', 'allow_ids' => true), 'name' => 'Filter Group %s', 'multilanguage' => $multilanguage),
                'children' => array('hidden_fields' => array('table' => 'product_filter', 'field' => 'name', 'allow_ids' => true), 'name' => 'Filter Gr. %s filter %s', 'multilanguage' => $multilanguage),
            ),
            'downloads' => array(
                'Download name %s' => array('hidden_fields' => array('table' => 'product_to_download', 'field' => 'name'),'multilanguage' => $multilanguage),
                'Download file %s' => array('hidden_fields' => array('table' => 'product_to_download', 'field' => 'filename')),
                'Download hash %s' => array('hidden_fields' => array('table' => 'product_to_download', 'field' => 'hash')),
                'Download mask %s' => array('hidden_fields' => array('table' => 'product_to_download', 'field' => 'mask')),
            ),
        );

        if ($this->hasOptionsCombinations){
            $fields['option_combinations'] = array(
                'Comb. %s Option' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'options', 'allow_ids' => true, 'data_type' => 'json', 'inner_field' => 'option'), 'multilanguage' => $multilanguage),
                'Comb. %s Option value' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'options', 'allow_ids' => true,'data_type' => 'json', 'inner_field' => 'option_value'), 'multilanguage' => $multilanguage),
                'Comb. %s Option type' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'options','data_type' => 'json', 'inner_field' => 'option_type'),),
                'Comb. %s Option value image' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'options','data_type' => 'json', 'inner_field' => 'option_image'),),
            );
            $fields['Opt. Comb. Model'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'model'),);
            $fields['Opt. Comb. Quantity'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'quantity'),);
            $fields['options_combinations_prices'] = array(
                'Opt. Comb. Price %s' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_price', 'key' => 'price'),),
                'Opt. Comb. Price Prefix %s' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_price', 'key' => 'price_prefix'),),
            );
            $fields['Opt. Comb. Subtract stock'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'subtract'),);
            $fields['opt_cmb_specials'] = array(
                'Opt. Comb. Spe. %s Cust. Group' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'allow_ids' => true, 'data_type' => 'json', 'inner_field' => 'option_special', 'key' => 'customer_group_id',),'multilanguage' => $multilanguage),
                'Opt. Comb. Spe. %s Priority' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_special', 'key' => 'priority',),),
                'Opt. Comb. Spe. %s Price' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_special', 'key' => 'price'),),
                'Opt. Comb. Spe. %s Date start' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_special', 'key' => 'date_start'),),
                'Opt. Comb. Spe. %s Date end' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_special', 'key' => 'date_end'),),
            );
            $fields['opt_cmb_discounts'] = array(
                'Opt. Comb. Dis. %s Cust. Group' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'allow_ids' => true, 'data_type' => 'json', 'inner_field' => 'option_discount', 'key' => 'customer_group_id',), 'multilanguage' => $multilanguage),
                'Opt. Comb. Dis. %s Quantity' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_discount', 'key' => 'quantity',),),
                'Opt. Comb. Dis. %s Priority' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_discount', 'key' => 'priority',),),
                'Opt. Comb. Dis. %s Price' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_discount', 'key' => 'price'),),
                'Opt. Comb. Dis. %s Date start' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_discount', 'key' => 'date_start'),),
                'Opt. Comb. Dis. %s Date end' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_discount', 'key' => 'date_end'),),
            );
            $fields['opt_cmb_points'] = array(
                'Opt. Comb. Points %s' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_points', 'key' => 'points',),),
                'Opt. Comb. Points Prefix %s' => array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'prices', 'data_type' => 'json', 'inner_field' => 'option_points', 'key' => 'points_prefix',),),
            );
            $fields['Opt. Comb. Image %s'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'images', 'data_type' => 'json', 'is_image' => true),);
            $fields['Opt. Comb. SKU'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'sku',),);
            $fields['Opt. Comb. UPC'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'upc',),);
            $fields['Opt. Comb. Weight'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'weight',),);
            $fields['Opt. Comb. Weight Prefix'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'weight_prefix',),);
            $fields['Opt. Comb. Length'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'length',),);
            $fields['Opt. Comb. Width'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'width',),);
            $fields['Opt. Comb. Height'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'height',),);
            $fields['Opt. Comb. Extra'] = array('hidden_fields' => array('table' => 'product_options_combinations', 'field' => 'extra_text',),);
        }

        $fields['Delete'] = array('hidden_fields' => array('table' => 'empty_columns', 'field' => 'delete', 'is_boolean' => true, 'type' => 'boolean', 'real_type' => 'tinyint'));


        if(version_compare(VERSION, '2', '<')) {
            unset($fields['Meta title']);
        }

        if(version_compare(VERSION, '1.5.3.1', '<=')) {
            unset($fields['EAN']);
            unset($fields['JAN']);
            unset($fields['MPN']);
            unset($fields['ISBN']);
            unset($fields['Tags']);
        }

        if (!$this->is_ocstore) {
            unset($fields['Meta H1']);
        }

        if (!$this->main_category) {
            unset($fields['Main category']);
        }

        if(!$this->is_oc_3x) {
            unset($fields['SEO url']['multistore']);
            unset($fields['SEO url']['multilanguage']);
        }

        if($this->has_custom_fields)
            $fields = $this->model_extension_module_ie_pro_tab_custom_fields->add_custom_fields_to_columns_special_tables($fields, $this->cat_name, $multilanguage);

        if(is_file($this->assets_path.'model_ie_pro_products_add_custom_columns.php'))
            require_once($this->assets_path.'model_ie_pro_products_add_custom_columns.php');

        $final_fields = array();

        foreach ($fields as $col_name => $col_info) {
            if (in_array($col_name, array('specials', 'discounts', 'categories_tree', 'attributes', 'filters', 'downloads', 'option_combinations', 'options_combinations_prices', 'opt_cmb_specials', 'opt_cmb_discounts', 'opt_cmb_points'))) {
                $final_fields[$col_name] = array();
                if (in_array($col_name, array('categories_tree', 'filters'))) {
                    $to_each = array('parent', 'children');
                    foreach ($to_each as $key => $col_type) {
                        $col_info_temp = $this->format_default_column($col_info[$col_type]['name'], $col_info[$col_type]);
                        $final_fields[$col_name][$col_type] = $col_info_temp;
                    }
                } else {
                    $columns = $col_info;
                    foreach ($columns as $col_name_temp => $col_info_temp) {
                        $col_info_temp = $this->format_default_column($col_name_temp, $col_info_temp);
                        $final_fields[$col_name][$col_name_temp] = $col_info_temp;
                    }
                }
            } else {
                $final_fields[$col_name] = $this->format_default_column($col_name, $col_info);
            }
        }

        $final_fields = parent::put_type_to_columns_formatted($final_fields, $multilanguage);

        return $final_fields;
    }

    /*
     * In this function create first data related with products before import products (categories, options, filters...)
     * Also format all product data to structure
     */
    public function pre_import($data_file) {
        if(is_file(DIR_SYSTEM.'assets/ie_pro_includes/' . 'model_ie_pro_products_function_pre_import_begin.php')){
            require_once(DIR_SYSTEM.'assets/ie_pro_includes/' . 'model_ie_pro_products_function_pre_import_begin.php');
        }

        if(is_file(DIR_SYSTEM.'assets/ie_pro_includes/' . 'import_xml_subproducts_inside_main_tag_process_opt_prices.php')){
            require_once(DIR_SYSTEM.'assets/ie_pro_includes/' . 'import_xml_subproducts_inside_main_tag_process_opt_prices.php');
        }

        $this->pre_import_create_product_associated_data($data_file);
        $this->product_by_key = $this->get_product_by_key();
        $special_tables = array(
            'product_description',
            'product_option_value',
            'product_image',
            'product_to_category',
            'product_attribute',
            'product_filter',
            'product_to_download',
            'seo_url',
            'product_reward',
            'product_related',
            'product_to_store',
            'product_to_layout',
            'product_special',
            'product_discount',
        );

        $product_id = '';
        $main_counter_product_ids = 1;

        $copy_data_file = array();

        $this->update_process($this->language->get('progress_import_elements_process_start'));
        $element_to_process = count($data_file);
        $element_processed = 0;
        $this->update_process(sprintf($this->language->get('progress_import_elements_processed'), $element_processed, $element_to_process));

        $first_product = $data_file[0];
        if(!array_key_exists($this->main_table, $first_product) || !array_key_exists($this->product_identificator, $first_product[$this->main_table]))
            $this->exception(sprintf($this->language->get('progress_import_error_main_identificator'), $this->product_identificator));

        $product_identificator_last = '';
        $parent_skipped = false;
        foreach ($data_file as $row_file_number => $tables_fields) {
            $temp = array();
            $is_option_row = false;
            $skipped = false;

            $product_identificator = $tables_fields[$this->main_table][$this->product_identificator];

            //For avoid problem in product indifiers like model with symbols like &
            if($this->product_identificator != 'product_id')
                $tables_fields[$this->main_table][$this->product_identificator] = htmlspecialchars($product_identificator);


            if($this->has_options) {
                $options_formated = $this->_importing_process_format_product_option_value($tables_fields['product_option_value'], $row_file_number, $product_id, $tables_fields);
                if($options_formated == 'no_option_valid')
                    continue;

                $some_options_data = array_filter($options_formated);

                $is_option_row = empty($product_identificator) && !empty($some_options_data);

                if(!$is_option_row) {
                    $is_option_row = !empty($product_identificator) && $product_identificator == $product_identificator_last ? true : false;
                }

                if(!$is_option_row) {
                    /*if (!empty($some_options_data)) {
                        $this->exception(sprintf($this->language->get('progress_import_product_error_option_data_in_main_row'), ($row_file_number+2)));
                    }*/
                    $temp['product_option'] = array();
                    $temp['product_option_value'] = array();
                } else {
                    $has_option_value = array_key_exists('option_value_id', $options_formated) && !empty($options_formated['option_value_id']);

                    if($has_option_value)
                        $temp['product_option_value'] = $options_formated;

                    $temp['product_option'] = $options_formated;
                }
                unset($tables_fields['product_option_value']);
            }

            $is_option_combination_row = false;
//                Check if it has options combinations columns
            if(
                $this->hasOptionsCombinations &&
                array_key_exists('product_options_combinations', $tables_fields)
            ){
                $option_combinations_formatted = $this->_importing_process_format_product_option_combinations($tables_fields['product_options_combinations'], $row_file_number, $product_id);
                $product_identificator = $tables_fields[$this->main_table][$this->product_identificator];
                $some_options_cmb_data = array_filter($option_combinations_formatted);
                $is_option_combination_row = empty($product_identificator) && !empty($some_options_cmb_data);

                unset($tables_fields['product_options_combinations']);
            }

            if (
                $this->hasOptionsCombinations &&
                array_key_exists('product_options_combinations', $tables_fields) &&
                !$is_option_combination_row
            ){
                $temp['product_options_combinations'] = array();
            }

            if(!$is_option_row && !$is_option_combination_row) {
                $parent_skipped = false;

                $creating = false;
                $editting = false;

                $product_id = $this->search_product_id($tables_fields);

                $creating = empty($product_id);
                $editting = !empty($product_id);
                $assigned_id = false;

                if(($creating && $this->skip_on_create) || ($editting && $this->skip_on_edit)) {
                    $product_id = 'SKIPPED';
                    $skipped = true;
                } else {
                    if(empty($product_id)) {
                        $identificator_value = array_key_exists($this->product_identificator, $tables_fields[$this->main_table]) ? $tables_fields[$this->main_table][$this->product_identificator] : '';
                        $force_id = $this->product_identificator == 'product_id' && !empty($identificator_value) ? $identificator_value : false;
                        $product_id = $this->assign_product_id($force_id);
                        $assigned_id = true;
                    }
                }

                if(!$skipped) {
                    foreach ($tables_fields as $table_name => $fields) {
                        if ($table_name == $this->main_table || !in_array($table_name, $special_tables)) {
                            $temp[$table_name] = $fields;
                            if($table_name != 'empty_columns')
                                $temp[$table_name]['product_id'] = $product_id;
                        } else {

                            if($table_name == 'product_special')
                                $fields['main_price'] = !empty($tables_fields['product']['price']) ? $tables_fields['product']['price'] : 0;

                            $processed_data = $this->{'_importing_process_format_' . $table_name}($fields, $product_id, $row_file_number, $creating);

                            if(!empty($processed_data))
                                $temp[$table_name] = $processed_data;
                        }

                        if($table_name == 'seo_url' && !$this->is_oc_3x) {
                            $copy_seo_url = $temp['seo_url'];
                            unset($temp['seo_url']);
                            $temp['url_alias'] = $copy_seo_url;
                        }
                    }
                }
            } else {
                if($parent_skipped)
                    continue;

                $skipped = true;
                if ($product_id != 'SKIPPED') {
                    $last_index = count($copy_data_file) - 1;

                    if ($last_index >= 0) {
                        if ($is_option_row) {
                            $copy_data_file[$last_index]['product_option_value'][] = $options_formated;
                            $copy_data_file[$last_index]['product_option'][] = $options_formated;
                        }
                        elseif($is_option_combination_row){
                            $copy_data_file[$last_index]['product_options_combinations'][] = $option_combinations_formatted;
                        }
                    }
                }
            }

            if(!$skipped) {
                $model = array_key_exists($this->main_table, $temp) && array_key_exists('model', $temp[$this->main_table]) && !empty($temp[$this->main_table]['model']) ? $temp[$this->main_table]['model'] : '';
                if(!empty($model)) $this->products_models[$model] = $product_id;

                //<editor-fold desc="Autogenerate SEO url">
                if($this->auto_seo_generator != '') {
                    $temp[$this->table_seo] = $this->get_seo_url_autogenerated($temp);
                }
                //</editor-fold>

                if(!array_key_exists('empty_columns', $temp))
                    $temp['empty_columns'] = array();

                $temp['empty_columns']['editting'] = $editting;
                $temp['empty_columns']['creating'] = $creating;

                if($assigned_id)
                    $temp['empty_columns']['forced_id'] = true;

                $skip_this_product = $editting && is_array($this->pre_filters_skip_elements_shop)  && in_array($product_id, $this->pre_filters_skip_elements_shop);

                if($skip_this_product)
                    $parent_skipped = true;
                else
                    $copy_data_file[] = $temp;

            }
            $product_identificator_last = $product_identificator;
            $element_processed++;
            $this->update_process(sprintf($this->language->get('progress_import_elements_processed'), $element_processed, $element_to_process), true);
        }

        //Options combinations - Fix for prices + strict update enabled
        if($this->hasOptionsCombinations) {
            foreach ($copy_data_file as $key => $pro) {
                if(!empty($pro['product_options_combinations']) && $this->strict_update) {
                    foreach ($pro['product_options_combinations'] as $key2 => $opt_comb_data) {
                        if(empty($opt_comb_data['prices']))
                            $copy_data_file[$key]['product_options_combinations'][$key2]['prices'] = '{"option_discount":[],"option_special":[],"option_price":[{"price_prefix":"+","price":0}],"option_points":[]}';
                        if(empty($opt_comb_data['subtract']))
                            $copy_data_file[$key]['product_options_combinations'][$key2]['subtract'] = 1;
                    }
                }
            }
        }

        return $copy_data_file;
    }

    public function get_product_by_key() {
        $results = $this->db->query("SELECT ".$this->escape_database_field('product_id').', '.$this->escape_database_field($this->product_identificator).' FROM '.$this->escape_database_table_name('product'));
        $final_result = array();
        if($results->num_rows) {
            foreach ($results->rows as $key => $prod) {
                $final_result[$prod[$this->product_identificator]] = $prod['product_id'];
            }
        }
        return $final_result;
    }

    public function get_seo_url_autogenerated($product_data) {
        $final_seo_url = array();
        $product_id = $product_data[$this->main_table]['product_id'];
        $query = 'product_id='.$product_id;

        $concatenate_stores = count($this->stores_import_format) > 1;
        $concatenate_languages = count($this->languages) > 1;

        if($this->auto_seo_generator == 'model' && array_key_exists('model', $product_data[$this->main_table])) {
            $seo_name = $product_data[$this->main_table]['model'];
            if($this->is_oc_3x) {
                foreach ($this->stores_import_format as $store) {
                    $store_id = $store['store_id'];
                    $seo_name_copy = $seo_name;

                    foreach ($this->languages as $key => $lang) {
                        $seo_name_copy .= $concatenate_languages ? '_'.$lang['code'] : '';
                        $seo_name_copy .= $concatenate_stores ? '_'.$store_id : '';

                        $language_id = $lang['language_id'];
                        $final_seo_url[] = array(
                            'language_id' => $language_id,
                            'store_id' => $store_id,
                            'query' => $query,
                            'keyword' => $this->format_seo_url($seo_name_copy)
                        );
                    }
                }
            } else {
                $final_seo_url = array(
                    'query' => $query,
                    'keyword' => $seo_name
                );
            }
        } else if(array_key_exists('product_description', $product_data)) {
            if($this->is_oc_3x) {
                foreach ($this->stores_import_format as $store) {
                    $store_id = $store['store_id'];
                    foreach ($this->languages as $key => $lang) {
                        $language_id = $lang['language_id'];

                        $seo_name = '';

                        foreach ($product_data['product_description'] as $key => $description) {
                            $key_seo = $this->auto_seo_generator == 'name_model' ? 'name' : $this->auto_seo_generator;
                            if(array_key_exists($key_seo, $description) && $description['language_id'] == $language_id) {
                                $seo_name = $description[$key_seo];
                                break;
                            }
                        }

                        if($this->auto_seo_generator == 'name_model' && array_key_exists('model', $product_data[$this->main_table]))
                            $seo_name .= '_'.$product_data[$this->main_table]['model'];

                        $seo_name_copy = $seo_name;
                        $seo_name_copy .= $concatenate_languages ? '_'.$lang['code'] : '';
                        $seo_name_copy .= $concatenate_stores ? '_'.$store_id : '';

                        if(!empty($seo_name_copy)) {
                            $final_seo_url[] = array(
                                'language_id' => $language_id,
                                'store_id' => $store_id,
                                'query' => $query,
                                'keyword' => $this->format_seo_url($seo_name_copy)
                            );
                        }
                    }
                }
            } else {
                foreach ($this->languages as $key => $lang) {
                    $language_id = $lang['language_id'];
                    $name = '';
                    $name_main_language = '';
                    foreach ($product_data['product_description'] as $key => $description) {
                        $key_seo = $this->auto_seo_generator == 'name_model' ? 'name' : $this->auto_seo_generator;
                        if(array_key_exists($key_seo, $description) && $description['language_id'] == $language_id) {
                            $name = $description[$key_seo];
                            if($language_id == $this->default_language_id)
                                $name_main_language = $description[$key_seo];
                        }
                    }

                    if(!empty($name) || !empty($name_main_language)) {
                        $seo_name_copy = !empty($name_main_language) ? $name_main_language : $name;

                        if($this->auto_seo_generator == 'name_model' && array_key_exists('model', $product_data[$this->main_table]))
                            $seo_name_copy .= '_'.$product_data[$this->main_table]['model'];

                        $seo_name_copy .= $concatenate_languages ? '_'.$lang['code'] : '';

                        $final_seo_url = array(
                            'query' => $query,
                            'keyword' => $this->format_seo_url($seo_name_copy)
                        );
                    }
                }
            }
        }

        return $final_seo_url;
    }
    public function search_product_id($prod_data) {
        $identificator_value = array_key_exists($this->product_identificator, $prod_data[$this->main_table]) ? $prod_data[$this->main_table][$this->product_identificator] : '';
        $product_id = $this->get_product_id($this->product_identificator, $identificator_value);
        return $product_id;
    }

    public function pre_import_create_product_associated_data($data_file) {
        $this->has_categories =
            ($this->cat_tree && (int)$this->profile['import_xls_cat_tree_number'] > 0 && array_key_exists('product_to_category', $data_file[0]))
            ||
            (!$this->cat_tree && (int)$this->profile['import_xls_cat_number'] > 0 && array_key_exists(0, $data_file) &&  array_key_exists('product_to_category', $data_file[0]));
        if($this->has_categories) {
            $this->load->model('extension/module/ie_pro_categories');
            $this->model_extension_module_ie_pro_categories->create_categores_from_product($data_file);

            if ($this->cat_tree) {
                $this->all_categories = $this->model_extension_module_ie_pro_categories->get_all_categories_tree_import_format();
            } else {
                $this->all_categories = $this->model_extension_module_ie_pro_categories->get_all_categories_import_format(
                    false,
                    $this->all_categories_mapped
                );
            }
        }

        if($this->hasFilters) {
            $this->has_filters = array_key_exists(0, $data_file) && array_key_exists('product_filter', $data_file[0]) && (int)$this->profile['import_xls_filter_group_number'] > 0;
            if($this->has_filters) {
                $this->load->model('extension/module/ie_pro_filter_groups');
                $this->model_extension_module_ie_pro_filter_groups->create_filter_groups_from_product($data_file);
                $this->all_filter_groups = $this->model_extension_module_ie_pro_filter_groups->get_all_filter_groups_import_format();

                $this->load->model('extension/module/ie_pro_filters');
                $this->model_extension_module_ie_pro_filters->create_filters_from_product($data_file);
                $this->all_filters = $this->model_extension_module_ie_pro_filters->get_all_filters_import_format();
                $this->all_filters_simple = $this->model_extension_module_ie_pro_filters->get_all_filters_import_format(true);
            }
        }

        $this->has_attributes = array_key_exists(0, $data_file) && array_key_exists('product_attribute', $data_file[0]) && (int)$this->profile['import_xls_attribute_number'] > 0;
        if($this->has_attributes) {
            $this->load->model('extension/module/ie_pro_attribute_groups');
            $this->model_extension_module_ie_pro_attribute_groups->create_attribute_groups_from_product($data_file);
            $this->all_attribute_groups = $this->model_extension_module_ie_pro_attribute_groups->get_all_attribute_groups_import_format();

            $this->load->model('extension/module/ie_pro_attributes');
            $this->model_extension_module_ie_pro_attributes->create_attributes_from_product($data_file);
            $this->all_attributes = $this->model_extension_module_ie_pro_attributes->get_all_attributes_import_format();
            $this->all_attributes_simple = $this->model_extension_module_ie_pro_attributes->get_all_attributes_import_format(false);
        }

        $this->has_manufacturers = array_key_exists(0, $data_file) && array_key_exists($this->main_table, $data_file[0]) && array_key_exists('manufacturer_id', $data_file[0][$this->main_table]);
        if($this->has_manufacturers) {
            $this->load->model('extension/module/ie_pro_manufacturers');
            $this->model_extension_module_ie_pro_manufacturers->create_manufacturers_from_product($data_file);
            $this->all_manufacturers_import = $this->model_extension_module_ie_pro_manufacturers->get_all_manufacturers_import_format();
        }

        $this->has_options = array_key_exists(0, $data_file) && array_key_exists('product_option_value', $data_file[0]);
        if($this->has_options) {

            $first_row = $data_file[0];

            if(!array_key_exists($this->main_table, $first_row) || !array_key_exists($this->product_identificator, $first_row[$this->main_table]))
                $this->exception(sprintf($this->language->get('progress_import_from_product_creating_options_error_empty_main_field'), $this->product_identificator));
        }

        if($this->has_options || (array_key_exists(0, $data_file) && array_key_exists('product_options_combinations', $data_file[0]))) {
            $this->load->model('extension/module/ie_pro_options');
            $this->model_extension_module_ie_pro_options->create_options_from_product($data_file);
            $this->all_options = $this->model_extension_module_ie_pro_options->get_all_options_import_format();

            $this->load->model('extension/module/ie_pro_option_values');
            $this->model_extension_module_ie_pro_option_values->create_option_values_from_product($data_file);
            $this->all_option_values = $this->model_extension_module_ie_pro_option_values->get_all_option_values_import_format();
        }

        $this->has_downloads = array_key_exists(0, $data_file) && array_key_exists('product_to_download', $data_file[0]);
        if($this->has_downloads) {
            $this->load->model('extension/module/ie_pro_downloads');
            $this->model_extension_module_ie_pro_downloads->create_downloads_from_product($data_file);
            $this->all_downloads = $this->model_extension_module_ie_pro_downloads->get_all_downloads_import_format();
        }

        $this->load->model('extension/module/ie_pro_customer_groups');
        $this->all_customer_groups = $this->model_extension_module_ie_pro_customer_groups->get_all_customer_groups();

    }

    function get_product_id($field, $value) {
        $value = htmlspecialchars(html_entity_decode($value));

        if($field == $this->product_identificator)
            return array_key_exists($value, $this->product_by_key) ? $this->product_by_key[$value] : '';
        else {
            $sql = "SELECT " . $this->escape_database_field('product_id') . " FROM " . $this->escape_database_table_name('product') . " WHERE " . $this->escape_database_field($field) . " = " . $this->escape_database_value($value);
            $result = $this->db->query($sql);
            return !empty($result->row) && array_key_exists('product_id', $result->row) ? $result->row['product_id'] : '';
        }
    }

    function assign_product_id($force_id = false) {
        if(!$force_id || ($force_id && (!is_numeric($force_id) || is_float($force_id)))) {
            $fields = '';

            if($this->mysql_buggy) {
                foreach ($this->database_schema['product'] as $field_name => $field_data) {
                    if($field_name != 'product_id')
                        $fields .= $field_name." = '', ";
                }
                $fields = rtrim($fields, ', ');
            }
            else
                $fields = $this->escape_database_field('model') . ' = ""';

            $this->db->query('INSERT INTO ' . $this->escape_database_table_name('product') . ' SET ' . $fields);

            return $this->db->getLastId();
        } else {
            if((int)$force_id >= 2147483647)
                $this->exception(sprintf($this->language->get('progress_import_product_error_product_id_limit'), $force_id));

            $this->db->query('INSERT INTO ' . $this->escape_database_table_name('product') . ' SET ' . $this->escape_database_field('product_id') . ' = ' . $this->escape_database_value($force_id));
            return $force_id;
        }
    }


    public function get_product_categories($product_id, $limit = '')
    {
        $result = $this->db->query('SELECT category_id FROM ' . $this->escape_database_table_name('product_to_category') . ' WHERE ' . $this->escape_database_field('product_id') . ' = ' . $this->escape_database_value($product_id) . ' GROUP BY category_id' . (!empty($limit) ? ' LIMIT ' . $limit : ''));
        $final_cat = array();
        if (!empty($result->rows)) {
            foreach ($result->rows as $key => $cat_id) {
                $final_cat[] = $cat_id['category_id'];
            }
        }
        $final_cat = array_values(array_unique($final_cat));
        return $final_cat;
    }

    function get_deeper_trees($categories) {
        $tree = array();
        foreach ($categories as $category_key => $category) {
            $path = $this->getCategoryPath($category);
            foreach ($path as $key => $item) {
                if(!isset($tree[$item])) {
                    $tree[$item] = array(
                        'index' => $category_key,
                        'cnt' => 1
                    );
                } else {
                    $tree[$item]['cnt'] += 1;
                }
            }
        }

        $result = array();
        foreach ($tree as $item) {
            if($item['cnt'] > 1) {
                continue;
            }

            $index = $item['index'];
            $result[] = $categories[$index];
        }

        return $result;
    }

    function getCategoryPath(array $category, $level = 0) {
        if(empty($category) || (is_array($category) && !array_key_exists('category_id', $category)))
            return array();
        $tree[$level] = $category['category_id'];
        if(!empty($category['childrens'])) {
            $level += 1;
            foreach ($category['childrens'] as $item) {
                $tree = array_merge($tree,$this->getCategoryPath($item, $level));
            }
        }

        return $tree;
    }

    public function get_product_manufacturer($product_id) {
        $result = $this->db->query('SELECT manufacturer_id FROM ' . $this->escape_database_table_name('product') . ' WHERE ' . $this->escape_database_field('product_id') . ' = ' . $this->escape_database_value($product_id));
        return array_key_exists('manufacturer_id', $result->row) ? $result->row['manufacturer_id'] : '';
    }

    public function get_product_categories_tree($product_id, $parent_number, $children_number)
    {
        $categories = $this->get_product_categories($product_id);

        $trees = array();

        foreach ($categories as $key => $category_id) {

            if(version_compare(VERSION, '1.5.6.4', '>=')) {
                $query = "SELECT DISTINCT c.category_id, (SELECT GROUP_CONCAT(cp.path_id ORDER BY level SEPARATOR '|') FROM " . $this->escape_database_table_name('category_path') . " cp WHERE cp.category_id = c.category_id GROUP BY cp.category_id) AS path FROM " . $this->escape_database_table_name('category') . " c WHERE c.category_id = ".$this->escape_database_value($category_id);
                $result = $this->db->query($query);
                $path = $result->num_rows ? $result->row['path'] : false;
            } else {
                $path = $this->get_path_old_versions($category_id);
            }
            $temp_tree = array();
            $tree_level = 1;
            if($path) {
                $categories_splitted = explode('|', $path);
                foreach ($categories_splitted as $key_path => $cat_id) {
                    $cat_info = array_key_exists($cat_id, $this->all_categories) ? $this->all_categories[$cat_id] : false;
                    if(!empty($cat_info)) {
                        if(count($categories_splitted) < ($key_path+1))
                            $cat_info['childrens'] = array();

                        if($tree_level != 1) {
                            if($tree_level == 2)
                                $temp_tree['childrens'][] = $cat_info;
                            elseif($tree_level == 3)
                                $temp_tree['childrens'][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 4)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 5)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 6)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 7)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 8)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 9)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][][0]['childrens'][][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 10)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][][0]['childrens'][][0]['childrens'][][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 11)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][][0]['childrens'][][0]['childrens'][][0]['childrens'][][0]['childrens'][] = $cat_info;
                            elseif($tree_level == 12)
                                $temp_tree['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][0]['childrens'][][0]['childrens'][][0]['childrens'][][0]['childrens'][][0]['childrens'][][0]['childrens'][] = $cat_info;
                        } else {
                            $temp_tree = $cat_info;
                        }
                        $tree_level++;
                    }
                }
            }
            $trees[] = $temp_tree;
        }
        return $trees;

        /*
        $parents = array();
        foreach ($categories as $key => $cat_id) {
            $parents[] = $cat_id;
            $has_parent = true;
            while ($has_parent) {

                $cat_id = $this->model_extension_module_ie_pro_categories->get_parent_id($cat_id);

                if ($cat_id)
                    $parents[] = $cat_id;
                else
                    $has_parent = false;
            }
        }
        $parent_ids = array_unique($parents);

        //Get parents data
        $parent_data = array();
        foreach ($parent_ids as $key => $parent_id) {
            if(array_key_exists($parent_id, $this->all_categories))
                $parent_data[] = $this->all_categories[$parent_id];
        }

        $final_tree = !empty($parent_data) ? $this->model_extension_module_ie_pro_categories->build_categories_tree($parent_data) : array();
        return $final_tree;*/
    }

    public function get_product_main_category($product_id) {
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND main_category = '1' LIMIT 1");
        return ($query->num_rows ? (int)$query->row['category_id'] : 0);
    }

    public function get_path_old_versions($category_id)
    {
        $query = $this->db->query("SELECT parent_id, category_id FROM ".$this->escape_database_table_name('category')." c WHERE c.category_id = ".$this->escape_database_value($category_id));
        if ($query->row['parent_id']) {
            return $this->get_path_old_versions($query->row['parent_id']) . '|' . $query->row['category_id'];
        } else {
            return $query->row['category_id'];
        }
    }
    public function get_product_filters($product_id) {
        $sql = 'SELECT pf.`filter_id`, fi.`filter_group_id`
                    FROM '.$this->escape_database_table_name('product_filter').' pf
                    LEFT JOIN (SELECT * from '.$this->escape_database_table_name('filter').' fi2 ORDER BY fi2.`sort_order` ASC) fi ON(fi.filter_id = pf.filter_id)
                    LEFT JOIN (SELECT * from '.$this->escape_database_table_name('filter_group').' fg ORDER BY fg.`sort_order` ASC) fg ON(fi.filter_group_id = fg.filter_group_id)
                    WHERE product_id = '.$this->escape_database_value($product_id).'
                    ORDER BY fg.sort_order, fi.sort_order';
        $resuls = $this->db->query($sql);

        $final_filters = array();
        if(!empty($resuls->rows)) {
            foreach ($resuls->rows as $key => $fil_info) {
                $filter_group_id = $fil_info['filter_group_id'];
                $filter_id = $fil_info['filter_id'];
                if(!array_key_exists($filter_group_id, $final_filters))
                    $final_filters[$filter_group_id] = array();

                $final_filters[$filter_group_id][] = $filter_id;
            }
        }

        $final_filters_2 = array();
        foreach ($final_filters as $filter_group_id => $filter_ids) {
            $final_filters_2[] = array(
                'filter_group_id' => $filter_group_id,
                'filters' => $filter_ids
            );
        }
        return $final_filters_2;
    }

    /*
     * IMPORTANT - TO THIS FUNCTION WORKS, NEED SET $this->all_attributes variable, example in model ie_pro_export.php
     * */
    public function get_product_attributes($product_id) {
        $prod_attr = $this->db->query('SELECT pa.`attribute_id` FROM '.$this->escape_database_table_name('product_attribute').' pa WHERE pa.product_id = '.$this->escape_database_value($product_id).' GROUP BY pa.`attribute_id`');

        $final_attributes = array();
        foreach ($prod_attr->rows as $key => $attr) {
            $attribute_id = $attr['attribute_id'];
            if(array_key_exists($attribute_id, $this->all_attributes)) {
                $prod_attr_values = $this->db->query('SELECT pa.`text`, pa.`language_id` FROM '.$this->escape_database_table_name('product_attribute').' pa WHERE pa.product_id = '.$this->escape_database_value($product_id).' AND pa.attribute_id = '.$attribute_id);
                $temp = array();
                $temp = $this->all_attributes[$attribute_id];
                $temp['text'] = array();
                foreach ($prod_attr_values->rows as $key2 => $attr_text)
                    $temp['text'][$attr_text['language_id']] = $attr_text['text'];

                $final_attributes[] = $temp;
            }
        }
        return $final_attributes;
    }

    public function get_product_seo_urls($product_id) {
        if($this->is_oc_3x) {
            $final_seo_urls = array();
            $url = $this->db->query('SELECT '.$this->escape_database_field('keyword').','.$this->escape_database_field('language_id').','.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('seo_url').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('product_id='.$product_id));
            foreach ($url->rows as $key => $seo_url) {
                if(!array_key_exists($seo_url['store_id'], $final_seo_urls))
                    $final_seo_urls[$seo_url['store_id']] = array();

                $final_seo_urls[$seo_url['store_id']][$seo_url['language_id']] = $seo_url['keyword'];
            }
            return $final_seo_urls;
        } else {
            $url = $this->db->query('SELECT '.$this->escape_database_field('keyword').' FROM '.$this->escape_database_table_name('url_alias').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('product_id='.$product_id));
            return array_key_exists('keyword', $url->row) ? $url->row['keyword'] : '';
        }
    }

    public function get_product_url($product_id, $store_id = '', $language_id = '') {
        if($this->is_oc_3x) {
            $result = $this->db->query('SELECT '.$this->escape_database_field('keyword').' FROM '.$this->escape_database_table_name('seo_url').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('product_id='.$product_id).' AND '.$this->escape_database_field('language_id').' = '.$this->escape_database_value($language_id).' AND '.$this->escape_database_field('store_id').' = '.$this->escape_database_value($store_id));
        } else {
            $result = $this->db->query('SELECT '.$this->escape_database_field('keyword').' FROM '.$this->escape_database_table_name('url_alias').' WHERE '.$this->escape_database_field('query').' = '.$this->escape_database_value('product_id='.$product_id));
        }

        return HTTPS_CATALOG.(array_key_exists('keyword', $result->row) && !empty($result->row['keyword']) ? $result->row['keyword'] : 'index.php?route=product/product&product_id='.$product_id);
    }

    public function get_product_specials($product_id) {
        $specials = $this->db->query('SELECT * FROM '.$this->escape_database_table_name('product_special').' WHERE '.$this->escape_database_field('product_id').' = '.$this->escape_database_value($product_id));

        $customer_groups_id = array();
        foreach ($this->customer_groups as $key => $cg) {
            $customer_groups_id[$cg['customer_group_id']] = 1;
        }

        $final_specials = array();
        foreach ($specials->rows as $key => $spe) {
            $cg_id = $spe['customer_group_id'];
            if (array_key_exists($cg_id, $customer_groups_id)) {
                $number = $customer_groups_id[$cg_id];
                $customer_groups_id[$cg_id]++;
                $identificator = $number . '_' . $cg_id;
                $final_specials[$identificator] = $spe;
            }
        }
        return $final_specials;
    }

    public function get_product_discounts($product_id) {
        $discounts = $this->db->query('SELECT * FROM '.$this->escape_database_table_name('product_discount').' WHERE '.$this->escape_database_field('product_id').' = '.$this->escape_database_value($product_id));

        $customer_groups_id = array();
        foreach ($this->customer_groups as $key => $cg) {
            $customer_groups_id[$cg['customer_group_id']] = 1;
        }

        $final_discounts = array();
        foreach ($discounts->rows as $key => $discount) {
            $cg_id = $discount['customer_group_id'];
            if(array_key_exists($cg_id, $customer_groups_id)) {
                $number = $customer_groups_id[$cg_id];
                $customer_groups_id[$cg_id]++;
                $identificator = $number . '_' . $cg_id;
                $final_discounts[$identificator] = $discount;
            }
        }

            return $final_discounts;
        }

    public function get_product_rewards($product_id) {
        $rewards = $this->db->query('SELECT '.$this->escape_database_field('customer_group_id').', '.$this->escape_database_field('points').' FROM '.$this->escape_database_table_name('product_reward').' WHERE '.$this->escape_database_field('product_id').' = '.$this->escape_database_value($product_id));

        $final_rewards = array();
        foreach ($rewards->rows as $key => $reward) {
            $cg_id = $reward['customer_group_id'];
            $final_rewards[$cg_id] = $reward['points'];
        }

        return $final_rewards;
    }

    public function get_product_related($product_id) {
        $sql = '
                SELECT pr.product_id, group_concat(pr.related_id SEPARATOR \'|\') as ids, group_concat(pror.'.$this->related_identificator.' SEPARATOR \'|\')  as models
                FROM '.$this->escape_database_table_name('product_related').' pr
                LEFT JOIN '.$this->escape_database_table_name('product').' pror ON (pror.product_id = pr.related_id)
                WHERE pr.product_id = '.$this->escape_database_value($product_id).'
            ';
        $related = $this->db->query($sql);

        return !empty($related->row) ? $related->row : array();
    }

    public function get_product_stores($product_id) {
        $stores = $this->db->query('SELECT '.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('product_to_store').' WHERE product_id = '.$this->escape_database_value($product_id));
        $final_stores = array();
        foreach ($stores->rows as $key => $val) {
            $final_stores[] = $val['store_id'];
        }
        return $final_stores;
    }

    public function get_product_layouts($product_id) {
        $layouts = $this->db->query('SELECT '.$this->escape_database_field('layout_id').', '.$this->escape_database_field('store_id').' FROM '.$this->escape_database_table_name('product_to_layout').' WHERE product_id = '.$this->escape_database_value($product_id));
        $final_layouts = array();
        foreach ($layouts->rows as $key => $val) {
            $store_id = $val['store_id'];
            $final_layouts[$store_id] = $val['layout_id'];
        }
        return $final_layouts;
    }

    public function get_product_images($product_id) {
        $related = $this->db->query('
                SELECT '.$this->escape_database_field('image').' FROM '.$this->escape_database_table_name('product_image').'
                WHERE product_id = '.$this->escape_database_value($product_id).' ORDER BY sort_order
            ');

        return !empty($related->rows) ? $related->rows : array();
    }

    public function get_product_downloads($product_id) {
        $downloads = $this->db->query('SELECT '.$this->escape_database_field('download_id').' FROM '.$this->escape_database_table_name('product_to_download').' WHERE product_id = '.$this->escape_database_value($product_id));
        $final_downloads = array();
        foreach ($downloads->rows as $key => $val) {
            $final_downloads[] = $val['download_id'];
        }
        return $final_downloads;
    }

    public function get_product_option_values($product_id) {
        $sql = 'SELECT pov.*, ov.sort_order, ov.image FROM '.$this->escape_database_table_name('product_option_value').' pov LEFT JOIN '.$this->escape_database_table_name('option_value').' ov ON(pov.option_value_id = ov.option_value_id) WHERE product_id = '.$this->escape_database_value($product_id);
        $option_values = $this->db->query( $sql);
        $final_option_values = array();
        foreach ($option_values->rows as $key => $val) {
            $final_option_values[] = $val;
        }

        $product_option_table = $this->escape_database_table_name( 'product_option');
        $option_table = $this->escape_database_table_name( 'option');
        $option_description_table = $this->escape_database_table_name( 'option_description');

        $sql = "SELECT prodopt.`product_option_id`,
                           prodopt.`option_id`,
                           prodopt.`{$this->product_option_value}`,
                           prodopt.`required`,
                           opt.`type`,
                           optd.`name`,
                           optd.`language_id`
                    FROM {$product_option_table} prodopt
                    LEFT JOIN {$option_table} opt
                              ON opt.`option_id` = prodopt.`option_id`
                    INNER JOIN {$option_description_table} optd
                              ON opt.`option_id` = optd.`option_id`
                    WHERE `product_id` = {$product_id}";

        $option = $this->db->query( $sql);
        $final_options = array();

        foreach ($option->rows as $key => $val) {
            if (isset( $final_options[$val['option_id']])) {
                $final_options[$val['option_id']]['name'][$val['language_id']] = $val['name'];
            } else {
                $val['name'] = [$val['language_id'] => $val['name']];
                unset( $val['language_id']);

                $final_options[$val['option_id']] = $val;
            }
        }

        foreach ($final_option_values as $key => $val) {
            $option_id = $val['option_id'];
            if(array_key_exists($option_id, $final_options)) {
                $final_option_values[$key]['option'] = $final_options[$option_id];
            }
        }

        $present_option_ids = array_unique( array_map( function( $option_data) {
            return +$option_data['option_id'];
        }, $final_option_values));

        foreach ($final_options as $option_id => $option_data) {
            if (!in_array( $option_id, $present_option_ids)) {
                $final_option_values[] = [
                    'product_option_value_id' => null,
                    'product_option_id' => $option_data['product_option_id'],
                    'product_id' => $product_id,
                    'option_id' => $option_id,
                    'option_value_id' => null,
                    'quantity' => null,
                    'subtract' => null,
                    'price' => null,
                    'price_prefix' => null,
                    'points' => null,
                    'points_prefix' => null,
                    'weight' => null,
                    'weight_prefix' => null,
                    'sort_order' => null,
                    'option' => $option_data
                ];
            }
        }

        return $final_option_values;
    }

    public function get($product_id) {
        if($this->is_oc_3x) {

        } else {

        }
        //'index.php?route=product/product&product_id='.$product_id;
    }

    public function get_product_field($product_id, $field) {
        $result = $this->db->query('SELECT '.$this->escape_database_field($field).' FROM '.$this->escape_database_table_name('product').'
                WHERE product_id = '.$this->escape_database_value($product_id));

        return array_key_exists($field, $result->row) ? $result->row[$field] : false;
    }

    public function check_file_data_has_options($data_file, $force_names = false) {
        //Check if exist some option columns
        $some_option_data = false;
        $options_columns = $this->check_file_option_column_keys($data_file, $force_names);
        if(!empty($options_columns)) {
            foreach ($data_file['data'] as $key => $data) {
                $result = $this->check_is_option_row($data, $options_columns);
                if(!empty($result)) {
                    $some_option_data = true;
                    break;
                }
            }
        }

        return $some_option_data;
    }

    public function check_file_option_column_keys($data_file, $force_names = false) {
        $options_columns = array();
        foreach ($data_file['columns'] as $key => $col_name) {
            $col_info = !empty($this->custom_columns[$col_name]) ? $this->custom_columns[$col_name] : false;
            if($col_info) {
                $table = !empty($col_info['table'])  ? $col_info['table'] : '';
                if($table == 'product_option_value') {
                    if($force_names) $key = $col_name;
                    $options_columns[$key] = $key;
                }
            }
        }
        return $options_columns;
    }

    public function check_is_option_row($row, $options_columns) {
        $some_option_data = array_filter(array_intersect_key($row, $options_columns));

        foreach ($row as $key => $val) {
            if(in_array($key, $options_columns))
                unset($row[$key]);
        }

        $some_normal_data = array_filter($row);

        return !empty($some_option_data) && empty($some_normal_data);
    }

    public function _importing_process_format_product_description($descriptions, $product_id, $row_file_number, $creating_element) {
        $final_descriptions = array();
        if(!empty($descriptions) && is_array($descriptions)) {
            foreach ($descriptions as $language_id => $fields) {
                $some_data = is_array($fields) ? array_filter($fields) : false;

                if(!empty($some_data)) {
                    $fields['language_id'] = $language_id;
                    $fields['product_id'] = $product_id;
                    $final_descriptions[] = $fields;
                }
            }

            if(!$this->multilanguage && $this->strict_update) {
                $main_name = $this->default_language_id;
                foreach ($this->languages as $key => $lang) {
                    $fields['language_id'] = $lang['language_id'];
                    $final_descriptions[] = $fields;
                }
            }

            //For new elements, force to add product id field for avoit problems in table "product_description"
            if($creating_element) {
                foreach ($final_descriptions as $key => $desc) {
                    $final_descriptions[$key]['product_id'] = $product_id;
                }
            }
        }

        foreach ($final_descriptions as $lang_id => $desc) {
            if(empty($desc['product_id']))
                return array();
        }

        return $final_descriptions;
    }

        public function _importing_process_format_product_image($images, $product_id, $row_file_number, $creating_element) {
            $fnal_images = array();
            if(!empty($images) && is_array($images)) {
                foreach ($images as $sort_order => $fields) {
                    $some_data = array_filter($fields);
                    if(!empty($some_data)) {
                        $fields['product_id'] = $product_id;
                        $fields['sort_order'] = $sort_order;
                        $fnal_images[] = $fields;
                    }

            }
        }
        return $fnal_images;
    }

    public function _importing_process_format_product_to_category($categories, $product_id, $row_file_number, $creating_element) {
        $array_cat_ids = array();
        $main_category = array_key_exists('main_category', $categories) && !empty($categories['main_category']) ? $categories['main_category'] : '';
        if($this->cat_tree) {
            $child_number = (int)$this->profile['import_xls_cat_tree_children_number']+1;
            foreach ($categories as $position => $cat_names) {

                if(!is_numeric($position)) continue;

                $last_cat_id = false;
                for ($i = 0; $i < $child_number; $i++) {
                    $parent_id = false;
                    if($i == 0) {
                        $cat_names_temp = array_key_exists('name', $cat_names) ? $cat_names['name'] : '';
                        $previous_parent_id = 0;
                    } else
                        $cat_names_temp = array_key_exists($i, $cat_names) ? $cat_names[$i]['name'] : '';

                    $some_cat_with_name = false;
                    if(!empty($cat_names_temp)) {
                        foreach ($cat_names_temp as $lang_id => $cat_name) {
                            if(!empty($cat_name)) {
                                $some_cat_with_name = true;
                                $allow_ids = $this->extract_id_allow_ids($cat_name);
                                if($allow_ids) {
                                    $parent_id = $allow_ids.'-forceId';
                                    $previous_parent_id = $parent_id;
                                    if (!$this->last_cat_assign)
                                        $array_cat_ids[] = $parent_id;
                                    $last_cat_id = $parent_id;
                                    if(!empty($main_category) && $main_category == $cat_name)
                                        $main_category_id = $last_cat_id;
                                    break;
                                } else {
                                    $name_formatted = $cat_name . '_' . $previous_parent_id . '_' . $lang_id;

                                    if (array_key_exists($name_formatted, $this->all_categories)) {
                                        $parent_id = $this->all_categories[$name_formatted];
                                        $previous_parent_id = $parent_id;
                                        if (!$this->last_cat_assign)
                                            $array_cat_ids[] = $parent_id;
                                        $last_cat_id = $parent_id;
                                        if(!empty($main_category) && $main_category == $cat_name)
                                            $main_category_id = $last_cat_id;
                                        break;
                                    } else {
                                        $array_cat_ids = [];
                                        $cat_name_prefix = "{$cat_name}_";

                                        foreach ($this->all_categories as $name => $cat_id) {
                                            if (strpos( $name, $cat_name_prefix) === 0) {
                                                $array_cat_ids[] = $cat_id;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if($this->last_cat_assign && $last_cat_id)
                    $array_cat_ids[] = $last_cat_id;
            }
        } else {
            $cat_number = (int)$this->profile['import_xls_cat_number'];
            if(!empty($categories)) {
                for ($i = 1; $i <= $cat_number; $i++) {
                    $cat_names = array_key_exists($i, $categories) && array_key_exists('category_id', $categories[$i]) ? $categories[$i]['category_id'] : array();
                    $cat_found = $some_cat_with_name = false;
                    foreach ($cat_names as $lang_id => $name) {
                        if (!empty($name)) {
                            $allow_ids = $this->extract_id_allow_ids($name);
                            if($allow_ids) {
                                $array_cat_ids[] = $allow_ids.'-forceId';
                                if(!empty($main_category) && $main_category == $name)
                                    $main_category_id = $allow_ids.'-forceId';
                                break;
                            }
                            $some_cat_with_name = true;
                            if (array_key_exists($name . '_' . $lang_id, $this->all_categories)) {
                                $array_cat_ids[] = $this->all_categories[$name . '_' . $lang_id];

                                if(!empty($main_category) && $main_category == $name)
                                    $main_category_id = $this->all_categories[$name . '_' . $lang_id];
                                break;
                            }
                        }
                    }
                }
            }
        }

        $array_cat_ids = array_unique($array_cat_ids);

        $final_categories = array();
        foreach ($array_cat_ids as $key => $cat_id) {
            $temp = array(
                'product_id' => $product_id,
                'category_id' => $cat_id,
            );

            $final_categories[] = $temp;
        }

        if(isset($main_category) && !empty($main_category) && !empty($main_category_id)) {
            $inserted_main_category = false;
            foreach ($final_categories as $key => $cat_info) {
                if($cat_info['category_id'] == $main_category_id) {
                    $final_categories[$key]['main_category'] = 1;
                    $inserted_main_category = true;
                    break;
                }
            }
            if(!$inserted_main_category) {
                $temp = array(
                    'product_id' => $product_id,
                    'category_id' => $main_category_id,
                    'main_category' => 1
                );
                array_push($final_categories, $temp);
            }
        }
        return $final_categories;
    }

    public function _importing_process_format_product_attribute($attributes, $product_id, $row_file_number, $creating_element) {
        $final_attributes = array();

        if(!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $key => $element) {
                if(!array_key_exists('attribute_group', $element))
                    break;

                $attribute_group_id_master = false;
                $attribute_id_master = false;

                $attr_group_names = $element['attribute_group'];
                foreach ($attr_group_names as $lang_id => $attr_group_name) {
                    if (!empty($attr_group_name)) {
                        $attribute_group_id = $this->extract_id_allow_ids($attr_group_name);

                        if(!empty($attribute_group_id))
                            break;

                        if (array_key_exists($attr_group_name . '_' . $lang_id, $this->all_attribute_groups)) {
                            $attribute_group_id = $this->all_attribute_groups[$attr_group_name . '_' . $lang_id];
                            break;
                        }

                        if($lang_id == $this->default_language_id && !empty($attribute_group_id))
                            $attribute_group_id_master = $attribute_group_id;
                    }
                }

                if(!empty($attribute_group_id_master))
                    $attribute_group_id = $attribute_group_id_master;

                if(empty($attribute_group_id))
                    continue;

                $attr_names = $element['attribute'];
                foreach ($attr_names as $lang_id => $attr_name) {
                    if (!empty($attr_name)) {
                        $attribute_id = $this->extract_id_allow_ids($attr_name);

                        if(!empty($attribute_id))
                            break;

                        $index_attr = $attribute_group_id . '_' . $attr_name. '_' . $lang_id;
                        if(!empty($this->all_attributes[$index_attr]))
                            $attribute_id = $this->all_attributes[$index_attr];

                        if($lang_id == $this->default_language_id && !empty($attribute_id))
                            $attribute_id_master = $attribute_id;
                    }
                }

                if(!empty($attribute_id_master))
                    $attribute_id = $attribute_id_master;

                if(empty($attribute_id))
                    continue;

                $attribute_values = !empty($element['attribute_value']) && is_array($element['attribute_value']) ? $element['attribute_value'] : array();

                foreach ($attribute_values as $lang_id => $attri_val_name) {
                    if(!empty($attr_group_name) && !empty($attri_val_name)) {
                        $temp = array(
                            'product_id' => $product_id,
                            'attribute_id' => $attribute_id,
                            'language_id' => $lang_id,
                            'text' => $attri_val_name
                        );
                        $final_attributes[] = $temp;

                        if(!$this->multilanguage && $this->count_languages_real > 1) {
                            foreach ($this->languages as $lang_id => $lang) {
                                $language_id = $lang['language_id'];
                                if($language_id != $lang_id) {
                                    $temp = array(
                                        'product_id' => $product_id,
                                        'attribute_id' => $attribute_id,
                                        'language_id' => $language_id,
                                        'text' => $attri_val_name
                                    );
                                    $final_attributes[] = $temp;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $final_attributes;
    }

    public function _importing_process_format_product_filter($filters, $product_id, $row_file_number, $creating_element) {
        if(!is_array($this->all_filter_groups))
            return array();
        $final_filters = array();
        $filter_number = (int)$this->profile['import_xls_filter_number'];
        if(!empty($filters) && is_array($filters)) {
            foreach ($filters as $key => $element) {
                $names = $element['name'];
                $filter_group_id = false;
                foreach ($names as $lang_id => $name) {
                    if (!empty($name)) {

                        $allow_ids = $this->extract_id_allow_ids($name);
                        if($allow_ids) {
                            $filter_group_id = $allow_ids;
                        }
                        elseif (array_key_exists($name . '_' . $lang_id, $this->all_filter_groups)) {
                            $filter_group_id = $this->all_filter_groups[$name . '_' . $lang_id];
                            break;
                        }
                    }
                }

                for ($i = 1; $i <= $filter_number; $i++) {
                    $names = array_key_exists($i, $element) && array_key_exists('name', $element[$i]) ? $element[$i]['name'] : '';
                    if (!empty($names)) {
                        $found = $some_with_name = false;
                        foreach ($names as $lang_id => $name) {
                            if (!empty($name)) {
                                $allow_ids = $this->extract_id_allow_ids($name);
                                if($allow_ids) {
                                    $filter_id = $allow_ids;
                                    $temp = array(
                                        'product_id' => $product_id,
                                        'filter_id' => $filter_id,
                                    );
                                    $final_filters[] = $temp;
                                    break;
                                }

                                $some_with_name = true;
                                $index = $name . '_' . $lang_id;

                                if (
                                    (!$filter_group_id && array_key_exists($index, $this->all_filters_simple)) ||
                                    (!empty($filter_group_id) && array_key_exists($filter_group_id . '_' . $index, $this->all_filters))
                                ) {
                                    $filter_id = $this->all_filters[$filter_group_id . '_' . $index];
                                    $temp = array(
                                        'product_id' => $product_id,
                                        'filter_id' => $filter_id,
                                    );
                                    $final_filters[] = $temp;
                                    break;
                                }
                            }
                        }
                    }
                }

            }
        }
        return $final_filters;
    }

    public function _importing_process_format_product_to_download($downloads, $product_id, $row_file_number, $creating_element) {
        $final_downloads = array();
        if(!empty($downloads) && is_array($downloads)) {
            foreach ($downloads as $key => $element) {
                $names = $element['name'];
                $found = $some_with_name = false;
                foreach ($names as $lang_id => $name) {
                    if(!empty($name)) {
                        $some_with_name = true;
                        if(array_key_exists($name.'_'.$lang_id, $this->all_downloads)) {
                            $download_id = $this->all_downloads[$name.'_'.$lang_id];
                            $temp = array(
                                'product_id' => $product_id,
                                'download_id' => $download_id,
                            );
                            $final_downloads[] = $temp;
                            break;
                        }
                    }
                }
            }
        }
        return $final_downloads;
    }

    public function _importing_process_format_seo_url($seo_urls, $product_id, $row_file_number, $creating_element) {
        $query = 'product_id='.$product_id;
        $final_seo_url = array();
        if($this->is_oc_3x) {
            foreach ($seo_urls as $store_id => $names) {
                foreach ($names['keyword'] as $lang_id => $name) {
                    if(!empty($name)) {
                        $final_seo_url[] = array(
                            'query' => $query,
                            'store_id' => $store_id,
                            'language_id' => $lang_id,
                            'keyword' => $this->format_seo_url($name)
                        );
                    }
                }

            }
        } else {
            $final_seo_url = array(
                'query' => $query,
                'keyword' => $this->format_seo_url($seo_urls['keyword'])
            );
        }
        return $final_seo_url;
    }

    public function _importing_process_format_product_reward($reward, $product_id, $row_file_number, $creating_element) {
        $final_rewards = array();

        foreach ($reward as $customer_group_id => $points) {
            $points = (int)$points['points'];
            if($points > 0) {
                $final_rewards[] = array(
                    'product_id' => $product_id,
                    'points' => $points,
                    'customer_group_id' => $customer_group_id,
                );
            }
        }
        return $final_rewards;
    }

    public function _importing_process_format_product_related($related, $product_id, $row_file_number, $creating_element) {
        $final_related = array();
        $related = explode('|', $related['related']);

        foreach ($related as $key => $model) {
            if(!empty($model)) {
                $model = $value = htmlspecialchars(trim($model));
                $related_id = $this->get_product_id($this->related_identificator, $model);

                if (empty($related_id) && !array_key_exists($model, $this->products_models))
                    $this->exception(sprintf($this->language->get('progress_import_product_error_product_related_not_found'), $row_file_number, $model, $row_file_number));

                $related_id = array_key_exists($model, $this->products_models) ? $this->products_models[$model] : $related_id;

                $final_related[] = array(
                    'product_id' => $product_id,
                    'related_id' => $related_id
                );
            }
        }
        return $final_related;
    }

    public function _importing_process_format_product_to_store($stores, $product_id, $row_file_number, $creating_element) {
        $final_stores = array();
        $stores = explode('|', $stores['store_id']);

        foreach ($stores as $key => $store_id) {
            $final_stores[] = array(
                'product_id' => $product_id,
                'store_id' => $store_id,
            );

        }
        return $final_stores;
    }

    public function _importing_process_format_product_to_layout($layouts, $product_id, $row_file_number, $creating_element) {
        $final_layouts = array();
        if(!empty($layouts) && is_array($layouts)) {
            foreach ($layouts as $store_id => $layout_id) {
                $final_layouts[] = array(
                    'layout_id' => is_array($layout_id) ? $layout_id['layout_id'] : '',
                    'store_id' => $store_id,
                    'product_id' => $product_id,
                );

            }
        }
        return $final_layouts;
    }

    public function _importing_process_format_product_special($specials, $product_id, $row_file_number, $creating_element) {
        $final_specials = array();
        $main_price = $specials['main_price'];
        unset($specials['main_price']);

        if(!empty($specials) && is_array($specials)) {
            foreach ($specials as $number => $special_datas) {
                foreach ($special_datas as $customer_group_id => $special_data) {
                    $some_special_data = array_filter($special_data);
                    $price_exist = array_key_exists('price', $special_data) && !empty($special_data['price']) && is_numeric($special_data['price']);
                    if($some_special_data && $price_exist && (empty($main_price) || $main_price > $special_data['price'])) {
                        $special_data['product_id'] = $product_id;
                        $special_data['customer_group_id'] = $customer_group_id;
                        $final_specials[] = $special_data;
                    }
                }
            }
        }
        return $final_specials;
    }

    public function _importing_process_format_product_discount($discounts, $product_id, $row_file_number, $creating_element) {
        $final_discounts = array();
        if(!empty($discounts) && is_array($discounts)) {
            foreach ($discounts as $number => $discount_datas) {
                foreach ($discount_datas as $customer_group_id => $discount_data) {
                    $some_discount_data = array_filter($discount_data);
                    $price_exist = array_key_exists('price', $discount_data) && !empty($discount_data['price']);
                    if($some_discount_data && $price_exist) {
                        $discount_data['product_id'] = $product_id;
                        $discount_data['customer_group_id'] = $customer_group_id;
                        $final_discounts[] = $discount_data;
                    }
                }
            }
        }
        return $final_discounts;
    }

    public function _importing_process_format_product_option_combinations($option_cmb_values, $row_number, $product_id)
    {
        $result = [];
        foreach ($option_cmb_values as $table_name => $value) {
            if ($table_name == 'options') {
                $options_arr = [];
                foreach ($value as $option_and_value) {

                    $option_name = array_values($option_and_value['option'])[0];
                    $option_name_language_id = array_keys($option_and_value['option'])[0];
                    $option_value_name = array_values($option_and_value['option_value'])[0];
                    $option_value_name_language_id = array_keys($option_and_value['option_value'])[0];
                    $option_type = $option_and_value['option_type'];
                    $option_image = !empty($option_and_value['option_image']) ? $option_and_value['option_image'] : '';


                    //if there isn't one of the option_values(in case it is a selectable option) or $option_id then the combination being proccesed its skipped because this
                    //could break the combination module.
                    if (
                        empty($option_name) ||
                        (empty($option_value_name) && !in_array($option_and_value['option_type'], ['file', 'date', 'time', 'datetime', 'text', 'textarea']))) {
                        continue;
                    }

                    // If there isn't an option with the read id then the option_combination row is skipped
                    $option_id = $this->extract_id_allow_ids($option_name);
                    $skip_exists = strpos($option_name, '-forceId') !== false;
                    $exist_option = $skip_exists || (!$skip_exists && $this->exist_option_id($option_id));

                    if ($option_id && !$exist_option){
                        return [];
                    }

                    // If there isn't an option_value with the read id that belongs the option_id then the option_combination row is skipped
                    $option_value_id = $this->extract_id_allow_ids($option_value_name);
                    $skip_exists = strpos($option_value_name, '-forceId') !== false;

                    if ($option_value_id && $option_id){
                        if ($this->its_an_option_value_list($option_value_id)){
                            $option_value_ids_arr = json_decode($option_value_id);

                            if(!$skip_exists)
                                foreach ($option_value_ids_arr as $id){
                                    if (!$this->exist_option_value_in_option($option_id, $id)) return [];
                                }
                        }
                        elseif(!$skip_exists && !$this->exist_option_value_in_option($option_id, $option_value_id)){
                            return [];
                        }
                    }

                    if ($option_value_id && !$option_id){
                        $option_id = (string) $this->find_option_id_in_all_options($option_name, $option_type);
                        if (!$option_id) return [];
                        if ($this->its_an_option_value_list($option_value_id)){
                            $option_value_ids_arr = json_decode($option_value_id);
                            foreach ($option_value_ids_arr as $id){
                                if (!$this->exist_option_value_in_option($option_id, $id)) return [];
                            }
                        }
                        elseif(!$this->exist_option_value_in_option($option_id, $option_value_id)){
                            return [];
                        }
                    }

                    $option_id = (!$option_id) ? (string) $this->find_option_id_in_all_options($option_name, $option_type) : $option_id;
                    if (!$option_id) {
                        $option_id = (string) $this->model_extension_module_ie_pro_options->create_simple_option([
                            'name' => [$option_name_language_id => $option_name],
                            'type' => $option_type,
                            'image' => $option_image
                        ]);
//                            $new_key = "{$option_name}_{$option_type}_0";
//                            $all_options = $this->all_options;
//                            $all_options[$new_key] = "$option_id";
//                            $this->all_options = $all_options;
                    }

                    if ($option_value_id && $this->its_an_option_value_list($option_value_id)){
                        $option_value_id = json_decode($option_value_id);
                    }
                    elseif (!$option_value_id && !$this->its_an_option_value_list($option_value_name)){
                        if ($this->its_a_selectable_option($option_id)) {
                            $option_value_id = (string)$this->find_option_value_id_in_all_option_values($option_id, $option_value_name);
                            if (!$option_value_id) {
                                $option_value_id = $this->model_extension_module_ie_pro_option_values->create_simple_option_value([
                                    'name' => [$option_value_name_language_id => $option_value_name],
                                    'option_id' => $option_id,
                                    'sort_order' => 0,
                                    'image' => $option_image
                                ]);
                            }
                        } else
                            $option_value_id = $option_value_name;
                    }
                    elseif (!$option_value_id && $this->its_an_option_value_list($option_value_name)){
                        $option_value_name_arr = json_decode($option_value_name);
                        $option_value_id = [];
                        foreach ($option_value_name_arr as $name){
                            $id = (string) $this->find_option_value_id_in_all_option_values($option_id, $name);
                            if (!$id) {
                                $id = $this->model_extension_module_ie_pro_option_values->create_simple_option_value([
                                    'name' => [$option_value_name_language_id => $name],
                                    'option_id' => $option_id,
                                    'sort_order' => 0,
                                    'image' => $option_image
                                ]);
//                                    $new_key = "{$option_id}_{$name}_0";
//                                    $this->all_option_values[$new_key] = $id;
                            }
                            $option_value_id[] = $id;
                        }
                    }

                    $options_arr[$option_id] = $option_value_id;
                }
                $result['options'] = json_encode($options_arr);
            } elseif ($table_name == 'images') {
                $images = array_filter($value);
                $count_comb_image = 1;
                $final_images = array();
                foreach ($images as $key => $image){
                    if ($this->is_url($image)) {
                        $force_name = 'comb-'.$row_number.'-'.$count_comb_image;
                        $count_comb_image++;
                        //get_remote_image_data

                        $img_info = $this->get_remote_image_data('options-combinations', 'images', $product_id, $row_number, $image, $force_name);

                        $download = !$this->skip_image_download || ($this->skip_image_download && !is_numeric(array_search($img_info['name'], $this->all_images)));
                        if($download)
                            $this->download_remote_image($img_info);

                        $final_images[$key] = $img_info['opencart_path'];
                    } else {
                        $final_images[$key] = $image;
                    }
                }
                $result['images'] = count($final_images) > 0 ? json_encode(array_values($final_images)) : '';
            } elseif ($table_name == 'prices') {
                $option_prices = [];
                if (array_key_exists('option_price', $value)) {
                    foreach ($value['option_price'] as $group_id => $option_price) {
                        $option_price['customer_group_id'] = $group_id;
                        $option_price['price'] = empty($option_price['price']) ? 0 : $option_price['price'];
                        $option_price['price_prefix'] = empty($option_price['price_prefix']) ? '=' : $option_price['price_prefix'];
                        $option_prices[] = $option_price;
                    }
                }

                $option_points = [];
                if (array_key_exists('option_points', $value)) {
                    foreach ($value['option_points'] as $group_id => $option_point) {
                        $option_point['customer_group_id'] = $group_id;
                        $option_point['points'] = empty($option_point['points']) ? 0 : $option_point['points'];
                        $option_point['points_prefix'] = empty($option_point['points_prefix']) ? '=' : $option_point['points_prefix'];
                        $option_points[] = $option_point;
                    }
                }

                $option_discounts = [];
                if (array_key_exists('option_discount', $value)) {
                    foreach ($value['option_discount'] as $option_discount) {
                        //ckeck if its a option combination empty row
                        if (empty(array_values($option_discount['customer_group_id'])[0])
                            && empty($option_discount['quantity']) && empty($option_discount['priority']) && empty($option_discount['price']) &&
                            empty($option_discount['date_start']) && empty($option_discount['date_end'])) {
                            continue;
                        }

                        $group_name = array_values($option_discount['customer_group_id'])[0];
                        $group_name_language_id = array_keys($option_discount['customer_group_id'])[0];
                        $group_id = '';

                        if (!empty($group_name)) {
                            $allow_ids = $this->extract_id_allow_ids($group_name);
                            if (!$allow_ids) {
                                //get group id by its name stored in group_name
                                $group_id = $this->find_group_id_by_name_in_all_customer_groups($group_name);
                                if (!$group_id) {
                                    //Create the new group with the given language_id and name.
                                    $group_id = $this->model_extension_module_ie_pro_customer_groups->create_customer_group($group_name_language_id, $group_name);
                                }
                            } else {
                                $group_id = $allow_ids;
                                if (!$this->exist_group_id_in_all_customer_groups($group_id)) return [];
                            }
                        }

                        $option_discount['customer_group_id'] = $group_id;
                        $option_discounts[] = $option_discount;
                    }
                }

                $option_specials = [];
                if (array_key_exists('option_special', $value)) {
                    foreach ($value['option_special'] as $option_special) {
                        //ckeck if its a option combination empty row
                        $is_empty_row = (!array_key_exists("customer_group_id", $option_special) || empty(array_values($option_special['customer_group_id'])[0]))
                            && empty($option_special['priority']) && empty($option_special['price']) &&
                            empty($option_special['date_start']) && empty($option_special['date_end']);

                        $empty_price = empty($option_special['price']);

                        if ($is_empty_row || $empty_price) {
                            continue;
                        }

                        $group_name = array_key_exists("customer_group_id", $option_special) ? array_values($option_special['customer_group_id'])[0] : '';
                        $group_name_language_id = array_key_exists("customer_group_id", $option_special) ? array_keys($option_special['customer_group_id'])[0] : '';
                        $group_id = '';

                        if (!empty($group_name)) {
                            $allow_ids = $this->extract_id_allow_ids($group_name);
                            if (!$allow_ids) {
                                //get group id by its name stored in group_name
                                $group_id = $this->find_group_id_by_name_in_all_customer_groups($group_name);
                                if (!$group_id) {
                                    //Create the new group with the given language_id and name.
                                    $group_id = $this->model_extension_module_ie_pro_customer_groups->create_customer_group($group_name_language_id, $group_name);
                                }
                            } else {
                                $group_id = $allow_ids;
                                if (!$this->exist_group_id_in_all_customer_groups($group_id)) return [];
                            }
                        }
                        $option_special['customer_group_id'] = $group_id;
                        $option_specials[] = $option_special;
                    }
                }

                $result['prices'] = json_encode([
                    'option_price' => $option_prices,
                    'option_points' => $option_points,
                    'option_discount' => $option_discounts,
                    'option_special' => $option_specials
                ]);
            } else {
                $result[$table_name] = $value;
            }

            $result['product_id'] = $product_id;
        }
        return $result;
    }

    private function its_a_selectable_option($option_id){
        $sql = "SELECT * FROM `" . DB_PREFIX . "option` WHERE option_id = '" . (int) $option_id . "'";
        $option = $this->db->query($sql)->row;
        return !in_array($option['type'], ['file', 'date', 'time','datetime', 'text', 'textarea']);
    }

    public function exist_option_id($option_id){
        if($this->all_options != '')
            foreach ($this->all_options as $id){
                if ($option_id == $id){
                    return true;
                }
            }
        return false;
    }

    public function exist_option_value_id($option_value_id){
        foreach ($this->all_option_values as $id){
            if ($id == $option_value_id){
                return true;
            }
        }
        return false;
    }

    public function exist_option_value_in_option($option_id, $option_value_id){
        foreach ($this->all_option_values as $key => $id){
            $key_arr = explode('_', $key);
            if ($key_arr[0] == $option_id && $id == $option_value_id){
                return true;
            }
        }
        return false;
    }

    public function find_group_id_by_name_in_all_customer_groups($group_name){
        $all_customer_groups = $this->model_extension_module_ie_pro_customer_groups->get_all_customer_groups();
        foreach ($all_customer_groups as $customer_group){
            if ($customer_group['name'] == $group_name){
                return $customer_group['customer_group_id'];
            }
        }
        return false;
    }

    public function exist_group_id_in_all_customer_groups($group_id){
        foreach ($this->all_customer_groups as $customer_group){
            if ($customer_group['customer_group_id'] == $group_id){
                return true;
            }
        }
        return false;
    }

    public function find_option_value_id_in_all_option_values($option_id, $option_value_name){
        $all_option_values = $this->model_extension_module_ie_pro_option_values->get_all_option_values_import_format();
        foreach ($all_option_values as $key => $option_value_id){
            $key_arr = explode('_', $key);
            if ($key_arr[0] == $option_id && $key_arr[1] == $option_value_name){
                return $option_value_id;
            }
        }
        return false;
    }

    public function find_option_id_in_all_options($option_name, $option_type){
        $all_options = $this->model_extension_module_ie_pro_options->get_all_options_import_format();
        foreach ($all_options as $key => $option_id){
            $key_arr = explode('_' ,$key);
            if ($key_arr[0] == $option_name && $key_arr[1] == $option_type){
                return $option_id;
            }
        }
        return false;
    }

    public function _importing_process_format_product_option_value($option_values, $row_number, $product_id, $full_row) {
        $option_id = $option_value_id = $simple_option_value = '';
        unset($full_row['product_option_value']);

        $only_option_data = $this->emptyArray($full_row);

        if(!empty($option_values)) {
            $option_type = array_key_exists('option_type', $option_values) ? $option_values['option_type'] : '';
            $option_no_values = !empty($option_type) && !in_array($option_type, $this->option_types_with_values);
            $image = array_key_exists('image', $option_values) ? $option_values['image'] : '';
            $sort_order = array_key_exists('sort_order', $option_values) ? $option_values['sort_order'] : '';

            $option_id = false;

            foreach ($this->languages as $key => $lang) {
                $language_id = $lang['language_id'];
                $option_name = array_key_exists($language_id, $option_values) && array_key_exists('option_name', $option_values[$language_id]) ? $option_values[$language_id]['option_name'] : '';

                if (!empty($option_name)) {
                    if (empty($option_type))
                        $this->exception(sprintf($this->language->get('progress_import_from_product_creating_option_values_error_option_type'), ($row_number+2), $option_name));

                    $allow_ids = $this->extract_id_allow_ids($option_name);

                    if($allow_ids) {
                        $option_id = $allow_ids;
                        break;
                    } else {
                        $index = $option_name . '_' . $option_type . '_' . $language_id;

                        if (array_key_exists($index, $this->all_options)) {
                            $option_id = $this->all_options[$index];
                            break;
                        }
                    }
                }
            }


            foreach ($this->languages as $key => $lang) {
                $language_id = $lang['language_id'];
                $option_value_name = array_key_exists($language_id, $option_values) && array_key_exists('name', $option_values[$language_id]) ? $option_values[$language_id]['name'] : '';

                if (!empty($option_value_name)) {
                    $allow_ids = $this->extract_id_allow_ids($option_value_name);

                    if($allow_ids) {
                        $option_value_id = $allow_ids;
                        break;
                    } else {
                        $index = $option_id . '_' . $option_value_name . '_' . $language_id;
                        if (array_key_exists($index, $this->all_option_values)) {
                            $option_value_id = $this->all_option_values[$index];
                            break;
                        }
                    }
                } elseif (!empty($option_value_name) && $option_no_values) {
                    $simple_option_value = $option_value_name;
                    break;
                }
            }
        }

        foreach ($this->languages as $key => $lang) {
            $lang_id = $lang['language_id'];
            if(array_key_exists($lang_id, $option_values))
                unset($option_values[$lang_id]);
        }

        if(array_key_exists('option_required', $option_values))
            $option_values['required'] = $option_values['option_required'];

        $option_values['option_id'] = $option_id;
        $option_values['option_value_id'] = $option_value_id;
        $option_values['value'] = $simple_option_value;

        if(!empty(array_filter($option_values)))
            $option_values['product_id'] = $product_id;

        if((!$option_id || !$option_value_id) && $only_option_data)
            return 'no_option_valid';

        if(!$option_id)
            $option_values = array();

        return $option_values;
    }

    public function _importing_assign_default_store_and_languages_in_creation($elements) {
        foreach ($elements as $key => $element) {
            $creating = array_key_exists('empty_columns', $element) && is_array($element['empty_columns']) && array_key_exists('creating', $element['empty_columns']) && $element['empty_columns']['creating'];
            if($creating && !array_key_exists('product_to_store', $element)) {
                $product_id = array_key_exists('product', $element) && is_array($element['product']) && array_key_exists('product_id', $element['product']) ? $element['product']['product_id'] : '';
                if ($product_id) {
                    $elements[$key]['product_to_store'] = array(
                        array(
                            'product_id' => $product_id,
                            'store_id' => 0
                        )
                    );
                }
            }
        }

        if($this->count_languages_real > 1 && !$this->multilanguage && array_key_exists('product_description', $elements[0])) {
            foreach ($elements as $key => $element) {
                $creating = array_key_exists('empty_columns', $element) && is_array($element['empty_columns']) && array_key_exists('creating', $element['empty_columns']) && $element['empty_columns']['creating'];
                $product_id = array_key_exists('product', $element) && is_array($element['product']) && array_key_exists('product_id', $element['product']) ? $element['product']['product_id'] : '';
                if($product_id) {
                    if ($creating && empty($element['product_description'])) {
                        $element['product_description'] = array();
                        $element['product_description'][$this->default_language_id]['name'] = $this->language->get('profile_import_product_missing_description_default');
                        $element['product_description'][$this->default_language_id]['product_id'] = $product_id;
                        //$this->exception(sprintf($this->language->get('progress_import_product_error_empty_description'), json_encode($element)));
                    }

                    if ($creating && !empty($element['product_description']) && count($element['product_description']) == 1) {
                        $lang_data = reset($element['product_description']);
                        $elements[$key]['product_description'] = array();
                        foreach ($this->languages_ids as $id => $code) {
                            $lang_data['language_id'] = $id;
                            $elements[$key]['product_description'][] = $lang_data;
                        }
                    }
                }
            }
        }

        return $elements;
    }

    public function _exporting_process_product_to_category($current_data, $product_id, $columns) {
        if(!$this->cat_tree) {
            $cat_number = $this->profile['import_xls_cat_number'];
            $categories = $this->get_product_categories($product_id, $cat_number);

            foreach ($categories as $position => $cat_id) {
                $real_position = $position + 1;

                foreach ($columns as $key => $col_info) {
                    if($col_info['field'] == 'main_category') {
                        $main_category_id = $this->get_product_main_category($product_id);
                        if($main_category_id) {
                            $allow_ids = $this->check_allow_ids($col_info);
                            if($allow_ids)
                                $current_data[$col_info['custom_name']] = $main_category_id;
                            else
                                $current_data[$col_info['custom_name']] = array_key_exists($main_category_id, $this->all_categories) && array_key_exists($this->default_language_id, $this->all_categories[$main_category_id]['name']) ? $this->all_categories[$main_category_id]['name'][$this->default_language_id] : '';
                        }
                        continue;
                    }
                    $allow_ids = $this->check_allow_ids($col_info);
                    $identificator_split = explode('_', $col_info['identificator']);
                    if ($real_position == $identificator_split[0]) {
                        if(!$allow_ids) {
                            foreach ($this->languages as $key2 => $lang) {
                                if ($identificator_split[1] == $lang['language_id']) {
                                    $current_data[$col_info['custom_name']] = array_key_exists($cat_id, $this->all_categories) && array_key_exists($lang['language_id'], $this->all_categories[$cat_id]['name']) ? $this->all_categories[$cat_id]['name'][$lang['language_id']] : '';
                                    break;
                                }
                            }
                        } else
                            $current_data[$col_info['custom_name']] = $cat_id;
                    }
                }
            }
        } else {
            $cat_parent_number = $this->profile['import_xls_cat_tree_number'];
            $cat_children_number = $this->profile['import_xls_cat_tree_children_number'];

            $categories_tree = $this->get_product_categories_tree($product_id, $cat_parent_number, $cat_children_number);

            /*if(!$this->last_cat_assign)
                $categories_tree = $this->get_deeper_trees($categories_tree);*/

            foreach ($columns as $key => $col_info) {
                if($col_info['field'] == 'main_category') {
                    $main_category_id = $this->get_product_main_category($product_id);
                    if($main_category_id) {
                        $allow_ids = $this->check_allow_ids($col_info);
                        if($allow_ids)
                            $current_data[$col_info['custom_name']] = $main_category_id;
                        else
                            $current_data[$col_info['custom_name']] = array_key_exists($main_category_id, $this->all_categories) && array_key_exists($this->default_language_id, $this->all_categories[$main_category_id]['name']) ? $this->all_categories[$main_category_id]['name'][$this->default_language_id] : '';
                    }
                    continue;
                }
                $allow_ids = $this->check_allow_ids($col_info);

                $identificator_split = explode('_', $col_info['identificator']);
                $is_parent = count($identificator_split) == 2;
                $parent_position = $identificator_split[0]-1;
                if ($is_parent)
                    $language_id = $identificator_split[1];
                else {
                    $children_level = $identificator_split[1];
                    $language_id = $identificator_split[2];
                }

                if (array_key_exists($parent_position, $categories_tree) && !empty($categories_tree[$parent_position])) {
                    if ($is_parent && array_key_exists('name', $categories_tree[$parent_position])) {
                        $cat_value = $allow_ids ? $categories_tree[$parent_position]['category_id'] : (array_key_exists($language_id, $categories_tree[$parent_position]['name']) ? $categories_tree[$parent_position]['name'][$language_id] : '');
                        if(!empty($cat_value))
                            $current_data[$col_info['custom_name']] = $cat_value;
                    } elseif (!$is_parent) {
                        $current_category = $categories_tree[$parent_position];
                        $exist_children = true;
                        for ($i = 0; $i < $children_level; $i++) {
                            if (!empty($current_category['childrens']))
                                $current_category = $current_category['childrens'][0];
                            else {
                                $exist_children = false;
                                break;
                            }
                        }
                        if ($exist_children && array_key_exists('name', $current_category)) {
                            $cat_value = $allow_ids ? $current_category['category_id'] : (array_key_exists($language_id, $current_category['name']) ? $current_category['name'][$language_id] : '');
                            if(!empty($cat_value))
                                $current_data[$col_info['custom_name']] = $cat_value;
                        }
                    }
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_filter($current_data, $product_id, $columns) {
        $filter_group_number = $this->profile['import_xls_filter_group_number'];
        $filter_filter_number = $this->profile['import_xls_filter_number'];
        $filters = $this->get_product_filters($product_id, $filter_group_number, $filter_filter_number);
        foreach ($filters as $filter_group_position => $fg_info) {
            $filter_group_id = $fg_info['filter_group_id'];
            $filters = $fg_info['filters'];
            $real_position = $filter_group_position+1;
            if(!empty($filter_group_id)) {
                foreach ($columns as $key => $col_info) {
                    $allow_ids = $this->check_allow_ids($col_info);
                    $identificator_split = explode('_', $col_info['identificator']);
                    if ($real_position == $identificator_split[0]) {
                        $is_filter_group = count($identificator_split) == 2;

                        if($allow_ids) {
                            if ($is_filter_group)
                                $current_data[$col_info['custom_name']] = $filter_group_id;
                            else {
                                $deep_filter = $identificator_split[1] - 1;
                                $filter_id = array_key_exists($deep_filter, $filters) ? $filters[$deep_filter] : '';
                                $current_data[$col_info['custom_name']] = $filter_id;
                            }
                        } else {
                            foreach ($this->languages as $key2 => $lang) {
                                if ($is_filter_group) { //Filter group
                                    if ($identificator_split[1] == $lang['language_id']) {
                                        $current_data[$col_info['custom_name']] = array_key_exists($filter_group_id, $this->all_filters) && array_key_exists($lang['language_id'], $this->all_filters[$filter_group_id]['name']) ? $this->all_filters[$filter_group_id]['name'][$lang['language_id']] : '';
                                        break;
                                    }
                                } else { //Filter
                                    $deep_filter = $identificator_split[1] - 1;
                                    $language_id = $identificator_split[2];

                                    $filter_id = array_key_exists($deep_filter, $filters) ? $filters[$deep_filter] : '';
                                    if (!empty($filter_id) && array_key_exists($filter_id, $this->all_filters[$filter_group_id]['filters']) && array_key_exists($language_id, $this->all_filters[$filter_group_id]['filters'][$filter_id]['name'])) {
                                        $current_data[$col_info['custom_name']] = $this->all_filters[$filter_group_id]['filters'][$filter_id]['name'][$language_id];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_attribute($current_data, $product_id, $columns) {
        $attribute_number = $this->profile['import_xls_attribute_number'];
        $attributes = $this->get_product_attributes($product_id);
        foreach ($columns as $key => $col_info) {
            $allow_ids = $this->check_allow_ids($col_info);
            $identificator_split = explode('_', $col_info['identificator']);
            $position = $identificator_split[0]-1;
            $language_id = !empty($identificator_split[1]) ? $identificator_split[1] : $this->default_language_id;

            $is_attribute_group = $col_info['field'] == 'attribute_group';
            $is_attribute = $col_info['field'] == 'attribute';
            $is_attribute_value = $col_info['field'] == 'attribute_value';

            if(array_key_exists($position, $attributes)) {
                if(!$allow_ids) {
                    foreach ($this->languages as $key2 => $lang) {
                        if ($language_id == $lang['language_id']) {
                            $index = $is_attribute_group ? 'attribute_group_name' : ($is_attribute ? 'name' : ($is_attribute_value ? 'text' : ''));
                            if (!empty($index) && array_key_exists($lang['language_id'], $attributes[$position][$index]) && !empty($attributes[$position][$index][$lang['language_id']])) {
                                $current_data[$col_info['custom_name']] = $attributes[$position][$index][$lang['language_id']];
                            }
                        }
                    }
                } else {
                    $index = $is_attribute_group ? 'attribute_group_id' : 'attribute_id';
                    $current_data[$col_info['custom_name']] = $attributes[$position][$index];
                }
            } else {
                $current_data[$col_info['custom_name']] = '';
            }
        }
        return $current_data;
    }

    public function _exporting_process_seo_url($current_data, $product_id, $columns) {
        $seo_urls = $this->get_product_seo_urls($product_id);
        if($this->is_oc_3x) {
            foreach ($columns as $key => $col_info) {
                $store_id = $col_info['store_id'];
                $language_id = !array_key_exists('language_id', $col_info) ? $this->default_language_id : $col_info['language_id'];
                foreach ($seo_urls as $seo_url_store_id => $seo_url_names) {
                    if ($store_id == $seo_url_store_id && array_key_exists($language_id, $seo_url_names) && !empty($seo_url_names[$language_id]))
                        $current_data[$col_info['custom_name']] = $seo_url_names[$language_id];
                }
            }
        } else {
            foreach ($columns as $key => $col_info) {
                $current_data[$col_info['custom_name']] = $seo_urls;
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_special($current_data, $product_id, $columns) {
        $product_specials = $this->get_product_specials($product_id);

        if(!empty($product_specials)) {
            foreach ($columns as $key => $col_info) {
                $identificator = $col_info['identificator'];
                $field = $col_info['field'];
                if(array_key_exists($identificator, $product_specials) && array_key_exists($field, $product_specials[$identificator])) {
                    $current_data[$col_info['custom_name']] = $product_specials[$identificator][$field];
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_discount($current_data, $product_id, $columns) {
        $product_discounts = $this->get_product_discounts($product_id);
        if(!empty($product_discounts)) {
            foreach ($columns as $key => $col_info) {
                $identificator = $col_info['identificator'];
                $field = $col_info['field'];
                if(array_key_exists($identificator, $product_discounts) && array_key_exists($field, $product_discounts[$identificator])) {
                    $current_data[$col_info['custom_name']] = $product_discounts[$identificator][$field];
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_related($current_data, $product_id, $columns) {
        $related = $this->get_product_related($product_id);
        $some_related = !empty($related['models']);
        if(!empty($some_related)) {
            foreach ($columns as $key => $col_info) {
                $current_data[$col_info['custom_name']] = $related['models'];
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_reward($current_data, $product_id, $columns) {
        $rewards = $this->get_product_rewards($product_id);

        if(!empty($rewards)) {
            foreach ($columns as $key => $col_info) {
                $identificator = $col_info['identificator'];
                if(array_key_exists($identificator, $rewards)) {
                    $current_data[$col_info['custom_name']] = $rewards[$identificator];
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_image($current_data, $product_id, $columns) {
        $images = $this->get_product_images($product_id);
        if(!empty($images)) {
            foreach ($columns as $key => $col_info) {
                $identificator = $col_info['identificator']-1;
                if(array_key_exists($identificator, $images)) {
                    $current_data[$col_info['custom_name']] = $images[$identificator]['image'];
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_manufacturer($current_data, $product_id, $columns) {
        $manufacturer_id = $this->get_product_manufacturer($product_id);
        if(!empty($manufacturer_id)) {
            foreach ($columns as $key => $col_info) {
                $allow_ids = $this->check_allow_ids($col_info);
                if($allow_ids)
                    $current_data[$col_info['custom_name']] = $manufacturer_id;
                else {
                    $language_id = array_key_exists('language_id', $col_info) && !empty($col_info['language_id']) ? $col_info['language_id'] : $this->default_language_id;
                    $current_data[$col_info['custom_name']] = array_key_exists($manufacturer_id, $this->all_manufacturers) && array_key_exists($language_id, $this->all_manufacturers[$manufacturer_id]) ? $this->all_manufacturers[$manufacturer_id][$language_id] : '';
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_to_store($current_data, $product_id, $columns) {
        $stores = $this->get_product_stores($product_id);

        if(!empty($stores)) {
            foreach ($columns as $key => $col_info) {
                $current_data[$col_info['custom_name']] = implode('|',$stores);
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_to_layout($current_data, $product_id, $columns) {
        $layouts = $this->get_product_layouts($product_id);

        if(!empty($layouts)) {
            foreach ($columns as $key => $col_info) {
                $name_instead_id = array_key_exists('name_instead_id', $col_info) && $col_info['name_instead_id'];
                $conversion_global_var = array_key_exists('conversion_global_var', $col_info) && $col_info['conversion_global_var'] ? $col_info['conversion_global_var'] : '';

                $store_id = $col_info['store_id'];
                if(array_key_exists($store_id, $layouts)) {
                    $layout_id = $layouts[$store_id];
                    $current_data[$col_info['custom_name']] = $name_instead_id && $conversion_global_var && array_key_exists($layout_id, $this->{$conversion_global_var}) ? $this->{$conversion_global_var}[$layout_id] : $layout_id;
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_to_download($current_data, $product_id, $columns) {
        $downloads = $this->get_product_downloads($product_id);
        if(!empty($downloads)) {
            foreach ($columns as $key => $col_info) {
                $identificator_split = explode('_', $col_info['identificator']);
                $position = $identificator_split[0] - 1;
                $language_id = array_key_exists(1, $identificator_split) ? $identificator_split[1] : '';

                $is_name = $col_info['field'] == 'name';
                if (array_key_exists($position, $downloads)) {
                    $download_id = $downloads[$position];
                    if (array_key_exists($download_id, $this->all_downloads)) {
                        if ($is_name && array_key_exists($language_id, $this->all_downloads[$download_id]['name']))
                            $current_data[$col_info['custom_name']] = $this->all_downloads[$download_id]['name'][$language_id];
                        else
                            $current_data[$col_info['custom_name']] = $this->all_downloads[$download_id][$col_info['field']];
                    }
                }
            }
        }
        return $current_data;
    }

    public function _exporting_process_product_options_combinations($product_id, $columns) {
        $optioncombinations_lib = new \optionscombinations\OptionsCombinationsLib($this->registry);
        $this->load->model('extension/module/options_combinations');
        $combinations = $optioncombinations_lib->getCombinations(['product_id' => $product_id]);

        if (empty($combinations))
            return [];

        $combinations_to_export =[];
        foreach ($combinations as $combination) {
            $combination_to_export = [];
            $combination_options_mapped = $optioncombinations_lib->getCombinationOptionsMapped($combination['id']);
            foreach ($columns as $col_name => $col_info) {
                if ($col_info['field'] == 'options'){
                    $index = explode('_', $col_info['identificator'])[0] - 1;
                    $id_instead_of_name = isset($col_info['id_instead_of_name']) && $col_info['id_instead_of_name'] == 1;
                    if (!empty(array_keys($combination_options_mapped)[$index])){
                        $option_id = array_keys($combination_options_mapped)[$index];
                        switch ($col_info['inner_field']){
                            case 'option':
                                if ($id_instead_of_name)
                                    $combination_to_export[$col_info['custom_name']] = $option_id;
                                else{
                                    $option = $this->all_options[$option_id];
                                    $combination_to_export[$col_info['custom_name']] = (array_key_exists('language_id', $col_info)) ?
                                    $option['name'][$col_info['language_id']] :
                                    $option['name'];
                                }
                                break;
                            case 'option_value':
                                if ($id_instead_of_name){
                                    $combination_to_export[$col_info['custom_name']] = is_array($combination_options_mapped[$option_id])
                                        ? json_encode(array_values($combination_options_mapped[$option_id]))
                                        : $combination_options_mapped[$option_id];
                                }
                                else{
                                    if (is_array($combination_options_mapped[$option_id])){
                                        $values = [];
                                        foreach ($combination_options_mapped[$option_id] as $option_value_id){
                                            $option_value = $this->all_option_values[$option_value_id];
                                            $values[] = (array_key_exists('language_id', $col_info)) ?
                                                $option_value['name'][$col_info['language_id']] :
                                                $option_value['name'];
                                        }
                                        $combination_to_export[$col_info['custom_name']] = json_encode(array_values($values));
                                    }
                                    else if (is_numeric($combination_options_mapped[$option_id]) && array_key_exists($combination_options_mapped[$option_id], $this->all_option_values)){
                                        $option_value = $this->all_option_values[$combination_options_mapped[$option_id]];
                                        if (!array_key_exists($col_info['language_id'], $option_value['name'])) {
                                            $option_value['name'][$col_info['language_id']] = array_values($option_value['name'])[0];
                                        }
                                        $combination_to_export[$col_info['custom_name']] = (array_key_exists('language_id', $col_info)) ?
                                            $option_value['name'][$col_info['language_id']] :
                                            $option_value['name'];
                                    } else
                                        $combination_to_export[$col_info['custom_name']] = $combination_options_mapped[$option_id];
                                }
                                break;
                            case 'option_type':
                                $option = $this->all_options[$option_id];
                                $combination_to_export[$col_info['custom_name']] = $option['type'];
                                break;
                        }
                    }
                }
                else if (isset($col_info['data_type']) && $col_info['data_type'] == 'json') {
                    $json_value = json_decode($combination[$col_info['field']], true);
                    //behaviour for prices, specials and discounts
                    if ($col_info['field'] == 'prices' && ($col_info['inner_field'] == 'option_price' || $col_info['inner_field'] == 'option_points')) {
                        $inner_field = $col_info['inner_field'];
                        $option_cmb_values = $json_value[$inner_field];
                        if (is_array($option_cmb_values))
                            foreach ($option_cmb_values as $option_cmb_value) {
                                if (!array_key_exists('customer_group_id', $option_cmb_value) || $col_info['customer_group_id'] == $option_cmb_value['customer_group_id']) {
                                    $combination_to_export[$col_info['custom_name']] = array_key_exists($col_info['key'], $option_cmb_value) ? $option_cmb_value[$col_info['key']] : '';
                                }
                            }
                    } elseif ($col_info['field'] == 'prices' && in_array($col_info['inner_field'], ['option_special', 'option_discount'])) {
                        $inner_field = $col_info['inner_field'];
                        $option_cmb_values = $json_value[$inner_field];
                        $identificator = explode('_', $col_info['identificator'])[0];
                        if (is_array($option_cmb_values))
                            foreach ($option_cmb_values as $index => $option_cmb_value) {
                                if ($index == $identificator - 1) {
                                    if ($col_info['key'] == 'customer_group_id' && !isset($col_info['id_instead_of_name'])) {
                                        $customer_group = array_filter($this->all_customer_groups, function ($element) use ($option_cmb_value, $col_info) {
                                            return $element['customer_group_id'] == $option_cmb_value['customer_group_id'] && $element['language_id'] == $col_info['language_id'];
                                        });
                                        $combination_to_export[$col_info['custom_name']] = count($customer_group) != 0 ? array_pop($customer_group)['name'] : '';
                                    } else {
                                        $combination_to_export[$col_info['custom_name']] = array_key_exists($col_info['key'], $option_cmb_value) ? $option_cmb_value[$col_info['key']] : '';
                                    }
                                }
                            }
                    } elseif ($col_info['field'] == 'images') {
                        if (!empty($json_value) && $json_value != '') {
                            foreach ($json_value as $index => $image_value) {
                                if ($col_info['identificator'] - 1 == $index) {
                                    $combination_to_export[$col_info['custom_name']] = $image_value;
                                }
                            }
                        }
                    }
                } else {
                    $combination_to_export[$col_info['custom_name']] = $combination[$col_info['field']];
                }
            }
            if (!empty($combination_to_export))
                $combinations_to_export[] = $combination_to_export;
        }
        return $combinations_to_export;
    }

    public function _exporting_process_product_option_value($product_id, $columns) {
        $new_columns = array();
        $product_option_values = $this->get_product_option_values($product_id);

        if(!empty($product_option_values)) {
            foreach ($product_option_values as $key => $opval) {
                $optval_id = $opval['option_value_id'];
                $optval_name = array_key_exists($optval_id, $this->all_option_values) ? $this->all_option_values[$optval_id]['name'] : '';
                $opt_name = array_key_exists($optval_id, $this->all_option_values) ? $this->all_option_values[$optval_id]['option']['name'] : '';

                if (empty( $opt_name)) {
                    $opt_name = isset( $opval['option']) && isset( $opval['option']['name'])
                        ? $opval['option']['name']
                        : '';
                }

                $temp = array();
                if(!empty($optval_id)) {
                    foreach ($columns as $key => $col_info) {
                        $allow_ids = $this->check_allow_ids($col_info);
                        $language_id = array_key_exists('language_id', $col_info) ? $col_info['language_id'] : '';
                        $field_name = $col_info['field'];
                        $is_option_field = strpos($field_name, 'option_') !== false && empty($col_info['custom_field']);
                        $field_name = $is_option_field ? str_replace('option_', '', $field_name) : $field_name;
                        $final_field = $is_option_field && array_key_exists('option', $opval) ? $opval['option'] : $opval;

                        if($allow_ids) {
                            $field_name = $is_option_field ? 'option_id' : 'option_value_id';
                            $temp[$col_info['custom_name']] = $final_field[$field_name];
                        }
                        else if (!$language_id && array_key_exists($field_name, $final_field))
                            $temp[$col_info['custom_name']] = $final_field[$field_name];
                        else if ($language_id && $is_option_field && is_array($opt_name) && array_key_exists($language_id, $opt_name))
                            $temp[$col_info['custom_name']] = $opt_name[$language_id];
                        else if ($language_id && !$is_option_field && is_array($optval_name) && array_key_exists($language_id, $optval_name))
                            $temp[$col_info['custom_name']] = $optval_name[$language_id];
                    }
                    if (!empty($temp)) {
                        $new_columns[] = $temp;
                    }
                } else {
                    foreach ($columns as $key => $col_info) {
                        $allow_ids = $this->check_allow_ids($col_info);
                        $language_id = array_key_exists('language_id', $col_info) ? $col_info['language_id'] : '';
                        $field_name = $col_info['field'];
                        $is_option_field = strpos($field_name, 'option_') !== false && empty($col_info['custom_field']);
                        $field_name = $is_option_field ? str_replace('option_', '', $field_name) : $field_name;
                        $final_field = $is_option_field && array_key_exists('option', $opval) ? $opval['option'] : $opval;

                        if ($allow_ids) {
                            $field_name = $is_option_field ? 'option_id' : 'option_value_id';
                            $temp[$col_info['custom_name']] = $final_field[$field_name];
                        }
                        else if (!$language_id && array_key_exists($field_name, $final_field)) {
                            $temp[$col_info['custom_name']] = $final_field[$field_name];
                        }
                        else if ($language_id && $is_option_field && is_array($opt_name) &&
                            array_key_exists($language_id, $opt_name)) {
                            $temp[$col_info['custom_name']] = $opt_name[$language_id];
                        }
                        else if ($language_id && !$is_option_field && is_array($optval_name) &&
                            array_key_exists($language_id, $optval_name)) {
                            $temp[$col_info['custom_name']] = $optval_name[$language_id];
                        }
                    }

                    if (!empty($temp)) {
                        $new_columns[] = $temp;
                    }
                }
            }

        }

        return $new_columns;
    }

    public function options_combinations_update_main_product_quantities($data_file) {
        $product_ids = array();
        foreach ($data_file as $key => $val) {
            $product_ids[] = $val['product']['product_id'];
        }
        $product_ids = array_unique($product_ids);
        $result = $this->db->query("SELECT product_id, SUM(quantity) as total FROM ".DB_PREFIX."product_options_combinations WHERE product_id IN (".implode(",", $product_ids).") GROUP BY product_id");
        foreach ($result->rows as $key => $val) {
            $this->db->query("UPDATE ".DB_PREFIX."product SET quantity = ".$val['total']." WHERE product_id = ".$val['product_id']);
        }
        return true;
    }

    private function delete_file_if_exists($path){
        if (is_file($path)) {
            return unlink($path);
        }
        return FALSE;
    }

    private function delete_product_images($element_id){
        $product_data = $this->db->query("SELECT * FROM " . $this->escape_database_table_name("product") . " where product_id = ".$element_id)->row;

        if(!empty($product_data['image']))
            $this->delete_file_if_exists(DIR_IMAGE.$product_data['image']);

        $product_images = $this->db->query("SELECT * FROM " . $this->escape_database_table_name("product_image") . " where product_id = ".$element_id)->rows;
        foreach ($product_images as $key => $product_image) {
            $this->delete_file_if_exists(DIR_IMAGE.$product_image['image']);
        }

    }

    public function delete_element($element_id) {
        foreach ($this->delete_tables_special as $key => $table_name) {
            if(array_key_exists($table_name, $this->database_schema)) {
                if(in_array($table_name, array('seo_url', 'url_alias')))
                    $this->db->query("DELETE FROM ".$this->escape_database_table_name($table_name)." WHERE ".$this->escape_database_field('query')." = ".$this->escape_database_value($this->main_field.'='.(int)$element_id));
                else if($table_name == 'product_related')
                    $this->db->query("DELETE FROM ".$this->escape_database_table_name($table_name)." WHERE related_id = '" . (int)$element_id . "'");
            }
        }
        if (isset($this->profile['columns']['Delete']['delete_associated_images']) && $this->profile['columns']['Delete']['delete_associated_images']){
            $this->delete_product_images($element_id);
        }
        parent::delete_element($element_id);
        $this->cache->delete('product');
    }

    public function set_quantity_0($element_id) {
        $sql = "UPDATE  {$this->escape_database_table_name('product')}  SET quantity=0  WHERE {$this->escape_database_field($this->main_field)} = {$element_id}";
        $this->db->query($sql);
    }



    public function disable_element($element_id){
        $sql = "UPDATE  {$this->escape_database_table_name('product')}  SET status=0  WHERE {$this->escape_database_field($this->main_field)} = {$element_id}";
        $this->db->query($sql);
    }
}
?>
