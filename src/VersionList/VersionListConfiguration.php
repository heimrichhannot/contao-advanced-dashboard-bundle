<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

class VersionListConfiguration
{
    const USER_ACCESS_LEVEL_ALL = 'all';
    const USER_ACCESS_LEVEL_SELF = 'self';

    /** @var array|int */
    protected $allowedUsers;

    /** @var array */
    private $tables;

    /** @var array */
    private $columns;

    /**
     * VersionListConfiguration constructor.
     */
    public function __construct(array $tables, array $columns, $allowedUsers)
    {
        $this->tables = $tables;
        $this->columns = $columns;

        if (!\is_array($allowedUsers) && !\is_int($allowedUsers)) {
            throw new \InvalidArgumentException('User must be either integer or an array of integers.');
        }
        $this->allowedUsers = $allowedUsers;
    }

    public function getTables(): array
    {
        return $this->tables ?? [];
    }

    public function getColumns(): array
    {
        return $this->columns ?? [];
    }

    /**
     * @return array|int
     */
    public function getAllowedUsers()
    {
        return $this->allowedUsers;
    }
}
