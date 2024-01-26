<?php

declare(strict_types=1);

namespace Service\Repository\Tests\Stubs;

use Service\Service\Repository\Repositories\BaseRepository;

class EloquentPostRepository extends BaseRepository
{
    protected string $model = EloquentPost::class;

    protected string $repositoryId = 'repository.post';
}
