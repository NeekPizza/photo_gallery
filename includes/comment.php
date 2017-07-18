<?php 
require_once(LIB_PATH.DS.'database.php');

class Comment extends DatabaseObject {

    public static $table_name="comments";

    protected static $db_fields = [
        'photograph_id',
        'created',
        'author',
        'body'
    ];

    protected static $return_fields = [
        'id',
        'photograph_id',
        'created',
        'author',
        'body'
    ];

    public $id;
    public $photograph_id;
    public $created;
    public $author;
    public $body;

    public static function make($photo_id, $author="Anonymous", $body="") {
        
        if(!empty($photo_id) && !empty($author) && !empty($body)){
            $comment = new Comment();
            $comment->photograph_id = (int)$photo_id;
            $comment->created = strftime("%Y-%m-%d %H:%M:%S", time());
            $comment->author = $author;
            $comment->body = $body;
            return $comment;
        } else {  
            return false;
        }   
    }

    public static function find_comments_on($photo_id=0) {

        global $database;

        $sql  = "SELECT * FROM " . self::$table_name;
        $sql .= " WHERE photograph_id=" .$database->escape_value($photo_id);
        $sql .= " ORDER BY created ASC";
        return self::find_by_sql($sql);
    }

    public static function find_all() {
        return static::find_by_sql("SELECT * FROM ".static::$table_name);
    }

     public static function count_all() {
        global $databse;
        $sql = "SELECT COUNT(*) FROM ".self::$table_name;
        $result_set = $database->query($sql);
        $row = $databse->fetch_array($result_set);
        return array_shift($row);
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

    public static function find_by_id($id = 0) {
        global $database;
        $result_array = static::find_by_sql("SELECT * FROM ".static::$table_name." WHERE id={$database->escape_value($id)} LIMIT 1"); 
        return !empty($result_array) ? array_shift($result_array) : false;
    }

    private static function instantiate($record) { 
        $object = new static;
        // More dynamic, short-form approach:
        foreach($record as $return => $value) {
            if($object->has_returns($return)) {
                $object->$return = $value;
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

    private function has_returns($return) {
        $object_vars = $this->returns();
        return array_key_exists($return, $object_vars);
    }

    protected function returns() {
        $returns = [];
        foreach(self::$return_fields as $field) {
            if(property_exists($this, $field)) {
                $returns[$field] = $this->$field;
            }
        }
        return $returns;
    }

    protected function sanitized_attributes() {
        global $database;
        $clean_attributes = [];
        
        foreach($this->attributes() as $key => $value) {
            $clean_attributes[$key] = $database->escape_value($value);
        }
        return $clean_attributes;
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
        $sql  = "INSERT INTO ".self::$table_name ." (";
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
        $sql .= " WHERE id=" . $database->escape_value($this->id);
        $sql .= " LIMIT 1";
        $database->query($sql);
        return ($database->affected_rows() == 1) ? true : false;
    }

    public function save() {
        return isset($this->id) ? $this->update() : $this->create();
    }



} // end of class
