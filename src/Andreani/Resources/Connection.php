<?php

 namespace Andreani\Resources;
 
 use Andreani\Resources\WsseAuthHeader;
 use Andreani\Resources\Response;
 
 class Connection{
     
     protected $configuration;
     protected $authHeader;
     
     public function __construct(WsseAuthHeader $authHeader) {
         $this->authHeader = $authHeader;
     }
     
     public function call($configuration,$arguments,$expose = false){
        $client = $this->getClient($configuration->url,$configuration->headers);
        $method = $configuration->method;
        try{
            if($configuration->message_type == 'external'){
                $message = $client->$method($arguments);
            } else {
                $message = $client->__soapCall($method,$arguments);
            }
            return $this->getResponse($message, true, $expose, $client);
        } catch (\SoapFault $e){
            return $this->getResponse($e->getMessage(), false, $expose, $client);
        }         
     }
     
     protected function getClient($url,$headers = array()){
        $options = array(
            'soap_version' => SOAP_1_2,
            'exceptions' => true,
            'trace' => 1,
            'wdsl_local_copy' => true
        );

        $client         = new \SoapClient($url, $options);		
        if(in_array('auth', $headers)){
            $client->__setSoapHeaders(array($this->authHeader));
        }
        
        return $client;
     }
     
     protected function getResponse($message,$valid,$expose,$client){
        $response = new Response($message, $valid);
        if($expose){
            $extra = new \stdClass();
            $extra->request = new \stdClass();
            $extra->response = new \stdClass();
            $extra->request->headers = $client->__getLastRequestHeaders();
            $extra->request->body = $client->__getLastRequest();
            $extra->response->headers = $client->__getLastResponseHeaders();
            $extra->response->body = $client->__getLastResponse();
            $response->setExtra($extra);
        }
        
        return $response;
     }
     
 }