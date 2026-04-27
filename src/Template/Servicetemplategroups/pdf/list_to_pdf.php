<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

use itnovum\openITCOCKPIT\Core\Views\Logo;

/**
 * @var \App\View\AppView $this
 * @var array $servicetemplategroups
 * @var int $numberOfServicetemplategroups
 * @var int $numberOfServicetemplates
 */

$Logo = new Logo();
$css = \App\itnovum\openITCOCKPIT\Core\AngularJS\PdfAssets::getCssFiles();

?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
    foreach ($css as $cssFile): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo WWW_ROOT . $cssFile; ?>"/>
    <?php endforeach; ?>
</head>
<body>
<div class="container-fluid">

    <div class="row">
        <div class="col-6">
            <h6>
                <i class="fa-solid fa-pen-to-square"></i>
                <?php echo __('Service groups Overview'); ?>
            </h6>
        </div>
        <div class="col-6 text-end">
            <img src="<?php echo $Logo->getLogoPdfPath(); ?>" width="200"/>
        </div>
    </div>

    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-calendar"></i> <?php echo date('F d, Y H:i:s'); ?>
        </div>
    </div>
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Service template groups: ' . $numberOfServicetemplategroups); ?>
        </div>
    </div>
    <div class="col-12 mb-1">
        <div>
            <i class="fa-solid fa-list-ol"></i> <?php echo __('Number of Service templates: ' . $numberOfServicetemplates); ?>
        </div>
    </div>

    <div class="padding-top-10">
        <table class="table table-striped table-bordered table-sm m-0">
            <thead>
            <tr>
                <th><?php echo __('Service template name'); ?></th>
                <th><?php echo __('Service template description'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($servicetemplategroups as $servicetemplategroup): ?>
                <!-- Servicegroup -->
                <tr>
                    <td class="table-secondary wrap" colspan="8">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <?php echo __('Service template group: '); ?>
                        <?php echo h($servicetemplategroup['container']['name']); ?>
                    </td>
                </tr>
                <?php
                if (!empty($servicetemplategroup['servicetemplates'])):
                    foreach ($servicetemplategroup['servicetemplates'] as $servicetemplate):?>
                        <tr>
                            <!-- Servicetemplate -->
                            <td>
                                <?= h($servicetemplate['template_name']); ?>
                            </td>
                            <td>
                                <?= h($servicetemplate['description']); ?>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td class="text-center"
                            colspan="8"><?php echo __('There are no service templates assigned to this group'); ?></td>
                    </tr>
                <?php
                endif;
            endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($servicetemplategroups)): ?>
            <div class="w-100 text-center text-danger italic pt-1">
                <?php echo __('No entries match the selection'); ?>
            </div>
        <?php endif; ?>

    </div>
</div>
</body>
