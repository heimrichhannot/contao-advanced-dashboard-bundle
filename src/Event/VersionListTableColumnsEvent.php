<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class VersionListTableColumnsEvent extends Event
{
    /** @var array */
    private $columns;

    /**
     * VersionListTableColumnsEvent constructor.
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
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
     * @param string|int|null $position
     */
    public function setColumn(string $key, array $value = [], $position = null): void
    {
        if (!$this->hasColumn($key) && null !== $position) {
            $this->array_insert($this->columns, $position, [$key => $value]);
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

    /**
     * @param int|string $position
     * @param mixed      $insert
     */
    private function array_insert(array &$array, $position, array $insert)
    {
        if (!\is_int($position)) {
            $position = array_search($position, array_keys($array)) + 1;
        }
        $array = array_merge(
            \array_slice($array, 0, $position),
            $insert,
            \array_slice($array, $position)
        );
    }
}
