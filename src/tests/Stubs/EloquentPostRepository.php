<?php

declare(strict_types=1);

namespace Nucleus\Repository\Tests\Stubs;

use Nucleus\Nucleus\Repository\Repositories\BaseRepository;

class EloquentPostRepository extends BaseRepository
{
    protected string $model = EloquentPost::class;

    protected string $repositoryId = 'repository.post';
}
