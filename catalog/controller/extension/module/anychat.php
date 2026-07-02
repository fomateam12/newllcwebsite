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

class ControllerExtensionModuleAnychat extends Controller {
    public function index() {
        $this->load->language('extension/module/anychat');
        $this->load->model('extension/module/anychat');
        
        $settings = $this->model_extension_module_anychat->getSettings();
        
        if (isset($settings['anychat_widget_id']) && !empty($settings['anychat_widget_id']) && $this->isAllowed($settings)) {
            $data = array(
                'settings' => $settings,
            );
            return $this->load->view('extension/module/anychat', $data);
        }
        return null;
    }
    
    public function isAllowed($settings)
    {
        if (!isset($settings['anychat_sandbox']) || $settings['anychat_sandbox'] == 0) {
            return true;
        }
        $ip = $this->getCurrentIp();
        $allowedIps = explode(PHP_EOL, $settings['anychat_allowed_ips']);
        return in_array($ip, $allowedIps);
    }
    
    public function eventCatalogScript(&$route, &$data, &$output)
    {
        $this->load->model('extension/module/anychat');
        $settings = $this->model_extension_module_anychat->getSettings();
        if (isset($settings['anychat_api_key']) && !empty($settings['anychat_api_key']) && isset($settings['anychat_widget_id']) && !empty($settings['anychat_widget_id'])) {
            $data['settings'] = $settings;
            $data['customerLoggedIn'] = false;
            if ($this->customer->isLogged()) {
                $data['customerLoggedIn'] = true;
                $data['customerName'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
                $data['customerEmail'] = $this->customer->getEmail();
            }
            $output .= $this->load->view('extension/module/anychat', $data);
            return $output;
        }
    }
    
    public function getCurrentIp()
    {
        $check = array(
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        );
        
        foreach ($check as $c) {
            if (isset($this->request->server[$c]) && !empty($this->request->server[$c]) && $this->request->server[$c] != '127.0.0.1') {
                return $this->request->server[$c];
            }
        }
        return null;
    }
}