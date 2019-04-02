<?php
    class sessions{
        
        function __construct() {
            foreach($_COOKIE as $key => $value){
                if(!isset($_SESSION[$key])){
                    json_decode($string);
                    if((json_last_error() == JSON_ERROR_NONE)){
                        $_SESSION[$key] = json_decode($value);
                    }else{
                        $_SESSION[$key] = $value;
                    }
                }
            }
        }
        
        static function check($key){
            if(is_array($key)){
                $set = true;
                foreach($key as $k){
                    if(!sessions::check($k)){
                        $set = false;
                    }
                }
                return $set;
            }else{
                $key = sessions::generateSessionKey($key);
                return isset($_SESSION[$key]);
            }
        }
        
        static function get($key){
            if(isset($_SESSION[sessions::generateSessionKey($key)])){
                return $_SESSION[sessions::generateSessionKey($key)];
            }else{
                return false;
            }
        }
        
        static function set($key, $value, $ttl = 0){
            $_SESSION[sessions::generateSessionKey($key)] = $value;
            if($ttl !== 0){
                if(is_object($value)){
                    $value = json_encode($value);
                }
                setcookie(sessions::generateSessionKey($key), $value, (time() + $ttl), "/", $_SERVER["HTTP_HOST"]);
            }
        }
        
        static function kill($key){
            unset($_SESSION[sessions::generateSessionKey($key)]);
            if(isset($_COOKIE[sessions::generateSessionKey($key)])){
                setcookie(sessions::generateSessionKey($key), "", (time() - 500000), "/", $_SERVER["HTTP_HOST"]);
            }
        }
        
        static function endSession(){
            foreach($_SESSION as $key => $value){
                unset($_SESSION[$key]);
            }
            foreach($_COOKIE as $key => $value){
                setcookie($key, "", (time() - 500000), "/", $_SERVER["HTTP_HOST"]);
            }
            session_destroy();
        }
        
        static function generateSessionKey($key){
            return $key;
        }
        
    }
?>