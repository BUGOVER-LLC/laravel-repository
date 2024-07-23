<?php

declare(strict_types=1);

namespace Service\Repository\Tests\Stubs;

use Service\Repository\Repositories\Repository;
use Service\Repository\Traits\Criteria;

class EloquentUserRepository extends Repository
{
    use Criteria;

    protected string $model = EloquentUser::class;

    protected string $repositoryId = 'repository.user';
}
