<?php
class ControllerExtensionModuleImportXls extends Controller
{
    private $error = array();

    private $data_to_view = array();

    private static $OPENCART_TABLES_WHITELIST = [
        // comunes/solo para v1.x
        'address',
        'affiliate',
        'affiliate_transaction',
        'attribute',
        'attribute_description',
        'attribute_group',
        'attribute_group_description',
        'banner',
        'banner_image',
        'banner_image_description',
        'category',
        'category_description',
        'category_filter',
        'category_path',
        'category_to_layout',
        'category_to_store',
        'country',
        'coupon',
        'coupon_category',
        'coupon_history',
        'coupon_product',
        'currency',
        'custom_field',
        'custom_field_description',
        'custom_field_to_customer_group',
        'custom_field_value',
        'custom_field_value_description',
        'customer',
        'customer_ban_ip',
        'customer_field',
        'customer_group',
        'customer_group_description',
        'customer_history',
        'customer_ip',
        'customer_online',
        'customer_reward',
        'customer_transaction',
        'download',
        'download_description',
        'extension',
        'filter',
        'filter_description',
        'filter_group',
        'filter_group_description',
        'geo_zone',
        'information',
        'information_description',
        'information_to_layout',
        'information_to_store',
        'language',
        'layout',
        'layout_route',
        'length_class',
        'length_class_description',
        'manufacturer',
        'manufacturer_to_store',
        'option',
        'option_description',
        'option_value',
        'option_value_description',
        'order',
        'order_download',
        'order_field',
        'order_fraud',
        'order_history',
        'order_option',
        'order_product',
        'order_recurring',
        'order_recurring_transaction',
        'order_status',
        'order_total',
        'order_voucher',
        'product',
        'product_attribute',
        'product_description',
        'product_discount',
        'product_filter',
        'product_image',
        'product_option',
        'product_option_value',
        'product_profile',
        'product_recurring',
        'product_related',
        'product_reward',
        'product_special',
        'product_to_category',
        'product_to_download',
        'product_to_layout',
        'product_to_store',
        'profile',
        'profile_description',
        'return',
        'return_action',
        'return_history',
        'return_reason',
        'return_status',
        'review',
        'setting',
        'stock_status',
        'store',
        'tax_class',
        'tax_rate',
        'tax_rate_to_customer_group',
        'tax_rule',
        'url_alias',
        'user',
        'user_group',
        'voucher',
        'voucher_history',
        'voucher_theme',
        'voucher_theme_description',
        'weight_class',
        'weight_class_description',
        'zone',
        'zone_to_geo_zone',

        // v2.x/v3.x
        'affiliate_activity',
        'affiliate_login',
        'api',
        'api_ip',
        'api_session',
        'cart',
        'city',
        'custom_field_customer_group',
        'customer_activity',
        'customer_login',
        'customer_search',
        'customer_wishlist',
        'event',
        'layout_module',
        'location',
        'marketing',
        'menu',
        'menu_description',
        'menu_module',
        'modification',
        'module',
        'order_custom_field',
        'recurring',
        'recurring_description',
        'theme',
        'translation',
        'upload',

        // solo v3.x
        'customer_affiliate',
        'customer_approval',
        'extension_install',
        'extension_path',
        'googleshopping_category',
        'googleshopping_product',
        'googleshopping_product_status',
        'googleshopping_product_target',
        'googleshopping_target',
        'order_shipment',
        'seo_url',
        'session',
        'shipping_courier',
        'statistics'
    ];

    public function __construct($registry)
    {
        //Call to parent __construct
            parent::__construct($registry);

        if(defined('IE_PRO_CRON')) {
            ob_start();
            $this->is_cron_task = true;
            $this->request->get['route'] = '';
            $this->request->get['ajax_function'] = 'launch_profile';
            $this->request->post['profile_id'] = PROFILE_ID;
        }

        //Check server requirements
            $this->_server_configuration();

        $this->_get_module_data();
        $this->_get_form_basic_data();

        if ($this->request->get['route'] == $this->real_extension_type.'/'.$this->extension_name)
            $this->form_array = $this->_construct_view_form();

        $this->setupClassLoader();
    }

