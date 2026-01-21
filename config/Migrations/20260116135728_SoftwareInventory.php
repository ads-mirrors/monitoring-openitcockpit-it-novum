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

use Migrations\BaseMigration;

/**
 * Class SoftwareInventory
 *
 * Created:
 * oitc migrations create SoftwareInventory
 *
 * Run migration:
 * oitc migrations migrate
 *
 * Usage:
 * openitcockpit-update
 */
class SoftwareInventory extends BaseMigration {

    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void {

        // TODO ENABLE THIS !!
        /*if ($this->hasTable('agentconfigs')) {
            $this->table('agentconfigs')
                ->addColumn('enable_packagemanager', 'boolean', [
                    'default' => '1',
                    'limit'   => null,
                    'null'    => false,
                    'after'   => 'push_noticed'
                ])
                ->update();
        }*/

        if (!$this->hasTable('packages_host_details')) {
            $this->table('packages_host_details')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false

                ])
                ->addPrimaryKey(['id'])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('os_type', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('os_name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('os_version', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('os_family', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('agent_version', 'string', [
                    'limit' => 15,
                    'null'  => false,
                ])
                ->addColumn('reboot_required', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('system_uptime', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('last_update', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('last_error', 'string', [
                    'limit' => 1000,
                    'null'  => true,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('packages_linux')) {
            $this->table('packages_linux')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false

                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 1000,
                    'null'    => true,
                ])
                ->addColumn('is_patch', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'name',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('packages_linux_hosts')) {
            $this->table('packages_linux_hosts')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('package_linux_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('current_version', 'string', [
                    'limit' => 64,
                    'null'  => false,
                ])
                ->addColumn('available_version', 'string', [
                    'limit' => 64,
                    'null'  => false,
                ])
                ->addColumn('needs_update', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('is_security_update', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('is_patch', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'package_linux_id',
                        'host_id'
                    ]
                )
                ->addIndex(
                    [
                        'package_linux_id',
                        'current_version',
                        'available_version'
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('windows_apps')) {
            $this->table('windows_apps')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false

                ])
                ->addPrimaryKey(['id'])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('publisher', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'name',
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('windows_apps_hosts')) {
            $this->table('windows_apps_hosts')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('windows_app_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => false
                ])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('version', 'string', [
                    'limit' => 64,
                    'null'  => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'windows_app_id',
                        'host_id'
                    ]
                )
                ->create();
        }

        if (!$this->hasTable('windows_updates')) {
            $this->table('windows_updates')
                ->addColumn('id', 'biginteger', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                    'signed'        => false

                ])
                ->addPrimaryKey(['id'])
                ->addColumn('host_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('description', 'string', [
                    'default' => null,
                    'limit'   => 512,
                    'null'    => true,
                ])
                ->addColumn('kbarticle_ids', 'string', [
                    'default' => null,
                    'limit'   => 512,
                    'null'    => true,
                ])

                // The update ID will generally be a GUID, but it can be any string that uniquely identifies. This identifier is required for calling many WindowsUpdateAdministrator methods.
                // https://learn.microsoft.com/en-us/uwp/api/windows.management.update.windowsupdate.updateid?view=winrt-26100
                ->addColumn('update_id', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('reboot_required', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('is_security_update', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('is_optional', 'boolean', [
                    'default' => false,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'limit' => null,
                    'null'  => false,
                ])
                ->addIndex(
                    [
                        'host_id',
                        'name',
                        'kbarticle_ids'
                    ]
                )
                ->create();
        }

    }
}
