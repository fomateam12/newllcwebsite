<?php
class ModelExtensionModuleCapanAdvancedProduct extends Model {

public function uretModel(){
	$cikti="";
	$ac=true;
	$aralik="1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ";
	$sayi=10;
	$onek="";
	$aralikSayi = '1234567890';
	$aralikKarakter = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';
	$araklikKarisik = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';
	$tip= $this->config->get('module_capan_advanced_product_kodTip');
	$karakter= $this->config->get('module_capan_advanced_product_kodKarakter');
	$onek= $this->config->get('module_capan_advanced_product_onek');
	if($tip==1)
		$aralik= $aralikSayi = '1234567890';
	else if($tip==2)
		$aralik=$aralikKarakter = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';
	else
		$aralik=$araklikKarisik = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';

	if(!empty($karakter))
		$sayi=$karakter;
	else
		$sayi=10;
	$cikti = $onek.substr(str_shuffle($aralik), 0, $sayi);
while ($ac) {

	
	
	$sorgu= $this->db->query("SELECT model FROM " . DB_PREFIX . "product WHERE model ='".$cikti."'");
	if($sorgu->num_rows > 0){
		$cikti = $onek.substr(str_shuffle($aralik), 0, $sayi);
	}
	else
		$ac=false;
}
	return $cikti;
}
/* Bizim kodlarımız */
    public function secenekEkle($product_id, $data) {
    	
    	if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

    }
   
