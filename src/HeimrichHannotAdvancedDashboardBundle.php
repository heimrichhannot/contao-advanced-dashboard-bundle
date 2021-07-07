<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle;

use HeimrichHannot\AdvancedDashboardBundle\DependencyInjection\HeimrichHannotAdvancedDashboardExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotAdvancedDashboardBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new HeimrichHannotAdvancedDashboardExtension();
    }
}
