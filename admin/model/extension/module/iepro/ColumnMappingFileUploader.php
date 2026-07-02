<?php
    class ColumnMappingFileUploader {
        /**
         * @var string
         */
        private $icon;

        /**
         * @var string
         */
        private $profile_type;

        /**
         * @var string
         */
        private $action;

        /**
         * @var string
         */
        private $button_text;

        /**
         * @var string
         */
        private $button_class;

        /**
         * @var string
         */
        private $js_on_change;

        /**
         * @var string
         */
        private $pre_processing_message;

        /**
         * @var string
         */
        private $post_processing_message;

        /**
         * @var bool
         */
        private $processed = false;

        public function icon( $icon) {
            $this->icon = $icon;

            return $this;
        }

        public function profile_type( $profile_type) {
            $this->profile_type = $profile_type;

            return $this;
        }

        public function action( $action) {
            $this->action = $action;

            return $this;
        }

        public function button_text( $button_text) {
            $this->button_text = $button_text;

            return $this;
        }

        public function button_class( $button_class) {
            $this->button_class = $button_class;

            return $this;
        }

        public function js_on_change( $js_on_change) {
            $this->js_on_change = $js_on_change;

            return $this;
        }

        public function pre_processing_message( $pre_processing_message) {
            $this->pre_processing_message = $pre_processing_message;

            return $this;
        }

        public function post_processing_message( $post_processing_message) {
            $this->post_processing_message = $post_processing_message;

            return $this;
        }

        public function alert_message( $alert_message) {
            $this->alert_message = $alert_message;

            return $this;
        }

        public function processed( $processed) {
            $this->processed = $processed;

            return $this;
        }

        public function render() {
            $on_change_selector = '.profile_import.main_configuration.configuration.type_select select[name="import_xls_file_format"]';

            if ($this->profile_type === 'import') {
               $on_change_selector .= ',.profile_import.main_configuration.configuration.type_select select[name="import_xls_file_origin"]';
            }

            $info_message = $this->processed
                           ? $this->post_processing_message
                           : $this->pre_processing_message;

            $class_message = $this->processed ? 'success' : 'info';
            $is_category_mapping = !empty($this->action) &&
                                   $this->action === 'profile_get_categories_mapping_columns_html';

            return '<div class="col-md-12 form-group-columns"
                         style="border: 2px solid #e1d8d8; width: 100%; margin-left: 15px;">

                        <div class="alert alert-'.$class_message.'">' .
                            $info_message . '
                        </div>

                        '.(!$this->processed && $is_category_mapping ? $this->get_profile_categories_alert_box_html() : '').'

                        <a onclick="'. $this->action . '( $(this)); return false;"
                            class="button ' . $this->button_class . '">
                            <i class="fa fa-' . $this->icon . '"></i>' .
                            $this->button_text . '
                        </a>


                    </div>

                    <script>
                        $(\'' . $on_change_selector . '\').on( \'change\', ' . $this->js_on_change . ');
                    </script>';
        }

        private function get_profile_categories_alert_box_html() {
            return '<div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 15px;">
                       <i class="fa fa-exclamation-circle"></i>' .
                       $this->alert_message . '
                    </div>';
        }
    }
