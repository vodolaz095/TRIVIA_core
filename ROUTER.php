<?php
class RouterException extends Exception
{

}

class ROUTER
{
	protected static $instance;

	private $assigns = array();
	private $current_dir;
	private $path;
	private $args = array();
	private $is_deployed = false;


	public static function init()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new ROUTER();
		}
		return self::$instance;
	}

	private function __construct($override_request_uri = false)
	{
		global $current_dir;
		$this->current_dir = $current_dir;
		//        $this->current_dir=dirname(__FILE__);
	}

	public static function assign($route, $module, $action)
	{
		if (preg_match('~^[a-z0-9_]+$~i', $module) and preg_match('~^[a-z0-9_]+$~i', $action))
		{
			$router = ROUTER::init();
			$path = $router->current_dir . '/modules/' . $module . '/' . $action . '.action.php';


			if (is_file($path))
			{
				$router->assigns[] = array(
					'route' => $route,
					'module' => $module,
					'action' => $action,
					'path' => $path
				);
			}
			else
			{
				throw new RouterException('File "' . $path . '" does\'t exists!');
			}
		}
		else
		{
			throw new RouterException('Bad Folder and Module names!');
		}
	}

	public static function assignFunction($route, $closure)
	{
		if (is_callable($closure))
		{
			$router = ROUTER::init();
			$router->assigns[] = array(
				'route' => $route,
				'function' => $closure
			);
		}
		else
		{
			throw new RouterException('Wrong object type for closure');
		}

	}

	public static function setServerRoot($dirname)
	{
		if(preg_match('~^[0-9a-z_]+$~i',$dirname))
		{
			$router = ROUTER::init();
			if(is_dir($router->current_dir . '/modules/' . $dirname))
			{
			$path = $router->current_dir . '/modules/' . $dirname . '/*.action.php';
				foreach (glob($path) as $filename)
				{
					$name=basename($filename,'.action.php');
					if($name=='index')
					$router->assign('~^/$~',$dirname,'index');
						else
					$router->assign('~^/'.$name.'/?$~',$dirname,$name);
				}
			}
			else
			{
				throw new RouterException('Wrong directory name!');
			}
		}
		else
		{
			throw new RouterException('Wrong directory name!');
		}
	}

	public static function getRoutes()
	{
		$a = array();
		$router = ROUTER::init();
		foreach ($router->assigns as $ass)
		{
			if (isset($ass['module']) and isset($ass['action']))
			{
				$a[] = array('route' => $ass['route'], 'execute' => $router->current_dir . '/' . $ass['module'] . '/' . $ass['action'] . '.action.php');
			}
			elseif (isset($ass['function']))
			{
				$a[] = array('route' => $ass['route'], 'function' => $ass['function']);
			}
			else
			{
				/*do nothing*/;
			}
		}
		return $a;
	}

/*
	public static function error404()
	{
		header("HTTP/1.0 404 Not Found");
	}

	public static function error403()
	{
		header('HTTP/1.0 403 Forbidden');
	}
*/

	public static function getParameters()
	{
		$router = ROUTER::init();
		return $router->args;
	}

	public static function setTemplate($tpl_name)
	{
		if(preg_match('~^[0-9a-z_]+$~i',$tpl_name))
		{
		$router = ROUTER::init();
		$path = $router->current_dir . '/modules/' . $tpl_name . '.tpl.php';
		if(file_exists($path))
			{
				require_once $path;
				if(is_callable('makehead') and is_callable('makebottom'))
					{
						return true;
					}
				else
					{
						throw new RouterException('Does template '.$path.' have functions makehead and makebottom?');
					}
			}
		else
			{
				throw new RouterException('Unable to locate template file in '.$path);
			}
		}
		else
			{
				throw new RouterException('Wrong template name!');
			}
	}


	public static function deploy($override_request_uri = false)
	{
		$unknown_route = true;
		$router = ROUTER::init();

		if ($router->is_deployed === false)
		{
			$router->is_deployed = true;

			if (PHP_SAPI == 'cli' and $override_request_uri)
			{
				$router->path = $override_request_uri;
			}
			else
			{
				$router->path = parse_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
			}

            //echo $router->path;
			foreach ($router->assigns as $assign)
			{
				if (preg_match($assign['route'], $router->path, $router->args))
				{
					$unknown_route = false;
					if (isset($assign['path']))
					{
						$a=ROUTER::getParameters();
                        require_once $assign['path'];
					}
					elseif(isset($assign['function']))
					{
						$a = $assign['function'];
						$a($router->args);
					}
					else
					{
						throw new RouterException('Wrong type of route?');
					}
					break;
                    //die($assign['path']);
				}
			}

			return (!$unknown_route);
		}
		else
		{
			throw new RouterException('Router Already Deployed!');
		}

	}


}
