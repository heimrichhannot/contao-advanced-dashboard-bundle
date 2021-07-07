<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Template;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfigurationFactory;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListGenerator;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;

/**
 * Class ParseTemplateListener.
 *
 * @Hook("parseTemplate")
 */
class ParseTemplateListener
{
    /** @var RenderListener */
    protected $renderListener;

    /** @var VersionListGenerator */
    protected $versionListGenerator;

    /** @var VersionListConfigurationFactory */
    protected $configurationFactory;

    public function __construct(RenderListener $renderListener, VersionListGenerator $versionListGenerator, VersionListConfigurationFactory $configurationFactory)
    {
        $this->renderListener = $renderListener;
        $this->versionListGenerator = $versionListGenerator;
        $this->configurationFactory = $configurationFactory;
    }

    public function __invoke(Template $template): void
    {
        if ('be_welcome' === $template->getName()) {
            ['versions' => $versions, 'columns' => $columns, 'pagination' => $pagination] =
                $this->versionListGenerator->generate($this->configurationFactory->createConfigurationForCurrentUser());
            $template->setName('be_advanced_dashboard');
            $template->versions = $versions;
            $template->pagination = $pagination;
            $template->columns = $columns;
            $this->renderListener->prepareContaoTemplate($template);
        }
    }
}
