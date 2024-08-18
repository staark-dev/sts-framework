<?php

namespace STS\app\Controllers;
use App\Controllers\BaseController;
use STS\core\Container;
use STS\core\Themes\ThemeManager;
use STS\core\Routing\Router;

class HomeController extends BaseController {
    public function index()
    {
        $theme = Container::getInstance()->make(ThemeManager::class);
        $theme->assign('app_name', 'My Application');

        // Utilizează metoda view definită în HelperTrait
        $this->themeManager->display('home');
    }
}
