<?php
namespace PuneMirror\Core;

final class Session
{
    private static array $config=[];
    public static function configure(array $config): void { self::$config=$config; }
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $secure=(bool)(self::$config['secure_cookie']??false) || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        session_name('pmnow');
        session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>$secure,'path'=>(string)(self::$config['session_path']??'/'),'lifetime'=>(int)(self::$config['lifetime']??0)]);
        session_start();
    }
    public static function get(string $key,mixed $default=null):mixed{self::start();return $_SESSION[$key]??$default;}
    public static function put(string $key,mixed $value):void{self::start();$_SESSION[$key]=$value;}
    public static function csrfToken(): string
    {
        self::start(); if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return (string)$_SESSION['_csrf'];
    }
    public static function validateCsrf(?string $token): bool
    {
        self::start(); return is_string($token)&&$token!==''&&isset($_SESSION['_csrf'])&&hash_equals((string)$_SESSION['_csrf'],$token);
    }
    public static function regenerate(): void { self::start(); session_regenerate_id(true); $_SESSION['_csrf']=bin2hex(random_bytes(32)); }
    public static function destroy(): void
    {
        self::start(); $_SESSION=[]; if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']);} session_destroy();
    }
}
