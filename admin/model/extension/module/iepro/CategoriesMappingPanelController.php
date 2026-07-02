<?php
    class CategoriesMappingPanelController extends IeProProfileObject {
        private $model_profile;

        /**
         * @var array|stdClass
         */
        private $categories_mapping;

        /**
         * @var array
         */
        private $all_categories;

        public function __construct( $controller) {
            parent::__construct( $controller);

            $this->model_profile = $this->controller->{$this->controller->model_profile};
        }

        public function execute() {
            $this->check_is_import_profile();

            $this->model_loader->load( $this->get_model_name());
            $this->html = $this->build_html();
        }

        public function get_result() {
            return $this->html;
        }

        private function check_is_import_profile() {
            $this->controller->profile_type = $this->parameters->get( 'profile_type', 'export');

          if ($this->controller->profile_type !== 'import'){
                var_dump( $_POST);
                var_dump( $this->controller->profile_type);
                die( 'Using categories mapping on export profile.');
            }
        }

        private function build_html(){
            $this->categories_mapping = $this->get_categories_mapping();

            $html = $this->build_form_html();
            $html .= $this->build_table_html();

            return $html;
        }

        private function get_categories_mapping() {
            $result = null;
            $profile_id = $this->get_profile_id();

            if ($profile_id !== null) {
                $profile = $this->model_profile->load( $profile_id, true);
                $profileInfo = $profile['profile'];

                $result = isset( $profileInfo['categories_mapping'])
                          ? $profileInfo['categories_mapping']
                          : null;

                if (!empty( $result) && !isset( $result['id_mappings'])) {
                    $result['id_mappings'] = [];
                }
            }

            $result = !empty( $result) ? (object)$result : null;

            return $result;
        }

        private function build_form_html() {
            $html = '<div class="row">
                        <div class="form-group type_button categories_mapping_file col-md-12">';

            $fileUploader = new ColumnMappingFileUploader();
            $fileUploader->icon( 'cubes')
                         ->profile_type( $this->controller->get_current_profile_type())
                         ->action( 'profile_get_categories_mapping_columns_html')
                         ->button_text( $this->language->get( 'profile_import_categories_mapping_load_columns'))
                         ->button_class( 'button_categories_mapping')
                         ->js_on_change( 'update_get_categories_upload_field')
                         ->pre_processing_message( $this->language->get( 'profile_import_categories_not_analyzed_yet'))
                         ->post_processing_message( $this->language->get( 'profile_import_categories_already_detected'))
                         ->alert_message( $this->language->get( 'profile_import_categories_configure_columns_first_warning'))
                         ->processed( $this->categories_mapping !== null);

            $html .= $fileUploader->render();

            $html .= $this->build_xml_extra_fields_html();

            $html .= '   </div>
                      </div>';

            $html .= $this->build_autocompletion_js();

            return $html;
        }

        private function build_autocompletion_js() {
            $result = '<script>';

            $result .= $this->build_category_list_js();

            /**
             * ATENCION: Para OC < 2.x se usa oc2x.js y ahí se define la función
             * de autocompletado como "autocomplete2". En OC >= 2.x se define
             * como "autocomplete". Aquí extraemos el nombre para usarlo más
             * abajo en el código generado. La solución efectiva es arreglar
             * oc2x.js para que la función se llame simplemente "autocomplete",
             * cuando eso se haga, arreglar este código.
             */
            $autocompleteName = $this->controller->oc_version > 1
                                ? 'autocomplete'
                                : 'autocomplete2';

            $result .= "function init_autocomplete() {
                            $('.category_input_selector').{$autocompleteName}({
                            delay: 2000,

                            source: function(request, response) {
                                var categories = _AUTOCOMPLETE_CATEGORY_LIST;

                                if (request.trim().length > 0) {
                                    var normalizedRequest = request.toLowerCase().replace( /\s+/g, '');

                                    categories = categories.filter( function( category){
                                        return category.normalizedName.indexOf( normalizedRequest) >= 0;
                                    });
                                }

                                return response( categories);
                            },

                            select: function( item) {
                                var el = $(this);
                                el.val( item.simpleName);
                                el.siblings('input[type=\"hidden\"]').val( item.value);
                                el.removeAttr('autocomplete');
                                el.next('ul.dropdown-menu').hide();
                            }
                            });
                        }

                        init_autocomplete();";

            $result .= '</script>';

            return $result;
        }

        private function build_category_list_js() {
            $list = array_map( function( $category){
                return (object)[
                   'id' => $category['category_id'],
                   'name' => $category['name'],
                   'simpleName' => $this->make_category_simple_name( $category['name'])
                ];
            }, $this->get_all_categories());

            $list = array_merge( [(object)['id' => null, 'name' => 'None', 'simpleName' => 'None']], $list);

            $result = 'var _CATEGORIES = ' . json_encode( $list) . ';';
            $result .= "var _AUTOCOMPLETE_CATEGORY_LIST = window._CATEGORIES.map( function( item){
                                  return {
                                    label: item.name,
                                    value: item.id,
                                    simpleName: item.simpleName,
                                    normalizedName: item.simpleName.toLowerCase().replace( /\s+/g, '')
                                  };
                              });";

            return $result;
        }

        private function make_category_simple_name( $name) {
            return htmlspecialchars_decode( preg_replace( '/&nbsp;/', ' ', $name));
        }

        private function build_table_html() {
            $html = '<div class="categories_mapping_columns">';
            $html .= $this->build_columns_html();
            $html .= '  </div>';

            return $html;
        }

        private function build_columns_html(){
            $result = '';

            if ($this->categories_mapping !== null){
                $builder = new CategoriesMappingPanelBuilder( $this->controller);
                $builder->setDefaultCategory( $this->categories_mapping->default);
                $builder->setCategoryMappings( $this->categories_mapping->mappings);
                $builder->setIdMappings( $this->categories_mapping->id_mappings);
                $builder->setAllCategories( $this->get_all_categories());

                $result = $builder->build();
            }

            return $result;
        }

        private function build_xml_extra_fields_html() {
            $panel_style = $this->profile_manager->get_format() === 'xml'
                           ? 'display: block'
                           : 'display: none';

            $remodal = $this->controller->get_remodal(
                'profile_import_mapping_categories_in_other_xml_node',
                $this->language->get( 'profile_import_mapping_categories_in_other_xml_node_remodal_title'),
                $this->language->get( 'profile_import_mapping_categories_in_other_xml_node_remodal_description'),
                ['button_cancel' => false,
                 'button_confirm' => false,
                 'link' => 'profile_import_mapping_categories_in_other_xml_node_link']
            );

            $result =  '<div class="col-md-12 profile_import_mapping_categories_extra_fields_panel"
                             style="' . $panel_style . '">

                            <div class="col-md-12 form-group-columns profile_import profile_export type_boolean">
                                <label class=" control-label">' .
                                    $this->language->get( 'profile_import_mapping_categories_in_other_xml_node_label') . '
                                </label>

                                <div>
                                    <label class="checkbox_container">
                                        <input name="import_xls_categories_in_other_xml_node"
                                            type="checkbox"
                                            class="ios-switch green">
                                        <div>
                                            <div></div>
                                        </div>
                                    </label>'  . $remodal . '
                                </div>
                            </div>

                            <div class="form-group type_button categories_file_upload_extras"
                                 style="display: none;">
                                <div class="row">
                                    <div class="col-md-3 text-right">' .
                                        $this->language->get( 'profile_import_mapping_categories_main_xml_node_label') . ':
                                    </div>

                                    <div class="col-md-5">
                                        <input type="text" name="import_xls_categories_node_xml"
                                            style="width: 100%">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 text-right">' .
                                        $this->language->get( 'profile_import_mapping_categories_id_attribute_label') . ':
                                    </div>

                                    <div class="col-md-5">
                                        <input type="text" name="import_xls_category_id_attribute"
                                            style="width: 100%">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 text-right">' .
                                        $this->language->get( 'profile_import_mapping_categories_parent_id_attribute_label') . ':
                                    </div>

                                    <div class="col-md-5">
                                        <input type="text" name="import_xls_category_parent_id_attribute"
                                            style="width: 100%">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 text-right">' .
                                        $this->language->get( 'profile_import_mapping_categories_value_attribute_label') . ':
                                    </div>

                                    <div class="col-md-5">
                                        <input type="text" name="import_xls_category_value_attribute" style="width: 100%">
                                    </div>
                                </div>
                            </div>

                            <script>
                                $(\'input[name="import_xls_categories_in_other_xml_node"]\').on( \'change\', toggle_categories_file_upload_extras_form);
                            </script>
                        </div>';

            return $result;
        }

        private function get_all_categories() {
            if ($this->all_categories === null) {
                $this->all_categories = $this->load_sorted_categories();

                $this->build_categories_map();
            }

            return $this->all_categories;
        }

        private function load_sorted_categories() {
            $model = $this->model_loader->load( 'ie_pro_categories');
            $result = $model->get_all_categories_catalog();

            usort( $result, function( $category1, $category2) {
                return strcmp( $category1['name'], $category2['name']);
            });

            return $result;
        }

        private function build_categories_map() {
            $this->categories_map = [];

            foreach ($this->all_categories as $category) {
                $this->categories_map[$category['category_id']] = $category;
            }
        }

        private function get_profile_id() {
            $result = $this->parameters->get( 'profile_id');

            return !empty( $result) ? $result : null;
        }

        private function get_model_name() {
            $type = $this->parameters->get_strict( 'import_xls_i_want', 'No import_xls_i_want data');

            return "ie_pro_{$type}";
        }
    }
