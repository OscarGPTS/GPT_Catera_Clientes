<?php

namespace App\Services\Rh;

use App\DTOs\RhUser;
use Illuminate\Support\Collection;

interface RhClientInterface
{
    public function searchByEmail(string $email): ?RhUser;

    public function getById(string $employeeId): ?RhUser;

    /** @return Collection<int, RhUser> */
    public function listAll(): Collection;
}
