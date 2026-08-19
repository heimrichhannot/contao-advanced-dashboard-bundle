<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\Translation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\PhpFileLoader;

class SymfonyPhpTranslationTest extends TestCase
{
    public function testContaoTranslationsUseSymfonyPhpResources(): void
    {
        $loader = new PhpFileLoader();
        $translations = [
            'contao_tl_user' => [
                'tl_user.huhAdvDash_legend' => 'Erweitertes Dashboard - Rechte',
                'tl_user.huhAdvDash_versionsRights.0' => 'Versions-Rechte',
                'tl_user.huhAdvDash_versionsRights.1' => 'Wählen Sie hier die Versions-Rechte aus.',
            ],
            'contao_tl_user_group' => [
                'tl_user_group.huhAdvDash_legend' => 'Erweitertes Dashboard - Rechte',
                'tl_user_group.huhAdvDash_versionsRights.0' => 'Versions-Rechte',
                'tl_user_group.huhAdvDash_versionsRights.1' => 'Wählen Sie hier die Versions-Rechte aus.',
            ],
        ];

        foreach ($translations as $domain => $expected) {
            $catalogue = $loader->load(
                __DIR__.'/../../translations/'.$domain.'.de.php',
                'de',
                $domain,
            );

            self::assertSame($expected, $catalogue->all($domain));
        }
    }
}
