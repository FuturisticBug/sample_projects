<?php
class Product_details_cat_model extends CI_Model {
	/**
	* Responsable for auto load the database
	* @return void
	*/
	var $radius_range			= 1; 
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
    /**
    * Get product by his is
    * @param int $product_id 
    * @return array
    */
//    public function record_count()
//    {
//        $num_data=$this->db->count_all("service_final");
//	return $num_data;
//    }


	function list_services($limit_start=0, $limit_end=10, $lat, $long, $id=null, $service_name=null, $imp_servids=null, $price_low=null,$price_high=null, $sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null, $srchtyp=0)
	{
		$keywords = array();
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$check_keywords = $this->db->where('status', 'Y')->like('name', $service_name, 'before')->get('manage_keyword')->result_array();
			
			foreach($check_keywords as $check_keyword)
				$keywords[] = $check_keyword['id'];
		}
		
		$this->db->select('site_pagination');
		$this->db->from('settings');
		$this->db->where('id',1);          
		$query 		= $this->db->get();
		
		$site_pagi 	=  $query->result_array();
		$pgination_def = 10;
		
		if(isset($site_pagi[0]['site_pagination'])) { $pgination_def = $site_pagi[0]['site_pagination']; }	
		
		//Select all values
		$this->db->select('service_final.*');
		$this->db->select('service_timeslots.date_start, service_timeslots.date_end');
		
		//Select actual distance from center
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		//Select first table
		$this->db->from('service_final');
		
		//Category search section
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		//All basic conditions
		$this->db->where('step_one', 		'Y');
		$this->db->where('step_two', 		'Y');
		$this->db->where('step_three', 	'Y');
		$this->db->where('step_four', 	'Y');
		$this->db->where('step_five', 	'Y');
		$this->db->where('step_six', 		'Y');
		$this->db->where('final_step', 	'Y');
		$this->db->where('remove_status', 	'N');
		$this->db->where('admin_removed', 	'0');
		$this->db->where('is_copied', 	'0');
		
		//Price search section
		//echo $price_low;
		//echo $price_high;
		if( $price_low >= 0 ){
			$this->db->where('changed_price >=', $price_low);
		}
		if( $price_high > 0 ){
			$this->db->where('changed_price <=', $price_high);
		}
		//if($price_low!='' && $price_high!='')
		//{
		//	$this->db->where('changed_price >=', $price_low);
		//	$this->db->where('changed_price <=', $price_high);
		//}
		
