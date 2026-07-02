<?php
    class ModelExtensionModuleIeProDatabase extends ModelExtensionModuleIePro {
        public function get_database($categories = array(), $fiel_conditions = array()) {
            $database_map = $this->get_database_fields();
            if(!empty($categories)) {
                if(!is_array($categories)) {
                    $copy_category = $categories;
                    $categories = array();
                    $categories[] = $copy_category;
                }
                $temp = array();
                foreach ($database_map as $category_temp => $tables) {
                    if(!in_array($category_temp, $categories))
                        unset($database_map[$category_temp]);
                }
            }
            $final_database_map = array();

            foreach ($database_map as $main_category => $tables) {
                foreach ($tables as $table_name => $fields) {
                    $table_name_with_prefix = $this->db_prefix.$table_name;
                    $query = 'SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE TABLE_SCHEMA = \''.DB_DATABASE.'\' AND TABLE_NAME LIKE \''.$table_name_with_prefix.'\'';
                    $result = $this->db->query($query);

                    if(!empty($result->rows)) {
                        foreach ($result->rows as $key => $fiel_db_info) {
                            $field_name = $fiel_db_info['COLUMN_NAME'];
                            if(array_key_exists($field_name, $database_map[$main_category][$table_name])) {
                                $type = $this->translate_type($fiel_db_info['DATA_TYPE']);

                                $field_copy = $database_map[$main_category][$table_name][$field_name];

                                $add_field = empty($fiel_conditions) || (!empty($fiel_conditions) && is_array($field_copy));

                                if(!empty($fiel_conditions) && $add_field) {
                                    foreach ($fiel_conditions as $condition_key => $val) {
                                        if(!array_key_exists($condition_key, $field_copy) || $val != $field_copy[$condition_key]) {
                                            $add_field = false;
                                            break;
                                        }
                                    }
                                }

                                if($add_field) {
                                    if (is_array($field_copy)) {
                                        $final_database_map[$main_category][$table_name][$field_name] = $field_copy;
                                        $final_database_map[$main_category][$table_name][$field_name]['type'] = $type;
                                        $final_database_map[$main_category][$table_name][$field_name]['real_type'] = $fiel_db_info['DATA_TYPE'];
                                    } else
                                        $final_database_map[$main_category][$table_name][$field_name] = array('type' => $type, 'real_type' => $fiel_db_info['DATA_TYPE']);
                                }
                            }
                        }
                    }
                }
            }

            return $final_database_map;
        }

        public function get_database_without_groups($with_types = true) {
            $database = $this->get_database();
            $final_database = array();
            foreach ($database as $group_name => $groups) {
                $deep = $this->array_depth($groups);

                if($deep == 3) {
                    foreach ($groups as $table_name => $fields) {
                        if(!array_key_exists($table_name, $final_database))
                            $final_database[$table_name] = $fields;
                        else
                            $final_database[$table_name] = array_merge($fields, $final_database[$table_name]);
                    }
                } else if($deep == 2)
                    if(!array_key_exists($group_name, $final_database))
                        $final_database[$group_name] = $groups;
                    else
                        $final_database[$group_name] = array_merge($groups, $final_database[$group_name]);
            }

            if(!$with_types) {
                $temp = array();

                foreach ($final_database as $table_name => $fields) {
                    $temp[$table_name] = array_keys($fields);
                }
                $final_database = $temp;
            }
            return $final_database;
        }

        public function translate_type($type) {
            if(in_array($type, array('int', 'float', 'decimal'))) {
                return 'number';
            } elseif(in_array($type, array('varchar', 'text', 'mediumtext', 'fulltext'))) {
                return 'string';
            } elseif(in_array($type, array('tinyint'))) {
                return 'boolean';
            } elseif(in_array($type, array('date', 'datetime'))) {
                return 'date';
            }
        }

        public function get_database_fields() {
            $database = array(
                'affiliates' => array(
                    'affiliate' => array(
                        'affiliate_id',
                        'firstname',
                        'lastname',
                        'email',
                        'telephone',
                        'fax',
                        'password',
                        'salt',
                        'company',
                        'website',
                        'address_1',
                        'address_2',
                        'city',
                        'postcode',
                        'country_id',
                        'zone_id',
                        'code',
                        'commission',
                        'tax',
                        'payment',
                        'cheque',
                        'paypal',
                        'bank_name',
                        'bank_branch_number',
                        'bank_swift_code',
                        'bank_account_name',
                        'bank_account_number',
                        'ip',
                        'status',
                        'approved',
                        'date_added',
                    ),
                    'affiliate_activity' => array(
                        'activity_id',
                        'affiliate_id',
                        'key',
                        'data',
                        'ip',
                        'date_added',
                    ),
                    'affiliate_login' => array(
                        'affiliate_login_id',
                        'email',
                        'ip',
                        'total',
                        'date_added',
                        'date_modified',
                    ),
                    'affiliate_transaction' => array(
                        'affiliate_transaction_id',
                        'affiliate_id',
                        'order_id',
                        'description',
                        'amount',
                        'date_added',
                    ),
                ),
                'attribute_groups' => array(
                    'attribute_group' => array(
                        'attribute_group_id' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'attribute_group_description' => array(
                        'attribute_group_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name',
                    ),
                ),
                'attributes' => array(
                    'attribute' => array(
                        'attribute_id' => array('is_filter' => true),
                        'attribute_group_id' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'attribute_description' => array(
                        'attribute_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                    ),
                ),
                'banners' => array(
                    'banner' => array(
                        'banner_id',
                        'name',
                        'status',
                    ),
                    'banner_image' => array(
                        'banner_image_id',
                        'banner_id',
                        'language_id',
                        'title',
                        'link',
                        'image',
                        'sort_order',
                    ),
                    'banner_image_description' => array(
                        'banner_image_id',
                        'language_id',
                        'banner_id',
                        'title',
                    ),
                ),
                'categories' => array(
                    'category' => array(
                        'category_id' => array('is_filter' => true),
                        'image' => array('is_filter' => true),
                        'parent_id' => array('is_filter' => true),
                        'top' => array('is_filter' => true),
                        'column' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                        'status' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                        'date_modified' => array('is_filter' => true),
                    ),
                    'category_description' => array(
                        'category_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                        'description' => array('is_filter' => true),
                        'meta_title' => array('is_filter' => true),
                        'meta_h1' => array('is_filter' => true),
                        'meta_description' => array('is_filter' => true),
                        'meta_keyword' => array('is_filter' => true),
                    ),
                    'category_filter' => array(
                        'category_id' => array('is_filter' => true),
                        'filter_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                    'category_path' => array(
                        'category_id' => array('is_filter' => true),
                        'path_id' => array('is_filter' => true),
                        'level' => array('is_filter' => true),
                    ),
                    'category_to_layout' => array(
                        'category_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'layout_id' => array('is_filter' => true),
                    ),
                    'category_to_store' => array(
                        'category_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                ),
                'countries' => array(
                    'country' => array(
                        'country_id',
                        'name',
                        'iso_code_2',
                        'iso_code_3',
                        'address_format',
                        'postcode_required',
                        'status',
                    ),
                ),
                'coupons' => array(
                    'coupon' => array(
                        'coupon_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'code' => array('is_filter' => true),
                        'type' => array('is_filter' => true),
                        'discount' => array('is_filter' => true),
                        'logged' => array('is_filter' => true),
                        'shipping' => array('is_filter' => true),
                        'total' => array('is_filter' => true),
                        'date_start' => array('is_filter' => true),
                        'date_end' => array('is_filter' => true),
                        'uses_total' => array('is_filter' => true),
                        'uses_customer' => array('is_filter' => true),
                        'status' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                    ),
                    'coupon_category' => array(
                        'coupon_id' => array('is_filter' => true),
                        'category_id' => array('is_filter' => true),
                    ),
                    'coupon_history' => array(
                        'coupon_history_id' => array('is_filter' => true),
                        'coupon_id' => array('is_filter' => true),
                        'order_id' => array('is_filter' => true),
                        'customer_id' => array('is_filter' => true),
                        'amount' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                    ),
                    'coupon_product' => array(
                        'coupon_product_id' => array('is_filter' => true),
                        'coupon_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                    ),
                ),
                'currencies' => array(
                    'currency' => array(
                        'currency_id',
                        'title',
                        'code',
                        'symbol_left',
                        'symbol_right',
                        'decimal_place',
                        'value',
                        'status',
                        'date_modified',
                    ),
                ),
                'custom_fields' => array(
                    'custom_field' => array(
                        'custom_field_id',
                        'type',
                        'value',
                        'location',
                        'position',
                        'required',
                        'status',
                        'sort_order',
                    ),
                    'custom_field_customer_group' => array(
                        'custom_field_id',
                        'customer_group_id',
                        'required',
                    ),
                    'custom_field_description' => array(
                        'custom_field_id',
                        'language_id',
                        'name',
                    ),
                    'custom_field_value' => array(
                        'custom_field_value_id',
                        'custom_field_id',
                        'sort_order',
                    ),
                    'custom_field_value_description' => array(
                        'custom_field_value_id',
                        'language_id',
                        'custom_field_id',
                        'name',
                    ),
                    'custom_field_to_customer_group' => array(
                        'custom_field_id',
                        'customer_group_id',
                    )
                ),
                'customer_groups' => array(
                    'customer_group' => array(
                        'customer_group_id' => array('is_filter' => true),
                        'approval' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'company_id_display' => array('is_filter' => true),
                        'company_id_required' => array('is_filter' => true),
                        'tax_id_display' => array('is_filter' => true),
                        'tax_id_required' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'customer_group_description' => array(
                        'customer_group_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                        'description' => array('is_filter' => true),
                    ),
                ),
                'customers' => array(
                    'customer' => array(
                        'customer_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true),
                        'firstname' => array('is_filter' => true),
                        'lastname' => array('is_filter' => true),
                        'email' => array('is_filter' => true),
                        'telephone' => array('is_filter' => true),
                        'fax' => array('is_filter' => true),
                        'password' => array('is_filter' => true),
                        'salt' => array('is_filter' => true),
                        'cart' => array('is_filter' => true),
                        'wishlist' => array('is_filter' => true),
                        'newsletter' => array('is_filter' => true),
                        'address_id' => array('is_filter' => true),
                        'custom_field' => array('is_filter' => true),
                        'ip' => array('is_filter' => true),
                        'status' => array('is_filter' => true),
                        'approved' => array('is_filter' => true),
                        'safe' => array('is_filter' => true),
                        'code' => array('is_filter' => true),
                        'token' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                    ),
                    'customer_activity' => array(
                        'activity_id',
                        'customer_id',
                        'key',
                        'data',
                        'ip',
                        'date_added',
                    ),
                    'customer_affiliate' => array(
                        'customer_id',
                        'company',
                        'website',
                        'tracking',
                        'commission',
                        'tax',
                        'payment',
                        'cheque',
                        'paypal',
                        'bank_name',
                        'bank_branch_number',
                        'bank_swift_code',
                        'bank_account_name',
                        'bank_account_number',
                        'custom_field',
                        'status',
                        'date_added',
                    ),
                    'customer_approval' => array(
                        'customer_approval_id',
                        'customer_id',
                        'type',
                        'date_added',
                    ),
                    'customer_ban_ip' => array(
                        'customer_ban_ip_id',
                        'ip',
                    ),
                    'customer_field' => array(
                        'customer_id',
                        'custom_field_id',
                        'custom_field_value_id',
                        'name',
                        'value',
                        'sort_order',
                    ),

                    'customer_history' => array(
                        'customer_history_id',
                        'customer_id',
                        'comment',
                        'date_added',
                    ),
                    'customer_ip' => array(
                        'customer_ip_id',
                        'customer_id',
                        'ip',
                        'date_added',
                    ),
                    'customer_login' => array(
                        'customer_login_id',
                        'email',
                        'ip',
                        'total',
                        'date_added',
                        'date_modified',
                    ),
                    'customer_online' => array(
                        'ip',
                        'customer_id',
                        'url',
                        'referer',
                        'date_added',
                    ),
                    'customer_reward' => array(
                        'customer_reward_id',
                        'customer_id',
                        'order_id',
                        'description',
                        'points',
                        'date_added',
                    ),
                    'customer_search' => array(
                        'customer_search_id',
                        'store_id',
                        'language_id',
                        'customer_id',
                        'keyword',
                        'category_id',
                        'sub_category',
                        'description',
                        'products',
                        'ip',
                        'date_added',
                    ),
                    'customer_transaction' => array(
                        'customer_transaction_id',
                        'customer_id',
                        'order_id',
                        'description',
                        'amount',
                        'date_added',
                    ),
                    'customer_wishlist' => array(
                        'customer_id',
                        'product_id',
                        'date_added',
                    ),
                ),
                'addresses' => array(
                    'address' => array(
                        'address_id' => array('is_filter' => true),
                        'customer_id' => array('is_filter' => true),
                        'firstname' => array('is_filter' => true),
                        'lastname' => array('is_filter' => true),
                        'company' => array('is_filter' => true),
                        'company_id' => array('is_filter' => true),
                        'tax_id' => array('is_filter' => true),
                        'address_1' => array('is_filter' => true),
                        'address_2' => array('is_filter' => true),
                        'city' => array('is_filter' => true),
                        'postcode' => array('is_filter' => true),
                        'country_id' => array('is_filter' => true),
                        'zone_id' => array('is_filter' => true),
                        'custom_field' => array('is_filter' => true),
                    ),
                ),
                'downloads' => array(
                    'download' => array(
                        'download_id',
                        'filename',
                        'mask',
                        'remaining',
                        'date_added',
                    ),
                    'download_description' => array(
                        'download_id',
                        'language_id',
                        'name',
                    ),
                ),
                'filters' => array(
                    'filter' => array(
                        'filter_id' => array('is_filter' => true),
                        'filter_group_id' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'filter_description' => array(
                        'filter_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'filter_group_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                    ),
                ),
                'filter_groups' => array(
                    'filter_group' => array(
                        'filter_group_id' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'filter_group_description' => array(
                        'filter_group_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                    ),
                ),
                'geo_zones' => array(
                    'geo_zone' => array(
                        'geo_zone_id',
                        'name',
                        'description',
                        'date_modified',
                        'date_added',
                    ),
                ),
                'informations' => array(
                    'information' => array(
                        'information_id',
                        'bottom',
                        'sort_order',
                        'status',
                    ),
                    'information_description' => array(
                        'information_id',
                        'language_id',
                        'title',
                        'description',
                        'meta_title',
                        'meta_description',
                        'meta_keyword',
                    ),
                    'information_to_layout' => array(
                        'information_id',
                        'store_id',
                        'layout_id',
                    ),
                    'information_to_store' => array(
                        'information_id',
                        'store_id',
                    ),
                ),
                'languages' => array(
                    'language' => array(
                        'language_id',
                        'name',
                        'code',
                        'locale',
                        'image',
                        'directory',
                        'filename',
                        'sort_order',
                        'status',
                    ),
                ),
                'layouts' => array(
                    'layout' => array(
                        'layout_id',
                        'name',
                    ),
                    'layout_route' => array(
                        'layout_route_id',
                        'layout_id',
                        'store_id',
                        'route',
                    ),
                ),
                'lengths' => array(
                    'length_class' => array(
                        'length_class_id',
                        'value',
                    ),
                    'length_class_description' => array(
                        'length_class_id',
                        'language_id',
                        'title',
                        'unit',
                    ),
                ),
                'locations' => array(
                    'location' => array(
                        'location_id',
                        'name',
                        'address',
                        'telephone',
                        'fax',
                        'geocode',
                        'image',
                        'open',
                        'comment',
                    ),
                ),
                'manufacturers' => array(
                    'manufacturer' => array(
                        'manufacturer_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'image' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'manufacturer_to_store' => array(
                        'manufacturer_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                ),
                'marketings' => array(
                    'marketing' => array(
                        'marketing_id',
                        'name',
                        'description',
                        'code',
                        'clicks',
                        'date_added',
                    ),
                ),
                'modifications' => array(
                    'modification' => array(
                        'modification_id',
                        'extension_install_id',
                        'name',
                        'code',
                        'author',
                        'version',
                        'link',
                        'xml',
                        'status',
                        'date_added',
                    ),
                ),
                'options' => array(
                    'option' => array(
                        'option_id' => array('is_filter' => true),
                        'type' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'option_description' => array(
                        'option_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                    ),
                ),
                'option_values' => array(
                    'option_value' => array(
                        'option_value_id'  => array('is_filter' => true),
                        'option_id'  => array('is_filter' => true),
                        'image'  => array('is_filter' => true),
                        'sort_order'  => array('is_filter' => true),
                    ),
                    'option_value_description' => array(
                        'option_value_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'option_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                    ),
                ),
                'orders' => array(
                    'order' => array(
                        'order_id' => array('is_filter' => true),
                        'invoice_no' => array('is_filter' => true),
                        'invoice_prefix' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true),
                        'store_name' => array('is_filter' => true),
                        'store_url' => array('is_filter' => true),
                        'customer_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true),
                        'firstname' => array('is_filter' => true),
                        'lastname' => array('is_filter' => true),
                        'email' => array('is_filter' => true),
                        'telephone' => array('is_filter' => true),
                        'fax' => array('is_filter' => true),
                        'custom_field' => array('is_filter' => true),
                        'payment_firstname' => array('is_filter' => true),
                        'payment_lastname' => array('is_filter' => true),
                        'payment_company' => array('is_filter' => true),
                        'payment_company_id' => array('is_filter' => true),
                        'payment_tax_id' => array('is_filter' => true),
                        'payment_address_1' => array('is_filter' => true),
                        'payment_address_2' => array('is_filter' => true),
                        'payment_city' => array('is_filter' => true),
                        'payment_postcode' => array('is_filter' => true),
                        'payment_country' => array('is_filter' => true),
                        'payment_country_id' => array('is_filter' => true),
                        'payment_zone' => array('is_filter' => true),
                        'payment_zone_id' => array('is_filter' => true),
                        'payment_address_format' => array('is_filter' => true),
                        'payment_custom_field' => array('is_filter' => true),
                        'payment_method' => array('is_filter' => true),
                        'payment_code' => array('is_filter' => true),
                        'shipping_firstname' => array('is_filter' => true),
                        'shipping_lastname' => array('is_filter' => true),
                        'shipping_company' => array('is_filter' => true),
                        'shipping_address_1' => array('is_filter' => true),
                        'shipping_address_2' => array('is_filter' => true),
                        'shipping_city' => array('is_filter' => true),
                        'shipping_postcode' => array('is_filter' => true),
                        'shipping_country' => array('is_filter' => true),
                        'shipping_country_id' => array('is_filter' => true),
                        'shipping_zone' => array('is_filter' => true),
                        'shipping_zone_id' => array('is_filter' => true),
                        'shipping_address_format' => array('is_filter' => true),
                        'shipping_custom_field' => array('is_filter' => true),
                        'shipping_method' => array('is_filter' => true),
                        'shipping_code' => array('is_filter' => true),
                        'comment' => array('is_filter' => true),
                        'total' => array('is_filter' => true),
                        'order_status_id' => array('is_filter' => true),
                        'affiliate_id' => array('is_filter' => true),
                        'commission' => array('is_filter' => true),
                        'marketing_id' => array('is_filter' => true),
                        'tracking' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true),
                        'currency_id' => array('is_filter' => true),
                        'currency_code' => array('is_filter' => true),
                        'currency_value' => array('is_filter' => true),
                        'ip' => array('is_filter' => true),
                        'forwarded_ip' => array('is_filter' => true),
                        'user_agent' => array('is_filter' => true),
                        'accept_language' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                        'date_modified' => array('is_filter' => true),
                    ),
                    'order_custom_field' => array(
                        'order_custom_field_id',
                        'order_id',
                        'custom_field_id',
                        'custom_field_value_id',
                        'name',
                        'value',
                        'type',
                        'location',
                    ),
                    'order_field' => array(
                        'order_id',
                        'custom_field_id',
                        'custom_field_value_id',
                        'name',
                        'value',
                        'sort_order',
                    ),
                    'order_download' => array(
                        'order_download_id',
                        'order_id',
                        'order_product_id',
                        'name',
                        'filename',
                        'mask',
                        'remaining',
                    ),
                    'order_fraud' => array(
                        'order_id',
                        'customer_id',
                        'country_match',
                        'country_code',
                        'high_risk_country',
                        'distance',
                        'ip_region',
                        'ip_city',
                        'ip_latitude',
                        'ip_longitude',
                        'ip_isp',
                        'ip_org',
                        'ip_asnum',
                        'ip_user_type',
                        'ip_country_confidence',
                        'ip_region_confidence',
                        'ip_city_confidence',
                        'ip_postal_confidence',
                        'ip_postal_code',
                        'ip_accuracy_radius',
                        'ip_net_speed_cell',
                        'ip_metro_code',
                        'ip_area_code',
                        'ip_time_zone',
                        'ip_region_name',
                        'ip_domain',
                        'ip_country_name',
                        'ip_continent_code',
                        'ip_corporate_proxy',
                        'anonymous_proxy',
                        'proxy_score',
                        'is_trans_proxy',
                        'free_mail',
                        'carder_email',
                        'high_risk_username',
                        'high_risk_password',
                        'bin_match',
                        'bin_country',
                        'bin_name_match',
                        'bin_name',
                        'bin_phone_match',
                        'bin_phone',
                        'customer_phone_in_billing_location',
                        'ship_forward',
                        'city_postal_match',
                        'ship_city_postal_match',
                        'score',
                        'explanation',
                        'risk_score',
                        'queries_remaining',
                        'maxmind_id',
                        'error',
                        'date_added',
                    ),
                    'order_history' => array(
                        'order_history_id',
                        'order_id',
                        'order_status_id' => array('is_filter' => true),
                        'notify' => array('is_filter' => true),
                        'comment' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                    ),
                    'order_product' => array(
                        'order_product_id',
                        'order_id',
                        'product_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'model' => array('is_filter' => true),
                        'quantity' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'total' => array('is_filter' => true),
                        'tax' => array('is_filter' => true),
                        'reward' => array('is_filter' => true),
                    ),
                    'order_option' => array(
                        'order_option_id',
                        'order_id',
                        'order_product_id',
                        'product_option_id' => array('is_filter' => true),
                        'product_option_value_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'value',
                        'type',
                    ),
                    'order_recurring' => array(
                        'order_recurring_id',
                        'order_id',
                        'created',
                        'reference',
                        'product_id',
                        'product_name',
                        'product_quantity',
                        'profile_id',
                        'profile_name',
                        'profile_description',
                        'profile_reference',
                        'recurring_id',
                        'recurring_name',
                        'recurring_description',
                        'recurring_frequency',
                        'recurring_cycle',
                        'recurring_duration',
                        'recurring_price',
                        'trial',
                        'trial_frequency',
                        'trial_cycle',
                        'trial_duration',
                        'trial_price',
                        'status',
                        'date_added',
                    ),
                    'order_recurring_transaction' => array(
                        'order_recurring_transaction_id',
                        'order_recurring_id',
                        'reference',
                        'type',
                        'amount',
                        'date_added',
                        'created',
                    ),
                    'order_shipment' => array(
                        'order_shipment_id',
                        'order_id',
                        'date_added',
                        'shipping_courier_id',
                        'tracking_number',
                    ),
                    'order_status' => array(
                        'order_status_id' => array('is_filter' => true),
                        'language_id',
                        'name' => array('is_filter' => true, 'on_condition' => 'maintable.order_status_id = sql_order_status.order_status_id'),
                    ),
                    'order_total' => array(
                        'order_total_id',
                        'order_id',
                        'code' => array('is_filter' => true),
                        'title' => array('is_filter' => true),
                        'value' => array('is_filter' => true),
                        'sort_order',
                    ),
                    'order_voucher' => array(
                        'order_voucher_id',
                        'order_id',
                        'voucher_id',
                        'description',
                        'code',
                        'from_name',
                        'from_email',
                        'to_name',
                        'to_email',
                        'voucher_theme_id',
                        'message',
                        'amount',
                    ),
                ),
                'order_totals' => array(
                    'order_total' => array(
                        'order_total_id',
                        'order_id' => array('is_filter' => true),
                        'code' => array('is_filter' => true),
                        'title' => array('is_filter' => true),
                        'value' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                ),
                'order_products' => array(
                    'order_product' => array(
                        'order_product_id',
                        'order_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'model' => array('is_filter' => true),
                        'quantity' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'total' => array('is_filter' => true),
                        'tax' => array('is_filter' => true),
                        'reward' => array('is_filter' => true),
                    ),
                ),
                'products' => array(
                    'product' => array(
                        'product_id' => array('is_filter' => true),
                        'model' => array('is_filter' => true),
                        'sku' => array('is_filter' => true),
                        'upc' => array('is_filter' => true),
                        'ean' => array('is_filter' => true),
                        'jan' => array('is_filter' => true),
                        'isbn' => array('is_filter' => true),
                        'mpn' => array('is_filter' => true),
                        'location' => array('is_filter' => true),
                        'quantity' => array('is_filter' => true),
                        'stock_status_id' => array('is_filter' => true),
                        'image' => array('is_filter' => true),
                        'manufacturer_id' => array('is_filter' => true),
                        'shipping' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'points' => array('is_filter' => true),
                        'tax_class_id' => array('is_filter' => true),
                        'date_available' => array('is_filter' => true),
                        'weight' => array('is_filter' => true),
                        'weight_class_id' => array('is_filter' => true),
                        'length' => array('is_filter' => true),
                        'width' => array('is_filter' => true),
                        'height' => array('is_filter' => true),
                        'length_class_id' => array('is_filter' => true),
                        'subtract' => array('is_filter' => true),
                        'minimum' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                        'status' => array('is_filter' => true),
                        'viewed' => array('is_filter' => true),
                        'date_added' => array('is_filter' => true),
                        'date_modified' => array('is_filter' => true),
                    ),
                    'product_attribute' => array(
                        'product_id' => array('is_filter' => true),
                        'attribute_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'text' => array('is_filter' => true),
                    ),
                    'product_description' => array(
                        'product_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                        'description' => array('is_filter' => true),
                        'tag' => array('is_filter' => true),
                        'meta_title' => array('is_filter' => true),
                        'meta_h1' => array('is_filter' => true),
                        'meta_description' => array('is_filter' => true),
                        'meta_keyword' => array('is_filter' => true),
                    ),
                    'product_discount' => array(
                        'product_discount_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'quantity' => array('is_filter' => true, 'is_conditional_field' => true),
                        'priority' => array('is_filter' => true, 'is_conditional_field' => true),
                        'price' => array('is_filter' => true),
                        'date_start' => array('is_filter' => true, 'is_conditional_field' => true),
                        'date_end' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                    'product_filter' => array(
                        'product_id' => array('is_filter' => true),
                        'filter_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                    'product_image' => array(
                        'product_image_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'image' => array('is_filter' => true, 'is_conditional_field' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'product_option' => array(
                        'product_option_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'option_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'value' => array('is_filter' => true),
                        'option_value' => array('is_filter' => true),
                        'required' => array('is_filter' => true),
                    ),
                    'product_option_value' => array(
                        'product_option_value_id' => array('is_filter' => true),
                        'product_option_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'option_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'option_value_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'quantity' => array('is_filter' => true),
                        'subtract' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'price_prefix' => array('is_filter' => true),
                        'points' => array('is_filter' => true),
                        'points_prefix' => array('is_filter' => true),
                        'weight' => array('is_filter' => true),
                        'weight_prefix' => array('is_filter' => true),
                    ),
                    'product_profile' => array(
                        'product_id' => array('is_filter' => true),
                        'profile_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true),
                    ),
                    'product_recurring' => array(
                        'product_id' => array('is_filter' => true),
                        'recurring_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true),
                    ),
                    'product_related' => array(
                        'product_id' => array('is_filter' => true),
                        'related_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                    'product_reward' => array(
                        'product_reward_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'points' => array('is_filter' => true),
                    ),
                    'product_special' => array(
                        'product_special_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'priority' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'date_start' => array('is_filter' => true, 'is_conditional_field' => true),
                        'date_end' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                    'product_tag' => array(
                        'product_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true),
                        'tag' => array('is_filter' => true),
                    ),
                    'product_to_category' => array(
                        'product_id' => array('is_filter' => true),
                        'category_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'main_category' => array('is_filter' => true),
                    ),
                    'product_to_download' => array(
                        'product_id' => array('is_filter' => true),
                        'download_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                    'product_to_layout' => array(
                        'product_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'layout_id' => array('is_filter' => true),
                    ),
                    'product_to_store' => array(
                        'product_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                ),
                'product_option_values' => array(
                    'product_option_value' => array(
                        'product_option_value_id' => array('is_filter' => true),
                        'product_option_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'option_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'option_value_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'quantity' => array('is_filter' => true),
                        'subtract' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'price_prefix' => array('is_filter' => true),
                        'points' => array('is_filter' => true),
                        'points_prefix' => array('is_filter' => true),
                        'weight' => array('is_filter' => true),
                        'weight_prefix' => array('is_filter' => true),
                    ),
                ),
                'product_options_combinations' => array(
                    'product_options_combinations' => array(
                        'id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'option_id' => array('is_filter' => true),
                        'options' => array('is_filter' => true, 'is_conditional_field' => true),
                        'images' => array('is_filter' => true),
                        'sku' => array('is_filter' => true),
                        'upc' => array('is_filter' => true),
                        'prices' => array('is_filter' => true),
                        'quantity' => array('is_filter' => true),
                        'subtract' => array('is_filter' => true),
                        'required' => array('is_filter' => true),
                        'weight' => array('is_filter' => true),
                        'weight_prefix' => array('is_filter' => true),
                        'option_type' => array('is_filter' => true),
                        'model' => array('is_filter' => true),
                        'length' => array('is_filter' => true),
                        'width' => array('is_filter' => true),
                        'height' => array('is_filter' => true),
                        'extra_text' => array('is_filter' => true),
                    ),
                ),
                'specials' => array(
                    'product_special' => array(
                        'product_special_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true),
                        'customer_group_id' => array('is_filter' => true),
                        'priority' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'date_start' => array('is_filter' => true),
                        'date_end' => array('is_filter' => true),
                    ),
                ),
                'discounts' => array(
                    'product_discount' => array(
                        'product_discount_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'customer_group_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'quantity' => array('is_filter' => true),
                        'priority' => array('is_filter' => true, 'is_conditional_field' => true),
                        'price' => array('is_filter' => true),
                        'date_start' => array('is_filter' => true),
                        'date_end' => array('is_filter' => true),
                    ),
                ),
                'images' => array(
                    'product_image' => array(
                        'product_image_id' => array('is_filter' => true),
                        'product_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'image' => array('is_filter' => true, 'is_conditional_field' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                ),
                'recurrings' => array(
                    'recurring' => array(
                        'recurring_id',
                        'price',
                        'frequency',
                        'duration',
                        'cycle',
                        'trial_status',
                        'trial_price',
                        'trial_frequency',
                        'trial_duration',
                        'trial_cycle',
                        'status',
                        'sort_order',
                    ),
                    'recurring_description' => array(
                        'recurring_id',
                        'language_id',
                        'name',
                    ),
                ),
                'profiles' => array(
                    'profile' => array(
                        'profile_id',
                        'sort_order',
                        'status',
                        'price',
                        'frequency',
                        'duration',
                        'cycle',
                        'trial_status',
                        'trial_price',
                        'trial_frequency',
                        'trial_duration',
                        'trial_cycle',
                    ),
                    'profile_description' => array (
                        'profile_id',
                        'language_id',
                        'name',
                    ),
                ),
                'returns' => array(
                    'return' => array(
                        'return_id',
                        'order_id',
                        'product_id',
                        'customer_id',
                        'firstname',
                        'lastname',
                        'email',
                        'telephone',
                        'product',
                        'model',
                        'quantity',
                        'opened',
                        'return_reason_id',
                        'return_action_id',
                        'return_status_id',
                        'comment',
                        'date_ordered',
                        'date_added',
                        'date_modified',
                    ),
                    'return_action' => array(
                        'return_action_id',
                        'language_id',
                        'name',
                    ),
                    'return_history' => array(
                        'return_history_id',
                        'return_id',
                        'return_status_id',
                        'notify',
                        'comment',
                        'date_added',
                    ),
                    'return_reason' => array(
                        'return_reason_id',
                        'language_id',
                        'name',
                    ),
                    'return_status' => array(
                        'return_status_id',
                        'language_id',
                        'name',
                    ),
                ),
                'reviews' => array(
                    'review' => array(
                        'review_id',
                        'product_id',
                        'customer_id',
                        'author',
                        'text',
                        'rating',
                        'status',
                        'date_added',
                        'date_modified',
                    ),
                ),
                'seo_urls' => array(
                    'url_alias' => array(
                        'url_alias_id',
                        'query' => array('is_conditional_field' => true, 'remove_main_conditional' => true),
                        'keyword',
                    ),
                    'seo_url' => array(
                        'seo_url_id',
                        'store_id' => array('is_conditional_field' => true),
                        'language_id' => array('is_conditional_field' => true),
                        'query' => array('is_conditional_field' => true, 'remove_main_conditional' => true),
                        'keyword',
                    ),
                ),
                'statistics' => array(
                    'statistics' => array(
                        'statistics_id',
                        'code',
                        'value',
                    ),
                ),
                'stock_statuses' => array(
                    'stock_status' => array(
                        'stock_status_id',
                        'language_id',
                        'name',
                    ),
                ),
                'stores' => array(
                    'store' => array(
                        'store_id',
                        'name',
                        'url',
                        'ssl',
                    ),
                ),
                'taxes' => array(
                    'tax_class' => array(
                        'tax_class_id',
                        'title',
                        'description',
                        'date_added',
                        'date_modified',
                    ),
                    'tax_rate' => array(
                        'tax_rate_id',
                        'geo_zone_id',
                        'name',
                        'rate',
                        'type',
                        'date_added',
                        'date_modified',
                    ),
                    'tax_rate_to_customer_group' => array(
                        'tax_rate_id',
                        'customer_group_id',
                    ),
                    'tax_rule' => array(
                        'tax_rule_id',
                        'tax_class_id',
                        'tax_rate_id',
                        'based',
                        'priority',
                    ),
                ),
                'uploads' => array(
                    'upload' => array(
                        'upload_id',
                        'name',
                        'filename',
                        'code',
                        'date_added',
                    ),
                ),
                /*'users' => array(
                    'user' => array(
                        'user_id',
                        'user_group_id',
                        'username',
                        'password',
                        'salt',
                        'firstname',
                        'lastname',
                        'email',
                        'image',
                        'code',
                        'ip',
                        'status',
                        'date_added',
                    ),
                    'user_group' => array(
                        'user_group_id',
                        'name',
                        'permission',
                    ),
                ),*/
                'vouchers' => array(
                    'voucher' => array(
                        'voucher_id',
                        'order_id',
                        'code',
                        'from_name',
                        'from_email',
                        'to_name',
                        'to_email',
                        'voucher_theme_id',
                        'message',
                        'amount',
                        'status',
                        'date_added',
                    ),
                    'voucher_history' => array(
                        'voucher_history_id',
                        'voucher_id',
                        'order_id',
                        'amount',
                        'date_added',
                    ),
                    'voucher_theme' => array(
                        'voucher_theme_id',
                        'image',
                    ),
                    'voucher_theme_description' => array(
                        'voucher_theme_id',
                        'language_id',
                        'name',
                    ),
                ),
                'weights' => array(
                    'weight_class' => array(
                        'weight_class_id',
                        'value',
                    ),
                    'weight_class_description' => array(
                        'weight_class_id',
                        'language_id',
                        'title',
                        'unit',
                    ),
                ),
                'zones' => array(
                    'zone' => array(
                        'zone_id',
                        'country_id',
                        'name',
                        'code',
                        'status',
                    ),
                    'zone_to_geo_zone' => array(
                        'zone_to_geo_zone_id',
                        'country_id',
                        'zone_id',
                        'geo_zone_id',
                        'date_added',
                        'date_modified',
                    ),
                ),
                'orders_product_data' => array(
                    'order_product' => array(
                        'order_product_id' => array('is_conditional_field' => true),
                        'order_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'model' => array('is_filter' => true),
                        'quantity' => array('is_filter' => true),
                        'price' => array('is_filter' => true)
                    )
                ),
                'order_products_custom_format' => array(
                    'order_product' => array(
                        'order_product_id',
                        'order_id' => array('is_filter' => true),
                        'name' => array('is_filter' => true),
                        'model' => array('is_filter' => true),
                        'quantity' => array('is_filter' => true),
                        'price' => array('is_filter' => true),
                        'disp_magazzino' => array('is_filter' => true),
                    )
                ),
            );

            if(!$this->hasOptionsCombinations)
                unset($database['product_options_combinations']);


            if(is_file($this->assets_path.'modify_database_fields.php')){
                require($this->assets_path.'modify_database_fields.php');
            }

            foreach ($this->ie_categories as $cat_name => $name) {
                if(!empty($cat_name)) {
                    $this->load->model('extension/module/ie_pro_'.$cat_name);
                    if(property_exists($this->{'model_extension_module_ie_pro_'.$cat_name}, 'get_database_fields')) {
                        $database[$cat_name] = $this->{'model_extension_module_ie_pro_'.$cat_name}->get_database_fields();
                    }
                }
            }

            if($this->is_ocstore) {
                $database['manufacturers'] = array(
                    'manufacturer' => array(
                        'manufacturer_id',
                        'name' => array('is_filter' => true),
                        'image' => array('is_filter' => true),
                        'sort_order' => array('is_filter' => true),
                    ),
                    'manufacturer_description' => array(
                        'manufacturer_id' => array('is_filter' => true),
                        'language_id' => array('is_filter' => true, 'is_conditional_field' => true),
                        'name' => array('is_filter' => true),
                        'description' => array('is_filter' => true),
                        'meta_title' => array('is_filter' => true),
                        'meta_h1' => array('is_filter' => true),
                        'meta_description' => array('is_filter' => true),
                        'meta_keyword' => array('is_filter' => true),
                    ),
                    'manufacturer_to_store' => array(
                        'manufacturer_id' => array('is_filter' => true),
                        'store_id' => array('is_filter' => true, 'is_conditional_field' => true),
                    ),
                );

                if(!$this->manufacturer_multilanguage)
                    unset($database['manufacturers']['manufacturer_description']['name']);
            }

            if(!$this->hasFilters) {
                unset($database['categories']['category_filter']);
                unset($database['filters']);
                unset($database['filter_groups']);
            }

            if(!$this->hasCustomerGroupDescriptions) {
                unset($database['customer_groups']['customer_group_description']);
                unset($database['customer_groups']['customer_group']['approval']);
                unset($database['customer_groups']['customer_group']['company_id_display']);
                unset($database['customer_groups']['customer_group']['company_id_required']);
                unset($database['customer_groups']['customer_group']['tax_id_display']);
                unset($database['customer_groups']['customer_group']['tax_id_required']);
                unset($database['customer_groups']['customer_group']['sort_order']);
            }
            else
                unset($database['customer_groups']['customer_group']['name']);


            if(version_compare(VERSION, '1.5.3.1', '<=')) {
                unset($database['products']['product']['ean']);
                unset($database['products']['product']['jan']);
                unset($database['products']['product']['mpn']);
                unset($database['products']['product']['isbn']);
                unset($database['products']['product_description']['tag']);
                unset($database['customers']['customer']['salt']);
                unset($database['orders']['order']['marketing_id']);
                unset($database['orders']['order']['tracking']);
            }
            if(version_compare(VERSION, '1.5.6.4', '<')) {
                unset($database['categories']['category_path']);
            }

            if(version_compare(VERSION, '2.3', '<')) {
                unset($database['customers']['customer']['language_id']);
                unset($database['customers']['customer']['code']);
            }

            if(version_compare(VERSION, '2', '<')) {
                unset($database['customers']['customer']['custom_field']);
                unset($database['customers']['customer']['safe']);
                unset($database['customers']['customer']['code']);
            }

            //Add here custom database fields
            if(is_file($this->assets_path.'add_columns_to_database_backup.php'))
                require($this->assets_path.'add_columns_to_database_backup.php');

            //Add empty array to all fields
            $temp_database = array();
            foreach ($database as $main_category => $tables) {
                foreach ($tables as $table_name => $fields) {
                    foreach ($fields as $key => $field_name) {
                        if(!is_array($field_name))
                            $temp_database[$main_category][$table_name][$field_name] = array();
                        else
                            $temp_database[$main_category][$table_name][$key] = $field_name;
                    }

                }

            }
            $database = $temp_database;

            if(!$this->is_ocstore) {
                unset($database['categories']['category_description']['meta_h1']);
                unset($database['products']['product_description']['meta_h1']);
            }

            if(!$this->main_category)
                unset($database['products']['product_to_category']['main_category']);

            if($this->has_custom_fields)
                $database = $this->model_extension_module_ie_pro_tab_custom_fields->add_custom_fields_to_database($database);

            return $database;
        }

        public function get_database_field_types() {
            $database = $this->get_database();
            $final_database_field_types = array();

            foreach ($database as $group => $table_fields) {
                foreach ($table_fields as $table_name => $fields) {
                    $final_database_field_types[$table_name] = $fields;
                }
            }

            return $final_database_field_types;
        }

        public function get_database_categories() {
            $database = $this->get_database_fields();
            $categories = array();

            foreach ($database as $category => $tables_fields) {
                $categories[$category] = $this->get_legible_database_field_name($category);
            }

            return $categories;
        }

        public function get_tables_conditional_fields() {
            $conditional_fields = array();
            $database = $this->get_database_fields();
            foreach ($database as $group_name => $tables_fields) {
                foreach ($tables_fields as $table_name => $fields) {
                    foreach ($fields as $field_name => $field) {
                        if(is_array($field) && array_key_exists('is_conditional_field', $field)) {
                            if(!array_key_exists($table_name, $conditional_fields))
                                $conditional_fields[$table_name] = array();
                            $conditional_fields[$table_name][] = $field_name;

                            $conditional_fields[$table_name] = array_unique($conditional_fields[$table_name]);
                        }
                    }

                }
            }
            return $conditional_fields;
        }

        public function get_tables_fields_main_conditional_remove() {
            $conditional_remove_fields = array();
            $database = $this->get_database_fields();
            foreach ($database as $group_name => $tables_fields) {
                foreach ($tables_fields as $table_name => $fields) {
                    foreach ($fields as $field_name => $field) {
                        if(is_array($field) && array_key_exists('remove_main_conditional', $field)) {
                            if(!array_key_exists($table_name, $conditional_remove_fields))
                                $conditional_remove_fields[$table_name] = array();
                            $conditional_remove_fields[$table_name][$field_name] = true;
                        }
                    }

                }

            }
            return $conditional_remove_fields;
        }

        public function get_database_data($categories) {

            $filter_ids = array_key_exists('element_ids', $this->request->post) && !empty($this->request->post['element_ids']) ? $this->request->post['element_ids'] : false;

            $database = $this->get_database($categories);

            $final_data = array();

            foreach ($database as $group => $tables_fields) {
                foreach ($tables_fields as $table_name => $fields) {
                    $sql = 'SELECT ';
                    foreach ($fields as $field_name => $field_data) {
                        $sql .= $this->escape_database_field($field_name).', ';
                    }
                    $sql = rtrim($sql, ', ');
                    $sql .= ' FROM '.$this->escape_database_table_name($table_name);

                    if(!empty($filter_ids) && array_key_exists($this->main_field, $this->database_schema[$table_name]))
                        $sql .= ' WHERE ' . $this->main_field . ' IN (' . implode(', ', $filter_ids) . ');';

                    $result = $this->db->query($sql);

                    if($result->num_rows) {
                        $data = array();
                        $columns = array();
                        foreach ($result->row as $column_name => $key) {
                            $columns[] = $column_name;
                        }

                        $data = array();
                        foreach ($result->rows as $key => $row) {
                            $data[] = $row;
                        }

                        $temp = array(
                            'columns' => $columns,
                            'data' => $data
                        );
                        $final_data[$table_name] = $temp;
                    }

                }
            }
            return $final_data;
        }
    }
?>
