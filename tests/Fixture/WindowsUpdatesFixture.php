<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WindowsUpdatesFixture
 */
class WindowsUpdatesFixture extends TestFixture
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
                'name' => 'Lorem ipsum dolor sit amet',
                'description' => 'Lorem ipsum dolor sit amet',
                'kbarticle_ids' => 'Lorem ipsum dolor sit amet',
                'update_id' => 'Lorem ipsum dolor sit amet',
                'reboot_required' => 1,
                'is_security_update' => 1,
                'is_optional' => 1,
                'created' => '2026-01-20 07:43:08',
                'modified' => '2026-01-20 07:43:08',
            ],
        ];
        parent::init();
    }
}
