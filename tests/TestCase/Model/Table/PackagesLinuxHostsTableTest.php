<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PackagesLinuxHostsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PackagesLinuxHostsTable Test Case
 */
class PackagesLinuxHostsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PackagesLinuxHostsTable
     */
    protected $PackagesLinuxHosts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.PackagesLinuxHosts',
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
        $config = $this->getTableLocator()->exists('PackagesLinuxHosts') ? [] : ['className' => PackagesLinuxHostsTable::class];
        $this->PackagesLinuxHosts = $this->getTableLocator()->get('PackagesLinuxHosts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->PackagesLinuxHosts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\PackagesLinuxHostsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\PackagesLinuxHostsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
