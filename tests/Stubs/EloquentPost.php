<?php

declare(strict_types=1);

namespace Service\Repository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class EloquentPost extends Model
{
    protected $table = 'posts';

    protected $fillable = ['user_id', 'parent_id', 'name'];

    public function user()
    {
        $this->belongsTo(EloquentUser::class);
    }
}
