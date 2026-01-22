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

use App\Model\Entity\WindowsUpdate;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;

/**
 * WindowsUpdates Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\WindowsUpdate newEmptyEntity()
 * @method \App\Model\Entity\WindowsUpdate newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsUpdate> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\WindowsUpdate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\WindowsUpdate> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WindowsUpdate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WindowsUpdate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WindowsUpdate> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WindowsUpdatesTable extends Table {
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('windows_updates');
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
            ->scalar('kbarticle_ids')
            ->maxLength('kbarticle_ids', 512)
            ->allowEmptyString('kbarticle_ids');

        $validator
            ->scalar('update_id')
            ->maxLength('update_id', 255)
            ->allowEmptyString('update_id');

        $validator
            ->boolean('reboot_required')
            ->notEmptyString('reboot_required');

        $validator
            ->boolean('is_security_update')
            ->notEmptyString('is_security_update');

        $validator
            ->boolean('is_optional')
            ->notEmptyString('is_optional');

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
     * @return WindowsUpdate[]
     */
    public function getAllWindowsUpdatesByHostId(int $hostId): array {
        $query = $this->find()
            ->where(['host_id' => $hostId])
            ->orderBy(['id' => 'DESC']);

        return $query->all()->toArray();
    }

    /**
     * Store Windows updates for a host
     *
     * @param int $hostId
     * @param array $availableUpdates
     * @return bool
     */
    public function saveUpdatesForHost(int $hostId, array $availableUpdates): bool {

        $existingUpdates = $this->getAllWindowsUpdatesByHostId($hostId);
        $existingUpdateIds = Hash::combine($existingUpdates, '{n}.update_id', '{n}.id');

        // Fake update for testing
        /*
        $availableUpdates[] = [
            'Title'            => 'TEST Security Intelligence-Update für Microsoft Defender Antivirus – KB2267602 (Version 1.443.762.0) – Aktueller Kanal (Allgemein)',
            'Description'      => 'Installieren Sie dieses Update, um die Dateien zu überarbeiten, die zum Erkenne',
            'KBArticleIDs'     => [
                '1122334',
                '1122335',
                '1122336',
            ],
            'IsInstalled'      => false,
            'IsSecurityUpdate' => true,
            'IsOptional'       => false,
            'UpdateID'         => '69f55396-a0ef-4f79-8539-4d0ccfa35ec6',
            'RevisionNumber'   => 201,
            'RebootRequired'   => false
        ];*/
        foreach ($availableUpdates as $update) {
            if (empty($update['UpdateID']) || empty($update['Title'])) {
                continue;
            }

            // The update ID will generally be a GUID, but it can be any string that uniquely identifies. This identifier is required for calling many WindowsUpdateAdministrator methods.
            // https://learn.microsoft.com/en-us/uwp/api/windows.management.update.windowsupdate.updateid?view=winrt-26100

            // New update?
            if (!isset($existingUpdateIds[$update['UpdateID']])) {
                $desc = null;
                if (isset($update['Description'])) {
                    $desc = substr($update['Description'], 0, 1000);
                }

                $newUpdate = $this->newEntity([
                    'name'               => $update['Title'],
                    'host_id'            => $hostId,
                    'description'        => $desc,
                    'kbarticle_ids'      => isset($update['KBArticleIDs']) ? implode(',', $update['KBArticleIDs']) : null,
                    'update_id'          => $update['UpdateID'],
                    'reboot_required'    => $update['RebootRequired'] ?? false,
                    'is_security_update' => $update['IsSecurityUpdate'] ?? false,
                    'is_optional'        => $update['IsOptional'] ?? false,
                ]);
                if ($this->save($newUpdate)) {
                    $existingUpdateIds[$update['UpdateID']] = $newUpdate->id;
                }
            }
        }

        // Remove old updates that are no longer reported by the agent
        $availableUpdateIds = array_flip(Hash::extract($availableUpdates, '{n}.UpdateID'));

        $updatesIdsToRemove = array_diff_key($existingUpdateIds, $availableUpdateIds);
        if (!empty($updatesIdsToRemove)) {
            $this->deleteAll(conditions: [
                'update_id IN' => array_keys($updatesIdsToRemove),
                'host_id'      => $hostId,
            ]);
        }

        return true;
    }

    /**
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getWindowsUpdatesForSummary(array $MY_RIGHTS = []): array {
        $all_windows_updates = [
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
                'WindowsUpdates.host_id',
                'WindowsUpdates.reboot_required',
                'WindowsUpdates.is_security_update',
                'WindowsUpdates.is_optional',
            ])
            ->disableAutoFields()
            ->innerJoin(
                ['Hosts' => 'hosts'],
                ['Hosts.id = WindowsUpdates.host_id']
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
            return $all_windows_updates;
        }
        foreach ($result as $windows_update) {
            if ($windows_update['is_security_update'] === false) {
                $all_windows_updates['updatesAvailable']++;
                $all_windows_updates['hostsWithUpdates'][$windows_update['host_id']] = $windows_update['host_id'];
            }
            if ($windows_update['is_security_update'] === true) {
                $all_windows_updates['securityUpdates']++;
                $all_windows_updates['hostsWithSecurityUpdates'][$windows_update['host_id']] = $windows_update['host_id'];
            }
        }
        $all_windows_updates['hostsWithUpdates'] = array_values($all_windows_updates['hostsWithUpdates']);
        $all_windows_updates['hostsWithSecurityUpdates'] = array_values($all_windows_updates['hostsWithSecurityUpdates']);

        return $all_windows_updates;
    }

}
