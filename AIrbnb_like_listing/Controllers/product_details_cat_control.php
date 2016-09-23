<?php
class Product_details_cat_control extends CI_Controller
{
     function __construct()
	{
		parent::__construct();
		$this->load->model('permission_model');
		$this->load->model('sitesetting_model');
		$this->load->model('product_details_cat_model');
		$this->load->model('service_details_view_model');
		
		$this->load->model('translate_currency_model');
		$this->load->model('custom_pagination_model');
		$this->load->model('service_view_model');
		$this->load->model('common_model');
		$this->load->model('meta_desc_model');
		$this->load->library('upload');
		$this->load->helper(array('form', 'url')); 
		$this->load->helper('url');
		$this->load->library('googlemaps');
		
		$this->load->model('home_model');
	}
		
	function url_get_contents ($Url)
	{
		if (!function_exists('curl_init')){ 
		    die('CURL is not installed!');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}	
	
	public function add_service_text($d)
	{
		$CI =& get_instance();
		$CI->load->model('footer_model');
		$table="admin_page_content";
		$id_b_lebel1=$d;
		$lebel1=$CI->footer_model->get_contents($table,$id_b_lebel1);
		return $lebel1;
	}
	
	//Added Arijit		
	function thelist_service_new()
	{
		$this->benchmark->mark('code_start');
		
		$search 			= $this->input->get('search');
		$is_main_search	= $this->input->get('main_search');
		$pageno 			= $this->input->get('pageno');
		$perpage 			= $this->input->get('perpage');
		$service_name 		= $this->input->get('service_name');
		$service_name		= str_replace(' ', '-', $service_name);
		$service_place 	= $this->input->get('service_place');
		$open_date 		= $this->input->get('open_date');
		$time_open 		= $this->input->get('time_open');
		$cat_id 			= $this->input->get('cat_id');
		$key_id 			= $this->input->get('key_id');
		$imp_servids 		= $this->input->get('imp_servids');
		$price_low 		= trim($this->input->get('price_low'));
		$price_high 		= trim($this->input->get('price_high'));
		$is_first 		= trim($this->input->get('is_first'));
		
		$imp_servids=0;
		
		//echo 'arijit: '.$service_name;
		
		/**For start date end date ***/ /*By Debojit On 24 Dec*/
		$dt_start=$open_date;  $dt_end=$time_open;
		if(!empty($key_id))
		{
		  $check_keywords = $this->db->where('status', 'Y')->like('id', $key_id)->get('manage_keyword')->row();
			
			if(!empty($check_keywords->name))
			{
			   $service_name	= $check_keywords->name;
			}
		}
		
		$ids_servarr=array(); $ids_servarr1=array();
		if($dt_start!='' ||  $dt_end!='')
		{
			$available_dates=$this->product_details_cat_model->date_opens($dt_start,$dt_end);
			
			foreach($available_dates  as $val)
			{
				if($val['service_id']!='' && $val['service_id']!=0)
					$ids_servarr[]=$val['service_id'];	
			}
			
			$always_available=$this->product_details_cat_model->always_available();
			
			foreach($always_available  as $val)
			{
				if($val['service_id']!='' && $val['service_id']!=0)
				{
					$ids_servarr[]=$val['service_id'];	
				}
			}
			
			$ids_servarr1 = array_filter(array_unique($ids_servarr));
			
			if(count($ids_servarr1) >0 )
			{
				$imp_servids=implode(",", $ids_servarr1);	
			}
		}
		
		//echo $imp_servids.'<br>'; 
		
		/** End  **/
		//echo $is_first;
		//echo $price_low;
		//exit;
		if($is_first == 1){
			if($price_low == 1)
				$price_low = '';
			
			if($price_high == 20000)
				$price_high = '';
		}
		
		$org_price_low = $price_low; $org_price_high = $price_high; 
		
		if($this->input->get('srch_lat'))
			$srch_lat 	= $this->input->get('srch_lat');
		else
			$srch_lat 	= $this->input->get('sw_lat');
		
		if($this->input->get('srch_lon'))
			$srch_lon 	= $this->input->get('srch_lon');
		else
			$srch_lon 	= $this->input->get('sw_lng');
			
		$sw_lat			= $this->input->get('sw_lat');
		$sw_long			= $this->input->get('sw_lng');
		$ne_lat			= $this->input->get('ne_lat');
		$ne_long			= $this->input->get('ne_lng');
		
		$srch_lat_new 		= $this->input->get('sw_lat_new');
		$srch_lon_new 		= $this->input->get('sw_lng_new');
		
		$sw_lat_new		= $this->input->get('sw_lat_new');
		$sw_long_new		= $this->input->get('sw_lng_new');
		$ne_lat_new		= $this->input->get('ne_lat_new');
		$ne_long_new		= $this->input->get('ne_lng_new');
		
		
		if($is_main_search == 0)
		{
			$srch_lat 	= ($srch_lat_new != '') 	? $srch_lat_new : $srch_lat;
			$srch_lon 	= ($srch_lon_new != '') 	? $srch_lon_new : $srch_lon;
			
			$sw_lat 		= ($sw_lat_new != '') 	? $sw_lat_new 	 : $srch_lat;
			$sw_long 		= ($sw_long_new != '') 	? $sw_long_new  : $srch_lat;
			$ne_lat 		= ($ne_lat_new != '') 	? $ne_lat_new 	 : $srch_lat;
			$ne_long 		= ($ne_long_new != '') 	? $ne_long_new  : $srch_lat;
		}
		
		$srchtyp='1';
		
		if($sw_lat != '' && $ne_lat != '' && $sw_long != ''&& $ne_long != '')
		{
			if(($sw_lat == $ne_lat) && ($sw_long == $ne_long)){
				$srch_lat 	= $sw_lat;
				$srch_lon 	= $sw_long;
				$srchtyp  	= 0;
			}
		}
		else{
			$srchtyp  	= 0;
		}
		
		//sw_lat=22.756101336677563&sw_lng=88.26564614169922
		//&ne_lat=22.756101336677563&ne_lng=88.26564614169922
		
		$currency_id 		= 3;
		if($this->session->userdata('mainsite_crncy'))
			$currency_id 	=$this->session->userdata('mainsite_crncy');
		
		$data['name_curr'] 	= $this->product_details_cat_model->get_currency_name($currency_id);

		$auto_search_data = $this->product_details_cat_model->get_all_services_and_tags();
		
		
		if($data['name_curr'] !='USD')
		{
			if($price_low !='' && $price_high !='')
			{
				$price_low 	= $this->translate_currency_model->get_currency_value1_another('', $data['name_curr'], 'USD', $price_low);
				$price_high 	= $this->translate_currency_model->get_currency_value1('', $data['name_curr'], 'USD', $price_high);	
			}
		}
		
		if($srch_lat !='' && $srch_lon!='')
		{
			$pos_center 	= $srch_lat.','.$srch_lon;
			$lat_center 	= $srch_lat;
			$long_center 	= $srch_lon;
		}
		else
		{
			if(trim($service_place)!='')
			{
				$prepAddr = urlencode($service_place);
				$geocode = $this->url_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
				$output= json_decode($geocode);
				
				if(isset($output->results))
				{
					$lat_center 	= $output->results[0]->geometry->location->lat;
					$lat_center 	= $lat_center == ''?0:$lat_center;
					$long_center 	= $output->results[0]->geometry->location->lng;
					$long_center 	= $long_center == ''?0:$long_center;
					$pos_center 	= $lat_center.','.$long_center;
				}	
			}
		}
		
		$total_link 	= 1;
		$limit_start 	= 0;	
		
		$all_data = array();
		
		if(!is_numeric($pageno))
			$pageno 		= 1;
			
		$limit_end 		= ($pageno > 1) ? ($pageno-1)*$perpage : 10;
		$limit_start		= ($pageno == 1) ? 0 : $perpage;
		
		$total_row 		= $this->product_details_cat_model->list_services_pagination($limit_start, $limit_end, $lat_center,$long_center, $cat_id, $service_name, $imp_servids, $price_low, $price_high,$sw_lat,$sw_long,$ne_lat,$ne_long,$srchtyp);
		$total_row 		= count($total_row);
		
		 $all_data['pagination_query'] 	= $this->db->last_query();
		//exit;
		
		$service_details 	= $this->product_details_cat_model->list_services($limit_start,$limit_end,$lat_center,$long_center,$cat_id,$service_name,$imp_servids,$price_low,$price_high,$sw_lat,$sw_long,$ne_lat,$ne_long,$srchtyp);
		
		$all_data['main_query'] 	= $this->db->last_query();
		//echo ' 2: '.$lat_center.' 3: '.$long_center.' 4: '.$cat_id.' 5: '.$service_name.' 6: '.$imp_servids.' 9: '.$sw_lat.' 10: '.$sw_long.' 11: '.$ne_lat.' 12: '.$ne_long.' 13: '.$srchtyp;
		
		$all_services 		= $this->product_details_cat_model->al_list_services($lat_center, $long_center, $cat_id, $service_name, $imp_servids, $sw_lat, $sw_long, $ne_lat, $ne_long, $srchtyp);
		
		$all_data['price_query'] 	= $this->db->last_query();
		
		if($total_row > 0){
			if($total_row % $perpage == 0)
				$total_link = $total_row/$perpage;
			else
				$total_link = ($total_row/$perpage)+1;
		}
		
		$total_link = (int)$total_link;
	
		$repeat_pos=array();
		$nodigits = 10;
		
		
		$logged_in_id  = $this->session->userdata('user_id_site');
		$to_currency 	= $data['name_curr'];
		
		if((isset($service_details)) &&(count($service_details)>0))
		{
			$all_data['results'] = array();
			//echo '<pre>'; print_r($service_details); echo '</pre>'; 
			$c=0;
			
			$product_price = array();
			
			foreach($service_details as $p=>$services)
			{
				$user_wishlist 	= $this->product_details_cat_model->check_wishlist($services->id, $logged_in_id);
				$user_wishlist_id 	= (!empty($user_wishlist) && isset($user_wishlist->id)) ? $user_wishlist->id : 0;
				
				if($user_wishlist_id)
					$wish_html = urlencode('<a id="wish_'.$p.'" href="javascript:remove_wishlist('.$user_wishlist_id.', '.$services->id.', '.$logged_in_id.', '.$p.')" style=" position: absolute; top: 5px; left: 7px;"><img src="'.base_url().'assets/images/hrt_pic2.png"></a>');
				 else
					$wish_html = urlencode('<a id="wish_'.$p.'" href="javascript:make_wishlist('.$services->id.', '.$logged_in_id.', '.$p.')" style=" position: absolute; top: 5px; left: 7px;"><img src="'.base_url().'assets/images/hrt_pic.png"></a>');
				  
				$cat_id		= $services->p_cat;
				$category_ID	= $services->category;
				$id 			= $services->id;
				
				$the_idsarr[] 	= $id;
				
				//$all_data['ids'][]	= $id;
				
				$user 		= $services->user_id;
				$product_name 	= $services->name;
				$product_desc 	= $services->description;
				
				//default is usd
				$price 		= '';
				if(isset($services->best_price))
					$price 	= $services->best_price;
				else
					$price 	= '';
				
				$currency_id 	= $services->currency_id;
				$new_price	= $price;
						
				if((isset($services->step_one) 	&& ($services->step_one=='Y')) 	&&
				(isset($services->step_two) 		&& ($services->step_two=='Y')) 	&&
				(isset($services->step_three) 	&& ($services->step_three=='Y')) 	&&
				(isset($services->step_four) 		&& ($services->step_four=='Y')) 	&&
				(isset($services->step_five) 		&& ($services->step_five=='Y')) 	&&
				(isset($services->step_six) 		&& ($services->step_six=='Y')))
				{
					$all_step 	= 1;
				}else
					$all_step 	= 0;
		  
					if($all_step==1)
					{
						$new_price = $price;
						
						$currency_id 		= $services->currency_id;
						
						//echo 'product id: '.$services->id.' product price: '.$price.'<br>';
						
						if($price != '' && $price > 0){
							//echo 'arijit '.$services->id.'<br>';
							$Data_price_old 	= $this->translate_currency_model->get_currency_value1($services->id, '', '', $price);
						}
						else
							$Data_price_old 	= 0;
						
						if(isset($Data_price_old))
							$new_price = $Data_price_old;
					
						$image_user	= "";			
						$link_name	= $services->alias_url;
						$link_id		= $link_name."-".$id;
						
						$day_search	= strtolower(date("l"));
						
						$calender_services=$this->product_details_cat_model->calender_details($id,$day_search);
						$calender_details = $this->service_details_view_model->calender_details($id);
						
						$start_date 		= $end_date = $avilable_product = '';
						
						$booking_type 		= (isset($calender_details[0]['booking_type'])) 	? $calender_details[0]['booking_type'] 	: '';
						$booking_type_opt 	= (isset($calender_details[0]['per_day_option'])) ? $calender_details[0]['per_day_option']: '';
						$start_date 		= (isset($calender_details[0]['date_start'])) 	? $calender_details[0]['date_start'] 	: '';
						$end_date 		= (isset($calender_details[0]['date_end'])) 		? $calender_details[0]['date_end'] 	: '';
						if($booking_type == 'per_day'){
							if($booking_type_opt == 'always')
								$avilable_product = '';
							elseif($booking_type_opt == 'specific'){
								if(strtotime($end_date) >= strtotime(date('Y-m-d')))
									$avilable_product = '';
								else
									$avilable_product = '<b>'.ucfirst($this->add_service_text(644)).'</b>';
							}
							else
								$avilable_product = '';
						}
						else{
							if(strtotime($end_date) > strtotime(date('Y-m-d')))
								$avilable_product = '';
							else
								$avilable_product = '<b>'.ucfirst($this->add_service_text(644)).'</b>';
						}
						
						$category_details	= $this->product_details_cat_model->get_cat_name($category_ID);
						$image_details		= $this->service_view_model->image_details($id);
						$user_details		= $this->product_details_cat_model->user_details($user);
						$user_img_details	= $this->product_details_cat_model->user_img_details($user);
						$current_lng 		= $this->session->userdata('mainsite_lang');
						$current_lng		= ($current_lng) ? $current_lng : 1;
						if($current_lng != 1){
							$table 	= "manage_category";
							$lang_featured_cat = $this->home_model->get_featured_cat_language($current_lng, $table, $category_ID);
							
							if(isset($lang_featured_cat[0]['value']) && $lang_featured_cat[0]['value'] !='')
								$category_details 	= $lang_featured_cat[0]['value'];
						}
						
						
						if(isset($user_img_details[0]->prof_image) && ($user_img_details[0]->prof_image!=""))
							$image_user =	site_url().'thumb.php?width=40&height=50&img='.base_url().'assets/user_image/thumb/'.$user_img_details[0]->prof_image;
						else
							$image_user = site_url().'thumb.php?width=40&amp;height=50&amp;img='.site_url().'assets/images/user-image.png';
						
						if($image_user!=''){
							  if(isset($user_details[0]->fb_log_status) && ($user_details[0]->fb_log_status=='Y'))
							  {
								   if(stristr($image_user, 'https://graph.facebook.com'))
								   {
									   $pos_str		= strpos($user_img_details[0]->prof_image,'?');
									   $str_cut		= substr($user_img_details[0]->prof_image,$pos_str+1,strlen($image_user));
									   $fb_prof_image	= str_replace($str_cut,"width=40&height=50",$user_img_details[0]->prof_image);
									   $image_user 	= $fb_prof_image;
								   }
							  }
						 }	
						$tot_review_new	= 0;
						$final_price 		= 0;  $final_price_range = array(); $rating_array = '';
						if($new_price)
						 {
							 $final_price = $new_price;
							 if(isset($services->total_review) && ($services->total_review!=''))
							 {
								 $tot_review		= $services->total_review;
								 $tot_review_arr	= explode('.',$tot_review);
								 $tot_review_arr1	= $tot_review_arr[0];
								 $tot_review_arr2	= $tot_review_arr[1];
								 
								 if($tot_review_arr2>50)
									 $tot_review_new	= ceil($tot_review);
								 else
									 $tot_review_new	= floor($tot_review);
							 }else{
								 $tot_review		= "";
								 $tot_review_new	= 0;
							 }
							 $rating_array 	= '<ul>';
							 
							 for($i=1;$i<=5;$i++)
							 {
								 $selected = "";
								 if(!empty($tot_review_new) && $i<=$tot_review_new)
									 $selected = "selected";
								 $rating_array .= '<li class='.$selected.'>&#9733;</li>';
							 }
							 $rating_array .= '</ul>';
						 }
						
						$services->id;
						$option_detail		= $services->option;
						$book_type		= $services->booking_type;
						$avilable_product	= '';
						if(isset($calender_services) && (count($calender_services)==0))
						 {
							 for ($p = 1; $p < 7; $p++){
								 $next_day	= strtolower(Date('l', strtotime("+".$p." days")));
								 $next_date	= Date('y-m-d', strtotime("+".$p." days"));
								 
								 
								 $check_holiday     =$this->product_details_cat_model->check_holiday($id,$next_date);
								 $calender_services_details=$this->product_details_cat_model->calender_details($id,$next_day);
								 if(isset($calender_services_details) && (count($calender_services_details)>0) && count($check_holiday)==0 )
								 {
									 //$date1=date('D m/d',strtotime($next_date)).'one';
									// $date1='Next available : '.date('D m/d',strtotime($next_date));
									 $date1 = date('D m/d',strtotime($next_date));
									 $avilable_product = '<b>'.ucfirst($this->add_service_text(644)).'</b>';
									 $calender_services=$calender_services_details;
									 break;
								 }
								 else{
									 //$date1=date('D m/d').'two';
									 //$date1='Currently available ';
									 $date1='';
									// break;
								 }
							 }
						 }
						else{
							//$date1=date('D m/d').'';
							
							$check_holiday=$this->product_details_cat_model->check_holiday($id,date('Y-m-d'));
							    if(count($check_holiday)==0)
							    {
								//$date1='Currently available ';
								$date1='';
							    }else{
								 $date1='';
								 $tds=1;
								 while($tds < 7 )	
								 {
								     $next_date=date('Y-m-d', strtotime("+".$tds." days"));
								     $check_holiday=$this->product_details_cat_model->check_holiday($id,$next_date);
								     if(count($check_holiday) ==0 )
								     {
								        //$date1='Next available : '.date('D m/d',strtotime($next_date));
									 $date1 = date('D m/d',strtotime($next_date));
									 $avilable_product = '<b>'.ucfirst($this->add_service_text(644)).'</b>';
								        break;
								     }
								     $tds++;
								 }	
								//$date1='Next available three';	 
							    }
						}
						
						$service_option_array = array(); $service_option_val = '';
						
						if($option_detail!="product")
						 {
							 if((isset($calender_services[0]->open_start) 	&&
								 ($calender_services[0]->open_start!=''))	&&
								 (isset($calender_services[0]->open_close) 	&&
								 ($calender_services[0]->open_close!='')))
							 {
								 $start_time=$calender_services[0]->open_start;
								 $start_time_new=explode(':',$start_time);
								 
								 $close_time=$calender_services[0]->open_close;
								 $close_time_new=explode(':',$close_time);
								 
								 $service_option_array['start_time_o']  	= ($start_time_new[0]) ? $start_time_new[0] : '00';
								 $service_option_array['start_time_c']  	= ($start_time_new[1]) ? $start_time_new[1] : '00';
								 $service_option_array['stime_val']  	= ($start_time_new[0]>12) ? "pm" : "am";
								 
								 $service_option_array['end_time_o']  	= ($close_time_new[0]) ? $close_time_new[0] : '00';
								 $service_option_array['end_time_c']  	= ($close_time_new[1]) ? $close_time_new[1] : '00';
								 $service_option_array['etime_val']  	= ($close_time_new[0]>12) ? "pm" : "am";
							 }
							 
							 if(!empty($service_option_array)){
								 $service_option_val = '<div class="time-sec-time">'.$service_option_array['start_time_o'].':'.$service_option_array['start_time_c'].' '.$service_option_array['stime_val'].'</div>';
								 
								 $service_option_val .= '<div class="time-sec-time">'.$service_option_array['end_time_o'].':'.$service_option_array['end_time_c'].' '.$service_option_array['etime_val'].'</div>';
							 }
						 }
						
						$service_option_val = ($service_option_val) ? urlencode($service_option_val) : '';
						
						if(strlen($product_name)>15)
							$str_name = mb_substr(trim($product_name),0,15, 'UTF-8').'..';
						else
							$str_name = $product_name;
						
						if(strlen($category_details) > 15)
							$str_cat = mb_substr(trim($category_details),0,15, 'UTF-8').'..';	
						else
							$str_cat = trim($category_details);
						
						$org_address = '';
						$address = array();
						if(isset($services->location_type) && ($services->location_type)=="location2")
						 {
							 $org_address = $services->map_address;
							 
							 if(isset($services->address_street_addr))
								 $address[] = $services->address_street_addr;	
							 if(isset($services->address_city))
								 $address[] = $services->address_city;	
							 if(isset($services->address_state))
								 $address[] =$services->address_state;	
							 if(isset($services->address_country))
								 $address[] =$services->address_country;	
						 }
						else{
							$org_address = $services->to_map_address;
							
							if(isset($services->from_city))
								$address[] =$services->from_city;	
							if(isset($services->from_state))
								$address[] =$services->from_state;	
							if(isset($services->from_country))
								$address[] =$services->from_country;	
						}
						
						$address1  = (!empty($org_address)) ? $org_address : implode(', ', array_filter($address));
						$naddress1 = $address1;
						
						$naddress = (strlen($naddress1) > 20) ? mb_substr($naddress1, 0, 20, 'utf-8').'...' : $naddress1;
						
						$infowin_image_mobile = (isset($image_details[0]->image)) ? base_url().'assets/uploads/thumb_big/'.$image_details[0]->image : base_url().'assets/images/placeholder.png';
						
						$infowin_image = (isset($image_details[0]->image)) ? base_url().'thumb.php?type=aspectfit&height=180&img='.base_url().'assets/uploads/thumb_details/'.$image_details[0]->image : base_url().'assets/images/no_image.png';
						
						$all_images = array();
						$all_images = $this->product_details_cat_model->allimages_service($id);
						$all_data['results'][$c]['img_query']	= $this->db->last_query();
						$product_images = array(); $product_img_li = $product_img_li1 = '';
						if(!empty($all_images)){
							foreach($all_images as $pi=>$all_image){
								$product_images[$pi] = base_url().'assets/uploads/thumb_details/'.$all_image['image'];
								$product_img_li  .= '<li><img src="'.base_url().'thumb.php?type=aspectfit&height=180&img='.base_url().'assets/uploads/thumb_details/'.$all_image['image'].'" alt="" /></li>';
								$product_img_li1 .= '<li><img src="'.base_url().'thumb.php?height=180&type=aspectfit&img='.base_url().'assets/uploads/thumb_details/'.$all_image['image'].'" alt="" /></li>';
							}
							$product_images = array_filter($product_images);
						}
						
						if($current_lng != 1){
							if($option_detail == 'service')
								$option_detail = $this->add_service_text('681'); 
							elseif($option_detail == 'product')
								$option_detail = $this->add_service_text('682');
						}
						
						$all_data['results'][$c]['distance']		= (string)$services->distance_by;
						$all_data['results'][$c]['id']			= (string)$id;
						$all_data['results'][$c]['user_id']		= (string)(string)$id;
						$all_data['results'][$c]['name']			= utf8_encode($str_name);
						$all_data['results'][$c]['address']  		= utf8_encode($naddress);
						$all_data['results'][$c]['address1']		= '';
						$all_data['results'][$c]['city']			= utf8_encode($services->address_city);
						$all_data['results'][$c]['cnty']			= '';
						$all_data['results'][$c]['state']  		= utf8_encode($services->address_state);
						$all_data['results'][$c]['zip']			= utf8_encode($services->address_zip_code);
						$all_data['results'][$c]['instant_book']  	= utf8_encode($services->instant_book);
						$all_data['results'][$c]['phone']  		= '';
						$all_data['results'][$c]['fax']			= '';
						$all_data['results'][$c]['cdist']			= '';
						$all_data['results'][$c]['msa']			= '';
						$all_data['results'][$c]['duns']  			= '';
						$all_data['results'][$c]['cage']			= '';
						$all_data['results'][$c]['yrest']			= '';
						$all_data['results'][$c]['contact']		= 'Arijit Kr Modak';
						$all_data['results'][$c]['title']  		= utf8_encode($str_name);
						$all_data['results'][$c]['url']			= (string)base_url()."service-product-details/".$link_id;
						$all_data['results'][$c]['busparntdunsnmb']	= '';
						$all_data['results'][$c]['exporctobjtvtxt']	= utf8_encode($str_cat);
						$all_data['results'][$c]['technetind']  	= '';
						$all_data['results'][$c]['emall']			= '';
						$all_data['results'][$c]['tof']  			= '';
						$all_data['results'][$c]['minc']			= '';
						$all_data['results'][$c]['buslastupdtdt']	= '';
						$all_data['results'][$c]['_id']			= '';
						$all_data['results'][$c]['__v']  			= (string)0;
						$all_data['results'][$c]['naics']			= array();
						$all_data['results'][$c]['latlon']  		= array((float)$services->lat_addr, (float)$services->long_addr);
						$all_data['results'][$c]['rgstrtnccrind']	= '';
						$all_data['results'][$c]['vietnam']		= '';
						$all_data['results'][$c]['dav']			= '';
						$all_data['results'][$c]['veteran']  		= '';
						$all_data['results'][$c]['women']			= '';
						$all_data['results'][$c]['exportcd']		= '';
						$all_data['results'][$c]['edi']  			= '';
						$all_data['results'][$c]['gcc']			= '';
						$all_data['results'][$c]['item']			= (string)$id;
						$all_data['results'][$c]['pos']			= (string)$c;
						$all_data['results'][$c]['link']			= (string)base_url()."service-product-details/".$link_id;
						$all_data['results'][$c]['image']			= (string)$infowin_image;
						$all_data['results'][$c]['image_mobile']	= (string)$infowin_image_mobile;
						$all_data['results'][$c]['post_page_no'] 	= (string)2;
						$all_data['results'][$c]['option_detail'] 	= (string)ucfirst($option_detail);
						$all_data['results'][$c]['product_available']= (string)$avilable_product;
						
						
						$all_data['results'][$c]['category']  		= utf8_encode(ucfirst($str_cat));
						$all_data['results'][$c]['price'] 			= (string)$new_price;
						$all_data['results'][$c]['currency']  		= (string)$data['name_curr'];
						$all_data['results'][$c]['rating']			= (string)urlencode($rating_array);
						$all_data['results'][$c]['rating_val']		= (string)$tot_review_new;
						$all_data['results'][$c]['product_date']	= (string)$date1;
						$all_data['results'][$c]['service_option']	= (string)$service_option_val;
						$all_data['results'][$c]['user_image']		= (string)$image_user;
						$all_data['results'][$c]['user_link']		= (string)site_url().'business-details/'.$user;
						$all_data['results'][$c]['product_images'] 	= $product_images;
						$all_data['results'][$c]['product_images_new'] 	= urlencode($product_img_li1);
						
						$all_data['results'][$c]['lat'] 			= (string)$services->lat_addr;
						$all_data['results'][$c]['long']  			= (string)$services->long_addr;
						
						$all_data['marker'][$c]  				= array('lat' => $services->lat_addr, 'lng' => $services->long_addr);
						
						$all_data['results'][$c]['user_wishlist']	= (string)$user_wishlist_id;
						$all_data['results'][$c]['user_wish_img']	= (string)$wish_html;
						
						$all_data['results'][$c]['info_content_new']['title'] 	= ($services->name > 30) ? utf8_encode(mb_substr(($services->name), 0, 30, 'utf-8')).'...' : utf8_encode(($services->name));
						$all_data['results'][$c]['info_content_new']['address']	= ($naddress1 > 50) ? utf8_encode(mb_substr(ucfirst($naddress1), 0, 50, 'utf-8')).'...' : utf8_encode(ucfirst($naddress1));
						$all_data['results'][$c]['info_content_new']['price']		= (string)$data['name_curr'].' '.$new_price;
						$all_data['results'][$c]['info_content_new']['images']	= $product_images;
						
						
						
						$new_name = (strlen($services->name) > 30) ? mb_substr($services->name, 0, 30, 'utf-8').'...' : $services->name;
						$new_add = (strlen($naddress1) > 100) ? mb_substr($naddress1, 0, 100, 'utf-8').'...' : $naddress1; 
						
						$all_data['results'][$c]['info_content']  	= urlencode('<div id="iw-container">
																	<div class="iw-title">'.ucfirst($new_name).'</div>
																	<div class="iw-content">
																		<div class="info_win_img" >
																			<ul class="bxslider bxslider_map">
																				'.$product_img_li.'
																			</ul>
																		</div>
																		<address class="info_win_add">'.ucwords($new_add).'</address>
																		<div class="price"><span class="info_win_title">Price: </span>'.$data['name_curr'].' '.$new_price.'</div>
																	</div>
																</div>');
						
						$c++;
					}
					//else{
					//	continue;
					//}
			}
			
			if(!empty($all_services)){
				
				foreach($all_services as $service){
					
					if(isset($service->best_price))
						$nprice 	= $service->best_price;
					else
						$nprice 	= '0';
					
					if($nprice != '' && $nprice > 0){
						$Data_price_new 	= $this->translate_currency_model->get_currency_value1($service->id, '', '', $nprice);
					}
					else
						$Data_price_new 	= 0;
					
					$product_price[$service->id]	= ceil($Data_price_new);
					
					//Seperating the results in chanks
					
					
				}
				
				
				
				
			}
			else
				$product_price = array();
			//echo '<pre>'; print_r($product_price); echo '</pre>';
			
			rsort($product_price);
			
			$config_services["base_url"] 		= '';
			$config_services["total_rows"] 	= $total_row;
			$config_services["per_page"] 		= $perpage;
			$config_services["cur_page"]		= $pageno;
			$config_services['function_name']	= 'go_to_page';
			
			$this->custom_pagination_model->initialize($config_services);
			$links_all	 				= urlencode($this->custom_pagination_model->create_links());
			
			$all_data['meta']   = array("page" => $pageno, "per_page" => $perpage, "count" => 10, "total_pages" => $total_link, 'links_all' => $links_all);
			
			$all_data['all_product_price']  	= $product_price;
			
			$all_data['req_price_range_low']  	= $org_price_low;
			$all_data['req_price_range_high']  = $org_price_high;
			
			$all_data['site_currency']  		= $data['name_curr'];
			
			$all_data['auto_search_data']		= $auto_search_data;
			
			$all_data['lat'] 				= $srch_lat;
			$all_data['lng']				= $srch_lon;
			
			$all_data['price_low'] 			= $price_low;
			$all_data['price_high']			= $price_high;
			
			$this->benchmark->mark('code_end');
			$all_data['execution_time']		= $this->benchmark->elapsed_time('code_start', 'code_end');
			
			//echo '<pre>'; print_r($all_data); echo '</pre>';
			
			//$all_data1 = utf8_encode($all_data);
			
			echo json_encode($all_data);
			
			//switch (json_last_error()) {
			//	case JSON_ERROR_NONE:
			//	    echo ' - No errors';
			//	break;
			//	case JSON_ERROR_DEPTH:
			//	    echo ' - Maximum stack depth exceeded';
			//	break;
			//	case JSON_ERROR_STATE_MISMATCH:
			//	    echo ' - Underflow or the modes mismatch';
			//	break;
			//	case JSON_ERROR_CTRL_CHAR:
			//	    echo ' - Unexpected control character found';
			//	break;
			//	case JSON_ERROR_SYNTAX:
			//	    echo ' - Syntax error, malformed JSON';
			//	break;
			//	case JSON_ERROR_UTF8:
			//	    echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
			//	break;
			//	default:
			//	    echo ' - Unknown error';
			//	break;
			//}
		}
		else
		{
			$all_data['results'] = array();
			$all_data['marker']  = array();
			$all_data['meta']   = array("page" => 1, "per_page" => 10, "count" => 0, "total_pages" => 1, 'links_all' => '');
			
			$all_data['all_product_price']  	= array();
			$all_data['auto_search_data']		= array();
			$all_data['req_price_range_low']  	= $org_price_low;
			$all_data['req_price_range_high']  = $org_price_high;
			
			$all_data['site_currency']  		= $data['name_curr'];
			 
			$all_data['lat'] 	= $srch_lat;
			$all_data['lng']	= $srch_lon;
			
			$all_data['price_low'] 			= $price_low;
			$all_data['price_high']			= $price_high;
			
			$this->benchmark->mark('code_end');
			$all_data['execution_time']		= $this->benchmark->elapsed_time('code_start', 'code_end');
			
			echo json_encode($all_data);
			
			//switch (json_last_error()) {
			//	case JSON_ERROR_NONE:
			//	    echo ' - No errors';
			//	break;
			//	case JSON_ERROR_DEPTH:
			//	    echo ' - Maximum stack depth exceeded';
			//	break;
			//	case JSON_ERROR_STATE_MISMATCH:
			//	    echo ' - Underflow or the modes mismatch';
			//	break;
			//	case JSON_ERROR_CTRL_CHAR:
			//	    echo ' - Unexpected control character found';
			//	break;
			//	case JSON_ERROR_SYNTAX:
			//	    echo ' - Syntax error, malformed JSON';
			//	break;
			//	case JSON_ERROR_UTF8:
			//	    echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
			//	break;
			//	default:
			//	    echo ' - Unknown error';
			//	break;
			//}
		}
		 
		
		 
		 //echo 'arijit '.$this->benchmark->elapsed_time('code_start', 'code_end');
	}
	
	public function get_location_details($loc=''){
		$xml = 'https://maps.google.com/maps/api/geocode/xml?address='.$loc;
				
		$ch = curl_init($xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$xml_parse = curl_exec($ch);
		curl_close($ch);
		
		$location = new SimpleXmlElement($xml_parse, LIBXML_NOCDATA);
		$loc_det = array('lat_center'=>'', 'long_center'=>'', 'sw_lat'=>'', 'sw_lng'=>'', 'ne_lat'=> '', 'ne_lng'=>'');
		if((string)$location->status == 'OK')
		{
			$loc_det['lat_center'] 	= (string)$location->result->geometry->location->lat;
			$loc_det['long_center']	= (string)$location->result->geometry->location->lng;
			
			$administrative_levels = (isset($location->result->address_component)) ? count($location->result->address_component) : '1';
			
			if($administrative_levels <= 5){
				$loc_det['sw_lat'] 	=  (isset($location->result->geometry->viewport->southwest->lat)) ? (string)$location->result->geometry->viewport->southwest->lat : 0;
				$loc_det['sw_lng'] 	=  (isset($location->result->geometry->viewport->southwest->lng)) ? (string)$location->result->geometry->viewport->southwest->lng : 0;
				$loc_det['ne_lat'] 	=  (isset($location->result->geometry->viewport->northeast->lat)) ? (string)$location->result->geometry->viewport->northeast->lat : 0;
				$loc_det['ne_lng'] 	=  (isset($location->result->geometry->viewport->northeast->lng)) ? (string)$location->result->geometry->viewport->northeast->lng : 0;
			}
		}
		
		return $loc_det;
	}
	
	
	public function index_new()
	{
		//echo 'arijit: <pre>'; print_r($_REQUEST);
		
		$data['settings'] 		= $this->sitesetting_model->get_settings();
		$data['ptitle'] 		= 'Service Or Product Listing';
		
		$link_url 			= $this->uri->segment(2);
	    
		$data['p_cats'] 		= $this->product_details_cat_model->catdrop_down();
		$data['key_details'] 	= $this->common_model->get('manage_keyword',array('name','id'),array('status'=>'Y'),null,null,null,null,'visit','desc');
		
		$data['price_high_arr'] 	= $this->product_details_cat_model->check_price_high();
		$data['price_low_arr'] 	= $this->product_details_cat_model->check_price_low();
		
		$data['page_no'] 		= 1;
		
		$data['is_searched'] 	= 0;
	    
		$view_port = array();
		$search = array("'", '+', ' '); $replace = array('', '-', '-');
		/***************Current location***************/
		$service_name 				= urldecode(($this->input->get('service_name')));
		$service_name				= str_replace($search, $replace, $service_name);
		//echo 'a: '.$service_name; die;
		
		$key_id	 				= $this->input->get('key_id');
		$service_place = $location	= urldecode($this->input->get('service_place'));
		$service_place_con 			= str_replace(' ', '+', $service_place);
		$administrative_levels 		= $this->input->get('administrative_levels');
		
		$location 				= 'Kolkata';
		$default_city				= 'Kolkata';
		$default_region			= 'WB';
		$lat_center 				= 22.572646;
		$long_center				= 88.363895;
		$view_port 				= array();
	    
		if($service_place_con !=  '')
		{
			$search_req_lat 			= $this->input->get('srch_lat');
			$search_req_lon 			= $this->input->get('srch_lon');
			
			$search_sw_lat 			= $this->input->get('sw_lat');
			$search_sw_lon 			= $this->input->get('sw_lng');
			$search_ne_lat 			= $this->input->get('ne_lat');
			$search_ne_lon 			= $this->input->get('ne_lng');
			
			if($search_req_lat != '' && $search_req_lon != '')
			{
				$data['is_searched'] 	=	1;
				
				$location 			= $service_place_con;
				
				$lat_center 			= $search_req_lat;
				$long_center 			= $search_req_lon;
				
				$view_port['sw_lat'] 	= $search_sw_lat;
				$view_port['sw_lng'] 	= $search_sw_lon;
				$view_port['ne_lat'] 	= $search_ne_lat;
				$view_port['ne_lng'] 	= $search_ne_lon;
			}
			else
			{
				$data['is_searched'] 	=	1;
				$xml = 'https://maps.google.com/maps/api/geocode/xml?address='.$service_place_con;
				
				//if(isset($data['settings'][0]['google_map_api_key']) && !empty($data['settings'][0]['google_map_api_key']))
				//	$xml = 'https://maps.google.com/maps/api/geocode/xml?key='.$data['settings'][0]['google_map_api_key'].'&address='.$service_place_con;
				//else
				//	$xml = 'https://maps.google.com/maps/api/geocode/xml?address='.$service_place_con;
				
				$ch = curl_init($xml);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$xml_parse = curl_exec($ch);
				curl_close($ch);
				
				$location = new SimpleXmlElement($xml_parse, LIBXML_NOCDATA);
				
				if((string)$location->status == 'OK')
				{
					$lat_center 	= (string)$location->result->geometry->location->lat;
					$long_center	= (string)$location->result->geometry->location->lng;
					
					$administrative_levels = (isset($location->result->address_component)) ? count($location->result->address_component) : '1';
					
					if($administrative_levels <= 5){
						$view_port['sw_lat'] 	=  (isset($location->result->geometry->viewport->southwest->lat)) ? (string)$location->result->geometry->viewport->southwest->lat : 0;
						$view_port['sw_lng'] 	=  (isset($location->result->geometry->viewport->southwest->lng)) ? (string)$location->result->geometry->viewport->southwest->lng : 0;
						$view_port['ne_lat'] 	=  (isset($location->result->geometry->viewport->northeast->lat)) ? (string)$location->result->geometry->viewport->northeast->lat : 0;
						$view_port['ne_lng'] 	=  (isset($location->result->geometry->viewport->northeast->lng)) ? (string)$location->result->geometry->viewport->northeast->lng : 0;
					}
					
					$pos_center 	= $lat_center.','.$long_center;
					if(isset($location->result->address_component[0]->long_name))
						$default_city 	= (string)$location->result->address_component[0]->long_name;
					else
						$default_city 	="";
					if(isset($location->result->address_component[4]->long_name))
						$default_region= (string)$location->result->address_component[4]->long_name;
					else
					   $default_region="";
				}
				else
				{
					$opts = array('http' =>
							array(
								'method'  => 'GET',
								'timeout' => 120 
							)
						);
				  
					$context  = stream_context_create($opts);
					
					$location_det = (unserialize(@file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR'], false, $context)));
				  
					if(!empty($location_det))
					{
						$lng = $location  = (isset($location_det['geoplugin_countryCode']) && !empty($location_det['geoplugin_countryCode'])) ? $location_det['geoplugin_countryCode'] : 'India';
						
						$all_loc_data = $this->get_location_details(str_replace(' ', '+', $location));
						
						$lat_center 	= (isset($all_loc_data['lat_center']) && !empty($all_loc_data['lat_center'])) ? $all_loc_data['lat_center'] : 	22.572646;
						$long_center 	= (isset($all_loc_data['long_center']) && !empty($all_loc_data['long_center'])) ? $all_loc_data['long_center'] : 	22.572646;
						
						$view_port['sw_lat'] 	=  (isset($all_loc_data['sw_lat'])) ? $all_loc_data['sw_lat'] : 0;
						$view_port['sw_lng'] 	=  (isset($all_loc_data['sw_lng'])) ? $all_loc_data['sw_lng'] : 0;
						$view_port['ne_lat'] 	=  (isset($all_loc_data['ne_lat'])) ? $all_loc_data['ne_lat'] : 0;
						$view_port['ne_lng'] 	=  (isset($all_loc_data['ne_lng'])) ? $all_loc_data['ne_lng'] : 0;
					}
				}
			}
		}
		else
		{
			$opts = array('http' =>
					array(
						'method'  => 'GET',
						'timeout' => 120 
					)
				);
		  
			$context  = stream_context_create($opts);
			
			$location_det = (unserialize(@file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR'], false, $context)));
			
			if(!empty($location_det))
			{
				$lng = $location  = (isset($location_det['geoplugin_countryName']) && !empty($location_det['geoplugin_countryName'])) ? $location_det['geoplugin_countryName'] : 'India';
				
				$all_loc_data = $this->get_location_details(str_replace(' ', '+', $location));
						
				$lat_center 	= (isset($all_loc_data['lat_center']) && !empty($all_loc_data['lat_center'])) ? $all_loc_data['lat_center'] : 	22.572646;
				$long_center 	= (isset($all_loc_data['long_center']) && !empty($all_loc_data['long_center'])) ? $all_loc_data['long_center'] : 	22.572646;
				
				$view_port['sw_lat'] 	=  (isset($all_loc_data['sw_lat'])) ? $all_loc_data['sw_lat'] : 0;
				$view_port['sw_lng'] 	=  (isset($all_loc_data['sw_lng'])) ? $all_loc_data['sw_lng'] : 0;
				$view_port['ne_lat'] 	=  (isset($all_loc_data['ne_lat'])) ? $all_loc_data['ne_lat'] : 0;
				$view_port['ne_lng'] 	=  (isset($all_loc_data['ne_lng'])) ? $all_loc_data['ne_lng'] : 0;
			}
		}
		
		$data['location'] 				= $location;
		$data['lat'] 					= $lat_center;
		$data['long'] 	 				= $long_center;
		$data['view_port']  			= $view_port;
		$data['administrative_levels'] 	= $administrative_levels;
		
		$dt_start=trim(str_replace("'","",$this->input->get('open_date')));
		$dt_end=trim(str_replace("'","",$this->input->get('time_open')));
		$imp_servids=0;
	    
		$data['imp_servids']=$imp_servids;
		
		$cat_arr 	= explode('-', $link_url);
		$cat_id 	= (isset($cat_arr[count($cat_arr)-1])) ? $cat_arr[count($cat_arr)-1] : '';

		if(!is_numeric($cat_id)){
			$cat_details = $this->product_details_cat_model->category_details($dt_start,$dt_end);
		}
		
		$cat_id 	= (is_numeric($cat_id)) ? $cat_id : '';
		$cat_id 	= $this->input->get('cat_id');
	  
		$currency_id	= 3;
		if($this->session->userdata('mainsite_crncy'))
		{
			$currency_id	= $this->session->userdata('mainsite_crncy');
		}
		 
		$data['name_curr'] 				= $this->product_details_cat_model->get_currency_name($currency_id);
		$data['category'] 				= $cat_id;
		$data['default_city'] 			= $default_city;
		$data['req_service_place'] 		= $service_place;
		$data['req_service_name']		= $service_name;
		$data['default_region'] 			= $default_region;
		$data['keyword_id'] 			= $key_id;
		
		if($this->session->userdata('mainsite_lang'))
			$ln_id = $this->session->userdata('mainsite_lang');
		else
			$ln_id = 1;
		//	Add descriptions for meta section
		$params 				= array('ln_id' => $ln_id,'id' => 32);
		$meta_details			= $this->meta_desc_model->initialize($params);
		$title_name 			= ($service_place) ? str_replace('-', ' ', $service_place) : '';
		
		$data['ptitle'] 		= ($title_name) ? $title_name.' - Freewilder' : $meta_details['site_title'];
		$data['pdesription'] 	= $meta_details['metaDesc'];
		$data['pkeywords']		= $meta_details['metaKey'];
		
		//echo '<pre>'; print_r($data); echo '</pre>';
		
		$this->load->view('header', $data);
		$this->load->view('product_details_cat_new', $data);
		$this->load->view('footer', $data);
	}
	
	
	public function index_new2()
	{
		
		
		$data['settings'] 		= $this->sitesetting_model->get_settings();
		$data['ptitle'] 		= 'Service Or Product Listing';
		
		$link_url 			= $this->uri->segment(2);
	    
		$data['p_cats'] 		= $this->product_details_cat_model->catdrop_down();
		$data['key_details'] 	= $this->common_model->get('manage_keyword',array('name','id'),array('status'=>'Y'),null,null,null,null,'visit','desc');
		
		$data['price_high_arr'] 	= $this->product_details_cat_model->check_price_high();
		$data['price_low_arr'] 	= $this->product_details_cat_model->check_price_low();
		
		$data['page_no'] 		= 1;
		
		$data['is_searched'] 	= 0;
	    
		$view_port = array();
		
		/***************Current location***************/
		$service_name 				= urldecode($this->input->get('service_name'));
		$key_id	 				= $this->input->get('key_id');
		$service_place = $location	= urldecode($this->input->get('service_place'));
		$service_place_con 			= str_replace(' ', '+', $service_place);
		$administrative_levels 		= $this->input->get('administrative_levels');
		
		$location 				= 'Kolkata';
		$default_city				= 'Kolkata';
		$default_region			= 'WB';
		$lat_center 				= 22.572646;
		$long_center				= 88.363895;
		$view_port 				= array();
	    
		if($service_place_con !=  '')
		{
			$search_req_lat 			= $this->input->get('srch_lat');
			$search_req_lon 			= $this->input->get('srch_lon');
			
			$search_sw_lat 			= $this->input->get('sw_lat');
			$search_sw_lon 			= $this->input->get('sw_lng');
			$search_ne_lat 			= $this->input->get('ne_lat');
			$search_ne_lon 			= $this->input->get('ne_lng');
			
			if($search_req_lat != '' && $search_req_lon != '')
			{
				$data['is_searched'] 	=	1;
				
				$location 			= $service_place_con;
				
				$lat_center 			= $search_req_lat;
				$long_center 			= $search_req_lon;
				
				$view_port['sw_lat'] 	= $search_sw_lat;
				$view_port['sw_lng'] 	= $search_sw_lon;
				$view_port['ne_lat'] 	= $search_ne_lat;
				$view_port['ne_lng'] 	= $search_ne_lon;
			}
			else
			{
				$data['is_searched'] 	=	1;
				$xml = 'https://maps.google.com/maps/api/geocode/xml?address='.$service_place_con;
				
				$ch = curl_init($xml);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$xml_parse = curl_exec($ch);
				curl_close($ch);
				
				$location = new SimpleXmlElement($xml_parse, LIBXML_NOCDATA);
				
				if((string)$location->status == 'OK')
				{
					$lat_center 	= (string)$location->result->geometry->location->lat;
					$long_center	= (string)$location->result->geometry->location->lng;
					
					$administrative_levels = (isset($location->result->address_component)) ? count($location->result->address_component) : '1';
					
					if($administrative_levels <= 5){
						$view_port['sw_lat'] 	=  (isset($location->result->geometry->viewport->southwest->lat)) ? (string)$location->result->geometry->viewport->southwest->lat : 0;
						$view_port['sw_lng'] 	=  (isset($location->result->geometry->viewport->southwest->lng)) ? (string)$location->result->geometry->viewport->southwest->lng : 0;
						$view_port['ne_lat'] 	=  (isset($location->result->geometry->viewport->northeast->lat)) ? (string)$location->result->geometry->viewport->northeast->lat : 0;
						$view_port['ne_lng'] 	=  (isset($location->result->geometry->viewport->northeast->lng)) ? (string)$location->result->geometry->viewport->northeast->lng : 0;
					}
					
					$pos_center 	= $lat_center.','.$long_center;
					if(isset($location->result->address_component[0]->long_name))
						$default_city 	= (string)$location->result->address_component[0]->long_name;
					else
						$default_city 	="";
					if(isset($location->result->address_component[4]->long_name))
						$default_region= (string)$location->result->address_component[4]->long_name;
					else
					   $default_region="";
				}
				else
				{
					$opts = array('http' =>
							array(
								'method'  => 'GET',
								'timeout' => 120 
							)
						);
				  
					$context  = stream_context_create($opts);
					
					$location_det = (unserialize(@file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR'], false, $context)));
				  
					if(!empty($location_det))
					{
						$lng = $location  = (isset($location_det['geoplugin_countryCode']) && !empty($location_det['geoplugin_countryCode'])) ? $location_det['geoplugin_countryCode'] : 'India';
						
						$all_loc_data = $this->get_location_details(str_replace(' ', '+', $location));
						
						$lat_center 	= (isset($all_loc_data['lat_center']) && !empty($all_loc_data['lat_center'])) ? $all_loc_data['lat_center'] : 	22.572646;
						$long_center 	= (isset($all_loc_data['long_center']) && !empty($all_loc_data['long_center'])) ? $all_loc_data['long_center'] : 	22.572646;
						
						$view_port['sw_lat'] 	=  (isset($all_loc_data['sw_lat'])) ? $all_loc_data['sw_lat'] : 0;
						$view_port['sw_lng'] 	=  (isset($all_loc_data['sw_lng'])) ? $all_loc_data['sw_lng'] : 0;
						$view_port['ne_lat'] 	=  (isset($all_loc_data['ne_lat'])) ? $all_loc_data['ne_lat'] : 0;
						$view_port['ne_lng'] 	=  (isset($all_loc_data['ne_lng'])) ? $all_loc_data['ne_lng'] : 0;
					}
				}
			}
		}
		else
		{
			$opts = array('http' =>
					array(
						'method'  => 'GET',
						'timeout' => 120 
					)
				);
		  
			$context  = stream_context_create($opts);
			
			$location_det = (unserialize(@file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR'], false, $context)));
			
			if(!empty($location_det))
			{
				$lng = $location  = (isset($location_det['geoplugin_countryName']) && !empty($location_det['geoplugin_countryName'])) ? $location_det['geoplugin_countryName'] : 'India';
				
				$all_loc_data = $this->get_location_details(str_replace(' ', '+', $location));
						
				$lat_center 	= (isset($all_loc_data['lat_center']) && !empty($all_loc_data['lat_center'])) ? $all_loc_data['lat_center'] : 	22.572646;
				$long_center 	= (isset($all_loc_data['long_center']) && !empty($all_loc_data['long_center'])) ? $all_loc_data['long_center'] : 	22.572646;
				
				$view_port['sw_lat'] 	=  (isset($all_loc_data['sw_lat'])) ? $all_loc_data['sw_lat'] : 0;
				$view_port['sw_lng'] 	=  (isset($all_loc_data['sw_lng'])) ? $all_loc_data['sw_lng'] : 0;
				$view_port['ne_lat'] 	=  (isset($all_loc_data['ne_lat'])) ? $all_loc_data['ne_lat'] : 0;
				$view_port['ne_lng'] 	=  (isset($all_loc_data['ne_lng'])) ? $all_loc_data['ne_lng'] : 0;
			}
		}
		
		$data['location'] 				= $location;
		$data['lat'] 					= $lat_center;
		$data['long'] 	 				= $long_center;
		$data['view_port']  			= $view_port;
		$data['administrative_levels'] 	= $administrative_levels;
		
		$dt_start=trim(str_replace("'","",$this->input->get('open_date')));
		$dt_end=trim(str_replace("'","",$this->input->get('time_open')));
		$imp_servids=0;
	    
		$data['imp_servids']=$imp_servids;
		
		$cat_arr 	= explode('-', $link_url);
		$cat_id 	= (isset($cat_arr[count($cat_arr)-1])) ? $cat_arr[count($cat_arr)-1] : '';

		if(!is_numeric($cat_id)){
			$cat_details = $this->product_details_cat_model->category_details($dt_start,$dt_end);
		}
		
		$cat_id 	= (is_numeric($cat_id)) ? $cat_id : '';
		$cat_id 	= $this->input->get('cat_id');
	  
		$currency_id	= 3;
		if($this->session->userdata('mainsite_crncy'))
		{
			$currency_id	= $this->session->userdata('mainsite_crncy');
		}
		 
		$data['name_curr'] 				= $this->product_details_cat_model->get_currency_name($currency_id);
		$data['category'] 				= $cat_id;
		$data['default_city'] 			= $default_city;
		$data['req_service_place'] 		= $service_place;
		$data['req_service_name']		= $service_name;
		$data['default_region'] 			= $default_region;
		$data['keyword_id'] 			= $key_id;
		
		if($this->session->userdata('mainsite_lang'))
			$ln_id = $this->session->userdata('mainsite_lang');
		else
			$ln_id = 1;
		//	Add descriptions for meta section
		$params 				= array('ln_id' => $ln_id,'id' => 32);
		$meta_details			= $this->meta_desc_model->initialize($params);
		$title_name 			= ($service_place) ? str_replace('-', ' ', $service_place) : '';
		
		$data['ptitle'] 		= ($title_name) ? $title_name.' - Freewilder' : $meta_details['site_title'];
		$data['pdesription'] 	= $meta_details['metaDesc'];
		$data['pkeywords']		= $meta_details['metaKey'];
		
		//echo '<pre>'; print_r($data); echo '</pre>';
		
		$data['footer_data']	= $data;
		
		$this->load->view('header', $data);
		$this->load->view('product_details_cat_new1', $data);
	}
	
	function make_price_chanks($price_arr=array())
	{
		$all_services 		= $this->product_details_cat_model->al_list_services('0','10','22.572646','88.36389499999996','','','0','','','22.3436288','88.19430439999996','23.0078201','88.54286960000002',1);
		
		foreach($all_services as $service){
			
			if(isset($service->best_price))
				$nprice 	= $service->best_price;
			else
				$nprice 	= '0';
			
			if($nprice != '' && $nprice > 0)
				$Data_price_new 	= $this->translate_currency_model->get_currency_value1($service->id, '', '', $nprice);
			else
				$Data_price_new 	= 0;
			
			$product_price[]	= $Data_price_new;
		}
		
		sort($product_price);
		
		echo '<pre>'; print_r($product_price); echo '</pre>';
		
		$lowest_price 	= 0;
		$heighst_price = round($product_price[count($product_price) - 1]);
		
		//Get heightst price length
		$hprice_length = strlen((string)$heighst_price);
		
		echo 'arijti: '.$hprice_length;
		
		$berak_point = 10; $chunk_array = array();
		
		if($hprice_length > 0 && $hprice_length <= 5)
			$berak_point = 100;
		elseif($hprice_length > 5 && $hprice_length <= 7)
			$berak_point = 1000;
		elseif($hprice_length > 7 && $hprice_length <= 8)
			$berak_point = 10000;
		elseif($hprice_length > 8 && $hprice_length <= 11)
			$berak_point = 100000;
		else
			$berak_point = 1000000;
		
		$count = 0;
		foreach($product_price as $p => $price){
			if($price <= $berak_point)
				$chunk_array[$count][] = $price;
			else{
				$berak_point = $berak_point * 2;
				$count++;
				$chunk_array[$count][] = $price;
			}
		}
		
		echo '<pre>'; print_r($chunk_array); echo '</pre>';
		
		$html = '<style>.main_bulid_cointainer{width: 500px; margin: 0 auto;}.each_block{display: inline-block; background: #bababa; border: 1px solid #bababa;}</style><div class="main_bulid_cointainer">';
		
		if(!empty($chunk_array)){
			$max_height 	= '20px'; $height = 0;
			$totla_splits 	= count($chunk_array);
			$width 		= (90 / $totla_splits);
			
			foreach($chunk_array as $k=>$chunk)
			{
				$style = $height = '';
				//if(count($chunk) > 20) $height = 20;
				$height = count($chunk);
				
				$style = "height: ".$height."px; width: ".$width.'%;';
				
				$html .= '<div class="each_block part_'.count($chunk).'" style="'.$style.'"></div>';
			}
		}
		
		$html .= '</div>';
		
		echo $html;
	}
	
	public function map()
	{
		$this->load->library('googlemaps');
		$config['center'] = '1600 Amphitheatre Parkway in Mountain View, Santa Clara County, California';
		$config['zoom'] = '6'; // before edit zoom was 13
		$config['styles'] = array(
				array("name"=>"Red Parks", "definition"=>array(
				array("featureType"=>"all", "stylers"=>array(array("saturation"=>"-30"))),
				array("featureType"=>"poi.park", "stylers"=>array(array("saturation"=>"10"), array("hue"=>"#990000")))
				 )),
				 array("name"=>"Black Roads", "definition"=>array(
				array("featureType"=>"all", "stylers"=>array(array("saturation"=>"-70"))),
				array("featureType"=>"road.arterial", "elementType"=>"geometry", "stylers"=>array(array("hue"=>"#000000")))
				)),
				array("name"=>"No Businesses", "definition"=>array(
				array("featureType"=>"poi.business", "elementType"=>"labels", "stylers"=>array(array("visibility"=>"off")))
				))
				);
		$config['stylesAsMapTypes'] = true;
		$config['stylesAsMapTypesDefault'] = "Black Roads"; 
		$this->googlemaps->initialize($config);
		$data['map'] = $this->googlemaps->create_map();
		//$this->load->view('view_file', $data);
		$this->load->view('map', $data);
	}
 }       
 ?>       