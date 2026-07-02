<?php
    class CategoriesAnalyzer extends IeProProfileObject {
        /**
         * @var ColumnsAnalyzer
         */
        private $columns_analyzer;

        /**
         * @var array
         */
        private $categories;

        /**
         * @var array
         */
        private $columns;

        /**
         * @var ColumnsConfiguration
         */
        private $columns_config;

        /**
         * @var string
         */
        private $category_locale;

        /**
         * @var bool
         */
        private $category_locale_detected = false;

        public function __construct( $controller) {
            parent::__construct( $controller);

            $this->columns_analyzer = new ColumnsAnalyzer( $controller);
        }

        public function execute() {
            $this->check_categories_mapping_columns_data();
            $this->load_columns_config();

            $this->model_loader->load_file_model();

            if ($this->is_xml_format() && $this->has_categories_main_node()) {
                $this->categories = $this->load_categories_from_xml_main_node();
            } else {
                $this->categories = $this->load_categories_common();
            }

            if (count( $this->categories) === 0) {
                throw new \Exception( $this->language->get( 'profile_import_error_no_categories_found'));
            }
        }

        public function get_result() {
            $model = $this->model_loader->load( 'ie_pro_categories');
            $allCategories = $model->get_all_categories_catalog();

            $builder = new CategoriesMappingPanelBuilder( $this->controller);
            $builder->setCategoryMappings( $this->categories);
            $builder->setAllCategories( $allCategories);

            return $builder->build();
        }

        private function load_categories_from_xml_main_node() {
            $categoriesNodePath = $this->parameters->get( 'import_xls_categories_node_xml');
            $idAttributeName = $this->parameters->get( 'import_xls_category_id_attribute', '');
            $parentIdAttributeName = $this->parameters->get( 'import_xls_category_parent_id_attribute', null);
            $valueAttributeName = $this->parameters->get( 'import_xls_category_value_attribute', '');

            $xmlData = XmlDataLoader::get_xml_data( $this->controller);
            $nodeName = $xmlData->get_path_node_name( $categoriesNodePath);

            $idAttributeName = $xmlData->strip_node_name_from_attribute(
                $idAttributeName,
                $nodeName,
                $this->language->get( 'profile_import_mapping_categories_id_attribute_label')
            );

            if ($this->is_category_tree() || !empty( $parentIdAttributeName)) {
                $parentIdAttributeName = $xmlData->strip_node_name_from_attribute(
                    $parentIdAttributeName,
                    $nodeName,
                    $this->language->get( 'profile_import_mapping_categories_parent_id_attribute')
                );
            }

            if ($valueAttributeName !== '' && $valueAttributeName !== $nodeName) {
                $valueAttributeName = $xmlData->strip_node_name_from_attribute(
                    $valueAttributeName,
                    $nodeName,
                    $this->language->get( 'profile_import_mapping_categories_value_attribute_label')
                );
            } else {
                $valueAttributeName = '';
            }

            $categoriesNode = $xmlData->get_nodes_from_path( $categoriesNodePath);
            $categoriesMap = $this->build_categories_map_from_node( $categoriesNode, [
                'id' => $idAttributeName,
                'parentId' => $parentIdAttributeName,
                'value' => $valueAttributeName
            ]);

            $mainNodePath = $this->parameters->get( 'import_xls_node_xml');

            if ($this->is_category_tree()) {
                $category_config = $this->columns_config->get_category_config();
                $categoryColumns = array_merge( $category_config->parents, $category_config->children);
            } else {
                $categoryColumns = $this->columns_config->get_category_config();
            }

            $nodes = $xmlData->get_nodes_from_path( $mainNodePath);
            $categories = [];

            foreach ($nodes as $node) {
                $categoryIds = $this->get_category_ids( $xmlData, $node, $categoryColumns);

                if (!empty( $categoryIds)) {
                    foreach ($categoryIds as $categoryId) {
                        if ($categoryId !== null) {
                            $categories[$categoryId] = $this->get_full_category_path( $categoriesMap, $categoryId);
                        }
                    }
                }
            }

            $result = [];

            foreach ($categories as $id => $name) {
                $segments = preg_split( '/\s+\>\s+/', $name);

                if (count( $segments) === 1){
                    $segments = [];
                }

                $result[] = (object)[
                    'id' => $id,
                    'name' => $name,
                    'segments' => $segments
                ];
            }

            return $result;
        }

        private function load_categories_common() {
            $this->profile_manager->build_fake();

            if ($this->get_file_format() !== 'spreadsheet') {
                $this->controller->model_extension_module_ie_pro_file->upload_file_import();
            }

            $category_columns = $this->columns_config->get_category_config();
            $category_column_names = $this->get_category_column_names( $category_columns);

            list($columns, $xmlData) = $this->get_columns_from_data();

            $this->controller->columns = $columns;

            $model_name = $this->model_loader->get_file_model_name();

            if ($this->get_file_format() === 'xml') {
                $data_file = $this->controller->{$model_name}->get_data_from_xml_data( $xmlData, $category_column_names);
            } else {
                $data_file = $this->controller->{$model_name}->get_data();
            }

            if ($this->is_category_tree()) {
                $result = $this->extract_categories_tree_from_product_file_data(
                    $data_file,
                    $category_columns->parents,
                    $category_columns->children
                );
            } else {
                $result = $this->extract_simple_categories_from_product_file_data(
                    $data_file,
                    $category_columns
                );
            }

            usort( $result, function( $category1, $category2) {
                return strcmp( $category1->name, $category2->name);
            });

            return $result;
        }

        private function get_category_column_names( $category_columns) {
            $result = [];

            $category_columns = (array)$category_columns;

            if (isset( $category_columns['parents'])) {
                foreach ($category_columns as $columns) {
                    foreach ($columns as $column_def) {
                        $result[] = $column_def['custom_name'];
                    }
                }
            } else {
                foreach ($category_columns as $column_def) {
                    $result[] = $column_def['custom_name'];
                }
            }

            return $result;
        }

        private function get_columns_from_data() {
            $xmlData = null;

            if ($this->columns == null && $this->get_file_format() === 'xml') {
                list($columns,$xmlData) = $this->columns_analyzer->get_columns_from_xml_full_data( true);

                $this->columns = array_map( function( $columnName) {
                    return [
                        'custom_name' => $columnName
                    ];
                }, $columns);
            }

            return [$this->columns, $xmlData];
        }

        private function build_categories_map_from_node( $node, $attributeNames) {
            $result = [];

            $attributeNames = $this->sanitize_attribute_names( $attributeNames);

            foreach ($node as $child) {
                $attributes = $child['@attributes'];

                if (!isset( $attributes[$attributeNames->id])) {
                    throw new \Exception( $this->language->get( 'profile_import_error_category_id_attribute_missing_or_incorrect'));
                }

                $id = $attributes[$attributeNames->id];

                if ($attributeNames->parentId !== null &&
                    isset( $attributes[$attributeNames->parentId])) {
                    $parentId = $attributes[$attributeNames->parentId];
                } else {
                    $parentId = null;
                }

                $value = null;

                if (isset( $attributes[$attributeNames->value])) {
                    $value = $attributes[$attributeNames->value];
                } elseif (isset( $child[$attributeNames->value])) {
                    $value = $child[$attributeNames->value];
                } else {
                    throw new \Exception( $this->language->get( 'profile_import_error_category_value_attribute_invalid'));
                }

                $result[$id] = (object)[
                    'id' => +$id,
                    'parentId' => $parentId !== null ? +$parentId : null,
                    'value' => $value
                ];
            }

            return $result;
        }

        private function sanitize_attribute_names( $attributeNames) {
            $attributeNames['id'] = $attributeNames['id'];
            $attributeNames['parentId'] = $attributeNames['parentId'];
            $attributeNames['value'] = $attributeNames['value'];

            $result = (object)$attributeNames;

            if (empty( $result->value)) {
                $result->value = '@value';
            }

            return $result;
        }

        private function get_full_category_path( $categoriesMap, $id) {
            if(!array_key_exists($id, $categoriesMap))
                return 'ERROR GETTING CATEGORY TREE';

            $category = $categoriesMap[$id];
            $result = $category->value;

            if ($category->parentId !== null) {
                while ($category->parentId !== null) {
                    $category = $categoriesMap[$category->parentId];

                    $result = "{$category->value} > {$result}";
                }
            }

            return $result;
        }

        private function check_categories_mapping_columns_data() {
            UploadManager::check_data( $this->controller);

            if (!$this->categories_columns_are_mapped()) {
                throw new \Exception( $this->language->get( 'profile_import_error_categories_columns_not_configured'));
            }
        }

        private function load_columns_config() {
            $this->columns_config = new ColumnsConfiguration( $this->parameters, $this->get_category_locale());
        }

        private function categories_columns_are_mapped() {
            $isCategoryTree = $this->is_category_tree();
            $categoryCount = 0;
            $catTreeParentCount = 0;
            $catTreeChildCount = 0;

            if ($isCategoryTree) {
                $catTreeParentCount = +$this->parameters->get( 'import_xls_cat_tree_number', 0);
                $catTreeChildCount = +$this->parameters->get( 'import_xls_cat_tree_children_number', 0);
            }
            else {
                $categoryCount = +$this->parameters->get( 'import_xls_cat_number', 0);
            }

            $categoryParentFields = [];
            $categoryParentStatuses = [];
            $categoryChildFields = [];
            $categoryChildStatuses = [];
            $categoryFields = [];
            $categoryStatuses = [];

            $categoryLocale = $this->get_category_locale();

            if ($isCategoryTree) {
                for ($parentLevel = 1; $parentLevel <= $catTreeParentCount; $parentLevel++) {
                    $categoryParentFields[] = $this->get_category_tree_parent_column_field(
                        $parentLevel,
                        $categoryLocale
                    );

                    $categoryParentStatuses[] = $this->get_category_tree_parent_column_status(
                        $parentLevel,
                        $categoryLocale
                    );

                    for ($childLevel = 1; $childLevel <= $catTreeChildCount; $childLevel++) {
                        $categoryChildFields[] = $this->get_category_tree_child_column_field(
                            $parentLevel,
                            $childLevel,
                            $categoryLocale
                        );

                        $categoryChildStatuses[] = $this->get_category_tree_child_column_status(
                            $parentLevel,
                            $childLevel,
                            $categoryLocale
                        );
                    }
                }
            } else {
                for ($i = 1; $i <= $categoryCount; $i++) {
                    $categoryFields[] = $this->get_category_column_field( $i, $categoryLocale);
                    $categoryStatuses[] = $this->get_category_column_status( $i, $categoryLocale);
                }
            }

            $fields = [];
            $statuses = [];

            if ($isCategoryTree) {
                $fields = array_merge( $categoryParentFields, $categoryChildFields);
                $statuses = array_merge( $categoryParentStatuses, $categoryChildStatuses);
            } else {
                $fields = $categoryFields;
                $statuses = $categoryStatuses;
            }

            return count( $fields) > 0 &&
                   ArrayTools::all_present( $fields) &&
                   ArrayTools::any_checked( $statuses);
        }

        private function extract_categories_tree_from_product_file_data(
            $file_data,
            $parent_columns,
            $child_columns){
            $result = [];

            $columns = $file_data['columns'];

            foreach ($file_data['data'] as $row) {
                foreach ($parent_columns as $parent_column_def){
                    $parent_column_name = $parent_column_def['real_name'];
                    $parent_column_index = array_search( $parent_column_name, $columns);

                    if ($parent_column_index !== false) {
                        $parent_category = $this->get_column_value(
                            $row,
                            $parent_column_index,
                            $parent_column_def
                        );

                        $this->add_if_missing( $parent_category, $result);

                        $this->process_child_categories(
                            $row,
                            $parent_category,
                            $child_columns,
                            $columns,
                            $result
                        );
                    }
                }
            }

            $result = array_map( function( $item){
               $segments = preg_split( '/\s+\>\s+/', $item);

               if (count( $segments) === 1){
                   $segments = [];
               }

               return (object)[
                   'name' => $item,
                   'segments' => $segments
               ];
            }, $result);

            return $result;
        }

        private function process_child_categories(
            $row,
            $parent_category,
            $child_columns,
            $columns,
            &$result) {
            foreach ($child_columns as $child_column_def){
                $child_column_name = $child_column_def['real_name'];
                $child_column_index = array_search( $child_column_name, $columns);

                if ($child_column_index !== false) {
                    $child_category = $this->get_column_value(
                        $row,
                        $child_column_index,
                        $child_column_def
                    );

                    if (!empty( $child_category)) {
                        if (strpos( $child_category, '>') !== false) {
                            $full_name = $child_category;

                            $this->add_if_missing( $full_name, $result);
                        } else {
                            if ($child_category !== $parent_category) {
                                $full_name = "{$parent_category} > {$child_category}";
                                $parent_category = $full_name;

                                $this->add_if_missing( $full_name, $result);
                            }
                        }
                    }
                }
            }
        }

        private function extract_simple_categories_from_product_file_data(
            $file_data,
            $category_columns){
            $result = [];

            $columns = $file_data['columns'];

            foreach ($file_data['data'] as $row){
                foreach ($category_columns as $category_column_def) {
                    $columnName = $category_column_def['real_name'];
                    $category_index = $this->get_category_column_index( $columnName, $columns, $category_column_def);

                    $category = $this->get_column_value( $row, $category_index, $category_column_def);

                    $this->add_if_missing( $category, $result);
                }
            }

            $result = array_map( function( $item){
               return (object)[
                   'name' => $item,
                   'segments' => []
               ];
            }, $result);

            return $result;
        }

        private function get_category_column_index( $columnName, $columns, $category_column_def) {
            $result = array_search( $columnName, $columns);

            if ($result === false) {
                if (!isset( $category_column_def['id_instead_of_name']) || !$category_column_def['id_instead_of_name']) {
                    $result = array_search( "{$columnName}>@value", $columns);
                } else {
                    $result = array_search( "{$columnName}>@attributes>@id", $columns);
                }
            }

            return $result;
        }

        private function is_xml_format() {
            return $this->profile_manager->get_format() === 'xml';
        }

        private function has_categories_main_node() {
            $categoriesMainNodePath = $this->parameters->get( 'import_xls_categories_node_xml');

            return $categoriesMainNodePath !== null &&
                   !empty( trim( $categoriesMainNodePath));
        }

        private function get_category_ids( XmlData $xmlData, $node, array $category_columns) {
            $self = $this;

            return array_map( function( $column) use($self, $xmlData, $node) {
                $column_name = $self->get_column_name( $column);

                return $xmlData->get_property_value( $node, $column_name);
            }, $category_columns);
        }

        private function get_column_value( $row, $index, $column_def) {
            $result = trim( $row[$index]);

            if (isset( $column_def['value_index'])) {
                $value_index = +$column_def['value_index'];
                $parts = explode( $column_def['splitted_values'], $result);

                $values = [];

                for ($i = 0; $i <= $value_index; $i++) {
                    if (isset( $parts[$i])) {
                        $values[] = $parts[$i];
                    }
                }

                $result = implode( ' > ', $values);
            }

            return $result;
        }

        private function add_if_missing( $value, &$array) {
            if (!empty( $value) && !in_array( $value, $array)){
                $array[] = $value;
            }
        }

        private function build_simple_category_column_name( $number, $locale = null) {
            return $locale !== null
                   ? "Cat. {$number} {$locale}"
                   : "Cat. {$number}";
        }

        private function build_parent_category_column_name( $number, $locale = null) {
            return $locale !== null
                   ? "Cat. tree {$number} parent {$locale}"
                   : "Cat. tree {$number} parent";
        }

        private function build_child_category_column_name( $parent, $level, $locale = null) {
            return $locale !== null
                   ? "Cat. tree {$parent} level {$level} {$locale}"
                   : "Cat. tree {$parent} level {$level}";
        }

        private function get_column_name( $column) {
            return preg_replace( '/&gt;/', '>', $column['custom_name']);
        }

        private function is_category_tree() {
            return $this->parameters->get( 'import_xls_category_tree') == '1';
        }

        private function get_category_tree_parent_column_field( $level, $locale = null){
            return $this->get_category_tree_parent_column_param( $level, 'custom_name', $locale);
        }

        private function get_category_tree_parent_column_status( $level, $locale = null){
            return $this->get_category_tree_parent_column_param( $level, 'status', $locale);
        }

        private function get_category_tree_child_column_field( $parentLevel, $level, $locale = null){
            return $this->get_category_tree_child_column_param(
                $parentLevel,
                $level,
                'custom_name',
                $locale
            );
        }

        private function get_category_tree_child_column_status( $parentLevel, $level, $locale = null){
            return $this->get_category_tree_child_column_param(
                $parentLevel,
                $level,
                'status',
                $locale
            );
        }

        private function get_category_column_param( $number, $fieldType, $locale = null) {
            return $this->get_column_parameter(
                $this->build_simple_category_column_name( $number, $locale),
                $fieldType
            );
        }

        private function get_category_tree_parent_column_param( $level, $fieldType, $locale = null){
            return $this->get_column_parameter(
                $this->build_parent_category_column_name( $level, $locale),
                $fieldType
            );
        }

        private function get_category_tree_child_column_param(
            $parentLevel,
            $level,
            $fieldType,
            $locale = null){
            return $this->get_column_parameter(
                $this->build_child_category_column_name( $parentLevel, $level, $locale),
                $fieldType
            );
        }

        private function get_category_column_field( $number, $locale = null){
            return $this->get_category_column_param( $number, 'custom_name', $locale);
        }

        private function get_category_column_status( $number, $locale = null){
            return $this->get_category_column_param( $number, 'status', $locale);
        }

        private function get_column_parameter( $name, $fieldType) {
            $columns = $this->get_columns_parameter();

            return array_key_exists($fieldType, $columns[$name]) ? $columns[$name][$fieldType] : '';
        }

        private function get_columns_parameter() {
            return $this->parameters->get( 'columns');
        }

        private function get_file_format() {
            return $this->parameters->get( 'import_xls_file_format');
        }

        private function get_category_locale() {
            if (!$this->category_locale_detected) {
                $this->category_locale = $this->detect_category_locale();
                $this->category_locale_detected = true;
            }

            return $this->category_locale;
        }

        private function detect_category_locale() {
            $result = null;

            if ($this->profile_manager->is_multilanguage()) {
                $categories_locales = $this->get_categories_locales();

                $current_lang_code = $this->controller->default_language_code;
                $current_locale = preg_replace( '/\-/', '_', $current_lang_code);

                if (array_search( $current_lang_code, $categories_locales) !== false) {
                    $result = $current_lang_code;
                } else if (array_search( $current_locale, $categories_locales) !== false) {
                    $result = $current_locale;
                } else if (count( $categories_locales) > 0) {
                    $result = $categories_locales[0];
                }
            }

            return $result;
        }

        private function get_categories_locales() {
            $result = [];
            $columns = $this->get_columns_parameter();

            foreach ($columns as $column_name => $column_data) {
                if (preg_match( '/Cat. \\d+ ([a-zA-Z]{2}[\-|_][a-zA-Z]{2})/', $column_name, $matches) === 1 &&
                    isset( $column_data['status']) &&
                    $column_data['status'] == 1) {
                    $result[] = $matches[1];
                }
            }

            return array_unique( $result);
        }
    }
