<?php
class ControllerExtensionTotalLoyaltyDiscount extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/total/loyalty_discount');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('total_loyalty_discount', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_total'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/total/loyalty_discount', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/total/loyalty_discount', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true);

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

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/total/loyalty_discount', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/total/loyalty_discount')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	public function install() {
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX ."loyalty_discount` (`loyalty_discount_id` int(11) NOT NULL AUTO_INCREMENT, `ordertotal` int(11) NOT NULL, `customer_group_id` int(11) NOT NULL, `priority` int(5) NOT NULL DEFAULT '1', `type`char(1) NOT NULL, ";
  		$sql .= "`percentage` decimal(15,4) NOT NULL DEFAULT '0.0000', `date_start` date NOT NULL DEFAULT '0000-00-00', `date_end` date NOT NULL DEFAULT '0000-00-00', PRIMARY KEY (`loyalty_discount_id`), KEY `ordertotal` (`ordertotal`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
		
		$this->db->query($sql);
		
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_loyalty_discount', 'total_loyalty_discount_sort_order', '2', '0'); ");
		$this->db->query("INSERT INTO `". DB_PREFIX ."setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_loyalty_discount', 'total_loyalty_discount_status', '1', '0');");
	}
	
	public function uninstall() {
	
		$this->db->query("DROP TABLE `" . DB_PREFIX ."loyalty_discount`");
		
		$this->db->query("DELETE FROM `". DB_PREFIX ."extension` WHERE `code` = 'loyalty_discount';");
		$this->db->query("DELETE FROM `". DB_PREFIX ."setting` WHERE `code` = 'total_loyalty_discount';");
		
	}
}