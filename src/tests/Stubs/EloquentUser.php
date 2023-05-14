<?php

declare(strict_types=1);

namespace Nucleus\Repository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class EloquentUser extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'age'];

    public function posts()
    {
        return $this->hasMany(EloquentPost::class, 'user_id');
    }
}
