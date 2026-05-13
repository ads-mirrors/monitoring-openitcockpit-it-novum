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

declare(strict_types=1);

use Cake\ORM\TableRegistry;
use MapModule\Model\Table\MapsTable;
use MapModule\Model\Table\MapUploadsTable;
use Migrations\BaseMigration;

/**
 * Class AddContainersToMapUploads
 *
 * Created via:
 * oitc migrations create -p MapModule AddContainersToMapUploads
 *
 * Run migration:
 * oitc migrations migrate -p MapModule
 *
 */
class AddContainersToMapUploads extends BaseMigration {
    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void {

        if ($this->hasTable('map_uploads')) {
            $this->table('map_uploads')
                ->removeColumn('container_id')
                ->update();
        }

        if (!$this->hasTable('mapuploads_to_containers')) {
            $this->table('mapuploads_to_containers')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('container_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('mapupload_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'container_id',
                    ]
                )
                ->addIndex(
                    [
                        'mapupload_id',
                    ]
                )
                ->create();

            /** @var MapsTable $MapsTable */
            $MapsTable = TableRegistry::getTableLocator()->get('MapModule.Maps');

            $backgroundsWithMapsContainers = $MapsTable->getUsedMapBackgroundsWithMapContainerIds();
            $containerBackgroundValues = [];
            if (!empty($backgroundsWithMapsContainers)) {
                foreach ($backgroundsWithMapsContainers as $backgroundWithMapsContainer) {
                    foreach ($backgroundWithMapsContainer['containers'] as $container) {
                        $containerBackgroundValues[$backgroundWithMapsContainer['background']][] = $container['id'];
                    }
                }
            }

            $iconsWithMapsContainers = $MapsTable->getUsedMapIconsWithMapContainerIds();
            $containerIconValues = [];
            if (!empty($iconsWithMapsContainers)) {
                foreach ($iconsWithMapsContainers as $iconWithMapsContainer) {
                    foreach ($iconWithMapsContainer['containers'] as $container) {
                        $containerIconValues[$iconWithMapsContainer['_matchingData']['Mapicons']['icon']][] = $container['id'];
                    }
                }
            }
            /** @var MapUploadsTable $MapUploadsTable */
            $MapUploadsTable = TableRegistry::getTableLocator()->get('MapModule.MapUploads');

            $mapUploads = $MapUploadsTable->getMapUploads();
            $valuesToInsert = [];
            foreach ($mapUploads as $mapUpload) {
                $containerIds = [ROOT_CONTAINER];
                // add map containers if background in used by maps to upload container for check the permissions
                switch ($mapUpload['upload_type']) {
                    case 1:  // 1 => Upload type background
                        if (!empty($containerBackgroundValues[$mapUpload['saved_name']])) {
                            $containerIds = array_unique(array_merge(
                                    $containerIds, $containerBackgroundValues[$mapUpload['saved_name']]
                                )
                            );
                        }
                        foreach ($containerIds as $containerId) {
                            $valuesToInsert[] = [
                                'mapupload_id' => $mapUpload['id'],
                                'container_id' => $containerId
                            ];
                        }
                        break;
                    case 3: // 3 => Upload icon
                        if (!empty($containerIconValues[$mapUpload['saved_name']])) {
                            $containerIds = array_unique(array_merge(
                                    $containerIds, $containerIconValues[$mapUpload['saved_name']]
                                )
                            );
                        }
                        foreach ($containerIds as $containerId) {
                            $valuesToInsert[] = [
                                'mapupload_id' => $mapUpload['id'],
                                'container_id' => $containerId
                            ];
                        }
                        break;
                    default:
                        $valuesToInsert[] = [
                            'mapupload_id' => $mapUpload['id'],
                            'container_id' => ROOT_CONTAINER
                        ];
                }
            }

            if (!empty($valuesToInsert)) {
                $adapter = $this->table('mapuploads_to_containers')->getAdapter();

                foreach ($valuesToInsert as $value) {
                    $adapter->getInsertBuilder()
                        ->insert(['mapupload_id', 'container_id'])
                        ->into('mapuploads_to_containers')
                        ->values($value)
                        ->execute();
                }
            }
        }
    }
}
