<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WindowsAppsHostsFixture
 */
class WindowsAppsHostsFixture extends TestFixture
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
                'windows_app_id' => 1,
                'host_id' => 1,
                'version' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-01-20 07:43:00',
                'modified' => '2026-01-20 07:43:00',
            ],
        ];
        parent::init();
    }
}
