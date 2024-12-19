<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Filament\Forms;

class Quiz extends Model
{
    use HasRelationships;

    protected static function boot()
    {
        static::creating(function ($model) {

            $model->slug = str($model->name)->slug(language: null);
        });

        parent::boot();
    }

    public function group() : BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function game() : HasOneThrough
    {
        return $this->hasOneThrough(Game::class, Group::class, 'id', 'id', 'group_id', 'game_id');
    }

    public function questions() : HasMany
    {
        return $this->hasMany(Question::class)->with("options");
    }

    public static function getForm() : array
    {
        return [
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
                ->options(fn() => Group::pluck('name', 'id'))
                ->required()
                ->searchable()
            ,

            Forms\Components\Repeater::make('questions')
                ->columnSpan('full')
                ->relationship()
                ->collapsible()
                ->cloneable()
                ->itemLabel(fn($state) => $state['title'] ?? 'New Question')
                ->mutateRelationshipDataBeforeSaveUsing(function (array $data) {
                    $i = 1;
                    $correctAnswers = collect();
                    foreach ($data['options'] as $option) {
                        $order = $i++;
                        if ($option['is_correct']) {
                            $correctAnswers->push($order);
                        }
                    }

                    $data['correct_answers'] = $correctAnswers->all();

                    return $data;
                })
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                    $i = 1;
                    $correctAnswers = collect();
                    foreach ($data['options'] as $option) {
                        $order = $i++;
                        if ($option['is_correct']) {
                            $correctAnswers->push($order);
                        }
                    }

                    $data['correct_answers'] = $correctAnswers->all();

                    return $data;
                })
                ->schema(Question::getForm()),
        ];
    }
}
