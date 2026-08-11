<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\VersionList;

use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use PHPUnit\Framework\TestCase;

class VersionListConfigurationTest extends TestCase
{
    public function testExposesItsRestrictions(): void
    {
        $configuration = new VersionListConfiguration(['tl_news'], ['date'], [1, 2]);

        self::assertSame(['tl_news'], $configuration->getTables());
        self::assertSame(['date'], $configuration->getColumns());
        self::assertSame([1, 2], $configuration->getAllowedUsers());
    }

    public function testRejectsAnEmptyUserList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VersionListConfiguration([], [], []);
    }

    public function testRejectsAUserListWithNonIntegerValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VersionListConfiguration([], [], [1, '2']);
    }
}
