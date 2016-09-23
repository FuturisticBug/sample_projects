<?php
	//echo '<pre>'; print_r($view_port); echo '</pre>';
 	function add_service_text($d)
	{
		$CI =& get_instance();
		$CI->load->model('footer_model');
		$table="admin_page_content";
		$id_b_lebel1=$d;
		$lebel1=$CI->footer_model->get_contents($table,$id_b_lebel1);
		return $lebel1;
	}
?>
	<style>
		.highlight_div {
			box-shadow: 4px 4px 0 #533955 , -4px -4px 0 #533955 , 4px -4px 0 #533955 , -4px 4px 0 #533955;
		}
		.cal_div{
			width: 50%;
			background-color: #ccc;
			float: left;
			text-align: center;
			height: auto;
			padding-bottom: 10px;
			padding-top: 5px;
		}
		.hilight_div{
			border: 2px solid #000;
		}
		/*.gmnoprint{
			top: 0 !important;
		}*/
		.gmnoprint img {
			max-width: none; 
		}
		#map_canvas img {max-width:none}
		/*.gmnoprint {visibility: hidden !important;}*/
		.gmnoscreen {visibility: hidden !important;}
		/*.gm-style-mtc {visibility: hidden !important;}*/
		.gm-style-cc {visibility: hidden !important;}
		.pac-logo:after {background-image: none}
		.small-input{
			width:175px;
		}
	</style>
	<script src="<?php echo base_url(); ?>assets/js/jquery.fixer.js" type="text/javascript"></script>
	<?php
		$df_city='';$df_region='';
		if(isset($default_city) && $default_city!=''){ $df_city=$default_city; }
		if(isset($default_region) && $default_region!=''){ $df_region=$default_region; }
		//$request_city = $this->input->get('service_place');
		$CI =& get_instance();
		$CI->load->model('product_details_cat_model');
		$CI->load->model('translate_currency_model');
	
		if(isset($_REQUEST['service_name']) && ($_REQUEST['service_name']!=""))
			$sname=$_REQUEST['service_name'];
		elseif($req_service_name != '')
			$sname = ucfirst($req_service_name);
		else
			$sname="";
			
		if(isset($_REQUEST['service_place']) && ($_REQUEST['service_place']!=""))
			$service_place	= $_REQUEST['service_place'];
		else if($req_service_place!='')
			$service_place	= ucfirst($req_service_place);
		else
			$service_place	= "";
		
		$service_place = str_replace('-', ' ', $service_place);
			
		if(isset($_REQUEST['open_date']) && ($_REQUEST['open_date']!=""))
			$open_date	= $_REQUEST['open_date'];
		else
			$open_date	= date('m-d-Y');
		if(isset($_REQUEST['time_open']) && ($_REQUEST['time_open']!=""))
			$time_open	= $_REQUEST['time_open'];
		else
			$time_open	= "";
		
		$mainsite_crncy		= $this->session->userdata('mainsite_crncy');
		$mainsite_crncy_name	= $CI->product_details_cat_model->get_currency($mainsite_crncy);
		
		if(isset($mainsite_crncy_name[0]->currency_name) && ($mainsite_crncy_name[0]->currency_name!=""))
			$cur_nm	= $mainsite_crncy_name[0]->currency_name;
		else
			$cur_nm	= 'USD';
		
		$price_high	= '';
		$price_low	= '';		
		if(isset($_REQUEST['price_low']) && $_REQUEST['price_low']!='')
			$price_low	= $_REQUEST['price_low'];
