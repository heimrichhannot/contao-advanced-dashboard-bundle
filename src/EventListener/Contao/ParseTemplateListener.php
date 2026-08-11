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
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfigurationFactory;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListGenerator;

#[AsHook('parseTemplate', priority: -10)]
class ParseTemplateListener
{
    public function __construct(
        private readonly VersionListGenerator $versionListGenerator,
        private readonly VersionListConfigurationFactory $configurationFactory,
    ) {
    }

    public function __invoke(Template $template): void
    {
        if ('be_welcome' !== $template->getName()) {
            return;
        }

        ['versions' => $versions, 'columns' => $columns, 'pagination' => $pagination] =
            $this->versionListGenerator->generate($this->configurationFactory->createConfigurationForCurrentUser());

        $template->setName('be_advanced_dashboard');
        $template->versions = $versions;
        $template->pagination = $pagination;
        $template->columns = $columns;
    }
}
