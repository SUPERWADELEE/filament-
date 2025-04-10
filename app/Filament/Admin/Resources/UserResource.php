<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                // Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\Select::make('role')
                    ->label('角色權限')
                    ->options([
                        'patient' => '患者',
                        'doctor' => '醫師',
                        'receptionist' => '櫃檯人員',
                        'admin' => '系統管理員',
                    ])
                    ->required()
                    ->afterStateUpdated(function ($state) {
                        // 添加這一行來檢查狀態更新
                        \Log::info('Role updated to: ' . $state);
                    }),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->searchable()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->searchable()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'patient' => '患者',
                        'doctor' => '醫師',
                        'receptionist' => '櫃檯人員',
                        'admin' => '系統管理員',
                    ]),
                Tables\Filters\SelectFilter::make('is_approved')
                    ->options([
                        '1' => '已審核',
                        '0' => '未審核',
                    ]),

                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    // public static function getWidgets(): array
    // {
    //     return [
    //         CalendarWidget::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    // 判斷是否為醫生
    public function isDoctor()
    {
        return $this->role === 'doctor';
    }
    // 判斷是否為患者
    public function isPatient()
    {
        return $this->role === 'patient';
    }
    // 判斷是否為櫃檯人員
    public function isReceptionist()
    {
        return $this->role === 'receptionist';
    }
    // 判斷是否為系統管理員
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
