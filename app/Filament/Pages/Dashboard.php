<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('year')
                    ->label('Tahun')
                    ->options(array_combine(
                        range(date('Y'), date('Y') - 5),
                        range(date('Y'), date('Y') - 5)
                    ))
                    ->default(date('Y')),
            ])
            ->columns(3);
    }
}