    public function index(){
        $this->_check_ajax_function();
        $this->document->setTitle($this->language->get('heading_title_2'));
        $this->_get_breadcrumbs();
        $this->_check_post_data();

        //Send token to view
            $this->data_to_view['token'] = $this->session->data[$this->token_name];
            $this->data_to_view['action'] = $this->url->link($this->real_extension_type.'/'.$this->extension_name, $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL');
            $this->data_to_view['cancel'] = $this->url->link(version_compare(VERSION, '2.0.0.0', '>=') ? $this->extension_url_cancel_oc_2x : $this->extension_url_cancel_oc_15x, $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL');

        $this->_load_basic_languages();
        $form = $this->model_extension_devmanextensions_tools->_get_form_in_settings();

        if(count($this->profiles_select) == 1 && !empty($form))
            $this->session->data['info'] = sprintf($this->language->get('profile_start_to_work'), '<a href="javascript:{}" onclick="$(\'a.tab_export---import, a.tab_Экспорт---Импорт\').click()">' , '</a>');

        $this->_check_errors_to_send();
        $this->data_to_view['form'] =  !empty($form) ? $form : '';
        if(empty($this->data_to_view['form'])) {
            $this->data_to_view['text_license_info'] = $this->language->get('text_license_info');
        }
        $this->_send_custom_variables_to_view();

        if(version_compare(VERSION, '2.0.0.0', '>='))
        {
            $data = $this->data_to_view;
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view($this->real_extension_type.'/'.$this->extension_view, $data));
        }
        else
        {
            $document_scripts = $this->document->getScripts();
            $scripts = array();
            foreach ($document_scripts as $key => $script)
                $scripts[] = $script;
            $this->data_to_view['scripts'] = $scripts;

            $document_styles = $this->document->getStyles();
            $styles = array();
            foreach ($document_styles as $key => $style)
                $styles[] = $style;
            $this->data_to_view['styles'] = $styles;

            $this->data = $this->data_to_view;

            if(version_compare(VERSION, '1.5.5.1', '<=')) {
                $this->data['styles'] = $this->document->getStyles();
                $this->data['scripts'] = $this->document->getScripts();
            }

            $this->template = $this->real_extension_type.'/'.$this->extension_view;

            $this->response->setOutput($this->render());
        }
    }

    public function _server_configuration() {
        if (strpos(ini_get('default_charset'), ';') !== false) {
           ini_set('default_charset', 'UTF-8');
        }

        /*ini_set('max_input_vars', 50000);

        $memory_limit = $this->config->get('import_xls_memory_limit') ? $this->config->get('import_xls_memory_limit') : 1024;
        ini_set("memory_limit", $memory_limit.'M');

        $max_execution_time = $this->config->get('import_xls_max_execution_time') ? $this->config->get('import_xls_max_execution_time') : 3600;
        ini_set("max_execution_time",$max_execution_time);*/

        if (strpos(ini_get('default_charset'), ';') !== false) {
           ini_set('default_charset', 'UTF-8');
        }

        ini_set("memory_limit","8096M");
        ini_set("max_execution_time",600000000);

        if(function_exists("error_reporting"))
            error_reporting(E_ALL);

        ini_set('display_errors', 1);

        if(phpversion() < '5.5') {
            die('ERROR: YOUR PHP VERSION IS <b>'.phpversion().'</b> REQUIRED <b>5.5.0 or higher</b>');
        }

        if( !extension_loaded('zip')) {
             die('ERROR: <b>php_zip</b> EXTENSION NEEDS BE ENABLED. IF YOU DON\'T KNOW HOW TO DO IT, YOUR HOSTING SUPPORT TEAM WILL CAN DO IT FOR YOU.');
        }
    }

    function _get_module_data() {
        $this->is_mijoshop = class_exists('MijoShop');

        if($this->is_mijoshop) {
            $app = JFactory::getApplication();
            //Joomla >= 3.2
            $prefix = $app->get('dbprefix');

            //Joomla < 3.2
            //$prefix = $app->getCfg('dbprefix');

            $this->db_prefix = $prefix . 'mijoshop_';
        }
        else
            $this->db_prefix = DB_PREFIX;

        $this->is_joocart = defined('JOOCART_COMPONENT_URL');
        $this->is_jcart = defined('JCART_SITE_URL');

        $this->extension_type = 'module';
        $this->real_extension_type = (version_compare(VERSION, '2.3', '>=') ? 'extension/':'').$this->extension_type;

        $this->extension_url_cancel_oc_15x = 'common/home';
        $this->extension_url_cancel_oc_2x = 'common/dashboard';

        $this->extension_name = 'import_xls';
        $this->extension_group_config = 'import_xls';
        $this->extension_id = '542068d4-ed24-47e4-8165-0994fa641b0a';

        $this->oc_version = version_compare(VERSION, '3.0.0.0', '>=') ? 3 : (version_compare(VERSION, '2.0.0.0', '>=') ? 2 : 1);
        $this->is_oc_3x = $this->oc_version >= 3;

        $main_category = $this->db->query("SHOW COLUMNS FROM `".DB_PREFIX."product_to_category` LIKE 'main_category'");
        $this->main_category = $main_category->num_rows;

        $this->is_ocstore = is_dir(DIR_APPLICATION . 'controller/octeam_tools') || is_dir(DIR_APPLICATION . 'controller/howto') || defined('OPENCARTFORUM_SERVER') || is_dir(DIR_APPLICATION . 'controller/neoseo_blog');

        //In OCStore 3.x, manufacturer hasn't "name" field multilanguage.
        $this->manufacturer_multilanguage = $this->is_ocstore && !$this->is_oc_3x && (version_compare(VERSION, '2.2', '>') || is_dir(DIR_APPLICATION . 'controller/neoseo_blog'));

        $this->data_to_view = array(
            'button_apply_allowed' => false,
            'button_save_allowed' => false,
            'extension_name' => $this->extension_name,
            'license_id' => $this->config->get($this->extension_group_config.'_license_id') ? $this->config->get($this->extension_group_config.'_license_id') : '',
            'oc_version' => $this->oc_version
        );

        $this->license_id = $this->config->get($this->extension_group_config.'_license_id') ? $this->config->get($this->extension_group_config.'_license_id') : '';
        $this->form_file_path = str_replace('system/', '', DIR_SYSTEM).$this->extension_name.'_form.txt';
        $this->form_file_url = HTTP_CATALOG.$this->extension_name.'_form.txt';

        $this->token_name = version_compare(VERSION, '3.0.0.0', '<') ? 'token' : 'user_token';
        if($this->is_cron_task)
            $this->session->data[$this->token_name] = '';

        $this->token = $this->session->data[$this->token_name];
        $this->extension_view = version_compare(VERSION, '3.0.0.0', '<') ? $this->extension_name.'.tpl' : $this->extension_name;

        $this->load->language($this->real_extension_type.'/'.$this->extension_name);
        $this->load->language($this->real_extension_type.'/ie_pro_general');

        $this->assets_path = DIR_SYSTEM.'assets/ie_pro_includes/';

        $this->custom_format_seo_url = is_file($this->assets_path.'model_ie_pro_function_format_seo_url.php');

        //<editor-fold desc="Get customer groups">
            if(version_compare(VERSION, '2.0.3.1', '<='))
            {
                $this->load->model('sale/customer_group');
                $this->customer_groups = $this->model_sale_customer_group->getCustomerGroups();
            }
            else
            {
                $this->load->model('customer/customer_group');
                $this->customer_groups = $this->model_customer_customer_group->getCustomerGroups();
            }
        //</editor-fold>
        $this->load->model('extension/devmanextensions/tools');
        $this->load->model('extension/module/ie_pro');
        $this->load->model('extension/module/ie_pro_tab_export_import');
        $this->load->model('extension/module/ie_pro_tab_migrations');

        $this->load->model('extension/module/ie_pro_profile');

        if(is_file($this->assets_path.'controller_ie_pro_add_new_global_models.php'))
            require($this->assets_path.'controller_ie_pro_add_new_global_models.php');

        $this->model_extension_module_ie_pro_profile->_check_profiles_table();
        $this->model_profile = 'model_extension_module_ie_pro_profile';

        //$this->has_cron = file_exists('model/extension/module/ie_pro_tab_crons.php');
        $this->has_cron = true;
        if($this->has_cron)
            $this->load->model('extension/module/ie_pro_tab_crons');

        $this->has_custom_fields = file_exists('model/extension/module/ie_pro_tab_custom_fields.php') || (defined('IE_PRO_CRON_HAS_CUSTOM_FIELDS') && IE_PRO_CRON_HAS_CUSTOM_FIELDS);
        if($this->has_custom_fields)
            $this->load->model('extension/module/ie_pro_tab_custom_fields');

        $this->load->model('extension/module/ie_pro_tab_profiles');
        $this->model_extension_module_ie_pro_tab_profiles->load_generic_data();

        $this->hasOptionsCombinations = $this->model_extension_module_ie_pro->IsOptionsCombinationsInstalled();
        //<editor-fold desc="Count languages active">
            $this->load->model('localisation/language');
            $languages = $this->model_localisation_language->getLanguages();
            $this->count_languages_real = count($languages);

            $languages_ids = array();
            foreach ($languages as $key => $value) {
                $code_formatted = $this->model_extension_module_ie_pro->format_column_name($value['code']);
                $languages[$key]['code'] = $code_formatted;
                $languages_ids[$value['language_id']] = $code_formatted;
            }

            $this->languages = $languages;
            $this->languages_ids = $languages_ids;
            $this->count_languages = 0;

            foreach ($this->languages as $key => $lang) {
                if($lang['status'])
                    $this->count_languages++;
            }

            $this->default_language_code = $this->config->get('config_admin_language');
            $language = $this->db->query('SELECT `language_id` FROM `'.$this->db_prefix.'language` WHERE `code` = "'.$this->default_language_code.'"');
            $this->default_language_id = array_key_exists('language_id', $language->row) ? $language->row['language_id'] : 1;
        //</editor-fold>

        $this->api_url = defined('DEVMAN_SERVER_TEST') ? DEVMAN_SERVER_TEST : 'https://devmanextensions.com/';
        $this->libraries_url = $this->api_url.'opencart_admin/ext_ie_pro/libraries.zip';
        $this->isdemo =  strpos($_SERVER['HTTP_HOST'], 'devmanextensions.com') !== false;

        $this->root_path = substr(DIR_APPLICATION, 0, strrpos(DIR_APPLICATION, '/', -2)).'/';
        $this->path_progress = $this->root_path.'ie_pro/';
        $this->path_progress_file = $this->path_progress.'progress'.($this->is_cron_task ? '_cron':'').'.iepro';
        $this->path_progress_cancelled_file = $this->path_progress.'progress_cancelled.iepro';
        $this->path_cache_public = HTTPS_CATALOG.($this->is_mijoshop ? 'components/com_mijoshop/opencart/':($this->is_joocart ? 'components/com_opencart/' : ($this->is_jcart ? 'components/com_jcart/' : ''))).'ie_pro/';
        $this->path_progress_public = $this->path_cache_public.'progress.iepro';
        $this->path_tmp = $this->path_progress.'tmp/';
        $this->path_tmp_public = $this->path_cache_public.'tmp/';
        $this->google_spreadsheet_json_file_path = $this->path_progress.'user_gdrive.json';

        $this->load->language($this->real_extension_type.'/ie_pro_tab_profiles');

        $ie_categories = array(
            '' => $this->language->get('select_empty'),
            'products' => $this->language->get('profile_i_want_products'),
            'specials' => $this->language->get('profile_i_want_specials'),
            'discounts' => $this->language->get('profile_i_want_discounts'),
            'images' => $this->language->get('profile_i_want_images'),
            'product_option_values' => $this->language->get('profile_i_want_product_option_values'),
            'categories' => $this->language->get('profile_i_want_categories'),
            'attribute_groups' => $this->language->get('profile_i_want_attribute_groups'),
            'attributes' => $this->language->get('profile_i_want_attributes'),
            'options' => $this->language->get('profile_i_want_options'),
            'option_values' => $this->language->get('profile_i_want_option_values'),
            'manufacturers' => $this->language->get('profile_i_want_manufacturers'),
            'filter_groups' => $this->language->get('profile_i_want_filter_groups'),
            'filters' => $this->language->get('profile_i_want_filters'),
            'customer_groups' => $this->language->get('profile_i_want_customer_groups'),
            'customers' => $this->language->get('profile_i_want_customers'),
            'addresses' => $this->language->get('profile_i_want_addresses'),
            'orders' => $this->language->get('profile_i_want_orders'),
            'order_products' => $this->language->get('profile_i_want_order_products'),
            'order_totals' => $this->language->get('profile_i_want_order_totals'),
            'coupons' => $this->language->get('profile_i_want_coupons'),
            'orders_product_data' => $this->language->get('profile_i_want_orders_product_data'),
        );

        if(is_file($this->assets_path.'add_custom_category.php')){
            require($this->assets_path.'add_custom_category.php');
        }

        $this->hasFilters = version_compare(VERSION, '1.5.5', '>');

        if(!$this->hasFilters) {
            unset($ie_categories['filter_groups']);
            unset($ie_categories['filters']);
        }

        if(is_file($this->assets_path.'controller_ie_pro_add_new_ie_pro_categories.php'))
            require($this->assets_path.'controller_ie_pro_add_new_ie_pro_categories.php');

        $this->ie_categories = $ie_categories;

        $this->layouts = $this->model_extension_module_ie_pro->get_layouts();
        $this->tax_classes = $this->model_extension_module_ie_pro->get_tax_classes();
        $this->stock_statuses = $this->model_extension_module_ie_pro->get_stock_statuses();
        $this->stores_import_format = $this->model_extension_module_ie_pro->get_stores_import_format();
        $this->stores_count = count($this->stores_import_format);

        $this->hasCustomerGroupDescriptions = version_compare(VERSION, '1.5.1.3', '>');
        $this->length_classes = $this->model_extension_module_ie_pro->get_classes_length();
        $this->weight_classes = $this->model_extension_module_ie_pro->get_classes_weight();
        $this->table_seo = $this->is_oc_3x ? 'seo_url' : 'url_alias';
        $this->load->model('extension/module/ie_pro_database');
        $this->database_field_types = $this->model_extension_module_ie_pro_database->get_database_field_types();
        $this->database_schema = $this->model_extension_module_ie_pro_database->get_database_without_groups();
        $this->manufacturer_name_in_table_manufacturer = array_key_exists('name', $this->database_schema['manufacturer']);
        $this->manufacturer_name_in_table_manufacturer_description = array_key_exists('manufacturer_description', $this->database_schema) && array_key_exists('name', $this->database_schema['manufacturer_description']);
        $this->product_option_value = array_key_exists('option_value', $this->database_schema['product_option']) ? 'option_value' : 'value';
        $this->is_t = strpos($this->license_id, 'trial-') !== false;
        $this->data_to_view['link_trial'] = ''; //sprintf($this->language->get('link_trial'), $this->extension_id, HTTPS_CATALOG);
        $this->is_t_elem = ord(2);

        $option_types_with_values = array(
            'select',
            'radio',
            'checkbox',
        );
        $this->tables_with_images = array(
            'product',
            'product_image',
            'category',
            'manufacturer',
            'option_value',
            'product_option_value',
        );
        $this->conditional_value_conditions = array('>=', '<=', '>', '<',  '!=', '==', '!*', '*', '~=', 'like');

        $this->option_types_with_values = $option_types_with_values;
        $this->image_path = version_compare(VERSION, '2', '<') ? 'data/' : 'catalog/';
    }

    function _get_form_basic_data() {
        $this->use_session_form = !$this->is_oc_3x;
        $this->form_token_name = 'devmanextensions_form_token_'.$this->extension_group_config;
        $this->form_session_name = 'devmanextensions_form_'.$this->extension_group_config;

        //Is the first time that configure extension?
            $this->setting_group_code = version_compare(VERSION, '2.0.1.0', '>=') ? 'code' : '`group`';
            $results = $this->db->query('SELECT setting_id FROM '. $this->db_prefix . 'setting WHERE '.$this->setting_group_code.' = "'.$this->extension_group_config.'" AND `key` NOT LIKE "%license_id%" LIMIT 1');
            $this->first_configuration = empty($results->row['setting_id']);
        //END

        $this->load->model('extension/devmanextensions/tools');

        //Devman Extensons - info@devmanextensions.com - 2016-10-09 19:39:52 - Load languages
            $this->load->model('localisation/language');
            $languages = $this->model_localisation_language->getLanguages();
            $this->langs = $this->model_extension_devmanextensions_tools->formatLanguages($languages);
        //END

        //Devman Extensions - info@devmanextensions.com - 2017-08-29 19:25:03 - Get customer groups
            $customer_groups = $this->model_extension_devmanextensions_tools->getCustomerGroups();
            $this->cg = $customer_groups;
        //END

        $this->oc_2 = version_compare(VERSION, '2.0.0.0', '>=');
        $this->oc_3 = version_compare(VERSION, '3.0.0.0', '>=');

        $form_basic_datas = array(
            'is_ocstore' => $this->is_ocstore,
            'tab_changelog' => true,
            'tab_help' => true,
            'tab_faq' => true,
            'retina_icons' => true,
            'extension_id' => $this->extension_id,
            'first_configuration' => $this->first_configuration,
            'positions' => $this->positions,
            'statuses' => $this->statuses,
            'stores' => $this->stores,
            'layouts' => $this->layouts,
            'languages' => $this->langs,
            'oc_version' => $this->oc_version,
            'oc_2' => $this->oc_2,
            'oc_3' => $this->oc_3,
            'customer_groups' => $this->cg,
            'version' => VERSION,
            'extension_version' => $this->language->get('extension_version'),
            'token' => $this->token,
            'extension_group_config' => $this->extension_group_config,
            'no_image_thumb' => $this->no_image_thumb,
            'lang' => array(
                'choose_store' => $this->language->get('choose_store'),
                'text_browse' => $this->language->get('text_browse'),
                'text_clear' => $this->language->get('text_clear'),
                'text_sort_order' => $this->language->get('text_sort_order'),
                'text_clone_row' => $this->language->get('text_clone_row'),
                'text_remove' => $this->language->get('text_remove'),
                'text_add_module' => $this->language->get('text_add_module'),
                'tab_help' => $this->language->get('tab_help'),
                'tab_changelog' => $this->language->get('tab_changelog'),
                'tab_faq' => $this->language->get('tab_faq'),
            ),
        );

        $this->form_basic_datas = $form_basic_datas;
    }

    public function _check_post_data() {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') &&
             $this->extension_module_iepro !== null &&
             $this->extension_module_iepro->validate_permiss()) {
            $this->session->data['error'] = '';

            //Devman Extensions - info@devmanextensions.com - 2016-10-21 18:57:30 - Custom functions
                if(
                    !empty($this->request->post['force_function']) || !empty($this->request->get['force_function'])
                    ||
                    !empty($this->request->post[$this->extension_group_config.'_force_function']) || !empty($this->request->get[$this->extension_group_config.'force_function'])
                )
                {
                    if(!empty($this->request->post['force_function']) || !empty($this->request->get['force_function']))
                        $index = 'force_function';
                    else
                        $index = $this->extension_group_config.'_force_function';

                    $post_get = !empty($this->request->post[$index]) ? 'post' : 'get';
                    $this->{$this->request->{$post_get}[$index]}();
                }
            //END

            //OC Versions compatibility
            $this->_redirect($this->real_extension_type.'/'.$this->extension_name);
        }
    }