?>
	<input type="hidden" name="request_addr" id="request_addr" value="<?php echo $service_place; ?>" />
	<script type="text/javascript">
		$(window).load(function(){
			$('html,body').animate({
				scrollTop: 0
			}, 800);
		});
		
		$(document).ready(function(){
			$(".map-fix").attr('style', 'position: absolute; top: 0px;');
		});
		
		function submit_search_frm()
		{
			$("#main_search").val(1);
			var open_date 	= document.getElementById('open_date').value;
			var time_open 	= document.getElementById('time_open').value;
			var mcp 		= parseInt($('#map_selected_properly').val());
			
			if (mcp == 1) {
				$('#is_all_proper').val(1);
				document.getElementById('err_search_str').innerHTML='';
			}
			else{
				document.getElementById('err_search_str').innerHTML='<?php echo ucfirst(add_service_text(950)); ?>';
				
				$('#is_all_proper').val(0);
				$("#service_place").focus();
			}
		}
	
		function change_page() {
			document.getElementById('page_name').value=document.getElementById('hid_addr').value;
		}
	</script>
	
	<?php
		$url_segment=$this->uri->segment(2);
		$vwcur='USD';
		if(isset($name_curr)) { $vwcur=$name_curr; }	
	?>
	
	<script type="text/javascript">
		function myfunc(lat,long,id,str)
		{
			document.getElementById("div_"+str).className = "hilight_div";
		}
		function myfunc1(lat,long,id,str)
		{
			document.getElementById("div_"+str).className = "p-listing clearfix";
		}
		
		function reset_all_ok() {
			$('#map_selected_properly').val(0);
			$('#is_all_proper').val(0);
		}
		
		function go_to_page(page_no){
			if (page_no != '' && page_no > 0) {
				//$('#is_all_proper').val(1);
				$("#page_no").val(page_no);
				$("#pagination_clicked").val(1);
				$("#search_filter").click();
			}
			
			$('html,body').animate({
				scrollTop: 0
			}, 800);
		}
		
		function service_name_change() {
			$("#search_filter").click();
		}
	</script>
	
	<div id="loading-filter-background" style="display: none;">
		<div id="loading-filter-image">
			<img alt="Spinner" src="<?php echo base_url()."assets/images/loader_free.gif";?>">
		</div>
		<div class="loading-text">Loading</div>
	</div>
	
	<section class="inner-page product-listing">
		<div class="container">
			<form name='search_frm' id='search_frm' action='<?php echo base_url()."product-details-new/search";?>' method='get'>
				<input type="hidden" id="post_cat_id" name="post_cat_id" value="<?php echo $category; ?>">
				<input type="hidden" id="post_key_id" name="post_key_id" value="">
				<input type="hidden" name="is_first" id="is_first" value="1" >
				
				<input type="hidden" name="is_all_proper" id="is_all_proper" value="0" >
				<input type="hidden" name="current_address" id="current_address" value="<?php echo $service_place; ?>" >
				
				<input type="hidden" name="pagination_clicked" id="pagination_clicked" value="0" >
				
				<input type="hidden" name="administrative_levels" id="administrative_levels" value="<?php echo (isset($_REQUEST['administrative_levels'])) 	? $_REQUEST['administrative_levels'] 	: '1' ?>" />
				
				<input type="hidden" name="srch_lat" id="srch_lat" value="<?php echo (isset($lat)) 	? $lat 	: '' ?>" />
				<input type="hidden" name="srch_lon" id="srch_lon" value="<?php echo (isset($long)) 	? $long 	: '' ?>" />
				
				<input type="hidden" name="sw_lat" id="sw_lat" value="<?php echo (isset($view_port['sw_lat'])) ? $view_port['sw_lat'] : '' ?>" />
				<input type="hidden" name="sw_lng" id="sw_lng" value="<?php echo (isset($view_port['sw_lng'])) ? $view_port['sw_lng'] : '' ?>" />
				<input type="hidden" name="ne_lat" id="ne_lat" value="<?php echo (isset($view_port['ne_lat'])) ? $view_port['ne_lat'] : '' ?>" />
				<input type="hidden" name="ne_lng" id="ne_lng" value="<?php echo (isset($view_port['ne_lng'])) ? $view_port['ne_lng'] : '' ?>" />
				
				<input type="hidden" name="srch_lat_new" id="srch_lat_new" 	value="" />
				<input type="hidden" name="srch_lon_new" id="srch_lon_new" 	value="" />
				
				<input type="hidden" name="sw_lat_new" id="sw_lat_new" 	value="" />
				<input type="hidden" name="sw_lng_new" id="sw_lng_new" 	value="" />
				<input type="hidden" name="ne_lat_new" id="ne_lat_new" 	value="" />
				<input type="hidden" name="ne_lng_new" id="ne_lng_new" 	value="" />
				
				<input type="hidden" name="main_search" id="main_search" value="1" />
				
				<input type="hidden" name="is_searched" id="is_searched" 	value="<?php echo (isset($is_searched)) ? $is_searched : '' ?>" />
				<input type="hidden" name="map_selected_properly" 		id="map_selected_properly" 		value="<?php echo (!empty($service_place)) ? 1 : 0 ?>"  />
				
				<div class="purple-form-sec">
					<h3><?php echo ucfirst(add_service_text(851)); ?></h3>
					<div class="home-form-area clearfix">
						<div class="big-input">
							<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
							<input type="text" placeholder="<?php echo ucfirst(add_service_text(178)); ?>"  name='service_name' id='service_name' value='<?php echo $sname; ?>'>
						</div>
						<div class="big-input">
							<span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span>
							<input type="text"  onFocus="/*geolocate()*/"  placeholder="<?php echo ucfirst(add_service_text(179)); ?>" onkeyup="reset_all_ok()" name='service_place' id='service_place' value='<?php echo $service_place; ?>'>
						</div>
						
						<div class="small-input">
							<div class="input-append input-group date">
								<input type="text" placeholder="<?php echo ucfirst(add_service_text(763)); ?>" name='open_date' id='open_date' value='<?php echo $open_date; ?>'>
								<script type="text/javascript">
									// When the document is ready
									$('#open_date').datepicker({
										dateFormat: "mm-dd-yy",
										minDate: 'today',
										
										onSelect: function(selected)
										{
											$('#time_open').datepicker('setDate', null);
											if (selected!='')
												document.getElementById('time_open').disabled = false;
											else
												document.getElementById('time_open').disabled = true;
											
											var dt = new Date(selected);
											//dt.setDate(dt.getDate()+1);
											dt.setDate(dt.getDate());
											$("#time_open").datepicker("option", "minDate", dt);
										}
									});
								</script>
								<span class="input-group-addon add-on">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
						
						<div class="small-input">
							<div class="input-append input-group date">
								<input disabled type="text" placeholder="<?php echo ucfirst(add_service_text(764)); ?>" name='time_open' id='time_open' value='<?php echo $time_open; ?>'>
								<script type="text/javascript">
									// When the document is ready
									$('#time_open').datepicker({
										dateFormat: "mm-dd-yy",
										minDate: 'today'
									});
								</script>
								<span class="input-group-addon add-on">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
								<!--<span aria-hidden="true" class="glyphicon glyphicon-chevron-down"></span>-->
							</div>
						</div>
						
						<script>
							$("#open_date").keyup(function(){
								var open_date=document.getElementById('open_date').value;
								if (open_date!='') {
									document.getElementById('time_open').disabled = false;
								}else{
									document.getElementById('time_open').disabled = true;
									$('#time_open').datepicker('setDate', null);
								}	
							});
							
							$( document ).ready(function() {
								var check_in=document.getElementById('time_open').value;
								if (check_in!='')
									document.getElementById('time_open').disabled = false;
								else{
									document.getElementById('time_open').disabled = true;
									$('#time_open').datepicker('setDate', null);
								}	
							});
						</script>
						
						<div class="small-input btn-ara">
							<button class="btn" onclick='submit_search_frm();' type="button" id="search_filter">
								<?php echo ucfirst(add_service_text(505)); ?>
								<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
							</button>
						</div>
						<span id="err_search_str" class="srch_alrt_text" ></span>
					</div>
					
					<div style="" class="home-form-area home_new_outr clearfix second-sort" >
						<div class="pr-range">	
							<p style="text-align: left;">
								<label style="font-weight: 500;" for="amount"><?php echo ucfirst(add_service_text(888)); ?>:</label>
								<input type="text" id="amount" readonly style="border:0; color:#533955; font-weight:bold;font-size: 13px;">
							</p>
						</div>	
						<div style="height: 0.5em;" class="pr-rangediv" id="slider-range"></div>
						
						<div class="sort-cat">
							<div class="small-input">		
								<div class="date-pick added-timedrop">
									<select onchange="cat_change();" name='category_sort' id='category_sort' class="selectpicker">
										<option value=""><?php echo ucfirst(add_service_text(1299)).' '.ucfirst(add_service_text(91)); ?></option>
										<?php
											//asort($p_cats);
											if(isset($p_cats) && count($p_cats)>0) {
												foreach($p_cats as $val)
												{								
													$selected_cat='';
													if(isset($category) && $category!=''){ $selected_cat=$category; }
													else if($this->input->get('post_cat_id')!=''){ $selected_cat=$this->input->get('post_cat_id'); }
													
													if($val['cat_name'] != ''){
												?>
														<option <?php if($selected_cat==$val['id']){ echo "selected"; }?> value="<?php echo $val['id']; ?>"><?php echo ucfirst(trim($val['cat_name'])); ?></option>
										<?php
													}
												}
											}  
										?>
									</select>
								</div>		
							</div>

							<div class="small-input">		
								<div class="date-pick added-timedrop">
									<select onchange="key_change();" name='keyword_sort' id='keyword_sort' class="selectpicker">
										<option value=""><?php echo ucfirst(add_service_text(1299)).' '.ucfirst(add_service_text(305)); ?></option>
										<?php
											//$key_details=asort($key_details);
											if(isset($key_details) && count($key_details)>0) {
												foreach($key_details as $val_key)
												{	
												?>
													<option value="<?php echo $val_key['id']; ?>" <?php if($keyword_id==$val_key['id']){ echo "selected"; } ?> ><?php echo ucfirst($val_key['name']); ?></option>
										<?php    }
											}  
										?>
									</select>
								</div>		
							</div>
						</div>
					</div>
					
					<input type="hidden" id="page_no" name="page_no" value="1">
					<input type="hidden" id="price_high" name="price_high" value="" >
					<input type="hidden" id="price_low" name="price_low"   value=""  >
				</div>
			</form>
			
			<script>
				function cat_change()
				{
					var cat = document.getElementById('category_sort').value;
					document.getElementById('post_cat_id').value		= cat;
					//$("#main_search").val(1);
					//$("#search_filter").click();
				}
				
				function key_change()
				{
					var key = document.getElementById('keyword_sort').value;
					document.getElementById('post_key_id').value		= key;
					//$("#main_search").val(1);
					//$("#search_filter").click();
				}
			</script>
		</div>
		<?php
			$set_perpage=10;
			if(isset($settings[0]['site_pagination']) && $settings[0]['site_pagination']!='')
				$set_perpage=$settings[0]['site_pagination'];
		?>
		<script>
			var timerStart = Date.now();
			var endtime;
			
			function make_wishlist(id, user_id, pos){
				var url = '<?php echo base_url() ?>product_details_control/wishlist_add_ajax/'+id+'/'+user_id;
				$.ajax({url: url, success: function(result){
					var res  = result.split("|@|"),
					insrt_id = parseInt(res[0]);
					
					if (res[1] == 1){
						$('#wish_'+pos).attr('href', 'javascript:remove_wishlist('+insrt_id+', '+id+', '+user_id+', '+pos+')');
						$('#wish_'+pos).html('<img src="<?php echo base_url(); ?>assets/images/hrt_pic2.png">');
					}
					else
						alert('Try again');
				}});
			}
			
			function remove_wishlist(id, sid, uid, pos){
				var url = '<?php echo base_url() ?>wishlist_control/wishlist_remove_ajax/'+id;
				$.ajax({url: url, success: function(result){
					if (result == 1){
						$('#wish_'+pos).attr('href', 'javascript:make_wishlist('+sid+', '+uid+', '+pos+')');
						$('#wish_'+pos).html('<img src="<?php echo base_url(); ?>assets/images/hrt_pic.png">');
					}
					else
						alert('Try again');
				}});
			}
			
		</script>
		
		<style>
			.map-marker{
				border: none;
				cursor: pointer;
				background-color: #ff5a5f;
				padding: 3px 10px;
				color: #fff;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 400;
				-webkit-box-shadow: 0 0 0 1px rgba(0,0,0,0.3);
				-moz-box-shadow: 0 0 0 1px rgba(0,0,0,0.3);
				box-shadow: 0 0 0 1px rgba(0,0,0,0.3);
			}
			.map-marker.highlight{
				background-color: #007a87;
				border: none;
			}
			.map-marker.active {
				background: #2d0b2f;
				z-index: 9999 !important;
				}
			/*.map-marker:before {
				bottom: -6px;
				margin-left: -6px;
				border-width: 6px;
				border-top-color: rgba(0,0,0,0.3);
				content: "";
				display: inline-block;
				position: absolute;
				bottom: -10px;
				left: 50%;
				margin-left: -10px;
				top: auto;
				border: 10px solid transparent;
				border-bottom: 0;
				border-top-color: rgba(0,0,0,0.1);
			}*/
		</style>
		
		<script src="<?php echo base_url() ?>assets/js/new_map/js/jquery.bxslider.min.js"></script>
		<link href="<?php echo base_url() ?>assets/js/new_map/css/jquery.bxslider.css" rel="stylesheet" />
		
		<div class="product-listing-area">
			<div class="container relate-map">
				<div class="row">
					<div class="col-sm-5">
						<div id="div_list" class="p-listing-outer">
							<div id="ms-listings"></div>
							<div id="ms-pagination" class="dataTables_paginate paging_bootstrap pagination" style="margin: 0;"></div>
						</div>
					</div>
					<div id="mapjsdiv" ></div>
					<div class="col-sm-7 map-fix" style="position: absolute; top: 0px;">
						<div class="map-p-fix map">
							<div class="listing-map-area" id="map-canvas">
								
							</div>
						</div>
					</div>
						
					<link href="<?php echo base_url() ?>assets/js/new_map/css/starter-template.css" rel="stylesheet">
					<link href="<?php echo base_url() ?>assets/js/new_map/css/ms-style.css" rel="stylesheet">
					<?php
						$CI =& get_instance();
						$CI->load->model('sitesetting_model');
						
						$site_settings = $CI->sitesetting_model->get_settings();
						
						if(isset($site_settings[0]['google_map_api_key']) && !empty($site_settings[0]['google_map_api_key']))
							echo '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key='.$site_settings[0]['google_map_api_key'].'&libraries=places"></script>';
						else
							echo '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>';
					?>
					<script src="<?php echo base_url() ?>assets/js/new_map/js/markerwithlabel.js"></script>
					<script type="text/javascript" src="<?php echo base_url() ?>assets/js/new_map/js/jquery.mapSearch.min.js"></script>
					<script>
						
						function string_escape(args) {
							try{
								fixedstring=decodeURIComponent(escape(args));
							}catch(e){
								fixedstring=args;
							}
							
							return fixedstring;
						}
						
						var search_map;
						var page_no 		= document.getElementById('page_no').value;;
						document.getElementById("loading-filter-background").style.display = "block";
						var uri_one 		= "search";
						var pagelim 		= "<?php echo $set_perpage;  ?>";
						var imp_servids 	= "<?php //echo $imp_servids ?>";
						
						var service_name=document.getElementById('service_name').value;
						var service_place=document.getElementById('service_place').value;
						var open_date	= document.getElementById('open_date').value;
						var time_open	= document.getElementById('time_open').value;
						//var Cat_Id="<?php echo $this->input->get('post_cat_id')  ?>";
						var Cat_Id	= document.getElementById('post_cat_id').value;
						var Key_Id	= document.getElementById('post_key_id').value;
						
						var price_low 	= document.getElementById('price_low').value;
						var price_high	= document.getElementById('price_high').value;
						
						var srch_lon	= document.getElementById('srch_lon').value;
						var srch_lat	= document.getElementById('srch_lat').value;
						
						var is_first	= document.getElementById('is_first').value;
						
						var sw_lat 	= document.getElementById('sw_lat').value;
						var sw_lng 	= document.getElementById('sw_lng').value;
						var ne_lat 	= document.getElementById('ne_lat').value;
						var ne_lng 	= document.getElementById('ne_lng').value;
						
						var main_search = document.getElementById('main_search').value;
						
						var is_searched = document.getElementById('is_searched').value;;
						
						if (service_place.search(/\S/) != -1) {
							service_place = service_place.replace(/\s/g,"-");
						}
						
						
						
						var ValueToPass = "service_place="+service_place+ "&service_name="+service_name+ "&open_date="+open_date+"&time_open="+time_open+ "&cat_id="+Cat_Id+ "&key_id=" +Key_Id+ "&pageno="+page_no + "&perpage="+pagelim + "&imp_servids="+imp_servids+ "&price_low="+price_low+ "&price_high="+price_high+ "&srch_lat="+srch_lat+ "&srch_lon="+srch_lon+ "&is_first="+is_first+'&sw_lat='+sw_lat+'&sw_lng='+sw_lng+'&ne_lat='+ne_lat+'&ne_lng='+ne_lng+'&main_search='+main_search;
						
						var ValueToPass_arr = {'service_place': service_place, 'service_name': service_name, 'open_date': open_date, 'time_open': time_open, 'cat_id': Cat_Id, 'key_id' : Key_Id, 'pageno': page_no, 'perpage': pagelim, 'imp_servids': imp_servids, 'price_low': price_low, 'price_high': price_high, 'srch_lat': srch_lat, 'srch_lon': srch_lon, 'is_first': is_first, 'main_search' : main_search};
						
						//console.log('arijit: '+ValueToPass);
						//console.log('arijit: '+ValueToPass_arr.toString());
						
						$(window).load(function(){
							//$('#service_place').focus();
							//console.log('listing');
						//(function ( $ ) {
							$('#map-canvas').mapSearch({
								initialPosition: [<?php echo $lat ?>, <?php echo $long ?>],
								//initialPosition:[40, -100],
								zoom: 10,
								//request_uri : 'http://sbadb.herokuapp.com/v1/bizs',
								request_uri : '<?php echo base_url(); ?>thelist_service_new',
								valueto_pass: ValueToPass,
								values_arr: ValueToPass_arr,
								is_searched: is_searched,
								is_searched1: 0,
								filters_form : '#search_filter',
								loading_class: '#loading-filter-background',
								search_box : true,
								listing_template : function(listing){
									
									
									var price   		= (listing.price == '0') ? '<strong>Free</strong>' : '<strong>'+listing.currency+'</strong> &nbsp;'+listing.price;
									
									var new_ins_icon = (listing.instant_book == 'yes') ? '<span class="h3 icon-beach hide-md"><i class="fa fa-bolt"></i></span>' : '';
									
									if (listing.service_option) {
										var end_part= '<div class="daytime-area clearfix" style="margin-top: 10px; width:100%;">'
										+		'<div class="day-sec"><span>'+listing.product_date+'</span></div>'
										+			'<div class="time-sec clearfix">'
										+				decodeURIComponent((listing.service_option+'').replace(/\+/g, '%20'))
										+		'</div>'
										+	      '</div>'
										if (listing.product_available!="") {
											end_part += '<div class="cost-sec clearfix">'+listing.product_available+'</div>';
										}
										
										
									}else{
										var end_part= '<div class="daytime-area clearfix" style="margin-top: 10px; width:100%;">'
										+		'<div class="day-sec"><span>'+listing.product_date+'</span></div>'
										+			'<div class="day-sec cost-sec clearfix">'
										+				listing.product_available
										+		'</div>'
										+	     '</div>'
									}
									
									//alert(end_part);
									
									return '<div id='+listing.id+' item='+listing.id+'>'
										+	 '<div class="p-img imgfix-cen listing_slider_default"  style="position: relative"><a href="'+listing.link+'"><img class="only_desktop" src="'+listing.image+'" alt="poduct-image"></a>'+decodeURIComponent((listing.user_wish_img+'').replace(/\+/g, '%20'))+'</div>'
										+	 '<div class="p-img imgfix-cen listing_slider"  style="display: none">'
										+		'<ul class="bxslider bxslider_list">'+decodeURIComponent((listing.product_images_new+'').replace(/\+/g, '%20'))+'</ul>'
										+ 	 '</div>'
										
										
										+	'<div class="product-r-side">'
												+	 '<div class="p-right">'
										+		'<a title="'+string_escape(listing.name)+'" target="_blank" href="'+listing.link+'"><h4>'+string_escape(listing.name)+ '</h4></a>'
										+		'<p>'+listing.option_detail+'</p>'
										+		'<div class="estate-second-line"> <span><?php echo ucfirst(add_service_text(627));?>: </span>'+string_escape(listing.category) +'</div>'
										+		'<div class="location-sec">'+string_escape(listing.address)+'</div>'
										+	 '</div>'
										+	 '<div class="avt-img"><a title="'+string_escape(listing.name)+'" href="'+listing.user_link+'"><img src="'+listing.user_image+'" alt="user-image"></a></div>'
										+	 '<div class="cost-sec clearfix">'
										+		'<a title="'+string_escape(listing.name)+'" href="'+listing.link+'"><div class="cost-area"><span>'+price+''+new_ins_icon+'</div></a>'
										+		'<div class="cost-stars-area">'
										+			'<div class="demo-table">'
										+				'<div id="tutorial23">'
										+					'<input type="hidden" name="rating" id="rating'+listing.id+'" value="'+listing.rating_val+'" />'
										+					decodeURIComponent((listing.rating+'').replace(/\+/g, '%20'))
										+				'</div>'
										+			'</div>'
										+		'</div>'
										+	 '</div>'
										+        end_part
										+	'</div>'
										+ '</div>'
										
										
								},
								
							});
							
							
							
						});
						
						//}( jQuery ));
						
					</script>
						
					<input type="hidden" id="post_page_no" value="2">
					
					<?php
						$the_idsarr=array();	
					
						if((!empty($service_details)) &&(count($service_details)>0))
							$i=0;
					?>
					<div  style="display: none" class="loder_cell" id="loder_cell">
						<img src="<?php echo base_url(); ?>assets/images/loader-cell.gif">
					</div>
				</div>
			</div>
		</div>
		<input type="hidden"  name="the_idsarr" id="the_idsarr" value="<?php echo implode(",",$the_idsarr); ?>" >
		<input type="hidden"  name="bythedistance" id="bythedistance" value="" >
	</section>
	
	<script>
		$('.map-fix').fixer({
			gap: 0
		});
		
		//$(window).load(function(){
		//	
		//});
		
	</script>