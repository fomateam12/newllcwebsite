<?php
    class ModelLoader {
        /**
         * @var ModelExtensionModuleIeProTabProfiles
         */
        private $controller;

        public function __construct( $controller) {
           $this->controller = $controller;
           $this->parameters = HttpPostParameters::from( $controller->request);
        }

        public function load( $name) {
            $this->controller->load->model( "extension/module/{$name}");
            $model_name = "model_extension_module_{$name}";

            return $this->controller->{$model_name};
        }

        public function load_file_model() {
            $this->load( 'ie_pro_file');

            return $this->load( $this->get_simple_file_model_name());
        }

        public function get_simple_file_model_name() {
            $format = $this->parameters->get( 'import_xls_file_format');

            return "ie_pro_file_{$format}";
        }

        public function get_file_model_name() {
            $format = $this->parameters->get( 'import_xls_file_format');

            return "model_extension_module_ie_pro_file_{$format}";
        }
    }
