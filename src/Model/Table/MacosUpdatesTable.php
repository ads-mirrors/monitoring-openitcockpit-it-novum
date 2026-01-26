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

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MacosUpdates Model
 *
 * @property \App\Model\Table\HostsTable&\Cake\ORM\Association\BelongsToMany $Hosts
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

        $this->hasMany('MacosUpdatesHosts', [
            'foreignKey' => 'macos_update_id',
            'className'  => MacosUpdatesHostsTable::class,
            'dependent'  => true,
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
}
