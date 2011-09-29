<?php 
 /*

	Facebook Catalog Browser - WP Online Store Plugin
	This modules allow Bright Software Solutions Facebook App to collect
	a list of products and categories. To allow access to this functionality
	the user needs to enter a secure key in the Module settings.
	This key is then used when setting up the App on your Facebook page.
	
	Copyright (C) 2011 Bright Software Solutions
						http://www.brightsoftwaresolutions.com
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
	
//JSON_ENCODE Alternative used if not already installed on system.   
  global  $encoder;
if (!function_exists('json_encode'))
	{
	  $encoder ='local';
	  function json_encode($a=false)
	  {
	    if (is_null($a)) return 'null';
	    if ($a === false) return 'false';
	    if ($a === true) return 'true';
	    if (is_scalar($a))
	    {
	      if (is_float($a))
	      {
	        // Always use "." for floats.
	        return floatval(str_replace(",", ".", strval($a)));
	      }
	      if (is_string($a))
	      {
	        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
	        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
	      }
	      else
	        return $a;
	    }
	    $isList = true;
	    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
	    {
	      if (key($a) !== $i)
	      {
	        $isList = false;
	        break;
	      }
	    }
	    $result = array();
	    if ($isList)
	    {
	      foreach ($a as $v) $result[] = json_encode($v);
	      return '[' . join(',', $result) . ']';
	    }
	    else
	    {
	      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
	      return '{' . join(',', $result) . '}';
	    }
	  }
	}  else {
	
		$encoder ='system';
	}
	
function is_utf8($str) {
    $c=0; $b=0;
    $bits=0;
    $len=strlen($str);
    for($i=0; $i<$len; $i++){
        $c=ord($str[$i]);
        if($c > 128){
            if(($c >= 254)) return false;
            elseif($c >= 252) $bits=6;
            elseif($c >= 248) $bits=5;
            elseif($c >= 240) $bits=4;
            elseif($c >= 224) $bits=3;
            elseif($c >= 192) $bits=2;
            else return false;
            if(($i+$bits) > $len) return false;
            while($bits > 1){
                $i++;
                $b=ord($str[$i]);
                if($b < 128 || $b > 191) return false;
                $bits--;
            }
        }
    }
    return true;
	}

	
function utf8json($inArray) { 
	    static $depth = 0; 
	    /* our return object */ 
	    $newArray = array(); 
	    /* safety recursion limit */ 
	    $depth ++; 
	    if($depth >= '100') { 
	        return false; 
	    } 
	    /* step through inArray */ 
	    foreach($inArray as $key=>$val) { 
	        if(is_array($val)) { 
	            /* recurse on array elements */ 
	            $newArray[$key] = utf8json($val); 				
	        } else { 
	            /* encode string values */ 				
				if ($val==null) {
					$newArray[$key] = null;
				} else {					
					if (is_utf8($val)) { 
						$newArray[$key] =$val;
					} else {
						$newArray[$key] = utf8_encode($val); 
					}
				}
	        } 
	    } 
	    /* return utf8 encoded array */ 
	    return $newArray; 
} 
function GenerateFeed($BSS_PLUGIN_KEY) {
	require_once WP_PLUGIN_DIR . '/wp-online-store/functions/database.php';
    require_once WP_PLUGIN_DIR . '/wp-online-store/functions/general.php';
	require_once WP_PLUGIN_DIR . '/wp-online-store/includes/application_top.php';

	// make a connection to the database... now
	osc_db_connect(DB_HOST, DB_USER, DB_PASSWORD) or die('Unable to connect to database server!');

   define('App_DefaultCategoryID',0);
   define('App_PageSize',30);   
   define('App_NewProductsMax',60);
   
   if (!isset($_GET['n']) || !isset($_GET['p'])) {
		die('Illegal Access Detected');  
   }
   $admin_name = $_GET['n'];
   $admin_pass = $_GET['p'];
   
   if (!isset($_GET['t'])) {
		$type = 'N'; //default new products
	} else {
		$type =  $_GET['t'];
	}
  
   if (!isset($_GET['catid'])) {
	$catid = App_DefaultCategoryID; //default to root cat
	} else {
		if (is_numeric($_GET['catid'])) {
			$catid = $_GET['catid'];
		} else {
			$catid = App_DefaultCategoryID;
		}
	}
	
   if ($catid<0) $catid =App_DefaultCategoryID;
   
   if (!isset($_GET['path'])) {
		$path = $catid;
	} else {				
		$path = $_GET['path'];		
	}
		
	$instock = true;
	if (isset($_GET['instock'])) {
		if ($_GET['instock']=="0" || $_GET['instock']=="false") {
			$instock = false;
		} 		
	}
	
   if (!isset($_GET['page'])) {
		$page = 0;
	} else {				
		if (is_numeric($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 0;
		}	
	}
   
   
   if (!($admin_name =='zc_browse')) {
		die('Illegal Access Detected');   
   }
   if (!($admin_pass == $BSS_PLUGIN_KEY)) {
		die('Illegal Access Detected');   
   }
   
   if (isset($_GET['validate'])) {   
	   $validate = $_GET['validate'];
	   if ($validate=='connection') {
			die('True');
	   }
	   if ($validate=='plugin_version') {
			die('1.0');
	   }
	    if ($validate=='plugin_type') {
			die('wpOnlineStore');
	   }
   }
   
   if (isset( $_GET['lang'])) {	
		$language = $_GET['lang'];		
   } else {   
		$language = DEFAULT_LANGUAGE;
   }
   
   // Lookup Language ID
  $query = "SELECT languages_id FROM " . TABLE_LANGUAGES . " l where code ='" . str_replace("'","",$language) ."'";
  $language_query = osc_db_query($query);
  if ($fields = tep_db_fetch_array($language_query)) {
	$language_id = $fields['languages_id'];
  } else {
	 $language_id=1;
  }
    
   
   if (isset( $_GET['rt'])) {	
		$requesttype = $_GET['rt'];
   } else {   
		$requesttype ='P';
   }
   
   if  ($requesttype=='C') {
   		
     	$query = "SELECT c.categories_id,categories_name  FROM " . TABLE_CATEGORIES ." c, " . TABLE_CATEGORIES_DESCRIPTION . " cd  where
				c.categories_id  = cd.categories_id and parent_id=" . App_DefaultCategoryID . " and sort_order >= 0
				and cd.language_id = " . $language_id . " order by sort_order ";
   } else {
	
	 $query_Tables ="";
	 $query_Order = " pd.products_name";
	 
	 $query_Limit_Offset = App_PageSize * $page;	 
	 $query_Limit = " limit " . $query_Limit_Offset . "," . App_PageSize;
	 
	 if ($catid<=0){ 
	 	$query_Where = "";
	 } else {
		$query_Tables .=  ", " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ";
		$query_Where = " and p2c.products_id = p.products_id and (p2c.categories_id = " .	 $catid;
		$subcategories_array = array();
		tep_get_subcategories($subcategories_array, $catid);
	    for ($i=0, $n=sizeof($subcategories_array); $i<$n; $i++ ) {
	      $query_Where .= " OR p2c.categories_id  = '" .  $subcategories_array[$i] ."'";
	    }
        $query_Where .= " ) ";
		
	 }
	 switch (strtoupper($type)) {
		case 'N':
			//New Products
			 $query_Where .= "";
			 $query_Order = " p.products_date_added desc ";
			break;
		case 'S':
			//Special Offers
			 $query_Where .= " and s.specials_id is not	null ";
			break;
		case 'F':
			
			if (defined('TABLE_FEATURED')) {
			 //Featured 			 
			$query_Tables .= ", " . TABLE_FEATURED ." f ";
			$query_Where .= " and f.products_id=p.products_id and f.status=1 
						and (now() < f.expires_date or f.expires_date='0001-01-01' or f.expires_date=0 or f.expires_date is null)";		
			} else {
			 //Featured - not available in osCommerce so specials are return instead
			 $query_Where .= " and s.specials_id is not	null ";
			 }
			break;
		case 'B':
			//Bestselllers
			 $query_Where .= "";
			 $query_Order = " p.products_ordered desc ";
		//case 'A':
			//All Products
		//	break;
	}
	
	if ($instock) {
		$query_Where = " and p.products_quantity>0 " . $query_Where;
	}
	 
	 
		$query_total = "Select distinct count(p.products_id) as rcount FROM 
							" . TABLE_PRODUCTS . " p ";
		if (strtoupper($type)=='S' || (!defined('TABLE_FEATURED') && strtoupper($type)=='F'))	{
			$query_total .=	" left outer join " . TABLE_SPECIALS . " s on s.status=1 and p.products_id=s.products_id
								and (now() < s.expires_date or s.expires_date='0001-01-01' or s.expires_date=0 or s.expires_date is null)";
		}
		$query_total .=	 $query_Tables . "
						WHERE 
							p.products_status=1 " . $query_Where;
	 
		
		 $data_query = osc_db_query($query_total);
		 $fields = tep_db_fetch_array($data_query);
		 $Product_Count =$fields['rcount'];
		 //Limit New Product to App_NewProductsMax Newest
		 if ((strtoupper($type)=='N' || strtoupper($type)=='B')  && $Product_Count>App_NewProductsMax) {		
			 $Product_Count =App_NewProductsMax;
		}
	
		if (DISPLAY_PRICE_WITH_TAX=='true') {
			$Query_SelectTax = ",tr.tax_rate ";
			$Query_TableTax = " left outer join " . TABLE_TAX_RATES ." tr on tr.tax_class_id = p.products_tax_class_id ";
		} else {		
			$Query_SelectTax = ",0.0 as tax_rate ";
			$Query_TableTax = " ";
		}
		 
		$query = "SELECT distinct p.products_id,pd.products_name,p.products_price,s.specials_new_products_price,p.products_image" . $Query_SelectTax ."
				FROM 
					" . TABLE_PRODUCTS . " p inner join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id=p.products_id  and pd.language_id = '" . $language_id . "'
					" . $Query_TableTax ."
					left outer join " . TABLE_SPECIALS . " s on s.status=1 and p.products_id=s.products_id
						and (now() < s.expires_date or s.expires_date='0001-01-01' or s.expires_date is null)
					" . $query_Tables . "
				WHERE 
					p.products_status=1 " . $query_Where . " order by  " . $query_Order . $query_Limit;
		
 }
	
  $data_query = osc_db_query($query);

  $Currency_Prefix = "";
  $Currency_Postfix = "";
  $dp = 2;
  $query = "SELECT symbol_left,symbol_right,decimal_places FROM " . TABLE_CURRENCIES . " c where code ='" . DEFAULT_CURRENCY  ."'";
  $currency = osc_db_query($query);
  while ($fields = tep_db_fetch_array($currency)) {
	$Currency_Prefix = $fields['symbol_left'];
	$Currency_Postfix =  $fields['symbol_right'];
	$dp = $fields['decimal_places'];
  }
  if ($Currency_Prefix =='£')  $Currency_Prefix = '&pound;';
  if ($Currency_Prefix =='€')  $Currency_Prefix = '&euro;';
  
  
  if ($Currency_Postfix =='£')  $Currency_Postfix = '&pound;';
  if ($Currency_Postfix =='€')  $Currency_Postfix = '&euro;';
  
  
  $format =  "%.". $dp ."f";
  $cat_paths = explode("-", $path);
  $rows = array();
  while ($fields = tep_db_fetch_array($data_query)) {	
	if  ($requesttype=='P') {
		$price = $fields['products_price'];	
		$price_special = $fields['specials_new_products_price'];
		if (DISPLAY_PRICE_WITH_TAX=='true') {
			$tax_rate = $fields['tax_rate'];	
			if (isset($tax_rate) && $tax_rate>0) {
				$price = $price * (1 + ($tax_rate/100));		
				if ($price_special) {
					$price_special = $price_special * (1 + ($tax_rate/100));			
				}
			}
		} 
		$fields['products_price'] = $Currency_Prefix . sprintf($format, round($price,$dp)) . $Currency_Postfix;
		if (isset($price_special)) {
			$fields['specials_new_products_price'] =$Currency_Prefix . sprintf($format, round($price_special,$dp)) . $Currency_Postfix;
		}
	}
	$datafields = $fields;
	if  ($requesttype=='C' && App_DefaultCategoryID!=$catid && in_array($fields['categories_id'],$cat_paths)) { //$fields['categories_id']==$catid) {
		 	$queryChildren = "SELECT c.categories_id,categories_name  FROM " . TABLE_CATEGORIES ." c, " . TABLE_CATEGORIES_DESCRIPTION . " cd  where
				c.categories_id  = cd.categories_id and parent_id=" . $fields['categories_id'] . " and sort_order >= 0 and cd.language_id = " . $language_id . " order by sort_order ";
			$queryChildress_Result = osc_db_query($queryChildren);
 			$rowschild = array();
 			while ($fields_sub = tep_db_fetch_array($queryChildress_Result)) {
				$rowschild[] = $fields_sub;
    		}
	 		$datafields['childern'] = $rowschild;
	}
	$rows[] = $datafields;
  }

	
 
	
   global  $encoder;
   if  ($requesttype=='C') {
		print json_encode(utf8json($rows));
	} else {
		$Category = array();
		$Category['rcount'] = $Product_Count;
		$Category['products'] = utf8json($rows);
		$Category['encode'] = $encoder;
		print json_encode($Category);
	}
}
?>