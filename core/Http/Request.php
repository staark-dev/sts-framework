<?php
declare(strict_types=1);
namespace STS\core\Http;

class Request {
    protected array $get;
    protected array $post;
    protected array $server;
    protected array $headers;

    public function __construct(
        array $get, 
        array $post, 
        array $server, 
        array $headers
    ) {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->headers = $headers;
    }

    // Funcția statică capture pentru a inițializa clasa cu datele din cererea curentă
    public static function collection(): self {
        $get = $_GET;
        $post = $_POST;
        $server = $_SERVER;
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return new self($get, $post, $server, $headers);
    }

    // Funcții de acces pentru a obține datele din cerere
    public function get(?string $key = null, $default = null) {
        return $key === null ? $this->get : ($this->get[$key] ?? $default);
    }

    public function post(?string $key = null, $default = null) {
        return $key === null ? $this->post : ($this->post[$key] ?? $default);
    }

    public function server(?string $key = null, $default = null) {
        return $key === null ? $this->server : ($this->server[$key] ?? $default);
    }

    public function header(?string $key = null, $default = null) {
        $key = strtolower($key);
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        return $key === null ? $this->headers : ($headers[$key] ?? $default);
    }

    // Alte metode utile, precum method(), uri(), etc.
    public function method(): string {
        return $this->server('REQUEST_METHOD', 'GET');
    }

    public function uri(): string {
        return $this->server('REQUEST_URI', '/');
    }

    // Verifică dacă cererea este de tip POST
    public function isPost(): bool {
        return $this->method() === 'POST';
    }

    // Verifică dacă cererea este de tip GET
    public function isGet(): bool {
        return $this->method() === 'GET';
    }

    // Returnează toate datele din cerere
    public function all(): array {
        return [
            'get' => $this->get,
            'post' => $this->post,
            'server' => $this->server,
            'headers' => $this->headers,
        ];
    }
}