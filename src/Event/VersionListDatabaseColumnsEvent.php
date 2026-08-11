<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class VersionListDatabaseColumnsEvent extends Event
{
    public function __construct(private array $columns = [])
    {
    }

    public function hasColumn(string $column): bool
    {
        return \in_array($column, $this->columns, true);
    }

    public function addColumn(string $column): void
    {
        if (!$this->hasColumn($column)) {
            $this->columns[] = $column;
        }
    }

    public function removeColumn(string $column): void
    {
        if (false !== ($key = array_search($column, $this->columns, true))) {
            unset($this->columns[$key]);
            $this->columns = array_values($this->columns);
        }
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = array_values($columns);
    }
}
