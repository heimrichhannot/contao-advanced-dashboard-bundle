<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class NativeTwigTemplateTest extends TestCase
{
    public function testTemplateCanBeCompiledAndExposesItsExtensionBlocks(): void
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__.'/../../contao/templates'),
            ['autoescape' => 'html', 'strict_variables' => true],
        );
        $twig->addExtension(new TranslationExtension(new IdentityTranslator()));

        $template = $twig->load('be_advanced_dashboard.html.twig');

        self::assertSame(
            [
                'dashboard',
                'dashboard_top',
                'messages',
                'before_shortcuts',
                'shortcuts',
                'before_versions',
                'versions',
                'dashboard_bottom',
                'credits',
            ],
            $template->getBlockNames(),
        );

        $html = $template->render([
            'systemMessages' => 'System messages',
            'messages' => '<p>Message</p>',
            'loginMsg' => 'Last login',
            'shortcuts' => 'Keyboard shortcuts',
            'shortcutsLink' => '<a href="https://example.com">Documentation</a>',
            'versions' => [['class' => 'even', 'cols' => ['id' => '<strong>1</strong>']]],
            'columns' => ['id' => ['label' => 'ID', 'class' => 'tl_right_nowrap']],
            'pagination' => '<nav>Pagination</nav>',
        ]);

        self::assertStringContainsString('<p>Message</p>', $html);
        self::assertStringContainsString('<th class="tl_right_nowrap">ID</th>', $html);
        self::assertStringContainsString('<td class="tl_right_nowrap"><strong>1</strong></td>', $html);
        self::assertStringContainsString('<nav>Pagination</nav>', $html);
    }
}
