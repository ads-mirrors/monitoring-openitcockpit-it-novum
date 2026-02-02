<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WindowsAppsFixture
 */
class WindowsAppsFixture extends TestFixture
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
                'name' => 'Lorem ipsum dolor sit amet',
                'publisher' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-01-20 07:42:47',
                'modified' => '2026-01-20 07:42:47',
            ],
        ];
        parent::init();
    }
}
