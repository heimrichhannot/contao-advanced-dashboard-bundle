<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class VersionsListDatabaseColumnsEvent extends Event
{
    private $columns = [];

    /**
     * VersionListDatabaseColumnsEvent constructor.
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function hasColumn(string $column): bool
    {
        return \in_array($column, $this->columns);
    }

    public function addColumn(string $column): void
    {
        if (!$this->hasColumn($column)) {
            $this->columns[] = $column;
        }
    }

    public function removeColumn(string $column): void
    {
        if (false !== ($key = array_search($column, $this->columns))) {
            unset($this->columns[$key]);
        }
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }
}
