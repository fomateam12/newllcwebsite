<?php
define('VERSION', '3.0.0.0');
require 'config.php';
require_once(DIR_SYSTEM . 'startup.php');
$registry = new Registry();

class SitemapCron {

    private $event;
    private $load;
    private $config;
    private $log;
	private $db;
	private $url;
   
    public function __construct($registry) {

        $this->event = new Event($registry);
        $registry->set('event', $this->event);

        $this->load = new Loader($registry);
        $registry->set('load', $this->load); 
            
        $this->config = new Config();
        $registry->set('config', $this->config);
    
        $this->log = new Log('sitemap.log');
        $registry->set('log', $this->log);
	
		$this->url = new Url('', '');
		$registry->set('url', $this->url);
		
        $this->db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        $registry->set('db', $this->db);
    
      }
    
      public function storeConfig() {
    
      $this->config->set('config_store_id', 0);
      $this->config->set('config_url', HTTP_SERVER);
      $this->config->set('config_ssl', HTTPS_SERVER);
    }
    
    public function setDefaultConfigurations() {
    
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $store_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" . $this->db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
          } else {
            $store_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') = '" . $this->db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
          }
          $store_query->num_rows ? $this->config->set('config_store_id', $store_query->row['store_id']) : $this->storeConfig();
        
