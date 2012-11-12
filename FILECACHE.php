<?php
class FileCacheException extends Exception
{

}
class FILECACHE
{
	protected static $instance;
	private $folder='/var/tmp/trivia/';


	private static function init()
	{
		if ( is_null(self::$instance) )
		{
			self::$instance = new FILECACHE();
		}
		return self::$instance;
	}

	private function __construct()
	{
		/* for now - do nothing...*/
	}

    public static function setCacheDir($directory=null)
    {
        $fc=FILECACHE::init();
        if($directory and is_dir($directory))
            {
                if(is_writable($directory))
                    {
                        $fc->folder=$directory;
                    }
                else
                    {
                        throw new FileCacheException('Cache directory '.$directory.' is not writable!');
                    }
            }
        elseif(mkdir($directory,0777))
            {
                $fc->folder=$directory;
            }
        else
            {
                throw new FileCacheException('Cache directory '.$directory.' does not exists and we cannot create it!');
            }
    }

	public static function set($key,$value_or_closure,$duration,$devel=false)
	{
		$ans=FILECACHE::get($key,$devel);
		if($ans)
			{
				return $ans;
			}
		else
			{
				if(is_scalar($value_or_closure))
					$a=$value_or_closure;
				else
					$a=$value_or_closure();

				if($devel)
					$ans='<div title="saved to filecache with key *'.$key.'*" and ttl='.$duration.'>'.$a.'</div>';
				else
					$ans=$a;

				return FILECACHE::setValue($key,$a,$duration,$devel) ? $ans : false;

			}
	}


	protected static function setValue($key,$value,$ttl)
	{
		if(preg_match('~^[a-z0-9_]+$~i',$key))
		{
			$fc=FILECACHE::init();
			$time=$ttl+time();
			foreach (glob($fc->folder.$key.'.*.tmp') as $filename)
			{
				//echo __FUNCTION__.'unlinking '.$filename.PHP_EOL;
				unlink($filename);
			}
			return (file_put_contents($fc->folder.$key.'.'.$time.'.tmp',$value)? $value : false);
		}
		else
		{
			throw new FileCacheException($key.' - is a bad key name!');
		}
	}

	public static  function get($key,$devel=false)
	{
		$fc=FILECACHE::init();
		foreach (glob($fc->folder.$key.'.*.tmp') as $filename)
		{
			$strlen=strlen($fc->folder);
			if(preg_match('~^([a-z0-9_]+)\.(\d+)\.tmp$~i',substr($filename,$strlen),$a))
				{
					$novue=$a[2]-time();
					if($novue>0)
						{
							$a=file_get_contents($filename);
							if($devel) $a='<div title="retrived from filecache with key *'.$key.'* from directory *'.$filename.'*" and ttl='.$novue.'>'.$a.'</div>';
							return $a;
						}
					else
						{
							return false;
						}
				}
			else
				{
					return false;
				}
		}


	}

	public static  function del($key)
	{
		$fc=FILECACHE::init();
		foreach (glob($fc->folder.$key.'.*.tmp') as $filename)
		{
			unlink($filename);
		}
	}

	public static  function flush()
	{
		$fc=FILECACHE::init();
		foreach (glob($fc->folder.'*.tmp') as $filename)
		{
			if(!unlink($filename)) throw new FileCacheException('Unable to remove filecache entry '.$filename.'!');
		}
		return true;
	}


}


/*
//example of code
date_default_timezone_set('UTC');

echo 'Closure test:'.PHP_EOL;
//FILECACHE::flush();
$text='Lalala - setted on '.date('r');
echo date('r').PHP_EOL;
echo FILECACHE::run('lalala',function(){
	return 'closure '.date('r');
},5,true).PHP_EOL;;


//echo 'setting = '.FILECACHE::set('blablabla',date('r'),5,true).PHP_EOL;
echo 'getting = '.FILECACHE::get('blablabla',true).PHP_EOL;
//FILECACHE::flush();
//*/
