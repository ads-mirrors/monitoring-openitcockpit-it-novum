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

namespace App\Command;

use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UserDefaultTemplatesTable;
use App\Model\Table\UsersTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Ldap\LdapClient;

/**
 * LdapAutocreateUsers command.
 */
class LdapAutocreateUsersCommand extends Command {
    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'ldap_autocreate_users';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string {
        return 'ldap_autocreate_users';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string {
        return 'A command to autocreate users from LDAP based on their group memberships and user default templates.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription());
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {

        $io->out('Scan for new LDAP users. This will take a while...');

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));

        // get all users from ldap
        $usersFromLdap = $Ldap->getUsersForLdapImport('', true);

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        // get all current users in OITC
        $usersFromDb = $UsersTable->getUsersForLdapImport();

        // check which users are not in the DB
        $newUsers = [];
        foreach ($usersFromLdap as $userFromLdap) {
            $isNewUser = true;
            foreach ($usersFromDb as $userFromDb) {
                if ($userFromDb['email'] === $userFromLdap['email']) {
                    $isNewUser = false;
                }
            }
            if ($isNewUser) {
                $newUsers[] = $userFromLdap;
            }
        }


        // get the ldap details for the new users
        $newUsersWithLdapDetails = $this->loadLdapUserDetailsForUsers($newUsers);
        $newUsersWithTemplates = $this->loadUserDefaultTemplatesForUsers($newUsersWithLdapDetails, $io);
        $this->createUsersByUserDefaultTemplates($newUsersWithTemplates, $io);

    }

    private function loadLdapUserDetailsForUsers($users) {
        if (!empty($users)) {

            /** @var UsercontainerrolesTable $UsercontainerrolesTable */
            $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

            foreach ($users as $userKey => $user) {

                $user['userContainerRoleContainerPermissionsLdap'] = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                    $user['memberof']
                );

                $usercontainerroles_ldap = [];
                foreach ($user['userContainerRoleContainerPermissionsLdap'] as $userContainerRole) {
                    foreach ($userContainerRole['containers'] as $container) {
                        $usercontainerroles_ldap[] = $container['id'];
                    }
                }

                $user['usercontainerroles_ldap']['_ids'] = $usercontainerroles_ldap;
                $users[$userKey] = $user;
            }
        }

