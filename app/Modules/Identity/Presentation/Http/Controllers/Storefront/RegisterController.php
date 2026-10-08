<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers\Storefront;

use App\Modules\Identity\Application\DTOs\RegisterCustomerData;
use App\Modules\Identity\Application\UseCases\RegisterCustomer;
use App\Modules\Identity\Presentation\Http\Requests\RegisterRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class RegisterController
{
    public function create(): View
    {
        return view('identity::auth.register');
    }

    public function store(RegisterRequest $request, RegisterCustomer $registerCustomer): RedirectResponse
    {
        $data = $request->validated();

        $userId = $registerCustomer->handle(new RegisterCustomerData(
            name: (string) $data['name'],
            email: (string) $data['email'],
            phone: (string) $data['phone'],
            password: (string) $data['password'],
        ));

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('home'))
            ->with('success', 'Welcome to '.config('app.name').'! Your account is ready.');
    }
}
