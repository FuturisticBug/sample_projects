	<?php
		$default_site_logo 	= (isset($settings[0]['site_logo'])) ? $settings[0]['site_logo'] : 'logo.png';
		$success 					= $error = $pay_amount = '';
		$stripe_type 				= (isset($settings['stripe_pay_type'])) ? $settings['stripe_pay_type'] : 2;
		$stripe_secret_key 			= $stripe_public_key = $user_stripe_id = $user_stripe_card_id = $user_card_status = $card_user_name = '';
		$card_last_digits			= $card_brand = $exp_year = $exp_month = $cvv_code = ''; $user_has_acard = 0;
		
		if($stripe_type == 1){
			$stripe_secret_key 		= (isset($settings['stripe_live_secret_key'])) ? $settings['stripe_live_secret_key'] : '';
			$stripe_public_key 		= (isset($settings['stripe_live_public_key'])) ? $settings['stripe_live_public_key'] : '';
		}
		else{
			$stripe_secret_key 		= (isset($settings['stripe_sandbox_secret_key'])) ? $settings['stripe_sandbox_secret_key'] : '';
			$stripe_public_key 		= (isset($settings['stripe_sandbox_public_key'])) ? $settings['stripe_sandbox_public_key'] : '';
		}
		
		$card_user_name = $user_stripe_id = $user_stripe_card_id = $user_card_status = $card_last_digits = $card_brand = $exp_year = $exp_month = $cvv_code = '';
		$user_has_acard = 0;
		
		if(!empty($user_stripe_data)){
			$card_user_name 		= (isset($user_stripe_data['name_on_card'])) 	? $user_stripe_data['name_on_card'] 	: '';
			$user_stripe_id		= (isset($user_stripe_data['stripe_id'])) 		? $user_stripe_data['stripe_id'] 		: '';
			$user_stripe_card_id	= (isset($user_stripe_data['card_id'])) 		? $user_stripe_data['card_id'] 		: '';
			$user_card_status		= (isset($user_stripe_data['card_status'])) 		? $user_stripe_data['card_status'] 	: '';
			$user_has_acard		= 1;
			
			$card_last_digits		= (isset($user_stripe_data['card_last_digits'])) 	? '********'.$user_stripe_data['card_last_digits'] : '';
			$card_brand			= (isset($user_stripe_data['card_brand'])) 		? $user_stripe_data['card_brand'] 		: '';
			$exp_year				= (isset($user_stripe_data['exp_year'])) 		? $user_stripe_data['exp_year'] 		: '';
			$exp_month			= (isset($user_stripe_data['exp_month'])) 		? $user_stripe_data['exp_month'] 		: '';
			$cvv_code				= (isset($user_stripe_data['cvv_code'])) 		? $user_stripe_data['cvv_code'] 		: '';
		}
	
		$flash_message 	= $this->session->flashdata('flash_message');
		$flash_message_cont = $this->session->flashdata('flash_message_cont');
	?>
	
	<script> var flash_msg = '<?php echo $flash_message ?>', error_msg = '', flash_message_cont = '<?php echo $flash_message_cont ?>'; </script>
	
	<!--Includng all necessary css and js for map-->
	<link href="<?php echo assets_url('site/map/css/ms-style.css') ?>" rel="stylesheet">
	<link href="<?php echo assets_url('site/css/jquery.mCustomScrollbar.min.css') ?>" rel="stylesheet">
	
	<?php
		//Google api Key is important and we are using the key stored in database
		if(isset($settings['google_map_api_key']) && !empty($settings['google_map_api_key']))
			echo '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key='.$settings['google_map_api_key'].'&libraries=places"></script>';
		else
			echo '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>';
	?>
	<script type="text/javascript" src="<?php echo assets_url('site/map/js/CustomGoogleMapMarker.js') ?>"></script>
	<script type="text/javascript" src="<?php echo assets_url('site/map/js/jquery.mapSearch.min.js') ?>"></script>
	<script type="text/javascript" src="<?php echo assets_url('site/js/jquery.mCustomScrollbar.min.js') ?>"></script>
	<script type="text/javascript" src="<?php echo assets_url('site/js/jquery.validate.min.js') ?>"></script>

	<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
	<script type="text/javascript" src="<?php echo assets_url('site/js/bootstrapValidator-min.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo assets_url('site/js/jquery.creditCardValidator.js'); ?>"></script>
	
	<!--Includng all necessary css and js for map-->
	<script>
		
		var  job_det 			= {},
			job_quote_leg_det 	= {},
			user_det 			= {},
			user_id 			= '',
			user_name 		= '',
			user_image 		= '',
			job_quotes 		= '',
			job_prices 		= '',
			job_prices_arr		= '',
			job_quote_dates 	= '',
			quote_user_det 	= '',
			quote_user_rating 	= '',
			job_prices_extra 	= '',
			job_prices_extra_arr= '',
			job_total_prices 	= '',
			job_total_prices_arr= '',
			all_job_quotes_html = '',
			all_job_legs_html 	= '',
			all_quote_ids 		= [],
			only_legs 		= 0;
			
		var  card_user_name 	= '<?php echo $card_user_name; ?>',
			user_stripe_id		= '<?php echo $user_stripe_id; ?>',
			user_stripe_card_id	= '<?php echo $user_stripe_card_id; ?>',
			user_card_status	= '<?php echo $user_card_status; ?>',
			user_has_acard 	= '<?php echo $user_has_acard; ?>',
			card_last_digits 	= '<?php echo $card_last_digits; ?>',
			card_brand		= '<?php echo $card_brand; ?>',
			exp_year			= '<?php echo $exp_year; ?>',
			exp_month			= '<?php echo $exp_month; ?>',
			cvv_code			= '<?php echo $cvv_code; ?>';
	</script>
	<script type="text/javascript" src="<?php echo assets_url('site/js/pages/dashboard-index.js'); ?>"></script>
	
	<script>
		// this identifies your website in the createToken call below
		Stripe.setPublishableKey('<?php echo $stripe_public_key; ?>');

		function stripeResponseHandler(status, response) {
			//console.log('arijit check valid');
			
			if (response.error) {
				var  error_type 	= response.error.type,
					error_message 	= response.error.message;
				
				$("#payment-card-error").html(error_message);
				$('#submit-pay').removeAttr("disabled");
				$("#pay_loading").hide();
				
			} else {
				$('#submit-pay').attr("disabled");
				var form$ = $("#payment-form");
				// token contains id, last4, and card type
				var token = response['id'];
				// insert the token into the form so it gets submitted to the server
				form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
				// and submit
				form$.get(0).submit();
			}
		}
		
		function close_overlay(args) {
			$(".overlay-popup").hide();
			$('body').removeClass('info-popup-active');
		}
	</script>
	
	
	<!--Left menu section-->
	<div class="sidebar-menu">
		<?php
			if(!empty($site_logo))
				echo '<a data-ajax="false" href="'.base_url().'" class="logo"><img src="'.assets_url('uploads/merchant_images/thumb/'.$site_logo).'" alt="logo" /></a>';
			else
				echo '<a data-ajax="false" href="'.base_url().'" class="logo"><img src="'.assets_url('site/images/'.$default_site_logo).'" alt="logo" /></a>';
		?>
		<ul>
			<?php
				if(!empty($users_all_menus))
				{
					foreach($users_all_menus as $menu)
						echo '<li><a href="'.base_url().$menu['url'].'" data-ajax="false">'.$menu['title'].'</a></li>';
				}
			?>
		</ul>
	</div>
	<div class="menu-overlay"></div>
	<!--Left menu section-->
	
	<!--Default page loader section-->
	<div id="loading-filter-background" style="display: none;">
		<div id="loading-filter-image">
			<i class="fa fa-refresh fa-spin" aria-hidden="true"></i>
		</div>
		<div class="loading-text">Loading</div>
	</div>

	<!-- dashboard screen -->
	<div data-role="page" id="signupPage" class="main-page">
		<div data-role="main" class="ui-content login-content">
			<div class="close-container help-info-popup overlay-popup">
				<div class="close-content">
					<h3 id="info_title">Infotmation</h3>
					<div class="close-para">
						<p id="info_content">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
					</div>
					<div class="close-footer">
						<a href="javascript:void(0)" onclick="close_overlay('overlay-popup')" class="close-btn-right">Close</a>
					</div>
				</div>
			</div>
			
			<div id="popup_cont">
				<div class="close-container overflow_content">
					<div class="close-content transparent">
						<div class="popup-wrap">
							<input type="hidden" name="now_show" id="now_show" value="" />
							<div class="close-footer">
								<a href="javascript:void(0)" class="close-btn popup-close"><img src="<?php echo assets_url('site/images/cross.png') ?>" alt="cross"></a>
							</div>
							<div class="user-top" id="user_img">
								<div class="user-img"><?php echo '<img id="job-user-image" src="'.assets_url('site/images/user-image.png').'" alt="user-img">'; ?></div>
								<h3 id="job-user-name"></h3>
							</div>
							<div class="popup-form" id="loading_content">
								<div class="loading-class">
									<i class="fa fa-refresh fa-spin" aria-hidden="true"></i>
								</div>
								<div class="loading-text-new">Loading</div>
							</div>
							<div class="popup-form hide" id="main_cont">
								
							</div>
							
							<div class="popup-form hide" id="leg_cont">
								<form name="leg_job_form" id="leg_job_form" data-ajax="false" action="" method="post">
									
									<input type="hidden" name="leg_job_id" id="leg_job_id" value="" />
									<input type="hidden" name="leg_user_id" id="leg_user_id" value="" />
									<input type="hidden" name="submit_type" id="submit_type" value="2" />
									
									<div id="leg_cont_show">
										<div class="signup-row">
											<div class="popup-form-box popup-form-left">
												<div class="selectForm">
													<select name="pick_addr_id" id="pick_addr_id" onchange="choosed_pickdrop_det(this.value, 'leg_pickup_addr')">
														<option>Address</option>
													</select>
													<a href="javascript:void(0)" class="dropdownA ui-link">
														<svg xmlns="http://www.w3.org/2000/svg" width="15" height="16" viewBox="0 0 20 30">
															<path class="cls-d" d="M1023.98,1179l-12.02,21.01L999.942,1179h24.038Z" transform="translate(-999.938 -1179)"></path>
														</svg>
													</a>
												</div>
											</div>
											<div class="popup-form-box popup-form-right">
												<input type="text" name="leg_start" id="leg_start" placeholder="Date" class="form-controls date-picker" data-role="none" readonly="readonly" />
												<a href="javascript:void(0)" class="dropdownA ui-link">
													<svg xmlns="http://www.w3.org/2000/svg" width="29" height="18" viewBox="0 0 46 36">
														<path d="M45.029,0.004 L0.003,0.004 L0.003,3.003 L0.003,9.003 L0.003,36.000 L48.031,36.000 L48.031,0.004 L45.029,0.004 ZM18.013,15.002 L12.010,15.002 L12.010,9.003 L18.013,9.003 L18.013,15.002 ZM21.015,9.003 L27.019,9.003 L27.019,15.002 L21.015,15.002 L21.015,9.003 ZM18.013,18.002 L18.013,24.001 L12.010,24.001 L12.010,18.002 L18.013,18.002 ZM21.015,18.002 L27.019,18.002 L27.019,24.001 L21.015,24.001 L21.015,18.002 ZM30.020,18.002 L36.024,18.002 L36.024,24.001 L30.020,24.001 L30.020,18.002 ZM30.020,15.002 L30.020,9.003 L36.024,9.003 L36.024,15.002 L30.020,15.002 ZM3.005,9.003 L9.008,9.003 L9.008,15.002 L3.005,15.002 L3.005,9.003 ZM3.005,18.002 L9.008,18.002 L9.008,24.001 L3.005,24.001 L3.005,18.002 ZM3.005,33.000 L3.005,27.001 L9.008,27.001 L9.008,33.000 L3.005,33.000 ZM12.010,33.000 L12.010,27.001 L18.013,27.001 L18.013,33.000 L12.010,33.000 ZM21.015,33.000 L21.015,27.001 L27.019,27.001 L27.019,33.000 L21.015,33.000 ZM30.020,33.000 L30.020,27.001 L36.024,27.001 L36.024,33.000 L30.020,33.000 ZM45.029,33.000 L39.026,33.000 L39.026,27.001 L45.029,27.001 L45.029,33.000 ZM45.029,24.001 L39.026,24.001 L39.026,18.002 L45.029,18.002 L45.029,24.001 ZM45.029,15.002 L39.026,15.002 L39.026,9.003 L42.027,9.003 L45.029,9.003 L45.029,15.002 Z" class="calNew"></path>
													</svg>	
												</a>
											</div>
											<label id="leg_start-error" class="error" for="leg_start"></label>
										</div>
										<div class="signup-row big-font first-sign-up-row"  id="leg_pickup_addr_div">
											<input type="text" name="leg_pickup_addr" id="leg_pickup_addr" placeholder="Pickup Address" class="form-controls inp-address" data-role="none" />
											<input type="hidden" name="leg_pickup_addr_lat" id="leg_pickup_addr_lat" data-role="none" />
											<input type="hidden" name="leg_pickup_addr_long" id="leg_pickup_addr_long" data-role="none" />
											
											<a href="javascript:void(0)" onclick="get_current_latlng('leg_pickup_addr')" class="signup-inp-ico">
												<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" width="15.437" height="15.438" viewBox="0 0 15.437 15.438">
													<path d="M7.088,15.425 L5.467,9.960 L-0.003,8.340 L15.444,-0.008 L7.088,15.425 Z" class="cls-1"/>
												</svg>
											</a>
										</div>
										<div class="signup-row">
											<div class="popup-form-box popup-form-left">
												<div class="selectForm">
													<select name="drop_addr_id" id="drop_addr_id" onchange="choosed_pickdrop_det(this.value, 'leg_drop_addr')">
														<option>Address</option>
													</select>
													<a href="javascript:void(0)" class="dropdownA ui-link">
														<svg xmlns="http://www.w3.org/2000/svg" width="15" height="16" viewBox="0 0 20 30">
															<path class="cls-d" d="M1023.98,1179l-12.02,21.01L999.942,1179h24.038Z" transform="translate(-999.938 -1179)"></path>
														</svg>
													</a>
												</div>
											</div>
											<div class="popup-form-box popup-form-right">
												<input type="text" name="leg_end" id="leg_end" placeholder="Date" class="form-controls date-picker" data-role="none" readonly="readonly" />
												<a href="javascript:void(0)" class="dropdownA ui-link">
													<svg xmlns="http://www.w3.org/2000/svg" width="29" height="18" viewBox="0 0 46 36">
														<path d="M45.029,0.004 L0.003,0.004 L0.003,3.003 L0.003,9.003 L0.003,36.000 L48.031,36.000 L48.031,0.004 L45.029,0.004 ZM18.013,15.002 L12.010,15.002 L12.010,9.003 L18.013,9.003 L18.013,15.002 ZM21.015,9.003 L27.019,9.003 L27.019,15.002 L21.015,15.002 L21.015,9.003 ZM18.013,18.002 L18.013,24.001 L12.010,24.001 L12.010,18.002 L18.013,18.002 ZM21.015,18.002 L27.019,18.002 L27.019,24.001 L21.015,24.001 L21.015,18.002 ZM30.020,18.002 L36.024,18.002 L36.024,24.001 L30.020,24.001 L30.020,18.002 ZM30.020,15.002 L30.020,9.003 L36.024,9.003 L36.024,15.002 L30.020,15.002 ZM3.005,9.003 L9.008,9.003 L9.008,15.002 L3.005,15.002 L3.005,9.003 ZM3.005,18.002 L9.008,18.002 L9.008,24.001 L3.005,24.001 L3.005,18.002 ZM3.005,33.000 L3.005,27.001 L9.008,27.001 L9.008,33.000 L3.005,33.000 ZM12.010,33.000 L12.010,27.001 L18.013,27.001 L18.013,33.000 L12.010,33.000 ZM21.015,33.000 L21.015,27.001 L27.019,27.001 L27.019,33.000 L21.015,33.000 ZM30.020,33.000 L30.020,27.001 L36.024,27.001 L36.024,33.000 L30.020,33.000 ZM45.029,33.000 L39.026,33.000 L39.026,27.001 L45.029,27.001 L45.029,33.000 ZM45.029,24.001 L39.026,24.001 L39.026,18.002 L45.029,18.002 L45.029,24.001 ZM45.029,15.002 L39.026,15.002 L39.026,9.003 L42.027,9.003 L45.029,9.003 L45.029,15.002 Z" class="calNew"></path>
													</svg>	
												</a>
											</div>
											<label id="leg_end-error" class="error" for="leg_end"></label>
										</div>
										<div class="signup-row big-font" id="leg_drop_addr_div">
											<input type="text" name="leg_drop_addr" id="leg_drop_addr" placeholder="Drop Address" class="form-controls inp-address" data-role="none" />
											<input type="hidden" name="leg_drop_addr_lat" id="leg_drop_addr_lat" data-role="none" />
											<input type="hidden" name="leg_drop_addr_long" id="leg_drop_addr_long" data-role="none" />
											
											<a href="javascript:void(0)" onclick="get_current_latlng('leg_drop_addr')" class="signup-inp-ico">
												<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" width="15.437" height="15.438" viewBox="0 0 15.437 15.438">
													<path d="M7.088,15.425 L5.467,9.960 L-0.003,8.340 L15.444,-0.008 L7.088,15.425 Z" class="cls-1"/>
												</svg>
											</a>
										</div>
									</div>
									
									<div id="leg_trms_show" class="hide">
										<div class="terms">
											<h3>Terms And Conditions</h3>
											<div class="terms-scroll custom-scrollbar">
												<div class="terms-content">
													<?php echo str_replace('\n', '<br>', $terms_conditions); ?>
												</div>
											</div>
										</div>
									</div>
									
									<div class="terms-anc">
										<a id="term_click" href="javascript:void(0)" onclick="show_terms('leg_trms_show', 'leg_cont_show')">See Terms &amp; Conditions</a>
									</div>
									<div class="quote-price">
										<label>Quote Price</label>
										<div class="quoted-input">
											<span>$</span>
											<input name="job_leg_price" id="job_leg_price" type="text" autocomplete="off" placeholder="0" value="" data-role="none">
										</div>
										<label id="job_leg_price-error" class="error" for="job_leg_price"></label>
									</div>
									<div class="agree-terms">
										<input name="leg_term_agree" id="leg_term_agree" type="checkbox" checked value="1" class="required" data-role="none" />
										<label for="leg_term_agree">Agree Terms &amp; Conditions</label>
									</div>
									<div class="popup-btns">
										<!--<input type="submit" value="SUBMIT LEG" class="submit-leg" data-role="none" />-->
										<button type="submit" id="submit-leg" class="submit-leg" data-role="none">Submit Leg <i id="leg_loading" class="fa fa-spinner fa-pulse fa-3x fa-fw hide"></i></button>
										<label id="leg-form-error" class="error"></label>
									</div>
								</form>
							</div>

							<div class="popup-form hide" id="quote_cont">
								<div class="terms">
									<h3>Terms And Conditions</h3>
									<div class="terms-scroll custom-scrollbar">
										<div class="terms-content">
											<?php echo str_replace('\n', '<br>', $terms_conditions); ?>
										</div>
									</div>
								</div>
								<form name="quote_job_form" id="quote_job_form" data-ajax="false" action="" method="post">
									<input type="hidden" name="quote_job_id" 	id="quote_job_id" value="" />
									<input type="hidden" name="quote_user_id" 	id="quote_user_id" value="" />
									<input type="hidden" name="submit_type" 	id="submit_type" value="1" />
									
									<div class="quote-price">
										<label>Quote Price</label>
										<div class="quoted-input">
											<span>$</span>
											<input name="job_price" id="job_price" type="text" autocomplete="off" placeholder="0" value="" data-role="none">
										</div>
										<label id="job_price-error" class="error" for="job_price"></label>
									</div>
									<div class="agree-terms">
										<input name="term_agree" id="term_agree" type="checkbox" checked value="1" class="required" data-role="none" />
										<label for="term_agree">Agree Terms &amp; Conditions</label>
									</div>
									<div class="popup-btns">
										<!--<input type="submit" value="SUBMIT QUOTE" id="submit-quote" class="submit-leg" data-role="none" />-->
										<button type="submit" id="submit-quote" class="submit-leg" data-role="none">Submit Quote <i id="quote_loading" class="fa fa-spinner fa-pulse fa-3x fa-fw hide"></i></button>
										<label id="quote-form-error" class="error"></label>
									</div>
								</form>
							</div>
							
							<div class="popup-form hide" id="msg_cont">
								
							</div>
							
							<div class="popup-form hide" id="job_quote_list_cont">
								<form name="payment-form" data-ajax="false" id="payment-form" action="<?php echo base_url().'make-payment' ?>" method="post">
									<input type="hidden" name="current_show_li" 		id="current_show_li" 	value="0" />
									
									<input type="hidden" name="total_quote_legs" 	id="total_quote_legs" 	value="" />
									<input type="hidden" name="current_quote_id" 	id="current_quote_id"	value="" />
									<input type="hidden" name="current_job_id" 		id="current_job_id" 	value="" />
									
									<input type="hidden" name="to_be_pay" 			id="to_be_pay" 		value="10.00" />
									<input type="hidden" name="to_be_refund" 		id="to_be_refund" 		value="0" />
									
									<input type="hidden" name="deduction_amount" 	id="deduction_amount" 	value="0" />
									<input type="hidden" name="deduction_percent" 	id="deduction_percent" 	value="" />
									<input type="hidden" name="extra_amount" 		id="extra_amount" 		value="0" />
									<input type="hidden" name="extra_percent" 		id="extra_percent" 		value="" />
									<input type="hidden" name="extra_days" 			id="extra_days" 		value="0" />
									<input type="hidden" name="pay_currency" 		id="pay_currency" 		value="usd" />
									
									<input type="hidden" name="current_stripe_id" 	id="current_stripe_id" 	value="<?php echo $user_stripe_id; ?>" />
									<input type="hidden" name="user_stripe_card_id" 	id="user_stripe_card_id" value="<?php echo $user_stripe_card_id; ?>" />
									<input type="hidden" name="user_card_status" 	id="user_card_status" 	value="<?php echo $user_card_status; ?>" />
									
									<input type="hidden" name="user_has_acard" 		id="user_has_acard" 	value="<?php echo $user_has_acard; ?>" />
									
									<ul class="pick-date">
										<li id="pickup_date">
											<span>Pick Up Date</span>
											<big>&nbsp;</big>
										</li>
										<li id="drop_date">
											<span>Delivery Date</span>
											<big>&nbsp;</big>
										</li>
									</ul>
								
									<div class="user-list custom-scrollbar" id="job_quote_leg_lists_outer">
										<ul id="job_quote_lists"></ul>
									</div>
								
									<div id="leg_trms_show1" class="hide">
										<div class="terms">
											<h3>Terms And Conditions</h3>
											<div class="terms-scroll custom-scrollbar">
												<div class="terms-content">
													<?php echo str_replace('\n', '<br>', $terms_conditions); ?>
												</div>
											</div>
										</div>
									</div>
								
									<ul class="calc-table">
										<li class="">
											<div class="calc-table-left">
												<span>
													<a info-cont="Refundable Deposit" href="javascript:void(0)" class="infoI">
														<svg xmlns="http://www.w3.org/2000/svg" width="22" height="16" viewBox="0 0 20 42">
															<path d="M17.500,-0.000 C7.835,-0.000 -0.000,7.836 -0.000,17.500 C-0.000,27.166 7.835,35.000 17.500,35.000 C27.165,35.000 35.000,27.166 35.000,17.500 C35.000,7.836 27.165,-0.000 17.500,-0.000 ZM20.611,27.328 C20.611,29.000 19.226,30.355 17.516,30.355 C15.806,30.355 14.420,29.000 14.420,27.328 L14.420,15.736 C14.420,14.062 15.806,12.711 17.516,12.711 C19.226,12.711 20.611,14.062 20.611,15.736 L20.611,27.328 ZM17.499,10.963 C15.716,10.963 14.269,9.547 14.269,7.804 C14.269,6.060 15.716,4.644 17.499,4.644 C19.285,4.644 20.731,6.060 20.731,7.804 C20.731,9.547 19.285,10.963 17.499,10.963 Z" class="cls-info"></path>
														</svg>
													</a>
													Refundable Deposit (20%)
												</span>
											</div>
											<div class="calc-table-right" id="extra_job_price">$</div>
										</li>
										<li class="bold-style">
											<div class="calc-table-left">
												<span>
													<a info-cont="Total amount of the job." href="javascript:void(0)" class="infoI">
														<svg xmlns="http://www.w3.org/2000/svg" width="22" height="16" viewBox="0 0 20 42">
															<path d="M17.500,-0.000 C7.835,-0.000 -0.000,7.836 -0.000,17.500 C-0.000,27.166 7.835,35.000 17.500,35.000 C27.165,35.000 35.000,27.166 35.000,17.500 C35.000,7.836 27.165,-0.000 17.500,-0.000 ZM20.611,27.328 C20.611,29.000 19.226,30.355 17.516,30.355 C15.806,30.355 14.420,29.000 14.420,27.328 L14.420,15.736 C14.420,14.062 15.806,12.711 17.516,12.711 C19.226,12.711 20.611,14.062 20.611,15.736 L20.611,27.328 ZM17.499,10.963 C15.716,10.963 14.269,9.547 14.269,7.804 C14.269,6.060 15.716,4.644 17.499,4.644 C19.285,4.644 20.731,6.060 20.731,7.804 C20.731,9.547 19.285,10.963 17.499,10.963 Z" class="cls-info"></path>
														</svg>
													</a>
													Total
												</span>
											</div>
											<div class="calc-table-right" id="total_job_price">$</div>
										</li>
										<li class="select-credit">
											<div class="calc-table-left">
												<span>
													<a info-cont="Payment methods you can use to pay." href="javascript:void(0)" class="infoI">
														<svg xmlns="http://www.w3.org/2000/svg" width="22" height="16" viewBox="0 0 20 42">
															<path d="M17.500,-0.000 C7.835,-0.000 -0.000,7.836 -0.000,17.500 C-0.000,27.166 7.835,35.000 17.500,35.000 C27.165,35.000 35.000,27.166 35.000,17.500 C35.000,7.836 27.165,-0.000 17.500,-0.000 ZM20.611,27.328 C20.611,29.000 19.226,30.355 17.516,30.355 C15.806,30.355 14.420,29.000 14.420,27.328 L14.420,15.736 C14.420,14.062 15.806,12.711 17.516,12.711 C19.226,12.711 20.611,14.062 20.611,15.736 L20.611,27.328 ZM17.499,10.963 C15.716,10.963 14.269,9.547 14.269,7.804 C14.269,6.060 15.716,4.644 17.499,4.644 C19.285,4.644 20.731,6.060 20.731,7.804 C20.731,9.547 19.285,10.963 17.499,10.963 Z" class="cls-info"></path>
														</svg>
													</a>
													Financing
												</span>
											</div>
											<div class="calc-table-right">
												<div class="selectForm">
													<select name="payment_type" id="payment_type" onchange="show_hide_pay_sec(this.value, 'payment_section')">
														<?php
															if(!empty($payment_types))
															{
																foreach($payment_types as $payment)
																{
																	$pytitle 	= (isset($payment['title'])) ? $payment['title'] : '';
																	$extra_p = 0; $extra_t  = $extra_d = '';
																	if(isset($payment['extra_percent']) && !empty($payment['extra_percent'])){
																		$pytitle = $pytitle.' (+'.$payment['extra_percent'].'%)';
																		$extra_p = $payment['extra_percent'];
																		$extra_t = '+';
																		$extra_d = (isset($payment['max_days'])) ? $payment['max_days'] : '';
																	}
																	elseif(!empty($payment['reduct_percent'])){
																		$pytitle = $pytitle.' (-'.$payment['reduct_percent'].'%)';
																		$extra_p = $payment['reduct_percent'];
																		$extra_t = '-';
																		$extra_d = (isset($payment['max_days'])) ? $payment['max_days'] : '';
																	}
																	
																	echo '<option extra_p="'.$extra_p.'" extra_t="'.$extra_t.'" extra_d="'.$extra_d.'" value="'.$payment['sort_code'].'">'.ucwords($pytitle).'</option>';
																}
															}
														?>
													</select>
													<a href="javascript:void(0)" class="dropdownA ui-link no-width"></a>
												</div>
											</div>
										</li>
									</ul>
									
									<div class="agree-terms">
										<input type="checkbox" id="agree" name="agree" data-role="none" value="1" />
										<label for="agree">Agree Terms &amp; Conditions</label>
									</div>
									
									<div class="terms-anc calc-terms-anc">
										<a href="javascript:void(0)" onclick="show_terms('leg_trms_show1', 'job_quote_leg_lists_outer')">See Terms And Conditions</a>
									</div>
									
									<label id="agree-error" class="error"></label>
									
									<div class="popup-accpt-btns" id="accept-button">
										<button type="button" id="accept-button-btn" onclick="open_pay_sec('accept-button', 'payment_section')" class="submit-leg acc-btn" data-role="none">Accept</button>
										<input type="button" id="send-sms-btn" onclick="send_pop_msg()" value="Send Message" class="submit-leg hide" data-role="none" />
										<button type="button" id="accept-activity-btn" onclick="open_activity_sec('activity-button', 'activity_section')" class="submit-leg hide" data-role="none">View Activity</button>
									</div>
									
									<div id="payment_section" class="hide">
										<div class="">
											<div class="alert alert-danger" id="a_x200" style="display: none;">
												<strong>Error!</strong> <span class="payment-errors"></span>
											</div>
											<span class="payment-success">
											</span>
											
											<?php $current_class = ($user_has_acard) ? 'readonly' : ''; ?>
											
											<fieldset>
												<?php
													if($current_class == 'readonly')
													{
														echo '<div class="ui-grid-b">
																<div class="signup-row guranteed-row radio-row ui-block-a">
																	<input checked id="use_existing1" name="use_existing" onclick="change_pay_card_det(1)" type="radio" data-role="none" value="1">
																	<label for="use_existing1">Existing Card</label>
																 </div>
																 <div class="signup-row guranteed-row radio-row ui-block-b">
																	<input id="use_existing2" name="use_existing" onclick="change_pay_card_det(2)" type="radio" data-role="none" value="2">
																	<label for="use_existing2">New Card</label>
																 </div>
															</div>';
													}
												?>
												
												<!-- Card Holder Name -->
												<div class="signup-row smallFont">
													<input type="text" name="cardholdername" id="cardholdername" data-role="none" maxlength="70" placeholder="Card Holder Name" <?php echo $current_class ?> value="<?php echo $card_user_name ?>" class="card-holder-name form-controls">
												</div>
												
												<!-- Card Number -->
												<div class="signup-row smallFont">
													<input type="text" name="cardnumber" id="cardnumber" data-role="none" id="cardnumber" maxlength="19" placeholder="Card Number" <?php echo $current_class ?> value="<?php echo $card_last_digits ?>" class="card-number form-controls">
												</div>
												
												<!-- Expiry-->
												<div class="signup-row selectForm ui-grid-b">
													<div class="selectForm ui-block-a year-month-drop">
														<select name="expirymonth" id="expirymonth" data-stripe="exp-month" <?php echo $current_class; ?> style="<?php echo ($current_class == 'readonly') ? ' pointer-events: none;' : ''; ?>">
															<option value="01" <?php echo ($exp_month == 1) ? 'selected' : 'selected'; ?>>01</option>
															<option value="02" <?php echo ($exp_month == 2) ? 'selected' : ''; ?>>02</option>
															<option value="03" <?php echo ($exp_month == 3) ? 'selected' : ''; ?>>03</option>
															<option value="04" <?php echo ($exp_month == 4) ? 'selected' : ''; ?>>04</option>
															<option value="05" <?php echo ($exp_month == 5) ? 'selected' : ''; ?>>05</option>
															<option value="06" <?php echo ($exp_month == 6) ? 'selected' : ''; ?>>06</option>
															<option value="07" <?php echo ($exp_month == 7) ? 'selected' : ''; ?>>07</option>
															<option value="08" <?php echo ($exp_month == 8) ? 'selected' : ''; ?>>08</option>
															<option value="09" <?php echo ($exp_month == 9) ? 'selected' : ''; ?>>09</option>
															<option value="10" <?php echo ($exp_month == 10) ? 'selected' : ''; ?>>10</option>
															<option value="11" <?php echo ($exp_month == 11) ? 'selected' : ''; ?>>11</option>
															<option value="12" <?php echo ($exp_month == 12) ? 'selected' : ''; ?>>12</option>
														</select>
														<a href="javascript:void(0)" class="dropdownA ui-link"></a>
													</div>
													<div class="ui-block-b mdivider"><span> / </span></div>
													<div class="selectForm ui-block-c year-month-drop">
														<select name="expyear" id="expyear" data-stripe="exp-year" <?php echo $current_class; ?> style="<?php echo ($current_class == 'readonly') ? ' pointer-events: none;' : ''; ?>">
														</select>
														<a href="javascript:void(0)" class="dropdownA ui-link"></a>
													</div>
													<script type="text/javascript">
														var select = $("#expyear"),
														currentyear= '<?php echo $exp_year; ?>',
														year = new Date().getFullYear();
														
														for (var i = 0; i < 20; i++) {
															var selected = (currentyear == (i + year)) ? 'selected' : '';
															select.append($("<option "+selected+" value='"+(i + year)+"' "+(i === 0 ? "selected" : "")+">"+(i + year)+"</option>"));
														}
													</script> 
												</div>
												<label id="expirymonth-error" class="error" for="expirymonth"></label>
												
												<!-- CVV -->
												<div class="signup-row smallFont">
													<input type="text" name="cvv" id="cvv" data-role="none" id="cvv" placeholder="Cvv" maxlength="4" class="card-cvc form-controls"  <?php echo $current_class ?> value="<?php echo $cvv_code ?>">
												</div>
												  
												<!-- Important notice -->
												<div class="popup-accpt-btns">
													<!--<button class="submit-leg" id="submit-pay" data-role="none" type="submit">Pay</button>-->
													<button class="submit-leg" id="submit-pay" data-role="none" type="submit">Pay <i id="pay_loading" class="fa fa-spinner fa-pulse fa-3x fa-fw hide"></i></button>
													<p class="error" id="payment-card-error"></p>
												</div>
											</fieldset>
										</div>
									</div>
									
									<div class="popup-accpt-bottom-btns">
										<a href="javascript:void(0)" id="decline_btn" onclick="decline_legs()" class="dec-ne-btn" data-role="none">Decline</a>
										<a href="javascript:void(0)" id="donext_btn" onclick="show_next_leg()" class="dec-ne-btn" data-role="none">Next</a>
										<a href="javascript:void(0)" class="strp-btn" data-role="none">
											<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" width="27.719" height="14.594" viewBox="0 0 27.719 14.594">
												<path d="M26.506,8.264 L1.218,8.264 C0.547,8.264 0.002,7.720 0.002,7.049 C0.002,6.378 0.547,5.834 1.218,5.834 L26.506,5.834 C27.178,5.834 27.722,6.378 27.722,7.049 C27.722,7.720 27.178,8.264 26.506,8.264 ZM26.506,2.432 L1.218,2.432 C0.547,2.432 0.002,1.888 0.002,1.217 C0.002,0.546 0.547,0.002 1.218,0.002 L26.506,0.002 C27.178,0.002 27.722,0.546 27.722,1.217 C27.722,1.888 27.178,2.432 26.506,2.432 ZM1.218,12.152 L26.506,12.152 C27.178,12.152 27.722,12.695 27.722,13.366 C27.722,14.037 27.178,14.581 26.506,14.581 L1.218,14.581 C0.547,14.581 0.002,14.037 0.002,13.366 C0.002,12.695 0.547,12.152 1.218,12.152 Z" class="cls-1"/>
											</svg>
										</a>
									</div>
								</form>
								
								<form name="activity_job" data-ajax="false" id="activity_job" method="POST" action="<?php echo base_url(); ?>job-activities">
									<input type="hidden" name="job_id" id="job_id" value="" />
								</form>
							</div>
							
							<div class="popup-form hide" id="error_cont">
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<a data-ajax="false" href="javascript:void(0)" onclick="launchFullscreen(document.documentElement);" id="goFS" class="cancel-signup cancel-map" style="font-size: 20px; color:  #fff"><i class="fa fa-arrows-alt" aria-hidden="true"></i><a>
			
			<a data-ajax="false" href="javascript:void(0)" class="menu-strap">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" width="20.281" height="14.813" viewBox="0 0 20.281 14.813">
					<path d="M19.413,14.821 L0.862,14.821 C0.384,14.821 -0.007,14.402 -0.007,13.891 C-0.007,13.380 0.384,12.962 0.862,12.962 L19.413,12.962 C19.891,12.962 20.283,13.380 20.283,13.891 C20.283,14.402 19.891,14.821 19.413,14.821 ZM0.862,6.485 L15.724,6.485 C16.202,6.485 16.593,6.903 16.593,7.414 C16.593,7.926 16.202,8.344 15.724,8.344 L0.862,8.344 C0.384,8.344 -0.007,7.926 -0.007,7.414 C-0.007,6.903 0.384,6.485 0.862,6.485 ZM18.491,1.867 L0.862,1.867 C0.384,1.867 -0.007,1.449 -0.007,0.938 C-0.007,0.426 0.384,0.008 0.862,0.008 L18.491,0.008 C18.969,0.008 19.360,0.426 19.360,0.938 C19.360,1.449 18.969,1.867 18.491,1.867 Z" class="cls-1"/>
				</svg>
			</a>
	
			<?php
				if(!empty($site_logo))
					echo '<a data-ajax="false" href="'.base_url().'" class="logo map-logo"><img src="'.assets_url('uploads/merchant_images/thumb/'.$site_logo).'" alt="logo" /></a>';
				else
					echo '<a data-ajax="false" href="'.base_url().'" class="logo map-logo"><img src="'.assets_url('site/images/'.$default_site_logo).'" alt="logo" /></a>';
			?>
				
			<!-- Main map search form -->
			<div class="map-wrapper">
				<div class="serach-map">
					<form name="search_frm" id="search_frm" method="post" action="" onsubmit="return false" data-ajax="false">
						<input name="search_place" id="search_place" type="text" class="search-controls" data-role="none" />
						
						<input type="hidden" name="dateRange" 		id="dateRange" 	value="<?php echo (isset($dateRange)) ? $dateRange : 0 ?>" />
						<input type="hidden" name="priceRange" 		id="priceRange" 	value="<?php echo (isset($priceRange)) ? $priceRange : 0 ?>" />
						<input type="hidden" name="search_type" 	id="search_type" 	value="<?php echo (isset($search_type)) ? $search_type : 'broker,driver,depot,customer,job' ?>" />
						
						<input type="hidden" name="current_address" 	id="current_address" value="<?php echo (isset($current_address)) ? $current_address : 'New York, NY, United States' ?>" />
						<input type="hidden" name="srch_lat" 		id="srch_lat" 		value="<?php echo (isset($srch_lat)) ? $srch_lat : '40.6700' ?>" />
						<input type="hidden" name="srch_lon" 		id="srch_lon" 		value="<?php echo (isset($srch_lon)) ? $srch_lon : '-73.9400' ?>" />
						
						<input type="hidden" name="sw_lat" 		id="sw_lat" 		value="<?php echo (isset($sw_lat)) ? $sw_lat : '' ?>" />
						<input type="hidden" name="sw_lng" 		id="sw_lng" 		value="<?php echo (isset($sw_lng)) ? $sw_lng : '' ?>" />
						<input type="hidden" name="ne_lat" 		id="ne_lat" 		value="<?php echo (isset($ne_lat)) ? $ne_lat : '' ?>" />
						<input type="hidden" name="ne_lng" 		id="ne_lng" 		value="<?php echo (isset($ne_lng)) ? $ne_lng : '' ?>" />
						
						<input type="hidden" name="administrative_levels" id="administrative_levels" value="<?php echo (isset($administrative_levels)) ? $administrative_levels : '' ?>" />
						<input type="hidden" name="is_all_proper" 	id="is_all_proper" 	value="0" />
						<input type="hidden" name="map_selected_properly" id="map_selected_properly" value="0" />
						<input type="hidden" name="main_search" 	id="main_search" 	value="0" />
						<input type="hidden" name="cmp_auth_id" 	id="cmp_auth_id" 	value="<?php echo (isset($cmp_auth_id)) ? $cmp_auth_id : '' ?>" />
						
						
						<span id="err_search_str"></span>
						
						<button type="button" class="search-submit-sontrols" id="search_filter" data-role="none">
							<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" width="20.688" height="28" viewBox="0 0 20.688 28">
								<path d="M18.613,12.852 C16.572,16.386 12.219,17.704 8.573,16.071 L2.493,26.599 C2.271,26.984 1.779,27.116 1.393,26.894 C1.008,26.672 0.876,26.179 1.099,25.794 L7.158,15.302 C3.913,12.963 2.904,8.500 4.947,4.962 C7.126,1.189 11.951,-0.104 15.725,2.075 C19.499,4.253 20.792,9.078 18.613,12.852 Z" class="search-path"/>
							</svg>
						</button>
					</form>
				</div>
					
				<div id="map-canvas" class="google-map"></div>
				
				//Initialize the map and get the data form db and show markers
				<script>
					var search_map; //main map veriable used in js
					//var user_current_countries = '<?php echo json_encode($countries); ?>';
					var seatch_types 	= ['broker','driver','depot','customer','job'];
					document.getElementById("loading-filter-background").style.display = "block";
					
					//getting search param values
					var  search_place	= $('#search_place').val(),
						dateRange		= $('#dateRange').val(),
						priceRange	= $('#priceRange').val(),
						search_type 	= $('#search_type').val(),
						srch_lon		= $('#srch_lon').val(),
						srch_lat		= $('#srch_lat').val(),
						sw_lat 		= $('#sw_lat').val(),
						sw_lng 		= $('#sw_lng').val(),
						ne_lat 		= $('#ne_lat').val(),
						ne_lng 		= $('#ne_lng').val(),
						cmp_auth_id	= $('#cmp_auth_id').val();
					
					if (search_place.search(/\S/) != -1) search_place = search_place.replace(/\s/g,"-");
					
					//Making an string of values to pass in ajax
					var ValueToPass 	= "search_place="	+search_place+
									  "&dateRange="	+dateRange+
									  "&priceRange="	+priceRange+
									  "&search_type="	+search_type+
									  "&srch_lat="		+srch_lat+
									  "&srch_lon="		+srch_lon+
									  '&sw_lat='		+sw_lat+
									  '&sw_lng='		+sw_lng+
									  '&ne_lat='		+ne_lat+
									  '&ne_lng='		+ne_lng+
									  '&cmp_auth_id='	+cmp_auth_id;
					
					//Making an object of values to pass in map js
					var ValueToPass_arr = {  'search_place': 	search_place,
										'dateRange': 		dateRange,
										'priceRange': 		priceRange,
										'search_type': 	search_type,
										'cmp_auth_id':		cmp_auth_id 
									  };
					
					//If google is loaded then call the map function
					google.maps.event.addDomListener(window, 'load', map_init);
					function map_init()
					{
						//user_current_countries_det = jQuery.parseJSON(user_current_countries);
						//
						//$.each(user_current_countries_det, function( index, value ) {
						//	//console.log(value);
						//});
						
						search_map	= $('#map-canvas').mapSearch({
							initialPosition: 	[0, 20],
							//user_current_countries: user_current_countries,
							zoom: 			3,
							maxZoom: 			15,
							map_styles: 		[
												{"featureType": "all",					"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},
												{"featureType": "all",					"elementType":"labels.text.stroke","stylers":[{"color":"#000000"},{"lightness":13}]},
												
												{"featureType": "administrative",			"elementType":"geometry.fill","stylers":[{"color":"#000000"}]},
												{"featureType": "administrative",			"elementType":"geometry.stroke","stylers":[{"color":"#144b53"},{"lightness":14},{"weight":1.4}]},
												{"featureType": "landscape",				"elementType":"all","stylers":[{"color":"#08304b"}]},
												
												{"featureType": "poi",					"elementType":"geometry","stylers":[{"color":"#0c4152"},{"lightness":5}]},
												{"featureType": "poi",					"elementType": "labels.icon","stylers": [{ "visibility": "off" }]},
												
												{"featureType": "water",					"elementType":"all","stylers":[{"color":"#021019"}]},
												
												{"featureType": "transit",				"elementType":"all","stylers":[{"color":"#146474"}]},
												{"featureType": "transit.line",			"stylers": [{ "color": "#808080" },{ "visibility": "off" }]},
												{"featureType": "transit.station.rail",		"elementType": "labels.icon","stylers": [{ "visibility": "off" }]},
												{"featureType": "transit.station.bus",		"stylers": [{ "visibility": "off" }]},
												{"featureType": "transit.station.airport",	"stylers": [{"visibility": "off" }] },
												
												{"featureType": "road.highway",			"elementType":"geometry.fill","stylers":[{"color":"#000000"}]},
												{"featureType": "road.highway",			"elementType":"geometry.stroke","stylers":[{"color":"#0b434f"},{"lightness":25}]},
												{"featureType": "road.arterial",			"elementType":"geometry.fill","stylers":[{"color":"#000000"}]},
												{"featureType": "road.arterial",			"elementType":"geometry.stroke","stylers":[{"color":"#0b3d51"},{"lightness":16}]},
												{"featureType": "road.local",				"elementType":"geometry",	"stylers":[{"color":"#000000"}]},
												{"featureType": "road",					"elementType": "labels.text",	"stylers": [{ "visibility": "off" }] },
												{"featureType": "road.highway",			"elementType": "labels.icon", "stylers": [{ "hue": "#0044ff" },{ "saturation": -18 }, { "lightness": 9 }]},
												{"featureType": 'road.highway', 			"stylers": [{"hue": '#0277bd'}, {"saturation": "-50"}]},
											],					
							request_uri: 		'<?php echo base_url(); ?>map-search-result',   
							valueto_pass: 		ValueToPass,
							values_arr: 		ValueToPass_arr,
							is_searched: 		0,
							is_searched1: 		0,
							filters_form : 	'#search_filter',  		
							loading_class: 	'#loading-filter-background',  		
							search_box : 		true, 				
							icon_style:		{
												"customer":	{url: "<?php echo assets_url('site/images/green-man.png') ?>",	scaledSize: "40,37"},
												"driver":		{url: "<?php echo assets_url('site/images/yellow-man.png') ?>",	scaledSize: "40,37"},
												"broker":		{url: "<?php echo assets_url('site/images/cyan-man.png') ?>",	scaledSize: "40,37"},
												"fleet":		{url: "<?php echo assets_url('site/images/red-car.png') ?>",	scaledSize: "40,37"},
												"depot":		{url: "<?php echo assets_url('site/images/home.png') ?>",		scaledSize: "40,37"}
											},  					
							listing_template : 	function(listing){ return '' },
						});
					}
					
					//Add or delete search types (customer or driver etc)
					function change_search_t(args) {
						var index = seatch_types.indexOf(args);
						//console.log('arr index: '+index);
						if (index > -1) { seatch_types.splice(index, 1); }
						else{ seatch_types.push(args); }
						//console.log(seatch_types+' -> '+args);
						
						$("#search_type").val(seatch_types.toString());
						$("#search_filter").click();
					}
				</script>
				
			</div>
			<div class="map-filter">
				<div class="bottom-collapse-btn">
					<img src="<?php echo assets_url('site/images/collapse-btn.png') ?>" alt="collapse-btn" />
				</div>
				<div class="map-check-group">
					<input type="checkbox" name="search_type_outer[]" checked value="broker" onclick="change_search_t(this.value)" id="broker" data-role="none" />
					<label for="broker">
						<div class="map-check-img">
							<img src="<?php echo assets_url('site/images/cyan-man.png') ?>" alt="cyan-man" />
							<img src="<?php echo assets_url('site/images/check-right.png') ?>" alt="check-right" class="map-check-icon" />
						</div>
						<span>Broker</span>
					</label>
					<input type="checkbox" name="search_type_outer[]" checked value="driver" id="driver" onclick="change_search_t(this.value)" data-role="none" />
					<label for="driver">
						<div class="map-check-img">
							<img src="<?php echo assets_url('site/images/yellow-man.png') ?>" alt="yellow-man" />
							<img src="<?php echo assets_url('site/images/check-right.png') ?>" alt="check-right" class="map-check-icon" />
						</div>
						<span>Driver</span>
					</label>

					<input type="checkbox" name="search_type_outer[]" checked value="depot" id="depot" onclick="change_search_t(this.value)" data-role="none" />
					<label for="depot">
						<div class="map-check-img">
							<img src="<?php echo assets_url('site/images/home.png') ?>" alt="home" />
							<img src="<?php echo assets_url('site/images/check-right.png') ?>" alt="check-right" class="map-check-icon" />
						</div>
						<span>Depot</span>
					</label>
					<input type="checkbox" name="search_type_outer[]" checked value="customer" id="customer" onclick="change_search_t(this.value)" data-role="none" />
					<label for="customer">
						<div class="map-check-img">
							<img src="<?php echo assets_url('site/images/green-man.png') ?>" alt="green-man" />
							<img src="<?php echo assets_url('site/images/check-right.png') ?>" alt="check-right" class="map-check-icon" />
						</div>
						<span>Customer</span>
					</label>

					<input type="checkbox" name="search_type_outer[]" checked value="job" id="job" onclick="change_search_t(this.value)" data-role="none" />
					<label for="job">
						<div class="map-check-img">
							<img src="<?php echo assets_url('site/images/red-job.png') ?>" alt="red-car" />
							<img src="<?php echo assets_url('site/images/check-right.png') ?>" alt="check-right" class="map-check-icon" />
						</div>
						<span>Job</span>
					</label>
				</div>
				<div class="map-filter-slider-group">
					<div class="map-filter-slider">
						<div class="filter-slider-main">
							<h3>Date</h3>
							<div class="date-ranger">
								<input type="text" id="dateRange_outer" name="dateRange_outer" value="" data-role="none" />
							</div>
						</div>
					</div>
					<div class="map-filter-add">
						<a data-ajax="false" href="<?php echo base_url().'add-job' ?>" class="map-filter-add-btn"><img src="<?php echo assets_url('site/images/plus-btn.svg') ?>" alt="plus-btn" /></a>
					</div>
					<div class="map-filter-slider">
						<div class="filter-slider-main">
							<h3>Price</h3>
							<div class="price-ranger">
								<input type="text" id="priceRange_outer" name="priceRange_outer" value="" data-role="none" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- dashboard screen -->

	<div id="error-section" class="ui-loader ui-corner-all ui-body-a ui-loader-verbose ui-loader-textonly hide">
		<span class="ui-icon-loading"></span>
		<h1 id="error_msg">Error Loading Page</h1>
	</div>

	<script>
		$( ".inp-address" ).each(function() {
			var id = $(this).attr('id');
			var k  = new google.maps.places.SearchBox(this);
			
			google.maps.event.addListener(k, "places_changed", function() {
				var e = place = k.getPlaces();
				var srch_lat  = srch_lon = '';
				if (e.length > 0) {
					
					if(typeof(place[0].geometry.location) != "undefined")
						srch_lat = place[0].geometry.location.lat();
					
					if(typeof(place[0].geometry.location) != "undefined")
						srch_lon = place[0].geometry.location.lng();
					
					$('#'+id+'_lat').val(srch_lat);
					$('#'+id+'_long').val(srch_lon);
				}
			})
		});
	</script>