<?php
class ControllerExtensionModuleDiscountsPackDiscountLoyalty extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/loyalty_discount');

		$this->document->setTitle($this->language->get('heading_title'));
		/* Bootstrap Select CDN */
		$this->document->addStyle("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css");
		$this->document->addScript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js");
		
		$this->load->model('extension/module/discount');
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_discount'] = $this->language->get('text_discount');
		$data['text_fixed'] = $this->language->get('text_fixed');
		$data['text_percentage'] = $this->language->get('text_percentage');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
		$data['text_select_all'] = $this->language->get('text_select_all');
		$data['text_unselect_all'] = $this->language->get('text_unselect_all');
		
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_ordertotal'] = $this->language->get('entry_ordertotal');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_discount'] = $this->language->get('entry_discount');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_type'] = $this->language->get('entry_type');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_remove'] = $this->language->get('button_remove');
		
		$data['error_permission'] = $this->language->get('error_permission');	
		
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
			'href' => $this->url->link('extension/module/discounts_pack/discount_loyalty', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['permission'] = $this->user->hasPermission('modify', 'extension/module/discounts_pack/discount_loyalty') ? 1 : 0;
		
		$data['cancel'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['back_link'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		
		$this->model_extension_module_discount->checkTableExist('loyalty');
		
		if (isset($this->request->post['total_loyalty_discount_status'])) {
			$data['loyalty_discount_status'] = $this->request->post['total_loyalty_discount_status'];
		} else {
			$data['loyalty_discount_status'] = $this->config->get('total_loyalty_discount_status');
		}

		if (isset($this->request->post['total_loyalty_discount_sort_order'])) {
			$data['loyalty_discount_sort_order'] = $this->request->post['total_loyalty_discount_sort_order'];
		} else {
			$data['loyalty_discount_sort_order'] = $this->config->get('total_loyalty_discount_sort_order');
		}
		
		if (version_compare(VERSION, '2.1', '>=')) {
			$this->load->model('customer/customer_group');
			$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		} else {
			$this->load->model('sale/customer_group');
			$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
		}
		
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		
		$discounts = $this->model_extension_module_discount->getAllDiscounts('loyalty');
		
		if (isset($this->request->post['loyalty_discount'])) {
			$loyalty_discounts = $this->request->post['loyalty_discount'];
		} elseif (isset($discounts)) {
			$loyalty_discounts = $discounts;
		} else {
			$loyalty_discounts = array();
		}

		$data['loyalty_discounts'] = array();
		
		foreach ($loyalty_discounts as $loyalty_discount) {
			$data['loyalty_discounts'][] = array(
				'loyalty_discount_id' => $loyalty_discount['loyalty_discount_id'],
				'status'			=> $loyalty_discount['status'],
				'ordertotal'		=> $loyalty_discount['ordertotal'],
				'customer_group_id' => $loyalty_discount['customer_group_id'],
				'priority'          => $loyalty_discount['priority'],
				'order_status'		=> explode(',',$loyalty_discount['order_status']),
				'discount'        	=> $loyalty_discount['percentage'],
				'date_start'        => ($loyalty_discount['date_start'] != '0000-00-00') ? $loyalty_discount['date_start'] : '',
				'date_end'          => ($loyalty_discount['date_end'] != '0000-00-00') ? $loyalty_discount['date_end'] : ''
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/discount_loyalty', $data));
	}
	
	public function saveDiscount() {
		
		$json = array();
		$this->load->language('extension/module/loyalty_discount');
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			
			$this->load->model('setting/setting');
			$this->load->model('extension/module/discount');
			
			parse_str(htmlspecialchars_decode($this->request->post['setting']), $settings);
			
			$this->model_setting_setting->editSetting('total_loyalty_discount', $settings);
			
			if(!empty($this->request->post['loyalty_discount'])) {
			
				parse_str(htmlspecialchars_decode($this->request->post['loyalty_discount']), $discount_data);

				$this->model_extension_module_discount->setDiscount($discount_data['loyalty_discount'], 'loyalty');
						
			} else {
			
				$this->model_extension_module_discount->setDiscount(NULL, 'loyalty');
				
			}
			
			$json['success'] = $this->language->get('text_success');

		} else {
			$json['error'] = $this->language->get('error_warning');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}
	
	public function activate() {
		$this->load->language('extension/module/loyalty_discount');
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			$this->db->query("UPDATE `". DB_PREFIX . "loyalty_discount` SET `status` = '1' WHERE `loyalty_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_activated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function deactivate() {
		$this->load->language('extension/module/loyalty_discount');
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			$this->db->query("UPDATE `". DB_PREFIX . "loyalty_discount` SET `status` = '0' WHERE `loyalty_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_deactivated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function install() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX ."loyalty_discount` (`loyalty_discount_id` int(11) NOT NULL AUTO_INCREMENT, `ordertotal` int(11) NOT NULL, `customer_group_id` int(11) NOT NULL, `priority` int(5) NOT NULL DEFAULT '1', `order_status` VARCHAR(96) NOT NULL, ";
		$sql .= "`percentage` decimal(15,4) NOT NULL DEFAULT '0.0000',`status` INT  NOT NULL DEFAULT '1', `date_start` date NOT NULL DEFAULT '0000-00-00', `date_end` date NOT NULL DEFAULT '0000-00-00', PRIMARY KEY (`loyalty_discount_id`), KEY `ordertotal` (`ordertotal`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
	
		$this->db->query($sql);
	
		$this->db->query("INSERT INTO `". DB_PREFIX ."extension` (`extension_id`, `type`, `code`) VALUES (NULL, 'total', 'loyalty_discount'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_loyalty_discount', 'total_loyalty_discount_sort_order', '2', '0'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_loyalty_discount', 'total_loyalty_discount_status', '1', '0');");
	}
	
	public function uninstall() {
		$key = 'loyalty';
				
		$this->db->query("DROP TABLE `" . DB_PREFIX . $key . "_discount`");
		$this->db->query("DELETE FROM `". DB_PREFIX ."extension` WHERE `code` = '" . $key . "_discount';");
		$this->db->query("DELETE FROM `". DB_PREFIX ."setting` WHERE `code` = 'total_" . $key . "_discount';");
		
	}
	
}