    public function _check_ajax_function() {
        if(
            !empty($this->request->post['ajax_function']) || !empty($this->request->get['ajax_function'])
            ||
            !empty($this->request->post[$this->extension_group_config.'_ajax_function']) || !empty($this->request->get[$this->extension_group_config.'ajax_function'])
        )
        {
            if(!empty($this->request->post['ajax_function']) || !empty($this->request->get['ajax_function']))
                $index = 'ajax_function';
            else
                $index = $this->extension_group_config.'_force_function';

            $post_get = !empty($this->request->post[$index]) ? 'post' : 'get';
            $function_name = $this->request->{$post_get}[$index];

            if($function_name == 'profile_load') {
                $this->{$this->model_profile}->load();
            } else if($function_name == 'cancel_process' && !empty($this->request->post['error'])) {
                $this->model_extension_module_ie_pro->cancel_process($this->request->post['error']);
                exit( -1);
            }

            $this->model_extension_module_ie_pro_tab_profiles->_check_ajax_function($function_name);
            $this->model_extension_module_ie_pro_tab_export_import->_check_ajax_function($function_name);
            $this->model_extension_module_ie_pro_tab_migrations->_check_ajax_function($function_name);
            if($this->has_cron)
                $this->model_extension_module_ie_pro_tab_crons->_check_ajax_function($function_name);
            if($this->has_custom_fields)
                $this->model_extension_module_ie_pro_tab_custom_fields->_check_ajax_function($function_name);

            if (method_exists('ControllerExtensionModuleImportXls', $function_name))
                $this->{$function_name}();
            else {
                echo json_encode(array('error' => true, 'message' => 'Method ' . $function_name . ' doesn\'t exists'));
                die;
            }
        }
    }