        return $users;

    }

    private function loadUserDefaultTemplatesForUsers(array $users, ConsoleIo $io) {

        $io->out('Load User default templates. This will take a while...');

        if (!empty($users)) {

            /** @var UserDefaultTemplatesTable $UserDefaultTemplatesTable */
            $UserDefaultTemplatesTable = TableRegistry::getTableLocator()->get('UserDefaultTemplates');

            // collect all ldapgroups to get all related user default templates
            $allLdapgroupIds = Hash::extract($users, '{n}.ldapgroups.{n}.id');
            $userDefaultTemplatesAndDetails = $UserDefaultTemplatesTable->getUserDefaultTemplatesForUserEdit($allLdapgroupIds);

            // build ldagroupId to userDefaultTemplates array
            $userDefaultTemplatesByLdapgroupIds = [];
            foreach ($userDefaultTemplatesAndDetails['UserDefaultTemplateDetails'] as $userDefaultTemplatesAndDetail) {
                foreach ($userDefaultTemplatesAndDetail['ldapgroups']['_ids'] as $ldapgroupId) {
                    if (isset($userDefaultTemplatesByLdapgroupIds[$ldapgroupId])) {
                        $userDefaultTemplatesByLdapgroupIds[$ldapgroupId][] = $userDefaultTemplatesAndDetail;
                    } else {
                        $userDefaultTemplatesByLdapgroupIds[$ldapgroupId] = [$userDefaultTemplatesAndDetail];
                    }
                }
            }

            // add user default template to user
            foreach ($users as $userKey => $user) {
                if (isset($user['ldapgroups']) && !empty($user['ldapgroups'])) {
                    foreach ($user['ldapgroups'] as $ldapgroup) {
                        if (isset($userDefaultTemplatesByLdapgroupIds[$ldapgroup['id']])) {
                            $user['userDefaultTemplate'] = $userDefaultTemplatesByLdapgroupIds[$ldapgroup['id']][0];
                            break;
                        }
                    }
                }
                $users[$userKey] = $user;
            }

        }

        return $users;
    }

    private function createUsersByUserDefaultTemplates(array $users, ConsoleIo $io) {

        $io->out('Create new LDAP users. This will take a while...');

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        foreach ($users as $userKey => $newUserFromLdap) {
            if (!isset($newUserFromLdap['userDefaultTemplate'])) {
                $io->out('User Creation for ' . $newUserFromLdap['email'] . ' was skipped, because of missing user default template.');
                continue;
            }

            $newUser = [
                'samaccountname'             => $newUserFromLdap['samaccountname'],
                'company'                    => $newUserFromLdap['company'],
                'department'                 => $newUserFromLdap['department'],
                'email'                      => $newUserFromLdap['email'],
                'firstname'                  => $newUserFromLdap['givenname'],
                'lastname'                   => $newUserFromLdap['sn'],
                'ldap_dn'                    => $newUserFromLdap['dn'],
                'timezone'                   => $newUserFromLdap['userDefaultTemplate']['timezone'],
                'i18n'                       => $newUserFromLdap['userDefaultTemplate']['i18n'],
                'dateformat'                 => $newUserFromLdap['userDefaultTemplate']['dateformat'],
                'showstatsinmenu'            => $newUserFromLdap['userDefaultTemplate']['showstatsinmenu'],
                'is_active'                  => true,
                'dashboard_tab_rotation'     => $newUserFromLdap['userDefaultTemplate']['dashboard_tab_rotation'],
                'paginatorlength'            => $newUserFromLdap['userDefaultTemplate']['paginatorlength'],
                'recursive_browser'          => $newUserFromLdap['userDefaultTemplate']['recursive_browser'],
                'is_oauth'                   => $newUserFromLdap['userDefaultTemplate']['is_oauth'],
                'usergroup_id'               => $newUserFromLdap['userDefaultTemplate']['usergroup_id'],
                'container_id'               => $newUserFromLdap['userDefaultTemplate']['container_id'],
                'user_default_template_id'   => $newUserFromLdap['userDefaultTemplate']['id'],
                'ContainersUsersMemberships' => $newUserFromLdap['userDefaultTemplate']['UserDefaultTemplatesToUserContainers'],
                'is_ldap'                    => true,
                'password'                   => "",
                'confirm_password'           => "",
                'phone'                      => "",
                'apikeys'                    => [],
                'usercontainerroles'         => [],
                'usercontainerroles_ldap'    => $newUserFromLdap['usercontainerroles_ldap']
            ];

            $newUser['containers'] = $UsersTable->containerPermissionsForSave(
                $newUser['ContainersUsersMemberships'],
                true,
            );

            // Convert old belongsToMany request into through join Membership data.

            // Add user container roles that are assigned via LDAP
            foreach ($newUserFromLdap['userContainerRoleContainerPermissionsLdap'] as $usercontainerrole) {
                $usercontainerroleId = $usercontainerrole['id'];
                $newUser['usercontainerroles'][$usercontainerroleId] = [
                    'id'        => $usercontainerroleId,
                    '_joinData' => [
                        'through_ldap' => true // This got assigned automatically via LDAP
                    ]
                ];
            }

            //remove password validation when user is imported from ldap
            $UsersTable->getValidator()->remove('password');
            $UsersTable->getValidator()->remove('confirm_password');

            $user = $UsersTable->newEmptyEntity();
            $user = $UsersTable->patchEntity($user, $newUser);

            $data = [
                'User' => $newUser
            ];

            $user = $UsersTable->createUser($user, $data, 0);
            if ($user->hasErrors()) {
                $io->out('Error on user Creation for ' . $newUserFromLdap['email']);
                $io->out('Errors: ' . json_encode($user->getErrors()));
                continue;
            }

            $io->out('LDAP user created for ' . $newUserFromLdap['email'] . ' with user default template "' . $newUserFromLdap['userDefaultTemplate']['name'] . '" (Template ID: ' . $newUserFromLdap['userDefaultTemplate']['id'] . ')');

        }
    }
}
