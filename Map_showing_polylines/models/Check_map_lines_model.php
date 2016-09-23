<?php

class Check_map_lines_model extends CI_Model {

	
	var $job_id 			= '';									//the id of the job which les we want to fetch
	var $user_id 			= '';									//Current loggedin user id
	var $start_point 		= 'A';									//Job start point
	var $end_point 		= 'C';									//Job end point
	var $indivisual_points 	= array('A', 'B', 'D', 'E', 'C');				//All job leg points
	var $connected_points 	= array(
								array('A', 'B'), array('A', 'E'),
								array('B', 'C'), array('B', 'E'), array('B', 'D'),
								array('D', 'C'), array('D', 'E'), array('D', 'B'),
								array('E', 'B'), array('E', 'D'), array('E', 'C'), array('E', 'D'), 
							);									//All job leg points connected matrix
	var $graph_point_arr 	= array();								//Final connected matrix
	var $call_func 		= '';									//Function to call
	var $call_url 			= '';									//Url to fetch connected matrix as json
	var $output 			= '';									//Final output
	
	//We are initialize the default variables and call the specific function
	public function initialize($params = array())
	{
		if(!empty($params))
		{
			foreach($params as $r => $parapm)
			{
				$this->$r = $parapm;
			}
		}
		
		if($this->call_func == 'generate_graph_arr'){
			$this->generate_graph_arr();
			return $this->output;
		}
		else{
			$this->main();
			return $this->output;
		}
	}
	
	//Main function to call python dfs graph code and ferch the outpur
	public function main()
	{
		$this->call_url = str_replace('http://', '', $this->call_url);
		$this->check_recursive_map($this->start_point, $this->end_point, $this->call_url);
	}
	
	//Generating graph connection matrix based on given co-ordinates
	public function generate_graph_arr()
	{
		//Put start point at first of the graph
		if(!empty($this->start_point))
			$this->graph_point_arr[$this->start_point] 	= array();
		
		if(!empty($this->indivisual_points))
		{
			//First we have to put all indivisual points in a array as key
			foreach($this->indivisual_points as $point)
				$this->graph_point_arr[$point] = array();
		}
		
		//Put end point at last of the graph
		if(!empty($this->end_point))
			$this->graph_point_arr[$this->end_point] 	= array();
		
		
		//Now we have to check and put all indivisual connected points
		foreach($this->graph_point_arr as $gp => $gpoint)
		{
			$this->graph_point_arr[$gp] = array();
			
			foreach($this->connected_points as $cp => $cpoint)
			{
				if(in_array($gp, $cpoint)){
					
					$next_point = '';
					foreach($cpoint as $spoint){ $next_point = ($spoint != $gp) ? $spoint : ''; }
					if($next_point != ''){ array_push($this->graph_point_arr[$gp], $next_point); }
				}
			}
		}
		
		echo json_encode($this->graph_point_arr);
	}
	
	//We are using depth-first search for this
	public function check_recursive_map($start_point, $end_point, $call_url)
	{
		//echo FILEUPLOADPATH.'application/outer_source/main.py '.$start_point.' '.$end_point.' '.$call_url.'<br><br>';
		
		$command 	= FILEUPLOADPATH.'application/outer_source/main.py '.$start_point.' '.$end_point.' '.$call_url;
		$output 	= exec("python ".$command);
		$det_arr 	= $final_leg_arr = array();
		
		if(!empty($output))
		{
			$search 					= array('u', '[[', ']]');		//Replacing the python output to usable php string
			$replace					= array('', '[', ']');			//Replacing the python output to usable php string
			$all_possible_leg_str		= str_replace($search, $replace, $output);
			$all_possible_leg_arr 		= explode('],', $all_possible_leg_str);
			
			if(!empty($all_possible_leg_arr))
			{
				$search1				= array('[', ']');
				$replace1				= array('', '');
				
				foreach($all_possible_leg_arr as $comp_leg_arr)
					$det_arr[] 		= str_replace($search1, $replace1, $comp_leg_arr);
			}
			
			if(!empty($det_arr))
			{
				foreach($det_arr as $a => $arr)
				{
					$arr_f 			= str_replace("'", '', $arr);
					$leg_points_arr 	= explode(',', $arr_f);
					
					foreach($leg_points_arr as $l => $ileg_point)
					{
						if(isset($leg_points_arr[$l+1]))
						{
							//Storing each point
							$final_arr[0]	= $ileg_point;				//node start point
							$final_arr[1]	= isset($leg_points_arr[$l+1]) ? $leg_points_arr[$l+1] : $ileg_point; //node end point
							
							$final_leg_arr[$a][]	= $final_arr;
						}
					}
				}
			}
		}
		
		$this->output = $final_leg_arr;
	}
}