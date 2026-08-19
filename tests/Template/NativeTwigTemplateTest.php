<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\Template;

use Contao\CoreBundle\Pagination\PaginationInterface;
use Contao\CoreBundle\String\HtmlAttributes;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class NativeTwigTemplateTest extends TestCase
{
    public function testTemplateCanBeCompiledAndExposesItsExtensionBlocks(): void
    {
        $loader = new FilesystemLoader(__DIR__.'/../../contao/templates');
        $loader->addPath(__DIR__.'/../../vendor/contao/core-bundle/contao/templates/twig', 'Contao');

        $twig = new Environment(
            $loader,
            ['autoescape' => 'html', 'strict_variables' => true],
        );
        $twig->addExtension(new TranslationExtension(new IdentityTranslator()));
        $twig->addFunction(new TwigFunction(
            'attrs',
            static fn (iterable|string|null $attributes = null): HtmlAttributes => new HtmlAttributes($attributes),
        ));

        $template = $twig->load('be_advanced_dashboard.html.twig');

        self::assertSame(
            [
                'dashboard',
                'messages',
                'shortcuts',
                'versions',
                'credits',
            ],
            $template->getBlockNames(),
        );

        $pagination = $this->createStub(PaginationInterface::class);
        $pagination->method('getPageCount')->willReturn(1);

        $html = $template->render([
            'systemMessages' => 'System messages',
            'messages' => '<p>Message</p>',
            'loginMsg' => 'Last login',
            'shortcuts' => 'Keyboard shortcuts',
            'shortcutsLink' => '<a href="https://example.com">Documentation</a>',
            'versions' => [[
                'date' => '2026-08-19 12:00',
                'username' => 'Example User',
                'shortTable' => 'tl_page',
                'pid' => 1,
                'description' => 'Updated page',
                'version' => 2,
                'active' => true,
                'operations' => 'Edit',
            ]],
            'pagination' => $pagination,
        ]);

        self::assertStringContainsString('<p>Message</p>', $html);
        self::assertStringContainsString('<th>ID</th>', $html);
        self::assertStringContainsString('<td>Example User</td>', $html);
        self::assertStringContainsString('<td>1</td>', $html);
        self::assertStringContainsString('<td>Updated page</td>', $html);
        self::assertStringContainsString('<td class="tl_right_nowrap">Edit</td>', $html);
    }
}
