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

    /**
     * @param class $baseUSER_descendantName - descendant of baseUSER class to represent Users model
     * @return bool - true on success
     * @throws UserException when
     */

    public static function setMedia($baseUSER_descendantName)
        {
            if(get_parent_class($baseUSER_descendantName)=='baseUSER')
            {
                $current_user=VISITOR::init();
                $current_user->media=$baseUSER_descendantName;
                $current_user->user=new $baseUSER_descendantName();
                return true;
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

    /**
     * Get current authenticated user parameter
     * @param string/null $value
     * @return value on success, false on error ( or user is not authenticated )
     */
    public static function get($value=null)
            {
                if(VISITOR::isAuth())
                    {
                        $v=VISITOR::init();
                        return $v->user->$value;
                    }
                else
                    return false;
            }

    /**
     * Get the name of the current used class to represent Users model
     * @return string
     */
    public static function getRole()
            {
                $v=VISITOR::init();
                return $v->media;
            }

    /**
     * Returns the current authenticated users ID if it is present
     * @return int value
     */
    public static function getId()
            {
                return VISITOR::get('id');
            }
    /**
     * Returns the current authenticated users Email if it is present
     * @return string value
     */

        public static function getEmail()
            {
                return VISITOR::get('email');
            }
    /**
     * Returns the current authenticated users Login if it is present
     * @return string value
     */

        public static function getUsername()
            {
                return VISITOR::get('login');
            }

    /**
     * Returns true is user is authenticated
     * @return boolean
     */

        public static function isLogined()
            {
                $current_user=VISITOR::init();
                return $current_user->user->isAuth();
            }

    /**
     * Wrapper for VISITOR::isLogined()
     * @return bool
     */
    public static function isAuth()
            {
                return VISITOR::isLogined();
            }

    /**
     * Calls the method auth of carried user's model class, to authenticate a user
     * @param string $login
     * @param string $password
     * @throws UserException
     */
    public static function auth($login,$password)
            {
                $current_user=VISITOR::init();
                if($current_user->media)
                {
                    if(get_parent_class($current_user->user)=='baseUSER')
                    {
                       $current_user->user->auth($login,$password);
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

    /**
     * Calls the logoff method of carried user's model class, to logoff user
     */
    public static function logoff()
            {
                $current_user=VISITOR::init();
                return $current_user->user->logoff();
            }

        public static function debug()
            {
                $current_user=VISITOR::init();
                print_r($current_user->user);
            }
    /**
     * Calls the reload method of carried user's model class, to repopulate users values in session
     */
        public static function reload()
            {
                $current_user=VISITOR::init();
                if($current_user->user)
                {
                    if(get_parent_class($current_user->user)=='baseUSER')
                    {
                        $current_user->user->reload();
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

    /**
     * Get current flash message for a user
     * @return string|bool
     */
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

    /**
     * Sets the current flash message to user
     * @param string $flash_message
     * @return bool
     */
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