    public function isimDegistir($product_id,$islem,$filtre_deger,$filtre_tipi,$gelen_isim)
    {
    	//$isim="";
    	
    	foreach ($gelen_isim as $key => $deger) {
    		if(!$gelen_isim==""){
    		$query = "SELECT name,language_id FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "' and language_id='".$key."'";
    	if($filtre_tipi==0)
    		$query .="AND name LIKE '%".$filtre_deger[$key]."%' ";
    	if($filtre_tipi==1)
    		$query .="AND name LIKE '".$filtre_deger[$key]."%' ";
    	if($filtre_tipi==2) 
    		$query .="AND name LIKE '%".$filtre_deger[$key]."' ";
    	$sonuc= $this->db->query("".$query."");
    	if($sonuc->num_rows >0)
    		{
    			foreach ($sonuc->rows as $getir) {
    				$isim=$getir['name'];
    				$dil=$getir['language_id'];
    			}
    			if($islem==0)
    			 $isim = $deger;
    			if($islem==1)
    			 $isim = $deger.' '.$isim;
    			if($islem==2)
    			 $isim = $isim.' '.$deger;

    			$this->db->query("UPDATE " . DB_PREFIX . "product_description SET name='".$isim."' WHERE product_id= '" . (int)$product_id . "' and language_id='".$dil."'");
    		}
    	}
    	}
    	
    }
    public function kategoriGuncelle($product_id,$kategoriler,$oncekiSil){
    	if ($oncekiSil==1) {
    		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
			foreach ($kategoriler as $kategori_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$kategori_id . "'");
    	}
    }
    	else{
    		
			foreach ($kategoriler as $kategori_id) {
				$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id='".$kategori_id."' ");
				if (!$query->num_rows>0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$kategori_id . "'");
				}
				
    	}
    		
			
		}
    }
    public function SecenekPuanUygula($product_id,$fiyatSecenek,$fiyatAksiyon,$fiyat) {
		$query = $this->db->query("SELECT points,product_option_value_id FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
		$c_fiyat = 0;
		foreach ($query->rows as $yazdir) {
			$c_fiyat = $yazdir['points'];
			$c_kimlik = $yazdir['product_option_value_id'];

			$yuzde = $fiyat/100;
		if($fiyatSecenek ==0 && $fiyatAksiyon==0 )
			$c_fiyat += $c_fiyat * $yuzde;
		else if($fiyatSecenek ==0 && $fiyatAksiyon==1)
			$c_fiyat -= $c_fiyat * $yuzde;
		else if($fiyatSecenek ==1 && $fiyatAksiyon==0)
			$c_fiyat += $fiyat;
		else if($fiyatSecenek ==1 && $fiyatAksiyon==1)
			$c_fiyat -= $fiyat;
		else if($fiyatAksiyon==2)
			$c_fiyat = $fiyat;
		else
			$c_fiyat = $c_fiyat;
		if($c_fiyat<0)
			$c_fiyat =0;
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product_option_value SET points = '". (float)$c_fiyat . "' WHERE product_option_value_id='".$c_kimlik."' AND product_id = '" . (int)$product_id . "'");
		}
	}
    public function SecenekFiyatUygula($product_id,$fiyatSecenek,$fiyatAksiyon,$fiyat) {
		$query = $this->db->query("SELECT price,product_option_value_id FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
		$c_fiyat = 0;
		foreach ($query->rows as $yazdir) {
			$c_fiyat = $yazdir['price'];
			$c_kimlik = $yazdir['product_option_value_id'];

			$yuzde = $fiyat/100;
		if($fiyatSecenek ==0 && $fiyatAksiyon==0 )
			$c_fiyat += $c_fiyat * $yuzde;
		else if($fiyatSecenek ==0 && $fiyatAksiyon==1)
			$c_fiyat -= $c_fiyat * $yuzde;
		else if($fiyatSecenek ==1 && $fiyatAksiyon==0)
			$c_fiyat += $fiyat;
		else if($fiyatSecenek ==1 && $fiyatAksiyon==1)
			$c_fiyat -= $fiyat;
		else if($fiyatAksiyon==2)
			$c_fiyat = $fiyat;
		else
			$c_fiyat = $c_fiyat;
		if($c_fiyat<0)
			$c_fiyat =0;
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product_option_value SET price = '". (float)$c_fiyat . "' WHERE product_option_value_id='".$c_kimlik."' AND product_id = '" . (int)$product_id . "'");
		}
	}
	public function urunFiyatUygula($product_id,$fiyatSecenek,$fiyatAksiyon,$fiyat) {
		$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		$c_fiyat = 0;
		foreach ($query->rows as $yazdir) {
			$c_fiyat = $yazdir['price'];
		}
		$yuzde = $fiyat/100;
		if($fiyatSecenek ==0 && $fiyatAksiyon==0 )
			$c_fiyat += $c_fiyat * $yuzde;
		else if($fiyatSecenek ==0 && $fiyatAksiyon==1)
			$c_fiyat -= $c_fiyat * $yuzde;
		else if($fiyatSecenek ==1 && $fiyatAksiyon==0)
			$c_fiyat += $fiyat;
		else if($fiyatSecenek ==1 && $fiyatAksiyon==1)
			$c_fiyat -= $fiyat;
		else if($fiyatAksiyon==2)
			$c_fiyat = $fiyat;
		else
			$c_fiyat = $c_fiyat;
		if($c_fiyat<0)
			$c_fiyat =0;
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product SET price = '". (float)$c_fiyat . "' WHERE product_id = '" . (int)$product_id . "'");
			
	}
	public function seo_capan($s,$product_id,$dil,$store_id) {
 $tr = array('ş','Ş','ı','I','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç','(',')','/',':',',');
 $eng = array('s','s','i','i','i','g','g','u','u','o','o','c','c','','','-','-','');
 $s = str_replace($tr,$eng,$s);
 $s = strtolower($s);
 $s = preg_replace('/&amp;amp;amp;amp;amp;amp;amp;amp;amp;.+?;/', '', $s);
 $s = preg_replace('/\s+/', '-', $s);
 $s = preg_replace('|-+|', '-', $s);
 $s = preg_replace('/#/', '', $s);
 $s = str_replace('.', '', $s);
 $s = trim($s, '-');
 $i=0;
 $ac=true;
while ($ac) {
	$i++;
	$sorgu= $this->db->query("SELECT store_id FROM " . DB_PREFIX . "seo_url WHERE keyword = '". $s . "'");
	if($sorgu->num_rows > 0){
		if($i==1)
			$s=$s ."-";
		if($i>=2)
			$s= substr($s, 0, -1);
		$s =$s ."".(string)$i;
	}
	else
		$ac=false;
}

$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$dil . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($s) . "'");
}
	public function urunSeo($product_id,$u_a,$u_m_a,$u_m_an,$u_e) {
		$query = $this->db->query("SELECT name,language_id FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
		$query2 = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		$query3 = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
		
		
		$store_id=0;
		$kategoriAd="";
		$uretici="";
		$ur_id=0;
		
		$ca_id[]=0;
		$ca_dil[]=0;
		$ca_cap_id[]=0;
		$derinlik[]=0;
		$der_id[]=0;
		

		// Post tanıma
		
		$query_store = $this->db->query("SELECT store_id FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
		foreach ($query_store->rows as $yazdir) {
			$store_id = $yazdir['store_id'];
		}
		foreach ($query->rows as $yazdir) {
			$urunAd[] = $yazdir['name'];
			$dil[]= $yazdir['language_id'];
		}
		foreach ($query2->rows as $yazdir) {
			$ur_id = $yazdir['manufacturer_id'];
		}
		
		
		$query4 = $this->db->query("SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = '" . (int)$ur_id . "'");
		foreach ($query4->rows as $yazdir) {
			$uretici = $yazdir['name'];
		}

		if ($u_a!=61) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
			if ($u_a==0) {
				foreach ($urunAd as $key => $yazdir) {
				$adres_tamlama[]= $this->seo_capan($yazdir,$product_id,$dil[$key],$store_id);
				}
			}
			if ($u_a==1) {
				foreach ($urunAd as $key => $yazdir) {
				$tamam1= $uretici . " ".$yazdir;
				$adres_tamlama[]= $this->seo_capan($tamam1,$product_id,$dil[$key],$store_id);
				}
			}

	
					
				
		}
		

		if ($u_m_a!=61) {
			if ($u_m_a==0) {
				foreach ($urunAd as $yazdir) {
				$adres_tamlama[]= $yazdir;
			}
			}
			if ($u_m_a==1) {
				foreach ($urunAd as $yazdir) {
				$tamam1= $uretici . " ".$yazdir;
				$adres_tamlama[]= $tamam1;
			}
			}
			foreach ($adres_tamlama as $key => $deger) {
		
						$this->db->query("UPDATE " . DB_PREFIX . "product_description SET meta_description = '" . $deger . "' WHERE language_id = '" . (int)$dil[$key] . "' and product_id = '" . (int)$product_id . "'");
					
		}
			
		}
	

		if ($u_m_an!=61) {
			if ($u_m_an==0) {
				foreach ($urunAd as $yazdir) {
				$adres_tamlama[]= $yazdir;
			}
		}
			if ($u_m_an==1) {
				foreach ($urunAd as $yazdir) {
				$tamam1= $uretici . " ".$yazdir;
				$adres_tamlama[]= $tamam1;
			}
			}
			foreach ($adres_tamlama as $key => $deger) {
		
						$this->db->query("UPDATE " . DB_PREFIX . "product_description SET meta_keyword = '" . $deger . "' WHERE language_id = '" . (int)$dil[$key] . "' and product_id = '" . (int)$product_id . "'");
					
		}
		}
	
		if ($u_e!=61) {
			if ($u_e==0) {
				foreach ($urunAd as $yazdir) {
				$adres_tamlama[]= $yazdir;
			}
		}
			if ($u_e==1) {
				foreach ($urunAd as $yazdir) {
				$tamam1= $uretici . ", ".$yazdir;
				$adres_tamlama[]= $tamam1;
			}
			}
			foreach ($adres_tamlama as $key => $deger) {
		
						$this->db->query("UPDATE " . DB_PREFIX . "product_description SET tag = '" . $deger . "' WHERE language_id = '" . (int)$dil[$key] . "' and product_id = '" . (int)$product_id . "'");
					
		}
		
	}
		
			
	}

	public function durumGuncelle($product_id,$durum) {
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product SET status = '". $durum . "' WHERE product_id = '" . (int)$product_id . "'");
			
	}
	public function gereklilikGuncelle($product_id,$durum) {
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product_option SET required = '". $durum . "' WHERE product_id = '" . (int)$product_id . "'");
			
	}
	public function SecenekadetGuncelle($product_id,$adetSecenek,$adetAksiyon,$adet) {
		$query = $this->db->query("SELECT quantity,product_option_value_id FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
		$c_adet =0;
		$c_kimlik =0;
		foreach ($query->rows as $yazdir) {
			$c_adet = $yazdir['quantity'];
			$c_kimlik = $yazdir['product_option_value_id'];
			$yuzde = $adet/100;
		if($adetSecenek ==0 && $adetAksiyon==0 )
			$c_adet += $c_adet * $yuzde;
		else if($adetSecenek ==0 && $adetAksiyon==1)
			$c_adet -= $c_adet * $yuzde;
		else if($adetSecenek ==1 && $adetAksiyon==0)
			$c_adet += $adet;
		else if($adetSecenek ==1 && $adetAksiyon==1)
			$c_adet -= $adet;
		else if($adetAksiyon==2)
			$c_adet = $adet;
		else
			$c_adet = $c_adet;
		if($c_adet<0)
			$c_adet =0;
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product_option_value SET quantity = '". $c_adet . "' WHERE product_option_value_id = '" . (int)$c_kimlik . "' AND  product_id = '" . (int)$product_id . "'");
		}
	}
	public function adetGuncelle($product_id,$adetSecenek,$adetAksiyon,$adet) {
		$query = $this->db->query("SELECT quantity FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		$c_adet =0;
		foreach ($query->rows as $yazdir) {
			$c_adet = $yazdir['quantity'];
		}
		//$c_adet = $query->rows[0];
		$yuzde = $adet/100;
		if($adetSecenek ==0 && $adetAksiyon==0 )
			$c_adet += $c_adet * $yuzde;
		else if($adetSecenek ==0 && $adetAksiyon==1)
			$c_adet -= $c_adet * $yuzde;
		else if($adetSecenek ==1 && $adetAksiyon==0)
			$c_adet += $adet;
		else if($adetSecenek ==1 && $adetAksiyon==1)
			$c_adet -= $adet;
		else if($adetAksiyon==2)
			$c_adet = $adet;
		else
			$c_adet = $c_adet;
		if($c_adet<0)
			$c_adet =0;
		
		$this->db->query("UPDATE  " . DB_PREFIX . "product SET quantity = '". $c_adet . "' WHERE product_id = '" . (int)$product_id . "'");
			
	}
	public function kategoriSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

	}
	public function kampanyaSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

	}
	public function ozellikSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

	}
	public function secenekSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");

	}
	public function yinelenenProfilSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = '" . (int)$product_id . "'");
		

	}
	public function filtreSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
		

	}
	public function ureticiSil($product_id) {

		$this->db->query("UPDATE " . DB_PREFIX . "product SET manufacturer_id = 0 WHERE product_id = '" . (int)$product_id . "'");
		

	}
	public function ilgiliSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
		

	}
	public function indirimSil($product_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

	}
	
	public function ureticiGuncelle($product_id,$uretici) {

		$this->db->query("UPDATE " . DB_PREFIX . "product SET manufacturer_id = '" . (int)$uretici . "' WHERE product_id = '" . (int)$product_id . "'");

	}
	public function kampanyaFiyatUygula($product_id,$kamp_muster_grup,$kamp_oncelik,$kamp_deger_tipi,$kamp_fiyat,$kamp_fiyat_tipi,$kamp_tarih_baslangic,$kamp_tarih_bitis) {
		$c_fiyat= 0;
		$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		foreach ($query->rows as $yazdir) {
			$c_fiyat = $yazdir['price'];
		}
		
		$yuzde = $kamp_fiyat/100;
		if($kamp_deger_tipi ==0 && $kamp_fiyat_tipi==0 )
			$c_fiyat += $c_fiyat * $yuzde;
		else if($kamp_deger_tipi ==0 && $kamp_fiyat_tipi==1)
			$c_fiyat -= $c_fiyat * $yuzde;
		else if($kamp_deger_tipi ==1 && $kamp_fiyat_tipi==0)
			$c_fiyat += $kamp_fiyat;
		else if($kamp_deger_tipi ==1 && $kamp_fiyat_tipi==1)
			$c_fiyat -= $kamp_fiyat;
		else if($kamp_fiyat_tipi==2)
			$c_fiyat = $kamp_fiyat;
		else
			$c_fiyat = $c_fiyat;
		if($c_fiyat<0)
			$c_fiyat =0;
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$kamp_muster_grup . "', priority = '" . (int)$kamp_oncelik . "', price = '" . (float)$c_fiyat . "', date_start = '" . $this->db->escape($kamp_tarih_baslangic) . "', date_end = '" . $this->db->escape($kamp_tarih_bitis) . "'");
			
	}
	public function indirimFiyatUygula($product_id,$ind_adet,$ind_muster_grup,$ind_oncelik,$ind_deger_tipi,$ind_fiyat,$ind_fiyat_tipi,$ind_tarih_baslangic,$ind_tarih_bitis) {
		$c_fiyat= 0;
		$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		foreach ($query->rows as $yazdir) {
			$c_fiyat = $yazdir['price'];
		}
		
		$yuzde = $ind_fiyat/100;
		if($ind_deger_tipi ==0 && $ind_fiyat_tipi==0 )
			$c_fiyat += $c_fiyat * $yuzde;
		else if($ind_deger_tipi ==0 && $ind_fiyat_tipi==1)
			$c_fiyat -= $c_fiyat * $yuzde;
		else if($ind_deger_tipi ==1 && $ind_fiyat_tipi==0)
			$c_fiyat += $ind_fiyat;
		else if($ind_deger_tipi ==1 && $ind_fiyat_tipi==1)
			$c_fiyat -= $ind_fiyat;
		else if($ind_fiyat_tipi==2)
			$c_fiyat = $ind_fiyat;
		else
			$c_fiyat = $c_fiyat;
		if($c_fiyat<0)
			$c_fiyat =0;
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET quantity='".$ind_adet."', product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$ind_muster_grup . "', priority = '" . (int)$ind_oncelik . "', price = '" . (float)$c_fiyat . "', date_start = '" . $this->db->escape($ind_tarih_baslangic) . "', date_end = '" . $this->db->escape($ind_tarih_bitis) . "'");
			
	}
	/*  Kodumuzun Sonu  */
}