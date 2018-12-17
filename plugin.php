<?php 

/* 
Plugin Name: Custom MailChimp Subscription Box
*/

define( 'CSP_MAILCHIMP_API_KEY', '26de8852de44f6008831275c62768d0c-us17' );
define( 'CSP_MAILCHIMP_SELECTED_LIST_ID', '2351e67d09' );

add_action( 'wp_footer', 'csp_enqueue_script' );
function csp_enqueue_script() { ?>
	<script>var ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';</script>
	<style>
	.csp_api_msg, .csp_api_msg p{
		font-size: 12px;
		text-transform: uppercase;
		color: red;
	}
	.csp_api_msg .csp-popup-success-msg{
		color: #c89425;
	}
	</style>
<?php }

function csp_submit() {
	
	if( is_array($_POST['data']) ){
		
		$formData = $_POST['data'];
		$email_key = csp_is_field( $formData, 'csp_email' );
		$fname = '';
		$lname = '';
		
		//Email Address
		if( isset($formData[$email_key]['value']) && !filter_var($formData[$email_key]['value'], FILTER_VALIDATE_EMAIL) === false ){
			
			$fname_key = csp_is_field( $formData, 'csp_fname' );
			$lname_key = csp_is_field( $formData, 'csp_lname' );
			
			if( isset($fname_key) ){
				$fname = $formData[$fname_key]['value'];
			
			}
			
			if( isset($lname_key) ){
				
				$lname = $formData[$lname_key]['value'];
			
			}
			
			$email = $formData[$email_key]['value'];
			
			$mailChimpEmailValidation = true;
			
			$mailChimpSubData = csp_mailchimp_subscription( 'subscribed',  $email, $fname, $lname );
				
			if( $mailChimpSubData['code'] == 400 ){
				
				$mailChimpEmailValidation = false;
				
			}
			
			if( $mailChimpEmailValidation == true ){
					
				$subscriptionStatus = array( 'operation' => 'success', 'msg' => '<p class="csp-popup-success-msg" >You have successfully subscribed.</p>'  );
			
			}else{
				
				$subscriptionStatus = array( 'operation' => 'error', 'msg' => $mailChimpSubData['msg'] );
				
			}
			
			wp_send_json( $subscriptionStatus );
			
		}
		
	
	}
	
	die(0);
	
}
add_action( 'wp_ajax_nopriv_csp_submit', 'csp_submit' );
add_action( 'wp_ajax_csp_submit', 'csp_submit' );


//mailchimp Subscribe and unsubscribe 
function csp_mailchimp_subscription( $status, $email, $fname = '', $lname = '' ){
	
	// status: unsubscribed, subscribed, cleaned, pending
	
	$argsBody = array(
			'email_address' => $email,
			'status'        => $status
		);
	
	if( $fname!='' || $lname!='' ){
		
		$argsBody['merge_fields'] = array( 
				'FNAME' => $fname,
				'LNAME' => $lname
			);
		
	}
	
	$args = array(
		'method' => 'PUT',
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( 'user:'. CSP_MAILCHIMP_API_KEY )
		),
		'body' => json_encode( $argsBody )
	);
	
	$response = wp_remote_post( 'https://' . substr(CSP_MAILCHIMP_API_KEY,strpos(CSP_MAILCHIMP_API_KEY,'-')+1) . '.api.mailchimp.com/3.0/lists/' . CSP_MAILCHIMP_SELECTED_LIST_ID . '/members/' . md5(strtolower($email)), $args );
	 
	$body = json_decode( $response['body'] );
	 
	if ( $response['response']['code'] == 200 && $body->status == $status ) {
		
		return array( 'operation' => 'success', 'code' => $response['response']['code'], 'msg' => '<p class="csp-popup-success-msg" >You have successfully ' . $status . '.</p>'  );
		
	} else {
		
		//$response['response']['code'] 
		return array( 'operation' => 'error', 'code' => $response['response']['code'], 'msg' => '<p class="csp-popup-error-msg" >' . $body->detail. '</p>' );
	
	}
	
}

//Check if field exist and return key
function csp_is_field( $formData, $fieldname ){ //Arg1 array of form data, field name to check  
	
	foreach($formData as $key => $value){
		
		if(is_array($value) && $value['name'] == $fieldname){
			  
			  return $key;
			  
		}
		
	}
	
	return false;
	
}

/*

(function($) {
	"use strict"; // Start of use strict

	$(document).ready(function(){
		
		$('#email-subscription-form').submit(function(){
			
			var subscribeForm = $('#email-subscription-form').serializeArray();
			
			$('.csp_loader').show();
			
			$.ajax({
				url : ajax_url,
				type : 'post',
				data : {
					action : 'csp_submit',
					data : subscribeForm
				},
				success : function( response ) {
					console.log(response);
					if(response.operation == 'success'){
						
						$('#email-subscription-form').find("input[type=text], input[type=email], textarea").val("");
						$('.csp_api_msg').html(response.msg);
						
					}else{
						
						$('.csp_api_msg').html(response.msg);
						
					}
					
					$('.csp_loader').hide();
					
				}
			});
			
			return false;
			
		});
		
	});
	
})(jQuery); // End of use strict


<form id="email-subscription-form" class="form-inline row">
 
	<div class="col">
	  <label class="sr-only" for="inlineFormInputGroup">Email</label>
	  
	  <div class="input-group mb-2 subscription-form-container">
		<input type="email" class="form-control" id="inlineFormInputGroup" name="csp_email" placeholder="Email" required>
		<div class="input-group-prepend">
		  <button class="btn btn-success input-group-text" type="submit">Subscribe</button>
		</div>
	  </div>
		<p class="csp_loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i></p>
		<p class="csp_api_msg"></p>
	</div>
  
</form>

*/
