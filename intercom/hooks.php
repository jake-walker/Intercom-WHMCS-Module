<?php

if (!defined('WHMCS')) {
  die('This file cannot be accessed directly');
}

use Illuminate\Database\Capsule\Manager as Capsule;

// run this code on every client area page
add_hook("ClientAreaFooterOutput", 1, function ($vars) {
  // define variables that we are going to use
  $output = "";
  $appId = "";
  $hmacKey = "";
  $userHash = "";

  // load the intercom app id from the whmcs database
  $data = Capsule::table('tbladdonmodules')
    ->select('value AS app_id')
    ->where('setting', '=', 'app_id')
    ->where('module', '=', 'intercom')
    ->first();

  if ($data) {
    $appId = $data->app_id;
  }

  // load the intercom hmac key from the whmcs database
  $data = Capsule::table('tbladdonmodules')
    ->select('value AS hmac_key')
    ->where('setting', '=', 'hmac_key')
    ->where('module', '=', 'intercom')
    ->first();

  if ($data) {
    $hmacKey = $data->hmac_key;
  }

  // define more variables we need
  $params = array();
  $userData = '';

  // if the client is logged in (and can get their details)
  if (isset($vars['clientsdetails']) && $vars['clientsdetails']['status'] == 'Active') {
    // data that intercom has built in functionality for
    // from: $vars['clientsdetails']
    // format: intercom value => value to grab
    $intercom_builtin = array(
      'email'                 => 'email',
      'name'                  => 'fullname',
      'phone'                 => 'phonenumberformatted'
    );

    // data that we get from $vars['clientsdetails']
    // format: intercom value => value to grab
    $keys = array(
      'whmcs_client_id'       => 'id',
      'whmcs_account_status'  => 'status',
      'whmcs_address_line1'   => 'address1',
      'whmcs_address_line2'   => 'address2',
      'whmcs_address_city'    => 'city',
      'whmcs_address_state'   => 'state',
      'whmcs_address_postcode'=> 'postcode',
      'whmcs_address_country' => 'countryname',
      'whmcs_pay_default_gateway' => 'defaultgateway',
      'whmcs_pay_card_type'   => 'cctype',
      'whmcs_pay_card_last_4' => 'cclastfour'
    );

    // data that we get from $vars['clientsstats']
    // format: intercom value => value to grab
    $stats = array(
      'whmcs_invoices_due'    => 'numdueinvoices',
      'whmcs_invoices_overdue'=> 'numoverdueinvoices',
      'whmcs_invoices_paid'   => 'numpaidinvoices',
      'whmcs_invoices_unpaid' => 'numunpaidinvoices',
      'whmcs_invoices_cancelled' => 'numcancelledinvoices',
      'whmcs_invoices_refunded' => 'numrefundedinvoices',
      'whmcs_invoices_payment_pending' => 'numpaymentpendinginvoices',
      'whmcs_products_hosting_active' => 'productsnumactivehosting',
      'whmcs_products_hosting'=> 'productsnumhosting',
      'whmcs_products_reseller_active' => 'productsnumactivereseller',
      'whmcs_products_reseller' => 'productsnumreseller',
      'whmcs_products_servers_active' => 'productsnumactiveservers',
      'whmcs_products_servers'=> 'productsnumservers',
      'whmcs_products_other'  => 'productsnumother',
      'whmcs_products_active' => 'productsnumactive',
      'whmcs_products_total'  => 'productsnumtotal',
      'whmcs_domains_active'  => 'numactivedomains',
      'whmcs_domains'         => 'numdomains',
      'whmcs_quotes_accepted' => 'numacceptedquotes',
      'whmcs_quotes'          => 'numquotes',
      'whmcs_tickets_active'  => 'numactivetickets',
      'whmcs_tickets'         => 'numtickets',
      'whmcs_affiliate_signups' => 'numaffiliatesignups'
    );

    // for each built in intercom value
    foreach ($intercom_builtin as $key => $value) {
      if (isset($vars['clientsdetails'][$value])) {
        // put into the params array to be sent to intercom
        $params[] = array(
          'key'   => $key,
          'value' => html_entity_decode($vars['clientsdetails'][$value]),
          'quotes'=> true,
          'keyquotes'=>false
        );
      }
    }

    foreach ($keys as $key => $value) {
      if (isset($vars['clientsdetails'][$value])) {
        $params[] = array(
          'key'   => $key,
          'value' => html_entity_decode($vars['clientsdetails'][$value]),
          'quotes'=> true
        );
      }
    }

    foreach ($stats as $key => $value) {
      if (isset($vars["clientsstats"][$value])) {
        $params[] = array(
          'key'   => $key,
          'value' => html_entity_decode($vars['clientsstats'][$value]),
          'quotes'=> false
        );
      }
    }

    // Grab the date user was registered.
    $params[] = [
      'key' => 'whmcs_account_created_at',
      'value' => strtotime($vars['client']['attributes']['datecreated'] . '00:00:00'),
      'quotes'=> false
    ];

    // Has this member opt out of email communications?
    $params[] = [
      'key' => 'whmcs_has_opted_out_email',
      'value' => $vars['clientsdetails']['emailoptout'] ? 'Yes' : 'No',
      'quotes'=> true
    ];

    // Does this client have 2FA enabled?
    $params[] = [
      'key' => 'whmcs_has_two_factor',
      'value' => $vars['clientsdetails']['twofaenabled'] ? 'Yes' : 'No',
      'quotes'=> true
    ];

    // Is this client an affiliate?
    $params[] = [
      'key' => 'whmcs_is_affiliate',
      'value' => $vars['clientsstats']['isAffiliate'] ? 'Yes' : 'No',
      'quotes'=> true
    ];

    // Grab the clients income
    if (isset($vars['clientsstats']) && method_exists($vars['clientsstats']['income'], 'toFull')) {
      $params[] = array(
        'key' => 'whmcs_income',
        'value' => $vars['clientsstats']['income']->toFull(),
        'quotes'=> true
      );
    }

    if ($params) {
      $userDataArray = array();

      // format the data collected and parse it into json for injecting into html
      foreach ($params as $param) {
        $key = $param['key'];
        $value = $param['value'];
        $quotes = $param['quotes'];
        $keyquotes = $param['keyquotes'];

        $str = "";

        if (!isset($keyquotes) || $keyquotes) {
          $str .= '"';
        }

        $str .= $key;

        if (!isset($keyquotes) || $keyquotes) {
          $str .= '"';
        }

        if ($quotes) {
          $userDataArray[] = $str.': "'.$value.'"';
        } else {
          $userDataArray[] = $str.': '.$value;
        }
      }

      $userData = implode(",\n    ", $userDataArray);
    }

    // if we have a hmac key set
    if (isset($hmacKey) && $hmacKey != "") {
      // generate a hmac hash of the client's email
      $userHash = hash_hmac(
        'sha256',
        $vars['clientsdetails']['email'], // client's email
        $hmacKey // intercom hmac key
      );
    }
  }

  // html to inject to page
  $output = "
    <script>\n
      window.intercomSettings = {\n
        app_id: \"{$appId}\",
  ";

  // if we have a user hash, inject it into the intercom code
  if (isset($userHash) && $userHash != "") {
    $output .= "
      user_hash: '".$userHash."',
    ";
  }

  $output .= ($userData) . "
      };\n
    </script>\n
    <script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==='function'){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/pei8ix75';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()</script>
  ";

  return $output;
});
