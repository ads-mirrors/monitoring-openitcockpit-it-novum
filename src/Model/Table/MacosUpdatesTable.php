<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

namespace App\Model\Table;

use App\Model\Entity\MacosUpdate;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;

/**
 * MacosUpdates Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\MacosUpdate newEmptyEntity()
 * @method \App\Model\Entity\MacosUpdate newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosUpdate> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MacosUpdate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MacosUpdate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MacosUpdate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MacosUpdate> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MacosUpdate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MacosUpdate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MacosUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MacosUpdate> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MacosUpdatesTable extends Table {
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('macos_updates');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('host_id')
            ->notEmptyString('host_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 512)
            ->allowEmptyString('description');

        $validator
            ->scalar('version')
            ->maxLength('version', 64)
            ->allowEmptyString('version');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['host_id'], 'Hosts'), ['errorField' => 'host_id']);

        return $rules;
    }

    /**
     * @param int $hostId
     * @return MacosUpdate[]
     */
    public function getAllMacosUpdatesByHostId(int $hostId): array {
        $query = $this->find()
            ->where(['host_id' => $hostId])
            ->orderBy(['id' => 'DESC']);

        return $query->all()->toArray();
    }

    /**
     * Store macOS updates for a host
     *
     * @param int $hostId
     * @param array $availableUpdates
     * @return bool
     */
    public function saveUpdatesForHost(int $hostId, array $availableUpdates): bool {

        $existingUpdates = $this->getAllMacosUpdatesByHostId($hostId);
        $existingUpdateNames = Hash::combine($existingUpdates, '{n}.name', '{n}.id');

        // Fake update for testing
        /*
        $availableUpdates[] = [
            'Name'        => 'TEST Command Line Tools for Xcode 26.2-26.2',
            'Description' => 'TEST DESC Command Line Tools for Xcode 26.2',
            'Version'     => '26.2.1'
        ];*/
        foreach ($availableUpdates as $update) {
            if (empty($update['Name']) || empty($update['Version'])) {
                continue;
            }

            // New update?
            if (!isset($existingUpdateNames[$update['Name']])) {
                $desc = null;
                if (isset($update['Description'])) {
                    $desc = substr($update['Description'], 0, 512);
                }

                $newUpdate = $this->newEntity([
                    'name'        => $update['Name'],
                    'host_id'     => $hostId,
                    'description' => $desc,
                    'version'     => $update['Version']
                ]);
                if ($this->save($newUpdate)) {
                    $existingUpdateNames[$update['Name']] = $newUpdate->id;
                }
            }
        }

        // Remove old updates that are no longer reported by the agent
        $availableUpdateNames = array_flip(Hash::extract($availableUpdates, '{n}.Name'));


        $updatesIdsToRemove = array_diff_key($existingUpdateNames, $availableUpdateNames);
        if (!empty($updatesIdsToRemove)) {
            $this->deleteAll(conditions: [
                'id IN'   => array_values($updatesIdsToRemove),
                'host_id' => $hostId,
            ]);
        }

        return true;
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getMacosUpdatesForSummary(array $MY_RIGHTS = []): array {
        $all_macos_updates = [
            'upToDate'                 => 0,
            'updatesAvailable'         => 0,
            'securityUpdates'          => 0,
            'totalInstallations'       => 0,
            'hostsUpToDate'            => [],
            'hostsWithUpdates'         => [],
            'hostsWithSecurityUpdates' => [],
        ];


        $query = $this->find('all')
            ->select([
                'MacosUpdates.host_id'
            ])
            ->disableAutoFields()
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = MacosUpdates.host_id']
            )
            ->where([
                'Hosts.disabled' => 0
            ]);


        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = Hosts.id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
        }

        $query->disableHydration();
        $result = $query->toArray();
        if (empty($result)) {
            return $all_macos_updates;
        }
        foreach ($result as $macos_update) {
            $all_macos_updates['updatesAvailable']++;
            $all_macos_updates['hostsWithSecurityUpdates'][$macos_update['host_id']] = $macos_update['host_id'];
        }
        $all_macos_updates['hostsWithSecurityUpdates'] = array_values($all_macos_updates['hostsWithSecurityUpdates']);
        return $all_macos_updates;
    }

}
