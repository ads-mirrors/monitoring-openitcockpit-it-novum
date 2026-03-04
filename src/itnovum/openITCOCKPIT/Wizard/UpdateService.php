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

namespace App\itnovum\openITCOCKPIT\Wizard;

use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ServicesTable;
use itnovum\openITCOCKPIT\Cache\ObjectsCache;
use itnovum\openITCOCKPIT\Core\Comparison\ServiceComparisonForSave;
use itnovum\openITCOCKPIT\Core\Merger\ServiceMergerForView;

class UpdateService {

    /**
     * @param int $serviceId
     * @param array $postData
     * @param array $servicetemplate
     * @param array $host
     * @param array $hostContactsAndContactgroups
     * @param array $hosttemplateContactsAndContactgroups
     * @param ServicesTable $ServicesTable
     * @param ChangelogsTable $ChangelogsTable
     * @param int $userId
     * @param ObjectsCache|null $ObjectsCacheChangelog
     * @return array
     */
    public static function save(
        int             $serviceId,
        array           $postData,
        array           $servicetemplate,
        array           $host,
        array           $hostContactsAndContactgroups,
        array           $hosttemplateContactsAndContactgroups,
        ServicesTable   $ServicesTable,
        ChangelogsTable $ChangelogsTable,
        int             $userId = 0, // 0 for cronjob
        ?ObjectsCache   $ObjectsCacheChangelog = null
    ): array {
        $service = $ServicesTable->getServiceForEdit($serviceId);

        $ServiceMergerForView = new ServiceMergerForView(
            $service,
            $servicetemplate,
            $hostContactsAndContactgroups,
            $hosttemplateContactsAndContactgroups
        );
        $mergedService = $ServiceMergerForView->getDataForView();
        $serviceForChangelog = $mergedService;

        // Update the fields
        foreach ($postData as $key => $value) {
            $mergedService['Service'][$key] = $value;
        }

        $newServiceForChangelogWithAllFields = $mergedService;

        $ServiceComparisonForSave = new ServiceComparisonForSave(
            $mergedService,
            $servicetemplate,
            $hostContactsAndContactgroups,
            $hosttemplateContactsAndContactgroups
        );
        $dataForSave = $ServiceComparisonForSave->getDataForSaveForAllFields();

        //Add required fields for validation
        $dataForSave['servicetemplate_flap_detection_enabled'] = $servicetemplate['Servicetemplate']['flap_detection_enabled'];
        $dataForSave['servicetemplate_flap_detection_on_ok'] = $servicetemplate['Servicetemplate']['flap_detection_on_ok'];
        $dataForSave['servicetemplate_flap_detection_on_warning'] = $servicetemplate['Servicetemplate']['flap_detection_on_warning'];
        $dataForSave['servicetemplate_flap_detection_on_critical'] = $servicetemplate['Servicetemplate']['flap_detection_on_critical'];
        $dataForSave['servicetemplate_flap_detection_on_unknown'] = $servicetemplate['Servicetemplate']['flap_detection_on_unknown'];

        //Update service data
        $serviceEntity = $ServicesTable->get($serviceId);
        $serviceEntity->setAccess('uuid', false);
        $serviceEntity->setAccess('id', false);
        $serviceEntity->setAccess('host_id', false);

        $serviceEntity = $ServicesTable->patchEntity($serviceEntity, $dataForSave);
        $ServicesTable->save($serviceEntity);

        if ($serviceEntity->hasErrors()) {
            return $serviceEntity->getErrors();
        } else {
            //No errors
            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'edit',
                'services',
                $serviceEntity->get('id'),
                OBJECT_SERVICE,
                $host['Host']['container_id'],
                $userId,
                $host['Host']['name'] . '/' . $mergedService['Service']['name'],
                array_merge($ServicesTable->resolveDataForChangelog($newServiceForChangelogWithAllFields, $ObjectsCacheChangelog), $newServiceForChangelogWithAllFields),
                array_merge($ServicesTable->resolveDataForChangelog($serviceForChangelog, $ObjectsCacheChangelog), $serviceForChangelog)
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);
            }
        }
        return [];
    }
}
