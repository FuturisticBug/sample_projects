<?php
//this page is used for charge users.
include('../database.php');

$qry_string="select * from settings where id='1' ";
$qry_mysql=mysqli_query($link,$qry_string);
$settings_table=mysqli_fetch_array($qry_mysql);

$current_day=date('Y-m-d');
$publish='';
$secret='';
$current_logid='';
$planid='';

/*
$base_url  =  ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ?  "https" : "http");
$base_url .=  "://".$_SERVER['HTTP_HOST'];
$base_url .=  str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
*/

$base_url=SITEURL;
$redirect_url=$base_url.'site_login';


if(isset($settings_table['stripe_publish']) && trim($settings_table['stripe_publish'])!='')
{
	$publish=$settings_table['stripe_publish'];
	$secret=$settings_table['stripe_secret'];
}


try {
$show_flash=1;
$current_logid=$_POST['user_id'];
//$current_logid=81;
$planid=$_POST['plan_id'];
//$planid=14;

$from_setting=$_POST['from_setting'];

$redirect_url=SITEURL.'regsuccess';

if($from_setting==1)
{
	$redirect_url=SITEURL.'plansettings';
}

require_once('Stripe/lib/Stripe.php');

if($current_logid!='' && $planid!='')
{
$plan_details_sql='select plan_price.*,plan_type.plan_type_name,payment_plans.title as plan_heading,plan_type.id as subscrip_id
									from plan_price,plan_type,payment_plans
									where
									plan_price.id = '.$planid.'
									and plan_price.plan_type = plan_type.id
									and plan_price.status  = 1
									and plan_price.plan_id = payment_plans.id
									';
$plan_details_mysql=mysqli_query($link,$plan_details_sql);
$plan_details=mysqli_fetch_array($plan_details_mysql);

//echo '<pre>';print_r($plan_details);
if(count($plan_details)>0)
{
	$price=	$plan_details['price']*100;
	$interval='';
	if($plan_details['subscrip_id']==1)
	{
		$interval=30;
	}
	if($plan_details['subscrip_id']==2)
	{
		$interval=90;
	}
	if($plan_details['subscrip_id']==3)
	{
		$interval=180;
	}
	if($plan_details['subscrip_id']==4)
	{
		$interval=365;
	}
	
	$end_date = date("Y-m-d", strtotime("+ ".$interval." days"));
	//echo $price."++".$end_date;
	//exit;
	
	Stripe::setApiKey($secret);
	/*********************Stripe Create Customer*********************/
	$customer = Stripe_Customer::create(array(
						'source' => $_POST['stripeToken'],
						//'email' => strip_tags(trim($_POST['stripeEmail'])),
						)
				);
	 $customer_id = $customer->id;
	 
	 $charge = Stripe_Charge::create(array(
					'amount' => $price, // amount in cents
					'currency' => 'usd',
					'customer' => $customer_id
						)
					);
	 $after_charge=$charge->__toArray(TRUE);
	 
	//echo "<pre>"; print_r($charge->__toArray(TRUE));
	//echo '*******************';
	//print $charge->id;
	
	
	if(isset($after_charge['paid']) && $after_charge['paid']==1)
	{
		$tran_table='insert into payment_transaction set
		            plan_id = '.$planid.',
			    start_date = "'.$current_day.'",
			    end_date = "'.$end_date.'",
			    plan_type = '.$planid.',
			    amount = "'.$plan_details['price'].'",
			    trans_id = "'.$after_charge['balance_transaction'].'",
			    paid_type = "'.$after_charge['source']['brand'].'",
			    user_id = '.$current_logid.'
		';
		$tran_table_mysql=mysqli_query($link,$tran_table);
		
		$update_details="Update businessuser_info set
		                  strip_cust_id = '".$customer_id."',
				  renew_date = '".$end_date."',
				  payactive_status = '1',
				  subscid = ".$planid."
				where uid = ".$current_logid."
		";
		$update_details_mysql=mysqli_query($link,$update_details);
		//echo "done";
	}
	/*******************************End******************************/
	header("Location: ".$redirect_url);
}


}

}

catch(Stripe_CardError $e) {
	
}

//catch the errors in any way you like

 catch (Stripe_InvalidRequestError $e) {
  // Invalid parameters were supplied to Stripe's API

} catch (Stripe_AuthenticationError $e) {
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)

} catch (Stripe_ApiConnectionError $e) {
  // Network communication with Stripe failed
} catch (Stripe_Error $e) {

  // Display a very generic error to the user, and maybe send
  // yourself an email
} catch (Exception $e) {

  // Something else happened, completely unrelated to Stripe
}
?>