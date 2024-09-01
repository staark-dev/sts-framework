<?php
namespace STS\core\Themes;

use STS\core\Security\Validator;
use STS\core\Session\SessionManager;
use STS\core\Helpers\FormHelper;
use STS\core\Facades\Translate;

class GlobalVariables
{
    protected array $variables = [];
    protected ?Validator $validator;
    protected ?SessionManager $session;
    protected ?FormHelper $formHelper;
    

    public function __construct()
    {
        $this->session = new SessionManager();
        $this->validator = new Validator();
        $this->formHelper = new FormHelper();

        // Inițializează variabilele globale
        $this->variables = [
            'page_title' => 'STS Framework',
            'descriptions' => '',
            
            // Functions usefull
            'csrf_token' => fn() => csrf_token(),
            'auth_user' => fn() => $_SESSION['user'] ?? null,
            'base_url' => fn() => rtrim((isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}", '/'),
            'auth' => fn() => isset($_SESSION['user_id']),
            'route' => fn($name, $params = []) => app('Router')->route($name, $params),
            'url' => fn($path = '') => url($path),

            // Validations, Sessions, Translate
            'trans' => fn($key, $params = []) => app('translation')->translate($key, $params),
            'add' => fn(string $key, string|array $value = []) => Translate::trans($key, $params),
            'session' => fn($key = null) => $key ? $this->session->get($key) : $_SESSION,
            'flash' => fn($key) => $this->session->getFlash($key),
            'validate' => fn($data, $rules) => $this->validator->validate($data, $rules),
            'validation_errors' => fn() => $this->validator->errors(),

            // Adaugă alte variabile globale aici...
            'formOpen' => fn(string $action = '', string $method = 'POST', array $attributes = []) 
                => $this->formHelper->open($action, $method, $attributes),
            'formClose' => fn() 
                => $this->formHelper->close(),
            'formInput' => fn(string $type, string $name, ?string $value = null, array $attributes = []) 
                => $this->formHelper->input($type, $name, $value, $attributes),
            'formlabel' => fn(string $name, ?string $text = null, array $attributes = []) 
                => $this->formHelper->label($name, $text, $attributes),
            'formbutton' => fn(string $text, array $attributes = []) 
                => $this->formHelper->button($text, $attributes),
            'formCheckbox' => fn(string $name, string $value, ?bool $checked = false, array $attributes = []) 
                => $this->formHelper->checkbox($name, $value, $checked, $attributes),
            'formRadio' => fn(string $name, string $value, ?bool $checked = false, array $attributes = []) 
                => $this->formHelper->radio($name, $value, $checked, $attributes),
            'csrfToken' => fn() 
                => $this->formHelper->csrfToken(),
        ];

        return $this->variables;
    }

    public function get($key)
    {
        return $this->variables[$key] ?? null;
    }

    public function all()
    {
        return $this->variables;
    }

    public function set($key, $value)
    {
        if (is_array($key)) {
            $this->variables = array_merge($this->variables, $key);
        } else {
            $this->variables[$key] = $value;
        }

        $this->variables[$key] = $value;
    }
}
