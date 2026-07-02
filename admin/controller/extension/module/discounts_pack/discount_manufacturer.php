<?php
class ControllerExtensionModuleDiscountsPackDiscountManufacturer extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/manufacturer_discount');
		
		$this->document->setTitle($this->language->get('heading_title'));
		/* Bootstrap Select CDN */
		$this->document->addStyle("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css");
		$this->document->addScript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js");

		$this->load->model('extension/module/discount');
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_discount'] = $this->language->get('text_discount');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_manufacturer'] = $this->language->get('entry_manufacturer');
		$data['entry_percentage'] = $this->language->get('entry_percentage');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_qty'] = $this->language->get('entry_qty');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$this->load->language('module/discounts_pack');
		$data['entry_override_special_price'] = $this->language->get('entry_override_special_price');
		$data['entry_override_discount_price'] = $this->language->get('entry_override_discount_price');
		
		$data['help_override_special_price'] = $this->language->get('help_override_special_price');
		$data['help_override_discount_price'] = $this->language->get('help_override_discount_price');
		
		
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_yes'] = $this->language->get('button_yes');
		$data['button_no'] = $this->language->get('button_no');	
		
		$data['error_permission'] = $this->language->get('error_permission');	
		
		$data['options'] = array('default', 'exclusive', 'override');
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->success)) {
			$data['success'] = $this->success;
		} else {
			$data['success'] = '';
		}
		
		$url = '';
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_discounts'),
			'href' => $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/discounts_pack/discount_manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['permission'] = $this->user->hasPermission('modify', 'extension/module/discounts_pack/discount_manufacturer') ? 1 : 0;
		
		$data['cancel'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['back_link'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		
		if (isset($this->request->post['total_manufacturer_discount_status'])) {
			$data['manufacturer_discount_status'] = $this->request->post['total_manufacturer_discount_status'];
		} else {
			$data['manufacturer_discount_status'] = $this->config->get('total_manufacturer_discount_status');
		}

		if (isset($this->request->post['total_manufacturer_discount_sort_order'])) {
			$data['manufacturer_discount_sort_order'] = $this->request->post['total_manufacturer_discount_sort_order'];
		} else {
			$data['manufacturer_discount_sort_order'] = $this->config->get('total_manufacturer_discount_sort_order');
		}
		
		$this->load->model('catalog/manufacturer');
		
		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
		
		if (version_compare(VERSION, '2.1', '>=')) {
			$this->load->model('customer/customer_group');
			$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		} else {
			$this->load->model('sale/customer_group');
			$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
		}
		
		$discounts = $this->model_extension_module_discount->getAllDiscounts('manufacturer');
		
		if (isset($this->request->post['manufacturer_discount'])) {
			$manufacturer_discounts = $this->request->post['manufacturer_discount'];
		} elseif (isset($discounts)) {
			$manufacturer_discounts = $discounts;
		} else {
			$manufacturer_discounts = array();
		}

		$data['manufacturer_discounts'] = array();
		
		foreach ($manufacturer_discounts as $manufacturer_discount) {
			$data['manufacturer_discounts'][] = array(
				'manufacturer_discount_id' => $manufacturer_discount['manufacturer_discount_id'],
				'status'			=> $manufacturer_discount['status'],
				'manufacturer_id'	=> $manufacturer_discount['manufacturer_id'],
				'customer_group_id' => $manufacturer_discount['customer_group_id'],
				'priority'          => $manufacturer_discount['priority'],
				'percentage'        => $manufacturer_discount['percentage'],
				'qty'				=> $manufacturer_discount['qty'],
				'date_start'        => ($manufacturer_discount['date_start'] != '0000-00-00') ? $manufacturer_discount['date_start'] : '',
				'date_end'          => ($manufacturer_discount['date_end'] != '0000-00-00') ? $manufacturer_discount['date_end'] : ''
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/discount_manufacturer', $data));
	}
	
	public function saveDiscount() {
		
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->language('extension/module/manufacturer_discount');
			$this->load->model('setting/setting');
			$this->load->model('extension/module/discount');
			
			parse_str(htmlspecialchars_decode($this->request->post['setting']), $settings);
			
			$this->model_setting_setting->editSetting('total_manufacturer_discount', $settings);
			
			if(!empty($this->request->post['manufacturer_discount'])) {
				
				parse_str(htmlspecialchars_decode($this->request->post['manufacturer_discount']), $discount_data);

				$this->model_extension_module_discount->setDiscount($discount_data['manufacturer_discount'], 'manufacturer');		
			} else {
				$this->model_extension_module_discount->setDiscount(NULL, 'manufacturer');
			}
			
			$json['success'] = $this->language->get('text_success');

		} else {
			$json['error'] = $this->language->get('error_warning');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}
	
	public function activate() {
		$this->load->language('extension/module/manufacturer_discount');
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			$this->db->query("UPDATE `". DB_PREFIX . "manufacturer_discount` SET `status` = '1' WHERE `manufacturer_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_activated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function deactivate() {
		$this->load->language('extension/module/manufacturer_discount');
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			$this->db->query("UPDATE `". DB_PREFIX . "manufacturer_discount` SET `status` = '0' WHERE `manufacturer_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_deactivated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function install() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX ."manufacturer_discount` (`manufacturer_discount_id` int(11) NOT NULL AUTO_INCREMENT, `manufacturer_id` int(11) NOT NULL, `customer_group_id` int(11) NOT NULL, `priority` int(5) NOT NULL DEFAULT '1', ";
		$sql .= "`percentage` decimal(15,4) NOT NULL DEFAULT '0.0000', `qty` int(1) NOT NULL DEFAULT '0',`status` INT  NOT NULL DEFAULT '1', `date_start` date NOT NULL DEFAULT '0000-00-00', `date_end` date NOT NULL DEFAULT '0000-00-00', PRIMARY KEY (`manufacturer_discount_id`), KEY `manufacturer_id` (`manufacturer_id`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
	
		$this->db->query($sql);
	
		$this->db->query("INSERT INTO `". DB_PREFIX ."extension` (`extension_id`, `type`, `code`) VALUES (NULL, 'total', 'manufacturer_discount'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_manufacturer_discount', 'total_manufacturer_discount_sort_order', '2', '0'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_manufacturer_discount', 'total_manufacturer_discount_status', '1', '0');");
	}
	
	public function uninstall() {
		$key = 'manufacturer';
				
		$this->db->query("DROP TABLE `" . DB_PREFIX . $key . "_discount`");
		$this->db->query("DELETE FROM `". DB_PREFIX ."extension` WHERE `code` = '" . $key . "_discount';");
		$this->db->query("DELETE FROM `". DB_PREFIX ."setting` WHERE `code` = 'total_" . $key . "_discount';");
		
	}
	
}