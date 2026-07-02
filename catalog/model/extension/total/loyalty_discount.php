<?php
class ModelExtensionTotalLoyaltyDiscount extends Model {
	public function getTotal($total) {
		if ($this->config->get('module_discounts_pack_status') && $this->cart->hasProducts() && $this->config->get('total_loyalty_discount_status')) {
			$this->load->language('extension/total/loyalty_discount');
			$this->load->model('extension/module/discount');
			
			$discount_total = 0;
			
			$total_discount = $this->model_extension_module_discount->getLoyaltyDiscount($total);
			
			//$total_discount = array('discount' => '2.0000', 'ordertotal' => 100, 'debug' => $total_discount['debug']);
			
			if ($total_discount) {
			
				foreach ($this->cart->getProducts() as $product) {
					$discount = 0;
					
					$discount = $product['total'] / 100 * $total_discount['discount'];
					
					if (!empty($this->config->get('module_discounts_pack_rounding'))) {
						if ($this->config->get('module_discounts_pack_rounding') != 'none') {
							$precision =  !empty($this->config->get('module_discounts_pack_rounding_precision')) ? (int)$this->config->get('module_discounts_pack_rounding_precision') : 0 ;
							$mode = $this->config->get('module_discounts_pack_rounding') == 'up' ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN ;
							$discount = round((float)$discount,$precision,$mode);
						}
					}
							
					if ($product['tax_class_id']) {
						$tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);

							foreach ($tax_rates as $tax_rate) {
								if (version_compare(VERSION, '2.2', '>=') && !empty($total['taxes'][$tax_rate['tax_rate_id']])) { 
									$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
								} elseif (!empty($taxes[$tax_rate['tax_rate_id']])) {
									$taxes[$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
								}
							}
					}
		
					if (empty($discount_data[$total_discount['ordertotal']])) {
					
						$decimals = explode('.', $total_discount['discount']);
						$discount_data[$total_discount['ordertotal']] = array(
							'code'       => 'loyalty_discount',
							'title'      => sprintf($this->language->get('text_loyalty_discount'), '-' . (($decimals[1]) == '0000' ? $decimals[0] : number_format($total_discount['discount'], 2)). '%'),
							'value'      => -$discount,
							'sort_order' => $this->config->get('total_loyalty_discount_sort_order')
						);	
					} else {
						$discount_data[$total_discount['ordertotal']]['value'] += -$discount;
		
					}
		
					$discount_total += $discount;
				}
			}
			
			if (!empty($discount_data)) {
				foreach ($discount_data as $key) {
					
					if (version_compare(VERSION, '2.2', '>=')) { 
						$total['totals'][] = array(
							'code'       => $key['code'],
							'title'      => $key['title'],
							'value'      => $key['value'],
							'sort_order' => $key['sort_order']
						);
					} else {
						$total_data[] = array(
							'code'       => $key['code'],
							'title'      => $key['title'],
							'value'      => $key['value'],
							'sort_order' => $key['sort_order']
						);
					}
				}
			}
			if (version_compare(VERSION, '2.2', '>=')) { 
				$total['total'] -= $discount_total;
			} else {
				$total -= $discount_total;
			}
		}
	}
}