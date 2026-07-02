<?php
    class XmlDataLoader extends IeProProfileObject {
        private $xml_array;

        public static function get_xml_data( $controller) {
            $xml_data = new XmlDataLoader( $controller);
            $xml_data->load();

            return $xml_data->get_data();
        }

        public function load() {
            switch ($this->profile_manager->get_file_origin()) {
                case 'manual':
                    $contents = $this->get_file_contents();
                    break;

                case 'url':
                    $contents = $this->get_url_contents();
                    break;

                case 'ftp':
                    $contents = $this->get_ftp_contents();
                    break;
            }

            $model = $this->model_loader->load_file_model( 'ie_pro_file_xml');

            $this->xml_array = $model->get_xml2array_object()->createArray( $contents);
        }

        public function get_data() {
            return new XmlData( $this->controller, $this->xml_array);
        }

        private function get_file_contents() {
            if (!$this->parameters->has_file_upload()) {
               throw new \Exception( $this->language->get( 'profile_import_categories_xml_file_upload_expected'));
            }

            $file = $this->parameters->file( 'file');

            return file_get_contents( $file['tmp_name']);
        }

        private function get_url_contents() {

            $ch = curl_init();
            /**
             * OJO: PARCHE de Lian
             * import_xls_url viene con "&" como "&amp;", fix_url() hace lo contrario
             *
             * TODO: Revisar y arreglar!!!
             */
            $url = $this->fix_url( $this->parameters->get('import_xls_url'));

            curl_setopt( $ch, CURLOPT_URL, $url);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt( $ch, CURLOPT_HEADER, false);
            curl_setopt( $ch, CURLOPT_TIMEOUT, 300);
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);

            $http_authentication = $this->parameters->get('import_xls_http_authentication');

            if ($http_authentication !== '') {
                $username = $this->parameters->get('import_xls_http_username');
                $password = $this->parameters->get('import_xls_http_password');
                curl_setopt( $ch, CURLOPT_USERPWD, "{$username}:{$password}");

                if ($http_authentication === 'digest') {
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                }
            }

            $result = curl_exec( $ch);

            if (curl_errno( $ch)) {
                throw new \Exception( sprintf( $this->language->get('curl_error'), curl_errno( $ch)));
            }

            $info = curl_getinfo( $ch);

            if ($info['http_code'] !== 200) {
                throw new \Exception( $result);
            }

            curl_close( $ch);

            return $result;

            //return file_get_contents(htmlspecialchars_decode($this->parameters->get( 'import_xls_url')));
        }

        private function get_ftp_contents() {
            $this->file_type = $this->parameters->get( 'import_xls_file_format');
            $file = $this->parameters->get_strict(
                'import_xls_ftp_file',
                $this->language->get('progress_export_ftp_empty_filename')
            );

            $filename = "{$file}.{$this->file_type}";

            $ftp_path = rtrim( $this->parameters->get( 'import_xls_ftp_path'));
            $final_path = "{$ftp_path}{$filename}";

            $connection = $this->open_ftp_connection();
            $temp_file = $this->controller->path_tmp.'temp_file.'.$this->file_type;
            ftp_get( $connection, $temp_file, $final_path, FTP_BINARY);
            ftp_close( $connection);

            return file_get_contents( $temp_file );
        }

        private function open_ftp_connection() {
            $server = $this->parameters->get( 'import_xls_ftp_host');
            $username = $this->parameters->get( 'import_xls_ftp_username');
            $password = html_entity_decode($this->parameters->get( 'import_xls_ftp_password'));
            $port = $this->parameters->get( 'import_xls_ftp_port');
            $port = empty($port) ? 21 : $port;

            $connection = ftp_connect( $server, $port);

            if (!$connection) {
                throw new \Exception( $this->language->get( 'progress_export_ftp_error_connection'));
            }

            $login = ftp_login( $connection, $username, $password);

           /* if (!$login) {
                throw new \Exception( $this->language->get( 'progress_export_ftp_error_login'));
            }*/

            $passive_mode = $this->parameters->get( 'import_xls_ftp_passive_mode', null) !== null;

            if ($passive_mode) {
                ftp_pasv( $connection, true);
            }

            return $connection;
        }

        /**
         * Convierte los '&amp;' --> '&'
         */
        private function fix_url( $url) {
           return str_replace( '&amp;', '&', $url);
        }
    }
