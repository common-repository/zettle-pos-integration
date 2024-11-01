<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\User;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\User\User;

interface UserBuilderInterface
{
    /**
     * @param array $data
     *
     * @return User
     */
    public function buildFromArray(array $data): User;

    /**
     * @param User $user
     * @return array
     */
    public function createDataArray(User $user): array;
}
