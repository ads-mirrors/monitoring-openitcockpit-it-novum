<?php declare(strict_types=1);
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

namespace App\Template\Users;

use App\itnovum\openITCOCKPIT\Export\CustomSpreadsheet;
use App\Model\Table\ContainersTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Cake\Log\Log;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use FreeDSx\Ldap\Exception\BindException;
use itnovum\openITCOCKPIT\Ldap\LdapClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UsersXlsxExport {

    private Spreadsheet $Spreadsheet;
    private int $loggedInUserID;
    private array $users;
    private array $UsersArray;
    private array $UserGroupsArray;
    private array $ContainersArray;
    private array $MY_RIGHTS;
    private bool $hasRootPrivileges;
    private bool $canBeExported = false;


    public function __construct(int $loggedInUserID, array $users, array $MY_RIGHTS, bool $hasRootPrivileges = false) {
        $this->loggedInUserID = $loggedInUserID;
        $this->users = $users;
        $this->MY_RIGHTS = $MY_RIGHTS;
        $this->hasRootPrivileges = $hasRootPrivileges;
        $this->Spreadsheet = new CustomSpreadsheet();
    }

    /**
     * I will generate the entire export in one method.
     * This means, I will...
     *   - Fetch data from CakePHP Tables
     *   - Build Sheets
     *   - Save the XLSX file to the given $fileName.
     *
     * @param string $fileName
     * @return void
     * @throws MissingEntityException
     * @throws BindException
     */
    public function export(string $fileName): void {
        $this->parseDataForExport();
        $this->UsersSheet();

        if ($this->canBeExported) {
            $this->UserRolesSheet();
            $this->ContainersSheet();

            $this->setAutoSizeForAllColumns();
        }

        $writer = new Xlsx($this->Spreadsheet);
        $writer->save($fileName);
    }

    /**
     * @return void
     */
    private function parseDataForExport(): void {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');
        try {
            $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));
            $ldapConnectionSuccessful = true;
        } catch (\Exception $e) {
            Log::error('Error while connecting to LDAP: ' . $e->getMessage());
            $ldapConnectionSuccessful = false;
        }

        /***  Users Section ***/

        $userGroupsIds = [];
        $this->UsersArray[] = [
            __('User ID'),
            __('Full name'),
            __('First name'),
            __('Last name'),
            __('Mail'),
            __('Company'),
            __('User role ID'),
            __('User role / Fallback User role'),
            __('LDAP user'),
            __('oAuth user'),
            __('User role through LDAP ID'),
            __('User role through LDAP'),
        ];
        $userNamesArray = [];
        $allUsersContainerIds = [];
        $userContainerArray = [];
        $rightsForIntersect = array_flip($this->MY_RIGHTS);
        if (!empty($this->users)) {
            $this->canBeExported = true;
            foreach ($this->users as $user) {
                $userDetails = $UsersTable->getUserForEdit($user['id']);
                $userNamesArray[] = sprintf('%s [%s]', $user['full_name'], $user['id']);
                $userContainerIds = [];
                $userGroupsIds[$userDetails['User']['usergroup_id']] = $userDetails['User']['usergroup_id'];
                $usergroupLdap = null;
                foreach ($user['usercontainerroles'] as $containerRole) {
                    foreach ($containerRole['containers'] as $container) {
                        $userContainerIds[$container['id']] = $container['_joinData']['permission_level'];
                        $allUsersContainerIds[$container['id']] = $container['id'];
                    }
                }
                foreach ($user['containers'] as $container) {
                    $userContainerIds[$container['id']] = $container['_joinData']['permission_level'];
                    $allUsersContainerIds[$container['id']] = $container['id'];
                }

                if (!empty($user['samaccountname']) && $ldapConnectionSuccessful) {
                    $ldapUser = $Ldap->getUser($user['samaccountname'], true);
                    if ($ldapUser) {
                        $ldapUser['userContainerRoleContainerPermissionsLdap'] = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                            $ldapUser['memberof']
                        );
                        foreach ($ldapUser['userContainerRoleContainerPermissionsLdap'] as $userContainerRole) {
                            foreach ($userContainerRole['containers'] as $container) {
                                if (isset($userContainerIds[$container['id']])) {
                                    //Container permission is already set.
                                    //Only overwrite it, if it is a WRITE_RIGHT
                                    if ($container['_joinData']['permission_level'] === WRITE_RIGHT) {
                                        $userContainerIds[$container['id']] = $container['_joinData']['permission_level'];
                                        $allUsersContainerIds[$container['id']] = $container['id'];
                                    }
                                } else {
                                    //Container is not yet in permissions - add it
                                    $userContainerIds[$container['id']] = $container['_joinData']['permission_level'];
                                    $allUsersContainerIds[$container['id']] = $container['id'];
                                }
                            }
                        }
                        // Load matching user role (Administrator, Viewer, etc...)
                        $usergroupLdap = $UsergroupsTable->getUsergroupByLdapUserMemberOf($ldapUser['memberof']);
                    }
                    if (!empty($usergroupLdap)) {
                        $userGroupsIds[$usergroupLdap['id']] = $usergroupLdap['id'];
                    }
                }
                $userContainerArray[$user['id']] = $userContainerIds;

                if (!$this->hasRootPrivileges) {
                    $userContainerArray[$user['id']] = array_intersect(
                        $userContainerIds,
                        $rightsForIntersect
                    );
                }
                $this->UsersArray[] = [
                    $user['id'],
                    $user['full_name'],
                    $userDetails['User']['firstname'],
                    $userDetails['User']['lastname'],
                    $user['email'],
                    $user['company'],
                    $user['usergroup']['id'],
                    $user['usergroup']['name'],
                    !empty($user['samaccountname']) ? __('Yes') : __('No'),
                    $user['is_oauth'] ? __('Yes') : __('No'),
                    $usergroupLdap['id'] ?? null, //LDAP CHECK
                    $usergroupLdap['name'] ?? null //LDAP CHECK
                ];
            }

            if (!$this->hasRootPrivileges) {
                $allUsersContainerIds = array_intersect(
                    $allUsersContainerIds,
                    $this->MY_RIGHTS
                );
                // set R+W Rights for visible containers, if exported users has root privileges, root user has rights for all containers
                foreach ($userContainerArray as $userId => $containers) {
                    if ((int)$userId !== $this->loggedInUserID) {
                        if (isset($containers[ROOT_CONTAINER]) && $containers[ROOT_CONTAINER] === WRITE_RIGHT) {
                            foreach ($userContainerArray[$this->loggedInUserID] as $containerId => $permissionLevel) {
                                $userContainerArray[$userId][$containerId] = WRITE_RIGHT;
                            }
                        }
                    }
                }
            }
            /***  Containers Section ***/
            $this->buildContainerData($userNamesArray, $allUsersContainerIds, $userContainerArray);

            /***  User groups Section ***/
            $this->buildUsergroupData($userGroupsIds);
        }
    }

    private function buildContainerData(array $userNamesArray, array $allUsersContainerIds, array $userContainerArray): void {

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        $this->ContainersArray[] = array_merge([__('Container ID'), __('Container')], $userNamesArray);

        $containerPermissionsByUserId = [];
        if (!$this->hasRootPrivileges) {
            if (isset($allUsersContainerIds[ROOT_CONTAINER])) {
                unset($allUsersContainerIds[ROOT_CONTAINER]);
            }
        }

        $allVisibleContainers = $ContainersTable->getResolvedContainersWithFullPathAndChildred($allUsersContainerIds);
        //refill rights for subcontainer
        if (!empty($userContainerArray) && !empty($allVisibleContainers['userParentAndChildrenContainers'])) {
            foreach ($userContainerArray as $userID => $containerIds) {
                $containerPermissionsByUserId[$userID] = [];
                foreach ($containerIds as $id => $permissionLevel) {
                    $containerPermissionsByUserId[$userID][$id] = $permissionLevel;
                    if (isset($allVisibleContainers['userParentAndChildrenContainers'][$id])) {

                        foreach ($allVisibleContainers['userParentAndChildrenContainers'][$id] as $subcontainerId) {
                            if (!in_array($subcontainerId, $containerIds, true)) {
                                $containerPermissionsByUserId[$userID][$subcontainerId] = $permissionLevel;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($allVisibleContainers['resolvedContainers']) && !empty($allVisibleContainers['userParentAndChildrenContainers'])) {
            foreach ($allVisibleContainers['resolvedContainers'] as $resolvedContainerId => $resolvedContainer) {
                $row = [
                    $resolvedContainerId,
                    $resolvedContainer
                ];
                foreach ($containerPermissionsByUserId as $userId => $containerPermissions) {
                    if (isset($containerPermissions[$resolvedContainerId])) {
                        $row[] = $containerPermissions[$resolvedContainerId] === WRITE_RIGHT ? 'R + W' : 'R';
                    } else {
                        $row[] = null;
                    }
                }
                $this->ContainersArray[] = $row;
            }
        }
    }

    private function buildUsergroupData(array $userGroupsIds): void {
        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        $usergroups = $UsergroupsTable->getUsergroupsByIds($userGroupsIds);
        if (!empty($usergroups)) {
            $usergroups = Hash::sort($usergroups, '{n}.name');
        }
        $this->UserGroupsArray = [];
        $userGroupNamesArray = [];
        $userGroupArray = [];
        foreach ($usergroups as $usergroup) {
            $userGroupNamesArray[] = sprintf('%s [%s]', $usergroup['name'], $usergroup['id']);

            $userGroupArray[$usergroup['id']] = $usergroup;
            $userGroupArray[$usergroup['id']]['allowed_acos'] = $UsergroupsTable->getOnlyAllowedAcosIdByUsergroupId($usergroup['id']);
        }

        $this->UserGroupsArray[] = array_merge([__('(Module) + Controller'), __('Action')], $userGroupNamesArray);
        $allExistingAcos = $UsergroupsTable->getAllAcosAsFilteredList(true);
        foreach ($allExistingAcos as $acoId => $acoPath) {
            preg_match('/^(.*)\/(.*)$/', $acoPath, $matches);
            if (isset($matches[1]) && isset($matches[2]) && !empty($userGroupArray)) {
                $controllerAndModule = $matches[1];
                $action = $matches[2];
                $row = [
                    $controllerAndModule,
                    $action
                ];
                foreach ($userGroupArray as $usergroup) {
                    if (isset($usergroup['allowed_acos'][$acoId])) {
                        $row[] = '✓';
                    } else {
                        $row[] = null;
                    }
                }
                $this->UserGroupsArray[] = $row;
            }
        }
    }


    private function UsersSheet(): void {
        $this->Spreadsheet->getActiveSheet()
            ->setTitle(__('Users'))
            ->fromArray(
                $this->UsersArray
            )->freezePane('A2');
    }

    private function UserRolesSheet(): void {
        $sheet = $this->Spreadsheet->createCustomSheet();
        $sheet->setTitle('User Roles')
            ->fromArrayWithCenteredValues(
                $this->UserGroupsArray,
                null,
                'A1',
                false,
                ['✓']
            )
            ->freezePane([3, 2]);
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    private function ContainersSheet(): void {
        $sheet = $this->Spreadsheet->createCustomSheet();
        $sheet->setTitle('Containers')
            ->fromArrayWithCenteredValues(
                $this->ContainersArray,
                null,
                'A1',
                false,
                ['R', 'R + W']
            )
            ->freezePane([3, 2]);
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    private function setAutoSizeForAllColumns(): void {
        $this->Spreadsheet->setActiveSheetIndex(0);
        foreach ($this->Spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $this->Spreadsheet->getActiveSheet()->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }
}
