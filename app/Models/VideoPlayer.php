<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Model;
class VideoPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'config'
    ];

    protected $casts = [
        'config' => 'array'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
