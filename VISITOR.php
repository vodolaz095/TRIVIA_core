<?php
class UserException extends Exception{};
class VISITOR
{
        protected static $instance;
        private $user;
        protected $isAuth;
        private $flash;
        private $media;

        private static function generateSessionKey()
        {
            if(PHP_SAPI!='cli')
            {
            if(session_id())
                return md5($_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : $_SERVER['REMOTE_ADDR']));
                    else
                return false;
            }
            else
                return false;
        }

        private function __construct()
            {
                if(PHP_SAPI!='cli')
                {
                    if(session_id())
                    {
                        if(isset($_SESSION['UA']))
                            {
                                if($_SESSION['UA']==self::generateSessionKey())
                                {
                                    /* do nothing, this is ok!*/
                                }
                                else
                                {
                                    unset($_SESSION['vizitor']);
                                }
                            }
                        else
                            {
                                $_SESSION['UA']=self::generateSessionKey();
                            }

                    }
                }
            }

        public static function setMedia($baseUSER_descendantName)
        {
            if(get_parent_class($baseUSER_descendantName)=='baseUSER')
            {
                if(method_exists($baseUSER_descendantName,'auth'))
                {
                    $current_user=VISITOR::init();
                    $current_user->media=$baseUSER_descendantName;
                    $current_user->user=new $baseUSER_descendantName();
                }
            }
            else
            {
                throw new UserException('Class '.$baseUSER_descendantName.' is not a descendant of baseUSER');
            }
        }

        private static function init()
            {
                if (is_null(self::$instance))
                {
                    self::$instance = new VISITOR();
                }
                return self::$instance;
            }

        public static function get($value=null)
            {
                if(VISITOR::isAuth())
                    {
//                        return 1;
                        $v=VISITOR::init();
                        return $v->user->$value;
                    }
                else
                    return false;
            }

        public static function getRole()
            {
                $v=VISITOR::init();
                return $v->media;
            }

        public static function getId()
            {
                return VISITOR::get('id');
            }

        public static function getEmail()
            {
                return VISITOR::get('email');
            }

        public static function getUsername()
            {
                return VISITOR::get('login');
            }

        public static function isLogined()
            {
                $current_user=VISITOR::init();
                return $current_user->user->isAuth();
            }
        public static function isAuth()
            {
                return VISITOR::isLogined();
            }

        public static function auth($login,$password)
            {
                $current_user=VISITOR::init();
                if($current_user->media)
                {
                    if(get_parent_class($current_user->user)=='baseUSER')
                    {
                        if(method_exists($current_user->media,'auth'))
                        {
                            $current_user->user->auth($login,$password);
                        }
                    }
                    else
                    {
                        throw new UserException('Class is not a descendant of baseUSER');
                    }
                }
                else
                {
                    throw new UserException('Set Media class for Vizitor Object via VISITOR::setMedia()');
                }
            }

        public static function logoff()
            {
                $current_user=VISITOR::init();
                $current_user->user->logoff();
            }

        public static function debug()
            {
                $current_user=VISITOR::init();
                print_r($current_user->user);
            }

        public static function reload()
            {
                $current_user=VISITOR::init();
                if($current_user->user)
                {
                    if(get_parent_class($current_user->user)=='baseUSER')
                    {
                        if(method_exists($current_user->media,'auth'))
                        {
                            $current_user->user->reload();
                        }
                    }
                    else
                    {
                        throw new UserException('Class is not a descendant of baseUSER');
                    }
                }
                else
                {
                    throw new UserException('Set Media class for Vizitor Object via VISITOR::setMedia()');
                }
            }

        public static function getFlash()
        {
            if(isset($_SESSION['flash']))
            {
                $a=$_SESSION['flash'];
                unset($_SESSION['flash']);
            }
            else
            {
                $a=false;
            }

            return($a);
        }

        public static function setFlash($flash_message)
        {
            $_SESSION['flash']=$flash_message;
            return(true);
        }

        public static function __callStatic($name,$arguments)
        {
            $current_user=VISITOR::init();
            if($current_user->user)
            {
                if(method_exists($current_user,$name))
                    {
                        return VISITOR::$name($arguments);
                    }


                if(get_parent_class($current_user->user)=='baseUSER')
                {
                    if(method_exists($current_user->user,'auth'))
                    {
                        return call_user_func_array(array($current_user->user,$name),$arguments);
                        //return $current_user->user->$name($arguments);
                    }
                }
                else
                {
                    throw new UserException('Class is not a descendant of baseUSER');
                }
            }
            else
            {
                throw new UserException('Set Media class for Vizitor Object via VISITOR::setMedia()');
            }
        }

    }

abstract class baseUSER
    {
    protected $user;

    /**
     * Abstract function which should populate $user by $login and $password
     * @param $login
     * @param $password
     * @return boolean on success
     */
    abstract public function auth($login,$password);
    abstract public function reload();

    public final function __construct()
        {
            if(isset($_SESSION['vizitor'][get_called_class()])) $this->user=$_SESSION['vizitor'][get_called_class()];
        }

    public final function __get($name)
        {
            return isset($this->user[$name]) ? $this->user[$name] : null;
        }

    public final function __set($name,$value)
        {
            $this->user[$name]=$value;
            $_SESSION['vizitor'][get_called_class()][$name]=$value;
        }

    /**
     * Return true if current media user is authenticated
     * @return bool
     */
    public final function isAuth()
        {
            return ($this->user) ? true : false;
        }

    /**
     * Close current media user session
     * @return bool
     */
    public final function logoff()
        {
            if($this->isAuth()){
                unset($_SESSION['vizitor'][get_called_class()]);
            }
            return true;
        }

    }
