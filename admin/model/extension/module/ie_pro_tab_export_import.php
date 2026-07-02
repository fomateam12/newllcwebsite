<?php
    class ModelExtensionModuleIeProTabExportImport extends ModelExtensionModuleIePro
    {
        public function __construct($registry) {
            parent::__construct($registry);
            $this->load->language($this->real_extension_type.'/ie_pro_tab_export_import');
        }

        public function get_fields() {
            $this->document->addStyle($this->api_url.'/opencart_admin/ext_ie_pro/css/tab_export_import.css?'.$this->get_ie_pro_version());
        		$this->document->addScript( 'view/javascript/devmanextensions/ext_ie_pro/tab_export_import.js' /*?'.$this->get_ie_pro_version()*/);

            $fields_load_profile = array(
                array(
                    'type' => 'html_hard',
                    'html_code' => '<div class="container_launch_profile">'
                ),
                    array(
                        'type' => 'html_hard',
                        'html_code' => '<div class="row">'
                    ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '<div class="col-md-6"><span class="main_title"><span class="retina-space-2382"></span>'.$this->language->get('export_import_launch_profile_main_title').'</span><p>'.$this->language->get('export_import_launch_profile_description').'</p></div>'
                        ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '<div class="col-md-6 container_profile_selector">'
                        ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<label>'.$this->language->get('export_import_profile_load_select').'</label>'
                            ),

                            array(
                                'label' => false,
                                'type' => 'select',
                                'options' => $this->profiles_select_import_export,
                                'name' => 'profiles',
                                'onchange' => 'check_profile_selected()',
                                'after' => '<div style="clear: both; height: 4px;"></div><a href="javascript:{}" onclick="launch_profile(true)">'.$this->language->get('export_import_download_empy_file').'</a>'
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="col-md-6 container_input_export_from">'
                            ),
                                array(
                                    'type' => 'text',
                                    'class' => 'input_profile_export',
                                    'name' => 'from',
                                    'label' => false,
                                    'placeholder' =>$this->language->get('export_import_profile_input_from'),
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '<div class="col-md-6 container_input_export_to">'
                            ),
                                array(
                                    'type' => 'text',
                                    'class' => 'input_profile_export',
                                    'name' => 'to',
                                    'label' => false,
                                    'placeholder' =>$this->language->get('export_import_profile_input_to'),
                                ),
                            array(
                                'type' => 'html_hard',
                                'html_code' => '</div>'
                            ),
                            array(
                                'type' => 'button',
                                'class' => 'input_profile_import',
                                'label' => $this->language->get('export_import_profile_upload_file'),
                                'text' => '<i class="fa fa-upload"></i> '.$this->language->get('export_import_profile_upload_file').'<span></span>',
                                'onclick' => "$(this).next('input').click();",
                                'help' =>$this->language->get('export_import_profile_upload_file_help'),
                                'after' => '<input onchange="readURL($(this));" name="upload" type="file" style="display:none;">'
                            ),
                            array(
                                'type' => 'button',
                                'class' => 'clear_style icon_right danger disabled',
                                'label' => false,
                                'text' => $this->language->get('export_import_start_button').'<span class="retina-space-2382"></span>',
                                'onclick' => 'launch_profile();',
                                'class_container' => 'launch_profile',
                                'after' => (!$this->isdemo ? '<br>'.$this->get_remodal('export_import_remodal_server_config', $this->language->get('export_import_remodal_server_config_title'), $this->language->get('export_import_remodal_server_config_description'), array('link' => '<b style="color:#f00;">'.$this->language->get('export_import_remodal_server_config_link').'</b>',  'button_cancel' => true, 'remodal_options' => 'hashTracking: false')) : '').$this->get_remodal('export_import_remodal_process', $this->language->get('export_import_remodal_process_title'), '', array('subtitle' => $this->language->get('export_import_remodal_process_subtitle'), 'button_close' => false, 'button_cancel' => true,  'remodal_options' => 'closeOnOutsideClick: false, closeOnEscape: false, hashTracking: false, closeOnCancel: false'))
                            ),
                        array(
                            'type' => 'html_hard',
                            'html_code' => '</div>'
                        ),
                    array(
                        'type' => 'html_hard',
                        'html_code' => '</div>'
                    ),
                array(
                    'type' => 'html_hard',
                    'html_code' => '</div>'
                ),
            );

            $fields_create_profile = $this->model_extension_module_ie_pro_tab_profiles->get_fields();

            $fields = array_merge($fields_load_profile, $fields_create_profile);

            if($this->is_t) {
                $legend = array(
                    'type' => 'html_hard',
                    'html_code' => '<div class="alert alert-danger" style="margin-bottom: 0px;"><i class="fa fa-exclamation-circle"></i>'.sprintf($this->language->get('trial_operation_restricted'),$this->is_t_elem).'<button type="button" class="close" data-dismiss="alert">×</button></div>'
                );
                array_unshift($fields, $legend);
            }
            return $fields;
        }

        public function _send_custom_variables_to_view($variables) {
            $variables['launch_profile_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=launch_profile', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['clean_progress_url'] = htmlspecialchars_decode($this->url->link($this->real_extension_type.'/'.$this->extension_name.'&ajax_function=clean_previous_process', $this->token_name.'=' . $this->session->data[$this->token_name], 'SSL'));
            $variables['progress_route'] = htmlspecialchars_decode($this->path_progress_public);
            return $variables;
        }

        public function _check_ajax_function($function_name) {
            if($function_name == 'launch_profile') {
                $this->launch_profile();
            }else if($function_name == 'clean_previous_process') {
                $this->create_progress_file();
                $this->ajax_die('Process created');
            }
        }

        public function launch_profile() {
            set_error_handler(array(&$this, 'customCatchError'));

            if(function_exists("error_reporting"))
                register_shutdown_function(array(&$this, 'fatalErrorShutdownHandler'));

            try {
                $profile_id = array_key_exists('profile_id', $this->request->post) && !empty($this->request->post['profile_id']) ? $this->request->post['profile_id'] : '';

                if(empty($profile_id))
                    $this->exception($this->language->get('export_import_error_empty_profile'));

                $profile = $this->{$this->model_profile}->load($profile_id, true);
                if(empty($profile))
                    $this->exception($this->language->get('export_import_error_profile_not_found'));

                $this->profile = $profile;
                $this->profile_id = $profile_id;
                $empty_profile = array_key_exists('empty', $this->request->post) && $this->request->post['empty'] == 'true';

                if($empty_profile) {
                    $this->load->model('extension/module/ie_pro_export');
                    $profile['profile']['import_xls_file_destiny'] = 'download';
                    $profile['type'] = $profile['profile']['profile_type'] = 'export';
                    $this->profile = $profile;
                    $this->request->post['from'] = 1;
                    $this->request->post['to'] = 2;
                    $this->load->model('extension/module/ie_pro_export');
                    $this->update_process($this->language->get('progress_export_starting_process'));
                    $this->model_extension_module_ie_pro_export->export($this->profile);
                } else {
                    if ($this->is_cron_task) {
                        $this->load->model('extension/module/ie_pro_tab_crons');
                        $this->create_progress_file();
                        $this->model_extension_module_ie_pro_tab_crons->check_profile($this->profile);
                    }

                    switch ($this->profile['type']) {
                        case 'export':
                            $this->load->model('extension/module/ie_pro_export');
                            $this->update_process($this->language->get('progress_export_starting_process'));
                            $this->model_extension_module_ie_pro_export->export($this->profile);
                            break;

                        case 'import':
                            $this->db->query("START TRANSACTION");
                            $this->load->model('extension/module/ie_pro_import');
                            $this->update_process($this->language->get('progress_import_starting_process'));
                            $this->model_extension_module_ie_pro_import->import($this->profile);
                            break;

                        case 'migration-export':
                            $this->load->model('extension/module/ie_pro_tab_migrations');
                            $this->update_process($this->language->get('progress_import_starting_process'));
                            $this->model_extension_module_ie_pro_tab_migrations->migration_export( $this->profile['profile']);
                            break;
                    }
                }
            } catch (Exception $e) {
                if(isset($profile) && $profile == 'import')
                    $this->db->query("ROLLBACK");

                $data = array(
                    'status' => 'error',
                    'message' => $e->getMessage(),
                );

                $this->update_process($data);
            }
            restore_error_handler();
        }
    }
?>
