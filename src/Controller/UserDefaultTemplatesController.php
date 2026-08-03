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
use App\Model\Table\UserDefaultTemplatesTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Database\PaginateOMat;

/**
 * UserDefaultTemplates Controller
 * @package App\Controller
 * @property \App\Model\Table\UserDefaultTemplatesTable $UserDefaultTemplates
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class UserDefaultTemplatesController extends AppController {

    public function index(): void {

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

    public function add(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if ($this->request->is('post') || $this->request->is('put')) {

            $data = $this->request->getData('Userdefaulttemplate', []);
            if (!isset($data['UserDefaultTemplatesToUserContainers'])) {
                $data['UserDefaultTemplatesToUserContainers'] = [];
            }
            $data['user_containers'] = $UsersTable->containerPermissionsForSave(
                $data['UserDefaultTemplatesToUserContainers'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );

            $userDefaultTemplateEntity = $UserDefaultTemplatesTable->newEmptyEntity();
            $userDefaultTemplateEntity = $UserDefaultTemplatesTable->patchEntity($userDefaultTemplateEntity, $data);
            $UserDefaultTemplatesTable->save($userDefaultTemplateEntity);
            if ($userDefaultTemplateEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $userDefaultTemplateEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            //No errors
            if ($this->isJsonRequest()) {
                $this->serializeCake4Id($userDefaultTemplateEntity); // REST API ID serialization
                return;
            }

            $this->set('userDefaultTemplate', $userDefaultTemplateEntity);
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate']);

        }
    }

    public function edit($id = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');


        if (!$UserDefaultTemplatesTable->existsById($id)) {
            throw new NotFoundException(__('User Default Template not found'));
        }

        $userDefaultTemplate = $UserDefaultTemplatesTable->getUserDefaultTemplateForEdit($id);
        $userDefaultTemplateForChangelog = $userDefaultTemplate;
        $containersToCheck = $userDefaultTemplate['UserDefaultTemplate']['containers']['_ids']; //Containers defined by the user itself

        $notPermittedUserContainerIds = [];
        foreach ($userDefaultTemplate['UserDefaultTemplate']['UserDefaultTemplatesToUserContainers'] as $containerId => $rightLevel) {
            if (!isset($this->MY_RIGHTS_LEVEL[$containerId]) || (isset($this->MY_RIGHTS_LEVEL[$containerId]) && $this->MY_RIGHTS_LEVEL[$containerId] < $rightLevel)) {
                $notPermittedUserContainerIds[] = $containerId;
            }
        }

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return user default template information
            $this->set('userDefaultTemplate', $userDefaultTemplate['UserDefaultTemplate']);
            $this->set('notPermittedUserContainerIds', array_map('intval', $notPermittedUserContainerIds)); // Make sure its a int array for Angular
            $this->viewBuilder()->setOption('serialize', ['userDefaultTemplate', 'notPermittedUserContainerIds']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('Userdefaulttemplate', []);
            if (!isset($data['UserDefaultTemplatesToUserContainers'])) {
                $data['UserDefaultTemplatesToUserContainers'] = [];
            }

            if (!$this->hasRootPrivileges) {
                $containerIdsWithWritePermissions = array_filter($this->MY_RIGHTS_LEVEL, function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);
                $userToEditContainerIdsWithWritePermissions = array_filter($data['UserDefaultTemplatesToUserContainers'], function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);

                $notPermittedUserContainerIds = array_keys(
                    array_diff_key($userToEditContainerIdsWithWritePermissions, $containerIdsWithWritePermissions)
                );

                foreach ($data['UserDefaultTemplatesToUserContainers'] as $key => $value) {
                    // do not overwrite container settings if the user does not have sufficient rights
                    if (in_array($key, $notPermittedUserContainerIds, true)) {
                        continue;
                    }
                    // reverting write permission to read permission due to insufficient user permission rights
                    if ($key !== ROOT_CONTAINER && !array_key_exists($key, $containerIdsWithWritePermissions) && $value > 1) {
                        $data['UserDefaultTemplatesToUserContainers'][$key] = READ_RIGHT;
                    }
                }
            }
            $data['user_containers'] = $UsersTable->containerPermissionsForSave(
                $data['UserDefaultTemplatesToUserContainers'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );


            $userDefaultTemplate = $UserDefaultTemplatesTable->get($id);
            $userDefaultTemplate->id = $id;
            $userDefaultTemplate = $UserDefaultTemplatesTable->patchEntity($userDefaultTemplate, $data);


            $UserDefaultTemplatesTable->save($userDefaultTemplate);

            if ($userDefaultTemplate->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $userDefaultTemplate->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            // No errors
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

        /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
        $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

        if (!$UserDefaultTemplatesTable->existsById($id)) {
            throw new NotFoundException(__('User default template not found'));
        }
        $userDefaultTemplate = $UserDefaultTemplatesTable->getUserDefaultTemplateById($id);

        if (!$this->allowedByContainerId($userDefaultTemplate['container_id'])) {
            $this->render403();
            return;
        }
        if (!in_array($userDefaultTemplate['container_id'], $this->MY_RIGHTS)) {
            $this->render403();
            return;
        }
        $userDefaultTemplateEntity = $UserDefaultTemplatesTable->get($id);
        if ($UserDefaultTemplatesTable->delete($userDefaultTemplateEntity)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }
}
