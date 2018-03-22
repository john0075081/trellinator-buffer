<?php
    class CurlRequest
    {
        private $curl;
        private $url;
        private $method;
        private $get_fields;
        private $post_fields;
        private $sending_file;
        const POST = 1;
        const GET = 2;
        
        private function __construct($url)
        {
            $this->curl = curl_init();
            $this->url = $url;
            $this->method = self::GET;
            $this->post_fields = array();
            $this->get_fields = array();
            $this->sending_file = FALSE;
            curl_setopt($this->curl,CURLOPT_HEADER, 0);
            curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->curl,CURLOPT_RETURNTRANSFER, 1);
        }

        public function dontVerifySslHost()
        {
            curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER , false );
            curl_setopt($this->curl,CURLOPT_SSL_VERIFYHOST , false );
        }
        
        public static function toUrl($url)
        {
            return new CurlRequest($url);
        }
        
        public function forwardPostFields()
        {
            if(count($_POST))
            {
                $this->method(self::POST);
                $this->postFields($_POST);
            }
            
            return $this;
        }

        public function forwardGetFields()
        {
            $this->getFields($_GET);
            return $this;
        }

        public function forwardUploads()
        {
            if(count($_FILES))
            {
                $this->method(self::POST);
                throw new CurlRequestException('Not ready to forward files yet');
            }

            return $this;
        }
        
        public function method($method)
        {
            $this->method = $method;
            return $this;
        }
        
        public function cookie($file)
        {
            curl_setopt($this->curl, CURLOPT_COOKIEJAR,$file);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE,$file);
            return $this;
        }
        
        public function setOption($name,$value)
        {
            curl_setopt($this->curl,$name,$value);
            return $this;
        }
        
        private static function addFieldToArray(&$array,$field,$value)
        {
            if(isset($array[$field])&&!is_array($array[$field]))
            {
                $bu = $array[$field];
                $array[$field] = array();
                $array[$field][] = $bu;
                $array[$field][] = $value;
            }
            
            else if(isset($array[$field])&&is_array($array[$field]))
                $array[$field][] = $value;
            else 
                $array[$field] = $value;
        }
        
        private function makeSureThisIsPost($method)
        {
            if($this->method === NULL)
                throw new CurlRequestBuilderException('You need to set method() to CurlRequest::POST before calling '.$method);
            else if($this->method !== CurlRequest::POST)
                throw new CurlRequestBuilderException('You need to set method() to CurlRequest::POST before calling '.$method);
        }

        public function sendFile($input_name,$path)
        {
            $this->makeSureThisIsPost('sendFile');
            self::addFieldToArray($this->post_fields,$input_name,'@'.$path);
            $this->sending_file = TRUE;
            curl_setopt($this->curl,CURLOPT_UPLOAD,1);
        }
        
        public function postFields($fields)
        {
            $this->makeSureThisIsPost('postFields');
            
            if(!is_array($fields))
                throw new CurlRequestBuilderException('You need to pass an array of fields to postFields');
            if(count($this->post_fields))
                throw new CurlRequestBuilderException('You have already started adding fields with postField');
                
            $this->post_fields = $fields;
            return $this;
        }
        
        public function postField($name,$value)
        {
            $this->makeSureThisIsPost('postField');
            self::addFieldToArray($this->post_fields,$name,$value);
        }

        public function getFields($fields)
        {
            if(count($this->get_fields))
                throw new CurlRequestBuilderException('You have already started adding fields with getField');

            $this->get_fields = $fields;
            return $this;
        }
        
        public function getField($name,$value)
        {
            self::addFieldToArray($this->get_fields,$name,$value);
        }
        
        public function execute()
        {
            if($this->method == self::POST)
            {
                curl_setopt($this->curl, CURLOPT_POST, 1);
                
                if(!$this->sending_file)
                    $post_fields = http_build_query($this->post_fields,'','&');
                else
                    $post_fields = $this->post_fields;
                
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_fields);
            }
            
            if(count($this->get_fields))
            {
                if(strpos($this->url,'?') === false)
                    $this->url .= '?';
                else
                    $this->url .= '&';
                $this->url .= http_build_query($this->get_fields,'','&');
            }
    
            curl_setopt($this->curl, CURLOPT_URL, $this->url);
            $return_data = curl_exec($this->curl);
            $errno = curl_errno($this->curl);
            $error = curl_error($this->curl);
    
            if($errno)
            {
                curl_close ($this->curl);
                throw new CurlRequestExecutionException($errno.': '.$error);
            }

            curl_close ($this->curl);
            return $return_data;
        }
    }

    class CurlRequestBuilderException extends Exception {}
    class CurlRequestExecutionException extends Exception{}
