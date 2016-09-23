<?php
class Url_generator_model extends CI_Model {
 
	/**
	* Responsable for auto load the database
	* @return void
	*/
	public function __construct()
	{
		
	}
	
	function url_generate($str, $replace = array(), $delimiter = '-', $concat_data = '', $added_id=0, $table='', $coloumn='alias_url') {
		$alias_url='';
		if(!empty($table))
		{
			$alias_url_obj=$this->db->where('id',$added_id)->get($table)->row();
			$alias_url=isset($alias_url_obj->$coloumn)?$alias_url_obj->$coloumn:'';
		}
		setlocale(LC_ALL, 'en_US.UTF8');
		
		if( !empty($replace) ) {
			$str = str_replace((array)$replace, ' ', $str);
		}
		$str   = strtolower($str);
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
	
		$clean = ($alias_url) ? base_url().$concat_data.'/'.$alias_url : base_url().$concat_data.'/'.$clean;
	
		return $clean;
	}
	
	function title_generate($title="") {
		setlocale(LC_ALL, 'en_US.UTF8');
		
		
		$str   = strtolower($title);
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '_'));
		$clean = preg_replace("/[\/_|+ -]+/", '_', $clean);
	
		//$clean = ($added_id) ? base_url().$concat_data.'/'.$added_id.'/'.$clean : base_url().$concat_data.'/'.$clean;
	
		return $clean;
	}

 
 
}
?>