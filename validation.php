<?php

function validate_email($email){	
	return filter_var($email, FILTER_VALIDATE_EMAIL);	
}

function validate_phone($phone)
{     
	$filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);     
	$phone_to_check = str_replace("-", "", $filtered_phone_number);
	if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14) {        
	   return false;     
	}else{       
	  return true;
	}
}
