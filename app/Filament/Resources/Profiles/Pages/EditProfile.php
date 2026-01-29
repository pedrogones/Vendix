<?php

namespace App\Filament\Resources\Profiles\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditProfile extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user';
//    protected static string $view = 'filament.pages.custom-profile-page';

    public $name;
    public $email;
    public $avatar_url;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->avatar_url = $user->avatar_url;
    }

    protected function getFormSchema(): array
    {
        return [
            FileUpload::make('avatar_url')
                ->label('Foto de perfil')
                ->image()
                ->disk('public')
                ->directory('avatars')
                ->maxSize(2048)
                ->acceptedFileTypes(['image/jpeg', 'image/png'])
                ->storeFiles(false),

            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('email')->label('E-mail')->email()->required(),
        ];
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'avatar_url' => 'nullable',
        ]);

        if ($this->avatar_url instanceof TemporaryUploadedFile) {
            $this->validate([
                'avatar_url' => 'image|max:2048',
            ]);
            $avatarPath = $this->avatar_url->store('avatars', 'public');
            auth()->user()->update(['avatar_url' => $avatarPath]);
        }

        auth()->user()->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $this->notify('success', 'Perfil atualizado!');
    }
}
