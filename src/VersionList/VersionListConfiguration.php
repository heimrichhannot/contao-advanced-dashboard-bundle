<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

class VersionListConfiguration
{
    public int $perPage = 15;

    /** @param list<int>|int $allowedUsers */
    public function __construct(
        private readonly array $tables,
        private readonly array|int $allowedUsers,
    )
    {
        if (\is_array($allowedUsers) && ([] === $allowedUsers || count($allowedUsers) !== count(array_filter($allowedUsers, 'is_int')))) {
            throw new \InvalidArgumentException('Users must be a non-empty list of integers.');
        }
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    /** @return list<int>|int */
    public function getAllowedUsers(): array|int
    {
        return $this->allowedUsers;
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }
}
