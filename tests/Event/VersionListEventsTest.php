<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\Event;

use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListDatabaseColumnsEvent;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListTableColumnsEvent;
use PHPUnit\Framework\TestCase;

class VersionListEventsTest extends TestCase
{
    public function testDatabaseColumnsCanBeAddedRemovedAndReplaced(): void
    {
        $event = new VersionListDatabaseColumnsEvent(['pid', 'tstamp']);
        $event->addColumn('custom');
        $event->addColumn('custom');
        $event->removeColumn('pid');

        self::assertSame(['tstamp', 'custom'], $event->getColumns());

        $event->setColumns(['version']);

        self::assertSame(['version'], $event->getColumns());
    }

    public function testTableColumnsCanBeInsertedAfterAnExistingColumn(): void
    {
        $event = new VersionListTableColumnsEvent([
            'date' => ['label' => 'Date'],
            'actions' => [],
        ]);
        $event->setColumn('custom', ['label' => 'Custom'], 'date');

        self::assertSame(['date', 'custom', 'actions'], array_keys($event->getColumns()));

        $event->removeColumn('custom');

        self::assertFalse($event->hasColumn('custom'));
    }
}
