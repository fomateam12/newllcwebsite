<?php
    class CategoriesMappingPanelBuilder extends IeProProfileObject {
        private $defaultCategory;
        private $allCategories;
        private $categoryMappings;
        private $idMappings;

        public function __construct( $controller) {
            parent::__construct( $controller);
        }

        public function setDefaultCategory( $defaultCategory) {
            $this->defaultCategory = $defaultCategory;
        }

        public function setAllCategories( $allCategories) {
            $this->allCategories = $allCategories;

            $this->build_categories_map();
        }

        public function setCategoryMappings( $categoryMappings) {
            $this->categoryMappings = $categoryMappings;
        }

        public function setIdMappings( $idMappings) {
            $this->idMappings = $idMappings;
        }

        public function build() {
            $default_category_name = empty( $this->defaultCategory)
                                     ? 'None'
                                     : $this->get_category_name( $this->defaultCategory);
            $table = $this->build_categories_table();

            return '<div class="row">
                        <div class="col-md-2 text-right">
                            <strong>' . $this->language->get( 'profile_import_categories_default_label') . '</strong>:
                        </div>

                        <div class="col-md-10">
                            <input class="form-control category_input_selector"
                                    value="' . $default_category_name . '">
                            <input type="hidden" name="categories_mapping_default"
                                    value="' . $this->defaultCategory . '">
                        </div>
                   </div>

                   <div style="clear: both; height: 20px;"></div>' . $table;
        }

        private function build_categories_table() {
            $userCategory = $this->language->get( 'profile_import_categories_user_category');
            $opencartCategory = $this->language->get( 'profile_import_categories_opencart_category');

            $tableBody = $this->is_object_mappings()
                         ? $this->build_table_body_from_object_mappings()
                         : $this->build_table_body_from_simple_mappings();

            return "<table class=\"table table-bordered table-hover\">
                        <thead>
                            <tr>
                                <td>{$userCategory}</td>
                                <td>{$opencartCategory}</td>
                            </tr>
                        <thead>

                        <tbody>
                           {$tableBody}
                        </tbody>
                    </table>";
        }

        private function is_object_mappings() {
            $keys = array_keys( $this->categoryMappings);

            return count( $keys) > 0
                   ? is_object( $this->categoryMappings[$keys[0]])
                   : false;
        }

        private function get_category_name( $category_id) {
            return isset( $this->categories_map[$category_id])
                   ? $this->categories_map[$category_id]['name']
                   : 'None';
        }

        private function build_categories_map() {
            $this->categories_map = [];

            foreach ($this->allCategories as $category) {
                $this->categories_map[$category['category_id']] = $category;
            }
        }

        private function build_table_body_from_object_mappings() {
            $result = '';
            $index = 0;

            foreach ($this->categoryMappings as $catIndex => $category) {
                $fieldName = "categories_mapping[{$index}]";
                $idName = "categories_id_mapping[{$index}]";

                if (!empty( $category->segments)){
                    $value = implode( ',', $category->segments);
                }
                else {
                    $value = !empty($category->name) ? $category->name : '';
                }

                $categoryName = !empty($category->name) ? $category->name : $catIndex;

                $result .= '<tr>';

                $category = (array)$category;
                $idValue = isset($category['id']) ? $category['id'] : null;

                $result .= $this->build_category_name_cell(
                    $categoryName,
                    $fieldName,
                    $value,
                    $idName,
                    $idValue
                );

                $result .= $this->build_category_value_cell( $index);

                $result .= '</tr>';

                $index++;
            }

            return $result;
        }

        private function build_table_body_from_simple_mappings(){
            $result = '';

            $normalizedMappings = $this->build_normalized_mappings();

            foreach ($normalizedMappings as $index => $mapping) {
                $categoryName = $mapping->categoryName;
                $clientCategoryId = $mapping->clientCategoryId;
                $ocCategoryId = $mapping->ocCategoryId;

                $clientCategoryName = preg_replace( '/,/', '&nbsp;&gt;&nbsp;', $categoryName);
                $mappingsFieldName = "categories_mapping[{$index}]";
                $idMappingsFieldName = "categories_id_mapping[{$index}]";
                $categoryName = $this->get_category_name( $ocCategoryId);

                $result .= '<tr>';

                $result .= $this->build_category_name_cell(
                    $clientCategoryName,
                    $mappingsFieldName,
                    $mapping->categoryName,
                    $idMappingsFieldName,
                    $clientCategoryId
                );

                $result .= $this->build_category_value_cell(
                    $index,
                    $categoryName,
                    $ocCategoryId
                );

                $result .= '</tr>';

                $index++;
            }

            return $result;
        }

        private function build_normalized_mappings(){
            $result = [];

            $categoryKeys = array_keys( $this->categoryMappings);

            if (!empty( $this->idMappings)) {
                $idKeys = array_keys( $this->idMappings);
            }

            foreach ($categoryKeys as $index => $categoryName) {
                $clientCategoryId = !empty( $idKeys)
                                    ? $idKeys[$index]
                                    : null;

                $result[] = (object)[
                    'categoryName' => $categoryName,
                    'clientCategoryId' => $clientCategoryId,
                    'ocCategoryId' => $this->categoryMappings[$categoryName]
                ];
            }

            return $result;
        }

        private function build_category_name_cell(
            $categoryName,
            $fieldName,
            $value,
            $idFieldName = null,
            $idValue = null) {

            $valueField = $this->build_hidden_field( $fieldName, $value);

            $idField = !empty( $idFieldName)
                       ? $this->build_hidden_field( $idFieldName, $idValue)
                       : '';

            return "<td>
                      {$categoryName}
                      {$valueField}
                      {$idField}
                    </td>";
        }

        private function build_category_value_cell(
            $index,
            $categoryName = null,
            $categoryId = null) {

            return empty( $categoryName)
                   ? $this->build_category_empty_value_cell( $index)
                   : $this->build_category_filled_value_cell( $index, $categoryName, $categoryId);
        }

        private function build_category_empty_value_cell( $index) {
            $hiddenField = $this->build_hidden_field( "categories_mapping_opencart[{$index}]");

            return "<td>
                      <input class=\"form-control category_input_selector\"
                             value=\"None\">
                      {$hiddenField}
                    </td>";
        }

        private function build_category_filled_value_cell(
            $index,
            $categoryName,
            $categoryId = null) {

            $hiddenField = $this->build_hidden_field(
                "categories_mapping_opencart[{$index}]",
                $categoryId
            );

            return "<td>
                      <input class=\"form-control category_input_selector\"
                             value=\"{$categoryName}\">
                      {$hiddenField}
                    </td>";
        }

        private function build_hidden_field( $name, $value = null) {
            $value = empty( $value) ? '' : $value;

            return "<input type=\"hidden\"
                           name=\"{$name}\"
                           value=\"{$value}\">";
        }
    }