		//Service name search sections
		$search_str	= "";
		if($service_name){
			$service_name	= str_replace("'","", $service_name);
			$service_name	= trim($service_name);
			
			if(!empty($keywords))
			{
				$search_str .= '( (service_final.alias_url LIKE \'%'.$service_name.'%\') OR ';
				$service_name	= implode(',', $keywords);
				if(count($keywords) > 1){
					$k=0;
					foreach($keywords as $keyword){
						
						if($k>0 && (($k-1)!=count($keywords)))
						{
							$search_str .=' OR FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}else{
							$search_str .=' FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}
						$k=$k+1;
					}
				}
				else{
					$service_name=implode(',', $keywords);
					$search_str .=' FIND_IN_SET ('.$service_name.', service_final.keyword)';
				}
				$search_str .=' )';
			}else{
				$this->db->where('(service_final.alias_url LIKE \'%'.$service_name.'%\')', NULL, FALSE);
			}
			if($search_str!="")
			{
				$this->db->where($search_str);
			}
		}
		
		//Select keyword search ids
		//if(!empty($keywords)){
		//	$service_name=implode(',', $keywords);
		//	if(count($keywords) > 1){
		//		foreach($keywords as $keyword){
		//			$this->db->or_where('FIND_IN_SET ('.$keyword.', service_final.keyword)', NULL, FALSE);	
		//		}
		//	}
		//	else{
		//		$service_name=implode(',', $keywords);
		//		$this->db->or_where('FIND_IN_SET ('.$service_name.', service_final.keyword)', NULL, FALSE);
		//	}
		//}
		
		//Select date search ids
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		//Radious search section
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=',$this->radius_range );
		}
		//Viewport search section
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('(lat_addr 		>'.$sw_lat.'
							AND lat_addr 	< '.$ne_lat.')
							AND (long_addr > '.$sw_long.' 
							AND long_addr 	< '.$ne_long.')', NULL, FALSE);
		}
		
		//Join service timeslots for past services and not completed services
		$this->db->where('((service_timeslots.date_start != ', 	'0000-00-00');
		$this->db->where('service_timeslots.date_end != ', 		'0000-00-00');
		$this->db->where('service_timeslots.date_end >= ', 		"'".date('Y-m-d').'\') OR (service_timeslots.per_day_option = "always"))', false);
		//$this->db->or_where('service_timeslots.per_day_option', 'always');
		$this->db->join('service_timeslots', 'service_final.id = service_timeslots.service_id', 'right');
		
		//Group by service id to remove duplicate results
		$this->db->group_by('service_final.id'); 
		
		//Page limit section
		if($limit_start && $limit_end)
			$this->db->limit($limit_start, $limit_end); 
		else
			$this->db->limit($pgination_def, 0);    
		
		//Ordering section
		$this->db->order_by('name','asc');
		
		$query = $this->db->get();
		
		//echo 'main querysfd: '.$this->db->last_query();
		//die;
		
		return $query->result();
	}

	function all_list_service($limit_start=null, $limit_end=null, $lat, $long, $id=null, $service_name=null, $imp_servids=null, $price_low=null,$price_high=null, $sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null, $srchtyp=0)
		{
		$keywords = array();
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$check_keywords = $this->db->where('status', 'Y')->like('name', $service_name, 'before')->get('manage_keyword')->result_array();
			
			foreach($check_keywords as $check_keyword)
				$keywords[] = $check_keyword['id'];
		}
		
		$this->db->select('site_pagination');
		$this->db->from('settings');
		$this->db->where('id',1);          
		$query 		= $this->db->get();
		
		$site_pagi 	=  $query->result_array();
		$pgination_def = 10;
		
		if(isset($site_pagi[0]['site_pagination'])) { $pgination_def = $site_pagi[0]['site_pagination']; }	
		
		//Select all values
		$this->db->select('service_final.*');
		$this->db->select('service_timeslots.date_start, service_timeslots.date_end');
		
		//Select actual distance from center
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		//Select first table
		$this->db->from('service_final');
		
		//Category search section
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		//All basic conditions
		$this->db->where('step_one', 		'Y');
		$this->db->where('step_two', 		'Y');
		$this->db->where('step_three', 	'Y');
		$this->db->where('step_four', 	'Y');
		$this->db->where('step_five', 	'Y');
		$this->db->where('step_six', 		'Y');
		$this->db->where('final_step', 	'Y');
		$this->db->where('remove_status', 	'N');
		$this->db->where('admin_removed', 	'0');
		$this->db->where('is_copied', 	'0');
		
		//Price search section
		//echo $price_low;
		//echo $price_high;
		if( $price_low >= 0 ){
			$this->db->where('changed_price >=', $price_low);
		}
		if( $price_high > 0 ){
			$this->db->where('changed_price <=', $price_high);
		}
		//if($price_low!='' && $price_high!='')
		//{
		//	$this->db->where('changed_price >=', $price_low);
		//	$this->db->where('changed_price <=', $price_high);
		//}
		
		//Service name search sections
		$search_str	= "";
		if($service_name){
			$service_name	= str_replace("'","", $service_name);
			$service_name	= trim($service_name);
			
			
			if(!empty($keywords))
			{
				$search_str .= '( (service_final.name LIKE \'%'.$service_name.'%\') OR ';
				$service_name	= implode(',', $keywords);
				if(count($keywords) > 1){
					$k=0;
					foreach($keywords as $keyword){
						
						if($k>0 && (($k-1)!=count($keywords)))
						{
							$search_str .=' OR FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}else{
							$search_str .=' FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}
						$k=$k+1;
					}
				}
				else{
					$service_name=implode(',', $keywords);
					$search_str .=' FIND_IN_SET ('.$service_name.', service_final.keyword)';
				}
				$search_str .=' )';
			}else{
				$this->db->where('(service_final.name LIKE \'%'.$service_name.'%\')', NULL, FALSE);
			}
			if($search_str!="")
			{
				$this->db->where($search_str);
			}
		}
		
		//Select keyword search ids
		//if(!empty($keywords)){
		//	$service_name=implode(',', $keywords);
		//	if(count($keywords) > 1){
		//		foreach($keywords as $keyword){
		//			$this->db->or_where('FIND_IN_SET ('.$keyword.', service_final.keyword)', NULL, FALSE);	
		//		}
		//	}
		//	else{
		//		$service_name=implode(',', $keywords);
		//		$this->db->or_where('FIND_IN_SET ('.$service_name.', service_final.keyword)', NULL, FALSE);
		//	}
		//}
		
		//Select date search ids
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		//Radious search section
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=', $this->radius_range );
		}
		//Viewport search section
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('(lat_addr 		>'.$sw_lat.'
							AND lat_addr 	< '.$ne_lat.')
							AND (long_addr > '.$sw_long.' 
							AND long_addr 	< '.$ne_long.')', NULL, FALSE);
		}
		
		//Join service timeslots for past services and not completed services
		$this->db->where('((service_timeslots.date_start != ', 	'0000-00-00');
		$this->db->where('service_timeslots.date_end != ', 		'0000-00-00');
		$this->db->where('service_timeslots.date_end >= ', 		"'".date('Y-m-d').'\') OR (service_timeslots.per_day_option = "always"))', false);
		//$this->db->where('(service_timeslots.date_start != ', 	'0000-00-00');
		//$this->db->where('service_timeslots.date_end != ', 	'0000-00-00');
		//$this->db->where('service_timeslots.per_day_option', 'always');
		//$this->db->or_where('service_timeslots.date_end >= ', 	"'".date('Y-m-d').'\')', false);
		$this->db->join('service_timeslots', 'service_final.id = service_timeslots.service_id', 'right');
		
		//Group by service id to remove duplicate results
		$this->db->group_by('service_final.id'); 
		
		//Page limit section
		if($limit_start && $limit_end)
			$this->db->limit($limit_start, $limit_end); 
		else
			$this->db->limit($pgination_def, 0);    
		
		//Ordering section
		$this->db->order_by('name','asc');
		
		$query = $this->db->get();
		
		return $query->result();
	}

	function list_services_pagination($limit_start, $limit_end, $lat, $long, $id=null, $service_name=null, $imp_servids=null, $price_low=null, $price_high=null, $sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null, $srchtyp=0)
	{
		$keywords = array();
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$check_keywords = $this->db->where('status', 'Y')->like('name', $service_name, 'before')->get('manage_keyword')->result_array();
			
			foreach($check_keywords as $check_keyword)
				$keywords[] = $check_keyword['id'];
		}
		
		//Select all values
		$this->db->select('service_final.id');
		$this->db->select('service_timeslots.date_start, service_timeslots.date_end');
		
		//Select actual distance from center
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		//Select first table
		$this->db->from('service_final');
		
		//Category search section
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		//All basic conditions
		$this->db->where('step_one', 		'Y');
		$this->db->where('step_two', 		'Y');
		$this->db->where('step_three', 	'Y');
		$this->db->where('step_four', 	'Y');
		$this->db->where('step_five', 	'Y');
		$this->db->where('step_six', 		'Y');
		$this->db->where('final_step', 	'Y');
		$this->db->where('remove_status', 	'N');
		$this->db->where('admin_removed', 	'0');
		$this->db->where('is_copied', 	'0');
		
		//Price search section
		if( $price_low >= 0 ){
			$this->db->where('changed_price >=', $price_low);
		}
		if( $price_high > 0 ){
			$this->db->where('changed_price <=', $price_high);
		}
		//if($price_low!='' && $price_high!='')
		//{
		//	$this->db->where('changed_price >=', $price_low);
		//	$this->db->where('changed_price <=', $price_high);
		//}
	 
		//Service name search sections
		if($service_name){
			$service_name=str_replace("'","",$service_name);
			$service_name=trim($service_name);
			//$this->db->where('(service_final.name LIKE \'%'.$service_name.'%\')', NULL, FALSE);
			$search_str	= "";
			if(!empty($keywords))
			{
				$search_str .= '( (service_final.alias_url LIKE \'%'.$service_name.'%\') OR ';
				$service_name	= implode(',', $keywords);
				if( count($keywords) > 1 ){
					$k	= 0;
					foreach($keywords as $keyword){
						
						if($k>0 && (($k-1)!=count($keywords)))
						{
							$search_str .=' OR FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}else{
							$search_str .=' FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}
						$k	= $k+1;
					}
				}
				else{
					$service_name	= implode(',', $keywords);
					$search_str 	.= ' FIND_IN_SET ('.$service_name.', service_final.keyword)';
				}
				$search_str 	.= ' )';
			}else{
				$this->db->where('(service_final.alias_url LIKE \'%'.$service_name.'%\')', NULL, FALSE);
			}
			if($search_str!="")
			{
				$this->db->where($search_str);
			}
		}
		
		//Select keyword search ids
		//if(!empty($keywords)){
		//	$service_name=implode(',', $keywords);
		//	if(count($keywords) > 1){
		//		foreach($keywords as $keyword){
		//			$this->db->or_where('FIND_IN_SET ('.$keyword.', service_final.keyword)', NULL, FALSE);	
		//		}
		//	}
		//	else{
		//		$service_name=implode(',', $keywords);
		//		$this->db->or_where('FIND_IN_SET ('.$service_name.', service_final.keyword)', NULL, FALSE);
		//	}
		//}
		
		//Select date search ids
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		//Radious search section
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=', $this->radius_range); 
		}
		//Viewport search section
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('( lat_addr 		>'.$sw_lat.'
							AND lat_addr 	< '.$ne_lat.')
							AND (long_addr > '.$sw_long.' 
							AND long_addr 	< '.$ne_long.'  )', NULL, FALSE);
		}
		
		//Join service timeslots for past services and not completed services
		//$this->db->where('(service_timeslots.date_start != ', 	'0000-00-00');
		//$this->db->where('service_timeslots.date_end != ', 	'0000-00-00');
		//$this->db->where('service_timeslots.per_day_option', 'always');
		//$this->db->or_where('service_timeslots.date_end >= ', 	"'".date('Y-m-d').'\')', false);
		$this->db->where('((service_timeslots.date_start != ', 	'0000-00-00');
		$this->db->where('service_timeslots.date_end != ', 		'0000-00-00');
		$this->db->where('service_timeslots.date_end >= ', 		"'".date('Y-m-d').'\') OR (service_timeslots.per_day_option = "always"))', false);
		$this->db->join('service_timeslots', 'service_final.id = service_timeslots.service_id', 'right');
		
		//Group by service id to remove duplicate results
		$this->db->group_by('service_final.id'); 
		
		//Ordering section
		if($lat!='' && $long!='')
		    $this->db->order_by('distance_by','asc');    
		else
			$this->db->order_by('service_final.name','asc'); 
		
		$query = $this->db->get();
		//echo '<br><br>pagination query: '.$this->db->last_query(),'<br><br>';
		return $query->result_array();
	}

	
	function al_list_services($lat=null, $long=null, $id=null, $service_name=null, $imp_servids=null, $sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null, $srchtyp=0)
	{
		$keywords = array();
		
		if($service_name){
			$service_name		= str_replace("'","", $service_name);
			$service_name		= trim($service_name);
			
			$check_keywords 	= $this->db->where('status', 'Y')->like('name', $service_name, 'before')->get('manage_keyword')->result_array();
			
			foreach($check_keywords as $check_keyword)
				$keywords[] = $check_keyword['id'];
		}
		
		//Select all values
		$this->db->select('service_final.*');
		$this->db->select('service_timeslots.date_start, service_timeslots.date_end');
		
		//Select actual distance from center
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		//Select first table
		$this->db->from('service_final');
		
		//Category search section
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		//All basic conditions
		$this->db->where('step_one', 		'Y');
		$this->db->where('step_two', 		'Y');
		$this->db->where('step_three', 	'Y');
		$this->db->where('step_four', 	'Y');
		$this->db->where('step_five', 	'Y');
		$this->db->where('step_six', 		'Y');
		$this->db->where('final_step', 	'Y');
		$this->db->where('remove_status', 	'N');
		$this->db->where('admin_removed', 	'0');
		$this->db->where('is_copied', 	'0');
		
		//Service name search sections
		if($service_name){
			$service_name	= str_replace("'","", $service_name);
			$service_name	= trim($service_name);
			//$this->db->where('(service_final.name LIKE \'%'.$service_name.'%\')', NULL, FALSE);
			$search_str	= "";
			if(!empty($keywords))
			{
				$search_str .= '( (service_final.alias_url LIKE \'%'.$service_name.'%\') OR ';
				$service_name	= implode(',', $keywords);
				if( count($keywords) > 1 ){
					$k	= 0;
					foreach($keywords as $keyword)
					{
						if($k>0 && (($k-1)!=count($keywords)))
						{
							$search_str .=' OR FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}else{
							$search_str .=' FIND_IN_SET ('.$keyword.', service_final.keyword)';
						}
						$k	= $k+1;
					}
				}
				else{
					$service_name	= implode(',', $keywords);
					$search_str 	.= ' FIND_IN_SET ('.$service_name.', service_final.keyword)';
				}
				$search_str 	.= ' )';
			}else{
				$this->db->where('(service_final.alias_url LIKE \'%'.$service_name.'%\')', NULL, FALSE);
			}
			if($search_str!="")
			{
				$this->db->where($search_str);
			}
		}
		
		//Select keyword search ids
		//if(!empty($keywords)){
		//	$service_name=implode(',', $keywords);
		//	if(count($keywords) > 1){
		//		foreach($keywords as $keyword){
		//			$this->db->or_where('FIND_IN_SET ('.$keyword.', service_final.keyword)', NULL, FALSE);	
		//		}
		//	}
		//	else{
		//		$service_name=implode(',', $keywords);
		//		$this->db->or_where('FIND_IN_SET ('.$service_name.', service_final.keyword)', NULL, FALSE);
		//	}
		//}
		
		//Select date search ids
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		//Radious search section
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=', $this->radius_range); 
		}
		//Viewport search section
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('( lat_addr 		>'.$sw_lat.'
							AND lat_addr 	< '.$ne_lat.')
							AND (long_addr > '.$sw_long.' 
							AND long_addr 	< '.$ne_long.'  )', NULL, FALSE);
		}
		
		//Join service timeslots for past services and not completed services
		//$this->db->where('(service_timeslots.date_start != ', 	'0000-00-00');
		//$this->db->where('service_timeslots.date_end != ', 	'0000-00-00');
		//$this->db->where('service_timeslots.per_day_option', 'always');
		//$this->db->or_where('service_timeslots.date_end >= ', 	"'".date('Y-m-d').'\')', false);
		$this->db->where('((service_timeslots.date_start != ', 	'0000-00-00');
		$this->db->where('service_timeslots.date_end != ', 		'0000-00-00');
		$this->db->where('service_timeslots.date_end >= ', 		"'".date('Y-m-d').'\') OR (service_timeslots.per_day_option = "always"))', false);
		$this->db->join('service_timeslots', 'service_final.id = service_timeslots.service_id', 'right');
		
		//Group by service id to remove duplicate results
		$this->db->group_by('service_final.id'); 
		
		$this->db->order_by('name','asc'); 
		
		$query = $this->db->get();
		
		//echo '<br><br>all list serv: '.$this->db->last_query().'<br><br>';
		
		return $query->result();
	}
	
	
	function keyword_id_exist($keyword_id)
	{
		$this->db->select('*');
		$this->db->from('manage_keyword');
		$this->db->where('id',$keyword_id);
		$this->db->where('status','Y');          
		$query = $this->db->get();
		    //echo $this->db->last_query();die;
		$site_pagi=  $query->result_array();
		return $site_pagi;
	}

	function list_services1($keyword_id,$log_user_id,$limit_start=0, $limit_end=10, $lat, $long, $id=null, $service_name=null, $imp_servids=null, $price_low=null,$price_high=null,$srchtyp=0,$sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null)
	{
		//echo $keyword_id;
		$keywords = array();
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$check_keywords = $this->db->where('status', 'Y')->like('name', $service_name)->get('manage_keyword')->result_array();
			
			foreach($check_keywords as $check_keyword)
				$keywords[] = $check_keyword['id'];
		}
		
		$this->db->select('site_pagination');
		$this->db->from('settings');
		$this->db->where('id',1);          
		$query = $this->db->get();
		    //echo $this->db->last_query();die;
		$site_pagi=  $query->result_array();
		
		$pgination_def=10;
		
		if(isset($site_pagi[0]['site_pagination'])) { $pgination_def=$site_pagi[0]['site_pagination']; }	
			
		$this->db->select('service_final.*');
		
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		$this->db->from('service_final');
		
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		$this->db->where('service_final.step_one', 		'Y');
		$this->db->where('service_final.step_two', 		'Y');
		$this->db->where('service_final.step_three',		'Y');
		$this->db->where('service_final.step_four',		'Y');
		$this->db->where('service_final.step_five',		'Y');
		$this->db->where('service_final.step_six',		'Y');
		$this->db->where('service_final.final_step',		'Y');
		$this->db->where('service_final.remove_status',	'N');
		$this->db->where('service_final.admin_removed', 	'0');
		$this->db->where('service_final.is_copied', 	'0');
		//$this->db->where('(user_id != '.$log_user_id.' )', NULL, FALSE);
		
		if($keyword_id != ''){
			$this->db->where('FIND_IN_SET ('.$keyword_id.', service_final.keyword)', NULL, FALSE);
		}
		
		if($price_low!='' && $price_high!='')
		{
			$this->db->where('changed_price >=', $price_low);
			$this->db->where('changed_price <=', $price_high);
		}
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$this->db->where('(service_final.name LIKE \'%'.$service_name.'%\')', NULL, FALSE);
		}
		
		if(!empty($keywords)){
			$service_name=implode(',', $keywords);
			if(count($keywords) > 1){
				foreach($keywords as $keyword){
					$this->db->or_where('FIND_IN_SET ('.$keyword.', service_final.keyword)', NULL, FALSE);	
				}
			}
			else{
				$service_name=implode(',', $keywords);
				$this->db->or_where('FIND_IN_SET ('.$service_name.', service_final.keyword)', NULL, FALSE);
			}
		}
		 
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=',$this->radius_range);
		}
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('(lat_addr >'.$sw_lat.'
				AND lat_addr 	< '.$ne_lat.')
				AND (long_addr > '.$sw_long.' 
				AND long_addr 	< '.$ne_long.')', NULL, FALSE);
		}
		
		$pastserv_str	= "SELECT service_id FROM `service_timeslots` WHERE date_start!='0000-00-00' and date_end!='0000-00-00' and date_end < '".date('Y-m-d')."' GROUP BY service_id";
			
		$pastserv_qry		=$this->db->query($pastserv_str);
		$pastserv_arr		=$pastserv_qry->result_array();
		
		$past_services		=0;
		$past_services_arr	=array();
		if(count($pastserv_arr)>0)
		{
			foreach($pastserv_arr as $val )
		     {
				$past_services_arr[]=$val['service_id'];
		     }
		     $past_services=implode(',',$past_services_arr);
		}
		
		$this->db->where('(service_final.id NOT IN ('.$past_services.'))', NULL, FALSE);
		
		/**End**/
		if($limit_start && $limit_end)
			$this->db->limit($limit_start, $limit_end); 
		else
			$this->db->limit($pgination_def, 0);    
		
		$this->db->order_by('name','asc');
		//$this->db->group_by('service_timeslots.service_id'); 
		$query = $this->db->get();
		return $query->result();
	}
	function list_services2($keyword_id,$log_user_id,$limit_start=0, $limit_end=10, $lat, $long, $id=null, $service_name=null, $imp_servids=null, $price_low=null,$price_high=null,$srchtyp=0,$sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null)
	{
		//echo $keyword_id;
		$keywords = array();
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$check_keywords = $this->db->where('status', 'Y')->like('name', $service_name)->get('manage_keyword')->result_array();
			
			foreach($check_keywords as $check_keyword)
				$keywords[] = $check_keyword['id'];
		}
		
		//$this->db->select('site_pagination');
		//$this->db->from('settings');
		//$this->db->where('id',1);          
		//$query = $this->db->get();
		//    //echo $this->db->last_query();die;
		//$site_pagi=  $query->result_array();
		//
		//$pgination_def=10;
		//
		//if(isset($site_pagi[0]['site_pagination'])) { $pgination_def=$site_pagi[0]['site_pagination']; }	
			
		$this->db->select('service_final.*');
		
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		$this->db->from('service_final');
		
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		$this->db->where('service_final.step_one', 		'Y');
		$this->db->where('service_final.step_two', 		'Y');
		$this->db->where('service_final.step_three',		'Y');
		$this->db->where('service_final.step_four',		'Y');
		$this->db->where('service_final.step_five',		'Y');
		$this->db->where('service_final.step_six',		'Y');
		$this->db->where('service_final.final_step',		'Y');
		$this->db->where('service_final.remove_status',	'N');
		$this->db->where('service_final.admin_removed', 	'0');
		$this->db->where('service_final.is_copied', 	'0');
		//$this->db->where('(user_id != '.$log_user_id.' )', NULL, FALSE);
		
		if($keyword_id != ''){
			$this->db->where('FIND_IN_SET ('.$keyword_id.', service_final.keyword)', NULL, FALSE);
		}
		
		if($price_low!='' && $price_high!='')
		{
			$this->db->where('changed_price >=', $price_low);
			$this->db->where('changed_price <=', $price_high);
		}
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			
			$this->db->where('(service_final.name LIKE \'%'.$service_name.'%\')', NULL, FALSE);
		}
		
		if(!empty($keywords)){
			$service_name=implode(',', $keywords);
			if(count($keywords) > 1){
				foreach($keywords as $keyword){
					$this->db->or_where('FIND_IN_SET ('.$keyword.', service_final.keyword)', NULL, FALSE);	
				}
			}
			else{
				$service_name=implode(',', $keywords);
				$this->db->or_where('FIND_IN_SET ('.$service_name.', service_final.keyword)', NULL, FALSE);
			}
		}
		 
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=',$this->radius_range);
		}
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('(lat_addr >'.$sw_lat.'
				AND lat_addr 	< '.$ne_lat.')
				AND (long_addr > '.$sw_long.' 
				AND long_addr 	< '.$ne_long.')', NULL, FALSE);
		}
		
		$pastserv_str="SELECT service_id FROM `service_timeslots` WHERE date_start!='0000-00-00' and date_end!='0000-00-00' and date_end < '".date('Y-m-d')."' GROUP BY service_id";
			
		$pastserv_qry=$this->db->query($pastserv_str);
		$pastserv_arr=$pastserv_qry->result_array();
		
		$past_services=0;
		$past_services_arr=array();
		if(count($pastserv_arr)>0)
		{
			foreach($pastserv_arr as $val )
		     {
				$past_services_arr[]=$val['service_id'];
		     }
		     $past_services=implode(',',$past_services_arr);
		}
		
		$this->db->where('(service_final.id NOT IN ('.$past_services.'))', NULL, FALSE);
		
		
		//$this->db->join('service_timeslots', 'service_final.id = service_timeslots.service_id');
		////$this->db->where('service_timeslots.date_start !=', '0000-00-00');
		////$this->db->where('service_timeslots.date_end !=', '0000-00-00');
		////$this->db->where('service_timeslots.date_end >=', date('Y-m-d'));
		//$this->db->where('(service_timeslots.date_start !="0000-00-00"
		//	       and service_timeslots.date_end !="0000-00-00"
		//	       and service_timeslots.date_end >="'.date('Y-m-d').'"
		//	    )', NULL, FALSE);
		
		
		/**End**/
		  
		
		//$this->db->limit($end_lt, $start_lt);
		//if($lat!='' && $long!='')
		//{
		//	$this->db->order_by('distance_by','asc');    
		//}
		
		
		$this->db->order_by('name','asc');
		//$this->db->group_by('service_timeslots.service_id'); 
		
		$query = $this->db->get();
		
		
		
		return $query->result();
	
	}
	
	function al_list_services1($keyword_id,$log_user_id,$limit_start=0, $limit_end=10, $lat, $long, $id=null, $service_name=null, $imp_servids=null, $price_low=null,$price_high=null, $srchtyp=0, $sw_lat=null, $sw_long=null, $ne_lat=null, $ne_long=null)
	{
		//$this->db->select('site_pagination');
		//$this->db->from('settings');
		//$this->db->where('id',1);          
		//$query = $this->db->get();
		//    //echo $this->db->last_query();die;
		//$site_pagi=  $query->result_array();
		//
		//$pgination_def=10;
		//
		//if(isset($site_pagi[0]['site_pagination'])) { $pgination_def=$site_pagi[0]['site_pagination']; }	
			
		$this->db->select('*');
		
		if($lat!='' && $long!='')
		{
			$this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
		}
		
		$this->db->from('service_final');
		
		if($id!='')
		{
			$this->db->where('(p_cat = '.$id.' OR category = '.$id.' )', NULL, FALSE);
		}
		
		$this->db->where('step_one', 	'Y');
		$this->db->where('step_two', 	'Y');
		$this->db->where('step_three','Y');
		$this->db->where('step_four', 'Y');
		$this->db->where('step_five', 'Y');
		$this->db->where('step_six', 	'Y');
		$this->db->where('service_final.remove_status',	'N');
		$this->db->where('service_final.admin_removed', 	'0');
		$this->db->where('service_final.is_copied', 	'0');
		//$this->db->where('(user_id != '.$log_user_id.' )', NULL, FALSE);
		//if($price_low!='' && $price_high!='')
		//{
		//	$this->db->where('changed_price >=', $price_low);
		//	$this->db->where('changed_price <=', $price_high);
		//}
		
		if($service_name){
			$service_name=str_replace("'","", $service_name);
			$service_name=trim($service_name);
			$this->db->where('(service_final.name LIKE \'%'.$service_name.'%\')', NULL, FALSE);
		}
		 
		if($imp_servids!='' && $imp_servids!=0)
		{
			$this->db->where('(service_final.id IN ('.$imp_servids.'))', NULL, FALSE);  
		}
		
		if($lat!='' && $long!='' && $srchtyp==0)
		{
			////////////////Added by srea////////
			$this->db->where('( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(' . $long . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat_addr ) ) ) )   <=',$this->radius_range); 
			////////////////Added by srea////////
		}
		else if($sw_lat!='' && $sw_long!='' )
		{ 
		     $this->db->where('(lat_addr >'.$sw_lat.'
			       AND lat_addr 	< '.$ne_lat.')
			       AND (long_addr 	> '.$sw_long.' 
			       AND long_addr 	< '.$ne_long.'  )', NULL, FALSE);
		}
		
		//if($limit_start && $limit_end)
		//	$this->db->limit($limit_start, $limit_end); 
		//else
		//	$this->db->limit($pgination_def, 0);    
		
		//$this->db->limit($end_lt, $start_lt);
		//if($lat!='' && $long!='')
		//{
		//	$this->db->order_by('distance_by','asc');    
		//}
		
		$this->db->order_by('name','asc'); 
		
		$query = $this->db->get();
		
		//echo $this->db->last_query();
		
		return $query->result();
	}
	
	
	
	
	function get_all_services_and_tags($tag_name = '', $is_app = 0){
		
		$return_array['name'] = $check_array = array();
		$return_array['id'] = array();
		
		$this->db->select('service_final.id, service_final.name,service_final.alias_url');
		$this->db->join('service_timeslots', 'service_final.id = service_timeslots.service_id');
		$this->db->where('step_one', 	'Y');
		$this->db->where('step_two', 	'Y');
		$this->db->where('step_three','Y');
		$this->db->where('step_four', 'Y');
		$this->db->where('step_five', 'Y');
		$this->db->where('step_six', 	'Y');
		$this->db->where('admin_removed', '0');
		$this->db->where('is_copied', '0');
		$this->db->where('remove_status', 'N');
		$this->db->where('service_timeslots.per_day_option', 'always');
		$this->db->or_where('service_timeslots.date_end >= ', date('Y-m-d'));
		$this->db->group_by("service_final.id"); 
		$this->db->from('service_final');
		$this->db->group_by('name','asc'); 
		$query = $this->db->get();
		//echo $this->db->last_query();
		
		$all_data = $query->result_array();
	
		$search = array("'", '-'); $replace = array('', ' ');
	
		if(!empty($all_data)){
			foreach($all_data as $data){
				if(!empty($data['name'])){
					//$return_array['name'][] = ucfirst(mb_substr($data['name'], 0, 100, 'UTF-8'));
					$return_array['name'][] 	= (htmlentities((ucfirst(strtolower(str_replace($search, $replace, ($data['alias_url'])))))));
					$return_array['id'][] 	= $data['id'];
				}
			}
		}
		
		$check_array = $return_array['name'];
		
		$this->db->select('id,name');
		$this->db->where('status', 	'Y');
		$this->db->from('manage_keyword');
		$this->db->order_by('name','asc'); 
		$query1 = $this->db->get();
		
		$all_data1 = $query1->result_array();
		//echo '<pre>'; print_r($all_data1); echo '</pre>';
		if(!empty($all_data1)){
			foreach($all_data1 as $data1){
				if(isset($data1['name']) && !empty($data1['name'])){
					if(!in_array(ucfirst($data1['name']), $check_array)){
						$return_array['name'][] 	= (htmlentities(ucfirst(strtolower(str_replace($search, $replace, $data1['name'])))));
						$return_array['id'][] 	= $data1['id'];
					}
				}
			}
		}
		
		return $return_array;
	}
	
	
	
	
	function check_wishlist($product_id = 0, $user_id = 0){
		
		$user_id = ($user_id) ? $user_id : $this->session->userdata('user_id_site');
		if($product_id > 0 && $user_id > 0){
			
			$this->db->select('user_wishlist.id');
			$this->db->from('user_wishlist');
			$this->db->where('service_product_id', $product_id);
			$this->db->where('user_id', $user_id);
			
			$query = $this->db->get();
			return $query->row(); 
		}
		else
			return 0;
	}
	
	//SELECT `service_timeslots`.`service_id`, `service_timeslots`.`date_start`, `service_timeslots`.`date_end` FROM (`service_timeslots`) LEFT JOIN `service_final` ON `service_final`.`id` = `service_timeslots`.`service_id` WHERE `date_start` != '0000-00-00' AND `date_end` != '0000-00-00' AND '2016-05-03' BETWEEN `service_timeslots`.`date_start` AND `service_timeslots`.`date_end` AND `service_timeslots`.`date_end` >= '2016-05-03' GROUP BY `service_id`
    
	function date_opens($dt_start = '', $dt_end = '')
	{
		$dt_start = date('Y-m-d',strtotime(str_replace("-","/",$dt_start)));
		$dt_end	=  date('Y-m-d',strtotime(str_replace("-","/",$dt_end)));
		
		$query = "SELECT `service_timeslots`.`service_id`, `service_timeslots`.`date_start`, `service_timeslots`.`date_end` FROM (`service_timeslots`) LEFT JOIN `service_final` ON `service_final`.`id` = `service_timeslots`.`service_id` WHERE `date_start` != '0000-00-00' AND `date_end` != '0000-00-00' AND `service_final`.`step_one` = 'Y' AND `service_final`.`step_two` = 'Y' AND `service_final`.`step_three` = 'Y' AND `service_final`.`step_four` = 'Y' AND `service_final`.`step_five` = 'Y' AND `service_final`.`step_six` = 'Y' AND `service_final`.`final_step` = 'Y' AND `service_final`.`remove_status` = 'N' AND `service_final`.`admin_removed` = '0' AND `service_final`.`is_copied` = '0' ";
		
		if($dt_start)
			$query  .= " AND '".$dt_start."' BETWEEN `service_timeslots`.`date_start` AND `service_timeslots`.`date_end`";
		
		if(!empty($dt_end) && ($dt_start != $dt_end))
			$query 	.= " OR `service_timeslots`.`date_end` >= '".$dt_end."'";
		
		$query .= " GROUP BY `service_id`";
		
		//echo $query.'<br>';
		
		$data = $this->db->query($query);
		$results = $data->result_array();
		
		return $results;
	}
    
	function catdrop_down()
     {
		$category_data = array();
		$this->db->select('*');
		$this->db->from('manage_category');
		$this->db->order_by('cat_name asc, parent_id asc'); 
		$this->db->where('status','Y');
		$results = $this->db->get()->result();
		
		foreach($results AS $result)
		{
			$category_data[] = array(
				'id' 			=> $result->id,
				'cat_name' 		=> $this->getPath($result->id)
			);
			//$category_data = array_merge($category_data, $this->all_cats($result->id));
		}
		return $category_data;
     }
	
	public function getPath($category_id)
	{
		$current_lng 	= (($this->session->userdata('mainsite_lang')!='')) ? $this->session->userdata('mainsite_lang') : '1';
		
		$this->db->select('parent_id,cat_name');
		$this->db->from('manage_category');
		$this->db->where('id',$category_id);
		$rows = $this->db->get()->result();
		
		foreach($rows AS $row)
		{
			if ($row->parent_id) {
				return $this->getPath($row->parent_id) . ' > ' . ucfirst(strtolower($row->cat_name));
			} else {
				if($current_lng != 1){
					
					$this->db->select('*');
					$this->db->from('lang_content');
					$this->db->where('contentid', $category_id);
					$this->db->where('langid', 	$current_lng);
					$this->db->where('reffield', 'cat_name');
					$this->db->where('reftable', 	'manage_category');
		
					$query = $this->db->get();
					$result=$query->row();
					
					if(isset($result->value) && !empty($result->value))
						return ucfirst(strtolower($result->value));
					else
						return ucfirst(strtolower($row->cat_name));
				}
				else
					return ucfirst(strtolower($row->cat_name));
			}
		}
	}
    
    
	function always_available()
	{
		$this->db->select('service_timeslots.service_id');
		$this->db->from('service_timeslots');
		$this->db->where('date_start', '0000-00-00');
		$this->db->where('date_end', '0000-00-00');
		
		//All basic conditions
		$this->db->where('service_final.step_one', 		'Y');
		$this->db->where('service_final.step_two', 		'Y');
		$this->db->where('service_final.step_three', 	'Y');
		$this->db->where('service_final.step_four', 		'Y');
		$this->db->where('service_final.step_five', 		'Y');
		$this->db->where('service_final.step_six', 		'Y');
		$this->db->where('service_final.final_step', 	'Y');
		$this->db->where('service_final.remove_status', 	'N');
		$this->db->where('service_final.admin_removed', 	'0');
		$this->db->where('service_final.is_copied', 		'0');
		
		$this->db->join('service_final', 'service_final.id = service_timeslots.service_id', 'left');
		$this->db->where('service_timeslots.per_day_option', 'always');
		//$this->db->where('service_final.option !=', 'product');
		$this->db->group_by('service_id');
		
		$query = $this->db->get();
		//echo $this->db->last_query();
		return $query->result_array();
	}
    
    
    
	
    
	function check_holiday($id,$to_day)
	{
		$this->db->select('*');
		$this->db->from('holiday');
		$this->db->where('serv_id',$id);
		$this->db->where('holi_date',$to_day);
		$query = $this->db->get();
		//echo $this->db->last_query();die;
		return  $query->result_array();
	}
       
       

    public function get_service_details($id,$limit_start=null,$limit_end=null,$lat=null,$long=null)
    {
		//$limit_start=null, $limit_end=null
	$this->db->select('site_pagination');
	$this->db->from('settings');
	$this->db->where('id',1);          
	$query = $this->db->get();
	    //echo $this->db->last_query();die;
	$site_pagi=  $query->result_array();
	$pgination_def=10;
	if(isset($site_pagi[0]['site_pagination'])) { $pgination_def=$site_pagi[0]['site_pagination']; }	
		
	$this->db->select('*');
	if($lat!='' && $long!='')
	{
	    $this->db->select("( 3959 * acos( cos( radians(" . $lat . ") ) * cos( radians(lat_addr ) ) * cos( radians( long_addr ) - radians(" . $long . ") ) + sin( radians(" . $lat . ") ) * sin( radians( lat_addr ) ) ) ) as distance_by ");
	}
	$this->db->from('service_final');
	if($id!='')
	{
	  $this->db->where('p_cat', $id);
	   $this->db->or_where('category', $id); 
	}
	
	if($limit_start && $limit_end)
	    {
	       $this->db->limit($limit_start, $limit_end); 
	    }else{
		$this->db->limit($pgination_def, 0);    
	    }
	//$this->db->limit($end_lt, $start_lt);
	if($lat!='' && $long!='')
	{
	    $this->db->order_by('distance_by','asc');    
	}
	
	
	$query = $this->db->get();
	 //echo $this->db->last_query();
           // exit;
	return $query->result();
    }
    
    function mappins_afterpagination($ids)
    {
	$array=explode(",",$ids);
	$this->db->select('*');
	$this->db->from('service_final');
	$this->db->where_in('id',$array);
	$query = $this->db->get();
	 
	return $query->result_array();
	
    }
    
    public function category_details($cat_id)
    {
	$this->db->select('*');
	$this->db->from('manage_category');
	$this->db->where('id', $cat_id);
	$query = $this->db->get();
	$return_data = $query->result();
	return $return_data;
    }
    public function image_details($id)
    {
	$this->db->select('*');
	$this->db->from('images');
	$this->db->where('service_or_product_id ', $id);
	$this->db->order_by('id', 'desc');
	$this->db->limit(1, 0);
	$query = $this->db->get();
	$return_image_data = $query->result();
	return $return_image_data;
    }
    
    
     public function allimages_service($id)
    {
	$this->db->select('*');
	$this->db->from('images');
	$this->db->where('service_or_product_id ', $id);
	$this->db->order_by('is_feature', 'desc');
	$this->db->order_by('id', 'asc');
	
	$query = $this->db->get();
	//echo $this->db->last_query();
           // exit;
	$return_image_data = $query->result_array();
	return $return_image_data;
    }
    
    public function user_details($uID)
    {
	$this->db->select('*');
	$this->db->from('users');
	$this->db->where('id', $uID);
	$query = $this->db->get();
	$return_data = $query->result();
	return $return_data;
	
    }
    function get_currency($currency_id)
	{
		$this->db->select('*');
		$this->db->where('id',$currency_id);
		$this->db->from('currency');
		$results = $this->db->get();
		return $results->result();
	}
    public function calender_details($id,$day)
    {
	$this->db->select('*');
	$this->db->where('service_id',$id);
	$this->db->group_by('service_id');
	$this->db->from('service_timeslots');
	$result_cal= $this->db->get();
	$service_cal= $result_cal->result_array();
	    if(!isset($service_cal[0]['booking_type']) || ($service_cal[0]['booking_type']!='per_day'))
	    {
		$book_type='';
		$sql="SELECT * FROM `service_timeslots` WHERE day='".$day."' and service_id='".$id."'";
		$query=$this->db->query($sql);
		$result_time=$query->result();
	    }else{
		$result_time='';
	    }
	    
	    return $result_time;
    }
   
    
	public function get_cat_name($category_id)
	{
	$this->db->select('parent_id,cat_name');
	$this->db->from('manage_category');
	$this->db->where('id',$category_id);
	$rows = $this->db->get()->result();
	if(isset($rows[0]->cat_name))
	{
	    $nm=$rows[0]->cat_name;
	    return $nm;
	}else{
	    $nm='-';
	    return $nm;
	}
	}
     public function all_cats($parent_id = 0)
        {
	$category_data = array();
	$this->db->select('*');
	$this->db->from('manage_category');
	$this->db->where('parent_id',$parent_id);
	$this->db->where('status','Y');
	$results = $this->db->get()->result();
	
	    foreach($results AS $result)
	    {
		    $category_data[] = array(
			    'id' => $result->id,
			    'cat_name' => $this->getPath($result->id)
		    );
		    $category_data = array_merge($category_data, $this->all_cats($result->id));
	    }
	    return $category_data;
        }
    public function  all_cats_edit($parent_id = 0,$id)
    {
	$category_data = array();
	$this->db->select('*');
	$this->db->from('manage_category');
	$this->db->where('parent_id',$parent_id);
	//$this->db->where("id !=",$id);
	$this->db->where('status','Y');
	$results = $this->db->get()->result();
	//print_r($results);
	//exit;
	    foreach($results AS $result)
	    {
		    //echo $result->id.',';
		    $category_data[] = array(
			    'id' => $result->id,
			    'cat_name' => $this->getPath($result->id)
		    );
		    $category_data = array_merge($category_data, $this->all_cats($result->id));
	    }
	    
	    return $category_data;
	
    }
    Public function getRowservice($user_id)
    {
	$this->db->select('*');
	$this->db->from('service_final');	
	$this->db->where('user_id', $user_id);
	$query = $this->db->get();
	return $query->num_rows();
    }
    public function profile($id)
    {
	$this->db->select('*');
	$this->db->from('users');
	$this->db->where('id',$id);          
	$query = $this->db->get();
	    //echo $this->db->last_query();die;
	return  $query->result();
    }
	    
    public function delete($id)
    {           
	    $this->db->where('id', $id);
	    $this->db->delete('service_final');
	    //echo $this->db->last_query();die;
	    return true;
    }
  
    public function get_service_details_search($new_service_name,$new_service_place,$open_date,$time_open,$cat_id=null)
    {
	$this->db->select('site_pagination');
	$this->db->from('settings');
	$this->db->where('id',1);          
	$query = $this->db->get();
	    //echo $this->db->last_query();die;
	$site_pagi=  $query->result_array();
	$pgination_def=10;
	if(isset($site_pagi[0]['site_pagination'])) { $pgination_def=$site_pagi[0]['site_pagination']; }
	
	$sql_qry="SELECT service.*,time.service_id,time.open_start,time.open_close   FROM  `service_final` AS service  INNER JOIN `service_timeslots` AS time ON service.id = time.service_id WHERE ";
	//$sql_qry="SELECT * FROM  `service_final` WHERE ";
	if(($new_service_name!='') && ($new_service_name!=' ') && ($new_service_name!='default'))
	{
		$new_name=$new_service_name;
		//echo $new_service_name;
		if(strpos($new_service_name,','))
		{
			$new_name=str_replace(","," ",$new_service_name);
		}
		 $arr_srvc_nm=explode(" ",$new_name);
		// print_r($arr_srvc_nm);
		 $no_array=count($arr_srvc_nm);
		
		if($no_array>0)
		{
			//$sql_qry.= " ";
			foreach($arr_srvc_nm as $key=>$srvc_name)
			{
			        $srvc_name=str_replace("'","",$srvc_name);
				$sql_qry.="`name` LIKE '%".$srvc_name."%'";
				if($key!=$no_array-1)
				{
					$sql_qry.=" OR ";
				}
			}
			//$sql_qry.= ")";
		}else{
			$sql_qry.= "`name` LIKE '%".$new_name."%'";
		}
	    
	    $check_name=1;
	}else{
	    $sql_qry.= "";
	    $check_name=0;
	}
	if(($new_service_place!='') && ($new_service_place!=' ') && ($new_service_place!='default'))
	{
		if($check_name!=0)
		{
		     $sql_qry.="and";
		}
	    
	   //  $sql_qry.= "(`from_country`=(SELECT `country_id` FROM `country` WHERE `country_name`='".$new_service_place."') or
		//	 `address_country`=(SELECT `country_id` FROM `country` WHERE `country_name`='".$new_service_place."'))";
		$sql_qry.= "(`from_country`='".$new_service_place."' or
			 `address_country`='".$new_service_place."')";
	     $check_place=1;
	}
	else
	{
		$check_place=0;
		$sql_qry.= "";
	}
	if(($open_date!='') && ($open_date!=' ') && ($open_date!='default'))
	{
	    if($check_name!=0 || $check_place!=0)
	    {
	     $sql_qry.="and";
	    }
	    $sql_qry.= " time.open_start='".$open_date."'";
	    $check_date=1;
	}
	else
	{
		$check_date=0;
		$sql_qry.= "";
	}
	if(($time_open!='') && ($time_open!=' ') && ($time_open!='default'))
	{
	    if($check_name!=0 || $check_place!=0 || $check_date!=0)
	    {
	     $sql_qry.="and";
	    }
	    $sql_qry.= " (time.open_start<='".strtotime($time_open)."' and time.open_close>'".strtotime($time_open)."') OR (time.open_start<'".strtotime($time_open)."' and time.open_close>='".strtotime($time_open)."')";
	}
	if($cat_id)
	{
		if($check_name!=0 || $check_place!=0 || $check_date!=0)
	    {
	     $sql_qry.=" and";
	    }
		$sql_qry.=" p_cat='".$cat_id."' OR category='".$cat_id."' ";
	}
	$sql_qry.=" GROUP BY time.service_id  ";
	
	//if($limit_start && $limit_end)
	//	{
	//		$sql_qry.=" LIMIT '".$limit_end."','".$limit_start."'";
	//			
	//	  // $this->db->limit($limit_start, $limit_end); 
	//	}else{
		    $sql_qry.=" LIMIT 0,".$pgination_def;
		//}

	//exit;
	$query=$this->db->query($sql_qry);
	//echo '<br>';
	//echo $sql_qry;
	//exit;
	//echo $this->db->last_query();die;
	$result=$query->result();
	return $result;
    }
    public function calender_details_per_day($day_timeslot_search,$id)
    {
	$this->db->select('*');
	$this->db->where('service_id',$id);
	$this->db->where('open_start',$day_timeslot_search);
	$this->db->from('service_timeslots');
	$results = $this->db->get();
	return $results->result_array();
    }
    public function calender_details_slot($day_timeslot_search,$id)
    {
	$this->db->select('*');
	$this->db->where('service_id',$id);
	$this->db->where('open_start',$day_timeslot_search);
	$this->db->from('service_timeslots');
	$results = $this->db->get();
	return $results->result_array();
    }
	public function get_currency_name($id)
     {
		$cur_name = '';
		$this->db->select('*');
		$this->db->where('id', $id);
		$this->db->from('currency');
		$query = $this->db->get();
          $c_data=$query->result();
		
		if(isset($c_data[0]->currency_name))
		{
			$cur_name = $c_data[0]->currency_name;
		}
		
		return $cur_name;
	}
	//////////29.07.2015////////////
    public function get_service_details_search_page($new_service_name,$new_service_place,$open_date,$time_open,$cat_id=null,$limit_start=null,$limit_end=null)
    {
	$sql_qry="SELECT service.*,time.service_id,time.open_start,time.open_close   FROM  `service_final` AS service  LEFT JOIN `service_timeslots` AS time ON service.id = time.service_id WHERE ";
	//$sql_qry="SELECT * FROM  `service_final` WHERE ";
	if(($new_service_name!='') && ($new_service_name!=' ') && ($new_service_name!='default'))
	{
	    $sql_qry.= "`name` LIKE  '%".$new_service_name."%'";
	    $check_name=1;
	}else{
	    $sql_qry.= "";
	    $check_name=0;
	}
	if(($new_service_place!='') && ($new_service_place!=' ') && ($new_service_place!='default'))
	{
		if($check_name!=0)
		{
		     $sql_qry.="and";
		}
	    
	//     $sql_qry.= "(`from_country`=(SELECT `country_id` FROM `country` WHERE `country_name`='".$new_service_place."') or
	//		 `address_country`=(SELECT `country_id` FROM `country` WHERE `country_name`='".$new_service_place."'))";
	$sql_qry.= "(`from_country`='".$new_service_place."' or `address_country`='".$new_service_place."')";
	     $check_place=1;
	}
	else
	{
		$check_place=0;
		$sql_qry.= "";
	}
	if(($open_date!='') && ($open_date!=' ') && ($open_date!='default'))
	{
	    if($check_name!=0 || $check_place!=0)
	    {
	     $sql_qry.="and";
	    }
	    $sql_qry.= " time.open_start='".$open_date."'";
	    $check_date=1;
	}
	else
	{
		$check_date=0;
		$sql_qry.= "";
	}
	if(($time_open!='') && ($time_open!=' ') && ($time_open!='default'))
	{
	    if($check_name!=0 || $check_place!=0 || $check_date!=0)
	    {
	     $sql_qry.="and";
	    }
	    $sql_qry.= " (time.open_start<='".strtotime($time_open)."' and time.open_close>'".strtotime($time_open)."') OR (time.open_start<'".strtotime($time_open)."' and time.open_close>='".strtotime($time_open)."')";
	}
	if($cat_id)
	{
		if($check_name!=0 || $check_place!=0 || $check_date!=0)
	    {
	     $sql_qry.=" and";
	    }
		$sql_qry.=" p_cat='".$cat_id."' OR category='".$cat_id."' ";
	}
	$sql_qry.=" GROUP BY time.service_id  ";
	
	if($limit_start && $limit_end)
		{
			//$limit_end=$limit_end+1;
			$sql_qry.=" LIMIT ".$limit_end.",".$limit_start."";
				
		  // $this->db->limit($limit_start, $limit_end); 
		}
		/*else{
		    $sql_qry.=" LIMIT 0,2";
		}*/
//echo $sql_qry;
//	exit;
	//exit;
	$query=$this->db->query($sql_qry);
	echo $this->db->last_query();die;
	$result=$query->result();
	return $result;
    } 
	////////29.07.2015/////////////
	 public function user_img_details($user_id)
		{
			$this->db->select('*');
            $this->db->from('profile_image');
            $this->db->where('user_id',$user_id);
            $this->db->order_by('id','DESC');
            $this->db->limit(1,0);
            $query = $this->db->get();           
            return  $query->result();
			
		}
		public function check_price_high()
		{
			$this->db->select_max('changed_price');
			//$this->db->where('user_id',$user_id);
			$this->db->from('service_final');
			$this->db->where('step_one','Y');
			$this->db->where('step_two','Y');
			$this->db->where('step_three','Y');
			$this->db->where('step_four','Y');
			$this->db->where('step_five','Y');
			$this->db->where('step_six','Y');
			$query = $this->db->get();
			//echo $this->db->last_query();die;
			$result=$query->result();
			return $result;
		}
		public function check_price_low()
		{
			$this->db->select_min('changed_price');
			//$this->db->where('user_id',$user_id);
			$this->db->from('service_final');
			$this->db->where('step_one','Y');
			$this->db->where('step_two','Y');
			$this->db->where('step_three','Y');
			$this->db->where('step_four','Y');
			$this->db->where('step_five','Y');
			$this->db->where('step_six','Y');
			$query = $this->db->get();
			//echo $this->db->last_query();die;
			$result=$query->result();
			return $result;
		}
}
?>