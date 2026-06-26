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

            $data = $this->request->getData('UserDefaultTemplates', []);
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
                'UserDefaultTemplates' => $data
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

    /**
     * Edit method
     *
     * @param string|null $id Ldap Import Setting id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null) {
        $ldapImportSetting = $this->UserDefaultTemplates->get($id, contain: ['Containers', 'Usercontainerroles', 'Ldapgroups']);
        $this->Authorization->authorize($ldapImportSetting);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $ldapImportSetting = $this->UserDefaultTemplates->patchEntity($ldapImportSetting, $this->request->getData());
            if ($this->UserDefaultTemplates->save($ldapImportSetting)) {
                $this->Flash->success(__('The ldap import setting has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ldap import setting could not be saved. Please, try again.'));
        }
        $usergroups = $this->UserDefaultTemplates->Usergroups->find('list', limit: 200)->all();
        $containers = $this->UserDefaultTemplates->Containers->find('list', limit: 200)->all();
        $usercontainerroles = $this->UserDefaultTemplates->Usercontainerroles->find('list', limit: 200)->all();
        $ldapgroups = $this->UserDefaultTemplates->Ldapgroups->find('list', limit: 200)->all();
        $this->set(compact('ldapImportSetting', 'usergroups', 'containers', 'usercontainerroles', 'ldapgroups'));
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
