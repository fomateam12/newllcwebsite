<?php
class ControllerExtensionModuleDiscountsPackDiscountCategory extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/category_discount');
		

		$this->document->setTitle($this->language->get('heading_title'));
		
		/* Bootstrap Select CDN */
		$this->document->addStyle("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css");
		$this->document->addScript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js");

		$this->load->model('extension/module/discount');
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_discount'] = $this->language->get('text_discount');
		
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_affect'] = $this->language->get('entry_affect');
		$data['entry_yes'] = $this->language->get('entry_yes');
		$data['entry_no'] = $this->language->get('entry_no');	
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_category'] = $this->language->get('entry_category');
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
		$data['help_enheritance'] = $this->language->get('help_enheritance');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_remove'] = $this->language->get('button_remove');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
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
			'href' => $this->url->link('extension/module/discounts', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/discounts_pack/discount_category', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['permission'] = $this->user->hasPermission('modify', 'extension/module/discounts_pack/discount_category') ? 1 : 0;
		
		$data['cancel'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['back_link'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		
		if (isset($this->request->post['total_category_discount_status'])) {
			$data['category_discount_status'] = $this->request->post['total_category_discount_status'];
		} else {
			$data['category_discount_status'] = $this->config->get('total_category_discount_status');
		}

		if (isset($this->request->post['total_category_discount_sort_order'])) {
			$data['category_discount_sort_order'] = $this->request->post['total_category_discount_sort_order'];
		} else {
			$data['category_discount_sort_order'] = $this->config->get('total_category_discount_sort_order');
		}
		
		$this->load->model('catalog/category');
		
		$categories = $this->model_catalog_category->getCategories();
		
		$data['categories'] = array();
		
		$sort_order = array();
		$name = array();
		$parent = array();
		
		foreach ($categories as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
			$name[$key] = $value['name'];
			$parent[$key] = $value['parent_id'];
		}
		
		array_multisort($parent, SORT_ASC, $name, SORT_ASC, $sort_order, SORT_ASC, $categories);
		
		$parent_categories = array();

		foreach ($categories as $key => $category) {
			if (empty($category['parent_id'])) {
				$parent_categories[$category['category_id']] = $category;
				$parent_categories[$category['category_id']]['children'] = array();
			}
		}
		
		foreach ($categories as $key => $category) {
			if (!array_key_exists($category['category_id'], $parent_categories)) {
				$category['path'] = $this->model_catalog_category->getCategoryPath($category['category_id']);
				
				/* Sort array to make sure the sort order is correct - top parent first */
				$level = array();
				foreach($category['path'] as $key => $value) {
					$level[$key] = $value['level'];
				}
				array_multisort($level, SORT_ASC, $category['path']);
				
				$parent_category = reset($category['path']);
				if(empty($parent_category['level']) && $parent_category['category_id'] != $parent_category['path_id'] && array_key_exists($parent_category['path_id'], $parent_categories)) {
					$explode = explode('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $category['name']);
					
					array_shift($explode);
					$category['name'] = implode('&nbsp;&gt;&nbsp;', $explode);;
					$parent_categories[$parent_category['path_id']]['children'][$category['category_id']] = $category;
				}
			}
		}
		
		foreach ($parent_categories as $parent_id => &$parent_data) {
			$child_name = array();
			foreach($parent_data['children'] as $child_category_id => $child_data) {
				$child_name[$child_category_id] = $child_data['name'];
			}
			array_multisort($child_name, SORT_ASC, $parent_data['children']);
		}
		
		$data['categories'] = $parent_categories;
		
		if (version_compare(VERSION, '2.1', '>=')) {
			$this->load->model('customer/customer_group');
			$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		} else {
			$this->load->model('sale/customer_group');
			$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
		}
		
		$discounts = $this->model_extension_module_discount->getAllDiscounts('category');
		
		if (isset($this->request->post['category_discount'])) {
			$category_discounts = $this->request->post['category_discount'];
		} elseif (isset($discounts)) {
			$category_discounts = $discounts;
		} else {
			$category_discounts = array();
		}

		$data['category_discounts'] = array();
		
		foreach ($category_discounts as $category_discount) {
			$data['category_discounts'][] = array(
				'category_discount_id' => $category_discount['category_discount_id'],
				'status'			=> $category_discount['status'],
				'category_id'		=> $category_discount['category_id'],
				'customer_group_id' => $category_discount['customer_group_id'],
				'priority'          => $category_discount['priority'],
				'percentage'        => $category_discount['percentage'],
				'affect'			=> $category_discount['affect'],
				'qty'				=> $category_discount['qty'],
				'date_start'        => ($category_discount['date_start'] != '0000-00-00') ? $category_discount['date_start'] : '',
				'date_end'          => ($category_discount['date_end'] != '0000-00-00') ? $category_discount['date_end'] : ''
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/discount_category', $data));
	}
	
	public function saveDiscount() {
		
		$json = array();
		$this->load->language('extension/module/category_discount');
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			
			$this->load->model('setting/setting');
			$this->load->model('extension/module/discount');
			
			parse_str(htmlspecialchars_decode($this->request->post['setting']), $settings);
			
			$this->model_setting_setting->editSetting('total_category_discount', $settings);
			
			if(!empty($this->request->post['category_discount'])) {
			
				parse_str(htmlspecialchars_decode($this->request->post['category_discount']), $discount_data);

				$this->model_extension_module_discount->setDiscount($discount_data['category_discount'], 'category');		
			} else {
				$this->model_extension_module_discount->setDiscount(NULL, 'category');	
			}
			
			$json['success'] = $this->language->get('text_success');

		} else {
			$json['error'] = $this->language->get('error_warning');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}
	
	public function activate() {
		$this->load->language('extension/module/category_discount');
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			$this->db->query("UPDATE `". DB_PREFIX . "category_discount` SET `status` = '1' WHERE `category_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_activated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function deactivate() {
		$this->load->language('extension/module/category_discount');
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			$this->db->query("UPDATE `". DB_PREFIX . "category_discount` SET `status` = '0' WHERE `category_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_deactivated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function install() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX ."category_discount` (`category_discount_id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `customer_group_id` int(11) NOT NULL, `priority` int(5) NOT NULL DEFAULT '1', ";
		$sql .= "`percentage` decimal(15,4) NOT NULL DEFAULT '0.0000', `affect` int(1) NOT NULL DEFAULT '0', `qty` int(1) NOT NULL DEFAULT '0',`status` INT  NOT NULL DEFAULT '1', `date_start` date NOT NULL DEFAULT '0000-00-00', `date_end` date NOT NULL DEFAULT '0000-00-00', PRIMARY KEY (`category_discount_id`), KEY `category_id` (`category_id`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
	
		$this->db->query($sql);
	
		$this->db->query("INSERT INTO `". DB_PREFIX ."extension` (`extension_id`, `type`, `code`) VALUES (NULL, 'total', 'category_discount'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_category_discount', 'total_category_discount_sort_order', '2', '0'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_category_discount', 'total_category_discount_status', '1', '0');");
	}
	
	public function uninstall() {
		$key = 'category';
				
		$this->db->query("DROP TABLE `" . DB_PREFIX . $key . "_discount`");
		$this->db->query("DELETE FROM `". DB_PREFIX ."extension` WHERE `code` = '" . $key . "_discount';");
		$this->db->query("DELETE FROM `". DB_PREFIX ."setting` WHERE `code` = 'total_" . $key . "_discount';");
		
	}
	
}