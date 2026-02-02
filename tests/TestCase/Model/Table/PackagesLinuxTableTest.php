<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PackagesLinuxTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PackagesLinuxTable Test Case
 */
class PackagesLinuxTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PackagesLinuxTable
     */
    protected $PackagesLinux;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.PackagesLinux',
        'app.Hosts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('PackagesLinux') ? [] : ['className' => PackagesLinuxTable::class];
        $this->PackagesLinux = $this->getTableLocator()->get('PackagesLinux', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->PackagesLinux);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\PackagesLinuxTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
