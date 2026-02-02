<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WindowsUpdatesHostsFixture
 */
class WindowsUpdatesHostsFixture extends TestFixture
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
                'windows_update_id' => 1,
                'host_id' => 1,
                'reboot_required' => 1,
                'is_security_update' => 1,
                'is_optional' => 1,
                'created' => '2026-01-26 07:59:37',
                'modified' => '2026-01-26 07:59:37',
            ],
        ];
        parent::init();
    }
}
