<?php

class Page
{

    protected $_url;
    protected $_defaultPage;

    public function __construct($url){
        $this->_url=$url;
    }

    public function getPage(){
        //see first part of the url and call the function
        $fct_to_call = $this->_url[0];
        //if empty then default page
        if ($fct_to_call == "") $fct_to_call = $this->_defaultPage;
        // if not valid name, then go to default page
        if (!method_exists($this, $fct_to_call)) $fct_to_call = $this->_defaultPage;
        //else call the function named
        return $this->$fct_to_call();
    }

}