          return true;
        
    }
    
    public function setConfigKeys() {
    
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' OR store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY store_id ASC");

        foreach ($query->rows as $result) {
      
          !$result['serialized'] ? $this->config->set($result['key'], $result['value']) :$this->config->set($result['key'], json_decode($result['value'], true));
        }
        return true;
      
    }

    public function saveSitemap() {
        $result = $this->generateSitemap();
        $file_name = 'webkul_sitemap.xml';
        $root = str_replace('system/', '', DIR_SYSTEM);
		$file = $root . $file_name;

		if ($file_name == '.htaccess') {
			if (file_exists($file . '.txt')) {
				unlink($file . 'txt');
			}
		}

		$fwrite = fopen($file, "w");
		fwrite($fwrite, html_entity_decode($result));
		fclose($fwrite);
    }

    public function generateSitemap() {

        if (!$this->config->get('module_webkul_sitemap_generator_status')) {
            return;
        }

		$frequency = date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s')));
        $date = 2;
		$priority = 0;
		
		$this->frequency = $frequency;
		$this->date = $date;
		$this->priority = $priority;
		$this->product_priority = '';
		$this->category_priority = '';

		$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$output = '<?xml-stylesheet type="text/xsl" href="' . HTTPS_SERVER . '/xml-css/main-sitemap.xsl"?>' . "\n";
		$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
				http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

		$output .= '  <url>' . "\n";
		$output .= '    <loc>' . HTTPS_SERVER . '</loc>' . "\n";
		if ($frequency) {
			$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
		} else {
			$output .= '    <changefreq>weekly</changefreq>' . "\n";
		}
		if ($date) {
			if ($date == 2) {
				$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
			} else {
				$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
			}
		}
		if ($priority) {
			$output .= '    <priority>1.0</priority>' . "\n";
		}
		$output .= '  </url>' . "\n";

		$products = $this->getProducts();

		foreach ($products as $product => $value) {
			$data = $this->url->link('product/product', 'product_id=' . $product);
			if ($this->config->get('module_webkul_sitemap_generator_status') && $this->config->get('config_seo_url')) {
                $link = $this->rewrite($data);
			} else if ($this->config->get('module_webkul_sitemap_generator_status') && !$this->config->get('config_seo_url')) {
                $link = $this->url->link('product/product', 'product_id=' . $product);
			}
			
			if ($link == ($this->url->link('product/product', 'product_id=' . $product))) {
				$link = HTTP_SERVER . $link;
			}

			$link = str_replace('&', '&amp;', $link);

			$seo_information = $this->db->query("SELECT * FROM ". DB_PREFIX ."wkseo_product_settings WHERE product_id='". $product ."'")->row;
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . $link . '</loc>' . "\n";
			if ($frequency) {
				$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($date) {
				if ($date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
				}
			}
			if ($priority) {
				if ($priority == 2) {
					$output .= '    <priority>1.0</priority>' . "\n";
				} else {
					$output .= '    <priority>' . $data['product_priority'] . '</priority>' . "\n";
				}
			}
			$output .= '  </url>' . "\n";
        }
        
        $output .= $this->getCategories(0);

		$manufacturers = $this->getManufacturers();
       
		foreach ($manufacturers as $manufacturer) {

			
			$data = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']);

			if ($this->config->get('module_webkul_sitemap_generator_status') && $this->config->get('config_seo_url')) {
                $link = $this->rewrite($data);
			} else if ($this->config->get('module_webkul_sitemap_generator_status') && !$this->config->get('config_seo_url')) {
                $link = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']);
			}
			$link = str_replace('&', '&amp;', $link);
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . $link . '</loc>' . "\n";
			if ($frequency) {
				$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($date) {
				if ($date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
				}
			}
			if ($priority) {	
				$output .= '    <priority>0.7</priority>' . "\n";	
			}
			$output .= '  </url>' . "\n";

			$products = $this->getProducts(array('filter_manufacturer_id' => $manufacturer['manufacturer_id']));
            
			foreach ($products as $product => $value) {
				$data = $this->url->link('product/product', 'manufacturer_id=' . $manufacturer['manufacturer_id'] . '&product_id=' . $product);
				
				if ($this->config->get('module_webkul_sitemap_generator_status') && $this->config->get('config_seo_url')) {
					$link = $this->rewrite($data);
				} else if ($this->config->get('module_webkul_sitemap_generator_status') && !$this->config->get('config_seo_url')) {
					$link = $this->url->link('product/product', 'manufacturer_id=' . $manufacturer['manufacturer_id'] . '&product_id=' . $product);
				}
				$link = str_replace('&', '&amp;', $link);
				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . $link . '</loc>' . "\n";
				if ($frequency) {
					$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
				} else {
					$output .= '    <changefreq>weekly</changefreq>' . "\n";
				}
				if ($date) {
					if ($date == 2) {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
					} else {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
					}
				}
				if ($priority) {	
					$output .= '    <priority>1.0</priority>' . "\n";	
				}
				$output .= '  </url>' . "\n";
			}
		}

		$informations = $this->getInformations();

		foreach ($informations as $information) {

			$data = $this->url->link('information/information', 'information_id=' . $information['information_id']);
			if ($this->config->get('module_webkul_sitemap_generator_status') && $this->config->get('config_seo_url')) {
				$link = $this->rewrite($data);
			} else if ($this->config->get('module_webkul_sitemap_generator_status') && !$this->config->get('config_seo_url')) {
				$link = $this->url->link('information/information', 'information_id=' . $information['information_id']);
			}
			$link = str_replace('&', '&amp;', $link);
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . $link . '</loc>' . "\n";
			if ($frequency) {
				$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($date) {
				if ($date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
				}
			}
			if ($priority) {	
				$output .= '    <priority>0.5</priority>' . "\n";	
			}
			$output .= '  </url>' . "\n";
		}

		$output .= '</urlset>';
		return $output;
    }
    
    public function getProducts($data = array()) {

		$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '1' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					$implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
			'rating',
			'p.sort_order',
			'p.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$product_data = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}
       
		return $product_data;
    }

    public function getProduct($product_id) {
        
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_title'       => $query->row['meta_title'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'tag'              => $query->row['tag'],
				'model'            => $query->row['model'],
				'sku'              => $query->row['sku'],
				'upc'              => $query->row['upc'],
				'ean'              => $query->row['ean'],
				'jan'              => $query->row['jan'],
				'isbn'             => $query->row['isbn'],
				'mpn'              => $query->row['mpn'],
				'location'         => $query->row['location'],
				'quantity'         => $query->row['quantity'],
				'stock_status'     => $query->row['stock_status'],
				'image'            => $query->row['image'],
				'manufacturer_id'  => $query->row['manufacturer_id'],
				'manufacturer'     => $query->row['manufacturer'],
				'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
				'special'          => $query->row['special'],
				'reward'           => $query->row['reward'],
				'points'           => $query->row['points'],
				'tax_class_id'     => $query->row['tax_class_id'],
				'date_available'   => $query->row['date_available'],
				'weight'           => $query->row['weight'],
				'weight_class_id'  => $query->row['weight_class_id'],
				'length'           => $query->row['length'],
				'width'            => $query->row['width'],
				'height'           => $query->row['height'],
				'length_class_id'  => $query->row['length_class_id'],
				'subtract'         => $query->row['subtract'],
				'rating'           => round($query->row['rating']),
				'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
				'minimum'          => $query->row['minimum'],
				'sort_order'       => $query->row['sort_order'],
				'status'           => $query->row['status'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
    }
    
    protected function getCategories($parent_id, $current_path = '') {
		$output = '';

		$results = $this->getCategory($parent_id);
        
        foreach ($results as $result) {
			if (!$current_path) {
				$new_path = $result['category_id'];
			} else {
				$new_path = $current_path . '_' . $result['category_id'];
			}

			$data = $this->url->link('product/category', 'path=' . $new_path);
			if ($this->config->get('module_webkul_sitemap_generator_status') && $this->config->get('config_seo_url')) {
                $link = $this->rewrite($data);
			} else if ($this->config->get('module_webkul_sitemap_generator_status') && !$this->config->get('config_seo_url')) {
                $link = $this->url->link('product/category', 'path=' . $new_path);
			}

			$link = str_replace('&', '&amp;', $link);

			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . $link . '</loc>' . "\n";
			if ($this->frequency) {
				$output .= '    <changefreq>' . $this->frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($this->date) {
				if ($this->date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($this->date)) . '</lastmod>' . "\n";
				}
			}
			if ($this->priority) {
				if ($this->priority == 2) {
					$output .= '    <priority>1.0</priority>' . "\n";
				} else {
					$output .= '    <priority>' . $this->category_priority . '</priority>' . "\n";
				}
			}
			$output .= '  </url>' . "\n";

			$products = $this->getProducts(array('filter_category_id' => $result['category_id']));

			foreach ($products as $product => $value) {
				$data = $this->url->link('product/product', 'path=' . $new_path . '&product_id=' . $product);
				if ($this->config->get('module_webkul_sitemap_generator_status') && $this->config->get('config_seo_url')) {
					$link = $this->rewrite($data);
				} else if ($this->config->get('module_webkul_sitemap_generator_status') && !$this->config->get('config_seo_url')) {
					$link = $this->url->link('product/product', 'path=' . $new_path . '&product_id=' . $product);
				}
				$link = str_replace('&', '&amp;', $link);
				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . $link . '</loc>' . "\n";
				if ($this->frequency) {
					$output .= '    <changefreq>' . $this->frequency . '</changefreq>' . "\n";
				} else {
					$output .= '    <changefreq>weekly</changefreq>' . "\n";
				}
				if ($this->date) {
					if ($this->date == 2) {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
					} else {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($this->date)) . '</lastmod>' . "\n";
					}
				}
				if ($this->priority) {
					if ($this->priority == 2) {
						$output .= '    <priority>1.0</priority>' . "\n";
					} else {
						$output .= '    <priority>' . $this->product_priority . '</priority>' . "\n";
					}
				}
				$output .= '  </url>' . "\n";
			}

			$output .= $this->getCategories($result['category_id'], $new_path);
		}

		return $output;
	}
    
    public function getCategory($parent_id = 0) {
        
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '1' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");
        
		return $query->rows;
    }
    
    public function getManufacturers($data = array()) {
    
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY name");

        $manufacturer_data = $query->rows;

		return $manufacturer_data;
    }
    
    public function getInformations() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE id.language_id = '1' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' ORDER BY i.sort_order, LCASE(id.title) ASC");

		return $query->rows;
	}
	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		if (!isset($url_info['query'])) {
			return $link;
		}


		parse_str($url_info['query'], $data);

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				$data['route'] = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$data['route']);
				if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
        		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "'");

				if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];

						unset($data[$key]);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
            		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "'");

          			if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				}

        
				else {
					if ($this->config->get('module_module_webkul_sitemap_generator_status') && !($data['route'] == 'product/product') && !($data['route'] == 'product/category') && !($data['route'] == 'product/manufacturer/info') && !($data['route'] == 'information/information')) {
						$query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "wkseo_sef WHERE route = '" . $data['route'] . "' AND language_id = '" . $this->config->get('config_language_id') . "'")->row;
						if (isset($query['keyword'])) {
							$sef = $query['keyword'];
						} else {
							$sef = str_replace('/', '-', $data['route']);
						}

						$url .= '/' . $sef;

						unset($data[$key]);
					}
				}

			}
		}

		if ($url) {

      
	  if (!($url == '/')) {
		if($this->config->get('wkseo_sef_extension')){
			$url .= '.' . $this->config->get('wkseo_sef_extension');
		}else{
			$url .= '';
		}
	}


			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

            $str_url = HTTP_SERVER . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('index.php', '', $url_info['path']) . $url . $query;
			
			return str_replace('//','/', $str_url);
			
		} else {
			return $link;
		}
	}

}

$sitemap = new SitemapCron($registry);
$sitemap->setDefaultConfigurations();
$sitemap->setConfigKeys();
$sitemap->saveSitemap();
