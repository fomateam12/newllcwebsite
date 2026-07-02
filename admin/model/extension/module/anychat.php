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

class ModelExtensionModuleAnychat extends Model
{
    private $version = '1.0.1';

    protected $fields = array(
        'sandbox',
        'allowed_ips',
        'disable_admin',
        'customerinfo',
        'api_key',
        'widget_id',
        'integration_type'
    );
    
    public function install()
    {
        $this->load->model('setting/setting');
        $this->installDefaults();
        return $this->model_setting_setting->editSetting('module_anychat', array(
            'module_anychat_status' => 1
        ));
    }
    
    public function installDefaults()
    {
        $data = array(
            $this->getFieldName('sandbox') => 0,
            $this->getFieldName('allowed_ips') => $this->getCurrentIp(),
            $this->getFieldName('disable_admin') => 0,
            $this->getFieldName('customerinfo') => 1,
            $this->getFieldName('api_key') => '',
            $this->getFieldName('widget_id') => '',
            $this->getFieldName('integration_type') => 2
        );
        return $this->setSettings($data);
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
    
    public function uninstall()
    {
        $this->load->model('setting/setting');
        return $this->model_setting_setting->deleteSetting('anychat') && $this->model_setting_setting->deleteSetting('module_anychat');
    }
    
    public function getPluginVersion()
    {
        return $this->version;
    }
    
    public function getSettings()
    {
        $this->load->model('setting/setting');
        return $this->model_setting_setting->getSetting('anychat');
    }
    
    public function setSettings($data)
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('anychat', $data);
    }
    
    public function getFieldLabels()
    {
        $this->load->language('extension/module/anychat');
        $data = array();
        foreach ($this->fields as $field) {
            $data[$field] = $this->language->get($field);
        }
        return $data;
    }
    
    public function getFieldName($name)
    {
        return 'anychat_' . $name;
    }
}
