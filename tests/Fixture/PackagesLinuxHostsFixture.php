<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PackagesLinuxHostsFixture
 */
class PackagesLinuxHostsFixture extends TestFixture
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
                'package_linux_id' => 1,
                'host_id' => 1,
                'current_version' => 'Lorem ipsum dolor sit amet',
                'available_version' => 'Lorem ipsum dolor sit amet',
                'needs_update' => 1,
                'is_security_update' => 1,
                'is_patch' => 1,
                'modified' => '2026-01-16 14:51:39',
                'created' => '2026-01-16 14:51:39',
            ],
        ];
        parent::init();
    }
}
