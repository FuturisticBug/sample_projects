<?php
class Mapview_controller extends CI_Controller {
	
	public $show_year 		= '';
	public $show_year_pir 	= '';
	public $show_year_url 	= '';
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('sitesetting_model');
		$this->load->model('Url_generator_model');
		$this->load->model('Home_model');
		
		$uri_year 	= $this->uri->segment(2);
		 $uri_year=($uri_year!='')? $uri_year : $this->session->userdata('site_year');
		$year_det 	= $this->db->where('type', 1)->where('year', $uri_year)->get('election_years')->row();
		$year_det1 	= $this->db->where('type', 2)->where('year', $uri_year)->get('election_years')->row();
		
		if((isset($year_det->id)) && (!empty($year_det))){
			$this->show_year 		= $year_det->id;
			$this->show_year_pir 	= (isset($year_det1->id)) ? $year_det1->id : 2;
			$this->show_year_url 	= $year_det->year;
		}
		else{
			$this->show_year 		= 1;
			$this->show_year_pir 	= 2;
			$this->show_year_url 	= 2012;
		}
	}
	
	public function index()
	{
		$all_regions 		= $this->db->where('status', 1)->order_by('title', 'asc')->get('regions')->result_array();
		$all_constituencies = $this->db->where('status', 1)->order_by('title', 'asc')->get('constituencies')->result_array(); 
		
		$all_parties		= $this->db->select('candidate_id')->select('party_id')->select('AVG(election_data.total_precent) as total_percent')->select('SUM(election_data.total_vote) as total_sum_vote')->where('election_type', 1)->where('year', 1)->where('status', 1)->group_by('party_id')->order_by('total_sum_vote', 'desc')->get('election_data')->result_array();
		
		$precident_arr = $precident_details = array();
		
		if(!empty($all_parties)){
			foreach($all_parties as $p=>$party)
			{
				$party_id 		= $party['party_id'];
				$precident_id 		= $party['candidate_id'];
				$total_votes 		= $party['total_sum_vote'];
				$total_percent 	= $party['total_percent'];
				
				$precident_details 	= $this->db->where('status', 1)->where('id', $precident_id)->get('president')->result_array(); 
				
				$party_details 	= $this->db->where('status', 1)->where('id', $party_id)->get('parties')->result_array();
				
				$precident_arr[$p]['precident_details'] = (isset($precident_details[0]) && !empty($precident_details[0])) ? $precident_details[0] : array();
				$precident_arr[$p]['party_details'] 	= (isset($party_details[0]) && !empty($party_details[0])) ? $party_details[0] : array();
				
				//$total_votes 	= $this->db->select('SUM(election_data.total_vote) as total_sum_vote')->where('status', 1)->where('party_id', $party_id)->get('election_data')->result_array();
				
				$precident_arr[$p]['total_votes'] 		= $total_votes;
				$precident_arr[$p]['total_percent'] 	= $total_percent;
			}
		}
		
		$all_ids  = $all_region_details	= array();
		foreach($all_constituencies as $k=>$all_c){
			$region_details 			= $this->db->where('id', $all_c['region_id'])->get('regions')->row();
			
			$election_data 			= $this->Home_model->get_region_result(1, $this->show_year, '', 0, $all_c['region_id'], $all_c['id']);
			$election_data1 			= $this->Home_model->get_region_result(2, $this->show_year_pir, '', 0, $all_c['region_id'], $all_c['id']);
			
			$alltotal_vote_pres=$this->db->select('SUM(election_data.total_vote) as total_sum_vote')->where('constituency_id',$all_c['id'])->where('election_type', 1)->where('year', $this->show_year)->where('status', 1)->where('is_confirmed', 1)->get('election_data')->row()->total_sum_vote;
			
			$details 					=  (isset($region_details->title)) ? ucfirst($region_details->title).' Region' : ucfirst($all_c['details']);
			
			$all_ids[$k]['id'] 			= trim(str_replace('\'', '', $all_c['map_id']));;
			$all_ids[$k]['title'] 		= $all_c['title'];
			//$all_ids[$k]['description']	= 	'<div class="graybox new_show">
			//								<h3>'.$all_c['title'].'</h3>
			//								<p>'.$details.'</p>
			//							</div>';
			$all_ids[$k]['description']	= '';
			$all_ids[$k]['balloonText'] 	= 	'<div class="graybox new_show">
											<h3>'.$all_c['title'].'</h3>
											<p>'.$details.'</p>
										</div>';
			$all_ids[$k]['color'] 		= '#bababa';
			$all_ids[$k]['backgroundAlpha']=0;
			$all_ids[$k]['backgroundColor']='#ffffff';
			
			if(!empty($election_data)){
				$leading_party_id 		= (isset($election_data[0]['party_id'])) ? $election_data[0]['party_id'] : '';
				$leading_total_vote 	= (isset($election_data[0]['total_vote'])) ? $election_data[0]['total_vote'] : '';
				
				$leading_party_details 	= $this->db->where('id', $leading_party_id)->order_by('title', 'asc')->get('parties')->row();
				
				$oponenet_party_id 		= (isset($election_data[1]['party_id'])) ? $election_data[1]['party_id'] : '';
				$oponenet_total_vote 	= (isset($election_data[1]['total_vote'])) ? $election_data[1]['total_vote'] : '';
				
				$oponenet_party_details = $this->db->where('id', $oponenet_party_id)->order_by('title', 'asc')->get('parties')->row();
				
				$html =  '<div class="graybox new_show">
							<h3>'.$all_c['title'].'</h3>
							<p>'.$details.'</p>
							<div class="pir-pres-det">
								<div class="pre-det-sec">
									<p class="hover-box-title">Presidential</p>
									<ul class="vot_ratin_sumry clearfix">';
										if(!empty($election_data)){
											foreach($election_data as $e=>$edata){
												if($e > 2) continue;
												if($alltotal_vote_pres!=0 )
												{
												$percentage=($edata['total_vote']/$alltotal_vote_pres)*100;
												}
												else
												{
													$percentage=0;
												}
												$add_class = ($e == 0) ? '<img src="'.base_url().'assets/site/images/tick.png">' : '';
												$parry_details = $this->db->where('id', $edata['party_id'])->get('parties')->row();
												$html .= '<li>
															<div style=" background-color: '.$parry_details->color.'; display: inline-block; height: 16px; width: 4px; float:left;"></div> 
															<p>'.$parry_details->title.' </p>
															<span style="width:22px;">'.$add_class.'</span>
															<span>'.number_format($percentage,2).'%</span>
														</li>';
											}
										}
					$html 	.=		'</ul>
								</div>
								<div class="pir-det-sec">
									<p class="hover-box-title">Parliamentiary</p>
										<ul class="vot_ratin_sumry clearfix">';
											if(!empty($election_data1)){
												foreach($election_data1 as $e1=>$edata1){
													if($e1 > 2) continue;
													$consti_total=$this->db->select('sum(total_vote) as consti_total')->where('constituency_id', $edata1['constituency_id'])->where('election_type', 2)->where('year', $this->show_year_pir)->where('is_confirmed',1)->get('election_data')->row();
													$consti_all_total= $consti_total->consti_total;
													$percent=$edata1['total_vote'];
													if($consti_all_total!=0 && $percent!=0)
													{ $percentage=($percent/$consti_all_total)*100; }
													else
													{
													$percentage=0.00;
													}
													$add_class1 = ($e1 == 0) ? '<img src="'.base_url().'assets/site/images/tick.png">' : '';
													$parry_details1 = $this->db->where('id', $edata1['party_id'])->get('parties')->row();
													$html .= '<li>
																<div style=" background-color: '.$parry_details1->color.'; display: inline-block; height: 20px; width: 4px; float:left;"></div> 
																<p>'.$parry_details1->title.' </p>
																<span style="width:22px;">'.$add_class1.'</span>
																<span>'.number_format($percentage,2).'%</span>
															</li>';
												}
											}
					$html 	.=		'</ul>
								</div>
							</div>
						</div>';
				
				
				$all_ids[$k]['color']		= $leading_party_details->color;
				$all_ids[$k]['backgroundColor']=$leading_party_details->color;
				$all_ids[$k]['balloonText'] 	= $html;
				//$all_ids[$k]['description']	= $html1;
			}
		}
		$pageId=44;
		$all_advertisement=$this->db->where('status', 1)->order_by('id', 'asc')->get('advertisement_position')->result_array();
		if(!empty($all_advertisement))
		{
		  foreach($all_advertisement as $k=>$row_advertisement)	
			{
			$all_advertisement[$k][$row_advertisement['position']]=$this->db->where('page', $pageId)->where('status', 1)->where('position',$row_advertisement['id'] )->get('advertisement')->result_array();
			
			}
		}
		
		$data['data']['all_advertisement']=$all_advertisement;
		$data['data']['all_precidents']	= $precident_arr;
		$data['data']['all_region_details']= $all_region_details;
		
		$data['data']['news_data'] 		= $this->db->where('status', 1)->order_by('id','desc')->limit(10)->get('news')->result_array(); 
		$data['data']['latest_photos'] 		= $this->db->where('status', 1)->order_by('id','desc')->limit(5)->get('photo')->result_array();
		
		$data['data']['all_ids'] 		= json_encode($all_ids);
		$data['data']['regions']			= $all_regions;
		$data['data']['constituencies']	= $all_constituencies;
		$this->session->set_userdata('site_year',$this->show_year_url);
		$data['data']['sec_year']					= $this->show_year_url;
		$data['data']['settings'] 		= $this->sitesetting_model->get_settings();
		
		$data['view_link'] = 'site/Mapview/index';
		$this->load->view('includes/template_site', $data);
	}
}
?>