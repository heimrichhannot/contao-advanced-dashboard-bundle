<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$dca = &$GLOBALS['TL_DCA']['tl_user_group'];

PaletteManipulator::create()
    ->addLegend('huhAdvDash_legend', 'forms_legend', PaletteManipulator::POSITION_AFTER)
//    ->addField('huhAdvDash_visibleUserGroups', 'huhAdvDash_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('huhAdvDash_versionsRights', 'huhAdvDash_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group');

//$dca['fields']['huhAdvDash_visibleUserGroups'] = [
//    'exclude'    => true,
//    'inputType'  => 'checkbox',
//    'foreignKey' => 'tl_user_group.name',
//    'eval'       => ['multiple' => true],
//    'sql'        => "blob NULL"
//];

$dca['fields']['huhAdvDash_versionsRights'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];
