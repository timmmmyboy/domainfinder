<?php
  
  // A few configuration variables you'll need to put in for your environment
  $whmcsurl = 'http://linktoyourwhmcsinstall.com';
  $whmcsusername = 'adminuser';
  $whmcspassword = 'adminpassword';
  $token = 'slacktoken';
  
  // If you're not the slackbot you can't call this script
  if($_POST['token']==$token){
    
    // Generic function to make calls to the WHMCS API. Accepts the API action and an array of parameters. Returns an array.
    function callwhmcsapi($action,$params){
    	$whmcsapiurl = $whmcsurl . '/includes/api.php';
    	$params['username'] = $whmcsusername;
    	$params['password'] = md5($whmcspassword);
    	$params['action'] = $action;
    	$params['responsetype'] = "json";
    	$query_string = "";
    	foreach ($params AS $k=>$v) $query_string .= "$k=".urlencode($v)."&";
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $whmcsapiurl);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    	$jsondata = curl_exec($ch);
    	if (curl_error($ch)) die("Connection Error: ".curl_errno($ch).' - '.curl_error($ch));
    	curl_close($ch);
    	$result = json_decode($jsondata,true);
    	return $result;
    }
    
    // Let's get the email address from Slack and add it to our WHMCS API call
    $params['email'] = $_POST['text'];
    
    // Now let's get an array of all client information from WHMCS using that email
    $client = callwhmcsapi('getclientsdetails',$params);
    
    // Now we have the Client ID in $client['userid'] which we can use to get a list of domains.
    // First let's make sure that it actually found someone.
    if($client['result']=='success'){
      
      // We need to reset our $params array to do another API call
      unset($params);
      
      // Now we'll add the client ID we got from the previous call to params and get a list of domains from WHMCS
      $params['clientid'] = $client['userid'];
      $domains = callwhmcsapi('getclientsdomains',$params);
      
      // Let's see if they have a domain. It should be an array in $domains['domains']
      if($domains['domains']['domain']){
        
        // Loop through the domains array and print out the domains associated with that user
        foreach($domains['domains']['domain'] as $thedomain){
          
          // Some of this formatting is Slack by the way, you can read about that at https://api.slack.com/docs/formatting
          echo '<http://' . $thedomain['domainname'] . '|' . $thedomain['domainname'] . '>
';
          
        }
        
      }
      
      // Otherwise they don't have a domain yet
      else{
        
        echo 'This user does not have a domain.';
        
      }
      
    }
    
    // Didn't find a user from that email address
    else {
      
      echo 'User not found';
      
    }
    
  }
  // Anyone other than Slackbot who calls the script
  else{
    echo 'Incorrect token';
  }