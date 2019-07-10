<?php

class Model {

    //PDO instance
    private static $_db;

    public static function init(){
        self::$_db = new PDO('mysql:host='.$GLOBALS["db"]["host"].';dbname='.$GLOBALS["db"]["database"].';charset=utf8', $GLOBALS["db"]["user"], $GLOBALS["db"]["password"]);
        self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (!$GLOBALS["envProd"]) self::$_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        unset($GLOBALS["db"]);
    }

    public static function request($sql, $data=NULL){
        try {
            if ($data == NULL){
                $result = self::$_db->query($sql);
            }
            else {
                $result = self::$_db->prepare($sql);
                $result->execute($data);
            }
            //store result
            $data = $result->fetchAll();
            //close request
            $result->closeCursor();
            //if no result
            if (empty($data)) $data="";
            //if only one answer we keep it instead of an array
            elseif (!isset($data[1])) $data=$data[0];
            return [
                "succeed" => TRUE,
                "data"    => $data
            ];
        }
        catch(Exception $e) {
            return [
                "succeed" => FALSE,
                "data"    => $e
            ];
        }
    }

    // build an sql SELECT query from args array
    public static function select($args){
        //add all fields to be selected
        $req = 'SELECT '.implode(", ", $args["fields"]);
        //add db table
        $req .= ' FROM '.$args["from"];
        //add optional thing
        if (isset($args["where"])) $req .= ' WHERE ' .implode(" AND ", $args["where"]);
        if (isset($args["order"])) $req .= " ORDER BY ".$args["order"];
        if (isset($args["limit"])) $req .= " LIMIT ".$args["limit"];
        //launch query and return result
        return self::request($req);
    }

    // build an sql UPDATE query from args array
    public static function update($args, $data){
        $req = 'UPDATE '.$args["table"];
        $req .= ' SET '.implode("=? , ", $args["fields"])."=?";
        $req .= ' WHERE '.implode(" AND ", $args["where"]);
        if (isset($args["limit"])) $req .= " LIMIT ".$args["limit"];
        //launch query and return result
        return self::request($req, $data);
    }

    // build an sql INSERT query from args array
    public static function insert($args, $data){
        $req = 'INSERT INTO '.$args["table"];
        $req .= ' ('.implode(", ", $args["fields"]).")";
        $req .= ' VALUES ( ?';
        $i = 1;
        while (isset($args["fields"][$i])){
            $req .= " , ?";
            $i++;
        }
        $req .= ")";
        //launch query and return result
        return self::request($req, $data);
    }

    // build an sql DELETE query from args array
    public static function delete($args){
        $req = 'DELETE FROM '.$args["from"];
        if (isset($args["where"])) $req .= ' WHERE ' .implode(" AND ", $args["where"]);
        //launch query and return result
        return self::request($req);
    }


}
