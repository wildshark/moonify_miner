<?php

include("moonify.php"); //include the sdk
$moonify=new Moonify(); //declare a new moonify instance
$moonify->setParams(array(
  "serviceID"=>"3lgIBtTCTuDIUZNu8Xy5xnncGPYxH7Xzf5w_ICJW-iaras8kzkCE3pzMDcxv1jgYpr8E5_WHqLfVz5zNKJj7u3N3sfG25NrgeIeV2HiRo1Pk21Ee2CcvzOaetxScR8k2", //Your private key
  "userID"=>"1" //You can optionnaly pass the id of the user visiting your website 
));
$moonify->openSession(); //open the session

if($moonify->error){
	echo $moonify->error;
} else {
	echo $moonify->getIntegrationCode();
}
?>