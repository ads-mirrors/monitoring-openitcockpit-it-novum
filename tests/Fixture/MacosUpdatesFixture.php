<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MacosUpdatesFixture
 */
class MacosUpdatesFixture extends TestFixture
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
                'description' => 'Lorem ipsum dolor sit amet',
                'version' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-01-26 07:59:50',
                'modified' => '2026-01-26 07:59:50',
            ],
        ];
        parent::init();
    }
}
