<?php
class FORM
{
    private $formname;
    private $elements;
    privare $clean=array();
    private $isSubmited=false;
    
    public function __construct($name=false){
	if($name){
	    $this->formname=$formname;
	} else {
	    $a=parse_url('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])
	    $this->formname=md5($a['path']);
	}

	if(isset($_SESSION['form_'.md5($formname)]){
	//form is submited!
	}
	
    }

//-----------------------------
    public function addHidden($name,$value){
	return ($this->elements[$name]=array('name'=>$name,'type'=>'hidden','value'=>$value));
    }

    public function addText($name,$value=false,$caption=false,$regexToValidate='~.*~',$errorMessage='Wrong value!'){
	return ($this->elements[$name]=array('name'=>$name,
					    'type'=>'text',
					    'value'=>$value,
					    'caption'=>$caption,
					    'regex'=>$regexToValidate,
					    'errorMessage'=>$errorMessage
					    ));
    }

//------------------------------
    public function setErrorMessage($name,$message){
	if(isset($this->elements[$name]){
	    $this->elements[$name]['errorMessage']
	} else {
	return false;
	}
    }

}