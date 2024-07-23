<?php

declare(strict_types=1);

namespace Service\Repository\Tests\Stubs;

use Service\Repository\Repositories\Repository;

class EloquentPostRepository extends Repository
{
    protected string $model = EloquentPost::class;

    protected string $repositoryId = 'repository.post';
}
