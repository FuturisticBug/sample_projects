<!--
This page is used for payment for plans.
-->
<script src="<?php echo base_url(); ?>assets/js/jquery-1.11.1.js"></script>
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<?php
$stripe_publish='';
if(isset($settings[0]['stripe_publish']) && trim($settings[0]['stripe_publish'])!='')
{
    $stripe_publish=trim($settings[0]['stripe_publish']);
}
?>
<script type="text/javascript">
// This identifies your website in the createToken call below
Stripe.setPublishableKey('<?php echo $stripe_publish; ?>');
// ...
</script>
<script>
$(function() {
$('#payment-form').submit(function(event) {
    
     var plan_id=$('#plan_id').val();
    
      if (plan_id!='') {
	
		var $form = $(this);
		
		// Disable the submit button to prevent repeated clicks
		$form.find('button').prop('disabled', true);
		
		Stripe.card.createToken($form, stripeResponseHandler);
		
		// Prevent the form from submitting with the default action
		return false;
    
	}else{
	    $('#exp_year_er').html('Please choose a plan');
	    $('#exp_year_er').show();
	    return false;
	}
    
	});			
});

function stripeResponseHandler(status, response) {
var $form = $('#payment-form');

if (response.error) {
// Show the errors on the form
$form.find('.payment-errors').text(response.error.message);
$form.find('button').prop('disabled', false);
} else {
// response contains id and card, which contains additional card details
var token = response.id;
// Insert the token into the form so it gets submitted to the server
$form.append($('<input type="hidden" name="stripeToken" />').val(token));
// and submit
$form.get(0).submit();
}
};
</script>
<div class="login_inr">
	<div class="container">
    	<div class="login_box">
        	<!--<h3 ><span style="width: 50%"  >Payment</span><span style="width: 50%;text-align: right;float:right;"><?php if(isset($plan_data[0]['title'])){ echo 'Plan Type - '.ucwords($plan_data[0]['title']); } ?></span></h3>-->
                <?php
                    if(isset($settings[0]['stripe_publish']) && trim($settings[0]['stripe_publish'])!='')
                    {
                ?>
                    <form action="<?php echo base_url().'stripe/charge.php'; ?>" method="post" id="payment-form" name="payment-form" >
                        
			<div class="plan-choice">
			    <span>Plan Type :</span>
	 
			    <p id="plan_head"><?php if(isset($plan_data[0]['title'])){ echo ucwords($plan_data[0]['title']); } ?> Plan </p>
				<p id="plan_price">: $<?php if(isset($plan_data[0]['price'])){ echo ucwords($plan_data[0]['price']); } ?> / <?php if(isset($plan_type_name[0]['plan_type_name'])){ echo ucwords($plan_type_name[0]['plan_type_name']); } ?></p>
			    
			    <a href="javascript:void(0);" class="btn btn-cng-pln" data-target="#chng-pln" data-toggle="modal">Change Plan</a>
			</div>
			        <div class="fld_outr">
                            <label>Name on Card</label>
                            <input  id="name_oncard"   name="name_oncard" type="text" placeholder="" >
                        </div>

					<div class="fld_outr">
                            <label>Card Number</label>
                            <input  id="card_number" data-stripe="number" name="card_number" type="text" placeholder="">
                            <div id="card_number_er" class="error" style="color: red;"></div>
                        </div>
                        <div class="fld_outr">
                            <label>Security Code</label>
                            <input maxlength="5" id="cvc_number" data-stripe="cvc" name="cvc_number" type="text" placeholder="" >
                            <div id="cvc_number_er" class="error" style="color: red;"></div>
                        </div>
                        <div class="fld_outr">
							<div class="row">
								<div class="col-sm-6 ">
								<label>Expiration Month</label>
								<!--<input  id="exp_month" data-stripe="exp-month"  name="exp_month" type="text" placeholder="" >-->
								<?php
								$months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
								?>
								<div class="plan-options" style="float: left;">
								<select  class="selectpicker select-con" id="exp_month" data-stripe="exp-month"  name="exp_month">
									<option value="" >Select Month</option>
									<?php foreach($months as $key=>$val)
									{ 
									?>
									<option value="<?php echo $key;?>"><?php echo $val;?></option>
									<?php } ?>
									</select>
								</div>
							</div>
							
							<div class="col-sm-6 ">
								<label>Expiration Year</label>
								<div class="plan-options" style="float: left;">
								<select  class="selectpicker select-con" id="exp_year" data-stripe="exp-year"  name="exp_year">
									<option value="" >Select Year</option>
									<?php for($y=2016;$y<=2030;$y++)
									{ 
									?>
									<option value="<?php echo $y;?>"><?php echo $y;?></option>
									<?php } ?>
									</select>
								</div>
							</div>

                            <div id="exp_month_er" class="error" style="color: red;"></div>
                        </div>
						</div>
                        <!--<div class="fld_outr">
                            <label>Expiration Year</label>
                            <input  id="exp_year" data-stripe="exp-year"  name="exp_year" type="text" placeholder="" >
                        </div>-->
                        <input type="hidden" name="user_id" id="user_id" value="<?php  echo $this->session->userdata('temp_rows_user_id'); ?>">
                        <input type="hidden" name="plan_id" id="plan_id" value="<?php  echo $this->session->userdata('plan_id'); ?>">
                        <div style="color: red;margin-top: 8px;" id="exp_year_er" class="error payment-errors" ></div>
                        
                        <div class="btn_outr">
                                <input name="" class="login_btn login_bg"   value="Save & Pay" type="submit">
                        </div>
                    </form>
                <?php }else
                    {
                         echo   '<h5>Payment Currently Unavailable</h5>';
                    } ?>    
        </div>
    </div>
