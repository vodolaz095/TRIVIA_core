<?php
class DB_exception extends Exception
    {
    }

class DB
    {
        protected static $instance;
        private $links=array();
        private $active_link;
        private $stats=array();
        private $driver;


        private static function init()
            {
                if (is_null(self::$instance))
                    {
                        self::$instance=new DB();
                    }
                return self::$instance;
            }

        private function __construct()
            {

                if (extension_loaded('pdo_mysql'))
                    {
                        $this->driver='PDO';
                        require_once 'dbdrv/pdo_mysql.php';
                    }
                elseif (extension_loaded('mysqli'))
                    {
                        $this->driver='MySQLi';
                        require_once 'dbdrv/mysqli.php';
                    }
                elseif (extension_loaded('mysql'))
                    {
                        $this->driver='MySQL';
                        require_once 'dbdrv/mysql.php';
                    }
                else
                    {
                        throw new DB_exception('Install PHP extensions for MySQL interaction!');
                    }
            }


    /**
     * This function adds a new link to database connections pool
     * @static
     * @param string $link_name - name of the new link to be created
     * @param string $dsn - DSN line for connection to database - mysql://username:SecretPassword@mysql_host1/DB_name1
     * @return bool - true on success
     * @throws DB_exception
     *
     * For example, we want to set 2 databases link, we can do it in this way
     * <code>
     * DB::addLink('main','mysql://username:SecretPassword@mysql_host1/DB_name1');
     * DB::addLink('archive','mysql://username:SecretPassword@mysql_host2/DB_name2');
     * </code>
     *
     * notice - after adding link this link becomes active
     * @see DB::setLink($link_name);
     */
        static public function addLink($link_name, $dsn)
            {
                $db=DB::init();

                if ($parameters=parse_url($dsn) and $link_name)
                    {
                        if ($parameters['scheme']=='mysql')
                            {
                                if ($db->driver=='PDO')
                                    {
                                        $classname='DB_PDO_MySQL';
                                    }
                                elseif ($db->driver=='MySQLi')
                                    {
                                        $classname='DB_MySQLi';
                                    }
                                elseif ($db->driver=='MySQL')
                                    {
                                        $classname='DB_MySQL';
                                    }
                                else
                                    {
                                        throw new DB_exception('Install PHP extensions for MySQL interaction!');
                                    }

//                                    try{ $link=new $classname($dsn);
//                                    } catch (DB_exception $e) {
//                                         $link=false;
//                                    }
                                    $link=new $classname($dsn);
                                    $db->links[$link_name]=$link;
                                    if($link) $db->active_link=$link_name;
                            }
                    }
                else
                    {
                        throw new DB_exception('Wrong dsn or link name! $dsn format shall be like mysql://user:pwd@mysql_host/database_name  ');
                    }

                return true;
            }
    /**
     * Function sets one of the added MySQL links to be active and be used for sending queries.
     * @static
     * @param $link_name
     * @throws DB_exception
     */
        static public function setLink($link_name)
            {
                $db=DB::init();
                if (array_key_exists($link_name, $db->links))
                    {
                        $db->active_link=$link_name;
                    }
                else
                    {
                        throw new DB_exception('Link with name "'.$link_name.'" doesn\'t exists!');
                    }
            }


    /**
     * Function returns the name of active database link used, or false if link is not setted
     * @static
     * @return bool
     */
        static public function getLinkName()
            {
                $db=DB::init();
                return ($db->active_link) ? $db->active_link : false;
            }

    /**
     * Function executes MySQL query on the active database link - @see DB::setLink($link_name);
     * @static
     * @param string $mysql_query - raw MySQL query to be executed - <i>protection from mysql injections HAS TO BE applied! - @see DB::f($string_to_escape)</i>
     * @param string $fetch_as_object - if you want to fetch result as object input there a name of object class
     * @param array $object_parameters - parameters needed to be setted to object to be created - @see mysql_fetch_object() at @link(http://php.net/manual/en/function.mysql-fetch-object.php) for details
     * @return bool - on update/insert/delete queries
     * @return array/array of objects - on select
     *
     *
     */

        static public function q($mysql_query, $fetch_as_object=null, $object_parameters=array())
            {

                $db=DB::init();

                if (isset($db->links[$db->active_link]))
                    {
                        $ans=$db->links[$db->active_link]->query($mysql_query, $fetch_as_object, $object_parameters);
                        $db->stats[]=array('Link'          =>$db->active_link,
                                           'Query'         =>$ans['query'],
                                           'Type'          =>$ans['type'],
                                           'Time'          =>$ans['time'],
                                           'Status'        =>$ans['status'],
                                           'Affected_rows' =>$ans['affected_rows']);

                        return $ans['result'];
                    }
                else
                    {

                        $db->stats[]=array('Link'          =>$db->active_link,
                                           'Query'         =>$mysql_query,
                                           'Type'          =>'No connection to database!',
                                           'Time'          =>0,
                                           'Status'        =>'No connection to database!',
                                           'Affected_rows' =>0);

                        throw new DB_exception('Unable to establish link "'.$db->active_link.'"');
                    }

            }

    /**
     * @param string $mysql_query - raw MySQL query to be executed - <i>protection from mysql injections HAS TO BE applied! - @see DB::f($string_to_escape)</i>
     * @param null $fetch_as_object
     * @static
     * @param string $fetch_as_object - if you want to fetch result as object input there a name of object class
     * @param array $object_parameters - parameters needed to be setted to object to be created - @see mysql_fetch_object() at @link(http://php.net/manual/en/function.mysql-fetch-object.php) for details
     * @return bool - on update/insert/delete queries
     * @return array/array of objects - on select
     */
    static public function fetchAll($mysql_query, $fetch_as_object=null, $object_parameters=array())
        {
            return DB::q($mysql_query, $fetch_as_object, $object_parameters);
        }

    /**
     * @param string $mysql_query  - raw MySQL query to be executed - <i>protection from mysql injections HAS TO BE applied! - @see DB::f($string_to_escape)</i>
     * @param null $fetch_as_object
     * @static
     * @param string $fetch_as_object - if you want to fetch result as object input there a name of object class
     * @param array $object_parameters - parameters needed to be setted to object to be created - @see mysql_fetch_object() at @link(http://php.net/manual/en/function.mysql-fetch-object.php) for details
     * @return bool - on update/insert/delete queries
     * @return associated array of row values or objects of row values- on select -
     */
    static public function fetchRow($mysql_query, $fetch_as_object=null, $object_parameters=array())
        {
            $ans=DB::q($mysql_query, $fetch_as_object, $object_parameters);
            return is_array($ans) ? (isset($ans[0])? $ans[0] : false) : false;
        }

    /**
     * @param string $mysql_query - raw MySQL query to be executed - <i>protection from mysql injections HAS TO BE applied! - @see DB::f($string_to_escape)</i>
     * @param string $fetch_as_object - if you want to fetch result as object input there a name of object class
     * @static
     * @param array $object_parameters - parameters needed to be setted to object to be created - @see mysql_fetch_object() at @link(http://php.net/manual/en/function.mysql-fetch-object.php) for details
     * @return bool
     * @return value
     * <code>
     * echo DB::fetchSingle('SELECT (2+1)'); // prints 3
     * </code>
     */
    static public function fetchSingle($mysql_query)
        {
            $ans=DB::fetchRow($mysql_query);
            if(is_array($ans))
                {
                if(count($ans)==1)
                    {
                        $a=array_values($ans);
                        return isset($a[0]) ? $a[0] : false;
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
    /**
     * Function escapes string from special characters witch can cause MySQL injections
     * @static
     * @param $string_to_escape
     * @return mixed
     *
     * @link http://php.net/manual/en/function.mysql-real-escape-string.php
     */
        static public function f($string_to_escape)
            {
                $db=DB::init();
                return trim($db->links[$db->active_link]->filter($string_to_escape));
            }


    /**
     * Insert data into table
     * @static
     * @param string $table_name - the name of a table, where we insert data
     * @param array $associated_array_of_values - values to insert
     * @return boolean - true on success, false on errors
     */
        public static function insert($table_name, $associated_array_of_values)
            {
                //todo - кривовато, но зато работает во всех проектах
                $columns=array();
                $values=array();
                foreach ($associated_array_of_values as $key=>$value)
                    {
                        if($value=='NULL')
                            {
                                $columns[]='`'.$key.'`';
                                $values[]='NULL';
                            }
                        else
                            {
                                $columns[]='`'.$key.'`';
                                $values[]='"'.DB::f($value).'"';
                            }
                    }
                $columnsString=implode(',',$columns);
                $valuesString=implode(',', $values);

                $q='INSERT INTO `'.$table_name.'`('.$columnsString.') VALUES ('.$valuesString.')';
                $current_link=DB::getLinkName();
                $DB=DB::init();
                $a=$DB->q($q);
/* not properly tested for now!
                if(in_array('backup',array_keys($DB->links)))
                {
                    $DB->setLink('backup');
                    $DB->q($q);
                    $DB->setLink($current_link);
                }
//*/
                return $a;
            }

    /**
     * Update data in a table
     * @static
     * @param string $table_name
     * @param array $assosiated_array_of_values - values to be updated
     * @param string $string_where - where condition
     * @return bool
     */
        public static function update($table_name, array $assosiated_array_of_values, $string_where)
            {
                $columns=array_keys($assosiated_array_of_values);
                $vals=array();
                foreach ($columns as $column)
                    {
                        $vals[]='`'.$column.'`="'.DB::f($assosiated_array_of_values[$column]).'"';
                    }
                $values=implode(',', $vals);
                $q='UPDATE `'.$table_name.'` SET '.$values.' WHERE '.$string_where;

                $current_link=DB::getLinkName();
                $DB=DB::init();
                $a=$DB->q($q);
/*  not properly tested for now
                if(in_array('backup',array_keys($DB->links)))
                {
                    $DB->setLink('backup');
                    $DB->q($q);
                    $DB->setLink($current_link);
                }
//*/

                return $a;
            }


    /**
     * Returns the last id, which was setted due to autoincrement feature of table
     * @static
     * @return integer on null
     */
        static public function getLastInsertId()
            {
                $db=DB::init();
                return $db->links[$db->active_link]->getLastInsertId();
            }

    /**
     * Shows statistical inforamion of all queries witch was executed from the start of script execution
     * @static
     * @return mixed
     */
        static public function s()
            {
                $db=DB::init();
                return $db->stats;
            }

    /**
     * Returns the current driver
     * @static
     * @return string 'MySQL' or 'MySQLi' or 'PDO'
     */
        static public function getDriver()
            {
                $db=DB::init();
                return $db->driver;
            }
    }



