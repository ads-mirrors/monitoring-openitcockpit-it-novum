<?php declare(strict_types=1);
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

namespace App\Template\Users;

use Acl\Model\Table\AcosTable;
use App\Lib\AclDependencies;
use App\Model\Entity\User;
use App\Model\Table\ContainersTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Ldap\LdapClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class UsersXlsxExport {
    private array $MY_RIGHTS;
    private bool $hasRootPrivileges;
    private Spreadsheet $Spreadsheet;
    private UsercontainerrolesTable $UsercontainerrolesTable;
    private UsergroupsTable $UsergroupsTable;
    private array $Users = [];
    private array $Containers = [];
    private array $ContainerRoles = [];
    private array $UserRoles = [];
    private array $Permissions = [];

    /**
     * I am the container permission matrix. I will look like this:
     * $this->ContainerPermissions = [
     *     42 => [  // CONTAINER ID
     *         13 => 2  // USER ID => PERMISSION LEVEL
     *     ]
     * ]
     */
    private array $ContainerPermissions = [];

    public function __construct(array $MY_RIGHTS, bool $hasRootPrivileges) {
        $this->MY_RIGHTS = $MY_RIGHTS;
        $this->hasRootPrivileges = $hasRootPrivileges;
        $this->Spreadsheet = new Spreadsheet();

        $this->UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');
        $this->UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');
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
     */
    public function export(string $fileName): void {
        $this->UsersSheet();
        $this->UserRolesSheet();
        $this->ContainersSheet();

        $writer = new Xlsx($this->Spreadsheet);
        $writer->save($fileName);
    }

    /**
     * I will build the entire Sheet "Users".
     * @return void
     */
    private function UsersSheet(): void {
        $this->buildUserData();
        $sheet = $this->Spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');
        $row = 0;
        $col = 0;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'First name');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Last name');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Mail');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User Role ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User role / Fallback User role');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Is LDAP User');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User role through LDAP ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'User role through LDAP');

        // Body Rows
        foreach ($this->Users as $UserId => $User) {
            $row++;
            $col = 0;

            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserId}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['firstname']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['lastname']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['email']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['usergroup']['id']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['usergroup']['name']}");
            $sheet->setCellValue(self::getCellPosition($col++, $row), $User['samaccountname'] ? 'YES' : 'NO');
            $sheet->setCellValue(self::getCellPosition($col++, $row), $User['UserRoleThroughLdap']['id'] ?? '');
            $sheet->setCellValue(self::getCellPosition($col++, $row), $User['UserRoleThroughLdap']['name'] ?? '');
        }
    }

    /**
     * I will build the data for the Users Sheet.
     * @return void
     */
    private function buildUserData(): void {
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $all_tmp_users = $UsersTable->getUsersExport($this->MY_RIGHTS);
        $LdapClient = $this->getLdapClient();
        foreach ($all_tmp_users as $_user) {
            /** @var User $_user */
            $user = $_user->toArray();

            if ($LdapClient && !empty($user['samaccountname'])) {
                $ldapUser = $LdapClient->getUser($user['samaccountname'], true);
                if (!$ldapUser) {
                    continue;
                }

                $ldapUser = $this->fetchLdapUserAttributes($ldapUser);
                $user = array_merge($user, $ldapUser);
            }
            $user['name'] = "{$user['firstname']} {$user['lastname']}";
            $this->Users[] = $user;
        }
    }

    private function fetchLdapUserAttributes(array $ldapUser): array {
        $ldapUser['userContainerRoleContainerPermissionsLdap'] = $this->UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
            $ldapUser['memberof']
        );

        $permissions = [];
        foreach ($ldapUser['userContainerRoleContainerPermissionsLdap'] as $userContainerRole) {
            foreach ($userContainerRole['containers'] as $container) {
                if (isset($permissions[$container['id']])) {
                    //Container permission is already set.
                    //Only overwrite it, if it is a WRITE_RIGHT
                    if ($container['_joinData']['permission_level'] === WRITE_RIGHT) {
                        $permissions[$container['id']] = $container;
                    }
                } else {
                    //Container is not yet in permissions - add it
                    $permissions[$container['id']] = $container;
                }
                $permissions[$container['id']]['user_roles'][$userContainerRole['id']] = [
                    'id'   => $userContainerRole['id'],
                    'name' => $userContainerRole['name']
                ];
            }
        }
        $ldapUser['userContainerRoleContainerPermissionsLdap'] = $permissions;

        // Load matching user role (Adminisgtrator, Viewer, etc...)
        $ldapUser['UserRoleThroughLdap'] = $this->UsergroupsTable->getUsergroupByLdapUserMemberOf($ldapUser['memberof']);

        return $ldapUser;
    }

    /**
     * I will build the entire Sheet "User Roles".
     * @return void
     */
    private function UserRolesSheet(): void {
        $this->buildUserRolesData();
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('User Roles');
        $row = 0;
        $col = 0;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), '(Module) + Controller');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Action');
        foreach ($this->UserRoles as $UserRole) {
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$UserRole['name']} [ID {$UserRole['id']}]");
        }

        // Body Rows
        foreach ($this->Permissions as $Permission) {
            $row++;
            $col = 0;

            $moduleControllerString = $Permission['controller'];
            if ($Permission['module']) {
                $moduleControllerString = "{$Permission['module']}/{$Permission['controller']}";
            }

            $sheet->setCellValue(self::getCellPosition($col++, $row), "$moduleControllerString");
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$Permission['action']}");
            foreach ($this->UserRoles as $UserRole) {
                $cellValue = $this->userRoleHasPermission($UserRole, $Permission) ? 'YES' : 'NO';
                $sheet->setCellValue(self::getCellPosition($col++, $row), $cellValue);
            }
        }
    }

    /**
     * I will build the data for the User Roles Sheet.
     * @return void
     */
    private function buildUserRolesData(): void {
        $this->UserRoles = $this->UsergroupsTable->find()
            ->contain([
                'Aros'       => [
                    'Acos'
                ],
                'Ldapgroups' => [
                    'fields' => [
                        'Ldapgroups.id'
                    ]
                ]
            ])
            ->all()->toArray();

        /** @var AcosTable $AcosTable */
        $AcosTable = TableRegistry::getTableLocator()->get('Acl.Acos');
        $acos = $AcosTable->find('threaded')
            ->disableHydration()
            ->all();
        $AclDependencies = new AclDependencies();
        $AclDList = $AclDependencies->filterAcosForFrontend($acos->toArray());
        foreach ($AclDList as $AclD) {
            if ($AclD['children']) {
                $this->addPermissionRow($AclD);
            }
        }
    }

    /**
     * I will build the entire Sheet "Containers".
     * @return void
     */
    private function ContainersSheet(): void {
        $this->buildContainersData();
        $this->buildPermissionsMatrix();
        $sheet = $this->Spreadsheet->createSheet();
        $sheet->setTitle('Containers');
        $row = 0;
        $col = 0;

        // Header Row
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Container ID');
        $sheet->setCellValue(self::getCellPosition($col++, $row), 'Container');
        foreach ($this->Users as $User) {
            $sheet->setCellValue(self::getCellPosition($col++, $row), "{$User['name']} [ID {$User['id']}]");
        }

        // Body Rows
        foreach ($this->Containers as $Container) {
            $row++;
            $col = 0;

            $sheet->setCellValue(self::getCellPosition($col++, $row), $Container['id']);
            $sheet->setCellValue(self::getCellPosition($col++, $row), $Container['name']);
            foreach ($this->Users as $User) {
                $permission = (int)$this->ContainerPermissions[$Container['id']][$User['id']];
                $permissionText = match ($permission) {
                    1 => 'R',
                    2 => 'RW',
                    default => '',
                };
                $sheet->setCellValue(self::getCellPosition($col++, $row), $permissionText);
            }
        }
    }

    /**
     * I will build the data for the Containers Sheet.
     * @return void
     */
    private function buildContainersData(): void {
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        if ($this->hasRootPrivileges === true) {
            $this->Containers = $ContainersTable->find()
                ->where(['Containers.containertype_id IN' => [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE]])
                ->disableHydration()
                ->toArray();
        } else {
            $this->Containers = $ContainersTable->find()
                ->andWhere([
                    'Containers.containertype_id IN' => [CT_GLOBAL, CT_TENANT, CT_LOCATION, CT_NODE],
                    'Containers.id IN '              => $this->MY_RIGHTS
                ])
                ->disableHydration()
                ->toArray();
        }

        foreach ($this->Containers as &$Container) {
            $Container['name'] = '/' . $ContainersTable->treePath($Container['id'], '/');
        }
        usort($this->Containers, static function (array $a, array $b) {
            return strcmp($a['name'], $b['name']);
        });

        $this->ContainerRoles = $this
            ->UsercontainerrolesTable
            ->find()
            ->contain(['Containers', 'Users'])
            ->toArray();
    }

    /**
     * I will traverse ContainerRoles and Users to build the ContainerPermissions matrix.
     * @return void
     * @todo LDAP Permissions
     */
    private function buildPermissionsMatrix(): void {
        foreach ($this->Users as $User) {
            foreach ($User['usercontainerroles'] as $UCR) {
                if ($UCR['_joinData']['through_ldap']) {
                    foreach ($UCR['containers'] as $Container) {
                        $this->ContainerPermissions[(int)$Container['id']][(int)$User['id']] = (int)$Container['_joinData']['permission_level'];
                    }
                }
            }
        }

        // Load permissions from Container Roles
        foreach ($this->ContainerRoles as $ContainerRoles) {
            foreach ($ContainerRoles['users'] as $User) {
                foreach ($ContainerRoles['containers'] as $Container) {
                    $this->ContainerPermissions[(int)$Container['id']][(int)$User['id']] = (int)$Container['_joinData']['permission_level'];
                }
            }
        }

        // Override explicitly given permissions from Users
        foreach ($this->Users as $User) {
            foreach ($User['containers'] as $UserContainer) {
                $this->ContainerPermissions[(int)$UserContainer['id']][(int)$User['id']] = (int)$UserContainer['_joinData']['permission_level'];
            }
        }
    }

    /**
     * I will return the Excel Cell Position like A1, B2, C3, ...
     * @param int $col
     * @param int $row
     * @return string
     */
    private static function getCellPosition(int $col, int $row): string {
        $letters = '';
        while ($col >= 0) {
            $letters = chr(($col % 26) + 65) . $letters;
            $col = (int)($col / 26) - 1;
        }
        return $letters . $row + 1;
    }


    /**
     * If openITCOCKPIT is configured to use LDAP, I will return an instance of LdapClient.
     * @return LdapClient|null
     */
    private function getLdapClient(): LdapClient|null {
        try {
            /** @var SystemsettingsTable $SystemSettingsTable */
            $SystemSettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            return LdapClient::fromSystemsettings($SystemSettingsTable->findAsArraySection('FRONTEND'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * I will check, if the given UserRole has the given Permission.
     * @param $UserRole
     * @param $Permission
     * @return bool
     */
    private function userRoleHasPermission($UserRole, $Permission): bool {
        foreach ($UserRole['aro']['acos'] as $Aco) {
            if ($Aco['id'] === $Permission['id']) {
                return true;
            }
        }
        return false;
    }

    private function addPermissionRow(array $Permission, string $controller = ''): void {
        if ($Permission['children']) {
            foreach ($Permission['children'] as $Child) {
                $this->addPermissionRow($Child, $Permission['alias']);
            }
            return;
        }
        $this->Permissions[] = [
            'module'     => '',
            'id'         => $Permission['id'],
            'controller' => $controller,
            'action'     => $Permission['alias']
        ];
    }
}
