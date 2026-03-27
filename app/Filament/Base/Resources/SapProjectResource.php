<?php

namespace App\Filament\Base\Resources;

use App\Filament\Base\Resources\SapProjectResource\Pages;
use App\Models\Sap\SapProject;
use App\Services\Sap\SapHanaService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SapProjectResource extends Resource
{
    protected static ?string $model = SapProject::class;

    protected static ?string $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'SAP Projects';
    protected static ?string $label           = 'SAP Project';
    protected static ?string $navigationGroup = 'SAP';

    public static function form(Form $form): Form
    {
        // Kita tidak membuat / edit project di sini, pure view dari SAP.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('PrjCode')
                    ->label('Project Code')
                    ->searchable()
                    ,

                Tables\Columns\TextColumn::make('PrjName')
                    ->label('Project Name')
                    ->wrap()
                    ->searchable()
                    ,

                Tables\Columns\TextColumn::make('ValidFrom')
                    ->label('Valid From')
                    ->date()
                    ,

                Tables\Columns\TextColumn::make('ValidTo')
                    ->label('Valid To')
                    ->date()
                    ,

                Tables\Columns\IconColumn::make('Active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(function (SapProject $record) {
                        // Di SAP biasanya 'Y' / 'N'
                        return strtoupper($record->Active ?? '') === 'Y';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Project')
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (SapProject $record) {
                        /** @var SapHanaService $service */
                        $service = app(SapHanaService::class);

                        $detail = $service->getProjectByCode($record->PrjCode);

                        return view('filament.base.sap.modals.project-detail', [
                            'project' => $detail,
                        ]);
                    }),
            ])
            ->defaultSort('PrjCode', 'desc')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSapProjects::route('/'),
        ];
    }

    // Disable create/edit/delete – pure view SAP
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
