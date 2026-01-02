<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static UnitEnum|string|null $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Apariencia';
    protected static ?string $title = 'Configuración de la app';

    protected string $view = 'filament.pages.app-settings';

    public ?string $app_icon_color = null;

    public function mount(): void
    {
        $settings = AppSetting::current();
        $this->form->fill([
            'app_icon_color' => $settings->app_icon_color,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ColorPicker::make('app_icon_color')
                    ->label('Color del logo / icono')
                    ->helperText('Este color se usará para el icono en accesos directos (iOS/Android).')
                    ->required()
                    ->default('#f59e0b'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = AppSetting::current();
        $settings->update([
            'app_icon_color' => $data['app_icon_color'],
        ]);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
