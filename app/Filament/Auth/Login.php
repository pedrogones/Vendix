<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

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

    public function getSubheading(): string | Htmlable | null
    {
        return new HtmlString('Não tem conta? ' . $this->registerAction->toHtml());
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