    public function _get_breadcrumbs() {
        $this->data_to_view['breadcrumbs'] = array();
        $this->data_to_view['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'),
            'separator' => false
        );

        $this->data_to_view['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title_2'),
            'href'      => $this->url->link($this->real_extension_type.'/'.$this->extension_name, $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'),
            'separator' => ' :: '
        );
    }

    public function _add_css_js_to_document() {
        //Add scripts and css
            if(version_compare(VERSION, '2.0.0.0', '<'))
            {
                $this->document->addScript($this->api_url.'/opencart_admin/common/js/jquery-2.1.1.min.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addScript($this->api_url.'/opencart_admin/common/js/bootstrap.min.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addStyle($this->api_url.'/opencart_admin/common/css/bootstrap.min.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());

                $this->document->addScript($this->api_url.'/opencart_admin/common/js/datetimepicker/moment.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addScript($this->api_url.'/opencart_admin/common/js/datetimepicker/bootstrap-datetimepicker.min.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addStyle($this->api_url.'/opencart_admin/common/css/bootstrap-datetimepicker.min.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            }

            $this->document->addStyle($this->api_url.'/opencart_admin/common/css/colpick.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addStyle($this->api_url.'/opencart_admin/common/css/bootstrap-select.min.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/common/js/colpick.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/common/js/bootstrap-select.min.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/common/js/tools.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addStyle($this->api_url.'/opencart_admin/common/css/license_form.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());

            $this->document->addStyle($this->api_url.'/opencart_admin/common/js/remodal/remodal.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addStyle($this->api_url.'/opencart_admin/common/js/remodal/remodal-default-theme.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addStyle($this->api_url.'/opencart_admin/common/js/remodal/remodal-default-theme-override.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/common/js/remodal/remodal.min.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/common/js/remodal/remodal-improve.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());

            if(version_compare(VERSION, '2.0.0.0', '>='))
            {
                $this->document->addScript($this->api_url.'/opencart_admin/common/js/oc2x.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addStyle($this->api_url.'/opencart_admin/common/css/oc2x.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            }
            else
            {
                $this->document->addScript($this->api_url.'/opencart_admin/common/js/oc2x.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addStyle($this->api_url.'/opencart_admin/common/css/oc2x.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addStyle($this->api_url.'/opencart_admin/common/css/oc15x.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addScript('view/javascript/ckeditor/ckeditor.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
                $this->document->addStyle('//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            }

            $this->document->addStyle($this->api_url.'/opencart_admin/common/retinaicon/style.css');
            $this->document->addStyle($this->api_url.'/opencart_admin/common/css/new_design.css');
        //END Add scripts and css

        //Add custom css
            $this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/general.css?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
            $this->document->addScript($this->api_url.'/opencart_admin/ext_ie_pro/js/general.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());
    }

    public function _check_errors_to_send() {
        if(version_compare(VERSION, '3.0.0.0', '>='))
        {
            if(!empty($this->session->data['error']))
            {
                $this->data_to_view['error_warning_2'] = $this->session->data['error'];
                unset($this->session->data['error']);
            }

            if(array_key_exists('new_version', $this->session->data) && !empty($this->session->data['new_version']))
            {
                $this->data_to_view['new_version'] = $this->session->data['new_version'];
                unset($this->session->data['new_version']);
            }

            if(!empty($this->session->data['error_expired']))
            {
                $this->data_to_view['error_warning_expired'] = $this->session->data['error_expired'];
                unset($this->session->data['error_expired']);
            }

            if(!empty($this->session->data['success']))
            {
                $this->data_to_view['success_message'] = $this->session->data['success'];
                unset($this->session->data['success']);
            }

            if(!empty($this->session->data['info']))
            {
                $this->data_to_view['info_message'] = $this->session->data['info'];
                unset($this->session->data['info']);
            }
        }
    }

    public function _load_basic_languages() {
        $lang_array = array(
            'heading_title_2',
            'button_save',
            'button_cancel',
            'apply_changes',
            'text_image_manager',
            'text_browse',
            'text_clear',
            'image_upload_description',
            'text_validate_license',
            'text_license_id',
            'text_send',
        );

        foreach ($lang_array as $key => $value) {
            $this->data_to_view[$value] = $this->language->get($value);
        }

        $this->data_to_view['heading_title'] = $this->language->get('heading_title');
    }

    public  function _redirect($url) {
        if(version_compare(VERSION, '2.0.0.0', '>='))
            $this->response->redirect($this->url->link($url, $this->token_name.'=' . $this->session->data[$this->token_name]));
        else
            $this->redirect($this->url->link($url, $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
    }

    function catchError($errno = '', $errstr = '', $errfile = '', $errline = '') {
        $message = '<b>Error number</b>: '.$errno.'<br>';
        $message .= '<b>Error details</b>: '.$errstr.'<br>';
        $message .= '<b>Error file</b>: '.$errfile.'<br>';
        $message .= '<b>Error line</b>: '.$errline;

        throw new Exception($message);
    }

    public function _send_custom_variables_to_view() {
        $jquery_variables = array();

        $jquery_variables = array(
            'token' => $this->session->data[$this->token_name],
            'token_name' => $this->token_name,
            'action' => html_entity_decode($this->url->link($this->real_extension_type.'/import_xls', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'link_ajax_get_form' => htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=ajax_get_form', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'link_ajax_open_ticket' => htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=ajax_open_ticket', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'text_image_manager' => $this->language->get('text_image_manager'),
            'convert_to_innodb_url' => htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=convert_to_innodb', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'download_libraries_url' => htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=download_libraries', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'libraries_download_error' => $this->language->get('libraries_download_error'),
            'remodal_button_confirm_loading_text' => $this->language->get('remodal_button_confirm_loading_text'),
            'profile_load_url' => htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=profile_load', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'cancel_process_url' => htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=cancel_process', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL')),
            'extension_version' => (int)abs(filter_var($this->language->get('heading_title'), FILTER_SANITIZE_NUMBER_INT)),
            'text_process_cancelled_by_user' => $this->language->get('process_cancelled_by_user'),
        );

        $jquery_variables = $this->model_extension_module_ie_pro_tab_profiles->_send_custom_variables_to_view($jquery_variables);
        $jquery_variables = $this->model_extension_module_ie_pro_tab_export_import->_send_custom_variables_to_view($jquery_variables);
        $jquery_variables = $this->model_extension_module_ie_pro_tab_migrations->_send_custom_variables_to_view($jquery_variables);
        if($this->has_cron)
            $jquery_variables = $this->model_extension_module_ie_pro_tab_crons->_send_custom_variables_to_view($jquery_variables);
        if($this->has_custom_fields)
            $jquery_variables = $this->model_extension_module_ie_pro_tab_custom_fields->_send_custom_variables_to_view($jquery_variables);

        $this->data_to_view['jquery_variables'] = $jquery_variables;
    }

    public function ajax_open_ticket()
    {
        $data = $this->request->post;
        $data['domain'] = HTTPS_CATALOG;
        $data['license_id'] = $this->config->get($this->extension_group_config.'_license_id');
        $result = $this->model_extension_devmanextensions_tools->curl_call($data, $this->api_url.'opencart/ajax_open_ticket');

        //from API are in json_encode
        echo $result; die;
    }

    public function convert_to_innodb() {
        $this->load->language($this->real_extension_type.'/import_xls');
        $array_return = array('error' => false, 'message' => $this->language->get('innodb_success'));

        //For fix error -> Error: Invalid default value for 'date_available'
            $link = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
            $mysql_version = (int)mysqli_get_client_version();
            if($mysql_version < 5.7)
                $this->db->query("UPDATE ".DB_PREFIX."product SET date_available = '0000-00-00' WHERE date_available = ''");
            mysqli_close($link);

        foreach ($this->get_tables_to_convert() as $table_name) {
            try {
                $sql = "ALTER TABLE `{$table_name}` ENGINE=INNODB ROW_FORMAT=DEFAULT;";

                $this->db->query( $sql);
            } catch (Exception $e) {
                $array_return['error'] = true;
                $array_return['message'] = $e->getMessage();
                break;
            }
        }

        if (!$array_return['error'])
        {
            $temp = array(
                'import_xls_innodb_converted' => true,
                'import_xls_license_id' => $this->config->get($this->extension_group_config.'_license_id')
            );

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('import_xls', $temp);
        }

        echo json_encode($array_return); die;
    }

    public function download_libraries() {
        $array_return = array('error' => false, 'message' => $this->language->get('libraries_download_successfull'));
        try {
            $file_path = DIR_SYSTEM."temp.zip";

            $f = file_put_contents($file_path, fopen($this->libraries_url, 'r'), LOCK_EX);

            if(FALSE === $f)
                throw new Exception($this->language->get('libraries_download_error_download'));

            $zip = new ZipArchive;
            $res = $zip->open($file_path);
            if ($res === TRUE) {
                $zip->extractTo(DIR_SYSTEM);
                $zip->close();
            } else {
                throw new Exception($this->language->get('libraries_download_error_download'));
            }

            unlink($file_path);
        } catch (Exception $e) {
            $array_return['error'] = true;
            $array_return['message'] = $e->getMessage();
        }
        echo json_encode($array_return); die;

    }

    public function _add_innodb_remodal($form_view) {

        $remodal_options = array(
            'open_on_ready' => true,
            'button_confirm_text' => '<i class="fa fa-database"></i>'.$this->language->get('innodb_modal_button_confirm'),
            'remodal_options' => 'closeOnConfirm: false'
        );

        $remodal_html = $this->model_extension_module_ie_pro->get_remodal('innodb', $this->language->get('innodb_modal_title'), $this->language->get('innodb_modal_description'), $remodal_options);

        $this->document->addScript($this->api_url.'/opencart_admin/ext_ie_pro/js/innodb.js?'.$this->model_extension_module_ie_pro->get_ie_pro_version());

        $form_view['tabs'][$this->language->get('tab_export_import')]['fields'][] = array(
            'type' => 'html_hard',
            'html_code' => $remodal_html
        );

        return $form_view;
    }

    public function _add_libraries_remodal($form_view) {
        $remodal_options = array(
            'open_on_ready' => true,
            'button_confirm_text' => '<i class="fa fa-download"></i>'.$this->language->get('libraries_download_confirm_download'),
            'button_cancel' => false,
            'button_close' => false,
            'button_cancel' => false,
            'remodal_options' => 'closeOnConfirm: false, closeOnEscape: false, closeOnOutsideClick: false'
        );

        $remodal_html = $this->model_extension_module_ie_pro->get_remodal('download_libraries', $this->language->get('libraries_download_remodal_title'), sprintf($this->language->get('libraries_download_remodal_description'), $this->libraries_url), $remodal_options);

        $form_view['tabs'][$this->language->get('tab_export_import')]['fields'][] = array(
            'type' => 'html_hard',
            'html_code' => $remodal_html
        );
        return $form_view;
    }

    public function _add_trial_remodal($form_view) {
        $remodal_options = array(
            'open_on_ready' => true,
            'button_cancel' => false,
        );

        $remodal_html = $this->model_extension_module_ie_pro->get_remodal('trial_remodal', $this->language->get('trial_remodal_modal_title'), $this->language->get('trial_remodal_modal_description'), $remodal_options);

        $form_view['tabs'][$this->language->get('tab_export_import')]['fields'][] = array(
            'type' => 'html_hard',
            'html_code' => $remodal_html
        );

        return $form_view;
    }

    public function _add_demo_customer_form_remodal($form_view) {
        $remodal_options = array(
            'open_on_ready' => true,
            'button_confirm' => false,
        );

        $remodal_html = $this->model_extension_module_ie_pro->get_remodal('customer_demo_form', $this->language->get('customer_demo_form_title'), $this->language->get('customer_demo_form_description'), $remodal_options);

        $form_view['tabs'][$this->language->get('tab_export_import')]['fields'][] = array(
            'type' => 'html_hard',
            'html_code' => $remodal_html
        );

        return $form_view;
    }

    public function _construct_view_form() {

        $this->_add_css_js_to_document();

        $form_view = array(
            'action' => $this->url->link($this->real_extension_type.'/'.$this->extension_name, $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'),
            'id' => $this->extension_name,
            'extension_name' => $this->extension_name,
            'columns' => 1,
            'tabs' => array(
                $this->language->get('tab_export_import') => array(
                    'icon' => '<span class="retina-arrows-0110"></span>',
                    'fields' => $this->model_extension_module_ie_pro_tab_export_import->get_fields(),
                ),
                $this->language->get('tab_cron_jobs') => array(
                    'icon' => '<span class="retina-theessentials-2639"></span>',
                    'fields' => $this->has_cron ? $this->model_extension_module_ie_pro_tab_crons->get_fields() : $this->_get_fields_tab_cron_jobs(),
                ),
                $this->language->get('tab_custom_fields') => array(
                    'icon' => '<span class="retina-theessentials-2547"></span>',
                    'fields' => $this->has_custom_fields ? $this->model_extension_module_ie_pro_tab_custom_fields->get_fields() : $this->_get_fields_tab_custom_fields(),
                ),
                $this->language->get('tab_migration') => array(
                    'icon' => '<span class="retina-theessentials-2580"></span>',
                    'fields' => $this->model_extension_module_ie_pro_tab_migrations->get_fields(),
                ),
            )
        );

        $no_libraries = !is_dir(DIR_SYSTEM.'library/Spout') ||  !is_dir(DIR_SYSTEM.'library/xml2array') || !is_dir(DIR_SYSTEM.'library/google_spreadsheets_2') || !is_dir(DIR_SYSTEM.'library/PhpSpreadsheet');

        $innodb = $this->config->get('import_xls_innodb_converted');

        if($no_libraries)
            $form_view = $this->_add_libraries_remodal($form_view);
        elseif(!$innodb)
            $form_view = $this->_add_innodb_remodal($form_view);
        elseif($this->is_t && $innodb)
            $form_view = $this->_add_trial_remodal($form_view);

        if($this->isdemo) {
            //$form_view = $this->_add_demo_customer_form_remodal($form_view);
        }

        $form_view = $this->model_extension_devmanextensions_tools->_get_form_values($form_view);

        return $form_view;
    }

    public function download_file() {
        $filename = $this->request->get['filename'];
        $filePath = $this->path_tmp_public.$filename;
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-type: text/' . $ext);

        if(!in_array($ext, array('json','xml'))) {
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
            );

            $output = file_get_contents($filePath, false, stream_context_create($arrContextOptions));
        } else {
            $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $filePath);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
              $output = curl_exec($ch);
              curl_close($ch);
        }
        echo $output;
        exit();
    }

    public function _get_fields_tab_custom_fields() {
        $custom_fields_purchase_message = $this->model_extension_devmanextensions_tools->curl_call(array('lang' => $this->is_ocstore ? 'rus' : 'eng'), $this->api_url.'opencart_export_import_pro/custom_fields_get_purchase_message');
        $fields = array(
            array(
                'type' => 'html_hard',
                'html_code' => $custom_fields_purchase_message
            )
        );
        return $fields;
    }

    public function _get_fields_tab_cron_jobs() {
        $cron_purchase_message = $this->model_extension_devmanextensions_tools->curl_call(array('lang' => $this->is_ocstore ? 'rus' : 'eng'), $this->api_url.'opencart_export_import_pro/cron_get_purchase_message');

        $fields = array(
            array(
                'type' => 'html_hard',
                'html_code' => $cron_purchase_message
            )
        );
        return $fields;
    }

    public function ajax_get_form($license_id = '') {
        $this->model_extension_devmanextensions_tools->ajax_get_form($license_id);
    }

    private function get_tables_to_convert() {
        $result = [];

        $whitelist_tables = $this->get_whitelist_tables();

        $sql = "SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND
                      ENGINE = 'MyISAM'";

        $rs = $this->db->query( $sql);

        foreach ($rs->rows as $key => $table) {
            $table_name = $table['TABLE_NAME'];

            if (in_array( $table_name, $whitelist_tables)) {
                $result[] = $table_name;
            }
        }

        return $result;
    }

    private function get_whitelist_tables() {
        return array_map( function( $table_name) {
            return DB_PREFIX . $table_name;
        }, self::$OPENCART_TABLES_WHITELIST);
    }

    private function setupClassLoader() {
        spl_autoload_register( [$this, 'ieProClassLoader'], true);
    }

    private function ieProClassLoader( $className) {
        $ieProDir = DIR_APPLICATION . '/model/extension/module/iepro';
        $ieProDirOCMOD = defined("DIR_MODIFICATION")  ? DIR_MODIFICATION . 'admin/model/extension/module/iepro' : $ieProDir;

        $filename = is_file("{$ieProDirOCMOD}/{$className}.php") ? "{$ieProDirOCMOD}/{$className}.php" : "{$ieProDir}/{$className}.php";

        if (file_exists( $filename)) {
            require_once $filename;

            return true;
        }

        return false;
    }
}
