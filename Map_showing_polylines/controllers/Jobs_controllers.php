<?php

class Jobs_controllers extends CI_Controller {

	var $geonames_username			= "arijit2016";
	var $radius_range 				= 5;
	var $extra_percent_charge		= 20;
	var $limit_page				= 10;
	var $system_timezone 			= 'UTC';
	var $linkedinApiKey				= '';
	var $linkedinApiSecret 			= '';
	
	var $get_company_det 			= '';
	var $get_company_arr 			= '';
	var $cmp_auth_name				= '';
	var $cmp_auth_id				= '';
	var $cmp_details 				= '';
	var $cmp_id					= '';
	var $settings					= '';
	var $site_title 				= '';
	var $pdesc					= '';
	var $pkeys					= '';
	var $site_logo					= '';
	var $cmp_auth_link_id 			= '';
	var $cmp_auth_no				= '';
	var $data						= array();
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Home_model');
		$this->load->model('sitesetting_model');
		$this->load->model('Users_model');
		$this->load->library('ImageThumb');
		
		$this->load->model('Check_line_intersect_model');
		$this->load->model('Check_map_lines_model');
		$this->load->model('User_email_model');
		$this->load->model('User_notifications_model');
		
		//Getting site settings data
		$settings_data = $this->sitesetting_model->get_settings();
		$this->extra_percent_charge 		= (isset($settings_data[0]['refundable_deposit_percent'])) ? $settings_data[0]['refundable_deposit_percent'] : $this->extra_percent_charge;
		
		$this->limit_page 				= (isset($settings_data[0]['site_pagination'])) 	? $settings_data[0]['site_pagination'] 	: $this->limit_page;
		$this->system_timezone			= (isset($settings_data[0]['system_timezone'])) 	? $settings_data[0]['system_timezone'] 	: $this->system_timezone;
		$this->geonames_username 		= (isset($settings_data[0]['geonames_username'])) ? $settings_data[0]['geonames_username']: $this->geonames_username;
		
		//FOR MERCHANT WISE SITE DYANAMICITY
		$this->settings				= (isset($settings_data[0])) 		? $settings_data[0] : '';
		$this->get_company_det 			= $this->uri->segment('1');
		$this->get_company_arr			= explode('-', $this->get_company_det);
		
		$this->cmp_auth_name 			= isset($this->get_company_arr[0]) ? $this->get_company_arr[0] : '';
		$this->cmp_auth_id 				= isset($this->get_company_arr[1]) ? $this->get_company_arr[1] : '';
		
		//Get company details
		$this->mongo_db->where(array('cmp_auth_id' => $this->cmp_auth_id));
		$cmp_details_act 				= $this->mongo_db->get('site_users');
		$this->cmp_details				= (isset($cmp_details_act[0]) && !empty($cmp_details_act[0])) ? $cmp_details_act[0] : array();
		
		if(!empty($this->cmp_details))
		{
			$this->cmp_id				= isset($this->cmp_details['_id']) 		? strval($this->cmp_details['_id']) 	: '';		
			$this->site_title 			= isset($this->cmp_details['site_title']) 	? $this->cmp_details['site_title'] 	: $this->settings['site_name'];
			$this->pdesc 				= isset($this->cmp_details['site_meta']) 	? $this->cmp_details['site_meta'] 		: $this->settings['meta_description'];
			$this->pkeys 				= isset($this->cmp_details['site_keyword']) 	? $this->cmp_details['site_keyword'] 	: $this->settings['meta_keywords'];
			$this->site_logo 			= isset($this->cmp_details['site_logo']) 	? $this->cmp_details['site_logo'] 		: '';
			
			if(!empty($this->cmp_auth_name) && !empty($this->cmp_auth_id))
				$this->cmp_auth_link_id	= $this->cmp_auth_name.'-'.$this->cmp_auth_id;
		}
		else
		{
			$this->cmp_auth_name = $this->cmp_auth_id =  $this->site_logo = '';
			
			$this->site_title 			= $this->settings['site_name'];
			$this->pdesc 				= $this->settings['meta_description'];
			$this->pkeys 				= $this->settings['meta_keywords'];
		}
		
		if(!empty($this->cmp_auth_link_id))
			$this->config->set_item('base_url', base_url().$this->cmp_auth_link_id) ;
			
		$this->data['settings'] 			= $this->settings;
		
		$this->data['cmp_auth_link']		= isset($this->cmp_auth_link_id) 	? $this->cmp_auth_link_id : '';
		$this->data['cmp_auth_name']		= isset($this->cmp_auth_name) 	? $this->cmp_auth_name 	 : '';
		$this->data['cmp_auth_id']		= isset($this->cmp_auth_id) 		? $this->cmp_auth_id 	 : '';
		
		$this->data['cmp_details']		= isset($this->cmp_details[0]) ? $this->cmp_details[0] : array();
		$this->data['ptitle']			= $this->site_title;
		$this->data['pdesc']			= $this->pdesc;
		$this->data['pkeys']			= $this->pkeys;
		$this->data['site_logo']			= $this->site_logo;
		//END
		
		//Added php gzip compression
		$controller_name 				= $this->router->fetch_class();
		$function_name 				= $this->router->fetch_method();
		
