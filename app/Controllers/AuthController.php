<?php
namespace STS\app\Controllers;
use STS\core\Controller;

use STS\core\Http\Response;
use STS\core\Http\Request;
use STS\core\Facades\Hash;
use STS\core\Security\Validator;
use STS\core\Facades\Database;
use STS\core\Facades\ResponseFacade;
use STS\core\Facades\Sessions;
use STS\core\Facades\Auth;

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
            'user_email_name' => 'required|string',
            'password' => 'required|string|numeric|min:6',
            'csrf_token' => 'required|csrf_token',
        ];
        
        // Validarea datelor din cerere
        $validate = new Validator($request);
        $validationResult = $validate->validate($request->post(), $rules);
        
        // Verifică dacă validarea a eșuat
        if (!$validationResult->passes()) {
            new Response(json_encode($validationResult->errors()), 200);
            $this->view('auth/login', 'Login', $validationResult->errors());
            return ResponseFacade::redirect('/auth/login', 200);
        }

        // Obține emailul și parola din datele validate
        $email_or_user = $request->post('user_email_name');
        $password = $request->post('password');

        // Verifică dacă utilizatorul există în baza de date
        $user = Database::table('users')
            ->where('email', '=', 'ionuzcostin@gmail.com')
            ->orWhere('username', '=', 'ionuzcostin@gmail.com')
            ->limit(1)
            ->get();
    
        // Verifică dacă utilizatorul a fost găsit
        if (empty($user)) {
            new Response(json_encode(['error' => 'Invalid credentials.']), 200);
            $this->view('auth/login', 'Login', $validationResult->errors());
            return ResponseFacade::redirect('/auth/login', 200);
        }

        // Verifică parola utilizând clasa Hash
        if (!Hash::check($password, $user[0]['password'])) {
            new Response(json_encode(['error' => 'Invalid credentials.']), 200);
            $this->view('auth/login', 'Login', $validationResult->errors());
            return ResponseFacade::redirect('/auth/login', 200);
        }

        $data = [
            'last_login' => date('Y-m-d H:i:s'),
            'remember_token' => session_id()
        ];
        
        // Construiește obiectul QueryBuilder și obține SQL-ul generat
        $query = Database::table('users')
            ->where('id', '=', $user[0]['id'])
            ->update($data);

        new Response(json_encode(['success' => true, 'user' => $user[0]]), 200);
        // Autentificare reușită, returnează un răspuns de succes
        Sessions::set('user', $user[0]['username']);
        Sessions::set('user_id', $user[0]['id']);
        Sessions::set('logged_in', true);

        // Debugging pentru sesiuni
        error_log("Sesiune setată: " . print_r($_SESSION, true));

        Auth::loginUser();
        session_write_close();
        return ResponseFacade::redirect('/', 200);
    }

    public function create(): void
    {
        $this->view('auth/register', 'Register');
    }

    public function signupHandle(Request $request): Response
    {
        if($request->isPost()) {
            // Implementarea logica pentru înregistrarea utilizatorului
            
            // Definește regulile de validare
            $rules = [
                'user_name'                 => 'required|string|max:64',
                'user_mail'                 => 'required|email|unique:users,email',
                'user_password'             => 'required|string|numeric|min:8',
                'user_password_confirm'     => 'required|string|numeric|min:8|same:user_password',
                'user_agree'                => 'required|accepted',
                'csrf_token'                => 'required|csrf_token' // Opțional, în funcție de middleware-ul tău
            ];
            
            // Validarea datelor din cerere
            $validate = new Validator($request);
            $validationResult = $validate->validate($request->post(), $rules);

            // Verifică dacă validarea a eșuat
            if (!$validationResult->passes()) {
                new Response(json_encode($validationResult->errors()), 200);
                $this->view('auth/register', 'Sign Up', $validationResult->errors());
                return ResponseFacade::redirect('/auth/signup', 200);
            }

            // Salvează utilizatorul în baza de date
            $user = Database::table('users')->insert([
                'username'             => $request->post('user_name'),
                'email'                => $request->post('user_mail'),
                'password'             => Hash::make($request->post('user_password')),
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
                'status'               => 'active'
            ]);
            
            // Returnează un răspuns de succes
            new Response(json_encode(['success' => true, 'user' => $user]), 200); // 200 OK
            return ResponseFacade::redirect('/', 200);
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
        new Response('User logged out successfully', 200);
        return ResponseFacade::redirect('/', 200);
    }
}