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


    private static function init()
    {
        if (is_null(self::$instance))
        {
            self::$instance = new ROUTER();
        }
        return self::$instance;
    }

    private function __construct()
    {

    }

    /**
     * Sets the current directory for project, where controllers and folder for modules are placed
     * @param string $current_dir - directory, where the BOOTLOADER.php file and main controllers are placed
     * @throws RouterException
     */
    public static function setCurrentDir($current_dir)
    {
        if(is_dir($current_dir))
        {
            $router=ROUTER::init();
            $router->current_dir=$current_dir;
        }
        else
        {
            throw new RouterException('Router can not set '.$currendDir.' as Current Directory for project!');
        }
    }

    /**
     * Assign module action for route
     * @param string-regex $route - route to be assigned -
     * @param string $module - module name (consists only from letters, _ and digits) - in folder `modules` subfolder `indx` is a module name
     * @param string $action - action name (consists only from letters, _ and digits) - in folder `indx` `indx.action.php` is a action name
     * @throws RouterException - when something is wrong
     */
    public static function assign($route, $module, $action)
    {
        $router = ROUTER::init();
        if($router->current_dir)
        {
            if (preg_match('~^[a-z0-9_]+$~i', $module) and preg_match('~^[a-z0-9_]+$~i', $action))
            {

                $path = $router->current_dir . '/modules/' . $module . '/' . $action . '.action.php';

                if (is_readable($path))
                {
//*
                    if(isset($router->assigns[$route]))
                    {
                        throw new RouterException('Route '.$route.' is already assignet to '.$module.'-'.$action.' via path '.$router->assigns[$module.'-'.$action]['path']);
                    }
                    else
                    {
                    $router->assigns[$route] = array(
                        'route' => $route,
                        'module' => $module,
                        'action' => $action,
                        'path' => $path
                    );
                    }
//*/
/*
                    $router->assigns[$route] = array(
                        'route' => $route,
                        'module' => $module,
                        'action' => $action,
                        'path' => $path
                    );
//*/
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
        else
        {
            throw new RouterException('Set the current directory from bootloader via command ROUTER::setCurrentDir(__DIR__)!!!');
        }
    }

    /**
     * Assign callback function to be executed when route matches the pattern
     * @param string-regex $route
     * @param $closure - function
     * @throws RouterException - when something is wrong
     */
    public static function assignFunction($route, $closure)
    {
        $router = ROUTER::init();
        if($router->current_dir)
        {
            if (is_callable($closure))
            {
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
        else
        {
            throw new RouterException('Set the current directory from bootloader via command ROUTER::setCurrentDir(__DIR__)!!!');
        }
    }

    /**
     * Sets the module as a default module - server route. So, request with pattern example.com example/dosmth example/list
     * goes to actions index.action.php, dosmth.action.php and list.action.php of a module, defined as ServerRoute
     * @param $dirname
     * @throws RouterException
     */
    public static function setServerRoot($module_name)
    {
        $router = ROUTER::init();
        if($router->current_dir)
        {
            if(preg_match('~^[0-9a-z_]+$~i',$module_name))
            {
                if(is_dir($router->current_dir . '/modules/' . $module_name))
                {
                    $path = $router->current_dir . '/modules/' . $module_name . '/*.action.php';
                    foreach (glob($path) as $filename)
                    {
                        $name=basename($filename,'.action.php');
                        if($name=='index')
                            $router->assign('~^/$~',$module_name,'index');
                        else
                            $router->assign('~^/'.$name.'/?$~',$module_name,$name);
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
        else
        {
            throw new RouterException('Set the current directory from bootloader via command ROUTER::setCurrentDir(__DIR__)!!!');
        }
    }

    /**
     * Get array of all assigned routes
     * @return array
     */
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

    /**
     * Returns the route parameters
     * @return array
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

    /**
     * The main function for router!!!
     * @param string $override_request_uri - string to override URL of pages from cli enviroment
     * @return true on success, false on errors (no action to route et cetera)
     * @throws RouterException
     */
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

            foreach ($router->assigns as $assign)
            {
                if (preg_match($assign['route'], $router->path, $router->args))
                {
                    $unknown_route = false;
                    if (isset($assign['path']))
                    {
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
