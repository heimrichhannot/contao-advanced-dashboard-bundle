<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\EventListener\Contao;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Template;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListBuilder;

#[AsHook('parseTemplate', priority: -10)]
readonly class ParseTemplateListener
{
    public function __construct(
        private VersionListBuilder $versionListBuilder,
    ) {
    }

    public function __invoke(Template $template): void
    {
        if ('be_welcome' !== $template->getName()) {
            return;
        }

        $versionList = $this->versionListBuilder->buildForCurrentUser();

        $template->setName('be_advanced_dashboard');
        $template->versions = $versionList->rows();
        $template->pagination = $versionList->pagination();
    }
}
