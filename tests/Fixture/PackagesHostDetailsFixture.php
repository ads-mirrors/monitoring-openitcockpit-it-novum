<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PackagesHostDetailsFixture
 */
class PackagesHostDetailsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'host_id' => 1,
                'os_name' => 'Lorem ipsum dolor sit amet',
                'os_version' => 'Lorem ipsum dolor sit amet',
                'agent_version' => 'Lorem ipsum d',
                'reboot_required' => 1,
                'system_uptime' => 1,
                'last_update' => '2026-01-19 14:05:26',
                'last_error' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-01-19 14:05:26',
                'modified' => '2026-01-19 14:05:26',
            ],
        ];
        parent::init();
    }
}
