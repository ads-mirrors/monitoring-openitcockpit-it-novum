<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MacosAppsHostsFixture
 */
class MacosAppsHostsFixture extends TestFixture
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
                'macos_app_id' => 1,
                'host_id' => 1,
                'version' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-01-21 10:47:26',
                'modified' => '2026-01-21 10:47:26',
            ],
        ];
        parent::init();
    }
}
