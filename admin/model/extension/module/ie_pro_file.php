<?php
    class ModelExtensionModuleIeProFile extends ModelExtensionModuleIePro {
        public function __construct($registry){
            parent::__construct($registry);
            $loader = new Loader($registry);
            $loader->language($this->real_extension_type.'/'.'ie_pro_file');

            if($this->profile) {
                if (isset( $this->profile['type']) &&
                    $this->profile['type'] === 'migration-export') {
                    $this->profile = $this->profile['profile'];
                } else {
                    $this->file_destiny = array_key_exists('import_xls_file_destiny', $this->profile) ? $this->profile['import_xls_file_destiny'] : '';
                    $this->file_type = array_key_exists('import_xls_file_format', $this->profile) ? $this->profile['import_xls_file_format'] : '';
                }
            }
        }

        public function get_filename() {
            $filename = 'NO DEFINED';

            if($this->force_filename_hard)
                $filename = $this->force_filename_hard.'.'.$this->file_type;
            elseif($this->profile) {
                $now = date('Y-m-d-His');

                if ($this->profile['profile_type'] === 'migration-export') {
                    $format = $this->profile['import_xls_file_format'];

                    $filename = "Migration-Export-{$now}.{$format}";
                } else {
                    $filename = ucfirst( $this->profile['profile_type']) . '-' . ucfirst($this->profile['import_xls_i_want']).'-'.$now.'.'.$this->profile['import_xls_file_format'];
                }
            }
            elseif($this->force_filename)
                $filename = $this->force_filename.'-'.date('Y-m-d-His').'.'.$this->file_type;

            return $filename;
        }

        /*
         * Called from model ie_pro_export.php
         * */
        function download_file_export() {
            if($this->profile != '' && $this->profile['import_xls_file_format'] == 'spreadsheet') {
                $data = array(
                    'status' => 'progress_export_finished',
                    'message' => sprintf($this->language->get('google_spreadsheet_export_finished'), $this->filename, 'https://docs.google.com/spreadsheets/d/'.$this->google_sheets_id)
                );
                $this->update_process($data);
            } else {
                if ($this->file_destiny == 'download') {
                    $this->update_process($this->language->get('progress_export_preparing_to_download'));

                    $downloadPath = ($this->file_type == 'xml' || $this->file_type == 'json') ? $this->get_force_download_link() : $this->get_download_link();

                    $data = array(
                        'status' => 'progress_export_finished',
                        'redirect' => $downloadPath,
                        'message' => $this->language->get('progress_export_finished')
                    );
                    $this->update_process($data);

                } elseif ($this->file_destiny == 'server') {
                    $this->update_process($this->language->get('progress_export_copying_file_to_destiny'));
                    $new_path = trim($this->profile['import_xls_file_destiny_server_path']);
                    $new_path .= substr($new_path, -1) != '/' ? '/' : '';

                    if (empty($new_path)) $this->exception($this->language->get('progress_export_empty_internal_server_path'));
                    if (!file_exists($new_path)) mkdir($new_path, 0775, true);

                    $filename = $this->_get_filename_with_sufix();
                    $final_path = $new_path . $filename;

                    copy($this->filename_path, $final_path);

                    $data = array(
                        'status' => 'progress_export_finished',
                        'message' => sprintf($this->language->get('progress_export_file_copied'), $final_path)
                    );
                    $this->update_process($data);
                } elseif ($this->file_destiny == 'external_server') {
                    $new_path = trim($this->profile['import_xls_ftp_path']);
                    $new_path .= substr($new_path, -1) != '/' ? '/' : '';

                    $filename = $this->profile['import_xls_ftp_file'] . '.' . $this->file_type;

                    if($this->force_filename_hard)
                        $filename = $this->force_filename_hard.'.'.$this->file_type;

                    if (empty($this->profile['import_xls_ftp_file'])) $this->exception($this->language->get('progress_export_ftp_empty_filename'));

                    $final_path = $new_path . $filename;

                    $connection = $this->ftp_open_connection();

                    try {
                        ftp_chdir($connection, $new_path);
                    } catch (Exception $e) {
                        ftp_mkdir($connection, $new_path);
                    }

                    $upload = ftp_put($connection, $final_path, $this->filename_path, FTP_BINARY);

                    if (!$upload)
                        $this->exception(sprintf($this->language->get('progress_export_ftp_error_uploaded'), $final_path));

                    ftp_close($connection);

                    $data = array(
                        'status' => 'progress_export_finished',
                        'message' => sprintf($this->language->get('progress_export_ftp_file_uploaded'), $final_path)
                    );
                    $this->update_process($data);
                }
            }
        }

        /*
         * Called from model ie_pro_import.php
         * */
        function upload_file_import() {
            $this->file_format = $this->profile == '' ? $this->file_format : $this->profile['import_xls_file_format'];
            $this->origin = $this->profile == '' ? 'manual' : $this->profile['import_xls_file_origin'];

            if (is_file( $this->assets_path.'model_ie_pro_file_pre_upload_file_import.php'))
                require( $this->assets_path.'model_ie_pro_file_pre_upload_file_import.php');

            if($this->origin == 'manual') {
                $file_tmp_name = array_key_exists('file', $_FILES) && array_key_exists('tmp_name', $_FILES['file']) ? $_FILES['file']['tmp_name'] : '';
                $file_name = array_key_exists('file', $_FILES) && array_key_exists('name', $_FILES['file']) ? $_FILES['file']['name'] : '';

                if(empty($file_name))
                    $this->exception($this->language->get('progress_import_error_empty_file'));
                if(empty($file_tmp_name))
                    $this->exception(sprintf($this->language->get('progress_import_error_filesize'), ini_get('upload_max_filesize')));

                $this->check_extension_profile($file_name);

                copy($file_tmp_name, $this->file_tmp_path);
            } elseif($this->origin == 'ftp') {
                $ftp_path = trim($this->profile['import_xls_ftp_path']);
                $ftp_path .= substr($ftp_path, -1) != '/' ? '/' : '';

                $filename = $this->profile['import_xls_ftp_file'] . '.' . $this->file_type;
                if (empty($this->profile['import_xls_ftp_file'])) $this->exception($this->language->get('progress_export_ftp_empty_filename'));
                $final_path = $ftp_path . $filename;

                $sftp = !empty($this->profile['import_xls_ftp_sftp']);

                if($sftp) {
                    if(!extension_loaded('ssh2'))
                        $this->exception($this->language->get('progress_export_ftp_sftp_error_ssh2'));
                    $connection = $this->sftp_open_connection($final_path);
                }
                else {
                    $connection = $this->ftp_open_connection();
                     if (!ftp_get($connection, $this->file_tmp_path, $final_path, FTP_BINARY)) {
                        $this->exception( $this->language->get('progress_export_ftp_error_downloading_file'));
                    }
                    ftp_close($connection);
                }
            } else {
                $file_url = !empty($this->profile['import_xls_url']) ? $this->profile['import_xls_url'] : $this->exception($this->language->get('progress_import_error_file_url_empty'));

                if($this->file_format != 'xml')
                    $this->check_extension_profile($file_url);

                $file_contents = $this->download_file_contents();

                file_put_contents($this->file_tmp_path, $file_contents);
            }

            if($this->profile != '' && $this->profile['import_xls_file_format'] == 'csv' && array_key_exists('import_xls_force_utf8', $this->profile) && !empty($this->profile['import_xls_force_utf8'])) {
                $file_content = file_get_contents($this->file_tmp_path);
                $file_content = iconv($this->profile['import_xls_force_utf8'], 'utf-8', $file_content);
                file_put_contents($this->file_tmp_path, $file_content);
            }
        }

        function check_extension_profile($file_name) {
            $extension_file =  strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if(!empty($extension_file) && strlen($extension_file) <= 4 && $extension_file != $this->file_format)
                $this->exception(sprintf($this->language->get('progress_import_error_extension'), $this->file_format, $extension_file));

        }

        function sftp_open_connection($file_path) {
            $server = trim($this->profile['import_xls_ftp_host']);
            $username = trim($this->profile['import_xls_ftp_username']);
            $password = trim($this->profile['import_xls_ftp_password']);
            $port = $this->profile['import_xls_ftp_port'] ? trim($this->profile['import_xls_ftp_port']) : 22;

            $connection = ssh2_connect($server, $port);

            if (!$connection)
                $this->exception($this->language->get('progress_export_ftp_sftp_errorconnect'));

            ssh2_auth_password($connection, $username, $password);
            $sftp = ssh2_sftp($connection);

            $file_contents = fopen("ssh2.sftp://".intval($sftp)."/".$file_path, 'r');
            file_put_contents($this->file_tmp_path, $file_contents);

            return true;
        }

        function ftp_open_connection() {
            $server = $this->profile['import_xls_ftp_host'];
            $username = $this->profile['import_xls_ftp_username'];
            $password = $this->profile['import_xls_ftp_password'];
            $port = $this->profile['import_xls_ftp_port'] ? $this->profile['import_xls_ftp_port'] : 21;

            $connection = ftp_connect($server, $port);
            if (!$connection)
                $this->exception($this->language->get('progress_export_ftp_error_connection'));
            $login = ftp_login($connection, $username, $password);

            if(array_key_exists('import_xls_ftp_passive_mode', $this->profile) && $this->profile['import_xls_ftp_passive_mode'])
                ftp_pasv($connection, true);

            if (!$login)
                $this->exception($this->language->get('progress_export_ftp_error_login'));

            return $connection;
        }

        function _get_filename_with_sufix() {
            $sufix = '';

            if($this->force_filename_hard)
                return $this->force_filename_hard.'.'.$this->file_type;

            if(!empty($this->profile['import_xls_file_destiny_server_file_name_sufix'])) {
                $sufix_type = $this->profile['import_xls_file_destiny_server_file_name_sufix'];
                $sufix = '-'.($sufix_type == 'date' ? date('Y-m-d') : date('Y-m-d-His'));
            }

            if (isset( $this->profile['import_xls_file_destiny_server_file_name'])) {
                $filename = $this->profile['import_xls_file_destiny_server_file_name'].$sufix.'.'.$this->file_type;
            } else {
                // Este es el caso de cuando se ejecuta un profile de migration desde CRON
                $filename = $this->filename;
            }

            return $filename;
        }

        function get_download_link() {
            $download_link = $this->path_tmp_public.$this->filename;
            return $download_link;
        }

        function get_force_download_link() {
            $download_link = html_entity_decode($this->url->link($this->real_extension_type.'/import_xls/download_file', $this->token_name.'=' . $this->session->data[$this->token_name].'&filename='.$this->filename, 'SSL'));
            return $download_link;
        }

        private function download_file_contents() {
            $url = $this->profile['import_xls_url'];
            if(!empty($this->profile['import_xls_force_refresh_cache'])) {
                $symbol = strpos($url, '?') !== false ? '&' : '?';
                $url .= $symbol.'force_refresh_cache='.time();
            }

            $url = str_replace("&amp;", "&", $url);

            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt( $ch, CURLOPT_HEADER, false);
            curl_setopt( $ch, CURLOPT_TIMEOUT, 300);
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);

            $http_authentication = $this->get_http_authentication();

            if ($http_authentication !== '') {
                $username = $this->profile['import_xls_http_username'];
                $password = $this->profile['import_xls_http_password'];
                curl_setopt( $ch, CURLOPT_USERPWD, "{$username}:{$password}");

                if ($http_authentication === 'digest') {
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                }
            }

            $result = curl_exec( $ch);

            if (curl_errno( $ch)) {
                $this->exception( sprintf( $this->language->get('curl_error'), curl_errno( $ch)));
            }

            $info = curl_getinfo( $ch);

            if ($info['http_code'] !== 200) {
                $this->exception( $result);
            }

            curl_close( $ch);

            return $result;
        }

        private function get_http_authentication() {
            return !empty($this->profile['import_xls_http_authentication'])
                   ? $this->profile['import_xls_http_authentication']
                   : '';
        }
    }
?>
