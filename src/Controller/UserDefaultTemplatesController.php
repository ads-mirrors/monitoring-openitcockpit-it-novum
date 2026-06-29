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

use App\Model\Table\UserDefaultTemplatesTable;
use Cake\Cache\Cache;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * UserDefaultTemplates Controller
 * @package App\Controller
 * @property \App\Model\Table\UserDefaultTemplatesTable $UserDefaultTemplates
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class UserDefaultTemplatesController extends AppController {

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {
        $query = $this->UserDefaultTemplates->find()
            ->contain(['Usergroups']);
        $query = $this->Authorization->applyScope($query);
        $userDefaultTemplates = $this->paginate($query);

        $this->set(compact('userDefaultTemplates'));
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
     * Delete method
     *
     * @param string|null $id Ldap Import Setting id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $ldapImportSetting = $this->UserDefaultTemplates->get($id);
        $this->Authorization->authorize($ldapImportSetting);
        if ($this->UserDefaultTemplates->delete($ldapImportSetting)) {
            $this->Flash->success(__('The ldap import setting has been deleted.'));
        } else {
            $this->Flash->error(__('The ldap import setting could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
