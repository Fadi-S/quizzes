<?php

namespace App\Filament\Game\Resources;

use App\Enums\QuestionType;
use App\Filament\Game\Resources\QuizResource\Pages;
use App\Filament\Game\Resources\QuizResource\RelationManagers;
use App\Models\Group;
use App\Models\Question;
use App\Models\Quiz;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                        if ($operation === 'create') {
                            $set('slug', str($state)->slug(language: null));
                        }
                    })
                    ->required(),

                Forms\Components\TextInput::make('slug')
                    ->disabled(),

                Forms\Components\Select::make('group_id')
                    ->label('Group')
                    ->options(fn() => Group::pluck('name', 'id')),

                Forms\Components\Repeater::make('questions')
                    ->columnSpan('full')
                    ->relationship()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn($state) => $state['title'] ?? 'New Question')
                    ->saveRelationshipsBeforeChildrenUsing(function (Quiz $record, $state) {
                        $questions = collect($state)->map(function ($question) use($record) {
                            $i = 1;
                            $correctAnswers = collect();
                            foreach ($question['options'] as $option) {
                                $order = $i++;
                                if ($option['is_correct']) {
                                    $correctAnswers->push($order);
                                }
                            }

                            $question['correct_answers'] = $correctAnswers->toJson();
                            unset($question['options']);
                            $question['picture'] = $question['picture'] ?: null;
                            $question['quiz_id'] = $record->id;
                            $question['id'] ??= null;

                            $question['created_at'] = Carbon::parse($question['created_at'] ?? now())->format('Y-m-d H:i:s');
                            $question['updated_at'] = Carbon::parse($question['updated_at'] ?? now())->format('Y-m-d H:i:s');

                            return $question;
                        });

                        Question::upsert($questions->all(), ['id'], ['title', 'type', 'correct_answers', 'picture', 'quiz_id']);
                    })
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->live()
                            ->required(),

                        Forms\Components\Radio::make('type')
                            ->live()
                            ->columns(3)
                            ->disabled(fn(?Model $record) => $record !== null && $record->exists)
                            ->options(QuestionType::toArray())
                            ->required(),

                        Forms\Components\FileUpload::make('picture')
                            ->visibility('public')
                            ->image()
                            ->nullable(),
//
//                        Forms\Components\ViewField::make('options')
//                            ->view('filament.forms.components.question-options'),

                        Forms\Components\Repeater::make('options')
                            ->grid()
                            ->relationship()
                            ->minItems(function ($get) {
                                $type = QuestionType::tryFrom($get('type'));
                                if ($type === QuestionType::Choose) {
                                    return 2;
                                }

                                return 1;
                            })
                            ->maxItems(function ($get) {
                                $type = QuestionType::tryFrom($get('type'));

                                if ($type === QuestionType::Written) {
                                    return null;
                                }

                                return 6;
                            })
                            ->orderColumn('order')
                            ->itemLabel(fn($state) => $state['name'] ?? '...')
                            ->reorderableWithDragAndDrop()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->live()
                                    ->columnSpan(1)
                                    ->required(),
                                Forms\Components\FileUpload::make('picture')
                                    ->columnSpan(1)
                                    ->visibility('public')
                                    ->image()
                                    ->nullable(),

                                Forms\Components\Checkbox::make('is_correct')
                                    ->afterStateHydrated(function ($state, Forms\Get $get, $set) {
                                        $answers = collect($get('../../correct_answers') ?: []);

                                        $set("is_correct", $answers->contains($get('order')));
                                    })
                                    ->dehydrated(false)
                                    ->visible(fn($get) => QuestionType::tryFrom($get('../../type')) === QuestionType::Choose),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }
}
