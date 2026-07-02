<?php
class ControllerSitemapSitemap extends Controller {
	private $error = array();

	public function index() {
		$data = $this->load->language('sitemap/sitemap');
		$this->load->model('sitemap/sitemap');
		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_sitemap_sitemap->setFileContent('webkul_sitemap.xml', $this->request->post['sitemap']);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('sitemap/sitemap', 'user_token=' . $this->session->data['user_token'], true));
		}

		$file_content = $this->model_sitemap_sitemap->getFileContent('webkul_sitemap.xml');

		$data['file_content'] = $this->db->escape($file_content);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sitemap/sitemap', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];
		$data['action'] = $this->url->link('sitemap/sitemap', 'user_token=' . $this->session->data['user_token'], true);
		$data['https_catalog'] = HTTPS_CATALOG;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sitemap/sitemap', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'sitemap/sitemap')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function addMenu(&$route = false, &$data = false, &$output = false) {

		if($this->config->get('module_webkul_sitemap_generator_status')) {
	
			$menusgs = array();
		
			if ($this->user->hasPermission('access', 'sitemap/sitemap'))  {

				$menusgs[] = array(
				'name'	     =>  'Config Setting',
				'href'     => $this->url->link('extension/module/webkul_sitemap_generator', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
				);
		
				$menusgs[] = array(
				'name'	     =>  'Sitemap',
				'href'     => $this->url->link('sitemap/sitemap', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
				);
		
				$menu = array(array(
				'id'       => 'webkul-sitemap-generator',
				'icon'	    => 'fa fa-sitemap',
				'name'	    => 'Webkul Sitemap Generator',
				'href'     => '',
				'children' => $menusgs,
				));
		
				array_splice($data['menus'],1,0,$menu);
			}
		}
	}
}
