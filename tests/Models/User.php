<?php

namespace Tests\Models;

use Feeldee\MediaBox\Models\HasMediaBox;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * ユーザをあらわすモデル
 */
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    use  HasFactory, HasMediaBox;
}
