<?php
namespace STS\app\Controllers;
use STS\core\Controller;

use STS\core\Http\Response;
use STS\core\Auth\Auth;
use STS\core\Security\Hash;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('user_auth', 'Login');
    }

    public function loginHandle(): Response
    {
        if(app('Request')->isPost()) {
            return new Response(app('Request')->post());
        }

        return false;
    }

    public function create(): void
    {
        $this->view('auth/register', 'Register');
    }

    public function signupHandle(): Response
    {
        if(app('Request')->isPost()) {
            return new Response(print_r(app('Request')->post()));
        }
    }

    public function profile(): void
    {

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