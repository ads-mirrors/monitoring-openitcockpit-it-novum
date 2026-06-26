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

namespace App\Model\Table;

use App\itnovum\openITCOCKPIT\Filter\UserDefaultTemplatesFilter;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\Changelog;
use App\Model\Entity\User;
use App\Model\Entity\UserDefaultTemplate;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * UserDefaultTemplates Model
 *
 * @property \App\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 *
 * @method \App\Model\Entity\UserDefaultTemplate newEmptyEntity()
 * @method \App\Model\Entity\UserDefaultTemplate newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserDefaultTemplate> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserDefaultTemplate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserDefaultTemplate> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserDefaultTemplate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserDefaultTemplate>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserDefaultTemplate> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UserDefaultTemplatesTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('user_default_templates');
        $this->setDisplayField('i18n');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Usergroups', [
            'foreignKey' => 'usergroup_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsToMany('Containers', [
            'through'          => 'UserDefaultTemplatesToContainers',
            'className'        => 'Containers',
            'foreignKey'       => 'user_default_templates_id',
            'targetForeignKey' => 'container_id',
            'joinTable'        => 'user_default_templates_to_containers'
        ]);

        $this->belongsToMany('Ldapgroups', [
            'className'        => 'Ldapgroups',
            'joinTable'        => 'ldapgroups_to_user_default_templates',
            'foreignKey'       => 'user_default_templates_id',
            'targetForeignKey' => 'ldapgroup_id',
            'saveStrategy'     => 'replace'
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->add('containers', 'custom', [
                'rule'    => [$this, 'validateHasContainerOrContainerUserRolePermissions'],
                'message' => __('You need to select at least one container or container role.')
            ]);

        $validator
            ->add('usercontainerroles', 'custom', [
                'rule'    => [$this, 'validateHasContainerOrContainerUserRolePermissions'],
                'message' => __('You need to select at least one container or container role.')
            ]);

        $validator
            ->integer('usergroup_id')
            ->requirePresence('usergroup_id', 'create')
            ->greaterThan('usergroup_id', 0, __('You have to select a user role.'))
            ->allowEmptyString('usergroup_id', null, false);

        $validator
            ->scalar('timezone')
            ->maxLength('timezone', 100)
            ->allowEmptyString('timezone');

        $validator
            ->scalar('i18n')
            ->maxLength('i18n', 100)
            ->notEmptyString('i18n');

        $validator
            ->boolean('is_oauth')
            ->notEmptyString('is_oauth');

        return $validator;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for containers and or user container roles
     */
    public function validateHasContainerOrContainerUserRolePermissions($value, $context) {
        // return !empty($context['data']['containers']) || !empty($context['data']['usercontainerroles']['_ids']) || !empty($context['data']['usercontainerroles_ldap']['_ids']);
        // ITC-3073
        if (!empty($context['data']['containers'])) {
            return true;
        }

        // Validation of POST request data (openITCOCKPIT Frontend)
        if (!empty($context['data']['usercontainerroles']['_ids']) || !empty($context['data']['usercontainerroles_ldap']['_ids'])) {
            return true;
        }

        // Validation of POST request data with through join data convert(openITCOCKPIT Frontend)
        if ((!empty($context['data']['usercontainerroles']) && !isset($context['data']['usercontainerroles']['_ids'])) || !empty($context['data']['usercontainerroles_ldap']['_ids'])) {
            return true;
        }

        // Validation of POST request data (openITCOCKPIT Frontend)
        // _ids is set - so it is an empty array
        // When it is a POST request from the openITCOCKPIT frontend we should never reach this code
        if (isset($context['data']['usercontainerroles']['_ids']) || isset($context['data']['usercontainerroles_ldap']['_ids'])) {
            return false;
        }


        // Validate LdapGroupImportCommand data
        // The usercontainerroles array holds both manually assigned user container roles and those, which got assigned through LDAP
        // This use a through_ldap (0 or 1) field in the linking table
        if (!empty($context['data']['usercontainerroles'])) {
            return true;
        }

        return false;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['usergroup_id'], 'Usergroups'), ['errorField' => 'usergroup_id']);

        return $rules;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['UserDefaultTemplates.id' => $id]);
    }

    /**
     * @param array $rights
     * @param UserDefaultTemplatesFilter $UserDefaultTemplatesFilter
     * @param PaginateOMat|null $PaginateOMat
     * @return array
     */
    public function getUserDefaultTemplatesIndex(UserDefaultTemplatesFilter $UserDefaultTemplatesFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        //Get all user ids where container assigned are made directly at the user
        $query = $this->find()
            ->select([
                'UserDefaultTemplates.id'
            ])
            ->matching('Containers')
            ->groupBy([
                'UserDefaultTemplates.id'
            ])
            ->disableHydration();

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'UserDefaultTemplatesToContainers.container_id IN' => $MY_RIGHTS
            ]);
        }
        $userDefaultTemplatesIds = Hash::extract($query->toArray(), '{n}.id');

        //Get all userDefaultTemplates ids where container assigned are made through an user container role
        $query = $this->find()
            ->select([
                'UserDefaultTemplates.id'
            ])
            ->matching('Usercontainerroles.Containers')
            ->groupBy([
                'UserDefaultTemplates.id'
            ])
            ->disableHydration();

        if (!empty($MY_RIGHTS)) {
            $query->where([
                'Containers.id IN' => $MY_RIGHTS
            ]);
        }

        $userIdsThroughContainerRoles = Hash::extract($query->toArray(), '{n}.id');

        $userDefaultTemplatesIds = array_unique(array_merge($userDefaultTemplatesIds, $userIdsThroughContainerRoles));

        $where = $UserDefaultTemplatesFilter->indexFilter();

        $query = $this->find();
        $query->select([
            'UserDefaultTemplates.id',
            'UserDefaultTemplates.is_oauth',
            'UserDefaultTemplates.name',
            'UserDefaultTemplates.description',
            'Usergroups.id',
            'Usergroups.name',
        ])
            ->contain([
                'Usergroups',
                'Containers',
            ]);

        if (!empty($userDefaultTemplatesIds)) {
            $query->where([
                'UserDefaultTemplates.id IN' => $userDefaultTemplatesIds
            ]);
        }

        if (!empty($where)) {
            $query->andWhere($where);
        }

        $query->orderBy(
            $UserDefaultTemplatesFilter->getOrderForPaginator('UserDefaultTemplates.id', 'asc')
        );
        $query->groupBy([
            'UserDefaultTemplates.id'
        ]);

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scrollCake4($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginateCake4($query, $PaginateOMat->getHandler());
            }
        }
        return $result;
    }

    /**
     * @param $containerPermissions
     * @param bool $hasRootPrivileges
     * @param array $MY_RIGHTS_LEVEL
     * @return array
     */
    public function containerPermissionsForSave($containerPermissions, bool $hasRootPrivileges = false, array $MY_RIGHTS_LEVEL = []) {
        //UserDefaultTemplatesToContainers

        $dataForSave = [];
        foreach ($containerPermissions as $containerId => $permissionLevel) {
            if (!$hasRootPrivileges && !isset($MY_RIGHTS_LEVEL[$containerId])) {
                //User has no rights to this container
                continue;
            }
            $containerId = (int)$containerId;
            $permissionLevel = (int)$permissionLevel;
            if ($permissionLevel !== READ_RIGHT && $permissionLevel !== WRITE_RIGHT) {
                $permissionLevel = READ_RIGHT;
            }
            if (!$hasRootPrivileges && $MY_RIGHTS_LEVEL[$containerId] < $containerPermissions[$containerId]) {
                //avoid to set higher permission level than the user has
                $permissionLevel = READ_RIGHT;
            }

            if ($containerId === ROOT_CONTAINER) {
                // ROOT_CONTAINER is always read/write
                $permissionLevel = WRITE_RIGHT;
            }

            $dataForSave[] = [
                'id'        => $containerId,
                '_joinData' => [
                    'permission_level' => $permissionLevel
                ]
            ];
        }

        return $dataForSave;
    }

    /**
     * This method provides a unified way to create new user. It will also make sure that the changelog is used
     * It will always return an Entity object, so make sure to check for "hasErrors()"
     *
     * @param UserDefaultTemplate $entity The entity that will be saved by the Table
     * @param array $userDefaultTemplates The userDefaultTemplates as array ( [ UserDefaultTemplates => [ name => Foo, id => 1 ... ] ] ) used by the Changelog
     * @param int $userId The ID of the user that did the Change (0 = Cronjob)
     * @return UserDefaultTemplate
     */
    public function createUserDefaultTemplate(UserDefaultTemplate $entity, array $userDefaultTemplates, int $userId): User {
        $this->save($entity);
        if ($entity->hasErrors()) {
            // We have some validation errors
            // Let the caller (probably CakePHP Controller) handle the error
            return $entity;
        }

        //No errors
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $extDataForChangelog = $this->resolveDataForChangelog($userDefaultTemplates);
        $containerIds = Hash::extract($userDefaultTemplates, 'UserDefaultTemplates.containers.{n}.id');

        $containerRoleContainerIds = $this->getContainerIdsOfUserContainerRoles($userDefaultTemplates);
        $containerIds = array_merge($containerIds, $containerRoleContainerIds);

        $changelog_data = $ChangelogsTable->parseDataForChangelog(
            'add',
            'UserDefaultTemplates',
            $entity->get('id'),
            OBJECT_USER_DEFAULT_TEMPLATES,
            $containerIds,
            $userId,
            $entity->get('name'),
            array_merge($userDefaultTemplates, $extDataForChangelog)
        );

        if ($changelog_data) {
            /** @var Changelog $changelogEntry */
            $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
            $ChangelogsTable->save($changelogEntry);
        }

        return $entity;
    }

    /**
     * @param array $dataToParse
     * @return array
     */
    public function resolveDataForChangelog($dataToParse = []) {
        $extDataForChangelog = [
            'Usercontainerroles' => [],
            'Containers'         => [],
            'Usergroup'          => [],
        ];

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');
        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        //container roles
        if (isset($dataToParse['UserDefaultTemplates']['usercontainerroles'])) {

            if (isset($dataToParse['UserDefaultTemplates']['usercontainerroles']['_ids'])) {
                foreach ($dataToParse['UserDefaultTemplates']['usercontainerroles']['_ids'] as $id) {
                    $usercontainerrole = $UsercontainerrolesTable->getUserContainerRoleById($id);
                    if (!empty($usercontainerrole)) {
                        $extDataForChangelog['Usercontainerroles'][] = [
                            'id'   => $usercontainerrole['id'],
                            'name' => $usercontainerrole['name']
                        ];
                    }
                }
            }

            foreach ($dataToParse['UserDefaultTemplates']['usercontainerroles'] as $usercontainerrole) {
                if (isset($usercontainerrole['id'])) {
                    if (!isset($usercontainerrole['name'])) {
                        $usercontainerrole = $UsercontainerrolesTable->getUserContainerRoleById($usercontainerrole['id']);
                    }
                    $extDataForChangelog['Usercontainerroles'][] = [
                        'id'   => $usercontainerrole['id'],
                        'name' => $usercontainerrole['name']
                    ];
                }
            }

        }

        //containers
        if (isset($dataToParse['UserDefaultTemplates']['containers'])) {

            if (isset($dataToParse['UserDefaultTemplates']['containers']['_ids']) && !empty($dataToParse['UserDefaultTemplates']['ContainersUsersMemberships'])) {
                foreach ($dataToParse['User']['containers']['_ids'] as $id) {
                    $containerWithName = $ContainersTable->getContainerById($id);
                    if (!empty($containerWithName)) {
                        $extDataForChangelog['Containers'][] = [
                            'id'               => $id,
                            'name'             => $containerWithName['name'],
                            'permission_level' => $dataToParse['UserDefaultTemplates']['UserDefaultTemplatesToContainers'][$id],
                        ];
                    }
                }
            } else {
                foreach ($dataToParse['UserDefaultTemplates']['containers'] as $container) {
                    $containerWithName = [];
                    if (!isset($dataToParse['UserDefaultTemplates']['containers']['name'])) {
                        $containerWithName = $ContainersTable->getContainerById($container['id']);
                    }
                    $extDataForChangelog['Containers'][] = [
                        'id'               => $container['id'],
                        'name'             => (!empty($containerWithName)) ? $containerWithName['name'] : $container['name'],
                        'permission_level' => $container['_joinData']['permission_level'],
                    ];
                }
            }

        }

        //usergroup
        if (isset($dataToParse['UserDefaultTemplates']['usergroup_id'])) {
            $usergroup = $UsergroupsTable->getUsergroupById($dataToParse['UserDefaultTemplates']['usergroup_id']);
            if (!empty($usergroup)) {
                $extDataForChangelog['Usergroup'] = [
                    'id'   => $usergroup['id'],
                    'name' => $usergroup['name']
                ];
            }
        }

        return $extDataForChangelog;
    }

    /**
     * This method return the container ids of the userDefaultTemplates container roles for saving in user logs
     *
     * @param array $userDefaultTemplates The userDefaultTemplates as array ( [ userDefaultTemplates => [ name => Foo, id => 1 ... ] ] )
     * @return array
     */
    public function getContainerIdsOfUserContainerRoles(array $userDefaultTemplates): array {

        $containerIds = [];

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        //get container ids from usercontainerroles to show userDefaultTemplates log entry
        if (isset($userDefaultTemplates['UserDefaultTemplates']['usercontainerroles']['_ids'])) {
            foreach ($userDefaultTemplates['UserDefaultTemplates']['usercontainerroles']['_ids'] as $id) {
                $userContainerRoles = $UsercontainerrolesTable->getUserContainerRoleForEdit($id);
                $containerRoleContainerIds = array_keys($userContainerRoles['Usercontainerrole']['ContainersUsercontainerrolesMemberships']);
                $containerIds = array_merge($containerIds, $containerRoleContainerIds);
            }
        } else {
            foreach ($userDefaultTemplates['UserDefaultTemplates']['usercontainerroles'] as $usercontainerrole) {
                $userContainerRoles = $UsercontainerrolesTable->getUserContainerRoleForEdit($usercontainerrole['id']);
                $containerRoleContainerIds = array_keys($userContainerRoles['Usercontainerrole']['ContainersUsercontainerrolesMemberships']);
                $containerIds = array_merge($containerIds, $containerRoleContainerIds);
            }
        }

        return $containerIds;

    }

}
