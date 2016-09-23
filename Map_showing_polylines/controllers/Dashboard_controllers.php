<?php

class Dashboard_controllers extends CI_Controller {

	public $radius_range 		= 5;
	public $refundable_percent	= 20;
	public $system_timezone 		= 'UTC';
	public $geonames_username	= "arijit2016";

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Home_model');
		$this->load->model('sitesetting_model');
		$this->load->model('Users_model');
		$this->load->library('ImageThumb');
		$this->load->model('myaccount_model');
		$this->load->model('User_email_model');
		$this->load->model('User_notifications_model');
		
		//Added php gzip compression
		$controller_name 	= $this->router->fetch_class();
		$function_name 	= $this->router->fetch_method();
		
		if($function_name != 'map_search_result')
		{
			if(!$this->session->userdata('site_is_logged_in'))
				redirect('');
		}
		
		$settings 				= $this->sitesetting_model->get_settings();
		$this->refundable_percent	= (isset($settings[0]['refundable_deposit_percent'])) 	? $settings[0]['refundable_deposit_percent'] : $this->refundable_percent;
		$this->system_timezone		= (isset($settings[0]['system_timezone'])) 			? $settings[0]['system_timezone'] 			: $this->system_timezone;
		$this->geonames_username 	= (isset($settings[0]['geonames_username'])) 		? $settings[0]['geonames_username'] 		: $this->geonames_username;
	}
	
	//Dashboard index function to show the page
	public function index()
	{
		$user_id = ($this->session->userdata('site_user_objId_hotcargo')) ?  $this->session->userdata('site_user_objId_hotcargo') : 1;
		$data['data']['user_id'] 		= $user_id;
		
		$data['data']['settings'] 		= $this->sitesetting_model->get_settings();
		$site_name 					= (isset($data['data']['settings'][0]['site_name'])) ? $data['data']['settings'][0]['site_name']  : '';
		
		//Getting user details
		$this->mongo_db->where(array('_id' => $user_id));
		$myaccount_data 				= $this->mongo_db->get('site_users');
		$data['data']['myaccount_data']	= (isset($myaccount_data[0])) ? $myaccount_data[0] : $myaccount_data;
		
		$this->mongo_db->where(array('user_id' => $user_id, 'status' => '1'));
		$users_stripe_details 			= $this->mongo_db->get('users_stripe_details');
		$data['data']['user_stripe_data']	= (isset($users_stripe_details[0])) ? $users_stripe_details[0] : array();
		
		//Get terms contents
		$this->mongo_db->where(array('page_alias' => 'terms-conditions'));
		$static_contents 		= $this->mongo_db->get('static_contents');
		
		$data['data']['terms_conditions']	= (isset($static_contents[0]['page_content'])) ? $static_contents[0]['page_content'] : '';
		
		$this->mongo_db->where(array('menu_type' => '1', 'status' => '1', 'menu_location' => '1'));
		$this->mongo_db->order_by(array('_id' => 'asc'));
		$users_all_menus 			= $this->mongo_db->get('menus');
		$data['data']['users_all_menus']	= (isset($users_all_menus)) ? $users_all_menus : array();
		
		$user_type 	= $this->session->userdata('site_user_type_hotcargo');
		
		
		//Get all type of payment options
		$this->mongo_db->where(array('status' => '1'));
		$payment_types 				= $this->mongo_db->get('payment_types');
		$data['data']['payment_types']	= $payment_types;
		
		//Get all users selected countries
		$this->mongo_db->where(array('user_id' => $user_id));
		$user_job_countries 	= $this->mongo_db->get('user_job_countries');
		$country_name 			= array();
		
		if(!empty($user_job_countries))
		{
			$all_user_countries = isset($user_job_countries[0]['countries']) ? $user_job_countries[0]['countries'] : array();
			
			if(!empty($all_user_countries))
			{
				foreach($all_user_countries as $k => $country)
				{
					//Get all users selected countries
					$this->mongo_db->where(array('_id' => $country));
					$country_det 		= $this->mongo_db->get('countries');
					
					$country_name[$k]['name']= (isset($country_det[0]['name'])) ? $country_det[0]['name'] : '';
					$country_view_port 		= $this->get_location_details(str_replace(' ', '+', trim($country_det[0]['name'])));
					
					$country_name[$k]['view_port']= $country_view_port;
				}
			}
		}
		
		
		$data['countries'] 	= $country_name;
		$data['ptitle'] 	= ($site_name) ? 'Dashboard - '.ucfirst($site_name) : 'Dashboard';
		$data['view_link'] 	= 'site/dashboard/index';
		$this->load->view('includes/template_site', $data);
	}
	
	public function check_json($string = '')
	{
		if(is_string($string))
		{
			json_decode($string);
			return (json_last_error() == JSON_ERROR_NONE) ? 1 : 0;
		}
		else
			return 0;
	}
	
	public function get_location_details($loc = '')
	{
		$xml 		= 'https://maps.google.com/maps/api/geocode/xml?address='.$loc;
		$ch 			= curl_init($xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$xml_parse 	= curl_exec($ch);
		curl_close($ch);
		
		$location 	= new SimpleXmlElement($xml_parse, LIBXML_NOCDATA);
		$loc_det 		= array('lat_center'=>'', 'long_center'=>'', 'sw_lat'=>'', 'sw_lng'=>'', 'ne_lat'=> '', 'ne_lng'=>'');
		
		if((string)$location->status == 'OK')
		{
			$loc_det['lat_center'] 	= (string)$location->result->geometry->location->lat;
			$loc_det['long_center']	= (string)$location->result->geometry->location->lng;
			
			$loc_det['sw_lat'] 	=  (isset($location->result->geometry->viewport->southwest->lat)) ? (string)$location->result->geometry->viewport->southwest->lat : 0;
			$loc_det['sw_lng'] 	=  (isset($location->result->geometry->viewport->southwest->lng)) ? (string)$location->result->geometry->viewport->southwest->lng : 0;
			$loc_det['ne_lat'] 	=  (isset($location->result->geometry->viewport->northeast->lat)) ? (string)$location->result->geometry->viewport->northeast->lat : 0;
			$loc_det['ne_lng'] 	=  (isset($location->result->geometry->viewport->northeast->lng)) ? (string)$location->result->geometry->viewport->northeast->lng : 0;
			
		}
		
		return $loc_det;
	}
	
	public function convert_to_string($data= '', $num = '1')
	{
		return $data; 
	}
	
	//Function to do actual search based on all search params
	public function map_search_result()
	{
		$user_id 		= $this->session->userdata('site_user_objId_hotcargo');
		$user_type 	= $this->session->userdata('site_user_type_hotcargo');
		
		$myaccount_data= $this->myaccount_model->get_account_data($user_id);
		
		//Getting all search params
		$search_place 	= $this->input->get('search_place');
		$dateRange 	= $this->input->get('dateRange');
		$priceRange 	= $this->input->get('priceRange');
		$search_type 	= $this->input->get('search_type');
		
		$srch_lat 	= $this->input->get('srch_lat');
		$srch_lon 	= $this->input->get('srch_lon');
		
		$sw_lat 		= $this->convert_to_string($this->input->get('sw_lat'), 2);
		$sw_lng 		= $this->convert_to_string($this->input->get('sw_lng'), 2);
		$ne_lat 		= $this->convert_to_string($this->input->get('ne_lat'), 2);
		$ne_lng 		= $this->convert_to_string($this->input->get('ne_lng'), 2);
		$is_loggedin	= 1;
		
		$user_job_c 	= array();
		//Getting user's all aelected countries
		$this->mongo_db->where(array('user_id' => $user_id));
		$user_job_counties 	 = $this->mongo_db->get('user_job_countries'); 
		
		if(!empty($user_job_counties))
		{
			$user_countries = isset($user_job_counties[0]['countries']) ? $user_job_counties[0]['countries'] : array();
			if(!empty($user_countries))
			{
				foreach($user_countries as $c => $country)
				{
					$this->mongo_db->where(array('_id' => strval($country)));
					$country_det 		= $this->mongo_db->get('countries'); 
					$user_job_c[]		= (isset($country_det[0]['iso'])) 	? $country_det[0]['iso'] : '';
				}
			}
		}
		
		
		if($this->session->userdata('site_is_logged_in'))
		{
			$where_arr 	= $where_gt = $where_lt = $where_lte = $order_arr = array();
			$where_in_val 	= array(); 
			$where_gt1_fld = $where_gt2_fld = $where_lt1_fld = $where_lt2_fld = $where_gt1_value = $where_gt2_value = $where_lt1_value =  $where_lt2_value = '';
			
			//If search lat & lng given then search with it
			if(!empty($srch_lat) && !empty($srch_lon))
			{
				//if search viewport exist then use viewport search
				if(!empty($sw_lat) && !empty($sw_lng) && !empty($ne_lat) && !empty($ne_lng))
				{
					//View port search query array
					$where_gt1_fld = 'company_address.lat'; 	$where_gt1_value 	= $sw_lat;
					$where_gt2_fld = 'company_address.long'; 	$where_gt2_value 	= $sw_lng;
					
					$where_lt1_fld = 'company_address.lat'; 	$where_lt1_value 	= $ne_lat;
					$where_lt2_fld = 'company_address.long';	$where_lt2_value 	= $ne_lng;
				}
				//if search viewport not given then use lat and lng for radious search
				else
				{
					//radious search query array
					$where_lte 	= array('( 3959 * acos( cos( radians(' . $srch_lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $srch_lon . ') ) * sin( radians( lat_addr ) ) ) ) as search_radious' => $this->radius_range );
				}
			}
			//If search lat & lng not given but search address given then get the lat and lng for the address and searcg with it
			elseif(!empty($search_place))
			{
				$all_loc_data 	= $this->get_location_details(str_replace(' ', '+', $search_place));
						
				$srch_lat 	= (isset($all_loc_data['lat_center']) && !empty($all_loc_data['lat_center'])) ? $all_loc_data['lat_center'] : 	'';
				$srch_lon 	= (isset($all_loc_data['long_center']) && !empty($all_loc_data['long_center'])) ? $all_loc_data['long_center'] : 	'';
				
				$sw_lat 		=  (isset($all_loc_data['sw_lat'])) ? $all_loc_data['sw_lat'] : '';
				$sw_lng 		=  (isset($all_loc_data['sw_lng'])) ? $all_loc_data['sw_lng'] : '';
				$ne_lat 		=  (isset($all_loc_data['ne_lat'])) ? $all_loc_data['ne_lat'] : '';
				$ne_lng 		=  (isset($all_loc_data['ne_lng'])) ? $all_loc_data['ne_lng'] : '';
				
				//if search viewport exist then use viewport search
				if(!empty($sw_lat) && !empty($sw_lng) && !empty($ne_lat) && !empty($ne_lng))
				{
					//View port search query array
					$where_gt1_fld = 'company_address.lat'; 	$where_gt1_value 	= $sw_lat;
					$where_gt2_fld = 'company_address.long'; 	$where_gt2_value 	= $sw_lng;
					
					$where_lt1_fld = 'company_address.lat'; 	$where_lt1_value 	= $ne_lat;
					$where_lt2_fld = 'company_address.long';	$where_lt2_value 	= $ne_lng;
				}
				//if search viewport not given then use lat and lng for radious search
				else
				{
					//radious search query array
					$where_lte 	= array('( 3959 * acos( cos( radians(' . $srch_lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $srch_lon . ') ) * sin( radians( lat_addr ) ) ) ) as search_radious' => $this->radius_range );
				}
			}
			
			//Add conditions for search
			$where_arr 		= array('status' 		=> '1');
			$order_arr		= array('first_name' 	=> 'asc');
			
			//Add search user types to search
			if(!empty($search_type)){
				$user_types 	= explode(',', 	 $search_type);
				
				//if(($key = array_search('job', $user_types)) !== false) {
				//	unset($user_types[$key]);
				//}
				
				$where_in_fld 	= 'user_type';
				$where_in_val 	= $user_types;
			}
			
			
			//Main where condition to fetch data
			if(!empty($where_arr))		$this->mongo_db->where($where_arr);
			
			//Adding Greater than conditions to fetch data
			if(!empty($where_gt1_fld))	$this->mongo_db->where_gt($where_gt1_fld, (float)$where_gt1_value);
			if(!empty($where_gt2_fld))	$this->mongo_db->where_gt($where_gt2_fld, (float)$where_gt2_value);
			
			//Adding Less than conditions to fetch data
			if(!empty($where_lt1_fld))	$this->mongo_db->where_lt($where_lt1_fld, (float)$where_lt1_value);
			if(!empty($where_lt2_fld))	$this->mongo_db->where_lt($where_lt2_fld, (float)$where_lt2_value);
			
			//Adding Less than equal conditions to fetch data
			if(!empty($where_lte)) 		$this->mongo_db->where_lte($where_lte);
			
			//Adding Where in conditions to fetch data
			if(!empty($where_in_fld))	$this->mongo_db->where_in($where_in_fld, $where_in_val);
			
			//Adding order to filter data
			if(!empty($order_arr))		$this->mongo_db->order_by($order_arr);
			
			if(!empty($search_type))		$all_details 	= $this->mongo_db->get('site_users', 0); 
			else						$all_details 	= array();
			
			//echo '<pre>'; print_r($all_details); echo '</pre>'; 
		
			if(!empty($search_type)){
				
				$user_types 	= explode(',', 	 $search_type);
				if(in_array('job', $user_types))
				{
					//if search viewport exist then use viewport search
					if(!empty($search_place) && !empty($sw_lat) && !empty($sw_lng) && !empty($ne_lat) && !empty($ne_lng))
					{
						//View port search query array
						$where_gt1_fld = 'pickup_address.lat'; 		$where_gt1_value 	= $sw_lat;
						$where_gt2_fld = 'pickup_address.long'; 	$where_gt2_value 	= $sw_lng;
						
						$where_lt1_fld = 'pickup_address.lat'; 		$where_lt1_value 	= $ne_lat;
						$where_lt2_fld = 'pickup_address.long';		$where_lt2_value 	= $ne_lng;
						
						//Adding Greater than conditions to fetch data
						if(!empty($where_gt1_fld))	$this->mongo_db->where_gt($where_gt1_fld, (float)$where_gt1_value);
						if(!empty($where_gt2_fld))	$this->mongo_db->where_gt($where_gt2_fld, (float)$where_gt2_value);
						
						//Adding Less than conditions to fetch data
						if(!empty($where_lt1_fld))	$this->mongo_db->where_lt($where_lt1_fld, (float)$where_lt1_value);
						if(!empty($where_lt2_fld))	$this->mongo_db->where_lt($where_lt2_fld, (float)$where_lt2_value);
					}
					
					if(!empty($user_job_c))
					{
						$this->mongo_db->where_in('pickup_address.country_code', $user_job_c);
					}
					
					if(!empty($priceRange))
					{
						$priceRange_arr = explode(',', $priceRange);
						if(count($priceRange_arr) > 1)
						{
							$price_high 	= isset($priceRange_arr[1]) 	? $priceRange_arr[1] : 0;
							$price_high 	= ($price_high == '1000000')	? 0 : $price_high;
							
							$price_low 	= isset($priceRange_arr[0]) 	? $priceRange_arr[0] : 0;
							$price_low 	= ($price_low == '0')		? 0 : $price_low;
							
							//Adding greater conditions to fetch data
							if(!empty($price_low)) 	$this->mongo_db->where_gte('cargo_value', (float)$price_low);
							
							//Adding Less than conditions to fetch data
							if(!empty($price_high))	$this->mongo_db->where_lte('cargo_value', (float)$price_high);
						}
					}
					
					//if(!empty($dateRange))
					//{
					//	$dateRange_arr = explode(',', $dateRange);
					//	if(count($dateRange_arr) > 1)
					//	{
					//		$date_high 	= $dateRange_arr[0];
					//		$date_low 	= $dateRange_arr[1];
					//		
					//		//View port search query array
					//		$where_job_gt2_fld = 'delivery_date'; 	$where_job_gt2_value 	= $price_high;
					//		$where_job_lt2_fld = 'delivery_date';	$where_job_lt2_value 	= $price_low;
					//		
					//		//Adding Greater than conditions to fetch data
					//		if(!empty($where_job_gt2_fld))	$this->mongo_db->where_gt($where_job_gt2_fld, $where_job_gt2_value);
					//		
					//		//Adding Less than conditions to fetch data
					//		if(!empty($where_job_lt2_fld))	$this->mongo_db->where_lt($where_job_lt2_fld, $where_job_lt2_value);
					//	}
					//}
					
					//Getting all jobs of this user
					$this->mongo_db->where(array('status' => '1'));
					
					//$this->mongo_db->where_lt('delivery_date', date('Y-m-d H:i:s'));
					//if($user_type == 'customer') $this->mongo_db->where(array('user_id' = $user_id));
					$job_lists 	= $this->mongo_db->get('jobs');
				}
			}
		}
		else{
			$all_details 	= $job_lists = array();
			$is_loggedin 	= 0;
		}
		
		//echo '<pre>'; print_r($all_details); echo '</pre>'; 
		$all_users 	= array();
		
		//if datas are avilable the format to to use firther
		if(!empty($all_details))
		{
			foreach($all_details as $k => $details)
			{
				$company_address = $delivery_address = $pickup_address = $home_address = array('address' => '', 'lat' => '', 'long' => '');
				$main_lat = $main_long = '';
				$icon 	= base_url().'assets/site/map/img/normal.png';
				
				//getting all type of address and their informations
				if(isset($details['company_address']))
				{
					//echo '<pre>'; print_r($details['company_address']); echo '</pre>';
					$user_id 		= (isset($details['_id'])) ? $details['_id'] : '';
					
					//Getting user rating
					$this->mongo_db->where(array('user_id' => strval($user_id), 'status' => "1"));
					$user_rating 	= $this->mongo_db->get('user_rating');
					
					//echo '<pre>'; print_r($user_rating); echo '</pre>';
					
					if(!is_array($details['company_address']))
					{
						$obj_cmp_addr 						= ($this->check_json($details['company_address'])) ? json_decode($details['company_address']) : $details['company_address'];
						$company_address['address'] 			= (isset($obj_cmp_addr->address)) 	? $obj_cmp_addr->address : $obj_cmp_addr;
						$company_address['lat'] 				= (isset($obj_cmp_addr->lat)) 	? $obj_cmp_addr->lat 	: '';
						$company_address['long'] 			= (isset($obj_cmp_addr->long)) 	? $obj_cmp_addr->long 	: '';
						
						$main_lat 						= (isset($obj_cmp_addr->lat)) 	? $obj_cmp_addr->lat 	: '';
						$main_long 						= (isset($obj_cmp_addr->long)) 	? $obj_cmp_addr->long 	: '';
					}
					else
					{
						$obj_cmp_addr 						= $details['company_address'];
						$company_address['address'] 			= (isset($obj_cmp_addr['address']))? $obj_cmp_addr['address'] : $obj_cmp_addr;
						$company_address['lat'] 				= (isset($obj_cmp_addr['lat'])) 	? $obj_cmp_addr['lat'] 	: '';
						$company_address['long'] 			= (isset($obj_cmp_addr['long'])) 	? $obj_cmp_addr['long'] 	: '';
						
						$main_lat 						= (isset($obj_cmp_addr['lat'])) 	? $obj_cmp_addr['lat'] 	: '';
						$main_long 						= (isset($obj_cmp_addr['long'])) 	? $obj_cmp_addr['long'] 	: '';
					}
				}
				
				elseif(isset($details['delivery_address']))
				{
					if(!is_array($details['delivery_address']))
					{
						$obj_dev_addr 						= ($this->check_json($details['delivery_address'])) ? json_decode($details['delivery_address']) : $details['delivery_address'];
						$delivery_address['address'] 			= (isset($obj_dev_addr->address)) 	? $obj_dev_addr->address : $obj_dev_addr;
						$delivery_address['lat'] 			= (isset($obj_dev_addr->lat)) 	? $obj_dev_addr->lat 	: '';
						$delivery_address['long'] 			= (isset($obj_dev_addr->long)) 	? $obj_dev_addr->long 	: '';
					}
					else
					{
						$obj_dev_addr 						= $details['delivery_address'];
						$delivery_address['address'] 			= (isset($obj_dev_addr['address'])) 	? $obj_dev_addr['address'] : $obj_dev_addr;
						$delivery_address['lat'] 			= (isset($obj_dev_addr['lat'])) 	? $obj_dev_addr['lat'] 	: '';
						$delivery_address['long'] 			= (isset($obj_dev_addr['long'])) 	? $obj_dev_addr['long'] 	: '';
					}
				}
				
				elseif(isset($details['pickup_address']))
				{
					if(!is_array($details['pickup_address']))
					{
						$obj_pick_addr 					= ($this->check_json($details['pickup_address'])) ? json_decode($details['pickup_address']) : $details['pickup_address'];
						$pickup_address['address'] 			= (isset($obj_pick_addr->address)) ? $obj_pick_addr->address : $obj_pick_addr;
						$pickup_address['lat'] 				= (isset($obj_pick_addr->lat))	? $obj_pick_addr->lat 	 : '';
						$pickup_address['long'] 				= (isset($obj_pick_addr->long)) 	? $obj_pick_addr->long 	 : '';
					}
					else
					{
						$obj_pick_addr 					= $details['pickup_address'];
						$pickup_address['address'] 			= (isset($obj_pick_addr['address'])) ? $obj_pick_addr['address'] : $obj_pick_addr;
						$pickup_address['lat'] 				= (isset($obj_pick_addr['lat']))	? $obj_pick_addr['lat'] 	 : '';
						$pickup_address['long'] 				= (isset($obj_pick_addr['long'])) 	? $obj_pick_addr['long'] 	 : '';
					}
				}
				
				elseif(isset($details['home_address']))
				{
					if(!is_array($details['pickup_address']))
					{
						$obj_home_addr 					= ($this->check_json($details['home_address'])) ? json_decode($details['home_address']) : $details['home_address'];
						$home_address['address'] 			= (isset($obj_home_addr->address)) ? $obj_home_addr->address : $obj_home_addr;
						$home_address['lat'] 				= (isset($obj_home_addr->lat)) 	? $obj_home_addr->lat 	 : '';
						$home_address['long'] 				= (isset($obj_home_addr->long)) 	? $obj_home_addr->long 	 : '';
					}
					else
					{
						$obj_home_addr 					= $details['home_address'];
						$home_address['address'] 			= (isset($obj_home_addr['address'])) ? $obj_home_addr['address'] : $obj_home_addr;
						$home_address['lat'] 				= (isset($obj_home_addr['lat'])) 	? $obj_home_addr['lat'] 	 : '';
						$home_address['long'] 				= (isset($obj_home_addr['long'])) 	? $obj_home_addr['long'] 	 : '';
					}
				}
				
				
				//getting respective user marker icon
				if(isset($details['user_type']) && ($details['user_type'] == 'customer'))
					$icon = base_url().'assets/site/images/green-man.png';
				elseif(isset($details['user_type']) && ($details['user_type'] == 'driver'))
					$icon = base_url().'assets/site/images/yellow-man.png';
				elseif(isset($details['user_type']) && ($details['user_type'] == 'depot'))
					$icon = base_url().'assets/site/images/home-man.png';
				elseif(isset($details['user_type']) && ($details['user_type'] == 'fleet'))
					$icon = base_url().'assets/site/images/red-man.png';
				elseif(isset($details['user_type']) && ($details['user_type'] == 'broker'))
					$icon = base_url().'assets/site/images/cyan-man.png';
			
				$info_content 	= '<div class="info_header"><h3>'.ucfirst($details['company_name']).'</h3><div class="info_cont"><p>'.ucwords($details['first_name'].' '.$details['last_name']).' - ('.ucfirst($details['user_type']).')'.'</p></div></div>';
				
				$all_users['results'][$k]['_id'] 			= strval($user_id);
				$all_users['results'][$k]['user_type'] 		= $details['user_type'];
				$all_users['results'][$k]['email'] 		= $details['email'];
				$all_users['results'][$k]['first_name'] 	= $details['first_name'];
				$all_users['results'][$k]['last_name'] 		= $details['last_name'];
				$all_users['results'][$k]['profile_image'] 	= (isset($details['profile_image'])) ? $details['profile_image'] : '';
				$all_users['results'][$k]['company_name'] 	= $details['company_name'];
				
				$all_users['results'][$k]['company_address'] = $company_address;
				$all_users['results'][$k]['delivery_address']= $delivery_address;
				$all_users['results'][$k]['pickup_address'] 	= $pickup_address;
				$all_users['results'][$k]['home_address'] 	= $home_address;
				
				$all_users['results'][$k]['user_rating']	= (isset($user_rating[0]['user_rating'])) ? $user_rating[0]['user_rating'] : '';
				$all_users['results'][$k]['status'] 		= $details['status'];
				
				$all_users['results'][$k]['latlon'] 		= array((float)$main_lat, (float)$main_long);
				$all_users['results'][$k]['price'] 		= "0.00";
				$all_users['results'][$k]['currency'] 		= "USD";
				$all_users['results'][$k]['lat'] 			= (string)$main_lat;
				$all_users['results'][$k]['lat_str'] 		= (string)$main_lat;
				$all_users['results'][$k]['long'] 			= (string)$main_long;
				$all_users['results'][$k]['long_str'] 		= (string)$main_long;
				$all_users['results'][$k]['info_content']	= urlencode($info_content);
				
				$all_users['results'][$k]['job_info']		= (object)array();
				
				$all_users['marker'][$k]					= array('type' => '1', 'lat' => $main_lat, 'lng' => $main_long, 'icon' => $icon);
			}
		}
		
		$job_last_update_arr = '';
		$cur_date 		 = date('Y-m-d');
		
		if(!empty($job_lists))
		{
			$point =	count($all_details);
			foreach($job_lists as $lists)
			{
				$is_job_approved 	= $job_has_updated_loc = 0;
				$delivery_address 	= $pickup_address = $home_address = array('address' => '', 'lat' => '', 'long' => '');
				$pickup_lat 		= $pickup_long = $drop_lat = $drop_long = $icon = $job_color = $job_status_txt = '';
				$job_info 		= $job_user_data = $job_user_rating = $all_quotes = $all_legs = array();
				
				$job_last_activity_date	= isset($lists['added_on']) ? date('Y-m-d', strtotime($lists['added_on'])) : date('Y-m-d');
				
				//If the job is approved then send only approved legs
				if(isset($lists['job_status']) && ($lists['job_status'] == '2'))
				{
					//get the job approved date
					$this->mongo_db->where(array('job_id' => strval($lists['_id'])));
					$job_approved_quotes 	= $this->mongo_db->get('job_approved_quotes');
					
					//If job is approved then update job last modified date
					$job_last_activity_date	= isset($job_approved_quotes[0]['approved_on']) ? date('Y-m-d', strtotime($job_approved_quotes[0]['approved_on'])) : $job_last_activity_date;
					
					//Find all updated locations of this job
					$this->mongo_db->where(array('job_id' => strval($lists['_id']), 'event_type' => 'update_location'));
					$this->mongo_db->order_by(array('_id' => 'asc'));
					$job_updated_locations 	= $this->mongo_db->get('job_events');
					
					if(!empty($job_updated_locations))
					{
						foreach($job_updated_locations as $cl => $current_loc)
						{
							$all_legs[$cl]['start_location']['address']	= isset($current_loc['event_address']['address']) ? $current_loc['event_address']['address'] : '';
							$all_legs[$cl]['start_location']['lat']		= isset($current_loc['event_address']['lat']) ? $current_loc['event_address']['lat'] : '';
							$all_legs[$cl]['start_location']['lat_str']	= isset($current_loc['event_address']['lat_str']) ? $current_loc['event_address']['lat_str'] : '';
							$all_legs[$cl]['start_location']['long']	= isset($current_loc['event_address']['long']) ? $current_loc['event_address']['long'] : '';
							$all_legs[$cl]['start_location']['long_str']	= isset($current_loc['event_address']['long_str']) ? $current_loc['event_address']['long_str'] : '';
							if(isset($job_updated_locations[$cl+1]) && !empty($job_updated_locations[$cl+1]))
							{
								$current_loc_end 	= $job_updated_locations[$cl+1];
								
								$all_legs[$cl]['end_location']['address']	= isset($current_loc_end['event_address']['address']) ? $current_loc_end['event_address']['address'] : '';
								$all_legs[$cl]['end_location']['lat']		= isset($current_loc_end['event_address']['lat']) ? $current_loc_end['event_address']['lat'] : '';
								$all_legs[$cl]['end_location']['lat_str']	= isset($current_loc_end['event_address']['lat_str']) ? $current_loc_end['event_address']['lat_str'] : '';
								$all_legs[$cl]['end_location']['long']		= isset($current_loc_end['event_address']['long']) ? $current_loc_end['event_address']['long'] : '';
								$all_legs[$cl]['end_location']['long_str']	= isset($current_loc_end['event_address']['long_str']) ? $current_loc_end['event_address']['long_str'] : '';
							}
							else{
								$current_loc_end 	= $lists;
								
								$all_legs[$cl]['end_location']['address']	= isset($lists['drop_address']['address']) 	? $lists['drop_address']['address'] : '';
								$all_legs[$cl]['end_location']['lat']		= isset($lists['drop_address']['lat']) 		? $lists['drop_address']['lat'] : '';
								$all_legs[$cl]['end_location']['lat_str']	= isset($lists['drop_address']['lat_str']) 	? $lists['drop_address']['lat_str'] : '';
								$all_legs[$cl]['end_location']['long']		= isset($lists['drop_address']['long']) 	? $lists['drop_address']['long'] : '';
								$all_legs[$cl]['end_location']['long_str']	= isset($lists['drop_address']['long_str']) 	? $lists['drop_address']['long_str'] : '';
							}
							
							if($cl == (count($job_updated_locations)- 1)){
								//If job is approved then update job last modified date
								$job_last_activity_date	= isset($current_loc[0]['added_on']) ? date('Y-m-d', strtotime($current_loc[0]['added_on'])) : $job_last_activity_date;
							}
						}
						
						$is_job_approved = $job_has_updated_loc = 1;
					}
					else
					{
						//find all approved legs to the map
						$job_approved_id 	= isset($lists['job_taken_by']) ? strval($lists['job_taken_by']) : '';
						$this->mongo_db->where(array('_id' => $job_approved_id));
						$job_approved_legs 	= $this->mongo_db->get('job_approved_quotes');
						
						if(isset($job_approved_legs[0]) && !empty($job_approved_legs[0]))
						{
							$all_legs_ids 	= isset($job_approved_legs[0]['quote_ids']) ? explode(',', $job_approved_legs[0]['quote_ids']) : array();
							if(!empty($all_legs_ids))
							{
								foreach($all_legs_ids as $ids)
								{
									$this->mongo_db->where(array('_id' => strval(trim($ids))));
									$indi_leg_det 	= $this->mongo_db->get('job_quotes_legs');
									
									if(isset($indi_leg_det[0]) && !empty($indi_leg_det[0])) $all_legs[]	= $indi_leg_det[0];
								}
								
								$is_job_approved = 1;
							}
						}
					}
				}
				else
				{
					//all quotes of this job
					$this->mongo_db->where(array('job_id' => strval($lists['_id']), 'type' => '1'));
					$all_quotes 	= $this->mongo_db->get('job_quotes_legs');
					
					//all legs of this job
					$this->mongo_db->where(array('job_id' => strval($lists['_id']), 'type' => '2'));
					$this->mongo_db->order_by(array('_id' => 'asc'));
					$all_legs 	= $this->mongo_db->get('job_quotes_legs');
					
					//Get last updated date
					$this->mongo_db->where(array('job_id' => strval($lists['_id'])));
					$last_post 				= $this->mongo_db->get('job_quotes_legs');
					$job_last_activity_date		= isset($last_post[0]['added_on']) ? date('Y-m-d', strtotime($last_post[0]['added_on'])) : $job_last_activity_date;
				}
				
				if(isset($lists['drop_address']))
				{
					if(!is_array($lists['drop_address']))
					{
						$obj_dev_addr 						= ($this->check_json($lists['drop_address'])) ? json_decode($lists['drop_address']) : $lists['drop_address'];
						$delivery_address['address'] 			= (isset($obj_dev_addr->address)) 	? $obj_dev_addr->address : $obj_dev_addr;
						$delivery_address['lat'] 			= (isset($obj_dev_addr->lat_str)) 	? $obj_dev_addr->lat_str 	: '';
						$delivery_address['lat_str'] 			= (isset($obj_dev_addr->lat_str)) 	? $obj_dev_addr->lat_str 	: '';
						$delivery_address['long'] 			= (isset($obj_dev_addr->long_str)) 	? $obj_dev_addr->long_str 	: '';
						$delivery_address['long_str'] 			= (isset($obj_dev_addr->long_str)) 	? $obj_dev_addr->long_str 	: '';
						
						
						$drop_lat 						= (isset($obj_dev_addr->lat_str)) 	? $obj_dev_addr->lat_str 	: '';
						$drop_long 						= (isset($obj_dev_addr->long_str)) 	? $obj_dev_addr->long_str 	: '';
					}
					else
					{
						$obj_dev_addr 						= $lists['drop_address'];
						$delivery_address['address'] 			= (isset($obj_dev_addr['address'])) 	? $obj_dev_addr['address'] : $obj_dev_addr;
						$delivery_address['lat'] 			= (isset($obj_dev_addr['lat_str'])) 	? $obj_dev_addr['lat_str'] 	: '';
						$delivery_address['lat_str'] 			= (isset($obj_dev_addr['lat_str'])) 	? $obj_dev_addr['lat_str'] 	: '';
						$delivery_address['long'] 			= (isset($obj_dev_addr['long_str'])) 	? $obj_dev_addr['long_str'] 	: '';
						$delivery_address['long_str'] 			= (isset($obj_dev_addr['long_str'])) 	? $obj_dev_addr['long_str'] 	: '';
						
						$drop_lat 						= (isset($obj_dev_addr['lat_str'])) 	? $obj_dev_addr['lat_str']	: '';
						$drop_long 						= (isset($obj_dev_addr['long_str'])) 	? $obj_dev_addr['long_str']	: '';
					}
				}
				
				if(isset($lists['pickup_address']))
				{
					if(!is_array($lists['pickup_address']))
					{
						$obj_pick_addr 					= ($this->check_json($lists['pickup_address'])) ? json_decode($lists['pickup_address']) : $lists['pickup_address'];
						$pickup_address['address'] 			= (isset($obj_pick_addr->address)) ? $obj_pick_addr->address : $obj_pick_addr;
						$pickup_address['lat'] 				= (isset($obj_pick_addr->lat_str))	? $obj_pick_addr->lat_str 	 : '';
						$pickup_address['lat_str'] 				= (isset($obj_pick_addr->lat_str))	? $obj_pick_addr->lat_str 	 : '';
						$pickup_address['long'] 				= (isset($obj_pick_addr->long_str)) 	? $obj_pick_addr->long_str 	 : '';
						$pickup_address['long_str'] 				= (isset($obj_pick_addr->long_str)) 	? $obj_pick_addr->long_str 	 : '';
						
						$pickup_lat 						= (isset($obj_pick_addr->lat_str)) 	? $obj_pick_addr->lat_str 	: '';
						$pickup_long 						= (isset($obj_pick_addr->long_str)) 	? $obj_pick_addr->long_str 	: '';
					}
					else
					{
						$obj_pick_addr 					= $lists['pickup_address'];
						$pickup_address['address'] 			= (isset($obj_pick_addr['address'])) ? $obj_pick_addr['address'] : $obj_pick_addr;
						$pickup_address['lat'] 				= (isset($obj_pick_addr['lat_str']))	? $obj_pick_addr['lat_str'] 	 : '';
						$pickup_address['lat_str'] 			= (isset($obj_pick_addr['lat_str']))	? $obj_pick_addr['lat_str'] 	 : '';
						$pickup_address['long'] 				= (isset($obj_pick_addr['long_str'])) 	? $obj_pick_addr['long_str'] 	 : '';
						$pickup_address['long_str'] 			= (isset($obj_pick_addr['long_str'])) 	? $obj_pick_addr['long_str'] 	 : '';
						
						$pickup_lat 						= (isset($obj_pick_addr['lat_str'])) 	? $obj_pick_addr['lat_str'] 	: '';
						$pickup_long 						= (isset($obj_pick_addr['long_str'])) 	? $obj_pick_addr['long_str'] 	: '';
					}
				}
				
				//getting respective user marker icon
				if(isset($lists['job_status']) && ($lists['job_status'] == '0')){
					$icon = base_url().'assets/site/images/white-job.png'; $job_status_txt = 'New Job'; 			$job_color = '#FFFFFF';
				}
				elseif(isset($lists['job_status']) && ($lists['job_status'] == '1')){
					$icon = base_url().'assets/site/images/blue-job.png'; $job_status_txt = 'Job Quoted on'; 		$job_color = '#032DCD';
				}
				elseif(isset($lists['job_status']) && ($lists['job_status'] == '2')){
					$icon = base_url().'assets/site/images/green-job.png'; $job_status_txt = 'Job in progress'; 	$job_color = '#28F202';
				}
				
				
				if(isset($lists['job_status']) && ($lists['job_status'] == '2')){
					$icon = base_url().'assets/site/images/green-job.png'; $job_status_txt = 'Job in progress'; 	$job_color = '#28F202';
				}
				elseif(!empty($all_quotes)){
					$icon = base_url().'assets/site/images/blue-job.png'; $job_status_txt = 'Job Quoted on'; 		$job_color = '#032DCD';
				}
				
				
				$info_content 	= '<div class="info_header"><h3>'.ucfirst($lists['title']).'</h3><div class="info_cont"><p>'.$job_status_txt.'</p></div></div>';
				
				if(isset($lists['user_id'])){
					$this->mongo_db->where(array('_id' => strval($lists['user_id'])));
					$job_user_data 	= $this->mongo_db->get('site_users');
					
					//$job_user_data	= $this->myaccount_model->get_account_data(strval($lists['user_id']));
					//echo '<pre>'; print_r($job_user_data); echo '</pre>';
					//Getting user rating
					$this->mongo_db->where(array('user_id' => strval($lists['user_id']), 'status' => "1"));
					$job_user_rating 	= $this->mongo_db->get('user_rating');
				}
				
				
				
				$all_users['results'][$point]['_id'] 			= strval($lists['_id']);
				$all_users['results'][$point]['user_type'] 		= 'job';
				$all_users['results'][$point]['email'] 			= '';
				$all_users['results'][$point]['first_name'] 		= $lists['title'];
				$all_users['results'][$point]['last_name'] 		= '';
				$all_users['results'][$point]['profile_image'] 	= (isset($details['image'])) ? $details['image'] : '';
				$all_users['results'][$point]['company_name'] 	= '';
				
				$all_users['results'][$point]['company_address'] 	= '';
				$all_users['results'][$point]['delivery_address']	= $delivery_address;
				$all_users['results'][$point]['pickup_address'] 	= $pickup_address;
				$all_users['results'][$point]['home_address'] 	= '';
				$all_users['results'][$point]['job_icon'] 		= array('url' => $icon);
				
				$all_users['results'][$point]['user_rating']		= '';
				$all_users['results'][$point]['status'] 		= $lists['status'];
				
				$all_users['results'][$point]['latlon'] 		= array((float)$pickup_lat, (float)$pickup_long);
				$all_users['results'][$point]['price'] 			= "0.00";
				$all_users['results'][$point]['currency'] 		= "USD";
				
				$all_users['results'][$point]['lat'] 			= '';
				$all_users['results'][$point]['long'] 			= '';
				
				$all_users['results'][$point]['pick_lat'] 		= (string)$pickup_lat;
				$all_users['results'][$point]['pick_long'] 		= (string)$pickup_long;
				
				$all_users['results'][$point]['drop_lat'] 		= (string)$drop_lat;
				$all_users['results'][$point]['drop_long'] 		= (string)$drop_long;
				
				$all_users['results'][$point]['info_content']	= urlencode($info_content);
				
				$all_users['results'][$point]['all_quotes']		= $all_quotes;
				$all_users['results'][$point]['all_legs']		= $all_legs;
				
				//size details
				if(isset($lists['size_type']) && !empty($lists['size_type']) && ($lists['size_type'] != 'SIZE'))
				{
					$this->mongo_db->where(array('_id' => strval($lists['size_type']), 'status' => "1"));
					$size_details 	= $this->mongo_db->get('sizes');
				}
				
				//type details
				if(isset($lists['type']) && !empty($lists['type']) && ($lists['size_type'] != 'TYPE'))
				{
					$this->mongo_db->where(array('_id' => strval($lists['type']), 'status' => "1"));
					$type_details 	= $this->mongo_db->get('type');
				}
				
				//special details
				if(isset($lists['special']) && !empty($lists['special']) && ($lists['size_type'] != 'SPECIAL'))
				{
					$this->mongo_db->where(array('_id' => strval($lists['special']), 'status' => "1"));
					$special_details 	= $this->mongo_db->get('special');
				}
				
				$legs_arr = array();
				
				////generate all legs arr
				if(!empty($all_legs))
				{
					//echo '<pre>'; print_r($all_legs);
					
					foreach($all_legs as $l => $legs)
					{
						$legs_arr[$l]['pick_up']['address'] 	= $legs['start_location']['address'];
						$legs_arr[$l]['pick_up']['lat'] 		= (isset($legs['start_location']['lat_str'])) ? $legs['start_location']['lat_str'] : $legs['start_location']['lat'];
						$legs_arr[$l]['pick_up']['lat_str'] 	= (isset($legs['start_location']['lat_str'])) ? $legs['start_location']['lat_str'] : $legs['start_location']['lat'];
						$legs_arr[$l]['pick_up']['long'] 		= (isset($legs['start_location']['long_str'])) ? $legs['start_location']['long_str'] : $legs['start_location']['long'];
						$legs_arr[$l]['pick_up']['long_str'] 	= (isset($legs['start_location']['long_str'])) ? $legs['start_location']['long_str'] : $legs['start_location']['long'];
						
						$legs_arr[$l]['drop_point']['address']	= $legs['end_location']['address'];
						$legs_arr[$l]['drop_point']['lat'] 	= (isset($legs['end_location']['lat_str'])) ? $legs['end_location']['lat_str'] : $legs['end_location']['lat'];
						$legs_arr[$l]['drop_point']['lat_str'] 	= (isset($legs['end_location']['lat_str'])) ? $legs['end_location']['lat_str'] : $legs['end_location']['lat'];
						$legs_arr[$l]['drop_point']['long'] 	= (isset($legs['end_location']['long_str'])) ? $legs['end_location']['long_str'] : $legs['end_location']['long'];
						$legs_arr[$l]['drop_point']['long_str'] = (isset($legs['end_location']['long_str'])) ? $legs['end_location']['long_str'] : $legs['end_location']['long'];
					}
				}
				
				$has_legs 	= (count($legs_arr) > 0) ? 1 : 0;
				
				//get the job info
				$job_info['id']							= strval($lists['_id']);
				$job_info['icon']							= $icon;
				$job_info['marker_color']					= $job_color;
				$job_info['user_id']						= strval($lists['user_id']);
				$job_info['user_name']						= isset($job_user_data[0]['first_name']) ? ucfirst($job_user_data[0]['first_name'].' '.$job_user_data[0]['last_name']) : '';
				$job_info['user_image']						= isset($job_user_data[0]['profile_image']) ? ucfirst($job_user_data[0]['profile_image'].' '.$job_user_data[0]['profile_image']) : '';
				$job_info['user_rating']						= (isset($job_user_rating[0]['user_rating'])) ? $job_user_rating[0]['user_rating'] : '';
				$job_info['is_own']							= ($lists['user_id'] == $user_id) ? '1' : '0';
				$job_info['job_description']					= (isset($lists['description'])) ? $lists['description'] : '';
				$job_info['job_image']						= (isset($lists['image'])) ? $lists['image'] : '';
				$job_info['pickup_address']					= (isset($lists['pickup_address'])) ? $lists['pickup_address'] : '';
				$job_info['drop_address']					= (isset($lists['drop_address'])) ? $lists['drop_address'] : '';
				$job_info['distance']						= (isset($lists['distance'])) ? $lists['distance'] : '';
				$job_info['distance_type']					= (isset($lists['distance_type'])) ? $lists['distance_type'] : '';
				$job_info['delivery_date']					= (isset($lists['delivery_date'])) ? date('dS M Y', strtotime($lists['delivery_date'])) : '';
				$job_info['size_type']						= (isset($size_details[0]['title'])) ? $size_details[0]['title'] : '';
				$job_info['containt_type']					= (isset($type_details[0]['title'])) ? $type_details[0]['title'] : '';
				$job_info['special']						= (isset($special_details[0]['title'])) ? $special_details[0]['title'] : '';
				$job_info['weight']							= (isset($lists['weight'])) ? $lists['weight'] : '';
				$job_info['cargo_value']						= (isset($lists['cargo_value'])) ? $lists['cargo_value'] : '';
				$job_info['max_job_price']					= (isset($lists['max_job_price'])) ? $lists['max_job_price'] : '';
				$job_info['is_gurrented']					= (isset($lists['is_gurrented'])) ? $lists['is_gurrented'] : '';
				$job_info['is_insured']						= (isset($lists['is_insured'])) ? $lists['is_insured'] : '';
				$job_info['status']							= (isset($lists['status'])) ? $lists['status'] : '0';
				$job_info['quotes_arr']						= $all_quotes;
				$job_info['legs_arr']						= $legs_arr;
				$job_info['leg_icon']						= ($is_job_approved) ? base_url().'assets/site/images/green-job.png' : base_url().'assets/site/images/blue-job.png';
				$job_info['is_job_approved']					= $is_job_approved;
				$job_info['job_has_updated_loc']				= $job_has_updated_loc;
				$job_info['has_legs']						= $has_legs;
				$job_info['last_activity_date']				= $job_last_activity_date;
				
				//Get the difference between current date and job last activity date
				$diff 	= abs(strtotime($cur_date) - strtotime($job_last_activity_date));
				$days 	= floor($diff / (60*60*24));
				
				$job_info['last_activity_date_diff']			= $days;
				
				$all_users['results'][$point]['job_info']		= ($job_info);
				
				$all_users['marker'][$point]					= array('type' => '2', "marker_color" => $job_color, 'job_info' => $job_info, 'leg_icon' => base_url().'assets/site/images/blue-job.png', "pick_up" => array('lat' => $pickup_lat, 'lat_str' => $pickup_lat, 'lng' => $pickup_long, 'lng_str' => $pickup_long, 'icon' => $icon), "drop" => array('lat' => $drop_lat, 'lat_str' => $drop_lat, 'lng' => $drop_long, 'lng_str' => $drop_long, 'icon' => $icon), 'is_job_approved' => $is_job_approved, 'job_has_updated_loc' => $job_has_updated_loc, 'has_legs' => $has_legs, 'all_legs' => $legs_arr);
				
				$point++;
			}
		}
		
		if(empty($all_users))
		{
			$all_users['results'] 						= array();
			$all_users['marker'] 						= array();
		}
		
		$all_users['is_loggedin']						= $is_loggedin;
		$all_users['meta']   							= array("page" => "1", "per_page" => "", "count" => count($all_details), "total_pages" => "", 'links_all' => "");
		$all_users['lat'] 								= $srch_lat;
		$all_users['lng']								= $srch_lon;
		
		$all_users['sw_lat'] 							= $sw_lat;
		$all_users['sw_lng']							= $sw_lng;
		$all_users['ne_lat'] 							= $ne_lat;
		$all_users['ne_lng']							= $ne_lng;
		
		//echo '<pre>'; print_r($all_users); echo '</pre>'; 
		
		echo json_encode($all_users);
	}
	
	public function logout()
	{
		$this->session->sess_destroy();
		redirect('');
	}
	
	
	public function make_payment()
	{
		$job_id 						= $this->input->post('current_job_id');
		$quote_id 					= $this->input->post('current_quote_id');
		$extra_amount					= $this->input->post('to_be_refund');
		
		$deduction_amount				= $this->input->post('deduction_amount');
		$deduction_percent				= $this->input->post('deduction_percent');
		$extra_amount_p				= $this->input->post('extra_amount');
		$extra_amount_percent			= $this->input->post('extra_percent');
		$extra_days					= $this->input->post('extra_days');
		
		
		$payment_type					= $this->input->post('payment_type');
		
		$user_id 						= ($this->session->userdata('site_user_objId_hotcargo')) ?  $this->session->userdata('site_user_objId_hotcargo') : 0;
		$setting_data 					= $this->myaccount_model->get_account_data($user_id);
		$data['data']['setting_data'] 	= $setting_data;
		$data['data']['settings'] 		= $settings = $this->sitesetting_model->get_settings();
		$myaccount_data 				= $this->myaccount_model->get_account_data($user_id);
		
		$sitename						= isset($setting_data[0]['site_name']) ? $setting_data[0]['site_name'] : 'Hotcargo';
		
		$this->mongo_db->where(array('_id' => $user_id));
		$user_details 					= $this->mongo_db->get('site_users');
		$customer_name					= (isset($user_details[0]['first_name'])) ? ucfirst($user_details[0]['first_name'].' '.$user_details[0]['last_name']) : '';
		
		$amount_to_charge 				= $this->input->post('to_be_pay');
			
		if($deduction_amount > 0)		$amount_to_charge = $amount_to_charge - $deduction_amount;
		if($extra_amount_p > 0)			$amount_to_charge = $amount_to_charge + $extra_amount_p;
		
		$amount_to_charge 				= ($amount_to_charge) ? $amount_to_charge : 0;
		
		if($payment_type == 'credit_card')
		{
			$stripe_type 					= (isset($settings[0]['stripe_pay_type'])) ? $settings[0]['stripe_pay_type'] : 2;
			$stripe_secret_key 				= $stripe_public_key = '';
			
			if($stripe_type == 1){
				$stripe_secret_key 			= (isset($settings[0]['stripe_live_secret_key'])) ? $settings[0]['stripe_live_secret_key'] : '';
				$stripe_public_key 			= (isset($settings[0]['stripe_live_public_key'])) ? $settings[0]['stripe_live_public_key'] : '';
			}
			else{
				$stripe_secret_key 			= (isset($settings[0]['stripe_sandbox_secret_key'])) ? $settings[0]['stripe_sandbox_secret_key'] : '';
				$stripe_public_key 			= (isset($settings[0]['stripe_sandbox_public_key'])) ? $settings[0]['stripe_sandbox_public_key'] : '';
			}
			
			$check_tobepaid 				= explode('.', $amount_to_charge);
			if(isset($check_tobepaid[1]) && $check_tobepaid[1] == '') $amount_to_charge = $amount_to_charge.'00';
			
			//$existing_stripe_id 			= $this->input->post('current_stripe_id');
			//$is_new_cus 					= ($existing_stripe_id) ? 0 : 1;
			
			$is_new_cus					= 1;
			
			$stripe_token 					= ($this->input->post('stripeToken')) ? $this->input->post('stripeToken') : '';
			
			$car_holder_name 				= $this->input->post('cardholdername');
			$car_cvv						= $this->input->post('cvv');
			
			require FILEUPLOADPATH.'assets/stripe/lib/Stripe.php';
			Stripe::setApiKey($stripe_secret_key);
			
			if($is_new_cus == 1)
			{
				try {
					if(!empty($stripe_token) && ($amount_to_charge > 0))
					{
						$data_to_store = $data_to_store1 = $data_to_store2 = array();
						
						$customer = Stripe_Customer::create(array(
							"card" 		=> $_POST['stripeToken'],
							"description" 	=> $car_holder_name )
						);
						
						$customer_det = $customer->__toArray(true);
						
						if(!empty($customer_det))
						{
							$customer_id 	= (isset($customer_det['id']) && !empty($customer_det['id'])) ? $customer_det['id'] : '';
							$card_id 		= (isset($customer_det['default_source']) && !empty($customer_det['default_source'])) ? $customer_det['default_source'] : '';
							$card_desc 	= (isset($customer_det['description']) && !empty($customer_det['description'])) ? $customer_det['description'] : '';
							
							$all_det 		= (isset($customer_det['sources']['data'][0]) && !empty($customer_det['sources']['data'][0])) ? $customer_det['sources']['data'][0] : '';
							//echo '<pre>'; print_r($customer_det);
							//echo 'cid: '.$customer_id.' card id: '.$card_id.' card desc: '.$card_desc.' all det: '.$all_det;
							//die;
							
							if($customer_id)
							{
								$data_to_save 	= array('status' => '0');
								$this->mongo_db->where(array('user_id' => $user_id));
								$this->mongo_db->set($data);
								$this->mongo_db->update('users_stripe_details');
								
								
								$data_to_store['user_id'] 		= strval($user_id);
								$data_to_store['added_on'] 		= date('Y-m-d H:i:s');
								$data_to_store['system_timezone']	= $this->system_timezone;
								$data_to_store['stripe_id'] 		= strval($customer_id);
								$data_to_store['card_id'] 		= strval($card_id);
								$data_to_store['name_on_card']	= $car_holder_name;
								$data_to_store['description'] 	= strval($card_desc);
								$data_to_store['address_city'] 	= (isset($all_det['address_city'])) 	? strval($all_det['address_city']) 	: '';
								$data_to_store['address_country'] 	= (isset($all_det['address_country'])) 	? strval($all_det['address_country']) 	: '';
								$data_to_store['address_line1'] 	= (isset($all_det['address_line1'])) 	? strval($all_det['address_line1']) 	: '';
								$data_to_store['address_line2'] 	= (isset($all_det['address_line2'])) 	? strval($all_det['address_line2']) 	: '';
								$data_to_store['address_state'] 	= (isset($all_det['address_state'])) 	? strval($all_det['address_state']) 	: '';
								$data_to_store['address_zip'] 	= (isset($all_det['address_zip'])) 	? strval($all_det['address_zip']) 		: '';
								$data_to_store['exp_month'] 		= (isset($all_det['exp_month'])) 		? strval($all_det['exp_month']) 		: '';
								$data_to_store['exp_year'] 		= (isset($all_det['exp_year'])) 		? strval($all_det['exp_year']) 		: '';
								$data_to_store['card_brand'] 		= (isset($all_det['brand'])) 			? strval($all_det['brand']) 			: '';
								$data_to_store['card_last_digits'] = (isset($all_det['last4'])) 			? strval($all_det['last4']) 			: '';
								$data_to_store['cvv_code'] 		= $car_cvv;
								$data_to_store['card_status'] 	= '1';
								$data_to_store['status'] 		= '1';
								
								//Inserting the data to db
								$insert_details 				= $this->mongo_db->insert('users_stripe_details', $data_to_store); 
								$last_id 						= $insert_details;
								
								# Make the credit card default for this user
								//$new_card 					= Stripe_Charge::retrieve($customer_id);
								//$new_card->default_source		= $card_id;
								//$new_card->save();
								
								# Charge the Customer instead of the card
								$make_payment 		= Stripe_Charge::create(array(
										"amount" 				=> $amount_to_charge * 100, // amount in cents, again
										"currency" 			=> "usd",
										"customer" 			=> $customer_id
									)
								);
								
								$make_payment_det 				= $make_payment->__toArray(true);
								
								if(!empty($make_payment_det))
								{	
									$error_code = (isset($make_payment_det['failure_code'])) ? $make_payment_det['failure_code'] : '';
									if(empty($error_code))
									{
										$data_to_store1['payment_type']		= 'credit_card';
										$data_to_store1['user_id']  			= strval($user_id);
										$data_to_store1['card_id']  			= strval($last_id);
										$data_to_store1['amount']  			= (isset($make_payment_det['amount'])) 					? strval(($make_payment_det['amount'] / 100)) : '0';
										$data_to_store1['currency']  			= (isset($make_payment_det['currency'])) 				? strval($make_payment_det['currency']) : '';
										$data_to_store1['bill_date']  		= date('Y-m-d H:i:s');
										$data_to_store1['system_timezone']		= $this->system_timezone;
										$data_to_store1['payment_status']  	= '1';
										$data_to_store1['name_on_card']  		= $car_holder_name;
										
										$data_to_store1['address_city']  		= (isset($make_payment_det['source']['address_city'])) 	? strval($make_payment_det['source']['address_city']) 	: '';
										$data_to_store1['address_country']  	= (isset($make_payment_det['source']['address_country'])) 	? strval($make_payment_det['source']['address_country']) : '';
										$data_to_store1['address_line1']  		= (isset($make_payment_det['source']['address_line1'])) 	? strval($make_payment_det['source']['address_line1']) 	: '';
										$data_to_store1['address_line2']  		= (isset($make_payment_det['source']['address_line2'])) 	? strval($make_payment_det['source']['address_line2']) 	: '';
										$data_to_store1['address_state']  		= (isset($make_payment_det['source']['address_state'])) 	? strval($make_payment_det['source']['address_state']) : '';
										
										$data_to_store1['address_zip']  		= (isset($make_payment_det['source']['address_zip'])) 		? strval($make_payment_det['source']['address_zip']) 	: '';
										$data_to_store1['exp_month']  		= (isset($make_payment_det['source']['exp_month'])) 		? strval($make_payment_det['source']['exp_month']) 	: '';
										$data_to_store1['exp_year']  			= (isset($make_payment_det['source']['exp_year'])) 		? strval($make_payment_det['source']['exp_year']) 	: '';
										$data_to_store1['card_brand']  		= (isset($make_payment_det['source']['brand'])) 			? strval($make_payment_det['source']['brand']) 		: '';
										$data_to_store1['card_last_digits']  	= (isset($make_payment_det['source']['last4'])) 			? strval($make_payment_det['source']['last4'])		: '';
										$data_to_store1['status']  			= '1';
																		 
										//Inserting the data to db
										$insert_pay_details 				= $this->mongo_db->insert('user_payments', $data_to_store1); 
										$pay_id 							= $insert_pay_details;
										
										if($pay_id)
										{
											$aprox_pay_date = '';
											if(!empty($extra_days) && ($extra_days > 0))
												$aprox_pay_date 	= date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $extra_days, date('Y')));
											
											$data_to_store2 					= array();
											//$data_to_store2['user_id'] 			= strval($user_id);
											$data_to_store2['job_id'] 			= strval($job_id);
											$data_to_store2['quote_ids'] 			= strval($quote_id);
											$data_to_store2['total_price']		= strval($amount_to_charge);
											$data_to_store2['payment_type']		= '1';
											$data_to_store2['payment_id']			= strval($pay_id);
											$data_to_store2['payment_status']		= '1';
											$data_to_store2['tax']				= '';
											
											$data_to_store2['refundable_amount']	= strval($extra_amount);
											$data_to_store2['refundable_percent']	= $this->refundable_percent;
											$data_to_store2['deduction_amount']	= $deduction_amount;
											$data_to_store2['deduction_percent']	= $deduction_percent;
											$data_to_store2['extra_amount']		= $extra_amount_p;
											$data_to_store2['extra_percent']		= $extra_amount_percent;
											$data_to_store2['extra_days']			= $extra_days;
											$data_to_store2['aprox_pay_date']		= $aprox_pay_date;
											$data_to_store2['is_pay_date_over']	= '0';
											$data_to_store2['is_refunded']		= '0';
											$data_to_store2['refund_pay_id']		= '';
											
											
											$data_to_store2['approved_on']		= date('Y-m-d H:i:s');
											$data_to_store2['system_timezone']		= $this->system_timezone;
											$data_to_store2['status']			= '1';
											
											//Inserting the data to db
											$insert_approved_details 		= $this->mongo_db->insert('job_approved_quotes', $data_to_store2);
											
											//Update of job quote
											$quote_ids 					= explode(',', $quote_id);
											if(!empty($quote_ids))
											{
												foreach($quote_ids as $qid)
												{
													$data_to_store3 		= array('request_status' => '1');
													$this->mongo_db->where(array('_id' => strval($qid)));
													$this->mongo_db->set($data_to_store3);
													$this->mongo_db->update('job_quotes_legs');
													
													//sending each quote/leg bidder confirmation email and sms.	
													$this->mongo_db->where(array('_id' => strval($qid)));
													$job_quote_details 	= $this->mongo_db->get('job_quotes_legs');
													$quote_user_id 	= (isset($job_quote_details[0]['user_id'])) 	? $job_quote_details[0]['user_id'] : '';
													$quote_type 		= (isset($job_quote_details[0]['type'])) 	? $job_quote_details[0]['type'] 	: '';
													
													if($quote_user_id)
													{
														$this->mongo_db->where(array('_id' => $quote_user_id));
														$job_user_details 	= $this->mongo_db->get('site_users');
														
														$user_email_id 	= isset($job_user_details[0]['email']) ? $job_user_details[0]['email'] : '';
														$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
														$user_phone_code 	= isset($job_user_details[0]['phone_code']) ? $job_user_details[0]['phone_code'] : '';
														$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
														$to_name 			= isset($job_user_details[0]['first_name']) ? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
														$leg_type 		= ($quote_type == 1) ? 'quote' : 'leg';
														
														//Getting the user settings
														$this->mongo_db->where(array('user_id' => $quote_user_id));
														$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
														
														if(!empty($job_user_notification_settings[0]))
														{
															$check_for_email 	= (isset($job_user_notification_settings[0]['quote_accept_jobs_notification']['email'])) ? $job_user_notification_settings[0]['quote_accept_jobs_notification']['email'] : 0;
															
															$check_for_sms 	= (isset($job_user_notification_settings[0]['quote_accept_jobs_notification']['sms'])) ? $job_user_notification_settings[0]['quote_accept_jobs_notification']['sms'] : 0;
															
															if($check_for_email)
															{
																$this->mongo_db->where(array('email_title' => 'job_approve_submit'));
																$email_temp_arr 	= $this->mongo_db->get('email_templates');
																$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
																
																if(!empty($email_temp))
																{
																	$search 		= array('[SITE_LOGO]', '[NAME]', '[LEG_TYPE]', '[CUSTOMER_NAME]', '[SITE_NAME]');
																	$replace 		= array(base_url().'assets/site/images/logo.png', $to_name, $leg_type, $customer_name, $sitename);
																	$email_temp_msg= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
																	$email_temp_msg= str_replace($search, $replace, $email_temp_msg);
																	
																	$email_temp_sub= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
																	if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
																}
															}
															
															//Check for sms settings
															$this->mongo_db->where(array('sms_title' => 'job_approve_submit'));
															$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
															$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
															
															if($user_mobile_no && $check_for_sms && !empty($sms_temp))
															{
																$search_sms 		= array('[LEG_TYPE]');
																$replace_sms 		= array($leg_type);
																
																$sms_temp_msg= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
																$sms_temp_msg= str_replace($search_sms, $replace_sms, $sms_temp_msg);
									
																$params['mobile_nos_to_send']	= array('+'.$user_phone_code.$user_mobile_no);
																$params['sms_messaages']		= array($sms_temp_msg);
																if($user_mobile_no)	$this->User_notifications_model->initialize($params);
															}
															
														}
													}
												}
											}
											
											//Update of job details
											$data_to_store4 	= array('job_status' => '2', 'job_taken_by' => strval($insert_approved_details));
											$this->mongo_db->where(array('_id' => strval($job_id)));
											$this->mongo_db->set($data_to_store4);
											$this->mongo_db->update('jobs');
											//sending the email and test message to respective job bidders
											
											$data_to_store_event['job_id'] 		= strval($job_id);
											$data_to_store_event['user_id']		= strval($user_id);
											$data_to_store_event['event_type'] 	= 'quote_accepted';
											$data_to_store_event['activity_details']= '';
											$data_to_store_event['event_cost'] 	= '0';
											$data_to_store_event['event_address'] 	= array();
											$data_to_store_event['added_on'] 		= date('Y-m-d H:i:s');
											$data_to_store_event['system_timezone']	= $this->system_timezone;
											$data_to_store_event['status']		= '1';
											$data_to_store_event['event_image'] 	= array();
											
											$insert_e 	= $this->mongo_db->insert('job_events', $data_to_store_event);
											
											$data_to_store1_event['job_id'] 		= strval($job_id);
											$data_to_store1_event['user_id']		= strval($user_id);
											$data_to_store1_event['event_type'] 	= 'order_started';
											$data_to_store1_event['activity_details'] = '';
											$data_to_store1_event['event_cost'] 	= '0';
											$data_to_store1_event['event_address'] 	= array();
											$data_to_store1_event['added_on'] 		= date('Y-m-d H:i:s');
											$data_to_store1_event['system_timezone']= $this->system_timezone;
											$data_to_store1_event['status']		= '1';
											$data_to_store1_event['event_image'] 	= array();
											
											$insert_e1 	= $this->mongo_db->insert('job_events', $data_to_store1_event);
											
										}
										
										$this->session->set_flashdata('flash_message', 'payment_success');
									}
									else{
										$this->session->set_flashdata('flash_message_cont', $error_code);
										$this->session->set_flashdata('flash_message', 'payment_failed');
									}
								}
								else
									$this->session->set_flashdata('flash_message', 'payment_failed');
							}
							else{
								$this->session->set_flashdata('flash_message_cont', 'Customer id not creted.');
								$this->session->set_flashdata('flash_message', 'payment_failed');
							}
						}
						else{
							$this->session->set_flashdata('flash_message_cont', 'Invalid contents.');
							$this->session->set_flashdata('flash_message', 'payment_failed');
						}
					}
					else{
						$this->session->set_flashdata('flash_message_cont', '');
						$this->session->set_flashdata('flash_message', 'payment_failed');
					}
				}
				catch (Exception $e) {
				
					$this->session->set_flashdata('flash_message_cont', $e->getMessage());
					$this->session->set_flashdata('flash_message', 'payment_failed');
				}
			}
			elseif(!empty($existing_stripe_id))
			{
				$data_to_store1 = $data_to_store2 = array();
				
				try {
					if($amount_to_charge > 0)
					{
						# Charge the Customer instead of the card
						$make_payment = Stripe_Charge::create(array(
								"amount" 			=> $amount_to_charge * 100, // amount in cents, again
								"currency" 		=> "usd",
								"customer" 		=> $existing_stripe_id
							)
						);
						
						$make_payment_det	= $make_payment->__toArray(true);
						
						//echo '<pre>'; print_r($make_payment_det); echo '</pre>'; die;
						
						if(!empty($make_payment_det))
						{
							$error_code = (isset($make_payment_det['failure_code'])) ? $make_payment_det['failure_code'] : '';
							if(empty($error_code))
							{
								$data_to_store1['user_id']  			= strval($user_id);
								$data_to_store1['card_id']  			= strval($last_id);
								$data_to_store1['amount']  			= (isset($make_payment_det['amount'])) 					? strval(($make_payment_det['amount'] / 100)) : '0';
								$data_to_store1['currency']  			= (isset($make_payment_det['currency'])) 				? strval($make_payment_det['currency']) : '';
								$data_to_store1['bill_date']  		= date('Y-m-d H:i:s');
								$data_to_store1['system_timezone']		= $this->system_timezone;
								$data_to_store1['payment_status']  	= '1';
								$data_to_store1['name_on_card']  		= $car_holder_name;
								
								$data_to_store1['address_city']  		= (isset($make_payment_det['source']['address_city'])) 	? strval($make_payment_det['source']['address_city']) 	: '';
								$data_to_store1['address_country']  	= (isset($make_payment_det['source']['address_country'])) 	? strval($make_payment_det['source']['address_country']) : '';
								$data_to_store1['address_line1']  		= (isset($make_payment_det['source']['address_line1'])) 	? strval($make_payment_det['source']['address_line1']) 	: '';
								$data_to_store1['address_line2']  		= (isset($make_payment_det['source']['address_line2'])) 	? strval($make_payment_det['source']['address_line2']) 	: '';
								$data_to_store1['address_state']  		= (isset($make_payment_det['source']['address_state'])) 	? strval($make_payment_det['source']['address_state']) : '';
								
								$data_to_store1['address_zip']  		= (isset($make_payment_det['source']['address_zip'])) 		? strval($make_payment_det['source']['address_zip']) 	: '';
								$data_to_store1['exp_month']  		= (isset($make_payment_det['source']['exp_month'])) 		? strval($make_payment_det['source']['exp_month']) 	: '';
								$data_to_store1['exp_year']  			= (isset($make_payment_det['source']['exp_year'])) 		? strval($make_payment_det['source']['exp_year']) 	: '';
								$data_to_store1['card_brand']  		= (isset($make_payment_det['source']['brand'])) 			? strval($make_payment_det['source']['brand']) 		: '';
								$data_to_store1['card_last_digits']  	= (isset($make_payment_det['source']['last4'])) 			? strval($make_payment_det['source']['last4'])		: '';
								$data_to_store1['status']  			= '1';
																 
								//Inserting the data to db
								$insert_pay_details 				= $this->mongo_db->insert('user_payments', $data_to_store1); 
								$pay_id 							= $insert_pay_details;
								
								if($pay_id)
								{
									$aprox_pay_date = '';
										if(!empty($extra_days) && ($extra_days > 0))
											$aprox_pay_date 	= date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $extra_days, date('Y')));
									
									$data_to_store2 				= array();
									$data_to_store2['job_id'] 		= strval($job_id);
									$data_to_store2['quote_ids'] 		= strval($quote_id);
									$data_to_store2['total_price']	= strval($amount_to_charge);
									$data_to_store2['payment_type']	= '1';
									$data_to_store2['payment_id']		= strval($pay_id);
									$data_to_store2['payment_status']	= '1';
									$data_to_store2['tax']			= '';
									
									$data_to_store2['refundable_amount']	= strval($extra_amount);
									$data_to_store2['refundable_percent']	= $this->refundable_percent;
									$data_to_store2['deduction_amount']	= $deduction_amount;
									$data_to_store2['deduction_percent']	= $deduction_percent;
									$data_to_store2['extra_amount']		= $extra_amount_p;
									$data_to_store2['extra_percent']		= $extra_amount_percent;
									$data_to_store2['extra_days']			= $extra_days;
									$data_to_store2['aprox_pay_date']		= $aprox_pay_date;
									
									$data_to_store2['is_pay_date_over']	= '0';
									$data_to_store2['is_refunded']		= '0';
									$data_to_store2['refund_pay_id']		= '';
									
									$data_to_store2['approved_on']		= date('Y-m-d H:i:s');
									$data_to_store2['system_timezone']		= $this->system_timezone;
									$data_to_store2['status']			= '1';
									
									//Inserting the data to db
									$insert_approved_details 		= $this->mongo_db->insert('job_approved_quotes', $data_to_store2);
									
									//Update of job quote
									$quote_ids 					= explode(',', $quote_id);
									if(!empty($quote_ids))
									{
										foreach($quote_ids as $qid)
										{
											$data_to_store3 		= array('request_status' => '1');
											$this->mongo_db->where(array('_id' => strval($qid)));
											$this->mongo_db->set($data_to_store3);
											$this->mongo_db->update('job_quotes_legs');
											
											//sending each quote/leg bidder confirmation email and sms.	
											$this->mongo_db->where(array('_id' => strval($qid)));
											$job_quote_details 	= $this->mongo_db->get('job_quotes_legs');
											$quote_user_id 	= (isset($job_quote_details[0]['user_id'])) 	? $job_quote_details[0]['user_id'] : '';
											$quote_type 		= (isset($job_quote_details[0]['type'])) 	? $job_quote_details[0]['type'] 	: '';
											
											if($quote_user_id)
											{
												$this->mongo_db->where(array('_id' => $quote_user_id));
												$job_user_details 	= $this->mongo_db->get('site_users');
												
												$user_email_id 	= isset($job_user_details[0]['email']) ? $job_user_details[0]['email'] : '';
												$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
												
												$user_phone_code 	= isset($job_user_details[0]['phone_code']) ? $job_user_details[0]['phone_code'] : '';
												$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
												
												$to_name 			= isset($job_user_details[0]['first_name']) ? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
												$leg_type 		= ($quote_type == 1) ? 'quote' : 'leg';
												
												//Getting the user settings
												$this->mongo_db->where(array('user_id' => $quote_user_id));
												$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
												
												if(!empty($job_user_notification_settings[0]))
												{
													$check_for_email 	= (isset($job_user_notification_settings[0]['quote_accept_jobs_notification']['email'])) ? $job_user_notification_settings[0]['quote_accept_jobs_notification']['email'] : 0;
													
													$check_for_sms 	= (isset($job_user_notification_settings[0]['quote_accept_jobs_notification']['sms'])) ? $job_user_notification_settings[0]['quote_accept_jobs_notification']['sms'] : 0;
													
													if($check_for_email)
													{
														$this->mongo_db->where(array('email_title' => 'job_approve_submit'));
														$email_temp_arr 	= $this->mongo_db->get('email_templates');
														$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
														
														if(!empty($email_temp))
														{
															$search 		= array('[SITE_LOGO]', '[NAME]', '[LEG_TYPE]', '[CUSTOMER_NAME]', '[SITE_NAME]');
															$replace 		= array(base_url().'assets/site/images/logo.png', $to_name, $leg_type, $customer_name, $sitename);
															
															$email_temp_msg= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
															$email_temp_msg= str_replace($search, $replace, $email_temp_msg);
															
															$email_temp_sub= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
															
															if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
															
														}
														
														
													}
													//Check for sms settings
													$this->mongo_db->where(array('sms_title' => 'job_approve_submit'));
													$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
													$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
													if($user_mobile_no && $check_for_sms && !empty($sms_temp))
													{
														$search_sms 		= array('[LEG_TYPE]');
														$replace_sms 		= array($leg_type);
														
														$sms_temp_msg= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
														$sms_temp_msg= str_replace($search_sms, $replace_sms, $sms_temp_msg);
							
														$params['mobile_nos_to_send']	= array('+'.$user_phone_code.$user_mobile_no);
														$params['sms_messaages']		= array($sms_temp_msg);
														if($user_mobile_no)	$this->User_notifications_model->initialize($params);
													}
													
												}
											}
										}
									}
									
									//Update of job details
									$data_to_store4 	= array('job_status' => '2', 'job_taken_by' => strval($insert_approved_details));
									$this->mongo_db->where(array('_id' => strval($job_id)));
									$this->mongo_db->set($data_to_store4);
									$this->mongo_db->update('jobs');
									
									$data_to_store_event['job_id'] 		= strval($job_id);
									$data_to_store_event['user_id']		= strval($user_id);
									$data_to_store_event['event_type'] 	= 'quote_accepted';
									$data_to_store_event['activity_details']= '';
									$data_to_store_event['event_cost'] 	= '0';
									$data_to_store_event['event_address'] 	= array();
									$data_to_store_event['added_on'] 		= date('Y-m-d H:i:s');
									$data_to_store_event['system_timezone']	= $this->system_timezone;
									$data_to_store_event['status']		= '1';
									$data_to_store_event['event_image'] 	= array();
									
									$insert_e 	= $this->mongo_db->insert('job_events', $data_to_store_event);
									
									$data_to_store1_event['job_id'] 		= strval($job_id);
									$data_to_store1_event['user_id']		= strval($user_id);
									$data_to_store1_event['event_type'] 	= 'order_started';
									$data_to_store1_event['activity_details'] = '';
									$data_to_store1_event['event_cost'] 	= '0';
									$data_to_store1_event['event_address'] 	= array();
									$data_to_store1_event['added_on'] 		= date('Y-m-d H:i:s');
									$data_to_store1_event['system_timezone']= $this->system_timezone;
									$data_to_store1_event['status']		= '1';
									$data_to_store1_event['event_image'] 	= array();
									
									$insert_e1 	= $this->mongo_db->insert('job_events', $data_to_store1_event);
								}
								
								$this->session->set_flashdata('flash_message', 'payment_success');
							}
							else{
								$this->session->set_flashdata('flash_message_cont', $error_code);
								$this->session->set_flashdata('flash_message', 'payment_failed');
							}
						}
						else
							$this->session->set_flashdata('flash_message', 'payment_failed');
					}
					else{
						$this->session->set_flashdata('flash_message_cont', 'Invalid contents.');
						$this->session->set_flashdata('flash_message', 'payment_failed');
					}
				}
				catch (Exception $e) {
					$this->session->set_flashdata('flash_message_cont', $e->getMessage());
					$this->session->set_flashdata('flash_message', 'payment_failed');
				} 
			}
		}
		else
		{
			$data_to_store1['payment_type']		= $payment_type;
			$data_to_store1['user_id']  			= strval($user_id);
			$data_to_store1['card_id']  			= '';
			$data_to_store1['amount']  			= strval($amount_to_charge);
			$data_to_store1['currency']  			= (isset($make_payment_det['currency'])) 	? strval($make_payment_det['currency']) 		: '';
			$data_to_store1['bill_date']  		= date('Y-m-d H:i:s');
			$data_to_store1['system_timezone']		= $this->system_timezone;
			$data_to_store1['payment_status']  	= '0';
			$data_to_store1['name_on_card']  		= '';
			$data_to_store1['address_city']  		= '';
			$data_to_store1['address_country']  	= '';
			$data_to_store1['address_line1']  		= '';
			$data_to_store1['address_line2']  		= '';
			$data_to_store1['address_state']  		= '';
			$data_to_store1['address_zip']  		= '';
			$data_to_store1['exp_month']  		= '';
			$data_to_store1['exp_year']  			= '';
			$data_to_store1['card_brand']  		= '';
			$data_to_store1['card_last_digits']  	= '';
			$data_to_store1['status']  			= '1';
				
			//Inserting the data to db
			$insert_pay_details 				= $this->mongo_db->insert('user_payments', $data_to_store1); 
			$pay_id 							= $insert_pay_details;
				
			if($pay_id)
			{
				$aprox_pay_date = '';
					if(!empty($extra_days) && ($extra_days > 0))
						$aprox_pay_date 	= date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $extra_days, date('Y')));
				
				$data_to_store2 					= array();
				$data_to_store2['job_id'] 			= strval($job_id);
				$data_to_store2['quote_ids'] 			= strval($quote_id);
				$data_to_store2['total_price']		= strval($amount_to_charge);
				$data_to_store2['payment_type']		= '1';
				$data_to_store2['payment_id']			= strval($pay_id);
				$data_to_store2['payment_status']		= '0';
				
				$data_to_store2['refundable_amount']	= strval($extra_amount);
				$data_to_store2['refundable_percent']	= $this->refundable_percent;
				$data_to_store2['deduction_amount']	= $deduction_amount;
				$data_to_store2['deduction_percent']	= $deduction_percent;
				$data_to_store2['extra_amount']		= $extra_amount_p;
				$data_to_store2['extra_percent']		= $extra_amount_percent;
				$data_to_store2['extra_days']			= $extra_days;
				$data_to_store2['aprox_pay_date']		= $aprox_pay_date;
				
				$data_to_store2['is_pay_date_over']	= '0';
				$data_to_store2['is_refunded']		= '0';
				$data_to_store2['refund_pay_id']		= '';
				$data_to_store2['approved_on']		= date('Y-m-d H:i:s');
				$data_to_store2['system_timezone']		= $this->system_timezone;
				$data_to_store2['status']			= '1';
				
				//Inserting the data to db
				$insert_approved_details 		= $this->mongo_db->insert('job_approved_quotes', $data_to_store2);
				
				//Update of job quote
				$quote_ids 					= explode(',', $quote_id);
				if(!empty($quote_ids))
				{
					foreach($quote_ids as $qid)
					{
						$data_to_store3 		= array('request_status' => '1');
						$this->mongo_db->where(array('_id' => strval($qid)));
						$this->mongo_db->set($data_to_store3);
						$this->mongo_db->update('job_quotes_legs');
						
						//sending each quote/leg bidder confirmation email and sms.	
						$this->mongo_db->where(array('_id' => strval($qid)));
						$job_quote_details 	= $this->mongo_db->get('job_quotes_legs');
						$quote_user_id 	= (isset($job_quote_details[0]['user_id'])) 	? $job_quote_details[0]['user_id'] : '';
						$quote_type 		= (isset($job_quote_details[0]['type'])) 	? $job_quote_details[0]['type'] 	: '';
						
						if($quote_user_id)
						{
							$this->mongo_db->where(array('_id' => $quote_user_id));
							$job_user_details 	= $this->mongo_db->get('site_users');
							
							$user_email_id 	= isset($job_user_details[0]['email']) ? $job_user_details[0]['email'] : '';
							$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
							
							$user_phone_code 	= isset($job_user_details[0]['phone_code']) ? $job_user_details[0]['phone_code'] : '';
							$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
							
							$to_name 			= isset($job_user_details[0]['first_name']) ? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
							$leg_type 		= ($quote_type == 1) ? 'quote' : 'leg';
							
							//Getting the user settings
							$this->mongo_db->where(array('user_id' => $quote_user_id));
							$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
							
							if(!empty($job_user_notification_settings[0]))
							{
								$check_for_email 	= (isset($job_user_notification_settings[0]['quote_accept_jobs_notification']['email'])) ? $job_user_notification_settings[0]['quote_accept_jobs_notification']['email'] : 0;
								
								$check_for_sms 	= (isset($job_user_notification_settings[0]['quote_accept_jobs_notification']['sms'])) ? $job_user_notification_settings[0]['quote_accept_jobs_notification']['sms'] : 0;
								
								if($check_for_email)
								{
									$this->mongo_db->where(array('email_title' => 'job_approve_submit'));
									$email_temp_arr 	= $this->mongo_db->get('email_templates');
									$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
									
									if(!empty($email_temp))
									{
										$search 		= array('[SITE_LOGO]', '[NAME]', '[LEG_TYPE]', '[CUSTOMER_NAME]', '[SITE_NAME]');
										$replace 		= array(base_url().'assets/site/images/logo.png', $to_name, $leg_type, $customer_name, $sitename);
										
										$email_temp_msg= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
										$email_temp_msg= str_replace($search, $replace, $email_temp_msg);
										
										$email_temp_sub= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
										
										if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
									}
								}
								
								//Check for sms settings
								$this->mongo_db->where(array('sms_title' => 'job_approve_submit'));
								$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
								$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
								if($user_mobile_no && $check_for_sms && !empty($sms_temp))
								{
									$search_sms 		= array('[LEG_TYPE]');
									$replace_sms 		= array($leg_type);
									
									$sms_temp_msg= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
									$sms_temp_msg= str_replace($search_sms, $replace_sms, $sms_temp_msg);
		
									$params['mobile_nos_to_send']	= array('+'.$user_phone_code.$user_mobile_no);
									$params['sms_messaages']		= array($sms_temp_msg);
									if($user_mobile_no)	$this->User_notifications_model->initialize($params);
								}
								
							}
						}
					}
				}
				
				//Update of job details
				$data_to_store4 = array('job_status' => '2', 'job_taken_by' => strval($insert_approved_details));
				$this->mongo_db->where(array('_id' => strval($job_id)));
				$this->mongo_db->set($data_to_store4);
				$this->mongo_db->update('jobs');
				//sending the email and test message to respective job bidders
				
				$data_to_store_event['job_id'] 		= strval($job_id);
				$data_to_store_event['user_id']		= strval($user_id);
				$data_to_store_event['event_type'] 	= 'quote_accepted';
				$data_to_store_event['activity_details']= '';
				$data_to_store_event['event_cost'] 	= '0';
				$data_to_store_event['event_address'] 	= array();
				$data_to_store_event['added_on'] 		= date('Y-m-d H:i:s');
				$data_to_store_event['system_timezone']	= $this->system_timezone;
				$data_to_store_event['status']		= '1';
				$data_to_store_event['event_image'] 	= array();
				
				$insert_e 						= $this->mongo_db->insert('job_events', $data_to_store_event);

				
				$data_to_store1_event['job_id'] 		= strval($job_id);
				$data_to_store1_event['user_id']		= strval($user_id);
				$data_to_store1_event['event_type'] 	= 'order_started';
				$data_to_store1_event['activity_details'] = '';
				$data_to_store1_event['event_cost'] 	= '0';
				$data_to_store1_event['event_address'] 	= array();
				$data_to_store1_event['added_on'] 		= date('Y-m-d H:i:s');
				$data_to_store1_event['system_timezone']= $this->system_timezone;
				$data_to_store1_event['status']		= '1';
				$data_to_store1_event['event_image'] 	= array();
				
				$insert_e1 	= $this->mongo_db->insert('job_events', $data_to_store1_event);
			}
			
			$this->session->set_flashdata('flash_message', 'payment_success');
		}
		
		redirect('dashboard');
	}
	
	public function update_job_details()
	{
		$all_jobs 	= $this->mongo_db->get('job_quotes_legs');
		
		if(!empty($all_jobs))
		{
			foreach($all_jobs as $jobs)
			{
				$job_id 			= isset($jobs['_id']) 						? strval($jobs['_id']) 				: '';
				$pick_addr_code	= isset($jobs['start_location']['country_code']) 	? $jobs['start_location']['country_code'] 	: '';
				
				if($pick_addr_code == '')
				{
					$pick_up_addr		= isset($jobs['start_location']['address']) 		? $jobs['start_location']['address'] 	: '';
					$pick_up_addr_lat 	= isset($jobs['start_location']['lat']) 		? $jobs['start_location']['lat'] 		: '';
					$pick_up_addr_lng 	= isset($jobs['start_location']['long']) 		? $jobs['start_location']['long'] 		: '';
					$pick_country_name	= isset($jobs['start_location']['country']) 		? $jobs['start_location']['country'] 	: '';
					$pick_country_code	= isset($jobs['start_location']['country_code']) 	? $jobs['start_location']['country_code']: '';
					
					$drop_up_addr		= isset($jobs['end_location']['address']) 		? $jobs['end_location']['address'] 	: '';
					$drop_up_addr_lat 	= isset($jobs['end_location']['lat']) 			? $jobs['end_location']['lat'] 		: '';
					$drop_up_addr_lng 	= isset($jobs['end_location']['long']) 			? $jobs['end_location']['long'] 		: '';
					$drop_country_name	= isset($jobs['end_location']['country']) 		? $jobs['end_location']['country'] 	: '';
					$drop_country_code	= isset($jobs['end_location']['country_code']) 	? $jobs['end_location']['country_code'] : '';
					
					$pickup_date		= isset($jobs['pickup_date']) 				? $jobs['pickup_date'] 				: '';
					$pickup_date		= ($pickup_date) 							? date('Y-m-d', strtotime($pickup_date)): date('Y-m-d');
					
					$drop_date		= isset($jobs['drop_date']) 					? $jobs['drop_date'] 				: '';
					$drop_date		= ($drop_date) 							? date('Y-m-d', strtotime($drop_date)) 	: date('Y-m-d');
					
					$pick_country_data = (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$pick_up_addr_lat.'&lng='.$pick_up_addr_lng.'&username='.$this->geonames_username.'&type=JSON', false, $context));
					$pick_country_data_arr 	= json_decode($pick_country_data);
					$pick_country_name 	= isset($pick_country_data_arr->countryName) 	? $pick_country_data_arr->countryName 	: '';
					$pick_country_code 	= isset($pick_country_data_arr->countryCode) 	? $pick_country_data_arr->countryCode 	: '';
					
					$data_to_store['start_location']['address'] 						= $pick_up_addr;
					$data_to_store['start_location']['lat'] 						= (float)$pick_up_addr_lat;
					$data_to_store['start_location']['lat_str'] 						= strval($pick_up_addr_lat);
					$data_to_store['start_location']['long'] 						= (float)$pick_up_addr_lng;
					$data_to_store['start_location']['long_str'] 					= strval($pick_up_addr_lng);
					$data_to_store['start_location']['country'] 						= $pick_country_name;
					$data_to_store['start_location']['country_code'] 					= $pick_country_code;
					
					$drop_country_data = (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$pick_up_addr_lat.'&lng='.$pick_up_addr_lng.'&username='.$this->geonames_username.'&type=JSON', false, $context));
					$drop_country_data_arr 	= json_decode($drop_country_data);
					$drop_country_name 	= isset($drop_country_data_arr->countryName) 	? $drop_country_data_arr->countryName 	: '';
					$drop_country_code 	= isset($drop_country_data_arr->countryCode) 	? $drop_country_data_arr->countryCode 	: '';
					
					$data_to_store['end_location']['address'] 						= $drop_up_addr;
					$data_to_store['end_location']['lat'] 							= (float)$drop_up_addr_lat;
					$data_to_store['end_location']['lat_str'] 						= strval($drop_up_addr_lat);
					$data_to_store['end_location']['long'] 							= (float)$drop_up_addr_lng;
					$data_to_store['end_location']['long_str'] 						= strval($drop_up_addr_lng);
					$data_to_store['end_location']['country'] 						= $drop_country_name;
					$data_to_store['end_location']['country_code'] 					= $drop_country_code;
					
					$data_to_store['pickup_date'] 								= $pickup_date;
					$data_to_store['drop_date'] 									= $drop_date;
					
					echo 'arijit: '.$job_id.'.<pre>'; print_r($data_to_store); echo '</pre>';
					
					//$this->mongo_db->where(array('_id' => strval($job_id)));
					//$this->mongo_db->set($data_to_store);
					//$this->mongo_db->update('job_quotes_legs'); 
				}
				
				
				
				//die;
			}
		}
	}
	
	
	
	
	public function update_user_details()
	{
		$opts = array('http' =>
			array(
			    'method'  => 'GET',
			    'timeout' => 120 
			)
		);
		
		$context  = stream_context_create($opts);
		
		$all_users 	= $this->mongo_db->get('site_users');
		
		if(!empty($all_users))
		{
			foreach($all_users as $user)
			{
				$user_id				= strval($user['_id']);
				$user_type 			= strval($user['user_type']);
				
				if($user_type == 'customer' || $user_type == 'fleet' || $user_type == 'broker' || $user_type == 'depot')
				{
					$company_address		= isset($user['company_address']['address']) 		? $user['company_address']['address'] 		: '';
					$company_address_lat 	= isset($user['company_address']['lat']) 			? $user['company_address']['lat'] 			: '';
					$company_address_lng 	= isset($user['company_address']['long']) 			? $user['company_address']['long'] 		: '';
					$company_country_name	= isset($user['company_address']['country']) 		? $user['company_address']['country'] 		: '';
					$company_country_code	= isset($user['company_address']['country_code']) 	? $user['company_address']['country_code']	: '';
					
					$delivery_addr			= isset($user['delivery_address']['address']) 		? $user['delivery_address']['address'] 		: '';
					$delivery_addr_lat 		= isset($user['delivery_address']['lat']) 			? $user['delivery_address']['lat'] 		: '';
					$delivery_addr_lng 		= isset($user['delivery_address']['long']) 			? $user['delivery_address']['long'] 		: '';
					$delivery_country_name	= isset($user['delivery_address']['country']) 		? $user['delivery_address']['country'] 		: '';
					$delivery_country_code	= isset($user['delivery_address']['country_code']) 	? $user['delivery_address']['country_code'] 	: '';
					
					$pickup_addr			= isset($user['pickup_address']['address']) 			? $user['pickup_address']['address'] 		: '';
					$pickup_addr_lat 		= isset($user['pickup_address']['lat']) 			? $user['pickup_address']['lat'] 			: '';
					$pickup_addr_lng 		= isset($user['pickup_address']['long']) 			? $user['pickup_address']['long'] 			: '';
					$pickup_country_name	= isset($user['pickup_address']['country']) 			? $user['pickup_address']['country'] 		: '';
					$pickup_country_code	= isset($user['pickup_address']['country_code']) 		? $jobs['pickup_address']['country_code'] 	: '';
					
					
					$company_address_data 	= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$company_address_lat.'&lng='.$company_address_lng.'&username='.$this->geonames_username.'&type=JSON', false, $context));
					$company_address_data_arr = json_decode($company_address_data);
					
					$company_country_name 	= isset($company_address_data_arr->countryName)			? $company_address_data_arr->countryName 		: '';
					$company_country_code 	= isset($company_address_data_arr->countryCode) 			? $company_address_data_arr->countryCode 		: '';
					
					
					$data_to_store['company_address']['address'] 	= $company_address;
					$data_to_store['company_address']['lat'] 		= (float)$company_address_lat;
					$data_to_store['company_address']['lat_str'] 	= strval($company_address_lat);
					$data_to_store['company_address']['long'] 		= (float)$company_address_lng;
					$data_to_store['company_address']['long_str'] 	= strval($company_address_lng);
					$data_to_store['company_address']['country'] 	= $company_country_name;
					$data_to_store['company_address']['country_code'] = $company_country_code;
					
					
					$delivery_address_data 		= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$delivery_addr_lat.'&lng='.$delivery_addr_lng.'&username='.$this->geonames_username.'&type=JSON', false, $context));
					$delivery_address_data_arr 	= json_decode($delivery_address_data);
					
					$delivery_country_name 		= isset($delivery_address_data_arr->countryName)			? $delivery_address_data_arr->countryName 		: '';
					$delivery_country_code 		= isset($delivery_address_data_arr->countryCode) 			? $delivery_address_data_arr->countryCode 		: '';
					
					
					$data_to_store['delivery_address']['address']		= $delivery_addr;
					$data_to_store['delivery_address']['lat'] 			= (float)$delivery_addr_lat;
					$data_to_store['delivery_address']['lat_str'] 		= strval($delivery_addr_lat);
					$data_to_store['delivery_address']['long'] 			= (float)$delivery_addr_lng;
					$data_to_store['delivery_address']['long_str'] 		= strval($delivery_addr_lng);
					$data_to_store['delivery_address']['country'] 		= $delivery_country_name;
					$data_to_store['delivery_address']['country_code'] 	= $delivery_country_code;
					
					
					$pickup_address_data 		= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$pickup_addr_lat.'&lng='.$pickup_addr_lng.'&username='.$this->geonames_username.'&type=JSON', false, $context));
					
					$pickup_address_data_arr 	= json_decode($pickup_address_data);
					$pickup_country_name 		= isset($pickup_address_data_arr->countryName)			? $pickup_address_data_arr->countryName 		: '';
					$pickup_country_code 		= isset($pickup_address_data_arr->countryCode) 			? $pickup_address_data_arr->countryCode 		: '';
					
					
					$data_to_store['pickup_address']['address']			= $pickup_addr;
					$data_to_store['pickup_address']['lat'] 			= (float)$pickup_addr_lat;
					$data_to_store['pickup_address']['lat_str'] 			= strval($pickup_addr_lat);
					$data_to_store['pickup_address']['long'] 			= (float)$pickup_addr_lng;
					$data_to_store['pickup_address']['long_str'] 		= strval($pickup_addr_lng);
					$data_to_store['pickup_address']['country'] 			= $pickup_country_name;
					$data_to_store['pickup_address']['country_code'] 		= $pickup_country_code;
					
					
					echo '<pre>'; print_r($data_to_store); echo '</pre>';
					
					$this->mongo_db->where(array('_id' => strval($user_id)));
					$this->mongo_db->set($data_to_store);
					$this->mongo_db->update('site_users');
				}
				
			}
		}
	}
}

?>