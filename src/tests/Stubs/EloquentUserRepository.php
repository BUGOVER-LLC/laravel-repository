<?php

declare(strict_types=1);

namespace Nucleus\Repository\Tests\Stubs;

use Nucleus\Repository\Traits\Criteria;
use Nucleus\Nucleus\Repository\Repositories\BaseRepository;

class EloquentUserRepository extends BaseRepository
{
    use Criteria;

    protected string $model = EloquentUser::class;

    protected string $repositoryId = 'repository.user';
}
