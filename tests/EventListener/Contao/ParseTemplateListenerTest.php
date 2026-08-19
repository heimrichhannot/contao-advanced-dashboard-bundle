<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\EventListener\Contao;

use Contao\CoreBundle\Pagination\PaginationInterface;
use Contao\Template;
use HeimrichHannot\AdvancedDashboardBundle\EventListener\Contao\ParseTemplateListener;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionList;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListBuilder;
use PHPUnit\Framework\TestCase;

class ParseTemplateListenerTest extends TestCase
{
    public function testIgnoresOtherTemplates(): void
    {
        $versionListBuilder = $this->createMock(VersionListBuilder::class);
        $versionListBuilder->expects(self::never())->method('buildForCurrentUser');

        $listener = new ParseTemplateListener($versionListBuilder);
        $template = $this->createTemplate('be_main');
        $listener($template);

        self::assertSame('be_main', $template->getName());
    }

    public function testPreparesTheNativeTwigDashboardTemplate(): void
    {
        $pagination = $this->createStub(PaginationInterface::class);
        $versionListBuilder = $this->createMock(VersionListBuilder::class);
        $versionListBuilder
            ->expects(self::once())
            ->method('buildForCurrentUser')
            ->willReturn(new VersionList(
                rows: [['id' => 1]],
                pagination: $pagination,
            ))
        ;

        $listener = new ParseTemplateListener($versionListBuilder);
        $template = $this->createTemplate('be_welcome');
        $listener($template);

        self::assertSame('be_advanced_dashboard', $template->getName());
        self::assertSame([['id' => 1]], $template->versions);
        self::assertSame($pagination, $template->pagination);
    }

    private function createTemplate(string $name): Template
    {
        return new class($name) extends Template {
            public function __construct(string $name)
            {
                $this->setName($name);
            }
        };
    }
}
