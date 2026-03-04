<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PackagesLinuxFixture
 */
class PackagesLinuxFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'packages_linux';
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
                'modified' => '2026-01-16 14:51:36',
                'created' => '2026-01-16 14:51:36',
            ],
        ];
        parent::init();
    }
}
