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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\PushNotificationsRelayTable;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Class NotificationsRelayController
 * @package App\Controller
 */
class NotificationsrelayController extends AppController {

    use LocatorAwareTrait;

    function index() {
        if (!$this->isAngularJsRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $TableLocator = $this->getTableLocator();

        /** @var PushNotificationsRelayTable $PushNotificationsRelayTable */
        $PushNotificationsRelayTable = $TableLocator->get('PushNotificationsRelay');

        if ($this->request->is('post') && $this->isAngularJsRequest()) {
            $entity = $PushNotificationsRelayTable->find()->first();
            if (is_null($entity)) {
                //No proxy configuration found
                $entity = $PushNotificationsRelayTable->newEmptyEntity();
            }

            $entity = $PushNotificationsRelayTable->patchEntity($entity, $this->request->getData('Relay'));

            if ($entity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $PushNotificationsRelayTable->save($entity);
        }

        $settings = $PushNotificationsRelayTable->getSettings();
        $this->set('relay', $settings);
        $this->viewBuilder()->setOption('serialize', ['relay']);
    }

}
