<?php
    class UploadManager extends IeProProfileObject {
        public static function check_data( $controller) {
            $uploadManager = new UploadManager( $controller);
            $uploadManager->check_data_is_valid();
        }

        public function check_data_is_valid() {
            switch ($this->profile_manager->get_file_origin())
            {
                case 'manual':
                    if ($this->parameters->has_file_upload()) {
                       $this->check_file_upload_is_valid();
                    }
                    break;

                case 'url':
                    $this->check_url_upload_is_valid();
                    break;

                case 'ftp':
                    $this->check_ftp_upload_is_valid();
                    break;
            }
        }

        private function check_file_upload_is_valid() {
            $format = $this->parameters->get( 'import_xls_file_format');

            if (empty( $format) || !$this->uploaded_file_matches_format( $format)){
                throw new \Exception( $this->language->get( 'profile_import_error_missing_upload_file_or_invalid_format'));
            }
        }

        private function check_url_upload_is_valid() {
            if ($this->parameters->is_empty( 'import_xls_url')) {
                throw new \Exception( $this->language->get( 'profile_import_error_missing_url'));
            }
        }

        private function check_ftp_upload_is_valid() {
            $host = $this->parameters->get( 'import_xls_ftp_host');
            $username = $this->parameters->get( 'import_xls_ftp_username');
            $password = $this->parameters->get( 'import_xls_ftp_password');
            $port = $this->parameters->get( 'import_xls_ftp_port');
            $path = $this->parameters->get( 'import_xls_ftp_path');
            $filename = $this->parameters->get( 'import_xls_ftp_file');

            if (!ArrayTools::all_present( [$host, $username, $password, $path, $filename])){
                throw new \Exception( $this->language->get( 'profile_import_error_missing_ftp_info'));
            }
            else if (!$this->is_valid_port( $port)) {
                throw new \Exception( $this->language->get( 'profile_import_error_missing_ftp_invalid_port'));
            }
        }

        private function uploaded_file_matches_format( $format){
            return $format === $this->get_upload_file_extension();
        }

        private function get_upload_file_extension() {
            $result = '';

            if ($this->parameters->has_file_upload()) {
                $file = $this->parameters->file( 'file');
                $filename = $file['name'];

                $dotIndex = strrpos( $filename, '.');
                $result = substr( $filename, $dotIndex + 1);
            }

            return $result;
        }

        private function is_valid_port( $port) {
            $port = trim($port);
            $port = empty($port) || !is_int($port) ? 21 : $port;
            return !empty( $port) &&
                   is_integer( $port) &&
                   +$port >= 1 &&
                   +$port <= 65535;
        }
    }
