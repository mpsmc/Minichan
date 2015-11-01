<?php
// Load a dummy class for non-r04rs that doesn't do anything
/*
if($_SESSION['UID']===$administrators["r04r"]) {
    //require("ChromePhp.class.php");
    class Console {
        public static function log() {debug_print_backtrace();die("LOG");}
        public static function warn() {debug_print_backtrace();die("WARN");}
        public static function error() {debug_print_backtrace();die("ERROR");}
        public static function group() {debug_print_backtrace();die("GROUP");}
        public static function info() {debug_print_backtrace();die("INFO");}
        public static function groupCollapsed() {debug_print_backtrace();die("groupCollapsed");}
        public static function groupEnd() {debug_print_backtrace();die("groupEnd");}
        public static function useFile() {}
    }
}else{*/
    class Console
    {
        public static function log()
        {
        }
        public static function warn()
        {
        }
        public static function error()
        {
        }
        public static function group()
        {
        }
        public static function info()
        {
        }
        public static function groupCollapsed()
        {
        }
        public static function groupEnd()
        {
        }
        public static function useFile()
        {
        }
    }
//}
