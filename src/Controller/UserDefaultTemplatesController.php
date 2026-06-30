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

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Filter\UserDefaultTemplatesFilter;
use App\Model\Entity\Changelog;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\UserDefaultTemplatesTable;
use Cake\Cache\Cache;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * UserDefaultTemplates Controller
 * @package App\Controller
 * @property \App\Model\Table\UserDefaultTemplatesTable $UserDefaultTemplates
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class UserDefaultTemplatesController extends AppController {

    public function index() {

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');
        $UserDefaultTemplatesFilter = new UserDefaultTemplatesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $UserDefaultTemplatesFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            // root users can see all users
            $MY_RIGHTS = [];
        }
        $all_userdefaulttemplates = $UserDefaultTemplatesTable->getUserDefaultTemplatesIndex($UserDefaultTemplatesFilter, $PaginateOMat, $MY_RIGHTS);

        foreach ($all_userdefaulttemplates as $index => $user_default_template) {
            $allowEdit = $this->hasRootPrivileges;
            if ($this->hasRootPrivileges === false) {
                $allowEdit = false;
                foreach ($user_default_template['containers'] as $container) {
                    if ($this->isWritableContainer($container['id'])) {
                        $allowEdit = true;
                        break;
                    }
                }
            }
            $all_userdefaulttemplates[$index]['allow_edit'] = $allowEdit;
        }

        $this->set('all_userdefaulttemplates', $all_userdefaulttemplates);
        $this->viewBuilder()->setOption('serialize', ['all_userdefaulttemplates']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        if ($this->request->is('post') || $this->request->is('put')) {

            $data = $this->request->getData('Userdefaulttemplate', []);
            if (!isset($data['UserDefaultTemplatesToContainers'])) {
                $data['UserDefaultTemplatesToContainers'] = [];
            }
            $data['containers'] = $UserDefaultTemplatesTable->containerPermissionsForSave(
                $data['UserDefaultTemplatesToContainers'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );

            $userDefaultTemplate = $UserDefaultTemplatesTable->newEmptyEntity();
            $userDefaultTemplate = $UserDefaultTemplatesTable->patchEntity($userDefaultTemplate, $data);

            $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

            $data = [
                'UserDefaultTemplate' => $data
            ];

            $userDefaultTemplate = $UserDefaultTemplatesTable->createUserDefaultTemplate($userDefaultTemplate, $data, $User->getId());
            if ($userDefaultTemplate->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $userDefaultTemplate->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            Cache::clear('permissions');
            $this->set('userDefaultTemplate', $userDefaultTemplate);
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        if (!$UserDefaultTemplatesTable->existsById($id)) {
            throw new NotFoundException(__('User Default Template not found'));
        }

        $userDefaultTemplate = $UserDefaultTemplatesTable->getUserDefaultTemplateForEdit($id);
        $userDefaultTemplateForChangelog = $userDefaultTemplate;
        $containersToCheck = $userDefaultTemplate['UserDefaultTemplate']['containers']['_ids']; //Containers defined by the user itself

        $notPermittedContainerIds = [];
        foreach ($userDefaultTemplate['UserDefaultTemplate']['UserDefaultTemplatesToContainers'] as $containerId => $rightLevel) {
            if (!isset($this->MY_RIGHTS_LEVEL[$containerId]) || (isset($this->MY_RIGHTS_LEVEL[$containerId]) && $this->MY_RIGHTS_LEVEL[$containerId] < $rightLevel)) {
                $notPermittedContainerIds[] = $containerId;
            }
        }

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return user default template information
            $this->set('userDefaultTemplate', $userDefaultTemplate['UserDefaultTemplate']);
            $this->set('notPermittedContainerIds', array_map('intval', $notPermittedContainerIds)); // Make sure its a int array for Angular
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate', 'notPermittedContainerIds']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('Userdefaulttemplate', []);
            if (!isset($data['UserDefaultTemplatesToContainers'])) {
                $data['UserDefaultTemplatesToContainers'] = [];
            }

            if (!$this->hasRootPrivileges) {
                $containerIdsWithWritePermissions = array_filter($this->MY_RIGHTS_LEVEL, function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);
                $userToEditContainerIdsWithWritePermissions = array_filter($data['UserDefaultTemplatesToContainers'], function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);

                $notPermittedContainerIds = array_keys(
                    array_diff_key($userToEditContainerIdsWithWritePermissions, $containerIdsWithWritePermissions)
                );

                foreach ($data['UserDefaultTemplatesToContainers'] as $key => $value) {
                    // do not overwrite container settings if the user does not have sufficient rights
                    if (in_array($key, $notPermittedContainerIds, true)) {
                        continue;
                    }
                    // reverting write permission to read permission due to insufficient user permission rights
                    if ($key !== ROOT_CONTAINER && !array_key_exists($key, $containerIdsWithWritePermissions) && $value > 1) {
                        $data['UserDefaultTemplatesToContainers'][$key] = READ_RIGHT;
                    }
                }
            }
            $data['containers'] = $UserDefaultTemplatesTable->containerPermissionsForSave(
                $data['UserDefaultTemplatesToContainers'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );
            $userDefaultTemplate = $UserDefaultTemplatesTable->get($id);
            $userDefaultTemplate->setAccess('id', false);

            $userDefaultTemplate = $UserDefaultTemplatesTable->patchEntity($userDefaultTemplate, $data);

            $data = [
                'UserDefaultTemplate' => $data
            ];

            $userDefaultTemplate = $UserDefaultTemplatesTable->updateUserDefaultTemplate(
                $userDefaultTemplate,
                $data,
                $userDefaultTemplateForChangelog,
                $User->getId()
            );
            if ($userDefaultTemplate->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $userDefaultTemplate->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            Cache::clear('permissions');
            $this->set('userDefaultTemplate', $userDefaultTemplate);
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate']);
        }
    }

    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        if (!$UserDefaultTemplatesTable->existsById($id)) {
            throw new NotFoundException(__('User Default Template not found'));
        }

        $userDefaultTemplate = $UserDefaultTemplatesTable->getUserDefaultTemplateById($id);
        $userDefaultTemplateForLog = $userDefaultTemplate;
        $containersToCheck = $userDefaultTemplate['containers']['_ids']; //Containers defined by the user itself

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        $userDefaultTemplate = $UserDefaultTemplatesTable->get($id);

        if ($UserDefaultTemplatesTable->delete($userDefaultTemplate)) {

            $containerIds = Hash::extract($userDefaultTemplateForLog, 'containers.{n}.id');

            /** @var  ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'delete',
                'UserDefaultTemplates',
                $id,
                OBJECT_USER_DEFAULT_TEMPLATES,
                $containerIds,
                $User->getId(),
                $userDefaultTemplateForLog['name'],
                $userDefaultTemplateForLog
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);

            }

            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);

            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }
}
