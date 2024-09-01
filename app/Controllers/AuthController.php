<?php
namespace STS\app\Controllers;
use STS\core\Controller;

use STS\core\Http\Response;
use STS\core\Http\Request;
use STS\core\Auth\Auth;
use STS\core\Facades\Hash;
use STS\core\Facades\Validator;
use STS\core\Facades\Database;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('auth/login', 'Login');
    }

    public function store(Request $request): Response
    {
        // Definește regulile de validare pentru datele de conectare
        $rules = [
            'user_email' => 'required|email',
            'user_password' => 'required|string|min:8'
        ];
        
        // Validează datele primite
        $validationResult = Validator::validate($request->post(), $rules);
        
        // Verifică dacă validarea a eșuat
        if (!$validationResult->passes()) {
            return new Response(json_encode($validationResult->errors()), 422); // 422 Unprocessable Entity
        }

        // Obține emailul și parola din datele validate
        $email = $request->post('user_email');
        $password = $request->post('user_password');

        // Verifică dacă utilizatorul există în baza de date
        $user = Database::table('users')
            ->where('email', '=', $email)
            ->get();

        // Verifică dacă utilizatorul a fost găsit
        if (empty($user)) {
            return new Response(json_encode(['error' => 'Invalid credentials.']), 401); // 401 Unauthorized
        }

        // Verifică parola utilizând clasa Hash
        if (!Hash::check($password, $user[0]['password'])) {
            return new Response(json_encode(['error' => 'Invalid credentials.']), 401); // 401 Unauthorized
        }

        // Autentificare reușită, returnează un răspuns de succes
        return new Response(json_encode(['success' => true, 'user' => $user[0]]), 200); // 200 OK
    }

    public function create(): void
    {
        $this->view('auth/register', 'Register');
    }

    public function signupHandle(): Response
    {
        if(app('Request')->isPost()) {
            return new Response(json_encode(app('Request')->post()));
        }
    }

    public function profile(): void
    {

    }

    public function forgotPassword(): Response
    {
        // Implementarea logica pentru recuperarea parolei
        // Exemplu:
        // $token = JWT::encode(['email' => $user['email']], config('app.jwt_secret'), 'HS256');
        // return new Response(['token' => $token], 200);

        return new Response('Forgot password page', 200);
    }

    public function logout(): Response
    {
        Auth::logout();
        return new Response('User logged out successfully', 200);
    }

    /*protected $userOrm;
    protected $roleOrm;

    public function __construct(Orm $userOrm)
    {
        $this->userOrm = $userOrm;
        $this->roleOrm = $roleOrm;
    }

    public function register(Request $request): Response
    {
        $data = $request->post();
        $data['password'] = Hash::make($data['password']);

        $this->userOrm->create($data);

        // Atribuie un rol implicit
        $role = $this->roleOrm->where('name', 'user')[0];
        $this->userOrm->belongsToMany('roles', 'user_role', 'user_id', 'role_id')->create(['user_id' => $this->userOrm->lastInsertId(), 'role_id' => $role['id']]);

        return new Response('User registered successfully', 201);
    }

    public function login(Request $request): Response
    {
        $credentials = $request->post();
        $user = $this->userOrm->where('email', $credentials['email'])[0];

        if ($user && Hash::check($credentials['password'], $user['password'])) {
            Auth::login($user['id']);
            return new Response('User logged in successfully');
        }

        return new Response('Invalid credentials', 401);
    }

    public function logout(): Response
    {
        Auth::logout();
        return new Response('User logged out successfully');
    }*/
}