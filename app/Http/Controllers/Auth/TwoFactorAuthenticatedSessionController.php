<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController as FortifyTwoFactorAuthenticatedSessionController;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /**
     * Attempt to authenticate a user using the two factor challenge.
     */
    public function store(Request $request): mixed
    {
        /** @var FortifyTwoFactorAuthenticatedSessionController $controller */
        $controller = app(FortifyTwoFactorAuthenticatedSessionController::class);

        return $controller($request);
    }
}
