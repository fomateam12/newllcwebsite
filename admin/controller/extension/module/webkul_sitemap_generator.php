<?php
class ControllerExtensionModuleWebkulSitemapGenerator extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/webkul_sitemap_generator');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_webkul_sitemap_generator', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

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
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/webkul_sitemap_generator', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/webkul_sitemap_generator', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_webkul_sitemap_generator_status'])) {
			$data['module_webkul_sitemap_generator_status'] = $this->request->post['module_webkul_sitemap_generator_status'];
		} else {
			$data['module_webkul_sitemap_generator_status'] = $this->config->get('module_webkul_sitemap_generator_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/webkul_sitemap_generator', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/webkul_sitemap_generator')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
		$this->load->model('sitemap/sitemap');
		$this->model_sitemap_sitemap->createTables();
		$this->__registerEvents();
	}

	public function uninstall() {
		$this->load->model('sitemap/sitemap');
		$this->model_sitemap_sitemap->dropTables();
		$this->__deleteEvents();
	}

	public function __registerEvents() {
		//add event to add the menu in the admin end code starts here
		$code = "webkul_sitemap_generator_column_menu";
		$trigger = "admin/view/common/column_left/before";
		$action = "sitemap/sitemap/addMenu";
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent($code, $trigger, $action);
	}
	 
	public function __deleteEvents() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('webkul_sitemap_generator_column_menu');
	}
}