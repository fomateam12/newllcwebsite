<?php
    class ModelExtensionModuleIeProTabCrons extends ModelExtensionModuleIePro {

        public function __construct($registry) {
            parent::__construct($registry);
            $this->load->language($this->real_extension_type.'/ie_pro_tab_crons');

            $this->cron_path_php = DIR_APPLICATION.'model/extension/module/ie_cron_jobs.php';
            $this->cron_params = 'action=cron_start profile_id=PROFILEID';
        }

        public function get_fields() {
            $code = version_compare(VERSION, '2', '>=') ? 'code' : 'group';
            $this->db->query("UPDATE ".DB_PREFIX."setting SET `".$code."` = 'import_xls_ie_pro_cron' WHERE `".$code."` = 'ie_pro_cron'");
            $this->db->query("UPDATE ".DB_PREFIX."setting SET `key` = 'import_xls_ie_pro_cron_path_php' WHERE `key` = 'ie_pro_cron_path_php'");
            $this->db->query("UPDATE ".DB_PREFIX."setting SET `key` = 'import_xls_ie_pro_cron' WHERE `key` = 'ie_pro_cron'");

            $this->document->addScript('view/javascript/devmanextensions/ext_ie_pro/tab_crons.js?'.$this->get_ie_pro_version());
            $this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/tab_crons.css?'.$this->get_ie_pro_version());

            $profiles_select = $this->{$this->model_profile}->get_profiles('', '', true);
            $minutes = array();
            for($i = 0; $i <= 59; $i++) {
                $minutes[$i] = $i;
            }
            $hours = array();
            for($i = 0; $i <= 23; $i++) {
                $hours[$i] = $i+1;
            }
            $days = array();
            for($i = 1; $i <= 31; $i++) {
                $days[$i] = $i;
            }
            $months = array();
            for($i = 1; $i <= 12; $i++) {
                $months[$i] = $this->language->get('cron_month_'.$i);
            }
            $week_days = array();
            for($i = 0; $i <= 6; $i++) {
                $week_days[$i] = $this->language->get('cron_weekday_'.$i);
            }

            $fields = array(
                /*array(
                    'label' => $this->language->get('cron_php_path'),
                    'type' => 'text',
                    'name' => 'ie_pro_cron_path_php',
                    'after' => $this->get_remodal('cron_php_path_remodal', $this->language->get('cron_php_path_remodal_title'), $this->language->get('cron_php_path_remodal_description'), array('link' => 'cron_php_path_remodal_link')).$this->get_remodal('cron_config_remodal', $this->language->get('cron_config_remodal_title'), sprintf($this->language->get('cron_config_remodal_description'), $this->get_image_link('cron_plesk_configuration.jpg'), $this->cron_path_php), array('button_cancel' => false, 'remodal_options' => 'hashTracking: false')),
                ),*/
                array(
                    'after' => '<div class="cron_config_remodal_description" style="display:none;">'.sprintf($this->language->get('cron_config_remodal_description'), $this->get_image_link('cron_plesk_configuration.jpg')).'</div>'.$this->get_remodal('cron_config_remodal', $this->language->get('cron_config_remodal_title'), sprintf($this->language->get('cron_config_remodal_description'), $this->get_image_link('cron_plesk_configuration.jpg'), $this->cron_path_php), array('button_cancel' => false, 'remodal_options' => 'hashTracking: false')),
                    'type' => 'table_inputs',
                    'name' => 'ie_pro_cron',
                    'class' => 'config',
                    'theads' => array(
                        $this->language->get('thead_cron_cron'),
                        $this->language->get('thead_cron_status'),
                        $this->language->get('thead_cron_email'),
                        //$this->language->get('thead_cron_period'),
                        $this->language->get('thead_cron_configurator'),
                    ),
                    'model_row' => array(
                        array(
                            'type' => 'select',
                            'name' => 'profile_id',
                            'options' => $profiles_select,
                            'class' => 'profile_id'
                        ),
                        array(
                            'type' => 'boolean',
                            'name' => 'status'
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'email'
                        ),
                        /*array(
                            'multiples_fields' => array(
                                array(
                                    'type' => 'select',
                                    'noneSelectedText' => $this->language->get('cron_all_minutes'),
                                    'multiple' => true,
                                    'options' => $minutes,
                                    'name' => 'minutes',
                                    'class' => 'minutes'
                                ),
                                array(
                                    'type' => 'select',
                                    'noneSelectedText' => $this->language->get('cron_all_hours'),
                                    'multiple' => true,
                                    'options' => $hours,
                                    'name' => 'hours',
                                    'class' => 'hours'
                                ),
                                array(
                                    'type' => 'select',
                                    'noneSelectedText' => $this->language->get('cron_all_days'),
                                    'multiple' => true,
                                    'options' => $days,
                                    'name' => 'days',
                                    'class' => 'days'
                                ),
                                array(
                                    'type' => 'select',
                                    'noneSelectedText' => $this->language->get('cron_all_months'),
                                    'multiple' => true,
                                    'options' => $months,
                                    'name' => 'months',
                                    'class' => 'months'
                                ),
                                array(
                                    'type' => 'select',
                                    'noneSelectedText' => $this->language->get('cron_all_weekdays'),
                                    'multiple' => true,
                                    'options' => $week_days,
                                    'name' => 'week_days',
                                    'class' => 'week_days'
                                ),
                            ),
                        ),*/
                        array(
                            'type' => 'html_hard',
                            'html_code' => '<a href="javascript:{}" onclick="get_cron_config($(this))"><b>'.$this->language->get('cron_config_remodal_link').'</b></a>',
                            'name' => 'config_generator'
                        ),
                    )
                ),
                array(
                    'type' => 'button',
                    'label' => $this->language->get('cron_save'),
                    'text' => '<i class="fa fa-save"></i> '.$this->language->get('cron_save'),
                    'onclick' => 'save_cron_configuration();',
                ),
            );

            return $fields;
        }

        function _check_ajax_function($function_name) {
            if($function_name == 'cron_save_configuration') {
                $this->cron_save_configuration();
            }
        }

        function cron_save_configuration() {
            try {
                $this->validate_permiss();
                $config = $this->request->post;
                unset($config['import_xls_ie_pro_cron']['replace_by_number']);

                $profile_ids = array();

                foreach ($config['import_xls_ie_pro_cron'] as $key => $conf) {
                    if(!empty($conf['profile_id']))
                        $profile_ids[] = $conf['profile_id'];
                }

                if(count($profile_ids) !== count(array_unique($profile_ids)))
                    throw new Exception($this->language->get('cron_config_save_error_repeat_profiles'));

                $this->load->model('setting/setting');
                $config['import_xls_ie_pro_cron'] = array_values($config['import_xls_ie_pro_cron']);
                $this->model_setting_setting->editSetting('import_xls_ie_pro_cron', $config);

                $array_return = array('error' => false, 'message' => $this->language->get('cron_config_save_sucessfully'));
            } catch (Exception $e) {
                $array_return['error'] = true;
                $array_return['message'] = $e->getMessage();
            }

            echo json_encode($array_return); die;
        }

        function _send_custom_variables_to_view($variables) {
            $variables['cron_save_configuration_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=cron_save_configuration', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['cron_error_profile_id'] = $this->language->get('cron_error_profile_id');
            $variables['cron_error_path_to_php'] = $this->language->get('cron_error_path_to_php');
            $variables['cron_command_copied'] = $this->language->get('cron_command_copied');
            $variables['cron_command'] = 'TIMECONFIG PHP-PATH '.$this->cron_path_php.' '.$this->cron_params;
            $variables['cron_main_path'] = $this->cron_path_php;
            $variables['cron_params'] = $this->cron_params;
            $variables['cron_link_to_exec_now'] = HTTPS_SERVER.'model/extension/module/ie_cron_jobs.php?action=cron_start&profile_id=PROFILEID';
            $variables['cron_backup_path'] = "{$this->root_path}iepro/backups";
            return $variables;
        }

        function check_profile($profile) {
            $type = $profile['type'];
            $destiny = array_key_exists('import_xls_file_destiny', $profile['profile']) ? $profile['profile']['import_xls_file_destiny'] : '';
            $origin = array_key_exists('import_xls_file_origin', $profile['profile']) ? $profile['profile']['import_xls_file_origin'] : '';
            $format = $profile['profile']['import_xls_file_format'];

            if($type == 'export' && ($destiny == 'download' && $format != 'spreadsheet'))
                throw new Exception('Export profile not compatible with CRON, this profile will download exported file from web browser.');
            elseif($type == 'import' && ($origin == 'manual' && $format != 'spreadsheet'))
                throw new Exception('Import profile not compatible with CRON, this profile will expected that file was uploaded manually.');

            $this->load->model('setting/setting');
            $cron_config = $this->model_setting_setting->getSetting('import_xls_ie_pro_cron');

            $status = false;
            $found = false;
            foreach ($cron_config['import_xls_ie_pro_cron'] as $key => $config) {
                $profile_id = array_key_exists('profile_id', $config) ? $config['profile_id'] : '';
                $status = array_key_exists('status', $config) && $config['status'];
                $found_temp = $profile_id == $profile['id'];
                $found = $found_temp ? true : $found;
                if($profile_id == $profile['id'] && $status) {
                    $cron_config = $config;
                    $status = true;
                    break;
                }
            }

            if(!$found)
                throw new Exception(sprintf($this->language->get('cron_error_not_found'), $profile['name']));

            if(!$status)
                throw new Exception(sprintf($this->language->get('cron_error_disabled'), $profile['name']));

            $this->cron_email_notification = $cron_config['email'];
        }

        public function email_report($message) {
            if($this->cron_email_notification != '') {
                if(version_compare(VERSION, '3', '<'))
                    $mail = new Mail();
                else
                    $mail = new Mail($this->config->get('config_mail_engine'));

                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

                $mail->setTo($this->cron_email_notification);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode('CRON REPORT', ENT_QUOTES, 'UTF-8'));

                $profile_name = isset( $this->profile['profile'])
                                ? $this->profile['profile']['import_xls_profile_name']
                                : $this->profile['import_xls_profile_name'];

                $message = "<strong>Profile ID</strong>: <em>{$this->profile_id}</em><br><strong>Profile Name</strong>: <em>{$profile_name}</em><br><hr>" . $message;
                $mail->setHtml($message);
                $mail->send();
            }
        }
    }
?>