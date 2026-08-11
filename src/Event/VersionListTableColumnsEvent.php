<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class VersionListTableColumnsEvent extends Event
{
    public function __construct(private array $columns)
    {
    }

    /**
     * Return if a column exist.
     */
    public function hasColumn(string $key): bool
    {
        return isset($this->columns[$key]);
    }

    /**
     * Set a column.
     * If the column already exists, it gets overridden.
     * If the column not exist, it's added to the end of the column list or at a given position.
     *
     */
    public function setColumn(string $key, array $value = [], int|string|null $position = null): void
    {
        if (!$this->hasColumn($key) && null !== $position) {
            $this->insert($this->columns, $position, [$key => $value]);
        } else {
            $this->columns[$key] = $value;
        }
    }

    /**
     * Return a column.
     */
    public function getColumn(string $key): ?array
    {
        return $this->columns[$key] ?? null;
    }

    /**
     * Remove a column.
     */
    public function removeColumn(string $key): void
    {
        if ($this->hasColumn($key)) {
            unset($this->columns[$key]);
        }
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    private function insert(array &$array, int|string $position, array $insert): void
    {
        if (!\is_int($position)) {
            $position = array_search($position, array_keys($array), true);
            $position = false === $position ? count($array) : $position + 1;
        }
        $array = array_merge(
            \array_slice($array, 0, $position),
            $insert,
            \array_slice($array, $position),
        );
    }
}
