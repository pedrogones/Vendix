<?php

namespace App\Filament\Auth;

use App\Models\Client;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('email')
                ->label('E-mail')
                ->required()
                ->autocomplete(),

            TextInput::make('password')
                ->label('Senha')
                ->password()
                ->required(),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        if (! auth()->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        if (auth()->user()->hasRole('Cliente')) {
            auth()->logout();
            throw ValidationException::withMessages([
                'email' => 'Usuário sem permissão para acessar o painel.',
            ]);
        }

        return null;
    }



    public function getSubheading(): string | Htmlable | null
    {
        return new HtmlString('N�o tem conta? ' . $this->registerAction->toHtml());
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->label('Cadastre-se')
            ->url(filament()->getRegistrationUrl())
            ->link()
            ->extraAttributes(['class' => 'underline font-semibold']);
    }
}



















