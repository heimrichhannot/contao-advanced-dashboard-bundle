<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\EventListener;

use Symfony\Contracts\EventDispatcher\Event;

class VersionListDatabaseColumnsEvent extends Event
{
    private $fields = [];

    /**
     * VersionListDatabaseColumnsEvent constructor.
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function hasField(string $field): bool
    {
        return \in_array($field, $this->fields);
    }

    public function addField(string $field): void
    {
        if (!$this->hasField($field)) {
            $this->fields[] = $field;
        }
    }

    public function removeField(string $field): void
    {
        if (false !== ($key = array_search($field, $this->fields))) {
            unset($this->fields[$key]);
        }
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }
}
