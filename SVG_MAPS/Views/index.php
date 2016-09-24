	<link rel="stylesheet" href="<?php echo base_url() ?>assets/js/svg_maps/ammap_new.css" type="text/css">
	<script src="<?php echo base_url() ?>assets/js/svg_maps/ammaps_new.js" type="text/javascript"></script>
	<script src="<?php echo base_url() ?>assets/js/svg_maps/ghanaLow_test.js" type="text/javascript"></script>
	<script src="<?php echo base_url() ?>assets/js/svg_maps/responsive.js" type="text/javascript"></script>
	
	<script>
		//var constituencies_arr = '<?php //echo $all_ids; ?>';
		
		var map = AmCharts.makeChart("mapdiv", {
			type: "map",
			colorSteps: 10,
			dataProvider: {
				map: "ghanaLow",
				areas:<?php echo $all_ids; ?>
			},
			borderColor: '#ffffff',
			borderAlpha: '0',
			backgroundColor: '#ffffff',
			backgroundAlpha: '0',
			areasSettings: {
				autoZoom: true,
				rollOverOutlineColor: "",
				selectedColor: "#BBBB00",
				color: "#BBBB00"
			},
			responsive: {
				enabled: true
			},
			linesSettings: {
				color: "#CC0000",
				alpha: 0.4
			},
			balloon: {
				disableMouseEvents: false,
				adjustBorderColor: false,
				borderAlpha: 1,
				borderColor: '#222222',
				borderThickness: 1,
				verticalPadding: 1,
				horizontalPadding: 1,
				shadowAlpha: 0,
				fillAlpha: 1,
				fontSize: '15px',
				textAlign: 'left'
			}
		});
		
		map.addListener("rendered", function (event) {
			var info = map.getDevInfo();
			//console.log(info.toString());
			$("#map_container").attr('style', 'width: 100%; height: 100%;');
		});
		
		map.addListener("clickMapObject", function (event) {
			var info = map.getDevInfo();
			console.log('map id: '+event.mapObject.id);
			var obj_color = event.mapObject.color
			
			// update US color in data
			var area = map.getObjectById(event.mapObject.id);
			area.color = obj_color;
			area.colorReal = area.color;
			
			// make the chart take in new color
			map.returnInitialColor(area);
		});
	</script>
	
	<div id="style_add"></div>
	<!-- /.container -->	
	<div class="container">
		<?php
			if(isset($all_advertisement[0]['TOP'][0]['link']))
			{ ?>
			
			<div class="presidential_banner only_mobile">
			  <div class="inline text-center">
			 <?php echo $all_advertisement[0]['TOP'][0]['link'];?>
			
			  </div>
			  
			</div>
			
			<?php  }
			?>
		
		<ul class="bredcrumb">
		
			
			<li><a href="<?php echo base_url(); ?>">Home</a></li>
			
			<li><a href="javascript:void(0)"><?php echo $sec_year ?></a></li>
            <li class="active"><a href="javascript:void(0)">Map</a></li>
		</ul>
		
		<div class="row">
			
			<div class="col-sm-8 col-xs-8 ban">
				<h3 class="main-heading"><?php echo "Election Ghana ".$sec_year; ?></h3>
				<div class="leftpanel">
					<!-- banner slider-->
					<!--result section start-->
					<section class="location ">      <!--location section start-->
						<div class="">
							<div id="map_container" style="width: 450px; height: 1000px; padding: 0;"><div class="graybox" id="mapdiv" style="width: 100%; height: 100%;"></div></div>
						</div>
					</section>
					<?php
			            if(isset($all_advertisement[4]['BOTTOM-LEFT'][0]['link']))
			            { ?>
					<section class="banner">
						<div class="result_bnr">
							<?php echo $all_advertisement[4]['BOTTOM-LEFT'][0]['link'];?>
						</div>
						
					</section>
					<?php
						}
					?>
					<!--location section end-->
				</div>
			</div>
			 
			<div class="col-sm-4 col-xs-4 ban">
				<div class="rightpanel clearfix">
					<?php
			            if(isset($all_advertisement[2]['TOP-RIGHT'][0]['link']))
			            { ?>
					<section class="ad_section graybox">
						<?php echo $all_advertisement[2]['TOP-RIGHT'][0]['link']; ?>
					</section>
					
					   <?php
						}
						?>
						
					<section class="tab graybox clearfix">
						<div class="tab_nav_outer">
							<ul class="tab_navbar clearfix">
								<li><a data-toggle="tab" href="#sectionN"><span>Latest News</span></a></li>
								<li class="active"><a data-toggle="tab" href="#sectionP"><span>Latest Photos</span></a></li>
							</ul>
						</div>
						   
						<div class="tab-content tab_cont_outer">
							<div id="sectionN" role="tabpanel" class="tab-pane">
								<div class="region_side">
									<ul class="regn_list">
										<?php
											if(!empty($news_data)){
												foreach($news_data as $k=>$news)
												 {   $i=$k+1;
													$news_link 	= ($news['link']) ? $news['link'] : '';
												   $content 	= (strlen($news['title']) > 30 ) ? mb_substr($news['title'], 0, 35, 'Utf-8').'...' : $news['title'];
													echo '<li><span class="list_no">'.$i.'</span><a target="_blank" href="'.$news_link.'">'.ucfirst($content).'more</a></li>';
												 }
											}
										?>
									</ul>
								</div>
							</div>	
							<div id="sectionP" role="tabpanel" class="tab-pane active" >
								
								<div class="latest_photo">
								   <?php
								   if(!empty($latest_photos))
								   {
								        foreach($latest_photos as $k=>$latest_photo)
										{ ?>
												<div class="lp_box clrearfix">
													 <div class="lp_pic" ><?php echo $latest_photo['image'];?></div> 
													 <div class="lp_para">
														 <a href="<?php echo $latest_photo['link'];?>" target="_blank">
														 <?php
														 //echo $latest_photo['title'];
														echo (strlen($latest_photo['title']) > 25 ) ? ucfirst(mb_substr($latest_photo['title'], 0, 22, 'Utf-8')).'...' : ucfirst($latest_photo['title']);
														 ?>
														 </a>
														 <p>
														<?php
														echo (strlen($latest_photo['details']) > 30 ) ? ucfirst(mb_substr($latest_photo['details'], 0, 35, 'Utf-8')).'...' : ucfirst($latest_photo['details']);
														
														?>
														 </p>
													 </div>
												 </div>
									   <?php
									   }
								   }
								   ?>
								</div>
							</div>
						</div>
					</section>
						
					<section class="tab graybox clearfix">
						<div class="tab_nav_outer">
							<ul class="tab_navbar clearfix">
								<li class="active"><a data-toggle="tab" href="#sectionA"><span>Regions</span></a></li>
								<li><a data-toggle="tab" href="#sectionB"><span>Constituency</span></a></li>
							</ul>
						</div>
						   
						<div class="tab-content tab_cont_outer">
							<div id="sectionA" role="tabpanel" class="tab-pane active">
								<div class="region_side">
									<ul class="regn_list">
										<?php
											if(!empty($regions)){
												foreach($regions as $k=>$region)
												{  $i=$k+1;
													echo '<li><span class="list_no">'.$i.'</span><a href="'.$this->Url_generator_model->url_generate($region['title'], '', '-', 'presidential/'.$sec_year.'/'.'region', $region['id'],'regions','alias_url').'">'.ucfirst($region['title']).'</a></li>';
												}
											}
										?>
									</ul>
								</div>
							</div>	
							<div id="sectionB" role="tabpanel" class="tab-pane">
								
								<ul class="regn_list">
									<?php
										if(!empty($constituencies)){
											foreach($constituencies as $k=>$constituency)
											{    $i=$k+1;
												echo '<li><span class="list_no">'.$i.'</span><a href="'.$this->Url_generator_model->url_generate($constituency['title'], '', '-', 'constituency-details/'.$sec_year, $constituency['id'],'constituencies','alias_url').'">'.ucfirst($constituency['title']).'</a></li>';
											}
										}
									?>
								</ul>
							</div>
						</div>
					</section>
					
				</div>
			</div>
		</div>
	</div>
