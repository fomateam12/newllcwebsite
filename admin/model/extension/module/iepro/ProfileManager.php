<?php
    class ProfileManager {
        /**
         * @var ModelExtensionModuleIeProTabProfiles
         */
        protected $controller;

        protected $language;

        /**
         * @var ModelLoader
         */
        protected $model_loader;

        /**
         * @var HttpPostParameters
         */
        protected $parameters;

        public function __construct( $controller) {
            $this->controller = $controller;
            $this->language = $this->controller->language;
            $this->model_loader = new ModelLoader( $controller);
            $this->parameters = HttpPostParameters::from( $controller->request);
        }

        public function get_type() {
            $result = $this->parameters->get( 'profile_type', null);

            if ($result === null) {
                $result = $this->parameters->get_strict( 'import_xls_i_want', 'No import_xls_i_want data');
            }

            return $result;
        }

        public function is_multilanguage() {
            return $this->parameters->get_boolean( 'import_xls_multilanguage');
        }

        public function get_file_origin() {
            $result = $this->parameters->get( 'import_xls_file_origin', null);

            if ($result === null) {
                $result = $this->parameters->get( 'import_xls_file_destiny', null);
            }

            return $result;
        }

        public function build_fake() {
            $this->add_properties_for_upload_file_import();

            if ($this->get_file_origin() === 'manual'){
                $params = $this->parameters->get([
                    'import_xls_file_origin',
                    'import_xls_node_xml',
                    'import_xls_csv_separator'
                ]);
            } else {
                $params = $this->parameters->get([
                    'import_xls_file_origin',
                    'import_xls_url',
                    'import_xls_ftp_host',
                    'import_xls_ftp_port',
                    'import_xls_ftp_username',
                    'import_xls_ftp_password',
                    'import_xls_ftp_path',
                    'import_xls_ftp_file',
                    'import_xls_ftp_passive_mode',
                    'import_xls_node_xml',
                    'import_xls_csv_separator',
                    'import_xls_file_without_columns',
                    'import_xls_http_authentication',
                    'import_xls_http_username',
                    'import_xls_http_password',
                ]);
            }

            $this->controller->profile = array_merge( $params, ['import_xls_file_format' => $this->get_format()]);
        }

        public function get_format() {
            return $this->parameters->get( 'import_xls_file_format');
        }

        private function add_properties_for_upload_file_import() {
            // Necesitamos estas asignaciones aqui para que upload_file_import()
            // funcione correctamente

            $typeCode = ucfirst( $this->get_type());
            $now = date('Y-m-d-His');
            $format = $this->get_format();

            $this->controller->filename = "I-{$typeCode}-{$now}.{$format}";
            $this->controller->file_format = $format;
            $this->controller->root_path = substr( DIR_APPLICATION, 0, strrpos( DIR_APPLICATION, '/', -2)) . '/';
            $this->controller->path_progress = "{$this->controller->root_path}ie_pro/";
            $this->controller->path_tmp = "{$this->controller->path_progress}tmp/";
            $this->controller->file_tmp_path = "{$this->controller->path_tmp}{$this->controller->filename}";
        }
    }
