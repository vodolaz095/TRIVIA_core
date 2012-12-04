<?php
abstract class basePAGE
    {
        protected $scripts=array();
        protected $css=array();
        private $values=array();

        public function __get($key)
            {
                return isset($this->values[$key]) ? $this->values[$key] : null;
            }

        public function __set($key,$value)
            {
                $this->values[$key]=$value;
                return true;
            }

        public function __construct($title)
            {
                $this->title=$title;
            }

        public function setDescription($description)
            {
                $this->description = $description;
            }

        public function setKeywords($keywords)
            {
                $this->keywords = $keywords;
            }

        public function setIndex($index=true)
            {
                $this->index = $index;
            }
        public function setContent($content='')
            {
                $this->content=$content;
            }

        protected  function makeheader()
            {
            ob_start();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru-RU">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title><?php echo $this->title;?></title>
    <meta name="description" content="<?php echo $this->description;?>" />
    <meta name="keywords" content="<?php echo $this->keywords;?>" />
    <meta name="robots" content="<?php if($this->index) echo 'all'; else echo 'none';?>" />
<?php foreach($this->css as $css):?>
    <link rel="stylesheet" href="<?php echo $css;?>" type="text/css" media="screen" />
<?php endforeach;?>
<?php foreach($this->scripts as $script):?>
    <script type="text/javascript" src="<?php echo $script;?>"></script>
<?php endforeach;?>
</head>
<body><?php
             return ob_get_clean();
            }

        protected function makebottom()
            {
                return '</body></html>';
            }

        public function render()
            {
                ob_start();
                echo $this->makeheader();
                echo $this->content;
                echo $this->makebottom();
                return ob_get_clean();
            }

        //public function


    public function __toString()
        {
            return $this->render();
        }

    }
