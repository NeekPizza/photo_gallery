<?php
require_once(LIB_PATH.DS.'database.php');

class User extends DatabaseObject {

    protected static $table_name = "users";
    protected static $db_fields = [
        'id', 
        'username', 
        'password', 
        'first_name',
        'last_name'];
    public $id;
    public $username;
    public $password;
    public $first_name;
    public $last_name;

    public static function find_all() {
        return self::find_by_sql("SELECT * FROM ".self::$table_name);
    }

     public static function count_all() {
        global $databse;
        $sql = "SELECT COUNT(*) FROM ".self::$table_name;
        $result_set = $database->query($sql);
        $row = $databse->fetch_array($result_set);
        return array_shift($row);
    }

    public static function find_by_id($id = 0) {
        global $database;
        $result_array = self::find_by_sql("SELECT * FROM ".self::$table_name." Where id={$id} LIMIT 1"); 
        return !empty($result_array) ? array_shift($result_array) : false;
    }

    public static function find_by_sql($sql="") {
        global $database;
        $result_set = $database->query($sql);
        $object_array = [];
        while ($row = $database->fetch_array($result_set)) {
            $object_array[] = self::instantiate($row);
        }
        return $object_array;
    }

    private static function instantiate($record) { 
        $object = new static;
        // More dynamic, short-form approach:
        foreach($record as $attribute => $value) {
            if($object->has_attribute($attribute)) {
                $object->$attribute = $value;
            }
        }
        return $object;
    }


    private function has_attribute($attribute) {
        $object_vars = $this->attributes();
        return array_key_exists($attribute, $object_vars);
    }

    protected function attributes() {
        $attributes = [];
        foreach(self::$db_fields as $field) {
            if(property_exists($this, $field)) {
                $attributes[$field] = $this->$field;
            }
        }
        return $attributes;
    }

    protected function sanitized_attributes() {
        global $database;
        $clean_attributes = [];
        
        foreach($this->attributes() as $key => $values) {
            $clean_attributes[$key] = $database->escape_value($value);
        }
        return $clean_attributes;
    }


    public function full_name() {
        if(isset($this->first_name) && isset($this->last_name)) {
            return $this->first_name . " " . $this->last_name;
        }
        return "";
    }

    public static function authenticate($username = "", $password = "") {
        global $database;
        $username = $database->escape_value($username);
        $password = $database->escape_value($password);

        $sql  = "SELECT * FROM users ";
        $sql .= "WHERE username = '{$username}' ";
        $sql .= "AND password = '{$password}' ";
        $sql .= "LIMIT 1";
        
        $result_array = self::find_by_sql($sql);
        return !empty($result_array) ? array_shift($result_array) : false;
    }

    public function create() {
        global $database;
        $attributes = $this->sanitized_attributes();
        $sql  = "INSERT INTO".self::$table_name ." (";
        $sql .= join(", ", array_keys($attributes));
        $sql .= ") VALUES ('";
        $sql .= join("', '",array_values($attributes));
        $sql .= "')";

        if($database->query($sql)) {
            $this->id = $database->insert_id();
            return true;
        } else {
            return false;
        }
    }

    public function update() {
        global $database;
        
        $attributes = $this->sanitized_attributes();
        foreach($attributes as $key => $value){
            $attribute_pairs[] = "{$key} = '{$value}'";
        }
        $sql  = "UPDATE ".self::$table_name ." SET ";
        $sql .= join(", ", $attribute_pairs);
        $sql .= " WHERE id=".$database->escape_value($this->id);
        $database->query($sql);
        return ($database->affected_rows() == 1);
    }

    public function delete() {
        global $database;

        $sql  = "DELETE FROM ".self::$table_name;
        $sql .= " HERE id=" . $database->escape_value($this->id);
        $sql .= " LIMIT 1";
        $database->query($sql);
        return ($database->affected_rows() == 1) ? true : false;
    }

    public function save() {
        return isset($this->id) ? $this->update() : $this->create();
    }

}  //end of class

