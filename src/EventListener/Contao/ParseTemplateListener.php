<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Template;
use HeimrichHannot\TwigSupportBundle\EventListener\RenderListener;

/**
 * Class ParseTemplateListener.
 *
 * @Hook("parseTemplate")
 */
class ParseTemplateListener
{
    protected RenderListener $renderListener;

    public function __construct(RenderListener $renderListener)
    {
        $this->renderListener = $renderListener;
    }

    public function __invoke(Template $template): void
    {
        if ('be_welcome' === $template->getName()) {
//            $template->setName('be_advanced_dashboard');
//            $this->renderListener->prepareContaoTemplate($template);
//            return;
        }
    }
}
