<?php
/**
* 2012-2022 Areama
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@areama.net so we can send you a copy immediately.
*
*  @author    Areama <contact@areama.net>
*  @copyright 2022 Areama
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Areama
*/

class ControllerExtensionModuleAnychat extends Controller
{
    private $version = '1.0.1';
    private $error = array();
    
    public function index()
    {
        $this->load->language('extension/module/anychat');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/anychat');
        $this->postProcess();
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['errors'] = $this->error;
        
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/anychat', 'user_token=' . $this->session->data['user_token'], true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/anychat', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
            );
        }

        if (!isset($this->request->get['module_id'])) {
            $data['action'] = $this->url->link('extension/module/anychat', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('extension/module/anychat', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
        }

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['settings'] = $this->model_extension_module_anychat->getSettings();
        $data['labels'] = $this->model_extension_module_anychat->getFieldLabels();
        $data['activeTab'] = $this->getActiveTab();
        
        array_merge($data, $this->loadLangText(array(
            'allowed_ips_helper',
            'general_config',
            'help',
            'about',
            'menu_button',
            'live_chat_widget'
        )));
        
        $data['helpTabContent'] = $this->load->view('extension/module/anychat_help', $this->loadLangText(array(
            'help',
            'ahychat_h1',
            'ahychat_h2',
            'ahychat_h3',
            'ahychat_h4',
            'ahychat_h5',
            'ahychat_h6',
            'ahychat_h7',
            'ahychat_h8',
            'ahychat_h9',
            'ahychat_h10',
            'ahychat_h11',
            'ahychat_h12'
        )));
        
        $aboutLangData = $this->loadLangText(array(
            'anychat_about1',
            'anychat_about3',
            'anychat_about4',
            'anychat_about5',
            'anychat_about6',
        ));
        
        $aboutLangData['anychat_about2'] = sprintf($this->language->get('anychat_about2'), $this->version);
        
        $data['aboutTabContent'] = $this->load->view('extension/module/anychat_about', $aboutLangData);
        
        $data['current_ip'] = $this->model_extension_module_anychat->getCurrentIp();
        
        $output = $this->load->view('extension/module/anychat', $data);
        $this->response->setOutput($output);
    }
    
    public function loadLangText($data)
    {
        $res = array();
        foreach ($data as $key) {
            $res[$key] = $this->language->get($key);
        }
        return $res;
    }
    
    public function getActiveTab()
    {
        $activeTab = 'anychat-general-tab';
        
        return $activeTab;
    }
    
    public function postProcess()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('extension/module/anychat');
            $this->model_extension_module_anychat->setSettings($this->request->post);
        }
    }
    
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/anychat')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['anychat_api_key']) || !$this->request->post['anychat_api_key']) {
            $this->error['anychat_api_key'] = $this->language->get('error_api_key');
        }
        if (!isset($this->request->post['anychat_widget_id']) || !$this->request->post['anychat_widget_id']) {
            $this->error['anychat_widget_id'] = $this->language->get('error_widget_id');
        }

        return !$this->error;
    }
    
    public function install()
    {
        $this->load->model('extension/module/anychat');
        $this->load->model('setting/event');
        $this->config->set('module_anychat_status', 1);
        $this->model_setting_event->addEvent(
            'anychat_admin_script',
            'admin/view/common/header/after',
            'extension/module/anychat/eventAdminScript'
        );
        
        $this->model_setting_event->addEvent(
            'anychat_catalog_script',
            'catalog/view/common/header/after',
            'extension/module/anychat/eventCatalogScript'
        );
        
        return $this->model_extension_module_anychat->install();
    }
    
    public function eventAdminScript(&$route, &$data, &$output)
    {
        if (isset($data['logged']) && $data['logged']) {
            $this->load->model('extension/module/anychat');
            $settings = $this->model_extension_module_anychat->getSettings();
            if ((isset($settings['anychat_disable_admin']) && !$settings['anychat_disable_admin']) || !isset($settings['anychat_disable_admin'])) {
                if (isset($settings['anychat_api_key']) && !empty($settings['anychat_api_key']) && isset($settings['anychat_widget_id']) && !empty($settings['anychat_widget_id'])) {
                    $data['settings'] = $settings;
                    $moduleAdminUrl = $this->url->link('extension/module/anychat', array('user_token' => $this->session->data['user_token']), true);
                    $moduleAdminUrl = str_replace('&amp;', '&', $moduleAdminUrl);
                    $data['moduleAdminUrl'] = $moduleAdminUrl;
                    $output .= $this->load->view('extension/module/anychat_admin', $data);
                    return $output;
                }
            }
        }
    }
    
    public function uninstall()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('anychat_admin_script');
        $this->model_setting_event->deleteEventByCode('anychat_catalog_script');
        $this->config->set('module_anychat_status', 0);
        $this->load->model('extension/module/anychat');
        $this->model_extension_module_anychat->uninstall();
        return true;
    }
}