		if($function_name != 'job_legs_filter_arr')
		{
			if(!$this->session->userdata('site_is_logged_in'))
				redirect('');
		}
		
	}
	
	function converToTz($time="", $toTz='', $fromTz='', $format='Y-m-d H:i:s')
	{
		//echo 'arijit: '.date_default_timezone_get().'<br>';
		//echo date('Y-m-d H:i:s').'<br>';
		// timezone by php friendly values
		$date = new DateTime($time, new DateTimeZone($fromTz));
		$date->setTimezone(new DateTimeZone($toTz));
		$time= $date->format($format);
		return $time;
	}

	//Dashboard index function to show the page
	public function index()
	{
		$user_id 					= $this->session->userdata('site_user_objId_hotcargo');
		$user_type 				= $this->session->userdata('site_user_type_hotcargo');
		
		$data['data']				= $this->data;
		$data['view_link'] 			= 'site/dashboard/index';
		$this->load->view('includes/template_site', $data);
	}
	
	public function add_job()
	{
		$user_id 					= $this->session->userdata('site_user_objId_hotcargo');
		$user_type 				= $this->session->userdata('site_user_type_hotcargo');
		
		//Getting all sizes
		$this->mongo_db->where(array('status' => '1'));
		$sizes_list 				= $this->mongo_db->get('sizes');
		
		//Getting all fields info contetns
		$this->mongo_db->where(array('page_title' => 'job'));
		$help_contents_list 		= $this->mongo_db->get('pages_help_contents');
		
		//Getting all specials
		$this->mongo_db->where(array('status' => '1'));
		$special_list 				= $this->mongo_db->get('special');
		
		//Getting all types
		$this->mongo_db->where(array('status' => '1'));
		$types_list 				= $this->mongo_db->get('types');
		
		$data['sizes_list']			= $sizes_list;
		$data['special_list']		= $special_list;
		$data['types_list']			= $types_list;
		
		$data['help_contents_list']	= (isset($help_contents_list[0]) && !empty($help_contents_list[0])) ? $help_contents_list[0] : array();
		
		$data['data']				= $this->data;
		$data['view_link'] 			= 'site/jobs/add_job';
		$this->load->view('includes/template_site', $data);
	}
	
	public function submit_job()
	{	
		$user_id 						= $this->session->userdata('site_user_objId_hotcargo');
		$user_type 					= $this->session->userdata('site_user_type_hotcargo');
		
		$this->mongo_db->where(array('_id' => $user_id));
		$job_user_details 				= $this->mongo_db->get('site_users');
		$job_owner_name     			= ucwords($job_user_details[0]['first_name']." ".$job_user_details[0]['last_name']);
		$user_timezone 				= (isset($_COOKIE['user_timezone']) && $_COOKIE['user_timezone']!='') ? $_COOKIE['user_timezone'] : $this->system_timezone;
		
		$opts = array('http' =>
			array(
			    'method'  => 'GET',
			    'timeout' => 120 
			)
		);
		
		$context  		= stream_context_create($opts);
		$data_to_store 	= $pickup_addr = $drop_addr = array();
		
		$pick_country_data 			= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$this->input->post('pickup_address_lat').'&lng='.$this->input->post('pickup_address_lng').'&username='.$this->geonames_username.'&type=JSON', false, $context));
		$pick_country_data_arr 		= json_decode($pick_country_data);
		$pick_country_name 			= isset($pick_country_data_arr->countryName) ? $pick_country_data_arr->countryName : '';
		$pick_country_code 			= isset($pick_country_data_arr->countryCode) ? $pick_country_data_arr->countryCode : '';
		
		$pickup_addr				= array('address' => $this->input->post('pickup_address'), 'lat' => (float)$this->input->post('pickup_address_lat'), 'lat_str' => strval($this->input->post('pickup_address_lat')), 'long' => (float)$this->input->post('pickup_address_lng'), 'long_str' => strval($this->input->post('pickup_address_lng')), 'country' => $pick_country_name, 'country_code' => $pick_country_code);
		
		
		$drop_country_data 			= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$this->input->post('drop_address_lat').'&lng='.$this->input->post('drop_address_lng').'&username='.$this->geonames_username.'&type=JSON', false, $context));
		$drop_country_data_arr 		= json_decode($drop_country_data);
		$drop_country_name 			= isset($drop_country_data_arr->countryName) ? $drop_country_data_arr->countryName : '';
		$drop_country_code 			= isset($drop_country_data_arr->countryCode) ? $drop_country_data_arr->countryCode : '';
	
		$drop_addr				= array('address' => $this->input->post('drop_address'), 'lat' => (float)$this->input->post('drop_address_lat'), 'lat_str' => strval($this->input->post('drop_address_lat')), 'long' => (float)$this->input->post('drop_address_lng'), 'long_str' => strval($this->input->post('drop_address_lng')), 'country' => $drop_country_name, 'country_code' => $drop_country_code);
		
		$data_to_store['user_id']		= $user_id;
		$data_to_store['title']			= '';
		$data_to_store['description']		= $this->input->post('description');
		$data_to_store['image']			= '';
		$data_to_store['pickup_address']	= $pickup_addr;
		$data_to_store['drop_address']	= $drop_addr;
		$data_to_store['distance']		= (float)$this->input->post('distance_val');
		$data_to_store['distance_type']	= "miles";
		$data_to_store['deliver_method']	= $this->input->post('deliver_method');
		$data_to_store['delivery_date']	= date('Y-m-d', strtotime($this->input->post('deliver_date')));
		
		$data_to_store['cargo_value']		= (float)$this->input->post('cargo_value');
		$data_to_store['size_type']		= $this->input->post('size_type');
		$data_to_store['size']			= (object)array('width' => (float)$this->input->post('size_width'), 'height' => (float)$this->input->post('size_length'), 'depth' => (float)$this->input->post('size_height'));
		$data_to_store['bins']			= array();
		$data_to_store['items']			= array();
		
		$data_to_store['type']			= $this->input->post('type');
		$data_to_store['special']		= $this->input->post('special');
		$data_to_store['weight']			= (float)$this->input->post('weight');
		$data_to_store['max_job_price']	= (float)$this->input->post('max_job_price');
		$data_to_store['is_gurrented']	= ($this->input->post('is_gurrented')) 	? $this->input->post('is_gurrented') 	: 0;
		$data_to_store['is_insured']		= ($this->input->post('is_insured'))	? $this->input->post('is_insured')		: 0;
		
		$data_to_store['job_priority']	= '0';
		$data_to_store['other_details']	= (object)array();
		$data_to_store['job_status']		= '0';
		$data_to_store['job_taken_by']	= '0';
		$data_to_store['added_on']		= (date('Y-m-d H:i:s'));
		$data_to_store['system_timezone']	= $this->system_timezone;
		$data_to_store['status']			= '1';
		
		//echo "<pre>";print_r($data_to_store);die;
		$insert 						= $this->mongo_db->insert('jobs', $data_to_store);
		$data_to_store_file 			= array();
		
		if($insert)
		{
			if(!empty($_FILES['job_image']['name']))
			{
				$file_type 			= (isset($_FILES['job_image']['type'])) ? explode('/', $_FILES['job_image']['type']) : array();
				$file_type_det 		= (isset($file_type[0])) ? $file_type[0] : '';
				
				$filename 			= (isset($_FILES['job_image']['name'])) ? substr($_FILES['job_image']['name'],strripos($_FILES['job_image']['name'],'.')) : '';
				$s					= time().$filename;
				$file 				= $_FILES['job_image']['tmp_name'];
				
			
				$DIR_IMG_NORMAL 		= FILEUPLOADPATH.'assets/uploads/job_images/';
				$fileNormal 			= $DIR_IMG_NORMAL.$s;
				$result 				= move_uploaded_file($file, $fileNormal);
				
				if($result)
				{
					$srcPath			= FILEUPLOADPATH.'assets/uploads/job_images/'.$s;
					$destPath1 		= FILEUPLOADPATH.'assets/uploads/job_images/thumb/'.$s;
					$destWidth1		= 500;
					$destHeight1		= 500;
					$this->imagethumb->resizeProportional($destPath1, $srcPath, $destWidth1, $destHeight1);
					$image_name		= $s;
					
					$data_to_store_file['image'] = $image_name;
				}
				
				if(!empty($data_to_store_file))
				{
					$this->mongo_db->where(array('_id' => strval($insert)));
					$this->mongo_db->set($data_to_store_file);
					$this->mongo_db->update('jobs');
				}
			}
			
			$job_cordinates 	= array(array('lat' => $this->input->post('pickup_address_lat'), 'lng' => $this->input->post('pickup_address_lng')), array('lat' => $this->input->post('drop_address_lat'), 'lng' => $this->input->post('drop_address_lng')));
			
			$this->session->set_flashdata('flash_message_cont', json_encode($job_cordinates));
			$this->session->set_flashdata('flash_message', 'job_add_success');
			
			//for sending email/sms adding jobs
			$this->mongo_db->where(array('iso' => $pick_country_code));
			$pick_country_det 		= $this->mongo_db->get('countries');
			$job_pick_country_id	= isset($pick_country_det[0]['_id']) ? strval($pick_country_det[0]['_id']) : '';
			
			$this->mongo_db->where(array('iso' => $drop_country_code));
			$drop_country_det 	= $this->mongo_db->get('countries');
			$job_drop_country_id	= isset($drop_country_det[0]['_id']) ? strval($drop_country_det[0]['_id']) : '';
			
			
			//For Checking merchant wise jobs
			$cmp_user_ids = array();
			if(!empty($this->cmp_auth_id))
			{
					//$where_arr['merchant_id']	= $this->cmp_auth_id;
					
					//Get all users of this company
					$this->mongo_db->where(array('merchant_id' => $this->cmp_auth_id));
					$all_cmp_users_arr 			= $this->mongo_db->get('site_users');
					if(!empty($all_cmp_users_arr)) {
						foreach($all_cmp_users_arr as $cmp) $cmp_user_ids[] = (isset($cmp['_id'])) ? strval($cmp['_id']) : '';
					}
			}
			//Getting all users who have checked all countries
			$this->mongo_db->where(array('is_all_countries' => '1'));
			$this->mongo_db->where_ne('user_id',$user_id);
			//Getting all users of this company wise
			if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
			$all_country_user_list 	= $this->mongo_db->get('user_job_countries');
			
			//Getting all users who have selected countries
			$this->mongo_db->where(array('is_all_countries' => '0'));
			$this->mongo_db->where_ne('user_id',$user_id);
			$this->mongo_db->where_in_all('countries',array($job_pick_country_id));
			//Getting all users of this company wise
			if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
			$selected_ecountry_user_list 	= $this->mongo_db->get('user_job_countries');
			
			$reciever_use_arr 			= array_merge($all_country_user_list, $selected_ecountry_user_list);
			array_filter($selected_ecountry_user_list);
			//$selected_ecountry_user_list 	= array_unique($selected_ecountry_user_list);
			
			//loop for sms/email receiver
			if(!empty($reciever_use_arr))
			{
				$params['mobile_nos_to_send'] = $params['sms_messaages'] 		= array();
				$params_email['email_ids']	= $params_email['email_names'] 	= array();
				$params_email['email_contents'] 	= $params_email['email_subs']	= '';
				
				$amount 		 = $data_to_store['cargo_value'];
				$start_location = $this->input->post('pickup_address');
				$end_location   = $this->input->post('drop_address');
				$job_owner_name = $job_owner_name;
				
				foreach($reciever_use_arr as $r => $receivers)
				{
					$receiver_user_id 	= (isset($receivers['user_id'])) ? $receivers['user_id'] : '';
					
					$this->mongo_db->where(array('_id' => $receiver_user_id));
					$job_user_details 	= $this->mongo_db->get('site_users');
					
					$user_email_id 	= isset($job_user_details[0]['email']) 		? $job_user_details[0]['email'] 		: '';
					$user_mcountry_code = isset($job_user_details[0]['phone_code']) 	? $job_user_details[0]['phone_code'] 	: '1';
					$user_mobile_no 	= isset($job_user_details[0]['user_phone']) 	? $job_user_details[0]['user_phone'] 	: '';
					$to_name 			= isset($job_user_details[0]['first_name']) 	? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';				
					//Getting the user settings
					$this->mongo_db->where(array('user_id' => $receiver_user_id));
					$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
					
					if(!empty($job_user_notification_settings[0]))
					{
						$check_for_email 	= (isset($job_user_notification_settings[0]['new_jobs_notification']['email'])) ? $job_user_notification_settings[0]['new_jobs_notification']['email'] : 0;
						
						$check_for_sms 	= (isset($job_user_notification_settings[0]['new_jobs_notification']['sms'])) ? $job_user_notification_settings[0]['new_jobs_notification']['sms'] : 0;
						
						$this->mongo_db->where(array('email_title' => 'job_submit'));
						$email_temp_arr 	= $this->mongo_db->get('email_templates');
						$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
						
						//Check for email settings 
						if(!empty($email_temp) && $check_for_email == '1')
						{
							$search 			= array('[SITE_LOGO]', '[NAME]', '[JOB_AMOUNT]', '[SITE_NAME]','[START_LOCATION]','[END_LOCATION]','[JOB_OWNER]');
							$replace 			= array(assets_url().'site/images/logo.png', $to_name, '$'.$amount, $sitename,$start_location,$end_location,$job_owner_name);
							$email_temp_msg	= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
							$email_temp_msg	= str_replace($search, $replace, $email_temp_msg);
							
							$email_temp_sub	= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
							
							$params_email['email_ids'][$r]		= $user_email_id;
							$params_email['email_names'][$r]		= $to_name;
							
							//if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
						}
						
						//Check for sms settings
						$this->mongo_db->where(array('sms_title' => 'job_submit'));
						$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
						$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
						
						if($user_mobile_no && $check_for_sms == '1' && !empty($sms_temp))
						{
							$search_sms 	= array('[JOB_OWNER]');
							$replace_sms 	= array($job_owner_name);
							
							$sms_temp_msg	= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
							$sms_temp_msg	= str_replace($search_sms, $replace_sms, $sms_temp_msg);
							
							$params['mobile_nos_to_send'][]	= '+'.$user_mcountry_code.$user_mobile_no;
							$params['sms_messaages'][]		= $sms_temp_msg;
						}
					}
				}
				
				//echo '<pre>'; print_r($params_email); print_r($params); die;
				
				//sending emails
				if(!empty($params_email['email_ids'])) 	$this->User_email_model->send_email($params_email['email_ids'], $email_temp_sub, $email_temp_msg, '', '', '', $params_email['email_names']);
				
				//sending sms
				if(!empty($params['sms_messaages'])) $this->User_notifications_model->initialize($params);
			}
			
			redirect('dashboard');
		}
		else
			redirect('dashboard');
	}
	
	//Function to fetch job details
	public function job_details_ajax()
	{
		$job_id 			= $this->input->post('job_id');
		$user_id 			= $this->input->post('user_id');
		
		$already_quote_leg_submit = 0; $all_content = $job_details = $job_quote_details = $job_leg_details = $user_details = $job_user_details = array();
		
		if(!empty($job_id) && ($job_id > 0))
		{
			if(!empty($user_id) && ($user_id > 0))
			{
				//Getting user details
				$this->mongo_db->where(array('_id' => $user_id));
				$user_all_details 	= $this->mongo_db->get('site_users');
				
				if(!empty($user_all_details))
				{
					//Getting user rating
					$this->mongo_db->where(array('user_id' => strval($user_id), 'status' => "1"));
					$user_rating 		= $this->mongo_db->get('user_rating');
					
					$user_details['id']			= isset($user_all_details[0]['_id']) 			? strval($user_all_details[0]['_id']) : '';
					$user_details['name']		= isset($user_all_details[0]['first_name']) 		? ucwords($user_all_details[0]['first_name'].' '.$user_all_details[0]['last_name']) : '';
					$user_details['user_image']	= isset($user_all_details[0]['profile_image']) 	? assets_url().'uploads/user_images/thumb/'.$user_all_details[0]['profile_image'] : '';
					$user_details['rating']		= (isset($user_rating[0]['user_rating'])) 		? number_format($user_rating[0]['user_rating'], 1) : '';
				}
				
				//check if user already submit the quote or leg
				$this->mongo_db->where(array('job_id' => $job_id, 'user_id' => $user_id));
				$already_quote_leg_submit 	= $this->mongo_db->count('job_quotes_legs');
			}
			
			//Job setails
			$this->mongo_db->where(array('_id' => $job_id));
			$job_details 	= $this->mongo_db->get('jobs');
			
			if(!empty($job_details))
			{
				$job_details[0]['id']		= (isset($job_details[0]['_id'])) ? strval($job_details[0]['_id']) : '';
				
				$job_user_id 	= (isset($job_details[0]['user_id'])) ? $job_details[0]['user_id'] : 0;
				
				//Checking further more for if the job is approved and the current session user is still part of the job
				$job_status 	= isset($job_details[0]['job_status']) ? $job_details[0]['job_status'] : 0;
				if($job_status == 2)
				{
					//check if user already submit the quote or leg
					$this->mongo_db->where(array('job_id' => $job_id, 'user_id' => $user_id, 'request_status' => '1'));
					$already_quote_leg_submit 	= $this->mongo_db->count('job_quotes_legs');
				}
				
				//Getting job posted user details
				$this->mongo_db->where(array('_id' => strval($job_user_id)));
				$job_user_all_details 	= $this->mongo_db->get('site_users');
				
				if(!empty($job_user_all_details))
				{
					//Getting user rating
					$this->mongo_db->where(array('user_id' => strval($job_user_id), 'status' => "1"));
					$job_user_rating 				= $this->mongo_db->get('user_rating');
					
					$job_user_details['id']			= isset($job_user_all_details[0]['_id']) 		? strval($job_user_all_details[0]['_id']) : '';
					$job_user_details['name']		= isset($job_user_all_details[0]['first_name']) 	? ucwords($job_user_all_details[0]['first_name'].' '.$job_user_all_details[0]['last_name']) : '';
					$job_user_details['user_image']	= isset($job_user_all_details[0]['profile_image'])? assets_url().'uploads/user_images/thumb/'.$job_user_all_details[0]['profile_image'] : '';
					$job_user_details['rating']		= (isset($job_user_rating[0]['user_rating'])) 	? $job_user_rating[0]['user_rating'] : '';
				}
				
				$job_details[0]['formated_date'] 		= $job_details[0]['size_details'] = $job_details[0]['type_details'] = $job_details[0]['special_details'] = '';
				
				$size_type 	= (isset($job_details[0]['size_type']) 	&& ($job_details[0]['size_type'] 	!= 'SIZE')) 	? $job_details[0]['size_type'] 	: '0';
				$job_type 	= (isset($job_details[0]['type']) 		&& ($job_details[0]['type'] 		!= 'TYPE')) 	? $job_details[0]['type'] 		: '0';
				$job_special 	= (isset($job_details[0]['special']) 	&& ($job_details[0]['special'] 	!= 'SPECIAL')) ? $job_details[0]['special'] 		: '0';
				
				$job_details[0]['formated_date'] 		= (isset($job_details[0]['delivery_date'])) ? date('dS M Y', strtotime($job_details[0]['delivery_date'])) : $job_details[0]['delivery_date'];
				
				//size details
				if(!empty($size_type))
				{
					$this->mongo_db->where(array('_id' => strval($size_type), 'status' => "1"));
					$size_details 	= $this->mongo_db->get('sizes');
					$job_details[0]['size_details'] = (isset($size_details[0]['title'])) ? $size_details[0]['title'] : '';
				}
				
				
				//type details
				if(!empty($job_type))
				{
					$this->mongo_db->where(array('_id' => strval($job_type), 'status' => "1"));
					$type_details 	= $this->mongo_db->get('types');
					$job_details[0]['type_details'] = (isset($type_details[0]['title'])) ? $type_details[0]['title'] : '';
				}
				
				//special details
				if(!empty($job_special))
				{
					$this->mongo_db->where(array('_id' => strval($job_special), 'status' => "1"));
					$special_details 	= $this->mongo_db->get('special');
					$job_details[0]['special_details'] = (isset($special_details[0]['title'])) ? $special_details[0]['title'] : '';
				}
				
				$job_details[0]['is_gurrented'] 	= (isset($job_details[0]['is_gurrented']) 	&& $job_details[0]['is_gurrented'] == 1) 	? 'Yes' : 'No';
				$job_details[0]['is_insured'] 	= (isset($job_details[0]['is_insured']) 	&& $job_details[0]['is_insured'] == 1) 		? 'Yes' : 'No';
				
				$job_details[0]['pick_up_addr'] 	= (isset($job_details[0]['pickup_address']['address'])) 	? $job_details[0]['pickup_address']['address'] 	: '';
				$job_details[0]['drop_addr'] 		= (isset($job_details[0]['drop_address']['address'])) 		? $job_details[0]['drop_address']['address'] 	: '';
				
				$job_details[0]['description']	= (isset($job_details[0]['description'])) ? ucfirst(trim($job_details[0]['description'])) : '';
				$job_details[0]['deliver_method']	= (isset($job_details[0]['deliver_method'])) ? ucfirst(str_replace('_', ' ', $job_details[0]['deliver_method'])) : '';
				$job_details[0]['cargo_value']	= (isset($job_details[0]['cargo_value'])) ? ucfirst(str_replace('_', ' ', $job_details[0]['cargo_value'])) : '';
			}
			
			//Job quote setails
			$this->mongo_db->where(array('job_id' 	=> $job_id, 'type' => '1'));
			$job_quote_details 	= $this->mongo_db->get('job_quotes_legs');
			
			//Job setails
			$this->mongo_db->where(array('job_id' 	=> $job_id, 'type' => '2'));
			$job_leg_details 	= $this->mongo_db->get('job_quotes_legs');
			
			//get the job detials of leg
			$this->mongo_db->where(array('job_id' 	=> $job_id, 'user_id' => $user_id));
			$job_quote_user_details 				= $this->mongo_db->get('job_quotes_legs');
			
			if(!empty($job_quote_user_details))
				$job_quote_user_details[0]['id']	= isset($job_quote_user_details[0]['_id']) ? strval($job_quote_user_details[0]['_id']) : '';
			
			$all_content['quote_leg_submited']		= $already_quote_leg_submit;
			$all_content['user_details'] 			= $user_details;
			$all_content['job_user_details']		= $job_user_details;
			$all_content['job_details'] 			= (isset($job_details[0])) 	? $job_details[0] 	: $job_details;
			
			$all_content['job_quote_details'] 		= $job_quote_details;
			$all_content['job_leg_details'] 		= $job_leg_details;
			
			$all_content['job_quote_user_details'] 	= (isset($job_quote_user_details[0])) ? $job_quote_user_details[0] : '';
			
			echo json_encode($all_content);
		}
		else
			echo '0';
	}
	
	//Function to get the the messages of this jobs
	function job_messages_details_ajax()
	{
		$job_id 			= $this->input->post('job_id');
		$user_id 			= $this->input->post('user_id');
		$user_timezone		= $this->system_timezone;
		$user_details 		= $job_details = $job_messages_det = $return_data = array();
		
		if(!empty($job_id) && ($job_id > 0))
		{
			if(!empty($user_id) && ($user_id > 0))
			{
				//Getting user details
				$this->mongo_db->where(array('_id' => $user_id));
				$user_all_details 	= $this->mongo_db->get('site_users');
				
				if(!empty($user_all_details))
				{
					//Getting user rating
					$this->mongo_db->where(array('user_id' => strval($user_id), 'status' => "1"));
					$user_rating 		= $this->mongo_db->get('user_rating');
					
					$user_details['id']			= isset($user_all_details[0]['_id']) 			? strval($user_all_details[0]['_id']) : '';
					$user_details['name']		= isset($user_all_details[0]['first_name']) 	? ucwords($user_all_details[0]['first_name'].' '.$user_all_details[0]['last_name']) : '';
					$user_details['user_image']	= isset($user_all_details[0]['profile_image']) 	? assets_url().'uploads/user_images/thumb/'.$user_all_details[0]['profile_image'] : '';
					$user_details['rating']		= (isset($user_rating[0]['user_rating'])) 		? number_format($user_rating[0]['user_rating'], 1) : '';
					
					//Getting the database timezone of the user at the time of register
					$user_timezone				= (isset($user_all_details[0]['user_timezone']) && !empty($user_all_details[0]['user_timezone'])) ? $user_all_details[0]['user_timezone'] : $this->system_timezone;
					//Updating the time with user's current system time zone
					$user_timezone				= ($this->session->userdata('user_timezone')) ? $this->session->userdata('user_timezone') : $user_timezone;
				}
			}
			
			
			//Job setails
			$this->mongo_db->where(array('_id' => $job_id));
			$job_details 	= $this->mongo_db->get('jobs');
			
			if(!empty($job_details))
			{
				$job_details[0]['_id'] 			= strval($job_details[0]['_id']);
				$job_details[0]['id'] 			= strval($job_details[0]['_id']);
				$job_user_id 					= (isset($job_details[0]['user_id'])) ? $job_details[0]['user_id'] : '';
				$juser_details					= array();
				
				//Getting user details
				if($job_user_id)
				{
					$this->mongo_db->where(array('_id' => $job_user_id));
					$job_user_details 	= $this->mongo_db->get('site_users');
					
					if(!empty($job_user_details))
					{
						//Getting user rating
						$this->mongo_db->where(array('user_id' => strval($job_user_id), 'status' => "1"));
						$job_user_rating 		= $this->mongo_db->get('user_rating');
						
						$juser_details['id']		= isset($job_user_details[0]['_id']) 			? strval($job_user_details[0]['_id']) : '';
						$juser_details['name']		= isset($job_user_details[0]['first_name']) 		? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
						$juser_details['user_image']	= isset($job_user_details[0]['profile_image']) 	? assets_url().'uploads/user_images/thumb/'.$job_user_details[0]['profile_image'] : '';
						$juser_details['rating']		= (isset($job_user_rating[0]['user_rating'])) 	? number_format($job_user_rating[0]['user_rating'], 1) : '';
					}
				}
				
				$job_details[0]['user_details'] 	= $juser_details;
				
				$job_details[0]['formated_date'] = $job_details[0]['size_details'] = $job_details[0]['type_details'] = $job_details[0]['special_details'] = '';
				
				$size_type 	= (isset($job_details[0]['size_type']) && ($job_details[0]['size_type'] != 'SIZE')) ? $job_details[0]['size_type'] 	: '0';
				$job_type 	= (isset($job_details[0]['type']) && ($job_details[0]['size_type'] != 'TYPE')) 	? $job_details[0]['type'] 		: '0';
				$job_special 	= (isset($job_details[0]['special']) && ($job_details[0]['size_type'] != 'SPECIAL')) 	? $job_details[0]['special'] 		: '0';
				
				$job_details[0]['formated_date'] = (isset($job_details[0]['delivery_date'])) ? date('dS M Y', strtotime($job_details[0]['delivery_date'])) : $job_details[0]['delivery_date'];
				
				//size details
				if(!empty($size_type))
				{
					$this->mongo_db->where(array('_id' => strval($size_type), 'status' => "1"));
					$size_details 	= $this->mongo_db->get('sizes');
					$job_details[0]['size_details'] = (isset($size_details[0]['title'])) ? $size_details[0]['title'] : '';
				}
				
				//type details
				if(!empty($job_type))
				{
					$this->mongo_db->where(array('_id' => strval($job_type), 'status' => "1"));
					$type_details 	= $this->mongo_db->get('types');
					$job_details[0]['type_details'] = (isset($type_details[0]['title'])) ? $type_details[0]['title'] : '';
				}
				
				//special details
				if(!empty($job_special))
				{
					$this->mongo_db->where(array('_id' => strval($job_special), 'status' => "1"));
					$special_details 	= $this->mongo_db->get('special');
					$job_details[0]['special_details'] = (isset($special_details[0]['title'])) ? $special_details[0]['title'] : '';
				}
			}
			
			
			//All Job messages
			$this->mongo_db->where(array('job_id' => $job_id));
			$job_messages 	= $this->mongo_db->get('job_messages');
			
			if(!empty($job_messages))
			{
				foreach($job_messages as $m => $message)
				{
					//Get message user details
					if(isset($message['user_id']) && !empty($message['user_id']))
					{
						$this->mongo_db->where(array('_id' => $message['user_id']));
						$user_details_arr 	= $this->mongo_db->get('site_users');
						$user_details		= (isset($user_details_arr[0])) ? $user_details_arr[0] : array();
						if((isset($user_details['_id']))) 	$user_details['_id']=  strval($user_details['_id']);
					}
					else $user_details 	= array();
					
					$job_user_timezone		= (isset($user_details['user_timezone']) && !empty($user_details['user_timezone'])) ? $user_details['user_timezone'] : $this->system_timezone;
					
					$message_date 		= (isset($message['added_on'])) ? $message['added_on'] : '';
					$message_time 		= (isset($message['added_on'])) ? $message['added_on'] : '';
					
					if(!empty($message_date) && ($user_timezone != $this->system_timezone))
						$message_date	= $this->converToTz($message_date, $user_timezone, $this->system_timezone, 'dS M Y');
					
					if(!empty($message_time) && ($user_timezone != $this->system_timezone))
						$message_time	= $this->converToTz($message_time, $user_timezone, $this->system_timezone, 'h:i a');
					else
						$message_time	= date('h:i a', strtotime($message_time));
					
					$job_messages_det[$m]['message'] 				= $message;
					$job_messages_det[$m]['message']['_id'] 		= isset($message['_id']) ? strval($message['_id']) : '';
					$job_messages_det[$m]['message']['message'] 		= isset($message['message']) ? ucfirst($message['message']) : '';
					$job_messages_det[$m]['message']['formated_date'] = $message_date;
					$job_messages_det[$m]['message']['formated_time'] = $message_time;
					$job_messages_det[$m]['message']['user_timezone'] = $user_timezone;
					$job_messages_det[$m]['message']['system_timezone'] = $this->system_timezone;
					
					$job_messages_det[$m]['user_details'] 	= $user_details;
				}
			}
			
			$return_data['job_details'] 		= isset($job_details[0]) ? $job_details[0] 	: array();
			$return_data['user_details'] 		= isset($user_details) 	? $user_details 	: array();
			$return_data['job_messages'] 		= $job_messages_det;
			
			echo json_encode($return_data);
		}
		else
			echo 0;
	}
	
	function submit_job_mesage()
	{
		$job_id 			= $this->input->post('job_id');
		$user_id 			= $this->input->post('user_id');
		$message			= $this->input->post('message');
		$server_time		= date('Y-m-d H:i:s');
		
		if(!empty($message))
		{
			if(!empty($job_id) && (!empty($user_id)))
			{
				//Geting user details
				$this->mongo_db->where(array('_id' => $user_id));
				$user_details_arr 	= $this->mongo_db->get('site_users');
				$user_image 		= (isset($user_details_arr[0]['profile_image']) && !empty($user_details_arr[0]['profile_image'])) ? $user_details_arr[0]['profile_image'] : '';
				
				//Getting the database timezone of the user at the time of register
				$user_timezone		= (isset($user_details_arr[0]['user_timezone']) && !empty($user_details_arr[0]['user_timezone'])) ? $user_details_arr[0]['user_timezone'] : $this->system_timezone;
				//Updating the time with user's current system time zone
				$user_timezone		= ($this->session->userdata('user_timezone')) ? $this->session->userdata('user_timezone') : $user_timezone;
				
				if($user_timezone != $this->system_timezone) $msg_posted_time = $this->converToTz($server_time, $user_timezone, $this->system_timezone, 'h:i a');
				else	$msg_posted_time =  date('h:i a');
				
				$data_to_store['job_id']		= $job_id;
				$data_to_store['user_id']	= $user_id;
				$data_to_store['message']	= $message;
				$data_to_store['added_on']	= date('Y-m-d H:i:s');
				$data_to_store['status']		= '1';
				
				$insert 					= $this->mongo_db->insert('job_messages', $data_to_store);
				$ins_id 					= strval($insert);
				
				$data_send				= array();
				$data_send['status']		= '1';
				$data_send['insert_id']		= $ins_id;
				$data_send['message']		= urlencode(ucfirst($message));
				$data_send['post_time']		= urlencode($msg_posted_time);
				$data_send['user_image']		= $user_image;
					
				if($ins_id) echo json_encode($data_send);
				else echo 0;
			}
			else echo 1;
		}
		else echo 2;
	}
	
	
	//Function to get the all legs and quotes of a jobs
	function job_details_quote_leg_ajax()
	{
		$job_id 			= $this->input->post('job_id');
		$user_id 			= $this->input->post('user_id');
		$only_legs 		= 0;
		$user_details 		= $job_details = $job_quote_leg_det = $user_det = array();
		
		if(!empty($job_id) && ($job_id > 0))
		{
			if(!empty($user_id) && ($user_id > 0))
			{
				//Getting user details
				$this->mongo_db->where(array('_id' => $user_id));
				$user_all_details 	= $this->mongo_db->get('site_users');
				
				if(!empty($user_all_details))
				{
					//Getting user rating
					$this->mongo_db->where(array('user_id' => strval($user_id), 'status' => "1"));
					$user_rating 		= $this->mongo_db->get('user_rating');
					
					$user_details['id']			= isset($user_all_details[0]['_id']) 			? strval($user_all_details[0]['_id']) : '';
					$user_details['name']		= isset($user_all_details[0]['first_name']) 	? ucwords($user_all_details[0]['first_name'].' '.$user_all_details[0]['last_name']) : '';
					$user_details['user_image']	= isset($user_all_details[0]['profile_image']) 	? assets_url().'uploads/user_images/thumb/'.$user_all_details[0]['profile_image'] : '';
					$user_details['rating']		= (isset($user_rating[0]['user_rating'])) 		? number_format($user_rating[0]['user_rating'], 1) : '';
				}
				
				//check if user already submit the quote or leg
				$this->mongo_db->where(array('job_id' => $job_id, 'user_id' => $user_id));
				$already_quote_leg_submit 	= $this->mongo_db->count('job_quotes_legs');
			}
			
			//Job setails
			$this->mongo_db->where(array('_id' => $job_id));
			$job_details 	= $this->mongo_db->get('jobs');
			
			//echo '<pre>'; print_r($job_details); echo '</pre>';
			
			if(!empty($job_details))
			{
				$job_details[0]['id'] 			= strval($job_details[0]['_id']);
				
				$job_user_id 	= (isset($job_details[0]['user_id'])) ? $job_details[0]['user_id'] : 0;
				
				$job_details[0]['formated_date'] = $job_details[0]['size_details'] = $job_details[0]['type_details'] = $job_details[0]['special_details'] = '';
				
				$size_type 	= (isset($job_details[0]['size_type'])) ? $job_details[0]['size_type'] 	: '0';
				$job_type 	= (isset($job_details[0]['type'])) 	? $job_details[0]['type'] 		: '0';
				$job_special 	= (isset($job_details[0]['special'])) 	? $job_details[0]['special'] 		: '0';
				
				$job_details[0]['formated_date'] = (isset($job_details[0]['delivery_date'])) ? date('dS M Y', strtotime($job_details[0]['delivery_date'])) : $job_details[0]['delivery_date'];
				
				//size details
				if(!empty($size_type))
				{
					$this->mongo_db->where(array('_id' => strval($size_type), 'status' => "1"));
					$size_details 	= $this->mongo_db->get('sizes');
					$job_details[0]['size_details'] = (isset($size_details[0]['title'])) ? $size_details[0]['title'] : '';
				}
				
				//type details
				if(!empty($job_type))
				{
					$this->mongo_db->where(array('_id' => strval($job_type), 'status' => "1"));
					$type_details 	= $this->mongo_db->get('types');
					$job_details[0]['type_details'] = (isset($type_details[0]['title'])) ? $type_details[0]['title'] : '';
				}
				
				//special details
				if(!empty($job_special))
				{
					$this->mongo_db->where(array('_id' => strval($job_special), 'status' => "1"));
					$special_details 	= $this->mongo_db->get('special');
					$job_details[0]['special_details'] = (isset($special_details[0]['title'])) ? $special_details[0]['title'] : '';
				}
			}
			
			$job_quote_det			= array('job_quote' => array(), 'job_prices' => array(), 'job_quote_dates' => array(), 'quote_user_det' => array(), 'quote_user_rating' => array()); 
			$job_deliver_dates 		= $job_legs_det = array();
			$job_total_price 		= $current_quote_count = 0;
			
			if($job_details[0]['job_status'] == 2)
			{
				//Find all quotes for this job
				$this->mongo_db->where(array('job_id' => $job_id, 'status' => '1'));
				$job_approve_quote_details 	= $this->mongo_db->get('job_approved_quotes');
				
				if(!empty($job_approve_quote_details))
				{
					$quote_ids 	= isset($job_approve_quote_details[0]['quote_ids']) ? $job_approve_quote_details[0]['quote_ids'] : array();
					$quote_ids_arr = explode(',', $quote_ids);
					
					if(!empty($quote_ids_arr))
					{
						$total_price_final = $total_extra_final = 0;
						$job_pick_up = $job_drop = '';
							
						foreach($quote_ids_arr as $k => $quote_id)
						{
							$this->mongo_db->where(array('_id' => strval(trim($quote_id))));
							$job_quote_res 	= $this->mongo_db->get('job_quotes_legs');
							
							$job_quote		= isset($job_quote_res[0]) ? $job_quote_res[0] : array();
							
							if(!empty($job_quote))
							{
								$job_quote['id'] 					= strval($job_quote['_id']);
								$quote_user_det_id					= $job_quote['user_id'];
								
								$this->mongo_db->where(array('_id' 	=> $quote_user_det_id));
								$user_all_details 					= $this->mongo_db->get('site_users');
								
								$this->mongo_db->where(array('user_id' 	=> strval($quote_user_det_id), 'status' => "1"));
								$user_rating 						= $this->mongo_db->get('user_rating');
								
								if(isset($user_all_details[0]) && !empty($user_all_details[0]))
								{
									$user_det['id']				= strval($user_all_details[0]['_id']);
									$user_det['first_name']			= ucwords($user_all_details[0]['first_name']);
									$user_det['last_name']			= ucwords($user_all_details[0]['last_name']);
									$user_det['profile_image']		= isset($user_all_details[0]['profile_image']) ? $user_all_details[0]['profile_image'] : ''; 
									$user_det['user_rating']			= (isset($user_rating[0]['user_rating'])) 	? number_format($user_rating[0]['user_rating'], 1) 	: '';
								}
								
								$job_price						= isset($job_quote['job_price']) ? $job_quote['job_price'] : 0;
							
								$pick_up							= (isset($job_quote['pickup_date']) && (!empty($job_quote['pickup_date']))) ? date('m/d/Y', strtotime($job_quote['pickup_date'])) : '';
								$drop							= (isset($job_quote['drop_date']) && (!empty($job_quote['pickup_date']))) ? date('m/d/Y', strtotime($job_quote['drop_date'])) 	: '';
								
								$extra_price						= number_format((($job_price * $this->extra_percent_charge) / 100), 2);
								$total_price 						= $job_price + $extra_price;
								
								$job_quote['user_details']			= $user_det;
								$job_quote['quote_dates']			= array('pick_up' => $pick_up, 'drop' => $drop);;
								$job_quote['extra_charge']			= number_format($extra_price, 2);
								$job_quote['job_total_prices']		= number_format($total_price, 2);
								
								$total_price_final 					= $total_price_final + $total_price;
								$total_extra_final 					= $total_extra_final + $extra_price;
								
								if($k == 0) 						$job_pick_up 	= $pick_up;
								if($k == (count($quote_ids_arr) - 1)) 	$job_drop 	= $drop;
								
								$job_quote_det['job_quote'][0][$k] 	= $job_quote;
								$job_quote_det['job_prices_extra'][0] 	= $total_extra_final;
								$job_quote_det['job_total_prices'][0] 	= $total_price_final;
								$job_quote_det['job_date_range'][0] 	= array('pick_up' => $job_pick_up, 'drop' => $job_drop);
							}
						}
					}
				}
			}
			else
			{
				//Find all quotes for this job
				$this->mongo_db->where(array('job_id' => $job_id, 'type' => '1', 'status' => '1'));
				$job_quote_leg_details 	= $this->mongo_db->get('job_quotes_legs');
				
				if(!empty($job_quote_leg_details))
				{
					foreach($job_quote_leg_details as $j => $job_quote)
					{
						$job_quote['id'] 					= strval($job_quote['_id']);
						$quote_user_det_id					= $job_quote['user_id'];
						
						$this->mongo_db->where(array('_id' => $quote_user_det_id));
						$user_all_details 					= $this->mongo_db->get('site_users');
						
						$this->mongo_db->where(array('user_id' => strval($quote_user_det_id), 'status' => "1"));
						$user_rating 						= $this->mongo_db->get('user_rating');
						
						$job_price						= isset($job_quote['job_price']) ? $job_quote['job_price'] : 0;
						$job_prices_extra					= number_format((($job_price * $this->extra_percent_charge) / 100), 2);
						
						if(isset($user_all_details[0]) && !empty($user_all_details[0]))
						{
							$user_det['id']				= strval($user_all_details[0]['_id']);
							$user_det['first_name']			= ucwords($user_all_details[0]['first_name']);
							$user_det['last_name']			= ucwords($user_all_details[0]['last_name']);
							$user_det['profile_image']		= isset($user_all_details[0]['profile_image']) ? $user_all_details[0]['profile_image'] : '';
							$user_det['user_rating']			= (isset($user_rating[0]['user_rating'])) 	? number_format($user_rating[0]['user_rating'], 1) 	: '';
						}
						
						$job_price= isset($job_quote['job_price']) ? $job_quote['job_price'] : 0;
						
						$pick_up	= (isset($job_quote['pickup_date']) && (!empty($job_quote['pickup_date']))) ? date('m/d/Y', strtotime($job_quote['pickup_date'])) : '';
						$drop	= (isset($job_quote['drop_date']) && (!empty($job_quote['pickup_date']))) ? date('m/d/Y', strtotime($job_quote['drop_date'])) 	: '';
						
						$extra_price						= number_format((($job_price * $this->extra_percent_charge) / 100), 2);
						$total_price 						= $job_price + $extra_price;
						
						$job_quote['user_details']			= $user_det;
						$job_quote['quote_dates']			= array('pick_up' => $pick_up, 'drop' => $drop);
						$job_quote['extra_charge']			= $extra_price;
						$job_quote['job_total_prices']		= number_format($total_price, 2);
						
						
						$job_quote_det['job_quote'][$j] 		= $job_quote;
						$job_quote_det['job_prices_extra'][$j]  = $extra_price;
						$job_quote_det['job_total_prices'][$j]  = $total_price;
						$job_quote_det['job_date_range'][$j]  	= array('pick_up' => $pick_up, 'drop' => $drop);
						
						$current_quote_count 				= $j;
					}
				}
				
				//Find all connected legs for this job
				$params 					= array();
				$params['job_id'] 			= $job_id;
				$params['user_id'] 			= $user_id;
				$params['start_point'] 		= trim(strval($job_details[0]['pickup_address']['lat_str']).'_'.strval($job_details[0]['pickup_address']['long_str']));
				$params['end_point'] 		= trim(strval($job_details[0]['drop_address']['lat_str']).'_'.strval($job_details[0]['drop_address']['long_str']));
				$params['call_func']		= 'main';
				$params['call_url']			= main_base_url().'Jobs_controllers/job_legs_filter_arr?job_id='.$job_id.'&user_id='.$user_id;
				
				//echo '<pre>'; print_r($params); 
				
				$all_possible_connected_legs 	= $this->Check_map_lines_model->initialize($params);
				
				//print_r($all_possible_connected_legs); echo '</pre>'; echo 'arijit'; die;
				
				if(!empty($all_possible_connected_legs))
				{
					foreach($all_possible_connected_legs as $k => $each_legs_set)
					{
						$leg_details_info 	= array();
						$is_this_leg_line_valid 	= 0;
						$leg_lines_length 		= count($each_legs_set);
						
						if(!empty($each_legs_set))
						{
							//Now we are checking each connection is valid with our database or not
							foreach($each_legs_set as $leg_line_set)
							{
								$return_data = $this->get_leg_co_ordinate_connection_valid($job_id, $leg_line_set);
								if($return_data > 0) $is_this_leg_line_valid++;
							}
							
							//if total valid paths is equal to the count of line points it is a valid pathe with our db
							if($leg_lines_length == $is_this_leg_line_valid)
							{
								//Now we getting the leg details information
								foreach($each_legs_set as $l 	=> $leg_line_set)
									$leg_details_info[$l] 	= $this->get_leg_co_ordinate_connection_details($job_id, $leg_line_set);
							}
						}
						
						$leg_position = count($job_quote_leg_details)+$k;
						
						if(!empty($leg_details_info))
						{
							$total_price = $total_extra = $extra_price = $job_price = 0;
							$job_pick_up = $job_drop = '';
							foreach($leg_details_info as $i => $info)
							{
								$job_price 	= $info['job_price'];
								$extra_price	= number_format((($job_price * $this->extra_percent_charge) / 100), 2);
								
								$total_price 	= $total_price + ($job_price + $extra_price);
								$total_extra 	= $total_extra + $extra_price;
								
								if($i == 0) 					$job_pick_up 		= $info['quote_dates']['pick_up'];
								if($i == (count($leg_details_info) - 1)) $job_drop 	= $info['quote_dates']['drop'];
								
								$job_quote_det['job_quote'][$leg_position][$i] 		= $info;
								$job_quote_det['job_prices_extra'][$leg_position]   	= $total_extra;
								$job_quote_det['job_total_prices'][$leg_position]  	= $total_price;
								$job_quote_det['job_date_range'][$leg_position]  		= array('pick_up' => $job_pick_up, 'drop' => $job_drop);
							}
						}
					}
				}
			}
			
			//get the job detials of leg
			$this->mongo_db->where(array('job_id' 	=> $job_id, 'user_id' => $user_id));
			$job_quote_user_details 	= $this->mongo_db->get('job_quotes_legs');
			
			$all_content['user_details'] 			= $user_details;
			$all_content['job_details'] 			= (isset($job_details[0])) 	? $job_details[0] 	: $job_details;
			$all_content['job_quote_leg_det'] 		= $job_quote_det;
			$all_content['job_legs_det'] 			= $job_legs_det;
			$all_content['only_legs'] 			= $only_legs;
			
			$all_content['job_quote_user_details'] 	= (isset($job_quote_user_details[0])) ? $job_quote_user_details[0] : array();
			
			echo json_encode($all_content);
		}
		else
			echo '0';
	}
	
	public function get_leg_co_ordinate_connection_valid($job_id, $coordinates)
	{
		//Check if this connection do exist or not. We will always get two co-ordinate one for start and another for end of the line
		$start_coordinate_str 	=  isset($coordinates[0]) ? $coordinates[0] : '';
		$end_coordinate_str 	=  isset($coordinates[1]) ? $coordinates[1] : '';
		
		//Now we need the lat and lng of the each point
		$start_coordinate_arr 	= explode('_', $start_coordinate_str);
		$end_coordinate_arr 	= explode('_', $end_coordinate_str);
		
		$start_point_lat 		= (isset($start_coordinate_arr[0])) 	? strval(trim($start_coordinate_arr[0])) : '';
		$start_point_long 		= (isset($start_coordinate_arr[1])) 	? strval(trim($start_coordinate_arr[1])) : '';
		
		$end_point_lat 		= (isset($end_coordinate_arr[0])) 		? strval(trim($end_coordinate_arr[0])) 	: '';
		$end_point_long 		= (isset($end_coordinate_arr[1])) 		? strval(trim($end_coordinate_arr[1])) 	: '';
		
		$this->mongo_db->where(array('job_id' => $job_id, 'type' => '2', 'start_location.lat_str' => $start_point_lat, 'start_location.long_str' => $start_point_long, 'end_location.lat_str' => $end_point_lat, 'end_location.long_str' => $end_point_long));
		$count = $this->mongo_db->count('job_quotes_legs');
		
		return count($count);
	}
	
	public function get_leg_co_ordinate_connection_details($job_id, $coordinates)
	{
		$job_quote_det			= array(); 
		
		//Check if this connection do exist or not. We will always get two co-ordinate one for start and another for end of the line
		$start_coordinate_str 	=  isset($coordinates[0]) ? $coordinates[0] : '';
		$end_coordinate_str 	=  isset($coordinates[1]) ? $coordinates[1] : '';
		
		//Now we need the lat and lng of the each point
		$start_coordinate_arr 	= explode('_', $start_coordinate_str);
		$end_coordinate_arr 	= explode('_', $end_coordinate_str);
		
		$start_point_lat 		= (isset($start_coordinate_arr[0])) 	? strval(trim($start_coordinate_arr[0])) : '';
		$start_point_long 		= (isset($start_coordinate_arr[1])) 	? strval(trim($start_coordinate_arr[1])) : '';
		
		$end_point_lat 		= (isset($end_coordinate_arr[0])) 		? strval(trim($end_coordinate_arr[0])) 	: '';
		$end_point_long 		= (isset($end_coordinate_arr[1])) 		? strval(trim($end_coordinate_arr[1])) 	: '';
		
		$this->mongo_db->where(array('job_id' => $job_id, 'type' => '2', 'start_location.lat_str' => $start_point_lat, 'start_location.long_str' => $start_point_long, 'end_location.lat_str' => $end_point_lat, 'end_location.long_str' => $end_point_long));
		$details 				= $this->mongo_db->get('job_quotes_legs');
		
		$user_det 			= array();
		if(isset($details[0]) && !empty($details[0]))
		{
			$job_quote 						= $details[0];
			
			$job_quote['id'] 					= strval($job_quote['_id']);
			$quote_user_det_id					= $job_quote['user_id'];
			
			$this->mongo_db->where(array('_id' => $quote_user_det_id));
			$user_all_details 					= $this->mongo_db->get('site_users');
			
			$this->mongo_db->where(array('user_id' => strval($quote_user_det_id), 'status' => "1"));
			$user_rating 						= $this->mongo_db->get('user_rating');
			
			if(isset($user_all_details[0]) && !empty($user_all_details[0]))
			{
				$user_det['id']				= strval($user_all_details[0]['_id']);
				$user_det['first_name']			= ucwords($user_all_details[0]['first_name']);
				$user_det['last_name']			= ucwords($user_all_details[0]['last_name']);
				$user_det['profile_image']		= (isset($user_all_details[0]['profile_image'])) ? $user_all_details[0]['profile_image'] : '';
				$user_det['user_rating']			= (isset($user_rating[0]['user_rating'])) 	? number_format($user_rating[0]['user_rating'], 1) 	: '';
			}
			
			$job_price						= isset($job_quote['job_price']) ? $job_quote['job_price'] : 0;
			$job_prices_extra					= number_format((($job_price * $this->extra_percent_charge) / 100), 2);
			
			$job_deliver_dates['pick_up']			= (isset($job_quote['pickup_date']) && (!empty($job_quote['pickup_date']))) ? date('m/d/Y', strtotime($job_quote['pickup_date'])) : '';
			$job_deliver_dates['drop']			= (isset($job_quote['drop_date']) && (!empty($job_quote['pickup_date']))) ? date('m/d/Y', strtotime($job_quote['drop_date'])) 	: '';
			
			$job_quote['user_details']			= $user_det;
			$job_quote['quote_dates']			= $job_deliver_dates;
			$job_quote['extra_charge']			= number_format((($job_price * $this->extra_percent_charge) / 100), 2);
			$job_quote['job_total_prices']		= number_format(($job_price + $job_prices_extra), 2);
			
			$job_quote_det 					= $job_quote;
		}
		
		return $job_quote_det;
	}
	
	public function get_all_points_connected_tothis_point($job_id, $point1, $point2)
	{
		$return_arr = array();
		
		//Find all legs for this job query
		$this->mongo_db->where(array('job_id' => $job_id, 'type' => '2', 'start_location.lat_str' => $point1, 'start_location.long_str' => $point2));
		$job_quote_leg_details 	= $this->mongo_db->get('job_quotes_legs');
		
		if(!empty($job_quote_leg_details))
		{
			foreach($job_quote_leg_details as $l => $leg)
			{
				$return_arr[$l][]		= $leg['end_location']['lat_str'];
				$return_arr[$l][]		= $leg['end_location']['long_str'];
			}
		}
		
		return $return_arr;
	}
	
	public function submit_quote()
	{
		$job_id 			= $this->input->post('quote_job_id');
		$job_price 		= $this->input->post('job_price');
		$user_id 			= $this->input->post('quote_user_id');
		$submit_type 		= $this->input->post('submit_type');
		//$start_location 	= $this->input->post('start_location');
		//$end_location 		= $this->input->post('end_location');
		
		$settings 	= $this->sitesetting_model->get_settings();
		$sitename 	= (isset($settings[0]['site_name'])) ? $settings[0]['site_name'] : '';
		
		$this->mongo_db->where(array('job_id' => $job_id, 'user_id' => $user_id));
		$check_already_submit 	= $this->mongo_db->count('job_quotes_legs');
		
		if($check_already_submit == 0)
		{
			//Job setails
			$this->mongo_db->where(array('_id' => $job_id));
			$job_details 	= $this->mongo_db->get('jobs');
			
			//Bid posted user setails
			$this->mongo_db->where(array('_id' => $user_id));
			$job_postuser_details 	= $this->mongo_db->get('site_users');
			
			$job_postuser_name 		= (isset($job_postuser_details[0])) ? ucwords($job_postuser_details[0]['first_name'].' '.$job_postuser_details[0]['last_name']) : '';
			
			if(!empty($job_details))
			{
				$data_to_store['job_id']				= $job_id;
				$data_to_store['user_id']			= $user_id;
				$data_to_store['type']				= ($submit_type) ? $submit_type : '1';
				$data_to_store['job_price']			= (float)$job_price;
				$data_to_store['start_location']		= (isset($job_details[0]['pickup_address'])) ? $job_details[0]['pickup_address'] : (object)array();
				$data_to_store['end_location']		= (isset($job_details[0]['drop_address'])) 	? $job_details[0]['drop_address'] : (object)array();
				$data_to_store['added_on']			= date('Y-m-d H:i:s');
				$data_to_store['system_timezone']		= $this->system_timezone;
				$data_to_store['request_status']		= '0';
				$data_to_store['status']				= '1';
				
				$insert 	= $this->mongo_db->insert('job_quotes_legs', $data_to_store);
				
				if($insert){
					
					$job_user_id 	= $job_details[0]['user_id'];
					
					$this->mongo_db->where(array('_id' => $job_user_id));
					$job_user_details 	= $this->mongo_db->get('site_users');
					
					$user_email_id 	= isset($job_user_details[0]['email']) ? $job_user_details[0]['email'] : '';
					$user_mcountry_code = isset($job_user_details[0]['phone_code']) ? $job_user_details[0]['phone_code'] : '1';
					$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
					$to_name 			= isset($job_user_details[0]['first_name']) ? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
					
					//Getting the user settings
					$this->mongo_db->where(array('user_id' => $job_user_id));
					$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
					
					if(!empty($job_user_notification_settings[0]))
					{
						$check_for_email 	= (isset($job_user_notification_settings[0]['quote_submit_jobs_notification']['email'])) ? $job_user_notification_settings[0]['quote_submit_jobs_notification']['email'] : 0;
						
						$check_for_sms 	= (isset($job_user_notification_settings[0]['quote_submit_jobs_notification']['sms'])) ? $job_user_notification_settings[0]['quote_submit_jobs_notification']['sms'] : 0;
						
						$this->mongo_db->where(array('email_title' => 'job_quote_submit'));
						$email_temp_arr 	= $this->mongo_db->get('email_templates');
						$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
						
						//Check for email settings 
						if(!empty($email_temp) && $check_for_email)
						{
							$search 		= array('[SITE_LOGO]', '[NAME]', '[QUOTE_AMOUNT]', '[SITE_NAME]');
							$replace 		= array(assets_url().'site/images/logo.png', $to_name, '$'.$job_price, $sitename);
							
							$email_temp_msg= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
							$email_temp_msg= str_replace($search, $replace, $email_temp_msg);
							
							$email_temp_sub= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
							
							if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
							
						}
						
						
						//Check for sms settings
						$this->mongo_db->where(array('sms_title' => 'job_quote_submit'));
						$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
						$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
						
						
						if($user_mobile_no && $check_for_sms && !empty($sms_temp))
						{
							$search_sms 		= array('[USER_NAME]', '[PRICE]');
							$replace_sms 		= array($job_postuser_name, '$'.$job_price);
							
							$sms_temp_msg= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
							$sms_temp_msg= str_replace($search_sms, $replace_sms, $sms_temp_msg);

							$params['mobile_nos_to_send']	= array('+'.$user_mcountry_code.$user_mobile_no);
							$params['sms_messaages']		= array($sms_temp_msg);
							
							if($user_mobile_no)	$this->User_notifications_model->initialize($params);
						}
					}
					
					echo 1;  //success job quoted
				}
				else
					echo 0;  //failed to job quoted
			}
			else
				echo 3;  //job does not exist
		}
		else
			echo 2;  //job already quoted
	}
	
	public function submit_leg()
	{
		$job_id 			= $this->input->post('leg_job_id');
		$job_price 		= $this->input->post('job_leg_price');
		$user_id 			= $this->input->post('leg_user_id');
		$submit_type 		= $this->input->post('submit_type');
		
		$start_location 	= $this->input->post('leg_pickup_addr');
		$start_location_lat = $this->input->post('leg_pickup_addr_lat');
		$start_location_lng = $this->input->post('leg_pickup_addr_long');
		
		$end_location 		= $this->input->post('leg_drop_addr');
		$end_location_lat 	= $this->input->post('leg_drop_addr_lat');
		$end_location_lng 	= $this->input->post('leg_drop_addr_long');
		
		$leg_start 		= $this->input->post('leg_start');
		$leg_end 			= $this->input->post('leg_end');
		
		$this->mongo_db->where(array('job_id' => $job_id, 'user_id' => $user_id));
		$check_already_submit 	= $this->mongo_db->count('job_quotes_legs');
		
		$settings 	= $this->sitesetting_model->get_settings();
		$sitename 	= (isset($settings[0]['site_name'])) ? $settings[0]['site_name'] : '';
		
		if($check_already_submit == 0)
		{
			//Job setails
			$this->mongo_db->where(array('_id' => $job_id));
			$job_details 	= $this->mongo_db->get('jobs');
			
			//Bid posted user setails
			$this->mongo_db->where(array('_id' => $user_id));
			$job_postuser_details 	= $this->mongo_db->get('site_users');
			
			$job_postuser_name 		= (isset($job_postuser_details[0])) ? ucwords($job_postuser_details[0]['first_name'].' '.$job_postuser_details[0]['last_name']) : '';
			
			$opts = array('http' =>
				array(
				    'method'  => 'GET',
				    'timeout' => 120 
				)
			);
			
			$context  = stream_context_create($opts);
		
			$start_country_data 	= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$start_location_lat.'&lng='.$start_location_lng.'&username=arijit2016&type=JSON', false, $context));
			$start_country_data_arr 	= json_decode($start_country_data);
			$start_country_name 	= isset($start_country_data_arr->countryName) ? $start_country_data_arr->countryName : '';
			$start_country_code 	= isset($start_country_data_arr->countryCode) ? $start_country_data_arr->countryCode : '';
			
			$end_country_data 		= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$end_location_lat.'&lng='.$end_location_lng.'&username=arijit2016&type=JSON', false, $context));
			$end_country_data_arr 	= json_decode($end_country_data);
			$end_country_name 		= isset($end_country_data_arr->countryName) ? $end_country_data_arr->countryName : '';
			$end_country_code 		= isset($end_country_data_arr->countryCode) ? $end_country_data_arr->countryCode : '';
			
			
			if(!empty($job_details))
			{
				$data_to_store['job_id']				= $job_id;
				$data_to_store['user_id']			= $user_id;
				$data_to_store['type']				= ($submit_type) ? $submit_type : '1';
				$data_to_store['job_price']			= (float)$job_price;
				$data_to_store['pickup_date']			= date('Y-m-d', strtotime($leg_start));
				$data_to_store['drop_date']			= date('Y-m-d', strtotime($leg_end));
				
				$data_to_store['start_location']		= array('address' => $start_location, 'lat' => (float)$start_location_lat, 'lat_str' => strval($start_location_lat), 'long' => (float)$start_location_lng, 'long_str' => strval($start_location_lng), 'country' => $start_country_name, 'country_code' => $start_country_code);
				
				$data_to_store['end_location']		= array('address' => $end_location, 'lat' => (float)$end_location_lat,'lat_str' => strval($end_location_lat), 'long' => (float)$end_location_lng, 'long_str' => strval($end_location_lng), 'country' => $end_country_name, 'country_code' => $end_country_code);
				
				$data_to_store['added_on']			= date('Y-m-d H:i:s');
				$data_to_store['system_timezone']		= $this->system_timezone;
				$data_to_store['request_status']		= '0';
				$data_to_store['status']				= '1';
				
				//echo '<pre>'; print_r($data_to_store); die;
				
				$insert 	= $this->mongo_db->insert('job_quotes_legs', $data_to_store);
				
				if($insert){
					
					$job_user_id 	= $job_details[0]['user_id'];
					
					$this->mongo_db->where(array('_id' => $job_user_id));
					$job_user_details 	= $this->mongo_db->get('site_users');
					
					$user_email_id 	= isset($job_user_details[0]['email']) ? $job_user_details[0]['email'] : '';
					$user_mcountry_code = isset($job_user_details[0]['phone_code']) ? $job_user_details[0]['phone_code'] : '1';
					$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
					$to_name 			= isset($job_user_details[0]['first_name']) ? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
					//Getting the user settings
					$this->mongo_db->where(array('user_id' => $job_user_id));
					$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
					
					if(!empty($job_user_notification_settings[0]))
					{
						$check_for_email 	= (isset($job_user_notification_settings[0]['quote_submit_jobs_notification']['email'])) ? $job_user_notification_settings[0]['quote_submit_jobs_notification']['email'] : 0;
						
						$check_for_sms 	= (isset($job_user_notification_settings[0]['quote_submit_jobs_notification']['sms'])) ? $job_user_notification_settings[0]['quote_submit_jobs_notification']['sms'] : 0;
						
						if($check_for_email)
						{
							$this->mongo_db->where(array('email_title' => 'job_leg_submit'));
							$email_temp_arr 	= $this->mongo_db->get('email_templates');
							$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
							
							if(!empty($email_temp))
							{
								$search 		= array('[SITE_LOGO]', '[NAME]', '[START_LOCATION]', '[END_LOCATION]', '[LEG_AMOUNT]', '[SITE_NAME]');
								$replace 		= array(assets_url().'site/images/logo.png', $to_name, $start_location, $end_location, '$'.$job_price, $sitename);
								
								$email_temp_msg= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
								$email_temp_msg= str_replace($search, $replace, $email_temp_msg);
								
								$email_temp_sub= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
								
								if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
							}
						}
						
						//Check for sms settings
						$this->mongo_db->where(array('sms_title' => 'job_leg_submit'));
						$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
						$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
						
						if($user_mobile_no && $check_for_sms && !empty($sms_temp))
						{
							$search_sms 		= array('[USER_NAME]', '[PRICE]');
							$replace_sms 		= array($job_postuser_name, '$'.$job_price);
							
							$sms_temp_msg= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
							$sms_temp_msg= str_replace($search_sms, $replace_sms, $sms_temp_msg);

							$params['mobile_nos_to_send']	= array('+'.$user_mcountry_code.$user_mobile_no);
							$params['sms_messaages']		= array($sms_temp_msg);
							if($user_mobile_no)	$this->User_notifications_model->initialize($params);
						}
						
					}
					
					echo 1;  //success job quoted
				}
				else
					echo 0;  //failed to job quoted
			}
			else
				echo 3;  //job does not exist
		}
		else
			echo 2;  //job already quoted
	}
	
	public function check_line_cross()
	{
		echo $this->Check_map_lines_model->main();
	}
	
	
	public function job_legs_filter_arr()
	{
		$job_id 				= $this->input->get('job_id');
		$user_id 				= $this->input->get('user_id');
		
		//Job setails
		$this->mongo_db->where(array('_id' => $job_id));
		$job_details 	= $this->mongo_db->get('jobs');
		
		if(!empty($job_details))
		{
			//Find all legs for this job query
			$this->mongo_db->where(array('job_id' => $job_id, 'type' => '2'));
			$this->mongo_db->order_by(array('_id' => 'desc'));
			$job_quote_leg_details 	= $this->mongo_db->get('job_quotes_legs');
			
			//Get all job points
			$job_start_points 	= $job_points = $visited = array();
			
			//Assign job pickup addr as first
			//$job_points[0][0]	= trim($job_details[0]['pickup_address']['lat']);
			//$job_points[0][1]	= trim($job_details[0]['pickup_address']['long']);
			
			//assign job legs addr in middle
			if(!empty($job_quote_leg_details))
			{
				foreach($job_quote_leg_details as $l=>$leg_point)
				{
					$point_dets 			= array();
					
					$leg_point_start_lat 	= isset($leg_point['start_location']['lat_str']) 	? strval($leg_point['start_location']['lat_str']) 	: '';
					$leg_point_start_long 	= isset($leg_point['start_location']['long_str']) ? strval($leg_point['start_location']['long_str']) 	: '';
					
					$this_point 			= trim($leg_point_start_lat.'_'.$leg_point_start_long);
					
					if(!empty($leg_point_start_lat) && !empty($leg_point_start_long))
						$point_dets = $this->get_all_points_connected_tothis_point($job_id, $leg_point_start_lat, $leg_point_start_long);
					
					$count 	= count($job_points);
					
					if(!in_array($this_point, $visited))
					{
						$job_points[$count]['check_pos'] 		= trim($leg_point_start_lat.'_'.$leg_point_start_long);
						
						if(!empty($point_dets))
						{
							foreach($point_dets as $e => $end_points)
							{
								$job_points[$count]['cpoints'][$e][0] 		= trim($leg_point_start_lat.'_'.$leg_point_start_long);
								$job_points[$count]['cpoints'][$e][1] 		= trim($end_points[0].'_'.$end_points[1]);
							}
						}
						
						$visited[] 			= $this_point;
					}	
				}
			}
			//Assign job drop addr as last
			$job_points[count($job_points)]['check_pos']		= $visited[count($visited)] = trim(strval($job_details[0]['drop_address']['lat_str']).'_'.strval($job_details[0]['drop_address']['long_str']));
			$job_points[count($job_points)]['cpoints']		= array();
			
			$graph_points 			= array();
			if(!empty($job_points))
			{
				foreach($job_points as $k=>$point)
				{
					if(!empty($point['cpoints']))
					{
						foreach($point['cpoints'] as $cpoint)
						{
							$graph_points[]	= array($cpoint[0], $cpoint[1]);
						}
					}
				}
			}
			
			//Find all connected legs for this job
			$params 					= array();
			$params['job_id'] 			= $job_id;
			$params['user_id'] 			= $user_id;
			$params['start_point'] 		= trim(strval($job_details[0]['pickup_address']['lat_str']).'_'.strval($job_details[0]['pickup_address']['long_str']));
			$params['end_point'] 		= trim(strval($job_details[0]['drop_address']['lat_str']).'_'.strval($job_details[0]['drop_address']['long_str']));
			$params['indivisual_points'] 	= $visited;
			$params['connected_points'] 	= $graph_points;
			$params['call_func']		= 'generate_graph_arr';
			
			$all_connected_legs 		= $this->Check_map_lines_model->initialize($params);
			
			echo $all_connected_legs;
		}
		else
			echo json_encode(array());
	}
		
		
	function discard_job_quote()
	{
		$job_id 		= $this->input->post('job_id');
		$job_quote_id 	= $this->input->post('job_quote_id');
		$user_id 		= $this->input->post('user_id');
		
		$this->mongo_db->where(array('_id' => $job_quote_id));
		$delete_leg 	= $this->mongo_db->delete('job_quotes_legs');
		
		if($delete_leg) echo 1;
		else  echo 0;
	}

	
	public function check_email()
	{
		$user_email_id 	= 'arijit.modak@esolzmail.com';
		$message_subject 	= 'wad awd wad wad aw adwa wd aw aw daw d';
		$message_content 	= 'wad awd wad wad awa wdaw d ad aw';
		$to_name 			= 'wada wd awdawd aw awd awd aw daw a';
		
		$this->User_email_model->send_email($user_email_id, $message_subject, $message_content, '', '', '', $to_name);
	}

	
	public function all_jobs()
	{
		$this->data['settings'] 		= $this->sitesetting_model->get_settings();
		$site_name 					= (isset($this->data['settings'][0]['site_name'])) ? $this->data['settings'][0]['site_name']  : '';
		
		if(!empty($this->cmp_details))
			$is_my_job		= trim($this->uri->segment(2));
		else
			$is_my_job		= trim($this->uri->segment(1));
			
		$this->data['is_my_job']		=	$is_my_job;
		
		$user_id 		= $this->session->userdata('site_user_objId_hotcargo');
		
		//Getting user details
		$this->mongo_db->where(array('_id' => $user_id));
		$myaccount_data 				= $this->mongo_db->get('site_users');
		$this->data['myaccount_data']	= (isset($myaccount_data[0])) ? $myaccount_data[0] : $myaccount_data;
		
		$this->mongo_db->where(array('user_id' => $user_id, 'status' => '1'));
		$users_stripe_details 			= $this->mongo_db->get('users_stripe_details');
		$this->data['user_stripe_data']	= (isset($users_stripe_details[0])) ? $users_stripe_details[0] : array();
		
		//Get terms contents
		$this->mongo_db->where(array('page_alias' => 'terms-conditions'));
		$static_contents 		= $this->mongo_db->get('static_contents');
		
		$this->data['terms_conditions']	= (isset($static_contents[0]['page_content'])) ? $static_contents[0]['page_content'] : '';
		
		$perPage 		= $this->limit_page;
		$cpage 		= ($this->input->post('current_page')) ? $this->input->post('current_page') : 1;

		$offset 	= ($cpage > 1) ? ($cpage * $perPage) - $perPage: 0;
		//$offset 	= ($cpage-1)*$perPage;
		
		//For Checking merchant wise jobs
		$cmp_user_ids = array();
		if(!empty($this->cmp_auth_id))
		{
				//$where_arr['merchant_id']	= $this->cmp_auth_id;
				
				//Get all users of this company
				$this->mongo_db->where(array('merchant_id' => $this->cmp_auth_id));
				$all_cmp_users_arr 			= $this->mongo_db->get('site_users');
				if(!empty($all_cmp_users_arr)) {
					foreach($all_cmp_users_arr as $cmp) $cmp_user_ids[] = (isset($cmp['_id'])) ? strval($cmp['_id']) : '';
					
					if(in_array($user_id,$cmp_user_ids))
					{
						if(($key = array_search($user_id, $cmp_user_ids)) !== false) {
							unset($cmp_user_ids[$key]);
						}
						//Getting all jobs of this company users
						$cmp_user_ids = array_values($cmp_user_ids);
					}
					
				}
		}
		//to check the condition for my job or all jobs
		$job_condition_arr	= ($is_my_job == 'my-jobs') ? array('user_id'=>$user_id) : array('status'=>'1');
		
		$ajax_job_data	= $all_job_data =  array();
		//for load more datas
		if($this->input->server('REQUEST_METHOD') === 'POST')
		{
			//$cpage 			= ($this->input->post('current_page')) ? $this->input->post('current_page') : 1;
			//$offset 			= ($cpage > 1) ? $cpage * $perPage : 0;
			
			
			$rating_value 		= $this->input->post('rating_val');
			$distance_value 	= $this->input->post('distance_val');
			
			$distance_arr['distance'] 	= ($distance_value == 'long') ? 'desc' : 'asc';
			
			//echo $offset;
			
			//echo '<pre>';
			//print_r($job_condition_arr);die;
			$this->mongo_db->where($job_condition_arr);
			
			
			if($is_my_job != 'my-jobs')
			{
				$this->mongo_db->where_ne('user_id',$user_id);
				//Getting all jobs of this company users
				if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
			}
			
			$this->mongo_db->limit($perPage);
			$this->mongo_db->offset($offset);
			//echo $offset.$perPage;die;
			if($distance_value!='')
				$this->mongo_db->order_by($distance_arr);
			else
				$this->mongo_db->order_by(array('delivery_date' => 'desc'));
			
			
			$data['all_job_details'] 	= $this->mongo_db->get('jobs');
			$data['total_jobs']    		= count($data['all_job_details']);
			
			//echo "off".$offset."per".$perPage;
			//echo "<pre>";
			//print_r($data['all_job_details']);die;
			if(isset($data['all_job_details']) && count($data['all_job_details'])>0)
			{
				foreach($data['all_job_details'] as $key => $result)
				{
					
					$is_partof_job	= '0';
					$job_id    	= strval($result['_id']);
					$client_id 	= (isset($result['user_id'])) ? $result['user_id'] : 0;
					$job_status = (isset($result['job_status'])) ? $result['job_status'] : 0;
					
					//to get client details
					$this->mongo_db->where(array('_id' => $client_id));
					$client_details = $this->mongo_db->get('site_users');
					
					//to get client details
					$this->mongo_db->where(array('user_id' => $client_id));
					$user_ratings = $this->mongo_db->get('user_rating');
					
					
					$ajax_job_data[$key]['user_rating']	= isset($user_ratings[0]['user_rating']) ? $user_ratings[0]['user_rating'] : '';
					
					//to get number of bids 
					if($job_status == '2')
					{
						$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1','request_status' => '1'));
					}
					else
					{
						$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1'));
					}
					$bids_details = $this->mongo_db->get('job_quotes_legs');
					$total_bids   = count($bids_details);
					
					//Get logged in user quote count
					$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1','user_id' 	=> $user_id));
					$user_job_details = $this->mongo_db->get('job_quotes_legs');
					$user_total_bids   = count($user_job_details);
					
					
					
					$client_f_name							= (isset($client_details[0]['first_name'])) ?trim($client_details[0]['first_name']) : '';
					$client_l_name							= (isset($client_details[0]['last_name'])) ?trim($client_details[0]['last_name']) : '';
					
					$ajax_job_data[$key]['client_name']   		= (strlen($client_f_name." ".$client_l_name)>50) ? ucfirst(mb_substr($client_f_name." ".$client_l_name, 0, 50, 'UTF-8'))."..." : ucfirst($client_f_name." ".$client_l_name);
					
					$ajax_job_data[$key]['profile_image'] 		= (isset($client_details[0]['profile_image'])) ? main_base_url().'thumb.php?height=70&width=70&type=aspectratio&img='.assets_url().'uploads/user_images/thumb/'.$client_details[0]['profile_image'] : main_base_url().'thumb.php?height=70&width=70&type=aspectratio&img='.assets_url().'site/images/user-image.png';
					
					$job_description_db						= (isset($result['description'])) ?trim($result['description']) : '';
					
					$ajax_job_data[$key]['job_description']   	= (strlen($job_description_db) > 500) ? ucfirst(mb_substr($job_description_db, 0, 500, 'UTF-8'))."..." : ucfirst($job_description_db);
					$pick_up_address_db						= (isset($result['pickup_address']['address'])) ?trim($result['pickup_address']['address']) : '';
					
					$ajax_job_data[$key]['pick_up_address']   	= (strlen($pick_up_address_db)>100) ? ucfirst(mb_substr($pick_up_address_db,0,100, 'UTF-8'))."..." : ucfirst($pick_up_address_db);
					
					$drop_address_db						= (isset($result['drop_address']['address'])) ? trim($result['drop_address']['address']) : '';
					
					$ajax_job_data[$key]['drop_address']   		= (strlen($drop_address_db)>100) ? ucfirst(mb_substr($drop_address_db, 0, 100, 'UTF-8'))."..." : ucfirst($drop_address_db);
					$ajax_job_data[$key]['job_distance'] 		= (isset($result['distance'])) ? number_format($result['distance'],2,'.','') : '0';
					
					$ajax_job_data[$key]['job_distance_unit'] 	= (isset($result['distance_type'])) ? $result['distance_type'] : '';
					$ajax_job_data[$key]['delivery_date'] 		= (isset($result['delivery_date']) && $result['delivery_date']!='') ? date("j F Y",strtotime($result['delivery_date'])) : '';
					$ajax_job_data[$key]['height'] 			= (isset($result['size']['height'])) ?$result['size']['height'] : '0';
					$ajax_job_data[$key]['width'] 			= (isset($result['size']['width']))  ?$result['size']['width'] : '0';
					$ajax_job_data[$key]['depth']				= (isset($result['size']['depth']))  ?$result['size']['depth'] : '0';
					$ajax_job_data[$key]['weight']			= (isset($result['weight']))  	?$result['weight'] : '0';
					$ajax_job_data[$key]['total_bids'] 		= $total_bids;
					$ajax_job_data[$key]['user_quote_count'] 	= $user_total_bids;
					$ajax_job_data[$key]['job_id']			= $job_id;
					
					//checking if this user is already part of this job or not after this job approved
					if($is_my_job != 'my-jobs')
					{
					if($job_status == '2')
						{
							$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1','request_status' => '1','user_id'=>$user_id));
							$is_partof_job = $this->mongo_db->count('job_quotes_legs');
						}
					}
					else
					{
						$is_partof_job = '1';
					}
					
					$ajax_job_data[$key]['curr_job_status']	= $job_status;
					$ajax_job_data[$key]['is_part_of_job']	= $is_partof_job;
				}
			}
			
			
			$this->mongo_db->where($job_condition_arr);
			
			if($is_my_job != 'my-jobs')
			{
				$this->mongo_db->where_ne('user_id',$user_id);
				//Getting all jobs of this company users
				if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
			}
			$this->mongo_db->limit($perPage);
			$this->mongo_db->offset($offset+$perPage);
			//echo $offset.$perPage;die;
			if($distance_value!='')
				$this->mongo_db->order_by($distance_arr);
			else
				$this->mongo_db->order_by(array('delivery_date' => 'desc'));
			
			$has_next_list					=	$this->mongo_db->get('jobs');
			$final_data['has_next_list'] 	= count($has_next_list);
			$final_data['results']			= $ajax_job_data;
			
			echo json_encode($final_data); die;
		}
		
		//for total jobs count in the view page
		$this->mongo_db->where($job_condition_arr);
		
		
		if($is_my_job != 'my-jobs')
		{
			$this->mongo_db->where_ne('user_id',$user_id);
			//Getting all jobs of this company users
			if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
		}
		
		//$data['all_jobs_by_count'] 	= $this->mongo_db->count('jobs');
		$data['total_jobs_count']    	= $this->mongo_db->count('jobs');
		
		
		$this->mongo_db->where($job_condition_arr);
		
		if($is_my_job != 'my-jobs')
		{
			$this->mongo_db->where_ne('user_id',$user_id);
			//Getting all jobs of this company users
			if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
		}	
		$this->mongo_db->limit($perPage);
		$this->mongo_db->offset($offset);
		
		$this->mongo_db->order_by(array('delivery_date' => 'desc'));
		
		$data['all_job_details'] 	= $this->mongo_db->get('jobs');
		$data['total_jobs']    		= count($data['all_job_details']);
		
		
		if($data['total_jobs'] >0)
		{
			foreach($data['all_job_details'] as $key => $result)
			{
				$is_partof_job	= '0';
				$job_id    	= strval($result['_id']);
				$client_id 	= (isset($result['user_id'])) ? $result['user_id'] : 0;
				$job_status = (isset($result['job_status'])) ? $result['job_status'] : 0;
				//to get client details
				$this->mongo_db->where(array('_id' => $client_id));
				$client_details = $this->mongo_db->get('site_users');
				
				//to get client details
				$this->mongo_db->where(array('user_id' => $client_id));
				$user_ratings = $this->mongo_db->get('user_rating');
				
				$data['all_job_details'][$key]['user_details']				= isset($client_details[0]) ? $client_details[0] : array();
				$data['all_job_details'][$key]['user_details']['user_rating']	= isset($user_ratings[0]['user_rating']) ? $user_ratings[0]['user_rating'] : '0';
				
				//to get number of bids
				if($job_status == '2')
				{
					$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1','request_status' => '1'));
				}
				else
				{
					$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1'));
				}
				
				$bids_details = $this->mongo_db->get('job_quotes_legs');
				$total_bids   = count($bids_details);
				
				//checking if this user is already part of this job or not after this job approved
				if($is_my_job != 'my-jobs')
				{
				if($job_status == '2')
					{
						$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1','request_status' => '1','user_id'=>$user_id));
						$is_partof_job = $this->mongo_db->count('job_quotes_legs');
					}
				}
				else
				{
					$is_partof_job = '1';
				}
				
				
				
				//Get logged in user quote count
				$this->mongo_db->where(array('job_id' 	=> $job_id, 'status' => '1','user_id' 	=> $user_id));
				$user_job_details = $this->mongo_db->get('job_quotes_legs');
				$user_total_bids   = count($user_job_details);
				
				$data['all_job_details'][$key]['quote_details']	= isset($bids_details) ? $bids_details : array();
				$data['all_job_details'][$key]['quote_count']	= $total_bids;
				$data['all_job_details'][$key]['user_quote_count']	= $user_total_bids;
				$data['all_job_details'][$key]['curr_job_status']	= $job_status;
				$data['all_job_details'][$key]['is_part_of_job']	= $is_partof_job;
			}
		}
			//echo "<pre>";
			//print_r($data);die;
		if($is_my_job != 'my-jobs')
		{
			$this->mongo_db->where($job_condition_arr);
			
			//Getting all jobs of this company users
			if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
			
			$this->mongo_db->where_ne('user_id',$user_id);
			$all_jobs_json	= $this->mongo_db->get('jobs');
			foreach($all_jobs_json as $json_jobs)
			{
				
				$all_job_id = strval($json_jobs['_id']);
				//$all_job_data[$all_job_id] 		= $json_jobs;
				$all_job_data[$all_job_id]['id'] 	= strval($json_jobs['_id']);
				
				//get all jobs details json at first time when page loads so that the data can transfer in js for submit quote and submit leg pop up
				
				$is_job_approved 	= $job_has_updated_loc = 0;
				$delivery_address 	= $pickup_address = $home_address = array('address' => '', 'lat' => '', 'long' => '');
				$pickup_lat 		= $pickup_long = $drop_lat = $drop_long = $icon = $job_color = $job_status_txt = '';
				$job_info 		= $job_user_data = $job_user_rating = $all_quotes = $all_legs = array();
				
				
				//	//all quotes of this job
					$this->mongo_db->where(array('job_id' => strval($json_jobs['_id']), 'type' => '1'));
					$all_quotes 	= $this->mongo_db->get('job_quotes_legs');
					
					//all legs of this job
					$this->mongo_db->where(array('job_id' => strval($json_jobs['_id']), 'type' => '2'));
					$this->mongo_db->order_by(array('_id' => 'asc'));
					$all_legs 	= $this->mongo_db->get('job_quotes_legs');
				
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
				
				
				//$all_job_data[$all_job_id]['is_own']							= ($json_jobs['user_id'] == $user_id) ? '1' : '0';
				//$all_job_data[$all_job_id]['job_description']					= (isset($json_jobs['description'])) ? $json_jobs['description'] : '';
				//$all_job_data[$all_job_id]['job_image']						= (isset($json_jobs['image'])) ? $json_jobs['image'] : '';
				$all_job_data[$all_job_id]['pickup_address']					= (isset($json_jobs['pickup_address'])) ? $json_jobs['pickup_address'] : '';
				$all_job_data[$all_job_id]['drop_address']					= (isset($json_jobs['drop_address'])) ? $json_jobs['drop_address'] : '';
				$all_job_data[$all_job_id]['distance']						= (isset($json_jobs['distance'])) ? $json_jobs['distance'] : '';
				$all_job_data[$all_job_id]['distance_type']					= (isset($json_jobs['distance_type'])) ? $json_jobs['distance_type'] : '';
				$all_job_data[$all_job_id]['delivery_date']					= (isset($json_jobs['delivery_date'])) ? date('dS M Y', strtotime($json_jobs['delivery_date'])) : '';
				
				$all_job_data[$all_job_id]['weight']							= (isset($json_jobs['weight'])) ? $json_jobs['weight'] : '';
				$all_job_data[$all_job_id]['cargo_value']						= (isset($json_jobs['cargo_value'])) ? $json_jobs['cargo_value'] : '';
				$all_job_data[$all_job_id]['max_job_price']					= (isset($json_jobs['max_job_price'])) ? $json_jobs['max_job_price'] : '';
				$all_job_data[$all_job_id]['is_gurrented']					= (isset($json_jobs['is_gurrented'])) ? $json_jobs['is_gurrented'] : '';
				$all_job_data[$all_job_id]['is_insured']						= (isset($json_jobs['is_insured'])) ? $json_jobs['is_insured'] : '';
				$all_job_data[$all_job_id]['status']							= (isset($json_jobs['status'])) ? $json_jobs['status'] : '0';
				$all_job_data[$all_job_id]['legs_arr']						= $legs_arr;
				
				
				//END
			}
		}
		else{
			$this->mongo_db->where($job_condition_arr);
			
			$all_jobs_json	= $this->mongo_db->get('jobs');
			foreach($all_jobs_json as $json_jobs)
			{
				$all_job_id = strval($json_jobs['_id']);
				//$all_job_data[$all_job_id] 		= $json_jobs;
				$all_job_data[$all_job_id]['id'] 	= strval($json_jobs['_id']);
			}
		}
		
		$all_job_json = json_encode($all_job_data);
		
		//to check next data availability
		$this->mongo_db->where($job_condition_arr);
		
		if($is_my_job != 'my-jobs')
		{
				$this->mongo_db->where_ne('user_id',$user_id);
				//Getting all jobs of this company users
				if(!empty($cmp_user_ids)) $this->mongo_db->where_in('user_id', $cmp_user_ids);
		}
		$this->mongo_db->limit($perPage);
		$this->mongo_db->offset($offset+$perPage);
		//echo $offset.$perPage;die;
		$this->mongo_db->order_by(array('delivery_date' => 'desc'));
		
		$has_next_list			=	$this->mongo_db->get('jobs');
		$data['has_next_list'] 	= count($has_next_list);
		$data['is_my_job'] 		= ($is_my_job == 'my-jobs') ? 1 : 0;
		$data['all_job_json']	= $all_job_json;
		
		//echo "<pre>";
		//print_r($data['all_job_details']);die;
		if($is_my_job == 'my-jobs')
			$this->data['ptitle'] 			= ($this->site_title) ? 'My jobs - '.ucfirst($this->site_title) : 'My jobs';
		else
			$this->data['ptitle'] 			= ($this->site_title) ? 'All jobs - '.ucfirst($this->site_title) : 'All jobs';
		
		$data['data']				= $this->data;
		
		$data['view_link'] 			= 'site/jobs/all_jobs';
		$this->load->view('includes/template_site', $data);
	}
	
	
	//for job quote listing page
	public function my_job_quote_list()
	{
		$this->data['settings'] 		= $this->sitesetting_model->get_settings();
		$site_name 					= (isset($this->data['settings'][0]['site_name'])) ? $this->data['settings'][0]['site_name']  : '';
		
		if(!empty($this->cmp_details))
			$is_my_job		= trim($this->uri->segment(2));
		else
			$is_my_job		= trim($this->uri->segment(1));
			
		$user_id 		= $this->session->userdata('site_user_objId_hotcargo');
		
		$job_objid 	= trim($this->input->post('job_id'));
		$data['job_id'] = $job_objid;
		$data['user_id'] = $user_id;
		
		//if the url call without post method and without job id
		if($job_objid =='')
			redirect('my-jobs');
			
		
		//to check the condition for my job quotes
		$job_condition_arr	= array('job_id'=>$job_objid);
		//echo $is_my_job.'asd';
		//print_r($job_condition_arr);die;
		
		//for total jobs count in the view page
		$this->mongo_db->where($job_condition_arr);
		$data['all_jobs_quotes_count'] 	= $this->mongo_db->get('job_quotes_legs');
		$data['total_qoutes_count']    		= count($data['all_jobs_quotes_count']);
		
		
		$this->mongo_db->where($job_condition_arr);
		$this->mongo_db->order_by(array('added_on' => 'desc'));
		
		$data['all_quotes_details'] 	= $this->mongo_db->get('job_quotes_legs');
		$data['total_qoutes']    		= count($data['all_quotes_details']);
		
		if($data['total_qoutes'] >0)
		{
			foreach($data['all_quotes_details'] as $key => $result)
			{
				
				$client_id 	= (isset($result['user_id'])) ? $result['user_id'] : 0;
				
				//to get client details
				$this->mongo_db->where(array('_id' => $client_id));
				$client_details = $this->mongo_db->get('site_users');
				
				//to get client details
				$this->mongo_db->where(array('user_id' => $client_id));
				$user_ratings = $this->mongo_db->get('user_rating');
				
				$data['all_quotes_details'][$key]['user_details']				= isset($client_details[0]) ? $client_details[0] : array();
				$data['all_quotes_details'][$key]['user_details']['user_rating']	= isset($user_ratings[0]['user_rating']) ? $user_ratings[0]['user_rating'] : '';
				
			}
		}
		
		//echo "<pre>";
		//print_r($data['all_job_details']);die;
		
		$this->data['ptitle'] 		= ($this->site_title) ? 'All quotes - '.ucfirst($this->site_title) : 'All quotes';
		$data['data']				= $this->data;
		$data['view_link'] 			= 'site/jobs/job_quotes_list';
		$this->load->view('includes/template_site', $data);
	}
	
	//For job event activities
	public function job_activities()
	{
		$user_id 	= ($this->session->userdata('site_user_objId_hotcargo')) ?  $this->session->userdata('site_user_objId_hotcargo') : 1;
		$this->data['user_id'] 		= $user_id;
		$user_type 					= $this->session->userdata('site_user_type_hotcargo');
		
		//$job_id						= $this->input->post('job_id');
		if(!empty($this->cmp_details))
			$job_id						= $this->uri->segment(3);
		else
			$job_id						= $this->uri->segment(2);
		$this->data['job_id']			= $job_id;
		
		if($job_id =='')
		{
			$this->session->set_flashdata('flash_message', 'wrong_job_id');
			redirect('dashboard');
		}
		
		//Getting user details
		$this->mongo_db->where(array('_id' => $user_id));
		$myaccount_data 				= $this->mongo_db->get('site_users');
		$this->data['myaccount_data']	= (isset($myaccount_data[0])) ? $myaccount_data[0] : $myaccount_data;
		$this->data['settings'] 		= $this->sitesetting_model->get_settings();
		$site_name 					= (isset($this->data['settings'][0]['site_name'])) ? $this->data['settings'][0]['site_name']  : '';
		$this->data['user_timezone']	= (isset($myaccount_data[0]['user_timezone']) && $myaccount_data[0]['user_timezone'] > 0) ? $myaccount_data[0]['user_timezone'] : $this->system_timezone;
		
		//Fetch job details
		$this->mongo_db->where(array('_id' => $job_id));
		$job_details 			= $this->mongo_db->get('jobs');
		
		if(empty($job_details))
		{
			$this->session->set_flashdata('flash_message', 'wrong_job_id');
			redirect('dashboard');
		}
		
		//Fetch user selected countries
		$this->mongo_db->where(array('job_id' => $job_id));
		$job_activity_lists 			= $this->mongo_db->get('job_events');
		
		//Get the total partial price
		$price = 0;
		if(isset($job_activity_lists) && count($job_activity_lists)>0)
		{
			foreach($job_activity_lists as $key=>$activities)
			{
				$price+=	(isset($activities['event_cost']) && $activities['event_cost']!='') ? $activities['event_cost'] :  0;
			}
		}
		
		$all_job_id = strval($job_details[0]['_id']);
		//$all_job_data[$all_job_id] 		= $json_jobs;
		$all_job_data[$all_job_id]['id'] 	= strval($job_details[0]['_id']);
		$all_job_json = json_encode($all_job_data);
		
		$this->data['all_job_json']	= $all_job_json;
		$this->data['system_timezone']	= $this->system_timezone;
		$this->data['job_activities']	= (isset($job_activity_lists)) ? $job_activity_lists : array();
		$this->data['partial_cost']	= $price;
		
		$this->data['ptitle'] 				= ($this->site_title) ? 'Job Activities - '.ucfirst($this->site_title) : 'Job Activities';
		$data['data']				= $this->data;
		$data['view_link'] 				= 'site/jobs/job_activities';
		
		$this->load->view('includes/template_site', $data);
	}
	
	//For submit events from job activity
	public function add_activity()
	{
		$user_id 	= ($this->session->userdata('site_user_objId_hotcargo')) ?  $this->session->userdata('site_user_objId_hotcargo') : 1;
		$this->data['user_id'] 		= $user_id;
		$user_type 					= $this->session->userdata('site_user_type_hotcargo');
		
		//$job_id						= $this->input->post('job_id');
		if(!empty($this->cmp_details))
			$job_id						= $this->uri->segment(3);
		else
			$job_id						= $this->uri->segment(2);
		$this->data['job_id']			= $job_id;
		
		//Getting all fields info contetns
		$this->mongo_db->where(array('page_title' => 'job activity'));
		$help_contents_list 	= $this->mongo_db->get('pages_help_contents');
		
		
		if($job_id =='')
		{
			$this->session->set_flashdata('flash_message', 'wrong_job_id');
			redirect('dashboard');
		}
		
		//Fetch job details
		$this->mongo_db->where(array('_id' => $job_id));
		$job_details 			= $this->mongo_db->get('jobs');
		
		if(empty($job_details))
		{
			$this->session->set_flashdata('flash_message', 'wrong_job_id');
			redirect('dashboard');
		}
		
		$this->data['help_contents_list']	= (isset($help_contents_list[0]) && !empty($help_contents_list[0])) ? $help_contents_list[0] : array();
		
		//Getting user details
		$this->mongo_db->where(array('_id' => $user_id));
		$myaccount_data 				= $this->mongo_db->get('site_users');
		$this->data['myaccount_data']	= (isset($myaccount_data[0])) ? $myaccount_data[0] : $myaccount_data;
		$this->data['settings'] 		= $this->sitesetting_model->get_settings();
		$site_name 						= (isset($this->data['settings'][0]['site_name'])) ? $this->data['settings'][0]['site_name']  : '';
		
		$this->data['system_timezone']	= $this->system_timezone;
		
		$this->data['ptitle'] 			= ($this->site_title) ? 'Add Activity - '.ucfirst($this->site_title) : 'Add Activity';
		$data['data']					= $this->data;
		$data['view_link'] 				= 'site/jobs/add_activity';
		
		$this->load->view('includes/template_site', $data);
	}
	
	//For submit events from job activity
	public function add_activity_submit()
	{
		$settings 	= $this->sitesetting_model->get_settings();
		$sitename 	= (isset($settings[0]['site_name'])) ? $settings[0]['site_name'] : '';
		
		$user_id 	= ($this->session->userdata('site_user_objId_hotcargo')) ?  $this->session->userdata('site_user_objId_hotcargo') : 1;
		$this->data['user_id'] 		= $user_id;
		$user_type 					= $this->session->userdata('site_user_type_hotcargo');
		
		$job_id						= $this->input->post('job_id');
		
		if($job_id =='')
		{
			$this->session->set_flashdata('flash_message', 'wrong_job_id');
			redirect('dashboard');
		}
		
		$end_country_data 		= (@file_get_contents('http://ws.geonames.org/countryCode?lat='.$this->input->post('event_address_lat').'&lng='.$this->input->post('event_address_lng').'&username=arijit2016&type=JSON', false, $context));
		$end_country_data_arr 	= json_decode($end_country_data);
		$end_country_name 		= isset($end_country_data_arr->countryName) ? $end_country_data_arr->countryName : '';
		$end_country_code 		= isset($end_country_data_arr->countryCode) ? $end_country_data_arr->countryCode : '';
		
		$data_to_store['job_id'] = strval($job_id);
		$data_to_store['user_id']= strval($user_id);
		
		$data_to_store['event_type'] 		= $this->input->post('event_type');
		$data_to_store['activity_details'] = $this->input->post('activity_details');
		$data_to_store['event_cost'] 		= $this->input->post('event_cost');
		$data_to_store['event_address'] 	= array(
											'address'		=> $this->input->post('event_address'),
											'lat'		=> (float)$this->input->post('event_address_lat'),
											'lat_str'		=> strval($this->input->post('event_address_lat')),
											'long'		=> (float)$this->input->post('event_address_lng'),
											'long_str'	=> strval($this->input->post('event_address_lng')),
											'country'		=> $end_country_name,
											'country_code'	=> $end_country_code
										);
		
		$data_to_store['added_on'] 		= date('Y-m-d H:i:s');
		$data_to_store['system_timezone']	= $this->system_timezone;
		$data_to_store['status']			= '1';
		$data_to_store['event_image'] 	= array();
		
		$insert 	= $this->mongo_db->insert('job_events', $data_to_store);
		
		if($insert)
		{
			$data_to_storeimg = array();
			//upload fixed field profile image
			if(isset($_FILES['event_img']['name']) && !empty($_FILES['event_img']['name']))
			{
				foreach($_FILES['event_img']['name'] as $key=> $imgs)
				{
					if(!empty($imgs))
					{
						$file_type 			= (isset($_FILES['event_img']['type'][$key])) ? explode('/', $_FILES['event_img']['type'][$key]) : array();
						$file_type_det 		= (isset($file_type[0])) ? $file_type[0] : '';
						
						$filename 			= (isset($_FILES['event_img']['name'][$key])) ? substr($_FILES['event_img']['name'][$key],strripos($_FILES['event_img']['name'][$key],'.')) : '';
						$s					= time()."_".rand(10,100)."_".$key."_".$filename;
						$file 				= $_FILES['event_img']['tmp_name'][$key];
						
						$DIR_IMG_NORMAL 		= FILEUPLOADPATH.'assets/uploads/event_images/';
						$fileNormal 			= $DIR_IMG_NORMAL.$s;
						$result 				= move_uploaded_file($file, $fileNormal);
						
						if($result)
						{
							$srcPath			= FILEUPLOADPATH.'assets/uploads/event_images/'.$s;
							$destPath1 		= FILEUPLOADPATH.'assets/uploads/event_images/thumb/'.$s;
							$destWidth1		= 500;
							$destHeight1		= 500;
							$this->imagethumb->resizeProportional($destPath1, $srcPath, $destWidth1, $destHeight1);
							$image_name		= $s;
							
							$data_to_storeimg['event_image'][] = $image_name;
						}
					}
				}
				
				if(!empty($data_to_storeimg))
				{
					$this->mongo_db->where(array('_id' => strval($insert)));
					$this->mongo_db->set($data_to_storeimg);
					$this->mongo_db->update('job_events');
				}
			}
			
			//for notification
			$event_type		=$this->input->post('event_type');
			if($event_type	== 'update_location')
			{
				$amount=	$this->input->post('event_cost');
				$curr_location = $this->input->post('event_address');
				$allnotifications_arr	=	array();
				
				//get job owner except login id
				$job_id		=	strval($job_id);
				$this->mongo_db->where(array('_id' => $job_id));
				$this->mongo_db->where_ne('user_id',$user_id);
				$owner_job_details 	= $this->mongo_db->get('jobs');
				if(isset($owner_job_details) && count($owner_job_details)>0)
				{
					$allnotifications_arr[]	=	isset($owner_job_details[0]['user_id']) ? $owner_job_details[0]['user_id'] : '';
				}
				
				//get quotes/leg owners except login id
				$this->mongo_db->where(array('job_id' => $job_id));
				$this->mongo_db->where_ne('user_id',$user_id);
				$leg_owners_job_details 	= $this->mongo_db->get('job_quotes_legs');
				if(isset($leg_owners_job_details) && count($leg_owners_job_details)>0)
				{
					foreach($leg_owners_job_details as $leg_owners)
					{
						$allnotifications_arr[]	=	isset($leg_owners['user_id']) ? $leg_owners['user_id'] : '';
					}
				}
				
				array_filter($allnotifications_arr);
				
				//loop for sms/email receiver
				if(!empty($allnotifications_arr))
				{
					foreach($allnotifications_arr as $receivers)
					{
						$receiver_user_id = $receivers;
						
						$this->mongo_db->where(array('_id' => $receiver_user_id));
						$job_user_details 	= $this->mongo_db->get('site_users');
						
						$user_email_id 	= isset($job_user_details[0]['email']) ? $job_user_details[0]['email'] : '';
						$user_mcountry_code = isset($job_user_details[0]['phone_code']) ? $job_user_details[0]['phone_code'] : '1';
						$user_mobile_no 	= isset($job_user_details[0]['user_phone']) ? $job_user_details[0]['user_phone'] : '';
						$to_name 			= isset($job_user_details[0]['first_name']) ? ucwords($job_user_details[0]['first_name'].' '.$job_user_details[0]['last_name']) : '';
						
						//Getting the user settings
						$this->mongo_db->where(array('user_id' => $receiver_user_id));
						$job_user_notification_settings 	= $this->mongo_db->get('user_settings');
						
						if(!empty($job_user_notification_settings[0]))
						{
							$check_for_email 	= (isset($job_user_notification_settings[0]['tracking_updates_notification']['email'])) ? $job_user_notification_settings[0]['tracking_updates_notification']['email'] : 0;
							
							$check_for_sms 	= (isset($job_user_notification_settings[0]['tracking_updates_notification']['sms'])) ? $job_user_notification_settings[0]['tracking_updates_notification']['sms'] : 0;
							
							$this->mongo_db->where(array('email_title' => 'tracking_update'));
							$email_temp_arr 	= $this->mongo_db->get('email_templates');
							$email_temp		= isset($email_temp_arr[0]) ? $email_temp_arr[0] : '';
							
							//Check for email settings 
							if(!empty($email_temp) && $check_for_email == '1')
							{
								$search 		= array('[SITE_LOGO]', '[NAME]', '[AMOUNT]', '[SITE_NAME]','[CURR_LOCATION]');
								$replace 		= array(assets_url().'site/images/logo.png', $to_name, '$'.$amount, $sitename,$curr_location);
								
								$email_temp_msg= isset($email_temp['email_template']) 	? $email_temp['email_template'] : '';
								$email_temp_msg= str_replace($search, $replace, $email_temp_msg);
								
								$email_temp_sub= isset($email_temp['email_subject']) 	? $email_temp['email_subject'] : '';
								
								if($user_email_id) $this->User_email_model->send_email($user_email_id, $email_temp_sub, $email_temp_msg, '', '', '', $to_name);
								
							}
							
							
							//Check for sms settings
							$this->mongo_db->where(array('sms_title' => 'tracking_update'));
							$sms_temp_arr 	= $this->mongo_db->get('sms_templates');
							$sms_temp		= isset($sms_temp_arr[0]) ? $sms_temp_arr[0] : '';
							
							
							if($user_mobile_no && $check_for_sms == '1' && !empty($sms_temp))
							{
								$search_sms 		= array('[CURR_LOCATION]', '[AMOUNT]');
								$replace_sms 		= array($curr_location, '$'.$amount);
								
								$sms_temp_msg= isset($sms_temp['sms_template']) ? $sms_temp['sms_template'] : '';
								$sms_temp_msg= str_replace($search_sms, $replace_sms, $sms_temp_msg);
	
								$params['mobile_nos_to_send']	= array('+'.$user_mcountry_code.$user_mobile_no);
								$params['sms_messaages']		= array($sms_temp_msg);
								
								if($user_mobile_no)	$this->User_notifications_model->initialize($params);
							}
						}
					}
				}
			}
			
			$this->session->set_flashdata('flash_message', 'event_added');
		}
		else
			$this->session->set_flashdata('flash_message', 'event_added_failed');
		
		redirect('job-activities/'.$job_id);
	}
	
	//For job event activities
	public function activity_details()
	{
		$user_id 	= ($this->session->userdata('site_user_objId_hotcargo')) ?  $this->session->userdata('site_user_objId_hotcargo') : 1;
		$this->data['user_id'] 		= $user_id;
		$user_type 					= $this->session->userdata('site_user_type_hotcargo');
		
		if(!empty($this->cmp_details))
			$event_id					= $is_my_job	= trim($this->uri->segment(3));
		else
			$event_id					= $is_my_job	= trim($this->uri->segment(2));
			
		$this->data['event_id']		= $event_id;
		//Getting user details
		$this->mongo_db->where(array('_id' => $user_id));
		$myaccount_data 				= $this->mongo_db->get('site_users');
		$this->data['myaccount_data']	= (isset($myaccount_data[0])) ? $myaccount_data[0] : $myaccount_data;
		$this->data['settings'] 		= $this->sitesetting_model->get_settings();
		$site_name 					= (isset($this->data['settings'][0]['site_name'])) ? $this->data['settings'][0]['site_name']  : '';
		
		//Fetch user selected countries
		$this->mongo_db->where(array('_id' => strval($event_id)));
		$job_activity_details 			= $this->mongo_db->get('job_events');
		
		if(count($job_activity_details)==0)
		{
			redirect('dashboard');
		}
		//Get the total partial price
		$price = 0;
		//if(isset($job_activity_details) && count($job_activity_details)>0)
		//{
		//	foreach($job_activity_details as $key=>$job_activity_details)
		//	{
		//		$price+=	(isset($job_activity_details['event_cost']) && $job_activity_details['event_cost']!='') ? $job_activity_details['event_cost'] :  0;
		//	}
		//}
		
		$this->data['job_activities_details']	= (isset($job_activity_details)) ? $job_activity_details : array();
		$this->data['job_id']				= (isset($job_activity_details[0]['job_id'])) ? $job_activity_details[0]['job_id'] : '';
		$this->data['partial_cost']	= $price;
		$this->data['ptitle'] 		= ($this->site_title) ? 'Activity Details - '.ucfirst($this->site_title) : 'Activity Details';
		$data['data']				= $this->data;
		$data['view_link'] 			= 'site/jobs/job_activity_details';
		
		$this->load->view('includes/template_site', $data);
	}
	

	//For my accepted quote details
	public function my_accepted_quote_det()
	{
		
		$job_id 		= $this->input->post('job_id');
		$user_id 		= $this->input->post('user_id');
		
		
		
		//Getting loggend user leg details
		$this->mongo_db->where(array('job_id' => $job_id,'user_id' => $user_id));
		$data['leg_details'] 			= $this->mongo_db->get('job_quotes_legs');
		
		//echo "<pre>";print_r($data['leg_details']);die;
		//to get client details
		$this->mongo_db->where(array('_id' => $user_id));
		$client_details = $this->mongo_db->get('site_users');
		
		$data['leg_details'][0]['user_details']				= isset($client_details[0]) ? $client_details[0] : array();
		
		$data['leg_details'][0]['pick_up_formated_date'] 		= (isset($data['leg_details'][0]['pickup_date'])) ? date('d M Y', strtotime($data['leg_details'][0]['pickup_date'])) : '';
		$data['leg_details'][0]['drop_formated_date'] 		= (isset($data['leg_details'][0]['drop_date'])) ? date('d M Y', strtotime($data['leg_details'][0]['drop_date'])) : '';
		
		
		echo json_encode($data);
		
	}
	

	public function read_jobs()
	{
		$has_next_list				=	$this->mongo_db->get('jobs');
		
		echo '<pre>';
		print_r($has_next_list);die;
		foreach($has_next_list as $result)
		{
			if(isset($result['delivery_date']))
			{
				$delivery_date 		= 	(trim($result['delivery_date'])!='') ? date('m-d-Y',strtotime($result['delivery_date'])) : trim($result['delivery_date']);
					
					$data_to_store_file =array('delivery_date' => $delivery_date);
					$this->mongo_db->where(array('_id' => strval($result['_id'])));
					$this->mongo_db->set($data_to_store_file);
					$this->mongo_db->update('jobs');
			}
			
		}
	
	}
	
}

?>