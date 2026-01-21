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

namespace App\itnovum\openITCOCKPIT\Agent;

use App\Model\Table\MacosAppsTable;
use App\Model\Table\MacosUpdatesTable;
use App\Model\Table\PackagesHostDetailsTable;
use App\Model\Table\PackagesLinuxTable;
use App\Model\Table\WindowsAppsTable;
use App\Model\Table\WindowsUpdatesTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AgentSoftwareInventory {

    private PackagesHostDetailsTable|Table $PackagesHostDetailsTable;
    private PackagesLinuxTable|Table $PackagesLinuxTable;
    private WindowsAppsTable|Table $WindowsAppsTable;
    private WindowsUpdatesTable|Table $WindowsUpdatesTable;
    private MacosAppsTable|Table $MacosAppsTable;

    private MacosUpdatesTable|Table $MacosUpdatesTable;

    public function __construct() {

        /** @var PackagesHostDetailsTable $PackagesHostDetailsTable */
        $this->PackagesHostDetailsTable = TableRegistry::getTableLocator()->get('PackagesHostDetails');
        /** @var PackagesLinuxTable $PackagesLinuxTable */
        $this->PackagesLinuxTable = TableRegistry::getTableLocator()->get('PackagesLinux');
        /** @var WindowsAppsTable $WindowsAppsTable */
        $this->WindowsAppsTable = TableRegistry::getTableLocator()->get('WindowsApps');
        /** @var WindowsUpdatesTable $WindowsUpdatesTable */
        $this->WindowsUpdatesTable = TableRegistry::getTableLocator()->get('WindowsUpdates');
        /** @var MacosAppsTable $MacosAppsTable */
        $this->MacosAppsTable = TableRegistry::getTableLocator()->get('MacosApps');
        /** @var MacosUpdatesTable $MacosUpdatesTable */
        $this->MacosUpdatesTable = TableRegistry::getTableLocator()->get('MacosUpdates');
    }


    /**
     * @param int $hostId
     * @param array $result
     * @return void
     * @throws \Exception
     */
    public function processAgentInventoryResponse(int $hostId, array $result): void {
        if (!isset($result['Pending']) || $result['Pending']) {
            throw new \Exception('Agent reports pending state, skipping package update');
        }

        if (!empty($result['Stats']) && !empty($result['Stats']['OperatingSystem'])) {
            $details = [
                'os_type'         => $result['Stats']['OperatingSystem'],   // "linux", "macos", "windows"
                'os_name'         => $result['Stats']['OsName'],            // "ubuntu", "opensuse-tumbleweed", "almalinux", "macos",  "Microsoft Windows 11 Enterprise"
                'os_version'      => $result['Stats']['OsVersion'] ?? '',   // "20.04",  "20260113",            "9.7",       "26.2",   "24H2 (10.0.26100.7462 Build 26100.7462)"
                'os_family'       => $result['Stats']['OsFamily'] ?? '',    // "debian", "suse",                "rhel",      "darwin", "windows"
                'agent_version'   => $result['Stats']['AgentVersion'] ?? '',
                'reboot_required' => !empty($result['Stats']['RebootRequired']) ? 1 : 0,
                'system_uptime'   => $result['Stats']['Uptime'] ?? 0,
                'last_update'     => date('Y-m-d H:i:s', $result['LastUpdate']),
                'last_error'      => !empty($result['Stats']['LastError']) ? $result['Stats']['LastError'] : null
            ];
            $this->PackagesHostDetailsTable->updateHostDetails($hostId, $details);
        }

        switch ($result['Stats']['OperatingSystem']) {
            case 'linux':
                $installedPackages = $result['InstalledPackages'] ?? [];
                $availableUpdates = $result['LinuxUpdates'] ?? [];

                $this->PackagesLinuxTable->savePackagesForHost($hostId, $installedPackages, $availableUpdates);
                break;

            case 'windows':
                $apps = $result['WindowsApps'] ?? [];
                $availableUpdates = $result['WindowsUpdates'] ?? [];

                $this->WindowsAppsTable->saveAppsForHost($hostId, $apps);
                $this->WindowsUpdatesTable->saveUpdatesForHost($hostId, $availableUpdates);
                break;

            case 'macos':
                $apps = $result['MacOSApps'] ?? [];
                $availableUpdates = $result['MacosUpdates'] ?? [];

                $this->MacosAppsTable->saveAppsForHost($hostId, $apps);
                $this->MacosUpdatesTable->saveUpdatesForHost($hostId, $availableUpdates);
                break;

            default:
                throw new \Exception('Package processing for OS ' . $result['Stats']['OperatingSystem'] . ' not implemented yet');
                break;
        }
    }

    /**
     * Cleans up unused packages and apps from the database.
     * @return void
     */
    public function cleanupUnusedPackages(): void {
        $this->PackagesLinuxTable->deleteUnusedPackages();
        $this->WindowsAppsTable->deleteUnusedApps();
        $this->MacosAppsTable->deleteUnusedApps();
    }

}
