<?php

class RedisException extends Exception
    {}

class REDIS
    {
        protected static $instance;
        private $sock;
        private $stats=array();

/*
 * Singleton get instanse
 */
        private static function init()
            {
                if (is_null(self::$instance))
                    {
                        self::$instance=new REDIS();
                    }
                return self::$instance;
            }

        private function __construct()
            {

            }

        function __destruct()
            {
                if($this->sock) fclose($this->sock);
            }

        static public function addServer($host='localhost',$port=6379)
            {
                $red=REDIS::init();
                $sock=@fsockopen($host, $port, $errno, $errstr, ini_get("default_socket_timeout"));
                if($sock===false)
                    {
                        throw new RedisException('REDIS connecting to server "'.$host.':'.$port.'" error:'.$errno.':'.$errstr);
                    }
                else
                    {
                        $red->sock=$sock;
                    }
            }

        static public function addLocalSocket($path='unix:///tmp/redis.sock')
            {
                $red=REDIS::init();
                $sock=@fsockopen($path, null, $errno, $errstr, ini_get("default_socket_timeout"));
                if($sock===false)
                {
                    throw new RedisException('REDIS connecting via local socket "'.$path.'" error:'.$errno.':'.$errstr);
                }
                else
                {
                    $red->sock=$sock;
                }
            }



        function __call($name, $args)
            {
                if($this->sock)
                {
                $start=microtime(true);
                array_unshift($args, strtoupper($name));

                $command=sprintf('*%d%s%s%s', count($args), sprintf('%s%s', chr(13), chr(10)), implode(array_map(function($arg)
                    {
                        return sprintf('$%d%s%s', strlen($arg), sprintf('%s%s', chr(13), chr(10)), $arg);
                    }, $args), sprintf('%s%s', chr(13), chr(10))), sprintf('%s%s', chr(13), chr(10)));

                for ($written=0; $written<strlen($command); $written+=$fwrite)
                    {
                        $fwrite=fwrite($this->sock, substr($command, $written));
                        if ($fwrite===FALSE)
                            {
                                throw new Exception('Failed to write entire command ("'.$command.'") to stream!');
                            }
                    }
                $a=$this->readResponse();
                if (is_array($a))
                    {
                        $type='Multi-Bulk';
                    }
                elseif ($a===true)
                    {
                        $type='OK';
                    }
                elseif ($a)
                    {
                        $type='Bulk/Integer';
                    }
                else
                    {
                        $type='Empty';
                    }

                $this->stats[]=array('command'=> trim(implode(' ',$args)),
                                     'time'   =>(1000*round((microtime(true)-$start), 6)),
                                     'type'   =>$type);
                return $a;
                }
                else
                {
                    throw new RedisException('Connection to Redis Server is not initialised!');
                }
            }


        private function readResponse()
            {
                /* Parse the response based on the reply identifier */
                $reply=trim(fgets($this->sock, 512));
                switch (substr($reply, 0, 1))
                {
                    /* Error reply */
                    case '-':
                        throw new RedisException(trim(substr($reply, 4)));
                        break;
                    /* Inline reply */
                    case '+':
                        $response=substr(trim($reply), 1);
                        if ($response==='OK')
                            {
                                $response=TRUE;
                            }
                        break;
                    /* Bulk reply */
                    case '$':
                        $response=NULL;
                        if ($reply=='$-1')
                            {
                                break;
                            }
                        $read=0;
                        $size=intval(substr($reply, 1));
                        if ($size>0)
                            {
                                do
                                    {
                                        $block_size=($size-$read)>1024 ? 1024 : ($size-$read);
                                        $r=fread($this->sock, $block_size);
                                        if ($r===FALSE)
                                            {
                                                throw new RedisException('Failed to read response from stream');
                                            }
                                        else
                                            {
                                                $read+=strlen($r);
                                                $response.=$r;
                                            }
                                    } while ($read<$size);
                            }
                        fread($this->sock, 2); /* discard crlf */
                        break;
                    /* Multi-bulk reply */
                    case '*':
                        $count=intval(substr($reply, 1));
                        if ($count=='-1')
                            {
                                return NULL;
                            }
                        $response=array();
                        for ($i=0; $i<$count; $i++)
                            {
                                $response[]=$this->readResponse();
                            }
                        break;
                    /* Integer reply */
                    case ':':
                        $response=intval(substr(trim($reply), 1));
                        break;

                    default:
                        die($reply);
                        //throw new Exception('Unknown response: ' . $reply);
                        break;
                }
                /* Party on */
                return $response;
            }


        public static function __callStatic($name, $args=array())
            {
                $red=REDIS::init();
                return call_user_func_array(array($red, $name), $args);
            }


        public static function s()
            {
                $red=REDIS::init();
                return $red->stats;
            }
    }