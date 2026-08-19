<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$dca = &$GLOBALS['TL_DCA']['tl_user'];

PaletteManipulator::create()
    ->addLegend('huhAdvDash_legend', 'forms_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField('huhAdvDash_versionsRights', 'huhAdvDash_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;

$dca['fields']['huhAdvDash_versionsRights'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];
