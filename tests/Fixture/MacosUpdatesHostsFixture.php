<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MacosUpdatesHostsFixture
 */
class MacosUpdatesHostsFixture extends TestFixture
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
                'macos_update_id' => 1,
                'host_id' => 1,
                'created' => '2026-01-26 07:59:57',
                'modified' => '2026-01-26 07:59:57',
            ],
        ];
        parent::init();
    }
}
