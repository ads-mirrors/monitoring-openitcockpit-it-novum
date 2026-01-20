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

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PackagesHostDetails Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \App\Model\Entity\PackagesHostDetail newEmptyEntity()
 * @method \App\Model\Entity\PackagesHostDetail newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\PackagesHostDetail> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PackagesHostDetail findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\PackagesHostDetail> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\PackagesHostDetail saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\PackagesHostDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\PackagesHostDetail> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PackagesHostDetailsTable extends Table {
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('packages_host_details');
        $this->setDisplayField('os_name');
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
            ->scalar('os_type')
            ->maxLength('os_type', 255)
            ->requirePresence('os_type', 'create')
            ->notEmptyString('os_type');

        $validator
            ->scalar('os_name')
            ->maxLength('os_name', 255)
            ->requirePresence('os_name', 'create')
            ->notEmptyString('os_name');

        $validator
            ->scalar('os_version')
            ->maxLength('os_version', 255)
            ->requirePresence('os_version', 'create')
            ->allowEmptyString('os_version');

        $validator
            ->scalar('os_family')
            ->maxLength('os_family', 255)
            ->allowEmptyString('os_family');

        $validator
            ->scalar('agent_version')
            ->maxLength('agent_version', 15)
            ->requirePresence('agent_version', 'create')
            ->notEmptyString('agent_version');

        $validator
            ->boolean('reboot_required')
            ->notEmptyString('reboot_required');

        $validator
            ->requirePresence('system_uptime', 'create')
            ->allowEmptyString('system_uptime');

        $validator
            ->dateTime('last_update')
            ->requirePresence('last_update', 'create')
            ->notEmptyDateTime('last_update');

        $validator
            ->scalar('last_error')
            ->maxLength('last_error', 1000)
            ->allowEmptyString('last_error');

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
     * @param array $details
     * @return \App\Model\Entity\PackagesHostDetail|\Cake\Datasource\EntityInterface|false
     */
    public function updateHostDetails(int $hostId, array $details) {
        $entity = $this->find()
            ->where(['host_id' => $hostId])
            ->first();

        if (!$entity) {
            $entity = $this->newEmptyEntity();
            $entity->host_id = $hostId;
        }

        $entity->setAccess('host_id', false);
        $entity = $this->patchEntity($entity, $details);
        return $this->save($entity);
    }

}
