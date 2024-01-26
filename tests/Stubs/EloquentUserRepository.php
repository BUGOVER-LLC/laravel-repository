<?php

declare(strict_types=1);

namespace Service\Repository\Tests\Stubs;

use Service\Repository\Traits\Criteria;
use Service\Service\Repository\Repositories\BaseRepository;

class EloquentUserRepository extends BaseRepository
{
    use Criteria;

    protected string $model = EloquentUser::class;

    protected string $repositoryId = 'repository.user';
}