</div>
<script>
    function change_plan(txt,id)
    {
	//alert(txt);
	var res = txt.split("###");
	var hd='No Plan Selected';
	if (res[1]!='' && res[1]!=undefined)
	{
	    var hd=res[1]+' Plan';
		$('#plan_price').html(': '+res[2]);
	}
	else
	{
		$('#plan_price').html('');
	}
	
	
	$('#plan_head').html(hd);
	$('#plan_id').val(res[0]);
	
	
	for (var i=1;i<=3;i++)
	{
	    if (id!=i)
	    {
		$('.select-con'+i).val('');
		$('.selectpicker').selectpicker('refresh');
		//$('.selectpicker').selectpicker();
	    }
	  //  alert($('.select-con'+i).val());
	}
	
    }
    
    function choose_plan()
    {
	var plan_id=$('#plan_id').val();
	
	if (plan_id=='')
	{
	    $('#poperror_div').html('Please select a plan');
	    $('#poperror_div').show();
	}else
	{
	    $('#chng-pln').modal('hide');
	    $('#poperror_div').hide();
	}
    }
    
</script>
<!--Plan Choose Popup-->
<div class="modal fade change-plan-p" id="chng-pln" role="dialog" tabindex="-1" role="dialog" aria-labelledby="chng-plnLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Plan Subscription </h3>
      </div>
      <div class="modal-body plan-mod-body">
	   <?php
		$icount=1;    
	        if(isset($payment_plan_db) && count($payment_plan_db)>0 )
		{
		    foreach($payment_plan_db as $val)
		    {
			//$query  = $this->db->query("select pp.id as plan_id,pp.plan_type as plan_type,pp.price as plan_price,pt.plan_type_name from plan_price as pp join plan_type as pt on pp.plan_type=pt.id where pp.status='1' and pp.plan_id='".$val['id']."' order by pt.id asc ");
			
			$query  = $this->db->query("select plan_price.*,plan_type.plan_type_name  from plan_price,plan_type  where plan_price.status='1' and plan_price.plan_id='".$val['id']."'  and plan_price.plan_type = plan_type.id  order by plan_type.id asc ");
			$result = $query->result_array();
			//print_r($result);
	   ?>
           <div class="each-plan active clearfix">
                <div class="plan-name">
                    <h4><?php echo ucfirst($val['title']); ?> Plan</h4>
                </div>

                <div class="plan-options">
                    <select   onchange="change_plan(this.value,'<?php echo $icount; ?>');" class="selectpicker select-con<?php echo $icount;  ?>">
			<option value="" >Please select a plan</option>
		    <?php foreach($result  as $row)
			 {
				$plan_price='$'.$row['price'].' / '.$row['plan_type_name'];
		    ?>	
                        <option  <?php if(isset($plan_data[0]['pln_id']) && $plan_data[0]['pln_id']==$row['id']) { echo "selected"; } ?> value="<?php echo $row['id'].'###'.$val['title'].'###'.$plan_price; ?>" >$<?php echo $row['price']; ?> / <?php echo $row['plan_type_name']; ?></option>
		    <?php } ?>
                    </select>
                </div>
            </div>
             <?php
		    $icount++;
		    }
		}
	     ?>
	    <div  style="color: red;display: none;" id="poperror_div" class="clearfix"></div>
	<div class="btn_outr">
            <input name="" class="login_btn login_bg" value="Save" onclick="choose_plan();"  type="button">
        </div>
      </div>
    </div>
  </div>
</div>
<!--Popup ends here-->
