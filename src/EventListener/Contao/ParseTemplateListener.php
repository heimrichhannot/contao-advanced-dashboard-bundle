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
 * @Hook("parseTemplate", priority=-10)
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

            if (!$template->positonBottom) {
                $template->positonBottom = '<div id="tl_credits"><p style="float:right;font-size:0.9em;padding-top:18px;"><a href="https://github.com/heimrichhannot/contao-advanced-dashboard-bundle">Advanced Dashboard</a> by <a href="https://www.heimrich-hannot.de" style="font-style: italic;">Heimrich+Hannot</a></p><div class="clear"></div></div>';
            }
            $this->renderListener->prepareContaoTemplate($template);
        }
    }
}
