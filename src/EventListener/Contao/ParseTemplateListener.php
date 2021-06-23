<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Template;
use HeimrichHannot\AdvancedDashboardBundle\Generator\VersionsListGenerator;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;

/**
 * Class ParseTemplateListener.
 *
 * @Hook("parseTemplate")
 */
class ParseTemplateListener
{
    protected $renderListener;
    /**
     * @var VersionsListGenerator
     */
    protected $versionListGenerator;

    public function __construct(RenderListener $renderListener, VersionsListGenerator $versionListGenerator)
    {
        $this->renderListener = $renderListener;
        $this->versionListGenerator = $versionListGenerator;
    }

    public function __invoke(Template $template): void
    {
        if ('be_welcome' === $template->getName()) {
            ['versions' => $versions, 'columns' => $columns, 'pagination' => $pagination] =
                $this->versionListGenerator->generate();
            $template->setName('be_advanced_dashboard');
            $template->versions = $versions;
            $template->pagination = $pagination;
            $template->columns = $columns;
            $this->renderListener->prepareContaoTemplate($template);
        }
    }
}
