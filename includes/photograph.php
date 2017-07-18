<?php
require_once(LIB_PATH.DS.'database.php');
require_once("initialize.php");


class Photograph extends DatabaseObject {

    protected static $table_name = "photographs";
    protected static $db_fields = [
        // 'id', 
        'filename', 
        'type', 
        'size',
        'caption'
    ];
    protected static $return_fields = [
        'id', 
        'filename', 
        'type', 
        'size',
        'caption'
    ];
    public $id;
    public $filename;
    public $type;
    public $size;
    public $caption;
    private $temp_path;
    protected $upload_dir = "images";
    public $errors = [];
    protected $upload_errors = [
        UPLOAD_ERR_OK         => "No errors.",
        UPLOAD_ERR_INI_SIZE   => "Larger than upload_max_filesize.",
        UPLOAD_ERR_FORM_SIZE  => "Larger than form MAX_FILE_SIZE.",
        UPLOAD_ERR_PARTIAL    => "Partial upload.",
        UPLOAD_ERR_NO_FILE    => "No file.",
        UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
        UPLOAD_ERR_CANT_WRITE => "Can't write to disk.",
        UPLOAD_ERR_EXTENSION  => "File upload stopped by extension."
    ];


    public static function find_all() {
        return static::find_by_sql("SELECT * FROM ".static::$table_name);
    }

    public static function count_all() {
        global $database;
        $sql = "SELECT COUNT(*) FROM ".self::$table_name;
        $result_set = $database->query($sql);
        $row = $database->fetch_array($result_set);
        return array_shift($row);
    }

    public static function find_by_id($id = 0) {
        global $database;
        $result_array = static::find_by_sql("SELECT * FROM ".static::$table_name." WHERE id={$database->escape_value($id)} LIMIT 1"); 
        return !empty($result_array) ? array_shift($result_array) : false;
    }

    public static function find_by_sql($sql="") {
        global $database;
        $result_set = $database->query($sql);
        $object_array = [];
        while ($row = $database->fetch_array($result_set)) {
            $object_array[] = static::instantiate($row);
        }
        return $object_array;
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


    public function attach_file($file) {
        // Perform error checking on the form parameters
        if(!$file || empty($file) || !is_array($file)) { // error: nothing uploaded or wrong argument usage
            $this->errors[] = "No file was uploaded.";
            return false;
        } elseif($file['error'] != 0) {  // error: report what PHP says went wrong
            $this->errors[] = $this->upload_errors[$file['error']];
            return false;
        } else { // Set object attirbutes to the form parameters.
        $this->temp_path = $file['tmp_name'];
        $this->filename  = basename($file['name']);
        $this->type      = $file['type'];
        $this->size      = $file['size'];
        return true;
        }

    }

    public function save() {
        if(isset($this->id)) {
            $this->update();
        } else {
            if(!empty($this->errors)) {return false;}
            if(strlen($this->caption) >= 255){
                $this->errors[] =  "The caption can only be 255 characters long";
                return false;
            }
            $target_path = SITE_ROOT.DS.'public'.DS.$this->upload_dir.DS.$this->filename;

            if(file_exists($target_path)) {
                $this->errors[] = "The file {$this->filename} already exists.";
                return false;
            }
            if(move_uploaded_file($this->temp_path, $target_path)) {
                if($this->create()){
                    unset($this->temp_path);
                    return true;
                }
            } else {
                $this->errors[] = "The file upload failed, possibly due to incorrect permissions on the upload folder.";
                return false;
           }
        }   
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

     public function create() {
        global $database;
        $attributes = $this->sanitized_attributes();
        $sql  = "INSERT INTO ".self::$table_name." (";
        $sql .= join(", ", array_keys($attributes));
        $sql .= ") VALUES ('";
        $sql .= join("', '", array_values($attributes));
        $sql .= "')";

        if($result=$database->query($sql)) {
            // var_dump($result);
            // die;
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

    public function destroy() {
        // First remove the database entry
        if($this->delete()){
            // then remove the file
            $target_path = SITE_ROOT.DS.'public'.DS.$this->image_path();
            return unlink($target_path) ? true : false;
        } else {
            // database delete failed
            return false;
        }


        // then remove the file
    }

    public function image_path() {
        return $this->upload_dir.DS.$this->filename;
    }

    public function size_as_text() {
        if($this->size <1024) {
            return "{$this->size} bytes";
        } elseif($this->size < 1048576) {
            $size_kb = round($this->size/1024);
            return "{$size_kb} KB";
        }
        $size_mb = round($this->size/1048576, 1);
        return "{$size_mb} MB";
    }
    public function comments() {
        return Comment::find_comments_on($this->id);
    }





} // end of class