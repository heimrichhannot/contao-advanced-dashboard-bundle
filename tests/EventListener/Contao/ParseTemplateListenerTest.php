<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\EventListener\Contao;

use Contao\Template;
use HeimrichHannot\AdvancedDashboardBundle\EventListener\Contao\ParseTemplateListener;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfigurationFactory;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListGenerator;
use PHPUnit\Framework\TestCase;

class ParseTemplateListenerTest extends TestCase
{
    public function testIgnoresOtherTemplates(): void
    {
        $generator = $this->createMock(VersionListGenerator::class);
        $generator->expects(self::never())->method('generate');

        $listener = new ParseTemplateListener($generator, $this->createMock(VersionListConfigurationFactory::class));
        $template = $this->createTemplate('be_main');
        $listener($template);

        self::assertSame('be_main', $template->getName());
    }

    public function testPreparesTheNativeTwigDashboardTemplate(): void
    {
        $configuration = new VersionListConfiguration([], [], 0);
        $configurationFactory = $this->createMock(VersionListConfigurationFactory::class);
        $configurationFactory->method('createConfigurationForCurrentUser')->willReturn($configuration);

        $receivedConfiguration = null;
        $generator = $this->createMock(VersionListGenerator::class);
        $generator
            ->expects(self::once())
            ->method('generate')
            ->willReturnCallback(
                static function (VersionListConfiguration $actualConfiguration) use (&$receivedConfiguration): array {
                    $receivedConfiguration = $actualConfiguration;

                    return [
                        'versions' => [['class' => 'even', 'cols' => ['id' => '1']]],
                        'columns' => ['id' => ['label' => 'ID']],
                        'pagination' => '<nav>Pagination</nav>',
                    ];
                },
            )
        ;

        $listener = new ParseTemplateListener($generator, $configurationFactory);
        $template = $this->createTemplate('be_welcome');
        $listener($template);

        self::assertSame($configuration, $receivedConfiguration);
        self::assertSame('be_advanced_dashboard', $template->getName());
        self::assertSame([['class' => 'even', 'cols' => ['id' => '1']]], $template->versions);
        self::assertSame(['id' => ['label' => 'ID']], $template->columns);
        self::assertSame('<nav>Pagination</nav>', $template->pagination);
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
