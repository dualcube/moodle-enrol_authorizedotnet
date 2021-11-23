<?php

$services = array(
  'moodle_enrol_authorizedotnet' => array(                      //the name of the web service
      'functions' => array ('moodle_authorizedotnet_payprocess'), //web service functions of this service
      'requiredcapability' => '',                //if set, the web service user need this capability to access 
                                                 //any function of this service. For example: 'some/capability:specified'                 
      'restrictedusers' =>0,                      //if enabled, the Moodle administrator must link some user to this service
                                                  //into the administration
      'enabled'=> 1,                               //if enabled, the service can be reachable on a default installation
      'shortname'=>'enrolauthorizedotnet' //the short name used to refer to this service from elsewhere including when fetching a token
   )
);

$functions = array(
    'moodle_authorizedotnet_payprocess' => array(
        'classname' => 'moodle_enrol_authorizedotnet_external',
        'methodname' => 'authorizedotnet_payment_processing',
        'classpath' => 'enrol/authorizedotnet/externallib.php',
        'description' => 'Load payprocess data',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true
    ),
);