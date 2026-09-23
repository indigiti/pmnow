<?php
namespace PuneMirror\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly array $body
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base=(string)(getenv('APP_BASE_PATH')?:'');
        $base='/' . trim($base,'/'); if($base==='/')$base='';
        if($base!=='' && ($path===$base || str_starts_with($path,$base.'/'))){
            $path=substr($path,strlen($base)) ?: '/';
        }
        $headersRaw = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $headers=[]; foreach($headersRaw as $k=>$v)$headers[strtolower((string)$k)]=(string)$v;
        $raw = file_get_contents('php://input') ?: '';
        $body = [];
        if ($raw !== '') { $decoded = json_decode($raw, true); if (is_array($decoded)) $body = $decoded; }
        if (!$body && $_POST) $body = $_POST;
        return new self($method, $path, $_GET, $headers, $body);
    }

    public function header(string $name): ?string { $v=$this->headers[strtolower($name)]??null; return $v===null?null:(string)$v; }
    public function clientIp(): string
    {
        $ip=(string)($_SERVER['REMOTE_ADDR']??'0.0.0.0');
        return filter_var($ip,FILTER_VALIDATE_IP)?$ip:'0.0.0.0';
    }
}
